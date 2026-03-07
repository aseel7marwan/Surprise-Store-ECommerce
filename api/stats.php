<?php
/**
 * Sales Statistics API
 * Returns sales data by month, year, product, etc.
 * Uses unified functions from functions.php for consistent data
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Check admin auth
initSecureSession();
if (!validateAdminSession()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'غير مصرح']);
    exit;
}

// Rate limiting
if (isApiRateLimited('stats')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'طلبات كثيرة']);
    exit;
}
recordApiRequest('stats');

$action = isset($_GET['action']) ? $_GET['action'] : 'summary';
$year = intval(isset($_GET['year']) ? $_GET['year'] : date('Y'));
$month = intval(isset($_GET['month']) ? $_GET['month'] : 0);

try {
    switch ($action) {
        case 'summary':
            // Use unified getSalesSummary from functions.php
            $data = getSalesSummary($year, $month);
            echo json_encode(['success' => true, 'data' => $data]);
            break;
            
        case 'monthly':
            echo json_encode(getMonthlyStats($year));
            break;
            
        case 'products':
            echo json_encode(getTopProductsStats($year, $month, intval(isset($_GET['limit']) ? $_GET['limit'] : 10)));
            break;
            
        case 'recent':
            echo json_encode(getRecentOrdersStats(intval(isset($_GET['limit']) ? $_GET['limit'] : 10)));
            break;
            
        case 'daily':
            echo json_encode(getDailyStats($year, $month));
            break;
            
        case 'categories':
            echo json_encode(getCategoryStats($year, $month));
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'إجراء غير معروف']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

/**
 * Get monthly sales for a year
 * Uses confirmed statuses from getConfirmedStatuses()
 */
function getMonthlyStats($year) {
    $pdo = db();
    $statuses = getConfirmedStatuses();
    $statusPlaceholders = "'" . implode("','", $statuses) . "'";
    
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(created_at) as month,
            COUNT(*) as orders,
            COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE YEAR(created_at) = :year 
        AND status IN ($statusPlaceholders)
        GROUP BY MONTH(created_at)
        ORDER BY month
    ");
    $stmt->execute(['year' => $year]);
    $data = $stmt->fetchAll();
    
    // Fill in missing months
    $months = [];
    $arabicMonths = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 
                     'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
    
    for ($i = 1; $i <= 12; $i++) {
        $month_i = $i;
        $found = array_filter($data, function($d) use ($month_i) {
            return $d['month'] == $month_i;
        });
        $found = reset($found);
        
        $months[] = [
            'month' => $i,
            'name' => $arabicMonths[$i - 1],
            'orders' => $found ? intval($found['orders']) : 0,
            'revenue' => $found ? floatval($found['revenue']) : 0
        ];
    }
    
    return ['success' => true, 'data' => $months, 'year' => $year];
}

/**
 * Get top selling products
 * Uses confirmed statuses from getConfirmedStatuses()
 */
