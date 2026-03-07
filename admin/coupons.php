<?php
/**
 * Coupons Management - إدارة الكوبونات
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

if (!validateAdminSession()) {
    redirect('login');
}

$message = '';
$error = '';
$csrf_token = generateCSRFToken();

// Get all products for selection
$allProducts = getProducts();

// Handle Create Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coupon'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $code = strtoupper(trim($_POST['code']));
        $discountType = $_POST['discount_type'];
        $discountValue = floatval($_POST['discount_value']);
        $minOrder = intval(isset($_POST['min_order']) ? $_POST['min_order'] : 0);
        $maxDiscount = intval(isset($_POST['max_discount']) ? $_POST['max_discount'] : 0);
        $maxUses = intval(isset($_POST['max_uses']) ? $_POST['max_uses'] : 0);
        $expiresAt = $_POST['expires_at'] ? $_POST['expires_at'] : null;
        $applyTo = isset($_POST['apply_to']) ? $_POST['apply_to'] : 'all';
        $productIds = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
        
        // Validation
        if (strlen($code) < 3) {
            $error = 'كود الخصم يجب أن يكون 3 أحرف على الأقل';
        } elseif ($discountValue <= 0) {
            $error = 'قيمة الخصم يجب أن تكون أكبر من صفر';
        } elseif (getCouponByCode($code)) {
            $error = 'هذا الكود موجود مسبقاً';
        } else {
            $stmt = db()->prepare("
                INSERT INTO coupons (code, discount_type, discount_value, min_order, max_discount, max_uses, expires_at, apply_to, product_ids, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $result = $stmt->execute([
                $code,
                $discountType,
                $discountValue,
                $minOrder,
                $maxDiscount,
                $maxUses,
                $expiresAt,
                $applyTo,
                json_encode($productIds)
            ]);
            
            if ($result) {
                $message = 'تم إنشاء الكوبون بنجاح: ' . $code;
            } else {
                $error = 'فشل في إنشاء الكوبون';
            }
        }
    }
}

// Handle Update Coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_coupon'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $id = intval($_POST['coupon_id']);
        $discountType = $_POST['discount_type'];
        $discountValue = floatval($_POST['discount_value']);
        $minOrder = intval(isset($_POST['min_order']) ? $_POST['min_order'] : 0);
        $maxDiscount = intval(isset($_POST['max_discount']) ? $_POST['max_discount'] : 0);
        $maxUses = intval(isset($_POST['max_uses']) ? $_POST['max_uses'] : 0);
        $expiresAt = $_POST['expires_at'] ? $_POST['expires_at'] : null;
        $applyTo = isset($_POST['apply_to']) ? $_POST['apply_to'] : 'all';
        $productIds = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $stmt = db()->prepare("
            UPDATE coupons SET 
                discount_type = ?, discount_value = ?, min_order = ?,
                max_discount = ?, max_uses = ?, expires_at = ?,
                apply_to = ?, product_ids = ?, is_active = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([
            $discountType, $discountValue, $minOrder,
            $maxDiscount, $maxUses, $expiresAt,
            $applyTo, json_encode($productIds), $isActive, $id
        ]);
        
        if ($result) {
            $message = 'تم تحديث الكوبون بنجاح';
        } else {
            $error = 'فشل في تحديث الكوبون';
        }
    }
}

// Handle Delete Coupon
if (isset($_GET['delete']) && validateCSRFToken(isset($_GET['token']) ? $_GET['token'] : '')) {
    $id = intval($_GET['delete']);
    $stmt = db()->prepare("DELETE FROM coupons WHERE id = ?");
    if ($stmt->execute([$id])) {
        $message = 'تم حذف الكوبون بنجاح';
    }
}

// Handle Toggle Status
if (isset($_GET['toggle']) && validateCSRFToken(isset($_GET['token']) ? $_GET['token'] : '')) {
    $id = intval($_GET['toggle']);
    $stmt = db()->prepare("UPDATE coupons SET is_active = NOT is_active WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: coupons');
    exit;
}

// Get all coupons
$coupons = getCoupons();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الكوبونات - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .coupon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .coupon-card {
            background: white;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 2px solid transparent;
            transition: var(--transition);
        }
        
        .coupon-card:hover {
            box-shadow: var(--shadow-md);
        }
        
        .coupon-card.inactive {
            opacity: 0.6;
            border-color: #ddd;
        }
        
        .coupon-header {
            background: var(--bg-hero);
            color: white;
            padding: 20px;
            position: relative;
        }
        
        .coupon-card.inactive .coupon-header {
            background: #999;
        }
        
        .coupon-code {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 2px;
            font-family: monospace;
        }
        
        .coupon-discount {
            position: absolute;
            top: 15px;
            left: 15px;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 700;
        }
        
        .coupon-body {
            padding: 20px;
        }
        
        .coupon-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 15px;
        }
        
        .coupon-info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: var(--text-light);
        }
        
        .coupon-info-item span:first-child {
            font-size: 1.1rem;
        }
        
        .coupon-stats {
            display: flex;
            justify-content: space-between;
            padding: 15px;
            background: #f8f9fa;
            border-radius: var(--radius-md);
            margin-bottom: 15px;
        }
        
        .coupon-stat {
            text-align: center;
        }
        
        .coupon-stat-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .coupon-stat-label {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .coupon-actions {
            display: flex;
            gap: 10px;
        }
        
        .coupon-actions .btn {
            flex: 1;
            padding: 10px;
            font-size: 0.85rem;
        }
        
        .coupon-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
        }
        
        .badge-active {
            background: #e8f5e9;
            color: #2e7d32;
        }
        
        .badge-expired {
            background: #ffebee;
            color: #c62828;
        }
        
        .badge-limited {
            background: #fff3e0;
            color: #e65100;
        }
        
        /* Create Form */
        .create-form {
            background: white;
            border-radius: var(--radius-lg);
            padding: 30px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 30px;
        }
        
        .create-form h3 {
            margin-bottom: 25px;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .discount-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .discount-type-btn {
            flex: 1;
            padding: 15px;
            border: 2px solid #eee;
            border-radius: var(--radius-md);
            background: white;
            cursor: pointer;
            text-align: center;
            transition: var(--transition);
        }
        
        .discount-type-btn:hover {
            border-color: var(--primary);
        }
        
        .discount-type-btn.active {
            border-color: var(--primary);
            background: rgba(233, 30, 140, 0.05);
        }
        
        .discount-type-btn input {
            display: none;
        }
        
        .discount-type-icon {
            font-size: 2rem;
            margin-bottom: 8px;
        }
        
        .discount-type-label {
            font-weight: 700;
            font-size: 0.95rem;
        }
        
        /* Preview */
        .preview-box {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            border-radius: var(--radius-md);
            padding: 20px;
            margin-top: 20px;
            display: none;
        }
        
        .preview-box.show {
            display: block;
        }
        
        .preview-title {
            font-weight: 700;
            color: #2e7d32;
            margin-bottom: 15px;
        }
        
        .preview-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #a5d6a7;
        }
        
        .preview-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 1.1rem;
        }
        
        /* Product Selection */
        .product-select-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 10px;
            max-height: 300px;
            overflow-y: auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: var(--radius-md);
            margin-top: 10px;
        }
        
        .product-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px;
            background: white;
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .product-checkbox:hover {
            background: rgba(233, 30, 140, 0.05);
        }
        
        .product-checkbox input:checked + span {
            color: var(--primary);
            font-weight: 600;
        }
        
        /* Date Input Styling */
        .date-input-wrapper {
            position: relative;
        }
        
        .date-input-wrapper input[type="date"] {
            padding-right: 45px;
            cursor: pointer;
        }
        
        .date-input-wrapper::before {
            content: '📅';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
            pointer-events: none;
        }
        
        input[type="date"] {
            position: relative;
            background: linear-gradient(135deg, #fff, #fafafa);
            border: 2px solid #e8e8e8;
            border-radius: var(--radius-md);
            padding: 14px 18px;
            font-family: inherit;
            font-size: 1rem;
            transition: var(--transition);
            color: var(--text-dark);
        }
        
        input[type="date"]:hover {
            border-color: var(--primary);
            box-shadow: 0 2px 8px rgba(233, 30, 140, 0.1);
        }
        
        input[type="date"]:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(233, 30, 140, 0.1);
            background: white;
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator {
            cursor: pointer;
            padding: 5px;
            border-radius: 5px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            opacity: 0.9;
            transition: var(--transition);
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        /* Expiry Date Badge Style */
        .expiry-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            border-radius: 25px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .expiry-badge.expires-soon {
            background: linear-gradient(135deg, #fff3e0, #ffe0b2);
            color: #e65100;
        }
        
        .expiry-badge.expires-later {
            background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
            color: #2e7d32;
        }
        
        .expiry-badge.no-expiry {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            color: #1565c0;
        }
        
        @media (max-width: 768px) {
            .coupon-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .discount-type-selector {
                flex-direction: column;
            }
            
            .coupon-info {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">🎟️ إدارة الكوبونات</h1>
            </div>

            <div class="admin-content">
                <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
                <?php endif; ?>
                
                <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
                <?php endif; ?>

                <!-- Create New Coupon -->
                <div class="create-form">
                    <h3>🆕 إنشاء كوبون جديد</h3>
                    
                    <form method="POST" id="couponForm">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="create_coupon" value="1">
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">كود الخصم *</label>
                                <input type="text" name="code" id="couponCode" class="form-control" 
                                       placeholder="مثال: SUMMER20" required minlength="3" maxlength="20"
                                       style="text-transform: uppercase; font-family: monospace; font-size: 1.1rem; letter-spacing: 2px;">
                                <small style="color: var(--text-muted);">3-20 حرف، أرقام أو حروف إنجليزية</small>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">تاريخ الانتهاء</label>
                                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                    <select name="expires_day" id="expiresDay" class="form-control" style="flex: 1; min-width: 80px;">
                                        <option value="">اليوم</option>
                                        <?php for ($d = 1; $d <= 31; $d++): ?>
                                        <option value="<?= $d ?>"><?= $d ?></option>
                                        <?php endfor; ?>
                                    </select>
                                    <select name="expires_month" id="expiresMonth" class="form-control" style="flex: 1.5; min-width: 100px;">
                                        <option value="">الشهر</option>
                                        <?php 
                                        $months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                                                   'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
                                        foreach ($months as $i => $month): ?>
                                        <option value="<?= $i + 1 ?>"><?= $month ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select name="expires_year" id="expiresYear" class="form-control" style="flex: 1; min-width: 90px;">
                                        <option value="">السنة</option>
                                        <?php 
                                        $currentYear = date('Y');
                                        for ($y = $currentYear; $y <= $currentYear + 5; $y++): ?>
                                        <option value="<?= $y ?>"><?= $y ?></option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <small style="color: var(--text-muted);">اتركها فارغة للكوبون الدائم</small>
                                <input type="hidden" name="expires_at" id="expiresAt">
                            </div>
                        </div>
                        
                        <label class="form-label">نوع الخصم *</label>
                        <div class="discount-type-selector">
                            <label class="discount-type-btn active" onclick="selectDiscountType(this)">
                                <input type="radio" name="discount_type" value="percentage" checked>
                                <div class="discount-type-icon">%</div>
                                <div class="discount-type-label">نسبة مئوية</div>
                            </label>
                            <label class="discount-type-btn" onclick="selectDiscountType(this)">
                                <input type="radio" name="discount_type" value="fixed">
                                <div class="discount-type-icon">💵</div>
                                <div class="discount-type-label">مبلغ ثابت</div>
                            </label>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">قيمة الخصم *</label>
                                <input type="number" name="discount_value" id="discountValue" class="form-control" 
                                       required min="1" step="0.01" placeholder="مثال: 10">
                                <small id="discountHint" style="color: var(--text-muted);">النسبة المئوية (1-100)</small>
                            </div>
                            
                            <div class="form-group" id="maxDiscountGroup">
                                <label class="form-label">الحد الأقصى للخصم</label>
                                <input type="number" name="max_discount" class="form-control" 
                                       min="0" placeholder="0 = بدون حد">
                                <small style="color: var(--text-muted);">للنسبة المئوية فقط</small>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">الحد الأدنى للطلب</label>
                                <input type="number" name="min_order" class="form-control" 
                                       min="0" value="0" placeholder="0 = بدون حد أدنى">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">الحد الأقصى للاستخدام</label>
                                <input type="number" name="max_uses" class="form-control" 
                                       min="0" value="0" placeholder="0 = غير محدود">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">تطبيق على</label>
                            <select name="apply_to" id="applyTo" class="form-control" onchange="toggleProductSelect()">
                                <option value="all">جميع المنتجات</option>
                                <option value="specific">منتجات محددة</option>
                            </select>
                        </div>
                        
                        <div id="productSelectSection" style="display: none;">
                            <label class="form-label">اختر المنتجات</label>
                            <div class="product-select-grid">
                                <?php foreach ($allProducts as $product): ?>
                                <label class="product-checkbox">
                                    <input type="checkbox" name="product_ids[]" value="<?= $product['id'] ?>">
                                    <span><?= htmlspecialchars($product['name']) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Preview Box -->
                        <div class="preview-box" id="previewBox">
                            <div class="preview-title">👁️ معاينة الخصم (مثال: طلب بقيمة 50,000 د.ع)</div>
                            <div class="preview-row">
                                <span>السعر الأصلي:</span>
                                <span>50,000 د.ع</span>
                            </div>
                            <div class="preview-row">
                                <span>الخصم:</span>
                                <span id="previewDiscount" style="color: #e91e8c;">-0 د.ع</span>
                            </div>
                            <div class="preview-row">
                                <span>السعر النهائي:</span>
                                <span id="previewFinal" style="color: #2e7d32;">50,000 د.ع</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg" style="margin-top: 25px;">
                            ✨ إنشاء الكوبون
                        </button>
                    </form>
                </div>

                <!-- Existing Coupons -->
                <h3 style="margin-bottom: 20px; color: var(--text-dark);">📋 الكوبونات الحالية (<?= count($coupons) ?>)</h3>
                
                <?php if (empty($coupons)): ?>
                <div class="empty-state" style="background: white; border-radius: var(--radius-lg);">
                    <div class="empty-icon">🎟️</div>
                    <h2 class="empty-title">لا توجد كوبونات</h2>
                    <p class="empty-text">أنشئ أول كوبون خصم من النموذج أعلاه</p>
                </div>
                <?php else: ?>
                <div class="coupon-grid">
                    <?php foreach ($coupons as $coupon): 
                        $isExpired = $coupon['expires_at'] && strtotime($coupon['expires_at']) < time();
                        $isLimited = $coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses'];
                    ?>
                    <div class="coupon-card <?= (!$coupon['is_active'] || $isExpired || $isLimited) ? 'inactive' : '' ?>">
                        <div class="coupon-header">
                            <div class="coupon-code"><?= htmlspecialchars($coupon['code']) ?></div>
                            <div class="coupon-discount">
                                <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                    <?= $coupon['discount_value'] ?>%
                                <?php else: ?>
                                    <?= number_format($coupon['discount_value']) ?> د.ع
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="coupon-body">
                            <div style="margin-bottom: 15px;">
                                <?php if (!$coupon['is_active']): ?>
                                    <span class="coupon-badge badge-expired">معطل</span>
                                <?php elseif ($isExpired): ?>
                                    <span class="coupon-badge badge-expired">منتهي الصلاحية</span>
                                <?php elseif ($isLimited): ?>
                                    <span class="coupon-badge badge-limited">وصل للحد الأقصى</span>
                                <?php else: ?>
                                    <span class="coupon-badge badge-active">نشط</span>
                                <?php endif; ?>
                                
                                <?php if (isset($coupon['apply_to']) && $coupon['apply_to'] === 'specific'): ?>
                                    <span class="coupon-badge" style="background: #e3f2fd; color: #1565c0;">منتجات محددة</span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="coupon-info">
                                <div class="coupon-info-item">
                                    <span>🛒</span>
                                    <span>الحد الأدنى: <?= $coupon['min_order'] > 0 ? number_format($coupon['min_order']) . ' د.ع' : 'لا يوجد' ?></span>
                                </div>
                                <?php 
                                    $expiryClass = 'no-expiry';
                                    $expiryIcon = '♾️';
                                    $expiryText = 'كوبون دائم';
                                    
                                    if ($coupon['expires_at']) {
                                        $daysLeft = floor((strtotime($coupon['expires_at']) - time()) / 86400);
                                        if ($daysLeft < 0) {
                                            $expiryClass = 'expires-soon';
                                            $expiryIcon = '⚠️';
                                            $expiryText = 'منتهي الصلاحية';
                                        } elseif ($daysLeft <= 7) {
                                            $expiryClass = 'expires-soon';
                                            $expiryIcon = '⏰';
                                            $expiryText = 'ينتهي خلال ' . $daysLeft . ' أيام';
                                        } elseif ($daysLeft <= 30) {
                                            $expiryClass = 'expires-later';
                                            $expiryIcon = '📅';
                                            $expiryText = 'ينتهي: ' . formatDateTime($coupon['expires_at'], 'date');
                                        } else {
                                            $expiryClass = 'expires-later';
                                            $expiryIcon = '✅';
                                            $expiryText = 'ينتهي: ' . formatDateTime($coupon['expires_at'], 'date');
                                        }
                                    }
                                ?>
                                <div class="coupon-info-item" style="grid-column: span 2;">
                                    <span class="expiry-badge <?= $expiryClass ?>"><?= $expiryIcon ?> <?= $expiryText ?></span>
                                </div>
                                <?php if ($coupon['discount_type'] === 'percentage' && $coupon['max_discount'] > 0): ?>
                                <div class="coupon-info-item">
                                    <span>🔒</span>
                                    <span>حد الخصم: <?= number_format($coupon['max_discount']) ?> د.ع</span>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="coupon-stats">
                                <div class="coupon-stat">
                                    <div class="coupon-stat-value"><?= $coupon['used_count'] ?></div>
                                    <div class="coupon-stat-label">استخدام</div>
                                </div>
                                <div class="coupon-stat">
                                    <div class="coupon-stat-value"><?= $coupon['max_uses'] > 0 ? $coupon['max_uses'] : '∞' ?></div>
                                    <div class="coupon-stat-label">الحد الأقصى</div>
                                </div>
                                <div class="coupon-stat">
                                    <div class="coupon-stat-value"><?= $coupon['max_uses'] > 0 ? max(0, $coupon['max_uses'] - $coupon['used_count']) : '∞' ?></div>
                                    <div class="coupon-stat-label">متبقي</div>
                                </div>
                            </div>
                            
                            <div class="coupon-actions">
                                <a href="?toggle=<?= $coupon['id'] ?>&token=<?= $csrf_token ?>" class="btn <?= $coupon['is_active'] ? 'btn-outline' : 'btn-primary' ?>">
                                    <?= $coupon['is_active'] ? '⏸️ تعطيل' : '▶️ تفعيل' ?>
                                </a>
                                <button type="button" class="btn-delete-full"
                                   data-delete-type="coupon"
                                   data-delete-id="<?= $coupon['id'] ?>"
                                   data-delete-name="<?= htmlspecialchars($coupon['code']) ?>"
                                   data-delete-token="<?= $csrf_token ?>">
                                    🗑️ حذف
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?= av('js/admin.js') ?>"></script>
    <script>
        function selectDiscountType(element) {
            document.querySelectorAll('.discount-type-btn').forEach(btn => btn.classList.remove('active'));
            element.classList.add('active');
            
            const isPercentage = element.querySelector('input').value === 'percentage';
            document.getElementById('maxDiscountGroup').style.display = isPercentage ? 'block' : 'none';
            document.getElementById('discountHint').textContent = isPercentage ? 'النسبة المئوية (1-100)' : 'المبلغ بالدينار العراقي';
            
            updatePreview();
        }
        
        function toggleProductSelect() {
            const applyTo = document.getElementById('applyTo').value;
            document.getElementById('productSelectSection').style.display = applyTo === 'specific' ? 'block' : 'none';
        }
        
        function updatePreview() {
            const discountValue = parseFloat(document.getElementById('discountValue').value) || 0;
            const discountType = document.querySelector('input[name="discount_type"]:checked').value;
            const maxDiscount = parseFloat(document.querySelector('input[name="max_discount"]').value) || 0;
            
            const samplePrice = 50000;
            let discount = 0;
            
            if (discountType === 'percentage') {
                discount = (samplePrice * discountValue) / 100;
                if (maxDiscount > 0 && discount > maxDiscount) {
                    discount = maxDiscount;
                }
            } else {
                discount = discountValue;
            }
            
            const finalPrice = Math.max(0, samplePrice - discount);
            
            document.getElementById('previewDiscount').textContent = '-' + new Intl.NumberFormat('en-US').format(discount) + ' د.ع';
            document.getElementById('previewFinal').textContent = new Intl.NumberFormat('en-US').format(finalPrice) + ' د.ع';
            
            document.getElementById('previewBox').classList.toggle('show', discountValue > 0);
        }
        
        // Event listeners
        document.getElementById('discountValue').addEventListener('input', updatePreview);
        document.querySelector('input[name="max_discount"]').addEventListener('input', updatePreview);
        
        // Code input uppercase
        document.getElementById('couponCode').addEventListener('input', function() {
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        });
        
        // Date selects - combine into hidden field
        function updateExpiryDate() {
            const day = document.getElementById('expiresDay').value;
            const month = document.getElementById('expiresMonth').value;
            const year = document.getElementById('expiresYear').value;
            
            if (day && month && year) {
                // Format: YYYY-MM-DD
                const formattedDate = `${year}-${month.toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}`;
                document.getElementById('expiresAt').value = formattedDate;
            } else {
                document.getElementById('expiresAt').value = '';
            }
        }
        
        document.getElementById('expiresDay').addEventListener('change', updateExpiryDate);
        document.getElementById('expiresMonth').addEventListener('change', updateExpiryDate);
        document.getElementById('expiresYear').addEventListener('change', updateExpiryDate);
        
        // Also update on form submit
        document.getElementById('couponForm').addEventListener('submit', updateExpiryDate);
    </script>
</body>
</html>
