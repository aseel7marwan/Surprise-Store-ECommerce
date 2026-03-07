<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/functions.php'; ?>
<?php require_once 'includes/seo.php'; ?>

<?php
$category = isset($_GET['category']) ? $_GET['category'] : null;
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';
$filters = ['exclude_hidden' => true];
if ($category) {
    $filters['category'] = $category;
}

// Get all products first
$allProducts = getProducts($filters);
$settings = getSettings();
$categories = getCategories();

// Smart Search Logic
$matchedProducts = [];
$relatedProducts = [];
$isSearching = !empty($searchQuery);

if ($isSearching) {
    $searchLower = mb_strtolower($searchQuery, 'UTF-8');
    $searchWords = preg_split('/\s+/', $searchLower);
    
    // Score-based matching
    foreach ($allProducts as $product) {
        $score = 0;
        $productName = mb_strtolower($product['name'], 'UTF-8');
        $productDesc = mb_strtolower($product['description'], 'UTF-8');
        $productCat = isset($product['category']) ? mb_strtolower($product['category'], 'UTF-8') : '';
        $catName = isset($categories[$product['category']]) ? mb_strtolower($categories[$product['category']]['name'], 'UTF-8') : '';
        
        // Check each search word
        foreach ($searchWords as $word) {
            if (empty($word)) continue;
            
            // Exact match in name (highest priority)
            if (strpos($productName, $word) !== false) {
                $score += 10;
            }
            // Match in category name
            if (strpos($catName, $word) !== false) {
                $score += 5;
            }
            // Match in description
            if (strpos($productDesc, $word) !== false) {
                $score += 3;
            }
        }
        
        if ($score > 0) {
            $product['_score'] = $score;
            $matchedProducts[] = $product;
        }
    }
    
    // Sort by score (highest first)
    usort($matchedProducts, function($a, $b) {
        return $b['_score'] - $a['_score'];
    });
    
    // Get related products (same category as first result, or featured/bestsellers)
    if (!empty($matchedProducts)) {
        $firstMatchCat = $matchedProducts[0]['category'];
        foreach ($allProducts as $product) {
            // Skip if already in matched
            $isMatched = false;
            foreach ($matchedProducts as $mp) {
                if ($mp['id'] === $product['id']) {
                    $isMatched = true;
                    break;
                }
            }
            if ($isMatched) continue;
            
            // Same category as first result
            if ($product['category'] === $firstMatchCat) {
                $relatedProducts[] = $product;
            }
        }
    }
    
    // If no matches, show featured/available products as suggestions
    if (empty($matchedProducts)) {
        foreach ($allProducts as $product) {
            if ($product['status'] === 'available') {
                $relatedProducts[] = $product;
            }
        }
    }
    
    // Limit related products
    $relatedProducts = array_slice($relatedProducts, 0, 4);
    
    $products = $matchedProducts;
} else {
    $products = $allProducts;
}

