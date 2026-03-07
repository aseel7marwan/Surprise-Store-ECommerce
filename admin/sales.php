<?php
/**
 * Sales Analytics - Professional Sales Dashboard
 * Shows real sales data from confirmed orders
 * Design matching Reports page for desktop, optimized cards for mobile
 */
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

if (!validateAdminSession()) {
    redirect('login');
}

// Get sales data (all time for main view)
$salesSummary = getSalesSummary();
$salesByCategory = getSalesByCategory();
$categoriesData = getCategories();

// Get selected category for product breakdown
$selectedCategory = isset($_GET['category']) ? $_GET['category'] : null;
$productSales = getSalesByProduct($selectedCategory);

// Search functionality
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
if ($searchQuery) {
    $productSales = searchProductsWithSales($searchQuery);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 المبيعات - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        /* ======== Desktop Design - Same as Reports ======== */
        
        .sales-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .sales-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--text-dark);
        }
        
        .sales-subtitle {
            color: var(--text-muted);
            margin-top: 5px;
            font-size: 0.95rem;
        }
        
        /* Stats Row - Same as Reports */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-box {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .stat-box:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .stat-box::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100%;
            height: 4px;
            background: var(--bg-hero);
        }
        
        .stat-box.green::before { background: linear-gradient(90deg, #4CAF50, #81C784); }
        .stat-box.blue::before { background: linear-gradient(90deg, #2196F3, #64B5F6); }
        .stat-box.orange::before { background: linear-gradient(90deg, #FF9800, #FFB74D); }
        .stat-box.purple::before { background: linear-gradient(90deg, #9C27B0, #BA68C8); }
        
        .stat-box .icon { font-size: 2.5rem; margin-bottom: 15px; }
        .stat-box .value { font-size: 1.8rem; font-weight: 900; color: var(--text-dark); line-height: 1.2; }
        .stat-box .label { color: var(--text-muted); font-size: 0.9rem; margin-top: 5px; }
        
        /* Filter Section */
        .filter-section {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .search-box {
            flex: 1;
            min-width: 250px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 14px 20px 14px 50px;
            border: 2px solid rgba(233, 30, 140, 0.15);
            border-radius: var(--radius-lg);
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(233, 30, 140, 0.1);
        }
        
        .search-box::before {
            content: '🔍';
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 1.2rem;
        }
        
        .category-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 12px 22px;
            border: 2px solid rgba(233, 30, 140, 0.15);
            background: white;
            border-radius: var(--radius-lg);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: var(--text-dark);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .filter-btn:hover {
            border-color: var(--primary);
            background: rgba(233, 30, 140, 0.05);
            transform: translateY(-2px);
        }
        
        .filter-btn.active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }
        
        /* Categories Section */
        .categories-section {
            margin-bottom: 35px;
        }
        
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }
        
        .category-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 25px;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-right: 4px solid var(--primary);
        }
        
        .category-card:hover {
            transform: translateX(-5px);
            box-shadow: var(--shadow-md);
        }
        
        .category-info { display: flex; align-items: center; gap: 15px; }
        .category-icon { font-size: 2rem; }
        .category-name { font-weight: 700; font-size: 1.1rem; }
        .category-stats { text-align: left; }
        .category-count { font-size: 1.5rem; font-weight: 800; color: var(--primary); }
        .category-revenue { font-size: 0.85rem; color: var(--text-muted); }
        
        /* Products Section - Admin Card Style */
        .products-section {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        
        .products-header {
            padding: 20px 25px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .products-title { font-size: 1.15rem; font-weight: 700; }
        
        .products-count {
            background: rgba(233, 30, 140, 0.1);
            color: var(--primary);
            padding: 6px 16px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
        }
        
        /* Products Table - Desktop */
        .products-table-wrap {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table th,
        .products-table td {
            padding: 16px 20px;
            text-align: right;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .products-table th {
            background: var(--bg-main);
            font-weight: 700;
            font-size: 0.85rem;
            color: var(--text-muted);
            white-space: nowrap;
        }
        
        .products-table tbody tr {
            transition: background 0.2s;
        }
        
        .products-table tbody tr:hover {
            background: rgba(233, 30, 140, 0.02);
        }
        
        .product-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-img {
            width: 55px;
            height: 55px;
            object-fit: cover;
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            flex-shrink: 0;
        }
        
        .product-info { min-width: 0; }
        .product-name { font-weight: 600; color: var(--text-dark); }
        .product-price { font-size: 0.8rem; color: var(--text-muted); margin-top: 3px; }
        
        .sold-badge {
            background: linear-gradient(135deg, #4CAF50, #81C784);
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.9rem;
            display: inline-block;
        }
        
        .revenue-value { font-weight: 700; color: var(--primary); }
        .no-sales { color: var(--text-muted); font-style: italic; }
        
        /* Rank badges */
        .rank-badge {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 0.85rem;
        }
        
        .rank-1 { background: linear-gradient(135deg, #FFD700, #FFA500); color: white; }
        .rank-2 { background: linear-gradient(135deg, #C0C0C0, #A0A0A0); color: white; }
        .rank-3 { background: linear-gradient(135deg, #CD7F32, #8B4513); color: white; }
        .rank-other { background: #f0f0f0; color: var(--text-muted); }
        
        /* Mobile Product Cards - Hidden on desktop */
        .mobile-products {
            display: none;
            padding: 20px;
        }
        
        .mobile-product-card {
            background: white;
            border-radius: var(--radius-lg);
            margin-bottom: 15px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
            border-right: 4px solid var(--primary);
            position: relative;
        }
        
        .mobile-product-top {
            display: flex;
            gap: 15px;
            padding: 15px;
        }
        
        .mobile-product-top img {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            flex-shrink: 0;
        }
        
        .mobile-product-info { flex: 1; min-width: 0; }
        .mobile-product-name { font-weight: 700; font-size: 1rem; margin-bottom: 5px; word-break: break-word; }
        .mobile-product-category { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 5px; }
        .mobile-product-price { font-size: 0.9rem; color: var(--primary); font-weight: 600; }
        
        .mobile-product-stats {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            background: #f8f9fa;
            border-top: 1px solid #eee;
        }
        
        .mobile-stat {
            padding: 12px;
            text-align: center;
            border-left: 1px solid #eee;
        }
        
        .mobile-stat:last-child { border-left: none; }
        .mobile-stat-value { font-weight: 800; font-size: 1.1rem; color: var(--text-dark); }
        .mobile-stat-label { font-size: 0.75rem; color: var(--text-muted); margin-top: 2px; }
        
        .mobile-rank {
            position: absolute;
            top: 12px;
            left: 12px;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        
        .empty-icon { font-size: 4rem; margin-bottom: 15px; }
        .empty-text { font-size: 1.1rem; }
        
        /* ======== Mobile Responsive ======== */
        @media (max-width: 768px) {
            .sales-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .sales-title { font-size: 1.4rem; }
            
            /* Stats */
            .stats-row {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .stat-box { padding: 18px; }
            .stat-box .icon { font-size: 1.8rem; margin-bottom: 10px; }
            .stat-box .value { font-size: 1.3rem; }
            .stat-box .label { font-size: 0.8rem; }
            
            /* Filters */
            .filter-section {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box { width: 100%; min-width: auto; }
            
            .category-filters {
                overflow-x: auto;
                flex-wrap: nowrap;
                padding-bottom: 10px;
                -webkit-overflow-scrolling: touch;
            }
            
            .filter-btn {
                padding: 10px 16px;
                font-size: 0.9rem;
                flex-shrink: 0;
            }
            
            /* Categories */
            .categories-grid { grid-template-columns: 1fr; gap: 12px; }
            .category-card { padding: 18px; }
            .category-icon { font-size: 1.5rem; }
            .category-count { font-size: 1.2rem; }
            
            /* Products - Hide table, show cards */
            .products-table-wrap { display: none !important; }
            .mobile-products { display: block; }
            
            .products-section {
                background: transparent;
                box-shadow: none;
            }
            
            .products-header {
                background: white;
                border-radius: var(--radius-lg);
                margin-bottom: 15px;
                box-shadow: var(--shadow-sm);
            }
            
            .section-title { font-size: 1.1rem; }
        }
        
        @media (max-width: 480px) {
            .stats-row { grid-template-columns: 1fr; gap: 10px; }
            
            .stat-box {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 15px;
            }
            
            .stat-box .icon { margin-bottom: 0; font-size: 1.8rem; }
            .stat-content { flex: 1; }
            
            .mobile-product-stats { grid-template-columns: 1fr 1fr; }
            .mobile-product-stats .mobile-stat:last-child { grid-column: span 2; }
        }
        
        /* Prevent horizontal scroll */
        body, html { overflow-x: hidden; }
        .admin-main { overflow-x: hidden; }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <!-- Header -->
            <div class="sales-header">
                <div>
                    <h1 class="sales-title">📊 المبيعات</h1>
                    <p class="sales-subtitle">تحليل المبيعات الحقيقية من الطلبات المؤكدة</p>
                </div>
            </div>
            
            <!-- Stats Row - Same as Reports -->
            <div class="stats-row">
                <div class="stat-box green">
                    <div class="icon">💰</div>
                    <div class="stat-content">
                        <div class="value"><?= formatPrice($salesSummary['total_revenue']) ?></div>
                        <div class="label">إجمالي المبيعات</div>
                    </div>
                </div>
                
                <div class="stat-box blue">
                    <div class="icon">📦</div>
                    <div class="stat-content">
                        <div class="value"><?= number_format($salesSummary['total_orders']) ?></div>
                        <div class="label">الطلبات المؤكدة</div>
                    </div>
                </div>
                
                <div class="stat-box orange">
                    <div class="icon">🛍️</div>
                    <div class="stat-content">
                        <div class="value"><?= number_format($salesSummary['total_items_sold']) ?></div>
                        <div class="label">القطع المباعة</div>
                    </div>
                </div>
                
                <div class="stat-box purple">
                    <div class="icon">🏆</div>
                    <div class="stat-content">
                        <div class="value" style="font-size: 1rem;"><?= $salesSummary['top_product'] ?: 'لا يوجد' ?></div>
                        <div class="label">الأكثر مبيعاً</div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Section -->
            <div class="filter-section">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="ابحث عن منتج..." value="<?= htmlspecialchars($searchQuery) ?>">
                </div>
                
                <div class="category-filters">
                    <a href="sales" class="filter-btn <?= !$selectedCategory && !$searchQuery ? 'active' : '' ?>">📊 الكل</a>
                    <?php foreach ($categoriesData as $key => $cat): ?>
                    <a href="sales?category=<?= $key ?>" class="filter-btn <?= $selectedCategory === $key ? 'active' : '' ?>">
                        <?= $cat['icon'] ?> <?= $cat['name'] ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Categories Overview -->
            <?php if (!$selectedCategory && !$searchQuery && count($salesByCategory) > 0): ?>
            <div class="categories-section">
                <h2 class="section-title">📁 المبيعات حسب الأقسام</h2>
                <div class="categories-grid">
                    <?php foreach ($salesByCategory as $catSales): 
                        $catInfo = isset($categoriesData[$catSales['category']]) ? $categoriesData[$catSales['category']] : null;
                        if (!$catInfo) continue;
                    ?>
                    <a href="sales?category=<?= $catSales['category'] ?>" class="category-card">
                        <div class="category-info">
                            <span class="category-icon"><?= $catInfo['icon'] ?></span>
                            <span class="category-name"><?= $catInfo['name'] ?></span>
                        </div>
                        <div class="category-stats">
                            <div class="category-count"><?= number_format($catSales['items_sold']) ?> قطعة</div>
                            <div class="category-revenue"><?= number_format($catSales['revenue']) ?> د.ع</div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Products Section -->
            <div class="products-section">
                <div class="products-header">
                    <h3 class="products-title">
                        <?php if ($selectedCategory && isset($categoriesData[$selectedCategory])): ?>
                            <?= $categoriesData[$selectedCategory]['icon'] ?> منتجات <?= $categoriesData[$selectedCategory]['name'] ?>
                        <?php elseif ($searchQuery): ?>
                            🔍 نتائج البحث: "<?= htmlspecialchars($searchQuery) ?>"
                        <?php else: ?>
                            🛍️ جميع المنتجات
                        <?php endif; ?>
                    </h3>
                    <span class="products-count"><?= count($productSales) ?> منتج</span>
                </div>
                
                <?php if (count($productSales) > 0): ?>
                
                <!-- Desktop Table -->
                <div class="products-table-wrap">
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>المنتج</th>
                                <th>القسم</th>
                                <th>الكمية المباعة</th>
                                <th>الإيرادات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rank = 1; foreach ($productSales as $product): 
                                $image = is_array($product['images']) && count($product['images']) > 0 
                                    ? $product['images'][0] 
                                    : 'logo.jpg';
                                $catInfo = isset($categoriesData[$product['category']]) ? $categoriesData[$product['category']] : null;
                                
                                $rankClass = 'rank-other';
                                if ($product['items_sold'] > 0 && $rank === 1) $rankClass = 'rank-1';
                                elseif ($product['items_sold'] > 0 && $rank === 2) $rankClass = 'rank-2';
                                elseif ($product['items_sold'] > 0 && $rank === 3) $rankClass = 'rank-3';
                            ?>
                            <tr>
                                <td><span class="rank-badge <?= $rankClass ?>"><?= $product['items_sold'] > 0 ? $rank : '-' ?></span></td>
                                <td>
                                    <div class="product-cell">
                                        <img src="../images/<?= $image ?>" alt="" class="product-img" onerror="this.src='../images/logo.jpg'">
                                        <div class="product-info">
                                            <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                                            <div class="product-price"><?= formatPrice($product['price']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?= $catInfo ? $catInfo['icon'] . ' ' . $catInfo['name'] : '-' ?></td>
                                <td>
                                    <?php if ($product['items_sold'] > 0): ?>
                                        <span class="sold-badge"><?= number_format($product['items_sold']) ?></span>
                                    <?php else: ?>
                                        <span class="no-sales">0</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($product['revenue'] > 0): ?>
                                        <span class="revenue-value"><?= formatPrice($product['revenue']) ?></span>
                                    <?php else: ?>
                                        <span class="no-sales">0 د.ع</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if ($product['items_sold'] > 0) $rank++; endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Mobile Cards -->
                <div class="mobile-products">
                    <?php $rank = 1; foreach ($productSales as $product): 
                        $image = is_array($product['images']) && count($product['images']) > 0 
                            ? $product['images'][0] 
                            : 'logo.jpg';
                        $catInfo = isset($categoriesData[$product['category']]) ? $categoriesData[$product['category']] : null;
                        
                        $rankClass = 'rank-other';
                        if ($product['items_sold'] > 0 && $rank === 1) $rankClass = 'rank-1';
                        elseif ($product['items_sold'] > 0 && $rank === 2) $rankClass = 'rank-2';
                        elseif ($product['items_sold'] > 0 && $rank === 3) $rankClass = 'rank-3';
                    ?>
                    <div class="mobile-product-card">
                        <?php if ($product['items_sold'] > 0): ?>
                        <span class="rank-badge <?= $rankClass ?> mobile-rank"><?= $rank ?></span>
                        <?php endif; ?>
                        
                        <div class="mobile-product-top">
                            <img src="../images/<?= $image ?>" alt="" onerror="this.src='../images/logo.jpg'">
                            <div class="mobile-product-info">
                                <div class="mobile-product-name"><?= htmlspecialchars($product['name']) ?></div>
                                <div class="mobile-product-category"><?= $catInfo ? $catInfo['icon'] . ' ' . $catInfo['name'] : 'غير محدد' ?></div>
                                <div class="mobile-product-price"><?= formatPrice($product['price']) ?></div>
                            </div>
                        </div>
                        
                        <div class="mobile-product-stats">
                            <div class="mobile-stat">
                                <div class="mobile-stat-value"><?= number_format($product['items_sold']) ?></div>
                                <div class="mobile-stat-label">قطعة مباعة</div>
                            </div>
                            <div class="mobile-stat">
                                <div class="mobile-stat-value" style="color: #4CAF50;"><?= number_format($product['revenue']) ?></div>
                                <div class="mobile-stat-label">د.ع إيرادات</div>
                            </div>
                            <div class="mobile-stat">
                                <div class="mobile-stat-value">#<?= $product['items_sold'] > 0 ? $rank : '-' ?></div>
                                <div class="mobile-stat-label">الترتيب</div>
                            </div>
                        </div>
                    </div>
                    <?php if ($product['items_sold'] > 0) $rank++; endforeach; ?>
                </div>
                
                <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <p class="empty-text">لا توجد منتجات</p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="<?= av('js/api-helper.js') ?>"></script>
    <script src="<?= av('js/admin.js') ?>"></script>
    <script>
        // Search functionality
        const searchInput = document.getElementById('searchInput');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const query = this.value.trim();
                if (query.length >= 2 || query.length === 0) {
                    window.location.href = 'sales' + (query ? '?search=' + encodeURIComponent(query) : '');
                }
            }, 500);
        });
        
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const query = this.value.trim();
                window.location.href = 'sales' + (query ? '?search=' + encodeURIComponent(query) : '');
            }
        });
    </script>
</body>
</html>
