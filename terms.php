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
        'title' => 'الشروط والأحكام - ' . SITE_BRAND_NAME . ' | سياسة الطلبات والتوصيل',
        'description' => 'الشروط والأحكام لـ ' . SITE_BRAND_NAME . ' للهدايا المخصصة في العراق. سياسة التوصيل والطلبات والدفع عند الاستلام.',
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
            line-height: 2;
        }
        
        .policy-content ul, .policy-content ol {
            padding-right: 30px;
            color: #333;
        }
        
        .policy-content li {
            margin-bottom: 12px;
            position: relative;
            color: #444;
            line-height: 1.8;
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
            background: linear-gradient(135deg, rgba(233, 30, 140, 0.08), #fff);
            border: 2px solid var(--primary);
            border-radius: var(--radius-md);
            padding: 25px;
            margin: 25px 0;
        }
        
        .highlight-box strong {
            color: var(--primary);
        }
        
        .highlight-box p {
            color: #333;
            margin-bottom: 0;
        }
        
        .warning-box {
            background: linear-gradient(135deg, #fff8e1, #fff);
            border: 2px solid #ffc107;
            border-radius: var(--radius-md);
            padding: 25px;
            margin: 25px 0;
        }
        
        .warning-box strong {
            color: #e65100;
        }
        
        .warning-box li {
            color: #5d4037;
        }
        
        @media (max-width: 768px) {
            .policy-content {
                padding: 25px;
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
            <div class="section-header">
                <h1 class="section-title">📜 الشروط والأحكام</h1>
                <p class="section-subtitle">يرجى قراءة الشروط بعناية قبل إتمام الشراء</p>
            </div>
            
            <div class="policy-content">
                <h2>📌 مقدمة</h2>
                <p>مرحباً بك في متجر <?= SITE_NAME ?>. باستخدامك لموقعنا أو خدماتنا، فإنك توافق على الالتزام بهذه الشروط والأحكام. نرجو قراءتها بعناية قبل إجراء أي عملية شراء.</p>
                
                <div class="highlight-box">
                    <p style="margin: 0;"><strong>⚡ ملاحظة مهمة:</strong> إتمام عملية الشراء يعني موافقتك الكاملة على جميع الشروط والأحكام المذكورة أدناه.</p>
                </div>
                
                <h2>💰 الأسعار والدفع</h2>
                <div class="policy-section">
                    <ul>
                        <li>جميع الأسعار المعروضة بالدينار العراقي (د.ع)</li>
                        <li>الأسعار شاملة للضرائب إن وُجدت</li>
                        <li><strong>طرق الدفع المتاحة:</strong> <?= getPaymentMethodsSummary() ?></li>
                        <li>نحتفظ بحق تعديل الأسعار دون إشعار مسبق</li>
                        <li>السعر المعتمد هو السعر وقت تأكيد الطلب</li>
                    </ul>
                </div>
                
                <h2>🚚 سياسة التوصيل</h2>
                <div class="policy-section">
                    <h3>رسوم التوصيل</h3>
                    <ul>
                        <li>التوصيل لجميع المحافظات العراقية: <?= formatPrice(isset($settings['delivery_price']) ? $settings['delivery_price'] : 5000) ?></li>
                        <li>يتم احتساب رسوم التوصيل عند إتمام الطلب</li>
                    </ul>
                    
                    <h3>مدة التوصيل</h3>
                    <ul>
                        <li>بغداد: 1-2 يوم عمل</li>
                        <li>باقي المحافظات: 2-5 أيام عمل</li>
                        <li>قد تتأخر الطلبات في أوقات الذروة أو المناسبات</li>
                    </ul>
                    
                    <h3>شروط التوصيل</h3>
                    <ul>
                        <li>يجب توفير عنوان صحيح وكامل</li>
                        <li>يجب الرد على مكالمات التوصيل</li>
                        <li>في حال عدم الاستلام، قد يتم إرجاع الطلب وتُفرض رسوم إضافية</li>
                    </ul>
                </div>
                
                <h2>📋 سياسة الاستبدال والاسترجاع</h2>
                
                <div class="warning-box">
                    <h3 style="color: #e65100; margin-top: 0;">⚠️ تنويه مهم:</h3>
                    <p style="margin-bottom: 0;">
                        جميع منتجاتنا يتم تنفيذها وتجهيزها <strong>حسب طلب واختيار العميل</strong> (طباعة، نقش، تصميم مخصص). 
                        نظراً لطبيعة هذه المنتجات المخصصة، <strong>لا يمكن استرجاعها أو استبدالها</strong> إذا كان الطلب مطابقاً لما اختاره العميل بنفسه.
                    </p>
                </div>
                
                <div class="policy-section">
                    <h3>🚫 لا يُقبل الاسترجاع أو الاستبدال في الحالات التالية:</h3>
                    <ul>
                        <li>تغيير رأي العميل بعد تنفيذ الطلب</li>
                        <li>عدم الرغبة في المنتج بعد التجهيز أو التسليم</li>
                        <li>اختلاف التوقعات عن المنتج النهائي (طالما أن الطلب نُفذ وفق التصميم/الصورة المرسلة من العميل)</li>
                        <li>المنتجات التي تم استلامها (بعد مغادرة المندوب)</li>
                        <li>أي سبب آخر لا علاقة له بخطأ من طرف المتجر</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h3>🚚 سياسة عدم الاستلام (عند التوصيل بواسطة المندوب):</h3>
                    <ul>
                        <li>في حال وصول المندوب وقرر العميل <strong>عدم استلام الطلب</strong> في ذلك الوقت، يحق للمتجر استرجاع الطلب عبر المندوب.</li>
                        <li><strong>بعد مغادرة المندوب وتسليم الطلب للعميل، لا يحق للعميل طلب الاسترجاع أو الاستبدال لاحقاً.</strong></li>
                        <li>يُعتبر تسلّم الطلب من المندوب موافقة نهائية على المنتج.</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h3>📢 عرض الطلبات غير المستلمة:</h3>
                    <p>في حال استرجاع الطلب بسبب عدم الاستلام (عبر المندوب فقط):</p>
                    <ul>
                        <li>يحق للمتجر عرض محتوى/منتج الطلب المسترجع لأغراض <strong>العرض والتسويق</strong> على منصات المتجر.</li>
                        <li><strong>لن يتم نشر أي معلومات شخصية</strong> للعميل (الاسم، رقم الهاتف، العنوان) مطلقاً مع هذا العرض.</li>
                        <li>يتم التعامل مع المنتج المسترجع بما يحفظ خصوصية العميل بالكامل.</li>
                    </ul>
                </div>
                
                <div class="policy-section">
                    <h3>✅ الحالات الاستثنائية (خطأ من طرف المتجر):</h3>
                    <p>يمكن النظر في معالجة الطلب <strong>فقط</strong> إذا ثبت وجود:</p>
                    <ul>
                        <li>خطأ في الطباعة أو التنفيذ من طرف المتجر</li>
                        <li>استلام منتج مختلف تماماً عن المطلوب</li>
                        <li>عيب تصنيع واضح أو تلف أثناء الشحن</li>
                    </ul>
                    <h4 style="margin-top: 15px; color: #333;">الإجراء المطلوب:</h4>
                    <ul>
                        <li>التواصل الفوري مع المتجر عند اكتشاف المشكلة</li>
                        <li>إرفاق صور واضحة للمنتج والمشكلة</li>
                        <li>التحقق والمعاينة من قبل المتجر</li>
                    </ul>
                    <p style="margin-top: 15px; padding: 12px; background: rgba(233, 30, 140, 0.08); border-radius: 8px;">
                        <strong>ملاحظة:</strong> يحتفظ المتجر بحق التقدير النهائي في جميع الحالات الاستثنائية، بعد التحقق من صحة الشكوى.
                    </p>
                </div>
                
                <div class="highlight-box">
                    <p style="margin: 0; text-align: center;">
                        <strong>⚡ بإتمام الطلب، فإنك توافق على هذه السياسة وتقر بأنك قد قرأتها وفهمتها.</strong>
                    </p>
                </div>
                
                <h2>🎟️ الكوبونات والخصومات</h2>
                <div class="policy-section">
                    <ul>
                        <li>لكل كوبون شروط استخدام خاصة به</li>
                        <li>لا يمكن الجمع بين كوبونين في طلب واحد</li>
                        <li>الكوبون صالح للاستخدام مرة واحدة (ما لم يُذكر غير ذلك)</li>
                        <li>قد تكون بعض المنتجات مستثناة من العروض</li>
                        <li>نحتفظ بحق إلغاء أي كوبون دون إشعار مسبق</li>
                    </ul>
                </div>
                
                <h2>📷 المنتجات المخصصة</h2>
                <div class="policy-section">
                    <p>عند طلب منتج مخصص (مطبوع بصورة):</p>
                    <ul>
                        <li>يجب رفع صورة بجودة عالية للحصول على أفضل نتيجة</li>
                        <li>تأكد من أنك تمتلك حقوق استخدام الصورة</li>
                        <li>لا نتحمل مسؤولية رداءة الطباعة إذا كانت الصورة بجودة منخفضة</li>
                        <li>المنتجات المخصصة <strong>غير قابلة للإلغاء أو الاسترجاع</strong> بعد بدء التنفيذ</li>
                    </ul>
                </div>
                
                <h2>🛡️ حقوق الملكية الفكرية</h2>
                <p>جميع المحتويات على هذا الموقع (الصور، النصوص، الشعارات، التصاميم) محمية بموجب قوانين حقوق الملكية الفكرية. يُمنع:</p>
                <ul>
                    <li>نسخ أو إعادة نشر المحتوى دون إذن خطي</li>
                    <li>استخدام الشعارات أو العلامات التجارية</li>
                    <li>التعديل على المحتوى أو إعادة توزيعه</li>
                </ul>
                
                <h2>⚖️ حدود المسؤولية</h2>
                <div class="policy-section">
                    <p>نحن غير مسؤولين عن:</p>
                    <ul>
                        <li>تأخر التوصيل لأسباب خارجة عن إرادتنا</li>
                        <li>عدم مطابقة الألوان الحقيقية للصور على الشاشة</li>
                        <li>سوء استخدام المنتجات</li>
                        <li>الأضرار الناتجة عن معلومات خاطئة قدمها العميل</li>
                    </ul>
                </div>
                
                <h2>📞 التواصل والشكاوى</h2>
                <p>نرحب بجميع ملاحظاتكم واستفساراتكم. للتواصل معنا:</p>
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
                <p style="margin-top: 15px;">نسعى للرد على جميع الاستفسارات في أقرب وقت ممكن.</p>
                
                <h2>🔄 تحديث الشروط</h2>
                <p>نحتفظ بحق تعديل هذه الشروط في أي وقت. ستكون التغييرات سارية فور نشرها على الموقع. ننصح بمراجعة هذه الصفحة بشكل دوري.</p>
                
                <p style="margin-top: 40px; text-align: center; color: var(--text-muted); padding: 20px; background: #f9f9f9; border-radius: var(--radius-md);">
                    📅 آخر تحديث: <?= date('Y/m/d') ?>
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 30px;">
                <a href="/privacy" class="btn btn-outline">🔒 سياسة الخصوصية</a>
                <a href="/" class="btn btn-primary" style="margin-right: 10px;">🏠 الرئيسية</a>
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
                    <h3 class="footer-title">خدمة العملاء</h3>
                    <ul class="footer-links">
                        <li>📞 دعم على مدار الساعة</li>
                        <li>✅ جودة مضمونة</li>
                        <?= renderPaymentMethodsHTML('list') ?>
                        <li>🚚 توصيل سريع</li>
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
