<?php
require_once '../includes/config.php';
require_once '../includes/security.php';

// Initialize secure session
initSecureSession();

// Set security headers
setSecurityHeaders();

// If already logged in, redirect to dashboard
if (validateAdminSession()) {
    redirect('./');
}

// Load admin credentials from file
$credentialsFile = ROOT_PATH . 'data/admin_credentials.php';
$adminCredentials = file_exists($credentialsFile) 
    ? include $credentialsFile 
    : ['username' => 'admin', 'password_hash' => 'YOUR_PASSWORD_HASH_HERE'];

$error = '';
$success = '';
$lockoutTime = 0;

// Check for logout message
if (isset($_GET['logged_out'])) {
    $success = '✅ تم تسجيل الخروج بنجاح';
}

// Check for session expired message
if (isset($_GET['expired'])) {
    $error = '⏱️ انتهت صلاحية الجلسة. يرجى تسجيل الدخول مرة أخرى.';
}

// Check for error message from redirect
if (isset($_SESSION['login_error'])) {
    $error = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// Rate limiting check - حماية من هجمات Brute Force
if (isLoginRateLimited()) {
    $lockoutTime = getRemainingLockoutTime();
    $minutes = ceil($lockoutTime / 60);
    $error = "⚠️ تم حظرك مؤقتاً بسبب محاولات تسجيل دخول كثيرة. انتظر {$minutes} دقيقة.";
}

// Handle login - Only process if POST with actual data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $lockoutTime === 0) {
    $username = sanitize(isset($_POST['username']) ? $_POST['username'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    $loginSuccess = false;
    
    // Only count as attempt if credentials were actually submitted
    if (empty($username) && empty($password)) {
        // Empty form submission - ignore, don't count
        // Just reload the page
    } elseif (!validateCSRFToken(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        // Invalid CSRF - don't count as login attempt
        $_SESSION['login_error'] = 'خطأ في الأمان. أعد تحميل الصفحة.';
        redirect('login');
    } else {
        // SECURITY: Check if device is blocked
        if (isDeviceBlocked()) {
            $_SESSION['login_error'] = '⛔ هذا الجهاز محظور من الدخول. تواصل مع المدير.';
            logSecurityEvent('BLOCKED_LOGIN', 'محاولة دخول من جهاز محظور', ['username' => $username]);
            redirect('login');
            exit;
        }
        
        // Run 2FA migration on first login after update
        ensure2FAMigration();
        
        $loginUserType = null;
        $loginUserId = null;
        $loginDisplayName = '';
        $loginStaffData = null;
        
        // 1) Check admin credentials
        if ($username === $adminCredentials['username'] && password_verify($password, $adminCredentials['password_hash'])) {
            $loginUserType = 'admin';
            $loginUserId = null;
            $loginDisplayName = 'المدير الرئيسي';
        }
        
        // 2) Check staff credentials
        if (!$loginUserType) {
            $staffMember = getStaffByUsername($username);
            if ($staffMember && password_verify($password, $staffMember['password'])) {
                if ($staffMember['is_active']) {
                    $loginUserType = 'staff';
                    $loginUserId = $staffMember['id'];
                    $loginDisplayName = $staffMember['first_name'] . ' ' . $staffMember['last_name'];
                    $loginStaffData = $staffMember;
                } else {
                    $_SESSION['login_error'] = 'حسابك معطل. تواصل مع المدير.';
                    redirect('login');
                    exit;
                }
            }
        }
        
        // If credentials are valid
        if ($loginUserType) {
            clearLoginAttempts();
            
            // ═══════════════════════════════════════════════════════════
            // 2FA FLOW
            // ═══════════════════════════════════════════════════════════
            $has2FA = is2FAEnabled($loginUserType, $loginUserId, $username);
            $deviceId = getDeviceId();
            $currentIP = getClientIP();
            
            if (!$has2FA) {
                // ── 2FA NOT SET UP → Force setup ──
                $_SESSION['2fa_pending_setup'] = true;
                $_SESSION['2fa_user_type'] = $loginUserType;
                $_SESSION['2fa_user_id'] = $loginUserId;
                $_SESSION['2fa_username'] = $username;
                $_SESSION['2fa_display_name'] = $loginDisplayName;
                $_SESSION['2fa_staff_data'] = $loginStaffData;
                
                logSecurityEvent('2FA_SETUP_REQUIRED', 'توجيه لإعداد 2FA', ['username' => $username]);
                redirect('setup-2fa');
                exit;
            }
            
            // ── 2FA IS ENABLED → Check device trust ──
            $trusted = isTrustedDevice($username, $deviceId, $currentIP);
            
            if ($trusted) {
                // TRUSTED DEVICE + SAME IP → Complete login (no 2FA needed)
                if ($loginUserType === 'admin') {
                    createAdminSession();
                    logSuccessfulLogin($username);
                    recordLoginSession('admin', null, $username, $loginDisplayName);
                } else {
                    createStaffSession($loginStaffData['id'], $loginStaffData);
                    logSuccessfulLogin($username . ' (موظف)');
                    recordLoginSession('staff', $loginStaffData['id'], $username, $loginDisplayName);
                }
                $_SESSION['2fa_completed'] = true;
                
                // Update device activity
                $location = getLocationFromIP($currentIP);
                updateKnownDeviceActivity($deviceId, $currentIP, $location);
                
                redirect('./');
                exit;
            }
            
            // ── UNTRUSTED or IP CHANGED → Require 2FA ──
            $knownDevice = getKnownDevice($username, $deviceId);
            $isNewDevice = !$knownDevice;
            $isIpChanged = ($knownDevice && !empty($knownDevice['is_trusted']) && $knownDevice['last_verified_ip'] !== $currentIP);
            
            // Force logout all other sessions
            forceLogoutAllSessions($username);
            
            // Store 2FA verification state
            $_SESSION['2fa_pending_verify'] = true;
            $_SESSION['2fa_user_type'] = $loginUserType;
            $_SESSION['2fa_user_id'] = $loginUserId;
            $_SESSION['2fa_username'] = $username;
            $_SESSION['2fa_display_name'] = $loginDisplayName;
            $_SESSION['2fa_staff_data'] = $loginStaffData;
            $_SESSION['2fa_is_new_device'] = $isNewDevice;
            $_SESSION['2fa_is_ip_changed'] = $isIpChanged;
            
            if ($isNewDevice) {
                logSecurityEvent('LOGIN_NEW_DEVICE', 'تسجيل دخول من جهاز جديد - مطلوب 2FA', [
                    'username' => $username, 'ip' => $currentIP
                ]);
            } elseif ($isIpChanged) {
                logSecurityEvent('LOGIN_IP_CHANGED', 'تغيير IP - مطلوب 2FA', [
                    'username' => $username, 'ip' => $currentIP,
                    'old_ip' => $knownDevice['last_verified_ip'] ?? 'unknown'
                ]);
            }
            
            redirect('verify-2fa');
            exit;
        }
        
        // Failed login
        if (!empty($username) || !empty($password)) {
            recordFailedLogin();
            logFailedLogin($username);
            
            // Show remaining attempts or lockout message
            $ip = getClientIP();
            $key = 'login_attempts_' . md5($ip);
            $attempts = isset($_SESSION[$key]['count']) ? $_SESSION[$key]['count'] : 0;
            $remaining = LOGIN_MAX_ATTEMPTS - $attempts;
            
            if ($remaining > 0) {
                $_SESSION['login_error'] = "اسم المستخدم أو كلمة المرور غير صحيحة. المحاولات المتبقية: {$remaining}";
            } else {
                $lockoutTime = getRemainingLockoutTime();
                $minutes = ceil($lockoutTime / 60);
                $_SESSION['login_error'] = "⚠️ تم حظرك مؤقتاً. انتظر {$minutes} دقيقة.";
            }
        }
        // Redirect to prevent form resubmission (PRG Pattern)
        redirect('login');
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - لوحة التحكم</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .lockout-timer {
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a);
            color: white;
            padding: 15px 20px;
            border-radius: var(--radius-md);
            text-align: center;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .lockout-timer .time {
            font-size: 1.5rem;
            display: block;
            margin-top: 5px;
        }
        .login-box {
            position: relative;
        }
        .security-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(76, 175, 80, 0.3);
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="security-badge">🔒 اتصال آمن</div>
            
            <div class="login-logo">
                <img src="../images/logo.jpg" alt="<?= SITE_NAME ?>">
                <h1>لوحة التحكم</h1>
            </div>
            
            <?php if ($success): ?>
            <div class="alert alert-success" style="background: rgba(76, 175, 80, 0.1); color: #4CAF50; border: 1px solid rgba(76, 175, 80, 0.3);"><?= $success ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            
            <?php if ($lockoutTime > 0): ?>
            <div class="lockout-timer">
                ⏱️ الوقت المتبقي للحظر:
                <span class="time" id="countdown"><?= ceil($lockoutTime / 60) ?>:<?= str_pad($lockoutTime % 60, 2, '0', STR_PAD_LEFT) ?></span>
            </div>
            <script>
                let remaining = <?= $lockoutTime ?>;
                const countdown = document.getElementById('countdown');
                const timer = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) {
                        clearInterval(timer);
                        location.reload();
                    }
                    const mins = Math.floor(remaining / 60);
                    const secs = remaining % 60;
                    countdown.textContent = mins + ':' + String(secs).padStart(2, '0');
                }, 1000);
            </script>
            <?php else: ?>
            <form method="POST" class="login-form" autocomplete="off">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                
                <div class="form-group">
                    <label class="form-label">اسم المستخدم</label>
                    <input type="text" name="username" class="form-control" required autofocus 
                           autocomplete="off" spellcheck="false">
                </div>
                
                <div class="form-group">
                    <label class="form-label">كلمة المرور</label>
                    <input type="password" name="password" class="form-control" required 
                           autocomplete="new-password">
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    🔐 تسجيل الدخول
                </button>
            </form>
            <?php endif; ?>
            
            <a href="../index.php" class="back-link">← العودة للمتجر</a>
        </div>
    </div>
</body>
</html>