function getTopProductsStats($year, $month = 0, $limit = 10) {
    $pdo = db();
    $statuses = getConfirmedStatuses();
    $statusPlaceholders = "'" . implode("','", $statuses) . "'";
    
    $dateCondition = "YEAR(o.created_at) = :year";
    $params = ['year' => $year];
    
    if ($month > 0) {
        $dateCondition .= " AND MONTH(o.created_at) = :month";
        $params['month'] = $month;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            oi.product_id,
            oi.product_name,
            SUM(oi.quantity) as total_sold,
            SUM(oi.price * oi.quantity) as total_revenue,
            COUNT(DISTINCT o.id) as order_count
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE $dateCondition AND o.status IN ($statusPlaceholders)
        GROUP BY oi.product_id, oi.product_name
        ORDER BY total_sold DESC
        LIMIT $limit
    ");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    
    // Add product images
    foreach ($products as &$product) {
        $p = getProduct($product['product_id']);
        $product['image'] = isset($p['images'][0]) ? $p['images'][0] : 'products/default.png';
        $product['total_sold'] = intval($product['total_sold']);
        $product['total_revenue'] = floatval($product['total_revenue']);
        $product['order_count'] = intval($product['order_count']);
    }
    
    return ['success' => true, 'data' => $products];
}

/**
 * Get recent orders
 */
function getRecentOrdersStats($limit = 10) {
    $pdo = db();
    
    $stmt = $pdo->prepare("
        SELECT id, order_number, customer_name, customer_phone, total, status, created_at
        FROM orders
        ORDER BY created_at DESC
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    foreach ($orders as &$order) {
        $st = getOrderStatusLabel($order['status']);
        $order['status_label'] = $st['label'];
        $order['status_class'] = $st['class'];
        $order['status_icon'] = $st['icon'];
        $order['total'] = floatval($order['total']);
        // Format date for display
        $order['created_at_formatted'] = formatDateTime($order['created_at'], 'short');
    }
    
    return ['success' => true, 'data' => $orders];
}

/**
 * Get daily sales for a month
 * Uses confirmed statuses from getConfirmedStatuses()
 */
function getDailyStats($year, $month) {
    if ($month < 1) $month = intval(date('m'));
    
    $pdo = db();
    $statuses = getConfirmedStatuses();
    $statusPlaceholders = "'" . implode("','", $statuses) . "'";
    
    $stmt = $pdo->prepare("
        SELECT 
            DAY(created_at) as day,
            COUNT(*) as orders,
            COALESCE(SUM(total), 0) as revenue
        FROM orders 
        WHERE YEAR(created_at) = :year AND MONTH(created_at) = :month
        AND status IN ($statusPlaceholders)
        GROUP BY DAY(created_at)
        ORDER BY day
    ");
    $stmt->execute(['year' => $year, 'month' => $month]);
    $data = $stmt->fetchAll();
    
    // Fill in missing days
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $days = [];
    
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $day_i = $i;
        $found = array_filter($data, function($d) use ($day_i) {
            return $d['day'] == $day_i;
        });
        $found = reset($found);
        
        $days[] = [
            'day' => $i,
            'orders' => $found ? intval($found['orders']) : 0,
            'revenue' => $found ? floatval($found['revenue']) : 0
        ];
    }
    
    return ['success' => true, 'data' => $days, 'year' => $year, 'month' => $month];
}

/**
 * Get sales by category
 * Uses confirmed statuses from getConfirmedStatuses() and getSalesByCategory()
 */
function getCategoryStats($year, $month = 0) {
    $pdo = db();
    $categories = getCategories();
    $statuses = getConfirmedStatuses();
    $statusPlaceholders = "'" . implode("','", $statuses) . "'";
    
    $dateCondition = "YEAR(o.created_at) = :year";
    $params = ['year' => $year];
    
    if ($month > 0) {
        $dateCondition .= " AND MONTH(o.created_at) = :month";
        $params['month'] = $month;
    }
    
    // Get products and map to categories
    $products = getProducts();
    $productCategories = [];
    foreach ($products as $p) {
        $productCategories[$p['id']] = $p['category'];
        // Also map by product_id string
        if (!empty($p['product_id'])) {
            $productCategories[$p['product_id']] = $p['category'];
        }
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            oi.product_id,
            SUM(oi.quantity) as qty,
            SUM(oi.price * oi.quantity) as revenue
        FROM order_items oi
        JOIN orders o ON oi.order_id = o.id
        WHERE $dateCondition AND o.status IN ($statusPlaceholders)
        GROUP BY oi.product_id
    ");
    $stmt->execute($params);
    $sales = $stmt->fetchAll();
    
    // Aggregate by category
    $categoryData = [];
    foreach ($categories as $key => $cat) {
        $categoryData[$key] = [
            'key' => $key,
            'name' => $cat['name'],
            'icon' => $cat['icon'],
            'quantity' => 0,
            'revenue' => 0
        ];
    }
    
    foreach ($sales as $sale) {
        $catKey = isset($productCategories[$sale['product_id']]) ? $productCategories[$sale['product_id']] : 'other';
        if (isset($categoryData[$catKey])) {
            $categoryData[$catKey]['quantity'] += intval($sale['qty']);
            $categoryData[$catKey]['revenue'] += floatval($sale['revenue']);
        }
    }
    
    return ['success' => true, 'data' => array_values($categoryData)];
}
