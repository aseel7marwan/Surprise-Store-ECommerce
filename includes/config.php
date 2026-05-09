<?php
/**
 * Surprise Store — configuration (database, sessions, integrations)
 * Secrets and infrastructure values come from `.env` (see `.env.example`).
 */

require_once __DIR__ . '/env.php';

$__surpriseRoot = dirname(__DIR__);
surprise_load_dotenv($__surpriseRoot . DIRECTORY_SEPARATOR . '.env');

// ============ IRAQ TIMEZONE SETTINGS ============
date_default_timezone_set('Asia/Baghdad');

// ============ ENVIRONMENT ============
$appEnv = strtolower(surprise_env('APP_ENV', 'local'));
$isProduction = ($appEnv === 'production') || (isset($_SERVER['HTTP_HOST']) && (
    strpos($_SERVER['HTTP_HOST'], 'surprise-iq.com') !== false ||
    strpos($_SERVER['HTTP_HOST'], 'web-hosting.com') !== false ||
    (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
));

// ============ TOTP / 2FA (used by includes/totp.php when loaded) ============
define('TOTP_ISSUER', surprise_env('TOTP_ISSUER', 'Surprise Store'));
$_totpWindow = (int) surprise_env('TOTP_VERIFY_WINDOW', '2');
define('TOTP_VERIFY_WINDOW', max(1, min(10, $_totpWindow)));
define('APP_SECRET', surprise_env('APP_SECRET', ''));

// ============ SECURE SESSION CONFIGURATION ============
if (session_status() === PHP_SESSION_NONE) {
    $cookieLifetime = (int) surprise_env('SESSION_COOKIE_LIFETIME', '2592000');
    if ($cookieLifetime < 60) {
        $cookieLifetime = 2592000;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $sameSite = surprise_env('SESSION_COOKIE_SAMESITE', 'Lax');
    if (!in_array($sameSite, array('Lax', 'Strict', 'None'), true)) {
        $sameSite = 'Lax';
    }
    if ($sameSite === 'None' && !$secure) {
        $sameSite = 'Lax';
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.gc_maxlifetime', (string) $cookieLifetime);
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '1000');

    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params(array(
            'lifetime' => $cookieLifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => $sameSite,
        ));
    } else {
        ini_set('session.cookie_httponly', '1');
        ini_set('session.cookie_secure', $secure ? '1' : '0');
        ini_set('session.cookie_samesite', $sameSite);
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_lifetime', (string) $cookieLifetime);
    }

    session_start();
}

// ============ PRODUCTION ERROR HANDLING ============
if ($isProduction) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', $__surpriseRoot . '/data/php_errors.log');

    if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
        $redirect = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        header('HTTP/1.1 301 Moved Permanently');
        header('Location: ' . $redirect);
        exit();
    }
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}

// ============ DATABASE CONFIGURATION (from .env) ============
$dbHost = surprise_env('DB_HOST');
$dbName = surprise_env('DB_NAME');
$dbUser = surprise_env('DB_USER');
$dbPass = surprise_env('DB_PASS');

if ($dbHost === '' || $dbName === '') {
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        fwrite(STDERR, "Surprise Store: DB_HOST and DB_NAME must be set in .env\n");
        exit(1);
    }
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Configuration</title></head><body>';
    echo '<h1>Service unavailable</h1><p>Database is not configured: set <code>DB_HOST</code> and <code>DB_NAME</code> in <code>.env</code>.</p>';
    echo '</body></html>';
    exit(1);
}

define('DB_HOST', $dbHost);
define('DB_NAME', $dbName);
define('DB_USER', $dbUser);
define('DB_PASS', $dbPass);

// PDO Connection with graceful error handling
$GLOBALS['db_connected'] = false;
$GLOBALS['pdo'] = null;
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );

    $pdo->exec("SET time_zone = '+03:00'");

    $GLOBALS['pdo'] = $pdo;
    $GLOBALS['db_connected'] = true;
} catch (PDOException $e) {
    $errorLogFile = $__surpriseRoot . '/data/db_errors.log';
    $errorEntry = date('Y-m-d H:i:s') . ' | ' . $e->getMessage() . "\n";
    @file_put_contents($errorLogFile, $errorEntry, FILE_APPEND | LOCK_EX);

    $GLOBALS['pdo'] = null;
    $GLOBALS['db_error'] = 'Database connection failed';
}

// ============ SITE SETTINGS ============
define('SITE_NAME', 'بيج سبرايز | Surprise page');

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

function _getSettingEarly($pdo, $key, $default = '') {
    if (!$pdo) {
        return $default;
    }
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
        $stmt->execute(array($key));
        $value = $stmt->fetchColumn();
        return ($value !== false && $value !== '') ? $value : $default;
    } catch (Exception $e) {
        return $default;
    }
}

$_telegramUsername = _getSettingEarly($GLOBALS['pdo'], 'telegram_username', 'sur_prisese');
$_instagramUsername = _getSettingEarly($GLOBALS['pdo'], 'instagram_username', 'sur._prises');

define('INSTAGRAM_USER', $_instagramUsername);
define('INSTAGRAM_DM', 'https://ig.me/m/' . $_instagramUsername);
define('INSTAGRAM_URL', 'https://instagram.com/' . $_instagramUsername);

define('TELEGRAM_ORDER_USERNAME', $_telegramUsername);
define('TELEGRAM_ORDER_DM', 'https://t.me/' . $_telegramUsername);

define('TELEGRAM_CHANNEL', $_telegramUsername);
define('TELEGRAM_CHANNEL_URL', 'https://t.me/' . $_telegramUsername);

define('ROOT_PATH', $__surpriseRoot . '/');
define('IMAGES_PATH', ROOT_PATH . 'images/');
define('UPLOADS_PATH', IMAGES_PATH . 'uploads/');

define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_EXTENSIONS', array('jpg', 'jpeg', 'png', 'webp'));

require_once __DIR__ . '/version.php';
if (!defined('ASSETS_VERSION')) {
    define('ASSETS_VERSION', SITE_VERSION);
}

// Telegram bot API (optional notifications)
define('TELEGRAM_BOT_TOKEN', surprise_env('TELEGRAM_BOT_TOKEN', ''));
define('TELEGRAM_CHAT_ID', surprise_env('TELEGRAM_CHAT_ID', ''));
define('TELEGRAM_ENABLED', filter_var(surprise_env('TELEGRAM_ENABLED', 'true'), FILTER_VALIDATE_BOOLEAN));

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
    header('Location: ' . $url);
    exit;
}

function db() {
    return $GLOBALS['pdo'];
}

function isDbConnected() {
    return !empty($GLOBALS['db_connected']) && $GLOBALS['pdo'] !== null;
}

unset($__surpriseRoot, $appEnv, $dbHost, $dbName, $dbUser, $dbPass, $_totpWindow, $cookieLifetime, $secure, $sameSite);
