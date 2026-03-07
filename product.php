<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/functions.php'; ?>
<?php require_once 'includes/seo.php'; ?>

<?php
$id = isset($_GET['id']) ? $_GET['id'] : null;
$product = $id ? getProduct($id) : null;

if (!$product) {
    header('Location: /products');
    exit;
}
$settings = getSettings();

// SEO - عنوان ثابت دائماً + وصف المنتج
$pageTitle = SITE_BRAND_NAME;
$pageDesc = 'اطلب ' . $product['name'] . ' من ' . SITE_BRAND_NAME . ' - هدية مميزة وفخمة. ' . substr($product['description'], 0, 120) . ' طلب هدايا أونلاين مع توصيل داخل العراق.';
$productImage = !empty($product['images'][0]) ? 'https://surprise-iq.com/images/' . $product['images'][0] : 'https://surprise-iq.com/images/logo.jpg';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_BRAND_NAME ?></title>
    
    <!-- SEO Meta Tags -->
    <?= generateMetaTags(array(
        'title' => $pageTitle,
        'description' => $pageDesc,
        'image' => $productImage,
        'type' => 'product'
    )) ?>
    
    <link rel="stylesheet" href="<?= v('css/main.css') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="/images/logo.jpg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo.jpg">
    <link rel="shortcut icon" href="/images/logo.jpg">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo.jpg">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#E91E8C">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    

    <!-- Product Structured Data -->
    <?= generateProductSchema($product) ?>
    <?= generateBreadcrumbSchema(array(
        array('name' => 'الرئيسية', 'url' => 'https://surprise-iq.com/'),
        array('name' => 'المنتجات', 'url' => 'https://surprise-iq.com/products'),
        array('name' => $product['name'], 'url' => 'https://surprise-iq.com/product?id=' . $product['id'])
    )) ?>
    
    <style>
        /* Product Page - Imperial Dark Theme */
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 30px;
            align-items: start;
        }
        
        .product-gallery {
            background: #F8F9FA;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            position: relative;
            border: 1px solid rgba(233, 30, 140, 0.1);
        }
        
        .product-gallery .main-image {
            width: 100%;
            height: auto;
            max-height: 550px;
            object-fit: contain;
            display: block;
            transition: var(--transition);
        }
        
        .product-gallery:hover .main-image {
            transform: scale(1.02);
        }
        
        .gallery-thumbs {
            display: flex;
            gap: 10px;
            padding: 15px;
            background: rgba(233, 30, 140, 0.05);
        }
        
        .gallery-thumb {
            width: 70px;
            height: 70px;
            border-radius: var(--radius-sm);
            object-fit: cover;
            cursor: pointer;
            border: 2px solid transparent;
            transition: var(--transition);
            opacity: 0.6;
        }
        
        .gallery-thumb:hover,
        .gallery-thumb.active {
            border-color: var(--primary);
            opacity: 1;
        }
        
        .product-details {
            padding: 20px 0;
        }
        
        .product-details h1 {
            font-family: var(--font-heading);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 20px;
            color: var(--text-dark);
            line-height: 1.3;
        }
        
        .product-details .price {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 25px;
            display: inline-block;
            color: var(--primary);
        }
        
        /* تنسيق السعر مع التخفيض */
        .product-details .price-wrapper {
            gap: 15px;
        }
        
        .product-details .price-current {
            font-size: 2rem;
        }
        
        .product-details .price-old {
            font-size: 1.2rem;
        }
        
        .product-details .discount-badge {
            font-size: 1rem;
            padding: 6px 15px;
        }
        
        .product-details .description {
            font-size: 1rem;
            line-height: 2;
            color: var(--text-muted);
            margin-bottom: 30px;
            padding: 20px;
            background: rgba(233, 30, 140, 0.05);
            border-radius: var(--radius-md);
            border-right: 3px solid var(--primary);
        }
        
        .product-actions {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .customization-section {
            background: linear-gradient(135deg, #FFFFFF, #F8F9FA);
            border-radius: var(--radius-md);
            padding: 30px;
            margin-top: 35px;
            border: 1px dashed rgba(233, 30, 140, 0.3);
        }
        
        .customization-section h3 {
            margin-bottom: 20px;
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 600;
        }
        
        .product-meta {
            margin-top: 35px;
            padding-top: 25px;
            border-top: 1px solid rgba(233, 30, 140, 0.1);
        }
        
        .product-meta-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
            color: var(--text-muted);
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            background: #FFFFFF;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-sm);
            margin-bottom: 25px;
            font-size: 0.95rem;
            border: 1px solid rgba(233, 30, 140, 0.1);
        }
        
        .breadcrumb a {
            color: var(--primary);
            font-weight: 600;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb span {
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
            .product-gallery-slider {
                height: 320px;
            }
            
            .product-details h1 {
                font-size: 1.5rem;
            }
            
            .product-details .price {
                font-size: 1.8rem;
            }
            
            .gallery-nav {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
        }
        
        /* ═══════════════════════════════════════════════════════════════
           PRODUCT GALLERY SLIDER - Manual Navigation
           ═══════════════════════════════════════════════════════════════ */
        .product-gallery-slider {
            position: relative;
            width: 100%;
            height: 450px;
            overflow: hidden;
            background: #f8f9fa;
            touch-action: pan-y pinch-zoom;
            cursor: pointer;
        }
        
        .product-gallery-slider .slider-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            opacity: 0;
            transform: scale(1.02);
            transition: opacity 0.4s ease, transform 0.4s ease;
            pointer-events: none;
        }
        
        .product-gallery-slider .slider-img.active {
            opacity: 1;
            transform: scale(1);
            pointer-events: auto;
        }
        
        /* Navigation Arrows */
        .gallery-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 50px;
            height: 50px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            border-radius: 50%;
            font-size: 1.3rem;
            color: var(--primary);
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .gallery-nav:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 6px 20px rgba(233, 30, 140, 0.3);
        }
        
        .gallery-prev {
            right: 15px; /* RTL: prev is on the right */
        }
        
        .gallery-next {
            left: 15px; /* RTL: next is on the left */
        }
        
        /* Navigation Dots for Gallery */
        .gallery-dots {
            position: absolute;
            bottom: 15px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            padding: 8px 15px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 20px;
            backdrop-filter: blur(5px);
            z-index: 10;
        }
        
        .gallery-dots .slider-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .gallery-dots .slider-dot:hover {
            background: rgba(255, 255, 255, 0.8);
        }
        
        .gallery-dots .slider-dot.active {
            background: var(--primary);
            transform: scale(1.3);
            box-shadow: 0 0 10px rgba(233, 30, 140, 0.6);
        }
        
        /* Swipe hint for mobile */
        @media (max-width: 768px) {
            .gallery-nav {
                display: none; /* Hide arrows on mobile, use swipe */
            }
            
            .product-gallery-slider::after {
                content: '← سحب للتنقل →';
                position: absolute;
                bottom: 50px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 0.75rem;
                color: rgba(0, 0, 0, 0.4);
                background: rgba(255, 255, 255, 0.7);
                padding: 4px 12px;
                border-radius: 12px;
                opacity: 0;
                transition: opacity 0.3s ease;
                pointer-events: none;
            }
            
            .product-gallery-slider:focus::after,
            .product-gallery-slider:active::after {
                opacity: 1;
            }
        }
        
        /* Keyboard navigation hint for desktop */
        @media (min-width: 769px) {
            .product-gallery-slider::before {
                content: '← → استخدم الأسهم للتنقل';
                position: absolute;
                top: 15px;
                left: 50%;
                transform: translateX(-50%);
                font-size: 0.75rem;
                color: rgba(0, 0, 0, 0.4);
                background: rgba(255, 255, 255, 0.8);
                padding: 4px 12px;
                border-radius: 12px;
                opacity: 0;
                transition: opacity 0.3s ease;
                pointer-events: none;
                z-index: 5;
            }
            
            .product-gallery-slider:focus::before,
            .product-gallery-slider:hover::before {
                opacity: 1;
            }
        }
        
        /* Extra Action Buttons (Wishlist & Share) */
        .product-extra-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-action {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: var(--radius-lg);
            background: white;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            font-weight: 600;
            color: #555;
        }
        
        .btn-action:hover {
            border-color: var(--primary);
            color: var(--primary);
            background: rgba(233, 30, 140, 0.05);
        }
        
        .btn-action .btn-icon {
            font-size: 1.2rem;
        }
        
        .btn-wishlist.active {
            background: linear-gradient(135deg, #ff6b8a, #e91e8c);
            border-color: transparent;
            color: white;
        }
        
        .btn-wishlist.active .btn-icon {
            animation: heartBeat 0.6s ease;
        }
        
        @keyframes heartBeat {
            0%, 100% { transform: scale(1); }
            25% { transform: scale(1.3); }
            50% { transform: scale(1); }
            75% { transform: scale(1.2); }
        }
        
        /* Share Icon Image */
        .action-icon-img {
            width: 22px;
            height: 22px;
            object-fit: contain;
        }
        
        /* Wishlist icon styling */
        .wishlist-icon-img {
            filter: brightness(0.4);
            transition: filter 0.3s ease;
        }
        
        .btn-wishlist:hover .wishlist-icon-img {
            filter: brightness(0.2);
        }
        
        .btn-wishlist.active .wishlist-icon-img {
            filter: brightness(0) invert(1);
        }
        
        .btn-share:hover {
            border-color: #1877f2;
            color: #1877f2;
            background: rgba(24, 119, 242, 0.05);
        }
        
        @media (max-width: 480px) {
            .product-extra-actions {
                flex-direction: row;
            }
            
            .btn-action {
                padding: 10px 15px;
                font-size: 0.85rem;
            }
            
            .btn-action .btn-text {
                display: none;
            }
            
            .btn-action .btn-icon {
                font-size: 1.4rem;
            }
        }
        
        /* ══════════════════════════════════════════
           MOBILE FIXED BOTTOM ACTION BAR - FINAL FIX
           ══════════════════════════════════════════ */
        .mobile-action-bar {
            display: none;
        }
        
        @media (max-width: 768px) {
            .mobile-action-bar {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: #fff;
                padding: 10px 12px;
                padding-bottom: calc(10px + env(safe-area-inset-bottom));
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
                z-index: 10000;
                gap: 10px;
                align-items: center;
                justify-content: space-between;
                border-top: 1px solid #eee;
                pointer-events: auto;
            }
            
            /* Base button style */
            .mobile-action-bar .mobile-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                border-radius: 10px;
                font-weight: 600;
                border: none;
                cursor: pointer;
                transition: all 0.2s ease;
                position: relative;
                z-index: 10 !important;
                pointer-events: auto !important;
                -webkit-tap-highlight-color: transparent;
                overflow: visible;
            }
            
            /* Wishlist button - Fixed size LEFT */
            .mobile-btn-wishlist {
                flex: 0 0 44px;
                width: 44px;
                height: 44px;
                min-width: 44px;
                background: #f5f5f5;
                border: 1px solid #ddd !important;
            }
            
            .mobile-btn-wishlist.active {
                background: linear-gradient(135deg, #ff6b8a, #e91e8c);
                border-color: transparent !important;
            }
            
            /* Upload/Cart button - FLEXIBLE CENTER (takes remaining space) */
            .mobile-btn-cart {
                flex: 1 1 auto;
                height: 44px;
                min-width: 0;
                padding: 0 16px;
                background: linear-gradient(135deg, #E91E8C, #FF6BB3);
                color: white;
                font-size: 0.85rem;
                box-shadow: 0 2px 8px rgba(233, 30, 140, 0.3);
            }
            
            .mobile-btn-cart:active {
                transform: scale(0.98);
            }
            
            .mobile-btn-upload .btn-icon {
                font-size: 1rem;
            }
            
            /* Share button - Fixed size RIGHT */
            .mobile-btn-share {
                flex: 0 0 44px;
                width: 44px;
                height: 44px;
                min-width: 44px;
                background: #f5f5f5;
                border: 1px solid #ddd !important;
            }
            
            .mobile-btn-share:active {
                background: #e3f2fd;
                border-color: #1877f2 !important;
            }
            
            /* Icon images */
            .mobile-action-icon {
                width: 22px;
                height: 22px;
                object-fit: contain;
                pointer-events: none;
            }
            
            .wishlist-mobile-icon {
                filter: brightness(0.5);
            }
            
            .mobile-btn-wishlist.active .wishlist-mobile-icon {
                filter: brightness(0) invert(1);
            }
            
            /* Hide desktop actions on mobile */
            .product-extra-actions {
                display: none !important;
            }
            
            /* Body padding for fixed bar */
            body {
                padding-bottom: 75px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="header" id="header">
        <div class="container">
            <div class="header-inner">
                <a href="/" class="logo">
                    <img src="images/logo.jpg" alt="<?= SITE_NAME ?>">
                </a>
                
                <nav class="nav">
                    <ul class="nav-links" id="navLinks">
                        <li><a href="/">الرئيسية</a></li>
                        <li><a href="/products">المنتجات</a></li>
                        <li><a href="/about">من نحن</a></li>
                        <li><a href="/track">تتبع طلبي</a></li>
                        <li><a href="#" onclick="openContactModal(); return false;">تواصل معنا</a></li>
                        <li><a href="/wishlist" class="wishlist-sidebar-link">المفضلة <img src="images/icons/wishlist.png" class="wishlist-sidebar-icon"> <span class="wishlist-count-badge">0</span></a></li>
                    </ul>
                    
                    <!-- Mobile Header Search -->
                    <div class="header-search-wrapper" id="headerSearchWrapper">
                        <button type="button" class="header-search-toggle" id="headerSearchToggle" aria-label="بحث">
                            🔍
                        </button>
                        <form action="/products" method="GET" class="header-search-form" id="headerSearchForm">
                            <input type="text" name="q" class="header-search-input" id="headerSearchInput" 
                                   placeholder="ابحث عن منتج..." autocomplete="off">
                            <button type="submit" class="header-search-submit">🔍</button>
                            <button type="button" class="header-search-close" id="headerSearchClose">✕</button>
                        </form>
                    </div>
                    
                    <a href="/cart" class="cart-icon">
                        🛒
                        <span class="cart-count" id="cartCount">0</span>
                    </a>
                    
                    <button class="menu-toggle" id="menuToggle">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                </nav>
            </div>
        </div>
    </header>
    
    <!-- Mobile Menu Overlay -->
    <div class="nav-overlay" id="navOverlay"></div>

    <main style="margin-top: 100px; padding-bottom: 60px;">
        <div class="container">
            <!-- Breadcrumb -->
            <nav class="breadcrumb">
                <a href="/">🏠 الرئيسية</a>
                <span>←</span>
                <a href="/products">🛍️ المنتجات</a>
                <span>←</span>
                <span style="color: var(--text-muted);"><?= $product['name'] ?></span>
            </nav>
            
            <div class="product-detail">
                <!-- Gallery with Manual Slider -->
                <div class="product-gallery">
                    <?php 
                    $images = isset($product['images']) ? $product['images'] : [];
                    if (empty($images)) $images = ['products/default.png'];
                    ?>
                    
                    <div class="product-gallery-slider product-image-slider" id="productGallerySlider">
                        <?php foreach ($images as $idx => $img): ?>
                        <img src="images/<?= $img ?>" 
                             alt="<?= $product['name'] ?>" 
                             class="slider-img <?= $idx === 0 ? 'active' : '' ?>"
                             id="mainImage"
                             data-index="<?= $idx ?>">
                        <?php endforeach; ?>
                        
                        <?php if (count($images) > 1): ?>
                        <!-- Navigation Arrows -->
                        <button class="gallery-nav gallery-prev" onclick="navigateGallery(-1)" aria-label="السابق">❮</button>
                        <button class="gallery-nav gallery-next" onclick="navigateGallery(1)" aria-label="التالي">❯</button>
                        
                        <!-- Navigation Dots -->
                        <div class="slider-dots gallery-dots">
                            <?php for ($i = 0; $i < count($images); $i++): ?>
                            <span class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>" onclick="showGalleryImage(<?= $i ?>)"></span>
                            <?php endfor; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (count($images) > 1): ?>
                    <div class="gallery-thumbs">
                        <?php foreach ($images as $index => $img): ?>
                        <img src="images/<?= $img ?>" alt="<?= $product['name'] ?>" 
                             class="gallery-thumb <?= $index === 0 ? 'active' : '' ?>"
                             onclick="showGalleryImage(<?= $index ?>)">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Details -->
                <div class="product-details">
                    <?php 
                    $badge = getProductBadge($product);
                    if ($badge): ?>
                    <span class="product-badge <?= $badge['class'] ?>" style="margin-bottom: 15px; display: inline-block;"><?= $badge['label'] ?></span>
                    <?php elseif ($product['featured']): ?>
                    <span class="product-badge badge-featured" style="margin-bottom: 15px;">⭐ منتج مميز</span>
                    <?php endif; ?>
                    
                    <h1><?= $product['name'] ?></h1>
                    
                    <?php if ($product['status'] === 'sold'): ?>
                    <div class="alert alert-error" style="margin-bottom: 20px;">
                        ⚠️ نفذت الكمية حالياً - تابعنا لمعرفة التوفر
                    </div>
                    <?php endif; ?>
                    
                    <?php if (hasDiscount($product)): ?>
                    <div class="price" style="display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
                        <?= formatPriceWithDiscount($product['price'], $product['old_price']) ?>
                    </div>
                    <?php else: ?>
                    <p class="price"><?= formatPrice($product['price']) ?></p>
                    <?php endif; ?>
                    
                    <div class="description"><?= nl2br($product['description']) ?></div>
                    
                    <?php 
                    // ═══════════════════════════════════════════════════════════════════
                    // PRODUCT OPTIONS DISPLAY - نظام المجموعات الجديد مع التوافق الخلفي
                    // ═══════════════════════════════════════════════════════════════════
                    $productOptions = isset($product['options']) ? $product['options'] : [];
                    $hasOptions = !empty($productOptions['colors']['enabled']) || 
                                  !empty($productOptions['sizes']['enabled']) || 
                                  !empty($productOptions['ages']['enabled']) || 
                                  !empty($productOptions['custom_text']['enabled']) ||
                                  !empty($productOptions['extra_fields']['enabled']) ||
                                  !empty($product['packaging_enabled']);
                    
                    // تحضير المجموعات مع التوافق الخلفي
                    function getOptionGroups($option, $labelDefault) {
                        if (!empty($option['groups'])) {
                            return $option['groups'];
                        } elseif (!empty($option['values'])) {
                            return [['label' => $labelDefault, 'items' => array_map(function($v) { 
                                return is_array($v) ? $v : ['name' => $v, 'qty' => 999]; 
                            }, $option['values'])]];
                        }
                        return [];
                    }
                    ?>
                    
                    <?php if ($hasOptions): ?>
                    <div class="product-options-customer" id="productOptionsSection" style="margin: 20px 0; padding: 20px; background: linear-gradient(135deg, #fef5f9, #fff0f5); border-radius: var(--radius-lg); border: 2px solid rgba(233, 30, 140, 0.15);">
                        <h3 style="margin: 0 0 18px 0; color: var(--primary); font-size: 1.05rem; display: flex; align-items: center; gap: 8px;">
                            🎛️ اختر المواصفات
                        </h3>
                        
                        <?php 
                        // === COLORS - مجموعات الألوان ===
                        if (!empty($productOptions['colors']['enabled'])):
                            $colorsGroups = getOptionGroups($productOptions['colors'], 'اللون');
                            $colorsRequired = !empty($productOptions['colors']['required']);
                            foreach ($colorsGroups as $groupIndex => $group):
                                $groupLabel = isset($group['label']) ? $group['label'] : 'اللون';
                                $groupItems = isset($group['items']) ? $group['items'] : [];
                        ?>
                        <div class="option-group" style="margin-bottom: 18px;" data-option-type="color" data-group-index="<?= $groupIndex ?>">
                            <label class="option-label" style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-dark); font-size: 0.95rem;">
                                🎨 <?= htmlspecialchars($groupLabel) ?> <?= $colorsRequired ? '<span style="color: red;">*</span>' : '' ?>
                            </label>
                            <div class="option-chips" style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <?php foreach ($groupItems as $item): 
                                    $itemName = is_array($item) ? $item['name'] : $item;
                                    $itemQty = is_array($item) && isset($item['qty']) ? intval($item['qty']) : 999;
                                    $isAvailable = $itemQty > 0;
                                ?>
                                <button type="button" class="option-chip color-chip <?= !$isAvailable ? 'out-of-stock' : '' ?>" 
                                        data-type="color" 
                                        data-group="<?= $groupIndex ?>"
                                        data-value="<?= htmlspecialchars($itemName) ?>"
                                        data-qty="<?= $itemQty ?>"
                                        onclick="<?= $isAvailable ? 'selectGroupOption(this)' : '' ?>"
                                        <?= !$isAvailable ? 'disabled' : '' ?>
                                        style="padding: 10px 18px; border: 2px solid <?= $isAvailable ? '#e0e0e0' : '#ccc' ?>; border-radius: 25px; background: <?= $isAvailable ? 'white' : '#f5f5f5' ?>; cursor: <?= $isAvailable ? 'pointer' : 'not-allowed' ?>; font-weight: 600; transition: all 0.2s ease; font-size: 0.9rem; <?= !$isAvailable ? 'opacity: 0.5; text-decoration: line-through;' : '' ?>">
                                    <?= htmlspecialchars($itemName) ?>
                                    <?php if (!$isAvailable): ?><span style="font-size: 0.7rem; color: #999;"> (نفذ)</span><?php endif; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" class="selected-option" data-option-type="color" data-group-index="<?= $groupIndex ?>" name="selected_color_<?= $groupIndex ?>" value="">
                        </div>
                        <?php endforeach; endif; ?>
                        
                        <?php 
                        // === SIZES - مجموعات الأحجام ===
                        if (!empty($productOptions['sizes']['enabled'])):
                            $sizesGroups = getOptionGroups($productOptions['sizes'], 'الحجم');
                            $sizesRequired = !empty($productOptions['sizes']['required']);
                            foreach ($sizesGroups as $groupIndex => $group):
                                $groupLabel = isset($group['label']) ? $group['label'] : 'الحجم';
                                $groupItems = isset($group['items']) ? $group['items'] : [];
                        ?>
                        <div class="option-group" style="margin-bottom: 18px;" data-option-type="size" data-group-index="<?= $groupIndex ?>">
                            <label class="option-label" style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-dark); font-size: 0.95rem;">
                                📐 <?= htmlspecialchars($groupLabel) ?> <?= $sizesRequired ? '<span style="color: red;">*</span>' : '' ?>
                            </label>
                            <div class="option-chips" style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <?php foreach ($groupItems as $item): 
                                    $itemName = is_array($item) ? $item['name'] : $item;
                                    $itemQty = is_array($item) && isset($item['qty']) ? intval($item['qty']) : 999;
                                    $isAvailable = $itemQty > 0;
                                ?>
                                <button type="button" class="option-chip size-chip <?= !$isAvailable ? 'out-of-stock' : '' ?>" 
                                        data-type="size" 
                                        data-group="<?= $groupIndex ?>"
                                        data-value="<?= htmlspecialchars($itemName) ?>"
                                        data-qty="<?= $itemQty ?>"
                                        onclick="<?= $isAvailable ? 'selectGroupOption(this)' : '' ?>"
                                        <?= !$isAvailable ? 'disabled' : '' ?>
                                        style="padding: 10px 18px; border: 2px solid <?= $isAvailable ? '#e0e0e0' : '#ccc' ?>; border-radius: 25px; background: <?= $isAvailable ? 'white' : '#f5f5f5' ?>; cursor: <?= $isAvailable ? 'pointer' : 'not-allowed' ?>; font-weight: 600; transition: all 0.2s ease; font-size: 0.9rem; <?= !$isAvailable ? 'opacity: 0.5; text-decoration: line-through;' : '' ?>">
                                    <?= htmlspecialchars($itemName) ?>
                                    <?php if (!$isAvailable): ?><span style="font-size: 0.7rem; color: #999;"> (نفذ)</span><?php endif; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" class="selected-option" data-option-type="size" data-group-index="<?= $groupIndex ?>" name="selected_size_<?= $groupIndex ?>" value="">
                        </div>
                        <?php endforeach; endif; ?>
                        
                        <?php 
                        // === AGES - الفئات العمرية ===
                        if (!empty($productOptions['ages']['enabled'])):
                            $agesGroups = getOptionGroups($productOptions['ages'], 'الفئة العمرية');
                            $agesRequired = !empty($productOptions['ages']['required']);
                            foreach ($agesGroups as $groupIndex => $group):
                                $groupLabel = isset($group['label']) ? $group['label'] : 'الفئة العمرية';
                                $groupItems = isset($group['items']) ? $group['items'] : [];
                        ?>
                        <div class="option-group" style="margin-bottom: 18px;" data-option-type="age" data-group-index="<?= $groupIndex ?>">
                            <label class="option-label" style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-dark); font-size: 0.95rem;">
                                👶 <?= htmlspecialchars($groupLabel) ?> <?= $agesRequired ? '<span style="color: red;">*</span>' : '' ?>
                            </label>
                            <div class="option-chips" style="display: flex; flex-wrap: wrap; gap: 10px;">
                                <?php foreach ($groupItems as $item): 
                                    $itemName = is_array($item) ? (isset($item['name']) ? $item['name'] : $item) : $item;
                                ?>
                                <button type="button" class="option-chip age-chip" 
                                        data-type="age" 
                                        data-group="<?= $groupIndex ?>"
                                        data-value="<?= htmlspecialchars($itemName) ?>"
                                        onclick="selectGroupOption(this)"
                                        style="padding: 10px 18px; border: 2px solid #e0e0e0; border-radius: 25px; background: white; cursor: pointer; font-weight: 600; transition: all 0.2s ease; font-size: 0.9rem;">
                                    <?= htmlspecialchars($itemName) ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" class="selected-option" data-option-type="age" data-group-index="<?= $groupIndex ?>" name="selected_age_<?= $groupIndex ?>" value="">
                        </div>
                        <?php endforeach; endif; ?>
                        
                        <?php if (!empty($productOptions['custom_text']['enabled'])): ?>
                        <!-- CUSTOM TEXT - للتوافق الخلفي -->
                        <div class="option-group" style="margin-bottom: 10px;">
                            <label class="option-label" style="display: block; margin-bottom: 10px; font-weight: 600; color: var(--text-dark); font-size: 0.95rem;">
                                ✏️ <?= htmlspecialchars($productOptions['custom_text']['label']) ?> <?= !empty($productOptions['custom_text']['required']) ? '<span style="color: red;">*</span>' : '' ?>
                            </label>
                            <input type="text" 
                                   id="customTextInput" 
                                   name="custom_text" 
                                   placeholder="<?= htmlspecialchars($productOptions['custom_text']['placeholder']) ?>"
                                   maxlength="<?= intval($productOptions['custom_text']['max_length']) ?>"
                                   style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: var(--radius-md); font-size: 1rem; transition: all 0.2s ease;">
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 6px;">
                                الحد الأقصى: <?= intval($productOptions['custom_text']['max_length']) ?> حرف
                            </p>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        // === EXTRA FIELDS - الحقول الإضافية الموحدة ===
                        if (!empty($productOptions['extra_fields']['enabled'])):
                            $extraFieldsGroups = isset($productOptions['extra_fields']['groups']) ? $productOptions['extra_fields']['groups'] : [];
                            foreach ($extraFieldsGroups as $groupIndex => $group):
                                $groupLabel = isset($group['label']) ? $group['label'] : '';
                                $groupItems = isset($group['items']) ? $group['items'] : [];
                        ?>
                        <div class="extra-fields-group" style="margin-bottom: 18px; padding: 15px; background: linear-gradient(135deg, rgba(156, 39, 176, 0.05), rgba(103, 58, 183, 0.05)); border-radius: 12px; border-right: 3px solid #9c27b0;">
                            <?php if (!empty($groupLabel)): ?>
                            <div style="font-weight: 700; color: #7b1fa2; font-size: 0.95rem; margin-bottom: 12px;">
                                🏷️ <?= htmlspecialchars($groupLabel) ?>
                            </div>
                            <?php endif; ?>
                            
                            <?php foreach ($groupItems as $itemIndex => $item):
                                $itemType = isset($item['type']) ? $item['type'] : 'text';
                                $itemLabel = isset($item['label']) ? $item['label'] : '';
                                $isRequired = !empty($item['required']);
                                $fieldId = "extra_field_{$groupIndex}_{$itemIndex}";
                                
                                if ($itemType === 'text'):
                                    $maxLength = isset($item['max_length']) ? intval($item['max_length']) : 50;
                                    $placeholder = isset($item['placeholder']) ? $item['placeholder'] : '';
                            ?>
                            <div class="option-group" style="margin-bottom: 12px;">
                                <label class="option-label" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-dark); font-size: 0.9rem;">
                                    ✏️ <?= htmlspecialchars($itemLabel) ?> <?= $isRequired ? '<span style="color: red;">*</span>' : '' ?>
                                </label>
                                <input type="text" 
                                       class="extra-field-input" 
                                       id="<?= $fieldId ?>"
                                       data-group="<?= $groupIndex ?>"
                                       data-item="<?= $itemIndex ?>"
                                       data-type="text"
                                       data-label="<?= htmlspecialchars($itemLabel) ?>"
                                       data-required="<?= $isRequired ? '1' : '0' ?>"
                                       placeholder="<?= htmlspecialchars($placeholder) ?>"
                                       maxlength="<?= $maxLength ?>"
                                       style="width: 100%; padding: 12px 16px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 0.95rem; transition: all 0.2s ease;">
                                <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">
                                    الحد الأقصى: <?= $maxLength ?> حرف
                                </p>
                            </div>
                            <?php else: 
                                $options = isset($item['options']) ? $item['options'] : [];
                                if (is_string($options)) {
                                    $options = array_filter(array_map('trim', preg_split('/[\|\n]/', $options)));
                                }
                            ?>
                            <div class="option-group" style="margin-bottom: 12px;" data-option-type="extra_select" data-group-index="<?= $groupIndex ?>" data-item-index="<?= $itemIndex ?>">
                                <label class="option-label" style="display: block; margin-bottom: 8px; font-weight: 600; color: var(--text-dark); font-size: 0.9rem;">
                                    📋 <?= htmlspecialchars($itemLabel) ?> <?= $isRequired ? '<span style="color: red;">*</span>' : '' ?>
                                </label>
                                <div class="option-chips" style="display: flex; flex-wrap: wrap; gap: 8px;">
                                    <?php foreach ($options as $opt): ?>
                                    <button type="button" class="option-chip extra-select-chip" 
                                            data-type="extra_select"
                                            data-group="<?= $groupIndex ?>"
                                            data-item="<?= $itemIndex ?>"
                                            data-label="<?= htmlspecialchars($itemLabel) ?>"
                                            data-value="<?= htmlspecialchars($opt) ?>"
                                            data-required="<?= $isRequired ? '1' : '0' ?>"
                                            onclick="selectExtraOption(this)"
                                            style="padding: 10px 16px; border: 2px solid #e0e0e0; border-radius: 20px; background: white; cursor: pointer; font-weight: 600; transition: all 0.2s ease; font-size: 0.85rem;">
                                        <?= htmlspecialchars($opt) ?>
                                    </button>
                                    <?php endforeach; ?>
                                </div>
                                <input type="hidden" class="extra-select-value" 
                                       data-group="<?= $groupIndex ?>" 
                                       data-item="<?= $itemIndex ?>"
                                       data-label="<?= htmlspecialchars($itemLabel) ?>"
                                       value="">
                            </div>
                            <?php endif; endforeach; ?>
                        </div>
                        <?php endforeach; endif; ?>
                        
                        <!-- Validation message -->
                        <div id="optionsValidationMsg" style="display: none; padding: 10px 15px; background: #fff3e0; border: 1px solid #ff9800; border-radius: var(--radius-md); color: #e65100; font-size: 0.9rem; margin-top: 10px;">
                            ⚠️ الرجاء اختيار جميع الخيارات المطلوبة
                        </div>
                    </div>
                    
                    <!-- ═══════════════════════════════════════════════════════════════════
                         GIFT CARDS - نظام بطاقات الرسائل المتعددة
                         ═══════════════════════════════════════════════════════════════════ -->
                    <?php 
                    $giftCardEnabled = !empty($productOptions['gift_card_enabled']);
                    $giftCards = isset($productOptions['gift_cards']) ? $productOptions['gift_cards'] : [];
                    
                    if ($giftCardEnabled && !empty($giftCards)): 
                    ?>
                    <div class="gift-cards-container" style="margin: 20px 0;">
                        <?php foreach ($giftCards as $index => $card): 
                            $cardId = $card['id'] ?? 'card_' . $index;
                            $isRequired = !empty($card['required']);
                            $maxLength = isset($card['max_length']) ? intval($card['max_length']) : 250;
                            $label = $card['label'] ?? 'بطاقة إهداء';
                            $helper = $card['helper'] ?? '';
                            $placeholder = $card['placeholder'] ?? '';
                        ?>
                        <div class="gift-card-section" id="section_<?= $cardId ?>" style="margin-bottom: 15px; padding: 20px; background: linear-gradient(135deg, rgba(233, 30, 140, 0.04), rgba(156, 39, 176, 0.04)); border-radius: 14px; border: 2px solid rgba(233, 30, 140, 0.15); transition: all 0.3s ease;">
                            <!-- Toggle Checkbox -->
                            <label style="display: flex; align-items: center; gap: 12px; cursor: pointer; margin: 0;">
                                <input type="checkbox" id="enabled_<?= $cardId ?>" 
                                       class="gift-card-checkbox"
                                       data-card-id="<?= $cardId ?>"
                                       data-required="<?= $isRequired ? '1' : '0' ?>"
                                       onchange="toggleGiftCardSection('<?= $cardId ?>')"
                                       <?= $isRequired ? 'checked' : '' ?>
                                       style="width: 22px; height: 22px; accent-color: #e91e8c; cursor: pointer;">
                                <div style="flex: 1;">
                                    <span style="font-weight: 700; color: var(--primary); font-size: 1rem;">
                                        🎁 <?= htmlspecialchars($label) ?>
                                        <?php if ($isRequired): ?>
                                        <span style="color: #e53935; font-size: 0.8rem;">*مطلوب</span>
                                        <?php endif; ?>
                                    </span>
                                    <p style="margin: 4px 0 0; font-size: 0.8rem; color: #888;">فعّل الخيار لكتابة رسالة مع هذه البطاقة</p>
                                </div>
                            </label>
                            
                            <!-- Message Input -->
                            <div id="input_section_<?= $cardId ?>" style="display: <?= $isRequired ? 'block' : 'none' ?>; margin-top: 15px; padding-top: 15px; border-top: 1px dashed rgba(233, 30, 140, 0.2);">
                                <?php if (!empty($helper)): ?>
                                <p style="margin: 0 0 10px; font-size: 0.85rem; color: #666;">📝 <?= htmlspecialchars($helper) ?></p>
                                <?php endif; ?>
                                <textarea id="msg_<?= $cardId ?>" 
                                          class="gift-card-textarea"
                                          maxlength="<?= $maxLength ?>"
                                          placeholder="<?= htmlspecialchars($placeholder) ?>"
                                          data-card-id="<?= $cardId ?>"
                                          data-required="<?= $isRequired ? '1' : '0' ?>"
                                          data-max-length="<?= $maxLength ?>"
                                          data-label="<?= htmlspecialchars($label) ?>"
                                          oninput="updateGiftCardCounter('<?= $cardId ?>')"
                                          style="width: 100%; min-height: 100px; padding: 14px; border: 2px solid rgba(233, 30, 140, 0.2); border-radius: 12px; font-size: 0.95rem; resize: vertical; direction: rtl; text-align: right; font-family: inherit; transition: border-color 0.2s ease; background: white;"
                                          onfocus="this.style.borderColor='var(--primary)'; this.style.boxShadow='0 0 0 3px rgba(233, 30, 140, 0.1)';"
                                          onblur="this.style.borderColor='rgba(233, 30, 140, 0.2)'; this.style.boxShadow='none';"></textarea>
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px; font-size: 0.8rem; color: #888;">
                                    <span id="counter_<?= $cardId ?>">0 / <?= $maxLength ?></span>
                                    <span style="color: var(--primary);">💌 <?= htmlspecialchars($label) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <script>
                    function toggleGiftCardSection(cardId) {
                        const checkbox = document.getElementById('enabled_' + cardId);
                        const section = document.getElementById('input_section_' + cardId);
                        const parentSection = document.getElementById('section_' + cardId);
                        
                        if (!checkbox || !section || !parentSection) return;
                        
                        if (checkbox.checked) {
                            section.style.display = 'block';
                            parentSection.style.borderColor = 'rgba(233, 30, 140, 0.4)';
                            parentSection.style.background = 'linear-gradient(135deg, rgba(233, 30, 140, 0.08), rgba(156, 39, 176, 0.08))';
                        } else {
                            section.style.display = 'none';
                            parentSection.style.borderColor = 'rgba(233, 30, 140, 0.15)';
                            parentSection.style.background = 'linear-gradient(135deg, rgba(233, 30, 140, 0.04), rgba(156, 39, 176, 0.04))';
                            // Clear textarea when unchecking
                            const textarea = document.getElementById('msg_' + cardId);
                            if (textarea) {
                                textarea.value = '';
                                textarea.style.borderColor = 'rgba(233, 30, 140, 0.2)';
                                updateGiftCardCounter(cardId);
                            }
                        }
                        
                        // Clear validation error if any
                        parentSection.style.borderColor = checkbox.checked ? 'rgba(233, 30, 140, 0.4)' : 'rgba(233, 30, 140, 0.15)';
                    }
                    
                    function updateGiftCardCounter(cardId) {
                        const textarea = document.getElementById('msg_' + cardId);
                        const counter = document.getElementById('counter_' + cardId);
                        if (!textarea || !counter) return;
                        const maxLength = parseInt(textarea.dataset.maxLength) || 250;
                        const currentLength = textarea.value.length;
                        counter.textContent = currentLength + ' / ' + maxLength;
                        counter.style.color = currentLength >= maxLength * 0.9 ? '#e53935' : '#888';
                        
                        // Reset border color if typing
                        if (currentLength > 0) {
                            textarea.style.borderColor = 'rgba(233, 30, 140, 0.2)';
                        }
                    }
                    </script>
                    
                    <!-- ═══════════════════════════════════════════════════════════════════
                         BOX OPTIONS - خيارات الصناديق/العلب
                         ═══════════════════════════════════════════════════════════════════ -->
                    <?php 
                    $boxOptions = isset($productOptions['box_options']) ? $productOptions['box_options'] : [];
                    $boxEnabled = !empty($boxOptions['enabled']) && !empty($boxOptions['items']);
                    $boxMandatory = !empty($boxOptions['mandatory']);
                    
                    if ($boxEnabled): 
                    ?>
                    <div class="option-group box-options-section" style="margin: 20px 0; padding: 18px; background: rgba(156, 39, 176, 0.03); border-radius: 12px; border: 1px solid rgba(156, 39, 176, 0.1);">
                        <label class="option-label" style="display: block; margin-bottom: 12px; font-weight: 700; color: #7b1fa2; font-size: 1rem;">
                            📦 اختر نوع الصندوق / التغليف الخاص <?= $boxMandatory ? '<span style="color: red;">*مطلوب</span>' : '' ?>
                        </label>
                        <div class="box-chips" style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                            <?php foreach ($boxOptions['items'] as $idx => $box): 
                                $isOutOfStock = isset($box['stock']) && $box['stock'] <= 0;
                                $boxPrice = intval($box['price'] ?? 0);
                            ?>
                            <div class="box-option-card <?= $isOutOfStock ? 'out-of-stock' : '' ?>" 
                                 onclick="<?= !$isOutOfStock ? "selectBoxOption(this, $idx, $boxPrice)" : "" ?>"
                                 data-idx="<?= $idx ?>"
                                 data-price="<?= $boxPrice ?>"
                                 data-name="<?= htmlspecialchars($box['name']) ?>"
                                 style="padding: 12px 15px; border: 2px solid #eee; border-radius: 12px; background: white; cursor: <?= $isOutOfStock ? 'not-allowed' : 'pointer' ?>; transition: all 0.2s ease; position: relative; <?= $isOutOfStock ? 'opacity: 0.6;' : '' ?>">
                                
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 10px;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-dark); margin-bottom: 2px;">
                                            <?= htmlspecialchars($box['name']) ?>
                                            <?php if (!empty($box['size'])): ?>
                                            <span style="font-size: 0.8rem; color: #888; font-weight: normal;">(<?= htmlspecialchars($box['size']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($box['description'])): ?>
                                        <div style="font-size: 0.8rem; color: #666; line-height: 1.4;"><?= htmlspecialchars($box['description']) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div style="text-align: left;">
                                        <?php if ($boxPrice > 0): ?>
                                        <div style="color: #9c27b0; font-weight: 800; font-size: 0.9rem;">+ <?= formatPrice($boxPrice) ?></div>
                                        <?php else: ?>
                                        <div style="color: #4CAF50; font-weight: 700; font-size: 0.85rem;">مجاني</div>
                                        <?php endif; ?>
                                        
                                        <?php if ($isOutOfStock): ?>
                                        <div style="color: #f44336; font-size: 0.75rem; font-weight: 700;">غير متوفر حالياً</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <input type="radio" name="selected_box" value="<?= $idx ?>" style="display: none;">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <input type="hidden" id="selectedBoxIdx" value="">
                        <input type="hidden" id="selectedBoxPrice" value="0">
                        <input type="hidden" id="selectedBoxName" value="">
                    </div>
                    
                    <style>
                    .box-option-card.selected {
                        border-color: #9c27b0 !important;
                        background: rgba(156, 39, 176, 0.05) !important;
                        box-shadow: 0 4px 12px rgba(156, 39, 176, 0.15);
                        transform: scale(1.01);
                    }
                    .box-option-card:hover:not(.out-of-stock):not(.selected) {
                        border-color: #ddd;
                        background: #fcfcfc;
                    }
                    </style>
                    <?php endif; ?>

                    <!-- ═══════════════════════════════════════════════════════════════════
                         PACKAGING - خيار التغليف للزبون (تم نقله إلى داخل الحاوية)
                         ═══════════════════════════════════════════════════════════════════ -->
                    <?php if (!empty($product['packaging_enabled'])): ?>
                    <div class="packaging-option-customer" style="margin: 15px 0; padding: 18px; background: linear-gradient(135deg, rgba(156, 39, 176, 0.04), rgba(103, 58, 183, 0.04)); border-radius: 12px; border: 2px dashed rgba(156, 39, 176, 0.2); transition: all 0.3s ease;">
                        <label style="display: flex; align-items: flex-start; gap: 12px; cursor: pointer; position: relative; margin: 0;">
                            <div style="margin-top: 3px;">
                                <input type="checkbox" id="packagingCheckbox" 
                                       data-price="<?= intval($product['packaging_price']) ?>"
                                       onchange="updateTotalPriceWithPackaging()"
                                       style="width: 22px; height: 22px; accent-color: #9c27b0; cursor: pointer;">
                            </div>
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 5px; margin-bottom: 5px;">
                                    <span style="font-weight: 700; color: #7b1fa2; font-size: 1rem;">🎁 إضافة تغليف للمنتج</span>
                                    <span style="font-weight: 800; color: #9c27b0; background: rgba(156, 39, 176, 0.1); padding: 2px 10px; border-radius: 20px; font-size: 0.85rem;">
                                        + <?= formatPrice($product['packaging_price']) ?>
                                    </span>
                                </div>
                                <?php if (!empty($product['packaging_description'])): ?>
                                <p style="font-size: 0.8rem; color: #666; margin: 0; line-height: 1.5;">
                                    <?= htmlspecialchars($product['packaging_description']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                        </label>
                    </div>
                    
                    <script>
                    function updateTotalPrice() {
                        const basePrice = <?= intval($product['price']) ?>;
                        
                        // 1. Packaging Price
                        const packagingCheckbox = document.getElementById('packagingCheckbox');
                        const packagingPrice = packagingCheckbox && packagingCheckbox.checked ? parseInt(packagingCheckbox.dataset.price) : 0;
                        
                        // 2. Box Option Price
                        const boxPrice = parseInt(document.getElementById('selectedBoxPrice')?.value || 0);
                        
                        const totalPrice = basePrice + packagingPrice + boxPrice;
                        
                        // تحديث عرض السعر في الصفحة
                        const priceElements = document.querySelectorAll('.price-value, .price');
                        priceElements.forEach(el => {
                            if (el.tagName === 'P' || el.classList.contains('price-value')) {
                                el.innerHTML = formatPrice_Local(totalPrice);
                            }
                        });
                        
                        // تحديث سمة السعر في زر الإضافة للسلة
                        const addBtn = document.getElementById('addToCartBtn');
                        const mobileBtn = document.getElementById('mobileAddToCartBtn');
                        [addBtn, mobileBtn].forEach(btn => {
                            if (btn) btn.dataset.price = totalPrice;
                        });

                        // إضافة تأثير بصري للتغليف (إذا وجد)
                        if (packagingCheckbox) {
                            const packagingBlock = packagingCheckbox.closest('.packaging-option-customer');
                            if (packagingCheckbox.checked) {
                                packagingBlock.style.background = 'linear-gradient(135deg, rgba(156, 39, 176, 0.08), rgba(103, 58, 183, 0.08))';
                                packagingBlock.style.borderColor = '#9c27b0';
                            } else {
                                packagingBlock.style.background = 'linear-gradient(135deg, rgba(156, 39, 176, 0.04), rgba(103, 58, 183, 0.04))';
                                packagingBlock.style.borderColor = 'rgba(156, 39, 176, 0.2)';
                            }
                        }
                    }

                    // للتوافق مع التسمية القديمة
                    function updateTotalPriceWithPackaging() { updateTotalPrice(); }

                    function formatPrice_Local(price) {
                        return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' د.ع';
                    }

                    function selectBoxOption(card, idx, price) {
                        // إزالة التحديد من الكروت الأخرى
                        document.querySelectorAll('.box-option-card').forEach(c => c.classList.remove('selected'));
                        
                        // تحديد الكرت الحالي
                        card.classList.add('selected');
                        
                        // تحديث القيم المخفية
                        document.getElementById('selectedBoxIdx').value = idx;
                        document.getElementById('selectedBoxPrice').value = price;
                        document.getElementById('selectedBoxName').value = card.dataset.name;
                        
                        // إخفاء رسالة التحقق
                        const validationMsg = document.getElementById('optionsValidationMsg');
                        if (validationMsg) validationMsg.style.display = 'none';
                        
                        // تحديث السعر الإجمالي
                        updateTotalPrice();
                    }
                    </script>
                    <?php endif; ?>

                    <style>
                        .option-chip:hover {
                            border-color: var(--primary) !important;
                            background: rgba(233, 30, 140, 0.05) !important;
                        }
                        .option-chip.selected {
                            border-color: var(--primary) !important;
                            background: linear-gradient(135deg, #E91E8C, #FF6BB3) !important;
                            color: white !important;
                            box-shadow: 0 2px 8px rgba(233, 30, 140, 0.3);
                        }
                        #customTextInput:focus {
                            border-color: var(--primary);
                            outline: none;
                            box-shadow: 0 0 0 3px rgba(233, 30, 140, 0.1);
                        }
                        @media (max-width: 480px) {
                            .option-chip {
                                padding: 8px 14px !important;
                                font-size: 0.85rem !important;
                            }
                            .product-options-customer {
                                padding: 15px !important;
                            }
                        }
                    </style>
                    
                    <script>
                    // Product options config from PHP - نظام المجموعات الجديد
                    window.productOptionsConfig = <?= json_encode([
                        'colors' => [
                            'enabled' => !empty($productOptions['colors']['enabled']),
                            'required' => !empty($productOptions['colors']['required']),
                            'groups' => !empty($productOptions['colors']['groups']) ? count($productOptions['colors']['groups']) : (!empty($productOptions['colors']['values']) ? 1 : 0)
                        ],
                        'sizes' => [
                            'enabled' => !empty($productOptions['sizes']['enabled']),
                            'required' => !empty($productOptions['sizes']['required']),
                            'groups' => !empty($productOptions['sizes']['groups']) ? count($productOptions['sizes']['groups']) : (!empty($productOptions['sizes']['values']) ? 1 : 0)
                        ],
                        'ages' => [
                            'enabled' => !empty($productOptions['ages']['enabled']),
                            'required' => !empty($productOptions['ages']['required']),
                            'groups' => !empty($productOptions['ages']['groups']) ? count($productOptions['ages']['groups']) : (!empty($productOptions['ages']['values']) ? 1 : 0)
                        ],
                        'custom_text' => [
                            'enabled' => !empty($productOptions['custom_text']['enabled']),
                            'required' => !empty($productOptions['custom_text']['required'])
                        ],
                        'extra_fields' => [
                            'enabled' => !empty($productOptions['extra_fields']['enabled']),
                            'required' => !empty($productOptions['extra_fields']['required']),
                            'groups' => isset($productOptions['extra_fields']['groups']) ? $productOptions['extra_fields']['groups'] : []
                        ],
                        'gift_cards' => [
                            'enabled' => !empty($productOptions['gift_card_enabled']),
                            'cards' => $productOptions['gift_cards'] ?? []
                        ],
                        'box_options' => [
                            'enabled' => !empty($productOptions['box_options']['enabled']),
                            'mandatory' => !empty($productOptions['box_options']['mandatory'])
                        ]
                    ]) ?>;
                    
                    // دالة اختيار خيار من مجموعة
                    function selectGroupOption(btn) {
                        if (btn.disabled || btn.classList.contains('out-of-stock')) return;
                        
                        const type = btn.dataset.type;
                        const groupIndex = btn.dataset.group;
                        const value = btn.dataset.value;
                        
                        // إزالة التحديد من العناصر الأخرى في نفس المجموعة
                        const group = btn.closest('.option-group');
                        const siblings = group.querySelectorAll('.option-chip');
                        siblings.forEach(s => s.classList.remove('selected'));
                        
                        // تحديد العنصر المختار
                        btn.classList.add('selected');
                        
                        // تحديث الحقل المخفي
                        const hiddenInput = group.querySelector('.selected-option');
                        if (hiddenInput) {
                            hiddenInput.value = value;
                        }
                        
                        // إخفاء رسالة التحقق
                        const validationMsg = document.getElementById('optionsValidationMsg');
                        if (validationMsg) validationMsg.style.display = 'none';
                    }
                    
                    // دالة اختيار خيار من الحقول الإضافية (select)
                    function selectExtraOption(btn) {
                        const group = btn.closest('.option-group');
                        const siblings = group.querySelectorAll('.extra-select-chip');
                        siblings.forEach(s => s.classList.remove('selected'));
                        
                        btn.classList.add('selected');
                        
                        const hiddenInput = group.querySelector('.extra-select-value');
                        if (hiddenInput) {
                            hiddenInput.value = btn.dataset.value;
                        }
                        
                        const validationMsg = document.getElementById('optionsValidationMsg');
                        if (validationMsg) validationMsg.style.display = 'none';
                    }
                    
                    // دالة قديمة للتوافق الخلفي
                    function selectOption(btn, type) {
                        selectGroupOption(btn);
                    }
                    
                    // جمع الخيارات المختارة (تُستدعى من cart.js)
                    function getSelectedProductOptions() {
                        const options = {};
                        
                        // جمع كل الخيارات من المجموعات المختلفة
                        document.querySelectorAll('.selected-option').forEach(input => {
                            const type = input.dataset.optionType;
                            const groupIndex = input.dataset.groupIndex;
                            const value = input.value;
                            
                            if (value) {
                                // إذا كان هناك مجموعة واحدة فقط، نستخدم التنسيق القديم
                                const config = window.productOptionsConfig[type + 's'];
                                if (config && config.groups <= 1) {
                                    options[type] = value;
                                } else {
                                    // مجموعات متعددة
                                    if (!options[type + '_groups']) options[type + '_groups'] = {};
                                    const groupLabel = input.closest('.option-group').querySelector('.option-label').textContent.replace(/[🎨📐👶✏️📋🏷️\*]/g, '').trim();
                                    options[type + '_groups'][groupLabel] = value;
                                }
                            }
                        });
                        
                        // النص المخصص
                        const customTextInput = document.getElementById('customTextInput');
                        if (customTextInput && customTextInput.value.trim()) {
                            options.custom_text = customTextInput.value.trim();
                        }
                        
                        // الحقول الإضافية - نصية
                        document.querySelectorAll('.extra-field-input').forEach(input => {
                            if (input.value.trim()) {
                                if (!options.extra_fields) options.extra_fields = [];
                                options.extra_fields.push({
                                    type: 'text',
                                    label: input.dataset.label,
                                    value: input.value.trim()
                                });
                            }
                        });
                        
                        // الحقول الإضافية - اختيارات
                        document.querySelectorAll('.extra-select-value').forEach(input => {
                            if (input.value) {
                                if (!options.extra_fields) options.extra_fields = [];
                                options.extra_fields.push({
                                    type: 'select',
                                    label: input.dataset.label,
                                    value: input.value
                                });
                            }
                        });
                        
                        // نظام بطاقات الرسائل المتعددة
                        const giftCards = [];
                        document.querySelectorAll('.gift-card-checkbox:checked').forEach(cb => {
                            const cardId = cb.dataset.cardId;
                            const textarea = document.getElementById('msg_' + cardId);
                            if (textarea && textarea.value.trim()) {
                                giftCards.push({
                                    id: cardId,
                                    label: textarea.dataset.label,
                                    message: textarea.value.trim().substring(0, parseInt(textarea.dataset.maxLength) || 250)
                                });
                            }
                        });
                        
                        if (giftCards.length > 0) {
                            options.gift_cards = giftCards;
                            options.gift_card_enabled = true; // للتوافق الخلفي
                        } else {
                            options.gift_card_enabled = false;
                        }
                        
                        // التغليف
                        const packagingCheckbox = document.getElementById('packagingCheckbox');
                        if (packagingCheckbox && packagingCheckbox.checked) {
                            options.packaging_selected = true;
                            options.packaging_price = parseInt(packagingCheckbox.dataset.price);
                            // جلب وصف التغليف إذا وجد
                            const pDesc = document.querySelector('.packaging-option-customer p');
                            if (pDesc) {
                                options.packaging_description = pDesc.textContent.trim();
                            }
                        } else {
                            options.packaging_selected = false;
                        }

                        // خيارات الصناديق
                        const boxIdx = document.getElementById('selectedBoxIdx')?.value;
                        if (boxIdx !== undefined && boxIdx !== "") {
                            options.box_selected = true;
                            options.box_selected_idx = parseInt(boxIdx);
                            options.box_name = document.getElementById('selectedBoxName').value;
                            options.box_price = parseInt(document.getElementById('selectedBoxPrice').value);
                        } else {
                            options.box_selected = false;
                        }
                        
                        return options;
                    }
                    
                    // التحقق من الخيارات المطلوبة
                    function validateProductOptions() {
                        const config = window.productOptionsConfig;
                        const validationMsg = document.getElementById('optionsValidationMsg');
                        let isValid = true;
                        
                        // التحقق من الألوان
                        if (config.colors.enabled && config.colors.required) {
                            const colorGroups = document.querySelectorAll('[data-option-type="color"]');
                            colorGroups.forEach(group => {
                                const hiddenInput = group.querySelector('.selected-option');
                                if (!hiddenInput || !hiddenInput.value) isValid = false;
                            });
                        }
                        
                        // التحقق من الأحجام
                        if (config.sizes.enabled && config.sizes.required) {
                            const sizeGroups = document.querySelectorAll('[data-option-type="size"]');
                            sizeGroups.forEach(group => {
                                const hiddenInput = group.querySelector('.selected-option');
                                if (!hiddenInput || !hiddenInput.value) isValid = false;
                            });
                        }
                        
                        // التحقق من الفئات العمرية
                        if (config.ages.enabled && config.ages.required) {
                            const ageGroups = document.querySelectorAll('[data-option-type="age"]');
                            ageGroups.forEach(group => {
                                const hiddenInput = group.querySelector('.selected-option');
                                if (!hiddenInput || !hiddenInput.value) isValid = false;
                            });
                        }
                        
                        // التحقق من النص المخصص
                        if (config.custom_text && config.custom_text.enabled && config.custom_text.required) {
                            const customTextInput = document.getElementById('customTextInput');
                            if (!customTextInput || !customTextInput.value.trim()) isValid = false;
                        }
                        
                        // التحقق من الحقول الإضافية
                        document.querySelectorAll('.extra-field-input[data-required="1"]').forEach(input => {
                            if (!input.value.trim()) isValid = false;
                        });
                        
                        // التحقق من خيارات الصناديق
                        if (config.box_options && config.box_options.enabled && config.box_options.mandatory) {
                            const boxIdx = document.getElementById('selectedBoxIdx')?.value;
                            if (boxIdx === undefined || boxIdx === "") isValid = false;
                        }

                        // التحقق من نظام بطاقات الرسائل المتعددة
                        document.querySelectorAll('.gift-card-checkbox').forEach(cb => {
                            const cardId = cb.dataset.cardId;
                            const textarea = document.getElementById('msg_' + cardId);
                            
                            // If required by admin, it must be checked
                            if (cb.dataset.required === '1' && !cb.checked) {
                                isValid = false;
                                cb.closest('.gift-card-section').style.borderColor = '#e53935';
                            }
                            
                            // If enabled (either required or voluntarily), message must not be empty if it's marked as required/enabled
                            if (cb.checked && textarea) {
                                if (textarea.dataset.required === '1' || cb.checked) { // If checked, message is effectively required to be useful
                                    if (!textarea.value.trim()) {
                                        isValid = false;
                                        textarea.style.borderColor = '#e53935';
                                    }
                                }
                            }
                        });
                        
                        if (!isValid && validationMsg) {
                            validationMsg.style.display = 'block';
                            validationMsg.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                        
                        return isValid;
                    }
                    
                    // تصدير الدوال
                    window.selectGroupOption = selectGroupOption;
                    window.selectExtraOption = selectExtraOption;
                    window.selectOption = selectOption;
                    window.getSelectedProductOptions = getSelectedProductOptions;
                    window.validateProductOptions = validateProductOptions;
                    </script>
                    <?php endif; ?>
                    

                    
                    <?php if ($product['status'] === 'available'): ?>
                    <div class="product-actions">
                        <?php if ($product['customizable']): ?>
                        <!-- للمنتجات القابلة للتخصيص: الزر معطل حتى رفع صورة -->
                        <button class="btn btn-primary btn-lg add-to-cart-custom" id="addToCartBtn"
                                data-id="<?= $product['id'] ?>"
                                data-name="<?= $product['name'] ?>"
                                data-price="<?= $product['price'] ?>"
                                data-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>"
                                disabled
                                style="opacity: 0.5; cursor: not-allowed;">
                            📷 ارفع صورتك أولاً
                        </button>
                        <?php else: ?>
                        <button class="btn btn-primary btn-lg add-to-cart"
                                data-id="<?= $product['id'] ?>"
                                data-name="<?= $product['name'] ?>"
                                data-price="<?= $product['price'] ?>"
                                data-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>">
                            🛒 أضف للسلة
                        </button>
                        <?php endif; ?>
                        
                        <!-- Wishlist & Share Buttons -->
                        <div class="product-extra-actions">
                            <button type="button" class="btn-action btn-share" onclick="shareProduct()">
                                <img src="images/icons/share.png" alt="مشاركة" class="action-icon-img">
                                <span class="btn-text">مشاركة</span>
                            </button>
                            <button type="button" class="btn-action btn-wishlist" data-wishlist-btn 
                                    data-product-id="<?= $product['id'] ?>" 
                                    data-product-name="<?= htmlspecialchars($product['name']) ?>" 
                                    data-product-price="<?= $product['price'] ?>" 
                                    data-product-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>">
                                <img src="images/icons/wishlist.png" alt="المفضلة" class="action-icon-img wishlist-icon-img">
                                <span class="btn-text">المفضلة</span>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($product['customizable']): ?>
                    <?php
                    // إعدادات صور التخصيص المتعددة
                    $customImgEnabled = !empty($productOptions['custom_images']['enabled']);
                    $customImgRequired = !empty($productOptions['custom_images']['required']);
                    $customImgLabel = isset($productOptions['custom_images']['label']) ? $productOptions['custom_images']['label'] : '';
                    $customImgMin = isset($productOptions['custom_images']['min_images']) ? intval($productOptions['custom_images']['min_images']) : 1;
                    $customImgMax = isset($productOptions['custom_images']['max_images']) ? intval($productOptions['custom_images']['max_images']) : 5;
                    $customImgMaxSize = isset($productOptions['custom_images']['max_size_mb']) ? intval($productOptions['custom_images']['max_size_mb']) : 5;
                    $customImgTypes = isset($productOptions['custom_images']['allowed_types']) ? $productOptions['custom_images']['allowed_types'] : ['jpg', 'jpeg', 'png', 'webp'];
                    $acceptTypes = implode(',', array_map(function($t) { return 'image/' . ($t === 'jpg' ? 'jpeg' : $t); }, $customImgTypes));
                    ?>
                    <div class="customization-section">
                        <h3>🎨 هذا المنتج قابل للتخصيص</h3>
                        <?php if (!empty($customImgLabel)): ?>
                        <p style="margin-bottom: 15px; color: var(--primary); font-weight: 600; font-size: 1.05rem; padding: 10px 15px; background: rgba(233, 30, 140, 0.08); border-radius: 8px; border-right: 3px solid var(--primary);">
                            📝 <?= htmlspecialchars($customImgLabel) ?>
                        </p>
                        <?php endif; ?>
                        <p style="margin-bottom: 20px; color: var(--text-light); line-height: 1.8;">
                            <?php if ($customImgEnabled && $customImgMax > 1): ?>
                            يمكنك إرفاق صورك الشخصية أو تصاميمك الخاصة (<?= $customImgMin ?> - <?= $customImgMax ?> صور) وسنقوم بطباعتها لك بأعلى جودة!
                            <?php else: ?>
                            يمكنك إرفاق صورتك الشخصية أو تصميمك الخاص وسنقوم بطباعته لك بأعلى جودة!
                            <?php endif; ?>
                        </p>
                        
                        <?php if ($customImgEnabled && $customImgMax > 1): ?>
                        <!-- واجهة رفع صور متعددة -->
                        <div id="multiUploadContainer">
                            <div class="multi-upload-area" id="multiUploadArea">
                                <div class="upload-icon">📷</div>
                                <p class="upload-text">اضغط أو اسحب الصور هنا</p>
                                <p class="upload-hint"><?= strtoupper(implode(', ', $customImgTypes)) ?> - حد أقصى <?= $customImgMaxSize ?>MB لكل صورة</p>
                                <p class="upload-hint" style="margin-top: 5px;">
                                    <?php if ($customImgMin > 0): ?>
                                    <span style="color: #e91e8c;">مطلوب: <?= $customImgMin ?> صورة على الأقل</span> | 
                                    <?php endif; ?>
                                    الحد الأقصى: <?= $customImgMax ?> صور
                                </p>
                                <input type="file" id="customImages" accept="<?= $acceptTypes ?>" multiple style="display: none;">
                            </div>
                            
                            <!-- معاينة الصور المرفوعة -->
                            <div id="multiUploadPreview" style="display: none; margin-top: 20px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                    <h4 style="margin: 0; color: var(--text-dark);">📸 الصور المرفوعة (<span id="uploadedCount">0</span>/<?= $customImgMax ?>)</h4>
                                    <button type="button" class="btn btn-sm btn-outline" id="addMoreBtn" style="display: none;">
                                        + إضافة المزيد
                                    </button>
                                </div>
                                <div id="previewGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(100px, 1fr)); gap: 12px;"></div>
                                <p id="minImagesWarning" style="display: none; color: #ff9800; font-size: 0.9rem; margin-top: 10px;">
                                    ⚠️ يرجى رفع <?= $customImgMin ?> صورة على الأقل
                                </p>
                            </div>
                        </div>
                        
                        <script>
                        // إعدادات رفع الصور المتعددة
                        window.customImagesConfig_<?= $product['id'] ?> = {
                            enabled: true,
                            required: <?= $customImgRequired ? 'true' : 'false' ?>,
                            minImages: <?= $customImgMin ?>,
                            maxImages: <?= $customImgMax ?>,
                            maxSizeMB: <?= $customImgMaxSize ?>,
                            allowedTypes: <?= json_encode($customImgTypes) ?>,
                            uploadedImages: []
                        };
                        </script>
                        <?php else: ?>
                        <!-- واجهة رفع صورة واحدة (الأصلية) -->
                        <div class="upload-area" id="uploadArea">
                            <div class="upload-icon">📷</div>
                            <p class="upload-text">اضغط لرفع صورتك</p>
                            <p class="upload-hint">JPG, PNG, WEBP - حد أقصى 5MB</p>
                            <input type="file" id="customImage" accept="image/jpeg,image/png,image/webp" style="display: none;">
                        </div>
                        
                        <div id="uploadPreview" style="display: none; margin-top: 20px; text-align: center;">
                            <img id="previewImage" style="max-width: 200px; border-radius: 12px; margin-bottom: 10px; box-shadow: var(--shadow-md);">
                            <p style="color: #4CAF50; font-weight: 600;">✓ تم رفع الصورة بنجاح</p>
                            <button class="btn btn-sm btn-outline" onclick="removeUpload()" style="margin-top: 10px;">🗑️ إزالة الصورة</button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Product Meta -->
                    <div class="product-meta">
                        <div class="product-meta-item">
                            <span>📂</span>
                            <span>القسم:</span>
                            <?php $cats = getCategories(); ?>
                            <a href="/products?category=<?= $product['category'] ?>" style="color: var(--primary); font-weight: 600;">
                                <?= isset($cats[$product['category']]['icon']) ? $cats[$product['category']]['icon'] : '' ?> 
                                <?= isset($cats[$product['category']]['name']) ? $cats[$product['category']]['name'] : $product['category'] ?>
                            </a>
                        </div>
                        <div class="product-meta-item">
                            <span>🚚</span>
                            <span>التوصيل:</span>
                            <span style="font-weight: 600;"><?= formatPrice(isset($settings['delivery_price']) ? $settings['delivery_price'] : 5000) ?> لجميع المحافظات</span>
                        </div>
                    </div>
                    
                    <!-- Payment Methods Section -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid rgba(233, 30, 140, 0.1);">
                        <h4 style="color: var(--primary); margin-bottom: 15px; font-size: 1rem; display: flex; align-items: center; gap: 8px;">
                            💳 طرق الدفع المتاحة
                        </h4>
                        <?= renderPaymentMethodsHTML('full') ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <?php 
        $reviews = getProductReviews($product['id']);
        $ratingStats = getProductRatingStats($product['id']);
        $initialReviewsCount = 3; // عدد التقييمات المعروضة أولاً
        $totalReviews = count($reviews);
        ?>
        <div class="container" style="margin-top: 40px; margin-bottom: 40px;">
            <div class="reviews-section" id="reviewsSection">
                <!-- Reviews Header with Stats -->
                <div class="reviews-header-section">
                    <h2 class="reviews-title">
                        ⭐ تقييمات العملاء
                    </h2>
                    <?php if ($ratingStats['count'] > 0): ?>
                    <div class="reviews-summary">
                        <div class="rating-big">
                            <span class="rating-number"><?= $ratingStats['average'] ?></span>
                            <span class="rating-max">/5</span>
                        </div>
                        <div class="rating-stars-big">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <?php if ($i <= floor($ratingStats['average'])): ?>
                                    <span class="star filled">★</span>
                                <?php elseif ($i - 0.5 <= $ratingStats['average']): ?>
                                    <span class="star half">★</span>
                                <?php else: ?>
                                    <span class="star empty">☆</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </div>
                        <div class="rating-count"><?= $ratingStats['count'] ?> تقييم</div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Add Review Form -->
                <div class="add-review-form">
                    <h3 class="form-title">📝 شاركنا رأيك</h3>
                    
                    <div id="reviewForm">
                        <!-- Star Rating -->
                        <div class="form-group">
                            <label class="form-label">تقييمك بالنجوم:</label>
                            <div class="star-rating-input" id="starRatingInput">
                                <span class="star-btn" data-value="1" title="ضعيف">☆</span>
                                <span class="star-btn" data-value="2" title="مقبول">☆</span>
                                <span class="star-btn" data-value="3" title="جيد">☆</span>
                                <span class="star-btn" data-value="4" title="جيد جداً">☆</span>
                                <span class="star-btn" data-value="5" title="ممتاز">☆</span>
                            </div>
                            <input type="hidden" id="ratingValue" value="0">
                            <span class="rating-hint" id="ratingHint">اختر تقييمك</span>
                        </div>
                        
                        <!-- Name (Optional) -->
                        <div class="form-group">
                            <label class="form-label">اسمك <span class="optional">(اختياري)</span>:</label>
                            <input type="text" id="reviewerName" class="form-input" placeholder="اتركه فارغاً للظهور كـ 'عميل'">
                        </div>
                        
                        <!-- Comment -->
                        <div class="form-group">
                            <label class="form-label">تعليقك:</label>
                            <textarea id="reviewComment" class="form-textarea" rows="3" placeholder="شاركنا تجربتك مع هذا المنتج..."></textarea>
                        </div>
                        
                        <button type="button" id="submitReviewBtn" class="btn btn-primary submit-review-btn">
                            <span class="btn-icon">✓</span> إرسال التقييم
                        </button>
                        
                        <div id="reviewMessage" class="review-message"></div>
                    </div>
                </div>
                
                <!-- Reviews List -->
                <div class="reviews-list" id="reviewsList">
                    <?php if (empty($reviews)): ?>
                    <div class="empty-reviews">
                        <div class="empty-icon">💬</div>
                        <h3>لا توجد تقييمات بعد</h3>
                        <p>كن أول من يقيّم هذا المنتج!</p>
                    </div>
                    <?php else: ?>
                    
                    <?php foreach ($reviews as $index => $review): ?>
                    <div class="review-item <?= $index >= $initialReviewsCount ? 'hidden-review' : '' ?>" data-review-index="<?= $index ?>">
                        <div class="review-item-header">
                            <div class="reviewer-info">
                                <div class="reviewer-avatar">👤</div>
                                <div class="reviewer-details">
                                    <span class="reviewer-name"><?= htmlspecialchars($review['customer_name']) ?></span>
                                    <span class="review-date"><?= formatReviewDate($review['created_at']) ?></span>
                                </div>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="star <?= $i <= $review['rating'] ? 'filled' : 'empty' ?>"><?= $i <= $review['rating'] ? '★' : '☆' ?></span>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                        <div class="review-comment-text">
                            "<?= nl2br(htmlspecialchars($review['comment'])) ?>"
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($totalReviews > $initialReviewsCount): ?>
                    <div class="load-more-container" id="loadMoreContainer">
                        <button type="button" id="loadMoreReviews" class="btn btn-outline load-more-btn">
                            👁 عرض المزيد (<span id="hiddenCount"><?= $totalReviews - $initialReviewsCount ?></span> تقييم آخر)
                        </button>
                    </div>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <style>
            /* Reviews Section Styles */
            .reviews-section {
                background: white;
                border-radius: var(--radius-xl);
                padding: 30px;
                box-shadow: var(--shadow-lg);
                border: 1px solid rgba(233, 30, 140, 0.1);
            }
            
            .reviews-header-section {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 2px solid #f5f5f5;
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .reviews-title {
                color: var(--primary);
                font-size: 1.5rem;
                margin: 0;
            }
            
            .reviews-summary {
                display: flex;
                align-items: center;
                gap: 15px;
                background: linear-gradient(135deg, #fff8e1, #fffde7);
                padding: 15px 25px;
                border-radius: var(--radius-lg);
                border: 1px solid rgba(244, 180, 0, 0.2);
            }
            
            .rating-big {
                text-align: center;
            }
            
            .rating-number {
                font-size: 2rem;
                font-weight: 800;
                color: #f4b400;
            }
            
            .rating-max {
                font-size: 1rem;
                color: var(--text-muted);
            }
            
            .rating-stars-big {
                font-size: 1.5rem;
            }
            
            .rating-stars-big .star {
                color: #f4b400;
            }
            
            .rating-stars-big .star.empty {
                color: #ddd;
            }
            
            .rating-count {
                color: var(--text-muted);
                font-size: 0.9rem;
            }
            
            /* Add Review Form */
            .add-review-form {
                background: linear-gradient(135deg, #fef5f9, #fff0f5);
                border-radius: var(--radius-lg);
                padding: 25px;
                margin-bottom: 30px;
                border: 2px dashed rgba(233, 30, 140, 0.15);
            }
            
            .form-title {
                margin: 0 0 20px 0;
                color: var(--text-dark);
                font-size: 1.1rem;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-label {
                display: block;
                margin-bottom: 10px;
                font-weight: 600;
                color: var(--text-dark);
            }
            
            .form-label .optional {
                font-weight: 400;
                color: var(--text-muted);
                font-size: 0.85rem;
            }
            
            .star-rating-input {
                display: flex;
                gap: 5px;
                font-size: 2.2rem;
            }
            
            .star-rating-input .star-btn {
                cursor: pointer;
                color: #ddd;
                transition: all 0.2s ease;
                user-select: none;
            }
            
            .star-rating-input .star-btn:hover {
                transform: scale(1.2);
            }
            
            .star-rating-input .star-btn.active {
                color: #f4b400;
            }
            
            .rating-hint {
                display: block;
                margin-top: 8px;
                font-size: 0.85rem;
                color: var(--text-muted);
            }
            
            .form-input, .form-textarea {
                width: 100%;
                padding: 14px 18px;
                border: 2px solid #eee;
                border-radius: var(--radius-md);
                font-size: 1rem;
                transition: all 0.3s ease;
                font-family: inherit;
            }
            
            .form-input:focus, .form-textarea:focus {
                border-color: var(--primary);
                outline: none;
                box-shadow: 0 0 0 4px rgba(233, 30, 140, 0.1);
            }
            
            .form-textarea {
                resize: vertical;
                min-height: 100px;
            }
            
            .submit-review-btn {
                padding: 14px 35px;
                font-size: 1rem;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .review-message {
                margin-top: 15px;
                padding: 15px;
                border-radius: var(--radius-md);
                font-weight: 600;
                display: none;
            }
            
            .review-message.success {
                display: block;
                background: linear-gradient(135deg, #d4edda, #c3e6cb);
                color: #155724;
            }
            
            .review-message.error {
                display: block;
                background: linear-gradient(135deg, #f8d7da, #f5c6cb);
                color: #721c24;
            }
            
            /* Reviews List */
            .empty-reviews {
                text-align: center;
                padding: 50px 20px;
                color: var(--text-muted);
            }
            
            .empty-reviews .empty-icon {
                font-size: 4rem;
                margin-bottom: 15px;
                opacity: 0.5;
            }
            
            .empty-reviews h3 {
                margin: 0 0 10px 0;
                color: var(--text-dark);
            }
            
            .empty-reviews p {
                margin: 0;
            }
            
            /* Review Item */
            .review-item {
                padding: 20px 0;
                border-bottom: 1px solid #f0f0f0;
                animation: fadeIn 0.3s ease;
            }
            
            .review-item:last-child {
                border-bottom: none;
            }
            
            .review-item.hidden-review {
                display: none;
            }
            
            .review-item-header {
                display: flex;
                justify-content: space-between;
                align-items: flex-start;
                margin-bottom: 12px;
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .reviewer-info {
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .reviewer-avatar {
                width: 45px;
                height: 45px;
                border-radius: 50%;
                background: linear-gradient(135deg, var(--primary), #ff6b9d);
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.3rem;
                color: white;
            }
            
            .reviewer-details {
                display: flex;
                flex-direction: column;
            }
            
            .reviewer-name {
                font-weight: 700;
                color: var(--text-dark);
            }
            
            .review-date {
                font-size: 0.8rem;
                color: var(--text-muted);
            }
            
            .review-rating {
                font-size: 1.2rem;
            }
            
            .review-rating .star.filled {
                color: #f4b400;
            }
            
            .review-rating .star.empty {
                color: #ddd;
            }
            
            .review-comment-text {
                background: linear-gradient(135deg, #f9f9f9, #f5f5f5);
                padding: 18px 20px;
                border-radius: var(--radius-md);
                color: #444;
                line-height: 1.8;
                font-size: 0.95rem;
                border-right: 4px solid var(--primary);
            }
            
            /* Load More */
            .load-more-container {
                text-align: center;
                padding: 25px 0 10px;
            }
            
            .load-more-btn {
                padding: 12px 30px;
                font-size: 0.95rem;
            }
            
            @keyframes fadeIn {
                from { opacity: 0; transform: translateY(10px); }
                to { opacity: 1; transform: translateY(0); }
            }
            
            /* Mobile Responsive - Enhanced */
            @media (max-width: 768px) {
                .reviews-section {
                    padding: 15px;
                    border-radius: var(--radius-lg);
                    margin: 0 -10px;
                }
                
                .reviews-header-section {
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    gap: 15px;
                    padding-bottom: 15px;
                    margin-bottom: 20px;
                }
                
                .reviews-title {
                    font-size: 1.25rem;
                    width: 100%;
                }
                
                .reviews-summary {
                    width: 100%;
                    justify-content: center;
                    padding: 12px 15px;
                    gap: 10px;
                }
                
                .rating-number {
                    font-size: 1.6rem;
                }
                
                .rating-stars-big {
                    font-size: 1.2rem;
                }
                
                .rating-count {
                    font-size: 0.8rem;
                }
                
                /* Form Improvements for Mobile */
                .add-review-form {
                    padding: 18px;
                    margin-bottom: 20px;
                }
                
                .form-title {
                    font-size: 1rem;
                    text-align: center;
                    margin-bottom: 18px;
                }
                
                .form-group {
                    margin-bottom: 15px;
                }
                
                .form-label {
                    font-size: 0.9rem;
                    margin-bottom: 8px;
                }
                
                .star-rating-input {
                    font-size: 2rem;
                    justify-content: center;
                    gap: 8px;
                }
                
                .star-rating-input .star-btn {
                    padding: 5px;
                }
                
                .rating-hint {
                    text-align: center;
                    margin-top: 10px;
                }
                
                .form-input, .form-textarea {
                    padding: 12px 14px;
                    font-size: 16px; /* Prevents zoom on iOS */
                }
                
                .submit-review-btn {
                    width: 100%;
                    padding: 14px 20px;
                    font-size: 1rem;
                    justify-content: center;
                }
                
                /* Review Items - Mobile Optimized */
                .review-item {
                    padding: 15px 0;
                    margin: 0;
                }
                
                .review-item-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 12px;
                }
                
                .reviewer-info {
                    width: 100%;
                    gap: 10px;
                }
                
                .reviewer-avatar {
                    width: 40px;
                    height: 40px;
                    font-size: 1.1rem;
                    flex-shrink: 0;
                }
                
                .reviewer-details {
                    flex: 1;
                    min-width: 0;
                }
                
                .reviewer-name {
                    font-size: 0.95rem;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                
                .review-date {
                    font-size: 0.75rem;
                }
                
                .review-rating {
                    display: flex;
                    align-items: center;
                    width: 100%;
                    padding: 8px 12px;
                    background: linear-gradient(135deg, #fffde7, #fff8e1);
                    border-radius: var(--radius-md);
                    font-size: 1.1rem;
                    letter-spacing: 3px;
                    justify-content: center;
                }
                
                .review-comment-text {
                    padding: 14px 16px;
                    font-size: 0.9rem;
                    line-height: 1.7;
                    border-right-width: 3px;
                    margin-top: 12px;
                }
                
                /* Empty State Mobile */
                .empty-reviews {
                    padding: 40px 15px;
                }
                
                .empty-reviews .empty-icon {
                    font-size: 3rem;
                }
                
                .empty-reviews h3 {
                    font-size: 1rem;
                }
                
                .empty-reviews p {
                    font-size: 0.85rem;
                }
                
                /* Load More Button - Mobile */
                .load-more-container {
                    padding: 20px 0 5px;
                }
                
                .load-more-btn {
                    width: 100%;
                    padding: 14px 20px;
                    font-size: 0.95rem;
                }
            }
            
            /* Extra small devices */
            @media (max-width: 400px) {
                .reviews-section {
                    padding: 12px;
                }
                
                .star-rating-input {
                    font-size: 1.7rem;
                }
                
                .reviewer-avatar {
                    width: 36px;
                    height: 36px;
                    font-size: 1rem;
                }
                
                .review-rating {
                    font-size: 1rem;
                    letter-spacing: 2px;
                }
                
                .review-comment-text {
                    padding: 12px 14px;
                    font-size: 0.85rem;
                }
            }
        </style>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <img src="images/logo.jpg" alt="<?= SITE_NAME ?>">
                        <span><?= SITE_NAME ?></span>
                    </div>
                    <p>
                        متجر الهدايا الفاخرة في العراق. نقدم لكم أجمل التحف والهدايا المخصصة بأعلى جودة وأفضل الأسعار.
                    </p>
                    <div class="social-links">
                        <a href="<?= INSTAGRAM_URL ?>" target="_blank" class="social-link instagram" title="Instagram">
                            <span class="social-text"><?= INSTAGRAM_USER ?>@</span>
                            <span class="social-name">Instagram</span>
                            <img src="images/icons/icon1.png" alt="Instagram" class="social-icon">
                        </a>
                        <a href="<?= TELEGRAM_CHANNEL_URL ?>" target="_blank" class="social-link telegram" title="Telegram">
                            <span class="social-text"><?= TELEGRAM_CHANNEL ?>@</span>
                            <span class="social-name">Telegram</span>
                            <img src="images/icons/icon2.png" alt="Telegram" class="social-icon">
                        </a>
                    </div>
                </div>
                
                <div>
                    <h3 class="footer-title">روابط سريعة</h3>
                    <ul class="footer-links">
                        <li><a href="/">🏠 الرئيسية</a></li>
                        <li><a href="/products">🛍️ جميع المنتجات</a></li>
                        <li><a href="/cart">🛒 سلة التسوق</a></li>
                        <li><a href="/privacy">🔒 سياسة الخصوصية</a></li>
                        <li><a href="/terms">📜 الشروط والأحكام</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">الأقسام</h3>
                    <ul class="footer-links">
                        <?php foreach (getCategories() as $key => $cat): ?>
                        <li><a href="/products?category=<?= $key ?>"><?= $cat['icon'] ?> <?= $cat['name'] ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">التوصيل</h3>
                    <ul class="footer-links">
                        <li>📦 جميع المحافظات: <?= formatPrice(isset($settings['delivery_price']) ? $settings['delivery_price'] : 5000) ?></li>
                        <li>🚚 توصيل سريع وآمن</li>
                        <?= renderPaymentMethodsHTML('list') ?>
                        <li>📞 دعم على مدار الساعة</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>© <?= date('Y') ?> <?= SITE_NAME ?> - جميع الحقوق محفوظة 💖</p>
            </div>
        </div>
    </footer>

    <script>
        window.INSTAGRAM_URL = '<?= INSTAGRAM_URL ?>';
        window.INSTAGRAM_USER = '<?= INSTAGRAM_USER ?>';
        window.TELEGRAM_URL = '<?= TELEGRAM_CHANNEL_URL ?>';
        window.TELEGRAM_USER = '<?= TELEGRAM_ORDER_USERNAME ?>';
    </script>
    <script src="<?= v('js/api-helper.js') ?>"></script>
    <script src="<?= v('js/app.js') ?>"></script>
    <script src="<?= v('js/cart.js') ?>"></script>
    <script>
        // ═══════════════════════════════════════════════════════════════
        // PRODUCT GALLERY - Manual Navigation (NO Auto-Sliding)
        // ═══════════════════════════════════════════════════════════════
        
        let currentGalleryIndex = 0;
        const galleryImages = document.querySelectorAll('#productGallerySlider .slider-img');
        const galleryDots = document.querySelectorAll('#productGallerySlider .slider-dot');
        const galleryThumbs = document.querySelectorAll('.gallery-thumb');
        const totalImages = galleryImages.length;
        
        // Show specific image
        function showGalleryImage(index) {
            if (index < 0) index = totalImages - 1;
            if (index >= totalImages) index = 0;
            
            currentGalleryIndex = index;
            
            // Update images
            galleryImages.forEach((img, i) => {
                img.classList.toggle('active', i === index);
            });
            
            // Update dots
            galleryDots.forEach((dot, i) => {
                dot.classList.toggle('active', i === index);
            });
            
            // Update thumbnails
            galleryThumbs.forEach((thumb, i) => {
                thumb.classList.toggle('active', i === index);
            });
        }
        
        // Navigate forward/backward
        function navigateGallery(direction) {
            showGalleryImage(currentGalleryIndex + direction);
        }
        
        // Keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                navigateGallery(1); // RTL: Left arrow = next
            } else if (e.key === 'ArrowRight') {
                navigateGallery(-1); // RTL: Right arrow = prev
            }
        });
        
        // Touch swipe for gallery
        let touchStartX = 0;
        let touchStartY = 0;
        const gallerySlider = document.getElementById('productGallerySlider');
        
        if (gallerySlider) {
            gallerySlider.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
                touchStartY = e.changedTouches[0].screenY;
            }, { passive: true });
            
            gallerySlider.addEventListener('touchmove', (e) => {
                const deltaX = Math.abs(e.changedTouches[0].screenX - touchStartX);
                const deltaY = Math.abs(e.changedTouches[0].screenY - touchStartY);
                if (deltaX > deltaY && deltaX > 10) {
                    e.preventDefault(); // Prevent vertical scroll during horizontal swipe
                }
            }, { passive: false });
            
            gallerySlider.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].screenX;
                const touchEndY = e.changedTouches[0].screenY;
                const diffX = touchStartX - touchEndX;
                const diffY = Math.abs(touchStartY - touchEndY);
                
                // Only handle horizontal swipes
                if (Math.abs(diffX) > 50 && Math.abs(diffX) > diffY) {
                    if (diffX > 0) {
                        // Swipe left (RTL: previous)
                        navigateGallery(-1);
                    } else {
                        // Swipe right (RTL: next)
                        navigateGallery(1);
                    }
                }
            }, { passive: true });
        }
        
        // Custom image upload
        const uploadArea = document.getElementById('uploadArea');
        const fileInput = document.getElementById('customImage');
        const uploadPreview = document.getElementById('uploadPreview');
        const previewImage = document.getElementById('previewImage');
        
        if (uploadArea && fileInput) {
            uploadArea.addEventListener('click', () => fileInput.click());
            
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    handleUpload(e.dataTransfer.files[0]);
                }
            });
            
            fileInput.addEventListener('change', function() {
                if (this.files.length) {
                    handleUpload(this.files[0]);
                }
            });

            // Initialize button state
            disableAddToCart();
            
            // Setup mobile upload button click handler
            setupMobileUploadButton();
        }
        
        async function handleUpload(file) {
            // Validate
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 10 * 1024 * 1024; // 10MB for high quality
            
            if (!allowedTypes.includes(file.type)) {
                showNotification('نوع الملف غير مسموح! استخدم JPG, PNG, WEBP', 'error');
                return;
            }
            
            if (file.size > maxSize) {
                showNotification('حجم الملف كبير جداً! الحد الأقصى 10MB', 'error');
                return;
            }
            
            // Show loading state
            uploadArea.innerHTML = '<div class="upload-icon">⏳</div><p class="upload-text">جاري رفع الصورة...</p>';
            
            try {
                // Create FormData and upload to server
                const formData = new FormData();
                formData.append('image', file);
                
                const response = await fetch('api/upload-image.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Store the uploaded filename (not base64)
                    window.uploadedImagePath_<?= $product['id'] ?> = result.filename;
                    
                    // Preview image
                    previewImage.src = result.url;
                    uploadArea.style.display = 'none';
                    uploadPreview.style.display = 'block';
                    
                    // Enable add to cart button
                    enableAddToCart();
                    
                    showNotification('✓ تم رفع الصورة بجودة عالية', 'success');
                } else {
                    throw new Error(result.error || 'فشل في رفع الصورة');
                }
            } catch (error) {
                console.error('Upload error:', error);
                showNotification(error.message || 'حدث خطأ أثناء رفع الصورة', 'error');
                
                // Reset upload area
                uploadArea.innerHTML = '<div class="upload-icon">📷</div><p class="upload-text">اضغط لرفع صورتك</p><p class="upload-hint">JPG, PNG, WEBP - حد أقصى 10MB</p>';
            }
        }
        
        // Enable add to cart button after image upload - فقط لرفع الصورة الواحدة
        function enableAddToCart() {
            // تجاهل إذا كان الرفع المتعدد مفعلاً
            if (document.getElementById('multiUploadArea')) {
                return;
            }
            
            const addBtn = document.getElementById('addToCartBtn');
            const mobileBtn = document.getElementById('mobileAddToCartBtn');
            const btns = [addBtn, mobileBtn].filter(b => b !== null);

            btns.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.style.cursor = 'pointer';
                btn.innerHTML = btn.id === 'mobileAddToCartBtn' 
                    ? '<span class="btn-icon">🛒</span><span>أضف للسلة</span>' 
                    : '🛒 أضف للسلة';
                btn.classList.remove('add-to-cart-custom');
                btn.classList.add('add-to-cart');
                
                // Add click event
                btn.onclick = function(e) {
                    e.preventDefault();
                    
                    let uploadedFilename = window.uploadedImagePath_<?= $product['id'] ?>;
                    if (!uploadedFilename) {
                        showNotification('يرجى رفع الصورة أولاً', 'error');
                        return;
                    }
                    
                    const productData = {
                        id: btn.dataset.id,
                        name: btn.dataset.name,
                        price: btn.dataset.price,
                        image: btn.dataset.image,
                        hasCustomImage: true,
                        customImage: uploadedFilename,
                        customImageUrl: 'images/uploads/' + uploadedFilename
                    };
                    
                    if (typeof getSelectedProductOptions === 'function') {
                        productData.selectedOptions = getSelectedProductOptions();
                    }
                    
                    Cart.add(productData);
                    
                    const originalHTML = btn.innerHTML;
                    btn.innerHTML = '✓ تمت الإضافة';
                    btn.disabled = true;
                    setTimeout(() => {
                        btn.innerHTML = originalHTML;
                        btn.disabled = false;
                    }, 1500);
                };
            });
        }
        
        // Disable add to cart button - فقط لرفع الصورة الواحدة
        function disableAddToCart() {
            // تجاهل إذا كان الرفع المتعدد مفعلاً
            if (document.getElementById('multiUploadArea')) {
                return;
            }
            
            const addBtn = document.getElementById('addToCartBtn');
            const mobileBtn = document.getElementById('mobileAddToCartBtn');
            
            if (addBtn) {
                addBtn.disabled = true;
                addBtn.style.opacity = '0.5';
                addBtn.style.cursor = 'not-allowed';
                addBtn.innerHTML = '📷 ارفع صورتك أولاً';
                addBtn.classList.remove('add-to-cart');
                addBtn.classList.add('add-to-cart-custom');
                addBtn.onclick = null;
            }

            if (mobileBtn) {
                mobileBtn.innerHTML = '<span class="btn-icon">📷</span><span>ارفع صورتك</span>';
                mobileBtn.classList.remove('add-to-cart');
                mobileBtn.classList.add('mobile-btn-upload');
                mobileBtn.disabled = false;
                mobileBtn.style.opacity = '1';
                mobileBtn.style.cursor = 'pointer';
            }
        }
        
        // Setup mobile upload button - handles click to trigger file input
        // فقط لرفع الصورة الواحدة
        function setupMobileUploadButton() {
            // تجاهل إذا كان الرفع المتعدد مفعلاً
            if (document.getElementById('multiUploadArea')) {
                return;
            }
            
            const mobileUploadBtn = document.getElementById('mobileAddToCartBtn');
            const customImageInput = document.getElementById('customImage');
            
            if (mobileUploadBtn && customImageInput) {
                // Remove any existing listeners by cloning once at setup
                const newBtn = mobileUploadBtn.cloneNode(true);
                mobileUploadBtn.parentNode.replaceChild(newBtn, mobileUploadBtn);
                
                // Add the upload click handler
                newBtn.addEventListener('click', function(e) {
                    // Check if image is already uploaded (button shows "أضف للسلة")
                    if (this.classList.contains('add-to-cart')) {
                        // Don't prevent - let onclick handler from enableAddToCart work
                        return;
                    }
                    
                    // Otherwise, trigger file input for upload
                    e.preventDefault();
                    e.stopPropagation();
                    
                    const fileInput = document.getElementById('customImage');
                    if (fileInput) {
                        fileInput.click();
                    }
                }, { passive: false });
            }
        }
        
        function removeUpload() {
            // Reset upload area content to original state immediately
            uploadArea.innerHTML = '<div class="upload-icon">📷</div><p class="upload-text">اضغط لرفع صورتك</p><p class="upload-hint">JPG, PNG, WEBP - حد أقصى 5MB</p>';
            uploadArea.style.display = 'block';
            uploadPreview.style.display = 'none';
            previewImage.src = '';
            fileInput.value = '';
            sessionStorage.removeItem('customImage_<?= $product['id'] ?>');
            window.uploadedImagePath_<?= $product['id'] ?> = null;
            
            // Disable add to cart button again
            disableAddToCart();
            
            // Re-setup mobile upload button handler
            setupMobileUploadButton();
        }
        
        // ========== REVIEWS SYSTEM ==========
        (function() {
            const starContainer = document.getElementById('starRatingInput');
            const ratingInput = document.getElementById('ratingValue');
            const ratingHint = document.getElementById('ratingHint');
            const submitBtn = document.getElementById('submitReviewBtn');
            const reviewMessage = document.getElementById('reviewMessage');
            const loadMoreBtn = document.getElementById('loadMoreReviews');
            const productId = <?= $product['id'] ?>;
            
            const ratingLabels = ['', 'ضعيف 😕', 'مقبول 🙂', 'جيد 👍', 'جيد جداً 😊', 'ممتاز! 🌟'];
            
            if (!starContainer) return;
            
            const stars = starContainer.querySelectorAll('.star-btn');
            
            // Star rating interaction
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const value = parseInt(this.dataset.value);
                    ratingInput.value = value;
                    updateStars(value);
                    if (ratingHint) ratingHint.textContent = ratingLabels[value];
                });
                
                star.addEventListener('mouseenter', function() {
                    const value = parseInt(this.dataset.value);
                    highlightStars(value);
                    if (ratingHint) ratingHint.textContent = ratingLabels[value];
                });
            });
            
            starContainer.addEventListener('mouseleave', function() {
                const currentRating = parseInt(ratingInput.value) || 0;
                updateStars(currentRating);
                if (ratingHint) {
                    ratingHint.textContent = currentRating > 0 ? ratingLabels[currentRating] : 'اختر تقييمك';
                }
            });
            
            function updateStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.textContent = '★';
                        star.style.color = '#f4b400';
                        star.classList.add('active');
                    } else {
                        star.textContent = '☆';
                        star.style.color = '#ddd';
                        star.classList.remove('active');
                    }
                });
            }
            
            function highlightStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.textContent = '★';
                        star.style.color = '#f4b400';
                    } else {
                        star.textContent = '☆';
                        star.style.color = '#ddd';
                    }
                });
            }
            
            // Submit review
            if (submitBtn) {
                submitBtn.addEventListener('click', async function() {
                    const rating = parseInt(ratingInput.value);
                    const name = document.getElementById('reviewerName').value.trim();
                    const comment = document.getElementById('reviewComment').value.trim();
                    
                    if (rating < 1) {
                        showMessage('يرجى اختيار تقييم بالنجوم ⭐', 'error');
                        return;
                    }
                    
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="btn-icon">⏳</span> جاري الإرسال...';
                    
                    try {
                        const response = await fetch('api/reviews.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                product_id: productId,
                                rating: rating,
                                comment: comment,
                                customer_name: name
                            })
                        });
                        
                        const data = await response.json();
                        
                        if (data.success) {
                            showMessage('🎉 ' + data.message, 'success');
                            // Reset form
                            ratingInput.value = 0;
                            updateStars(0);
                            if (ratingHint) ratingHint.textContent = 'اختر تقييمك';
                            document.getElementById('reviewerName').value = '';
                            document.getElementById('reviewComment').value = '';
                            
                            // Reload page to show new review
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showMessage('❌ ' + (data.error || 'حدث خطأ'), 'error');
                        }
                    } catch (err) {
                        showMessage('❌ حدث خطأ في الاتصال', 'error');
                    }
                    
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<span class="btn-icon">✓</span> إرسال التقييم';
                });
            }
            
            // Load More Reviews
            if (loadMoreBtn) {
                loadMoreBtn.addEventListener('click', function() {
                    const hiddenReviews = document.querySelectorAll('.review-item.hidden-review');
                    hiddenReviews.forEach(review => {
                        review.classList.remove('hidden-review');
                        review.style.animation = 'fadeIn 0.5s ease';
                    });
                    
                    // Hide the load more button
                    document.getElementById('loadMoreContainer').style.display = 'none';
                });
            }
            
            function showMessage(text, type) {
                reviewMessage.className = 'review-message ' + type;
                reviewMessage.textContent = text;
                reviewMessage.style.display = 'block';
                
                setTimeout(() => {
                    reviewMessage.style.display = 'none';
                    reviewMessage.className = 'review-message';
                }, 5000);
            }
        })();
    </script>
    
    <script src="<?= v('js/wishlist.js') ?>"></script>
    
    <!-- Share Product Function -->
    <script>
        function shareProduct() {
            const productName = '<?= addslashes($product['name']) ?>';
            const productUrl = window.location.href;
            const shareText = productName + ' - ' + productUrl;
            
            // Try Web Share API first (mobile)
            if (navigator.share) {
                navigator.share({
                    title: productName,
                    text: 'شاهد هذا المنتج من Surprise!',
                    url: productUrl
                }).catch(err => {
                    console.log('Share cancelled');
                });
            } else {
                // Fallback: Copy to clipboard
                navigator.clipboard.writeText(shareText).then(() => {
                    showShareToast('✅ تم نسخ الرابط!');
                }).catch(() => {
                    // Final fallback: prompt
                    prompt('انسخ رابط المنتج:', productUrl);
                });
            }
        }
        
        function showShareToast(message) {
            let toast = document.getElementById('shareToast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'shareToast';
                toast.style.cssText = `
                    position: fixed;
                    bottom: calc(90px + env(safe-area-inset-bottom));
                    left: 50%;
                    transform: translateX(-50%) translateY(100px);
                    background: linear-gradient(135deg, #28a745, #20c997);
                    color: white;
                    padding: 12px 24px;
                    border-radius: 30px;
                    font-weight: 600;
                    font-size: 0.9rem;
                    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.4);
                    z-index: 10001;
                    opacity: 0;
                    transition: all 0.3s ease;
                    text-align: center;
                `;
                document.body.appendChild(toast);
            }
            
            toast.textContent = message;
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(-50%) translateY(0)';
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-50%) translateY(100px)';
            }, 2500);
        }
        
        // Update wishlist button state on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Both Desktop and Mobile buttons are now handled by Wishlist.updateUI() in wishlist.js
        });
    </script>
    
    <!-- Mobile Fixed Bottom Action Bar -->
    <?php if ($product['status'] === 'available'): ?>
    <div class="mobile-action-bar">
        <!-- 1. Wishlist (LEFT) -->
        <button class="mobile-btn mobile-btn-wishlist" data-wishlist-btn
                data-product-id="<?= $product['id'] ?>"
                data-product-name="<?= htmlspecialchars($product['name']) ?>"
                data-product-price="<?= $product['price'] ?>"
                data-product-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>">
            <img src="images/icons/wishlist.png" alt="المفضلة" class="mobile-action-icon wishlist-mobile-icon">
        </button>
        
        <!-- 2. Upload/Cart (CENTER - Main Button) -->
        <?php if ($product['customizable']): ?>
        <button class="mobile-btn mobile-btn-cart mobile-btn-upload" 
                id="mobileAddToCartBtn"
                data-id="<?= $product['id'] ?>"
                data-name="<?= htmlspecialchars($product['name']) ?>"
                data-price="<?= $product['price'] ?>"
                data-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>">
            <span class="btn-icon">📷</span>
            <span>ارفع صورتك</span>
        </button>
        <?php else: ?>
        <button class="mobile-btn mobile-btn-cart add-to-cart"
                data-id="<?= $product['id'] ?>"
                data-name="<?= $product['name'] ?>"
                data-price="<?= $product['price'] ?>"
                data-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>">
            <span class="btn-icon">🛒</span>
            <span>أضف للسلة</span>
        </button>
        <?php endif; ?>
        
        <!-- 3. Share (RIGHT) -->
        <button class="mobile-btn mobile-btn-share" onclick="shareProduct()">
            <img src="images/icons/share.png" alt="مشاركة" class="mobile-action-icon">
        </button>
    </div>
    <?php endif; ?>
    
    <!-- CSS لرفع الصور المتعددة -->
    <style>
    .multi-upload-area {
        border: 2px dashed var(--primary);
        border-radius: 16px;
        padding: 30px 20px;
        text-align: center;
        background: linear-gradient(135deg, rgba(233, 30, 140, 0.03), rgba(156, 39, 176, 0.03));
        cursor: pointer;
        transition: all 0.3s ease;
    }
    .multi-upload-area:hover {
        border-color: #9c27b0;
        background: linear-gradient(135deg, rgba(233, 30, 140, 0.08), rgba(156, 39, 176, 0.08));
    }
    .multi-upload-area.drag-over {
        border-color: #4CAF50;
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1), rgba(76, 175, 80, 0.05));
    }
    .multi-upload-area .upload-icon {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }
    .preview-item {
        position: relative;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        aspect-ratio: 1;
    }
    .preview-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .preview-item .remove-btn {
        position: absolute;
        top: 5px;
        right: 5px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: rgba(244, 67, 54, 0.9);
        color: white;
        border: none;
        cursor: pointer;
        font-size: 14px;
        line-height: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
    }
    .preview-item .remove-btn:hover {
        background: #d32f2f;
        transform: scale(1.1);
    }
    .preview-item .uploading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }
    </style>
    
    <!-- JavaScript لرفع الصور المتعددة - نفس منطق الصورة الواحدة -->
    <script>
    (function() {
        'use strict';
        
        const productId = <?= $product['id'] ?>;
        const config = window['customImagesConfig_' + productId];
        
        // إذا لم يكن الرفع المتعدد مفعلاً، اخرج
        if (!config || !config.enabled) return;
        
        const uploadArea = document.getElementById('multiUploadArea');
        const fileInput = document.getElementById('customImages');
        const previewContainer = document.getElementById('multiUploadPreview');
        const previewGrid = document.getElementById('previewGrid');
        const uploadedCountEl = document.getElementById('uploadedCount');
        const addMoreBtn = document.getElementById('addMoreBtn');
        const minWarning = document.getElementById('minImagesWarning');
        
        if (!uploadArea || !fileInput) return;
        
        // ═══════════════════════════════════════════════════════════════
        // المصفوفة الرئيسية - المصدر الوحيد للحقيقة
        // ═══════════════════════════════════════════════════════════════
        let uploadedImages = [];
        
        // ═══════════════════════════════════════════════════════════════
        // رفع الصور - نفس أسلوب الصورة الواحدة (click بسيط)
        // ═══════════════════════════════════════════════════════════════
        uploadArea.addEventListener('click', function() {
            fileInput.click();
        });
        
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('dragover');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            if (e.dataTransfer.files.length) {
                handleFiles(e.dataTransfer.files);
            }
        });
        
        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                handleFiles(this.files);
            }
            this.value = '';
        });
        
        // ═══════════════════════════════════════════════════════════════
        // زر إضافة المزيد - click بسيط
        // ═══════════════════════════════════════════════════════════════
        if (addMoreBtn) {
            addMoreBtn.addEventListener('click', function(e) {
                e.preventDefault();
                fileInput.click();
            });
        }
        
        // ═══════════════════════════════════════════════════════════════
        // زر الجوال - نفس منطق setupMobileUploadButton
        // ═══════════════════════════════════════════════════════════════
        function setupMobileMultiButton() {
            const mobileBtn = document.getElementById('mobileAddToCartBtn');
            if (!mobileBtn) return;
            
            mobileBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                const count = uploadedImages.length;
                const meetsMinimum = count >= config.minImages;
                
                if (count > 0 && meetsMinimum) {
                    addToCart();
                } else {
                    fileInput.click();
                }
            });
        }
        
        // ═══════════════════════════════════════════════════════════════
        // معالجة الملفات
        // ═══════════════════════════════════════════════════════════════
        function handleFiles(files) {
            const maxSizeBytes = config.maxSizeMB * 1024 * 1024;
            
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                if (uploadedImages.length >= config.maxImages) {
                    showNotification('وصلت للحد الأقصى (' + config.maxImages + ' صور)', 'error');
                    break;
                }
                
                const ext = file.name.split('.').pop().toLowerCase();
                if (!config.allowedTypes.includes(ext) && !(ext === 'jpg' && config.allowedTypes.includes('jpeg'))) {
                    showNotification('نوع الملف ' + ext + ' غير مسموح', 'error');
                    continue;
                }
                
                if (file.size > maxSizeBytes) {
                    showNotification('حجم الملف كبير جداً (الحد: ' + config.maxSizeMB + 'MB)', 'error');
                    continue;
                }
                
                uploadImage(file);
            }
        }
        
        // ═══════════════════════════════════════════════════════════════
        // رفع صورة للسيرفر
        // ═══════════════════════════════════════════════════════════════
        async function uploadImage(file) {
            const tempId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            
            // إنشاء preview
            const previewItem = document.createElement('div');
            previewItem.className = 'preview-item';
            previewItem.id = tempId;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                previewItem.innerHTML = '<img src="' + e.target.result + '" alt="جاري الرفع..."><div class="uploading-overlay">⏳</div>';
            };
            reader.readAsDataURL(file);
            
            previewGrid.appendChild(previewItem);
            updateUI();
            
            try {
                const formData = new FormData();
                formData.append('image', file);
                
                const response = await fetch('api/upload-image.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // حفظ في المصفوفة
                    uploadedImages.push({
                        id: tempId,
                        filename: result.filename,
                        url: result.url
                    });
                    
                    // تحديث preview مع زر الحذف
                    previewItem.innerHTML = '<img src="' + result.url + '" alt="صورة مخصصة"><button type="button" class="remove-btn" data-id="' + tempId + '">×</button>';
                    
                    // ربط زر الحذف
                    previewItem.querySelector('.remove-btn').onclick = function() {
                        removeImage(tempId);
                    };
                    
                    updateUI();
                    showNotification('✓ تم رفع الصورة', 'success');
                } else {
                    throw new Error(result.error || 'فشل الرفع');
                }
            } catch (error) {
                previewItem.remove();
                showNotification(error.message || 'خطأ في الرفع', 'error');
                updateUI();
            }
        }
        
        // ═══════════════════════════════════════════════════════════════
        // حذف صورة - نهائي وشامل
        // ═══════════════════════════════════════════════════════════════
        function removeImage(id) {
            // حذف من المصفوفة
            uploadedImages = uploadedImages.filter(function(img) {
                return img.id !== id;
            });
            
            // حذف من DOM
            const el = document.getElementById(id);
            if (el) el.remove();
            
            updateUI();
        }
        
        // للتوافق مع onclick في HTML
        window.removeUploadedImage = removeImage;
        
        // ═══════════════════════════════════════════════════════════════
        // تحديث الواجهة
        // ═══════════════════════════════════════════════════════════════
        function updateUI() {
            const count = uploadedImages.length;
            const meetsMinimum = count >= config.minImages;
            
            // العداد
            if (uploadedCountEl) uploadedCountEl.textContent = count;
            
            // منطقة المعاينة
            if (previewContainer) previewContainer.style.display = count > 0 ? 'block' : 'none';
            
            // منطقة الرفع
            if (uploadArea) uploadArea.style.display = count >= config.maxImages ? 'none' : 'block';
            
            // زر إضافة المزيد
            if (addMoreBtn) addMoreBtn.style.display = (count > 0 && count < config.maxImages) ? 'inline-flex' : 'none';
            
            // تحذير الحد الأدنى
            if (minWarning) minWarning.style.display = (count > 0 && count < config.minImages) ? 'block' : 'none';
            
            // تحديث أزرار السلة
            updateButtons(count, meetsMinimum);
        }
        
        // ═══════════════════════════════════════════════════════════════
        // تحديث أزرار الإضافة للسلة
        // ═══════════════════════════════════════════════════════════════
        function updateButtons(count, meetsMinimum) {
            const desktopBtn = document.getElementById('addToCartBtn');
            const mobileBtn = document.getElementById('mobileAddToCartBtn');
            const canAdd = count > 0 && meetsMinimum;
            
            // زر سطح المكتب
            if (desktopBtn) {
                desktopBtn.disabled = false;
                desktopBtn.style.opacity = '1';
                desktopBtn.style.cursor = 'pointer';
                
                if (canAdd) {
                    desktopBtn.innerHTML = '🛒 أضف للسلة';
                    desktopBtn.onclick = function(e) {
                        e.preventDefault();
                        addToCart();
                    };
                } else {
                    desktopBtn.innerHTML = count === 0 ? '📷 ارفع صورتك أولاً' : '⚠️ يرجى رفع ' + config.minImages + ' صورة على الأقل';
                    desktopBtn.onclick = function(e) {
                        e.preventDefault();
                        fileInput.click();
                    };
                }
            }
            
            // زر الجوال
            if (mobileBtn) {
                mobileBtn.disabled = false;
                mobileBtn.style.opacity = '1';
                
                if (canAdd) {
                    mobileBtn.innerHTML = '<span class="btn-icon">🛒</span><span>أضف للسلة</span>';
                } else {
                    mobileBtn.innerHTML = '<span class="btn-icon">📷</span><span>ارفع صورك</span>';
                }
            }
        }
        
        // ═══════════════════════════════════════════════════════════════
        // إضافة للسلة - نفس أسلوب الصورة الواحدة
        // ═══════════════════════════════════════════════════════════════
        function addToCart() {
            const filenames = uploadedImages.map(function(img) { return img.filename; });
            
            if (filenames.length === 0 || filenames.length < config.minImages) {
                showNotification('يرجى رفع ' + config.minImages + ' صورة على الأقل', 'error');
                return;
            }
            
            const btn = document.getElementById('addToCartBtn');
            if (!btn) return;
            
            const productData = {
                id: btn.dataset.id,
                name: btn.dataset.name,
                price: btn.dataset.price,
                image: btn.dataset.image,
                hasCustomImage: true,
                customImages: filenames.slice(),
                customImage: filenames[0],
                customImageUrl: 'images/uploads/' + filenames[0]
            };
            
            if (typeof getSelectedProductOptions === 'function') {
                productData.selectedOptions = getSelectedProductOptions();
            }
            
            Cart.add(productData);
            
            // تأكيد بصري
            const originalHTML = btn.innerHTML;
            btn.innerHTML = '✓ تمت الإضافة';
            btn.disabled = true;
            
            const mobileBtn = document.getElementById('mobileAddToCartBtn');
            const mobileOriginalHTML = mobileBtn ? mobileBtn.innerHTML : '';
            if (mobileBtn) {
                mobileBtn.innerHTML = '<span class="btn-icon">✓</span><span>تمت الإضافة</span>';
                mobileBtn.disabled = true;
            }
            
            setTimeout(function() {
                btn.innerHTML = originalHTML;
                btn.disabled = false;
                if (mobileBtn) {
                    mobileBtn.innerHTML = mobileOriginalHTML;
                    mobileBtn.disabled = false;
                }
            }, 1500);
        }
        
        // ═══════════════════════════════════════════════════════════════
        // التهيئة
        // ═══════════════════════════════════════════════════════════════
        setupMobileMultiButton();
        updateUI();
        
        console.log('[MultiUpload] تم التهيئة للمنتج:', productId);
    })();
    </script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function(err) {
                    console.log('SW registration failed');
                });
            });
        }
        
        // Setup mobile upload button AFTER it exists in DOM
        // فقط إذا لم يكن الرفع المتعدد مفعلاً (لأنه يتولى إدارة الزر بنفسه)
        (function() {
            var multiUploadArea = document.getElementById('multiUploadArea');
            // إذا كان الرفع المتعدد مفعلاً، لا تستدعي setupMobileUploadButton القديمة
            if (multiUploadArea) {
                console.log('[SingleUpload] تم تعطيل setupMobileUploadButton لأن الرفع المتعدد مفعل');
                return;
            }
            if (typeof setupMobileUploadButton === 'function') {
                setupMobileUploadButton();
            }
        })();
    </script>
</body>
</html>
