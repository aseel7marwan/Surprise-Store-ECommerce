<?php require_once 'includes/config.php'; ?>
<?php require_once 'includes/functions.php'; ?>
<?php require_once 'includes/seo.php'; ?>
<?php $settings = getSettings(); ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_BRAND_NAME ?></title>
    
    <!-- SEO Meta Tags -->
    <?= generateMetaTags(array(
        'title' => SITE_BRAND_NAME,
        'description' => 'تعرف على ' . SITE_BRAND_NAME . ' - متجر الهدايا الفاخرة الأول في العراق. قصتنا، رؤيتنا، وقيمنا. نقدم أجمل الهدايا المخصصة بجودة عالية وتوصيل لجميع المحافظات.',
        'type' => 'website'
    )) ?>
    
    <link rel="stylesheet" href="<?= v('css/main.css') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="/images/logo.jpg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo.jpg">
    <link rel="shortcut icon" href="/images/logo.jpg">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo.jpg">
    
    <!-- Structured Data -->
    <?= generateOrganizationSchema() ?>
    
    <style>
        /* ═══════════════════════════════════════════════════════════════
           ABOUT PAGE - PREMIUM DESIGN MATCHING SITE IDENTITY
           ═══════════════════════════════════════════════════════════════ */
        
        /* Hero Section */
        .about-hero {
            background: linear-gradient(135deg, #E91E8C 0%, #FF6BB3 50%, #E91E8C 100%);
            color: white;
            padding: 100px 0 80px;
            margin-top: 80px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .about-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 60%);
            animation: heroFloat 20s linear infinite;
        }
        
        @keyframes heroFloat {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .about-hero::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 80px;
            background: linear-gradient(to top, #FFFFFF, transparent);
        }
        
        .about-hero .container {
            position: relative;
            z-index: 1;
        }
        
        .about-hero-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            display: block;
            animation: bounce 2s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .about-hero h1 {
            font-family: var(--font-heading);
            font-size: 2.8rem;
            margin-bottom: 15px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }
        
        .about-hero p {
            font-size: 1.2rem;
            opacity: 0.95;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.8;
        }
        
        /* Main Content */
        .about-content {
            padding: 80px 0;
            background: linear-gradient(180deg, #FFFFFF 0%, #FFF5F8 100%);
        }
        
        /* About Cards */
        .about-card {
            background: #FFFFFF;
            border-radius: 24px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(233, 30, 140, 0.08);
            border: 1px solid rgba(233, 30, 140, 0.1);
            transition: all 0.4s ease;
            position: relative;
            overflow: hidden;
        }
        
        .about-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(180deg, #E91E8C, #FF6BB3);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 60px rgba(233, 30, 140, 0.15);
        }
        
        .about-card:hover::before {
            opacity: 1;
        }
        
        .about-card-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .about-card-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #FFF0F5, #FFE4ED);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            flex-shrink: 0;
        }
        
        .about-card h2 {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            color: #1a1a2e;
            margin: 0;
        }
        
        .about-card p {
            color: #555;
            line-height: 2;
            margin-bottom: 15px;
            font-size: 1rem;
        }
        
        .about-card p:last-child {
            margin-bottom: 0;
        }
        
        .about-card strong {
            color: var(--primary);
        }
        
        /* Values Grid */
        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .value-card {
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF5F8 100%);
            border-radius: 20px;
            padding: 35px 25px;
            text-align: center;
            border: 2px solid rgba(233, 30, 140, 0.1);
            transition: all 0.4s ease;
            position: relative;
        }
        
        .value-card::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 20px;
            background: linear-gradient(135deg, rgba(233, 30, 140, 0.05), transparent);
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .value-card:hover {
            transform: translateY(-8px) scale(1.02);
            border-color: var(--primary);
            box-shadow: 0 20px 50px rgba(233, 30, 140, 0.2);
        }
        
        .value-card:hover::after {
            opacity: 1;
        }
        
        .value-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            display: block;
            position: relative;
            z-index: 1;
        }
        
        .value-title {
            font-family: var(--font-heading);
            font-weight: 800;
            color: #1a1a2e;
            font-size: 1.2rem;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        
        .value-desc {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.8;
            position: relative;
            z-index: 1;
        }
        
        /* Features List */
        .features-list {
            list-style: none;
            padding: 0;
            margin: 25px 0 0;
        }
        
        .features-list li {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #FFF0F5, #FFFFFF);
            border-radius: 12px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
            border: 1px solid rgba(233, 30, 140, 0.08);
        }
        
        .features-list li:hover {
            transform: translateX(-5px);
            background: linear-gradient(135deg, #FFE4ED, #FFF0F5);
            border-color: rgba(233, 30, 140, 0.2);
        }
        
        .features-list .check-icon {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #E91E8C, #FF6BB3);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
            flex-shrink: 0;
        }
        
        .features-list span {
            color: #333;
            font-weight: 500;
            font-size: 1rem;
        }
        
        /* Stats Section */
        .stats-section {
            background: linear-gradient(135deg, #E91E8C 0%, #FF6BB3 50%, #E91E8C 100%);
            padding: 70px 0;
            position: relative;
            overflow: hidden;
        }
        
        .stats-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="10" cy="10" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="30" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="20" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="30" cy="60" r="2" fill="rgba(255,255,255,0.08)"/><circle cx="70" cy="80" r="1" fill="rgba(255,255,255,0.1)"/></svg>');
            animation: statsFloat 30s linear infinite;
        }
        
        @keyframes statsFloat {
            0% { background-position: 0 0; }
            100% { background-position: 100px 100px; }
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 30px;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        
        .stat-item {
            color: white;
            padding: 20px;
        }
        
        .stat-number {
            font-family: var(--font-heading);
            font-size: 3.5rem;
            font-weight: 900;
            margin-bottom: 10px;
            text-shadow: 0 2px 20px rgba(0,0,0,0.2);
        }
        
        .stat-label {
            font-size: 1.1rem;
            opacity: 0.95;
            font-weight: 500;
        }
        
        /* Team Message */
        .team-message {
            background: linear-gradient(135deg, #FFFFFF 0%, #FFF5F8 100%);
            border-radius: 24px;
            padding: 50px 40px;
            margin-top: 50px;
            text-align: center;
            border: 2px solid rgba(233, 30, 140, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .team-message::before {
            content: '💝';
            position: absolute;
            top: -30px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 5rem;
            opacity: 0.1;
        }
        
        .team-message h3 {
            font-family: var(--font-heading);
            color: var(--primary);
            font-size: 1.5rem;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .team-message p {
            color: #444;
            line-height: 2.2;
            max-width: 800px;
            margin: 0 auto;
            font-size: 1.1rem;
            font-style: italic;
        }
        
        /* CTA Section */
        .about-cta {
            background: linear-gradient(180deg, #FFF5F8 0%, #FFFFFF 100%);
            padding: 70px 0;
            text-align: center;
        }
        
        .about-cta h2 {
            font-family: var(--font-heading);
            font-size: 2rem;
            color: #1a1a2e;
            margin-bottom: 15px;
        }
        
        .about-cta p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 35px;
        }
        
        .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .cta-buttons .btn {
            padding: 16px 35px;
            font-size: 1rem;
            border-radius: 50px;
            font-weight: 700;
            transition: all 0.3s ease;
        }
        
        .cta-buttons .btn:hover {
            transform: translateY(-3px);
        }
        
        .btn-instagram {
            background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888);
            color: white;
            border: none;
        }
        
        .btn-telegram {
            background: linear-gradient(135deg, #0088cc, #00aced);
            color: white;
            border: none;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .about-hero {
                padding: 80px 20px 60px;
            }
            
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .about-hero p {
                font-size: 1rem;
            }
            
            .about-card {
                padding: 30px 25px;
            }
            
            .about-card h2 {
                font-size: 1.3rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .stat-number {
                font-size: 2.5rem;
            }
            
            .team-message {
                padding: 40px 25px;
            }
            
            .about-cta h2 {
                font-size: 1.6rem;
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
                        <li><a href="/about" class="active">من نحن</a></li>
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

    <!-- Hero Section -->
    <section class="about-hero">
        <div class="container">
            <span class="about-hero-icon">🎁</span>
            <h1><?= SITE_BRAND_NAME_AR ?></h1>
            <p>نحول اللحظات العادية إلى ذكريات استثنائية من خلال هدايا فريدة ومميزة تحمل بصمتك الخاصة</p>
        </div>
    </section>

    <!-- About Content -->
    <div class="about-content">
        <div class="container">
            
            <!-- Who We Are -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-card-icon">💖</div>
                    <h2>من نحن؟</h2>
                </div>
                <p>
                    مرحباً بك في <strong><?= SITE_BRAND_NAME ?></strong>، وجهتك الأولى للهدايا الفريدة والمميزة في العراق! 
                    بدأنا رحلتنا بشغف كبير لتقديم تجربة إهداء استثنائية تجمع بين الجودة العالية والتصميم الأنيق.
                </p>
                <p>
                    نؤمن أن كل هدية تحمل قصة، ونسعى لأن نجعل قصتك فريدة ومميزة. سواء كنت تبحث عن هدية لشخص عزيز 
                    أو تريد إضافة لمسة خاصة لمناسبة ما، نحن هنا لنساعدك على إيجاد الهدية المثالية التي ستبقى في الذاكرة.
                </p>
            </div>
            
            <!-- Vision -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-card-icon">🎯</div>
                    <h2>رؤيتنا</h2>
                </div>
                <p>
                    أن نكون الوجهة الأولى والأفضل للهدايا الفاخرة في العراق، من خلال تقديم منتجات عالية الجودة 
                    وتجربة تسوق سلسة ومميزة تلبي توقعات عملائنا وتتجاوزها. نسعى لأن نكون جزءاً من كل مناسبة سعيدة في حياتكم.
                </p>
            </div>
            
            <!-- Values -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-card-icon">⭐</div>
                    <h2>قيمنا</h2>
                </div>
                <div class="values-grid">
                    <div class="value-card">
                        <span class="value-icon">🏆</span>
                        <div class="value-title">الجودة أولاً</div>
                        <div class="value-desc">نختار فقط أفضل المنتجات والمواد لضمان رضا عملائنا التام</div>
                    </div>
                    <div class="value-card">
                        <span class="value-icon">💝</span>
                        <div class="value-title">الاهتمام بالتفاصيل</div>
                        <div class="value-desc">كل منتج يمر بفحص دقيق قبل الشحن لضمان الكمال</div>
                    </div>
                    <div class="value-card">
                        <span class="value-icon">🤝</span>
                        <div class="value-title">ثقة العميل</div>
                        <div class="value-desc">نبني علاقات طويلة الأمد من خلال الصدق والشفافية</div>
                    </div>
                    <div class="value-card">
                        <span class="value-icon">🚀</span>
                        <div class="value-title">الابتكار المستمر</div>
                        <div class="value-desc">نطور منتجاتنا باستمرار لمواكبة أحدث الصيحات</div>
                    </div>
                </div>
            </div>
            
            <!-- What Makes Us Special -->
            <div class="about-card">
                <div class="about-card-header">
                    <div class="about-card-icon">🎨</div>
                    <h2>ما يميزنا</h2>
                </div>
                <p>
                    نقدم خدمة <strong>الطباعة المخصصة</strong> التي تتيح لك إضافة صورك الشخصية على العديد من منتجاتنا. 
                    تخيل أن تهدي شخصاً عزيزاً منتجاً يحمل ذكرياتكم المشتركة - هذا ما نقدمه لك!
                </p>
                <ul class="features-list">
                    <li>
                        <span class="check-icon">✓</span>
                        <span>منتجات أصلية بجودة عالية ومضمونة</span>
                    </li>
                    <li>
                        <span class="check-icon">✓</span>
                        <span>طباعة احترافية بألوان زاهية ودائمة</span>
                    </li>
                    <li>
                        <span class="check-icon">✓</span>
                        <span>تغليف أنيق وفاخر يليق بهديتك</span>
                    </li>
                    <li>
                        <span class="check-icon">✓</span>
                        <span>توصيل سريع لجميع محافظات العراق</span>
                    </li>
                    <li>
                        <span class="check-icon">✓</span>
                        <span>دفع آمن عند الاستلام</span>
                    </li>
                    <li>
                        <span class="check-icon">✓</span>
                        <span>خدمة عملاء متميزة على مدار الساعة</span>
                    </li>
                </ul>
            </div>
            
            <!-- Team Message -->
            <div class="team-message">
                <h3>💌 رسالة من فريق العمل</h3>
                <p>
                    "نشكركم على ثقتكم بنا واختياركم <?= SITE_BRAND_NAME_AR ?> لمشاركة لحظاتكم الجميلة. 
                    وعدنا لكم هو أن نستمر في تقديم الأفضل دائماً. كل طلب نستلمه يُعامَل بعناية واهتمام كأنه هديتنا الشخصية.
                    شكراً لأنكم جزء من عائلتنا! 💖"
                </p>
            </div>
        </div>
    </div>
    
    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">1000+</div>
                    <div class="stat-label">عميل سعيد</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">500+</div>
                    <div class="stat-label">منتج متميز</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">19</div>
                    <div class="stat-label">محافظة نغطيها</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">24/7</div>
                    <div class="stat-label">خدمة العملاء</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="about-cta">
        <div class="container">
            <h2>هل أنت مستعد للبدء؟</h2>
            <p>اكتشف مجموعتنا الرائعة من الهدايا المميزة واصنع ذكريات لا تُنسى</p>
            <div class="cta-buttons">
                <a href="/products" class="btn btn-primary btn-lg">تصفح المنتجات</a>
                <a href="<?= INSTAGRAM_URL ?>" target="_blank" class="btn btn-instagram btn-lg" style="display: inline-flex; align-items: center; gap: 8px;">
                    <img src="images/icons/icon1.png" alt="Instagram" style="width: 20px; height: 20px;">
                    انستقرام
                </a>
                <a href="<?= TELEGRAM_CHANNEL_URL ?>" target="_blank" class="btn btn-telegram btn-lg" style="display: inline-flex; align-items: center; gap: 8px;">
                    <img src="images/icons/icon2.png" alt="Telegram" style="width: 20px; height: 20px;">
                    تيليجرام
                </a>
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
                        <li><a href="/">الرئيسية</a></li>
                        <li><a href="/products">جميع المنتجات</a></li>
                        <li><a href="/about">من نحن</a></li>
                        <li><a href="/track">تتبع طلبي</a></li>
                        <li><a href="/privacy">سياسة الخصوصية</a></li>
                        <li><a href="/terms">الشروط والأحكام</a></li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="footer-title">تواصل معنا</h3>
                    <div class="social-links" style="flex-direction: column; gap: 12px; margin-top: 15px;">
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
                    <ul class="footer-links" style="margin-top: 15px;">
                        <li>العراق</li>
                        <li>توصيل لجميع المحافظات</li>
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
    <script src="<?= v('js/app.js') ?>"></script>
    <script src="<?= v('js/cart.js') ?>"></script>
</body>
</html>
