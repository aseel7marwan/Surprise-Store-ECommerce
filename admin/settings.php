<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

// Settings: Admin or staff with settings_manage permission
if (!validateAdminSession() || !hasPermission('settings_manage')) {
    redirect('index');
}

$settings = getSettings();
$message = '';
$error = '';
$passwordMessage = '';
$passwordError = '';
$csrf_token = generateCSRFToken();

// Handle Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $telegramUsername = sanitize(trim($_POST['telegram_username']));
        $instagramUsername = sanitize(trim($_POST['instagram_username']));
        $contactText = sanitize(trim($_POST['contact_text']));
        
        $newSettings = [
            // Social Media Settings
            'telegram_username' => $telegramUsername,
            'instagram_username' => $instagramUsername,
            'contact_text' => $contactText ?: 'تواصل معنا',
            // Build full URLs
            'telegram_url' => $telegramUsername ? 'https://t.me/' . $telegramUsername : '',
            'instagram_url' => $instagramUsername ? 'https://instagram.com/' . $instagramUsername : '',
            // Keep legacy support
            'telegram_order_username' => $telegramUsername,
            'telegram_order_dm' => $telegramUsername ? 'https://t.me/' . $telegramUsername : '',
            // Other settings
            'delivery_price' => intval($_POST['delivery_price']),
            'privacy_policy' => isset($_POST['privacy_policy']) ? $_POST['privacy_policy'] : '',
            'terms' => isset($_POST['terms']) ? $_POST['terms'] : ''
        ];
        
        if (saveSettings($newSettings)) {
            $message = 'تم حفظ الإعدادات بنجاح! التغييرات تنعكس فوراً في كل صفحات الموقع.';
            $settings = getSettings();
        } else {
            $error = 'فشل في حفظ الإعدادات';
        }
    }
}

// Handle Payment Methods Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment_methods'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $paymentSettings = [];
        $enabledMethods = isset($_POST['payment_enabled']) ? $_POST['payment_enabled'] : [];
        
        foreach (getPaymentMethods() as $id => $method) {
            $paymentSettings[$id] = [
                'enabled' => in_array($id, $enabledMethods),
                'is_default' => false // لا يوجد افتراضي
            ];
        }
        
        if (savePaymentMethodsSettings($paymentSettings)) {
            $message = 'تم حفظ إعدادات طرق الدفع بنجاح';
        } else {
            $error = 'فشل في حفظ إعدادات طرق الدفع';
        }
    }
}

// Load admin credentials from file
$credentialsFile = ROOT_PATH . 'data/admin_credentials.php';
$adminCredentials = file_exists($credentialsFile) 
    ? include $credentialsFile 
    : ['username' => 'admin', 'password_hash' => 'YOUR_PASSWORD_HASH_HERE'];

