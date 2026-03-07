<?php
/**
 * Surprise! Store - 2FA Setup Page
 * First-time TOTP setup with QR code and manual key
 * 
 * @version 6.0.0
 */
require_once '../includes/config.php';
require_once '../includes/security.php';
require_once '../includes/totp.php';
require_once '../includes/qrcode.php';

initSecureSession();
setSecurityHeaders();

// Must have passed password verification (2fa_pending_setup flag)
if (empty($_SESSION['2fa_pending_setup']) || empty($_SESSION['2fa_user_type'])) {
    redirect('login');
    exit;
}

$userType = $_SESSION['2fa_user_type'];
$userId = $_SESSION['2fa_user_id'] ?? null;
$username = $_SESSION['2fa_username'] ?? '';
$displayName = $_SESSION['2fa_display_name'] ?? $username;

$error = '';
$success = false;

// Generate or retrieve temporary secret
if (empty($_SESSION['2fa_temp_secret'])) {
    $_SESSION['2fa_temp_secret'] = generateTOTPSecret();
}
$secret = $_SESSION['2fa_temp_secret'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'خطأ في الأمان. أعد المحاولة.';
    } else {
        $code = trim($_POST['totp_code'] ?? '');
        
        if (verifyTOTPCode($secret, $code)) {
            // Save secret to database
            if (save2FASecret($userType, $userId, $username, $secret)) {
                // Clear setup state
                unset($_SESSION['2fa_pending_setup']);
                unset($_SESSION['2fa_temp_secret']);
                
                // Mark 2FA as completed for this session
                $_SESSION['2fa_completed'] = true;
                
                // Now complete the actual login
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
                
                // Trust this device
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
                
                logSecurityEvent('2FA_SETUP', 'تم إعداد التحقق بخطوتين', [
                    'username' => $username,
                    'user_type' => $userType
                ]);
                
                // Redirect to dashboard
                redirect('./');
                exit;
            } else {
                $error = 'خطأ في حفظ الإعدادات. حاول مرة أخرى.';
            }
        } else {
            $error = 'الرمز غير صحيح. تأكد من إدخال الرمز الظاهر في تطبيق المصادقة.';
        }
    }
}

