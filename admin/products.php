<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

if (!validateAdminSession()) {
    redirect('login');
}

$categories = getCategories();
$message = '';
$error = '';
$csrf_token = generateCSRFToken();

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (validateCSRFToken($_GET['token'])) {
        $id = intval($_GET['delete']);
        $product = getProduct($id);
        
        if ($product && !empty($product['images'])) {
            foreach ($product['images'] as $img) {
                deleteImage($img);
            }
        }
        
        if (deleteProduct($id)) {
            $message = 'تم حذف المنتج بنجاح';
        } else {
            $error = 'فشل في حذف المنتج';
        }
    }
}

// Handle Status Toggle
if (isset($_POST['toggle_status'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $id = intval($_POST['product_id']);
        $newStatus = sanitize($_POST['new_status']);
        
        $product = getProduct($id);
        if ($product) {
            $product['status'] = $newStatus;
            saveProduct($product);
            $message = 'تم تحديث حالة المنتج';
        }
    }
}

// Handle Add/Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $id = intval(isset($_POST['product_id']) ? $_POST['product_id'] : 0);
        $isEdit = $id > 0;
        
        $product = $isEdit ? getProduct($id) : [];
        
        $product['id'] = $id ?: null;
        $product['name'] = sanitize($_POST['name']);
        $product['description'] = sanitize($_POST['description']);
        $product['price'] = intval($_POST['price']);
        $product['old_price'] = intval(isset($_POST['old_price']) ? $_POST['old_price'] : 0);
        $product['category'] = sanitize($_POST['category']);
        $product['status'] = sanitize($_POST['status']);
        $product['stock'] = intval(isset($_POST['stock']) ? $_POST['stock'] : 10);
        $product['customizable'] = isset($_POST['customizable']);
        $product['featured'] = isset($_POST['featured']);
        
        // ═══════════════════════════════════════════════════════════════
        // PACKAGING - التغليف (لكل منتج بشكل مستقل)
        // ═══════════════════════════════════════════════════════════════
        $product['packaging_enabled'] = isset($_POST['packaging_enabled']);
        if ($product['packaging_enabled']) {
            // التحقق من سعر التغليف - أرقام إنكليزية فقط
            $packagingPriceRaw = isset($_POST['packaging_price']) ? $_POST['packaging_price'] : '';
            // إزالة أي حروف غير رقمية
            $packagingPriceClean = preg_replace('/[^0-9]/', '', $packagingPriceRaw);
            
            if (empty($packagingPriceClean) || !is_numeric($packagingPriceClean) || intval($packagingPriceClean) <= 0) {
                $error = 'التغليف مفعّل لكن سعر التغليف غير صالح. يرجى إدخال رقم صحيح بالأرقام الإنكليزية فقط.';
            } else {
                $product['packaging_price'] = intval($packagingPriceClean);
            }
            // وصف التغليف (اختياري)
            $product['packaging_description'] = sanitize(isset($_POST['packaging_description']) ? $_POST['packaging_description'] : '');
        } else {
            $product['packaging_price'] = 0;
            $product['packaging_description'] = '';
        }
        
        // ═══════════════════════════════════════
        // PRODUCT OPTIONS HANDLING - نظام المجموعات الجديد
        // ═══════════════════════════════════════
        $options = [];
        
        // === COLORS - نظام المجموعات مع الكميات ===
        if (isset($_POST['options_colors_enabled'])) {
            $colorsGroups = [];
            $colorsValues = []; // للتوافق الخلفي
            
            // قراءة المجموعات من الفورم
            if (!empty($_POST['colors_groups']) && is_array($_POST['colors_groups'])) {
                foreach ($_POST['colors_groups'] as $groupData) {
                    $groupLabel = sanitize(isset($groupData['label']) ? $groupData['label'] : 'اللون');
                    $groupItems = [];
                    if (!empty($groupData['items']) && is_array($groupData['items'])) {
                        foreach ($groupData['items'] as $item) {
                            $itemName = sanitize(isset($item['name']) ? $item['name'] : '');
                            $itemQty = intval(isset($item['qty']) ? $item['qty'] : 999);
                            if (!empty($itemName)) {
                                $groupItems[] = ['name' => $itemName, 'qty' => $itemQty];
                                $colorsValues[] = $itemName; // للتوافق الخلفي
                            }
                        }
                    }
                    if (!empty($groupLabel) || !empty($groupItems)) {
                        $colorsGroups[] = ['label' => $groupLabel, 'items' => $groupItems];
                    }
                }
            }
            
            $options['colors'] = [
                'enabled' => true,
                'required' => isset($_POST['options_colors_required']),
                'groups' => $colorsGroups,
                'values' => array_unique($colorsValues) // للتوافق الخلفي
            ];
        }
        
        // === SIZES - نظام المجموعات مع الكميات ===
        if (isset($_POST['options_sizes_enabled'])) {
            $sizesGroups = [];
            $sizesValues = []; // للتوافق الخلفي
            
            if (!empty($_POST['sizes_groups']) && is_array($_POST['sizes_groups'])) {
                foreach ($_POST['sizes_groups'] as $groupData) {
                    $groupLabel = sanitize(isset($groupData['label']) ? $groupData['label'] : 'الحجم');
                    $groupItems = [];
                    if (!empty($groupData['items']) && is_array($groupData['items'])) {
                        foreach ($groupData['items'] as $item) {
                            $itemName = sanitize(isset($item['name']) ? $item['name'] : '');
                            $itemQty = intval(isset($item['qty']) ? $item['qty'] : 999);
                            if (!empty($itemName)) {
                                $groupItems[] = ['name' => $itemName, 'qty' => $itemQty];
                                $sizesValues[] = $itemName;
                            }
                        }
                    }
                    if (!empty($groupLabel) || !empty($groupItems)) {
                        $sizesGroups[] = ['label' => $groupLabel, 'items' => $groupItems];
                    }
                }
            }
            
            $options['sizes'] = [
                'enabled' => true,
                'required' => isset($_POST['options_sizes_required']),
                'groups' => $sizesGroups,
                'values' => array_unique($sizesValues)
            ];
        }
        
        
        // === EXTRA FIELDS - الحقول الإضافية الموحدة (تدمج النص والاختيارات) ===
        if (isset($_POST['options_extra_fields_enabled'])) {
            $extraFieldsGroups = [];
            
            if (!empty($_POST['extra_fields_groups']) && is_array($_POST['extra_fields_groups'])) {
                foreach ($_POST['extra_fields_groups'] as $groupData) {
                    $groupLabel = sanitize(isset($groupData['label']) ? $groupData['label'] : '');
                    $groupItems = [];
                    
                    if (!empty($groupData['items']) && is_array($groupData['items'])) {
                        foreach ($groupData['items'] as $item) {
                            $itemType = isset($item['type']) ? $item['type'] : 'text';
                            $itemData = [
                                'type' => $itemType,
                                'label' => sanitize(isset($item['label']) ? $item['label'] : ''),
                                'required' => !empty($item['required'])
                            ];
                            
                            if ($itemType === 'text') {
                                $itemData['max_length'] = intval(isset($item['max_length']) ? $item['max_length'] : 50);
                                $itemData['placeholder'] = sanitize(isset($item['placeholder']) ? $item['placeholder'] : '');
                            } else {
                                // select - تحويل الخيارات من نص إلى مصفوفة
                                $optionsText = isset($item['options']) ? $item['options'] : '';
                                $optionsArray = array_filter(array_map('trim', preg_split('/[\|\n]/', $optionsText)));
                                $itemData['options'] = array_values($optionsArray);
                            }
                            
                            if (!empty($itemData['label'])) {
                                $groupItems[] = $itemData;
                            }
                        }
                    }
                    
                    if (!empty($groupLabel) || !empty($groupItems)) {
                        $extraFieldsGroups[] = ['label' => $groupLabel, 'items' => $groupItems];
                    }
                }
            }
            
            $options['extra_fields'] = [
                'enabled' => true,
                'required' => isset($_POST['options_extra_fields_required']),
                'groups' => $extraFieldsGroups
            ];
        }
        
        // === GIFT CARD - بطاقة إهداء (Multi-Card System) ===
        if (isset($_POST['options_gift_card_enabled'])) {
            $giftCards = [];
            if (!empty($_POST['gift_cards_data']) && is_array($_POST['gift_cards_data'])) {
                foreach ($_POST['gift_cards_data'] as $card) {
                    $label = sanitize(isset($card['label']) ? $card['label'] : '');
                    if (!empty($label)) {
                        $giftCards[] = [
                            'id' => sanitize(isset($card['id']) ? $card['id'] : uniqid('card_')),
                            'label' => $label,
                            'helper' => sanitize(isset($card['helper']) ? $card['helper'] : ''),
                            'required' => !empty($card['required']),
                            'max_length' => intval(isset($card['max_length']) ? $card['max_length'] : 250),
                            'placeholder' => sanitize(isset($card['placeholder']) ? $card['placeholder'] : '')
                        ];
                    }
                }
            }
            
            // Fallback for single legacy field or if no cards were dynamically added
            if (empty($giftCards)) {
                $giftCards[] = [
                    'id' => uniqid('card_'),
                    'label' => sanitize(isset($_POST['options_gift_card_label']) ? $_POST['options_gift_card_label'] : 'رسالة البطاقة'),
                    'helper' => sanitize(isset($_POST['options_gift_card_helper']) ? $_POST['options_gift_card_helper'] : 'اكتب رسالتك التي تريدها داخل البطاقة'),
                    'required' => isset($_POST['options_gift_card_required']),
                    'max_length' => intval(isset($_POST['options_gift_card_max_length']) ? $_POST['options_gift_card_max_length'] : 250),
                    'placeholder' => sanitize(isset($_POST['options_gift_card_placeholder']) ? $_POST['options_gift_card_placeholder'] : 'مثال: كل عام وأنتِ بخير...')
                ];
            }
            
            $options['gift_cards'] = $giftCards;
            $options['gift_card_enabled'] = true;
        }
        
        // === CUSTOM TEXT ===
        if (isset($_POST['options_custom_text_enabled'])) {
            $options['custom_text'] = [
                'enabled' => true,
                'label' => sanitize(isset($_POST['options_custom_text_label']) ? $_POST['options_custom_text_label'] : 'نص مخصص'),
                'placeholder' => sanitize(isset($_POST['options_custom_text_placeholder']) ? $_POST['options_custom_text_placeholder'] : ''),
                'required' => isset($_POST['options_custom_text_required']),
                'max_length' => intval(isset($_POST['options_custom_text_max_length']) ? $_POST['options_custom_text_max_length'] : 50)
            ];
        }
        
        // === CUSTOM IMAGES - مع مجموعات الصور ===
        if (isset($_POST['options_custom_images_enabled'])) {
            $allowedTypes = [];
            if (!empty($_POST['options_custom_images_types']) && is_array($_POST['options_custom_images_types'])) {
                $allowedTypes = array_map('sanitize', $_POST['options_custom_images_types']);
            }
            if (empty($allowedTypes)) {
                $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];
            }
            
            // معالجة مجموعات الصور
            $imagesGroups = [];
            if (!empty($_POST['custom_images_groups']) && is_array($_POST['custom_images_groups'])) {
                foreach ($_POST['custom_images_groups'] as $groupData) {
                    $imagesGroups[] = [
                        'label' => sanitize(isset($groupData['label']) ? $groupData['label'] : ''),
                        'min' => max(0, intval(isset($groupData['min']) ? $groupData['min'] : 1)),
                        'max' => max(1, intval(isset($groupData['max']) ? $groupData['max'] : 3)),
                        'max_size' => max(1, intval(isset($groupData['max_size']) ? $groupData['max_size'] : 5)),
                        'required' => !empty($groupData['required'])
                    ];
                }
            }
            
            $options['custom_images'] = [
                'enabled' => true,
                'required' => isset($_POST['options_custom_images_required']),
                'groups' => $imagesGroups,
                'allowed_types' => $allowedTypes,
                // للتوافق الخلفي
                'label' => !empty($imagesGroups[0]['label']) ? $imagesGroups[0]['label'] : 'ارفع صورك للطباعة',
                'min_images' => !empty($imagesGroups[0]['min']) ? $imagesGroups[0]['min'] : 1,
                'max_images' => !empty($imagesGroups[0]['max']) ? $imagesGroups[0]['max'] : 5,
                'max_size_mb' => !empty($imagesGroups[0]['max_size']) ? $imagesGroups[0]['max_size'] : 5
            ];

            // ⛔ شرط صارم: لا يمكن تفعيل رفع صور التخصيص إلا إذا كان المنتج قابل للتخصيص (الطباعة)
            if (!$product['customizable']) {
                $options['custom_images']['enabled'] = false;
            }
        }

        // === BOX OPTIONS - خيارات العلب/الصناديق ===
        if (isset($_POST['options_box_enabled'])) {
            $boxItems = [];
            if (!empty($_POST['box_options_data']) && is_array($_POST['box_options_data'])) {
                foreach ($_POST['box_options_data'] as $box) {
                    $name = sanitize(isset($box['name']) ? $box['name'] : '');
                    if (!empty($name)) {
                        $boxItems[] = [
                            'name' => $name,
                            'size' => sanitize(isset($box['size']) ? $box['size'] : ''),
                            'description' => sanitize(isset($box['description']) ? $box['description'] : ''),
                            'price' => intval(isset($box['price']) ? $box['price'] : 0),
                            'stock' => intval(isset($box['stock']) ? $box['stock'] : 999)
                        ];
                    }
                }
            }
            $options['box_options'] = [
                'enabled' => true,
                'mandatory' => isset($_POST['options_box_mandatory']),
                'items' => $boxItems
            ];
        }
        
        $product['options'] = $options;
        
        // Handle deleted images (marked for deletion)
        $existingImages = isset($product['images']) ? $product['images'] : [];
        $deletedIndexes = [];
        
        if (!empty($_POST['deleted_images'])) {
            $deletedIndexes = array_map('intval', explode(',', $_POST['deleted_images']));
            rsort($deletedIndexes); // Sort descending to delete from end first
            
            foreach ($deletedIndexes as $index) {
                if (isset($existingImages[$index])) {
                    // Delete the actual file
                    deleteImage($existingImages[$index]);
                    // Remove from array
                    array_splice($existingImages, $index, 1);
                }
            }
            $product['images'] = $existingImages;
        }
        
        // Handle new image uploads
        $newImages = [];
        
        if (!empty($_FILES['images']['name'][0])) {
            // Count uploaded files
            $uploadCount = count(array_filter($_FILES['images']['name']));
            $totalImages = count($existingImages) + $uploadCount;
            
            // Check max 4 images
            if ($totalImages > 4) {
                $error = 'الحد الأقصى 4 صور للمنتج الواحد';
            } else {
                foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                    if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['images']['name'][$key],
                            'tmp_name' => $tmp_name,
                            'error' => $_FILES['images']['error'][$key],
                            'size' => $_FILES['images']['size'][$key]
                        ];
                        $result = uploadImage($file, 'products');
                        if ($result['success']) {
                            $newImages[] = 'products/' . $result['filename'];
                        } else {
                            $error = $result['error'];
                        }
                    }
                }
                
                if (!empty($newImages)) {
                    $product['images'] = array_merge($existingImages, $newImages);
                }
            }
        }
        
        // Check minimum 1 image for new products
        if (!$isEdit && empty($product['images'])) {
            $error = 'يجب إضافة صورة واحدة على الأقل للمنتج';
        }
        
        if (empty($error)) {
            if (saveProduct($product)) {
                $message = $isEdit ? 'تم تحديث المنتج بنجاح' : 'تم إضافة المنتج بنجاح';
            } else {
                $error = 'فشل في حفظ المنتج';
            }
        }
    }
}

// Mode: list, add, edit
$mode = 'list';
$editProduct = null;

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'add') {
        $mode = 'add';
    } elseif ($_GET['action'] === 'edit' && isset($_GET['id'])) {
        $mode = 'edit';
        $editProduct = getProduct(intval($_GET['id']));
        if (!$editProduct) {
            redirect('products');
        }
    }
}

// Get products with filters
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

