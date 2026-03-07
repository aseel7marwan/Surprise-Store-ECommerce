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

// Handle Status Update
if (isset($_POST['update_status'])) {
    if (validateCSRFToken($_POST['csrf_token'])) {
        $id = intval($_POST['order_id']);
        $status = $_POST['new_status'];
        if (updateOrderStatus($id, $status)) {
            $message = 'تم تحديث حالة الطلب';
        }
    }
}

// Handle Delete
if (isset($_GET['delete']) && isset($_GET['token'])) {
    if (validateCSRFToken($_GET['token'])) {
        if (deleteOrder(intval($_GET['delete']))) {
            $message = 'تم حذف الطلب';
        }
    }
}

// View single order
$viewOrder = null;
if (isset($_GET['view'])) {
    $viewOrder = getOrder($_GET['view']);
}

// ========== FILTERS & SEARCH ==========
$currentYear = intval(date('Y'));
$currentMonth = intval(date('m'));

// Get available years from orders
$pdo = db();
$stmt = $pdo->query("SELECT DISTINCT YEAR(created_at) as year FROM orders WHERE created_at IS NOT NULL ORDER BY year DESC");
$availableYears = $stmt->fetchAll(PDO::FETCH_COLUMN);
if (!in_array($currentYear, $availableYears)) {
    array_unshift($availableYears, $currentYear);
}
rsort($availableYears);

$arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];

$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';
$yearFilter = isset($_GET['year']) ? intval($_GET['year']) : $currentYear;
$monthFilter = isset($_GET['month']) ? intval($_GET['month']) : 0;
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Pagination
$perPage = 15;
$currentPage = max(1, intval(isset($_GET['page']) ? $_GET['page'] : 1));
$offset = ($currentPage - 1) * $perPage;

// Get filtered orders
$filters = [
    'status' => $statusFilter,
    'search' => $searchQuery,
    'year' => $yearFilter,
    'month' => $monthFilter,
    'sort' => $sortBy,
    'limit' => $perPage,
    'offset' => $offset
];

$orders = getOrders($filters);

// Get total for pagination (without limit)
$totalFilters = $filters;
unset($totalFilters['limit'], $totalFilters['offset']);
$totalOrders = count(getOrders($totalFilters));
$totalPages = ceil($totalOrders / $perPage);

