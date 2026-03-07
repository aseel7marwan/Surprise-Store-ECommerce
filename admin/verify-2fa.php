<?php
/**
 * Surprise! Store - 2FA Verification Page
 * Shown when login requires 2FA code (untrusted device or IP change)
 * 
 * @version 6.0.0
 */
require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/totp.php';

initSecureSession();
setSecurityHeaders();

// Must have passed password verification (2fa_pending_verify flag)
if (empty($_SESSION['2fa_pending_verify']) || empty($_SESSION['2fa_user_type'])) {
    redirect('login');
    exit;
}

$userType = $_SESSION['2fa_user_type'];
$userId = $_SESSION['2fa_user_id'] ?? null;
$username = $_SESSION['2fa_username'] ?? '';
$displayName = $_SESSION['2fa_display_name'] ?? $username;

$error = '';
$rateLimited = false;
$remainingAttempts = 5;

// Check rate limiting
if (is2FARateLimited($username, getClientIP())) {
    $rateLimited = true;
    $error = '⚠️ تم تجاوز عدد المحاولات المسموحة. انتظر 10 دقائق.';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$rateLimited) {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'خطأ في الأمان. أعد المحاولة.';
    } else {
        $code = trim($_POST['totp_code'] ?? '');
        $secret = get2FASecret($userType, $userId, $username);
        
        if ($secret && verifyTOTPCode($secret, $code)) {
            // Success! Clear 2FA pending state
            unset($_SESSION['2fa_pending_verify']);
            $_SESSION['2fa_completed'] = true;
            
            // Complete the actual login
            if ($userType === 'admin') {
                createAdminSession();
                logSuccessfulLogin($username);
                recordLoginSession('admin', null, $username, $displayName);
            } else {
                $staffData = getStaffByUsername($username);
                if ($staffData) {
                    createStaffSession($staffData['id'], $staffData);
                    logSuccessfulLogin($username . ' (موظف)');
                    recordLoginSession('staff', $staffData['id'], $username, $displayName);
                }
            }
            
            // Trust this device with current IP
            $deviceId = getDeviceId();
            $ip = getClientIP();
            trustDevice($username, $deviceId, $ip);
            
            // Mark 2FA as completed and clear pending flags
            $_SESSION['2fa_completed'] = true;
            unset($_SESSION['2fa_pending_setup']);
            unset($_SESSION['2fa_pending_verify']);
            unset($_SESSION['2fa_user_type']);
            unset($_SESSION['2fa_user_id']);
            unset($_SESSION['2fa_username']);
            unset($_SESSION['2fa_display_name']);
            unset($_SESSION['2fa_staff_data']);
            unset($_SESSION['2fa_is_new_device']);
            unset($_SESSION['2fa_is_ip_changed']);
            
            logSecurityEvent('2FA_SUCCESS', 'تحقق ناجح بخطوتين', [
                'username' => $username,
                'user_type' => $userType,
                'device_id' => substr($deviceId, 0, 8) . '...'
            ]);
            
            // Redirect to dashboard
            redirect('./');
            exit;
        } else {
            // Failed attempt
            record2FAAttempt($username, getClientIP());
            $remainingAttempts = getRemaining2FAAttempts($username, getClientIP());
            
            logSecurityEvent('2FA_FAIL', 'فشل في التحقق بخطوتين', [
                'username' => $username,
                'remaining' => $remainingAttempts
            ]);
            
            if ($remainingAttempts <= 0) {
                $rateLimited = true;
                $error = '⚠️ تم تجاوز عدد المحاولات. انتظر 10 دقائق.';
            } else {
                $error = "الرمز غير صحيح. المحاولات المتبقية: {$remainingAttempts}";
            }
        }
    }
}

$csrf_token = generateCSRFToken();

