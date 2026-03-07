<?php
/**
 * Surprise! Store - Image Optimization System
 * نظام تحسين وضغط الصور
 * 
 * Features:
 * - Auto-compress uploaded images
 * - Generate thumbnails
 * - WebP conversion
 * - Lazy loading support
 */

// ============ CONFIGURATION ============
define('IMAGE_QUALITY', 85);           // JPEG/WebP quality (1-100)
define('THUMB_WIDTH', 400);            // Thumbnail width
define('THUMB_HEIGHT', 400);           // Thumbnail height
define('MAX_IMAGE_WIDTH', 1920);       // Max width for large images
define('MAX_IMAGE_HEIGHT', 1920);      // Max height for large images
define('WEBP_ENABLED', true);          // Enable WebP conversion

/**
 * تحسين صورة مرفوعة
 * @param string $sourcePath مسار الصورة الأصلية
 * @param string $destPath مسار الصورة المحسنة (اختياري)
 * @param int $quality جودة الضغط
 * @return array نتيجة العملية
 */
function optimizeImage($sourcePath, $destPath = null, $quality = null) {
    if (!file_exists($sourcePath)) {
        return ['success' => false, 'error' => 'الملف غير موجود'];
    }
    
    $quality = $quality ?? IMAGE_QUALITY;
    $destPath = $destPath ?? $sourcePath;
    
    // الحصول على معلومات الصورة
    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        return ['success' => false, 'error' => 'ملف غير صالح'];
    }
    
    list($width, $height, $type) = $imageInfo;
    $originalSize = filesize($sourcePath);
    
    // إنشاء الصورة من الملف
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $image = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return ['success' => false, 'error' => 'نوع صورة غير مدعوم'];
    }
    
    if (!$image) {
        return ['success' => false, 'error' => 'فشل في قراءة الصورة'];
    }
    
    // تصغير الصورة إذا كانت كبيرة جداً
    $newWidth = $width;
    $newHeight = $height;
    
    if ($width > MAX_IMAGE_WIDTH || $height > MAX_IMAGE_HEIGHT) {
        $ratio = min(MAX_IMAGE_WIDTH / $width, MAX_IMAGE_HEIGHT / $height);
        $newWidth = (int)($width * $ratio);
        $newHeight = (int)($height * $ratio);
        
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        
        // الحفاظ على الشفافية للـ PNG
        if ($type === IMAGETYPE_PNG) {
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
            imagefill($resized, 0, 0, $transparent);
        }
        
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);
        $image = $resized;
    }
    
    // حفظ الصورة المضغوطة
    $ext = strtolower(pathinfo($destPath, PATHINFO_EXTENSION));
    
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $result = imagejpeg($image, $destPath, $quality);
            break;
        case 'png':
            // PNG compression (0-9, 9 = max compression)
            $pngQuality = (int)((100 - $quality) / 10);
            $result = imagepng($image, $destPath, min(9, max(0, $pngQuality)));
            break;
        case 'webp':
            $result = imagewebp($image, $destPath, $quality);
            break;
        default:
            $result = imagejpeg($image, $destPath, $quality);
    }
    
    imagedestroy($image);
    
    if (!$result) {
        return ['success' => false, 'error' => 'فشل في حفظ الصورة'];
    }
    
    $newSize = filesize($destPath);
    $savings = $originalSize - $newSize;
    $savingsPercent = $originalSize > 0 ? round(($savings / $originalSize) * 100, 1) : 0;
    
    return [
        'success' => true,
        'original_size' => $originalSize,
        'new_size' => $newSize,
        'savings' => $savings,
        'savings_percent' => $savingsPercent,
        'dimensions' => ['width' => $newWidth, 'height' => $newHeight]
    ];
}

/**
 * إنشاء صورة مصغرة (Thumbnail)
 * @param string $sourcePath مسار الصورة الأصلية
 * @param string $thumbPath مسار الصورة المصغرة
 * @param int $thumbWidth عرض الـ thumbnail
 * @param int $thumbHeight ارتفاع الـ thumbnail
 * @return array نتيجة العملية
 */
