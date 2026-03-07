<?php
/**
 * Surprise! Store - Staff Management
 * إدارة الموظفين والصلاحيات
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

// Access: Admin or staff with staff_manage permission
if (!validateAdminSession() || !hasPermission('staff_manage')) {
    redirect('index');
}

$message = '';
$error = '';
$csrf_token = generateCSRFToken();
$editStaff = null;

// Get governorates list
$governorates = getIraqGovernorates();
$availablePermissions = getAvailablePermissions();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'خطأ في الأمان. أعد المحاولة.';
    } else {
        $action = $_POST['action'] ?? '';
        
        // Save staff (add/edit)
        if ($action === 'save') {
            $data = [
                'id' => intval($_POST['id'] ?? 0),
                'username' => sanitize($_POST['username'] ?? ''),
                'first_name' => sanitize($_POST['first_name'] ?? ''),
                'last_name' => sanitize($_POST['last_name'] ?? ''),
                'job_title' => sanitize($_POST['job_title'] ?? ''),
                'governorate' => sanitize($_POST['governorate'] ?? ''),
                'district' => sanitize($_POST['district'] ?? ''),
                'neighborhood' => sanitize($_POST['neighborhood'] ?? ''),
                'address' => sanitize($_POST['address'] ?? ''),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
                'permissions' => []
            ];
            
            // Collect permissions
            foreach ($availablePermissions as $key => $label) {
                if ($key !== 'staff') { // staff permission is admin-only
                    $data['permissions'][$key] = isset($_POST['perm_' . $key]);
                }
            }
            
            // Password handling
            if (empty($data['id'])) {
                // New staff - password required
                if (empty($_POST['password']) || strlen($_POST['password']) < 6) {
                    $error = 'كلمة المرور مطلوبة (6 أحرف على الأقل)';
                } elseif ($_POST['password'] !== $_POST['password_confirm']) {
                    $error = 'كلمة المرور وتأكيدها غير متطابقين';
                } else {
                    $data['password'] = $_POST['password'];
                }
            } else {
                // Edit - password optional
                if (!empty($_POST['password'])) {
                    if (strlen($_POST['password']) < 6) {
                        $error = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
                    } elseif ($_POST['password'] !== $_POST['password_confirm']) {
                        $error = 'كلمة المرور وتأكيدها غير متطابقين';
                    } else {
                        $data['password'] = $_POST['password'];
                    }
                }
            }
            
            // Validate username
            if (empty($error)) {
                if (empty($data['username']) || strlen($data['username']) < 3) {
                    $error = 'اسم المستخدم مطلوب (3 أحرف على الأقل)';
                } elseif (!isUsernameUnique($data['username'], $data['id'] ?: null)) {
                    $error = 'اسم المستخدم مستخدم من قبل';
                } elseif (empty($data['first_name']) || empty($data['last_name'])) {
                    $error = 'الاسم الأول والأخير مطلوبين';
                }
            }
            
            // Save
            if (empty($error)) {
                if (saveStaff($data)) {
                    $message = $data['id'] ? 'تم تحديث بيانات الموظف بنجاح' : 'تم إضافة الموظف بنجاح';
                } else {
                    // Check if table exists
                    try {
                        $tableCheck = db()->query("SHOW TABLES LIKE 'staff'");
                        if ($tableCheck->rowCount() == 0) {
                            $error = 'جدول الموظفين غير موجود! يرجى تشغيل migration_staff.sql في phpMyAdmin أولاً.';
                        } else {
                            $error = 'حدث خطأ أثناء الحفظ. تحقق من البيانات المدخلة.';
                        }
                    } catch (Exception $e) {
                        $error = 'خطأ في الاتصال بقاعدة البيانات: ' . $e->getMessage();
                    }
                }
            }
        }
        
        // Toggle active status
        if ($action === 'toggle_status') {
            $id = intval($_POST['staff_id'] ?? 0);
            $staff = getStaffById($id);
            if ($staff) {
                $staff['is_active'] = $staff['is_active'] ? 0 : 1;
                $staff['permissions'] = json_decode($staff['permissions'], true);
                if (saveStaff($staff)) {
                    $message = $staff['is_active'] ? 'تم تفعيل الحساب' : 'تم تعطيل الحساب';
                }
            }
        }
        
        // Delete staff
        if ($action === 'delete') {
            $id = intval($_POST['staff_id'] ?? 0);
            if (deleteStaff($id)) {
                $message = 'تم حذف الموظف';
            } else {
                $error = 'فشل في حذف الموظف';
            }
        }
        
        // Reset 2FA for staff
        if ($action === 'reset_2fa') {
            $id = intval($_POST['staff_id'] ?? 0);
            $staff = getStaffById($id);
            if ($staff && isFullAdmin()) {
                if (process2FAReset($staff['username'])) {
                    $message = '✅ تم إعادة تعيين 2FA للموظف: ' . htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) . ' — سيُطلب منه إعداد جديد عند الدخول';
                } else {
                    $error = 'فشل في إعادة تعيين 2FA';
                }
            } else {
                $error = 'صلاحيات غير كافية أو الموظف غير موجود';
            }
        }
        
        // Reset MY OWN 2FA (Super Admin)
        if ($action === 'reset_my_2fa' && isFullAdmin()) {
            $adminUsername = $_SESSION['admin_username'] ?? 'admin';
            if (process2FAReset($adminUsername)) {
                $message = '✅ تم إعادة تعيين 2FA للمدير — سيُطلب منه إعداد جديد عند الدخول';
            } else {
                $error = 'فشل في إعادة تعيين 2FA';
            }
        }
    }
}

// Edit mode
if (isset($_GET['edit'])) {
    $editStaff = getStaffById(intval($_GET['edit']));
    if ($editStaff) {
        $editStaff['permissions'] = json_decode($editStaff['permissions'], true) ?: [];
    }
}

// Get all staff
$allStaff = getAllStaff();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الموظفين - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .staff-form {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .permissions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            padding: 20px;
            background: linear-gradient(135deg, rgba(103, 58, 183, 0.05), rgba(156, 39, 176, 0.05));
            border-radius: var(--radius-md);
            border: 1px solid rgba(156, 39, 176, 0.1);
        }
        .permission-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: white;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }
        .permission-item:hover {
            border-color: var(--primary);
        }
        .permission-item input:checked + span {
            color: var(--primary);
            font-weight: 600;
        }
        .permission-item input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        .staff-table {
            width: 100%;
            border-collapse: collapse;
        }
        .staff-table th, .staff-table td {
            padding: 15px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        .staff-table th {
            background: var(--bg-main);
            font-weight: 600;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        .status-inactive {
            background: #ffebee;
            color: #c62828;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.85rem;
            border-radius: 8px;
        }
        @media (max-width: 768px) {
            .admin-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
                text-align: center;
            }
            .admin-title {
                font-size: 1.5rem;
            }
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            .permissions-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
                padding: 10px;
            }
            .permission-item {
                padding: 12px;
                font-size: 0.9rem;
                flex-direction: row !important;
                align-items: flex-start !important;
                text-align: right !important;
                gap: 10px;
                border-bottom: 1px solid rgba(156, 39, 176, 0.05);
            }
            .permission-item span {
                line-height: 1.4;
                word-break: break-word;
            }
            .permission-item input {
                flex-shrink: 0;
                margin-top: 4px;
            }

            /* Convert table to cards */
            .staff-table {
                display: block;
                border: none;
            }
            .staff-table thead {
                display: none;
            }
            .staff-table tbody {
                display: block;
            }
            .staff-table tr {
                display: block;
                background: white;
                border: 1px solid var(--gray-200);
                border-radius: 15px;
                margin-bottom: 20px;
                padding: 15px;
                box-shadow: var(--shadow-sm);
                position: relative;
            }
            .staff-table td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
                border-bottom: 1px solid var(--gray-100);
                text-align: right;
                font-size: 0.95rem;
            }
            .staff-table td:last-child {
                border-bottom: none;
                flex-direction: column;
                gap: 10px;
                padding-top: 15px;
                align-items: stretch;
            }
            .staff-table td::before {
                content: attr(data-label);
                font-weight: 700;
                color: var(--text-muted);
                font-size: 0.85rem;
                flex-shrink: 0;
                margin-left: 10px;
            }
            .staff-table td[data-label="الإجراءات"]::before {
                display: none;
            }
            .staff-table td strong {
                color: var(--primary);
            }
            .action-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                justify-content: center;
            }
            .action-buttons .btn {
                flex: 1;
                min-width: 100px;
                justify-content: center;
                text-align: center;
            }
            .action-buttons form {
                flex: 1;
                display: flex;
            }
            .action-buttons form button {
                width: 100%;
            }

            /* Form Buttons */
            .form-actions {
                flex-direction: row !important;
                gap: 10px;
            }
            .form-actions .btn {
                flex: 1;
                justify-content: center;
                padding: 12px 5px;
                font-size: 0.9rem;
                white-space: nowrap;
            }
        }

        @media (max-width: 480px) {
            .form-actions {
                flex-direction: column !important;
            }
            .form-actions .btn {
                width: 100%;
            }
            .permissions-grid {
                grid-template-columns: 1fr;
            }
            .permission-item {
                flex-direction: row;
                text-align: right;
                padding: 12px;
            }
        }
    </style>