// Handle Password Change (only original admin can change admin credentials)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password']) && isAdmin()) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $newUsername = trim(isset($_POST['new_username']) ? $_POST['new_username'] : '');
        $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
        
        // Verify current password using password_verify
        if (!password_verify($currentPassword, $adminCredentials['password_hash'])) {
            $passwordError = 'كلمة المرور الحالية غير صحيحة';
        } elseif (empty($newUsername) || strlen($newUsername) < 3) {
            $passwordError = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        } elseif (!empty($newPassword) && strlen($newPassword) < 6) {
            $passwordError = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل';
        } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
            $passwordError = 'كلمة المرور الجديدة غير متطابقة';
        } else {
            // Generate new hash if password changed
            $finalHash = !empty($newPassword) 
                ? password_hash($newPassword, PASSWORD_DEFAULT) 
                : $adminCredentials['password_hash'];
            
            $content = "<?php\n";
            $content .= "/**\n";
            $content .= " * ملف بيانات الأدمن - محمي بالهاش\n";
            $content .= " * يتم تعديله تلقائياً من صفحة تغيير كلمة المرور\n";
            $content .= " */\n\n";
            $content .= "return [\n";
            $content .= "    'username' => '" . addslashes($newUsername) . "',\n";
            $content .= "    'password_hash' => '" . $finalHash . "'\n";
            $content .= "];\n";
            
            if (file_put_contents($credentialsFile, $content)) {
                $passwordMessage = 'تم تحديث بيانات الدخول بنجاح. سيتم تسجيل خروجك الآن.';
                // Reload credentials for display
                $adminCredentials = include $credentialsFile;
                // Log out after password change
                echo '<script>setTimeout(function(){ window.location.href = "login.php"; }, 2000);</script>';
            } else {
                $passwordError = 'فشل في حفظ التغييرات. تحقق من صلاحيات الملفات.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">⚙️ إعدادات الموقع</h1>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if (isAdmin()): ?>
            <!-- Admin Credentials - Only visible to original admin -->
            <div class="admin-card" style="margin-bottom: 30px; border: 2px solid rgba(233, 30, 140, 0.2);">
                <div class="admin-card-header" style="background: linear-gradient(135deg, rgba(233, 30, 140, 0.1), rgba(255, 107, 157, 0.1));">
                    <h2 class="admin-card-title">🔐 بيانات الدخول للأدمن</h2>
                </div>
                <div style="padding: 20px;">
                    <?php if ($passwordMessage): ?>
                    <div class="alert alert-success"><?= $passwordMessage ?></div>
                    <?php endif; ?>
                    
                    <?php if ($passwordError): ?>
                    <div class="alert alert-error"><?= $passwordError ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="change_password" value="1">
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">اسم المستخدم الحالي</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($adminCredentials['username']) ?>" disabled 
                                   style="background: #f5f5f5;">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">اسم المستخدم الجديد *</label>
                            <input type="text" name="new_username" class="form-control" 
                                   value="<?= htmlspecialchars($adminCredentials['username']) ?>" required minlength="3">
                        </div>
                        
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label class="form-label">كلمة المرور الحالية *</label>
                            <input type="password" name="current_password" class="form-control" 
                                   required placeholder="أدخل كلمة المرور الحالية للتأكيد">
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">كلمة المرور الجديدة</label>
                                <input type="password" name="new_password" class="form-control" 
                                       minlength="6" placeholder="اتركها فارغة إذا لا تريد تغييرها">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">تأكيد كلمة المرور الجديدة</label>
                                <input type="password" name="confirm_password" class="form-control" 
                                       placeholder="أعد كتابة كلمة المرور الجديدة">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                            🔒 تحديث بيانات الدخول
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="save_settings" value="1">

                <!-- Social Media Settings -->
                <?php 
                // Get current saved values with proper defaults
                $currentTelegram = isset($settings['telegram_username']) && !empty($settings['telegram_username']) 
                    ? $settings['telegram_username'] 
                    : (isset($settings['telegram_order_username']) && !empty($settings['telegram_order_username']) 
                        ? $settings['telegram_order_username'] 
                        : 'sur_prisese');
                $currentInstagram = isset($settings['instagram_username']) && !empty($settings['instagram_username']) 
                    ? $settings['instagram_username'] 
                    : 'sur._prises';
                $currentContactText = isset($settings['contact_text']) && !empty($settings['contact_text']) 
                    ? $settings['contact_text'] 
                    : 'تواصل معنا';
                ?>
                <div class="admin-card" style="margin-bottom: 30px; border: 2px solid rgba(138, 43, 226, 0.2);">
                    <div class="admin-card-header" style="background: linear-gradient(135deg, rgba(138, 43, 226, 0.1), rgba(75, 0, 130, 0.05));">
                        <h2 class="admin-card-title">📱 سوشيال ميديا / تواصل معنا</h2>
                    </div>
                    <div style="padding: 20px;">
                        <p style="color: var(--text-muted); margin-bottom: 20px; font-size: 0.95rem; line-height: 1.7;">
                            عدّل حسابات التواصل التي تظهر للزبائن في الموقع. <strong>بعد الحفظ</strong> تنعكس التغييرات تلقائياً في: الرئيسية، الفوتر، السلة، تتبع الطلب، وجميع صفحات الموقع.
                        </p>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px;">
                            <!-- Telegram -->
                            <div class="form-group">
                                <label class="form-label" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <span style="width: 36px; height: 36px; background: linear-gradient(135deg, #0088cc, #00aced); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 10px rgba(0,136,204,0.3);">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
                                            <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                        </svg>
                                    </span>
                                    <span style="font-weight: 600;">حساب تليجرام</span>
                                </label>
                                <div style="position: relative;">
                                    <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #0088cc; font-weight: 700; font-size: 1.1rem;">@</span>
                                    <input type="text" name="telegram_username" class="form-control" 
                                           style="padding-right: 40px; font-weight: 600;"
                                           value="<?= htmlspecialchars($currentTelegram) ?>">
                                </div>
                            </div>
                            
                            <!-- Instagram -->
                            <div class="form-group">
                                <label class="form-label" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                    <span style="width: 36px; height: 36px; background: linear-gradient(135deg, #833AB4, #E1306C, #F77737); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 10px rgba(225,48,108,0.3);">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
                                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                        </svg>
                                    </span>
                                    <span style="font-weight: 600;">حساب انستقرام</span>
                                </label>
                                <div style="position: relative;">
                                    <span style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); color: #E1306C; font-weight: 700; font-size: 1.1rem;">@</span>
                                    <input type="text" name="instagram_username" class="form-control" 
                                           style="padding-right: 40px; font-weight: 600;"
                                           value="<?= htmlspecialchars($currentInstagram) ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Contact Text -->
                        <div class="form-group" style="margin-top: 20px;">
                            <label class="form-label" style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                <span style="width: 36px; height: 36px; background: var(--primary); border-radius: 10px; display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 10px rgba(233,30,140,0.3);">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="white">
                                        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
                                    </svg>
                                </span>
                                <span style="font-weight: 600;">نص التواصل</span>
                            </label>
                            <input type="text" name="contact_text" class="form-control" 
                                   style="font-weight: 600;"
                                   value="<?= htmlspecialchars($currentContactText) ?>">
                            <small style="color: var(--text-muted); margin-top: 8px; display: block;">النص الذي يظهر بجانب أيقونات التواصل في الموقع</small>
                        </div>
                        
                        <!-- Test Links Section -->
                        <div style="background: linear-gradient(135deg, rgba(0,0,0,0.02), rgba(0,0,0,0.04)); padding: 20px; border-radius: var(--radius-lg); margin-top: 25px; border: 1px solid rgba(0,0,0,0.05);">
                            <p style="font-weight: 700; margin-bottom: 15px; color: var(--text-dark); display: flex; align-items: center; gap: 8px;">
                                🔗 تجربة روابط التواصل
                                <span style="background: #e8f5e9; color: #2e7d32; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600;">القيم المحفوظة حالياً</span>
                            </p>
                            <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                                <!-- Telegram Button -->
                                <a href="https://t.me/<?= htmlspecialchars($currentTelegram) ?>" 
                                   target="_blank" 
                                   class="social-test-btn telegram-btn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.093.036.306.02.472-.18 1.898-.962 6.502-1.36 8.627-.168.9-.499 1.201-.82 1.23-.696.065-1.225-.46-1.9-.902-1.056-.693-1.653-1.124-2.678-1.8-1.185-.78-.417-1.21.258-1.91.177-.184 3.247-2.977 3.307-3.23.007-.032.014-.15-.056-.212s-.174-.041-.249-.024c-.106.024-1.793 1.14-5.061 3.345-.48.33-.913.49-1.302.48-.428-.008-1.252-.241-1.865-.44-.752-.245-1.349-.374-1.297-.789.027-.216.325-.437.893-.663 3.498-1.524 5.83-2.529 6.998-3.014 3.332-1.386 4.025-1.627 4.476-1.635z"/>
                                    </svg>
                                    <span>تليجرام</span>
                                    <span style="opacity: 0.8; font-size: 0.8rem;">@<?= htmlspecialchars($currentTelegram) ?></span>
                                </a>
                                
                                <!-- Instagram Button -->
                                <a href="https://instagram.com/<?= htmlspecialchars($currentInstagram) ?>" 
                                   target="_blank"
                                   class="social-test-btn instagram-btn">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                    </svg>
                                    <span>انستقرام</span>
                                    <span style="opacity: 0.8; font-size: 0.8rem;">@<?= htmlspecialchars($currentInstagram) ?></span>
                                </a>
                            </div>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 12px;">
                                اضغط على الأزرار لتجربة الروابط. بعد التعديل والحفظ ستتحدث الروابط تلقائياً.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Delivery Settings -->
                <div class="admin-card" style="margin-bottom: 30px;">
                    <div class="admin-card-header">
                        <h2 class="admin-card-title">🚚 سعر التوصيل</h2>
                    </div>
                    <div style="padding: 20px;">
                        <div class="form-group">
                            <label class="form-label">سعر التوصيل لجميع المحافظات (دينار عراقي)</label>
                            <input type="number" name="delivery_price" class="form-control" 
                                   value="<?= isset($settings['delivery_price']) ? $settings['delivery_price'] : 5000 ?>" min="0">
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;">
                                سعر موحد للتوصيل لجميع محافظات العراق
                            </p>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">💾 حفظ الإعدادات</button>
            </form>

            <!-- Payment Methods Form (Separate Form) -->
            <form method="POST" style="margin-top: 30px;">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="save_payment_methods" value="1">
                
                <div class="admin-card" style="border: 2px solid rgba(201, 164, 73, 0.3);">
                    <div class="admin-card-header" style="background: linear-gradient(135deg, rgba(201, 164, 73, 0.15), rgba(229, 199, 107, 0.1));">
                        <h2 class="admin-card-title">💳 طرق الدفع المتاحة</h2>
                    </div>
                    <div style="padding: 20px;">
                        <p style="color: var(--text-muted); margin-bottom: 20px; line-height: 1.8;">
                            فعّل أو عطّل طرق الدفع حسب رغبتك. التغييرات تنعكس تلقائياً في جميع صفحات الموقع.
                        </p>
                        
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                            <?php foreach (getPaymentMethods() as $id => $method): ?>
                            <label style="cursor: pointer;">
                                <div class="payment-toggle-card" style="background: <?= $method['enabled'] ? '#f0fff0' : '#fafafa' ?>; 
                                            border: 2px solid <?= $method['enabled'] ? '#4CAF50' : '#ddd' ?>; 
                                            border-radius: var(--radius-md); 
                                            padding: 18px;
                                            transition: all 0.3s ease;">
                                    <div style="display: flex; align-items: center; justify-content: space-between;">
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <span style="font-size: 2rem;"><?= $method['icon'] ?></span>
                                            <div>
                                                <strong style="font-size: 1rem; display: block; color: #333;"><?= htmlspecialchars($method['name']) ?></strong>
                                                <small style="color: #666; font-size: 0.8rem;"><?= htmlspecialchars($method['description']) ?></small>
                                            </div>
                                        </div>
                                        <!-- Toggle Switch -->
                                        <div class="toggle-switch">
                                            <input type="checkbox" 
                                                   name="payment_enabled[]" 
                                                   value="<?= $id ?>" 
                                                   id="payment_<?= $id ?>"
                                                   <?= $method['enabled'] ? 'checked' : '' ?>>
                                            <span class="toggle-slider"></span>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        
                        <button type="submit" class="btn btn-primary" style="margin-top: 20px;">
                            💾 حفظ طرق الدفع
                        </button>
                    </div>
                </div>
            </form>

            <!-- System Time Info (Diagnostic) -->
            <?php
            // Get timezone diagnostics
            $phpTime = date('Y-m-d H:i:s');
            $phpTimezone = date_default_timezone_get();
            $mysqlTime = '';
            $mysqlSessionTz = '';
            $mysqlGlobalTz = '';
            
            if (isDbConnected()) {
                try {
                    $mysqlTime = db()->query("SELECT NOW() as now_time")->fetchColumn();
                    $mysqlSessionTz = db()->query("SELECT @@session.time_zone")->fetchColumn();
                    $mysqlGlobalTz = db()->query("SELECT @@global.time_zone")->fetchColumn();
                } catch (Exception $e) {
                    $mysqlTime = 'Error';
                }
            }
            
            // Calculate difference
            $phpTs = strtotime($phpTime);
            $mysqlTs = strtotime($mysqlTime);
            $diffSeconds = abs($phpTs - $mysqlTs);
            $diffMinutes = round($diffSeconds / 60);
            $isSync = $diffMinutes < 2; // Allow 2 minute tolerance
            ?>
            <div class="admin-card" style="margin-top: 30px; border: 2px solid rgba(59, 130, 246, 0.2);">
                <div class="admin-card-header" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.1), rgba(37, 99, 235, 0.05));">
                    <h2 class="admin-card-title">🕐 معلومات النظام والتوقيت</h2>
                </div>
                <div style="padding: 20px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 15px;">
                            <div style="font-size: 0.85rem; color: #0369a1; margin-bottom: 5px;">🐘 وقت PHP</div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: #0c4a6e;"><?= formatDateTime($phpTime, 'full') ?></div>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 5px;">Timezone: <?= $phpTimezone ?></div>
                        </div>
                        
                        <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 15px;">
                            <div style="font-size: 0.85rem; color: #15803d; margin-bottom: 5px;">🗄️ وقت MySQL NOW()</div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: #14532d;"><?= $mysqlTime ? formatDateTime($mysqlTime, 'full') : 'غير متاح' ?></div>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 5px;">Session: <?= $mysqlSessionTz ?></div>
                        </div>
                        
                        <div style="background: <?= $isSync ? '#f0fdf4' : '#fef2f2' ?>; border: 1px solid <?= $isSync ? '#bbf7d0' : '#fecaca' ?>; border-radius: 12px; padding: 15px;">
                            <div style="font-size: 0.85rem; color: <?= $isSync ? '#15803d' : '#b91c1c' ?>; margin-bottom: 5px;">
                                <?= $isSync ? '✅ الحالة' : '⚠️ الحالة' ?>
                            </div>
                            <div style="font-size: 1.1rem; font-weight: 700; color: <?= $isSync ? '#14532d' : '#7f1d1d' ?>;">
                                <?= $isSync ? 'متزامن' : 'فرق ' . $diffMinutes . ' دقيقة' ?>
                            </div>
                            <div style="font-size: 0.8rem; color: #64748b; margin-top: 5px;">Global TZ: <?= $mysqlGlobalTz ?></div>
                        </div>
                    </div>
                    
                    <div style="background: linear-gradient(135deg, #faf5ff, #f3e8ff); border: 1px solid #e9d5ff; border-radius: 12px; padding: 15px; margin-top: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <span style="font-size: 1.5rem;">📋</span>
                            <span style="font-weight: 700; color: #7c3aed;">الإصدار الحالي: v<?= SITE_VERSION ?></span>
                        </div>
                        <p style="color: #6b7280; font-size: 0.9rem; line-height: 1.6; margin: 0;">
                            ✅ التوقيت موحد على Asia/Baghdad (+03:00)
                            <br>
                            ✅ PHP و MySQL يستخدمان نفس التوقيت
                            <br>
                            ✅ كل التواريخ تُعرض بتوقيت بغداد
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
        }
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: 0.3s;
            border-radius: 26px;
        }
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 20px;
            width: 20px;
            right: 3px;
            bottom: 3px;
            background-color: white;
            transition: 0.3s;
            border-radius: 50%;
        }
        .toggle-switch input:checked + .toggle-slider {
            background-color: #4CAF50;
        }
        .toggle-switch input:checked + .toggle-slider:before {
            transform: translateX(-24px);
        }
        .payment-toggle-card:hover {
            border-color: var(--primary) !important;
            box-shadow: 0 4px 15px rgba(201, 164, 73, 0.2);
        }
        
        /* Social Media Test Buttons */
        .social-test-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            color: white;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        
        .social-test-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.2);
        }
        
        .telegram-btn {
            background: linear-gradient(135deg, #0088cc, #00aced);
        }
        
        .telegram-btn:hover {
            background: linear-gradient(135deg, #0077b3, #0099d9);
        }
        
        .instagram-btn {
            background: linear-gradient(135deg, #833AB4, #E1306C, #F77737);
        }
        
        .instagram-btn:hover {
            background: linear-gradient(135deg, #7231a0, #d12d63, #e06a2f);
        }
        
        @media (max-width: 480px) {
            .social-test-btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <script src="<?= av('js/admin.js') ?>"></script>
    <script>
        // Custom Arabic validation messages
        document.addEventListener('DOMContentLoaded', function() {
            // Username field
            const usernameField = document.querySelector('input[name="new_username"]');
            if (usernameField) {
                usernameField.addEventListener('invalid', function(e) {
                    if (this.validity.tooShort) {
                        this.setCustomValidity('اسم المستخدم يجب أن يكون 3 أحرف على الأقل');
                    } else if (this.validity.valueMissing) {
                        this.setCustomValidity('يرجى إدخال اسم المستخدم');
                    }
                });
                usernameField.addEventListener('input', function() {
                    this.setCustomValidity('');
                });
            }
            
            // Current password field  
            const currentPassField = document.querySelector('input[name="current_password"]');
            if (currentPassField) {
                currentPassField.addEventListener('invalid', function(e) {
                    if (this.validity.valueMissing) {
                        this.setCustomValidity('يرجى إدخال كلمة المرور الحالية');
                    }
                });
                currentPassField.addEventListener('input', function() {
                    this.setCustomValidity('');
                });
            }
            
            // New password field
            const newPassField = document.querySelector('input[name="new_password"]');
            if (newPassField) {
                newPassField.addEventListener('invalid', function(e) {
                    if (this.validity.tooShort) {
                        this.setCustomValidity('كلمة المرور يجب أن تكون 6 أحرف على الأقل');
                    }
                });
                newPassField.addEventListener('input', function() {
                    this.setCustomValidity('');
                    // Also validate confirm password
                    const confirmField = document.querySelector('input[name="confirm_password"]');
                    if (confirmField && confirmField.value && this.value !== confirmField.value) {
                        confirmField.setCustomValidity('كلمة المرور غير متطابقة');
                    } else if (confirmField) {
                        confirmField.setCustomValidity('');
                    }
                });
            }
            
            // Confirm password field
            const confirmPassField = document.querySelector('input[name="confirm_password"]');
            if (confirmPassField) {
                confirmPassField.addEventListener('input', function() {
                    const newPass = document.querySelector('input[name="new_password"]');
                    if (newPass && newPass.value && this.value !== newPass.value) {
                        this.setCustomValidity('كلمة المرور غير متطابقة');
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
        });
    </script>
</body>
</html>