// Determine context message
$isNewDevice = !empty($_SESSION['2fa_is_new_device']);
$isIpChanged = !empty($_SESSION['2fa_is_ip_changed']);
$contextIcon = $isNewDevice ? '📱' : ($isIpChanged ? '🌐' : '🔒');
$contextMsg = $isNewDevice ? 'تم اكتشاف جهاز جديد' : ($isIpChanged ? 'تم اكتشاف تغيير في عنوان IP' : 'مطلوب التحقق بخطوتين');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق بخطوتين - لوحة التحكم</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .verify-container {
            max-width: 440px;
            margin: 60px auto;
            padding: 20px;
        }
        .verify-box {
            background: var(--white, #fff);
            border-radius: var(--radius-lg, 16px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px 30px;
            position: relative;
        }
        .verify-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(99, 102, 241, 0.3);
            white-space: nowrap;
        }
        .verify-icon {
            text-align: center;
            font-size: 3rem;
            margin: 15px 0 10px;
        }
        .verify-title {
            text-align: center;
            margin: 0 0 5px;
            font-size: 1.3rem;
            color: var(--dark, #1a1a2e);
        }
        .verify-context {
            text-align: center;
            padding: 10px 15px;
            background: #fef3c7;
            border: 1px solid #fbbf24;
            border-radius: 8px;
            margin: 15px 0;
            font-size: 0.85rem;
            color: #92400e;
        }
        .verify-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }
        .code-input-group {
            display: flex;
            gap: 8px;
            justify-content: center;
            direction: ltr;
            margin: 20px 0;
        }
        .code-input-group input {
            width: 48px;
            height: 58px;
            text-align: center;
            font-size: 1.6rem;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            outline: none;
            transition: all 0.2s;
            background: #f8fafc;
        }
        .code-input-group input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
            background: white;
        }
        .code-input-group input.filled {
            border-color: #10b981;
            background: #f0fdf4;
        }
        .code-input-group input.error {
            border-color: #ef4444;
            background: #fef2f2;
            animation: shake 0.4s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .hidden-code {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 0.9rem;
            text-align: center;
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .submit-btn:hover {
            background: linear-gradient(135deg, #4f46e5, #4338ca);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
        }
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .lockout-box {
            text-align: center;
            padding: 25px;
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            border-radius: 12px;
            margin: 15px 0;
        }
        .lockout-box .time {
            font-size: 2rem;
            font-weight: 700;
            color: #dc2626;
            display: block;
            margin-top: 8px;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #64748b;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .back-link:hover { color: #334155; }
        .user-info {
            text-align: center;
            margin: 10px 0 15px;
            padding: 8px;
            background: #f1f5f9;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #475569;
        }
        .user-info strong { color: #1e293b; }
    </style>
</head>
<body class="login-page">
    <div class="verify-container">
        <div class="verify-box">
            <div class="verify-badge">🔐 تحقق مطلوب</div>
            
            <div class="verify-icon"><?= $contextIcon ?></div>
            <h2 class="verify-title">التحقق بخطوتين</h2>
            
            <div class="verify-context">
                <?= htmlspecialchars($contextMsg) ?>
            </div>
            
            <div class="user-info">
                الحساب: <strong><?= htmlspecialchars($username) ?></strong>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <?php if ($rateLimited): ?>
            <div class="lockout-box">
                ⏱️ الوقت المتبقي:
                <span class="time" id="lockoutTimer">10:00</span>
            </div>
            <script>
                let remaining = 600;
                const timerEl = document.getElementById('lockoutTimer');
                const timer = setInterval(() => {
                    remaining--;
                    if (remaining <= 0) { clearInterval(timer); location.reload(); }
                    const m = Math.floor(remaining / 60);
                    const s = remaining % 60;
                    timerEl.textContent = m + ':' + String(s).padStart(2, '0');
                }, 1000);
            </script>
            <?php else: ?>
            
            <p class="verify-subtitle">
                أدخل الرمز المكون من 6 أرقام من تطبيق المصادقة
            </p>
            
            <form method="POST" id="verifyForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="text" name="totp_code" id="hiddenCode" class="hidden-code" maxlength="6" autocomplete="off">
                
                <div class="code-input-group" id="codeInputs">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0" autofocus>
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
                    <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
                </div>
                
                <button type="submit" class="submit-btn" id="submitBtn" disabled>
                    🔓 تحقق ودخول
                </button>
            </form>
            <?php endif; ?>
            
            <div style="margin-top: 20px; text-align: center; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 12px; line-height: 1.6;">
                    ⚠️ إذا فقدت تطبيق المصادقة، تواصل مع الإدارة لإعادة تعيين 2FA
                </p>
                <a href="logout" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; background: #fee2e2; color: #dc2626; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">🚪 تسجيل خروج</a>
            </div>
        </div>
    </div>
    
    <script>
        const inputs = document.querySelectorAll('#codeInputs input');
        const hiddenCode = document.getElementById('hiddenCode');
        const submitBtn = document.getElementById('submitBtn');
        const hasError = <?= $error ? 'true' : 'false' ?>;
        
        if (!inputs.length) { /* rate limited, no inputs */ }
        
        function updateHiddenCode() {
            let code = '';
            inputs.forEach(inp => { code += inp.value; });
            hiddenCode.value = code;
            if (submitBtn) submitBtn.disabled = code.length !== 6;
            inputs.forEach(inp => { inp.classList.toggle('filled', inp.value.length > 0); });
        }
        
        inputs.forEach((input, idx) => {
            // Show error animation on load if there was an error
            if (hasError) {
                input.classList.add('error');
                setTimeout(() => { input.classList.remove('error'); }, 600);
            }
            
            input.addEventListener('input', function(e) {
                let v = this.value.replace(/[^0-9]/g, '');
                this.value = v;
                this.classList.remove('error');
                updateHiddenCode();
                if (v && idx < inputs.length - 1) { inputs[idx + 1].focus(); }
                if (hiddenCode.value.length === 6) {
                    setTimeout(() => { document.getElementById('verifyForm').submit(); }, 200);
                }
            });
            
            input.addEventListener('keydown', function(e) {
                if (e.key === 'Backspace' && !this.value && idx > 0) {
                    inputs[idx - 1].focus();
                    inputs[idx - 1].value = '';
                    updateHiddenCode();
                }
            });
            
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                for (let i = 0; i < Math.min(paste.length, 6); i++) { inputs[i].value = paste[i]; }
                updateHiddenCode();
                if (paste.length >= 6) {
                    inputs[5].focus();
                    setTimeout(() => { document.getElementById('verifyForm').submit(); }, 200);
                } else {
                    inputs[Math.min(paste.length, 5)].focus();
                }
            });
        });
    </script>
</body>
</html>
