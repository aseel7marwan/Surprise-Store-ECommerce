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
        'title' => 'سياسة الخصوصية - ' . SITE_BRAND_NAME . ' | حماية بياناتك',
        'description' => 'اطلع على سياسة الخصوصية لـ ' . SITE_BRAND_NAME . ' للهدايا الفاخرة. كيف نحمي بياناتك الشخصية وصورك ومعلوماتك.',
        'type' => 'website'
    )) ?>
    
    <link rel="stylesheet" href="<?= v('css/main.css') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="/images/logo.jpg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo.jpg">
    <link rel="shortcut icon" href="/images/logo.jpg">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo.jpg">
    <style>
        .policy-content {
            background: white;
            border-radius: var(--radius-lg);
            padding: 45px;
            box-shadow: var(--shadow-md);
            line-height: 2.2;
        }
        
        .policy-content h2 {
            color: var(--primary);
            margin: 35px 0 18px;
            font-size: 1.4rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .policy-content h2:first-child {
            margin-top: 0;
        }
        
        .policy-content p {
            color: #333;
            margin-bottom: 18px;
        }
        
        .policy-content ul {
            padding-right: 30px;
            color: #333;
        }
        
        .policy-content li {
            margin-bottom: 12px;
            position: relative;
            color: #444;
        }
        
        .policy-content li::marker {
            color: var(--primary);
        }
        
        .policy-content li strong {
            color: #222;
        }
        
        .policy-section {
            background: rgba(233, 30, 140, 0.05);
            border-radius: var(--radius-md);
            padding: 25px 30px;
            margin: 25px 0;
            border-right: 4px solid var(--primary);
        }
        
        .policy-section h3 {
            font-size: 1.15rem;
            font-weight: 700;
            color: #222;
            margin-bottom: 15px;
        }
        
        .highlight-box {
            background: linear-gradient(135deg, #fff5f8, #fff);
            border: 2px solid var(--primary-light);
            border-radius: var(--radius-md);
            padding: 25px;
            margin: 25px 0;
        }
        
        .highlight-box strong {
            color: var(--primary);
        }
        
        .highlight-box p {
            color: #333;
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
            <div class="section-header">
                <h1 class="section-title">🔒 سياسة الخصوصية</h1>
                <p class="section-subtitle">التزامنا بحماية بياناتك وخصوصيتك</p>
            </div>
            
            <div class="policy-content">
                <h2>🛡️ حماية بياناتك</h2>
                <p>نحن في متجر <?= SITE_NAME ?> نلتزم التزاماً تاماً بحماية خصوصيتك وبياناتك الشخصية. نحترم ثقتك بنا ونعمل جاهدين للحفاظ على سرية معلوماتك.</p>
                
                <h2>📋 البيانات التي نجمعها</h2>
                <ul>
                    <li><strong>الاسم ورقم الهاتف</strong> - لغرض التواصل وتأكيد الطلب</li>
                    <li><strong>العنوان</strong> - لغرض التوصيل فقط</li>
                    <li><strong>الصور المرفوعة</strong> - للمنتجات المخصصة (الطباعة)</li>
                </ul>
                
                <h2>📷 سياسة الصور والمحتوى المرفوع</h2>
                <div class="highlight-box">
                    <p style="margin: 0;"><strong>نؤكد لك ما يلي:</strong></p>
                </div>
                <ul>
                    <li>الصور الشخصية التي ترفعها تُستخدم <strong>فقط</strong> لتنفيذ طلبك</li>
                    <li>لا نشارك صورك مع أي طرف ثالث أبداً</li>
                    <li>يتم حذف الصور تلقائياً بعد إتمام الطلب</li>
                    <li>لا نستخدم صورك لأي غرض تسويقي أو إعلاني</li>
                </ul>
                
                <div class="policy-section">
                    <h3>📢 استثناء: الطلبات غير المستلمة</h3>
                    <p>في حال استرجاع الطلب بسبب عدم الاستلام (عند وجود المندوب):</p>
                    <ul>
                        <li>يحق للمتجر عرض محتوى/منتج الطلب المسترجع لأغراض <strong>العرض والتسويق</strong> على منصات المتجر.</li>
                        <li><strong>لن يتم نشر أي معلومات شخصية</strong> تكشف هوية العميل (الاسم، رقم الهاتف، العنوان) مطلقاً.</li>
                        <li>يتم التعامل مع المنتج المسترجع بما يحفظ سرية بيانات العميل بالكامل.</li>
                    </ul>
                </div>
                
                <h2>🔐 أمان البيانات</h2>
                <p>نستخدم أحدث تقنيات الحماية لتأمين بياناتك:</p>
                <ul>
                    <li>تشفير البيانات أثناء النقل</li>
                    <li>فلترة الملفات المرفوعة ومنع الملفات الضارة</li>
                    <li>تخزين آمن للمعلومات</li>
                </ul>
                
                <h2>📞 تواصل معنا</h2>
                <p>لأي استفسارات حول الخصوصية، تواصل معنا عبر:</p>
                <div class="social-links" style="justify-content: flex-start; margin-top: 15px;">
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
                
                <hr style="margin: 40px 0; border: none; border-top: 2px solid #eee;">
                
                <h2>📜 الشروط والأحكام</h2>
                
                <div class="policy-section">
                    <h3>💰 الأسعار والدفع</h3>
                    <ul>
                        <li>جميع الأسعار بالدينار العراقي (د.ع)</li>
                        <li><?= getPaymentInfoText() ?></li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h3>🚚 التوصيل</h3>
                    <ul>
                        <li>التوصيل لجميع المحافظات: <?= formatPrice(isset($settings['delivery_price']) ? $settings['delivery_price'] : 5000) ?></li>
                        <li>مدة التوصيل: 2-5 أيام عمل</li>
                        <li>يتم التواصل قبل التوصيل لتأكيد الموعد</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h3>📋 الاستبدال والاسترجاع</h3>
                    <p style="margin: 0;">للاطلاع على سياسة الاستبدال والاسترجاع الكاملة، يرجى مراجعة <a href="/terms" style="color: var(--primary); font-weight: 600;">الشروط والأحكام</a>.</p>
                </div>
                
                <div class="policy-section">
                    <h3>📢 عرض الطلبات غير المستلمة وحماية الخصوصية</h3>
                    <p>في حال استرجاع الطلب بسبب عدم الاستلام أثناء التوصيل عبر المندوب:</p>
                    <ul>
                        <li>يحق للمتجر عرض محتوى الطلب (المنتج فقط) لأغراض العرض والتسويق على منصات المتجر</li>
                        <li><strong>نلتزم التزاماً تاماً</strong> بعدم نشر أي معلومات شخصية تتعلق بالعميل:
                            <ul style="margin-top: 8px;">
                                <li>لا اسم العميل</li>
                                <li>لا رقم الهاتف</li>
                                <li>لا العنوان</li>
                                <li>لا أي بيانات تكشف هوية صاحب الطلب</li>
                            </ul>
                        </li>
                    </ul>
                </div>
                
                <p style="margin-top: 40px; text-align: center; color: var(--text-muted); padding: 20px; background: #f9f9f9; border-radius: var(--radius-md);">
                    📅 آخر تحديث: <?= date('Y/m/d') ?>
                </p>
            </div>
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
</body>
</html>
