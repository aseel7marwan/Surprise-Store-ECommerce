<?php
/**
 * Backup System - نظام النسخ الاحتياطي
 * للمدير الرئيسي فقط (Super Admin)
 * v3.9.1 - Full Rebuild with Fail-safe
 */

// ============ UI DISABLED ============
// Redirect to dashboard as requested by owner
require_once '../includes/config.php';
require_once '../includes/functions.php';
initSecureSession();
header('Location: index');
exit;

// ============ FAIL-SAFE ERROR HANDLER ============
// Catch ALL errors and prevent 500
$backupError = '';
$backupMessage = '';

function backupErrorHandler($errno, $errstr, $errfile, $errline) {
    global $backupError;
    $backupError = "خطأ PHP: $errstr (سطر $errline)";
    error_log("Backup Error: $errstr in $errfile on line $errline");
    return true;
}
set_error_handler('backupErrorHandler');

function backupExceptionHandler($e) {
    global $backupError;
    $backupError = "استثناء: " . $e->getMessage();
    error_log("Backup Exception: " . $e->getMessage());
}
set_exception_handler('backupExceptionHandler');

// Prevent any output before headers
ob_start();

// ============ INCLUDES (with error catching) ============
try {
    require_once '../includes/config.php';
    require_once '../includes/functions.php';
    require_once '../includes/security.php';
} catch (Exception $e) {
    $backupError = 'خطأ في تحميل ملفات النظام: ' . $e->getMessage();
}

// ============ AUTHENTICATION ============
try {
    initSecureSession();
    setSecurityHeaders();
    
    // BACKUP UI DISABLED - Redirect to dashboard
    header('Location: index');
    exit;

    if (!validateAdminSession()) {
        ob_end_clean();
        header('Location: login');
        exit;
    }
    
    if (!isAdmin()) {
        ob_end_clean();
        $_SESSION['error'] = 'ليس لديك صلاحية الوصول';
        header('Location: index');
        exit;
    }
} catch (Exception $e) {
    $backupError = 'خطأ في التحقق من الصلاحيات';
}

// ============ REMAINING CODE (unreachable due to exit on line 14) ============

// ============ CONFIGURATION ============
$backupDir = '';
$backups = array();
$totalSize = 0;

try {
    // Define backup directory
    if (defined('ROOT_PATH')) {
        $backupDir = ROOT_PATH . 'backups/';
    } else {
        $backupDir = dirname(dirname(__FILE__)) . '/backups/';
    }
    
    // Create directory if needed
    if (!is_dir($backupDir)) {
        if (!@mkdir($backupDir, 0755, true)) {
            $backupError = 'لا يمكن إنشاء مجلد النسخ الاحتياطي';
        }
    }
    
    // Create protection files
    if (is_dir($backupDir) && is_writable($backupDir)) {
        if (!file_exists($backupDir . '.htaccess')) {
            @file_put_contents($backupDir . '.htaccess', "Order deny,allow\nDeny from all");
        }
        if (!file_exists($backupDir . 'index.php')) {
            @file_put_contents($backupDir . 'index.php', "<?php die();");
        }
    }
} catch (Exception $e) {
    $backupError = 'خطأ في إعداد مجلد النسخ';
}

// ============ CSRF TOKEN ============
$csrf_token = '';
try {
    $csrf_token = generateCSRFToken();
} catch (Exception $e) {
    $csrf_token = md5(session_id() . time());
}

// ============ HELPER FUNCTIONS ============
function formatFileSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    }
    return number_format($bytes / 1024, 1) . ' KB';
}

function safeBackupFilename($prefix, $ext) {
    return $prefix . '_' . date('Ymd_His') . '_' . substr(md5(uniqid(mt_rand(), true)), 0, 6) . '.' . $ext;
}

