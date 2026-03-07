<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Initialize security
initSecureSession();
setSecurityHeaders();

// Check admin auth with enhanced security
if (!validateAdminSession()) {
    redirect('login');
}

// Logout
if (isset($_GET['logout'])) {
    destroyAdminSession();
    session_destroy();
    redirect('login');
}

$settings = getSettings();
$categories = getCategories();

// Stats
$totalProducts = count(getProducts());
$totalOrders = countOrders();
$pendingOrders = countOrders('pending');
$activeBanners = count(getBanners(true));
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        /* ═══════════════════════════════════════════════════════════════
           DASHBOARD PAGE - Mobile Responsive Styles
           ═══════════════════════════════════════════════════════════════ */
        
        /* Dashboard Header */
        .dashboard-header {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .dashboard-header h1 {
            margin: 0;
        }
        
        .dashboard-header p {
            color: var(--text-muted);
            margin: 0;
        }
        
        /* Stats Grid - 2x2 on mobile */
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }
        
        /* Quick Links Grid */
        .quick-links-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 30px;
        }
        
        /* Mobile - Recent Orders Cards */
        .mobile-orders-cards {
            display: none;
        }
        
        .mobile-order-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: var(--shadow-sm);
            border-right: 4px solid var(--primary);
        }
        
        .mobile-order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 10px;
            border-bottom: 1px dashed #eee;
        }
        
        .mobile-order-number {
            font-weight: 800;
            color: var(--primary);
            font-size: 1rem;
        }
        
        .mobile-order-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        
        .mobile-order-item .label {
            font-size: 0.75rem;
            color: var(--text-muted);
            display: block;
        }
        
        .mobile-order-item .value {
            font-weight: 600;
            font-size: 0.9rem;
            color: var(--text-dark);
        }
        
        /* Mobile - Recent Products Cards */
        .mobile-products-cards {
            display: none;
        }
        
        .mobile-product-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 15px;
            margin-bottom: 12px;
            box-shadow: var(--shadow-sm);
            display: flex;
            gap: 15px;
            align-items: center;
        }
        
        .mobile-product-card img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-md);
            flex-shrink: 0;
        }
        
        .mobile-product-info {
            flex: 1;
        }
        
        .mobile-product-info h4 {
            margin: 0 0 5px 0;
            font-size: 0.95rem;
            color: var(--text-dark);
        }
        
        .mobile-product-info .category {
            font-size: 0.85rem;
            color: var(--text-muted);
        }
        
        .mobile-product-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #eee;
        }
        
        .mobile-product-price {
            font-weight: 700;
            color: var(--primary);
            font-size: 0.95rem;
        }
        
        /* Tablet Responsive */
        @media (max-width: 1024px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .quick-links-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .dashboard-header {
                text-align: center;
            }
            
            .dashboard-stats {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .stat-card {
                padding: 15px;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                font-size: 1.2rem;
            }
            
            .stat-info h3 {
                font-size: 1.4rem;
            }
            
            .stat-info p {
                font-size: 0.8rem;
            }
            
            /* Hide desktop tables, show mobile cards */
            .admin-table-wrapper {
                display: none;
            }
            
            .mobile-orders-cards,
            .mobile-products-cards {
                display: block;
            }
            
            .quick-links-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-card-header {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start;
            }
            
            .admin-card-header .btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Small phones */
        @media (max-width: 480px) {
            .dashboard-stats {
                gap: 10px;
            }
            
            .stat-card {
                padding: 12px;
                gap: 10px;
            }
            
            .stat-icon {
                width: 45px;
                height: 45px;
                font-size: 1rem;
            }
            
            .stat-info h3 {
                font-size: 1.2rem;
            }
            
            .stat-info p {
                font-size: 0.75rem;
            }
            
            .mobile-order-body {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <!-- Dashboard Header -->
            <div class="dashboard-header">
                <h1 class="admin-title">📊 نظرة عامة</h1>
                <p>مرحباً بك في لوحة تحكم <?= SITE_NAME ?></p>
            </div>

            <!-- Stats Grid -->
            <div class="dashboard-stats">
                <a href="orders?status=pending" class="stat-card" style="text-decoration: none; <?= $pendingOrders > 0 ? 'border: 2px solid var(--primary); animation: pulse 2s infinite;' : '' ?>">
                    <div class="stat-icon" style="background: #FF5722;">📋</div>
                    <div class="stat-info">
                        <h3><?= $pendingOrders ?></h3>
                        <p>طلبات جديدة</p>
                    </div>
                </a>
                
                <a href="orders" class="stat-card" style="text-decoration: none;">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <h3><?= $totalOrders ?></h3>
                        <p>إجمالي الطلبات</p>
                    </div>
                </a>
                
                <a href="products" class="stat-card" style="text-decoration: none;">
                    <div class="stat-icon" style="background: #4CAF50;">🛍️</div>
                    <div class="stat-info">
                        <h3><?= $totalProducts ?></h3>
                        <p>المنتجات</p>
                    </div>
                </a>
                
                <a href="banners" class="stat-card" style="text-decoration: none;">
                    <div class="stat-icon" style="background: #9C27B0;">🖼️</div>
                    <div class="stat-info">
                        <h3><?= $activeBanners ?></h3>
                        <p>بانرات نشطة</p>
                    </div>
                </a>
            </div>
            
            <?php if ($pendingOrders > 0): ?>
            <div class="alert alert-info" style="margin-bottom: 20px;">
                📋 لديك <strong><?= $pendingOrders ?></strong> طلب جديد بانتظار المراجعة! 
                <a href="orders?status=pending" style="color: inherit; font-weight: bold;">عرض الطلبات ←</a>
            </div>
            <?php endif; ?>

            <!-- Recent Orders -->
            <div class="admin-card" style="margin-bottom: 30px;">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">📋 أحدث الطلبات</h2>
                    <a href="orders" class="btn btn-primary btn-sm">عرض الكل</a>
                </div>
                
                <?php $recentOrders = getOrders(['limit' => 5]); ?>
                <?php if (empty($recentOrders)): ?>
                <div class="empty-state" style="padding: 40px; text-align: center;">
                    <div class="empty-icon" style="font-size: 3rem; margin-bottom: 15px;">📋</div>
                    <p style="color: var(--text-muted);">لا توجد طلبات بعد</p>
                </div>
                <?php else: ?>
                
                <!-- Desktop Table -->
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>رقم الطلب</th>
                                <th>الزبون</th>
                                <th>الإجمالي</th>
                                <th>الحالة</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <?php $st = getOrderStatusLabel($order['status']); ?>
                            <tr onclick="window.location='orders.php?view=<?= $order['id'] ?>'" style="cursor: pointer;">
                                <td><strong>#<?= $order['order_number'] ?></strong></td>
                                <td><?= $order['customer_name'] ?: '(غير محدد)' ?></td>
                                <td style="color: var(--primary); font-weight: 600;"><?= formatPrice($order['total']) ?></td>
                                <td><span class="status <?= $st['class'] ?>"><?= $st['icon'] ?> <?= $st['label'] ?></span></td>
                                <td style="font-size: 0.85rem;"><?= formatDateTime($order['created_at'], 'short') ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Cards -->
                <div class="mobile-orders-cards" style="padding: 15px;">
                    <?php foreach ($recentOrders as $order): ?>
                    <?php $st = getOrderStatusLabel($order['status']); ?>
                    <div class="mobile-order-card" onclick="window.location='orders.php?view=<?= $order['id'] ?>'" style="cursor: pointer;">
                        <div class="mobile-order-header">
                            <span class="mobile-order-number">#<?= $order['order_number'] ?></span>
                            <span class="status <?= $st['class'] ?>"><?= $st['icon'] ?> <?= $st['label'] ?></span>
                        </div>
                        <div class="mobile-order-body">
                            <div class="mobile-order-item">
                                <span class="label">👤 الزبون</span>
                                <span class="value"><?= $order['customer_name'] ?: '(غير محدد)' ?></span>
                            </div>
                            <div class="mobile-order-item">
                                <span class="label">💰 الإجمالي</span>
                                <span class="value" style="color: var(--primary);"><?= formatPrice($order['total']) ?></span>
                            </div>
                            <div class="mobile-order-item">
                                <span class="label">📅 التاريخ</span>
                                <span class="value"><?= formatDateTime($order['created_at'], 'short') ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Recent Products -->
            <div class="admin-card">
                <div class="admin-card-header">
                    <h2 class="admin-card-title">📦 أحدث المنتجات</h2>
                    <a href="products" class="btn btn-primary btn-sm">عرض الكل</a>
                </div>
                
                <?php $products = getProducts(['limit' => 5]); ?>
                
                <!-- Desktop Table -->
                <div class="admin-table-wrapper">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>الصورة</th>
                                <th>الاسم</th>
                                <th>القسم</th>
                                <th>السعر</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($products, 0, 5) as $product): ?>
                            <tr>
                                <td>
                                    <img src="../images/<?= isset($product['images'][0]) ? $product['images'][0] : 'logo.jpg' ?>" alt="" onerror="this.src='../images/logo.jpg'">
                                </td>
                                <td><strong><?= $product['name'] ?></strong></td>
                                <td><?= isset($categories[$product['category']]['icon']) ? $categories[$product['category']]['icon'] : '' ?> <?= isset($categories[$product['category']]['name']) ? $categories[$product['category']]['name'] : $product['category'] ?></td>
                                <td style="color: var(--primary); font-weight: 600;"><?= formatPriceWithDiscount($product['price'], isset($product['old_price']) ? $product['old_price'] : 0) ?></td>
                                <td>
                                    <?php 
                                    $statusClasses = [
                                        'available' => 'status-available',
                                        'sold' => 'status-sold',
                                        'hidden' => 'status-hidden'
                                    ];
                                    $statusClass = isset($statusClasses[$product['status']]) ? $statusClasses[$product['status']] : 'status-hidden';
                                    $statusTexts = [
                                        'available' => 'متوفر',
                                        'sold' => 'نفذ',
                                        'hidden' => 'مخفي'
                                    ];
                                    $statusText = isset($statusTexts[$product['status']]) ? $statusTexts[$product['status']] : 'غير معروف';
                                    ?>
                                    <span class="status <?= $statusClass ?>"><?= $statusText ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Cards -->
                <div class="mobile-products-cards" style="padding: 15px;">
                    <?php foreach (array_slice($products, 0, 5) as $product): ?>
                    <?php 
                    $statusClasses = [
                        'available' => 'status-available',
                        'sold' => 'status-sold',
                        'hidden' => 'status-hidden'
                    ];
                    $statusClass = isset($statusClasses[$product['status']]) ? $statusClasses[$product['status']] : 'status-hidden';
                    $statusTexts = [
                        'available' => 'متوفر',
                        'sold' => 'نفذ',
                        'hidden' => 'مخفي'
                    ];
                    $statusText = isset($statusTexts[$product['status']]) ? $statusTexts[$product['status']] : 'غير معروف';
                    ?>
                    <div class="mobile-product-card">
                        <img src="../images/<?= isset($product['images'][0]) ? $product['images'][0] : 'logo.jpg' ?>" alt="" onerror="this.src='../images/logo.jpg'">
                        <div class="mobile-product-info">
                            <h4><?= $product['name'] ?></h4>
                            <span class="category"><?= isset($categories[$product['category']]['icon']) ? $categories[$product['category']]['icon'] : '' ?> <?= isset($categories[$product['category']]['name']) ? $categories[$product['category']]['name'] : $product['category'] ?></span>
                            <div class="mobile-product-footer">
                                <span class="mobile-product-price"><?= formatPriceWithDiscount($product['price'], isset($product['old_price']) ? $product['old_price'] : 0) ?></span>
                                <span class="status <?= $statusClass ?>"><?= $statusText ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="quick-links-grid">
                <a href="products?action=add" class="stat-card" style="text-decoration: none;">
                    <div class="stat-icon">➕</div>
                    <div class="stat-info">
                        <h3 style="font-size: 1.2rem;">إضافة منتج</h3>
                        <p>أضف منتج جديد للمتجر</p>
                    </div>
                </a>
                
                <a href="settings" class="stat-card" style="text-decoration: none;">
                    <div class="stat-icon" style="background: #607D8B;">⚙️</div>
                    <div class="stat-info">
                        <h3 style="font-size: 1.2rem;">الإعدادات</h3>
                        <p>تعديل إعدادات الموقع</p>
                    </div>
                </a>
                
                <a href="<?= INSTAGRAM_URL ?>" target="_blank" class="stat-card" style="text-decoration: none;">
                    <div class="stat-icon" style="background: linear-gradient(45deg, #f09433, #bc1888);">📸</div>
                    <div class="stat-info">
                        <h3 style="font-size: 1.2rem;">انستقرام</h3>
                        <p>@<?= INSTAGRAM_USER ?></p>
                    </div>
                </a>
            </div>
        </main>
    </div>
    <script src="<?= av('js/admin.js') ?>"></script>
</body>
</html>

