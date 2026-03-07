<?php
/**
 * Categories Management Page - إدارة الأقسام
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

if (!validateAdminSession()) {
    redirect('login');
}

$categories = getCategories(true);
$productCounts = getProductsCountByCategory();
$message = '';
$error = '';
$csrf_token = generateCSRFToken();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validateCSRFToken(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    switch ($action) {
        case 'add':
            $result = addCategory([
                'name' => sanitize($_POST['name']),
                'icon' => sanitize($_POST['icon'])
            ]);
            if ($result['success']) {
                $message = 'تم إضافة القسم بنجاح';
                $categories = getCategories(true);
            } else {
                $error = $result['error'];
            }
            break;
            
        case 'update':
            $id = sanitize($_POST['category_id']);
            $result = updateCategory($id, [
                'name' => sanitize($_POST['name']),
                'icon' => sanitize($_POST['icon']),
                'sort_order' => intval($_POST['sort_order'])
            ]);
            if ($result['success']) {
                $message = 'تم تحديث القسم بنجاح';
                $categories = getCategories(true);
            } else {
                $error = $result['error'];
            }
            break;
            
        case 'delete':
            $id = sanitize($_POST['category_id']);
            $result = deleteCategory($id);
            if ($result['success']) {
                $message = 'تم حذف القسم بنجاح';
                $categories = getCategories(true);
            } else {
                $error = $result['error'];
            }
            break;
            
        case 'toggle':
            $id = sanitize($_POST['category_id']);
            if (toggleCategoryActive($id)) {
                $message = 'تم تغيير حالة القسم';
                $categories = getCategories(true);
            } else {
                $error = 'فشل في تغيير الحالة';
            }
            break;
    }
    
    // Refresh product counts
    $productCounts = getProductsCountByCategory();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📂 إدارة الأقسام - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .reports-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-box .icon { font-size: 2rem; margin-bottom: 10px; }
        .stat-box .value { font-size: 1.8rem; font-weight: 900; color: var(--text-dark); }
        .stat-box .label { color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; }
        
        /* Categories Grid */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .category-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            transition: var(--transition);
            border: 2px solid transparent;
        }
        
        .category-card:hover {
            box-shadow: var(--shadow-md);
            border-color: rgba(233, 30, 140, 0.2);
        }
        
        .category-card.inactive {
            opacity: 0.6;
            background: #f9f9f9;
        }
        
        .category-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
        }
        
        .category-icon {
            font-size: 3rem;
            margin-bottom: 10px;
            display: block;
        }
        
        .category-name {
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .category-status {
            position: absolute;
            top: 10px;
            left: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 700;
        }
        
        .status-active {
            background: rgba(76, 175, 80, 0.2);
            color: #4CAF50;
        }
        
        .status-inactive {
            background: rgba(244, 67, 54, 0.2);
            color: #F44336;
        }
        
        .category-body {
            padding: 20px;
        }
        
        .category-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .category-info-item {
            text-align: center;
        }
        
        .category-info-value {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--primary);
        }
        
        .category-info-label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .category-actions {
            display: flex;
            gap: 8px;
        }
        
        .category-actions .btn {
            flex: 1;
            padding: 10px;
            font-size: 0.85rem;
        }
        
        /* Add Category Card */
        .add-category-card {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border: 2px dashed rgba(233, 30, 140, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 250px;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .add-category-card:hover {
            border-color: var(--primary);
            background: rgba(233, 30, 140, 0.05);
        }
        
        .add-category-icon {
            font-size: 3rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .add-category-text {
            color: var(--primary);
            font-weight: 600;
        }
        
        /* Modal */
        .modal-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: rgba(0,0,0,0.6) !important;
            display: none !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 9999 !important;
            padding: 20px !important;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }
        
        .modal-overlay.active {
            display: flex !important;
            opacity: 1;
            visibility: visible;
        }
        
        .modal-overlay .modal {
            display: block !important;
            position: relative !important;
            top: auto !important;
            left: auto !important;
            right: auto !important;
            bottom: auto !important;
            background: white !important;
            border-radius: 16px !important;
            max-width: 500px !important;
            width: 100% !important;
            max-height: 90vh !important;
            overflow-y: auto !important;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.4) !important;
            transform: scale(0.9);
            opacity: 0;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }
        
        .modal-overlay.active .modal {
            transform: scale(1);
            opacity: 1;
        }
        
        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-title {
            font-size: 1.2rem;
            font-weight: 700;
        }
        
        .modal-close {
            width: 35px;
            height: 35px;
            border: none;
            background: #f0f0f0;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.2rem;
            transition: var(--transition);
        }
        
        .modal-close:hover {
            background: #e0e0e0;
        }
        
        .modal-body {
            padding: 25px;
        }
        
        .modal-footer {
            padding: 15px 25px;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
        }
        
        /* Icon Picker */
        .icon-picker {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .icon-option {
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: #f8f9fa;
            border: 2px solid transparent;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: var(--transition);
        }
        
        .icon-option:hover {
            border-color: var(--primary-light);
            background: rgba(233, 30, 140, 0.1);
        }
        
        .icon-option.selected {
            border-color: var(--primary);
            background: rgba(233, 30, 140, 0.15);
        }
        
        @media (max-width: 768px) {
            .reports-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <!-- Header -->
            <div class="reports-header">
                <div>
                    <h1 class="admin-title">📂 إدارة الأقسام</h1>
                    <p style="color: var(--text-muted); margin-top: 5px;">أضف وعدّل أقسام المنتجات</p>
                </div>
                
                <button class="btn btn-primary" onclick="openAddModal()">
                    ➕ إضافة قسم جديد
                </button>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="icon">📂</div>
                    <div class="value"><?= count($categories) ?></div>
                    <div class="label">إجمالي الأقسام</div>
                </div>
                <div class="stat-box">
                    <div class="icon">✅</div>
                    <div class="value"><?php 
                        $activeCount = 0;
                        foreach ($categories as $c) {
                            if (isset($c['is_active']) ? $c['is_active'] : true) {
                                $activeCount++;
                            }
                        }
                        echo $activeCount;
                    ?></div>
                    <div class="label">الأقسام النشطة</div>
                </div>
                <div class="stat-box">
                    <div class="icon">📦</div>
                    <div class="value"><?= array_sum($productCounts) ?></div>
                    <div class="label">إجمالي المنتجات</div>
                </div>
            </div>
            
            <!-- Categories Grid -->
            <div class="categories-grid">
                <?php foreach ($categories as $id => $category): ?>
                <div class="category-card <?= (isset($category['is_active']) ? $category['is_active'] : true) ? '' : 'inactive' ?>">
                    <div class="category-header">
                        <span class="category-status <?= (isset($category['is_active']) ? $category['is_active'] : true) ? 'status-active' : 'status-inactive' ?>">
                            <?= (isset($category['is_active']) ? $category['is_active'] : true) ? 'نشط' : 'معطل' ?>
                        </span>
                        <span class="category-icon"><?= isset($category['icon']) ? $category['icon'] : '📦' ?></span>
                        <span class="category-name"><?= htmlspecialchars($category['name']) ?></span>
                    </div>
                    <div class="category-body">
                        <div class="category-info">
                            <div class="category-info-item">
                                <div class="category-info-value"><?= isset($productCounts[$id]) ? $productCounts[$id] : 0 ?></div>
                                <div class="category-info-label">منتج</div>
                            </div>
                            <div class="category-info-item">
                                <div class="category-info-value"><?= isset($category['sort_order']) ? $category['sort_order'] : 0 ?></div>
                                <div class="category-info-label">الترتيب</div>
                            </div>
                            <div class="category-info-item">
                                <div class="category-info-value" style="font-size: 0.9rem; color: var(--text-muted);"><?= $id ?></div>
                                <div class="category-info-label">المعرف</div>
                            </div>
                        </div>
                        <div class="category-actions">
                            <button class="btn btn-outline" onclick='openEditModal(<?= json_encode(array_merge(["id" => $id], $category)) ?>)'>
                                ✏️ تعديل
                            </button>
                            <form method="POST" style="flex: 1; display: flex;">
                                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="category_id" value="<?= $id ?>">
                                <button type="submit" class="btn btn-outline" style="width: 100%;">
                                    <?= (isset($category['is_active']) ? $category['is_active'] : true) ? '🔒 تعطيل' : '🔓 تفعيل' ?>
                                </button>
                            </form>
                            <button type="button" class="btn btn-outline" style="flex: 1; color: #F44336; border-color: #F44336;" 
                                onclick='openDeleteModal("<?= $id ?>", "<?= htmlspecialchars($category["name"], ENT_QUOTES) ?>", <?= isset($productCounts[$id]) ? $productCounts[$id] : 0 ?>)'>
                                🗑️
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Add New Card -->
                <div class="category-card add-category-card" onclick="openAddModal()">
                    <div class="add-category-icon">➕</div>
                    <div class="add-category-text">إضافة قسم جديد</div>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal-overlay" id="categoryModal">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title" id="modalTitle">إضافة قسم جديد</h3>
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <form method="POST" id="categoryForm">
                <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="category_id" id="categoryId" value="">
                
                <div class="modal-body">
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">اسم القسم *</label>
                        <input type="text" name="name" id="categoryName" class="form-control" required 
                               placeholder="مثال: الإكسسوارات">
                    </div>
                    
                    <div class="form-group" style="margin-bottom: 20px;">
                        <label class="form-label">الأيقونة *</label>
                        <input type="text" name="icon" id="categoryIcon" class="form-control" required
                               placeholder="مثال: 💍" style="font-size: 1.5rem; text-align: center;">
                        <div class="icon-picker">
                            <?php 
                            $icons = ['🎁', '🖨️', '✨', '⌚', '💍', '🎀', '🌹', '💎', '📿', '🧸', '🎈', '🕯️', '🖼️', '📷', '🎨', '🛍️'];
                            foreach ($icons as $icon): 
                            ?>
                            <span class="icon-option" onclick="selectIcon('<?= $icon ?>')"><?= $icon ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="form-group" id="sortOrderGroup" style="display: none;">
                        <label class="form-label">الترتيب</label>
                        <input type="number" name="sort_order" id="categorySortOrder" class="form-control" 
                               min="1" value="1">
                        <small style="color: var(--text-muted);">الأرقام الأصغر تظهر أولاً</small>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">إلغاء</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">➕ إضافة القسم</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal-overlay" id="deleteModal">
        <div class="modal" style="max-width: 400px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #F44336, #E53935); color: white;">
                <h3 class="modal-title">⚠️ تأكيد الحذف</h3>
                <button class="modal-close" onclick="closeDeleteModal()" style="color: white;">×</button>
            </div>
            <div class="modal-body" style="text-align: center; padding: 30px;">
                <div id="deleteModalContent"></div>
            </div>
            <div class="modal-footer" style="justify-content: center;">
                <button type="button" class="btn btn-outline" onclick="closeDeleteModal()">إلغاء</button>
                <form method="POST" id="deleteForm" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" id="deleteCategoryId" value="">
                    <button type="submit" class="btn" style="background: #F44336; color: white;">🗑️ حذف القسم</button>
                </form>
            </div>
        </div>
    </div>
    
    <script src="<?= av('js/admin.js') ?>"></script>
    <script>
        // Elements
        const modal = document.getElementById('categoryModal');
        const deleteModal = document.getElementById('deleteModal');
        const modalTitle = document.getElementById('modalTitle');
        const formAction = document.getElementById('formAction');
        const categoryId = document.getElementById('categoryId');
        const categoryName = document.getElementById('categoryName');
        const categoryIcon = document.getElementById('categoryIcon');
        const categorySortOrder = document.getElementById('categorySortOrder');
        const sortOrderGroup = document.getElementById('sortOrderGroup');
        const submitBtn = document.getElementById('submitBtn');
        
        // Debug check
        console.log('Category Modal Elements:', {
            modal: modal,
            categoryName: categoryName,
            categoryIcon: categoryIcon
        });
        
        function openAddModal() {
            console.log('Opening Add Modal');
            if (!modal) {
                console.error('Modal not found!');
                return;
            }
            modalTitle.textContent = 'إضافة قسم جديد';
            formAction.value = 'add';
            categoryId.value = '';
            categoryName.value = '';
            categoryIcon.value = '📦';
            sortOrderGroup.style.display = 'none';
            submitBtn.innerHTML = '➕ إضافة القسم';
            modal.classList.add('active');
            setTimeout(() => categoryName.focus(), 100);
            updateSelectedIcon('📦');
        }
        
        function openEditModal(category) {
            console.log('Opening Edit Modal for:', category);
            if (!modal) {
                console.error('Modal not found!');
                return;
            }
            modalTitle.textContent = 'تعديل القسم';
            formAction.value = 'update';
            categoryId.value = category.id;
            categoryName.value = category.name;
            categoryIcon.value = category.icon || '📦';
            categorySortOrder.value = category.sort_order || 1;
            sortOrderGroup.style.display = 'block';
            submitBtn.innerHTML = '✓ حفظ التغييرات';
            modal.classList.add('active');
            setTimeout(() => categoryName.focus(), 100);
            updateSelectedIcon(category.icon || '📦');
        }
        
        function closeModal() {
            modal.classList.remove('active');
        }
        
        function selectIcon(icon) {
            categoryIcon.value = icon;
            updateSelectedIcon(icon);
        }
        
        function updateSelectedIcon(selectedIcon) {
            document.querySelectorAll('.icon-option').forEach(el => {
                el.classList.toggle('selected', el.textContent === selectedIcon);
            });
        }
        
        // Delete Modal Functions
        function openDeleteModal(categoryId, categoryName, productCount) {
            const contentDiv = document.getElementById('deleteModalContent');
            const deleteCategoryIdInput = document.getElementById('deleteCategoryId');
            const deleteForm = document.getElementById('deleteForm');
            
            deleteCategoryIdInput.value = categoryId;
            
            if (productCount > 0) {
                // Cannot delete - has products
                contentDiv.innerHTML = `
                    <div style="font-size: 4rem; margin-bottom: 20px;">🚫</div>
                    <h3 style="color: #F44336; margin-bottom: 15px;">لا يمكن حذف هذا القسم</h3>
                    <p style="color: var(--text-muted); line-height: 1.8;">
                        القسم "<strong>${categoryName}</strong>" يحتوي على <strong style="color: #F44336;">${productCount}</strong> منتج.<br>
                        يجب نقل المنتجات إلى قسم آخر قبل الحذف.
                    </p>
                `;
                deleteForm.style.display = 'none';
            } else {
                // Can delete
                contentDiv.innerHTML = `
                    <div style="font-size: 4rem; margin-bottom: 20px;">🗑️</div>
                    <h3 style="margin-bottom: 15px;">هل أنت متأكد؟</h3>
                    <p style="color: var(--text-muted); line-height: 1.8;">
                        سيتم حذف القسم "<strong>${categoryName}</strong>" نهائياً.<br>
                        لا يمكن التراجع عن هذا الإجراء.
                    </p>
                `;
                deleteForm.style.display = 'inline';
            }
            
            deleteModal.classList.add('active');
        }
        
        function closeDeleteModal() {
            deleteModal.classList.remove('active');
        }
        
        // Legacy function for backward compatibility - now shows nice modal
        function confirmDelete(productCount) {
            // This is called from form onsubmit, we need to prevent default and show modal
            // But we'll use the new button approach instead
            return false;
        }
        
        // Close modals on overlay click
        modal?.addEventListener('click', function(e) {
            if (e.target === modal) closeModal();
        });
        
        deleteModal?.addEventListener('click', function(e) {
            if (e.target === deleteModal) closeDeleteModal();
        });
        
        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeDeleteModal();
            }
        });
        
        // Log when page loads
        console.log('Categories page loaded successfully');
    </script>
</body>
</html>