// ============ BACKUP DATABASE FUNCTION ============
function createDatabaseBackup($backupDir) {
    try {
        $pdo = db();
        if (!$pdo) {
            return array('success' => false, 'error' => 'لا يوجد اتصال بقاعدة البيانات');
        }
        
        $filename = safeBackupFilename('database', 'sql');
        $filepath = $backupDir . $filename;
        
        // Get all tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        if (empty($tables)) {
            return array('success' => false, 'error' => 'قاعدة البيانات فارغة');
        }
        
        $sql = "-- Surprise! Store Database Backup\n";
        $sql .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Tables: " . count($tables) . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            // Get table structure
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create['Create Table'] . ";\n\n";
            
            // Get data
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($rows)) {
                foreach ($rows as $row) {
                    $cols = array_map(function($c) { return "`$c`"; }, array_keys($row));
                    $vals = array_map(function($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, array_values($row));
                    $sql .= "INSERT INTO `$table` (" . implode(',', $cols) . ") VALUES (" . implode(',', $vals) . ");\n";
                }
                $sql .= "\n";
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        // Write file
        if (!@file_put_contents($filepath, $sql, LOCK_EX)) {
            return array('success' => false, 'error' => 'فشل في كتابة ملف النسخة');
        }
        
        $size = @filesize($filepath);
        return array(
            'success' => true,
            'message' => 'تم نسخ قاعدة البيانات بنجاح',
            'filename' => $filename,
            'size' => $size
        );
        
    } catch (Exception $e) {
        return array('success' => false, 'error' => 'خطأ: ' . $e->getMessage());
    }
}

// ============ BACKUP FILES FUNCTION ============
function createFilesBackup($backupDir) {
    try {
        // Check ZipArchive
        if (!class_exists('ZipArchive')) {
            return array('success' => false, 'error' => 'امتداد ZipArchive غير متاح على السيرفر');
        }
        
        $filename = safeBackupFilename('files', 'zip');
        $filepath = $backupDir . $filename;
        
        $zip = new ZipArchive();
        $result = $zip->open($filepath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== true) {
            return array('success' => false, 'error' => 'فشل في إنشاء ملف ZIP (Error: ' . $result . ')');
        }
        
        $rootPath = defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(__FILE__)) . '/';
        $count = 0;
        
        // Directories to backup
        $dirs = array(
            'images/products/',
            'images/banners/',
            'images/uploads/',
            'images/icons/',
            'data/'
        );
        
        foreach ($dirs as $dir) {
            $fullPath = $rootPath . $dir;
            if (!is_dir($fullPath)) continue;
            
            $files = @scandir($fullPath);
            if (!$files) continue;
            
            foreach ($files as $file) {
                if ($file[0] === '.') continue;
                $filePath = $fullPath . $file;
                if (is_file($filePath)) {
                    $zip->addFile($filePath, $dir . $file);
                    $count++;
                }
            }
        }
        
        // Add logo
        $logoPath = $rootPath . 'images/logo.jpg';
        if (file_exists($logoPath)) {
            $zip->addFile($logoPath, 'images/logo.jpg');
            $count++;
        }
        
        $zip->close();
        
        if (!file_exists($filepath)) {
            return array('success' => false, 'error' => 'فشل في حفظ ملف ZIP');
        }
        
        $size = @filesize($filepath);
        return array(
            'success' => true,
            'message' => "تم نسخ الملفات ({$count} ملف)",
            'filename' => $filename,
            'size' => $size
        );
        
    } catch (Exception $e) {
        return array('success' => false, 'error' => 'خطأ: ' . $e->getMessage());
    }
}

