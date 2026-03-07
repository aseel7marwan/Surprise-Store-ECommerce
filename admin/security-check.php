<?php
/**
 * System Security & Health Check
 * Run this file to verify system security and configuration
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Security headers for this page
setSecurityHeaders();

$checks = [];
$allPassed = true;

// ═══════════════════════════════════════════════════════════
// 1. DATABASE CONNECTION CHECK
// ═══════════════════════════════════════════════════════════
try {
    $pdo = db();
    $pdo->query("SELECT 1");
    $checks['database'] = ['status' => 'pass', 'message' => 'Database connection successful'];
} catch (Exception $e) {
    $checks['database'] = ['status' => 'fail', 'message' => 'Database connection failed: ' . $e->getMessage()];
    $allPassed = false;
}

// ═══════════════════════════════════════════════════════════
// 2. REQUIRED TABLES CHECK
// ═══════════════════════════════════════════════════════════
$requiredTables = ['products', 'orders', 'order_items', 'banners', 'settings', 'admins', 'customer_uploads'];
$missingTables = [];

try {
    $pdo = db();
    foreach ($requiredTables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        $checks['tables'] = ['status' => 'pass', 'message' => 'All required tables exist'];
    } else {
        $checks['tables'] = ['status' => 'fail', 'message' => 'Missing tables: ' . implode(', ', $missingTables)];
        $allPassed = false;
    }
} catch (Exception $e) {
    $checks['tables'] = ['status' => 'fail', 'message' => 'Table check failed: ' . $e->getMessage()];
    $allPassed = false;
}

// ═══════════════════════════════════════════════════════════
// 3. DIRECTORY PERMISSIONS CHECK
// ═══════════════════════════════════════════════════════════
$writableDirs = [
    'images/uploads' => IMAGES_PATH . 'uploads/',
    'images/products' => IMAGES_PATH . 'products/',
    'images/banners' => IMAGES_PATH . 'banners/',
    'data' => ROOT_PATH . 'data/'
];

$dirIssues = [];
foreach ($writableDirs as $name => $path) {
    if (!is_dir($path)) {
        $dirIssues[] = "$name (missing)";
    } elseif (!is_writable($path)) {
        $dirIssues[] = "$name (not writable)";
    }
}

if (empty($dirIssues)) {
    $checks['directories'] = ['status' => 'pass', 'message' => 'All directories are properly configured'];
} else {
    $checks['directories'] = ['status' => 'warn', 'message' => 'Directory issues: ' . implode(', ', $dirIssues)];
}

// ═══════════════════════════════════════════════════════════
// 4. SESSION CONFIGURATION CHECK
// ═══════════════════════════════════════════════════════════
$sessionChecks = [];

if (ini_get('session.cookie_httponly') == 1) {
    $sessionChecks[] = 'HttpOnly: ✓';
} else {
    $sessionChecks[] = 'HttpOnly: ✗';
    $allPassed = false;
}

if (ini_get('session.use_strict_mode') == 1) {
    $sessionChecks[] = 'Strict Mode: ✓';
} else {
    $sessionChecks[] = 'Strict Mode: ✗';
}

if (ini_get('session.cookie_samesite') === 'Strict') {
    $sessionChecks[] = 'SameSite: ✓';
} else {
    $sessionChecks[] = 'SameSite: ' . (ini_get('session.cookie_samesite') ?: 'Not set');
}

$checks['session'] = ['status' => 'pass', 'message' => implode(' | ', $sessionChecks)];

// ═══════════════════════════════════════════════════════════
// 5. PDO SECURITY SETTINGS CHECK
// ═══════════════════════════════════════════════════════════
try {
    $pdo = db();
    $emulate = $pdo->getAttribute(PDO::ATTR_EMULATE_PREPARES);
    
    if (!$emulate) {
        $checks['pdo_security'] = ['status' => 'pass', 'message' => 'PDO prepared statements are properly configured (native)'];
    } else {
        $checks['pdo_security'] = ['status' => 'warn', 'message' => 'PDO is using emulated prepared statements'];
    }
} catch (Exception $e) {
    $checks['pdo_security'] = ['status' => 'fail', 'message' => 'PDO security check failed'];
    $allPassed = false;
}

// ═══════════════════════════════════════════════════════════
// 6. SECURITY FUNCTIONS CHECK
// ═══════════════════════════════════════════════════════════
$securityFunctions = [
    'generateCSRFToken',
    'validateCSRFToken',
    'sanitize',
    'isLoginRateLimited',
    'validateAdminSession',
    'setSecurityHeaders',
    'validateUploadedFile',
    'logSecurityEvent'
];

$missingFunctions = [];
foreach ($securityFunctions as $func) {
    if (!function_exists($func)) {
        $missingFunctions[] = $func;
    }
}

if (empty($missingFunctions)) {
    $checks['security_functions'] = ['status' => 'pass', 'message' => 'All security functions are available'];
} else {
    $checks['security_functions'] = ['status' => 'fail', 'message' => 'Missing functions: ' . implode(', ', $missingFunctions)];
    $allPassed = false;
}

// ═══════════════════════════════════════════════════════════
// 7. HTACCESS CHECK
// ═══════════════════════════════════════════════════════════
if (file_exists(ROOT_PATH . '.htaccess')) {
    $htaccess = file_get_contents(ROOT_PATH . '.htaccess');
    $hasSecurityRules = (
        strpos($htaccess, 'X-Frame-Options') !== false ||
        strpos($htaccess, 'X-Content-Type-Options') !== false
    );
    
    if ($hasSecurityRules) {
        $checks['htaccess'] = ['status' => 'pass', 'message' => '.htaccess exists with security headers'];
    } else {
        $checks['htaccess'] = ['status' => 'warn', 'message' => '.htaccess exists but may need security headers'];
    }
} else {
    $checks['htaccess'] = ['status' => 'warn', 'message' => '.htaccess file not found'];
}

// ═══════════════════════════════════════════════════════════
// 8. PHP VERSION CHECK
// ═══════════════════════════════════════════════════════════
$phpVersion = PHP_VERSION;
$minVersion = '7.4.0';

if (version_compare($phpVersion, $minVersion, '>=')) {
    $checks['php_version'] = ['status' => 'pass', 'message' => "PHP $phpVersion (minimum: $minVersion)"];
} else {
    $checks['php_version'] = ['status' => 'fail', 'message' => "PHP $phpVersion is below minimum ($minVersion)"];
    $allPassed = false;
}

// ═══════════════════════════════════════════════════════════
// 9. REQUIRED EXTENSIONS CHECK
// ═══════════════════════════════════════════════════════════
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'fileinfo'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (!extension_loaded($ext)) {
        $missingExtensions[] = $ext;
    }
}

if (empty($missingExtensions)) {
    $checks['extensions'] = ['status' => 'pass', 'message' => 'All required PHP extensions are loaded'];
} else {
    $checks['extensions'] = ['status' => 'fail', 'message' => 'Missing extensions: ' . implode(', ', $missingExtensions)];
    $allPassed = false;
}

// ═══════════════════════════════════════════════════════════
// 10. DATA INTEGRITY CHECK
// ═══════════════════════════════════════════════════════════
try {
    $pdo = db();
    
    // Check for orphaned order items
    $stmt = $pdo->query("SELECT COUNT(*) FROM order_items WHERE order_id NOT IN (SELECT id FROM orders)");
    $orphanedItems = $stmt->fetchColumn();
    
    if ($orphanedItems == 0) {
        $checks['data_integrity'] = ['status' => 'pass', 'message' => 'No data integrity issues found'];
    } else {
        $checks['data_integrity'] = ['status' => 'warn', 'message' => "$orphanedItems orphaned order items found"];
    }
} catch (Exception $e) {
    $checks['data_integrity'] = ['status' => 'warn', 'message' => 'Could not check data integrity'];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Security Check - Surprise!</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, sans-serif; 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); 
            min-height: 100vh; 
            padding: 40px 20px;
            color: #fff;
        }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { 
            text-align: center; 
            margin-bottom: 40px; 
            font-size: 2rem;
            background: linear-gradient(135deg, #E91E8C, #FF6B9D);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .status-card {
            background: rgba(255,255,255,0.05);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 15px;
            border: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .status-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        .status-pass { background: rgba(76, 175, 80, 0.2); }
        .status-fail { background: rgba(244, 67, 54, 0.2); }
        .status-warn { background: rgba(255, 152, 0, 0.2); }
        .status-info h3 { margin-bottom: 5px; font-size: 1.1rem; }
        .status-info p { opacity: 0.7; font-size: 0.9rem; }
        .summary {
            background: linear-gradient(135deg, rgba(233, 30, 140, 0.2), rgba(255, 107, 157, 0.2));
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            border: 2px solid rgba(233, 30, 140, 0.3);
        }
        .summary h2 { font-size: 1.5rem; margin-bottom: 10px; }
        .summary p { opacity: 0.8; }
        .summary.success { border-color: rgba(76, 175, 80, 0.5); background: rgba(76, 175, 80, 0.1); }
        .summary.failure { border-color: rgba(244, 67, 54, 0.5); background: rgba(244, 67, 54, 0.1); }
        .timestamp { text-align: center; opacity: 0.5; margin-top: 30px; font-size: 0.85rem; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 فحص أمان النظام</h1>
        
        <div class="summary <?= $allPassed ? 'success' : 'failure' ?>">
            <h2><?= $allPassed ? '✅ النظام آمن وجاهز' : '⚠️ يوجد مشاكل تحتاج إصلاح' ?></h2>
            <p><?= $allPassed ? 'جميع فحوصات الأمان والنظام تمت بنجاح' : 'يرجى مراجعة الفحوصات أدناه وإصلاح المشاكل' ?></p>
        </div>
        
        <?php foreach ($checks as $name => $check): ?>
        <div class="status-card">
            <div class="status-icon status-<?= $check['status'] ?>">
                <?php
                switch ($check['status']) {
                    case 'pass': echo '✓'; break;
                    case 'fail': echo '✗'; break;
                    case 'warn': echo '⚠'; break;
                }
                ?>
            </div>
            <div class="status-info">
                <h3><?= ucwords(str_replace('_', ' ', $name)) ?></h3>
                <p><?= htmlspecialchars($check['message']) ?></p>
            </div>
        </div>
        <?php endforeach; ?>
        
        <p class="timestamp">تم الفحص في: <?= formatDateTime(date('Y-m-d H:i:s'), 'full') ?></p>
    </div>
</body>
</html>
