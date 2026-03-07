<?php
/**
 * Surprise! Store - Configuration
 * Database & Site Settings
 */

// ============ IRAQ TIMEZONE SETTINGS ============
// Set Iraq timezone (Baghdad = UTC+3)
date_default_timezone_set('Asia/Baghdad');

// ============ SECURE SESSION CONFIGURATION ============
if (session_status() === PHP_SESSION_NONE) {
    // Session cookie settings for maximum stability
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 1 : 0);
    ini_set('session.use_strict_mode', 1);
    ini_set('session.cookie_samesite', 'Lax'); // Lax is more stable than Strict for redirects
    ini_set('session.cookie_path', '/'); // Ensure cookie works across all paths
    
    // 30-day session lifetime (extended for "remember me" functionality)
    ini_set('session.cookie_lifetime', 2592000); // 30 days
    ini_set('session.gc_maxlifetime', 2592000);  // 30 days
    
    // Reduce garbage collection probability to prevent premature session deletion
    ini_set('session.gc_probability', 1);
    ini_set('session.gc_divisor', 1000); // 0.1% chance per request
    
    @session_start();
}

// ============ ENVIRONMENT DETECTION ============
// Auto-detect environment based on hostname
$isProduction = (isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'surprise-iq.com') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'web-hosting.com') !== false ||
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
));

// ============ PRODUCTION ERROR HANDLING ============
if ($isProduction) {
    // Production: Hide errors from users, log them instead
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/data/php_errors.log');
    
    // HTTPS redirect - Force secure connection in production
    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }
} else {
    // Development: Show errors
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// ============ DATABASE CONFIGURATION ============

if ($isProduction) {
    // Production Database
    define('DB_HOST', 'YOUR_DB_HOST_HERE');
    define('DB_NAME', 'YOUR_DB_NAME_HERE');
    define('DB_USER', 'YOUR_DB_USER_HERE');
    define('DB_PASS', 'YOUR_DB_PASS_HERE');
} else {
    // Local XAMPP Development
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'surprise_store');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// PDO Connection with graceful error handling
$GLOBALS['db_connected'] = false;
$GLOBALS['pdo'] = null;
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );
    
    // ============ IRAQ/BAGHDAD TIMEZONE FOR MYSQL ============
    // تثبيت توقيت بغداد (+03:00) لجميع عمليات MySQL
    // هذا يضمن أن NOW(), CURRENT_TIMESTAMP تستخدم توقيت بغداد
    // نستخدم +03:00 بدلاً من 'Asia/Baghdad' للتوافق مع جميع السيرفرات
    $pdo->exec("SET time_zone = '+03:00'");
    
    $GLOBALS['pdo'] = $pdo;
    $GLOBALS['db_connected'] = true;
} catch (PDOException $e) {
    // SECURITY: Never expose database errors to users
    // Log the error securely
    $errorLogFile = dirname(__DIR__) . '/data/db_errors.log';
    $errorEntry = date('Y-m-d H:i:s') . ' | ' . $e->getMessage() . "\n";
    @file_put_contents($errorLogFile, $errorEntry, FILE_APPEND | LOCK_EX);
    
    // Set a null connection - functions will need to handle this gracefully
    $GLOBALS['pdo'] = null;
    $GLOBALS['db_error'] = 'Database connection failed'; // Generic message only
}

// ============ SITE SETTINGS ============
// اسم الموقع الرسمي الثابت - يظهر في Google
define('SITE_NAME', 'بيج سبرايز | Surprise page');

// Auto-detect SITE_URL based on current environment
if (isset($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php';
    $scriptDir = dirname($scriptName);
    if ($scriptDir === '/' || $scriptDir === '\\' || $scriptDir === '.') {
        $baseDir = '';
    } else {
        $baseDir = $scriptDir;
    }
    define('SITE_URL', $protocol . $host . $baseDir);
} else {
    define('SITE_URL', 'http://localhost/surprise');
}

// ============ DYNAMIC SOCIAL MEDIA SETTINGS ============
// These are loaded from database settings (admin panel) with fallback to defaults
// Function to get a single setting from DB (early loading before functions.php)
function _getSettingEarly($pdo, $key, $default = '') {
    if (!$pdo) return $default;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return ($value !== false && $value !== '') ? $value : $default;
    } catch (Exception $e) {
        return $default;
    }
}

// Load social settings from database (if connected)
$_telegramUsername = _getSettingEarly($GLOBALS['pdo'], 'telegram_username', 'sur_prisese');
$_instagramUsername = _getSettingEarly($GLOBALS['pdo'], 'instagram_username', 'sur._prises');

// Instagram
define('INSTAGRAM_USER', $_instagramUsername);
define('INSTAGRAM_DM', 'https://ig.me/m/' . $_instagramUsername);
define('INSTAGRAM_URL', 'https://instagram.com/' . $_instagramUsername);

// ============ TELEGRAM ORDER SETTINGS ============
// Telegram username for receiving orders (without @)
define('TELEGRAM_ORDER_USERNAME', $_telegramUsername);
define('TELEGRAM_ORDER_DM', 'https://t.me/' . $_telegramUsername);

// ============ TELEGRAM CHANNEL ============
define('TELEGRAM_CHANNEL', $_telegramUsername);
define('TELEGRAM_CHANNEL_URL', 'https://t.me/' . $_telegramUsername);

// ============ PATHS ============
define('ROOT_PATH', dirname(__DIR__) . '/');
define('IMAGES_PATH', ROOT_PATH . 'images/');
define('UPLOADS_PATH', IMAGES_PATH . 'uploads/');

// ============ UPLOAD SETTINGS ============
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', array('jpg', 'jpeg', 'png', 'webp'));

// ============ ADMIN SETTINGS ============
// Default credentials (password is hashed)
define('ADMIN_USER', 'admin');
// Generate a new hash with: password_hash('your_password', PASSWORD_DEFAULT)
define('ADMIN_PASS_HASH', 'YOUR_PASSWORD_HASH_HERE');

// ============ CACHE BUSTING VERSION ============
// تم نقل التحكم بالإصدار إلى ملف منفصل لسهولة التعديل
// ملف التحكم: includes/version.php
require_once __DIR__ . '/version.php';
// للتوافق مع الكود القديم
if (!defined('ASSETS_VERSION')) {
    define('ASSETS_VERSION', SITE_VERSION);
}

// ============ TELEGRAM NOTIFICATIONS ============
// To get Bot Token: Talk to @BotFather on Telegram
// To get Chat ID: Send a message to the bot then it will be auto-discovered
define('TELEGRAM_BOT_TOKEN', 'YOUR_TELEGRAM_BOT_TOKEN_HERE');
define('TELEGRAM_CHAT_ID', 'YOUR_TELEGRAM_CHAT_ID_HERE');
define('TELEGRAM_ENABLED', true);

// ============ SECURITY FUNCTIONS ============
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

// ============ DATABASE HELPER ============
function db() {
    return $GLOBALS['pdo'];
}

function isDbConnected() {
    return !empty($GLOBALS['db_connected']) && $GLOBALS['pdo'] !== null;
}