$csrf_token = generateCSRFToken();
$totpUri = getTOTPUri($secret, $username);
$formattedSecret = formatSecretForDisplay($secret);
$qrSVG = generateQRCodeSVG($totpUri);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعداد التحقق بخطوتين - لوحة التحكم</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .setup-container {
            max-width: 500px;
            margin: 40px auto;
            padding: 20px;
        }
        .setup-box {
            background: var(--white, #fff);
            border-radius: var(--radius-lg, 16px);
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            padding: 40px 30px;
            position: relative;
        }
        .setup-badge {
            position: absolute;
            top: -12px;
            left: 50%;
            transform: translateX(-50%);
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 6px 20px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(245, 158, 11, 0.3);
            white-space: nowrap;
        }
        .setup-title {
            text-align: center;
            margin: 15px 0 5px;
            font-size: 1.4rem;
            color: var(--dark, #1a1a2e);
        }
        .setup-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 25px;
            font-size: 0.9rem;
            line-height: 1.6;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 25px;
        }
        .step-dot {
            width: 10px; height: 10px;
            border-radius: 50%;
            background: #e5e7eb;
            transition: background 0.3s;
        }
        .step-dot.active { background: #f59e0b; }
        .step-dot.done { background: #10b981; }
        .qr-section {
            text-align: center;
            margin: 20px 0;
        }
        .qr-section svg {
            max-width: 220px;
            height: auto;
            border: 3px solid #f3f4f6;
            border-radius: 12px;
            padding: 8px;
            background: white;
        }
        .manual-key {
            margin: 15px 0;
            padding: 15px;
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 10px;
            text-align: center;
        }
        .manual-key-label {
            font-size: 0.8rem;
            color: #64748b;
            margin-bottom: 8px;
        }
        .manual-key-value {
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 2px;
            color: #1e293b;
            word-break: break-all;
            direction: ltr;
            text-align: center;
            user-select: all;
        }
        .copy-btn {
            display: inline-block;
            margin-top: 8px;
            padding: 4px 14px;
            font-size: 0.75rem;
            background: #e2e8f0;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            color: #475569;
            transition: all 0.2s;
        }
        .copy-btn:hover { background: #cbd5e1; }
        .copy-btn.copied { background: #10b981; color: white; }
        .instructions {
            background: linear-gradient(135deg, #eff6ff, #f0f9ff);
            border: 1px solid #bfdbfe;
            border-radius: 10px;
            padding: 15px;
            margin: 20px 0;
        }
        .instructions h4 {
            color: #1d4ed8;
            margin: 0 0 10px;
            font-size: 0.9rem;
        }
        .instructions ol {
            margin: 0;
            padding-right: 20px;
            font-size: 0.85rem;
            line-height: 1.8;
            color: #334155;
        }
        .verify-section {
            margin-top: 25px;
        }
        .code-input-group {
            display: flex;
            gap: 6px;
            justify-content: center;
            direction: ltr;
            margin: 15px 0;
        }
        .code-input-group input {
            width: 45px;
            height: 55px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            transition: border-color 0.2s, box-shadow 0.2s;
            background: #f8fafc;
        }
        .code-input-group input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
            background: white;
        }
        .code-input-group input.filled {
            border-color: #10b981;
            background: #f0fdf4;
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
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        .submit-btn {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #f59e0b, #d97706);
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
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }
        .submit-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .app-links {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8rem;
            color: #94a3b8;
        }
        .app-links a {
            color: #3b82f6;
            text-decoration: none;
        }
        .app-links a:hover { text-decoration: underline; }
    </style>
</head>
<body class="login-page">
    <div class="setup-container">
        <div class="setup-box">
            <div class="setup-badge">🔐 إعداد مطلوب</div>
            
            <h2 class="setup-title">التحقق بخطوتين (2FA)</h2>
            <p class="setup-subtitle">
                لحماية حسابك، يجب إعداد التحقق بخطوتين باستخدام تطبيق المصادقة
            </p>
            
            <div class="step-indicator">
                <div class="step-dot active"></div>
                <div class="step-dot"></div>
                <div class="step-dot"></div>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="instructions">
                <h4>📋 خطوات الإعداد:</h4>
                <ol>
                    <li>حمّل تطبيق <strong>Google Authenticator</strong> على هاتفك</li>
                    <li>امسح رمز QR أدناه بالتطبيق</li>
                    <li>أدخل الرمز المكون من 6 أرقام</li>
                </ol>
            </div>
            
            <div class="qr-section">
                <?= $qrSVG ?>
            </div>
            
            <div class="manual-key">
                <div class="manual-key-label">أو أدخل هذا المفتاح يدوياً في التطبيق:</div>
                <div class="manual-key-value" id="secretKey"><?= htmlspecialchars($formattedSecret) ?></div>
                <button class="copy-btn" onclick="copyKey()" id="copyBtn">📋 نسخ المفتاح</button>
            </div>
            
            <div class="verify-section">
                <form method="POST" id="verifyForm">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="text" name="totp_code" id="hiddenCode" class="hidden-code" maxlength="6" autocomplete="off">
                    
                    <label style="display: block; text-align: center; font-weight: 600; margin-bottom: 5px; color: #334155;">
                        أدخل الرمز من التطبيق:
                    </label>
                    
                    <div class="code-input-group" id="codeInputs">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="0" autofocus>
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
                        <input type="text" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submitBtn" disabled>
                        ✅ تفعيل التحقق بخطوتين
                    </button>
                </form>
            </div>
            
            <div class="app-links">
                📱 حمّل Google Authenticator: 
                <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank">Android</a> | 
                <a href="https://apps.apple.com/app/google-authenticator/id388497605" target="_blank">iOS</a>
            </div>
            
            <div style="margin-top: 20px; text-align: center; padding-top: 15px; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 0.8rem; color: #94a3b8; margin-bottom: 10px;">لا تملك التطبيق حالياً؟ يمكنك الخروج والعودة لاحقاً</p>
                <a href="logout" style="display: inline-flex; align-items: center; gap: 6px; padding: 10px 24px; background: #fee2e2; color: #dc2626; border-radius: 8px; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: all 0.2s;" onmouseover="this.style.background='#fecaca'" onmouseout="this.style.background='#fee2e2'">🚪 تسجيل خروج</a>
            </div>
        </div>
    </div>
    
    <script>
        // Code input handling
        const inputs = document.querySelectorAll('#codeInputs input');
        const hiddenCode = document.getElementById('hiddenCode');
        const submitBtn = document.getElementById('submitBtn');
        
        function updateHiddenCode() {
            let code = '';
            inputs.forEach(inp => { code += inp.value; });
            hiddenCode.value = code;
            submitBtn.disabled = code.length !== 6;
            
            // Update filled class
            inputs.forEach(inp => {
                inp.classList.toggle('filled', inp.value.length > 0);
            });
        }
        
        inputs.forEach((input, idx) => {
            input.addEventListener('input', function(e) {
                let v = this.value.replace(/[^0-9]/g, '');
                this.value = v;
                updateHiddenCode();
                
                if (v && idx < inputs.length - 1) {
                    inputs[idx + 1].focus();
                }
                
                // Auto-submit when all 6 digits entered
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
            
            // Handle paste
            input.addEventListener('paste', function(e) {
                e.preventDefault();
                const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
                for (let i = 0; i < Math.min(paste.length, 6); i++) {
                    inputs[i].value = paste[i];
                }
                updateHiddenCode();
                if (paste.length >= 6) {
                    inputs[5].focus();
                    setTimeout(() => { document.getElementById('verifyForm').submit(); }, 200);
                } else {
                    inputs[Math.min(paste.length, 5)].focus();
                }
            });
        });
        
        // Copy secret key
        function copyKey() {
            const key = document.getElementById('secretKey').textContent.replace(/\s/g, '');
            navigator.clipboard.writeText(key).then(() => {
                const btn = document.getElementById('copyBtn');
                btn.textContent = '✅ تم النسخ';
                btn.classList.add('copied');
                setTimeout(() => {
                    btn.textContent = '📋 نسخ المفتاح';
                    btn.classList.remove('copied');
                }, 2000);
            });
        }
        
        // Step indicator animation
        setTimeout(() => { document.querySelectorAll('.step-dot')[0].classList.add('done'); document.querySelectorAll('.step-dot')[1].classList.add('active'); }, 1000);
    </script>
</body>
</html>