// ============ HANDLE CREATE BACKUP ============
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_backup'])) {
    try {
        if (!validateCSRFToken($_POST['csrf_token'])) {
            $backupError = 'خطأ في التحقق الأمني (CSRF)';
        } else {
            $type = isset($_POST['backup_type']) ? $_POST['backup_type'] : 'database';
            
            switch ($type) {
                case 'database':
                    $result = createDatabaseBackup($backupDir);
                    break;
                case 'files':
                    $result = createFilesBackup($backupDir);
                    break;
                case 'full':
                    $dbResult = createDatabaseBackup($backupDir);
                    $filesResult = createFilesBackup($backupDir);
                    if ($dbResult['success'] && $filesResult['success']) {
                        $result = array('success' => true, 'message' => 'تم إنشاء نسخة كاملة (قاعدة البيانات + الملفات)');
                    } else {
                        $errors = array();
                        if (!$dbResult['success']) $errors[] = 'DB: ' . $dbResult['error'];
                        if (!$filesResult['success']) $errors[] = 'Files: ' . $filesResult['error'];
                        $result = array('success' => false, 'error' => implode(' | ', $errors));
                    }
                    break;
                default:
                    $result = array('success' => false, 'error' => 'نوع نسخة غير معروف');
            }
            
            if ($result['success']) {
                $backupMessage = $result['message'];
            } else {
                $backupError = $result['error'];
            }
        }
    } catch (Exception $e) {
        $backupError = 'خطأ في إنشاء النسخة: ' . $e->getMessage();
    }
}

// ============ HANDLE DOWNLOAD ============
if (isset($_GET['download']) && isset($_GET['token'])) {
    try {
        if (validateCSRFToken($_GET['token'])) {
            $file = basename($_GET['download']);
            $filepath = $backupDir . $file;
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            
            if (file_exists($filepath) && in_array($ext, array('sql', 'zip', 'json'))) {
                ob_end_clean();
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $file . '"');
                header('Content-Length: ' . filesize($filepath));
                header('Cache-Control: no-cache');
                readfile($filepath);
                exit;
            } else {
                $backupError = 'الملف غير موجود';
            }
        } else {
            $backupError = 'رابط غير صالح';
        }
    } catch (Exception $e) {
        $backupError = 'خطأ في التنزيل';
    }
}

// ============ HANDLE DELETE ============
if (isset($_GET['delete']) && isset($_GET['token'])) {
    try {
        if (validateCSRFToken($_GET['token'])) {
            $file = basename($_GET['delete']);
            $filepath = $backupDir . $file;
            
            if (file_exists($filepath) && @unlink($filepath)) {
                header('Location: backup?deleted=1');
                exit;
            } else {
                $backupError = 'فشل في حذف الملف';
            }
        }
    } catch (Exception $e) {
        $backupError = 'خطأ في الحذف';
    }
}

// ============ GET EXISTING BACKUPS ============
try {
    if (is_dir($backupDir) && is_readable($backupDir)) {
        $files = @scandir($backupDir);
        if ($files) {
            foreach ($files as $file) {
                if ($file[0] === '.') continue;
                if ($file === 'index.php') continue;
                
                $filepath = $backupDir . $file;
                if (!is_file($filepath)) continue;
                
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, array('sql', 'zip', 'json'))) continue;
                
                // Determine type
                if ($ext === 'sql') {
                    $type = 'قاعدة بيانات';
                    $icon = '🗄️';
                    $class = 'type-db';
                } elseif ($ext === 'zip') {
                    $type = 'ملفات';
                    $icon = '📦';
                    $class = 'type-files';
                } else {
                    $type = 'بيانات';
                    $icon = '📋';
                    $class = 'type-json';
                }
                
                $size = @filesize($filepath);
                $date = @filemtime($filepath);
                
                $backups[] = array(
                    'name' => $file,
                    'size' => $size,
                    'date' => $date,
                    'type' => $type,
                    'icon' => $icon,
                    'class' => $class
                );
                
                $totalSize += $size;
            }
            
            // Sort by date
            usort($backups, function($a, $b) {
                return $b['date'] - $a['date'];
            });
        }
    }
} catch (Exception $e) {
    // Silently fail
}

