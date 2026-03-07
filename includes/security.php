<?php
/**
 * Surprise! Store - Security Functions
 * Rate Limiting, Session Security, etc.
 */

/**
 * ═══════════════════════════════════════════════════════════
 * RATE LIMITING - حد لعدد المحاولات
 * ═══════════════════════════════════════════════════════════
 */

// Rate limiting settings
define('LOGIN_MAX_ATTEMPTS', 5);        // Max login attempts
define('LOGIN_LOCKOUT_TIME', 900);      // 15 minutes lockout
define('API_MAX_REQUESTS', 60);         // Max API requests per minute
define('ORDER_MAX_PER_IP', 10);         // Max orders per IP per hour

/**
 * Check if IP is rate limited for login
 */
function isLoginRateLimited() {
    $ip = getClientIP();
    $key = 'login_attempts_' . md5($ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if lockout time passed
    if (time() - $data['first_attempt'] > LOGIN_LOCKOUT_TIME) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
        return false;
    }
    
    return $data['count'] >= LOGIN_MAX_ATTEMPTS;
}

/**
 * Record a failed login attempt
 */
function recordFailedLogin() {
    $ip = getClientIP();
    $key = 'login_attempts_' . md5($ip);
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $_SESSION[$key]['count']++;
}

/**
 * Clear login attempts on successful login
 */
function clearLoginAttempts() {
    $ip = getClientIP();
    $key = 'login_attempts_' . md5($ip);
    unset($_SESSION[$key]);
}

/**
 * Get remaining lockout time in seconds
 */
function getRemainingLockoutTime() {
    $ip = getClientIP();
    $key = 'login_attempts_' . md5($ip);
    
    if (!isset($_SESSION[$key])) {
        return 0;
    }
    
    $data = $_SESSION[$key];
    $elapsed = time() - $data['first_attempt'];
    $remaining = LOGIN_LOCKOUT_TIME - $elapsed;
    
    return max(0, $remaining);
}

/**
 * Check API rate limit
 */
function isApiRateLimited($action = 'general') {
    $ip = getClientIP();
    $key = 'api_' . $action . '_' . md5($ip);
    $minute = date('Y-m-d-H-i');
    
    if (!isset($_SESSION[$key]) || $_SESSION[$key]['minute'] !== $minute) {
        $_SESSION[$key] = ['count' => 0, 'minute' => $minute];
    }
    
    return $_SESSION[$key]['count'] >= API_MAX_REQUESTS;
}

/**
 * Record API request
 */
function recordApiRequest($action = 'general') {
    $ip = getClientIP();
    $key = 'api_' . $action . '_' . md5($ip);
    $minute = date('Y-m-d-H-i');
    
    if (!isset($_SESSION[$key]) || $_SESSION[$key]['minute'] !== $minute) {
        $_SESSION[$key] = ['count' => 0, 'minute' => $minute];
    }
    
    $_SESSION[$key]['count']++;
}

/**
 * Check order rate limit per IP
 */
function isOrderRateLimited() {
    $ip = getClientIP();
    $key = 'orders_' . md5($ip);
    $hour = date('Y-m-d-H');
    
    if (!isset($_SESSION[$key]) || $_SESSION[$key]['hour'] !== $hour) {
        $_SESSION[$key] = ['count' => 0, 'hour' => $hour];
    }
    
    return $_SESSION[$key]['count'] >= ORDER_MAX_PER_IP;
}

/**
 * Record order submission
 */
function recordOrder() {
    $ip = getClientIP();
    $key = 'orders_' . md5($ip);
    $hour = date('Y-m-d-H');
    
    if (!isset($_SESSION[$key]) || $_SESSION[$key]['hour'] !== $hour) {
        $_SESSION[$key] = ['count' => 0, 'hour' => $hour];
    }
    
    $_SESSION[$key]['count']++;
}

/**
 * Get client IP address
 */
function getClientIP() {
    $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
    
    // Check for proxy headers
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

/**
 * ═══════════════════════════════════════════════════════════
 * SESSION SECURITY - أمان الجلسات مع نظام Remember Me
 * ═══════════════════════════════════════════════════════════
 */

// Session timeout settings - EXTENDED for user convenience
define('SESSION_TIMEOUT', 604800);      // 7 days of inactivity (was 24 hours)
define('SESSION_MAX_LIFETIME', 2592000); // 30 days maximum session (was 24 hours)
define('REMEMBER_ME_DURATION', 2592000); // 30 days for remember me cookie

/**
 * Secure session configuration
 * Called after session_start() in config.php
 */
function initSecureSession() {
    // Check for remember me cookie first
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        checkRememberMeCookie();
    }
    
    // Regenerate session ID periodically but not too frequently
    // Too frequent regeneration can cause session loss on shared hosting
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
    } elseif (time() - $_SESSION['last_regeneration'] > 3600) { // Every 1 hour
        // Only regenerate if we have an active session to preserve
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
            // Safely regenerate - keep old session data
            session_regenerate_id(false); // false = don't delete old session file immediately
        }
        $_SESSION['last_regeneration'] = time();
    }
    
    // Auto-record current session if logged in but not recorded
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        ensureCurrentSessionRecorded();
        // Update last activity in database
        updateSessionActivity();
    }
    
    // ═══ 2FA ENFORCEMENT ═══
    // If user has pending 2FA setup or verify, redirect to the right page
    // This prevents direct URL access to dashboard when 2FA is incomplete
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
    $allowed2FAPages = ['login.php', 'setup-2fa.php', 'verify-2fa.php', 'logout.php'];
    
    if (!in_array($currentPage, $allowed2FAPages)) {
        if (!empty($_SESSION['2fa_pending_setup'])) {
            header('Location: setup-2fa');
            exit;
        }
        if (!empty($_SESSION['2fa_pending_verify'])) {
            header('Location: verify-2fa');
            exit;
        }
    }
}

/**
 * Check session validity for admin
 */
function validateAdminSession() {
    // Check if session exists
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) {
        // Try remember me cookie
        if (checkRememberMeCookie()) {
            return true;
        }
        return false;
    }
    
    $now = time();
    
    // Check maximum session lifetime (30 days from login)
    if (isset($_SESSION['admin_login_time'])) {
        if ($now - $_SESSION['admin_login_time'] > SESSION_MAX_LIFETIME) {
            destroyAdminSession();
            return false;
        }
    }
    
    // Check inactivity timeout (7 days of no activity)
    if (isset($_SESSION['admin_last_activity'])) {
        if ($now - $_SESSION['admin_last_activity'] > SESSION_TIMEOUT) {
            destroyAdminSession();
            return false;
        }
    }
    
    // Check user agent consistency (security measure)
    if (isset($_SESSION['admin_user_agent'])) {
        if ($_SESSION['admin_user_agent'] !== md5(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '')) {
            destroyAdminSession();
            return false;
        }
    }
    
    // Check if session was force-logged-out or blocked from admin panel
    if (!isCurrentSessionValid()) {
        destroyAdminSession();
        return false;
    }
    
    // 2FA check: Auto-complete for trusted devices, otherwise let initSecureSession handle redirects
    // IMPORTANT: Do NOT destroy session here — that causes login loops.
    // initSecureSession() handles 2FA redirects safely without session destruction.
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
    $allowed2FAPages = ['login.php', 'setup-2fa.php', 'verify-2fa.php', 'logout.php'];
    if (!in_array($currentPage, $allowed2FAPages) && empty($_SESSION['2fa_completed'])) {
        $userType = isset($_SESSION['staff_id']) ? 'staff' : 'admin';
        $userId = $_SESSION['staff_id'] ?? null;
        $username = $_SESSION['staff_username'] ?? ($_SESSION['admin_username'] ?? 'admin');
        
        if (is2FAEnabled($userType, $userId, $username)) {
            $deviceId = getDeviceId();
            $currentIP = getClientIP();
            if (isTrustedDevice($username, $deviceId, $currentIP)) {
                $_SESSION['2fa_completed'] = true;
            }
            // If not trusted, do NOT destroy session — initSecureSession will redirect
        }
    }
    
    // Update last activity time
    $_SESSION['admin_last_activity'] = $now;
    
    return true;
}

/**
 * Get remaining session time in seconds
 */
function getSessionRemainingTime() {
    if (!isset($_SESSION['admin_last_activity'])) {
        return 0;
    }
    $elapsed = time() - $_SESSION['admin_last_activity'];
    $remaining = SESSION_TIMEOUT - $elapsed;
    return max(0, $remaining);
}

/**
 * Check if current PHP session has been force-logged-out by admin
 * This enables immediate termination when admin uses Force Logout
 * SAFE: Returns true if any error occurs to prevent site breakage
 */
function isCurrentSessionValid() {
    // Safety first - if anything fails, assume valid
    try {
        if (!function_exists('db') || !isDbConnected()) {
            return true;
        }
        
        $currentSessionId = session_id();
        if (empty($currentSessionId)) {
            return true;
        }
        
        $pdo = db();
        if (!$pdo) {
            return true;
        }
        
        // Check if forced_logouts table exists first
        try {
            $tableCheck = $pdo->query("SHOW TABLES LIKE 'forced_logouts'");
            if ($tableCheck->rowCount() === 0) {
                // Table doesn't exist yet - that's OK, skip this check
                return true;
            }
            
            // Check if session was force-logged-out
            $stmt = $pdo->prepare("SELECT id FROM forced_logouts WHERE php_session_id = ? LIMIT 1");
            $stmt->execute([$currentSessionId]);
            
            if ($stmt->fetch()) {
                // Session was force-logged-out - invalidate and clean up
                $pdo->prepare("DELETE FROM forced_logouts WHERE php_session_id = ?")->execute([$currentSessionId]);
                return false;
            }
        } catch (Exception $e) {
            // Table might not exist - that's fine, continue
        }
        
        // Check login_sessions table for blocked/admin_logout status
        try {
            $checkStatus = $pdo->prepare("SELECT status FROM login_sessions WHERE session_id = ? LIMIT 1");
            $checkStatus->execute([$currentSessionId]);
            $session = $checkStatus->fetch();
            
            if ($session && in_array($session['status'], ['blocked', 'admin_logout'])) {
                return false;
            }
        } catch (Exception $e) {
            // Table might not exist or column missing - continue
        }
        
        return true;
        
    } catch (Exception $e) {
        // Any error = assume valid to prevent lockout
        return true;
    }
}

