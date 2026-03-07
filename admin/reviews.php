<?php
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

initSecureSession();
setSecurityHeaders();

if (!validateAdminSession()) {
    redirect('login');
}

$message = '';
$error = '';
$csrf_token = generateCSRFToken();

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (validateCSRFToken($_GET['token'])) {
        $id = intval($_GET['delete']);
        if (deleteReview($id)) {
            $message = 'تم حذف التقييم بنجاح';
        } else {
            $error = 'فشل في حذف التقييم';
        }
    }
}

// Handle Toggle Visibility
if (isset($_GET['toggle']) && isset($_GET['token'])) {
    if (validateCSRFToken($_GET['token'])) {
        $id = intval($_GET['toggle']);
        if (toggleReviewVisibility($id)) {
            $message = 'تم تحديث حالة التقييم';
        } else {
            $error = 'فشل في تحديث الحالة';
        }
    }
}

// Filter by product
$productFilter = isset($_GET['product']) ? intval($_GET['product']) : 0;
// Filter by category
$categoryFilter = isset($_GET['category']) ? $_GET['category'] : '';
$selectedProduct = null;

// Get all reviews with product info
$filters = [];
if ($productFilter > 0) {
    $filters['product_id'] = $productFilter;
    $selectedProduct = getProduct($productFilter);
}

$reviews = getAllReviews($filters);
$totalReviews = countReviews();
$visibleReviews = countReviews(true);

// Get all categories and create a mapping from ID/slug to Arabic name
$categories = getCategories();
$categoryNames = [];
foreach ($categories as $catId => $catData) {
    // Map both the ID and any slug variation to the Arabic name
    $categoryNames[$catId] = $catData['name'] ?? $catId;
    $categoryNames[strtolower($catId)] = $catData['name'] ?? $catId;
}

// Helper function to get Arabic category name
function getCategoryArabicName($catSlug, $categoryNames) {
    if (empty($catSlug)) return 'غير مصنف';
    $slug = strtolower(trim($catSlug));
    return $categoryNames[$slug] ?? $catSlug;
}

// Get all products with their review counts, organized by category
$allProducts = getProducts();
$productsByCategory = [];

foreach ($allProducts as $p) {
    $stats = getProductRatingStats($p['id']);
    $p['review_count'] = $stats['count'];
    $p['avg_rating'] = $stats['average'];
    
    // Use the English slug as key but we'll display Arabic name
    $catSlug = $p['category'] ?: 'uncategorized';
    if (!isset($productsByCategory[$catSlug])) {
        $productsByCategory[$catSlug] = [];
    }
    $productsByCategory[$catSlug][] = $p;
}

// Sort products within each category by review count
foreach ($productsByCategory as &$products) {
    usort($products, function($a, $b) {
        return $b['review_count'] - $a['review_count'];
    });
}