</head>
<body class="admin-layout">
    <?php include 'includes/sidebar.php'; ?>
    
    <main class="admin-main">
        <div class="admin-header">
            <h1 class="admin-title">👥 إدارة الموظفين</h1>
            <?php if (!$editStaff): ?>
            <a href="?add=1" class="btn btn-primary">+ إضافة موظف جديد</a>
            <?php endif; ?>
        </div>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <!-- Add/Edit Form -->
        <?php if (isset($_GET['add']) || $editStaff): ?>
        <div class="staff-form">
            <h3 style="margin-bottom: 25px; color: var(--primary);">
                <?= $editStaff ? '✏️ تعديل موظف: ' . htmlspecialchars($editStaff['first_name'] . ' ' . $editStaff['last_name']) : '➕ إضافة موظف جديد' ?>
            </h3>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" value="<?= $editStaff['id'] ?? '' ?>">
                
                <!-- Basic Info -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">اسم المستخدم (للدخول) *</label>
                        <input type="text" name="username" class="form-control" required minlength="3"
                               value="<?= htmlspecialchars($editStaff['username'] ?? '') ?>"
                               placeholder="مثال: ahmed123">
                    </div>
                    <div class="form-group">
                        <label class="form-label">المسمى الوظيفي</label>
                        <input type="text" name="job_title" class="form-control"
                               value="<?= htmlspecialchars($editStaff['job_title'] ?? '') ?>"
                               placeholder="مثال: موظف مبيعات، مدير مخزن...">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">الاسم الأول *</label>
                        <input type="text" name="first_name" class="form-control" required
                               value="<?= htmlspecialchars($editStaff['first_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">الاسم الأخير *</label>
                        <input type="text" name="last_name" class="form-control" required
                               value="<?= htmlspecialchars($editStaff['last_name'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">كلمة المرور <?= $editStaff ? '(اتركها فارغة لعدم التغيير)' : '*' ?></label>
                        <input type="password" name="password" class="form-control" <?= $editStaff ? '' : 'required' ?> minlength="6"
                               placeholder="6 أحرف على الأقل">
                    </div>
                    <div class="form-group">
                        <label class="form-label">تأكيد كلمة المرور</label>
                        <input type="password" name="password_confirm" class="form-control"
                               placeholder="أعد كتابة كلمة المرور">
                    </div>
                </div>
                
                <!-- Address -->
                <h4 style="margin: 25px 0 15px; color: #666;">📍 العنوان</h4>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">المحافظة</label>
                        <select name="governorate" class="form-control">
                            <option value="">اختر المحافظة</option>
                            <?php foreach ($governorates as $gov): ?>
                            <option value="<?= $gov ?>" <?= ($editStaff['governorate'] ?? '') === $gov ? 'selected' : '' ?>><?= $gov ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">المنطقة</label>
                        <input type="text" name="district" class="form-control"
                               value="<?= htmlspecialchars($editStaff['district'] ?? '') ?>"
                               placeholder="مثال: الكرادة، المنصور...">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">الحي</label>
                        <input type="text" name="neighborhood" class="form-control"
                               value="<?= htmlspecialchars($editStaff['neighborhood'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">العنوان التفصيلي</label>
                        <textarea name="address" class="form-control" rows="2"
                                  placeholder="رقم المنزل، اسم الشارع، معلم قريب..."><?= htmlspecialchars($editStaff['address'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <!-- Status -->
                <div class="form-group" style="margin: 20px 0;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="is_active" <?= ($editStaff['is_active'] ?? 1) ? 'checked' : '' ?>
                               style="width: 20px; height: 20px; accent-color: var(--primary);">
                        <span style="font-weight: 600;">حساب نشط (يمكنه تسجيل الدخول)</span>
                    </label>
                </div>
                
                <!-- Permissions -->
                <h4 style="margin: 25px 0 15px; color: #9c27b0;">🔐 الصلاحيات</h4>
                <p style="color: #666; margin-bottom: 15px; font-size: 0.9rem;">حدد الأقسام التي يستطيع الموظف الوصول إليها:</p>
                
                <div class="permissions-grid">
                    <?php foreach ($availablePermissions as $key => $label): ?>
                    <?php if ($key !== 'staff'): // إدارة الموظفين للمدير فقط ?>
                    <label class="permission-item">
                        <input type="checkbox" name="perm_<?= $key ?>" 
                               <?= isset($editStaff['permissions'][$key]) && $editStaff['permissions'][$key] ? 'checked' : '' ?>>
                        <span><?= $label ?></span>
                    </label>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="form-actions" style="margin-top: 30px; display: flex; gap: 15px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editStaff ? '💾 حفظ التغييرات' : '➕ إضافة الموظف' ?>
                    </button>
                    <a href="staff" class="btn btn-outline">إلغاء</a>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <?php if (isFullAdmin()): ?>
        <!-- Admin's Own 2FA -->
        <div class="admin-card" style="margin-bottom: 20px; border: 2px solid rgba(233, 30, 140, 0.15);">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px; padding: 20px;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <span style="font-size: 2rem;">👑</span>
                    <div>
                        <div style="font-weight: 700; font-size: 1.05rem;">حساب المدير الرئيسي (<?= htmlspecialchars($_SESSION['admin_username'] ?? 'admin') ?>)</div>
                        <div style="font-size: 0.85rem; color: #888; margin-top: 4px;">Google Authenticator — إعادة تعيين المصادقة الثنائية للمدير</div>
                    </div>
                </div>
                <button type="button" class="btn btn-sm" style="background: #fff3e0; color: #e65100; font-weight: 600; padding: 10px 20px;" onclick="openResetMyFA()">🔑 إعادة تعيين 2FA للمدير</button>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Staff List -->
        <div class="admin-card">
            <div class="admin-card-header">
                <h3 class="admin-card-title">📋 قائمة الموظفين (<?= count($allStaff) ?>)</h3>
            </div>
            
            <?php if (empty($allStaff)): ?>
            <div style="padding: 50px; text-align: center; color: #888;">
                <div style="font-size: 3rem; margin-bottom: 15px;">👥</div>
                <p>لا يوجد موظفين حالياً</p>
                <a href="?add=1" class="btn btn-primary" style="margin-top: 15px;">+ إضافة أول موظف</a>
            </div>
            <?php else: ?>
            <div style="overflow-x: auto;">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>اسم المستخدم</th>
                            <th>الاسم</th>
                            <th>الوظيفة</th>
                            <th>الحالة</th>
                            <th>آخر دخول</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allStaff as $staff): ?>
                        <tr>
                            <td data-label="اسم المستخدم"><strong><?= htmlspecialchars($staff['username']) ?></strong></td>
                            <td data-label="الاسم"><?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name']) ?></td>
                            <td data-label="الوظيفة"><?= htmlspecialchars($staff['job_title'] ?: '-') ?></td>
                            <td data-label="الحالة">
                                <span class="status-badge <?= $staff['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $staff['is_active'] ? '✓ نشط' : '✗ معطل' ?>
                                </span>
                            </td>
                            <td data-label="آخر دخول" style="font-size: 0.85rem; color: #666;">
                                <?= $staff['last_login'] ? formatDateTime($staff['last_login'], 'short') : 'لم يسجل دخول' ?>
                            </td>
                            <td data-label="الإجراءات">
                                <div class="action-buttons">
                                    <a href="?edit=<?= $staff['id'] ?>" class="btn btn-sm btn-outline">✏️ تعديل</a>
                                    <form method="POST" style="display: contents;" onsubmit="return confirm('<?= $staff['is_active'] ? 'تعطيل' : 'تفعيل' ?> هذا الحساب؟')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                        <button type="submit" class="btn btn-sm <?= $staff['is_active'] ? 'btn-outline' : 'btn-primary' ?>">
                                            <?= $staff['is_active'] ? '🔒 تعطيل' : '🔓 تفعيل' ?>
                                        </button>
                                    </form>
                                    <form method="POST" style="display: contents;" onsubmit="return confirm('حذف هذا الموظف نهائياً؟')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="staff_id" value="<?= $staff['id'] ?>">
                                        <button type="submit" class="btn btn-sm" style="background: #ffebee; color: #c62828;">🗑️ حذف</button>
                                    </form>
                                    <?php if (isFullAdmin()): ?>
                                    <button type="button" class="btn btn-sm" style="background: #fff3e0; color: #e65100;" onclick="openReset2FA(<?= $staff['id'] ?>, '<?= htmlspecialchars($staff['first_name'] . ' ' . $staff['last_name'], ENT_QUOTES) ?>')">🔑 Reset 2FA</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
    
    <!-- Reset 2FA Modal -->
    <div id="reset2faModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); align-items:center; justify-content:center;">
        <div style="background:white; border-radius:16px; padding:30px; max-width:440px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.3); text-align:center; animation:modalIn 0.25s ease;">
            <div style="font-size:3rem; margin-bottom:15px;">🔑</div>
            <h3 style="margin-bottom:10px; color:#e65100;">إعادة تعيين 2FA</h3>
            <p style="color:#666; margin-bottom:8px; font-size:0.95rem;" id="reset2faName"></p>
            <div style="background:#fff3e0; border:1px solid #ffe0b2; border-radius:10px; padding:15px; margin:15px 0; text-align:right; font-size:0.85rem; color:#e65100; line-height:1.7;">
                ⚠️ <strong>تحذير:</strong> هذا الإجراء سيقوم بـ:
                <ul style="margin:8px 15px 0; padding:0;">
                    <li>إلغاء تفعيل Google Authenticator</li>
                    <li>حذف المفتاح السري القديم</li>
                    <li>إزالة جميع الأجهزة الموثوقة</li>
                    <li>تسجيل خروج من جميع الجلسات</li>
                </ul>
            </div>
            <p style="color:#888; font-size:0.8rem; margin-bottom:20px;">سيُجبر المستخدم على إعداد 2FA جديد عند تسجيل الدخول التالي</p>
            <form method="POST" id="reset2faForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" value="reset_2fa">
                <input type="hidden" name="staff_id" id="reset2faStaffId" value="">
                <div style="display:flex; gap:10px; justify-content:center;">
                    <button type="button" onclick="closeReset2FA()" style="padding:10px 25px; border:1px solid #ddd; border-radius:10px; background:white; cursor:pointer; font-size:0.9rem;">إلغاء</button>
                    <button type="submit" style="padding:10px 25px; border:none; border-radius:10px; background:#e65100; color:white; cursor:pointer; font-size:0.9rem; font-weight:600;">✅ تأكيد إعادة التعيين</button>
                </div>
            </form>
        </div>
    </div>
    <style>@keyframes modalIn{from{opacity:0;transform:scale(0.9)}to{opacity:1;transform:scale(1)}}</style>
    <script>
    function openReset2FA(staffId, staffName) {
        document.getElementById('reset2faStaffId').value = staffId;
        document.getElementById('reset2faName').textContent = 'الموظف: ' + staffName;
        document.getElementById('reset2faModal').style.display = 'flex';
    }
    function closeReset2FA() {
        document.getElementById('reset2faModal').style.display = 'none';
    }
    document.getElementById('reset2faModal').addEventListener('click', function(e) {
        if (e.target === this) closeReset2FA();
    });
    
    function openResetMyFA() {
        document.getElementById('reset2faStaffId').value = 'my_admin';
        document.getElementById('reset2faName').textContent = 'حساب المدير الرئيسي';
        document.getElementById('reset2faModal').style.display = 'flex';
        // Change form action for admin self-reset
        document.querySelector('#reset2faForm input[name="action"]').value = 'reset_my_2fa';
    }
    
    var origOpenReset2FA = openReset2FA;
    openReset2FA = function(staffId, staffName) {
        document.querySelector('#reset2faForm input[name="action"]').value = 'reset_2fa';
        origOpenReset2FA(staffId, staffName);
    };
    </script>
    
    <script src="<?= av('js/admin.js') ?>"></script>
</body>
</html>
