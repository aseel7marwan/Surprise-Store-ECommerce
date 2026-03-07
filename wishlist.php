<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/functions.php'; ?>
<?php require_once 'includes/seo.php'; ?>

<?php $settings = getSettings(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>بيج سبرايز | Surprise page</title>
    
    <!-- SEO Meta Tags -->
    <?= generateMetaTags(array(
        'title' => 'المفضلة - ' . SITE_BRAND_NAME,
        'description' => 'قائمة المنتجات المفضلة لديك في ' . SITE_BRAND_NAME . ' للهدايا الفاخرة.',
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
    
    <style>
        .wishlist-container {
            min-height: 60vh;
            padding: 30px 0;
            margin-top: 80px;
        }
        
        .wishlist-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .wishlist-header h1 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 10px;
        }
        
        .wishlist-header p {
            color: var(--text-muted);
        }
        
        .wishlist-empty {
            text-align: center;
            padding: 60px 20px;
            background: #f8f9fa;
            border-radius: var(--radius-lg);
        }
        
        .wishlist-empty-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .wishlist-empty h2 {
            color: #333;
            margin-bottom: 15px;
        }
        
        .wishlist-empty p {
            color: var(--text-muted);
            margin-bottom: 25px;
        }
        
        .wishlist-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 25px;
        }
        
        .wishlist-item {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            position: relative;
            transition: var(--transition);
        }
        
        .wishlist-item:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .wishlist-item-image {
            height: 200px;
            overflow: hidden;
        }
        
        .wishlist-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .wishlist-item-info {
            padding: 15px;
        }
        
        .wishlist-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .wishlist-item-price {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
        }
        
        .wishlist-item-actions {
            display: flex;
            gap: 10px;
        }
        
        .wishlist-item-actions a {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.85rem;
            text-decoration: none;
            transition: var(--transition);
        }
        
        .wishlist-view-btn {
            background: var(--bg-gold-gradient);
            color: white;
        }
        
        .wishlist-remove-btn {
            background: #f5f5f5;
            color: #666;
            cursor: pointer;
            border: none;
        }
        
        .wishlist-remove-btn:hover {
            background: #ffebee;
            color: #c62828;
        }
        
        .wishlist-clear-btn {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 30px;
            background: #f5f5f5;
            color: #666;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .wishlist-clear-btn:hover {
            background: #ffebee;
            color: #c62828;
        }
        
        @media (max-width: 768px) {
            .wishlist-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            .wishlist-item-image {
                height: 150px;
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
                        <li><a href="/wishlist" class="wishlist-sidebar-link active">المفضلة <img src="images/icons/wishlist.png" class="wishlist-sidebar-icon"> <span class="wishlist-count-badge">0</span></a></li>
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

    <div class="wishlist-container">
        <div class="container">
            <div class="wishlist-header">
                <h1>❤️ قائمة المفضلة</h1>
                <p>المنتجات التي أعجبتك</p>
            </div>
            
            <!-- Empty State (shown by JS when no items) -->
            <div class="wishlist-empty" id="wishlistEmpty" style="display: none;">
                <div class="wishlist-empty-icon">💝</div>
                <h2>قائمة المفضلة فارغة</h2>
                <p>أضف منتجات إلى المفضلة بالضغط على أيقونة القلب</p>
                <a href="/products" class="btn btn-primary">🛍️ تصفح المنتجات</a>
            </div>
            
            <!-- Wishlist Items (populated by JS) -->
            <div class="wishlist-grid" id="wishlistGrid"></div>
            
            <div style="text-align: center;" id="wishlistActions" style="display: none;">
                <button class="wishlist-clear-btn" onclick="Wishlist.clear(); renderWishlist();">🗑️ مسح القائمة</button>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <div class="footer-logo">
                        <img src="images/logo.jpg" alt="<?= SITE_NAME ?>">
                        <span><?= SITE_NAME ?></span>
                    </div>
                    <p>متجر الهدايا الفاخرة في العراق.</p>
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
                        <li><a href="/products">🛍️ المنتجات</a></li>
                        <li><a href="/track">📦 تتبع الطلب</a></li>
                        <li><a href="/wishlist">❤️ المفضلة</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">التوصيل</h3>
                    <ul class="footer-links">
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
    
    <script>
        function renderWishlist() {
            const items = Wishlist.getAll();
            const grid = document.getElementById('wishlistGrid');
            const emptyState = document.getElementById('wishlistEmpty');
            const actions = document.getElementById('wishlistActions');
            
            if (items.length === 0) {
                grid.style.display = 'none';
                emptyState.style.display = 'block';
                actions.style.display = 'none';
                return;
            }
            
            grid.style.display = 'grid';
            emptyState.style.display = 'none';
            actions.style.display = 'block';
            
            grid.innerHTML = items.map(item => `
                <div class="wishlist-item">
                    <a href="/product?id=${item.id}">
                        <div class="wishlist-item-image">
                            <img src="images/${item.image}" alt="${item.name}" loading="lazy">
                        </div>
                    </a>
                    <div class="wishlist-item-info">
                        <div class="wishlist-item-name">${item.name}</div>
                        <div class="wishlist-item-price">${Number(item.price).toLocaleString()} د.ع</div>
                        <div class="wishlist-item-actions">
                            <a href="/product?id=${item.id}" class="wishlist-view-btn">👁️ عرض</a>
                            <button class="wishlist-remove-btn" onclick="Wishlist.remove('${item.id}'); renderWishlist();">🗑️</button>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        // Render on page load
        document.addEventListener('DOMContentLoaded', renderWishlist);
    </script>
</body>
</html>
