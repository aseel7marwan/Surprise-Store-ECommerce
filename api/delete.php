<?php
/**
 * Unified Delete API - AJAX Handler
 * Handles delete operations for all admin sections
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

header('Content-Type: application/json');

// Initialize security
initSecureSession();
setSecurityHeaders();

// Check admin auth
if (!validateAdminSession()) {
    echo json_encode(['success' => false, 'error' => 'غير مصرح']);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$type = isset($input['type']) ? $input['type'] : (isset($_POST['type']) ? $_POST['type'] : '');
$id = isset($input['id']) ? $input['id'] : (isset($_POST['id']) ? $_POST['id'] : '');
$token = isset($input['token']) ? $input['token'] : (isset($_POST['token']) ? $_POST['token'] : '');

// Validate CSRF token
if (!validateCSRFToken($token)) {
    echo json_encode(['success' => false, 'error' => 'رمز الأمان غير صالح']);
    exit;
}

// Validate inputs
if (empty($type) || empty($id)) {
    echo json_encode(['success' => false, 'error' => 'بيانات غير مكتملة']);
    exit;
}

$result = false;
$message = '';

switch ($type) {
    case 'product':
        // Delete product images first
        $product = getProduct($id);
        if ($product && !empty($product['images'])) {
            foreach ($product['images'] as $img) {
                deleteImage($img);
            }
        }
        $result = deleteProduct($id);
        $message = $result ? 'تم حذف المنتج بنجاح' : 'فشل في حذف المنتج';
        break;
        
    case 'order':
        $result = deleteOrder($id);
        $message = $result ? 'تم حذف الطلب بنجاح' : 'فشل في حذف الطلب';
        break;
        
    case 'coupon':
        $stmt = db()->prepare("DELETE FROM coupons WHERE id = ?");
        $result = $stmt->execute([$id]);
        $message = $result ? 'تم حذف الكوبون بنجاح' : 'فشل في حذف الكوبون';
        break;
        
    case 'banner':
        $banner = getBanner($id);
        if ($banner && !empty($banner['image_path'])) {
            deleteImage($banner['image_path']);
        }
        $result = deleteBanner($id);
        $message = $result ? 'تم حذف البانر بنجاح' : 'فشل في حذف البانر';
        break;
        
    case 'backup':
        $file = basename($id);
        $path = ROOT_PATH . 'backups/' . $file;
        if (file_exists($path) && strpos($file, '.zip') !== false) {
            $result = unlink($path);
            $message = $result ? 'تم حذف النسخة الاحتياطية بنجاح' : 'فشل في حذف النسخة الاحتياطية';
        } else {
            $message = 'الملف غير موجود أو غير صالح';
        }
        break;
        
    default:
        $message = 'نوع العنصر غير معروف';
}

echo json_encode([
    'success' => $result,
    'message' => $message
]);
