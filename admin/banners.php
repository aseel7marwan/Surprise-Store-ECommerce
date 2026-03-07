<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

if (!validateAdminSession()) {
    redirect('login');
}

$message = '';
$error = '';
$csrf_token = generateCSRFToken();

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (validateCSRFToken($_GET['token'])) {
        $id = intval($_GET['delete']);
        $banner = getBanner($id);
        if ($banner) {
            deleteImage($banner['image_path']);
        }
        if (deleteBanner($id)) {
            $message = 'تم حذف البانر بنجاح';
        }
    }
}

// Handle Toggle Active
if (isset($_POST['toggle_active'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $id = intval($_POST['banner_id']);
        toggleBannerActive($id);
        $message = 'تم تحديث حالة البانر';
    }
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_banner'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        if (!empty($_FILES['banner_image']['name'])) {
            $result = uploadImage($_FILES['banner_image'], 'banners');
            
            if ($result['success']) {
                saveBanner([
                    'image_path' => 'banners/' . $result['filename'],
                    'title' => sanitize($_POST['title']),
                    'subtitle' => sanitize($_POST['subtitle']),
                    'is_active' => true,
                    'sort_order' => 0
                ]);
                $message = 'تم إضافة البانر بنجاح';
            } else {
                $error = $result['error'];
            }
        } else {
            $error = 'يرجى اختيار صورة للبانر';
        }
    }
}

$banners = getBanners();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة البانرات - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <h1 class="admin-title">🖼️ إدارة البانرات</h1>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <!-- Add New Banner -->
            <div class="admin-card" style="margin-bottom: 30px;">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">➕ إضافة بانر جديد</h2>
                </div>
                <div style="padding: 20px;">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="add_banner" value="1">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">العنوان الرئيسي</label>
                                <input type="text" name="title" class="form-control" placeholder="مثال: أفخم الهدايا">
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">العنوان الفرعي</label>
                                <input type="text" name="subtitle" class="form-control" placeholder="مثال: تحف مضيئة وهدايا فريدة">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">صورة البانر *</label>
                            
                            <div style="display: flex; gap: 15px; align-items: center;">
                                <label class="custom-file-upload" style="flex: 1; cursor: pointer;">
                                    <input type="file" name="banner_image" id="bannerImageInput" accept="image/jpeg,image/png,image/webp" required style="display: none;">
                                    <div class="file-upload-btn" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 15px 20px; border: 2px dashed rgba(233, 30, 140, 0.3); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #fff 0%, #fafafa 100%);">
                                        <span style="font-size: 1.5rem;">🖼️</span>
                                        <span class="file-name" id="bannerFileName" style="font-weight: 600; color: var(--text-muted);">اختر صورة البانر</span>
                                    </div>
                                </label>
                                <div id="bannerImagePreview" style="width: 120px; height: 50px; border-radius: 8px; overflow: hidden; display: none; border: 2px solid var(--primary);">
                                    <img src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            </div>
                            
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 8px;">
                                الحجم الموصى به: 1200×400 بكسل (JPG, PNG, WEBP)
                            </p>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">➕ إضافة البانر</button>
                    </form>
                </div>
            </div>

            <!-- Banners List -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">البانرات الحالية (<?= count($banners) ?>)</h2>
                </div>
                
                <?php if (empty($banners)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🖼️</div>
                    <h2 class="empty-title">لا توجد بانرات</h2>
                    <p class="empty-text">أضف بانر جديد لعرضه في الصفحة الرئيسية</p>
                </div>
                <?php else: ?>
                <div style="display: grid; gap: 20px; padding: 20px;">
                    <?php foreach ($banners as $banner): ?>
                    <div style="display: flex; gap: 20px; align-items: center; padding: 15px; background: var(--bg-main); border-radius: var(--radius-md); flex-wrap: wrap;">
                        <img src="../images/<?= $banner['image_path'] ?>" alt="" style="width: 200px; height: 80px; object-fit: cover; border-radius: var(--radius-sm);" onerror="this.src='../images/logo.jpg'">
                        
                        <div style="flex: 1; min-width: 200px;">
                            <h3 style="margin-bottom: 5px;"><?= $banner['title'] ?: '(بدون عنوان)' ?></h3>
                            <p style="color: var(--text-muted); font-size: 0.9rem;"><?= $banner['subtitle'] ?: '' ?></p>
                        </div>
                        
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="banner_id" value="<?= $banner['id'] ?>">
                                <input type="hidden" name="toggle_active" value="1">
                                <button type="submit" class="btn btn-sm <?= $banner['is_active'] ? 'btn-primary' : 'btn-outline' ?>">
                                    <?= $banner['is_active'] ? '✓ نشط' : '○ معطل' ?>
                                </button>
                            </form>
                            
                            <button type="button" class="action-btn action-btn-delete"
                               data-delete-type="banner"
                               data-delete-id="<?= $banner['id'] ?>"
                               data-delete-name="<?= htmlspecialchars($banner['title'] ?: 'بانر') ?>"
                               data-delete-token="<?= $csrf_token ?>">
                                🗑️
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <script src="<?= av('js/admin.js') ?>"></script>
    <script>
    // Banner image upload preview
    document.getElementById('bannerImageInput')?.addEventListener('change', function() {
        const file = this.files[0];
        const preview = document.getElementById('bannerImagePreview');
        const fileNameSpan = document.getElementById('bannerFileName');
        const uploadBtn = this.closest('.custom-file-upload').querySelector('.file-upload-btn');
        
        if (file) {
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.querySelector('img').src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(file);
            
            // Update button text
            fileNameSpan.textContent = file.name.length > 25 ? file.name.substring(0, 25) + '...' : file.name;
            fileNameSpan.style.color = 'var(--primary)';
            uploadBtn.style.borderColor = 'var(--primary)';
            uploadBtn.style.background = 'linear-gradient(135deg, #fff0f8 0%, #ffe0f0 100%)';
        }
    });
    
    // Click on custom file upload label
    document.querySelector('.custom-file-upload')?.addEventListener('click', function(e) {
        if (e.target.tagName !== 'INPUT') {
            const input = this.querySelector('input[type="file"]');
            input.click();
        }
    });
    </script>
</body>
</html>
