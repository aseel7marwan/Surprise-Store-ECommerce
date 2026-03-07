<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/functions.php'; ?>
<?php require_once 'includes/seo.php'; ?>

<?php 
$products = getProducts(['exclude_hidden' => true]);

// Get best sellers - use featured products or latest available
$bestSellers = array_filter($products, function($p) {
    return !empty($p['featured']) && $p['status'] === 'available';
});

if (count($bestSellers) < 4) {
    $available = array_filter($products, function($p) {
        return $p['status'] === 'available';
    });
    $bestSellers = array_slice($available, 0, 8);
} else {
    $bestSellers = array_slice($bestSellers, 0, 8);
}

$settings = getSettings();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيج سبرايز | Surprise page</title>
    
    <!-- SEO Meta Tags -->
    <?= generateMetaTags(array(
        'title' => SITE_BRAND_NAME . ' - متجر هدايا عراقي | هدايا مخصصة، هدايا مناسبات، توصيل داخل العراق',
        'description' => SITE_BRAND_NAME . ' - متجر هدايا أونلاين في العراق - هدايا فخمة ومميزة للمناسبات: هدايا عيد ميلاد، هدايا زواج، هدايا تخرج. تحف مضيئة، ساعات محفورة، أكواب مطبوعة، بوكسات هدايا. توصيل لجميع المحافظات: بغداد، البصرة، أربيل، كربلاء.',
        'type' => 'website'
    )) ?>
    
    <link rel="stylesheet" href="<?= v('css/main.css') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="/images/logo.jpg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo.jpg">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/logo.jpg">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo.jpg">
    <link rel="shortcut icon" href="/images/logo.jpg">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#E91E8C">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    

    <!-- Preconnect for Performance -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    
    <!-- Structured Data -->
    <?= generateWebsiteSchema() ?>
    <?= generateOrganizationSchema() ?>
    <?= generateStoreSchema() ?>
    
    <style>
    /* ═══════════════════════════════════════
       HOMEPAGE PREMIUM STYLES
       ═══════════════════════════════════════ */
    
    /* Hero Banner - Professional Design */
    .hero {
        margin-top: 70px;
        position: relative;
        overflow: hidden;
        background: #111;
    }
    
    .hero-slider {
        display: flex;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .hero-slide {
        min-width: 100%;
        position: relative;
        height: 400px;
    }
    
    .hero-slide img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        display: block;
    }
    
    /* Text Overlay - Centered with Dark Gradient */
    .hero-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        padding: 20px;
        background: linear-gradient(
            to bottom,
            rgba(0, 0, 0, 0.2) 0%,
            rgba(0, 0, 0, 0.4) 50%,
            rgba(0, 0, 0, 0.6) 100%
        );
    }
    
    .hero-title {
        font-family: var(--font-arabic);
        font-size: 2.5rem;
        font-weight: 800;
        color: #FFFFFF;
        text-shadow: 0 3px 15px rgba(0, 0, 0, 0.7), 0 1px 3px rgba(0, 0, 0, 0.9);
        margin-bottom: 12px;
        letter-spacing: 1px;
        line-height: 1.3;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
        font-weight: 500;
        color: rgba(255, 255, 255, 0.9);
        text-shadow: 0 2px 8px rgba(0, 0, 0, 0.8);
        max-width: 600px;
    }
    
    /* Hero Dots */
    .hero-dots {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        display: flex;
        gap: 10px;
        z-index: 10;
    }
    
    .hero-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.5);
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
    }
    
    .hero-dot:hover {
        background: rgba(255, 255, 255, 0.8);
    }
    
    .hero-dot.active {
        background: var(--primary);
        transform: scale(1.3);
        box-shadow: 0 0 10px rgba(233, 30, 140, 0.8);
    }
    
    /* Mobile Hero - Adjusted for Amazon-style two-row header */
    @media (max-width: 768px) {
        .hero {
            margin-top: 120px;
        }
        
        .hero-slide {
            height: 280px;
        }
        
        .hero-title {
            font-size: 1.6rem;
        }
        
        .hero-subtitle {
            font-size: 0.9rem;
        }
        
        .hero-dots {
            bottom: 15px;
        }
        
        .hero-dot {
            width: 8px;
            height: 8px;
        }
    }
    
    @media (max-width: 480px) {
        .hero-slide {
            height: 220px;
        }
        
        .hero-title {
            font-size: 1.3rem;
        }
        
        .hero-subtitle {
            font-size: 0.8rem;
        }
    }
    
    /* Best Sellers Horizontal Slider */
    .bestsellers-section {
        background: #F8F9FA;
        padding: 30px 0;
        border-bottom: 1px solid rgba(233, 30, 140, 0.1);
    }
    
    .bestsellers-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
        padding: 0 20px;
    }
    
    .bestsellers-title {
        font-family: var(--font-heading);
        font-size: 1.3rem;
        color: var(--primary);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .bestsellers-link {
        font-size: 0.85rem;
        color: var(--text-muted);
        transition: var(--transition);
    }
    
    .bestsellers-link:hover {
        color: var(--primary);
    }
    
    .bestsellers-slider {
        display: flex;
        gap: 15px;
        overflow-x: auto;
        padding: 10px 20px 20px;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    
    .bestsellers-slider::-webkit-scrollbar {
        display: none;
    }
    
    .bestseller-card {
        flex: 0 0 160px;
        scroll-snap-align: start;
        background: #FFFFFF;
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid rgba(233, 30, 140, 0.1);
        transition: var(--transition);
        box-shadow: var(--shadow-sm);
    }
    
    .bestseller-card:hover {
        border-color: var(--primary);
        transform: translateY(-3px);
        box-shadow: var(--shadow-gold);
    }
    
    .bestseller-image {
        position: relative;
        height: 160px;
        min-height: 160px;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .bestseller-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: var(--transition);
    }
    
    .bestseller-card:hover .bestseller-image img {
        transform: scale(1.05);
    }
    
    .bestseller-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        background: var(--bg-gold-gradient);
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
    }
    
    .bestseller-info {
        padding: 12px;
    }
    
    .bestseller-name {
        font-size: 0.85rem;
        font-weight: 600;
        color: var(--text-dark);
        margin-bottom: 6px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .bestseller-price {
        font-size: 1rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 10px;
    }
    
    .bestseller-btn {
        width: 100%;
        padding: 8px;
        font-size: 0.75rem;
        font-weight: 600;
        border: none;
        border-radius: 6px;
        background: var(--bg-gold-gradient);
        color: #fff;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
    }
    
    .bestseller-btn:hover {
        box-shadow: var(--shadow-gold);
        transform: scale(1.02);
    }
    
    /* Categories - Instagram Stories Style */
    .categories-stories {
        background: #FFFFFF;
        padding: 25px 0;
        border-bottom: 1px solid rgba(233, 30, 140, 0.08);
    }
    
    .categories-scroll {
        display: flex;
        gap: 20px;
        overflow-x: auto;
        padding: 10px 20px;
        scrollbar-width: none;
    }
    
    .categories-scroll::-webkit-scrollbar {
        display: none;
    }
    
    .category-story {
        flex: 0 0 auto;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        transition: var(--transition);
    }
    
    .category-story:hover {
        transform: scale(1.05);
    }
    
    .category-circle {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, #FFF0F5, #FFFFFF);
        border: 2px solid rgba(233, 30, 140, 0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        transition: var(--transition);
        position: relative;
    }
    
    .category-story:hover .category-circle {
        border-color: var(--primary);
        box-shadow: 0 0 20px rgba(233, 30, 140, 0.25);
    }
    
    .category-circle::before {
        content: '';
        position: absolute;
        inset: -3px;
        border-radius: 50%;
        background: var(--bg-gold-gradient);
        z-index: -1;
        opacity: 0;
        transition: var(--transition);
    }
    
    .category-story:hover .category-circle::before {
        opacity: 1;
    }
    
    .category-name {
        font-size: 0.75rem;
        color: var(--text-muted);
        text-align: center;
        max-width: 80px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    .category-story:hover .category-name {
        color: var(--primary);
    }
    
    /* Features - Compact */
    .features-compact {
        background: #F8F9FA;
        padding: 30px 0;
        border-bottom: 1px solid rgba(233, 30, 140, 0.08);
    }
    
    .features-row {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
    }
    
    .feature-mini {
        text-align: center;
        padding: 15px 10px;
    }
    
    .feature-mini-icon {
        font-size: 1.5rem;
        margin-bottom: 8px;
    }
    
    .feature-mini-text {
       font-size: 0.75rem;
        color: var(--text-muted);
        line-height: 1.4;
    }
    
    /* Section Headers - Minimal */
    .section-minimal {
        padding: 60px 0;
    }
    
    .section-header-minimal {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 30px;
    }
    
    .section-title-minimal {
        font-family: var(--font-heading);
        font-size: 1.5rem;
        color: var(--text-dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .section-title-minimal span {
        color: var(--primary);
    }
    
    /* ═══════════════════════════════════════
       DESKTOP OPTIMIZATIONS
       ═══════════════════════════════════════ */
    @media (min-width: 1024px) {
        .hero {
            max-height: 70vh;
        }
        
        .hero-slide img {
            height: 70vh;
            max-height: 600px;
        }
        
        .bestsellers-section {
            padding: 50px 0;
        }
        
        .bestsellers-header {
            max-width: 1200px;
            margin: 0 auto 30px;
            padding: 0 40px;
        }
        
        .bestsellers-title {
            font-size: 1.8rem;
        }
        
        .bestsellers-slider {
            max-width: 1200px;
            margin: 0 auto;
            padding: 10px 40px 30px;
            gap: 25px;
            justify-content: center;
            flex-wrap: wrap;
            overflow-x: visible;
        }
        
        .bestseller-card {
            flex: 0 0 220px;
        }
        
        .bestseller-image {
            height: 220px;
        }
        
        .bestseller-info {
            padding: 18px;
        }
        
        .bestseller-name {
            font-size: 1rem;
        }
        
        .bestseller-price {
            font-size: 1.2rem;
        }
        
        .bestseller-btn {
            padding: 12px;
            font-size: 0.9rem;
        }
        
        /* Categories - Centered Grid for Desktop */
        .categories-stories {
            padding: 40px 0;
        }
        
        .categories-scroll {
            max-width: 1200px;
            margin: 0 auto;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            overflow-x: visible;
            padding: 20px 40px;
        }
        
        .category-circle {
            width: 90px;
            height: 90px;
            font-size: 2.2rem;
        }
        
        .category-name {
            font-size: 0.9rem;
            max-width: 100px;
        }
        
        /* Features */
        .features-compact {
            padding: 50px 0;
        }
        
        .features-row {
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .feature-mini {
            padding: 25px 20px;
        }
        
        .feature-mini-icon {
            font-size: 2rem;
            margin-bottom: 12px;
        }
        
        .feature-mini-text {
            font-size: 0.9rem;
        }
        
        /* Products Grid */
        .section-minimal {
            padding: 80px 0;
        }
        
        .section-title-minimal {
            font-size: 1.8rem;
        }
    }
    
    @media (max-width: 768px) {
        .bestseller-card {
            flex: 0 0 140px;
        }
        
        .bestseller-image {
            height: 140px;
        }
        
        .category-circle {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }
        
        .features-row {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    
    @media (max-width: 480px) {
        .bestseller-card {
            flex: 0 0 130px;
        }
        
        .bestseller-image {
            height: 130px;
        }
        
        .category-circle {
            width: 55px;
            height: 55px;
            font-size: 1.3rem;
        }
        
        .category-name {
            font-size: 0.7rem;
        }
    }
    
    /* ═══════════════════════════════════════
       ORDER TRACKING SECTION
       ═══════════════════════════════════════ */
    .track-order-section {
        background: linear-gradient(135deg, #FFF0F5 0%, #FFFFFF 50%, #F8F9FA 100%);
        padding: 50px 0;
        border-top: 1px solid rgba(233, 30, 140, 0.1);
        border-bottom: 1px solid rgba(233, 30, 140, 0.1);
    }
    
    .track-order-card {
        max-width: 500px;
        margin: 0 auto;
        background: #FFFFFF;
        border-radius: 20px;
        padding: 35px 30px;
        border: 2px solid rgba(233, 30, 140, 0.15);
        box-shadow: var(--shadow-lg);
        text-align: center;
    }
    
    .track-order-icon {
        font-size: 3rem;
        margin-bottom: 15px;
        display: block;
    }
    
    .track-order-title {
        font-family: var(--font-heading);
        font-size: 1.4rem;
        color: var(--primary);
        margin-bottom: 10px;
    }
    
    .track-order-subtitle {
        color: var(--text-muted);
        font-size: 0.9rem;
        margin-bottom: 25px;
    }
    
    .track-order-form {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }
    
    .track-order-form input {
        flex: 1;
        min-width: 150px;
        padding: 14px 18px;
        border-radius: 12px;
        border: 2px solid rgba(233, 30, 140, 0.15);
        background: #FFFFFF;
        color: var(--text-dark);
        font-size: 0.95rem;
        text-align: center;
        transition: var(--transition);
    }
    
    .track-order-form input::placeholder {
        color: var(--text-muted);
    }
    
    .track-order-form input:focus {
        border-color: var(--primary);
        outline: none;
        box-shadow: 0 0 20px rgba(233, 30, 140, 0.15);
    }
    
    .track-order-btn {
        width: 100%;
        padding: 14px 25px;
        background: var(--bg-gold-gradient);
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        border: none;
        border-radius: 12px;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }
    
    .track-order-btn:hover {
        box-shadow: var(--shadow-gold);
        transform: translateY(-2px);
    }
    
    @media (min-width: 768px) {
        .track-order-card {
            padding: 45px 40px;
        }
        
        .track-order-title {
            font-size: 1.6rem;
        }
        
        .track-order-form {
            flex-wrap: nowrap;
        }
        
        .track-order-btn {
            width: auto;
            min-width: 130px;
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
                        <li><a href="/" class="active">الرئيسية</a></li>
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
    
    <!-- Mobile Menu Overlay - Must be in HTML, not created by JS -->
    <div class="nav-overlay" id="navOverlay"></div>

    <!-- Hero Slider - Compact for Mobile -->
    <?php $banners = getBanners(true); ?>
    <section class="hero">
        <div class="hero-slider" id="heroSlider">
            <?php foreach ($banners as $banner): ?>
            <div class="hero-slide">
                <img src="images/<?= $banner['image_path'] ?>" alt="<?= $banner['title'] ?>" onerror="this.src='images/logo.jpg'">
                <div class="hero-overlay">
                    <h1 class="hero-title"><?= $banner['title'] ?></h1>
                    <p class="hero-subtitle"><?= $banner['subtitle'] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="hero-dots" id="heroDots"></div>
    </section>

    <!-- Best Sellers Horizontal Slider -->
    <section class="bestsellers-section">
        <div class="bestsellers-header">
            <h2 class="bestsellers-title"> الأكثر مبيعاً 🔥</h2>
            <a href="/products" class="bestsellers-link">عرض الكل ←</a>
        </div>
        <div class="bestsellers-slider">
            <?php foreach ($bestSellers as $product): 
                $images = isset($product['images']) ? $product['images'] : [];
                if (empty($images)) $images = ['products/default.png'];
                // If only 1 image, duplicate it for smooth effect
                if (count($images) === 1) $images[] = $images[0];
            ?>
            <a href="/product?id=<?= $product['id'] ?>" class="bestseller-card" style="text-decoration: none; color: inherit;">
                <div class="bestseller-image product-image-slider" data-images='<?= json_encode($images) ?>'>
                    <?php foreach ($images as $idx => $img): ?>
                    <img src="images/<?= $img ?>" alt="<?= $product['name'] ?>" class="slider-img <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>">
                    <?php endforeach; ?>
                    <?php if (count($images) > 1): ?>
                    <div class="slider-dots">
                        <?php for ($i = 0; $i < count($images); $i++): ?>
                        <span class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($product['is_best_seller'])): ?>
                    <span class="bestseller-badge">🏆 الأفضل</span>
                    <?php elseif (!empty($product['is_trending'])): ?>
                    <span class="bestseller-badge">🔥 رائج</span>
                    <?php endif; ?>

                </div>
                <div class="bestseller-info">
                    <h3 class="bestseller-name"><?= $product['name'] ?></h3>
                    <div class="bestseller-price"><?= formatPriceWithDiscount($product['price'], isset($product['old_price']) ? $product['old_price'] : 0) ?></div>
                    <?php if ($product['customizable']): ?>
                    <span class="bestseller-btn">🎨 تخصيص</span>
                    <?php else: ?>
                    <span class="bestseller-btn">👁️ عرض</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Categories - Instagram Stories Style -->
    <section class="categories-stories">
        <div class="categories-scroll">
            <?php foreach (getCategories() as $key => $cat): ?>
            <a href="/products?category=<?= $key ?>" class="category-story">
                <div class="category-circle"><?= $cat['icon'] ?></div>
                <span class="category-name"><?= $cat['name'] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Features - Compact -->
    <section class="features-compact">
        <div class="container">
            <div class="features-row">
                <div class="feature-mini">
                    <div class="feature-mini-icon">🎁</div>
                    <div class="feature-mini-text">هدايا فاخرة</div>
                </div>
                <div class="feature-mini">
                    <div class="feature-mini-icon">✨</div>
                    <div class="feature-mini-text">تخصيص كامل</div>
                </div>
                <div class="feature-mini">
                    <div class="feature-mini-icon">🚚</div>
                    <div class="feature-mini-text">توصيل سريع</div>
                </div>
                <div class="feature-mini">
                    <div class="feature-mini-icon">💎</div>
                    <div class="feature-mini-text">جودة عالية</div>
                </div>
            </div>
        </div>
    </section>

    <!-- All Products -->
    <section class="section section-minimal">
        <div class="container">
            <div class="section-header-minimal">
                <h2 class="section-title-minimal">🛍️ <span>تشكيلتنا المميزة</span></h2>
                <a href="/products" class="bestsellers-link">عرض الكل ←</a>
            </div>
            
            <div class="products-grid">
                <?php 
                $allProducts = array_filter($products, function($p) {
                    return $p['status'] !== 'hidden';
                });
                $allProducts = array_slice($allProducts, 0, 8);
                foreach ($allProducts as $product): 
                    $images = isset($product['images']) ? $product['images'] : [];
                    if (empty($images)) $images = ['products/default.png'];
                    if (count($images) === 1) $images[] = $images[0];
                ?>
                <div class="product-card fade-in" data-id="<?= $product['id'] ?>">
                    <a href="/product?id=<?= $product['id'] ?>">
                        <div class="product-image product-image-slider" data-images='<?= json_encode($images) ?>'>
                            <?php foreach ($images as $idx => $img): ?>
                            <img src="images/<?= $img ?>" alt="<?= $product['name'] ?>" class="slider-img <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>" loading="lazy" decoding="async">
                            <?php endforeach; ?>
                            <?php if (count($images) > 1): ?>
                            <div class="slider-dots">
                                <?php for ($i = 0; $i < count($images); $i++): ?>
                                <span class="slider-dot <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>"></span>
                                <?php endfor; ?>
                            </div>
                            <?php endif; ?>
                            <?php $badge = getProductBadge($product); ?>
                            <?php if ($product['status'] === 'sold'): ?>
                            <span class="product-badge badge-sold">نفذت الكمية</span>
                            <?php elseif ($badge): ?>
                            <span class="product-badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                            <?php elseif ($product['customizable']): ?>
                            <span class="product-badge badge-available">🎨 قابل للتخصيص</span>
                            <?php endif; ?>
                            
                            <button type="button" class="wishlist-btn" 
                                    data-wishlist-btn
                                    data-product-id="<?= $product['id'] ?>"
                                    data-product-name="<?= htmlspecialchars($product['name']) ?>"
                                    data-product-price="<?= $product['price'] ?>"
                                    data-product-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>"
                                    title="إضافة للمفضلة">
                                <span class="heart-empty">♡</span>
                                <span class="heart-full">♥</span>
                            </button>

                        </div>
                    </a>
                    <div class="product-info">
                        <a href="/product?id=<?= $product['id'] ?>">
                            <h3 class="product-name"><?= $product['name'] ?></h3>
                        </a>
                        <p class="product-desc"><?= $product['description'] ?></p>
                        <div class="product-footer">
                            <?= formatPriceWithDiscount($product['price'], isset($product['old_price']) ? $product['old_price'] : 0) ?>
                            <?php if ($product['status'] === 'available'): ?>
                                <?php if ($product['customizable']): ?>
                                <a href="/product?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">
                                    🎨 خصّص
                                </a>
                                <?php else: ?>
                                <button class="btn btn-primary btn-sm add-to-cart"
                                        data-id="<?= $product['id'] ?>"
                                        data-name="<?= $product['name'] ?>"
                                        data-price="<?= $product['price'] ?>"
                                        data-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>">
                                    🛒 أضف
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div style="text-align: center; margin-top: 40px;">
                <a href="/products" class="btn btn-outline btn-lg">عرض جميع المنتجات ←</a>
            </div>
        </div>
    </section>



    <!-- Order Tracking Section -->
    <section class="track-order-section">
        <div class="container">
            <div class="track-order-card">
                <span class="track-order-icon">📦</span>
                <h2 class="track-order-title">تتبع طلبك</h2>
                <p class="track-order-subtitle">أدخل رقم الطلب ورقم الهاتف لمتابعة حالة طلبك</p>
                
                <form action="track.php" method="POST" class="track-order-form">
                    <input type="text" name="order_number" placeholder="رقم الطلب" required>
                    <input type="tel" name="phone" placeholder="رقم الهاتف" required>
                    <button type="submit" class="track-order-btn">
                        🔍 تتبع
                    </button>
                </form>
            </div>
        </div>
    </section>

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
                        <li><a href="/about">💖 من نحن</a></li>
                        <li><a href="/cart">🛒 سلة التسوق</a></li>
                        <li><a href="/track">📦 تتبع طلبي</a></li>
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
        // Define social URLs for contact modal
        window.INSTAGRAM_URL = '<?= INSTAGRAM_URL ?>';
        window.INSTAGRAM_USER = '<?= INSTAGRAM_USER ?>';
        window.TELEGRAM_URL = '<?= TELEGRAM_CHANNEL_URL ?>';
        window.TELEGRAM_USER = '<?= TELEGRAM_ORDER_USERNAME ?>';
    </script>
    <script src="<?= v('js/api-helper.js') ?>"></script>
    <script src="<?= v('js/app.js') ?>"></script>
    <script src="<?= v('js/cart.js') ?>"></script>
    <script src="<?= v('js/wishlist.js') ?>"></script>
    
    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js').catch(function(err) {
                    console.log('SW registration failed');
                });
            });
        }
    </script>
</body>
</html>
