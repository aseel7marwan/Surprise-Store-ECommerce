<?php
/**
 * API Endpoint: Upload Customer Image
 * Uploads customer's custom image with auto-optimization
 * SECURITY: Rate limited, MIME validated, secure filename
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/images.php';
require_once '../includes/logging.php';

// Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// SECURITY: Rate limiting for uploads (max 20 per hour per IP)
if (isApiRateLimited('upload')) {
    logApi('upload-image', 429, ['reason' => 'rate_limited']);
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'تجاوزت الحد المسموح لرفع الملفات. حاول لاحقاً.']);
    exit;
}
recordApiRequest('upload');

// Create uploads directory if not exists
$uploadsDir = IMAGES_PATH . 'uploads/';
if (!is_dir($uploadsDir)) {
    mkdir($uploadsDir, 0755, true);
    // Add security .htaccess
    file_put_contents($uploadsDir . '.htaccess', "Options -Indexes\nAddHandler cgi-script .php .pl .py .jsp .asp .sh .cgi\nOptions -ExecCGI");
}

// Check if file uploaded via form
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $result = handleFileUpload($_FILES['image']);
    echo json_encode($result);
    exit;
}

// Check for base64 data
$input = json_decode(file_get_contents('php://input'), true);

if (!empty($input['image_data'])) {
    $result = handleBase64Upload($input['image_data']);
    echo json_encode($result);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'error' => 'No image provided']);
exit;

/**
 * Handle file upload (multipart/form-data)
 */
function handleFileUpload($file) {
    // Validate file size (10MB max for high quality)
    $maxSize = 10 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        logApi('upload-image', 400, ['reason' => 'file_too_large', 'size' => $file['size']]);
        return ['success' => false, 'error' => 'حجم الملف كبير جداً (الحد الأقصى 10MB)'];
    }
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    
    if (!in_array($mimeType, $allowedTypes)) {
        logApi('upload-image', 400, ['reason' => 'invalid_type', 'mime' => $mimeType]);
        return ['success' => false, 'error' => 'نوع الملف غير مسموح (JPG, PNG, WEBP فقط)'];
    }
    
    // Get extension
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp'
    ];
    $extension = $extensions[$mimeType];
    
    // Generate unique filename
    $filename = 'customer_' . uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = IMAGES_PATH . 'uploads/' . $filename;
    
    // Move uploaded file - KEEP ORIGINAL QUALITY for printing
    // Customer images are for printing, so we don't compress them
    if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
        logApi('upload-image', 500, ['reason' => 'move_failed']);
        return ['success' => false, 'error' => 'فشل في حفظ الصورة'];
    }
    
    // NO compression for customer uploads - they need full quality for printing
    // Only resize if extremely large (over 4000px) to prevent server issues
    $imageInfo = @getimagesize($uploadPath);
    if ($imageInfo && ($imageInfo[0] > 4000 || $imageInfo[1] > 4000)) {
        // Only resize, don't compress quality
        optimizeImage($uploadPath, null, 100); // 100% quality = no compression
    }
    
    // Log success
    logApi('upload-image', 200, [
        'filename' => $filename,
        'size' => filesize($uploadPath),
        'note' => 'full_quality_preserved'
    ]);
    
    return [
        'success' => true,
        'filename' => $filename,
        'path' => 'uploads/' . $filename,
        'url' => 'images/uploads/' . $filename,
        'size' => filesize($uploadPath),
        'type' => $mimeType,
        'quality' => 'original' // Full quality preserved
    ];
}

/**
 * Handle base64 image data
 */
function handleBase64Upload($imageData) {
    // Validate base64 format
    if (!preg_match('/^data:image\/(jpeg|png|webp);base64,/', $imageData, $matches)) {
        logApi('upload-image', 400, ['reason' => 'invalid_base64']);
        return ['success' => false, 'error' => 'Invalid image format'];
    }
    
    $imageType = $matches[1];
    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
    $imageData = base64_decode($imageData);
    
    if ($imageData === false) {
        return ['success' => false, 'error' => 'Invalid image data'];
    }
    
    // Check size (10MB max)
    if (strlen($imageData) > 10 * 1024 * 1024) {
        return ['success' => false, 'error' => 'حجم الصورة كبير جداً'];
    }
    
    // Generate filename
    $extension = $imageType === 'jpeg' ? 'jpg' : $imageType;
    $filename = 'upload_' . uniqid() . '_' . time() . '.' . $extension;
    $uploadPath = IMAGES_PATH . 'uploads/' . $filename;
    
    // Save file - KEEP ORIGINAL QUALITY for printing
    if (!file_put_contents($uploadPath, $imageData)) {
        logApi('upload-image', 500, ['reason' => 'save_failed']);
        return ['success' => false, 'error' => 'Failed to save image'];
    }
    
    // NO compression for customer uploads - they need full quality for printing
    // Log success
    logApi('upload-image', 200, [
        'filename' => $filename,
        'size' => strlen($imageData),
        'source' => 'base64',
        'note' => 'full_quality_preserved'
    ]);
    
    return [
        'success' => true,
        'filename' => $filename,
        'path' => 'uploads/' . $filename,
        'url' => 'images/uploads/' . $filename,
        'quality' => 'original' // Full quality preserved
    ];
}
