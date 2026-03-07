<?php
/**
 * Surprise! Store - Logging System
 * نظام تسجيل شامل للأحداث والأخطاء
 * 
 * أنواع السجلات:
 * - orders.log: سجل الطلبات
 * - api.log: سجل طلبات API
 * - errors.log: أخطاء PHP
 * - security.log: أحداث أمنية (موجود في security.php)
 * - stock.log: تغييرات المخزون
 */

// ============ LOG LEVELS ============
define('LOG_DEBUG', 'DEBUG');
define('LOG_INFO', 'INFO');
define('LOG_WARNING', 'WARNING');
define('LOG_ERROR', 'ERROR');
define('LOG_CRITICAL', 'CRITICAL');

// ============ LOG FILES PATH ============
define('LOGS_PATH', dirname(__DIR__) . '/data/logs/');

/**
 * كتابة سجل عام
 * @param string $type نوع السجل (orders, api, errors, stock)
 * @param string $level مستوى الأهمية
 * @param string $message الرسالة
 * @param array $context بيانات إضافية
 */
function writeLog($type, $level, $message, $context = []) {
    // إنشاء مجلد السجلات إذا لم يوجد
    if (!is_dir(LOGS_PATH)) {
        @mkdir(LOGS_PATH, 0755, true);
        // إنشاء .htaccess لحماية المجلد
        @file_put_contents(LOGS_PATH . '.htaccess', "Order Deny,Allow\nDeny from all");
    }
    
    $logFile = LOGS_PATH . $type . '.log';
    
    // تدوير السجل إذا تجاوز 10MB
    if (file_exists($logFile) && filesize($logFile) > 10 * 1024 * 1024) {
        $archiveName = LOGS_PATH . $type . '_' . date('Y-m-d_H-i-s') . '.log';
        @rename($logFile, $archiveName);
        
        // حذف السجلات القديمة (أكثر من 30 يوم)
        cleanOldLogs($type);
    }
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'level' => $level,
        'message' => $message,
        'ip' => getClientIPSafe(),
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 200),
        'url' => $_SERVER['REQUEST_URI'] ?? '',
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
        'context' => $context
    ];
    
    // إضافة معلومات المستخدم إذا موجود
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in']) {
        $entry['admin'] = $_SESSION['staff_username'] ?? 'admin';
    }
    
    $line = json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
    
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

/**
 * الحصول على IP بشكل آمن
 */
function getClientIPSafe() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($ips[0]);
    } elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ?: '0.0.0.0';
}

/**
 * حذف السجلات القديمة
 */
function cleanOldLogs($type) {
    $files = glob(LOGS_PATH . $type . '_*.log');
    $maxAge = 30 * 24 * 60 * 60; // 30 يوم
    
    foreach ($files as $file) {
        if (time() - filemtime($file) > $maxAge) {
            @unlink($file);
        }
    }
}

// ============ SPECIALIZED LOGGERS ============

/**
 * سجل الطلبات
 */
function logOrder($action, $orderId, $details = []) {
    $context = array_merge(['order_id' => $orderId], $details);
    writeLog('orders', LOG_INFO, $action, $context);
}

/**
 * سجل طلبات API
 */
function logApi($endpoint, $status, $details = []) {
    $level = $status >= 400 ? LOG_WARNING : LOG_INFO;
    $context = array_merge([
        'endpoint' => $endpoint,
        'status' => $status
    ], $details);
    writeLog('api', $level, "API: $endpoint [$status]", $context);
}

/**
 * سجل الأخطاء
 */
function logError($message, $exception = null, $context = []) {
    if ($exception) {
        $context['exception'] = [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine()
        ];
    }
    writeLog('errors', LOG_ERROR, $message, $context);
}

/**
 * سجل المخزون
 */
function logStock($action, $productId, $productName, $details = []) {
    $context = array_merge([
        'product_id' => $productId,
        'product_name' => $productName
    ], $details);
    writeLog('stock', LOG_INFO, $action, $context);
}

/**
 * سجل النشاط العام
 */
function logActivity($action, $details = []) {
    writeLog('activity', LOG_INFO, $action, $details);
}

/**
 * سجل Debug (للتطوير فقط)
 */
function logDebug($message, $data = []) {
    // فقط في بيئة التطوير
    if (!defined('IS_PRODUCTION') || !IS_PRODUCTION) {
        writeLog('debug', LOG_DEBUG, $message, $data);
    }
}

// ============ LOG VIEWER (Admin) ============

/**
 * قراءة آخر سجلات
 * @param string $type نوع السجل
 * @param int $lines عدد السطور
 * @return array
 */
function getRecentLogs($type, $lines = 100) {
    $logFile = LOGS_PATH . $type . '.log';
    
    if (!file_exists($logFile)) {
        return [];
    }
    
    $logs = [];
    $file = new SplFileObject($logFile, 'r');
    $file->seek(PHP_INT_MAX);
    $totalLines = $file->key();
    
    $startLine = max(0, $totalLines - $lines);
    $file->seek($startLine);
    
    while (!$file->eof()) {
        $line = $file->fgets();
        if (trim($line)) {
            $entry = json_decode($line, true);
            if ($entry) {
                $logs[] = $entry;
            }
        }
    }
    
    return array_reverse($logs);
}

/**
 * إحصائيات السجلات
 */
function getLogStats($type) {
    $logFile = LOGS_PATH . $type . '.log';
    
    if (!file_exists($logFile)) {
        return [
            'size' => 0,
            'lines' => 0,
            'last_modified' => null
        ];
    }
    
    return [
        'size' => filesize($logFile),
        'size_formatted' => formatBytes(filesize($logFile)),
        'lines' => count(file($logFile)),
        'last_modified' => date('Y-m-d H:i:s', filemtime($logFile))
    ];
}

/**
 * تنسيق حجم الملف
 */
function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * مسح سجل معين
 */
function clearLog($type) {
    $logFile = LOGS_PATH . $type . '.log';
    if (file_exists($logFile)) {
        // أرشفة قبل المسح
        $archiveName = LOGS_PATH . $type . '_cleared_' . date('Y-m-d_H-i-s') . '.log';
        @rename($logFile, $archiveName);
        return true;
    }
    return false;
}