function createThumbnail($sourcePath, $thumbPath = null, $thumbWidth = null, $thumbHeight = null) {
    if (!file_exists($sourcePath)) {
        return ['success' => false, 'error' => 'الملف غير موجود'];
    }
    
    $thumbWidth = $thumbWidth ?? THUMB_WIDTH;
    $thumbHeight = $thumbHeight ?? THUMB_HEIGHT;
    
    // إنشاء مسار الـ thumbnail إذا لم يعطى
    if (!$thumbPath) {
        $pathInfo = pathinfo($sourcePath);
        $thumbPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['basename'];
        
        // إنشاء مجلد thumbs
        $thumbDir = $pathInfo['dirname'] . '/thumbs/';
        if (!is_dir($thumbDir)) {
            @mkdir($thumbDir, 0755, true);
        }
    }
    
    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        return ['success' => false, 'error' => 'ملف غير صالح'];
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // حساب الأبعاد الجديدة مع الحفاظ على النسبة
    $ratio = min($thumbWidth / $width, $thumbHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    // إنشاء الصورة
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $source = @imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $source = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return ['success' => false, 'error' => 'نوع غير مدعوم'];
    }
    
    if (!$source) {
        return ['success' => false, 'error' => 'فشل في قراءة الصورة'];
    }
    
    // إنشاء الـ thumbnail
    $thumb = imagecreatetruecolor($newWidth, $newHeight);
    
    // الحفاظ على الشفافية
    if ($type === IMAGETYPE_PNG) {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
        imagefill($thumb, 0, 0, $transparent);
    }
    
    imagecopyresampled($thumb, $source, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // حفظ الـ thumbnail
    $ext = strtolower(pathinfo($thumbPath, PATHINFO_EXTENSION));
    
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $result = imagejpeg($thumb, $thumbPath, IMAGE_QUALITY);
            break;
        case 'png':
            $result = imagepng($thumb, $thumbPath, 6);
            break;
        case 'webp':
            $result = imagewebp($thumb, $thumbPath, IMAGE_QUALITY);
            break;
        default:
            $result = imagejpeg($thumb, $thumbPath, IMAGE_QUALITY);
    }
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    if (!$result) {
        return ['success' => false, 'error' => 'فشل في حفظ الـ thumbnail'];
    }
    
    return [
        'success' => true,
        'path' => $thumbPath,
        'dimensions' => ['width' => $newWidth, 'height' => $newHeight]
    ];
}

/**
 * تحويل صورة إلى WebP
 * @param string $sourcePath مسار الصورة الأصلية
 * @param string $webpPath مسار ملف WebP (اختياري)
 * @return array نتيجة العملية
 */
function convertToWebP($sourcePath, $webpPath = null) {
    if (!WEBP_ENABLED || !function_exists('imagewebp')) {
        return ['success' => false, 'error' => 'WebP غير مدعوم'];
    }
    
    if (!file_exists($sourcePath)) {
        return ['success' => false, 'error' => 'الملف غير موجود'];
    }
    
    // إنشاء مسار WebP
    if (!$webpPath) {
        $pathInfo = pathinfo($sourcePath);
        $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    }
    
    $imageInfo = @getimagesize($sourcePath);
    if (!$imageInfo) {
        return ['success' => false, 'error' => 'ملف غير صالح'];
    }
    
    list($width, $height, $type) = $imageInfo;
    
    switch ($type) {
        case IMAGETYPE_JPEG:
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $image = @imagecreatefrompng($sourcePath);
            break;
        default:
            return ['success' => false, 'error' => 'نوع غير مدعوم للتحويل'];
    }
    
    if (!$image) {
        return ['success' => false, 'error' => 'فشل في قراءة الصورة'];
    }
    
    // للـ PNG: الحفاظ على الشفافية
    if ($type === IMAGETYPE_PNG) {
        imagepalettetotruecolor($image);
        imagealphablending($image, true);
        imagesavealpha($image, true);
    }
    
    $result = imagewebp($image, $webpPath, IMAGE_QUALITY);
    imagedestroy($image);
    
    if (!$result) {
        return ['success' => false, 'error' => 'فشل في حفظ WebP'];
    }
    
    $originalSize = filesize($sourcePath);
    $webpSize = filesize($webpPath);
    $savings = $originalSize - $webpSize;
    
    return [
        'success' => true,
        'path' => $webpPath,
        'original_size' => $originalSize,
        'webp_size' => $webpSize,
        'savings' => $savings,
        'savings_percent' => round(($savings / $originalSize) * 100, 1)
    ];
}

/**
 * معالجة صورة منتج جديد
 * ضغط + thumbnail + WebP
 * @param string $imagePath مسار الصورة
 * @return array نتائج المعالجة
 */