// SEO - تحديد العنوان والوصف بناءً على القسم أو البحث
// عنوان الصفحة ثابت دائماً
$pageTitle = SITE_BRAND_NAME;
if ($isSearching) {
    $pageDesc = 'نتائج البحث عن "' . htmlspecialchars($searchQuery) . '" في متجر ' . SITE_BRAND_NAME . ' للهدايا الفاخرة.';
} elseif ($category && isset($categories[$category])) {
    $catInfo = $categories[$category];
    $pageDesc = 'تسوق أفضل هدايا ' . $catInfo['name'] . ' في العراق من ' . SITE_BRAND_NAME . ' - هدايا ' . $catInfo['name'] . ' فخمة ومميزة. توصيل داخل العراق لجميع المحافظات.';
} else {
    $pageDesc = 'تصفح منتجات ' . SITE_BRAND_NAME . ' - هدايا فخمة ومميزة: تحف مضيئة، ساعات محفورة، أكواب مطبوعة، بوكسات هدايا، إكسسوارات، هدايا نسائية ورجالية. توصيل داخل العراق.';
}
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
        'type' => 'website'
    )) ?>
    
    <link rel="stylesheet" href="<?= v('css/main.css') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="/images/logo.jpg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo.jpg">
    <link rel="shortcut icon" href="/images/logo.jpg">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo.jpg">
    
    <!-- PWA Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#E91E8C">
    

    <!-- Structured Data -->
    <?= generateStoreSchema() ?>
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
                        <li><a href="/products" class="active">المنتجات</a></li>
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

    <main class="products-main" style="margin-top: 100px; padding-bottom: 60px;">
        <div class="container">
            <!-- Search Bar -->
            <div class="search-container">
                <form action="/products" method="GET" class="search-form">
                    <div class="search-input-wrapper">
                        <span class="search-icon">🔍</span>
                        <input type="text" name="q" class="search-input" 
                               placeholder="ابحث عن منتج... (ساعة، هدية، بوكس...)" 
                               value="<?= htmlspecialchars($searchQuery) ?>"
                               autocomplete="off">
                        <?php if (!empty($searchQuery)): ?>
                        <a href="/products" class="search-clear">✕</a>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="search-btn">بحث</button>
                </form>
            </div>
            
            <?php if ($isSearching): ?>
            <!-- Search Results Header -->
            <div class="search-results-header">
                <h1 class="section-title">🔍 نتائج البحث</h1>
                <p class="section-subtitle">
                    <?php if (!empty($products)): ?>
                    تم العثور على <strong><?= count($products) ?></strong> منتج لـ "<strong><?= htmlspecialchars($searchQuery) ?></strong>"
                    <?php else: ?>
                    لم نجد نتائج لـ "<strong><?= htmlspecialchars($searchQuery) ?></strong>"
                    <?php endif; ?>
                </p>
            </div>
            <?php else: ?>
            <div class="section-header">
                <h1 class="section-title">
                    <?php if ($category && isset(getCategories()[$category])): ?>
                        <?= getCategories()[$category]['icon'] ?> <?= getCategories()[$category]['name'] ?>
                    <?php else: ?>
                        🛍️ جميع المنتجات
                    <?php endif; ?>
                </h1>
                <p class="section-subtitle">اكتشف تشكيلتنا الفريدة من أجمل الهدايا</p>
            </div>
            <?php endif; ?>
            
            <?php if (!$isSearching): ?>
            <!-- Categories Filter -->
            <div class="categories" style="margin-bottom: 50px;">
                <a href="/products" class="category-btn <?= !$category ? 'active' : '' ?>">
                    🛍️ الكل
                </a>
                <?php foreach (getCategories() as $key => $cat): ?>
                <a href="/products?category=<?= $key ?>" class="category-btn <?= $category === $key ? 'active' : '' ?>">
                    <?= $cat['icon'] ?> <?= $cat['name'] ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Products Grid -->
            <?php if (empty($products)): ?>
            <div class="empty-state">
                <?php if ($isSearching): ?>
                <div class="empty-icon">🔍</div>
                <h2 class="empty-title">لا توجد نتائج</h2>
                <p class="empty-text">لم نجد منتجات تطابق بحثك. جرب كلمات أخرى!</p>
                <?php else: ?>
                <div class="empty-icon">📦</div>
                <h2 class="empty-title">لا توجد منتجات</h2>
                <p class="empty-text">لم يتم العثور على منتجات في هذا القسم</p>
                <?php endif; ?>
                <a href="/products" class="btn btn-primary btn-lg">🛍️ عرض جميع المنتجات</a>
            </div>
            
            <!-- Related Products (when no search results) -->
            <?php if ($isSearching && !empty($relatedProducts)): ?>
            <div class="related-section">
                <h3 class="related-title">💡 قد يعجبك أيضاً</h3>
                <div class="products-grid">
                    <?php foreach ($relatedProducts as $product): 
                        $images = isset($product['images']) ? $product['images'] : [];
                        if (empty($images)) $images = ['products/default.png'];
                        if (count($images) === 1) $images[] = $images[0];
                    ?>
                    <div class="product-card fade-in">
                        <a href="/product?id=<?= $product['id'] ?>">
                            <div class="product-image product-image-slider" data-images='<?= json_encode($images) ?>'>
                                <?php foreach ($images as $idx => $img): ?>
                                <img src="images/<?= $img ?>" alt="<?= $product['name'] ?>" class="slider-img <?= $idx === 0 ? 'active' : '' ?>" data-index="<?= $idx ?>">
                                <?php endforeach; ?>
                            </div>
                        </a>
                        <div class="product-info">
                            <a href="/product?id=<?= $product['id'] ?>">
                                <h3 class="product-name"><?= $product['name'] ?></h3>
                            </a>
                            <p class="product-desc"><?= $product['description'] ?></p>
                            <div class="product-footer">
                                <?= formatPriceWithDiscount($product['price'], isset($product['old_price']) ? $product['old_price'] : 0) ?>
                                <a href="/product?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">عرض</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): 
                    $images = isset($product['images']) ? $product['images'] : [];
                    if (empty($images)) $images = ['products/default.png'];
                    if (count($images) === 1) $images[] = $images[0];
                ?>
                <div class="product-card fade-in">
                    <a href="/product?id=<?= $product['id'] ?>">
                        <div class="product-image product-image-slider" data-images='<?= json_encode($images) ?>'>
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
                            <?php 
                            $badge = getProductBadge($product);
                            if ($product['status'] === 'sold'): ?>
                            <span class="product-badge badge-sold">نفذت الكمية</span>
                            <?php elseif ($badge): ?>
                            <span class="product-badge <?= $badge['class'] ?>"><?= $badge['label'] ?></span>
                            <?php elseif ($product['customizable']): ?>
                            <span class="product-badge badge-available">🎨 قابل للتخصيص</span>
                            <?php elseif ($product['featured']): ?>
                            <span class="product-badge badge-featured">⭐ مميز</span>
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
                                    🎨 خصّص المنتج
                                </a>
                                <?php else: ?>
                                <button class="btn btn-primary btn-sm add-to-cart"
                                        data-id="<?= $product['id'] ?>"
                                        data-name="<?= $product['name'] ?>"
                                        data-price="<?= $product['price'] ?>"
                                        data-image="<?= isset($product['images'][0]) ? $product['images'][0] : '' ?>">
                                    🛒 أضف للسلة
                                </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
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
        window.INSTAGRAM_URL = '<?= INSTAGRAM_URL ?>';
        window.INSTAGRAM_USER = '<?= INSTAGRAM_USER ?>';
        window.TELEGRAM_URL = '<?= TELEGRAM_CHANNEL_URL ?>';
        window.TELEGRAM_USER = '<?= TELEGRAM_ORDER_USERNAME ?>';
    </script>
    <script src="<?= v('js/api-helper.js') ?>"></script>
    <script src="<?= v('js/app.js') ?>"></script>
    <script src="<?= v('js/cart.js') ?>"></script>
    <script src="<?= v('js/wishlist.js') ?>"></script>
</body>
</html>