/**
 * Create admin session with security measures
 * @param bool $rememberMe Whether to set remember me cookie
 */
function createAdminSession($rememberMe = true) {
    // Preserve 2FA state through session regeneration
    $preserve2FA = $_SESSION['2fa_completed'] ?? null;
    session_regenerate_id(true);
    if ($preserve2FA) $_SESSION['2fa_completed'] = $preserve2FA;
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_user_agent'] = md5(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    $_SESSION['admin_ip'] = getClientIP();
    clearLoginAttempts();
    
    // Set remember me cookie for persistent login
    if ($rememberMe) {
        setRememberMeCookie('admin', null);
    }
}

/**
 * Destroy admin session and all remember me tokens
 */
function destroyAdminSession() {
    // Clear remember me cookie
    clearRememberMeCookie();
    
    $_SESSION['admin_logged_in'] = false;
    unset($_SESSION['admin_login_time']);
    unset($_SESSION['admin_last_activity']);
    unset($_SESSION['admin_user_agent']);
    unset($_SESSION['admin_ip']);
    unset($_SESSION['staff_id']);
    unset($_SESSION['staff_username']);
    unset($_SESSION['staff_name']);
    unset($_SESSION['staff_permissions']);
}

/**
 * ═══════════════════════════════════════════════════════════
 * REMEMBER ME SYSTEM - نظام تذكرني الآمن
 * ═══════════════════════════════════════════════════════════
 */

/**
 * Set remember me cookie with secure token
 * @param string $userType 'admin' or 'staff'
 * @param int|null $staffId Staff ID if user is staff
 */