// Clear output buffer
ob_end_clean();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>النسخ الاحتياطي - <?php echo defined('SITE_NAME') ? SITE_NAME : 'Surprise'; ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?php echo function_exists('av') ? av('css/main.css') : '../css/main.css'; ?>">
    <link rel="stylesheet" href="<?php echo function_exists('av') ? av('css/admin.css') : '../css/admin.css'; ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .backup-header { background: linear-gradient(135deg, #e91e8c 0%, #f8a5c2 100%); border-radius: 16px; padding: 25px; margin-bottom: 25px; color: white; }
        .backup-header h1 { font-size: 1.8rem; margin-bottom: 8px; }
        .backup-header p { opacity: 0.9; }
        
        .stats-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-top: 20px; }
        .stat-box { background: rgba(255,255,255,0.2); border-radius: 12px; padding: 18px; text-align: center; backdrop-filter: blur(5px); }
        .stat-box .num { font-size: 2rem; font-weight: 700; }
        .stat-box .lbl { font-size: 0.9rem; opacity: 0.9; }
        
        .card { background: var(--bg-card); border-radius: 12px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .card h3 { margin-bottom: 20px; font-size: 1.1rem; display: flex; align-items: center; gap: 10px; }
        
        .alert { padding: 15px 20px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        
        .backup-options { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; margin-bottom: 20px; }
        .backup-opt { padding: 18px; border: 2px solid var(--border-color); border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.2s; }
        .backup-opt:hover { border-color: #e91e8c; }
        .backup-opt.selected { border-color: #e91e8c; background: rgba(233, 30, 140, 0.05); }
        .backup-opt input { display: none; }
        .backup-opt .ico { font-size: 2rem; margin-bottom: 8px; }
        .backup-opt .title { font-weight: 600; margin-bottom: 4px; }
        .backup-opt .desc { font-size: 0.8rem; color: var(--text-muted); }
        
        .backup-table { width: 100%; border-collapse: collapse; }
        .backup-table th, .backup-table td { padding: 12px; text-align: right; border-bottom: 1px solid var(--border-color); }
        .backup-table th { background: var(--bg-hover); font-weight: 600; font-size: 0.9rem; }
        .backup-table tr:hover { background: var(--bg-hover); }
        
        .type-badge { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .type-db { background: #dbeafe; color: #1d4ed8; }
        .type-files { background: #d1fae5; color: #047857; }
        .type-json { background: #fef3c7; color: #b45309; }
        
        .actions { display: flex; gap: 6px; flex-wrap: wrap; }
        .actions .btn { padding: 6px 12px; font-size: 0.85rem; border-radius: 6px; }
        
        .empty { text-align: center; padding: 50px 20px; color: var(--text-muted); }
        .empty-icon { font-size: 3.5rem; margin-bottom: 15px; opacity: 0.4; }
        
        @media (max-width: 768px) {
            .backup-table { font-size: 0.85rem; }
            .backup-table th, .backup-table td { padding: 10px 8px; }
        }
    </style>
</head>
<body class="admin-page">
    <div class="admin-layout">
        <?php @include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-content">
                
                <?php if ($backupError): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($backupError); ?></div>
                <?php endif; ?>
                
                <?php if ($backupMessage): ?>
                <div class="alert alert-success">✅ <?php echo htmlspecialchars($backupMessage); ?></div>
                <?php endif; ?>
                
                <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">✅ تم حذف النسخة بنجاح</div>
                <?php endif; ?>
                
                <!-- Header -->
                <div class="backup-header">
                    <h1>💾 النسخ الاحتياطي</h1>
                    <p>إنشاء وإدارة نسخ احتياطية لقاعدة البيانات والملفات</p>
                    
                    <div class="stats-row">
                        <div class="stat-box">
                            <div class="num"><?php echo count($backups); ?></div>
                            <div class="lbl">📁 النسخ المحفوظة</div>
                        </div>
                        <div class="stat-box">
                            <div class="num"><?php echo formatFileSize($totalSize); ?></div>
                            <div class="lbl">💾 الحجم الكلي</div>
                        </div>
                        <div class="stat-box">
                            <div class="num"><?php echo class_exists('ZipArchive') ? '✓' : '✗'; ?></div>
                            <div class="lbl">📦 ZIP متاح</div>
                        </div>
                        <div class="stat-box">
                            <div class="num"><?php echo is_writable($backupDir) ? '✓' : '✗'; ?></div>
                            <div class="lbl">📝 الكتابة</div>
                        </div>
                    </div>
                </div>
                
                <!-- Create Backup -->
                <div class="card">
                    <h3>🆕 إنشاء نسخة احتياطية</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="create_backup" value="1">
                        
                        <div class="backup-options">
                            <label class="backup-opt selected" onclick="selectOpt(this)">
                                <input type="radio" name="backup_type" value="database" checked>
                                <div class="ico">🗄️</div>
                                <div class="title">قاعدة البيانات</div>
                                <div class="desc">الطلبات والمنتجات</div>
                            </label>
                            
                            <label class="backup-opt" onclick="selectOpt(this)">
                                <input type="radio" name="backup_type" value="files">
                                <div class="ico">🖼️</div>
                                <div class="title">الملفات</div>
                                <div class="desc">الصور والإعدادات</div>
                            </label>
                            
                            <label class="backup-opt" onclick="selectOpt(this)">
                                <input type="radio" name="backup_type" value="full">
                                <div class="ico">📦</div>
                                <div class="title">نسخة كاملة</div>
                                <div class="desc">DB + ملفات</div>
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">💾 إنشاء النسخة الآن</button>
                    </form>
                </div>
                
                <!-- Backups List -->
                <div class="card">
                    <h3>📋 النسخ المحفوظة (<?php echo count($backups); ?>)</h3>
                    
                    <?php if (empty($backups)): ?>
                    <div class="empty">
                        <div class="empty-icon">📂</div>
                        <p>لا توجد نسخ احتياطية محفوظة</p>
                    </div>
                    <?php else: ?>
                    <div style="overflow-x: auto;">
                        <table class="backup-table">
                            <thead>
                                <tr>
                                    <th>الملف</th>
                                    <th>النوع</th>
                                    <th>الحجم</th>
                                    <th>التاريخ</th>
                                    <th>إجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $b): ?>
                                <tr>
                                    <td style="font-family: monospace; font-size: 0.85rem;"><?php echo htmlspecialchars($b['name']); ?></td>
                                    <td><span class="type-badge <?php echo $b['class']; ?>"><?php echo $b['icon']; ?> <?php echo $b['type']; ?></span></td>
                                    <td><?php echo formatFileSize($b['size']); ?></td>
                                    <td><?php echo formatDateTime(date('Y-m-d H:i:s', $b['date']), 'short'); ?></td>
                                    <td class="actions">
                                        <a href="?download=<?php echo urlencode($b['name']); ?>&token=<?php echo $csrf_token; ?>" class="btn btn-primary">⬇️ تحميل</a>
                                        <a href="?delete=<?php echo urlencode($b['name']); ?>&token=<?php echo $csrf_token; ?>" class="btn btn-danger" onclick="return confirm('حذف هذه النسخة؟')">🗑️</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tips -->
                <div class="card" style="background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 1px solid #a7f3d0;">
                    <h3 style="color: #047857;">💡 نصائح مهمة</h3>
                    <ul style="padding-right: 20px; line-height: 2; color: #065f46;">
                        <li>أنشئ نسخة احتياطية <strong>أسبوعياً</strong> على الأقل</li>
                        <li>احتفظ بالنسخ في مكان خارجي (Google Drive, Dropbox)</li>
                        <li>⚠️ لا تشارك ملفات النسخ مع أي شخص</li>
                    </ul>
                </div>
                
            </div>
        </main>
    </div>

    <script>
        function selectOpt(el) {
            document.querySelectorAll('.backup-opt').forEach(function(o) {
                o.classList.remove('selected');
            });
            el.classList.add('selected');
        }
    </script>
    <script src="<?php echo function_exists('av') ? av('js/admin.js') : '../js/admin.js'; ?>"></script>
</body>
</html>