function processProductImage($imagePath) {
    $results = [
        'original' => $imagePath,
        'optimized' => false,
        'thumbnail' => false,
        'webp' => false
    ];
    
    // 1. ضغط الصورة الأصلية
    $optimizeResult = optimizeImage($imagePath);
    if ($optimizeResult['success']) {
        $results['optimized'] = $optimizeResult;
    }
    
    // 2. إنشاء thumbnail
    $thumbResult = createThumbnail($imagePath);
    if ($thumbResult['success']) {
        $results['thumbnail'] = $thumbResult['path'];
    }
    
    // 3. تحويل إلى WebP
    if (WEBP_ENABLED) {
        $webpResult = convertToWebP($imagePath);
        if ($webpResult['success']) {
            $results['webp'] = $webpResult['path'];
        }
    }
    
    return $results;
}

/**
 * الحصول على مسار الصورة المناسب (WebP إذا متاح)
 * @param string $imagePath المسار الأصلي
 * @param bool $preferWebP تفضيل WebP
 * @return string المسار المناسب
 */
function getOptimizedImagePath($imagePath, $preferWebP = true) {
    if (!$preferWebP || !WEBP_ENABLED) {
        return $imagePath;
    }
    
    // فحص إذا الزائر يدعم WebP
    $supportsWebP = isset($_SERVER['HTTP_ACCEPT']) && 
                    strpos($_SERVER['HTTP_ACCEPT'], 'image/webp') !== false;
    
    if (!$supportsWebP) {
        return $imagePath;
    }
    
    // البحث عن نسخة WebP
    $pathInfo = pathinfo($imagePath);
    $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    
    if (file_exists(IMAGES_PATH . ltrim($webpPath, '/'))) {
        return $webpPath;
    }
    
    return $imagePath;
}

/**
 * الحصول على مسار الـ Thumbnail
 * @param string $imagePath المسار الأصلي
 * @return string مسار الـ thumbnail
 */
function getThumbnailPath($imagePath) {
    $pathInfo = pathinfo($imagePath);
    $thumbPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['basename'];
    
    $fullThumbPath = IMAGES_PATH . ltrim($thumbPath, '/');
    
    if (file_exists($fullThumbPath)) {
        return $thumbPath;
    }
    
    // إذا لم يوجد thumbnail، أرجع الصورة الأصلية
    return $imagePath;
}

/**
 * حذف جميع نسخ الصورة
 * @param string $imagePath المسار الأصلي
 */
function deleteAllImageVersions($imagePath) {
    $fullPath = IMAGES_PATH . ltrim($imagePath, '/');
    $pathInfo = pathinfo($fullPath);
    
    // الصورة الأصلية
    if (file_exists($fullPath)) {
        @unlink($fullPath);
    }
    
    // WebP
    $webpPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '.webp';
    if (file_exists($webpPath)) {
        @unlink($webpPath);
    }
    
    // Thumbnail
    $thumbPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['basename'];
    if (file_exists($thumbPath)) {
        @unlink($thumbPath);
    }
    
    // Thumbnail WebP
    $thumbWebpPath = $pathInfo['dirname'] . '/thumbs/' . $pathInfo['filename'] . '.webp';
    if (file_exists($thumbWebpPath)) {
        @unlink($thumbWebpPath);
    }
}

/**
 * إحصائيات تحسين الصور
 * @param string $directory مجلد الصور
 * @return array الإحصائيات
 */
function getImageOptimizationStats($directory = 'products') {
    $path = IMAGES_PATH . $directory . '/';
    
    if (!is_dir($path)) {
        return ['error' => 'المجلد غير موجود'];
    }
    
    $stats = [
        'total_images' => 0,
        'total_size' => 0,
        'webp_count' => 0,
        'webp_size' => 0,
        'thumbnails_count' => 0,
        'thumbnails_size' => 0,
        'potential_savings' => 0
    ];
    
    $files = glob($path . '*.{jpg,jpeg,png,webp}', GLOB_BRACE);
    
    foreach ($files as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $size = filesize($file);
        
        if ($ext === 'webp') {
            $stats['webp_count']++;
            $stats['webp_size'] += $size;
        } else {
            $stats['total_images']++;
            $stats['total_size'] += $size;
        }
    }
    
    // Thumbnails
    $thumbPath = $path . 'thumbs/';
    if (is_dir($thumbPath)) {
        $thumbs = glob($thumbPath . '*.*');
        foreach ($thumbs as $thumb) {
            $stats['thumbnails_count']++;
            $stats['thumbnails_size'] += filesize($thumb);
        }
    }
    
    // تقدير التوفير المحتمل (30% تقريباً)
    $stats['potential_savings'] = (int)($stats['total_size'] * 0.3);
    
    return $stats;
}