$totalProducts = count($allProducts);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة التقييمات - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        /* Reviews Admin Styles */
        .reviews-header {
            margin-bottom: 20px;
        }
        
        /* Stats Row */
        .reviews-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 18px;
            text-align: center;
            box-shadow: var(--shadow-sm);
            border-bottom: 3px solid var(--primary);
        }
        
        .stat-card.visible { border-color: #4CAF50; }
        .stat-card.hidden-stat { border-color: #9e9e9e; }
        
        .stat-number {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--primary), #ff6b9d);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            margin-top: 5px;
        }
        
        /* Main Container */
        .reviews-main {
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 25px;
            min-height: 500px;
        }
        
        /* Sidebar Panel */
        .sidebar-panel {
            background: white;
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            height: fit-content;
            max-height: calc(100vh - 200px);
            position: sticky;
            top: 20px;
        }
        
        .panel-header {
            background: linear-gradient(135deg, var(--primary), #ff6b9d);
            color: white;
            padding: 18px 20px;
        }
        
        .panel-header h3 {
            margin: 0 0 12px 0;
            font-size: 1.1rem;
        }
        
        .search-input {
            width: 100%;
            padding: 10px 15px;
            border: none;
            border-radius: var(--radius-md);
            font-size: 16px;
        }
        
        .search-input:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
        }
        
        .panel-content {
            max-height: calc(100vh - 350px);
            overflow-y: auto;
            padding: 15px;
        }
        
        /* All Reviews Button */
        .all-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px;
            background: #f5f5f5;
            border-radius: var(--radius-lg);
            margin-bottom: 15px;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.2s;
        }
        
        .all-btn:hover {
            background: #eee;
        }
        
        .all-btn.active {
            background: linear-gradient(135deg, var(--primary), #ff6b9d);
            color: white;
        }
        
        .count-badge {
            background: linear-gradient(135deg, #f4b400, #ffca28);
            color: white;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 700;
            margin-right: auto;
        }
        
        /* Category Section */
        .category-section {
            margin-bottom: 15px;
        }
        
        .category-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 15px;
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            color: var(--text-dark);
            transition: all 0.2s;
        }
        
        .category-header:hover {
            background: linear-gradient(135deg, #e9ecef, #dee2e6);
        }
        
        .category-header.active {
            background: linear-gradient(135deg, #fff0f5, #ffe8f0);
            color: var(--primary);
        }
        
        .category-arrow {
            transition: transform 0.3s;
        }
        
        .category-header.open .category-arrow {
            transform: rotate(180deg);
        }
        
        .category-products {
            display: none;
            padding: 10px 0 5px 0;
        }
        
        .category-products.show {
            display: block;
        }
        
        /* Product Item */
        .product-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            border-radius: var(--radius-md);
            text-decoration: none;
            color: inherit;
            transition: all 0.2s;
            margin-bottom: 5px;
            border: 2px solid transparent;
        }
        
        .product-item:hover {
            background: #fff5f8;
            border-color: rgba(233, 30, 140, 0.15);
        }
        
        .product-item.active {
            background: linear-gradient(135deg, #fff0f5, #ffe8f0);
            border-color: var(--primary);
        }
        
        .product-item img {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-sm);
            object-fit: cover;
        }
        
        .product-item-info {
            flex: 1;
            min-width: 0;
        }
        
        .product-item-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-dark);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .product-item-stats {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .rating-badge {
            background: #fffde7;
            color: #f4b400;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: 600;
        }
        
        /* Reviews Panel */
        .reviews-panel {
            min-width: 0;
        }
        
        /* Selected Product Header */
        .selected-header {
            background: white;
            border-radius: var(--radius-xl);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .selected-header img {
            width: 70px;
            height: 70px;
            border-radius: var(--radius-lg);
            object-fit: cover;
            border: 3px solid var(--primary);
        }
        
        .selected-info {
            flex: 1;
        }
        
        .selected-info h2 {
            margin: 0 0 8px 0;
            font-size: 1.2rem;
        }
        
        .selected-meta {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .meta-value {
            font-weight: 700;
            color: var(--primary);
        }
        
        .back-btn {
            padding: 10px 20px;
            background: #f5f5f5;
            border: none;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            color: var(--text-dark);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        
        .back-btn:hover {
            background: #eee;
        }
        
        /* Review Cards */
        .review-card {
            background: white;
            border-radius: var(--radius-xl);
            margin-bottom: 15px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }
        
        .review-card.is-hidden {
            opacity: 0.6;
        }
        
        .review-product-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 18px;
            background: #f8f9fa;
            border-bottom: 1px solid #eee;
        }
        
        .review-product-bar img {
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            object-fit: cover;
        }
        
        .review-product-bar .name {
            flex: 1;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .review-body {
            padding: 18px;
        }
        
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
            gap: 10px;
        }
        
        .reviewer {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .reviewer-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary), #ff6b9d);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
        }
        
        .reviewer-name {
            font-weight: 600;
        }
        
        .review-date {
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .review-rating {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .stars {
            color: #f4b400;
            font-size: 1.2rem;
            letter-spacing: 2px;
        }
        
        .status-tag {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-tag.visible {
            background: #d4edda;
            color: #155724;
        }
        
        .status-tag.hidden {
            background: #fff3cd;
            color: #856404;
        }
        
        .review-text {
            background: #f9f9f9;
            padding: 15px;
            border-radius: var(--radius-md);
            border-right: 3px solid var(--primary);
            line-height: 1.7;
            color: #444;
        }
        
        .review-actions {
            display: flex;
            gap: 10px;
            padding: 14px 18px;
            background: #fafafa;
            border-top: 1px solid #eee;
        }
        
        .review-actions .btn {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
        }
        
        .btn-show { background: #4CAF50; color: white; }
        .btn-hide { background: #ff9800; color: white; }
        .btn-delete { background: #f44336; color: white; }
        
        .btn:hover { opacity: 0.9; }
        
        /* Empty State */
        .empty-state {
            background: white;
            border-radius: var(--radius-xl);
            padding: 60px 20px;
            text-align: center;
            color: var(--text-muted);
        }
        
        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 15px;
            opacity: 0.5;
        }
        
        /* Load More */
        .load-more-btn {
            display: block;
            width: 100%;
            padding: 14px;
            background: #f5f5f5;
            border: none;
            border-radius: var(--radius-md);
            font-weight: 600;
            cursor: pointer;
            color: var(--primary);
            transition: all 0.2s;
        }
        
        .load-more-btn:hover {
            background: #eee;
        }
        
        .hidden-review {
            display: none;
        }
        
        /* ═══════════════════════════════════════
           MOBILE RESPONSIVE - CLEAN & ORGANIZED
           ═══════════════════════════════════════ */
        @media (max-width: 1024px) {
            .reviews-main {
                grid-template-columns: 1fr;
            }
            
            .sidebar-panel {
                position: relative;
                max-height: none;
            }
            
            .panel-content {
                max-height: none;
                overflow: visible;
            }
            
            /* Hide sidebar when viewing product reviews on mobile */
            .viewing-product .sidebar-panel {
                display: none;
            }
            
            .viewing-product .mobile-back-bar {
                display: flex !important;
            }
        }
        
        @media (max-width: 768px) {
            .reviews-stats {
                gap: 8px;
            }
            
            .stat-card {
                padding: 12px 8px;
            }
            
            .stat-number {
                font-size: 1.4rem;
            }
            
            .stat-label {
                font-size: 0.7rem;
            }
            
            /* Category sections - cleaner on mobile */
            .category-header {
                padding: 10px 12px;
                font-size: 0.9rem;
            }
            
            .category-products {
                padding: 8px 0;
            }
            
            .product-item {
                padding: 8px;
            }
            
            .product-item img {
                width: 35px;
                height: 35px;
            }
            
            .product-item-name {
                font-size: 0.8rem;
            }
            
            .product-item-stats {
                font-size: 0.7rem;
            }
            
            /* Selected product header mobile */
            .selected-header {
                flex-direction: column;
                text-align: center;
                padding: 15px;
            }
            
            .selected-header img {
                width: 60px;
                height: 60px;
            }
            
            .selected-meta {
                justify-content: center;
                gap: 15px;
            }
            
            /* Review cards mobile */
            .review-card {
                margin-bottom: 12px;
            }
            
            .review-product-bar {
                padding: 12px;
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .review-product-bar .btn {
                width: 100%;
                text-align: center;
            }
            
            .review-body {
                padding: 15px;
            }
            
            .review-header {
                flex-direction: column;
                gap: 12px;
            }
            
            .review-rating {
                width: 100%;
                justify-content: center;
                padding: 10px;
                background: #fffde7;
                border-radius: var(--radius-md);
            }
            
            .review-actions {
                flex-direction: column;
                padding: 12px;
            }
        }
        
        /* Mobile Back Bar */
        .mobile-back-bar {
            display: none;
            align-items: center;
            gap: 15px;
            background: white;
            padding: 15px;
            border-radius: var(--radius-lg);
            margin-bottom: 15px;
            box-shadow: var(--shadow-sm);
        }
        
        .mobile-back-bar .back-btn {
            padding: 12px 20px;
        }
        
        .mobile-back-bar .product-name {
            flex: 1;
            font-weight: 600;
            font-size: 0.95rem;
        }
        
        /* Search results counter */
        #searchResults {
            padding: 10px 15px;
            background: #e8f5e9;
            color: #2e7d32;
            border-radius: var(--radius-md);
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 0.85rem;
            display: none;
        }
        
        #searchResults.no-results {
            background: #ffebee;
            color: #c62828;
        }
        
        /* Scrollbar */
        .panel-content::-webkit-scrollbar {
            width: 5px;
        }
        
        .panel-content::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <main class="admin-main">
            <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <div class="reviews-header">
                <h1 class="admin-title">⭐ إدارة التقييمات</h1>
                <p style="color: var(--text-muted);">اختر قسم ثم منتج لعرض تقييماته</p>
            </div>

            <!-- Stats -->
            <div class="reviews-stats">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalReviews ?></div>
                    <div class="stat-label">📊 إجمالي</div>
                </div>
                <div class="stat-card visible">
                    <div class="stat-number" style="-webkit-text-fill-color: #4CAF50;"><?= $visibleReviews ?></div>
                    <div class="stat-label">👁 ظاهرة</div>
                </div>
                <div class="stat-card hidden-stat">
                    <div class="stat-number" style="-webkit-text-fill-color: #9e9e9e;"><?= $totalReviews - $visibleReviews ?></div>
                    <div class="stat-label">🚫 مخفية</div>
                </div>
            </div>

            <div class="reviews-main <?= $productFilter ? 'viewing-product' : '' ?>">
                <!-- Sidebar: Categories & Products -->
                <div class="sidebar-panel">
                    <div class="panel-header">
                        <h3>📦 المنتجات (<?= $totalProducts ?>)</h3>
                        <input type="text" class="search-input" id="searchInput" placeholder="🔍 ابحث عن منتج...">
                    </div>
                    
                    <div class="panel-content">
                        <div id="searchResults"></div>
                        
                        <a href="reviews" class="all-btn <?= !$productFilter && !$categoryFilter ? 'active' : '' ?>">
                            <span>📊</span>
                            <span>جميع التقييمات</span>
                            <span class="count-badge"><?= $totalReviews ?></span>
                        </a>
                        
                        <!-- Categories with Products -->
                        <?php foreach ($productsByCategory as $catSlug => $catProducts): ?>
                        <?php 
                        // Get Arabic name for category
                        $catDisplayName = getCategoryArabicName($catSlug, $categoryNames);
                        ?>
                        <div class="category-section" data-category="<?= htmlspecialchars($catSlug) ?>">
                            <div class="category-header <?= $categoryFilter === $catSlug ? 'active open' : '' ?>">
                                <span>📂 <?= htmlspecialchars($catDisplayName) ?> (<?= count($catProducts) ?>)</span>
                                <span class="category-arrow">▼</span>
                            </div>
                            <div class="category-products <?= $categoryFilter === $catSlug ? 'show' : '' ?>">
                                <?php foreach ($catProducts as $p): ?>
                                <?php 
                                $pImg = !empty($p['images'][0]) ? '../images/' . $p['images'][0] : '../images/logo.jpg';
                                ?>
                                <a href="reviews?product=<?= $p['id'] ?>&category=<?= urlencode($catSlug) ?>" 
                                   class="product-item <?= $productFilter == $p['id'] ? 'active' : '' ?>"
                                   data-name="<?= htmlspecialchars(strtolower($p['name'])) ?>">
                                    <img src="<?= $pImg ?>" alt="" onerror="this.src='../images/logo.jpg'">
                                    <div class="product-item-info">
                                        <div class="product-item-name"><?= htmlspecialchars($p['name']) ?></div>
                                        <div class="product-item-stats">
                                            <?php if ($p['review_count'] > 0): ?>
                                            <span class="count-badge" style="font-size: 0.65rem; padding: 1px 6px;"><?= $p['review_count'] ?></span>
                                            <span class="rating-badge">★ <?= $p['avg_rating'] ?></span>
                                            <?php else: ?>
                                            <span style="color: #aaa;">لا توجد تقييمات</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Reviews Panel -->
                <div class="reviews-panel">
                    <?php if ($productFilter && $selectedProduct): ?>
                    <!-- Mobile Back Bar -->
                    <div class="mobile-back-bar">
                        <a href="reviews" class="back-btn">← رجوع</a>
                        <span class="product-name"><?= htmlspecialchars($selectedProduct['name']) ?></span>
                    </div>
                    
                    <!-- Selected Product Header -->
                    <?php $pStats = getProductRatingStats($productFilter); ?>
                    <div class="selected-header">
                        <?php $selImg = !empty($selectedProduct['images'][0]) ? '../images/' . $selectedProduct['images'][0] : '../images/logo.jpg'; ?>
                        <img src="<?= $selImg ?>" alt="" onerror="this.src='../images/logo.jpg'">
                        <div class="selected-info">
                            <h2>📦 <?= htmlspecialchars($selectedProduct['name']) ?></h2>
                            <div class="selected-meta">
                                <span>⭐ التقييم: <span class="meta-value"><?= $pStats['average'] ?>/5</span></span>
                                <span>💬 التقييمات: <span class="meta-value"><?= $pStats['count'] ?></span></span>
                            </div>
                        </div>
                        <a href="reviews" class="back-btn desktop-back">← رجوع للأقسام</a>
                    </div>
                    <?php endif; ?>

                    <!-- Reviews List -->
                    <?php if (empty($reviews)): ?>
                    <div class="empty-state">
                        <div class="icon">⭐</div>
                        <h3><?= $productFilter ? 'لا توجد تقييمات لهذا المنتج' : 'لا توجد تقييمات بعد' ?></h3>
                        <p>ستظهر التقييمات هنا عندما يقوم العملاء بتقييم المنتجات</p>
                    </div>
                    <?php else: ?>
                    
                    <?php 
                    $reviewLimit = 10;
                    foreach ($reviews as $idx => $review): 
                    $hideReview = $idx >= $reviewLimit ? 'hidden-review' : '';
                    ?>
                    <div class="review-card <?= $review['is_visible'] ? '' : 'is-hidden' ?> <?= $hideReview ?>">
                        <?php if (!$productFilter): ?>
                        <div class="review-product-bar">
                            <?php 
                            $rImg = !empty($review['product_images'][0]) ? '../images/' . $review['product_images'][0] : '../images/logo.jpg';
                            ?>
                            <img src="<?= $rImg ?>" alt="" onerror="this.src='../images/logo.jpg'">
                            <span class="name"><?= htmlspecialchars($review['product_name'] ?: 'منتج محذوف') ?></span>
                            <a href="reviews?product=<?= $review['product_id'] ?>" class="btn" style="padding: 6px 12px; background: #eee; color: var(--text-dark); font-size: 0.75rem;">
                                عرض تقييماته
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <div class="review-body">
                            <div class="review-header">
                                <div class="reviewer">
                                    <div class="reviewer-avatar">👤</div>
                                    <div>
                                        <div class="reviewer-name"><?= htmlspecialchars($review['customer_name']) ?></div>
                                        <div class="review-date">📅 <?= formatReviewDate($review['created_at']) ?></div>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <span class="stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <?= $i <= $review['rating'] ? '★' : '☆' ?>
                                        <?php endfor; ?>
                                    </span>
                                    <span class="status-tag <?= $review['is_visible'] ? 'visible' : 'hidden' ?>">
                                        <?= $review['is_visible'] ? '👁 ظاهر' : '🚫 مخفي' ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if (!empty($review['comment'])): ?>
                            <div class="review-text">"<?= nl2br(htmlspecialchars($review['comment'])) ?>"</div>
                            <?php else: ?>
                            <p style="color: #999; font-style: italic;">— تقييم بدون تعليق —</p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="review-actions">
                            <a href="reviews?toggle=<?= $review['id'] ?>&token=<?= $csrf_token ?><?= $productFilter ? '&product='.$productFilter : '' ?>" 
                               class="btn <?= $review['is_visible'] ? 'btn-hide' : 'btn-show' ?>">
                                <?= $review['is_visible'] ? '🚫 إخفاء' : '👁 إظهار' ?>
                            </a>
                            <a href="reviews?delete=<?= $review['id'] ?>&token=<?= $csrf_token ?><?= $productFilter ? '&product='.$productFilter : '' ?>" 
                               class="btn btn-delete"
                               onclick="return confirm('⚠️ حذف هذا التقييم نهائياً؟')">
                                🗑️ حذف
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($reviews) > $reviewLimit): ?>
                    <button type="button" id="loadMoreReviews" class="load-more-btn">
                        👁 عرض المزيد (<?= count($reviews) - $reviewLimit ?> تقييم آخر)
                    </button>
                    <?php endif; ?>
                    
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Category Toggle
        document.querySelectorAll('.category-header').forEach(header => {
            header.addEventListener('click', function() {
                const productsDiv = this.nextElementSibling;
                const isOpen = this.classList.contains('open');
                
                // Close all other categories
                document.querySelectorAll('.category-header').forEach(h => {
                    h.classList.remove('open');
                    h.nextElementSibling.classList.remove('show');
                });
                
                // Toggle current
                if (!isOpen) {
                    this.classList.add('open');
                    productsDiv.classList.add('show');
                }
            });
        });
        
        // Search Products
        const searchInput = document.getElementById('searchInput');
        const searchResults = document.getElementById('searchResults');
        const allBtn = document.querySelector('.all-btn');
        const categories = document.querySelectorAll('.category-section');
        const products = document.querySelectorAll('.product-item');
        
        if (searchInput) {
            let timeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => performSearch(this.value), 150);
            });
        }
        
        function performSearch(query) {
            query = query.toLowerCase().trim();
            let count = 0;
            
            if (!query) {
                // Reset view
                searchResults.style.display = 'none';
                allBtn.style.display = 'flex';
                categories.forEach(cat => {
                    cat.style.display = 'block';
                    cat.querySelector('.category-products').classList.remove('show');
                    cat.querySelector('.category-header').classList.remove('open');
                });
                products.forEach(p => p.style.display = 'flex');
                return;
            }
            
            // Search mode
            allBtn.style.display = 'none';
            categories.forEach(cat => {
                const catProducts = cat.querySelectorAll('.product-item');
                let hasMatch = false;
                
                catProducts.forEach(p => {
                    const name = p.dataset.name || '';
                    const textName = p.querySelector('.product-item-name')?.textContent.toLowerCase() || '';
                    
                    if (name.includes(query) || textName.includes(query)) {
                        p.style.display = 'flex';
                        hasMatch = true;
                        count++;
                        
                        // Highlight match
                        const nameEl = p.querySelector('.product-item-name');
                        if (nameEl && !nameEl.dataset.original) {
                            nameEl.dataset.original = nameEl.textContent;
                        }
                        if (nameEl && nameEl.dataset.original) {
                            const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
                            nameEl.innerHTML = nameEl.dataset.original.replace(regex, '<mark style="background:#fff176;padding:0 2px;border-radius:2px;">$1</mark>');
                        }
                    } else {
                        p.style.display = 'none';
                    }
                });
                
                if (hasMatch) {
                    cat.style.display = 'block';
                    cat.querySelector('.category-products').classList.add('show');
                    cat.querySelector('.category-header').classList.add('open');
                } else {
                    cat.style.display = 'none';
                }
            });
            
            // Show results count
            searchResults.style.display = 'block';
            if (count > 0) {
                searchResults.className = '';
                searchResults.innerHTML = '✓ تم العثور على <strong>' + count + '</strong> منتج';
            } else {
                searchResults.className = 'no-results';
                searchResults.innerHTML = '✗ لا توجد نتائج لـ "' + escapeHtml(query) + '"';
            }
        }
        
        function escapeRegex(str) {
            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }
        
        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }
        
        // Load More Reviews
        const loadMoreBtn = document.getElementById('loadMoreReviews');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                document.querySelectorAll('.review-card.hidden-review').forEach(card => {
                    card.classList.remove('hidden-review');
                });
                this.style.display = 'none';
            });
        }
    </script>
    
    <style>
        .desktop-back {
            display: inline-flex;
        }
        
        @media (max-width: 1024px) {
            .desktop-back {
                display: none !important;
            }
        }
    </style>
    
    <!-- Admin JS for sidebar toggle -->
    <script src="<?= av('js/admin.js') ?>"></script>
</body>
</html>
