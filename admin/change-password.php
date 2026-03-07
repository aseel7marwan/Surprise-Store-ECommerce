<?php
require_once '../includes/config.php';
require_once '../includes/security.php';

/**
 * صفحة تغيير كلمة المرور - آمنة وبسيطة
 * تعمل بدون تسجيل دخول
 * Access: http://localhost/surprise/admin/change-password.php
 */

$credentialsFile = ROOT_PATH . 'data/admin_credentials.php';
$message = '';
$messageType = '';

// قراءة البيانات الحالية
function getAdminCredentials() {
    global $credentialsFile;
    if (file_exists($credentialsFile)) {
        return include $credentialsFile;
    }
    return ['username' => 'admin', 'password_hash' => 'YOUR_PASSWORD_HASH_HERE'];
}

// حفظ البيانات الجديدة مع الهاش
function saveAdminCredentials($username, $password) {
    global $credentialsFile;
    
    // إنشاء الهاش
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    // كتابة الملف
    $content = "<?php\n";
    $content .= "/**\n";
    $content .= " * ملف بيانات الأدمن - محمي بالهاش\n";
    $content .= " * يتم تعديله تلقائياً من صفحة تغيير كلمة المرور\n";
    $content .= " */\n\n";
    $content .= "return [\n";
    $content .= "    'username' => '" . addslashes($username) . "',\n";
    $content .= "    'password_hash' => '" . $hash . "'\n";
    $content .= "];\n";
    
    return file_put_contents($credentialsFile, $content) !== false;
}