function setRememberMeCookie($userType = 'admin', $staffId = null) {
    // Generate secure token
    $selector = bin2hex(random_bytes(12)); // 24 chars
    $validator = bin2hex(random_bytes(32)); // 64 chars
    $hashedValidator = hash('sha256', $validator);
    
    $expires = time() + REMEMBER_ME_DURATION;
    
    // Store token in database
    if (function_exists('db') && isDbConnected()) {
        try {
            // Create remember_tokens table if not exists
            db()->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_type ENUM('admin', 'staff') NOT NULL,
                staff_id INT NULL,
                selector VARCHAR(24) NOT NULL UNIQUE,
                hashed_validator VARCHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_selector (selector),
                INDEX idx_expires (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            
            // Insert token
            $stmt = db()->prepare("INSERT INTO remember_tokens (user_type, staff_id, selector, hashed_validator, expires_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$userType, $staffId, $selector, $hashedValidator, date('Y-m-d H:i:s', $expires)]);
            
            // Set cookie with selector:validator
            $cookieValue = $selector . ':' . $validator;
            $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
            
            setcookie('surprise_remember', $cookieValue, [
                'expires' => $expires,
                'path' => '/',
                'domain' => '',
                'secure' => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
        } catch (Exception $e) {
            // Silently fail - remember me is optional
            error_log("Remember me cookie failed: " . $e->getMessage());
        }
    }
}

/**
 * Check remember me cookie and restore session
 * @return bool Whether session was restored
 */
function checkRememberMeCookie() {
    if (!isset($_COOKIE['surprise_remember'])) {
        return false;
    }
    
    $cookie = $_COOKIE['surprise_remember'];
    $parts = explode(':', $cookie);
    
    if (count($parts) !== 2) {
        clearRememberMeCookie();
        return false;
    }
    
    list($selector, $validator) = $parts;
    
    if (!function_exists('db') || !isDbConnected()) {
        return false;
    }
    
    try {
        // Find token by selector
        $stmt = db()->prepare("SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()");
        $stmt->execute([$selector]);
        $token = $stmt->fetch();
        
        if (!$token) {
            clearRememberMeCookie();
            return false;
        }
        
        // Verify validator
        $hashedValidator = hash('sha256', $validator);
        if (!hash_equals($token['hashed_validator'], $hashedValidator)) {
            // Possible token theft - delete all tokens for this user
            if ($token['user_type'] === 'staff' && $token['staff_id']) {
                $stmt = db()->prepare("DELETE FROM remember_tokens WHERE staff_id = ?");
                $stmt->execute([$token['staff_id']]);
            } else {
                $stmt = db()->prepare("DELETE FROM remember_tokens WHERE user_type = 'admin' AND staff_id IS NULL");
                $stmt->execute();
            }
            logSecurityEvent('TOKEN_THEFT', 'Possible remember me token theft detected');
            clearRememberMeCookie();
            return false;
        }
        
        // Token valid - restore session
        if ($token['user_type'] === 'staff' && $token['staff_id']) {
            $staff = getStaffById($token['staff_id']);
            if ($staff && $staff['is_active']) {
                createStaffSession($staff['id'], $staff);
                // Rotate token for security
                rotateRememberMeToken($selector, 'staff', $staff['id']);
                return true;
            }
        } else {
            // Admin user
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_login_time'] = time();
            $_SESSION['admin_last_activity'] = time();
            $_SESSION['admin_user_agent'] = md5(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
            $_SESSION['admin_ip'] = getClientIP();
            // Rotate token for security
            rotateRememberMeToken($selector, 'admin', null);
            return true;
        }
        
    } catch (Exception $e) {
        error_log("Remember me check failed: " . $e->getMessage());
    }
    
    return false;
}

/**
 * Rotate remember me token (for security after each use)
 */
function rotateRememberMeToken($oldSelector, $userType, $staffId) {
    try {
        // Delete old token
        $stmt = db()->prepare("DELETE FROM remember_tokens WHERE selector = ?");
        $stmt->execute([$oldSelector]);
        
        // Create new token
        setRememberMeCookie($userType, $staffId);
    } catch (Exception $e) {
        // Silently fail
    }
}

/**
 * Clear remember me cookie and token
 */
function clearRememberMeCookie() {
    if (isset($_COOKIE['surprise_remember'])) {
        $parts = explode(':', $_COOKIE['surprise_remember']);
        if (count($parts) === 2) {
            $selector = $parts[0];
            if (function_exists('db') && isDbConnected()) {
                try {
                    $stmt = db()->prepare("DELETE FROM remember_tokens WHERE selector = ?");
                    $stmt->execute([$selector]);
                } catch (Exception $e) {
                    // Silently fail
                }
            }
        }
        
        // Clear cookie
        setcookie('surprise_remember', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
}

/**
 * Invalidate all remember me tokens for a user (on password change)
 * @param string $userType 'admin' or 'staff'
 * @param int|null $staffId Staff ID if user is staff
 */
function invalidateAllRememberTokens($userType = 'admin', $staffId = null) {
    if (!function_exists('db') || !isDbConnected()) {
        return;
    }
    
    try {
        if ($userType === 'staff' && $staffId) {
            $stmt = db()->prepare("DELETE FROM remember_tokens WHERE staff_id = ?");
            $stmt->execute([$staffId]);
        } else {
            $stmt = db()->prepare("DELETE FROM remember_tokens WHERE user_type = 'admin' AND staff_id IS NULL");
            $stmt->execute();
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

/**
 * Clean up expired tokens (call periodically)
 */
function cleanupExpiredTokens() {
    if (!function_exists('db') || !isDbConnected()) {
        return;
    }
    
    try {
        db()->exec("DELETE FROM remember_tokens WHERE expires_at < NOW()");
    } catch (Exception $e) {
        // Silently fail
    }
}

/**
 * ═══════════════════════════════════════════════════════════
 * INPUT SANITIZATION - تنظيف المدخلات
 * ═══════════════════════════════════════════════════════════
 */

/**
 * Sanitize input string (only if not already defined in functions.php)
 */
if (!function_exists('sanitize')) {
    function sanitize($input) {
        if (is_array($input)) {
            return array_map('sanitize', $input);
        }
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Sanitize for SQL (use with PDO prepared statements)
 */
function sanitizeSQL($input) {
    return preg_replace('/[^\p{L}\p{N}\s\-\_\.@]/u', '', $input);
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Iraqi format)
 */
function isValidPhone($phone) {
    // Remove spaces and dashes
    $phone = preg_replace('/[\s\-]/', '', $phone);
    // Check Iraqi format: 07XX or +9647XX
    return preg_match('/^(\+964|0)7[0-9]{9}$/', $phone);
}

/**
 * ═══════════════════════════════════════════════════════════
 * SECURITY HEADERS - رؤوس الأمان
 * ═══════════════════════════════════════════════════════════
 */

/**
 * Set security headers
 */
function setSecurityHeaders() {
    // Prevent clickjacking
    header('X-Frame-Options: SAMEORIGIN');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // XSS Protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Content Security Policy (balanced for compatibility)
    // Allows: inline scripts/styles, Google Fonts, Analytics, FB Pixel, images
    $csp = "default-src 'self'; ";
    $csp .= "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://www.googletagmanager.com https://www.google-analytics.com https://connect.facebook.net; ";
    $csp .= "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; ";
    $csp .= "font-src 'self' https://fonts.gstatic.com data:; ";
    $csp .= "img-src 'self' data: https: blob:; ";
    $csp .= "connect-src 'self' https://www.google-analytics.com https://www.facebook.com; ";
    $csp .= "frame-src 'self' https://www.facebook.com;";
    
    header("Content-Security-Policy: " . $csp);
}

/**
 * ═══════════════════════════════════════════════════════════
 * FILE UPLOAD SECURITY - أمان رفع الملفات
 * ═══════════════════════════════════════════════════════════
 */

/**
 * Validate uploaded file
 */
function validateUploadedFile($file, $maxSize = null, $allowedTypes = null) {
    $maxSize = ($maxSize !== null) ? $maxSize : MAX_FILE_SIZE;
    $allowedTypes = ($allowedTypes !== null) ? $allowedTypes : ALLOWED_EXTENSIONS;
    
    $errors = [];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'حدث خطأ أثناء رفع الملف';
        return ['success' => false, 'errors' => $errors];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        $errors[] = 'حجم الملف كبير جداً. الحد الأقصى: ' . ($maxSize / 1024 / 1024) . 'MB';
    }
    
    // Check extension
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes)) {
        $errors[] = 'نوع الملف غير مسموح. الأنواع المسموحة: ' . implode(', ', $allowedTypes);
    }
    
    // Verify MIME type
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $allowedMimes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp'
    ];
    
    if (!isset($allowedMimes[$ext]) || $mimeType !== $allowedMimes[$ext]) {
        $errors[] = 'محتوى الملف لا يتطابق مع نوعه';
    }
    
    if (!empty($errors)) {
        return ['success' => false, 'errors' => $errors];
    }
    
    return ['success' => true];
}

/**
 * Generate secure filename
 */
function generateSecureFilename($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid('img_', true) . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
}

/**
 * ═══════════════════════════════════════════════════════════
 * LOGGING - سجلات الأمان
 * ═══════════════════════════════════════════════════════════
 */

/**
 * Log security event
 */
function logSecurityEvent($type, $message, $data = []) {
    $logFile = ROOT_PATH . 'data/security.log';
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'ip' => getClientIP(),
        'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Unknown',
        'message' => $message,
        'data' => $data
    ];
    
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
    
    // Append to log file (create if not exists)
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * Log failed login attempt
 */
function logFailedLogin($username) {
    logSecurityEvent('FAILED_LOGIN', 'فشل تسجيل الدخول', ['username' => $username]);
}

/**
 * Log successful login
 */
function logSuccessfulLogin($username) {
    logSecurityEvent('LOGIN', 'تسجيل دخول ناجح', ['username' => $username]);
}

/**
 * Log suspicious activity
 */
function logSuspiciousActivity($reason) {
    logSecurityEvent('SUSPICIOUS', 'نشاط مشبوه', ['reason' => $reason]);
}

/**
 * ═══════════════════════════════════════════════════════════
 * STAFF MANAGEMENT - نظام إدارة الموظفين والصلاحيات
 * ═══════════════════════════════════════════════════════════
 */

/**
 * قائمة الصلاحيات المتاحة
 */
function getAvailablePermissions() {
    return [
        'dashboard' => 'الرئيسية',
        'reports' => 'التقارير',
        'sales' => 'المبيعات',
        'orders' => 'الطلبات',
        'products' => 'المنتجات',
        'categories' => 'الأقسام',
        'coupons' => 'الكوبونات',
        'reviews' => 'التقييمات',
        'banners' => 'البانرات',
        'backup' => 'النسخ الاحتياطي',
        'staff' => 'إدارة الموظفين',
        'security_log' => 'سجل الأمان',
        'staff_manage' => 'إدارة الموظفين (متقدم)',
        'settings_manage' => 'الإعدادات (متقدم)'
    ];
}

/**
 * التحقق هل المستخدم الحالي مدير (ليس موظف)
 */
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] 
           && (!isset($_SESSION['staff_id']) || $_SESSION['staff_id'] === null);
}

/**
 * التحقق هل المستخدم موظف
 */
function isStaff() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] 
           && isset($_SESSION['staff_id']) && $_SESSION['staff_id'] !== null;
}

/**
 * التحقق من صلاحية معينة للموظف الحالي
 */
function hasPermission($permission) {
    // المدير لديه كل الصلاحيات
    if (isAdmin()) {
        return true;
    }
    
    // الموظف - نتحقق من صلاحياته
    if (isStaff() && isset($_SESSION['staff_permissions'])) {
        $permissions = $_SESSION['staff_permissions'];
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    return false;
}

/**
 * التحقق من صلاحيات Super Admin الكاملة
 * المدير الرئيسي = true دائماً
 * الموظف = يجب أن يملك staff_manage + settings_manage معاً
 */
function isFullAdmin() {
    // المدير الرئيسي لديه كل الصلاحيات
    if (isAdmin()) return true;
    
    // الموظف: يجب أن يملك الشرطين معاً
    if (isStaff() && isset($_SESSION['staff_permissions'])) {
        $p = $_SESSION['staff_permissions'];
        return !empty($p['staff_manage']) && !empty($p['settings_manage']);
    }
    
    return false;
}

/**
 * التحقق من صلاحية الوصول للصفحة الحالية
 * يُستخدم في بداية كل صفحة إدارية
 */
function checkPagePermission($permission) {
    if (!validateAdminSession()) {
        redirect('login');
        exit;
    }
    
    // الإعدادات للمدير فقط
    if ($permission === 'settings' && !isAdmin()) {
        redirect('index');
        exit;
    }
    
    // إدارة الموظفين للمدير فقط
    if ($permission === 'staff' && !isAdmin()) {
        redirect('index');
        exit;
    }
    
    if (!hasPermission($permission)) {
        // عرض صفحة غير مصرح أو إعادة توجيه
        redirect('index');
        exit;
    }
}

/**
 * إنشاء جلسة موظف
 * @param bool $rememberMe Whether to set remember me cookie
 */
function createStaffSession($staffId, $staffData, $rememberMe = true) {
    // Preserve 2FA state through session regeneration
    $preserve2FA = $_SESSION['2fa_completed'] ?? null;
    session_regenerate_id(true);
    if ($preserve2FA) $_SESSION['2fa_completed'] = $preserve2FA;
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_user_agent'] = md5(isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    $_SESSION['admin_ip'] = getClientIP();
    
    // بيانات الموظف
    $_SESSION['staff_id'] = $staffId;
    $_SESSION['staff_username'] = $staffData['username'];
    $_SESSION['staff_name'] = $staffData['first_name'] . ' ' . $staffData['last_name'];
    $_SESSION['staff_permissions'] = json_decode($staffData['permissions'], true);
    
    clearLoginAttempts();
    
    // تحديث آخر دخول
    if (function_exists('db') && isDbConnected()) {
        $stmt = db()->prepare("UPDATE staff SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$staffId]);
    }
    
    // Set remember me cookie for persistent login
    if ($rememberMe) {
        setRememberMeCookie('staff', $staffId);
    }
}

/**
 * الحصول على معلومات الموظف من قاعدة البيانات
 */
function getStaffByUsername($username) {
    if (!function_exists('db') || !isDbConnected()) {
        return null;
    }
    
    try {
        $stmt = db()->prepare("SELECT * FROM staff WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * الحصول على موظف بالـ ID
 */
function getStaffById($id) {
    if (!function_exists('db') || !isDbConnected()) {
        return null;
    }
    
    try {
        $stmt = db()->prepare("SELECT * FROM staff WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * الحصول على جميع الموظفين
 */
function getAllStaff() {
    if (!function_exists('db') || !isDbConnected()) {
        return [];
    }
    
    try {
        $stmt = db()->query("SELECT * FROM staff ORDER BY created_at DESC");
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * حفظ موظف جديد أو تحديث موظف موجود
 */
function saveStaff($data) {
    if (!function_exists('db') || !isDbConnected()) {
        error_log("saveStaff: Database not connected");
        return false;
    }
    
    $pdo = db();
    
    try {
        // التأكد من وجود permissions كـ array
        $permissions = isset($data['permissions']) && is_array($data['permissions']) 
            ? $data['permissions'] 
            : [];
        
        if (!empty($data['id'])) {
            // تحديث موظف موجود
            $sql = "UPDATE staff SET 
                    username = ?, first_name = ?, last_name = ?, job_title = ?,
                    governorate = ?, district = ?, neighborhood = ?, address = ?,
                    permissions = ?, is_active = ?
                    WHERE id = ?";
            $params = [
                $data['username'] ?? '', 
                $data['first_name'] ?? '', 
                $data['last_name'] ?? '', 
                $data['job_title'] ?? '',
                $data['governorate'] ?? '', 
                $data['district'] ?? '', 
                $data['neighborhood'] ?? '', 
                $data['address'] ?? '',
                json_encode($permissions, JSON_UNESCAPED_UNICODE), 
                isset($data['is_active']) ? (int)$data['is_active'] : 1,
                $data['id']
            ];
            
            // إذا تم تغيير كلمة المرور
            if (!empty($data['password'])) {
                $sql = "UPDATE staff SET 
                        username = ?, first_name = ?, last_name = ?, job_title = ?,
                        governorate = ?, district = ?, neighborhood = ?, address = ?,
                        permissions = ?, is_active = ?, password = ?
                        WHERE id = ?";
                $params = [
                    $data['username'] ?? '', 
                    $data['first_name'] ?? '', 
                    $data['last_name'] ?? '', 
                    $data['job_title'] ?? '',
                    $data['governorate'] ?? '', 
                    $data['district'] ?? '', 
                    $data['neighborhood'] ?? '', 
                    $data['address'] ?? '',
                    json_encode($permissions, JSON_UNESCAPED_UNICODE), 
                    isset($data['is_active']) ? (int)$data['is_active'] : 1,
                    password_hash($data['password'], PASSWORD_DEFAULT),
                    $data['id']
                ];
                
                // SECURITY: Invalidate all remember me tokens when password changes
                invalidateAllRememberTokens('staff', $data['id']);
            }
            
            $stmt = $pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            // SECURITY: If staff is deactivated, invalidate their tokens
            if ($result && isset($data['is_active']) && !$data['is_active']) {
                invalidateAllRememberTokens('staff', $data['id']);
            }
            
            return $result;
        } else {
            // إنشاء موظف جديد
            $stmt = $pdo->prepare("
                INSERT INTO staff (username, password, first_name, last_name, job_title,
                                   governorate, district, neighborhood, address, permissions, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            return $stmt->execute([
                $data['username'] ?? '',
                password_hash($data['password'] ?? '', PASSWORD_DEFAULT),
                $data['first_name'] ?? '',
                $data['last_name'] ?? '',
                $data['job_title'] ?? '',
                $data['governorate'] ?? '',
                $data['district'] ?? '',
                $data['neighborhood'] ?? '',
                $data['address'] ?? '',
                json_encode($permissions, JSON_UNESCAPED_UNICODE),
                isset($data['is_active']) ? (int)$data['is_active'] : 1
            ]);
        }
    } catch (PDOException $e) {
        error_log("saveStaff PDO Error: " . $e->getMessage());
        return false;
    } catch (Exception $e) {
        error_log("saveStaff Error: " . $e->getMessage());
        return false;
    }
}

/**
 * حذف موظف
 */
function deleteStaff($id) {
    if (!function_exists('db') || !isDbConnected()) {
        return false;
    }
    
    try {
        // SECURITY: Invalidate remember me tokens first
        invalidateAllRememberTokens('staff', $id);
        
        $stmt = db()->prepare("DELETE FROM staff WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * التحقق من اسم مستخدم فريد
 */
function isUsernameUnique($username, $excludeId = null) {
    if (!function_exists('db') || !isDbConnected()) {
        return true;
    }
    
    try {
        if ($excludeId) {
            $stmt = db()->prepare("SELECT COUNT(*) FROM staff WHERE username = ? AND id != ?");
            $stmt->execute([$username, $excludeId]);
        } else {
            $stmt = db()->prepare("SELECT COUNT(*) FROM staff WHERE username = ?");
            $stmt->execute([$username]);
        }
        return $stmt->fetchColumn() == 0;
    } catch (Exception $e) {
        return true;
    }
}

/**
 * قائمة محافظات العراق
 */
function getIraqGovernorates() {
    return [
        'بغداد', 'البصرة', 'نينوى', 'أربيل', 'النجف', 'كربلاء',
        'ذي قار', 'بابل', 'ديالى', 'الأنبار', 'كركوك', 'صلاح الدين',
        'واسط', 'ميسان', 'المثنى', 'القادسية', 'دهوك', 'السليمانية', 'حلبجة'
    ];
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * TWO-FACTOR AUTHENTICATION (2FA) - نظام التحقق بخطوتين (v6.0.0)
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Run 2FA database migrations (called once, cached per request)
 */
function ensure2FAMigration() {
    static $migrated = false;
    if ($migrated) return;
    
    if (!function_exists('db') || !isDbConnected()) return;
    
    try {
        $pdo = db();
        
        // 1. Create admin_2fa table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_2fa (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                totp_secret VARCHAR(64) NOT NULL,
                totp_enabled TINYINT(1) DEFAULT 0,
                totp_setup_at DATETIME DEFAULT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // 2. Create totp_attempts table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS totp_attempts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                username VARCHAR(100) NOT NULL,
                attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip_time (ip_address, attempted_at),
                INDEX idx_user_time (username, attempted_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // 3. Add 2FA columns to staff table (safe ALTER - ignore if exists)
        try {
            $check = $pdo->query("SHOW COLUMNS FROM staff LIKE 'totp_secret'");
            if ($check->rowCount() === 0) {
                $pdo->exec("ALTER TABLE staff ADD COLUMN totp_secret VARCHAR(64) DEFAULT NULL");
                $pdo->exec("ALTER TABLE staff ADD COLUMN totp_enabled TINYINT(1) DEFAULT 0");
                $pdo->exec("ALTER TABLE staff ADD COLUMN totp_setup_at DATETIME DEFAULT NULL");
            }
        } catch (Exception $e) {}
        
        // 4. Add trust columns to known_devices table (safe ALTER)
        try {
            $check = $pdo->query("SHOW COLUMNS FROM known_devices LIKE 'is_trusted'");
            if ($check->rowCount() === 0) {
                $pdo->exec("ALTER TABLE known_devices ADD COLUMN is_trusted TINYINT(1) DEFAULT 0");
                $pdo->exec("ALTER TABLE known_devices ADD COLUMN trusted_at DATETIME DEFAULT NULL");
                $pdo->exec("ALTER TABLE known_devices ADD COLUMN last_verified_ip VARCHAR(45) DEFAULT ''");
            }
        } catch (Exception $e) {}
        
        $migrated = true;
    } catch (Exception $e) {
        error_log("2FA migration error: " . $e->getMessage());
    }
}

/**
 * Check if a user has 2FA enabled
 * @return bool
 */
function is2FAEnabled($userType, $userId, $username) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        ensure2FAMigration();
        $pdo = db();
        
        if ($userType === 'admin') {
            $stmt = $pdo->prepare("SELECT totp_enabled FROM admin_2fa WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
        } else {
            $stmt = $pdo->prepare("SELECT totp_enabled FROM staff WHERE id = ? LIMIT 1");
            $stmt->execute([$userId]);
        }
        
        $row = $stmt->fetch();
        return $row && !empty($row['totp_enabled']);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get 2FA secret for a user
 * @return string|false
 */
function get2FASecret($userType, $userId, $username) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        ensure2FAMigration();
        $pdo = db();
        
        if ($userType === 'admin') {
            $stmt = $pdo->prepare("SELECT totp_secret FROM admin_2fa WHERE username = ? AND totp_enabled = 1 LIMIT 1");
            $stmt->execute([$username]);
        } else {
            $stmt = $pdo->prepare("SELECT totp_secret FROM staff WHERE id = ? AND totp_enabled = 1 LIMIT 1");
            $stmt->execute([$userId]);
        }
        
        $row = $stmt->fetch();
        return $row ? $row['totp_secret'] : false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Save 2FA secret for a user
 * @return bool
 */
function save2FASecret($userType, $userId, $username, $secret) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        ensure2FAMigration();
        $pdo = db();
        
        if ($userType === 'admin') {
            // Upsert into admin_2fa
            $stmt = $pdo->prepare("
                INSERT INTO admin_2fa (username, totp_secret, totp_enabled, totp_setup_at)
                VALUES (?, ?, 1, NOW())
                ON DUPLICATE KEY UPDATE totp_secret = VALUES(totp_secret), totp_enabled = 1, totp_setup_at = NOW()
            ");
            return $stmt->execute([$username, $secret]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE staff SET totp_secret = ?, totp_enabled = 1, totp_setup_at = NOW() WHERE id = ?
            ");
            return $stmt->execute([$secret, $userId]);
        }
    } catch (Exception $e) {
        error_log("save2FASecret error: " . $e->getMessage());
        return false;
    }
}

/**
 * Reset 2FA for a specific user (Super Admin only)
 * Disables TOTP, clears secret, untrusts all devices, forces logout
 * @param string $targetUsername The username to reset
 * @return bool Success
 */
function process2FAReset($targetUsername) {
    if (!isFullAdmin()) return false;
    if (!function_exists('db') || !isDbConnected()) return false;
    if (empty($targetUsername)) return false;
    
    try {
        $pdo = db();
        $pdo->beginTransaction();
        
        // 1. Check if target is staff
        $stmt = $pdo->prepare("SELECT id FROM staff WHERE username = ? LIMIT 1");
        $stmt->execute([$targetUsername]);
        $staffRow = $stmt->fetch();
        
        if ($staffRow) {
            // Reset staff TOTP
            $stmt = $pdo->prepare("UPDATE staff SET totp_enabled = 0, totp_secret = '', totp_setup_at = NULL WHERE id = ?");
            $stmt->execute([$staffRow['id']]);
        }
        
        // 2. Reset in admin_2fa table (covers both admin and staff entries)
        $stmt = $pdo->prepare("UPDATE admin_2fa SET totp_enabled = 0, totp_secret = '', totp_setup_at = NULL WHERE username = ?");
        $stmt->execute([$targetUsername]);
        
        // 3. Untrust ALL devices for this user
        $stmt = $pdo->prepare("UPDATE known_devices SET is_trusted = 0, trusted_at = NULL WHERE username = ?");
        $stmt->execute([$targetUsername]);
        
        $pdo->commit();
        
        // 4. Force logout (outside transaction — contains DDL/CREATE TABLE)
        try { forceLogoutAllSessions($targetUsername); } catch (Exception $e) {}
        
        // 5. Log (outside transaction — contains DDL/CREATE TABLE)
        $adminName = $_SESSION['admin_username'] ?? ($_SESSION['staff_username'] ?? 'admin');
        logSecurityEvent('2FA_RESET', 'تم إعادة تعيين 2FA بواسطة الإدارة', [
            'target_user' => $targetUsername,
            'reset_by' => $adminName,
            'action' => 'secret_cleared+devices_untrusted+sessions_terminated'
        ]);
        
        return true;
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
        error_log("process2FAReset error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if a device is trusted for a user AND same IP
 * @return bool True if trusted and IP matches
 */
function isTrustedDevice($username, $deviceId, $currentIP) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        ensure2FAMigration();
        $stmt = db()->prepare("
            SELECT is_trusted, last_verified_ip FROM known_devices 
            WHERE username = ? AND device_id = ? AND is_forgotten = 0
            LIMIT 1
        ");
        $stmt->execute([$username, $deviceId]);
        $device = $stmt->fetch();
        
        if (!$device || empty($device['is_trusted'])) {
            return false;
        }
        
        // Trusted but IP changed → not trusted for this login
        if ($device['last_verified_ip'] !== $currentIP) {
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Mark a device as trusted with current IP
 */
function trustDevice($username, $deviceId, $ip) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        ensure2FAMigration();
        ensureKnownDevicesTable();
        
        // First ensure device exists in known_devices
        $check = db()->prepare("SELECT id FROM known_devices WHERE username = ? AND device_id = ? LIMIT 1");
        $check->execute([$username, $deviceId]);
        
        if (!$check->fetch()) {
            // Register if not exists
            $deviceInfo = parseUserAgent();
            $location = getLocationFromIP($ip);
            registerKnownDevice($username, $deviceId, $deviceInfo, $ip, $location);
        }
        
        $stmt = db()->prepare("
            UPDATE known_devices 
            SET is_trusted = 1, trusted_at = NOW(), last_verified_ip = ?, last_seen = NOW()
            WHERE username = ? AND device_id = ?
        ");
        $result = $stmt->execute([$ip, $username, $deviceId]);
        
        if ($result) {
            logSecurityEvent('TRUST_DEVICE', 'تم توثيق الجهاز', [
                'username' => $username,
                'device_id' => substr($deviceId, 0, 8) . '...',
                'ip' => $ip
            ]);
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("trustDevice error: " . $e->getMessage());
        return false;
    }
}

/**
 * Untrust a device (force 2FA on next login)
 */
function untrustDevice($username, $deviceId) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        $stmt = db()->prepare("
            UPDATE known_devices SET is_trusted = 0, trusted_at = NULL, last_verified_ip = ''
            WHERE username = ? AND device_id = ?
        ");
        $result = $stmt->execute([$username, $deviceId]);
        
        if ($result) {
            logSecurityEvent('UNTRUST_DEVICE', 'تم إلغاء توثيق الجهاز', [
                'username' => $username,
                'device_id' => substr($deviceId, 0, 8) . '...'
            ]);
        }
        
        return $result;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Force logout ALL sessions for a user (except current)
 * Used when new device or IP change is detected
 */
function forceLogoutAllSessions($username, $exceptSessionId = null) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        $pdo = db();
        
        // Create forced_logouts table if needed
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS forced_logouts (
                id INT AUTO_INCREMENT PRIMARY KEY,
                php_session_id VARCHAR(128) NOT NULL,
                forced_by VARCHAR(100) DEFAULT 'system',
                forced_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_session (php_session_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Get all active sessions for this user
        $sql = "SELECT session_id FROM login_sessions WHERE username = ? AND status = 'active'";
        $params = [$username];
        
        if ($exceptSessionId) {
            $sql .= " AND session_id != ?";
            $params[] = $exceptSessionId;
        }
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sessions = $stmt->fetchAll();
        
        $count = 0;
        foreach ($sessions as $session) {
            // Mark for forced logout
            $pdo->prepare("INSERT IGNORE INTO forced_logouts (php_session_id, forced_by) VALUES (?, 'system_2fa')")
                ->execute([$session['session_id']]);
            
            // Update session status
            $pdo->prepare("UPDATE login_sessions SET status = 'admin_logout', action_by = 'system_2fa', action_at = NOW() WHERE session_id = ?")
                ->execute([$session['session_id']]);
            
            $count++;
        }
        
        if ($count > 0) {
            logSecurityEvent('FORCE_LOGOUT_ALL', 'تم إنهاء جميع الجلسات', [
                'username' => $username,
                'sessions_terminated' => $count,
                'reason' => 'new_device_or_ip_change'
            ]);
        }
        
        return $count;
    } catch (Exception $e) {
        error_log("forceLogoutAllSessions error: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if 2FA attempts are rate limited
 * @return bool
 */
function is2FARateLimited($username, $ip) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        ensure2FAMigration();
        $stmt = db()->prepare("
            SELECT COUNT(*) as cnt FROM totp_attempts 
            WHERE (username = ? OR ip_address = ?) 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ");
        $stmt->execute([$username, $ip]);
        $row = $stmt->fetch();
        return $row && $row['cnt'] >= 5;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Record a failed 2FA attempt
 */
function record2FAAttempt($username, $ip) {
    if (!function_exists('db') || !isDbConnected()) return;
    
    try {
        ensure2FAMigration();
        $stmt = db()->prepare("INSERT INTO totp_attempts (ip_address, username) VALUES (?, ?)");
        $stmt->execute([$ip, $username]);
        
        // Clean old attempts (older than 1 hour)
        db()->exec("DELETE FROM totp_attempts WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    } catch (Exception $e) {}
}

/**
 * Get remaining 2FA attempts
 * @return int
 */
function getRemaining2FAAttempts($username, $ip) {
    if (!function_exists('db') || !isDbConnected()) return 5;
    
    try {
        $stmt = db()->prepare("
            SELECT COUNT(*) as cnt FROM totp_attempts 
            WHERE (username = ? OR ip_address = ?) 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
        ");
        $stmt->execute([$username, $ip]);
        $row = $stmt->fetch();
        return max(0, 5 - ($row ? $row['cnt'] : 0));
    } catch (Exception $e) {
        return 5;
    }
}

/**
 * Check if device IP changed since last trust verification
 * @return bool true if IP is different from last trusted IP
 */
function hasDeviceIPChanged($username, $deviceId, $currentIP) {
    if (!function_exists('db') || !isDbConnected()) return true;
    
    try {
        $stmt = db()->prepare("
            SELECT last_verified_ip FROM known_devices 
            WHERE username = ? AND device_id = ? AND is_forgotten = 0 AND is_trusted = 1
            LIMIT 1
        ");
        $stmt->execute([$username, $deviceId]);
        $device = $stmt->fetch();
        
        if (!$device) return true;
        return $device['last_verified_ip'] !== $currentIP;
    } catch (Exception $e) {
        return true;
    }
}

/**
 * ═══════════════════════════════════════════════════════════════════════════
 * LOGIN SESSIONS SYSTEM - نظام سجل تسجيل الدخول (v3.8.0)
 * ═══════════════════════════════════════════════════════════════════════════
 */

/**
 * Parse User Agent to get device info
 */
function parseUserAgent($userAgent = null) {
    $userAgent = $userAgent ?: (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    
    $result = [
        'device_type' => 'unknown',
        'device_name' => 'غير معروف',
        'os_name' => '',
        'os_version' => '',
        'browser_name' => '',
        'browser_version' => ''
    ];
    
    if (empty($userAgent)) return $result;
    
    // Detect Device Type
    if (preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
        $result['device_type'] = 'phone';
        $result['device_name'] = '📱 هاتف';
    } elseif (preg_match('/iPad|Tablet|PlayBook|Kindle|Silk/i', $userAgent)) {
        $result['device_type'] = 'tablet';
        $result['device_name'] = '📱 تابلت';
    } else {
        $result['device_type'] = 'desktop';
        $result['device_name'] = '💻 كمبيوتر';
    }
    
    // Detect OS
    if (preg_match('/Windows NT 10/i', $userAgent)) {
        $result['os_name'] = 'Windows';
        $result['os_version'] = '10/11';
    } elseif (preg_match('/Windows NT 6\.3/i', $userAgent)) {
        $result['os_name'] = 'Windows';
        $result['os_version'] = '8.1';
    } elseif (preg_match('/Windows NT/i', $userAgent)) {
        $result['os_name'] = 'Windows';
    } elseif (preg_match('/Mac OS X ([0-9_]+)/i', $userAgent, $m)) {
        $result['os_name'] = 'macOS';
        $result['os_version'] = str_replace('_', '.', $m[1]);
    } elseif (preg_match('/iPhone OS ([0-9_]+)/i', $userAgent, $m)) {
        $result['os_name'] = 'iOS';
        $result['os_version'] = str_replace('_', '.', $m[1]);
    } elseif (preg_match('/Android ([0-9.]+)/i', $userAgent, $m)) {
        $result['os_name'] = 'Android';
        $result['os_version'] = $m[1];
    } elseif (preg_match('/Linux/i', $userAgent)) {
        $result['os_name'] = 'Linux';
    }
    
    // Detect Browser
    if (preg_match('/Edg\/([0-9.]+)/i', $userAgent, $m)) {
        $result['browser_name'] = 'Edge';
        $result['browser_version'] = $m[1];
    } elseif (preg_match('/Chrome\/([0-9.]+)/i', $userAgent, $m)) {
        $result['browser_name'] = 'Chrome';
        $result['browser_version'] = $m[1];
    } elseif (preg_match('/Safari\/([0-9.]+)/i', $userAgent) && preg_match('/Version\/([0-9.]+)/i', $userAgent, $m)) {
        $result['browser_name'] = 'Safari';
        $result['browser_version'] = $m[1];
    } elseif (preg_match('/Firefox\/([0-9.]+)/i', $userAgent, $m)) {
        $result['browser_name'] = 'Firefox';
        $result['browser_version'] = $m[1];
    } elseif (preg_match('/Opera|OPR\/([0-9.]+)/i', $userAgent, $m)) {
        $result['browser_name'] = 'Opera';
        $result['browser_version'] = isset($m[1]) ? $m[1] : '';
    }
    
    return $result;
}

/**
 * Generate STABLE device fingerprint (without IP)
 * This fingerprint stays the same even when IP changes
 */
function generateDeviceFingerprint($userAgent = null) {
    $userAgent = $userAgent ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
    $accept = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    
    // Stable fingerprint: user-agent + accept-language (NO IP!)
    return hash('sha256', $userAgent . '|' . $accept);
}

/**
 * Get or create persistent device ID (cookie-based)
 * This ID persists across sessions and IP changes
 */
function getDeviceId() {
    $cookieName = 'surprise_device_id';
    
    // Check if device_id cookie exists
    if (isset($_COOKIE[$cookieName]) && strlen($_COOKIE[$cookieName]) === 64) {
        return $_COOKIE[$cookieName];
    }
    
    // Generate new device ID
    $deviceId = bin2hex(random_bytes(32)); // 64 chars hex
    
    // Set persistent cookie (1 year)
    // secure flag: only set on HTTPS to ensure cookie works on HTTP localhost
    $isHTTPS = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
               || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    setcookie($cookieName, $deviceId, [
        'expires' => time() + (365 * 24 * 60 * 60), // 1 year
        'path' => '/',
        'domain' => '',
        'secure' => $isHTTPS,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Also set in global for immediate use
    $_COOKIE[$cookieName] = $deviceId;
    
    return $deviceId;
}

/**
 * Check if device is known for a user
 * @return array|false Device record if known, false if new
 */
function getKnownDevice($username, $deviceId) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        ensureKnownDevicesTable();
        
        $stmt = db()->prepare("
            SELECT * FROM known_devices 
            WHERE username = ? AND device_id = ? AND is_forgotten = 0
            LIMIT 1
        ");
        $stmt->execute([$username, $deviceId]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Register a device as known
 */
function registerKnownDevice($username, $deviceId, $deviceInfo, $ip, $location) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        ensureKnownDevicesTable();
        $fingerprint = generateDeviceFingerprint();
        
        // Check if device was previously forgotten
        $check = db()->prepare("SELECT id FROM known_devices WHERE username = ? AND device_id = ?");
        $check->execute([$username, $deviceId]);
        $existing = $check->fetch();
        
        if ($existing) {
            // Un-forget the device
            $stmt = db()->prepare("
                UPDATE known_devices 
                SET is_forgotten = 0, fingerprint = ?, device_type = ?, os_name = ?, browser_name = ?,
                    last_ip = ?, last_country = ?, last_city = ?, last_seen = NOW()
                WHERE id = ?
            ");
            return $stmt->execute([
                $fingerprint, $deviceInfo['device_type'], $deviceInfo['os_name'], $deviceInfo['browser_name'],
                $ip, $location['country'] ?? '', $location['city'] ?? '', $existing['id']
            ]);
        }
        
        // New device registration
        $stmt = db()->prepare("
            INSERT INTO known_devices 
            (username, device_id, fingerprint, device_type, os_name, browser_name, 
             first_ip, last_ip, first_country, first_city, last_country, last_city, first_seen, last_seen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        return $stmt->execute([
            $username, $deviceId, $fingerprint,
            $deviceInfo['device_type'], $deviceInfo['os_name'], $deviceInfo['browser_name'],
            $ip, $ip, $location['country'] ?? '', $location['city'] ?? '', $location['country'] ?? '', $location['city'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("registerKnownDevice error: " . $e->getMessage());
        return false;
    }
}

/**
 * Forget a device (called when deleting from security log)
 */
function forgetDevice($username, $deviceId) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        $stmt = db()->prepare("UPDATE known_devices SET is_forgotten = 1 WHERE username = ? AND device_id = ?");
        return $stmt->execute([$username, $deviceId]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Update known device last seen info
 */
function updateKnownDeviceActivity($deviceId, $ip, $location) {
    if (!function_exists('db') || !isDbConnected()) return;
    
    try {
        $stmt = db()->prepare("
            UPDATE known_devices 
            SET last_ip = ?, last_country = ?, last_city = ?, last_seen = NOW()
            WHERE device_id = ? AND is_forgotten = 0
        ");
        $stmt->execute([$ip, $location['country'] ?? '', $location['city'] ?? '', $deviceId]);
    } catch (Exception $e) {}
}

/**
 * Ensure known_devices table exists
 */
function ensureKnownDevicesTable() {
    static $checked = false;
    if ($checked) return;
    
    try {
        db()->exec("
            CREATE TABLE IF NOT EXISTS known_devices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(100) NOT NULL,
                device_id VARCHAR(64) NOT NULL,
                fingerprint VARCHAR(64) NOT NULL,
                device_type VARCHAR(20) DEFAULT 'unknown',
                os_name VARCHAR(50) DEFAULT '',
                browser_name VARCHAR(50) DEFAULT '',
                first_ip VARCHAR(45),
                last_ip VARCHAR(45),
                first_country VARCHAR(100) DEFAULT '',
                first_city VARCHAR(100) DEFAULT '',
                last_country VARCHAR(100) DEFAULT '',
                last_city VARCHAR(100) DEFAULT '',
                is_forgotten TINYINT(1) DEFAULT 0,
                first_seen DATETIME,
                last_seen DATETIME,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_user_device (username, device_id),
                INDEX idx_device (device_id),
                INDEX idx_forgotten (is_forgotten)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $checked = true;
    } catch (Exception $e) {}
}

/**
 * Get location from IP (free API)
 */
function getLocationFromIP($ip) {
    $result = [
        'country' => '',
        'country_code' => '',
        'city' => ''
    ];
    
    // Skip for local IPs
    if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0 || strpos($ip, '10.') === 0) {
        $result['country'] = 'محلي';
        $result['city'] = 'Development';
        return $result;
    }
    
    try {
        // Use ip-api.com (free, 45 req/min)
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city";
        $context = stream_context_create(['http' => ['timeout' => 2]]);
        $response = @file_get_contents($url, false, $context);
        
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['status']) && $data['status'] === 'success') {
                $result['country'] = isset($data['country']) ? $data['country'] : '';
                $result['country_code'] = isset($data['countryCode']) ? $data['countryCode'] : '';
                $result['city'] = isset($data['city']) ? $data['city'] : '';
            }
        }
    } catch (Exception $e) {
        // Silently fail
    }
    
    return $result;
}

/**
 * Check if device is blocked
 */
function isDeviceBlocked($ip = null, $fingerprint = null, $userAgentHash = null) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    $ip = $ip ?: getClientIP();
    $fingerprint = $fingerprint ?: generateDeviceFingerprint();
    $userAgentHash = $userAgentHash ?: hash('sha256', isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
    
    try {
        $stmt = db()->prepare("
            SELECT id FROM blocked_devices 
            WHERE is_active = 1 
            AND (ip_address = ? OR device_fingerprint = ? OR user_agent_hash = ?)
            LIMIT 1
        ");
        $stmt->execute([$ip, $fingerprint, $userAgentHash]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Check if this is a new device for the user
 */
function isNewDeviceForUser($username, $fingerprint) {
    return checkIfNewDevice($username, $fingerprint);
}

function checkIfNewDevice($username, $fingerprint) {
    $pdo = db();
    if (!$pdo) return true;
    
    try {
        // Only check non-deleted records — deleting a device record makes it "new" again
        $stmt = $pdo->prepare("SELECT id FROM login_sessions WHERE username = ? AND device_fingerprint = ? AND (is_deleted = 0 OR is_deleted IS NULL) LIMIT 1");
        $stmt->execute([$username, $fingerprint]);
        return $stmt->fetch() === false;
    } catch (Exception $e) {
        return true;
    }
}

/**
 * Check if this is a new location for the user
 */
function isNewLocationForUser($username, $country, $city) {
    return checkIfNewLocation($username, $country, $city);
}

function checkIfNewLocation($username, $country, $city) {
    if (empty($country)) return false;
    $pdo = db();
    if (!$pdo) return true;
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM login_sessions WHERE username = ? AND country = ? LIMIT 1");
        $stmt->execute([$username, $country]);
        return $stmt->fetch() === false;
    } catch (Exception $e) {
        return true;
    }
}

/**
 * Record login session - Uses known_devices system
 * 
 * Logic:
 * 1. Check known_devices table using device_id cookie
 * 2. If known → mark as known device, check if IP changed
 * 3. If unknown → mark as new device, register in known_devices
 * 4. Logout doesn't touch known_devices
 * 5. Delete → forgets device from known_devices
 */
function recordLoginSession($userType, $userId, $username, $displayName = '') {
    try {
        if (!function_exists('db')) return null;
        
        $pdo = db();
        if (!$pdo) return null;
        
        // Ensure tables exist
        try {
            createLoginSessionsTable();
            ensureKnownDevicesTable();
        } catch (Exception $e) {}
        
        $currentSessionId = session_id();
        $ip = getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $deviceInfo = parseUserAgent($userAgent);
        $fingerprint = generateDeviceFingerprint($userAgent);
        $deviceId = getDeviceId(); // Persistent cookie-based ID
        
        // Get location
        $location = [];
        try {
            $location = getLocationFromIP($ip);
        } catch (Exception $e) {
            $location = ['country' => '', 'country_code' => '', 'city' => ''];
        }
        
        // ═══════════════════════════════════════════════════════════
        // CHECK KNOWN DEVICES TABLE
        // ═══════════════════════════════════════════════════════════
        $knownDevice = getKnownDevice($username, $deviceId);
        
        $isNewDevice = false;
        $isIpChanged = false;
        
        if ($knownDevice) {
            $isIpChanged = ($knownDevice['last_ip'] !== $ip);
            updateKnownDeviceActivity($deviceId, $ip, $location);
        } else {
            $isNewDevice = true;
            registerKnownDevice($username, $deviceId, $deviceInfo, $ip, $location);
        }
        
        // ═══════════════════════════════════════════════════════════
        // SINGLE RECORD PER DEVICE: UPSERT LOGIC
        // Check for existing non-deleted record for this device+user
        // ═══════════════════════════════════════════════════════════
        try {
            $existing = $pdo->prepare("
                SELECT id, ip_address, session_id 
                FROM login_sessions 
                WHERE device_id = ? AND username = ? AND (is_deleted = 0 OR is_deleted IS NULL) AND status != 'deleted'
                ORDER BY id DESC LIMIT 1
            ");
            $existing->execute([$deviceId, $username]);
            $existingRecord = $existing->fetch();
        } catch (Exception $e) {
            $existingRecord = false;
        }
        
        if ($existingRecord) {
            // ═══ KNOWN DEVICE — UPDATE existing record ═══
            $oldIp = $existingRecord['ip_address'] ?? '';
            $ipActuallyChanged = ($oldIp !== $ip && !empty($oldIp));
            
            $updateSql = "
                UPDATE login_sessions SET
                    session_id = ?,
                    ip_address = ?,
                    last_login_at = NOW(),
                    last_activity = NOW(),
                    last_ip = ?,
                    status = 'active',
                    is_new_ip = ?,
                    login_count = COALESCE(login_count, 1) + 1,
                    user_agent = ?,
                    country = ?, country_code = ?, city = ?
            ";
            $params = [
                $currentSessionId,
                $ip,
                $ip,
                $ipActuallyChanged ? 1 : 0,
                $userAgent,
                $location['country'] ?? '', $location['country_code'] ?? '', $location['city'] ?? ''
            ];
            
            if ($ipActuallyChanged) {
                $updateSql .= ", ip_changed_at = NOW()";
            }
            
            $updateSql .= " WHERE id = ?";
            $params[] = $existingRecord['id'];
            
            $pdo->prepare($updateSql)->execute($params);
            
            $_SESSION['login_session_recorded'] = true;
            $_SESSION['login_session_id'] = $existingRecord['id'];
            $_SESSION['device_id'] = $deviceId;
            
            return $existingRecord['id'];
            
        } else {
            // ═══ NEW DEVICE — INSERT one new record ═══
            $stmt = $pdo->prepare("
                INSERT INTO login_sessions 
                (user_type, user_id, username, user_display_name, session_id, device_id,
                 ip_address, user_agent, device_type, device_name, os_name, os_version, 
                 browser_name, browser_version, device_fingerprint,
                 country, country_code, city, is_new_device, is_new_ip, 
                 status, login_at, last_login_at, last_activity, last_ip, login_count)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW(), NOW(), NOW(), ?, 1)
            ");
            
            $result = $stmt->execute([
                $userType, $userId, $username, $displayName,
                $currentSessionId, $deviceId,
                $ip, $userAgent,
                $deviceInfo['device_type'], $deviceInfo['device_name'],
                $deviceInfo['os_name'], $deviceInfo['os_version'],
                $deviceInfo['browser_name'], $deviceInfo['browser_version'],
                $fingerprint,
                $location['country'] ?? '', $location['country_code'] ?? '', $location['city'] ?? '',
                1, // is_new_device
                $isIpChanged ? 1 : 0,
                $ip // last_ip
            ]);
            
            if ($result) {
                $sessionId = $pdo->lastInsertId();
                $_SESSION['login_session_recorded'] = true;
                $_SESSION['login_session_id'] = $sessionId;
                $_SESSION['device_id'] = $deviceId;
                
                // Send Telegram notification for new device
                try {
                    sendSecurityTelegramNotification($username, $deviceInfo, $location, true, false);
                } catch (Exception $e) {}
                
                return $sessionId;
            }
        }
        
        return null;
        
    } catch (Exception $e) {
        error_log("recordLoginSession fatal error: " . $e->getMessage());
        return null;
    }
}

/**
 * Update session activity
 */
function updateSessionActivity() {
    if (!function_exists('db') || !isDbConnected()) return;
    
    try {
        $stmt = db()->prepare("
            UPDATE login_sessions 
            SET last_activity = NOW() 
            WHERE session_id = ? AND status = 'active'
        ");
        $stmt->execute([session_id()]);
    } catch (Exception $e) {
        // Silently fail
    }
}

/**
 * Get all login sessions for admin view
 */
function getLoginSessions($filters = []) {
    if (!function_exists('db') || !isDbConnected()) return [];
    
    try {
        $where = [];
        $params = [];
        
        if (!empty($filters['status'])) {
            $where[] = "status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $filters['search'] . '%';
            $where[] = "(username LIKE ? OR ip_address LIKE ? OR city LIKE ? OR country LIKE ?)";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if (!empty($filters['device_type'])) {
            $where[] = "device_type = ?";
            $params[] = $filters['device_type'];
        }
        
        if (!empty($filters['new_device'])) {
            $where[] = "is_new_device = 1";
        }
        
        if (!empty($filters['period'])) {
            switch ($filters['period']) {
                case 'today':
                    $where[] = "DATE(login_at) = CURDATE()";
                    break;
                case 'week':
                    $where[] = "login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $where[] = "login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                    break;
            }
        }
        
        // Always exclude deleted sessions unless explicitly requested
        if (empty($filters['status']) || $filters['status'] !== 'deleted') {
            $where[] = "status != 'deleted'";
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT * FROM login_sessions {$whereClause} ORDER BY login_at DESC LIMIT 500";
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get session by ID
 */
function getLoginSessionById($id) {
    if (!function_exists('db') || !isDbConnected()) return null;
    
    try {
        $stmt = db()->prepare("SELECT * FROM login_sessions WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Force logout a session
 */
function forceLogoutSession($sessionId, $adminUsername) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        $session = getLoginSessionById($sessionId);
        if (!$session) return false;
        
        // Update session status
        $stmt = db()->prepare("
            UPDATE login_sessions 
            SET status = 'admin_logout', logged_out_at = NOW(), logged_out_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$adminUsername, $sessionId]);
        
        // Invalidate remember token if exists
        if (!empty($session['remember_token_selector'])) {
            $stmt = db()->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            $stmt->execute([$session['remember_token_selector']]);
        }
        
        // Log admin action
        logAdminAction($adminUsername, 'session_logout', $session['username'], [
            'session_id' => $sessionId,
            'device' => $session['device_name'],
            'ip' => $session['ip_address']
        ]);
        
        // Send Telegram notification
        sendAdminActionTelegram('تسجيل خروج جلسة', $session['username'], $session['device_name'], $adminUsername);
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Block a device
 */
function blockDevice($sessionId, $adminUsername, $reason = '') {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        $session = getLoginSessionById($sessionId);
        if (!$session) return false;
        
        // Update session status
        $stmt = db()->prepare("
            UPDATE login_sessions 
            SET status = 'blocked', blocked_at = NOW(), blocked_by = ?, block_reason = ?
            WHERE id = ?
        ");
        $stmt->execute([$adminUsername, $reason, $sessionId]);
        
        // Add to blocked devices
        $stmt = db()->prepare("
            INSERT INTO blocked_devices 
            (ip_address, device_fingerprint, user_agent_hash, blocked_by, block_reason, original_session_id)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $userAgentHash = hash('sha256', $session['user_agent']);
        $stmt->execute([
            $session['ip_address'],
            $session['device_fingerprint'],
            $userAgentHash,
            $adminUsername,
            $reason,
            $sessionId
        ]);
        
        // Invalidate remember token
        if (!empty($session['remember_token_selector'])) {
            $stmt = db()->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            $stmt->execute([$session['remember_token_selector']]);
        }
        
        // Log admin action
        logAdminAction($adminUsername, 'device_block', $session['username'], [
            'session_id' => $sessionId,
            'ip' => $session['ip_address'],
            'reason' => $reason
        ]);
        
        // Send Telegram notification
        sendAdminActionTelegram('حظر جهاز', $session['username'], $session['device_name'] . ' - ' . $session['ip_address'], $adminUsername);
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Unblock a device
 */
function unblockDevice($blockId, $adminUsername) {
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        $stmt = db()->prepare("
            UPDATE blocked_devices 
            SET is_active = 0, unblocked_at = NOW(), unblocked_by = ?
            WHERE id = ?
        ");
        $stmt->execute([$adminUsername, $blockId]);
        
        // Log admin action
        logAdminAction($adminUsername, 'device_unblock', "Block ID: {$blockId}", []);
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete session (logout first)
 */
function deleteSession($sessionId, $adminUsername) {
    // First force logout
    forceLogoutSession($sessionId, $adminUsername);
    
    // Then hide from list (don't actually delete for audit trail)
    if (!function_exists('db') || !isDbConnected()) return false;
    
    try {
        $stmt = db()->prepare("
            UPDATE login_sessions 
            SET status = 'deleted'
            WHERE id = ?
        ");
        $stmt->execute([$sessionId]);
        
        return true;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Log admin action
 */
function logAdminAction($adminUsername, $actionType, $target, $details = []) {
    if (!function_exists('db') || !isDbConnected()) return;
    
    try {
        $stmt = db()->prepare("
            INSERT INTO admin_actions_log 
            (admin_username, action_type, action_target, action_details, ip_address)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $adminUsername,
            $actionType,
            $target,
            json_encode($details, JSON_UNESCAPED_UNICODE),
            getClientIP()
        ]);
    } catch (Exception $e) {
        // Silently fail
    }
}

/**
 * Send security Telegram notification
 */
function sendSecurityTelegramNotification($username, $deviceInfo, $location, $isNewDevice, $isNewLocation) {
    $settings = function_exists('getSettings') ? getSettings() : [];
    $botToken = isset($settings['telegram_bot_token']) ? $settings['telegram_bot_token'] : '';
    $chatId = isset($settings['telegram_chat_id']) ? $settings['telegram_chat_id'] : '';
    
    if (empty($botToken) || empty($chatId)) return;
    
    $alerts = [];
    if ($isNewDevice) $alerts[] = '📱 جهاز جديد';
    if ($isNewLocation) $alerts[] = '🌍 موقع جديد';
    
    $message = "🔐 *تنبيه أمني - تسجيل دخول*\n";
    $message .= "━━━━━━━━━━━━━━━━\n";
    $message .= "⚠️ " . implode(' | ', $alerts) . "\n\n";
    $message .= "👤 *المستخدم:* `{$username}`\n";
    $message .= "📱 *الجهاز:* {$deviceInfo['device_name']}\n";
    $message .= "💻 *النظام:* {$deviceInfo['os_name']} {$deviceInfo['os_version']}\n";
    $message .= "🌐 *المتصفح:* {$deviceInfo['browser_name']}\n";
    
    $locationStr = !empty($location['city']) ? "{$location['city']}, {$location['country']}" : ($location['country'] ?: 'غير متاح');
    $message .= "📍 *الموقع:* {$locationStr}\n";
    $message .= "\n⏰ " . date('Y-m-d H:i:s');
    
    // Send async
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'Markdown'];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    @curl_exec($ch);
    curl_close($ch);
}

/**
 * Send admin action Telegram notification
 */
function sendAdminActionTelegram($action, $targetUser, $details, $adminUsername) {
    $settings = function_exists('getSettings') ? getSettings() : [];
    $botToken = isset($settings['telegram_bot_token']) ? $settings['telegram_bot_token'] : '';
    $chatId = isset($settings['telegram_chat_id']) ? $settings['telegram_chat_id'] : '';
    
    if (empty($botToken) || empty($chatId)) return;
    
    $message = "🛡️ *إجراء إداري*\n";
    $message .= "━━━━━━━━━━━━━━━━\n";
    $message .= "📋 *الإجراء:* {$action}\n";
    $message .= "👤 *الهدف:* {$targetUser}\n";
    $message .= "📝 *التفاصيل:* {$details}\n";
    $message .= "👮 *بواسطة:* {$adminUsername}\n";
    $message .= "\n⏰ " . date('Y-m-d H:i:s');
    
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $data = ['chat_id' => $chatId, 'text' => $message, 'parse_mode' => 'Markdown'];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    @curl_exec($ch);
    curl_close($ch);
}

/**
 * Create login sessions table (called directly)
 */
function createLoginSessionsTable() {
    $pdo = db();
    if (!$pdo) return false;
    
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'login_sessions'");
        if ($stmt && $stmt->fetch()) {
            return true; // Table exists
        }
        
        // Create login_sessions table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_type VARCHAR(20) NOT NULL DEFAULT 'admin',
                user_id INT NULL,
                username VARCHAR(100) NOT NULL,
                user_display_name VARCHAR(200) DEFAULT '',
                session_id VARCHAR(128) NOT NULL,
                remember_token_selector VARCHAR(24) NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                device_type VARCHAR(20) DEFAULT 'unknown',
                device_name VARCHAR(100) DEFAULT '',
                os_name VARCHAR(50) DEFAULT '',
                os_version VARCHAR(20) DEFAULT '',
                browser_name VARCHAR(50) DEFAULT '',
                browser_version VARCHAR(20) DEFAULT '',
                device_fingerprint VARCHAR(64) DEFAULT '',
                country VARCHAR(100) DEFAULT '',
                country_code VARCHAR(5) DEFAULT '',
                city VARCHAR(100) DEFAULT '',
                status VARCHAR(20) DEFAULT 'active',
                is_new_device TINYINT(1) DEFAULT 0,
                is_new_location TINYINT(1) DEFAULT 0,
                blocked_at DATETIME NULL,
                blocked_by VARCHAR(100) NULL,
                block_reason VARCHAR(255) NULL,
                logged_out_at DATETIME NULL,
                logged_out_by VARCHAR(100) NULL,
                login_at DATETIME NOT NULL,
                last_login_at DATETIME NULL,
                last_activity DATETIME NULL,
                last_ip VARCHAR(45) DEFAULT '',
                ip_changed_at DATETIME NULL,
                login_count INT DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_session (session_id),
                INDEX idx_status (status),
                INDEX idx_device_id (device_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create blocked_devices table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS blocked_devices (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NULL,
                device_fingerprint VARCHAR(64) NULL,
                user_agent_hash VARCHAR(64) NULL,
                blocked_by VARCHAR(100) NOT NULL,
                block_reason VARCHAR(255) DEFAULT '',
                original_session_id INT NULL,
                is_active TINYINT(1) DEFAULT 1,
                unblocked_at DATETIME NULL,
                unblocked_by VARCHAR(100) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_active (is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Create admin_actions_log table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_actions_log (
                id INT AUTO_INCREMENT PRIMARY KEY,
                admin_username VARCHAR(100) NOT NULL,
                action_type VARCHAR(50) NOT NULL,
                action_target VARCHAR(200) DEFAULT '',
                action_details TEXT,
                ip_address VARCHAR(45) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        return true;
        
    } catch (Exception $e) {
        error_log("createLoginSessionsTable error: " . $e->getMessage());
        return false;
    }
}

/**
 * Alias for backward compatibility
 */
function ensureLoginSessionsTables() {
    return createLoginSessionsTable();
}

/**
 * Get session statistics
 */
function getSessionStats() {
    if (!function_exists('db') || !isDbConnected()) {
        return ['active' => 0, 'blocked' => 0, 'new_devices' => 0, 'today' => 0];
    }
    
    try {
        $stats = [];
        
        $stmt = db()->query("SELECT COUNT(*) FROM login_sessions WHERE status = 'active' AND last_activity > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
        $stats['active'] = $stmt->fetchColumn();
        
        $stmt = db()->query("SELECT COUNT(*) FROM blocked_devices WHERE is_active = 1");
        $stats['blocked'] = $stmt->fetchColumn();
        
        $stmt = db()->query("SELECT COUNT(*) FROM login_sessions WHERE is_new_device = 1 AND DATE(login_at) = CURDATE()");
        $stats['new_devices'] = $stmt->fetchColumn();
        
        $stmt = db()->query("SELECT COUNT(*) FROM login_sessions WHERE DATE(login_at) = CURDATE()");
        $stats['today'] = $stmt->fetchColumn();
        
        return $stats;
        
    } catch (Exception $e) {
        return ['active' => 0, 'blocked' => 0, 'new_devices' => 0, 'today' => 0];
    }
}

/**
 * Ensure current session is recorded in login_sessions
 * This auto-records existing sessions when they access admin panel
 */
function ensureCurrentSessionRecorded() {
    if (!function_exists('db') || !isDbConnected()) return;
    if (!isset($_SESSION['admin_logged_in']) || !$_SESSION['admin_logged_in']) return;
    
    // Check if already recorded for this session
    if (isset($_SESSION['login_session_recorded'])) return;
    
    try {
        // Ensure tables exist
        ensureLoginSessionsTables();
        
        $pdo = db();
        $currentSessionId = session_id();
        
        // Fast path: check if this exact session_id is already recorded
        $stmt = $pdo->prepare("SELECT id, status, is_deleted FROM login_sessions WHERE session_id = ? LIMIT 1");
        $stmt->execute([$currentSessionId]);
        $existingSession = $stmt->fetch();
        
        if ($existingSession) {
            $_SESSION['login_session_recorded'] = true;
            $_SESSION['login_session_id'] = $existingSession['id'];
            
            if (empty($existingSession['is_deleted']) && $existingSession['status'] !== 'deleted') {
                try {
                    $pdo->prepare("UPDATE login_sessions SET last_activity = NOW() WHERE id = ?")->execute([$existingSession['id']]);
                } catch (Exception $e) {}
            }
            return;
        }
        
        // Determine user info
        $userType = 'admin';
        $userId = null;
        $username = 'admin';
        $displayName = 'المدير الرئيسي';
        
        if (isset($_SESSION['staff_id'])) {
            $userType = 'staff';
            $userId = $_SESSION['staff_id'];
            $username = $_SESSION['staff_username'] ?? 'staff';
            $displayName = $_SESSION['staff_name'] ?? '';
        }
        
        // Get device and location info
        $ip = getClientIP();
        $userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $deviceInfo = parseUserAgent($userAgent);
        $fingerprint = generateDeviceFingerprint($userAgent);
        $deviceId = getDeviceId();
        $location = getLocationFromIP($ip);
        
        // ═══ SINGLE RECORD PER DEVICE: Check for existing record by device_id ═══
        try {
            $existing = $pdo->prepare("
                SELECT id, ip_address 
                FROM login_sessions 
                WHERE device_id = ? AND username = ? AND (is_deleted = 0 OR is_deleted IS NULL) AND status != 'deleted'
                ORDER BY id DESC LIMIT 1
            ");
            $existing->execute([$deviceId, $username]);
            $existingRecord = $existing->fetch();
        } catch (Exception $e) {
            $existingRecord = false;
        }
        
        if ($existingRecord) {
            // ═══ KNOWN DEVICE — UPDATE existing record ═══
            $oldIp = $existingRecord['ip_address'] ?? '';
            $ipChanged = ($oldIp !== $ip && !empty($oldIp));
            
            $sql = "
                UPDATE login_sessions SET
                    session_id = ?,
                    ip_address = ?,
                    last_login_at = NOW(),
                    last_activity = NOW(),
                    last_ip = ?,
                    status = 'active',
                    is_new_ip = ?,
                    login_count = COALESCE(login_count, 1) + 1,
                    user_agent = ?,
                    country = ?, country_code = ?, city = ?
            ";
            $params = [
                $currentSessionId, $ip, $ip,
                $ipChanged ? 1 : 0,
                $userAgent,
                $location['country'], $location['country_code'], $location['city']
            ];
            
            if ($ipChanged) {
                $sql .= ", ip_changed_at = NOW()";
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $existingRecord['id'];
            
            $pdo->prepare($sql)->execute($params);
            
            $_SESSION['login_session_recorded'] = true;
            $_SESSION['login_session_id'] = $existingRecord['id'];
        } else {
            // ═══ NEW DEVICE — INSERT one record ═══
            $stmt = $pdo->prepare("
                INSERT INTO login_sessions 
                (user_type, user_id, username, user_display_name, session_id, device_id,
                 ip_address, user_agent, device_type, device_name, os_name, os_version, 
                 browser_name, browser_version, device_fingerprint,
                 country, country_code, city, 
                 is_new_device, is_new_location, 
                 login_at, last_login_at, last_activity, last_ip, login_count)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), NOW(), ?, 1)
            ");
            
            $isNewDevice = isNewDeviceForUser($username, $fingerprint);
            $isNewLocation = isNewLocationForUser($username, $location['country'], $location['city']);
            
            $stmt->execute([
                $userType, $userId, $username, $displayName,
                $currentSessionId, $deviceId,
                $ip, $userAgent,
                $deviceInfo['device_type'], $deviceInfo['device_name'],
                $deviceInfo['os_name'], $deviceInfo['os_version'],
                $deviceInfo['browser_name'], $deviceInfo['browser_version'],
                $fingerprint,
                $location['country'], $location['country_code'], $location['city'],
                $isNewDevice ? 1 : 0,
                $isNewLocation ? 1 : 0,
                $ip // last_ip
            ]);
            
            $_SESSION['login_session_recorded'] = true;
            
            if ($isNewDevice || $isNewLocation) {
                sendSecurityTelegramNotification($username, $deviceInfo, $location, $isNewDevice, $isNewLocation);
            }
        }
        
    } catch (Exception $e) {
        error_log("ensureCurrentSessionRecorded error: " . $e->getMessage());
    }
}

/**
 * Mark current session as logged out (called from logout.php)
 */
function markSessionAsLoggedOut() {
    if (!function_exists('db') || !isDbConnected()) return;
    
    try {
        $stmt = db()->prepare("
            UPDATE login_sessions 
            SET status = 'logged_out', logged_out_at = NOW()
            WHERE session_id = ? AND status = 'active'
        ");
        $stmt->execute([session_id()]);
    } catch (Exception $e) {
        // Silently fail
    }
}

/**
 * NOTE: isCurrentSessionValid() is now defined earlier in this file (line ~268)
 * with enhanced safety checks for table existence
 */

/**
 * Expire old active sessions automatically
 */
function expireOldSessions() {
    if (!function_exists('db') || !isDbConnected()) return;
    
    try {
        // Expire sessions with no activity for more than 7 days
        db()->exec("
            UPDATE login_sessions 
            SET status = 'expired' 
            WHERE status = 'active' 
            AND last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
    } catch (Exception $e) {
        // Silently fail
    }
}
