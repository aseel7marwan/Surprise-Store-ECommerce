<?php
/**
 * Security Log - سجل تسجيل الدخول والأمان
 * للمدير الرئيسي فقط (Super Admin)
 * v5.0.0 - Fixed: Delete actually works, no session resurrection
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);

function securityLogErrorHandler($errno, $errstr, $errfile, $errline) {
    global $pageError;
    $pageError = "خطأ: $errstr في السطر $errline";
    return true;
}
set_error_handler('securityLogErrorHandler');

$pageError = '';
$pageMessage = '';

try {
    require_once '../includes/config.php';
    require_once '../includes/functions.php';
    require_once '../includes/security.php';
} catch (Exception $e) {
    $pageError = 'خطأ في تحميل الملفات الأساسية';
}

initSecureSession();
setSecurityHeaders();

if (!validateAdminSession()) {
    redirect('login');
    exit;
}

if (!hasPermission('security_log')) {
    $_SESSION['error'] = 'ليس لديك صلاحية الوصول لهذه الصفحة';
    redirect('index');
    exit;
}

// Create/update tables if needed
try {
    $pdo = db();
    if ($pdo) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS login_sessions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_type VARCHAR(20) NOT NULL DEFAULT 'admin',
                user_id INT NULL,
                username VARCHAR(100) NOT NULL,
                user_display_name VARCHAR(200) DEFAULT '',
                session_id VARCHAR(128) NOT NULL,
                ip_address VARCHAR(45) NOT NULL,
                user_agent TEXT,
                device_type VARCHAR(20) DEFAULT 'unknown',
                device_fingerprint VARCHAR(64) DEFAULT '',
                os_name VARCHAR(50) DEFAULT '',
                browser_name VARCHAR(50) DEFAULT '',
                country VARCHAR(100) DEFAULT '',
                city VARCHAR(100) DEFAULT '',
                status VARCHAR(20) DEFAULT 'active',
                is_deleted TINYINT(1) DEFAULT 0,
                is_new_device TINYINT(1) DEFAULT 0,
                is_new_ip TINYINT(1) DEFAULT 0,
                is_new_location TINYINT(1) DEFAULT 0,
                action_by VARCHAR(100) NULL,
                action_at DATETIME NULL,
                login_at DATETIME NOT NULL,
                last_activity DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_username (username),
                INDEX idx_status (status),
                INDEX idx_deleted (is_deleted),
                INDEX idx_fingerprint (device_fingerprint),
                INDEX idx_session (session_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Add missing columns if they don't exist
        try {
            $pdo->exec("ALTER TABLE login_sessions ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) DEFAULT 0");
            $pdo->exec("ALTER TABLE login_sessions ADD COLUMN IF NOT EXISTS device_id VARCHAR(64) DEFAULT '' AFTER session_id");
            $pdo->exec("ALTER TABLE login_sessions ADD COLUMN IF NOT EXISTS is_new_ip TINYINT(1) DEFAULT 0 AFTER is_new_device");
            $pdo->exec("ALTER TABLE login_sessions ADD COLUMN IF NOT EXISTS last_login_at DATETIME NULL AFTER login_at");
            $pdo->exec("ALTER TABLE login_sessions ADD COLUMN IF NOT EXISTS last_ip VARCHAR(45) DEFAULT '' AFTER last_activity");
            $pdo->exec("ALTER TABLE login_sessions ADD COLUMN IF NOT EXISTS ip_changed_at DATETIME NULL AFTER last_ip");
            $pdo->exec("ALTER TABLE login_sessions ADD COLUMN IF NOT EXISTS login_count INT DEFAULT 1 AFTER ip_changed_at");
        } catch (Exception $e) {
            // Try alternative syntax for older MySQL
            try {
                $pdo->exec("ALTER TABLE login_sessions ADD COLUMN device_id VARCHAR(64) DEFAULT ''");
            } catch (Exception $e2) {}
            try {
                $pdo->exec("ALTER TABLE login_sessions ADD COLUMN is_new_ip TINYINT(1) DEFAULT 0");
            } catch (Exception $e2) {}
            try {
                $pdo->exec("ALTER TABLE login_sessions ADD COLUMN last_login_at DATETIME NULL");
            } catch (Exception $e2) {}
            try {
                $pdo->exec("ALTER TABLE login_sessions ADD COLUMN last_ip VARCHAR(45) DEFAULT ''");
            } catch (Exception $e2) {}
            try {
                $pdo->exec("ALTER TABLE login_sessions ADD COLUMN ip_changed_at DATETIME NULL");
            } catch (Exception $e2) {}
            try {
                $pdo->exec("ALTER TABLE login_sessions ADD COLUMN login_count INT DEFAULT 1");
            } catch (Exception $e2) {}
        }
    }
} catch (Exception $e) {}

$csrf_token = generateCSRFToken();
$currentSessionId = session_id();

// ═══════════════════════════════════════════════════════════════
// AJAX ACTIONS - SUPER ADMIN ONLY
// ═══════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    if (!validateCSRFToken($_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'error' => 'خطأ في الأمان - CSRF']);
        exit;
    }
    
    if (!isFullAdmin()) {
        echo json_encode(['success' => false, 'error' => 'غير مصرح - يتطلب صلاحيات المدير الكاملة']);
        exit;
    }
    
    $action = $_POST['ajax_action'];
    $adminUsername = $_SESSION['admin_username'] ?? ($_SESSION['staff_username'] ?? 'admin');
    
    try {
        $pdo = db();
        
        switch ($action) {
            // ═══════════════════════════════════════════
            // FORCE LOGOUT - Invalidate session immediately
            // ═══════════════════════════════════════════
            case 'logout':
                $sessionId = intval($_POST['session_id']);
                
                $check = $pdo->prepare("SELECT id, session_id, username, status FROM login_sessions WHERE id = ? AND is_deleted = 0");
                $check->execute([$sessionId]);
                $session = $check->fetch();
                
                if (!$session) {
                    echo json_encode(['success' => false, 'error' => 'السجل غير موجود']);
                    break;
                }
                
                if ($session['session_id'] === $currentSessionId) {
                    echo json_encode(['success' => false, 'error' => 'لا يمكنك إخراج جلستك الحالية']);
                    break;
                }
                
                // Update status
                $stmt = $pdo->prepare("
                    UPDATE login_sessions 
                    SET status = 'admin_logout', last_activity = NOW(), action_by = ?, action_at = NOW()
                    WHERE id = ?
                ");
                $result = $stmt->execute([$adminUsername, $sessionId]);
                
                // Add to forced_logouts table
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS forced_logouts (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        php_session_id VARCHAR(128) NOT NULL,
                        forced_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        forced_by VARCHAR(100),
                        INDEX idx_session (php_session_id)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $pdo->prepare("INSERT IGNORE INTO forced_logouts (php_session_id, forced_by) VALUES (?, ?)")
                        ->execute([$session['session_id'], $adminUsername]);
                } catch (Exception $e) {}
                
                echo json_encode(['success' => $result, 'message' => 'تم إخراج الجلسة - سيُطرد المستخدم فوراً']);
                break;
                
            // ═══════════════════════════════════════════
            // BLOCK - Block device permanently
            // ═══════════════════════════════════════════
            case 'block':
                $sessionId = intval($_POST['session_id']);
                $reason = sanitize($_POST['reason'] ?? 'حظر يدوي من المدير');
                
                $check = $pdo->prepare("SELECT * FROM login_sessions WHERE id = ? AND is_deleted = 0");
                $check->execute([$sessionId]);
                $session = $check->fetch();
                
                if (!$session) {
                    echo json_encode(['success' => false, 'error' => 'السجل غير موجود']);
                    break;
                }
                
                if ($session['session_id'] === $currentSessionId) {
                    echo json_encode(['success' => false, 'error' => 'لا يمكنك حظر جلستك الحالية']);
                    break;
                }
                
                // Update session status
                $pdo->prepare("
                    UPDATE login_sessions 
                    SET status = 'blocked', last_activity = NOW(), action_by = ?, action_at = NOW()
                    WHERE id = ?
                ")->execute([$adminUsername, $sessionId]);
                
                // Add to blocked_devices
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS blocked_devices (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        ip_address VARCHAR(45),
                        device_fingerprint VARCHAR(64),
                        user_agent_hash VARCHAR(64),
                        blocked_by VARCHAR(100),
                        block_reason TEXT,
                        original_session_id INT,
                        is_active TINYINT(1) DEFAULT 1,
                        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                        INDEX idx_fingerprint (device_fingerprint),
                        INDEX idx_ip (ip_address)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
                    
                    $pdo->prepare("
                        INSERT INTO blocked_devices 
                        (ip_address, device_fingerprint, user_agent_hash, blocked_by, block_reason, original_session_id)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $session['ip_address'],
                        $session['device_fingerprint'],
                        hash('sha256', $session['user_agent'] ?: ''),
                        $adminUsername,
                        $reason,
                        $sessionId
                    ]);
                } catch (Exception $e) {}
                
                // Force logout too
                try {
                    $pdo->prepare("INSERT IGNORE INTO forced_logouts (php_session_id, forced_by) VALUES (?, ?)")
                        ->execute([$session['session_id'], $adminUsername]);
                } catch (Exception $e) {}
                
                echo json_encode(['success' => true, 'message' => 'تم حظر الجهاز - لن يتمكن من الدخول مجدداً']);
                break;
                
            // ═══════════════════════════════════════════
            // DELETE - Forget Device + Soft delete session
            // Removes device from known_devices so next login = new device
            // ═══════════════════════════════════════════
            case 'delete':
                $sessionId = intval($_POST['session_id']);
                
                // Use SELECT * to avoid column not found errors
                $check = $pdo->prepare("SELECT * FROM login_sessions WHERE id = ? AND is_deleted = 0");
                $check->execute([$sessionId]);
                $session = $check->fetch();
                
                if (!$session) {
                    echo json_encode(['success' => false, 'error' => 'السجل غير موجود أو محذوف مسبقاً']);
                    break;
                }
                
                if ($session['session_id'] === $currentSessionId) {
                    echo json_encode(['success' => false, 'error' => 'لا يمكنك حذف جلستك الحالية']);
                    break;
                }
                
                // If active, force logout first
                if ($session['status'] === 'active') {
                    $pdo->prepare("UPDATE login_sessions SET status = 'admin_logout', action_by = ?, action_at = NOW() WHERE id = ?")
                        ->execute([$adminUsername, $sessionId]);
                    try {
                        $pdo->prepare("INSERT IGNORE INTO forced_logouts (php_session_id, forced_by) VALUES (?, ?)")
                            ->execute([$session['session_id'], $adminUsername]);
                    } catch (Exception $e) {}
                }
                
                // FORGET DEVICE - Remove from known_devices
                // Next login will be treated as "new device"
                if (!empty($session['device_id'])) {
                    forgetDevice($session['username'], $session['device_id']);
                }
                
                // SOFT DELETE - Mark session as deleted
                $stmt = $pdo->prepare("
                    UPDATE login_sessions 
                    SET is_deleted = 1, status = 'deleted', action_by = ?, action_at = NOW()
                    WHERE id = ?
                ");
                $result = $stmt->execute([$adminUsername, $sessionId]);
                
                if ($result && $stmt->rowCount() > 0) {
                    echo json_encode(['success' => true, 'message' => 'تم نسيان الجهاز - سيظهر كجهاز جديد عند الدخول التالي']);
                } else {
                    echo json_encode(['success' => false, 'error' => 'فشل في الحذف']);
                }
                break;
                
            // ═══════════════════════════════════════════
            // BULK DELETE - Delete multiple records
            // ═══════════════════════════════════════════
            case 'bulk_delete':
                $ids = isset($_POST['ids']) ? json_decode($_POST['ids'], true) : [];
                if (!is_array($ids) || empty($ids)) {
                    echo json_encode(['success' => false, 'error' => 'لم يتم تحديد سجلات']);
                    break;
                }
                
                $deleted = 0;
                $skippedActive = 0;
                $skippedCurrent = 0;
                
                foreach ($ids as $id) {
                    $id = intval($id);
                    
                    $check = $pdo->prepare("SELECT id, session_id, status FROM login_sessions WHERE id = ? AND is_deleted = 0");
                    $check->execute([$id]);
                    $session = $check->fetch();
                    
                    if (!$session) continue;
                    
                    // Skip current session
                    if ($session['session_id'] === $currentSessionId) {
                        $skippedCurrent++;
                        continue;
                    }
                    
                    // Skip active sessions (user can force logout first if needed)
                    if ($session['status'] === 'active') {
                        $skippedActive++;
                        continue;
                    }
                    
                    // SOFT DELETE
                    if ($pdo->prepare("UPDATE login_sessions SET is_deleted = 1, status = 'deleted', action_by = ?, action_at = NOW() WHERE id = ?")
                            ->execute([$adminUsername, $id])) {
                        $deleted++;
                    }
                }
                
                $msg = "تم حذف {$deleted} سجل";
                if ($skippedActive > 0) {
                    $msg .= " (استثنيت {$skippedActive} جلسة نشطة)";
                }
                if ($skippedCurrent > 0) {
                    $msg .= " (استثنيت جلستك الحالية)";
                }
                echo json_encode(['success' => true, 'message' => $msg, 'deleted' => $deleted, 'skipped_active' => $skippedActive]);
                break;
                
            // ═══════════════════════════════════════════
            // BULK LOGOUT - Force logout multiple sessions
            // ═══════════════════════════════════════════
            case 'bulk_logout':
                $ids = isset($_POST['ids']) ? json_decode($_POST['ids'], true) : [];
                if (!is_array($ids) || empty($ids)) {
                    echo json_encode(['success' => false, 'error' => 'لم يتم تحديد سجلات']);
                    break;
                }
                
                $loggedOut = 0;
                $skippedActiveNow = 0;
                $skippedCurrent = 0;
                $skippedNotActive = 0;
                
                foreach ($ids as $id) {
                    $id = intval($id);
                    
                    $check = $pdo->prepare("SELECT id, session_id, username, status, last_activity FROM login_sessions WHERE id = ? AND is_deleted = 0");
                    $check->execute([$id]);
                    $session = $check->fetch();
                    
                    if (!$session) continue;
                    
                    // Skip current session
                    if ($session['session_id'] === $currentSessionId) {
                        $skippedCurrent++;
                        continue;
                    }
                    
                    // Skip non-active sessions (already logged out)
                    if ($session['status'] !== 'active') {
                        $skippedNotActive++;
                        continue;
                    }
                    
                    // Check if "active now" (last activity within 15 minutes)
                    $isActiveNow = false;
                    if (!empty($session['last_activity'])) {
                        $lastAct = strtotime($session['last_activity']);
                        $isActiveNow = ($lastAct >= (time() - 900));
                    }
                    
                    if ($isActiveNow) {
                        $skippedActiveNow++;
                        continue;
                    }
                    
                    // Force logout
                    $pdo->prepare("
                        UPDATE login_sessions 
                        SET status = 'admin_logout', last_activity = NOW(), action_by = ?, action_at = NOW()
                        WHERE id = ?
                    ")->execute([$adminUsername, $id]);
                    
                    try {
                        $pdo->prepare("INSERT IGNORE INTO forced_logouts (php_session_id, forced_by) VALUES (?, ?)")
                            ->execute([$session['session_id'], $adminUsername]);
                    } catch (Exception $e) {}
                    
                    $loggedOut++;
                }
                
                // Log the event
                logSecurityEvent('BULK_LOGOUT', 'تسجيل خروج جماعي', [
                    'logged_out' => $loggedOut,
                    'skipped_active_now' => $skippedActiveNow,
                    'executed_by' => $adminUsername
                ]);
                
                $msg = "تم تسجيل خروج {$loggedOut} جلسة";
                if ($skippedActiveNow > 0) {
                    $msg .= "، واستُثنيت {$skippedActiveNow} جلسة نشطة الآن";
                }
                if ($skippedCurrent > 0) {
                    $msg .= " (استثنيت جلستك الحالية)";
                }
                echo json_encode(['success' => true, 'message' => $msg, 'logged_out' => $loggedOut, 'skipped_active_now' => $skippedActiveNow]);
                break;
                
            // ═══════════════════════════════════════════
            // CLEANUP ALL - Force logout + delete everything except current session
            // ═══════════════════════════════════════════
            case 'cleanup_all':
                // Step 1: Force logout all active sessions except current
                $stmtLogout = $pdo->prepare("
                    UPDATE login_sessions 
                    SET status = 'admin_logout', last_activity = NOW(), action_by = ?, action_at = NOW()
                    WHERE session_id != ? AND status = 'active' AND is_deleted = 0
                ");
                $stmtLogout->execute([$adminUsername, $currentSessionId]);
                $loggedOutCount = $stmtLogout->rowCount();
                
                // Insert into forced_logouts for all active sessions
                try {
                    $activeSessions = $pdo->prepare("SELECT session_id FROM login_sessions WHERE session_id != ? AND status = 'admin_logout' AND action_by = ? AND is_deleted = 0");
                    $activeSessions->execute([$currentSessionId, $adminUsername]);
                    while ($row = $activeSessions->fetch()) {
                        try {
                            $pdo->prepare("INSERT IGNORE INTO forced_logouts (php_session_id, forced_by) VALUES (?, ?)")
                                ->execute([$row['session_id'], $adminUsername]);
                        } catch (Exception $e) {}
                    }
                } catch (Exception $e) {}
                
                // Step 2: Soft delete ALL sessions except current
                $stmtDelete = $pdo->prepare("
                    UPDATE login_sessions 
                    SET is_deleted = 1, status = 'deleted', action_by = ?, action_at = NOW()
                    WHERE session_id != ? AND is_deleted = 0
                ");
                $stmtDelete->execute([$adminUsername, $currentSessionId]);
                $deletedCount = $stmtDelete->rowCount();
                
                // Log the event
                logSecurityEvent('CLEANUP_ALL', 'تنظيف شامل للسجل', [
                    'logged_out' => $loggedOutCount,
                    'deleted' => $deletedCount,
                    'executed_by' => $adminUsername
                ]);
                
                $msg = "تم تنظيف السجل: تسجيل خروج {$loggedOutCount} جلسة + حذف {$deletedCount} سجل. تم استثناء جلستك الحالية.";
                echo json_encode(['success' => true, 'message' => $msg, 'logged_out' => $loggedOutCount, 'deleted' => $deletedCount]);
                break;
                
            // ═══════════════════════════════════════════
            // UNBLOCK - Remove block
            // ═══════════════════════════════════════════
            case 'unblock':
                $sessionId = intval($_POST['session_id']);
                
                $check = $pdo->prepare("SELECT device_fingerprint FROM login_sessions WHERE id = ?");
                $check->execute([$sessionId]);
                $session = $check->fetch();
                
                if (!$session) {
                    echo json_encode(['success' => false, 'error' => 'السجل غير موجود']);
                    break;
                }
                
                $pdo->prepare("UPDATE login_sessions SET status = 'logged_out', action_by = ?, action_at = NOW() WHERE id = ?")
                    ->execute([$adminUsername, $sessionId]);
                
                try {
                    $pdo->prepare("UPDATE blocked_devices SET is_active = 0 WHERE device_fingerprint = ?")
                        ->execute([$session['device_fingerprint']]);
                } catch (Exception $e) {}
                
                echo json_encode(['success' => true, 'message' => 'تم إلغاء الحظر']);
                break;
                
            // ═══════════════════════════════════════════
            // UNTRUST - Remove device trust (force 2FA next login)
            // ═══════════════════════════════════════════
            case 'untrust':
                $sessionId = intval($_POST['session_id']);
                
                $check = $pdo->prepare("SELECT username, device_id FROM login_sessions WHERE id = ?");
                $check->execute([$sessionId]);
                $session = $check->fetch();
                
                if (!$session || empty($session['device_id'])) {
                    echo json_encode(['success' => false, 'error' => 'السجل غير موجود أو لا يوجد جهاز مرتبط']);
                    break;
                }
                
                untrustDevice($session['username'], $session['device_id']);
                
                echo json_encode(['success' => true, 'message' => 'تم إلغاء توثيق الجهاز - سيُطلب 2FA عند الدخول التالي']);
                break;
                
            default:
                echo json_encode(['success' => false, 'error' => 'إجراء غير معروف']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'حدث خطأ: ' . $e->getMessage()]);
    }
    exit;
}

// ═══════════════════════════════════════════════════════════════
// GET SESSIONS (excluding deleted)
// ═══════════════════════════════════════════════════════════════
$filterStatus = $_GET['status'] ?? '';
$filterSearch = $_GET['search'] ?? '';
$filterPeriod = $_GET['period'] ?? '';
$filterNewDevice = isset($_GET['new_device']) ? 1 : 0;

$sessions = [];
$stats = ['active' => 0, 'today' => 0, 'new_devices' => 0, 'blocked' => 0];

try {
    $pdo = db();
    if ($pdo) {
        // IMPORTANT: Always exclude deleted sessions (is_deleted = 1)
        $where = ["is_deleted = 0"];
        $params = [];
        
        if ($filterStatus) {
            $where[] = "status = ?";
            $params[] = $filterStatus;
        }
        
        if ($filterSearch) {
            $where[] = "(username LIKE ? OR ip_address LIKE ? OR city LIKE ? OR country LIKE ?)";
            $search = "%$filterSearch%";
            $params = array_merge($params, [$search, $search, $search, $search]);
        }
        
        if ($filterPeriod === 'today') {
            $where[] = "DATE(login_at) = CURDATE()";
        } elseif ($filterPeriod === 'week') {
            $where[] = "login_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($filterPeriod === 'month') {
            $where[] = "login_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
        
        if ($filterNewDevice) {
            $where[] = "is_new_device = 1";
        }
        
        $whereClause = 'WHERE ' . implode(' AND ', $where);
        
        $stmt = $pdo->prepare("SELECT * FROM login_sessions $whereClause ORDER BY login_at DESC LIMIT 200");
        $stmt->execute($params);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Stats - also exclude deleted
        $stats['active'] = $pdo->query("SELECT COUNT(*) FROM login_sessions WHERE status = 'active' AND is_deleted = 0")->fetchColumn();
        $stats['today'] = $pdo->query("SELECT COUNT(*) FROM login_sessions WHERE DATE(login_at) = CURDATE() AND is_deleted = 0")->fetchColumn();
        $stats['new_devices'] = $pdo->query("SELECT COUNT(*) FROM login_sessions WHERE is_new_device = 1 AND DATE(login_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND is_deleted = 0")->fetchColumn();
        $stats['blocked'] = $pdo->query("SELECT COUNT(*) FROM login_sessions WHERE status = 'blocked' AND is_deleted = 0")->fetchColumn();
    }
} catch (Exception $e) {
    $pageError = 'خطأ في قراءة السجلات';
    $sessions = [];
}

// ═══════════════════════════════════════════════════════════════
// Helper Functions
// ═══════════════════════════════════════════════════════════════
function getDeviceIcon($type) {
    switch (strtolower($type)) {
        case 'phone': return '📱';
        case 'tablet': return '📱';
        case 'desktop': return '💻';
        default: return '🖥️';
    }
}

function getStatusLabel($status) {
    switch ($status) {
        case 'active': return ['🟢 نشط', 'status-active'];
        case 'expired': return ['⏱️ منتهي', 'status-expired'];
        case 'logged_out': return ['🚪 خروج', 'status-logout'];
        case 'admin_logout': return ['🔒 إخراج إداري', 'status-admin'];
        case 'blocked': return ['⛔ محظور', 'status-blocked'];
        default: return ['غير معروف', ''];
    }
}

function getDeviceBadge($session, $currentSessionId) {
    $badges = [];
    
    if ($session['session_id'] === $currentSessionId) {
        $badges[] = '<span class="device-badge current">✓ جلستك الحالية</span>';
    }
    
    if ($session['is_new_device']) {
        $badges[] = '<span class="device-badge new-device">📱 جهاز جديد</span>';
    } elseif (!empty($session['is_new_ip'])) {
        $badges[] = '<span class="device-badge new-ip">🌐 IP جديد</span>';
    }
    
    // 2FA Trust badge
    if (!empty($session['device_id']) && !empty($session['username'])) {
        try {
            if (function_exists('db') && isDbConnected()) {
                $stmt = db()->prepare("SELECT is_trusted FROM known_devices WHERE username = ? AND device_id = ? AND is_forgotten = 0 LIMIT 1");
                $stmt->execute([$session['username'], $session['device_id']]);
                $device = $stmt->fetch();
                if ($device) {
                    if (!empty($device['is_trusted'])) {
                        $badges[] = '<span class="device-badge" style="background:#d1fae5;color:#065f46;">🔒 موثوق</span>';
                    } else {
                        $badges[] = '<span class="device-badge" style="background:#fef3c7;color:#92400e;">🔓 غير موثوق</span>';
                    }
                }
            }
        } catch (Exception $e) {}
    }
    
    return implode(' ', $badges);
}

// Check if session is "active now" (last activity within 15 minutes)
function isActiveNow($session) {
    if ($session['status'] !== 'active') return false;
    if (empty($session['last_activity'])) return false;
    
    $lastActivity = strtotime($session['last_activity']);
    $fifteenMinutesAgo = time() - (15 * 60);
    
    return $lastActivity >= $fifteenMinutesAgo;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل الأمان - <?php echo SITE_NAME; ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="<?php echo av('css/main.css'); ?>">
    <link rel="stylesheet" href="<?php echo av('css/admin.css'); ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .security-header {
            background: linear-gradient(135deg, #e91e8c 0%, #f8a5c2 100%);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            color: white;
        }
        .security-header h1 { font-size: 1.8rem; margin-bottom: 10px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 15px; margin-top: 20px; }
        .stat-card { background: rgba(255,255,255,0.2); border-radius: 12px; padding: 18px; text-align: center; backdrop-filter: blur(5px); }
        .stat-card .number { font-size: 2rem; font-weight: 700; }
        .stat-card .label { font-size: 0.85rem; opacity: 0.9; }
        .stat-card.active { border: 2px solid rgba(74, 222, 128, 0.5); }
        .stat-card.blocked { border: 2px solid rgba(248, 113, 113, 0.5); }
        .stat-card.new { border: 2px solid rgba(251, 191, 36, 0.5); }
        
        .filters-bar {
            background: linear-gradient(135deg, #fff 0%, #fff5f8 100%);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            border: 2px solid rgba(233, 30, 140, 0.1);
        }
        .filters-bar .search-wrap {
            position: relative;
            flex: 1;
            min-width: 200px;
        }
        .filters-bar .search-wrap::before {
            content: '🔍';
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1rem;
        }
        .filters-bar input[type="text"] {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border-radius: 25px;
            border: 2px solid rgba(233, 30, 140, 0.15);
            font-size: 0.95rem;
        }
        .filters-bar select {
            padding: 12px 35px 12px 15px;
            border-radius: 25px;
            border: 2px solid rgba(233, 30, 140, 0.15);
            font-size: 0.95rem;
            min-width: 140px;
        }
        .filters-bar .checkbox-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 15px;
            background: rgba(251, 191, 36, 0.1);
            border-radius: 25px;
            cursor: pointer;
        }
        .filters-bar .btn { padding: 12px 25px; border-radius: 25px; font-weight: 600; }
        .filters-bar .btn-primary { background: linear-gradient(135deg, #e91e8c, #c71585); color: white; border: none; }
        .filters-bar .btn-secondary { background: #f3f4f6; color: #374151; border: 2px solid #e5e7eb; }
        
        .bulk-actions {
            background: linear-gradient(135deg, #fef3c7, #fde68a);
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 15px;
            display: none;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
            border: 2px solid #fbbf24;
        }
        .bulk-actions.show { display: flex; }
        .bulk-actions .selected-count { font-weight: 700; color: #92400e; }
        .bulk-actions .btn-bulk-delete {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        .bulk-actions .btn-bulk-logout {
            background: linear-gradient(135deg, #d97706, #b45309);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .session-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 16px; overflow: hidden; }
        .session-table th, .session-table td { padding: 14px; text-align: right; border-bottom: 1px solid #f3f4f6; }
        .session-table th { background: linear-gradient(135deg, #fff5f8, #fff); font-weight: 600; }
        .session-table tr:hover { background: #fff5f8; }
        .session-table tr.warning { background: rgba(251, 191, 36, 0.08); }
        .session-table tr.blocked { background: rgba(248, 113, 113, 0.08); }
        .session-table tr.current { background: rgba(74, 222, 128, 0.08); }
        .session-table tr.active-now { border-right: 4px solid #22c55e; }
        
        .session-table .checkbox-cell { width: 40px; text-align: center; }
        .session-table input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #e91e8c; }
        
        .status-badge { display: inline-block; padding: 5px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
        .status-active { background: rgba(74, 222, 128, 0.2); color: #16a34a; }
        .status-expired, .status-logout { background: rgba(156, 163, 175, 0.2); color: #6b7280; }
        .status-admin { background: rgba(251, 191, 36, 0.2); color: #d97706; }
        .status-blocked { background: rgba(248, 113, 113, 0.2); color: #dc2626; }
        
        .device-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; margin-left: 5px; }
        .device-badge.new-device { background: rgba(251, 191, 36, 0.2); color: #d97706; }
        .device-badge.new-ip { background: rgba(59, 130, 246, 0.2); color: #2563eb; }
        .device-badge.current { background: rgba(74, 222, 128, 0.2); color: #16a34a; }
        .device-badge.active-now { background: rgba(34, 197, 94, 0.3); color: #15803d; animation: pulse 2s infinite; }
        
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.7; } }
        
        .action-btns { display: flex; gap: 5px; flex-wrap: wrap; }
        .action-btn { padding: 6px 12px; border-radius: 8px; border: none; cursor: pointer; font-size: 0.8rem; display: inline-flex; align-items: center; gap: 4px; transition: all 0.2s; font-weight: 500; }
        .action-btn.logout { background: #fef3c7; color: #92400e; }
        .action-btn.logout:hover { background: #fde68a; }
        .action-btn.block { background: #fee2e2; color: #991b1b; }
        .action-btn.block:hover { background: #fecaca; }
        .action-btn.delete { background: #f3f4f6; color: #6b7280; }
        .action-btn.delete:hover { background: #e5e7eb; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #6b7280; }
        .empty-state-icon { font-size: 4rem; margin-bottom: 15px; opacity: 0.5; }
        
        @media (max-width: 900px) {
            .session-table { display: none; }
            .mobile-cards { display: block; }
            .filters-bar { flex-direction: column; }
        }
        @media (min-width: 901px) {
            .mobile-cards { display: none; }
        }
        
        .session-card { background: #fff; border-radius: 16px; padding: 18px; margin-bottom: 15px; border: 2px solid #f3f4f6; }
        .session-card.warning { border-color: #fbbf24; }
        .session-card.blocked { border-color: #f87171; }
        .session-card.current { border-color: #4ade80; }
        .session-card-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px; gap: 10px; }
        .session-card-user { font-weight: 700; font-size: 1.1rem; }
        .session-card-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f3f4f6; font-size: 0.9rem; }
        .session-card-row:last-child { border-bottom: none; }
        .session-card-actions { display: flex; gap: 8px; margin-top: 15px; flex-wrap: wrap; }
        
        .modal-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); z-index: 9999; align-items: center; justify-content: center; }
        .modal-overlay.show { display: flex; }
        .modal-box { background: #fff; border-radius: 20px; padding: 30px; max-width: 420px; width: 90%; text-align: center; animation: modalIn 0.3s ease; }
        @keyframes modalIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .modal-icon { font-size: 3.5rem; margin-bottom: 15px; }
        .modal-title { font-size: 1.3rem; font-weight: 700; margin-bottom: 10px; }
        .modal-msg { color: #6b7280; margin-bottom: 25px; line-height: 1.6; }
        .modal-btns { display: flex; gap: 12px; justify-content: center; }
        .modal-btns button { padding: 12px 30px; border-radius: 10px; font-weight: 600; cursor: pointer; border: none; }
        .modal-btns .btn-confirm { background: linear-gradient(135deg, #e91e8c, #c71585); color: #fff; }
        .modal-btns .btn-cancel { background: #f3f4f6; color: #374151; }
        
        .toast { position: fixed; bottom: 30px; left: 50%; transform: translateX(-50%); padding: 15px 30px; border-radius: 12px; color: #fff; font-weight: 600; z-index: 99999; display: none; }
        .toast.show { display: block; animation: toastIn 0.3s ease; }
        .toast.success { background: linear-gradient(135deg, #22c55e, #16a34a); }
        .toast.error { background: linear-gradient(135deg, #dc2626, #b91c1c); }
        @keyframes toastIn { from { transform: translateX(-50%) translateY(20px); opacity: 0; } to { transform: translateX(-50%) translateY(0); opacity: 1; } }
    </style>
</head>
<body class="admin-page">
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <div class="admin-content">
                <?php if ($pageError): ?>
                <div class="alert alert-error">❌ <?php echo htmlspecialchars($pageError); ?></div>
                <?php endif; ?>
                
                <div class="security-header">
                    <h1>🛡️ مركز الأمان - سجل تسجيل الدخول</h1>
                    <p>مراقبة جميع عمليات تسجيل الدخول وإدارة الجلسات</p>
                    
                    <div class="stats-grid">
                        <div class="stat-card active">
                            <div class="number"><?php echo intval($stats['active']); ?></div>
                            <div class="label">🟢 نشط الآن</div>
                        </div>
                        <div class="stat-card">
                            <div class="number"><?php echo intval($stats['today']); ?></div>
                            <div class="label">📅 اليوم</div>
                        </div>
                        <div class="stat-card new">
                            <div class="number"><?php echo intval($stats['new_devices']); ?></div>
                            <div class="label">📱 أجهزة جديدة</div>
                        </div>
                        <div class="stat-card blocked">
                            <div class="number"><?php echo intval($stats['blocked']); ?></div>
                            <div class="label">⛔ محظور</div>
                        </div>
                    </div>
                </div>
                
                <form class="filters-bar" method="GET">
                    <div class="search-wrap">
                        <input type="text" name="search" placeholder="بحث بالاسم، IP، الموقع..." value="<?php echo htmlspecialchars($filterSearch); ?>">
                    </div>
                    
                    <select name="period">
                        <option value="">كل الأوقات</option>
                        <option value="today" <?php echo $filterPeriod === 'today' ? 'selected' : ''; ?>>اليوم</option>
                        <option value="week" <?php echo $filterPeriod === 'week' ? 'selected' : ''; ?>>هذا الأسبوع</option>
                        <option value="month" <?php echo $filterPeriod === 'month' ? 'selected' : ''; ?>>هذا الشهر</option>
                    </select>
                    
                    <select name="status">
                        <option value="">كل الحالات</option>
                        <option value="active" <?php echo $filterStatus === 'active' ? 'selected' : ''; ?>>🟢 نشط</option>
                        <option value="blocked" <?php echo $filterStatus === 'blocked' ? 'selected' : ''; ?>>⛔ محظور</option>
                        <option value="admin_logout" <?php echo $filterStatus === 'admin_logout' ? 'selected' : ''; ?>>🔒 تم إخراجه</option>
                    </select>
                    
                    <label class="checkbox-wrap">
                        <input type="checkbox" name="new_device" <?php echo $filterNewDevice ? 'checked' : ''; ?>>
                        <span>📱 أجهزة جديدة فقط</span>
                    </label>
                    
                    <button type="submit" class="btn btn-primary">تصفية</button>
                    <a href="security-log" class="btn btn-secondary">إعادة تعيين</a>
                    <button type="button" class="btn" style="background:linear-gradient(135deg,#dc2626,#991b1b);color:#fff;border:none;font-weight:600;" onclick="cleanupAll()">🧹 تنظيف السجل</button>
                </form>
                
                <!-- Bulk Actions -->
                <div class="bulk-actions" id="bulkActions">
                    <span class="selected-count"><span id="selectedCount">0</span> محدد</span>
                    <button type="button" class="btn-bulk-logout" onclick="bulkLogout()">🚪 تسجيل خروج المحدد</button>
                    <button type="button" class="btn-bulk-delete" onclick="bulkDelete()">🗑️ حذف المحدد</button>
                    <button type="button" class="btn btn-secondary" onclick="clearSelection()">إلغاء التحديد</button>
                </div>
                
                <?php if (empty($sessions)): ?>
                <div class="empty-state">
                    <div class="empty-state-icon">📋</div>
                    <h3>لا توجد سجلات</h3>
                    <p>سيتم عرض سجلات تسجيل الدخول هنا</p>
                </div>
                <?php else: ?>
                
                <table class="session-table">
                    <thead>
                        <tr>
                            <th class="checkbox-cell"><input type="checkbox" id="selectAll" onchange="toggleSelectAll()"></th>
                            <th>المستخدم</th>
                            <th>الجهاز</th>
                            <th>الموقع</th>
                            <th>IP</th>
                            <th>الوقت</th>
                            <th>الحالة</th>
                            <th>ملاحظات</th>
                            <th>إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sessions as $s): 
                            $isCurrentSession = ($s['session_id'] === $currentSessionId);
                            $isActiveNowSession = isActiveNow($s);
                            $rowClass = '';
                            if ($isCurrentSession) $rowClass = 'current';
                            elseif ($s['status'] === 'blocked') $rowClass = 'blocked';
                            elseif ($s['is_new_device']) $rowClass = 'warning';
                            if ($isActiveNowSession && !$isCurrentSession) $rowClass .= ' active-now';
                            $statusInfo = getStatusLabel($s['status']);
                        ?>
                        <tr class="<?php echo $rowClass; ?>" data-session-id="<?php echo $s['id']; ?>">
                            <td class="checkbox-cell">
                                <?php if (!$isCurrentSession): ?>
                                <input type="checkbox" class="row-checkbox" value="<?php echo $s['id']; ?>" data-is-active="<?php echo $s['status'] === 'active' ? '1' : '0'; ?>" onchange="updateBulkActions()">
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($s['username']); ?></strong>
                                <?php if ($s['user_type'] === 'admin'): ?>
                                    <span style="color: #8b5cf6; font-size: 0.8rem;">👑 مدير</span>
                                <?php else: ?>
                                    <span style="color: #6b7280; font-size: 0.8rem;">👤 موظف</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <span style="font-size: 1.5rem;"><?php echo getDeviceIcon($s['device_type']); ?></span>
                                    <div>
                                        <div><?php echo htmlspecialchars($s['os_name'] ?: 'غير معروف'); ?></div>
                                        <div style="font-size: 0.8rem; color: #6b7280;"><?php echo htmlspecialchars($s['browser_name'] ?: ''); ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $s['country'] ? htmlspecialchars(($s['city'] ? $s['city'] . '، ' : '') . $s['country']) : '<span style="color:#9ca3af;">غير متاح</span>'; ?></td>
                            <td>
                                <code style="font-size: 0.85rem;"><?php echo htmlspecialchars($s['ip_address']); ?></code>
                                <?php 
                                $lastIp = $s['last_ip'] ?? '';
                                if (!empty($lastIp) && $lastIp !== $s['ip_address']): ?>
                                <div style="font-size: 0.75rem; color: #d97706; margin-top: 2px;">آخر: <code><?php echo htmlspecialchars($lastIp); ?></code></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 0.9rem;"><?php echo formatDateTime($s['login_at'], 'date'); ?></div>
                                <div style="font-size: 0.8rem; color: #6b7280;"><?php echo formatDateTime($s['login_at'], 'time'); ?></div>
                                <?php 
                                $lastLogin = $s['last_login_at'] ?? null;
                                $loginCount = intval($s['login_count'] ?? 1);
                                if ($lastLogin && $lastLogin !== $s['login_at']): ?>
                                <div style="font-size: 0.75rem; color: #2563eb; margin-top: 3px;">آخر دخول: <?php echo formatDateTime($lastLogin, 'short'); ?></div>
                                <?php endif; ?>
                                <?php if ($loginCount > 1): ?>
                                <span class="device-badge" style="background: rgba(139,92,246,0.15); color: #7c3aed; margin-top: 3px;"><?php echo $loginCount; ?>× دخول</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $statusInfo[1]; ?>"><?php echo $statusInfo[0]; ?></span>
                                <?php if ($isActiveNowSession && !$isCurrentSession): ?>
                                <span class="device-badge active-now">⚡ نشط الآن</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo getDeviceBadge($s, $currentSessionId); ?></td>
                            <td>
                                <div class="action-btns">
                                    <?php if ($s['status'] === 'active' && !$isCurrentSession): ?>
                                        <button class="action-btn logout" onclick="doAction('logout', <?php echo $s['id']; ?>)">🚪 خروج</button>
                                        <button class="action-btn block" onclick="doAction('block', <?php echo $s['id']; ?>)">⛔ حظر</button>
                                    <?php endif; ?>
                                    <?php if ($s['status'] === 'blocked'): ?>
                                        <button class="action-btn" style="background: #d1fae5; color: #065f46;" onclick="doAction('unblock', <?php echo $s['id']; ?>)">✅ إلغاء الحظر</button>
                                    <?php endif; ?>
                                    <?php if (!$isCurrentSession && !empty($s['device_id'])): ?>
                                        <button class="action-btn" style="background: #fef3c7; color: #92400e; font-size: 0.75rem;" onclick="doAction('untrust', <?php echo $s['id']; ?>)">🔓 إلغاء توثيق</button>
                                    <?php endif; ?>
                                    <?php if (!$isCurrentSession): ?>
                                        <button class="action-btn delete" onclick="doAction('delete', <?php echo $s['id']; ?>)">🗑️</button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Mobile Cards -->
                <div class="mobile-cards">
                    <?php foreach ($sessions as $s): 
                        $isCurrentSession = ($s['session_id'] === $currentSessionId);
                        $cardClass = '';
                        if ($isCurrentSession) $cardClass = 'current';
                        elseif ($s['status'] === 'blocked') $cardClass = 'blocked';
                        elseif ($s['is_new_device']) $cardClass = 'warning';
                        $statusInfo = getStatusLabel($s['status']);
                    ?>
                    <div class="session-card <?php echo $cardClass; ?>" data-session-id="<?php echo $s['id']; ?>">
                        <div class="session-card-header">
                            <div class="session-card-user">
                                <?php echo htmlspecialchars($s['username']); ?>
                                <?php if ($s['user_type'] === 'admin'): ?><span style="color: #8b5cf6;">👑</span><?php endif; ?>
                            </div>
                            <span class="status-badge <?php echo $statusInfo[1]; ?>"><?php echo $statusInfo[0]; ?></span>
                        </div>
                        
                        <div style="margin-bottom: 10px;"><?php echo getDeviceBadge($s, $currentSessionId); ?></div>
                        
                        <div class="session-card-row"><span>📱 الجهاز</span><span><?php echo htmlspecialchars($s['os_name'] ?: 'غير معروف'); ?></span></div>
                        <div class="session-card-row"><span>📍 الموقع</span><span><?php echo htmlspecialchars($s['city'] ?: $s['country'] ?: 'غير متاح'); ?></span></div>
                        <div class="session-card-row"><span>🌐 IP</span><span><code><?php echo htmlspecialchars($s['ip_address']); ?></code></span></div>
                        <?php 
                        $lastIp = $s['last_ip'] ?? '';
                        if (!empty($lastIp) && $lastIp !== $s['ip_address']): ?>
                        <div class="session-card-row"><span>🔄 آخر IP</span><span><code><?php echo htmlspecialchars($lastIp); ?></code></span></div>
                        <?php endif; ?>
                        <div class="session-card-row"><span>🕐 أول دخول</span><span><?php echo formatDateTime($s['login_at'], 'short'); ?></span></div>
                        <?php 
                        $lastLogin = $s['last_login_at'] ?? null;
                        $loginCount = intval($s['login_count'] ?? 1);
                        if ($lastLogin && $lastLogin !== $s['login_at']): ?>
                        <div class="session-card-row"><span>🔁 آخر دخول</span><span><?php echo formatDateTime($lastLogin, 'short'); ?></span></div>
                        <?php endif; ?>
                        <?php if ($loginCount > 1): ?>
                        <div class="session-card-row"><span>🔢 عدد مرات الدخول</span><span style="color: #7c3aed; font-weight: 700;"><?php echo $loginCount; ?></span></div>
                        <?php endif; ?>
                        
                        <?php if (!$isCurrentSession): ?>
                        <div class="session-card-actions">
                            <?php if ($s['status'] === 'active'): ?>
                                <button class="action-btn logout" onclick="doAction('logout', <?php echo $s['id']; ?>)">🚪 خروج</button>
                                <button class="action-btn block" onclick="doAction('block', <?php echo $s['id']; ?>)">⛔ حظر</button>
                            <?php endif; ?>
                            <?php if ($s['status'] === 'blocked'): ?>
                                <button class="action-btn" style="background: #d1fae5; color: #065f46;" onclick="doAction('unblock', <?php echo $s['id']; ?>)">✅ إلغاء الحظر</button>
                            <?php endif; ?>
                            <?php if (!empty($s['device_id'])): ?>
                                <button class="action-btn" style="background: #fef3c7; color: #92400e; font-size: 0.8rem;" onclick="doAction('untrust', <?php echo $s['id']; ?>)">🔓 إلغاء توثيق</button>
                            <?php endif; ?>
                            <button class="action-btn delete" onclick="doAction('delete', <?php echo $s['id']; ?>)">🗑️ حذف</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Confirm Modal -->
    <div class="modal-overlay" id="confirmModal">
        <div class="modal-box">
            <div class="modal-icon" id="modalIcon">⚠️</div>
            <h3 class="modal-title" id="modalTitle">تأكيد</h3>
            <p class="modal-msg" id="modalMsg">هل أنت متأكد؟</p>
            <div class="modal-btns">
                <button class="btn-confirm" id="modalConfirm">نعم</button>
                <button class="btn-cancel" id="modalCancel">إلغاء</button>
            </div>
        </div>
    </div>
    
    <!-- Toast -->
    <div class="toast" id="toast"></div>

    <script src="<?php echo av('js/admin.js'); ?>"></script>
    <script>
        var csrfToken = '<?php echo $csrf_token; ?>';
        var confirmCallback = null;
        
        function showModal(title, msg, icon, callback) {
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMsg').textContent = msg;
            document.getElementById('modalIcon').textContent = icon;
            document.getElementById('confirmModal').classList.add('show');
            confirmCallback = callback;
        }
        
        document.getElementById('modalConfirm').onclick = function() {
            document.getElementById('confirmModal').classList.remove('show');
            if (confirmCallback) confirmCallback(true);
        };
        document.getElementById('modalCancel').onclick = function() {
            document.getElementById('confirmModal').classList.remove('show');
            if (confirmCallback) confirmCallback(false);
        };
        
        function showToast(msg, isError) {
            var t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'toast show ' + (isError ? 'error' : 'success');
            setTimeout(function() { t.classList.remove('show'); }, 3000);
        }
        
        function doAction(action, id) {
            var configs = {
                'logout': { title: 'تسجيل خروج', msg: 'هل تريد إخراج هذه الجلسة؟ سيُطرد المستخدم فوراً.', icon: '🚪' },
                'block': { title: 'حظر الجهاز', msg: 'هل تريد حظر هذا الجهاز؟ لن يتمكن من الدخول مجدداً.', icon: '⛔' },
                'unblock': { title: 'إلغاء الحظر', msg: 'هل تريد إلغاء حظر هذا الجهاز؟', icon: '✅' },
                'delete': { title: 'حذف السجل', msg: 'سيتم حذف هذا السجل ولن يظهر مجدداً.', icon: '🗑️' },
                'untrust': { title: 'إلغاء توثيق الجهاز', msg: 'سيُطلب التحقق بخطوتين (2FA) من هذا الجهاز عند الدخول التالي.', icon: '🔓' }
            };
            var c = configs[action];
            
            showModal(c.title, c.msg, c.icon, function(ok) {
                if (!ok) return;
                
                var fd = new FormData();
                fd.append('ajax_action', action);
                fd.append('session_id', id);
                fd.append('csrf_token', csrfToken);
                
                fetch('', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) {
                            showToast(d.message, false);
                            var row = document.querySelector('tr[data-session-id="' + id + '"]');
                            var card = document.querySelector('.session-card[data-session-id="' + id + '"]');
                            if (action === 'delete') {
                                // Remove from DOM immediately
                                if (row) { row.style.transition = 'opacity 0.3s'; row.style.opacity = '0'; setTimeout(function() { row.remove(); }, 300); }
                                if (card) { card.style.transition = 'opacity 0.3s'; card.style.opacity = '0'; setTimeout(function() { card.remove(); }, 300); }
                            } else {
                                setTimeout(function() { location.reload(); }, 1000);
                            }
                        } else {
                            showToast(d.error || 'حدث خطأ', true);
                        }
                    })
                    .catch(function(e) {
                        showToast('خطأ في الاتصال', true);
                    });
            });
        }
        
        // Bulk selection
        function toggleSelectAll() {
            var checked = document.getElementById('selectAll').checked;
            document.querySelectorAll('.row-checkbox').forEach(function(cb) {
                cb.checked = checked;
            });
            updateBulkActions();
        }
        
        function updateBulkActions() {
            var checked = document.querySelectorAll('.row-checkbox:checked').length;
            document.getElementById('selectedCount').textContent = checked;
            document.getElementById('bulkActions').classList.toggle('show', checked > 0);
        }
        
        function clearSelection() {
            document.querySelectorAll('.row-checkbox').forEach(function(cb) { cb.checked = false; });
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }
        
        function bulkDelete() {
            var ids = [];
            var activeCount = 0;
            document.querySelectorAll('.row-checkbox:checked').forEach(function(cb) {
                ids.push(cb.value);
                if (cb.dataset.isActive === '1') activeCount++;
            });
            
            if (ids.length === 0) return;
            
            var msg = 'سيتم حذف ' + ids.length + ' سجل';
            if (activeCount > 0) {
                msg += ' (الجلسات النشطة ' + activeCount + ' ستُستثنى تلقائياً)';
            }
            
            showModal('حذف جماعي', msg, '🗑️', function(ok) {
                if (!ok) return;
                
                var fd = new FormData();
                fd.append('ajax_action', 'bulk_delete');
                fd.append('ids', JSON.stringify(ids));
                fd.append('csrf_token', csrfToken);
                
                fetch('', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) {
                            showToast(d.message, false);
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast(d.error || 'حدث خطأ', true);
                        }
                    });
            });
        }
        
        function bulkLogout() {
            var ids = [];
            var activeNowCount = 0;
            document.querySelectorAll('.row-checkbox:checked').forEach(function(cb) {
                ids.push(cb.value);
            });
            
            if (ids.length === 0) return;
            
            var msg = 'سيتم تسجيل خروج ' + ids.length + ' جلسة محددة';
            msg += '\n(الجلسات النشطة الآن ستُستثنى تلقائياً)';
            
            showModal('تسجيل خروج جماعي', msg, '🚪', function(ok) {
                if (!ok) return;
                
                var fd = new FormData();
                fd.append('ajax_action', 'bulk_logout');
                fd.append('ids', JSON.stringify(ids));
                fd.append('csrf_token', csrfToken);
                
                fetch('', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) {
                            showToast(d.message, false);
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast(d.error || 'حدث خطأ', true);
                        }
                    })
                    .catch(function(e) {
                        showToast('خطأ في الاتصال', true);
                    });
            });
        }
        
        function cleanupAll() {
            showModal('🧹 تنظيف شامل للسجل', 'سيتم تسجيل خروج جميع الأجهزة ثم حذف سجلاتها. سيتم استثناء جلستك الحالية فقط.', '⚠️', function(ok) {
                if (!ok) return;
                
                var fd = new FormData();
                fd.append('ajax_action', 'cleanup_all');
                fd.append('csrf_token', csrfToken);
                
                fetch('', { method: 'POST', body: fd })
                    .then(function(r) { return r.json(); })
                    .then(function(d) {
                        if (d.success) {
                            showToast(d.message, false);
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            showToast(d.error || 'حدث خطأ', true);
                        }
                    })
                    .catch(function(e) {
                        showToast('خطأ في الاتصال', true);
                    });
            });
        }
    </script>
</body>
</html>