$credentials = getAdminCredentials();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newUsername = trim(isset($_POST['new_username']) ? $_POST['new_username'] : '');
    $newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validation
    if (empty($newUsername) || strlen($newUsername) < 3) {
        $message = '❌ اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
        $messageType = 'error';
    } elseif (strlen($newPassword) < 6) {
        $message = '❌ كلمة المرور يجب أن تكون 6 أحرف على الأقل';
        $messageType = 'error';
    } elseif ($newPassword !== $confirmPassword) {
        $message = '❌ كلمة المرور وتأكيدها غير متطابقتين';
        $messageType = 'error';
    } else {
        if (saveAdminCredentials($newUsername, $newPassword)) {
            $message = '✅ تم تغيير بيانات الدخول بنجاح!';
            $messageType = 'success';
            
            // Clear any existing login attempts
            $ip = getClientIP();
            $key = 'login_attempts_' . md5($ip);
            unset($_SESSION[$key]);
            
            // Log the change
            logSecurityEvent('PASSWORD_CHANGE', 'تم تغيير بيانات الأدمن');
            
            // Reload credentials
            $credentials = getAdminCredentials();
        } else {
            $message = '❌ خطأ في حفظ الملف. تأكد من صلاحيات الكتابة.';
            $messageType = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تغيير بيانات الدخول - Surprise!</title>
    <meta name="robots" content="noindex, nofollow">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Cairo', sans-serif;
            background: linear-gradient(135deg, #F8F9FA 0%, #FFFFFF 50%, #FFF0F5 100%);
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: #FFFFFF;
            padding: 40px;
            border-radius: 16px;
            max-width: 450px;
            width: 100%;
            border: 1px solid rgba(233, 30, 140, 0.2);
            box-shadow: 0 20px 60px rgba(233, 30, 140, 0.1);
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }
        h1 {
            color: #E91E8C;
            margin-bottom: 10px;
            text-align: center;
            font-size: 1.5rem;
        }
        .subtitle {
            text-align: center;
            color: #888;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }
        .current-info {
            background: rgba(76, 175, 80, 0.1);
            border: 1px solid rgba(76, 175, 80, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 25px;
        }
        .current-info h3 {
            color: #4CAF50;
            font-size: 0.9rem;
            margin-bottom: 10px;
        }
        .current-info p {
            color: #fff;
            font-size: 0.95rem;
            margin: 5px 0;
        }
        .current-info code {
            background: #F8F9FA;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: monospace;
            color: #212529;
        }
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            margin-top: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #aaa;
            font-size: 0.9rem;
        }
        input {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid #DEE2E6;
            border-radius: 10px;
            background: #FFFFFF;
            color: #212529;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
        }
        input:focus {
            outline: none;
            border-color: #E91E8C;
            box-shadow: 0 0 15px rgba(233, 30, 140, 0.15);
        }
        input::placeholder {
            color: #555;
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #E91E8C, #FF6BB3);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(233, 30, 140, 0.3);
        }
        .message {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            text-align: center;
            font-weight: 600;
        }
        .success { 
            background: rgba(76, 175, 80, 0.15); 
            color: #4CAF50; 
            border: 1px solid rgba(76, 175, 80, 0.3);
        }
        .error { 
            background: rgba(244, 67, 54, 0.15); 
            color: #F44336; 
            border: 1px solid rgba(244, 67, 54, 0.3);
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 25px;
            color: #E91E8C;
            text-decoration: none;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }
        .back-link:hover {
            color: #C41574;
        }
        .toggle-password {
            position: relative;
        }
        .toggle-password .show-btn {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 5px;
            margin: 0;
            width: auto;
        }
        .toggle-password .show-btn:hover {
            color: #E91E8C;
            transform: translateY(-50%);
            box-shadow: none;
        }
        .note {
            background: rgba(233, 30, 140, 0.05);
            border: 1px solid rgba(233, 30, 140, 0.15);
            border-radius: 10px;
            padding: 15px;
            margin-top: 25px;
            font-size: 0.85rem;
            color: #6C757D;
            line-height: 1.6;
        }
        .note strong {
            color: #E91E8C;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="../images/logo.jpg" alt="Surprise!">
        </div>
        
        <h1>🔐 تغيير بيانات الدخول</h1>
        <p class="subtitle">غيّر اسم المستخدم وكلمة المرور</p>
        
        <div class="current-info">
            <h3>📋 البيانات الحالية:</h3>
            <p>👤 المستخدم: <code><?= htmlspecialchars($credentials['username']) ?></code></p>
            <p>🔑 كلمة المرور: <code>••••••••</code> <small style="color:#666">(مشفرة)</small></p>
            <div class="security-badge">🔒 محمية بالتشفير</div>
        </div>
        
        <?php if ($message): ?>
        <div class="message <?= $messageType ?>">
            <?= $message ?>
        </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>اسم المستخدم الجديد:</label>
                <input type="text" name="new_username" required 
                       value="<?= htmlspecialchars($credentials['username']) ?>"
                       placeholder="أدخل اسم المستخدم" minlength="3">
            </div>
            
            <div class="form-group">
                <label>كلمة المرور الجديدة:</label>
                <div class="toggle-password">
                    <input type="password" name="new_password" id="new_password" required 
                           placeholder="أدخل كلمة المرور الجديدة" minlength="6">
                    <button type="button" class="show-btn" onclick="togglePassword('new_password', this)">👁️</button>
                </div>
            </div>
            
            <div class="form-group">
                <label>تأكيد كلمة المرور:</label>
                <div class="toggle-password">
                    <input type="password" name="confirm_password" id="confirm_password" required 
                           placeholder="أعد كتابة كلمة المرور">
                    <button type="button" class="show-btn" onclick="togglePassword('confirm_password', this)">👁️</button>
                </div>
            </div>
            
            <button type="submit">💾 حفظ التغييرات</button>
        </form>
        
        <a href="login" class="back-link">← العودة لتسجيل الدخول</a>
        
        <div class="note">
            <strong>🛡️ ملاحظة أمنية:</strong><br>
            كلمة المرور تُخزن مشفرة باستخدام bcrypt. 
            لا يمكن لأحد رؤية كلمة المرور الفعلية حتى لو وصل للملفات.
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🙈';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }
    </script>
</body>
</html>
