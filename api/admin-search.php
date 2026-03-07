<?php
/**
 * Admin Search API - Live Search & Filtering
 * Surprise! Store v2.5.4 - FINAL FIX
 * 
 * Enhanced with security measures and proper filtering
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Initialize secure session
initSecureSession();

// Check admin auth with secure validation
if (!validateAdminSession()) {
    http_response_code(401);
    logSecurityEvent('UNAUTHORIZED_API', 'Unauthorized admin-search API access attempt');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// API Rate limiting
if (isApiRateLimited('admin_search')) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many requests']);
    exit;
}
recordApiRequest('admin_search');

// Sanitize inputs
$type = sanitize(isset($_GET['type']) ? $_GET['type'] : '');
$search = sanitize(isset($_GET['search']) ? $_GET['search'] : '');
$status = sanitize(isset($_GET['status']) ? $_GET['status'] : '');
$category = sanitize(isset($_GET['category']) ? $_GET['category'] : '');
$year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$month = isset($_GET['month']) ? intval($_GET['month']) : 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;

// Validate year range (prevent unrealistic years)
if ($year > 0 && ($year < 2020 || $year > 2050)) {
    $year = intval(date('Y'));
}

// Validate month range
if ($month < 0 || $month > 12) {
    $month = 0;
}

// ============================================
// PRODUCTS SEARCH
// ============================================
if ($type === 'products') {
    $filters = ['exclude_hidden' => false];
    $categories = getCategories();
    
    if ($search) {
        $filters['search'] = $search;
    }
    if ($status) {
        $filters['status'] = $status;
    }
    if ($category) {
        $filters['category'] = $category;
    }
    
    $products = getProducts($filters);
    
    // Filter by search if not supported in getProducts
    if ($search && !empty($products)) {
        $searchLower = mb_strtolower($search);
        $products = array_filter($products, function($p) use ($searchLower) {
            return mb_strpos(mb_strtolower($p['name']), $searchLower) !== false ||
                   mb_strpos(mb_strtolower(isset($p['description']) ? $p['description'] : ''), $searchLower) !== false;
        });
    }
    
    // Add Arabic category names
    foreach ($products as &$product) {
        $catKey = isset($product['category']) ? $product['category'] : '';
        if (isset($categories[$catKey])) {
            $product['category_name'] = $categories[$catKey]['icon'] . ' ' . $categories[$catKey]['name'];
        } else {
            $product['category_name'] = $catKey;
        }
    }
    
    echo json_encode([
        'success' => true,
        'count' => count($products),
        'products' => array_values($products)
    ]);

// ============================================
// ORDERS SEARCH
// ============================================
} elseif ($type === 'orders') {
    $filters = [];
    
    // Search filter
    if ($search) {
        $filters['search'] = $search;
    }
    
    // Status filter - IMPORTANT: empty string or "all" means no status filter
    if ($status && $status !== 'all' && $status !== '') {
        $filters['status'] = $status;
    }
    // If status is empty or "all", we don't add status filter - fetch all orders
    
    // Year filter
    if ($year > 0) {
        $filters['year'] = $year;
    }
    
    // Month filter
    if ($month > 0) {
        $filters['month'] = $month;
    }
    
    // Get all orders matching filters (for count)
    $allOrders = getOrders($filters);
    $totalOrders = count($allOrders);
    $totalPages = ceil($totalOrders / $perPage);
    
    // Apply pagination
    $offset = ($page - 1) * $perPage;
    $filters['limit'] = $perPage;
    $filters['offset'] = $offset;
    
    $orders = getOrders($filters);
    
    // Add status labels and formatted date
    foreach ($orders as &$order) {
        $st = getOrderStatusLabel($order['status']);
        $order['status_label'] = $st['label'];
        $order['status_class'] = $st['class'];
        $order['status_icon'] = $st['icon'];
        // Format date for consistent Arabic display
        $order['created_at_formatted'] = formatDateTime($order['created_at'], 'short');
    }
    
    echo json_encode([
        'success' => true,
        'count' => $totalOrders,
        'orders' => array_values($orders),
        'pagination' => [
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'totalItems' => $totalOrders
        ]
    ]);

// ============================================
// INVALID TYPE
// ============================================
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid type']);
}