// Stats
$stats = [
    'total' => countOrders(),
    'pending' => countOrders('pending'),
    'confirmed' => countOrders('confirmed'),
    'processing' => countOrders('processing'),
    'shipped' => countOrders('shipped'),
    'delivered' => countOrders('delivered'),
    'cancelled' => countOrders('cancelled')
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الطلبات - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= av('css/main.css') ?>">
    <link rel="stylesheet" href="<?= av('css/admin.css') ?>">
    <link rel="icon" href="../images/logo.jpg">
    <style>
        .status-all { background: linear-gradient(135deg, #E91E8C, #FF6B9D); color: white; }
        .status-pending { background: #FFF3E0; color: #E65100; }
        .status-confirmed { background: #E3F2FD; color: #1565C0; }
        .status-processing { background: #F3E5F5; color: #7B1FA2; }
        .status-shipped { background: #E8F5E9; color: #2E7D32; }
        .status-delivered { background: #E0F2F1; color: #00695C; }
        .status-cancelled { background: #FFEBEE; color: #C62828; }
        
        .order-detail-card { background: white; border-radius: var(--radius-lg); box-shadow: var(--shadow-md); overflow: hidden; }
        .order-detail-header { 
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); 
            color: white; 
            padding: 25px; 
        }
        .order-detail-header h2,
        .order-detail-header p,
        .order-detail-header span:not(.status) {
            color: white !important;
        }
        .order-detail-header h2 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            letter-spacing: 1px;
        }
        .order-detail-header p {
            opacity: 0.95;
            font-size: 1rem;
            font-weight: 500;
        }
        .order-items-table { width: 100%; border-collapse: collapse; }
        .order-items-table th, .order-items-table td { padding: 12px; border-bottom: 1px solid #eee; text-align: right; }
        
        /* Enhanced Customer Images Gallery */
        .customer-images-section {
            margin-top: 25px;
            padding: 20px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-radius: var(--radius-lg);
            border: 2px dashed rgba(233, 30, 140, 0.2);
        }
        
        .customer-images-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
        }
        
        .customer-images-header h4 {
            margin: 0;
            color: var(--text-dark);
        }
        
        .customer-images-header .badge {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
        }
        
        .customer-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .customer-image-card {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            background: white;
        }
        
        .customer-image-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(233, 30, 140, 0.2);
        }
        
        .customer-image-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        
        .customer-image-card:hover img {
            transform: scale(1.05);
        }
        
        .customer-image-actions {
            display: flex;
            justify-content: center;
            gap: 5px;
            padding: 10px;
            background: white;
        }
        
        .customer-image-actions a {
            padding: 8px 15px;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #E91E8C, #FF6B9D);
            color: white;
        }
        
        .btn-view:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(233, 30, 140, 0.3);
        }
        
        .btn-download {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .btn-download:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
        }
        
        /* Image Lightbox */
        .image-lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 10000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            padding: 20px;
        }
        
        .image-lightbox.active {
            display: flex;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .lightbox-close {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .lightbox-close:hover {
            background: rgba(255,255,255,0.2);
            transform: rotate(90deg);
        }
        
        .lightbox-image-container {
            max-width: 90vw;
            max-height: 75vh;
            overflow: auto;
            border-radius: 12px;
            background: #111;
            padding: 10px;
        }
        
        .lightbox-image-container img {
            max-width: 100%;
            max-height: 70vh;
            object-fit: contain;
            border-radius: 8px;
        }
        
        .lightbox-controls {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .lightbox-controls button,
        .lightbox-controls a {
            padding: 12px 25px;
            border-radius: 30px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
            border: none;
        }
        
        .lightbox-zoom {
            background: rgba(255,255,255,0.15);
            color: white;
            border: 2px solid rgba(255,255,255,0.3) !important;
        }
        
        .lightbox-zoom:hover {
            background: rgba(255,255,255,0.25);
        }
        
        .lightbox-download {
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
        }
        
        .lightbox-download:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 20px rgba(76, 175, 80, 0.4);
        }
        
        .lightbox-info {
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            margin-top: 15px;
            text-align: center;
        }
        
        .search-filters { 
            background: white; 
            border-radius: var(--radius-lg); 
            padding: 25px; 
            margin-bottom: 20px; 
            box-shadow: var(--shadow-sm);
            border: 1px solid rgba(233, 30, 140, 0.08);
        }
        
        .search-filters-grid { 
            display: grid; 
            grid-template-columns: 2fr repeat(4, 1fr) auto; 
            gap: 20px; 
            align-items: end; 
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .filter-group .form-label {
            font-weight: 700;
            color: var(--text-dark);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .premium-select {
            padding: 12px 18px;
            border-radius: var(--radius-lg);
            border: 2px solid rgba(233, 30, 140, 0.15);
            background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
            font-weight: 600;
            font-size: 0.9rem;
            min-width: 140px;
            cursor: pointer;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23E91E8C' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 12px center;
            padding-left: 35px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        .premium-select:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 15px rgba(233, 30, 140, 0.15);
            transform: translateY(-2px);
        }
        
        .premium-select:focus {
            border-color: var(--primary);
            outline: none;
            box-shadow: 0 0 0 4px rgba(233, 30, 140, 0.1), 0 4px 15px rgba(233, 30, 140, 0.15);
        }
        
        .stats-mini { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 20px; }
        .stats-mini-item { padding: 8px 15px; border-radius: var(--radius-full); font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: var(--transition); text-decoration: none; }
        .stats-mini-item:hover { transform: scale(1.05); }
        .pagination { display: flex; justify-content: center; gap: 5px; margin-top: 20px; padding: 20px; flex-wrap: wrap; }
        .pagination a, .pagination span { padding: 8px 15px; border-radius: var(--radius-md); background: white; color: var(--text-dark); font-weight: 600; text-decoration: none; }
        .pagination a:hover { background: var(--primary-light); }
        .pagination .active { background: var(--primary); color: white; }
        
        .clear-filters-btn {
            padding: 12px 20px;
            border-radius: var(--radius-lg);
            background: linear-gradient(135deg, #f5f5f5 0%, #e8e8e8 100%);
            border: 2px solid transparent;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
        }
        
        .clear-filters-btn:hover {
            background: linear-gradient(135deg, #ffe0f0 0%, #ffd0e8 100%);
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        /* Mobile Toggle Button */
        .filters-toggle-btn {
            display: none;
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            margin-bottom: 15px;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .filters-toggle-btn.active {
            background: linear-gradient(135deg, #666, #888);
        }
        
        /* Orders Table Mobile */
        .orders-table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        
        /* Mobile Cards View */
        .mobile-order-card {
            display: none;
            background: white;
            border-radius: var(--radius-lg);
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: var(--shadow-sm);
            border-right: 4px solid var(--primary);
        }
        
        .mobile-order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
            padding-bottom: 12px;
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
        
        .mobile-order-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }
        
        .mobile-order-item .label {
            font-size: 0.75rem;
            color: var(--text-muted);
        }
        
        .mobile-order-item .value {
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .mobile-order-footer {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px dashed #eee;
        }
        
        .mobile-order-footer .btn {
            flex: 1;
            padding: 10px;
            font-size: 0.85rem;
            text-align: center;
        }
        
        @media (max-width: 1200px) { 
            .search-filters-grid { grid-template-columns: 1fr 1fr 1fr; } 
        }
        
        @media (max-width: 768px) { 
            .search-filters-grid { 
                grid-template-columns: 1fr; 
                gap: 12px;
            }
            
            .search-filters {
                padding: 15px;
            }
            
            .search-filters.collapsed {
                display: none;
            }
            
            .filters-toggle-btn {
                display: flex;
            }
            
            .stats-mini {
                justify-content: center;
            }
            
            .stats-mini-item {
                padding: 6px 12px;
                font-size: 0.8rem;
            }
            
            /* Hide table, show cards */
            .orders-table {
                display: none;
            }
            
            .mobile-order-card {
                display: block;
            }
            
            .admin-title {
                font-size: 1.2rem;
            }
            
            .pagination a, .pagination span {
                padding: 6px 10px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 480px) { 
            .mobile-order-body {
                grid-template-columns: 1fr;
            }
            
            .mobile-order-footer {
                flex-direction: column;
            }
            
            .stats-mini-item {
                padding: 5px 10px;
                font-size: 0.75rem;
            }
            
            /* Order product card mobile */
            .order-product-card {
                flex-direction: column !important;
            }
            
            .order-product-card > div:first-child {
                width: 100% !important;
                display: flex;
                justify-content: center;
            }
            
            .order-product-card > div:first-child img:first-child {
                width: 100% !important;
                max-width: 200px;
                height: auto !important;
                aspect-ratio: 1;
            }
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="admin-main">
            <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>

            <?php if ($viewOrder): ?>
            <!-- Order Detail View -->
            <div class="admin-header">
                <h1 class="admin-title">📋 تفاصيل الطلب #<?= $viewOrder['order_number'] ?></h1>
                <a href="orders" class="btn btn-outline">← العودة للقائمة</a>
            </div>
            
            <div class="order-detail-card">
                <div class="order-detail-header">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
                        <div>
                            <h2 style="font-size: 1.5rem;">#<?= $viewOrder['order_number'] ?></h2>
                            <p style="opacity: 0.9;"><?= formatDateTime($viewOrder['created_at'], 'full') ?></p>
                        </div>
                        <div>
                            <?php $st = getOrderStatusLabel($viewOrder['status']); ?>
                            <span class="status <?= $st['class'] ?>" style="font-size: 1rem; padding: 8px 20px;">
                                <?= $st['icon'] ?> <?= $st['label'] ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div style="padding: 25px;">
                    <!-- Customer Info -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
                        <div>
                            <h4 style="color: var(--text-muted); margin-bottom: 8px;">👤 اسم الزبون</h4>
                            <p style="font-size: 1.1rem;"><?= $viewOrder['customer_name'] ?: '(غير محدد)' ?></p>
                        </div>
                        <div>
                            <h4 style="color: var(--text-muted); margin-bottom: 8px;">📱 رقم الهاتف</h4>
                            <p style="font-size: 1.1rem;"><?= $viewOrder['customer_phone'] ?: '(غير محدد)' ?></p>
                        </div>
                        <div>
                            <h4 style="color: var(--text-muted); margin-bottom: 8px;">📍 المحافظة</h4>
                            <p style="font-size: 1.1rem;"><?= $viewOrder['customer_city'] ?: '(غير محدد)' ?></p>
                        </div>
                    </div>
                    
                    <?php 
                    // For older orders, try to extract area from combined address
                    $displayAddress = $viewOrder['customer_address'] ?: '';
                    $areaDisplay = '';
                    
                    // Check if address contains " - " which indicates area + detailed address
                    if (strpos($displayAddress, ' - ') !== false) {
                        $parts = explode(' - ', $displayAddress, 2);
                        $areaDisplay = trim($parts[0]);
                        $displayAddress = isset($parts[1]) ? trim($parts[1]) : '';
                    }
                    ?>
                    
                    <?php if (!empty($areaDisplay)): ?>
                    <div style="background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); padding: 12px 20px; border-radius: var(--radius-md); margin-bottom: 15px; border-right: 4px solid #4caf50;">
                        <h4 style="color: #2e7d32; margin-bottom: 6px;">📍 المنطقة/الحي</h4>
                        <p style="font-size: 1.1rem; color: #1b5e20;"><?= htmlspecialchars($areaDisplay) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($displayAddress)): ?>
                    <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 15px 20px; border-radius: var(--radius-md); margin-bottom: 25px; border-right: 4px solid var(--primary);">
                        <h4 style="color: var(--text-muted); margin-bottom: 8px;">🏠 العنوان التفصيلي</h4>
                        <p style="font-size: 1.1rem; line-height: 1.6;"><?= nl2br(htmlspecialchars($displayAddress)) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($viewOrder['contact_method']) && !empty($viewOrder['contact_value'])): ?>
                    <div style="background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%); padding: 15px 20px; border-radius: var(--radius-md); margin-bottom: 25px; border-right: 4px solid #7c4dff;">
                        <h4 style="color: #5e35b1; margin-bottom: 8px;">📱 وسيلة التواصل المفضلة</h4>
                        <div style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                            <?php 
                            $contactIconImg = '';
                            $contactLabel = '';
                            switch($viewOrder['contact_method']) {
                                case 'instagram': 
                                    $contactIconImg = '<img src="../images/icons/icon1.png" alt="Instagram" style="width: 18px; height: 18px; vertical-align: middle; margin-left: 5px;">'; 
                                    $contactLabel = 'انستقرام';
                                    break;
                                case 'whatsapp': 
                                    $contactIconImg = '<img src="../images/icons/whatsapp.png" alt="WhatsApp" style="width: 18px; height: 18px; vertical-align: middle; margin-left: 5px;">'; 
                                    $contactLabel = 'واتساب';
                                    break;
                                case 'telegram': 
                                    $contactIconImg = '<img src="../images/icons/icon2.png" alt="Telegram" style="width: 18px; height: 18px; vertical-align: middle; margin-left: 5px;">'; 
                                    $contactLabel = 'تيليجرام';
                                    break;
                            }
                            ?>
                            <span style="background: linear-gradient(135deg, #7c4dff, #651fff); color: white; padding: 8px 16px; border-radius: 20px; font-weight: 600; display: inline-flex; align-items: center;">
                                <?= $contactIconImg ?> <?= $contactLabel ?>
                            </span>
                            <span style="font-size: 1.2rem; font-weight: 700; color: #333; direction: ltr;">
                                <?= htmlspecialchars($viewOrder['contact_value']) ?>
                            </span>
                            <?php if ($viewOrder['contact_method'] === 'whatsapp'): ?>
                            <a href="https://wa.me/<?= preg_replace('/^0/', '964', $viewOrder['contact_value']) ?>" 
                               target="_blank" 
                               style="background: #25D366; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 5px;">
                                <img src="../images/icons/whatsapp.png" alt="WhatsApp" style="width: 16px; height: 16px;"> مراسلة
                            </a>
                            <?php elseif ($viewOrder['contact_method'] === 'telegram' && strpos($viewOrder['contact_value'], '@') === 0): ?>
                            <a href="https://t.me/<?= substr($viewOrder['contact_value'], 1) ?>" 
                               target="_blank" 
                               style="background: #0088cc; color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 5px;">
                                <img src="../images/icons/icon2.png" alt="Telegram" style="width: 16px; height: 16px;"> مراسلة
                            </a>
                            <?php elseif ($viewOrder['contact_method'] === 'instagram'): ?>
                            <a href="https://instagram.com/<?= str_replace('@', '', $viewOrder['contact_value']) ?>" 
                               target="_blank" 
                               style="background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); color: white; padding: 6px 12px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; display: inline-flex; align-items: center; gap: 5px;">
                                <img src="../images/icons/icon1.png" alt="Instagram" style="width: 16px; height: 16px;"> زيارة
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Terms Consent Status -->
                    <?php if (isset($viewOrder['terms_consent'])): ?>
                    <div style="background: <?= $viewOrder['terms_consent'] ? 'linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%)' : 'linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%)' ?>; padding: 15px 20px; border-radius: var(--radius-md); margin-bottom: 25px; border-right: 4px solid <?= $viewOrder['terms_consent'] ? '#4caf50' : '#f44336' ?>;">
                        <h4 style="color: <?= $viewOrder['terms_consent'] ? '#2e7d32' : '#c62828' ?>; margin-bottom: 8px;">
                            <?= $viewOrder['terms_consent'] ? '✅' : '❌' ?> الموافقة على الشروط والخصوصية
                        </h4>
                        <div style="font-size: 1rem;">
                            <?php if ($viewOrder['terms_consent']): ?>
                                <span style="color: #2e7d32; font-weight: 600;">تمت الموافقة</span>
                                <?php if (!empty($viewOrder['consent_timestamp'])): ?>
                                    <span style="color: #666; margin-right: 10px;">
                                        🕐 <?= formatDateTime($viewOrder['consent_timestamp'], 'full') ?>
                                    </span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #c62828; font-weight: 600;">لم تتم الموافقة</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Order Items - Enhanced Display -->
                    <h3 style="margin-bottom: 15px;">🛒 المنتجات المطلوبة</h3>
                    
                    <div class="order-products-grid" style="display: flex; flex-direction: column; gap: 20px;">
                        <?php foreach ($viewOrder['items'] as $item): ?>
                        <div class="order-product-card" style="display: flex; gap: 20px; background: #f8f9fa; border-radius: 12px; padding: 15px; border: 1px solid #e9ecef;">
                            <!-- Product Image -->
                            <div style="flex-shrink: 0; width: 120px;">
                                <?php 
                                $productImage = '../images/products/giftbox.png';
                                if (!empty($item['images']) && is_array($item['images']) && count($item['images']) > 0) {
                                    $imgPath = $item['images'][0];
                                    // تحقق إذا كان المسار يحتوي على products/ بالفعل
                                    if (strpos($imgPath, 'products/') === 0) {
                                        $productImage = '../images/' . $imgPath;
                                    } else {
                                        $productImage = '../images/products/' . $imgPath;
                                    }
                                }
                                ?>
                                <img src="<?= $productImage ?>" 
                                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                     style="width: 120px; height: 120px; object-fit: cover; border-radius: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                                     onerror="this.src='../images/products/giftbox.png'">
                                <?php if (!empty($item['images']) && count($item['images']) > 1): ?>
                                <div style="display: flex; gap: 4px; margin-top: 8px; flex-wrap: wrap;">
                                    <?php for ($i = 1; $i < min(4, count($item['images'])); $i++): 
                                        $thumbPath = $item['images'][$i];
                                        $thumbSrc = (strpos($thumbPath, 'products/') === 0) ? '../images/' . $thumbPath : '../images/products/' . $thumbPath;
                                    ?>
                                    <img src="<?= $thumbSrc ?>" 
                                         style="width: 35px; height: 35px; object-fit: cover; border-radius: 5px; cursor: pointer;"
                                         onclick="window.open('<?= $thumbSrc ?>', '_blank')"
                                         onerror="this.style.display='none'">
                                    <?php endfor; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Product Details -->
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                    <div>
                                        <h4 style="margin: 0; font-size: 1.1rem; color: #333;">
                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </h4>
                                        <?php if (!empty($item['product_category'])): ?>
                                        <span style="display: inline-block; background: linear-gradient(135deg, #E91E8C, #FF6B9D); color: white; font-size: 0.75rem; padding: 3px 10px; border-radius: 15px; margin-top: 5px;">
                                            <?= htmlspecialchars($item['product_category']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($item['product_id'])): ?>
                                    <a href="products?edit=<?= $item['product_id'] ?>" 
                                       style="background: #f0f0f0; padding: 5px 10px; border-radius: 8px; font-size: 0.8rem; color: #666; text-decoration: none;"
                                       title="تعديل المنتج">
                                        ✏️ تعديل
                                    </a>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if (!empty($item['product_description'])): ?>
                                <p style="color: #666; font-size: 0.9rem; margin-bottom: 10px; line-height: 1.5;">
                                    <?= mb_substr(strip_tags($item['product_description']), 0, 100) ?>...
                                </p>
                                <?php endif; ?>
                                
                                <div style="display: flex; gap: 20px; flex-wrap: wrap; align-items: center;">
                                    <div>
                                        <span style="color: #888; font-size: 0.85rem;">السعر:</span>
                                        <strong style="color: #D4AF37; font-size: 1.1rem;"><?= formatPrice($item['price']) ?></strong>
                                    </div>
                                    <div>
                                        <span style="color: #888; font-size: 0.85rem;">الكمية:</span>
                                        <strong style="color: #333; font-size: 1.1rem;"><?= $item['quantity'] ?></strong>
                                    </div>
                                    <div>
                                        <span style="color: #888; font-size: 0.85rem;">المجموع:</span>
                                        <strong style="color: var(--primary); font-size: 1.1rem;"><?= formatPrice($item['price'] * $item['quantity']) ?></strong>
                                    </div>
                                    <?php if (!empty($item['packaging_selected'])): ?>
                                    <span style="background: #F3E5F5; color: #7B1FA2; padding: 5px 12px; border-radius: 15px; font-size: 0.85rem; font-weight: 600; border: 1px solid rgba(123, 31, 162, 0.2);">
                                        🎁 التغليف: نعم <?= !empty($item['packaging_description']) ? "(" . htmlspecialchars($item['packaging_description']) . ")" : "" ?> (+ <?= formatPrice($item['packaging_price']) ?>)
                                    </span>
                                    <?php else: ?>
                                    <span style="background: #f5f5f5; color: #666; padding: 5px 12px; border-radius: 15px; font-size: 0.85rem;">
                                        🎁 لا يوجد تغليف
                                    </span>
                                    <?php endif; ?>
                                    
                                    <?php if ($item['has_custom_image']): ?>
                                    <span style="background: #E3F2FD; color: #1565C0; padding: 5px 12px; border-radius: 15px; font-size: 0.85rem;">
                                        📷 صورة مخصصة
                                    </span>
                                    <?php endif; ?>
                                    <?php if (!empty($item['product_customizable'])): ?>
                                    <span style="background: #FFF3E0; color: #E65100; padding: 5px 12px; border-radius: 15px; font-size: 0.85rem;">
                                        ✨ قابل للتخصيص
                                    </span>
                                    <?php endif; ?>
                                    <?php if (isset($item['product_stock'])): ?>
                                    <span style="background: <?= $item['product_stock'] > 0 ? '#E8F5E9' : '#FFEBEE' ?>; color: <?= $item['product_stock'] > 0 ? '#2E7D32' : '#C62828' ?>; padding: 5px 12px; border-radius: 15px; font-size: 0.85rem;">
                                        📦 المخزون: <?= $item['product_stock'] ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php 
                                // Display selected options if present - دعم نظام المجموعات الجديد
                                if (!empty($item['selected_options']) && is_array($item['selected_options'])): 
                                    $opts = $item['selected_options'];
                                ?>
                                <div style="margin-top: 12px; padding: 12px 15px; background: linear-gradient(135deg, rgba(156, 39, 176, 0.08), rgba(103, 58, 183, 0.08)); border-radius: 10px; border-right: 3px solid #9c27b0;">
                                    <div style="font-weight: 600; color: #7b1fa2; font-size: 0.85rem; margin-bottom: 8px;">🎛️ الخيارات المختارة:</div>
                                    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                                        <?php // الخيارات البسيطة (مجموعة واحدة) ?>
                                        <?php if (!empty($opts['color'])): ?>
                                        <span style="background: linear-gradient(135deg, #e91e8c, #c2185b); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                            🎨 اللون: <?= htmlspecialchars($opts['color']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($opts['size'])): ?>
                                        <span style="background: linear-gradient(135deg, #2196f3, #1976d2); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                            📐 الحجم: <?= htmlspecialchars($opts['size']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($opts['age'])): ?>
                                        <span style="background: linear-gradient(135deg, #4caf50, #388e3c); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                            👶 العمر: <?= htmlspecialchars($opts['age']) ?>
                                        </span>
                                        <?php endif; ?>
                                        <?php if (!empty($opts['custom_text'])): ?>
                                        <span style="background: linear-gradient(135deg, #ff9800, #f57c00); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                            ✏️ النص: <?= htmlspecialchars($opts['custom_text']) ?>
                                        </span>
                                        <?php endif; ?>
                                        
                                        <?php // مجموعات الألوان المتعددة ?>
                                        <?php if (!empty($opts['color_groups']) && is_array($opts['color_groups'])): ?>
                                            <?php foreach ($opts['color_groups'] as $groupLabel => $value): ?>
                                            <span style="background: linear-gradient(135deg, #e91e8c, #c2185b); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                                🎨 <?= htmlspecialchars($groupLabel) ?>: <?= htmlspecialchars($value) ?>
                                            </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <?php // مجموعات الأحجام المتعددة ?>
                                        <?php if (!empty($opts['size_groups']) && is_array($opts['size_groups'])): ?>
                                            <?php foreach ($opts['size_groups'] as $groupLabel => $value): ?>
                                            <span style="background: linear-gradient(135deg, #2196f3, #1976d2); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                                📐 <?= htmlspecialchars($groupLabel) ?>: <?= htmlspecialchars($value) ?>
                                            </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <?php // مجموعات الفئات العمرية المتعددة ?>
                                        <?php if (!empty($opts['age_groups']) && is_array($opts['age_groups'])): ?>
                                            <?php foreach ($opts['age_groups'] as $groupLabel => $value): ?>
                                            <span style="background: linear-gradient(135deg, #4caf50, #388e3c); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                                👶 <?= htmlspecialchars($groupLabel) ?>: <?= htmlspecialchars($value) ?>
                                            </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <?php // الحقول الإضافية الموحدة ?>
                                        <?php if (!empty($opts['extra_fields']) && is_array($opts['extra_fields'])): ?>
                                            <?php foreach ($opts['extra_fields'] as $field): ?>
                                            <span style="background: linear-gradient(135deg, #9c27b0, #7b1fa2); color: white; padding: 5px 14px; border-radius: 20px; font-size: 0.85rem; font-weight: 600;">
                                                <?= ($field['type'] ?? '') === 'text' ? '✏️' : '📋' ?> <?= htmlspecialchars($field['label'] ?? '') ?>: <?= htmlspecialchars($field['value'] ?? '') ?>
                                            </span>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        
                                        <?php // نظام بطاقات الرسائل المتعددة ?>
                                        <?php if (!empty($opts['gift_cards']) && is_array($opts['gift_cards'])): ?>
                                            <?php foreach ($opts['gift_cards'] as $card): ?>
                                            <div style="width: 100%; margin-top: 10px; padding: 12px 15px; background: linear-gradient(135deg, rgba(233, 30, 140, 0.08), rgba(156, 39, 176, 0.08)); border-radius: 10px; border-right: 4px solid #e91e8c;">
                                                <div style="font-weight: 600; color: #c2185b; font-size: 0.85rem; margin-bottom: 5px;">🎁 <?= htmlspecialchars($card['label'] ?? 'بطاقة إهداء') ?>:</div>
                                                <div style="color: #333; font-size: 0.9rem; line-height: 1.6; direction: rtl; white-space: pre-wrap; border-top: 1px dashed rgba(233, 30, 140, 0.2); padding-top: 5px; margin-top: 5px;"><?= nl2br(htmlspecialchars($card['message'] ?? '')) ?></div>
                                            </div>
                                            <?php endforeach; ?>
                                        <?php // التوافق مع النظام القديم ?>
                                        <?php elseif (isset($opts['gift_card_enabled'])): ?>
                                        <div style="width: 100%; margin-top: 10px; padding: 12px 15px; background: linear-gradient(135deg, rgba(233, 30, 140, 0.08), rgba(156, 39, 176, 0.08)); border-radius: 10px; border-right: 4px solid #e91e8c;">
                                            <div style="font-weight: 600; color: #c2185b; font-size: 0.85rem; margin-bottom: 5px;">🎁 بطاقة رسالة: <?= $opts['gift_card_enabled'] ? 'نعم' : 'لا' ?></div>
                                            <?php if ($opts['gift_card_enabled'] && !empty($opts['gift_card_message'])): ?>
                                                <div style="font-weight: 600; color: #c2185b; font-size: 0.85rem; margin-bottom: 5px; border-top: 1px dashed rgba(233, 30, 140, 0.2); padding-top: 5px; margin-top: 5px;">📝 نص الرسالة:</div>
                                                <div style="color: #333; font-size: 0.9rem; line-height: 1.6; direction: rtl; white-space: pre-wrap;"><?= nl2br(htmlspecialchars($opts['gift_card_message'])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <?php elseif (!empty($opts['gift_card_message'])): ?>
                                        <div style="width: 100%; margin-top: 10px; padding: 12px 15px; background: linear-gradient(135deg, rgba(233, 30, 140, 0.08), rgba(156, 39, 176, 0.08)); border-radius: 10px; border-right: 4px solid #e91e8c;">
                                            <div style="font-weight: 600; color: #c2185b; font-size: 0.85rem; margin-bottom: 5px;">💌 رسالة البطاقة:</div>
                                            <div style="color: #333; font-size: 0.9rem; line-height: 1.6; direction: rtl; white-space: pre-wrap;"><?= nl2br(htmlspecialchars($opts['gift_card_message'])) ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php 
                                // عرض صور التخصيص الخاصة بهذا المنتج
                                if (!empty($item['custom_images_decoded']) && is_array($item['custom_images_decoded'])): 
                                ?>
                                <div style="margin-top: 12px; padding: 12px 15px; background: linear-gradient(135deg, rgba(33, 150, 243, 0.08), rgba(3, 169, 244, 0.08)); border-radius: 10px; border-right: 3px solid #2196f3;">
                                    <div style="font-weight: 600; color: #1565c0; font-size: 0.85rem; margin-bottom: 10px;">
                                        📷 صور التخصيص لهذا المنتج (<?= count($item['custom_images_decoded']) ?> صورة):
                                    </div>
                                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                                        <?php foreach ($item['custom_images_decoded'] as $imgIndex => $customImg): ?>
                                        <?php $imgFullPath = "../images/uploads/" . $customImg; ?>
                                        <div style="width: 80px; height: 80px; border-radius: 8px; overflow: hidden; position: relative; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
                                            <img src="<?= $imgFullPath ?>" alt="صورة تخصيص <?= $imgIndex + 1 ?>" 
                                                 style="width: 100%; height: 100%; object-fit: cover; cursor: pointer;"
                                                 onclick="openImageLightbox('<?= $imgFullPath ?>', '<?= $customImg ?>')">
                                            <a href="<?= $imgFullPath ?>" download="custom_<?= $viewOrder['order_number'] ?>_<?= $imgIndex + 1 ?>.<?= pathinfo($customImg, PATHINFO_EXTENSION) ?>"
                                               style="position: absolute; bottom: 3px; right: 3px; background: rgba(0,0,0,0.7); color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; text-decoration: none;"
                                               title="تحميل">⬇</a>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Order Totals -->
                    <div style="margin-top: 25px; background: linear-gradient(135deg, #f8f9fa, #e9ecef); border-radius: 12px; padding: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: #666;">
                            <span>📦 المنتجات:</span>
                            <span style="font-weight: 600;"><?= formatPrice($viewOrder['subtotal']) ?></span>
                        </div>
                        <?php if (isset($viewOrder['packaging_total']) && $viewOrder['packaging_total'] > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: #9c27b0;">
                            <span>🎁 التغليف:</span>
                            <span style="font-weight: 600;"><?= formatPrice($viewOrder['packaging_total']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: #666;">
                            <span>🚚 التوصيل:</span>
                            <span style="font-weight: 600;"><?= formatPrice($viewOrder['delivery_fee']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; padding-top: 15px; border-top: 2px dashed #ccc;">
                            <span style="font-size: 1.2rem; font-weight: 700;">الإجمالي الكلي:</span>
                            <span style="font-size: 1.3rem; font-weight: 800; color: var(--primary);"><?= formatPrice($viewOrder['total']) ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($viewOrder['notes'])): ?>
                    <div style="margin-top: 25px; padding: 15px; background: var(--bg-main); border-radius: var(--radius-md);">
                        <h4 style="margin-bottom: 10px;">📝 ملاحظات الزبون</h4>
                        <p><?= nl2br(htmlspecialchars($viewOrder['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($viewOrder['uploaded_images'])): ?>
                    <div class="customer-images-section">
                        <div class="customer-images-header">
                            <h4>📷 صور الزبون للطباعة</h4>
                            <span class="badge"><?= count($viewOrder['uploaded_images']) ?> صورة</span>
                        </div>
                        <p style="color: #666; font-size: 0.9rem; margin-bottom: 15px;">
                            انقر على الصورة لعرضها بحجم كامل مع إمكانية التكبير والتحميل
                        </p>
                        <div class="customer-images">
                            <?php foreach ($viewOrder['uploaded_images'] as $index => $img): ?>
                            <?php 
                                $imgPath = "../images/uploads/" . $img;
                                $imgUrl = "images/uploads/" . $img;
                            ?>
                            <div class="customer-image-card">
                                <img src="<?= $imgPath ?>" 
                                     alt="صورة الزبون <?= $index + 1 ?>" 
                                     onclick="openImageLightbox('<?= $imgPath ?>', '<?= $img ?>')"
                                     loading="lazy">
                                <div class="customer-image-actions">
                                    <a href="javascript:void(0)" 
                                       onclick="openImageLightbox('<?= $imgPath ?>', '<?= $img ?>')" 
                                       class="btn-view">👁️ عرض</a>
                                    <a href="<?= $imgPath ?>" 
                                       download="order_<?= $viewOrder['order_number'] ?>_image_<?= $index + 1 ?>.<?= pathinfo($img, PATHINFO_EXTENSION) ?>" 
                                       class="btn-download">⬇️ تحميل</a>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Download All Button -->
                        <?php if (count($viewOrder['uploaded_images']) > 1): ?>
                        <div style="margin-top: 20px; text-align: center;">
                            <p style="color: #888; font-size: 0.85rem;">💡 نصيحة: يمكنك النقر على كل صورة لعرضها بالحجم الكامل</p>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Image Lightbox Modal -->
                    <div id="imageLightbox" class="image-lightbox">
                        <button class="lightbox-close" onclick="closeImageLightbox()">✕</button>
                        <div class="lightbox-image-container">
                            <img id="lightboxImage" src="" alt="صورة كاملة">
                        </div>
                        <div class="lightbox-controls">
                            <button class="lightbox-zoom" onclick="toggleZoom()">🔍 تبديل التكبير</button>
                            <a id="lightboxDownload" href="" download="" class="lightbox-download">⬇️ تحميل الصورة</a>
                            <button class="lightbox-zoom" onclick="openInNewTab()">🔗 فتح في تبويب جديد</button>
                        </div>
                        <p class="lightbox-info" id="lightboxInfo"></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Update Status -->
                    <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee;">
                        <h4 style="margin-bottom: 15px;">تحديث حالة الطلب</h4>
                        <form method="POST" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                            <input type="hidden" name="order_id" value="<?= $viewOrder['id'] ?>">
                            <input type="hidden" name="update_status" value="1">
                            <select name="new_status" class="form-control" style="width: auto;">
                                <option value="pending" <?= $viewOrder['status'] === 'pending' ? 'selected' : '' ?>>⏳ قيد الانتظار</option>
                                <option value="confirmed" <?= $viewOrder['status'] === 'confirmed' ? 'selected' : '' ?>>✓ تم التأكيد</option>
                                <option value="processing" <?= $viewOrder['status'] === 'processing' ? 'selected' : '' ?>>🔧 قيد التجهيز</option>
                                <option value="shipped" <?= $viewOrder['status'] === 'shipped' ? 'selected' : '' ?>>🚚 تم الشحن</option>
                                <option value="delivered" <?= $viewOrder['status'] === 'delivered' ? 'selected' : '' ?>>✅ تم التوصيل</option>
                                <option value="cancelled" <?= $viewOrder['status'] === 'cancelled' ? 'selected' : '' ?>>✗ ملغي</option>
                            </select>
                            <button type="submit" class="btn btn-primary">تحديث</button>
                        </form>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- Orders List -->
            <div class="admin-header">
                <h1 class="admin-title">📋 إدارة الطلبات</h1>
                <span style="padding: 10px 15px; background: rgba(233, 30, 140, 0.1); border-radius: var(--radius-full); font-weight: 600;">
                    <?= $stats['total'] ?> طلب
                </span>
            </div>

            <!-- Quick Stats -->
            <div class="stats-mini">
                <a href="orders" data-status="all" class="stats-mini-item status-all">📊 الكل (<?= $stats['total'] ?>)</a>
                <a href="orders?status=pending" data-status="pending" class="stats-mini-item status-pending">⏳ جديد (<?= $stats['pending'] ?>)</a>
                <a href="orders?status=confirmed" data-status="confirmed" class="stats-mini-item status-confirmed">✓ مؤكد (<?= $stats['confirmed'] ?>)</a>
                <a href="orders?status=processing" data-status="processing" class="stats-mini-item status-processing">🔧 تجهيز (<?= $stats['processing'] ?>)</a>
                <a href="orders?status=shipped" data-status="shipped" class="stats-mini-item status-shipped">🚚 شحن (<?= $stats['shipped'] ?>)</a>
                <a href="orders?status=delivered" data-status="delivered" class="stats-mini-item status-delivered">✅ مكتمل (<?= $stats['delivered'] ?>)</a>
                <?php if ($stats['cancelled'] > 0): ?>
                <a href="orders?status=cancelled" data-status="cancelled" class="stats-mini-item status-cancelled">✗ ملغي (<?= $stats['cancelled'] ?>)</a>
                <?php endif; ?>
            </div>

            <!-- Mobile: Toggle Filters Button -->
            <button class="filters-toggle-btn" id="filtersToggle" onclick="toggleFilters()">
                🔍 الفلاتر والبحث
            </button>

            <!-- Search & Filters -->
            <div class="search-filters collapsed" id="searchFilters">
                <div class="search-filters-grid">
                    <div class="filter-group">
                        <label class="form-label">🔍 بحث فوري</label>
                        <input type="text" id="liveSearch" class="form-control" placeholder="رقم الطلب، اسم الزبون، رقم الهاتف..." value="<?= htmlspecialchars($searchQuery) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="form-label">📋 الحالة</label>
                        <select id="filterStatus" class="premium-select">
                            <option value="">📊 الكل</option>
                            <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>⏳ قيد الانتظار</option>
                            <option value="confirmed" <?= $statusFilter === 'confirmed' ? 'selected' : '' ?>>✓ مؤكد</option>
                            <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>🔧 تجهيز</option>
                            <option value="shipped" <?= $statusFilter === 'shipped' ? 'selected' : '' ?>>🚚 شحن</option>
                            <option value="delivered" <?= $statusFilter === 'delivered' ? 'selected' : '' ?>>✅ مكتمل</option>
                            <option value="cancelled" <?= $statusFilter === 'cancelled' ? 'selected' : '' ?>>✗ ملغي</option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="form-label">📅 السنة</label>
                        <select id="filterYear" class="premium-select">
                            <?php foreach ($availableYears as $y): ?>
                            <option value="<?= $y ?>" <?= $y == $yearFilter ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label class="form-label">📆 الشهر</label>
                        <select id="filterMonth" class="premium-select">
                            <option value="0" <?= $monthFilter == 0 ? 'selected' : '' ?>>📊 كل السنة</option>
                            <?php foreach ($arabicMonths as $i => $name): ?>
                            <option value="<?= $i + 1 ?>" <?= ($i + 1) == $monthFilter ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="justify-content: flex-end;">
                        <button type="button" id="clearFilters" class="clear-filters-btn">🔄 مسح الفلاتر</button>
                    </div>
                </div>
                <div id="searchResults" style="margin-top: 15px; font-size: 0.9rem; color: var(--text-muted);"></div>
            </div>

            <?php if ($searchQuery || $monthFilter > 0): ?>
            <div style="margin-bottom: 15px; color: var(--text-muted); padding: 10px 15px; background: rgba(233, 30, 140, 0.05); border-radius: var(--radius-md);">
                📊 عرض <?= count($orders) ?> من <?= $totalOrders ?> نتيجة
                <?php if ($searchQuery): ?> | 🔍 البحث: "<?= htmlspecialchars($searchQuery) ?>"<?php endif; ?>
                <?php if ($monthFilter > 0): ?> | 📆 <?= $arabicMonths[$monthFilter - 1] ?> <?= $yearFilter ?><?php endif; ?>
            </div>
            <?php endif; ?>

            <div class="admin-card">
                <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📋</div>
                    <h2 class="empty-title">لا توجد طلبات</h2>
                    <p class="empty-text"><?= $searchQuery ? 'لم يتم العثور على نتائج لبحثك' : 'ستظهر الطلبات الجديدة هنا' ?></p>
                    <?php if ($searchQuery || $statusFilter): ?>
                    <a href="orders" class="btn btn-primary">عرض كل الطلبات</a>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <table class="admin-table orders-table">
                    <thead>
                        <tr><th>رقم الطلب</th><th>الزبون</th><th>المنتجات</th><th>الإجمالي</th><th>الحالة</th><th>التاريخ</th><th>إجراءات</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <?php 
                        $st = getOrderStatusLabel($order['status']); 
                        $orderItems = getOrderItems($order['id']);
                        ?>
                        <tr>
                            <td><strong>#<?= $order['order_number'] ?></strong></td>
                            <td>
                                <?= $order['customer_name'] ?: '(غير محدد)' ?>
                                <?php if ($order['customer_phone']): ?><br><small style="color: var(--text-muted);"><?= $order['customer_phone'] ?></small><?php endif; ?>
                                <?php if (!empty($order['contact_method'])): ?>
                                <br><span style="font-size: 0.75rem; background: <?= $order['contact_method'] === 'instagram' ? '#E1306C' : ($order['contact_method'] === 'whatsapp' ? '#25D366' : '#0088cc') ?>; color: white; padding: 2px 6px; border-radius: 10px; display: inline-flex; align-items: center;" title="<?= htmlspecialchars($order['contact_value'] ?? '') ?>">
                                    <img src="../images/icons/<?= $order['contact_method'] === 'instagram' ? 'icon1.png' : ($order['contact_method'] === 'whatsapp' ? 'whatsapp.png' : 'icon2.png') ?>" alt="" style="width: 12px; height: 12px;">
                                </span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 200px;">
                                <?php if (!empty($orderItems)): ?>
                                    <?php foreach ($orderItems as $oItem): ?>
                                    <div style="margin-bottom: 5px; padding: 4px 8px; background: #f8f9fa; border-radius: 6px; font-size: 0.85rem;">
                                        <strong><?= htmlspecialchars($oItem['product_name']) ?></strong> × <?= $oItem['quantity'] ?>
                                        <?php if (!empty($oItem['selected_options']['packaging_selected'])): ?>
                                        <span style="background: #F3E5F5; color: #7B1FA2; padding: 1px 6px; border-radius: 10px; font-size: 0.7rem; margin-right: 5px;">🎁</span>
                                        <?php endif; ?>
                                        <?php if ($oItem['has_custom_image']): ?>
                                        <span style="background: #E3F2FD; color: #1565C0; padding: 1px 6px; border-radius: 10px; font-size: 0.7rem;">📷</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td><strong style="color: var(--primary);"><?= formatPrice($order['total']) ?></strong></td>
                            <td><span class="status <?= $st['class'] ?>"><?= $st['icon'] ?> <?= $st['label'] ?></span></td>
                            <td style="font-size: 0.85rem;"><?= formatDateTime($order['created_at'], 'short') ?></td>
                            <td>
                                <div class="action-btns">
                                    <a href="orders?view=<?= $order['id'] ?>" class="action-btn action-btn-view" title="عرض">👁️</a>
                                    <button type="button" class="action-btn action-btn-delete" 
                                       title="حذف"
                                       data-delete-type="order"
                                       data-delete-id="<?= $order['id'] ?>"
                                       data-delete-name="#<?= $order['order_number'] ?>"
                                       data-delete-token="<?= $csrf_token ?>">🗑️</button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Mobile Order Cards -->
                <?php foreach ($orders as $order): ?>
                <?php 
                $st = getOrderStatusLabel($order['status']); 
                // إعادة استخدام orderItems من الجدول أو جلبها
                if (!isset($orderItems) || empty($orderItems)) {
                    $orderItems = getOrderItems($order['id']);
                }
                ?>
                <div class="mobile-order-card">
                    <div class="mobile-order-header">
                        <span class="mobile-order-number">#<?= $order['order_number'] ?></span>
                        <span class="status <?= $st['class'] ?>"><?= $st['icon'] ?> <?= $st['label'] ?></span>
                    </div>
                    <div class="mobile-order-body">
                        <div class="mobile-order-item" style="grid-column: span 2;">
                            <span class="label">🛒 المنتجات</span>
                            <div class="value" style="margin-top: 5px;">
                                <?php if (!empty($orderItems)): ?>
                                    <?php foreach ($orderItems as $oItem): ?>
                                    <div style="margin-bottom: 5px; padding: 6px 10px; background: #f8f9fa; border-radius: 8px; font-size: 0.85rem;">
                                        <strong><?= htmlspecialchars($oItem['product_name']) ?></strong> × <?= $oItem['quantity'] ?>
                                        <?php if (!empty($oItem['selected_options']['packaging_selected'])): ?>
                                        <span style="background: #F3E5F5; color: #7B1FA2; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem; margin-right: 5px;">🎁 تغليف</span>
                                        <?php endif; ?>
                                        <?php if ($oItem['has_custom_image']): ?>
                                        <span style="background: #E3F2FD; color: #1565C0; padding: 2px 8px; border-radius: 10px; font-size: 0.7rem;">📷 صورة</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mobile-order-item">
                            <span class="label">👤 الزبون</span>
                            <span class="value"><?= $order['customer_name'] ?: '(غير محدد)' ?></span>
                        </div>
                        <div class="mobile-order-item">
                            <span class="label">💰 الإجمالي</span>
                            <span class="value" style="color: var(--primary);"><?= formatPrice($order['total']) ?></span>
                        </div>
                        <div class="mobile-order-item">
                            <span class="label">📱 الهاتف</span>
                            <span class="value"><?= $order['customer_phone'] ?: '-' ?></span>
                        </div>
                        <div class="mobile-order-item">
                            <span class="label">📅 التاريخ</span>
                            <span class="value"><?= formatDateTime($order['created_at'], 'datetime') ?></span>
                        </div>
                        <?php if (!empty($order['contact_method'])): ?>
                        <div class="mobile-order-item">
                            <span class="label">📱 التواصل</span>
                            <span class="value" style="direction: ltr; display: flex; align-items: center; gap: 5px;">
                                <img src="../images/icons/<?= $order['contact_method'] === 'instagram' ? 'icon1.png' : ($order['contact_method'] === 'whatsapp' ? 'whatsapp.png' : 'icon2.png') ?>" alt="" style="width: 16px; height: 16px;">
                                <?= htmlspecialchars($order['contact_value'] ?? '') ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mobile-order-footer">
                        <a href="orders?view=<?= $order['id'] ?>" class="btn btn-primary">👁️ عرض التفاصيل</a>
                        <button type="button" class="btn-delete-full"
                           data-delete-type="order"
                           data-delete-id="<?= $order['id'] ?>"
                           data-delete-name="#<?= $order['order_number'] ?>"
                           data-delete-token="<?= $csrf_token ?>">🗑️ حذف</button>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php 
                    $baseUrl = 'orders.php?' . http_build_query(array_filter([
                        'status' => $statusFilter, 
                        'search' => $searchQuery,
                        'year' => $yearFilter, 
                        'month' => $monthFilter, 
                        'sort' => $sortBy
                    ]));
                    ?>
                    <?php if ($currentPage > 1): ?><a href="<?= $baseUrl ?>&page=<?= $currentPage - 1 ?>">« السابق</a><?php endif; ?>
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                    <?php if ($i == $currentPage): ?><span class="active"><?= $i ?></span><?php else: ?><a href="<?= $baseUrl ?>&page=<?= $i ?>"><?= $i ?></a><?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($currentPage < $totalPages): ?><a href="<?= $baseUrl ?>&page=<?= $currentPage + 1 ?>">التالي »</a><?php endif; ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    
    <script>
    // Orders page scripts are now handled by js/orders-filters.js
    
    // ========== IMAGE LIGHTBOX FUNCTIONS ==========
    let currentImageSrc = '';
    let isZoomed = false;
    
    function openImageLightbox(src, filename) {
        const lightbox = document.getElementById('imageLightbox');
        const lightboxImage = document.getElementById('lightboxImage');
        const lightboxDownload = document.getElementById('lightboxDownload');
        const lightboxInfo = document.getElementById('lightboxInfo');
        
        currentImageSrc = src;
        lightboxImage.src = src;
        lightboxDownload.href = src;
        lightboxDownload.download = filename;
        lightboxInfo.textContent = `📁 ${filename}`;
        
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Reset zoom
        isZoomed = false;
        lightboxImage.style.maxWidth = '100%';
        lightboxImage.style.maxHeight = '70vh';
        lightboxImage.style.cursor = 'zoom-in';
    }
    
    function closeImageLightbox() {
        const lightbox = document.getElementById('imageLightbox');
        lightbox.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    function toggleZoom() {
        const lightboxImage = document.getElementById('lightboxImage');
        const container = document.querySelector('.lightbox-image-container');
        
        if (isZoomed) {
            lightboxImage.style.maxWidth = '100%';
            lightboxImage.style.maxHeight = '70vh';
            lightboxImage.style.cursor = 'zoom-in';
            container.style.overflow = 'auto';
        } else {
            lightboxImage.style.maxWidth = 'none';
            lightboxImage.style.maxHeight = 'none';
            lightboxImage.style.cursor = 'zoom-out';
            container.style.overflow = 'scroll';
        }
        isZoomed = !isZoomed;
    }
    
    function openInNewTab() {
        window.open(currentImageSrc, '_blank');
    }
    
    // Close lightbox on ESC key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeImageLightbox();
        }
    });
    
    // Close lightbox when clicking outside image
    document.getElementById('imageLightbox')?.addEventListener('click', (e) => {
        if (e.target.id === 'imageLightbox') {
            closeImageLightbox();
        }
    });
    
    // ========== MOBILE FILTERS TOGGLE ==========
    function toggleFilters() {
        const filters = document.getElementById('searchFilters');
        const btn = document.getElementById('filtersToggle');
        
        if (filters && btn) {
            filters.classList.toggle('collapsed');
            btn.classList.toggle('active');
            btn.innerHTML = filters.classList.contains('collapsed') 
                ? '🔍 الفلاتر والبحث' 
                : '✕ إخفاء الفلاتر';
        }
    }
    
    // Check if on mobile and show filters on desktop
    function checkScreenSize() {
        const filters = document.getElementById('searchFilters');
        if (filters && window.innerWidth > 768) {
            filters.classList.remove('collapsed');
        }
    }
    
    // Run on load and resize
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
    </script>
    <script src="<?= av('js/api-helper.js') ?>"></script>
    <script src="<?= av('js/orders-filters.js') ?>"></script>
    <script src="<?= av('js/admin.js') ?>"></script>
</body>
</html>