$filters = [
    'search' => $searchQuery,
    'category' => $categoryFilter,
    'status' => $statusFilter
];
$products = getProducts($filters);
$totalProducts = count(getProducts());
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المنتجات - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        /* Stock Management Styles */
        .stock-control, .stock-control-mobile {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        
        .stock-control-mobile {
            background: rgba(201, 164, 73, 0.1);
            padding: 8px 12px;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        
        .stock-label {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-left: 8px;
        }
        
        .stock-btn {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: bold;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stock-btn.stock-minus {
            background: linear-gradient(135deg, #ff5722, #ff9800);
            color: white;
        }
        
        .stock-btn.stock-plus {
            background: linear-gradient(135deg, #4CAF50, #8BC34A);
            color: white;
        }
        
        .stock-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        
        .stock-btn:active {
            transform: scale(0.95);
        }
        
        .stock-value {
            min-width: 35px;
            text-align: center;
            font-weight: 700;
            font-size: 1rem;
            color: var(--primary);
            background: rgba(201, 164, 73, 0.15);
            padding: 4px 10px;
            border-radius: 6px;
        }
        
        .stock-value.low-stock {
            color: #ff5722;
            background: rgba(255, 87, 34, 0.15);
        }
        
        .stock-value.out-of-stock {
            color: #f44336;
            background: rgba(244, 67, 54, 0.15);
        }
        
        .stock-updating {
            opacity: 0.6;
            pointer-events: none;
        }
        
        /* Products Page - Mobile Responsive Styles */
        .products-header {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .products-header h1 {
            margin: 0;
        }
        
        .products-header p {
            color: var(--text-muted);
            margin: 5px 0 0 0;
        }
        
        .filter-bar {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            background: white;
            border-radius: var(--radius-lg);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
            min-width: 140px;
        }
        
        .filter-group.search {
            flex: 2;
            min-width: 200px;
        }
        
        .filter-label {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .premium-select {
            padding: 12px 18px;
            border-radius: var(--radius-lg);
            border: 2px solid rgba(233, 30, 140, 0.15);
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23E91E8C' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 12px center;
            padding-left: 35px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .premium-select:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(233, 30, 140, 0.15);
        }
        
        .premium-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(233, 30, 140, 0.1);
        }
        
        .premium-input {
            padding: 12px 18px;
            border-radius: var(--radius-lg);
            border: 2px solid rgba(233, 30, 140, 0.15);
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .premium-input:hover {
            border-color: var(--primary);
        }
        
        .premium-input:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(233, 30, 140, 0.1);
        }
        
        .clear-btn {
            padding: 12px 20px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            border: 2px solid transparent;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .clear-btn:hover {
            background: linear-gradient(135deg, #ffe0f0 0%, #ffd0e8 100%);
            border-color: var(--primary);
        }
        
        /* Mobile Cards for Products */
        .product-cards-mobile {
            display: none;
        }
        
        .product-card-mobile {
            background: white;
            border-radius: var(--radius-lg);
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: var(--shadow-sm);
            border-right: 4px solid var(--primary);
        }
        
        .product-card-header {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 12px;
        }
        
        .product-card-header img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-md);
        }
        
        .product-card-info {
            flex: 1;
        }
        
        .product-card-info h4 {
            margin: 0 0 5px 0;
            font-size: 1rem;
        }
        
        .product-card-info .category {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .product-card-body {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 12px;
            border-top: 1px dashed #eee;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .product-card-price {
            font-weight: 800;
            color: var(--primary);
            font-size: 1.1rem;
        }
        
        .product-card-actions {
            display: flex;
            gap: 8px;
        }
        
        @media (max-width: 768px) {
            .products-header {
                text-align: center;
            }
            
            .filter-bar {
                flex-direction: column;
                padding: 15px;
            }
            
            .filter-group {
                width: 100%;
                min-width: auto;
            }
            
            .filter-group.search {
                flex: none;
                min-width: auto;
            }
            
            /* Hide desktop table, show mobile cards */
            .admin-table-wrapper {
                display: none;
            }
            
            .product-cards-mobile {
                display: block;
            }
            
            .admin-title {
                font-size: 1.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($mode === 'list'): ?>
            <!-- Products List -->
            <div class="products-header">
                <div>
                    <h1 class="admin-title">📦 إدارة المنتجات (<?= $totalProducts ?>)</h1>
                    <p>إدارة وتعديل منتجات المتجر</p>
                </div>
                <div>
                    <a href="products?action=add" class="btn btn-primary">➕ إضافة منتج جديد</a>
                </div>
            </div>
            
            <!-- Search & Filters -->
            <div class="filter-bar">
                <div class="filter-group search">
                    <span class="filter-label">🔍 بحث فوري</span>
                    <input type="text" id="productSearch" class="premium-input" placeholder="ابحث بالاسم أو الوصف..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
                <div class="filter-group">
                    <span class="filter-label">📂 القسم</span>
                    <select id="productCategory" class="premium-select">
                        <option value="">الكل</option>
                        <?php foreach ($categories as $key => $cat): ?>
                        <option value="<?= $key ?>" <?= $categoryFilter === $key ? 'selected' : '' ?>><?= $cat['icon'] ?> <?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <span class="filter-label">📊 الحالة</span>
                    <select id="productStatus" class="premium-select">
                        <option value="">الكل</option>
                        <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>✓ متوفر</option>
                        <option value="sold" <?= $statusFilter === 'sold' ? 'selected' : '' ?>>✗ نفذ</option>
                        <option value="hidden" <?= $statusFilter === 'hidden' ? 'selected' : '' ?>>👁 مخفي</option>
                    </select>
                </div>
                <div class="filter-group" style="flex: 0;">
                    <span class="filter-label">&nbsp;</span>
                    <button type="button" id="clearProductFilters" class="clear-btn">🗑️ مسح</button>
                </div>
            </div>
            <div id="productSearchResults" style="margin-bottom: 15px; font-size: 0.9rem; color: var(--text-muted);"></div>
            
            <!-- Mobile Cards View -->
            <div class="product-cards-mobile">
                <?php foreach ($products as $product): ?>
                <div class="product-card-mobile">
                    <div class="product-card-header">
                        <img src="../images/<?= isset($product['images'][0]) ? $product['images'][0] : 'logo.jpg' ?>" alt="" onerror="this.src='../images/logo.jpg'">
                        <div class="product-card-info">
                            <h4><?= $product['name'] ?></h4>
                            <span class="category"><?= isset($categories[$product['category']]['icon']) ? $categories[$product['category']]['icon'] : '' ?> <?= isset($categories[$product['category']]['name']) ? $categories[$product['category']]['name'] : $product['category'] ?></span>
                            <?php if ($product['customizable']): ?>
                            <br><small style="color: var(--primary);">🎨 قابل للتخصيص</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="product-card-body">
                        <div class="stock-control-mobile" data-product-id="<?= $product['id'] ?>">
                            <span class="stock-label">📦 المخزون:</span>
                            <button type="button" class="stock-btn stock-minus">−</button>
                            <span class="stock-value" id="stock-mobile-<?= $product['id'] ?>"><?= isset($product['stock']) ? intval($product['stock']) : 0 ?></span>
                            <button type="button" class="stock-btn stock-plus">+</button>
                        </div>
                        <span class="product-card-price"><?= formatPriceWithDiscount($product['price'], isset($product['old_price']) ? $product['old_price'] : 0) ?></span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="toggle_status" value="1">
                            <select name="new_status" onchange="this.form.submit()" class="form-control" style="width: auto; padding: 5px 10px; font-size: 0.85rem;">
                                <option value="available" <?= $product['status'] === 'available' ? 'selected' : '' ?>>✓ متوفر</option>
                                <option value="sold" <?= $product['status'] === 'sold' ? 'selected' : '' ?>>✗ نفذ</option>
                                <option value="hidden" <?= $product['status'] === 'hidden' ? 'selected' : '' ?>>👁 مخفي</option>
                            </select>
                        </form>
                        <div class="product-card-actions">
                            <a href="products?action=edit&id=<?= $product['id'] ?>" class="action-btn action-btn-edit" title="تعديل">✏️</a>
                            <button type="button" class="action-btn action-btn-delete" 
                               title="حذف"
                               data-delete-type="product"
                               data-delete-id="<?= $product['id'] ?>"
                               data-delete-name="<?= htmlspecialchars($product['name']) ?>"
                               data-delete-token="<?= $csrf_token ?>">🗑️</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (empty($products)): ?>
                <div class="empty-state" style="background: white; border-radius: var(--radius-lg); padding: 40px;">
                    <div class="empty-icon">📦</div>
                    <h2 class="empty-title">لا توجد منتجات</h2>
                    <a href="products?action=add" class="btn btn-primary">إضافة منتج جديد</a>
                </div>
                <?php endif; ?>
            </div>


            <div class="admin-card">
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th>الاسم</th>
                                <th>القسم</th>
                                <th>السعر</th>
                                <th>المخزون</th>
                                <th>الحالة</th>
                                <th>مميز</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>
                                    <img src="../images/<?= isset($product['images'][0]) ? $product['images'][0] : 'logo.jpg' ?>" alt="" onerror="this.src='../images/logo.jpg'">
                                </td>
                                <td>
                                    <strong><?= $product['name'] ?></strong>
                                    <?php if ($product['customizable']): ?>
                                    <br><small style="color: var(--primary);">قابل للتخصيص</small>
                                    <?php endif; ?>
                                </td>
                                <td><?= isset($categories[$product['category']]['icon']) ? $categories[$product['category']]['icon'] : '' ?> <?= isset($categories[$product['category']]['name']) ? $categories[$product['category']]['name'] : $product['category'] ?></td>
                                <td><?= formatPriceWithDiscount($product['price'], isset($product['old_price']) ? $product['old_price'] : 0) ?></td>
                                <td>
                                    <div class="stock-control" data-product-id="<?= $product['id'] ?>">
                                        <button type="button" class="stock-btn stock-minus" title="-1">−</button>
                                        <span class="stock-value" id="stock-<?= $product['id'] ?>"><?= isset($product['stock']) ? intval($product['stock']) : 0 ?></span>
                                        <button type="button" class="stock-btn stock-plus" title="+1">+</button>
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                        <input type="hidden" name="toggle_status" value="1">
                                        <select name="new_status" onchange="this.form.submit()" class="form-control" style="width: auto; padding: 5px 10px;">
                                            <option value="available" <?= $product['status'] === 'available' ? 'selected' : '' ?>>✓ متوفر</option>
                                            <option value="sold" <?= $product['status'] === 'sold' ? 'selected' : '' ?>>✗ نفذ</option>
                                            <option value="hidden" <?= $product['status'] === 'hidden' ? 'selected' : '' ?>>👁 مخفي</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?= $product['featured'] ? '⭐' : '-' ?></td>
                                <td>
                                    <div class="action-btns">
                                        <a href="products?action=edit&id=<?= $product['id'] ?>" class="action-btn action-btn-edit" title="تعديل">✏️</a>
                                        <button type="button" class="action-btn action-btn-delete" 
                                           title="حذف"
                                           data-delete-type="product"
                                           data-delete-id="<?= $product['id'] ?>"
                                           data-delete-name="<?= htmlspecialchars($product['name']) ?>"
                                           data-delete-token="<?= $csrf_token ?>">🗑️</button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if (empty($products)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📦</div>
                    <h2 class="empty-title">لا توجد منتجات</h2>
                    <a href="products?action=add" class="btn btn-primary">إضافة منتج جديد</a>
                </div>
                <?php endif; ?>
            </div>

            <?php else: ?>
            <!-- Add/Edit Form -->
            <div class="admin-header">
                <h1 class="admin-title"><?= $mode === 'add' ? '➕ إضافة منتج جديد' : '✏️ تعديل المنتج' ?></h1>
                <a href="products" class="btn btn-outline">← العودة للقائمة</a>
            </div>

            <div class="admin-card">
                <div style="padding: 30px;">
                    <form method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <input type="hidden" name="save_product" value="1">
                        <?php if ($editProduct): ?>
                        <input type="hidden" name="product_id" value="<?= $editProduct['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label class="form-label">اسم المنتج *</label>
                            <input type="text" name="name" class="form-control" required value="<?= isset($editProduct['name']) ? $editProduct['name'] : '' ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">الوصف *</label>
                            <textarea name="description" class="form-control" required rows="4"><?= isset($editProduct['description']) ? $editProduct['description'] : '' ?></textarea>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">💰 السعر الحالي (بالدينار العراقي) *</label>
                                <input type="number" name="price" class="form-control" required min="0" value="<?= isset($editProduct['price']) ? $editProduct['price'] : '' ?>" id="currentPrice">
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                                    السعر الفعلي الذي يدفعه الزبون
                                </p>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">🏷️ السعر القديم (قبل التخفيض)</label>
                                <input type="number" name="old_price" class="form-control" min="0" value="<?= isset($editProduct['old_price']) ? $editProduct['old_price'] : 0 ?>" id="oldPrice" placeholder="اتركه 0 إذا لا يوجد تخفيض">
                                <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 5px;">
                                    سيظهر مشطوبًا بجانب السعر الجديد
                                </p>
                            </div>
                        </div>
                        
                        <!-- Discount Preview -->
                        <div id="discountPreview" style="background: linear-gradient(135deg, #fff3e0, #ffe0b2); border-radius: var(--radius-md); padding: 15px; margin-bottom: 20px; display: none; border-right: 4px solid #ff9800;">
                            <div style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                                <span style="font-weight: 700; color: #e65100;">🔖 معاينة التخفيض:</span>
                                <span style="text-decoration: line-through; color: #999; font-size: 1.1rem;" id="previewOldPrice">0</span>
                                <span style="color: var(--primary); font-weight: 800; font-size: 1.3rem;" id="previewNewPrice">0</span>
                                <span style="background: #ff5722; color: white; padding: 4px 12px; border-radius: 20px; font-weight: 700; font-size: 0.85rem;" id="previewDiscount">-0%</span>
                            </div>
                        </div>
                        
                        <!-- Stock Management -->
                        <div class="form-group" style="background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(139, 195, 74, 0.1)); padding: 20px; border-radius: var(--radius-md); border-right: 4px solid #4CAF50; margin-bottom: 20px;">
                            <label class="form-label" style="color: #2e7d32;">📦 المخزون (عدد القطع المتوفرة)</label>
                            <input type="number" name="stock" class="form-control" min="0" value="<?= isset($editProduct['stock']) ? intval($editProduct['stock']) : 10 ?>" style="max-width: 200px;">
                            <p style="font-size: 0.8rem; color: #558b2f; margin-top: 8px;">
                                عدد القطع المتوفرة حالياً. عند وصول المخزون إلى 0 سيتغير حالة المنتج تلقائياً إلى "نفذ".
                            </p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                            <div class="form-group">
                                <label class="form-label">📂 القسم *</label>
                                <select name="category" class="form-control" required>
                                    <?php foreach ($categories as $key => $cat): ?>
                                    <option value="<?= $key ?>" <?= (isset($editProduct['category']) ? $editProduct['category'] : '') === $key ? 'selected' : '' ?>>
                                        <?= $cat['icon'] ?> <?= $cat['name'] ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        
                            <div class="form-group">
                                <label class="form-label">📊 حالة المنتج</label>
                                <select name="status" class="form-control">
                                    <option value="available" <?= (isset($editProduct['status']) ? $editProduct['status'] : '') === 'available' ? 'selected' : '' ?>>✓ متوفر</option>
                                    <option value="sold" <?= (isset($editProduct['status']) ? $editProduct['status'] : '') === 'sold' ? 'selected' : '' ?>>✗ نفذت الكمية</option>
                                    <option value="hidden" <?= (isset($editProduct['status']) ? $editProduct['status'] : '') === 'hidden' ? 'selected' : '' ?>>👁 مخفي</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: flex; gap: 30px; margin-bottom: 20px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="customizable" id="customizableCheckbox" onchange="handleCustomizableChange(this)" <?= (isset($editProduct['customizable']) ? $editProduct['customizable'] : false) ? 'checked' : '' ?>>
                                <span>🎨 قابل للتخصيص (الطباعة)</span>
                            </label>
                            
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="checkbox" name="featured" <?= (isset($editProduct['featured']) ? $editProduct['featured'] : false) ? 'checked' : '' ?>>
                                <span>⭐ منتج مميز</span>
                            </label>
                        </div>
                        
                        <!-- ═══════════════════════════════════════════════════════════════════
                             PACKAGING SECTION - قسم التغليف (لكل منتج)
                             ═══════════════════════════════════════════════════════════════════ -->
                        <?php 
                        $packagingEnabled = !empty($editProduct['packaging_enabled']);
                        $packagingPrice = isset($editProduct['packaging_price']) ? intval($editProduct['packaging_price']) : 0;
                        $packagingDescription = isset($editProduct['packaging_description']) ? $editProduct['packaging_description'] : '';
                        ?>
                        <div id="packagingOptionBlock" class="form-group" style="background: linear-gradient(135deg, rgba(156, 39, 176, 0.08), rgba(233, 30, 99, 0.08)); padding: 20px; border-radius: var(--radius-md); border: 2px solid <?= $packagingEnabled ? '#9c27b0' : '#e0e0e0' ?>; margin-bottom: 20px; transition: all 0.3s ease;">
                            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
                                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 700; color: #7b1fa2;">
                                    <input type="checkbox" name="packaging_enabled" id="packagingEnabled" 
                                           onchange="togglePackagingSection(this)" 
                                           <?= $packagingEnabled ? 'checked' : '' ?>
                                           style="width: 20px; height: 20px; accent-color: #9c27b0;">
                                    <span style="font-size: 1.1rem;">🎁 تفعيل التغليف لهذا المنتج</span>
                                </label>
                            </div>
                            
                            <div id="packagingDetails" style="display: <?= $packagingEnabled ? 'block' : 'none' ?>; padding: 15px; background: white; border-radius: 10px; border-right: 3px solid #9c27b0;">
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label class="form-label" style="color: #7b1fa2; font-weight: 600;">
                                        💰 سعر التغليف (IQD) <span style="color: #f44336;">*</span>
                                    </label>
                                    <input type="text" 
                                           name="packaging_price" 
                                           id="packagingPrice"
                                           class="form-control" 
                                           value="<?= $packagingPrice > 0 ? $packagingPrice : '' ?>"
                                           placeholder="مثال: 5000"
                                           pattern="[0-9]*"
                                           inputmode="numeric"
                                           onkeypress="return /[0-9]/.test(event.key)"
                                           oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                           style="max-width: 200px; font-size: 1.1rem; font-weight: 600;">
                                    <p style="font-size: 0.8rem; color: #888; margin-top: 5px;">
                                        ⚠️ أرقام إنكليزية فقط (إجباري إذا التغليف مفعّل)
                                    </p>
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label class="form-label" style="color: #7b1fa2; font-weight: 600;">
                                        📝 وصف التغليف <span style="color: #888; font-weight: normal;">(اختياري)</span>
                                    </label>
                                    <input type="text" 
                                           name="packaging_description" 
                                           class="form-control" 
                                           value="<?= htmlspecialchars($packagingDescription) ?>"
                                           placeholder="مثال: تغليف هدية فاخر مع كارت | تغليف خاص للمجوهرات"
                                           maxlength="150"
                                           style="font-size: 0.95rem;">
                                    <p style="font-size: 0.8rem; color: #888; margin-top: 5px;">
                                        💡 يظهر للزبون كتوضيح عن نوع التغليف (اتركه فارغاً إذا لا تحتاجه)
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- ═══════════════════════════════════════════════════════════════════
                             PRODUCT OPTIONS SECTION - خيارات المنتج (اختياري)
                             ═══════════════════════════════════════════════════════════════════ -->
                        <?php 
                        // ═══════════════════════════════════════════════════════════════════
                        // PRODUCT OPTIONS - دعم نظام المجموعات الجديد مع التوافق الخلفي
                        // ═══════════════════════════════════════════════════════════════════
                        $productOptions = isset($editProduct['options']) ? $editProduct['options'] : [];
                        
                        // === COLORS ===
                        $colorsEnabled = !empty($productOptions['colors']['enabled']);
                        $colorsRequired = !empty($productOptions['colors']['required']);
                        // دعم المجموعات الجديدة مع التوافق الخلفي
                        $colorsGroups = [];
                        if (!empty($productOptions['colors']['groups'])) {
                            $colorsGroups = $productOptions['colors']['groups'];
                        } elseif (!empty($productOptions['colors']['values'])) {
                            // تحويل النظام القديم لمجموعة افتراضية
                            $colorsGroups = [[
                                'label' => 'اللون',
                                'items' => array_map(function($c) { return ['name' => $c, 'qty' => 999]; }, $productOptions['colors']['values'])
                            ]];
                        }
                        
                        // === SIZES ===
                        $sizesEnabled = !empty($productOptions['sizes']['enabled']);
                        $sizesRequired = !empty($productOptions['sizes']['required']);
                        $sizesGroups = [];
                        if (!empty($productOptions['sizes']['groups'])) {
                            $sizesGroups = $productOptions['sizes']['groups'];
                        } elseif (!empty($productOptions['sizes']['values'])) {
                            $sizesGroups = [[
                                'label' => 'الحجم',
                                'items' => array_map(function($s) { return ['name' => $s, 'qty' => 999]; }, $productOptions['sizes']['values'])
                            ]];
                        }
                        
                        // === AGES ===
                        $agesEnabled = !empty($productOptions['ages']['enabled']);
                        $agesRequired = !empty($productOptions['ages']['required']);
                        $agesGroups = [];
                        if (!empty($productOptions['ages']['groups'])) {
                            $agesGroups = $productOptions['ages']['groups'];
                        } elseif (!empty($productOptions['ages']['values'])) {
                            $agesGroups = [[
                                'label' => 'الفئة العمرية',
                                'items' => $productOptions['ages']['values']
                            ]];
                        }
                        
                        // === CUSTOM TEXT ===
                        $customTextEnabled = !empty($productOptions['custom_text']['enabled']);
                        $customTextRequired = !empty($productOptions['custom_text']['required']);

                        // === BOX OPTIONS ===
                        $boxOptionsEnabled = !empty($productOptions['box_options']['enabled']);
                        $boxOptionsMandatory = !empty($productOptions['box_options']['mandatory']);
                        $boxOptionsItems = isset($productOptions['box_options']['items']) ? $productOptions['box_options']['items'] : [];
                        $customTextGroups = [];
                        if (!empty($productOptions['custom_text']['groups'])) {
                            $customTextGroups = $productOptions['custom_text']['groups'];
                        } elseif ($customTextEnabled) {
                            $customTextGroups = [[
                                'label' => isset($productOptions['custom_text']['label']) ? $productOptions['custom_text']['label'] : 'نص مخصص',
                                'placeholder' => isset($productOptions['custom_text']['placeholder']) ? $productOptions['custom_text']['placeholder'] : '',
                                'max_length' => isset($productOptions['custom_text']['max_length']) ? intval($productOptions['custom_text']['max_length']) : 50,
                                'required' => !empty($productOptions['custom_text']['required'])
                            ]];
                        }
                        
                        // === CUSTOM IMAGES ===
                        $customImagesEnabled = !empty($productOptions['custom_images']['enabled']);
                        $customImagesRequired = !empty($productOptions['custom_images']['required']);
                        $customImagesLabel = isset($productOptions['custom_images']['label']) ? $productOptions['custom_images']['label'] : 'ارفع صورك للطباعة';
                        $customImagesMin = isset($productOptions['custom_images']['min_images']) ? intval($productOptions['custom_images']['min_images']) : 1;
                        $customImagesMax = isset($productOptions['custom_images']['max_images']) ? intval($productOptions['custom_images']['max_images']) : 5;
                        $customImagesMaxSize = isset($productOptions['custom_images']['max_size_mb']) ? intval($productOptions['custom_images']['max_size_mb']) : 5;
                        $customImagesAllowedTypes = isset($productOptions['custom_images']['allowed_types']) ? $productOptions['custom_images']['allowed_types'] : ['jpg', 'jpeg', 'png', 'webp'];
                        
                        // للتوافق مع القيم القديمة
                        $colorsValues = isset($productOptions['colors']['values']) ? $productOptions['colors']['values'] : [];
                        $sizesValues = isset($productOptions['sizes']['values']) ? $productOptions['sizes']['values'] : [];
                        $agesValues = isset($productOptions['ages']['values']) ? $productOptions['ages']['values'] : [];
                        $customTextLabel = isset($productOptions['custom_text']['label']) ? $productOptions['custom_text']['label'] : 'نص مخصص';
                        $customTextPlaceholder = isset($productOptions['custom_text']['placeholder']) ? $productOptions['custom_text']['placeholder'] : '';
                        $customTextMaxLength = isset($productOptions['custom_text']['max_length']) ? intval($productOptions['custom_text']['max_length']) : 50;
                        ?>
                        
                        <div class="form-group product-options-section" style="background: linear-gradient(135deg, rgba(103, 58, 183, 0.08), rgba(156, 39, 176, 0.08)); border-radius: var(--radius-lg); padding: 25px; border-right: 4px solid #9c27b0; margin-bottom: 25px;">
                            <h3 style="margin: 0 0 20px 0; color: #7b1fa2; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                                🎛️ خيارات المنتج (اختياري)
                                <small style="font-weight: normal; color: var(--text-muted); font-size: 0.8rem;">— فعّل فقط ما تحتاجه</small>
                            </h3>
                            
                            <!-- COLORS OPTION - نظام المجموعات الجديد -->
                            <div class="option-block" style="background: white; border-radius: var(--radius-md); padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 15px;">
                                    <input type="checkbox" name="options_colors_enabled" id="optColorsEnabled" <?= $colorsEnabled ? 'checked' : '' ?> onchange="toggleOptionSection('colors')">
                                    <span style="font-weight: 700; font-size: 1rem;">🎨 تفعيل الألوان</span>
                                    <label style="margin-right: auto; display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                        <input type="checkbox" name="options_colors_required" <?= $colorsRequired ? 'checked' : '' ?>>
                                        إجباري
                                    </label>
                                </label>
                                <div id="colorsSection" style="display: <?= $colorsEnabled ? 'block' : 'none' ?>;">
                                    <!-- مجموعات الألوان -->
                                    <div id="colorsGroupsContainer">
                                        <?php 
                                        $colorGroupIndex = 0;
                                        if (!empty($colorsGroups)):
                                            foreach ($colorsGroups as $group): 
                                        ?>
                                        <div class="option-group-card" data-group-index="<?= $colorGroupIndex ?>" style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                                <input type="text" name="colors_groups[<?= $colorGroupIndex ?>][label]" value="<?= htmlspecialchars($group['label']) ?>" placeholder="وصف المجموعة (مثال: لون الإطار)" class="form-control" style="flex: 1; font-weight: 600;">
                                                <button type="button" onclick="removeOptionGroup(this, 'colors')" class="btn btn-outline" style="color: #f44336; border-color: #f44336; padding: 8px 12px;">🗑️</button>
                                            </div>
                                            <div class="group-items" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;">
                                                <?php 
                                                if (!empty($group['items'])):
                                                    foreach ($group['items'] as $itemIndex => $item): 
                                                        $itemName = is_array($item) ? $item['name'] : $item;
                                                        $itemQty = is_array($item) ? (isset($item['qty']) ? $item['qty'] : 999) : 999;
                                                ?>
                                                <div class="color-item-chip" style="display: flex; align-items: center; gap: 5px; background: linear-gradient(135deg, #e91e8c, #c2185b); color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem;">
                                                    <span><?= htmlspecialchars($itemName) ?></span>
                                                    <input type="hidden" name="colors_groups[<?= $colorGroupIndex ?>][items][<?= $itemIndex ?>][name]" value="<?= htmlspecialchars($itemName) ?>">
                                                    <input type="number" name="colors_groups[<?= $colorGroupIndex ?>][items][<?= $itemIndex ?>][qty]" value="<?= $itemQty ?>" min="0" style="width: 50px; padding: 2px 5px; border: none; border-radius: 8px; text-align: center; font-size: 0.75rem;" title="الكمية المتاحة">
                                                    <button type="button" onclick="removeGroupItem(this)" style="background: rgba(255,255,255,0.3); border: none; color: white; width: 18px; height: 18px; border-radius: 50%; cursor: pointer; font-size: 12px;">×</button>
                                                </div>
                                                <?php 
                                                    endforeach;
                                                endif;
                                                ?>
                                            </div>
                                            <div style="display: flex; gap: 8px;">
                                                <input type="text" class="new-item-input form-control" placeholder="أدخل لون جديد" style="flex: 1;" onkeypress="if(event.key==='Enter'){event.preventDefault();addGroupItem(this,'colors',<?= $colorGroupIndex ?>);}">
                                                <input type="number" class="new-item-qty form-control" placeholder="الكمية" value="999" min="0" style="width: 80px;">
                                                <button type="button" onclick="addGroupItem(this,'colors',<?= $colorGroupIndex ?>)" class="btn btn-outline" style="white-space: nowrap;">+ إضافة</button>
                                            </div>
                                        </div>
                                        <?php 
                                            $colorGroupIndex++;
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                    <button type="button" onclick="addOptionGroup('colors')" class="btn btn-outline" style="width: 100%; border-style: dashed; color: #9c27b0; border-color: #9c27b0;">
                                        ➕ إضافة مجموعة ألوان جديدة
                                    </button>
                                    <p style="font-size: 0.8rem; color: #888; margin-top: 10px;">
                                        💡 يمكنك إضافة عدة مجموعات (مثلاً: لون الإطار، لون الخلفية). حدد الكمية لكل لون (0 = غير متاح).
                                    </p>
                                    <!-- Hidden field for colors groups data -->
                                    <input type="hidden" name="options_colors_groups_json" id="colorsGroupsJson" value='<?= htmlspecialchars(json_encode($colorsGroups, JSON_UNESCAPED_UNICODE)) ?>'>
                                </div>
                            </div>
                            
                            <!-- SIZES OPTION - نظام المجموعات الجديد -->
                            <?php
                            $fixedSizes = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL (2XL)', 'XXXL (3XL)', '4XL', '5XL', '6XL'];
                            ?>
                            <div class="option-block" style="background: white; border-radius: var(--radius-md); padding: 20px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 15px;">
                                    <input type="checkbox" name="options_sizes_enabled" id="optSizesEnabled" <?= $sizesEnabled ? 'checked' : '' ?> onchange="toggleOptionSection('sizes')">
                                    <span style="font-weight: 700; font-size: 1rem;">📐 تفعيل الأحجام</span>
                                    <label style="margin-right: auto; display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                        <input type="checkbox" name="options_sizes_required" <?= $sizesRequired ? 'checked' : '' ?>>
                                        إجباري
                                    </label>
                                </label>
                                <div id="sizesSection" style="display: <?= $sizesEnabled ? 'block' : 'none' ?>;">
                                    <!-- مجموعات الأحجام -->
                                    <div id="sizesGroupsContainer">
                                        <?php 
                                        $sizeGroupIndex = 0;
                                        if (!empty($sizesGroups)):
                                            foreach ($sizesGroups as $group): 
                                        ?>
                                        <div class="option-group-card" data-group-index="<?= $sizeGroupIndex ?>" style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                                <input type="text" name="sizes_groups[<?= $sizeGroupIndex ?>][label]" value="<?= htmlspecialchars($group['label']) ?>" placeholder="وصف المجموعة (مثال: حجم التيشيرت)" class="form-control" style="flex: 1; font-weight: 600;">
                                                <button type="button" onclick="removeOptionGroup(this, 'sizes')" class="btn btn-outline" style="color: #f44336; border-color: #f44336; padding: 8px 12px;">🗑️</button>
                                            </div>
                                            <p style="font-size: 0.8rem; color: #666; margin-bottom: 10px;">اختر الأحجام المتاحة وحدد الكمية لكل حجم:</p>
                                            <div class="group-items sizes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; margin-bottom: 10px;">
                                                <?php 
                                                $groupItems = !empty($group['items']) ? $group['items'] : [];
                                                $groupItemNames = array_map(function($item) { 
                                                    return is_array($item) ? $item['name'] : $item; 
                                                }, $groupItems);
                                                foreach ($fixedSizes as $size): 
                                                    $isSelected = in_array($size, $groupItemNames);
                                                    $itemQty = 999;
                                                    if ($isSelected) {
                                                        foreach ($groupItems as $item) {
                                                            if ((is_array($item) && $item['name'] === $size) || $item === $size) {
                                                                $itemQty = is_array($item) && isset($item['qty']) ? $item['qty'] : 999;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                ?>
                                                <label class="size-checkbox-label" style="display: flex; align-items: center; gap: 6px; padding: 8px 10px; border: 2px solid <?= $isSelected ? '#2196f3' : '#e0e0e0' ?>; border-radius: 10px; background: <?= $isSelected ? 'linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(25, 118, 210, 0.1))' : 'white' ?>; cursor: pointer;">
                                                    <input type="checkbox" class="size-check" data-size="<?= htmlspecialchars($size) ?>" <?= $isSelected ? 'checked' : '' ?> style="width: 16px; height: 16px; accent-color: #2196f3;" onchange="toggleSizeItem(this, <?= $sizeGroupIndex ?>)">
                                                    <span style="font-weight: 600; font-size: 0.85rem; flex: 1;"><?= htmlspecialchars($size) ?></span>
                                                    <input type="number" class="size-qty" value="<?= $itemQty ?>" min="0" style="width: 45px; padding: 2px 4px; border: 1px solid #ddd; border-radius: 6px; text-align: center; font-size: 0.75rem; <?= $isSelected ? '' : 'opacity: 0.5;' ?>" <?= $isSelected ? '' : 'disabled' ?> title="الكمية">
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                            <!-- Hidden inputs for this group's sizes -->
                                            <div class="sizes-hidden-inputs"></div>
                                        </div>
                                        <?php 
                                            $sizeGroupIndex++;
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                    <button type="button" onclick="addSizesGroup()" class="btn btn-outline" style="width: 100%; border-style: dashed; color: #2196f3; border-color: #2196f3;">
                                        ➕ إضافة مجموعة أحجام جديدة
                                    </button>
                                    <p style="font-size: 0.8rem; color: #888; margin-top: 10px;">
                                        💡 يمكنك إضافة عدة مجموعات (مثلاً: حجم التيشيرت، حجم البنطلون). حدد الكمية لكل حجم (0 = غير متاح).
                                    </p>
                                    <input type="hidden" name="options_sizes_groups_json" id="sizesGroupsJson" value='<?= htmlspecialchars(json_encode($sizesGroups, JSON_UNESCAPED_UNICODE)) ?>'>
                                </div>
                            </div>
                            
                            <!-- EXTRA FIELDS OPTION - الحقول الإضافية الموحدة (تدمج النص المخصص والفئات العمرية) -->
                            <div class="option-block" style="background: white; border-radius: var(--radius-md); padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 15px;">
                                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 15px;">
                                    <input type="checkbox" name="options_extra_fields_enabled" id="optExtraFieldsEnabled" <?= !empty($productOptions['extra_fields']['enabled']) ? 'checked' : '' ?> onchange="toggleOptionSection('extraFields')">
                                    <span style="font-weight: 700; font-size: 1rem;">🏷️ تفعيل حقول إضافية (نقش/اسم/عمر/ملاحظات)</span>
                                    <label style="margin-right: auto; display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                        <input type="checkbox" name="options_extra_fields_required" <?= !empty($productOptions['extra_fields']['required']) ? 'checked' : '' ?>>
                                        إجباري
                                    </label>
                                </label>
                                <div id="extraFieldsSection" style="display: <?= !empty($productOptions['extra_fields']['enabled']) ? 'block' : 'none' ?>;">
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px; background: #f5f5f5; padding: 10px; border-radius: 8px;">
                                        💡 أنشئ مجموعات مخصصة تحتوي على حقول نصية (مثل الاسم/النقش) أو قوائم اختيارات (مثل الفئة العمرية). كل مجموعة تظهر للزبون بوصفها الخاص.
                                    </p>
                                    
                                    <div id="extraFieldsGroupsContainer">
                                        <?php 
                                        $extraFieldsGroups = isset($productOptions['extra_fields']['groups']) ? $productOptions['extra_fields']['groups'] : [];
                                        foreach ($extraFieldsGroups as $groupIndex => $group): 
                                        ?>
                                        <div class="option-group-card extra-fields-group" data-group-index="<?= $groupIndex ?>" style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                                <input type="text" name="extra_fields_groups[<?= $groupIndex ?>][label]" value="<?= htmlspecialchars($group['label'] ?? '') ?>" placeholder="وصف المجموعة للزبون (مثال: معلومات الهدية)" class="form-control" style="flex: 1; font-weight: 600;">
                                                <button type="button" onclick="removeOptionGroup(this, 'extra_fields')" class="btn btn-outline" style="color: #f44336; border-color: #f44336; padding: 8px 12px;">🗑️</button>
                                            </div>
                                            <div class="extra-fields-items" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;">
                                                <?php 
                                                $items = isset($group['items']) ? $group['items'] : [];
                                                foreach ($items as $itemIndex => $item): 
                                                    $itemType = isset($item['type']) ? $item['type'] : 'text';
                                                    if ($itemType === 'text'):
                                                ?>
                                                <div class="extra-field-item" data-field-type="text" style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 193, 7, 0.08)); border: 1px solid #ffb74d; border-radius: 10px; padding: 12px;">
                                                    <input type="hidden" name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][type]" value="text">
                                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                                        <span style="font-size: 1.1rem;">✏️</span>
                                                        <span style="font-weight: 600; font-size: 0.85rem; color: #e65100;">حقل نصي</span>
                                                        <button type="button" onclick="removeGroupItem(this)" style="margin-right: auto; background: #f44336; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 14px;">×</button>
                                                    </div>
                                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                                                        <input type="text" name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][label]" value="<?= htmlspecialchars($item['label'] ?? '') ?>" placeholder="العنوان" class="form-control" style="font-size: 0.85rem;">
                                                        <input type="number" name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][max_length]" value="<?= intval($item['max_length'] ?? 50) ?>" min="5" max="200" class="form-control" style="font-size: 0.85rem;">
                                                    </div>
                                                    <input type="text" name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][placeholder]" value="<?= htmlspecialchars($item['placeholder'] ?? '') ?>" placeholder="نص توضيحي (اختياري)" class="form-control" style="font-size: 0.85rem; margin-top: 8px;">
                                                    <label style="display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.8rem; color: #666;">
                                                        <input type="checkbox" name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][required]" value="1" <?= !empty($item['required']) ? 'checked' : '' ?>> إجباري
                                                    </label>
                                                </div>
                                                <?php else: ?>
                                                <div class="extra-field-item" data-field-type="select" style="background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(129, 199, 132, 0.08)); border: 1px solid #81c784; border-radius: 10px; padding: 12px;">
                                                    <input type="hidden" name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][type]" value="select">
                                                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                                                        <span style="font-size: 1.1rem;">📋</span>
                                                        <span style="font-weight: 600; font-size: 0.85rem; color: #2e7d32;">قائمة اختيارات</span>
                                                        <button type="button" onclick="removeGroupItem(this)" style="margin-right: auto; background: #f44336; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 14px;">×</button>
                                                    </div>
                                                    <input type="text" name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][label]" value="<?= htmlspecialchars($item['label'] ?? '') ?>" placeholder="العنوان" class="form-control" style="font-size: 0.85rem; margin-bottom: 8px;">
                                                    <textarea name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][options]" placeholder="اكتب الخيارات مفصولة بـ |" class="form-control" rows="2" style="font-size: 0.85rem;"><?= htmlspecialchars(is_array($item['options'] ?? '') ? implode(' | ', $item['options']) : ($item['options'] ?? '')) ?></textarea>
                                                    <label style="display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.8rem; color: #666;">
                                                        <input type="checkbox" name="extra_fields_groups[<?= $groupIndex ?>][items][<?= $itemIndex ?>][required]" value="1" <?= !empty($item['required']) ? 'checked' : '' ?>> إجباري
                                                    </label>
                                                </div>
                                                <?php endif; endforeach; ?>
                                            </div>
                                            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                <button type="button" onclick="addExtraFieldItem(this, <?= $groupIndex ?>, 'text')" class="btn btn-outline" style="font-size: 0.85rem;">
                                                    ✏️ + حقل نصي
                                                </button>
                                                <button type="button" onclick="addExtraFieldItem(this, <?= $groupIndex ?>, 'select')" class="btn btn-outline" style="font-size: 0.85rem;">
                                                    📋 + قائمة اختيارات
                                                </button>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <button type="button" onclick="addExtraFieldsGroup()" class="btn btn-outline" style="width: 100%; padding: 12px; border: 2px dashed var(--primary); color: var(--primary); font-weight: 600; margin-top: 10px;">
                                        ➕ إضافة مجموعة حقول جديدة
                                    </button>
                                </div>
                            </div>

                            <!-- BOX OPTIONS OPTION - خيارات الصناديق/العلب -->
                            <div class="option-block" style="background: white; border-radius: var(--radius-md); padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 15px; border: 2px solid rgba(156, 39, 176, 0.1);">
                                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 15px;">
                                    <input type="checkbox" name="options_box_enabled" id="optBoxEnabled" <?= $boxOptionsEnabled ? 'checked' : '' ?> onchange="toggleOptionSection('box')">
                                    <span style="font-weight: 700; font-size: 1rem;">📦 تفعيل خيارات الصناديق (Box Options)</span>
                                    <label style="margin-right: auto; display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                        <input type="checkbox" name="options_box_mandatory" <?= $boxOptionsMandatory ? 'checked' : '' ?>>
                                        إجباري
                                    </label>
                                </label>
                                <div id="boxSection" style="display: <?= $boxOptionsEnabled ? 'block' : 'none' ?>;">
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px; background: #f5f5f5; padding: 10px; border-radius: 8px;">
                                        💡 أضف أنواعاً مختلفة من الصناديق أو العلب لهذا المنتج. يمكنك تحديد سعر إضافي وكمية متوفرة لكل نوع.
                                    </p>
                                    
                                    <div id="boxOptionsContainer" style="display: flex; flex-direction: column; gap: 12px; margin-bottom: 15px;">
                                        <?php foreach ($boxOptionsItems as $idx => $box): ?>
                                        <div class="box-option-item" style="background: #fafafa; border: 1px solid #ddd; border-radius: 10px; padding: 15px; position: relative;">
                                            <button type="button" onclick="this.closest('.box-option-item').remove()" style="position: absolute; top: 10px; left: 10px; background: #ff5252; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer;">&times;</button>
                                            
                                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 10px; padding-left: 30px;">
                                                <div>
                                                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">اسم الخيار (مثال: علبة فاخرة حمراء)</label>
                                                    <input type="text" name="box_options_data[<?= $idx ?>][name]" value="<?= htmlspecialchars($box['name']) ?>" class="form-control" required>
                                                </div>
                                                <div>
                                                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">الحجم (اختياري)</label>
                                                    <input type="text" name="box_options_data[<?= $idx ?>][size]" value="<?= htmlspecialchars($box['size']) ?>" class="form-control" placeholder="20x20">
                                                </div>
                                            </div>
                                            
                                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                                                <div>
                                                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">زيادة السعر (IQD)</label>
                                                    <input type="number" name="box_options_data[<?= $idx ?>][price]" value="<?= $box['price'] ?>" class="form-control" min="0">
                                                </div>
                                                <div>
                                                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">الكمية المتوفرة</label>
                                                    <input type="number" name="box_options_data[<?= $idx ?>][stock]" value="<?= $box['stock'] ?>" class="form-control" min="0">
                                                </div>
                                                <div style="grid-column: span 3; margin-top: 5px;">
                                                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">وصف بسيط (يظهر تحت الاسم)</label>
                                                    <input type="text" name="box_options_data[<?= $idx ?>][description]" value="<?= htmlspecialchars($box['description']) ?>" class="form-control" placeholder="علبة مصنوعة من الكرتون المقوى بلمسة حريرية">
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <button type="button" onclick="addBoxOption()" class="btn btn-outline" style="width: 100%; border-style: dashed; color: #9c27b0; border-color: #9c27b0;">
                                        ➕ إضافة خيار صندوق جديد
                                    </button>
                                </div>
                            </div>
                            
                            <!-- GIFT CARD OPTION - بطاقة إهداء (Multi-Card System) -->
                            <?php 
                            $giftCardEnabled = !empty($productOptions['gift_card_enabled']);
                            $giftCards = isset($productOptions['gift_cards']) ? $productOptions['gift_cards'] : [];
                            
                            // Compatibility: Check for legacy format if no new format exists
                            if (empty($giftCards) && !empty($productOptions['gift_card']['enabled'])) {
                                $giftCards[] = [
                                    'id' => 'card_legacy_' . uniqid(),
                                    'label' => $productOptions['gift_card']['label'] ?? 'رسالة البطاقة',
                                    'helper' => $productOptions['gift_card']['helper'] ?? '',
                                    'required' => !empty($productOptions['gift_card']['required']),
                                    'max_length' => $productOptions['gift_card']['max_length'] ?? 250,
                                    'placeholder' => $productOptions['gift_card']['placeholder'] ?? ''
                                ];
                            }
                            ?>
                            <div class="option-block" style="background: linear-gradient(135deg, rgba(233, 30, 140, 0.05), rgba(156, 39, 176, 0.05)); border-radius: var(--radius-md); padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 2px solid rgba(233, 30, 140, 0.15); margin-top: 20px;">
                                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 1px dashed rgba(233,30,140,0.2);">
                                    <input type="checkbox" name="options_gift_card_enabled" id="optGiftCardEnabled" <?= $giftCardEnabled ? 'checked' : '' ?> onchange="toggleOptionSection('giftCard')">
                                    <span style="font-weight: 800; font-size: 1.1rem; color: var(--primary);">🎁 نظام بطاقات الرسائل المتعددة (Gift Cards)</span>
                                </label>
                                
                                <div id="giftCardSection" style="display: <?= $giftCardEnabled ? 'block' : 'none' ?>;">
                                    <p style="font-size: 0.85rem; color: #666; margin-bottom: 20px; background: white; padding: 12px; border-radius: 10px; border: 1px solid #eee;">
                                        💡 يمكنك إضافة أكثر من بطاقة أو رسالة لنفس المنتج. الزبون سيقوم بتفعيل البطاقة التي يريدها وكتابة الرسالة بداخلها.
                                    </p>
                                    
                                    <div id="giftCardsContainer" style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
                                        <?php if (!empty($giftCards)): foreach ($giftCards as $idx => $card): ?>
                                        <div class="gift-card-item" style="background: white; border: 1px solid #ddd; border-radius: 12px; padding: 18px; position: relative; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                                            <input type="hidden" name="gift_cards_data[<?= $idx ?>][id]" value="<?= htmlspecialchars($card['id'] ?? '') ?>">
                                            
                                            <button type="button" onclick="this.closest('.gift-card-item').remove()" style="position: absolute; top: 10px; left: 10px; background: #ff5252; color: white; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">&times;</button>
                                            
                                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; padding-left: 35px;">
                                                <div>
                                                    <label style="font-size: 0.8rem; font-weight: 700; color: #444; margin-bottom: 5px; display: block;">عنوان البطاقة (يظهر للزبون)</label>
                                                    <input type="text" name="gift_cards_data[<?= $idx ?>][label]" value="<?= htmlspecialchars($card['label'] ?? '') ?>" class="form-control" placeholder="مثال: بطاقة داخلية">
                                                </div>
                                                <div>
                                                    <label style="font-size: 0.8rem; font-weight: 700; color: #444; margin-bottom: 5px; display: block;">الحد الأقصى للأحرف</label>
                                                    <input type="number" name="gift_cards_data[<?= $idx ?>][max_length]" value="<?= $card['max_length'] ?? 250 ?>" min="10" max="1000" class="form-control">
                                                </div>
                                            </div>
                                            
                                            <div style="margin-bottom: 15px;">
                                                <label style="font-size: 0.8rem; font-weight: 700; color: #444; margin-bottom: 5px; display: block;">نص مساعد للزبون</label>
                                                <input type="text" name="gift_cards_data[<?= $idx ?>][helper]" value="<?= htmlspecialchars($card['helper'] ?? '') ?>" class="form-control" placeholder="مثال: اكتب رسالتك هنا...">
                                            </div>
                                            
                                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 15px;">
                                                <div style="flex: 1;">
                                                    <label style="font-size: 0.8rem; font-weight: 700; color: #444; margin-bottom: 5px; display: block;">نص توضيحي (Placeholder)</label>
                                                    <input type="text" name="gift_cards_data[<?= $idx ?>][placeholder]" value="<?= htmlspecialchars($card['placeholder'] ?? '') ?>" class="form-control" placeholder="مثال: كل عام وأنت بخير...">
                                                </div>
                                                <div style="padding-top: 25px;">
                                                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: #d32f2f; font-size: 0.9rem;">
                                                        <input type="checkbox" name="gift_cards_data[<?= $idx ?>][required]" value="1" <?= !empty($card['required']) ? 'checked' : '' ?>> إلزامي
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; else: ?>
                                            <p id="noGiftCardsNote" style="text-align: center; color: #888; font-style: italic; padding: 10px;">لم يتم إضافة أي بطاقات بعد.</p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <button type="button" onclick="addGiftCardBlock()" class="btn btn-outline" style="width: 100%; padding: 12px; border: 2px dashed var(--primary); color: var(--primary); font-weight: 700; background: white; border-radius: 10px;">
                                        ➕ إضافة بطاقة/رسالة جديدة للمنتج
                                    </button>
                                </div>
                            </div>
                            
                            <!-- CUSTOM IMAGES OPTION - صور التخصيص المتعددة بنظام المجموعات -->
                            <div class="option-block" id="customImagesOptionBlock" style="background: white; border-radius: var(--radius-md); padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-top: 15px;">
                                <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin-bottom: 15px;">
                                    <input type="checkbox" name="options_custom_images_enabled" id="optCustomImagesEnabled" <?= $customImagesEnabled ? 'checked' : '' ?> onchange="handleCustomImagesToggle(this)">
                                    <span style="font-weight: 700; font-size: 1rem;">📷 تفعيل رفع صور التخصيص المتعددة</span>
                                    <label style="margin-right: auto; display: flex; align-items: center; gap: 5px; font-size: 0.85rem; color: var(--text-muted);">
                                        <input type="checkbox" name="options_custom_images_required" id="optCustomImagesRequired" <?= $customImagesRequired ? 'checked' : '' ?>>
                                        إجباري
                                    </label>
                                </label>
                                <div id="customImagesLockedNote" style="display: none; padding: 10px 15px; background: #fff3e0; border: 1px solid #ffb74d; border-radius: 8px; margin-bottom: 15px; font-size: 0.85rem; color: #e65100;">
                                    ⚠️ رفع الصور مفعّل تلقائياً لأن المنتج قابل للتخصيص (الطباعة). لإيقافه، أوقف خيار الطباعة أولاً.
                                </div>
                                <div id="customImagesSection" style="display: <?= $customImagesEnabled ? 'block' : 'none' ?>;">
                                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 15px; background: #f5f5f5; padding: 10px; border-radius: 8px;">
                                        💡 أنشئ مجموعات صور متعددة (مثلاً: صورة للوجه الأمامي، صورة للخلفية). كل مجموعة لها وصفها وإعداداتها الخاصة.
                                    </p>
                                    
                                    <div id="customImagesGroupsContainer">
                                        <?php 
                                        $imagesGroups = isset($productOptions['custom_images']['groups']) ? $productOptions['custom_images']['groups'] : [];
                                        // إذا لم توجد مجموعات، أنشئ مجموعة افتراضية من البيانات القديمة
                                        if (empty($imagesGroups) && $customImagesEnabled) {
                                            $imagesGroups = [[
                                                'label' => $customImagesLabel,
                                                'min' => $customImagesMin,
                                                'max' => $customImagesMax,
                                                'max_size' => $customImagesMaxSize,
                                                'required' => $customImagesRequired
                                            ]];
                                        }
                                        foreach ($imagesGroups as $groupIndex => $group): 
                                        ?>
                                        <div class="option-group-card images-group" data-group-index="<?= $groupIndex ?>" style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                                                <input type="text" name="custom_images_groups[<?= $groupIndex ?>][label]" value="<?= htmlspecialchars($group['label'] ?? '') ?>" placeholder="وصف المجموعة (مثال: صورة الوجه الأمامي)" class="form-control" style="flex: 1; font-weight: 600;">
                                                <button type="button" onclick="removeOptionGroup(this, 'images')" class="btn btn-outline" style="color: #f44336; border-color: #f44336; padding: 8px 12px;">🗑️</button>
                                            </div>
                                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                                                <div>
                                                    <label style="font-size: 0.75rem; color: #888; display: block; margin-bottom: 4px;">الحد الأدنى</label>
                                                    <input type="number" name="custom_images_groups[<?= $groupIndex ?>][min]" class="form-control" min="0" max="10" value="<?= intval($group['min'] ?? 1) ?>" style="font-size: 0.85rem;">
                                                </div>
                                                <div>
                                                    <label style="font-size: 0.75rem; color: #888; display: block; margin-bottom: 4px;">الحد الأقصى</label>
                                                    <input type="number" name="custom_images_groups[<?= $groupIndex ?>][max]" class="form-control" min="1" max="10" value="<?= intval($group['max'] ?? 3) ?>" style="font-size: 0.85rem;">
                                                </div>
                                                <div>
                                                    <label style="font-size: 0.75rem; color: #888; display: block; margin-bottom: 4px;">الحجم (MB)</label>
                                                    <input type="number" name="custom_images_groups[<?= $groupIndex ?>][max_size]" class="form-control" min="1" max="20" value="<?= intval($group['max_size'] ?? 5) ?>" style="font-size: 0.85rem;">
                                                </div>
                                            </div>
                                            <label style="display: flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 0.85rem; color: #666;">
                                                <input type="checkbox" name="custom_images_groups[<?= $groupIndex ?>][required]" value="1" <?= !empty($group['required']) ? 'checked' : '' ?>> إجباري
                                            </label>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <button type="button" onclick="addImagesGroup()" class="btn btn-outline" style="width: 100%; padding: 12px; border: 2px dashed #9c27b0; color: #9c27b0; font-weight: 600; margin-top: 10px;">
                                        ➕ إضافة مجموعة صور جديدة
                                    </button>
                                    
                                    <!-- أنواع الصور المسموحة عامة لكل المجموعات -->
                                    <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                                        <label style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 8px; display: block;">أنواع الملفات المسموحة:</label>
                                        <div style="display: flex; flex-wrap: wrap; gap: 12px;">
                                            <?php 
                                            $allTypes = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                                            foreach ($allTypes as $type): 
                                            $isChecked = in_array($type, $customImagesAllowedTypes);
                                            ?>
                                            <label style="display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 6px 12px; border: 1px solid <?= $isChecked ? '#9c27b0' : '#e0e0e0' ?>; border-radius: 20px; background: <?= $isChecked ? 'rgba(156, 39, 176, 0.1)' : 'white' ?>;">
                                                <input type="checkbox" name="options_custom_images_types[]" value="<?= $type ?>" <?= $isChecked ? 'checked' : '' ?> style="accent-color: #9c27b0;">
                                                <span style="text-transform: uppercase; font-weight: 600; font-size: 0.8rem;"><?= $type ?></span>
                                            </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- END PRODUCT OPTIONS SECTION -->
                        
                        <div class="form-group">
                            <label class="form-label">صور المنتج * (1-4 صور)</label>
                            
                            <!-- Hidden input to track deleted images -->
                            <input type="hidden" name="deleted_images" id="deletedImagesInput" value="">
                            
                            <?php if ($editProduct && !empty($editProduct['images'])): ?>
                            <div class="image-preview existing-images" id="existingImagesContainer" style="margin-bottom: 15px;">
                                <?php foreach ($editProduct['images'] as $index => $img): ?>
                                <div class="image-preview-item" id="img-item-<?= $index ?>" data-index="<?= $index ?>" style="position: relative;">
                                    <img src="../images/<?= $img ?>" alt="" onerror="this.parentElement.style.display='none'">
                                    <span class="image-number" style="position: absolute; top: 5px; right: 5px; background: rgba(0,0,0,0.6); color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.75rem;"><?= $index + 1 ?></span>
                                    <button type="button" 
                                            class="delete-image-btn" 
                                            data-image-index="<?= $index ?>"
                                            title="حذف الصورة"
                                            style="position: absolute; top: 0; left: 0; width: 35px; height: 35px; border-radius: 0 0 15px 0; background: rgba(255, 68, 68, 0.9); color: white; border: none; cursor: pointer; font-size: 18px; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: all 0.2s ease; box-shadow: 2px 2px 8px rgba(0,0,0,0.2); z-index: 999; pointer-events: auto;">
                                        ✕
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <p id="imageCountInfo" style="font-size: 0.85rem; color: var(--text-muted); margin-bottom: 15px;">
                                📷 <span id="currentImageCount"><?= count($editProduct['images']) ?></span> صورة حالياً (الحد الأقصى 4)
                                <span id="deletedImagesNote" style="color: #ff5722; display: none;"> - سيتم حذف <span id="deletedCount">0</span> صورة عند الحفظ</span>
                            </p>
                            <?php endif; ?>
                            
                            <!-- Image Upload Container -->
                            <div id="imageUploadContainer">
                                <div class="image-upload-row" style="display: flex; gap: 10px; align-items: center; margin-bottom: 10px;">
                                    <label class="custom-file-upload" style="flex: 1;">
                                        <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" class="image-input" style="display: none;" <?= !$editProduct ? 'required' : '' ?>>
                                        <div class="file-upload-btn" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 15px 20px; border: 2px dashed rgba(233, 30, 140, 0.3); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #fff 0%, #fafafa 100%);">
                                            <span style="font-size: 1.5rem;">📷</span>
                                            <span class="file-name" style="font-weight: 600; color: var(--text-muted);">اختر صورة</span>
                                        </div>
                                    </label>
                                    <div class="image-thumb" style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; display: none; border: 2px solid var(--primary);">
                                        <img src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Add More Images Button -->
                            <button type="button" id="addMoreImages" class="btn btn-outline" style="margin-top: 10px; width: 100%;">
                                ➕ إضافة صورة أخرى
                            </button>
                            
                            <p style="font-size: 0.85rem; color: var(--text-muted); margin-top: 12px;">
                                📌 صورة واحدة على الأقل مطلوبة | الحد الأقصى 4 صور | (JPG, PNG, WEBP) | حد 5MB لكل صورة
                            </p>
                        </div>
                        
                        <div style="display: flex; gap: 15px; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <?= $mode === 'add' ? '➕ إضافة المنتج' : '💾 حفظ التعديلات' ?>
                            </button>
                            <a href="products" class="btn btn-outline btn-lg">إلغاء</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    <?php if ($mode === 'list'): ?>
    // AJAX Live Search for Products
    const csrf = '<?= $csrf_token ?>';
    let searchTimeout;
    
    const productSearch = document.getElementById('productSearch');
    const productCategory = document.getElementById('productCategory');
    const productStatus = document.getElementById('productStatus');
    const clearProductFilters = document.getElementById('clearProductFilters');
    const searchResults = document.getElementById('productSearchResults');
    
    function doProductSearch() {
        const params = new URLSearchParams({
            type: 'products',
            search: productSearch?.value || '',
            category: productCategory?.value || '',
            status: productStatus?.value || ''
        });
        
        if (searchResults) searchResults.innerHTML = '⏳ جاري البحث...';
        
        // Set as last action for retry
        if (window.apiHelper) {
            window.apiHelper.setLastAction(doProductSearch);
        }
        
        // Create AbortController for timeout
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000);
        
        fetch('../api/admin-search.php?' + params, { signal: controller.signal })
            .then(res => {
                clearTimeout(timeoutId);
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.text();
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Invalid JSON:', text.substring(0, 200));
                    throw new Error('استجابة غير صالحة من الخادم');
                }
            })
            .then(data => {
                if (data.success) {
                    if (searchResults) searchResults.innerHTML = `✅ تم العثور على ${data.count} منتج`;
                    renderProducts(data.products);
                } else {
                    if (searchResults) searchResults.innerHTML = '❌ ' + (data.error || 'خطأ في البحث');
                }
            })
            .catch(err => {
                clearTimeout(timeoutId);
                console.error('Product search error:', err);
                
                let errorMsg = '❌ ';
                if (err.name === 'AbortError') {
                    errorMsg += 'انتهت مهلة الاتصال';
                } else if (err.message.includes('fetch')) {
                    errorMsg += 'فشل الاتصال بالخادم';
                } else {
                    errorMsg += 'خطأ في البحث';
                }
                
                if (searchResults) {
                    searchResults.innerHTML = `
                        ${errorMsg}
                        <button onclick="doProductSearch()" style="margin-right: 10px; background: var(--primary); color: white; border: none; padding: 5px 15px; border-radius: 15px; cursor: pointer;">
                            🔄 إعادة المحاولة
                        </button>
                    `;
                }
            });
    }
    
    // Make doProductSearch global for retry
    window.doProductSearch = doProductSearch;
    
    function formatPriceWithDiscount(price, oldPrice = 0) {
        const p = formatPrice(price);
        if (oldPrice > price) {
            const o = formatPrice(oldPrice);
            return `<span class="price-current">${p}</span> <span class="price-old">${o}</span>`;
        }
        return `<span class="price-current">${p}</span>`;
    }

    function renderProducts(products) {
        const tableContainer = document.querySelector('.admin-table tbody');
        const mobileContainer = document.querySelector('.product-cards-mobile');
        
        if (!tableContainer) return;
        
        if (products.length === 0) {
            const emptyTable = '<tr><td colspan="8" style="text-align: center; padding: 30px;">لا توجد نتائج</td></tr>';
            const emptyMobile = `
                <div class="empty-state" style="background: white; border-radius: var(--radius-lg); padding: 40px;">
                    <div class="empty-icon">📦</div>
                    <h2 class="empty-title">لا توجد نتائج</h2>
                </div>
            `;
            tableContainer.innerHTML = emptyTable;
            if (mobileContainer) mobileContainer.innerHTML = emptyMobile;
            return;
        }
        
        const statusLabels = {available: '✓ متوفر', sold: '✗ نفذ', hidden: '👁 مخفي'};
        const statusClasses = {available: 'status-available', sold: 'status-sold', hidden: 'status-hidden'};
        
        // Render Desktop Table
        tableContainer.innerHTML = products.map(p => {
            const img = (p.images && p.images[0]) || 'logo.jpg';
            const oldPrice = p.old_price || 0;
            const isCustomizable = p.customizable ? '<br><small style="color: var(--primary);">قابل للتخصيص</small>' : '';
            const featured = p.featured ? '⭐' : '-';
            
            return `
            <tr>
                <td>
                    <img src="../images/${img}" alt="" onerror="this.src='../images/logo.jpg'" style="width:60px;height:60px;object-fit:cover;border-radius:8px;">
                </td>
                <td>
                    <strong>${p.name}</strong>
                    ${isCustomizable}
                </td>
                <td>${p.category_name || '-'}</td>
                <td>${formatPriceWithDiscount(p.price, oldPrice)}</td>
                <td>
                    <div class="stock-control" data-product-id="${p.id}">
                        <button type="button" class="stock-btn stock-minus" title="-1">−</button>
                        <span class="stock-value" id="stock-${p.id}">${p.stock || 0}</span>
                        <button type="button" class="stock-btn stock-plus" title="+1">+</button>
                    </div>
                </td>
                <td>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="${csrf}">
                        <input type="hidden" name="product_id" value="${p.id}">
                        <input type="hidden" name="toggle_status" value="1">
                        <select name="new_status" onchange="this.form.submit()" class="form-control" style="width: auto; padding: 5px 10px;">
                            <option value="available" ${p.status === 'available' ? 'selected' : ''}>✓ متوفر</option>
                            <option value="sold" ${p.status === 'sold' ? 'selected' : ''}>✗ نفذ</option>
                            <option value="hidden" ${p.status === 'hidden' ? 'selected' : ''}>👁 مخفي</option>
                        </select>
                    </form>
                </td>
                <td>${featured}</td>
                <td>
                    <div class="action-btns">
                        <a href="products?action=edit&id=${p.id}" class="action-btn action-btn-edit" title="تعديل">✏️</a>
                        <button type="button" class="action-btn action-btn-delete" 
                           title="حذف"
                           data-delete-type="product"
                           data-delete-id="${p.id}"
                           data-delete-name="${escapeHtml(p.name)}"
                           data-delete-token="${csrf}">🗑️</button>
                    </div>
                </td>
            </tr>
            `;
        }).join('');

        // Render Mobile Cards
        if (mobileContainer) {
            mobileContainer.innerHTML = products.map(p => {
                const img = (p.images && p.images[0]) || 'logo.jpg';
                const oldPrice = p.old_price || 0;
                const isCustomizable = p.customizable ? '<br><small style="color: var(--primary);">🎨 قابل للتخصيص</small>' : '';
                
                return `
                <div class="product-card-mobile">
                    <div class="product-card-header">
                        <img src="../images/${img}" alt="" onerror="this.src='../images/logo.jpg'">
                        <div class="product-card-info">
                            <h4>${p.name}</h4>
                            <span class="category">${p.category_name || '-'}</span>
                            ${isCustomizable}
                        </div>
                    </div>
                    <div class="product-card-body">
                        <div class="stock-control-mobile" data-product-id="${p.id}">
                            <span class="stock-label">📦 المخزون:</span>
                            <button type="button" class="stock-btn stock-minus">−</button>
                            <span class="stock-value" id="stock-mobile-${p.id}">${p.stock || 0}</span>
                            <button type="button" class="stock-btn stock-plus">+</button>
                        </div>
                        <span class="product-card-price">${formatPriceWithDiscount(p.price, oldPrice)}</span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="${csrf}">
                            <input type="hidden" name="product_id" value="${p.id}">
                            <input type="hidden" name="toggle_status" value="1">
                            <select name="new_status" onchange="this.form.submit()" class="form-control" style="width: auto; padding: 5px 10px; font-size: 0.85rem;">
                                <option value="available" ${p.status === 'available' ? 'selected' : ''}>✓ متوفر</option>
                                <option value="sold" ${p.status === 'sold' ? 'selected' : ''}>✗ نفذ</option>
                                <option value="hidden" ${p.status === 'hidden' ? 'selected' : ''}>👁 مخفي</option>
                            </select>
                        </form>
                        <div class="product-card-actions">
                            <a href="products?action=edit&id=${p.id}" class="action-btn action-btn-edit" title="تعديل">✏️</a>
                            <button type="button" class="action-btn action-btn-delete" 
                               title="حذف"
                               data-delete-type="product"
                               data-delete-id="${p.id}"
                               data-delete-name="${escapeHtml(p.name)}"
                               data-delete-token="${csrf}">🗑️</button>
                        </div>
                    </div>
                </div>
                `;
            }).join('');
        }
    }
    
    function formatPrice(price) {
        return new Intl.NumberFormat('en-US').format(price) + ' د.ع';
    }
    
    // Event listeners
    if (productSearch) {
        productSearch.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(doProductSearch, 300);
        });
    }
    
    if (productCategory) productCategory.addEventListener('change', doProductSearch);
    if (productStatus) productStatus.addEventListener('change', doProductSearch);
    
    if (clearProductFilters) {
        clearProductFilters.addEventListener('click', () => {
            if (productSearch) productSearch.value = '';
            if (productCategory) productCategory.value = '';
            if (productStatus) productStatus.value = '';
            doProductSearch();
        });
    }
    <?php endif; ?>
    
    <?php if ($mode !== 'list'): ?>
    // Discount Preview Calculations
    function updateDiscountPreview() {
        const currentPrice = parseInt(document.getElementById('currentPrice')?.value) || 0;
        const oldPrice = parseInt(document.getElementById('oldPrice')?.value) || 0;
        const preview = document.getElementById('discountPreview');
        
        if (oldPrice > currentPrice && currentPrice > 0) {
            const discount = Math.round(((oldPrice - currentPrice) / oldPrice) * 100);
            document.getElementById('previewOldPrice').textContent = formatPrice(oldPrice);
            document.getElementById('previewNewPrice').textContent = formatPrice(currentPrice);
            document.getElementById('previewDiscount').textContent = '-' + discount + '%';
            preview.style.display = 'block';
        } else {
            preview.style.display = 'none';
        }
    }
    
    function formatPrice(price) {
        return new Intl.NumberFormat('en-US').format(price) + ' د.ع';
    }
    
    document.getElementById('currentPrice')?.addEventListener('input', updateDiscountPreview);
    document.getElementById('oldPrice')?.addEventListener('input', updateDiscountPreview);
    
    // Initial check on page load
    updateDiscountPreview();
    
    // ============================================
    // Image Upload Management
    // ============================================
    const existingImagesCount = <?= $editProduct ? count(isset($editProduct['images']) ? $editProduct['images'] : []) : 0 ?>;
    let uploadRowsCount = 1;
    const maxImages = 4;
    
    // Handle file selection and preview
    function handleFileSelect(input) {
        const file = input.files[0];
        const row = input.closest('.image-upload-row');
        const thumb = row.querySelector('.image-thumb');
        const fileName = row.querySelector('.file-name');
        const uploadBtn = row.querySelector('.file-upload-btn');
        
        if (file) {
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                thumb.querySelector('img').src = e.target.result;
                thumb.style.display = 'block';
            };
            reader.readAsDataURL(file);
            
            // Update button text
            fileName.textContent = file.name.length > 20 ? file.name.substring(0, 20) + '...' : file.name;
            fileName.style.color = 'var(--primary)';
            uploadBtn.style.borderColor = 'var(--primary)';
            uploadBtn.style.background = 'linear-gradient(135deg, #fff0f8 0%, #ffe0f0 100%)';
        }
    }
    
    // Add event listener to initial input
    document.querySelectorAll('.image-input').forEach(input => {
        input.addEventListener('change', function() {
            handleFileSelect(this);
        });
    });
    
    // NOTE: removed manual click handler here
    // The label already contains input[type=file], browser handles click natively
    // Adding manual .click() was causing double file picker opening
    
    // Add more images button
    document.getElementById('addMoreImages')?.addEventListener('click', function() {
        const totalImages = existingImagesCount + uploadRowsCount;
        
        if (totalImages >= maxImages) {
            showNotification('الحد الأقصى هو 4 صور للمنتج الواحد', 'error');
            return;
        }
        
        uploadRowsCount++;
        
        const container = document.getElementById('imageUploadContainer');
        const newRow = document.createElement('div');
        newRow.className = 'image-upload-row';
        newRow.style.cssText = 'display: flex; gap: 10px; align-items: center; margin-bottom: 10px;';
        
        newRow.innerHTML = `
            <label class="custom-file-upload" style="flex: 1;">
                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" class="image-input" style="display: none;">
                <div class="file-upload-btn" style="display: flex; align-items: center; justify-content: center; gap: 10px; padding: 15px 20px; border: 2px dashed rgba(233, 30, 140, 0.3); border-radius: var(--radius-lg); cursor: pointer; transition: all 0.3s ease; background: linear-gradient(135deg, #fff 0%, #fafafa 100%);">
                    <span style="font-size: 1.5rem;">📷</span>
                    <span class="file-name" style="font-weight: 600; color: var(--text-muted);">اختر صورة</span>
                </div>
            </label>
            <div class="image-thumb" style="width: 60px; height: 60px; border-radius: 8px; overflow: hidden; display: none; border: 2px solid var(--primary);">
                <img src="" alt="" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
            <button type="button" class="remove-image-btn" style="width: 40px; height: 40px; border-radius: 50%; background: #ff5252; color: white; border: none; cursor: pointer; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;" title="إزالة">✕</button>
        `;
        
        container.appendChild(newRow);
        
        // Add event listeners to new row
        const newInput = newRow.querySelector('.image-input');
        newInput.addEventListener('change', function() {
            handleFileSelect(this);
        });
        
        // NOTE: no need for click listener - label with input inside works natively
        
        const removeBtn = newRow.querySelector('.remove-image-btn');
        removeBtn.addEventListener('click', function() {
            newRow.remove();
            uploadRowsCount--;
            updateAddMoreButton();
        });
        
        updateAddMoreButton();
    });
    
    function updateAddMoreButton() {
        const totalImages = existingImagesCount + uploadRowsCount;
        const addBtn = document.getElementById('addMoreImages');
        
        if (totalImages >= maxImages) {
            addBtn.style.display = 'none';
        } else {
            addBtn.style.display = 'block';
            addBtn.textContent = `➕ إضافة صورة أخرى (${totalImages}/${maxImages})`;
        }
    }
    
    // Initial update
    updateAddMoreButton();
    
    // ============================================
    // Delete Existing Images (Mark for deletion - saves on form submit)
    // ============================================
    window.deletedImages = [];
    var deletedInput = document.getElementById('deletedImagesInput');
    var totalImagesOriginal = <?= $editProduct ? count(isset($editProduct['images']) ? $editProduct['images'] : []) : 0 ?>;
    var imagesContainer = document.getElementById('existingImagesContainer');
    
    // Use document-level delegation to be PJAX-safe
    // But check if we already added this type of listener to避免 duplicates
    if (!window.productImageManagerInit) {
        window.productImageManagerInit = true;
        
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-image-btn');
            const restoreBtn = e.target.closest('.restore-btn');
            
            if (deleteBtn) {
                console.log('Delete button clicked, index:', deleteBtn.dataset.imageIndex);
                const imageIndex = parseInt(deleteBtn.dataset.imageIndex);
                const imageItem = document.getElementById(`img-item-${imageIndex}`);
                const deletedInput = document.getElementById('deletedImagesInput');
                
                if (!imageItem || !deletedInput) {
                    console.error('Image item or hidden input not found');
                    return;
                }
                
                // Visible images check
                const visibleImages = document.querySelectorAll('.image-preview-item:not(.deleted)');
                if (visibleImages.length <= 1) {
                    showNotification('يجب أن يحتوي المنتج على صورة واحدة على الأقل', 'error');
                    return;
                }
                
                imageItem.classList.add('deleted');
                imageItem.style.opacity = '0.3';
                imageItem.style.transform = 'scale(0.9)';
                imageItem.style.filter = 'grayscale(1)';
                
                deleteBtn.style.background = '#4caf50';
                deleteBtn.textContent = '↩️';
                deleteBtn.title = 'استرجاع الصورة';
                deleteBtn.classList.add('restore-btn');
                deleteBtn.classList.remove('delete-image-btn');
                
                const overlay = document.createElement('div');
                overlay.className = 'delete-overlay';
                overlay.innerHTML = '<span style="font-size: 1.5rem;">🗑️</span>';
                overlay.style.cssText = 'position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; border-radius: 8px; pointer-events: none; z-index: 10;';
                imageItem.appendChild(overlay);
                
                if (!window.deletedImages.includes(imageIndex)) {
                    window.deletedImages.push(imageIndex);
                }
                if (deletedInput) deletedInput.value = window.deletedImages.join(',');
                
                updateDeletedCount();
                updateAddMoreButton();
            }
            
            if (restoreBtn) {
                const imageItem = restoreBtn.closest('.image-preview-item');
                const imageIndex = parseInt(restoreBtn.dataset.imageIndex);
                const deletedInput = document.getElementById('deletedImagesInput');

                imageItem.classList.remove('deleted');
                imageItem.style.opacity = '1';
                imageItem.style.transform = 'scale(1)';
                imageItem.style.filter = 'none';
                
                const overlay = imageItem.querySelector('.delete-overlay');
                if (overlay) overlay.remove();
                
                restoreBtn.style.background = '#ff4444';
                restoreBtn.textContent = '✕';
                restoreBtn.title = 'حذف الصورة';
                restoreBtn.classList.remove('restore-btn');
                restoreBtn.classList.add('delete-image-btn');
                
                window.deletedImages = window.deletedImages.filter(i => i !== imageIndex);
                if (deletedInput) deletedInput.value = window.deletedImages.join(',');
                
                updateDeletedCount();
                updateAddMoreButton();
            }
        });

        // Hover effect duplication prevention
        document.addEventListener('mouseover', function(e) {
            const btn = e.target.closest('.delete-image-btn');
            if (btn) {
                btn.style.transform = 'scale(1.1)';
                btn.style.background = '#ff0000';
            }
        });
        
        document.addEventListener('mouseout', function(e) {
            const btn = e.target.closest('.delete-image-btn');
            if (btn) {
                btn.style.transform = 'scale(1)';
                btn.style.background = '#ff4444';
            }
        });
    }
    
    function updateDeletedCount() {
        const note = document.getElementById('deletedImagesNote');
        const countSpan = document.getElementById('deletedCount');
        const currentCount = document.getElementById('currentImageCount');
        
        if (window.deletedImages && window.deletedImages.length > 0) {
            if (note) note.style.display = 'inline';
            if (countSpan) countSpan.textContent = window.deletedImages.length;
        } else {
            if (note) note.style.display = 'none';
        }
        
        // Update current count (visible - deleted)
        const currentImagesInDOM = document.querySelectorAll('.image-preview-item:not(.deleted)').length;
        if (currentCount) {
            currentCount.textContent = currentImagesInDOM;
        }
    }
    <?php endif; ?>
    </script>
    
    <!-- Stock Management Script -->
    <script>
    // Stock Management Functions
    // Use event delegation for stock buttons to handle dynamic search results
    document.addEventListener('click', function(e) {
        const minusBtn = e.target.closest('.stock-minus');
        const plusBtn = e.target.closest('.stock-plus');
        
        if (minusBtn) {
            const control = minusBtn.closest('.stock-control, .stock-control-mobile');
            if (control) {
                const productId = control.dataset.productId;
                updateStock(productId, 'subtract', 1);
            }
        }
        
        if (plusBtn) {
            const control = plusBtn.closest('.stock-control, .stock-control-mobile');
            if (control) {
                const productId = control.dataset.productId;
                updateStock(productId, 'add', 1);
            }
        }
    });
    
    function updateStock(productId, action, amount) {
        // Find all stock value elements for this product
        const stockElements = document.querySelectorAll('#stock-' + productId + ', #stock-mobile-' + productId);
        const controls = document.querySelectorAll('[data-product-id="' + productId + '"]');
        
        // Add loading state
        controls.forEach(function(c) { c.classList.add('stock-updating'); });
        
        // Make AJAX request
        const formData = new FormData();
        formData.append('action', action);
        formData.append('product_id', productId);
        formData.append('amount', amount);
        
        fetch('../api/stock.php', {
            method: 'POST',
            body: formData
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            controls.forEach(function(c) { c.classList.remove('stock-updating'); });
            
            if (data.success) {
                // Update all stock displays for this product
                stockElements.forEach(function(el) {
                    el.textContent = data.stock;
                    
                    // Update styling based on stock level
                    el.classList.remove('low-stock', 'out-of-stock');
                    if (data.stock === 0) {
                        el.classList.add('out-of-stock');
                    } else if (data.stock <= 5) {
                        el.classList.add('low-stock');
                    }
                });
                
                // Show brief notification
                showStockNotification(data.message, 'success');
            } else {
                showStockNotification(data.error || 'حدث خطأ', 'error');
            }
        })
        .catch(function(error) {
            controls.forEach(function(c) { c.classList.remove('stock-updating'); });
            showStockNotification('خطأ في الاتصال', 'error');
            console.error('Stock update error:', error);
        });
    }
    
    function showStockNotification(message, type) {
        // Remove existing notifications
        const existing = document.querySelector('.stock-notification');
        if (existing) existing.remove();
        
        // Create notification
        const notification = document.createElement('div');
        notification.className = 'stock-notification ' + type;
        notification.textContent = message;
        notification.style.cssText = 'position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); padding: 12px 24px; border-radius: 8px; color: white; font-weight: 600; z-index: 9999; animation: slideUp 0.3s ease;';
        notification.style.background = type === 'success' ? '#4CAF50' : '#f44336';
        
        document.body.appendChild(notification);
        
        // Remove after 2 seconds
        setTimeout(function() {
            notification.style.opacity = '0';
            setTimeout(function() { notification.remove(); }, 300);
        }, 2000);
    }
    
    // Add animation keyframes
    const style = document.createElement('style');
    style.textContent = '@keyframes slideUp { from { opacity: 0; transform: translateX(-50%) translateY(20px); } to { opacity: 1; transform: translateX(-50%) translateY(0); } }';
    document.head.appendChild(style);
    
    // ═══════════════════════════════════════════════════════════════════
    // PRODUCT OPTIONS - Chips Management
    // ═══════════════════════════════════════════════════════════════════
    
    // Toggle option section visibility
    function toggleOptionSection(type) {
        const sectionMap = {
            'colors': 'colorsSection',
            'sizes': 'sizesSection',
            'ages': 'agesSection',
            'customText': 'customTextSection',
            'customImages': 'customImagesSection',
            'extraFields': 'extraFieldsSection',
            'giftCard': 'giftCardSection',
            'box': 'boxSection'
        };
        const checkboxMap = {
            'colors': 'optColorsEnabled',
            'sizes': 'optSizesEnabled',
            'ages': 'optAgesEnabled',
            'customText': 'optCustomTextEnabled',
            'customImages': 'optCustomImagesEnabled',
            'extraFields': 'optExtraFieldsEnabled',
            'giftCard': 'optGiftCardEnabled',
            'box': 'optBoxEnabled'
        };
        
        const section = document.getElementById(sectionMap[type]);
        const checkbox = document.getElementById(checkboxMap[type]);
        
        if (section && checkbox) {
            section.style.display = checkbox.checked ? 'block' : 'none';
        }
    }

    function addBoxOption() {
        const container = document.getElementById('boxOptionsContainer');
        if (!container) return;
        
        const idx = container.querySelectorAll('.box-option-item').length;
        const div = document.createElement('div');
        div.className = 'box-option-item';
        div.style.cssText = 'background: #fafafa; border: 1px solid #ddd; border-radius: 10px; padding: 15px; position: relative;';
        
        div.innerHTML = `
            <button type="button" onclick="this.closest('.box-option-item').remove()" style="position: absolute; top: 10px; left: 10px; background: #ff5252; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer;">&times;</button>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px; margin-bottom: 10px; padding-left: 30px;">
                <div>
                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">اسم الخيار (مثال: علبة فاخرة حمراء)</label>
                    <input type="text" name="box_options_data[${idx}][name]" class="form-control" required>
                </div>
                <div>
                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">الحجم (اختياري)</label>
                    <input type="text" name="box_options_data[${idx}][size]" class="form-control" placeholder="20x20">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                <div>
                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">زيادة السعر (IQD)</label>
                    <input type="number" name="box_options_data[${idx}][price]" value="0" class="form-control" min="0">
                </div>
                <div>
                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">الكمية المتوفرة</label>
                    <input type="number" name="box_options_data[${idx}][stock]" value="999" class="form-control" min="0">
                </div>
                <div style="grid-column: span 3; margin-top: 5px;">
                    <label style="font-size: 0.75rem; font-weight: 700; color: #666; display: block; margin-bottom: 4px;">وصف بسيط (يظهر تحت الاسم)</label>
                    <input type="text" name="box_options_data[${idx}][description]" class="form-control" placeholder="علبة مصنوعة من الكرتون المقوى بلمسة حريرية">
                </div>
            </div>
        `;
        
        container.appendChild(div);
    }
    
    // Add chip to a category
    function addChip(type) {
        const inputMap = {
            'colors': 'newColorInput',
            'sizes': 'newSizeInput',
            'ages': 'newAgeInput'
        };
        const containerMap = {
            'colors': 'colorsChips',
            'sizes': 'sizesChips',
            'ages': 'agesChips'
        };
        const hiddenMap = {
            'colors': 'colorsValuesInput',
            'sizes': 'sizesValuesInput',
            'ages': 'agesValuesInput'
        };
        const colorMap = {
            'colors': 'linear-gradient(135deg, #e91e8c, #c2185b)',
            'sizes': 'linear-gradient(135deg, #2196f3, #1976d2)',
            'ages': 'linear-gradient(135deg, #4caf50, #388e3c)'
        };
        
        const input = document.getElementById(inputMap[type]);
        const container = document.getElementById(containerMap[type]);
        const hidden = document.getElementById(hiddenMap[type]);
        
        if (!input || !container || !hidden) return;
        
        const value = input.value.trim();
        if (!value) return;
        
        // Get current values
        let values = [];
        try {
            values = JSON.parse(hidden.value || '[]');
        } catch (e) {
            values = [];
        }
        
        // Check duplicate
        if (values.includes(value)) {
            showNotification('هذه القيمة موجودة مسبقاً', 'error');
            return;
        }
        
        // Add to array
        values.push(value);
        hidden.value = JSON.stringify(values);
        
        // Create chip element
        const chip = document.createElement('span');
        chip.className = 'chip';
        chip.style.cssText = `background: ${colorMap[type]}; color: white; padding: 6px 14px; border-radius: 20px; font-size: 0.85rem; display: flex; align-items: center; gap: 8px;`;
        chip.innerHTML = `
            ${escapeHtml(value)}
            <button type="button" onclick="removeChip(this, '${type}')" style="background: rgba(255,255,255,0.3); border: none; color: white; width: 18px; height: 18px; border-radius: 50%; cursor: pointer; font-size: 12px; line-height: 1;">×</button>
        `;
        container.appendChild(chip);
        
        // Clear input
        input.value = '';
        input.focus();
    }
    
    // Remove chip
    function removeChip(button, type) {
        const chip = button.parentElement;
        const hiddenMap = {
            'colors': 'colorsValuesInput',
            'sizes': 'sizesValuesInput',
            'ages': 'agesValuesInput'
        };
        
        const hidden = document.getElementById(hiddenMap[type]);
        if (!hidden) return;
        
        // Get text content (without the button)
        const value = chip.childNodes[0].textContent.trim();
        
        // Remove from array
        let values = [];
        try {
            values = JSON.parse(hidden.value || '[]');
        } catch (e) {
            values = [];
        }
        
        const index = values.indexOf(value);
        if (index > -1) {
            values.splice(index, 1);
            hidden.value = JSON.stringify(values);
        }
        
        // Remove chip element
        chip.remove();
    }
    
    // Escape HTML helper
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Make functions globally available
    window.toggleOptionSection = toggleOptionSection;
    window.addChip = addChip;
    window.removeChip = removeChip;
    
    // ═══════════════════════════════════════════════════════════════════
    // PACKAGING - التحكم في إظهار/إخفاء قسم التغليف
    // ═══════════════════════════════════════════════════════════════════
    function togglePackagingSection(checkbox) {
        const packagingDetails = document.getElementById('packagingDetails');
        const packagingBlock = document.getElementById('packagingOptionBlock');
        const packagingPriceInput = document.getElementById('packagingPrice');
        
        if (checkbox.checked) {
            if (packagingDetails) packagingDetails.style.display = 'block';
            if (packagingBlock) {
                packagingBlock.style.borderColor = '#9c27b0';
                packagingBlock.style.background = 'linear-gradient(135deg, rgba(156, 39, 176, 0.12), rgba(233, 30, 99, 0.08))';
            }
            // التركيز على حقل السعر
            if (packagingPriceInput) {
                setTimeout(() => packagingPriceInput.focus(), 100);
            }
        } else {
            if (packagingDetails) packagingDetails.style.display = 'none';
            if (packagingBlock) {
                packagingBlock.style.borderColor = '#e0e0e0';
                packagingBlock.style.background = 'linear-gradient(135deg, rgba(156, 39, 176, 0.08), rgba(233, 30, 99, 0.08))';
            }
        }
    }
    window.togglePackagingSection = togglePackagingSection;
    
    // تحديث تنسيق checkbox الأحجام عند التحديد
    function updateSizeCheckboxStyle(checkbox) {
        const label = checkbox.closest('.size-checkbox-label');
        if (!label) return;
        
        if (checkbox.checked) {
            label.style.borderColor = '#2196f3';
            label.style.background = 'linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(25, 118, 210, 0.1))';
            label.querySelector('span').style.color = '#1976d2';
        } else {
            label.style.borderColor = '#e0e0e0';
            label.style.background = 'white';
            label.querySelector('span').style.color = '#555';
        }
    }
    window.updateSizeCheckboxStyle = updateSizeCheckboxStyle;
    
    // ═══════════════════════════════════════════════════════════════════
    // ربط صارم: "قابل للتخصيص (الطباعة)" شرط أساسي لـ "رفع الصور المتعددة"
    // ═══════════════════════════════════════════════════════════════════
    
    // عند تغيير حالة الطباعة
    function handleCustomizableChange(checkbox) {
        const customImagesCheckbox = document.getElementById('optCustomImagesEnabled');
        const customImagesSection = document.getElementById('customImagesSection');
        const customImagesBlock = document.getElementById('customImagesOptionBlock');
        const customImagesLockedNote = document.getElementById('customImagesLockedNote');
        
        if (!customImagesCheckbox) return;
        
        if (!checkbox.checked) {
            // ⛔ إذا تم إطفاء الطباعة: أطفئ رفع الصور فوراً
            customImagesCheckbox.checked = false;
            if (customImagesSection) customImagesSection.style.display = 'none';
            if (customImagesLockedNote) customImagesLockedNote.style.display = 'none';
            if (customImagesBlock) {
                customImagesBlock.style.borderColor = '#e0e0e0';
                customImagesBlock.style.background = 'white';
                customImagesBlock.style.opacity = '0.7'; // تعتيم بسيط للإشارة لعدم التوفر
            }
        } else {
            // ✅ إذا تم تفعيل الطباعة: اجعل خيار رفع الصور متاحاً (لكن لا تفعله تلقائياً إلا إذا أراد المستخدِم)
            if (customImagesBlock) {
                customImagesBlock.style.opacity = '1';
                customImagesBlock.style.borderColor = '';
                customImagesBlock.style.background = 'white';
            }
        }
    }
    
    // عند محاولة تغيير حالة رفع الصور
    function handleCustomImagesToggle(checkbox) {
        const customizableCheckbox = document.getElementById('customizableCheckbox');
        const customImagesSection = document.getElementById('customImagesSection');
        
        // 🚨 الشرط الصارم: لا تفعيل بدون طباعة
        if (checkbox.checked && (!customizableCheckbox || !customizableCheckbox.checked)) {
            checkbox.checked = false; // أعد الإطفاء فوراً
            showNotification('لا يمكن تفعيل رفع صور التخصيص إلا بعد تفعيل قابل للتخصيص (الطباعة).', 'error');
            return;
        }
        
        // التبديل العادي للـ section
        if (customImagesSection) {
            customImagesSection.style.display = checkbox.checked ? 'block' : 'none';
        }
    }
    
    // عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        const customizableCheckbox = document.getElementById('customizableCheckbox');
        const customImagesCheckbox = document.getElementById('optCustomImagesEnabled');
        const customImagesBlock = document.getElementById('customImagesOptionBlock');
        
        if (customizableCheckbox && !customizableCheckbox.checked) {
            if (customImagesBlock) customImagesBlock.style.opacity = '0.7';
            if (customImagesCheckbox) customImagesCheckbox.checked = false;
        }
    });
    
    window.handleCustomizableChange = handleCustomizableChange;
    window.handleCustomImagesToggle = handleCustomImagesToggle;
    
    // ═══════════════════════════════════════════════════════════════════
    // دوال إدارة المجموعات الجديدة
    // ═══════════════════════════════════════════════════════════════════
    
    // عدادات المجموعات
    let colorsGroupCounter = document.querySelectorAll('#colorsGroupsContainer .option-group-card').length;
    let sizesGroupCounter = document.querySelectorAll('#sizesGroupsContainer .option-group-card').length;
    
    // إضافة مجموعة ألوان جديدة
    function addOptionGroup(type) {
        const container = document.getElementById(type + 'GroupsContainer');
        if (!container) return;
        
        const counter = type === 'colors' ? colorsGroupCounter++ : sizesGroupCounter++;
        const bgColor = type === 'colors' ? '#e91e8c' : '#2196f3';
        
        const groupHtml = `
        <div class="option-group-card" data-group-index="${counter}" style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <input type="text" name="${type}_groups[${counter}][label]" value="" placeholder="وصف المجموعة (مثال: ${type === 'colors' ? 'لون الإطار' : 'حجم التيشيرت'})" class="form-control" style="flex: 1; font-weight: 600;">
                <button type="button" onclick="removeOptionGroup(this, '${type}')" class="btn btn-outline" style="color: #f44336; border-color: #f44336; padding: 8px 12px;">🗑️</button>
            </div>
            <div class="group-items" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 10px;"></div>
            <div style="display: flex; gap: 8px;">
                <input type="text" class="new-item-input form-control" placeholder="أدخل ${type === 'colors' ? 'لون' : 'قيمة'} جديد" style="flex: 1;" onkeypress="if(event.key==='Enter'){event.preventDefault();addGroupItem(this,'${type}',${counter});}">
                <input type="number" class="new-item-qty form-control" placeholder="الكمية" value="999" min="0" style="width: 80px;">
                <button type="button" onclick="addGroupItem(this,'${type}',${counter})" class="btn btn-outline" style="white-space: nowrap;">+ إضافة</button>
            </div>
        </div>`;
        
        container.insertAdjacentHTML('beforeend', groupHtml);
    }
    
    // إضافة مجموعة أحجام جديدة
    function addSizesGroup() {
        const container = document.getElementById('sizesGroupsContainer');
        if (!container) return;
        
        const counter = sizesGroupCounter++;
        const fixedSizes = ['XXS', 'XS', 'S', 'M', 'L', 'XL', 'XXL (2XL)', 'XXXL (3XL)', '4XL', '5XL', '6XL'];
        
        let sizesHtml = fixedSizes.map(size => `
            <label class="size-checkbox-label" style="display: flex; align-items: center; gap: 6px; padding: 8px 10px; border: 2px solid #e0e0e0; border-radius: 10px; background: white; cursor: pointer;">
                <input type="checkbox" class="size-check" data-size="${size}" style="width: 16px; height: 16px; accent-color: #2196f3;" onchange="toggleSizeItem(this, ${counter})">
                <span style="font-weight: 600; font-size: 0.85rem; flex: 1;">${size}</span>
                <input type="number" class="size-qty" value="999" min="0" style="width: 45px; padding: 2px 4px; border: 1px solid #ddd; border-radius: 6px; text-align: center; font-size: 0.75rem; opacity: 0.5;" disabled title="الكمية">
            </label>
        `).join('');
        
        const groupHtml = `
        <div class="option-group-card" data-group-index="${counter}" style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <input type="text" name="sizes_groups[${counter}][label]" value="" placeholder="وصف المجموعة (مثال: حجم التيشيرت)" class="form-control" style="flex: 1; font-weight: 600;">
                <button type="button" onclick="removeOptionGroup(this, 'sizes')" class="btn btn-outline" style="color: #f44336; border-color: #f44336; padding: 8px 12px;">🗑️</button>
            </div>
            <p style="font-size: 0.8rem; color: #666; margin-bottom: 10px;">اختر الأحجام المتاحة وحدد الكمية لكل حجم:</p>
            <div class="group-items sizes-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 8px; margin-bottom: 10px;">
                ${sizesHtml}
            </div>
            <div class="sizes-hidden-inputs"></div>
        </div>`;
        
        container.insertAdjacentHTML('beforeend', groupHtml);
    }
    
    
    // حذف مجموعة - بنافذة تأكيد مخصصة
    function removeOptionGroup(btn, type) {
        showConfirmDialog('حذف المجموعة', 'هل تريد حذف هذه المجموعة وجميع عناصرها؟', function() {
            btn.closest('.option-group-card').remove();
        });
    }
    
    // إضافة عنصر للمجموعة (للألوان)
    function addGroupItem(btn, type, groupIndex) {
        const card = btn.closest('.option-group-card');
        const input = card.querySelector('.new-item-input');
        const qtyInput = card.querySelector('.new-item-qty');
        const itemsContainer = card.querySelector('.group-items');
        
        const value = input.value.trim();
        const qty = parseInt(qtyInput.value) || 999;
        
        if (!value) return;
        
        // عدد العناصر الحالية
        const itemCount = itemsContainer.querySelectorAll('.color-item-chip').length;
        
        const bgColor = type === 'colors' ? 'linear-gradient(135deg, #e91e8c, #c2185b)' : 'linear-gradient(135deg, #2196f3, #1976d2)';
        
        const chipHtml = `
        <div class="color-item-chip" style="display: flex; align-items: center; gap: 5px; background: ${bgColor}; color: white; padding: 6px 12px; border-radius: 20px; font-size: 0.85rem;">
            <span>${escapeHtml(value)}</span>
            <input type="hidden" name="${type}_groups[${groupIndex}][items][${itemCount}][name]" value="${escapeHtml(value)}">
            <input type="number" name="${type}_groups[${groupIndex}][items][${itemCount}][qty]" value="${qty}" min="0" style="width: 50px; padding: 2px 5px; border: none; border-radius: 8px; text-align: center; font-size: 0.75rem;" title="الكمية المتاحة">
            <button type="button" onclick="removeGroupItem(this)" style="background: rgba(255,255,255,0.3); border: none; color: white; width: 18px; height: 18px; border-radius: 50%; cursor: pointer; font-size: 12px;">×</button>
        </div>`;
        
        itemsContainer.insertAdjacentHTML('beforeend', chipHtml);
        input.value = '';
        qtyInput.value = '999';
        input.focus();
    }
    
    // حذف عنصر من المجموعة - بنافذة تأكيد مخصصة
    function removeGroupItem(btn) {
        showConfirmDialog('حذف العنصر', 'هل تريد حذف هذا العنصر؟', function() {
            btn.closest('.color-item-chip, .size-item-chip, .extra-field-item').remove();
        });
    }
    
    // تبديل حالة الحجم مع تحديث الكمية
    function toggleSizeItem(checkbox, groupIndex) {
        const label = checkbox.closest('.size-checkbox-label');
        const qtyInput = label.querySelector('.size-qty');
        const size = checkbox.dataset.size;
        const card = checkbox.closest('.option-group-card');
        const hiddenContainer = card.querySelector('.sizes-hidden-inputs');
        
        if (checkbox.checked) {
            // تفعيل حقل الكمية
            qtyInput.disabled = false;
            qtyInput.style.opacity = '1';
            label.style.borderColor = '#2196f3';
            label.style.background = 'linear-gradient(135deg, rgba(33, 150, 243, 0.1), rgba(25, 118, 210, 0.1))';
            
            // إضافة hidden inputs
            const itemCount = hiddenContainer.querySelectorAll('input[type=hidden]').length / 2;
            hiddenContainer.insertAdjacentHTML('beforeend', `
                <input type="hidden" class="size-hidden-${size.replace(/[^a-zA-Z0-9]/g, '')}" name="sizes_groups[${groupIndex}][items][${itemCount}][name]" value="${size}">
                <input type="hidden" class="size-hidden-qty-${size.replace(/[^a-zA-Z0-9]/g, '')}" name="sizes_groups[${groupIndex}][items][${itemCount}][qty]" value="${qtyInput.value}">
            `);
            
            // تحديث الكمية عند التغيير
            qtyInput.onchange = function() {
                const qtyHidden = hiddenContainer.querySelector('.size-hidden-qty-' + size.replace(/[^a-zA-Z0-9]/g, ''));
                if (qtyHidden) qtyHidden.value = this.value;
            };
        } else {
            // تعطيل حقل الكمية
            qtyInput.disabled = true;
            qtyInput.style.opacity = '0.5';
            label.style.borderColor = '#e0e0e0';
            label.style.background = 'white';
            
            // حذف hidden inputs
            const nameHidden = hiddenContainer.querySelector('.size-hidden-' + size.replace(/[^a-zA-Z0-9]/g, ''));
            const qtyHidden = hiddenContainer.querySelector('.size-hidden-qty-' + size.replace(/[^a-zA-Z0-9]/g, ''));
            if (nameHidden) nameHidden.remove();
            if (qtyHidden) qtyHidden.remove();
        }
    }
    
    // تهيئة الأحجام الموجودة عند تحميل الصفحة
    document.addEventListener('DOMContentLoaded', function() {
        // تهيئة hidden inputs للأحجام الموجودة
        document.querySelectorAll('#sizesGroupsContainer .option-group-card').forEach((card, groupIndex) => {
            const hiddenContainer = card.querySelector('.sizes-hidden-inputs');
            if (!hiddenContainer) return;
            
            card.querySelectorAll('.size-check:checked').forEach((checkbox, itemIndex) => {
                const size = checkbox.dataset.size;
                const qtyInput = checkbox.closest('.size-checkbox-label').querySelector('.size-qty');
                const qty = qtyInput ? qtyInput.value : 999;
                
                hiddenContainer.insertAdjacentHTML('beforeend', `
                    <input type="hidden" class="size-hidden-${size.replace(/[^a-zA-Z0-9]/g, '')}" name="sizes_groups[${groupIndex}][items][${itemIndex}][name]" value="${size}">
                    <input type="hidden" class="size-hidden-qty-${size.replace(/[^a-zA-Z0-9]/g, '')}" name="sizes_groups[${groupIndex}][items][${itemIndex}][qty]" value="${qty}">
                `);
                
                // ربط تحديث الكمية
                if (qtyInput) {
                    qtyInput.onchange = function() {
                        const qtyHidden = hiddenContainer.querySelector('.size-hidden-qty-' + size.replace(/[^a-zA-Z0-9]/g, ''));
                        if (qtyHidden) qtyHidden.value = this.value;
                    };
                }
            });
        });
    });
    
    // ═══════════════════════════════════════════════════════════════════
    // نافذة تأكيد مخصصة بنفس هوية الموقع
    // ═══════════════════════════════════════════════════════════════════
    let confirmCallback = null;
    
    function showConfirmDialog(title, message, onConfirm) {
        confirmCallback = onConfirm;
        
        // إنشاء النافذة إذا لم تكن موجودة
        if (!document.getElementById('customConfirmDialog')) {
            const dialogHtml = `
            <div id="customConfirmDialog" class="confirm-dialog-overlay">
                <div class="confirm-dialog">
                    <div class="confirm-dialog-icon">⚠️</div>
                    <h3 class="confirm-dialog-title">تأكيد</h3>
                    <p class="confirm-dialog-message">هل أنت متأكد؟</p>
                    <div class="confirm-dialog-actions">
                        <button type="button" class="confirm-dialog-btn confirm-cancel" onclick="closeConfirmDialog()">إلغاء</button>
                        <button type="button" class="confirm-dialog-btn confirm-ok" onclick="executeConfirm()">تأكيد</button>
                    </div>
                </div>
            </div>
            <style>
                .confirm-dialog-overlay {
                    display: none;
                    position: fixed;
                    top: 0; left: 0; right: 0; bottom: 0;
                    background: rgba(0, 0, 0, 0.6);
                    z-index: 10000;
                    align-items: center;
                    justify-content: center;
                    animation: fadeIn 0.2s ease;
                }
                .confirm-dialog-overlay.active { display: flex; }
                .confirm-dialog {
                    background: white;
                    border-radius: 20px;
                    padding: 30px;
                    max-width: 400px;
                    width: 90%;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                    animation: scaleIn 0.2s ease;
                }
                @keyframes scaleIn { from { transform: scale(0.9); opacity: 0; } to { transform: scale(1); opacity: 1; } }
                .confirm-dialog-icon { font-size: 3rem; margin-bottom: 15px; }
                .confirm-dialog-title { font-size: 1.3rem; font-weight: 700; color: #333; margin-bottom: 10px; }
                .confirm-dialog-message { color: #666; font-size: 0.95rem; margin-bottom: 25px; line-height: 1.6; }
                .confirm-dialog-actions { display: flex; gap: 12px; justify-content: center; }
                .confirm-dialog-btn {
                    padding: 12px 30px;
                    border-radius: 12px;
                    font-weight: 700;
                    font-size: 0.95rem;
                    cursor: pointer;
                    border: none;
                    transition: all 0.2s ease;
                }
                .confirm-cancel {
                    background: #f0f0f0;
                    color: #666;
                }
                .confirm-cancel:hover { background: #e0e0e0; }
                .confirm-ok {
                    background: linear-gradient(135deg, #E91E8C, #FF6B9D);
                    color: white;
                }
                .confirm-ok:hover { transform: scale(1.05); box-shadow: 0 4px 15px rgba(233, 30, 140, 0.4); }
            </style>`;
            document.body.insertAdjacentHTML('beforeend', dialogHtml);
        }
        
        const dialog = document.getElementById('customConfirmDialog');
        dialog.querySelector('.confirm-dialog-title').textContent = title;
        dialog.querySelector('.confirm-dialog-message').textContent = message;
        dialog.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeConfirmDialog() {
        const dialog = document.getElementById('customConfirmDialog');
        if (dialog) {
            dialog.classList.remove('active');
            document.body.style.overflow = '';
        }
        confirmCallback = null;
    }
    
    function executeConfirm() {
        if (confirmCallback) confirmCallback();
        closeConfirmDialog();
    }
    
    // ═══════════════════════════════════════════════════════════════════
    // نظام الحقول الإضافية الموحد (يدمج النص المخصص والفئات العمرية)
    // ═══════════════════════════════════════════════════════════════════
    let extraFieldsGroupCounter = document.querySelectorAll('#extraFieldsGroupsContainer .option-group-card').length || 0;
    
    function addExtraFieldsGroup() {
        const container = document.getElementById('extraFieldsGroupsContainer');
        if (!container) return;
        
        const counter = extraFieldsGroupCounter++;
        
        const groupHtml = `
        <div class="option-group-card extra-fields-group" data-group-index="${counter}" style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <input type="text" name="extra_fields_groups[${counter}][label]" value="" placeholder="وصف المجموعة للزبون (مثال: معلومات الهدية)" class="form-control" style="flex: 1; font-weight: 600;">
                <button type="button" onclick="removeOptionGroup(this, 'extra_fields')" class="btn btn-outline" style="color: #f44336; border-color: #f44336; padding: 8px 12px;">🗑️</button>
            </div>
            <div class="extra-fields-items" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 12px;"></div>
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <button type="button" onclick="addExtraFieldItem(this, ${counter}, 'text')" class="btn btn-outline" style="font-size: 0.85rem;">
                    ✏️ + حقل نصي
                </button>
                <button type="button" onclick="addExtraFieldItem(this, ${counter}, 'select')" class="btn btn-outline" style="font-size: 0.85rem;">
                    📋 + قائمة اختيارات
                </button>
            </div>
        </div>`;
        
        container.insertAdjacentHTML('beforeend', groupHtml);
    }
    
    function addExtraFieldItem(btn, groupIndex, fieldType) {
        const card = btn.closest('.option-group-card');
        const itemsContainer = card.querySelector('.extra-fields-items');
        const itemCount = itemsContainer.querySelectorAll('.extra-field-item').length;
        
        let itemHtml = '';
        
        if (fieldType === 'text') {
            itemHtml = `
            <div class="extra-field-item" data-field-type="text" style="background: linear-gradient(135deg, rgba(255, 152, 0, 0.1), rgba(255, 193, 7, 0.08)); border: 1px solid #ffb74d; border-radius: 10px; padding: 12px;">
                <input type="hidden" name="extra_fields_groups[${groupIndex}][items][${itemCount}][type]" value="text">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="font-size: 1.1rem;">✏️</span>
                    <span style="font-weight: 600; font-size: 0.85rem; color: #e65100;">حقل نصي</span>
                    <button type="button" onclick="removeGroupItem(this)" style="margin-right: auto; background: #f44336; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 14px;">×</button>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                    <input type="text" name="extra_fields_groups[${groupIndex}][items][${itemCount}][label]" placeholder="العنوان (مثال: اسم المهدى إليه)" class="form-control" style="font-size: 0.85rem;">
                    <input type="number" name="extra_fields_groups[${groupIndex}][items][${itemCount}][max_length]" placeholder="الحد الأقصى" value="50" min="5" max="200" class="form-control" style="font-size: 0.85rem;">
                </div>
                <input type="text" name="extra_fields_groups[${groupIndex}][items][${itemCount}][placeholder]" placeholder="نص توضيحي (اختياري)" class="form-control" style="font-size: 0.85rem; margin-top: 8px;">
                <label style="display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.8rem; color: #666;">
                    <input type="checkbox" name="extra_fields_groups[${groupIndex}][items][${itemCount}][required]" value="1"> إجباري
                </label>
            </div>`;
        } else {
            itemHtml = `
            <div class="extra-field-item" data-field-type="select" style="background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(129, 199, 132, 0.08)); border: 1px solid #81c784; border-radius: 10px; padding: 12px;">
                <input type="hidden" name="extra_fields_groups[${groupIndex}][items][${itemCount}][type]" value="select">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                    <span style="font-size: 1.1rem;">📋</span>
                    <span style="font-weight: 600; font-size: 0.85rem; color: #2e7d32;">قائمة اختيارات</span>
                    <button type="button" onclick="removeGroupItem(this)" style="margin-right: auto; background: #f44336; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 14px;">×</button>
                </div>
                <input type="text" name="extra_fields_groups[${groupIndex}][items][${itemCount}][label]" placeholder="العنوان (مثال: الفئة العمرية)" class="form-control" style="font-size: 0.85rem; margin-bottom: 8px;">
                <textarea name="extra_fields_groups[${groupIndex}][items][${itemCount}][options]" placeholder="اكتب الخيارات، كل خيار بسطر جديد (مثال: 0-2 سنوات | 3-5 سنوات | 6-10 سنوات)" class="form-control" rows="3" style="font-size: 0.85rem;"></textarea>
                <label style="display: flex; align-items: center; gap: 6px; margin-top: 8px; font-size: 0.8rem; color: #666;">
                    <input type="checkbox" name="extra_fields_groups[${groupIndex}][items][${itemCount}][required]" value="1"> إجباري
                </label>
            </div>`;
        }
        
        itemsContainer.insertAdjacentHTML('beforeend', itemHtml);
    }
    
    // ═══════════════════════════════════════════════════════════════════
    // نظام مجموعات الصور المتعددة
    // ═══════════════════════════════════════════════════════════════════
    let imagesGroupCounter = document.querySelectorAll('#customImagesGroupsContainer .option-group-card').length || 0;
    
    function addImagesGroup() {
        const container = document.getElementById('customImagesGroupsContainer');
        if (!container) return;
        
        const counter = imagesGroupCounter++;
        
        const groupHtml = `
        <div class="option-group-card images-group" data-group-index="${counter}" style="background: #fafafa; border: 1px solid #e0e0e0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 12px;">
                <input type="text" name="custom_images_groups[${counter}][label]" value="" placeholder="وصف المجموعة (مثال: صورة الوجه الأمامي)" class="form-control" style="flex: 1; font-weight: 600;">
                <button type="button" onclick="removeOptionGroup(this, 'images')" class="btn btn-outline" style="color: #f44336; border-color: #f44336; padding: 8px 12px;">🗑️</button>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px;">
                <div>
                    <label style="font-size: 0.75rem; color: #888; display: block; margin-bottom: 4px;">الحد الأدنى</label>
                    <input type="number" name="custom_images_groups[${counter}][min]" class="form-control" min="0" max="10" value="1" style="font-size: 0.85rem;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: #888; display: block; margin-bottom: 4px;">الحد الأقصى</label>
                    <input type="number" name="custom_images_groups[${counter}][max]" class="form-control" min="1" max="10" value="3" style="font-size: 0.85rem;">
                </div>
                <div>
                    <label style="font-size: 0.75rem; color: #888; display: block; margin-bottom: 4px;">الحجم (MB)</label>
                    <input type="number" name="custom_images_groups[${counter}][max_size]" class="form-control" min="1" max="20" value="5" style="font-size: 0.85rem;">
                </div>
            </div>
            <label style="display: flex; align-items: center; gap: 6px; margin-top: 10px; font-size: 0.85rem; color: #666;">
                <input type="checkbox" name="custom_images_groups[${counter}][required]" value="1"> إجباري
            </label>
        </div>`;
        
        container.insertAdjacentHTML('beforeend', groupHtml);
    }
    
    // تصدير الدوال
    window.showConfirmDialog = showConfirmDialog;
    window.closeConfirmDialog = closeConfirmDialog;
    window.executeConfirm = executeConfirm;
    window.addOptionGroup = addOptionGroup;
    window.addSizesGroup = addSizesGroup;
    window.removeOptionGroup = removeOptionGroup;
    window.addGroupItem = addGroupItem;
    window.removeGroupItem = removeGroupItem;
    window.toggleSizeItem = toggleSizeItem;
    window.addExtraFieldsGroup = addExtraFieldsGroup;
    window.addExtraFieldItem = addExtraFieldItem;
    window.addImagesGroup = addImagesGroup;
    // ═══════════════════════════════════════════════════════════════════
    // نظام بطاقات الرسائل المتعددة
    // ═══════════════════════════════════════════════════════════════════
    let giftCardGroupCounter = document.querySelectorAll('#giftCardsContainer .gift-card-item').length || 0;
    
    function addGiftCardBlock() {
        const container = document.getElementById('giftCardsContainer');
        if (!container) return;
        
        // Remove empty state note if exists
        const note = document.getElementById('noGiftCardsNote');
        if (note) note.remove();
        
        const counter = giftCardGroupCounter++;
        const cardId = 'card_' + Math.random().toString(36).substr(2, 9);
        
        const cardHtml = `
        <div class="gift-card-item" style="background: white; border: 1px solid #ddd; border-radius: 12px; padding: 18px; position: relative; box-shadow: 0 2px 5px rgba(0,0,0,0.05); animation: slideUp 0.3s ease;">
            <input type="hidden" name="gift_cards_data[${counter}][id]" value="${cardId}">
            
            <button type="button" onclick="this.closest('.gift-card-item').remove()" style="position: absolute; top: 10px; left: 10px; background: #ff5252; color: white; border: none; width: 28px; height: 28px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; font-size: 18px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">&times;</button>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px; padding-left: 35px;">
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700; color: #444; margin-bottom: 5px; display: block;">عنوان البطاقة (يظهر للزبون)</label>
                    <input type="text" name="gift_cards_data[${counter}][label]" value="" class="form-control" placeholder="مثال: بطاقة داخلية">
                </div>
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700; color: #444; margin-bottom: 5px; display: block;">الحد الأقصى للأحرف</label>
                    <input type="number" name="gift_cards_data[${counter}][max_length]" value="250" min="10" max="1000" class="form-control">
                </div>
            </div>
            
            <div style="margin-bottom: 15px;">
                <label style="font-size: 0.8rem; font-weight: 700; color: #444; margin-bottom: 5px; display: block;">نص مساعد للزبون</label>
                <input type="text" name="gift_cards_data[${counter}][helper]" value="" class="form-control" placeholder="مثال: اكتب رسالتك هنا...">
            </div>
            
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 15px;">
                <div style="flex: 1;">
                    <label style="font-size: 0.8rem; font-weight: 700; color: #444; margin-bottom: 5px; display: block;">نص توضيحي (Placeholder)</label>
                    <input type="text" name="gift_cards_data[${counter}][placeholder]" value="" class="form-control" placeholder="مثال: كل عام وأنت بخير...">
                </div>
                <div style="padding-top: 25px;">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; font-weight: 600; color: #d32f2f; font-size: 0.9rem;">
                        <input type="checkbox" name="gift_cards_data[${counter}][required]" value="1"> إلزامي
                    </label>
                </div>
            </div>
        </div>`;
        
        container.insertAdjacentHTML('beforeend', cardHtml);
    }
    
    window.addGiftCardBlock = addGiftCardBlock;
    </script>
    
    <script src="<?= av('js/api-helper.js') ?>"></script>
    <script src="<?= av('js/admin.js') ?>"></script>
</body>
</html>
