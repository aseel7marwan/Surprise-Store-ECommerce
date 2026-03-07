<?php
/**
 * ============================================
 * 🔄 VERSION CONTROL FOR CACHE BUSTING
 * ============================================
 * 
 * هذا الملف يتحكم في إصدار ملفات CSS و JS
 * 
 * 📌 كيفية الاستخدام:
 * - كل مرة تقوم بتعديل أي ملف CSS أو JS
 * - قم بتغيير رقم VERSION أدناه
 * - سيتم تحديث الكاش تلقائياً في جميع المتصفحات
 * 
 * 💡 مثال: إذا كان الإصدار "1.0.0" غيّره إلى "1.0.1"
 * أو استخدم تاريخ ووقت: "2026-01-14-1615"
 * 
 * ============================================
 */

// ⚡ غيّر هذا الرقم بعد كل تحديث للموقع
define('SITE_VERSION', '6.0.9');

// ============================================
// 🛠️ الدوال المساعدة - لا تعدل من هنا
// ============================================

/**
 * إضافة رقم الإصدار لملفات CSS و JS
 * 
 * @param string $path مسار الملف
 * @return string المسار مع رقم الإصدار
 * 
 * استخدام:
 * <link rel="stylesheet" href="<?= v('css/main.css') ?>">
 * <script src="<?= v('js/app.js') ?>"></script>
 */
function v($path) {
    // إزالة أي query string موجود
    $cleanPath = strtok($path, '?');
    return $cleanPath . '?v=' . SITE_VERSION;
}

/**
 * إضافة رقم الإصدار للملفات الإدارية (admin)
 * 
 * @param string $path مسار الملف (بدون ../)
 * @return string المسار مع رقم الإصدار
 * 
 * استخدام:
 * <link rel="stylesheet" href="<?= av('css/main.css') ?>">
 * ينتج: ../css/main.css?v=1.0.1
 */
function av($path) {
    $cleanPath = strtok($path, '?');
    return '../' . $cleanPath . '?v=' . SITE_VERSION;
}

/**
 * طباعة رقم الإصدار الحالي
 * مفيد للعرض في الفوتر أو لوحة التحكم
 */
function getVersion() {
    return SITE_VERSION;
}
?>
