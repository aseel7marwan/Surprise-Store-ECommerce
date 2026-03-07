<?php
/**
 * Order Tracking Page - تتبع الطلب
 */
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/seo.php';

$order = null;
$error = '';

// Handle tracking form
if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['order'])) {
    $orderNumber = sanitize(isset($_POST['order_number']) ? $_POST['order_number'] : (isset($_GET['order']) ? $_GET['order'] : ''));
    $phone = sanitize(isset($_POST['phone']) ? $_POST['phone'] : (isset($_GET['phone']) ? $_GET['phone'] : ''));
    
    if (empty($orderNumber)) {
        $error = 'يرجى إدخال رقم الطلب';
    } elseif (empty($phone)) {
        $error = 'يرجى إدخال رقم الهاتف';
    } else {
        $order = getOrderForTracking($orderNumber, $phone);
        if (!$order) {
            $error = 'لم يتم العثور على الطلب. تأكد من رقم الطلب ورقم الهاتف';
        }
    }
}

$trackingLabels = getTrackingStatusLabels();
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
        'title' => 'تتبع الطلب - ' . SITE_BRAND_NAME . ' | معرفة حالة طلبك',
        'description' => 'تتبع حالة طلبك من ' . SITE_BRAND_NAME . ' للهدايا الفاخرة. أدخل رقم الطلب ورقم الهاتف لمعرفة مكان شحنتك وموعد وصولها.',
        'type' => 'website'
    )) ?>
    
    <link rel="stylesheet" href="<?= v('css/main.css') ?>">
    <link rel="icon" type="image/png" sizes="48x48" href="/images/logo.jpg">
    <link rel="icon" type="image/png" sizes="32x32" href="/images/logo.jpg">
    <link rel="shortcut icon" href="/images/logo.jpg">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/logo.jpg">
    <style>
        .tracking-container {
            max-width: 700px;
            margin: 120px auto 60px;
            padding: 0 20px;
        }
        
        .tracking-form-card {
            background: linear-gradient(135deg, #FFFFFF, #F8F9FA);
            border-radius: var(--radius-lg);
            padding: 40px;
            box-shadow: var(--shadow-lg);
            margin-bottom: 30px;
            border: 1px solid rgba(233, 30, 140, 0.15);
        }
        
        .tracking-form-card h1 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8rem;
            color: var(--primary);
        }
        
        .tracking-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .order-card {
            background: linear-gradient(135deg, #FFFFFF, #F8F9FA);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(233, 30, 140, 0.15);
        }
        
        .order-header {
            background: linear-gradient(135deg, #E91E8C, #FF6BB3);
            color: #fff;
            padding: 30px;
            text-align: center;
        }
        
        .order-header h2 {
            margin-bottom: 15px;
            font-size: 1.4rem;
            font-weight: 700;
        }
        
        .order-number {
            font-size: 1.3rem;
            font-weight: 900;
            background: rgba(255,255,255,0.2);
            color: #fff;
            padding: 12px 28px;
            border-radius: 30px;
            display: inline-block;
            letter-spacing: 1px;
        }
        
        .order-info {
            padding: 25px;
            border-bottom: 1px solid rgba(233, 30, 140, 0.1);
            background: #FAFBFC;
        }
        
        .order-info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }
        
        .order-info-item {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(233, 30, 140, 0.05);
            padding: 12px 15px;
            border-radius: var(--radius-sm);
            border: 1px solid rgba(233, 30, 140, 0.1);
        }
        
        .order-info-item span:first-child {
            font-size: 1.4rem;
        }
        
        .order-info-item span:last-child {
            color: var(--text-dark);
            font-weight: 500;
        }
        
        /* Timeline Styles */
        .tracking-timeline {
            padding: 30px;
            background: #F8F9FA;
        }
        
        .tracking-timeline h3 {
            margin-bottom: 25px;
            color: var(--primary);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .timeline {
            position: relative;
            padding-right: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            right: 10px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: linear-gradient(to bottom, #E91E8C, rgba(233, 30, 140, 0.3));
            border-radius: 3px;
        }
        
        .timeline-item {
            position: relative;
            padding-bottom: 25px;
            padding-right: 45px;
        }
        
        .timeline-item:last-child {
            padding-bottom: 0;
        }
        
        .timeline-dot {
            position: absolute;
            right: -35px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: linear-gradient(135deg, #E91E8C, #FF6BB3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: #fff;
            border: 3px solid #F8F9FA;
            box-shadow: 0 4px 15px rgba(233, 30, 140, 0.3);
            transition: all 0.3s ease;
        }
        
        .timeline-dot.active {
            transform: scale(1.15);
            box-shadow: 0 4px 20px rgba(233, 30, 140, 0.5);
        }
        
        .timeline-content {
            background: #FFFFFF;
            padding: 18px 22px;
            border-radius: var(--radius-md);
            border: 1px solid rgba(233, 30, 140, 0.1);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }
        
        .timeline-content:hover {
            border-color: rgba(233, 30, 140, 0.25);
            box-shadow: var(--shadow-md);
        }
        
        .timeline-content h4 {
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--text-dark);
            font-size: 1.05rem;
        }
        
        .timeline-content p {
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .timeline-time {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .timeline-time::before {
            content: '🕐';
            font-size: 0.75rem;
        }
        
        .current-status {
            text-align: center;
            padding: 30px 25px;
            margin: 25px;
            border-radius: var(--radius-lg);
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, rgba(233, 30, 140, 0.08), rgba(255, 182, 217, 0.1));
            border: 2px solid rgba(233, 30, 140, 0.2);
        }
        
        .current-status::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.15;
            background: radial-gradient(circle at 50% 0%, #E91E8C, transparent 70%);
        }
        
        .current-status-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            display: inline-block;
            animation: pulse 2s ease-in-out infinite;
            filter: drop-shadow(0 4px 10px rgba(233, 30, 140, 0.3));
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .current-status-label {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-dark);
            position: relative;
        }
        
        .order-items {
            padding: 25px;
            background: #FAFBFC;
        }
        
        .order-items h3 {
            margin-bottom: 15px;
            color: var(--primary);
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-bottom: 1px solid rgba(233, 30, 140, 0.08);
            color: var(--text-dark);
            background: #FFFFFF;
            border-radius: var(--radius-sm);
            margin-bottom: 8px;
            box-shadow: var(--shadow-sm);
        }
        
        .order-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .order-item span:first-child {
            color: var(--text-muted);
        }
        
        .order-item span:last-child {
            font-weight: 600;
            color: var(--primary);
        }
        
        /* Form inputs on light background */
        .tracking-form-card .form-control {
            background: #FFFFFF;
            border: 2px solid rgba(233, 30, 140, 0.15);
            color: var(--text-dark);
        }
        
        .tracking-form-card .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 15px rgba(233, 30, 140, 0.15);
        }
        
        .tracking-form-card .form-control::placeholder {
            color: var(--text-muted);
        }
        
        @media (max-width: 600px) {
            .order-info-grid {
                grid-template-columns: 1fr;
            }
            
            .tracking-form-card {
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
                        <li><a href="/track" class="active">تتبع طلبي</a></li>
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

    <div class="tracking-container">
        <?php if (!$order): ?>
        <!-- Tracking Form -->
        <div class="tracking-form-card">
            <h1>📦 تتبع طلبك</h1>
            
            <?php if ($error): ?>
            <div class="alert alert-error" style="margin-bottom: 20px;"><?= $error ?></div>
            <?php endif; ?>
            
            <form method="POST" class="tracking-form">
                <div class="form-group">
                    <label class="form-label">رقم الطلب *</label>
                    <input type="text" name="order_number" class="form-control" 
                           placeholder="مثال: SRP-123456" required
                           value="<?= htmlspecialchars(isset($_POST['order_number']) ? $_POST['order_number'] : '') ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">رقم الهاتف المستخدم في الطلب *</label>
                    <input type="tel" name="phone" class="form-control" 
                           placeholder="07XXXXXXXXX" required
                           value="<?= htmlspecialchars(isset($_POST['phone']) ? $_POST['phone'] : '') ?>">
                </div>
                
                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                    🔍 تتبع الطلب
                </button>
            </form>
            
            <p style="text-align: center; margin-top: 20px; color: var(--text-muted); font-size: 0.9rem;">
                ستجد رقم الطلب في رسالة التأكيد التي نسختها عند إتمام الطلب
            </p>
        </div>
        
        <?php else: ?>
        <!-- Order Details -->
        <div class="order-card">
            <div class="order-header">
                <h2>تفاصيل طلبك</h2>
                <span class="order-number"><?= $order['order_number'] ?></span>
            </div>
            
            <!-- Current Status -->
            <?php $currentStatus = isset($trackingLabels[$order['status']]) ? $trackingLabels[$order['status']] : $trackingLabels['pending']; ?>
            <div class="current-status" style="background: linear-gradient(135deg, <?= $currentStatus['color'] ?>20, #fff);">
                <div class="current-status-icon"><?= $currentStatus['icon'] ?></div>
                <div class="current-status-label"><?= $currentStatus['label'] ?></div>
            </div>
            
            <!-- Order Info -->
            <div class="order-info">
                <div class="order-info-grid">
                    <div class="order-info-item">
                        <span>👤</span>
                        <span><?= htmlspecialchars($order['customer_name']) ?></span>
                    </div>
                    <div class="order-info-item">
                        <span>📍</span>
                        <span><?= htmlspecialchars($order['customer_city']) ?></span>
                    </div>
                    <div class="order-info-item">
                        <span>💰</span>
                        <span><?= formatPrice($order['total']) ?></span>
                    </div>
                    <div class="order-info-item">
                        <span>📅</span>
                        <span><?= formatDateTime($order['created_at'], 'date') ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="order-items">
                <h3>🛒 المنتجات</h3>
                <?php foreach ($order['items'] as $item): ?>
                <div class="order-item">
                    <span><?= htmlspecialchars($item['product_name']) ?> × <?= $item['quantity'] ?></span>
                    <span><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Tracking Timeline -->
            <div class="tracking-timeline">
                <h3>📋 سجل التتبع</h3>
                
                <?php if (!empty($order['tracking'])): ?>
                <div class="timeline">
                    <?php foreach ($order['tracking'] as $track): ?>
                    <?php $status = isset($trackingLabels[$track['status']]) ? $trackingLabels[$track['status']] : array('label' => $track['status'], 'icon' => '•'); ?>
                    <div class="timeline-item">
                        <div class="timeline-dot active" style="background: <?= isset($status['color']) ? $status['color'] : 'var(--primary)' ?>">
                            <?= $status['icon'] ?>
                        </div>
                        <div class="timeline-content">
                            <h4><?= $status['label'] ?></h4>
                            <?php if (!empty($track['note'])): ?>
                            <p><?= htmlspecialchars($track['note']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($track['location'])): ?>
                            <p>📍 <?= htmlspecialchars($track['location']) ?></p>
                            <?php endif; ?>
                            <div class="timeline-time">
                                <?= formatDateTime($track['created_at'], 'short') ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot active"><?= $currentStatus['icon'] ?></div>
                        <div class="timeline-content">
                            <h4><?= $currentStatus['label'] ?></h4>
                            <p>تم استلام طلبك وسيتم تحديثك بأي تطورات</p>
                            <div class="timeline-time">
                                <?= formatDateTime($order['created_at'], 'short') ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div style="padding: 20px; text-align: center;">
                <a href="/track" class="btn btn-outline">🔍 تتبع طلب آخر</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div style="display: flex; flex-direction: column; align-items: center; gap: 20px; margin-bottom: 20px; padding: 0 15px;">
                <div class="social-links" style="max-width: 350px; width: 100%;">
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
