<?php
/**
 * Surprise! Store - MySQL Helper Functions
 */

// ============ PRODUCT FUNCTIONS ============

function getProducts($filters = []) {
    // Check database connection first
    if (!isDbConnected()) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM products WHERE 1=1";
        $params = [];
        
        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['featured'])) {
            $sql .= " AND featured = 1";
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }
        
        if (!empty($filters['exclude_hidden'])) {
            $sql .= " AND status != 'hidden'";
        }
        
        $sql .= " ORDER BY featured DESC, created_at DESC";
        
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // Decode JSON fields (images, options)
        foreach ($products as &$product) {
            $images = isset($product['images']) ? $product['images'] : '[]';
            $product['images'] = json_decode($images, true);
            if (!is_array($product['images'])) {
                $product['images'] = array();
            }
            
            $options = isset($product['options']) ? $product['options'] : '{}';
            $product['options'] = json_decode($options, true);
            if (!is_array($product['options'])) {
                $product['options'] = array();
            }
        }
        
        return $products;
    } catch (Exception $e) {
        return [];
    }
}

function getProduct($id) {
    if (!isDbConnected()) {
        return null;
    }
    
    try {
        $stmt = db()->prepare("SELECT * FROM products WHERE id = ? OR product_id = ?");
        $stmt->execute([$id, $id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Decode images JSON
            $images = isset($product['images']) ? $product['images'] : '[]';
            $product['images'] = json_decode($images, true);
            if (!is_array($product['images'])) {
                $product['images'] = array();
            }
            
            // Decode options JSON
            $options = isset($product['options']) ? $product['options'] : '{}';
            $product['options'] = json_decode($options, true);
            if (!is_array($product['options'])) {
                $product['options'] = array();
            }
        }
        
        return $product;
    } catch (Exception $e) {
        return null;
    }
}

function saveProduct($data) {
    $images = is_array($data['images']) ? json_encode($data['images']) : $data['images'];
    $oldPrice = isset($data['old_price']) ? intval($data['old_price']) : 0;
    $stock = isset($data['stock']) ? intval($data['stock']) : 10;
    
    // Handle product options (colors, sizes, ages, custom text)
    $options = [];
    if (!empty($data['options']) && is_array($data['options'])) {
        $options = $data['options'];
    }
    $optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);
    
    // Handle packaging fields
    $packagingEnabled = !empty($data['packaging_enabled']) ? 1 : 0;
    $packagingPrice = isset($data['packaging_price']) ? intval($data['packaging_price']) : 0;
    $packagingDescription = isset($data['packaging_description']) ? $data['packaging_description'] : '';
    
    if (!empty($data['id'])) {
        // Update
        $stmt = db()->prepare("
            UPDATE products SET 
                name = ?, description = ?, price = ?, old_price = ?, category = ?, 
                images = ?, customizable = ?, featured = ?, status = ?, stock = ?, options = ?,
                packaging_enabled = ?, packaging_price = ?, packaging_description = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['name'], $data['description'], $data['price'], $oldPrice, $data['category'],
            $images, $data['customizable'] ? 1 : 0, $data['featured'] ? 1 : 0, 
            $data['status'], $stock, $optionsJson,
            $packagingEnabled, $packagingPrice, $packagingDescription,
            $data['id']
        ]);
    } else {
        // Insert
        $productId = 'prod_' . uniqid();
        $stmt = db()->prepare("
            INSERT INTO products (product_id, name, description, price, old_price, category, images, customizable, featured, status, stock, options, packaging_enabled, packaging_price, packaging_description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute(array(
            $productId, $data['name'], $data['description'], $data['price'], $oldPrice,
            $data['category'], $images, $data['customizable'] ? 1 : 0, 
            $data['featured'] ? 1 : 0, isset($data['status']) ? $data['status'] : 'available', $stock, $optionsJson,
            $packagingEnabled, $packagingPrice, $packagingDescription
        ));
    }
}

function deleteProduct($id) {
    $stmt = db()->prepare("DELETE FROM products WHERE id = ? OR product_id = ?");
    return $stmt->execute([$id, $id]);
}

// ============ ORDER FUNCTIONS ============

function getOrders($filters = []) {
    $sql = "SELECT * FROM orders WHERE 1=1";
    $params = [];
    
    if (!empty($filters['status'])) {
        $sql .= " AND status = ?";
        $params[] = $filters['status'];
    }
    
    if (!empty($filters['search'])) {
        $sql .= " AND (order_number LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
        $search = '%' . $filters['search'] . '%';
        $params[] = $search;
        $params[] = $search;
        $params[] = $search;
    }
    
    // Year filter
    if (!empty($filters['year'])) {
        $sql .= " AND YEAR(created_at) = ?";
        $params[] = intval($filters['year']);
    }
    
    // Month filter
    if (!empty($filters['month']) && intval($filters['month']) > 0) {
        $sql .= " AND MONTH(created_at) = ?";
        $params[] = intval($filters['month']);
    }
    
    // Legacy date range filters (for backward compatibility)
    if (!empty($filters['date_from'])) {
        $sql .= " AND DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
    }
    
    if (!empty($filters['date_to'])) {
        $sql .= " AND DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
    }
    
    // Sorting
    $sortOptions = [
        'newest' => 'created_at DESC',
        'oldest' => 'created_at ASC',
        'total_high' => 'total DESC',
        'total_low' => 'total ASC'
    ];
    $sortKey = isset($filters['sort']) ? $filters['sort'] : 'newest';
    $sort = isset($sortOptions[$sortKey]) ? $sortOptions[$sortKey] : 'created_at DESC';
    $sql .= " ORDER BY $sort";
    
    // Pagination
    if (!empty($filters['limit'])) {
        $sql .= " LIMIT " . intval($filters['limit']);
        if (!empty($filters['offset'])) {
            $sql .= " OFFSET " . intval($filters['offset']);
        }
    }
    
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function countOrders($status = null) {
    if ($status) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE status = ?");
        $stmt->execute([$status]);
    } else {
        $stmt = db()->query("SELECT COUNT(*) FROM orders");
    }
    return $stmt->fetchColumn();
}

function getOrder($id) {
    $stmt = db()->prepare("SELECT * FROM orders WHERE id = ? OR order_number = ?");
    $stmt->execute([$id, $id]);
    $order = $stmt->fetch();
    
    if ($order) {
        // Get order items with product details (join by id or product_id)
        $stmt = db()->prepare("
            SELECT 
                oi.*,
                p.name as product_current_name,
                p.images as product_images,
                p.description as product_description,
                p.category as product_category,
                p.price as current_price,
                p.old_price as product_old_price,
                p.status as product_status,
                p.customizable as product_customizable,
                p.stock as product_stock
            FROM order_items oi
            LEFT JOIN products p ON (oi.product_id = p.id OR oi.product_id = p.product_id)
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $items = $stmt->fetchAll();
        
        // Process items to decode JSON images
        foreach ($items as &$item) {
            if (!empty($item['product_images'])) {
                $item['images'] = json_decode($item['product_images'], true);
            } else {
                $item['images'] = [];
            }
            // فك ترميز صور التخصيص المتعددة
            if (!empty($item['custom_images'])) {
                $item['custom_images_decoded'] = json_decode($item['custom_images'], true);
            } else {
                $item['custom_images_decoded'] = [];
            }
            // فك ترميز الخيارات المختارة (selected_options) من JSON
            if (!empty($item['selected_options'])) {
                $item['selected_options'] = json_decode($item['selected_options'], true);
            } else {
                $item['selected_options'] = [];
            }
            // استخدم اسم المنتج الحالي إذا كان موجوداً
            if (!empty($item['product_current_name'])) {
                $item['product_name'] = $item['product_current_name'];
            } elseif (empty($item['product_name'])) {
                $item['product_name'] = 'منتج محذوف';
            }
        }
        $order['items'] = $items;
        
        // Get uploaded images
        $stmt = db()->prepare("SELECT file_path FROM customer_uploads WHERE order_id = ?");
        $stmt->execute([$order['id']]);
        $order['uploaded_images'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    return $order;
}

function generateOrderNumber() {
    $prefix = 'SRP';
    $date = date('ymd');
    $random = strtoupper(substr(uniqid(), -4));
    return $prefix . $date . $random;
}

function saveOrder($data) {
    if (!isDbConnected()) {
        return false;
    }
    
    $pdo = db();
    $pdo->beginTransaction();
    
    try {
        $orderNumber = generateOrderNumber();
        
        // Build full address with area (now combined district + neighborhood)
        $fullAddress = '';
        if (!empty($data['customer_area'])) {
            $fullAddress .= $data['customer_area'];
        }
        if (!empty($data['customer_address'])) {
            $fullAddress .= ($fullAddress ? ' - ' : '') . $data['customer_address'];
        }
        
        // Insert order
        $stmt = $pdo->prepare("
            INSERT INTO orders (order_number, customer_name, customer_phone, customer_city, customer_address, contact_method, contact_value, subtotal, packaging_total, discount, coupon_code, delivery_fee, total, notes, terms_consent, consent_timestamp, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        // Parse consent_timestamp from ISO format
        $consentTs = null;
        if (!empty($data['consent_timestamp'])) {
            try {
                $dt = new DateTime($data['consent_timestamp']);
                $dt->setTimezone(new DateTimeZone('Asia/Baghdad'));
                $consentTs = $dt->format('Y-m-d H:i:s');
            } catch (Exception $e) {
                $consentTs = date('Y-m-d H:i:s');
            }
        }
        
        $stmt->execute(array(
            $orderNumber,
            isset($data['customer_name']) ? $data['customer_name'] : '',
            isset($data['customer_phone']) ? $data['customer_phone'] : '',
            isset($data['customer_city']) ? $data['customer_city'] : '',
            $fullAddress,
            isset($data['contact_method']) && !empty($data['contact_method']) ? $data['contact_method'] : null,
            isset($data['contact_value']) && !empty($data['contact_value']) ? $data['contact_value'] : null,
            isset($data['subtotal']) ? $data['subtotal'] : 0,
            isset($data['packaging_total']) ? $data['packaging_total'] : 0,
            isset($data['discount']) ? $data['discount'] : 0,
            isset($data['coupon_code']) ? $data['coupon_code'] : null,
            isset($data['delivery_fee']) ? $data['delivery_fee'] : 0,
            isset($data['total']) ? $data['total'] : 0,
            isset($data['notes']) ? $data['notes'] : '',
            isset($data['terms_consent']) ? ($data['terms_consent'] ? 1 : 0) : 0,
            $consentTs
        ));
        
        $orderId = $pdo->lastInsertId();
        
        // Insert order items
        $stmtItem = $pdo->prepare("
            INSERT INTO order_items (order_id, product_id, product_name, price, quantity, has_custom_image, selected_options, custom_images, packaging_selected, packaging_price, packaging_description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($data['items'] as $item) {
            // Prepare selected options as JSON
            $selectedOptions = null;
            if (!empty($item['selectedOptions'])) {
                $selectedOptions = json_encode($item['selectedOptions'], JSON_UNESCAPED_UNICODE);
            }
            
            // استخراج بيانات التغليف من الخيارات المختارة
            $packagingSelected = 0;
            $packagingPrice = 0;
            $packagingDescription = '';
            
            if (!empty($item['selectedOptions'])) {
                $sOpt = $item['selectedOptions'];
                $packagingSelected = !empty($sOpt['packaging_selected']) ? 1 : 0;
                $packagingPrice = isset($sOpt['packaging_price']) ? intval($sOpt['packaging_price']) : 0;
                $packagingDescription = isset($sOpt['packaging_description']) ? $sOpt['packaging_description'] : '';
            }
            
            // إعداد صور التخصيص المتعددة كـ JSON
            $customImages = null;
            if (!empty($item['customImages']) && is_array($item['customImages'])) {
                $customImages = json_encode($item['customImages'], JSON_UNESCAPED_UNICODE);
            } elseif (!empty($item['customImage'])) {
                // للتوافق مع الصورة الواحدة القديمة
                $customImages = json_encode([$item['customImage']], JSON_UNESCAPED_UNICODE);
            }
            
            $stmtItem->execute(array(
                $orderId,
                isset($item['id']) ? $item['id'] : null,
                $item['name'],
                $item['price'],
                $item['quantity'],
                !empty($item['hasCustomImage']) ? 1 : 0,
                $selectedOptions,
                $customImages,
                $packagingSelected,
                $packagingPrice,
                $packagingDescription
            ));
        }
        
        // Insert uploaded images
        if (!empty($data['uploaded_images'])) {
            $stmtUpload = $pdo->prepare("INSERT INTO customer_uploads (order_id, file_path) VALUES (?, ?)");
            foreach ($data['uploaded_images'] as $img) {
                $stmtUpload->execute([$orderId, $img]);
            }
        }
        
        $pdo->commit();
        
        return [
            'id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $data['total']
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function updateOrderStatus($id, $newStatus) {
    if (!isDbConnected()) return false;
    $pdo = db();
    
    // Get current order status and number
    $stmt = $pdo->prepare("SELECT order_number, status FROM orders WHERE id = ?");
    $stmt->execute([$id]);
    $order = $stmt->fetch();
    
    if (!$order) return false;
    
    $currentStatus = $order['status'];
    $orderNumber = $order['order_number'];
    
    // Update status
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$newStatus, $id]);
    
    if ($result && $currentStatus !== $newStatus) {
        // Send Telegram Notification for status update
        $statusLabels = getTrackingStatusLabels();
        $newLabel = isset($statusLabels[$newStatus]) ? $statusLabels[$newStatus]['label'] : $newStatus;
        $icon = isset($statusLabels[$newStatus]) ? $statusLabels[$newStatus]['icon'] : 'ℹ️';
        
        $msg = "🔔 <b>تحديث حالة الطلب</b>\n";
        $msg .= "━━━━━━━━━━━━━━━━\n";
        $msg .= "📦 رقم الطلب: <code>$orderNumber</code>\n";
        $msg .= "🔄 الحالة الجديدة: <b>$icon $newLabel</b>\n";
        $msg .= "⏰ تحديث: " . formatDateTime(date('Y-m-d H:i:s'), 'full');
        
        sendTelegramNotification($msg);
    }
    
    // Statuses that count as sales (confirmed and beyond)
    $salesStatuses = ['confirmed', 'processing', 'shipped', 'delivered'];
    $wasSale = in_array($currentStatus, $salesStatuses);
    $isSale = in_array($newStatus, $salesStatuses);
    
    // If becoming a sale (first time confirmed), update stats
    if ($result && $isSale && !$wasSale) {
        updateProductSalesStats($id);
    }
    
    // If no longer a sale (cancelled from a sales status), reverse stats
    if ($result && $wasSale && !$isSale) {
        reverseProductSalesStats($id);
    }
    
    return $result;
}

/**
 * Update product sales statistics when order is confirmed
 * Deducts stock and updates sales counts
 */
function updateProductSalesStats($orderId) {
    $pdo = db();
    
    // Get order items
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
    foreach ($items as $item) {
        if (!$item['product_id']) continue;
        
        $productId = $item['product_id'];
        
        // Try to find product by id (numeric) or product_id (varchar)
        $checkStmt = $pdo->prepare("
            SELECT id, stock, name FROM products 
            WHERE id = ? OR product_id = ?
            LIMIT 1
        ");
        $checkStmt->execute([$productId, $productId]);
        $product = $checkStmt->fetch();
        
        if (!$product) continue;
        
        // Use the actual product id
        $actualId = $product['id'];
        
        // Update product sales count AND reduce stock
        $stmt = $pdo->prepare("
            UPDATE products 
            SET total_sold = COALESCE(total_sold, 0) + ?,
                monthly_sold = COALESCE(monthly_sold, 0) + ?,
                stock = GREATEST(0, COALESCE(stock, 0) - ?),
                last_sale_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $item['quantity'], 
            $item['quantity'], 
            $item['quantity'],
            $actualId
        ]);
        
        // Auto-update status to 'sold' if stock reaches 0
        $stmt = $pdo->prepare("
            UPDATE products 
            SET status = 'sold' 
            WHERE id = ?
              AND stock <= 0 
              AND status = 'available'
        ");
        $stmt->execute([$actualId]);

        // === DEDUCT BOX STOCK ===
        // Get the order item data to check for selected box
        $orderItemStmt = $pdo->prepare("SELECT selected_options FROM order_items WHERE order_id = ? AND product_id = ?");
        $orderItemStmt->execute([$orderId, $productId]);
        $orderItem = $orderItemStmt->fetch();
        
        if ($orderItem && !empty($orderItem['selected_options'])) {
            $opts = json_decode($orderItem['selected_options'], true);
            if (!empty($opts['box_selected'])) {
                $boxIdx = $opts['box_selected_idx'] ?? $opts['box_selected'] ?? null;
                
                // Get fresh product options
                $prodStmt = $pdo->prepare("SELECT options FROM products WHERE id = ?");
                $prodStmt->execute([$actualId]);
                $pRow = $prodStmt->fetch();
                
                if ($pRow && !empty($pRow['options'])) {
                    $pOptions = json_decode($pRow['options'], true);
                    if ($boxIdx !== null && isset($pOptions['box_options']['items'][$boxIdx])) {
                        // Deduct stock
                        $currentStock = $pOptions['box_options']['items'][$boxIdx]['stock'] ?? 999;
                        $pOptions['box_options']['items'][$boxIdx]['stock'] = max(0, $currentStock - $item['quantity']);
                        
                        // Save back
                        $updateStmt = $pdo->prepare("UPDATE products SET options = ? WHERE id = ?");
                        $updateStmt->execute([json_encode($pOptions, JSON_UNESCAPED_UNICODE), $actualId]);
                    }
                }
            }
        }
    }
    
    // Recalculate best seller badges
    updateBestSellerBadges();
}

/**
 * Reverse product sales stats (when order is cancelled)
 * Restores stock and reverses sales counts
 */
function reverseProductSalesStats($orderId) {
    $pdo = db();
    
    $stmt = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
    foreach ($items as $item) {
        if (!$item['product_id']) continue;
        
        $productId = $item['product_id'];
        
        // Try to find product by id (numeric) or product_id (varchar)
        $checkStmt = $pdo->prepare("
            SELECT id FROM products 
            WHERE id = ? OR product_id = ?
            LIMIT 1
        ");
        $checkStmt->execute([$productId, $productId]);
        $product = $checkStmt->fetch();
        
        if (!$product) continue;
        
        // Use the actual product id
        $actualId = $product['id'];
        
        // Reverse sales count AND restore stock
        $stmt = $pdo->prepare("
            UPDATE products 
            SET total_sold = GREATEST(0, COALESCE(total_sold, 0) - ?),
                monthly_sold = GREATEST(0, COALESCE(monthly_sold, 0) - ?),
                stock = COALESCE(stock, 0) + ?
            WHERE id = ?
        ");
        $stmt->execute([
            $item['quantity'], 
            $item['quantity'], 
            $item['quantity'],
            $actualId
        ]);
        
        // === RESTORE BOX STOCK ===
        $orderItemStmt = $pdo->prepare("SELECT selected_options FROM order_items WHERE order_id = ? AND product_id = ?");
        $orderItemStmt->execute([$orderId, $productId]);
        $orderItem = $orderItemStmt->fetch();
        
        if ($orderItem && !empty($orderItem['selected_options'])) {
            $opts = json_decode($orderItem['selected_options'], true);
            if (!empty($opts['box_selected'])) {
                $boxIdx = $opts['box_selected_idx'] ?? $opts['box_selected'] ?? null;
                
                $prodStmt = $pdo->prepare("SELECT options FROM products WHERE id = ?");
                $prodStmt->execute([$actualId]);
                $pRow = $prodStmt->fetch();
                
                if ($pRow && !empty($pRow['options'])) {
                    $pOptions = json_decode($pRow['options'], true);
                    if ($boxIdx !== null && isset($pOptions['box_options']['items'][$boxIdx])) {
                        $currentStock = $pOptions['box_options']['items'][$boxIdx]['stock'] ?? 0;
                        $pOptions['box_options']['items'][$boxIdx]['stock'] = $currentStock + $item['quantity'];
                        
                        $updateStmt = $pdo->prepare("UPDATE products SET options = ? WHERE id = ?");
                        $updateStmt->execute([json_encode($pOptions, JSON_UNESCAPED_UNICODE), $actualId]);
                    }
                }
            }
        }
        
        // Restore status to 'available' if stock > 0 and was 'sold'
        $stmt = $pdo->prepare("
            UPDATE products 
            SET status = 'available' 
            WHERE id = ? 
              AND stock > 0 
              AND status = 'sold'
        ");
        $stmt->execute([$actualId]);
    }
    
    updateBestSellerBadges();
}

/**
 * Update best seller badges for products
 */
function updateBestSellerBadges() {
    $pdo = db();
    
    // Reset all badges first
    $pdo->exec("UPDATE products SET is_best_seller = 0, is_trending = 0");
    
    // Mark top 3 all-time best sellers
    $pdo->exec("
        UPDATE products 
        SET is_best_seller = 1 
        WHERE total_sold > 0 
        ORDER BY total_sold DESC 
        LIMIT 3
    ");
    
    // Mark top 3 trending this month
    $pdo->exec("
        UPDATE products 
        SET is_trending = 1 
        WHERE monthly_sold > 0 
        ORDER BY monthly_sold DESC 
        LIMIT 3
    ");
}

/**
 * Reset monthly sales (call this at the start of each month)
 */
function resetMonthlySales() {
    $pdo = db();
    $pdo->exec("UPDATE products SET monthly_sold = 0");
    updateBestSellerBadges();
}

/**
 * Get best selling products
 */
/**
 * Get product badge (best seller / trending)
 */
function getProductBadge($product) {
    if (!empty($product['is_best_seller']) && $product['is_best_seller']) {
        return ['type' => 'best_seller', 'label' => '🏆 الأكثر مبيعاً', 'class' => 'badge-bestseller'];
    }
    if (!empty($product['is_trending']) && $product['is_trending']) {
        return ['type' => 'trending', 'label' => '🔥 رائج هذا الشهر', 'class' => 'badge-trending'];
    }
    return null;
}

function deleteOrder($id) {
    $stmt = db()->prepare("DELETE FROM orders WHERE id = ?");
    return $stmt->execute([$id]);
}

function getOrderStatusLabel($status) {
    $statuses = [
        'pending' => ['label' => 'قيد الانتظار', 'class' => 'status-pending', 'icon' => '⏳'],
        'confirmed' => ['label' => 'تم التأكيد', 'class' => 'status-confirmed', 'icon' => '✓'],
        'processing' => ['label' => 'قيد التجهيز', 'class' => 'status-processing', 'icon' => '🔧'],
        'shipped' => ['label' => 'تم الشحن', 'class' => 'status-shipped', 'icon' => '🚚'],
        'delivered' => ['label' => 'تم التوصيل', 'class' => 'status-delivered', 'icon' => '✅'],
        'cancelled' => ['label' => 'ملغي', 'class' => 'status-cancelled', 'icon' => '✗']
    ];
    return isset($statuses[$status]) ? $statuses[$status] : $statuses['pending'];
}

function countOrdersByStatus($status = null) {
    return countOrders($status);
}

// ============ BANNER FUNCTIONS ============

function getBanners($activeOnly = false) {
    if (!isDbConnected()) {
        return [];
    }
    
    try {
        $sql = "SELECT * FROM banners";
        if ($activeOnly) {
            $sql .= " WHERE is_active = 1";
        }
        $sql .= " ORDER BY sort_order ASC, id ASC";
        
        $stmt = db()->query($sql);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

function getBanner($id) {
    $stmt = db()->prepare("SELECT * FROM banners WHERE id = ? OR banner_id = ?");
    $stmt->execute([$id, $id]);
    return $stmt->fetch();
}

function saveBanner($data) {
    if (!empty($data['id'])) {
        $stmt = db()->prepare("
            UPDATE banners SET image_path = ?, title = ?, subtitle = ?, is_active = ?, sort_order = ?
            WHERE id = ?
        ");
        return $stmt->execute(array(
            $data['image_path'], isset($data['title']) ? $data['title'] : '', isset($data['subtitle']) ? $data['subtitle'] : '',
            $data['is_active'] ? 1 : 0, isset($data['sort_order']) ? $data['sort_order'] : 0, $data['id']
        ));
    } else {
        $bannerId = 'banner_' . uniqid();
        $stmt = db()->prepare("
            INSERT INTO banners (banner_id, image_path, title, subtitle, is_active, sort_order)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute(array(
            $bannerId, $data['image_path'], isset($data['title']) ? $data['title'] : '', 
            isset($data['subtitle']) ? $data['subtitle'] : '', 1, isset($data['sort_order']) ? $data['sort_order'] : 0
        ));
    }
}

function toggleBannerActive($id) {
    $stmt = db()->prepare("UPDATE banners SET is_active = NOT is_active WHERE id = ?");
    return $stmt->execute([$id]);
}

function deleteBanner($id) {
    $stmt = db()->prepare("DELETE FROM banners WHERE id = ? OR banner_id = ?");
    return $stmt->execute([$id, $id]);
}

// ============ SETTINGS FUNCTIONS ============

function getSettings() {
    // Defaults
    $defaults = [
        'instagram' => 'sur._prises',
        'instagram_url' => 'https://instagram.com/sur._prises',
        'instagram_dm' => 'https://ig.me/m/sur._prises',
        'telegram_order_username' => 'sur_prisese',
        'telegram_order_dm' => 'https://t.me/sur_prisese',
        'delivery_price' => 5000,
        'currency' => 'د.ع'
    ];
    
    if (!isDbConnected()) {
        return $defaults;
    }
    
    try {
        $stmt = db()->query("SELECT setting_key, setting_value FROM settings");
        $rows = $stmt->fetchAll();
        
        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return array_merge($defaults, $settings);
    } catch (Exception $e) {
        return $defaults;
    }
}

function getSetting($key, $default = null) {
    $stmt = db()->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();
    return $value !== false ? $value : $default;
}

function saveSetting($key, $value) {
    $stmt = db()->prepare("
        INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = ?
    ");
    return $stmt->execute([$key, $value, $value]);
}

function saveSettings($settings) {
    foreach ($settings as $key => $value) {
        saveSetting($key, $value);
    }
    return true;
}

// ============ IMAGE UPLOAD FUNCTIONS ============

function uploadImage($file, $destination = 'products') {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'خطأ في رفع الملف'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'error' => 'حجم الملف كبير جداً (الحد الأقصى 5MB)'];
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'error' => 'نوع الملف غير مسموح (jpg, png, webp فقط)'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $allowed_mimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowed_mimes)) {
        return ['success' => false, 'error' => 'نوع الملف غير صالح'];
    }
    
    $newName = uniqid() . '_' . time() . '.' . $ext;
    $destPath = IMAGES_PATH . $destination . '/' . $newName;
    
    if (move_uploaded_file($file['tmp_name'], $destPath)) {
        return ['success' => true, 'filename' => $newName, 'path' => 'images/' . $destination . '/' . $newName];
    }
    
    return ['success' => false, 'error' => 'فشل في حفظ الملف'];
}

function deleteImage($path) {
    $fullPath = ROOT_PATH . $path;
    if (file_exists($fullPath) && strpos($path, 'images/') === 0) {
        return unlink($fullPath);
    }
    return false;
}

// ============ UTILITY FUNCTIONS ============

function formatPrice($price) {
    return number_format($price, 0, '', ',') . ' د.ع';
}

/**
 * Format datetime in Iraq timezone with 12-hour format
 * @param string $datetime Date/time string from database (already in Baghdad timezone)
 * @param string $format Format type: 'full', 'date', 'time', 'short', 'datetime'
 * @return string Formatted date/time in Arabic
 * 
 * NOTE: After MySQL timezone fix, all dates from DB are already in Baghdad time (+03:00)
 * We parse them directly without additional timezone conversion
 */
function formatDateTime($datetime, $format = 'full') {
    if (empty($datetime)) return '-';
    
    try {
        // Parse datetime assuming it's already in Baghdad timezone
        // Since MySQL session time_zone is set to +03:00, all dates from DB are in Baghdad time
        $dt = new DateTime($datetime, new DateTimeZone('Asia/Baghdad'));
        
        // Arabic day names
        $arabicDays = [
            'Sunday' => 'الأحد',
            'Monday' => 'الإثنين', 
            'Tuesday' => 'الثلاثاء',
            'Wednesday' => 'الأربعاء',
            'Thursday' => 'الخميس',
            'Friday' => 'الجمعة',
            'Saturday' => 'السبت'
        ];
        
        // Arabic month names
        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];
        
        // Get hour for 12-hour format
        $hour = (int)$dt->format('G'); // 0-23
        $minute = $dt->format('i');
        $period = $hour < 12 ? 'ص' : 'م'; // صباحاً / مساءً
        $hour12 = $hour % 12;
        if ($hour12 == 0) $hour12 = 12;
        
        $day = $dt->format('j'); // Day of month
        $month = (int)$dt->format('n'); // Month number
        $year = $dt->format('Y');
        $dayName = isset($arabicDays[$dt->format('l')]) ? $arabicDays[$dt->format('l')] : $dt->format('l');
        $monthName = isset($arabicMonths[$month]) ? $arabicMonths[$month] : $month;
        
        switch ($format) {
            case 'full':
                // الأربعاء، 15 يناير 2026 - 10:30 م
                return "{$dayName}، {$day} {$monthName} {$year} - {$hour12}:{$minute} {$period}";
            
            case 'date':
                // 15 يناير 2026
                return "{$day} {$monthName} {$year}";
            
            case 'time':
                // 10:30 م
                return "{$hour12}:{$minute} {$period}";
            
            case 'short':
                // 2026/1/15 10:30 م
                return "{$year}/{$month}/{$day} {$hour12}:{$minute} {$period}";
            
            case 'datetime':
                // 15 يناير - 10:30 م
                return "{$day} {$monthName} - {$hour12}:{$minute} {$period}";
            
            case 'numeric':
                // 2026/01/15 10:30 م
                return "{$year}/" . str_pad($month, 2, '0', STR_PAD_LEFT) . "/" . str_pad($day, 2, '0', STR_PAD_LEFT) . " {$hour12}:{$minute} {$period}";
            
            default:
                return "{$year}/{$month}/{$day} {$hour12}:{$minute} {$period}";
        }
    } catch (Exception $e) {
        return $datetime; // Return original if parsing fails
    }
}


/**
 * Format price with discount display (old price strikethrough + discount badge)
 * @param int $price Current price
 * @param int $oldPrice Old price (before discount)
 * @param bool $showBadge Whether to show discount percentage badge
 * @return string HTML formatted price
 */
function formatPriceWithDiscount($price, $oldPrice = 0, $showBadge = true) {
    $priceFormatted = formatPrice($price);
    
    // If no old price or old price is not greater than current price, return simple price
    if (!$oldPrice || $oldPrice <= $price) {
        return '<span class="price-current">' . $priceFormatted . '</span>';
    }
    
    // Calculate discount percentage
    $discountPercent = round((($oldPrice - $price) / $oldPrice) * 100);
    $oldPriceFormatted = formatPrice($oldPrice);
    
    // Layout: [Current Price] [Old Price strikethrough] [Discount Badge]
    $html = '<span class="price-wrapper">';
    $html .= '<span class="price-current">' . $priceFormatted . '</span>';
    $html .= '<span class="price-old">' . $oldPriceFormatted . '</span>';
    if ($showBadge) {
        $html .= '<span class="discount-badge">-' . $discountPercent . '%</span>';
    }
    $html .= '</span>';
    
    return $html;
}

/**
 * Get discount percentage between old and new price
 */
function getDiscountPercent($price, $oldPrice) {
    if (!$oldPrice || $oldPrice <= $price) {
        return 0;
    }
    return round((($oldPrice - $price) / $oldPrice) * 100);
}

/**
 * Check if product has discount
 */
function hasDiscount($product) {
    return isset($product['old_price']) && $product['old_price'] > 0 && $product['old_price'] > $product['price'];
}

function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function getCategories($includeInactive = false) {
    // Default categories (fallback)
    $defaultCategories = [
        'boxes' => ['id' => 'boxes', 'name' => 'بوكسات 2026', 'icon' => '🎁', 'sort_order' => 1, 'is_active' => true],
        'printing' => ['id' => 'printing', 'name' => 'الطباعة الشخصية', 'icon' => '🖨️', 'sort_order' => 2, 'is_active' => true],
        'decorations' => ['id' => 'decorations', 'name' => 'التحفيات والبلورات', 'icon' => '✨', 'sort_order' => 3, 'is_active' => true],
        'watches' => ['id' => 'watches', 'name' => 'الساعات', 'icon' => '⌚', 'sort_order' => 4, 'is_active' => true]
    ];
    
    // Try to load from JSON file with error handling
    try {
        if (defined('ROOT_PATH')) {
            $categoriesFile = ROOT_PATH . 'data/categories.json';
            if (file_exists($categoriesFile)) {
                $content = @file_get_contents($categoriesFile);
                if ($content !== false) {
                    $savedCategories = json_decode($content, true);
                    if ($savedCategories && is_array($savedCategories)) {
                        // Sort by sort_order
                        uasort($savedCategories, function($a, $b) {
                            $aOrder = isset($a['sort_order']) ? $a['sort_order'] : 99;
                            $bOrder = isset($b['sort_order']) ? $b['sort_order'] : 99;
                            return $aOrder - $bOrder;
                        });
                        
                        // Filter inactive if needed
                        if (!$includeInactive) {
                            $savedCategories = array_filter($savedCategories, function($cat) {
                                return isset($cat['is_active']) ? $cat['is_active'] : true;
                            });
                        }
                        
                        return $savedCategories;
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail and use defaults
    }
    
    // Return defaults if file doesn't exist or reading failed
    return $defaultCategories;
}

/**
 * Get a single category by ID
 */
function getCategory($id) {
    $categories = getCategories(true);
    return isset($categories[$id]) ? $categories[$id] : null;
}

/**
 * Save categories to JSON file
 */
function saveCategories($categories) {
    $categoriesFile = ROOT_PATH . 'data/categories.json';
    return file_put_contents($categoriesFile, json_encode($categories, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Add a new category
 */
function addCategory($data) {
    $categories = getCategories(true);
    
    // Generate ID from name if not provided
    $id = isset($data['id']) ? $data['id'] : sanitizeCategoryId($data['name']);
    
    // Check if ID already exists
    if (isset($categories[$id])) {
        return ['success' => false, 'error' => 'هذا القسم موجود مسبقاً'];
    }
    
    // Get max sort order
    $maxSort = 0;
    foreach ($categories as $cat) {
        $catSort = isset($cat['sort_order']) ? $cat['sort_order'] : 0;
        if ($catSort > $maxSort) {
            $maxSort = $cat['sort_order'];
        }
    }
    
    $categories[$id] = array(
        'id' => $id,
        'name' => $data['name'],
        'icon' => isset($data['icon']) ? $data['icon'] : '📦',
        'sort_order' => $maxSort + 1,
        'is_active' => isset($data['is_active']) ? (bool)$data['is_active'] : true
    );
    
    if (saveCategories($categories)) {
        return ['success' => true, 'id' => $id];
    }
    return ['success' => false, 'error' => 'فشل في حفظ القسم'];
}

/**
 * Update an existing category
 */
function updateCategory($id, $data) {
    $categories = getCategories(true);
    
    if (!isset($categories[$id])) {
        return ['success' => false, 'error' => 'القسم غير موجود'];
    }
    
    // Update fields
    if (isset($data['name'])) $categories[$id]['name'] = $data['name'];
    if (isset($data['icon'])) $categories[$id]['icon'] = $data['icon'];
    if (isset($data['sort_order'])) $categories[$id]['sort_order'] = (int)$data['sort_order'];
    if (isset($data['is_active'])) $categories[$id]['is_active'] = (bool)$data['is_active'];
    
    if (saveCategories($categories)) {
        return ['success' => true];
    }
    return ['success' => false, 'error' => 'فشل في حفظ التغييرات'];
}

/**
 * Delete a category
 */
function deleteCategory($id) {
    $categories = getCategories(true);
    
    if (!isset($categories[$id])) {
        return ['success' => false, 'error' => 'القسم غير موجود'];
    }
    
    // Check if category has products
    $pdo = db();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category = ?");
    $stmt->execute([$id]);
    $productCount = $stmt->fetchColumn();
    
    if ($productCount > 0) {
        return ['success' => false, 'error' => "لا يمكن حذف القسم لأنه يحتوي على {$productCount} منتج. انقل المنتجات أولاً."];
    }
    
    unset($categories[$id]);
    
    if (saveCategories($categories)) {
        return ['success' => true];
    }
    return ['success' => false, 'error' => 'فشل في حفظ التغييرات'];
}

/**
 * Toggle category active status
 */
function toggleCategoryActive($id) {
    $categories = getCategories(true);
    
    if (!isset($categories[$id])) {
        return false;
    }
    
    $currentStatus = isset($categories[$id]['is_active']) ? $categories[$id]['is_active'] : true;
    $categories[$id]['is_active'] = !$currentStatus;
    return saveCategories($categories);
}

/**
 * Sanitize category ID (convert Arabic name to slug)
 */
function sanitizeCategoryId($name) {
    // Remove special characters and convert spaces to underscores
    $id = preg_replace('/[^\p{L}\p{N}\s]/u', '', $name);
    $id = preg_replace('/\s+/', '_', trim($id));
    $id = strtolower($id);
    
    // If still empty or has issues, generate random
    if (empty($id) || strlen($id) < 2) {
        $id = 'cat_' . uniqid();
    }
    
    return $id;
}

/**
 * Get products count by category
 */
function getProductsCountByCategory() {
    $pdo = db();
    $stmt = $pdo->query("SELECT category, COUNT(*) as count FROM products GROUP BY category");
    $counts = [];
    while ($row = $stmt->fetch()) {
        $counts[$row['category']] = $row['count'];
    }
    return $counts;
}

// ============ TELEGRAM NOTIFICATIONS ============

/**
 * Discover Telegram Chat ID automatically from recent updates
 */
function discoverTelegramChatId() {
    if (!defined('TELEGRAM_BOT_TOKEN') || empty(TELEGRAM_BOT_TOKEN)) {
        return null;
    }

    $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/getUpdates";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && !empty($response)) {
        $result = json_decode($response, true);
        if (isset($result['ok']) && $result['ok'] && !empty($result['result'])) {
            // Find the most recent chat id
            $chatId = null;
            foreach (array_reverse($result['result']) as $update) {
                if (isset($update['message']['chat']['id'])) {
                    $chatId = $update['message']['chat']['id'];
                    break;
                } elseif (isset($update['my_chat_member']['chat']['id'])) {
                    $chatId = $update['my_chat_member']['chat']['id'];
                    break;
                }
            }
            
            if ($chatId) {
                // Save it to database if connected
                if (isDbConnected()) {
                    saveSetting('telegram_chat_id', $chatId);
                }
                return $chatId;
            }
        }
    }
    
    // Log failure (internal only)
    error_log("Failed to discover Telegram chat_id: " . ($response ?: "No response"));
    return null;
}

/**
 * Send notification to Telegram with cURL and auto-discovery
 */
function sendTelegramNotification($message) {
    if (!defined('TELEGRAM_ENABLED') || !TELEGRAM_ENABLED) {
        return false;
    }
    
    if (empty(TELEGRAM_BOT_TOKEN)) {
        return false;
    }
    
    // Get chat id: prioritize config, then DB, then auto-discovery
    $chatId = defined('TELEGRAM_CHAT_ID') && !empty(TELEGRAM_CHAT_ID) ? TELEGRAM_CHAT_ID : null;
    
    if (!$chatId && isDbConnected()) {
        $chatId = getSetting('telegram_chat_id');
    }
    
    if (!$chatId) {
        $chatId = discoverTelegramChatId();
    }
    
    if (!$chatId) {
        return false;
    }
    
    // Split message if too long (Telegram limit is 4096 characters)
    $messages = [];
    if (mb_strlen($message) > 4000) {
        $msgLen = mb_strlen($message);
        for ($i = 0; $i < $msgLen; $i += 4000) {
            $messages[] = mb_substr($message, $i, 4000);
        }
    } else {
        $messages = [$message];
    }
    
    $success = true;
    foreach ($messages as $msg) {
        $url = "https://api.telegram.org/bot" . TELEGRAM_BOT_TOKEN . "/sendMessage";
        $data = [
            'chat_id' => $chatId,
            'text' => $msg,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) $success = false;
    }
    
    return $success;
}

/**
 * Send comprehensive new order notification
 */
function notifyNewOrder($order, $items) {
    $message = "🛒 <b>طلب جديد!</b>\n";
    $message .= "━━━━━━━━━━━━━━━━\n";
    $message .= "📦 رقم الطلب: <code>{$order['order_number']}</code>\n\n";
    
    $message .= "👤 الزبون: " . ($order['customer_name'] ?: 'غير متوفر') . "\n";
    $message .= "📱 الهاتف: " . ($order['customer_phone'] ?: 'غير متوفر') . "\n\n";
    
    $message .= "📍 العنوان الكامل:\n";
    $message .= "   • المحافظة: " . ($order['customer_city'] ?: 'غير متوفر') . "\n";
    if (!empty($order['customer_area'])) {
        $message .= "   • المنطقة/الحي: {$order['customer_area']}\n";
    }
    $message .= "   • العنوان: " . ($order['customer_address'] ?: 'غير متوفر') . "\n\n";
    
    $message .= "🛍️ المنتجات:\n";
    foreach ($items as $item) {
        $pPrice = intval($item['price']);
        $qty = intval($item['quantity']);
        $itemTotal = $pPrice * $qty;
        
        $message .= "* {$item['name']} × $qty = " . number_format($itemTotal) . " د.ع\n";
        
        // Options
        if (!empty($item['selectedOptions'])) {
            $opts = $item['selectedOptions'];
            $optParts = [];
            
            // Core options
            if (!empty($opts['color'])) $optParts[] = "اللون: {$opts['color']}";
            if (!empty($opts['size'])) $optParts[] = "الحجم: {$opts['size']}";
            if (!empty($opts['age'])) $optParts[] = "الفئة العمرية: {$opts['age']}";
            if (!empty($opts['custom_text'])) $optParts[] = "النص: {$opts['custom_text']}";
            
            // Multiple groups (color_groups, size_groups, age_groups)
            $groups = ['color_groups', 'size_groups', 'age_groups'];
            foreach ($groups as $g) {
                if (!empty($opts[$g]) && is_array($opts[$g])) {
                    foreach ($opts[$g] as $label => $val) {
                        $optParts[] = "$label: $val";
                    }
                }
            }
            
            // Extra Unified Fields
            if (!empty($opts['extra_fields']) && is_array($opts['extra_fields'])) {
                foreach ($opts['extra_fields'] as $field) {
                    if (!empty($field['label']) && !empty($field['value'])) {
                        $optParts[] = "{$field['label']}: {$field['value']}";
                    }
                }
            }
            
            if (!empty($optParts)) {
                $message .= "   🎛️ " . implode(' | ', $optParts) . "\n";
            }
            
            // نظام بطاقات الرسائل المتعددة
            if (!empty($opts['gift_cards']) && is_array($opts['gift_cards'])) {
                foreach ($opts['gift_cards'] as $card) {
                    if (!empty($card['label']) && !empty($card['message'])) {
                        $label = htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8');
                        $cardMsg = htmlspecialchars($card['message'], ENT_QUOTES, 'UTF-8');
                        $message .= "   🎁 $label: \"$cardMsg\"\n";
                    }
                }
            } elseif (isset($opts['gift_card_enabled'])) {
                // Compatibility for older formats
                $status = $opts['gift_card_enabled'] ? 'نعم' : 'لا';
                $message .= "   🎁 بطاقة رسالة: {$status}\n";
                if ($opts['gift_card_enabled'] && !empty($opts['gift_card_message'])) {
                    $giftMsg = htmlspecialchars($opts['gift_card_message'], ENT_QUOTES, 'UTF-8');
                    $message .= "   📝 نص الرسالة: \"{$giftMsg}\"\n";
                }
            } elseif (!empty($opts['gift_card_message'])) {
                // Fallback for older orders
                $giftMsg = htmlspecialchars($opts['gift_card_message'], ENT_QUOTES, 'UTF-8');
                $message .= "   💌 بطاقة رسالة: \"{$giftMsg}\"\n";
            }
            
            // Packaging
            if (!empty($opts['packaging_selected'])) {
                $pkgPrice = isset($opts['packaging_price']) ? intval($opts['packaging_price']) : 0;
                $pkgDesc = !empty($opts['packaging_description']) ? " ({$opts['packaging_description']})" : "";
                $message .= "   🎁 التغليف: نعم$pkgDesc (+ " . number_format($pkgPrice) . " د.ع)\n";
            }
            
            // Box Options
            if (!empty($opts['box_selected'])) {
                $boxName = $opts['box_name'] ?? 'صندوق خاص';
                $boxPrice = isset($opts['box_price']) ? intval($opts['box_price']) : 0;
                $message .= "   📦 الصندوق: $boxName (+ " . number_format($boxPrice) . " د.ع)\n";
            }
        }
        
        // Custom Images
        $imgs = [];
        if (!empty($item['customImages']) && is_array($item['customImages'])) {
            $imgs = $item['customImages'];
        } elseif (!empty($item['customImage'])) {
            $imgs = [$item['customImage']];
        }
        
        if (!empty($imgs)) {
            $imgLabel = (count($imgs) == 1) ? "صورة مرفقة للطباعة" : "صور مرفقة للطباعة: " . count($imgs) . " صورة";
            $message .= "   📷 $imgLabel\n";
            foreach ($imgs as $img) {
                $imgUrl = SITE_URL . '/' . $img;
                $message .= "   - <a href=\"$imgUrl\">فتح الصورة</a>\n";
            }
        }
    }
    
    // Financial Summary
    $subtotal = isset($order['subtotal']) ? intval($order['subtotal']) : 0;
    $packaging = isset($order['packaging_total']) ? intval($order['packaging_total']) : 0;
    $delivery = isset($order['delivery_fee']) ? intval($order['delivery_fee']) : 0;
    $total = isset($order['total']) ? intval($order['total']) : ($subtotal + $packaging + $delivery);
    
    $message .= "\n💰 المنتجات: " . number_format($subtotal) . " د.ع\n";
    if ($packaging > 0) $message .= "🎁 إجمالي التغليف: " . number_format($packaging) . " د.ع\n";
    $message .= "🚚 التوصيل: " . number_format($delivery) . " د.ع\n";
    $message .= "💵 الإجمالي النهائي: " . number_format($total) . " د.ع\n\n";
    
    // Contact Method
    if (!empty($order['contact_method']) && !empty($order['contact_value'])) {
        $methodLabel = '';
        switch($order['contact_method']) {
            case 'instagram': $methodLabel = 'انستقرام 📸'; break;
            case 'whatsapp': $methodLabel = 'واتساب 💬'; break;
            case 'telegram': $methodLabel = 'تليجرام ✈️'; break;
            default: $methodLabel = $order['contact_method'];
        }
        $message .= "📱 وسيلة التواصل: $methodLabel\n";
        $message .= "🔗 المعرّف/الرقم: <code>{$order['contact_value']}</code>\n\n";
    }
    
    // Terms Consent
    if (!empty($order['terms_consent'])) {
        $message .= "✅ الموافقة على الشروط/الخصوصية: نعم\n\n";
    }
    
    if (!empty($order['notes'])) {
        $message .= "📝 ملاحظات: {$order['notes']}\n\n";
    }
    
    $orderTime = formatDateTime(date('Y-m-d H:i:s'), 'full');
    $message .= "⏰ $orderTime";
    
    return sendTelegramNotification($message);
}

// ============ COUPON SYSTEM ============

/**
 * Get all coupons
 */
function getCoupons() {
    $stmt = db()->query("SELECT * FROM coupons ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

/**
 * Get coupon by code
 */
function getCouponByCode($code) {
    $stmt = db()->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([strtoupper($code)]);
    return $stmt->fetch();
}

/**
 * Validate and apply coupon
 */
function validateCoupon($code, $subtotal) {
    $coupon = getCouponByCode($code);
    
    if (!$coupon) {
        return ['valid' => false, 'error' => 'كود الخصم غير صحيح'];
    }
    
    // Check expiry
    if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
        return ['valid' => false, 'error' => 'كود الخصم منتهي الصلاحية'];
    }
    
    // Check usage limit
    if ($coupon['max_uses'] > 0 && $coupon['used_count'] >= $coupon['max_uses']) {
        return ['valid' => false, 'error' => 'تم استخدام الكود الحد الأقصى من المرات'];
    }
    
    // Check minimum order
    if ($coupon['min_order'] > 0 && $subtotal < $coupon['min_order']) {
        return ['valid' => false, 'error' => 'الحد الأدنى للطلب: ' . formatPrice($coupon['min_order'])];
    }
    
    // Calculate discount
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($subtotal * $coupon['discount_value']) / 100;
        if ($coupon['max_discount'] > 0 && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = $coupon['discount_value'];
    }
    
    return [
        'valid' => true,
        'coupon' => $coupon,
        'discount' => $discount,
        'message' => 'تم تطبيق الخصم: ' . formatPrice($discount)
    ];
}

/**
 * Use coupon (increment usage count)
 */
function useCoupon($couponId) {
    $stmt = db()->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
    return $stmt->execute([$couponId]);
}

/**
 * Create new coupon
 */
function createCoupon($data) {
    $stmt = db()->prepare("
        INSERT INTO coupons (code, discount_type, discount_value, min_order, max_discount, max_uses, expires_at, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ");
    
    return $stmt->execute(array(
        strtoupper($data['code']),
        $data['discount_type'],
        $data['discount_value'],
        isset($data['min_order']) ? $data['min_order'] : 0,
        isset($data['max_discount']) ? $data['max_discount'] : 0,
        isset($data['max_uses']) ? $data['max_uses'] : 0,
        isset($data['expires_at']) ? $data['expires_at'] : null
    ));
}

// ============ ORDER TRACKING ============

/**
 * Get tracking info for order
 */
function getOrderTracking($orderNumber) {
    $stmt = db()->prepare("
        SELECT ot.*, o.order_number, o.status as current_status
        FROM order_tracking ot
        JOIN orders o ON ot.order_id = o.id
        WHERE o.order_number = ?
        ORDER BY ot.created_at DESC
    ");
    $stmt->execute([$orderNumber]);
    return $stmt->fetchAll();
}

/**
 * Add tracking update
 */
function addTrackingUpdate($orderId, $status, $note = '', $location = '') {
    $stmt = db()->prepare("
        INSERT INTO order_tracking (order_id, status, note, location)
        VALUES (?, ?, ?, ?)
    ");
    
    $result = $stmt->execute([$orderId, $status, $note, $location]);
    
    // Also update order status
    if ($result) {
        updateOrderStatus($orderId, $status);
    }
    
    return $result;
}

/**
 * Get tracking status labels
 */
function getTrackingStatusLabels() {
    return [
        'pending' => ['label' => 'قيد الانتظار', 'icon' => '⏳', 'color' => '#E91E8C'],
        'confirmed' => ['label' => 'تم التأكيد', 'icon' => '✓', 'color' => '#6366F1'],
        'processing' => ['label' => 'جاري التجهيز', 'icon' => '🔧', 'color' => '#8B5CF6'],
        'shipped' => ['label' => 'تم الشحن', 'icon' => '🚚', 'color' => '#10B981'],
        'out_for_delivery' => ['label' => 'في الطريق إليك', 'icon' => '📦', 'color' => '#0EA5E9'],
        'delivered' => ['label' => 'تم التوصيل', 'icon' => '✅', 'color' => '#22C55E'],
        'cancelled' => ['label' => 'ملغي', 'icon' => '✗', 'color' => '#EF4444']
    ];
}

/**
 * Get public order info for tracking page
 */
function getOrderForTracking($orderNumber, $phone) {
    $stmt = db()->prepare("
        SELECT id, order_number, customer_name, customer_city, status, total, created_at
        FROM orders 
        WHERE order_number = ? AND customer_phone = ?
    ");
    $stmt->execute([$orderNumber, $phone]);
    $order = $stmt->fetch();
    
    if ($order) {
        $order['tracking'] = getOrderTracking($orderNumber);
        $order['items'] = getOrderItems($order['id']);
    }
    
    return $order;
}

/**
 * Get order items with fresh product data
 * جلب عناصر الطلب مع أحدث بيانات المنتج من قاعدة البيانات
 */
function getOrderItems($orderId) {
    $stmt = db()->prepare("
        SELECT 
            oi.product_name,
            oi.quantity,
            oi.price,
            oi.has_custom_image,
            oi.custom_image_path,
            oi.selected_options,
            oi.product_id,
            oi.packaging_selected,
            oi.packaging_price,
            oi.packaging_description,
            oi.custom_images,
            p.name as current_product_name,
            p.images as product_images,
            p.description as product_description,
            p.category as product_category,
            p.status as product_status
        FROM order_items oi
        LEFT JOIN products p ON (oi.product_id = p.id OR oi.product_id = p.product_id)
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $items = $stmt->fetchAll();
    
    // Process items
    foreach ($items as &$item) {
        // Decode selected_options JSON
        if (!empty($item['selected_options'])) {
            $item['selected_options'] = json_decode($item['selected_options'], true);
        } else {
            $item['selected_options'] = [];
        }
        
        // استخدم اسم المنتج الحالي إذا كان موجوداً، وإلا استخدم الاسم المحفوظ
        if (!empty($item['current_product_name'])) {
            $item['product_name'] = $item['current_product_name'];
        }
        
        // فك ترميز صور المنتج
        if (!empty($item['product_images'])) {
            $item['images'] = json_decode($item['product_images'], true);
        } else {
            $item['images'] = [];
        }
        
        // فك ترميز صور التخصيص
        if (!empty($item['custom_images'])) {
            $item['custom_images_decoded'] = json_decode($item['custom_images'], true);
        } else {
            $item['custom_images_decoded'] = [];
        }
    }
    
    return $items;
}

// ============ PAYMENT METHODS SYSTEM ============

/**
 * Get all available payment methods
 * Central source of truth for payment methods across the site
 * Reads from settings file for dynamic control from admin panel
 */
function getPaymentMethods() {
    // Default payment methods configuration
    $defaultMethods = [
        'cod' => [
            'id' => 'cod',
            'name' => 'الدفع عند الاستلام',
            'icon' => '💵',
            'description' => 'ادفع نقداً عند استلام طلبك',
            'enabled' => true,
            'is_default' => true,
            'sort_order' => 1
        ],
        'mastercard' => [
            'id' => 'mastercard',
            'name' => 'ماستر كارد',
            'icon' => '💳',
            'description' => 'الدفع ببطاقة الائتمان',
            'enabled' => true,
            'is_default' => false,
            'sort_order' => 2
        ],
        'zain_cash' => [
            'id' => 'zain_cash',
            'name' => 'زين كاش',
            'icon' => '📱',
            'description' => 'الدفع عبر تطبيق زين كاش',
            'enabled' => true,
            'is_default' => false,
            'sort_order' => 3
        ],
        'asia_credit' => [
            'id' => 'asia_credit',
            'name' => 'رصيد آسيا',
            'icon' => '🏦',
            'description' => 'الدفع عبر رصيد آسيا هوالي',
            'enabled' => true,
            'is_default' => false,
            'sort_order' => 4
        ]
    ];
    
    // Try to load saved settings with error handling
    try {
        if (defined('ROOT_PATH')) {
            $settingsFile = ROOT_PATH . 'data/payment_methods.json';
            if (file_exists($settingsFile)) {
                $content = @file_get_contents($settingsFile);
                if ($content !== false) {
                    $savedSettings = json_decode($content, true);
                    if ($savedSettings && is_array($savedSettings)) {
                        // Merge saved settings with defaults
                        foreach ($defaultMethods as $id => &$method) {
                            if (isset($savedSettings[$id])) {
                                $method['enabled'] = isset($savedSettings[$id]['enabled']) ? $savedSettings[$id]['enabled'] : $method['enabled'];
                                $method['is_default'] = isset($savedSettings[$id]['is_default']) ? $savedSettings[$id]['is_default'] : $method['is_default'];
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail and use defaults
    }
    
    return $defaultMethods;
}

/**
 * Save payment methods settings
 */
function savePaymentMethodsSettings($settings) {
    $settingsFile = ROOT_PATH . 'data/payment_methods.json';
    return file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Get enabled payment methods only
 */
function getEnabledPaymentMethods() {
    return array_filter(getPaymentMethods(), function($method) {
        return $method['enabled'];
    });
}

/**
 * Get default payment method
 */
function getDefaultPaymentMethod() {
    $methods = getPaymentMethods();
    foreach ($methods as $method) {
        if ($method['is_default'] && $method['enabled']) {
            return $method;
        }
    }
    // Fallback to first enabled
    $enabled = getEnabledPaymentMethods();
    return reset($enabled);
}

/**
 * Get payment method by ID
 */
function getPaymentMethod($id) {
    $methods = getPaymentMethods();
    return isset($methods[$id]) ? $methods[$id] : null;
}

/**
 * Render payment methods as HTML list for footer/display
 * @param string $style - 'list' (default), 'icons', 'inline', 'full'
 * @return string HTML
 */
function renderPaymentMethodsHTML($style = 'list') {
    $methods = getEnabledPaymentMethods();
    $html = '';
    
    switch ($style) {
        case 'inline':
            // Compact inline display
            $items = [];
            foreach ($methods as $method) {
                $items[] = $method['icon'] . ' ' . $method['name'];
            }
            $html = implode(' • ', $items);
            break;
            
        case 'icons':
            // Icons only
            $html = '<div class="payment-icons">';
            foreach ($methods as $method) {
                $html .= '<span class="payment-icon" title="' . htmlspecialchars($method['name']) . '">' . $method['icon'] . '</span>';
            }
            $html .= '</div>';
            break;
            
        case 'full':
            // Full cards with descriptions
            $html = '<div class="payment-methods-grid">';
            foreach ($methods as $method) {
                $html .= '<div class="payment-method-card">';
                $html .= '<span class="payment-method-icon">' . $method['icon'] . '</span>';
                $html .= '<div class="payment-method-info">';
                $html .= '<strong>' . htmlspecialchars($method['name']) . '</strong>';
                $html .= '<small>' . htmlspecialchars($method['description']) . '</small>';
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
            break;
            
        case 'select':
            // For checkout forms
            $html = '<div class="payment-methods-select">';
            foreach ($methods as $method) {
                $checked = $method['is_default'] ? 'checked' : '';
                $html .= '<label class="payment-option">';
                $html .= '<input type="radio" name="payment_method" value="' . $method['id'] . '" ' . $checked . '>';
                $html .= '<span class="payment-option-content">';
                $html .= '<span class="payment-icon">' . $method['icon'] . '</span>';
                $html .= '<span class="payment-name">' . htmlspecialchars($method['name']) . '</span>';
                $html .= '</span>';
                $html .= '</label>';
            }
            $html .= '</div>';
            break;
            
        case 'list':
        default:
            // Simple list for footers
            $html = '';
            foreach ($methods as $method) {
                $html .= '<li>' . $method['icon'] . ' ' . htmlspecialchars($method['name']) . '</li>';
            }
            break;
    }
    
    return $html;
}

/**
 * Get payment methods summary text
 * For single line display
 */
function getPaymentMethodsSummary() {
    $methods = getEnabledPaymentMethods();
    $names = array_map(function($m) {
        return $m['name'];
    }, $methods);
    
    if (count($names) > 2) {
        $last = array_pop($names);
        return implode('، ', $names) . ' و' . $last;
    }
    
    return implode(' و ', $names);
}

/**
 * Get payment info text with icon
 * For product pages and cart
 */
function getPaymentInfoText() {
    $methods = getEnabledPaymentMethods();
    $icons = array_map(function($m) {
        return $m['icon'];
    }, $methods);
    return implode(' ', $icons) . ' ' . getPaymentMethodsSummary() . ' - آمن 100%';
}

// ============ REVIEW FUNCTIONS ============

/**
 * Initialize reviews table if not exists
 */
function initReviewsTable() {
    if (!isDbConnected()) return false;
    
    try {
        $pdo = db();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS reviews (
                id INT AUTO_INCREMENT PRIMARY KEY,
                product_id INT NOT NULL,
                customer_name VARCHAR(100) DEFAULT 'عميل',
                rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
                comment TEXT,
                is_visible TINYINT(1) DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_product_id (product_id),
                INDEX idx_is_visible (is_visible)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Add a new review
 */
function addReview($productId, $rating, $comment = '', $customerName = '') {
    if (!isDbConnected()) return false;
    
    // Ensure table exists
    initReviewsTable();
    
    try {
        $name = !empty($customerName) ? $customerName : 'عميل';
        $rating = max(1, min(5, intval($rating)));
        
        $stmt = db()->prepare("
            INSERT INTO reviews (product_id, customer_name, rating, comment, is_visible, created_at)
            VALUES (?, ?, ?, ?, 1, NOW())
        ");
        return $stmt->execute([$productId, $name, $rating, $comment]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get reviews for a product
 */
function getProductReviews($productId, $visibleOnly = true) {
    if (!isDbConnected()) return [];
    
    // Ensure table exists
    initReviewsTable();
    
    try {
        $sql = "SELECT * FROM reviews WHERE product_id = ?";
        if ($visibleOnly) {
            $sql .= " AND is_visible = 1";
        }
        $sql .= " ORDER BY created_at DESC";
        
        $stmt = db()->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get all reviews (for admin)
 */
function getAllReviews($filters = []) {
    if (!isDbConnected()) return [];
    
    // Ensure table exists
    initReviewsTable();
    
    try {
        $sql = "
            SELECT r.*, p.name as product_name, p.images as product_images
            FROM reviews r
            LEFT JOIN products p ON r.product_id = p.id
            WHERE 1=1
        ";
        $params = [];
        
        if (isset($filters['is_visible'])) {
            $sql .= " AND r.is_visible = ?";
            $params[] = $filters['is_visible'];
        }
        
        if (!empty($filters['product_id'])) {
            $sql .= " AND r.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        $sql .= " ORDER BY r.created_at DESC";
        
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $reviews = $stmt->fetchAll();
        
        // Decode product images
        foreach ($reviews as &$review) {
            if (!empty($review['product_images'])) {
                $review['product_images'] = json_decode($review['product_images'], true);
            } else {
                $review['product_images'] = [];
            }
        }
        
        return $reviews;
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get review by ID
 */
function getReview($id) {
    if (!isDbConnected()) return null;
    
    try {
        $stmt = db()->prepare("SELECT * FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Toggle review visibility
 */
function toggleReviewVisibility($id) {
    if (!isDbConnected()) return false;
    
    try {
        $stmt = db()->prepare("UPDATE reviews SET is_visible = NOT is_visible WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Delete a review
 */
function deleteReview($id) {
    if (!isDbConnected()) return false;
    
    try {
        $stmt = db()->prepare("DELETE FROM reviews WHERE id = ?");
        return $stmt->execute([$id]);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get product rating statistics
 */
function getProductRatingStats($productId) {
    if (!isDbConnected()) {
        return ['average' => 0, 'count' => 0, 'distribution' => [1=>0, 2=>0, 3=>0, 4=>0, 5=>0]];
    }
    
    // Ensure table exists
    initReviewsTable();
    
    try {
        // Get average and count
        $stmt = db()->prepare("
            SELECT AVG(rating) as average, COUNT(*) as count 
            FROM reviews 
            WHERE product_id = ? AND is_visible = 1
        ");
        $stmt->execute([$productId]);
        $stats = $stmt->fetch();
        
        // Get distribution
        $stmt = db()->prepare("
            SELECT rating, COUNT(*) as count 
            FROM reviews 
            WHERE product_id = ? AND is_visible = 1
            GROUP BY rating
        ");
        $stmt->execute([$productId]);
        $dist = $stmt->fetchAll();
        
        $distribution = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
        foreach ($dist as $d) {
            $distribution[$d['rating']] = $d['count'];
        }
        
        return [
            'average' => round($stats['average'] ?: 0, 1),
            'count' => intval($stats['count']),
            'distribution' => $distribution
        ];
    } catch (Exception $e) {
        return ['average' => 0, 'count' => 0, 'distribution' => [1=>0, 2=>0, 3=>0, 4=>0, 5=>0]];
    }
}

/**
 * Count total reviews
 */
function countReviews($visibleOnly = false) {
    if (!isDbConnected()) return 0;
    
    // Ensure table exists
    initReviewsTable();
    
    try {
        $sql = "SELECT COUNT(*) FROM reviews";
        if ($visibleOnly) {
            $sql .= " WHERE is_visible = 1";
        }
        $stmt = db()->query($sql);
        return $stmt->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Generate star rating HTML
 */
function generateStarsHTML($rating, $interactive = false, $size = 'normal') {
    $rating = floatval($rating);
    $sizeClass = $size === 'small' ? 'stars-sm' : ($size === 'large' ? 'stars-lg' : '');
    
    $html = '<div class="stars-display ' . $sizeClass . '">';
    
    for ($i = 1; $i <= 5; $i++) {
        if ($rating >= $i) {
            $html .= '<span class="star filled">★</span>';
        } elseif ($rating >= $i - 0.5) {
            $html .= '<span class="star half">★</span>';
        } else {
            $html .= '<span class="star empty">☆</span>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Format review date in Arabic
 */
function formatReviewDate($date) {
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'الآن';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return 'قبل ' . $mins . ' دقيقة';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return 'قبل ' . $hours . ' ساعة';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return 'قبل ' . $days . ' يوم';
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return 'قبل ' . $weeks . ' أسبوع';
    } else {
        return formatDateTime($date, 'date');
    }
}

// ============ SALES ANALYTICS FUNCTIONS ============

/**
 * Get confirmed order statuses (orders that count towards sales)
 */
function getConfirmedStatuses() {
    return ['confirmed', 'processing', 'shipped', 'delivered'];
}

/**
 * Get best selling products based on REAL confirmed orders
 * @param int $limit Number of products to return
 * @return array Products sorted by sales count
 */
function getBestSellingProducts($limit = 8) {
    if (!isDbConnected()) return [];
    
    try {
        $statuses = getConfirmedStatuses();
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        
        $sql = "SELECT 
                    p.*,
                    COALESCE(SUM(oi.quantity), 0) as total_sold
                FROM products p
                LEFT JOIN order_items oi ON p.product_id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ($placeholders)
                WHERE p.status != 'hidden'
                GROUP BY p.id
                HAVING total_sold > 0
                ORDER BY total_sold DESC
                LIMIT ?";
        
        $params = array_merge($statuses, [$limit]);
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // Decode JSON images
        foreach ($products as &$product) {
            if (isset($product['images']) && is_string($product['images'])) {
                $product['images'] = json_decode($product['images'], true);
            }
        }
        
        return $products;
    } catch (Exception $e) {
        error_log("getBestSellingProducts error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get sales summary statistics
 * @param int $year Optional year filter (null = all time)
 * @param int $month Optional month filter (0 = all months)
 * @return array Sales summary
 */
function getSalesSummary($year = null, $month = 0) {
    if (!isDbConnected()) return [
        'total_revenue' => 0,
        'total_orders' => 0,
        'total_items_sold' => 0,
        'avg_order_value' => 0,
        'top_category' => null,
        'top_product' => null,
        'pending_orders' => 0,
        'growth_percentage' => 0
    ];
    
    try {
        $pdo = db();
        $statuses = getConfirmedStatuses();
        $statusPlaceholders = "'" . implode("','", $statuses) . "'";
        
        // Build date conditions for different table contexts
        $orderDateCondition = "";
        $joinDateCondition = "";
        $params = [];
        $joinParams = [];
        
        if ($year !== null) {
            $orderDateCondition = " AND YEAR(created_at) = ?";
            $joinDateCondition = " AND YEAR(o.created_at) = ?";
            $params[] = $year;
            $joinParams[] = $year;
            if ($month > 0) {
                $orderDateCondition .= " AND MONTH(created_at) = ?";
                $joinDateCondition .= " AND MONTH(o.created_at) = ?";
                $params[] = $month;
                $joinParams[] = $month;
            }
        }
        
        // Total revenue and orders (confirmed only)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as total_orders, 
                   COALESCE(SUM(total), 0) as total_revenue,
                   COALESCE(AVG(total), 0) as avg_order_value
            FROM orders 
            WHERE status IN ($statusPlaceholders) $orderDateCondition
        ");
        $stmt->execute($params);
        $totals = $stmt->fetch();
        
        // Total items sold (confirmed orders only)
        $stmt = $pdo->prepare("
            SELECT COALESCE(SUM(oi.quantity), 0) as total_items
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status IN ($statusPlaceholders) $joinDateCondition
        ");
        $stmt->execute($joinParams);
        $items = $stmt->fetch();
        
        // Pending orders count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as pending_count
            FROM orders 
            WHERE status = 'pending' $orderDateCondition
        ");
        $stmt->execute($params);
        $pending = $stmt->fetch();
        
        // Top category
        $stmt = $pdo->prepare("
            SELECT p.category, SUM(oi.quantity) as sold
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            LEFT JOIN products p ON (oi.product_id = p.product_id OR oi.product_id = p.id)
            WHERE o.status IN ($statusPlaceholders) $joinDateCondition
            GROUP BY p.category
            ORDER BY sold DESC
            LIMIT 1
        ");
        $stmt->execute($joinParams);
        $topCat = $stmt->fetch();
        
        // Top product
        $stmt = $pdo->prepare("
            SELECT oi.product_name, SUM(oi.quantity) as sold
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status IN ($statusPlaceholders) $joinDateCondition
            GROUP BY oi.product_id, oi.product_name
            ORDER BY sold DESC
            LIMIT 1
        ");
        $stmt->execute($joinParams);
        $topProd = $stmt->fetch();
        
        // Calculate growth percentage (compare with previous period)
        $growth = 0;
        if ($year !== null) {
            $prevParams = [];
            if ($month > 0) {
                $prevMonth = $month - 1;
                $prevYear = $year;
                if ($prevMonth < 1) {
                    $prevMonth = 12;
                    $prevYear--;
                }
                $prevDateCondition = " AND YEAR(created_at) = ? AND MONTH(created_at) = ?";
                $prevParams = [$prevYear, $prevMonth];
            } else {
                $prevDateCondition = " AND YEAR(created_at) = ?";
                $prevParams = [$year - 1];
            }
            
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(total), 0) as prev_revenue
                FROM orders 
                WHERE status IN ($statusPlaceholders) $prevDateCondition
            ");
            $stmt->execute($prevParams);
            $prev = $stmt->fetch();
            
            if ($prev['prev_revenue'] > 0) {
                $growth = round((($totals['total_revenue'] - $prev['prev_revenue']) / $prev['prev_revenue']) * 100, 1);
            }
        }
        
        return [
            'total_revenue' => (int)$totals['total_revenue'],
            'total_orders' => (int)$totals['total_orders'],
            'total_items_sold' => (int)$items['total_items'],
            'avg_order_value' => round($totals['avg_order_value'], 0),
            'pending_orders' => (int)$pending['pending_count'],
            'top_category' => $topCat ? $topCat['category'] : null,
            'top_product' => $topProd ? $topProd['product_name'] : null,
            'growth_percentage' => $growth
        ];
    } catch (Exception $e) {
        error_log("getSalesSummary error: " . $e->getMessage());
        return [
            'total_revenue' => 0,
            'total_orders' => 0,
            'total_items_sold' => 0,
            'avg_order_value' => 0,
            'pending_orders' => 0,
            'top_category' => null,
            'top_product' => null,
            'growth_percentage' => 0
        ];
    }
}

/**
 * Get sales by category with product breakdown
 * Uses order_items as the single source of truth
 * @return array Categories with their sales data
 */
function getSalesByCategory() {
    if (!isDbConnected()) return [];
    
    try {
        $pdo = db();
        $statuses = getConfirmedStatuses();
        $statusPlaceholders = "'" . implode("','", $statuses) . "'";
        
        // Get sales data directly from order_items for confirmed orders
        $sql = "SELECT 
                    oi.product_id,
                    oi.product_name,
                    SUM(oi.quantity) as items_sold,
                    SUM(oi.quantity * oi.price) as revenue
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ($statusPlaceholders)
                GROUP BY oi.product_id, oi.product_name";
        
        $stmt = $pdo->query($sql);
        $salesData = $stmt->fetchAll();
        
        // Get all products to map categories
        $products = getProducts(['exclude_hidden' => false]);
        $productMap = [];
        foreach ($products as $p) {
            // Map by product_id (string) and by id (int)
            if (!empty($p['product_id'])) {
                $productMap[$p['product_id']] = $p;
            }
            $productMap[$p['id']] = $p;
        }
        
        // Aggregate by category
        $categoryData = [];
        foreach ($salesData as $sale) {
            $product = null;
            // Try to find product by product_id
            if (isset($productMap[$sale['product_id']])) {
                $product = $productMap[$sale['product_id']];
            }
            
            $category = $product ? ($product['category'] ?? 'other') : 'other';
            
            if (!isset($categoryData[$category])) {
                $categoryData[$category] = [
                    'category' => $category,
                    'order_count' => 0,
                    'items_sold' => 0,
                    'revenue' => 0
                ];
            }
            
            $categoryData[$category]['items_sold'] += (int)$sale['items_sold'];
            $categoryData[$category]['revenue'] += (float)$sale['revenue'];
        }
        
        // Sort by items_sold descending
        usort($categoryData, function($a, $b) {
            return $b['items_sold'] - $a['items_sold'];
        });
        
        return array_values($categoryData);
    } catch (Exception $e) {
        error_log("getSalesByCategory error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get sales for products - uses order_items as single source of truth
 * @param string $category Category slug (optional filter)
 * @return array Products with sales data
 */
function getSalesByProduct($category = null) {
    if (!isDbConnected()) return [];
    
    try {
        $pdo = db();
        $statuses = getConfirmedStatuses();
        $statusPlaceholders = "'" . implode("','", $statuses) . "'";
        
        // Get ALL sales from order_items for confirmed orders 
        // This is the single source of truth
        $sql = "SELECT 
                    oi.product_id,
                    oi.product_name,
                    SUM(oi.quantity) as items_sold,
                    SUM(oi.quantity * oi.price) as revenue
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.id
                WHERE o.status IN ($statusPlaceholders)
                GROUP BY oi.product_id, oi.product_name
                ORDER BY items_sold DESC";
        
        $stmt = $pdo->query($sql);
        $salesData = $stmt->fetchAll();
        
        // Create a map of product_id to sales
        $salesMap = [];
        foreach ($salesData as $sale) {
            $salesMap[$sale['product_id']] = [
                'items_sold' => (int)$sale['items_sold'],
                'revenue' => (float)$sale['revenue'],
                'product_name' => $sale['product_name']
            ];
        }
        
        // Get all products
        $products = getProducts(['exclude_hidden' => true]);
        
        // Filter by category if specified
        if ($category) {
            $products = array_filter($products, function($p) use ($category) {
                return ($p['category'] ?? '') === $category;
            });
        }
        
        // Merge product data with sales data
        $result = [];
        foreach ($products as $product) {
            $productId = $product['product_id'] ?? $product['id'];
            $sales = isset($salesMap[$productId]) ? $salesMap[$productId] : null;
            
            // Also check by numeric id
            if (!$sales && isset($salesMap[$product['id']])) {
                $sales = $salesMap[$product['id']];
            }
            
            $result[] = [
                'id' => $product['id'],
                'product_id' => $product['product_id'] ?? $product['id'],
                'name' => $product['name'],
                'category' => $product['category'] ?? '',
                'images' => $product['images'] ?? [],
                'price' => $product['price'] ?? 0,
                'items_sold' => $sales ? $sales['items_sold'] : 0,
                'revenue' => $sales ? $sales['revenue'] : 0
            ];
        }
        
        // Sort by items_sold descending
        usort($result, function($a, $b) {
            return $b['items_sold'] - $a['items_sold'];
        });
        
        return $result;
    } catch (Exception $e) {
        error_log("getSalesByProduct error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get monthly sales data for charts
 * @param int $months Number of months to get
 * @return array Monthly data
 */
function getMonthlySales($months = 12) {
    if (!isDbConnected()) return [];
    
    try {
        $statuses = getConfirmedStatuses();
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    COUNT(*) as orders,
                    SUM(total) as revenue
                FROM orders 
                WHERE status IN ($placeholders)
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                ORDER BY month ASC";
        
        $params = array_merge($statuses, [$months]);
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log("getMonthlySales error: " . $e->getMessage());
        return [];
    }
}

/**
 * Search products with sales data
 * @param string $query Search query
 * @return array Products matching search with sales
 */
function searchProductsWithSales($query) {
    if (!isDbConnected() || empty($query)) return [];
    
    try {
        $statuses = getConfirmedStatuses();
        $placeholders = implode(',', array_fill(0, count($statuses), '?'));
        $search = '%' . $query . '%';
        
        $sql = "SELECT 
                    p.id,
                    p.product_id,
                    p.name,
                    p.category,
                    p.images,
                    p.price,
                    COALESCE(SUM(oi.quantity), 0) as items_sold,
                    COALESCE(SUM(oi.quantity * oi.price), 0) as revenue
                FROM products p
                LEFT JOIN order_items oi ON p.product_id = oi.product_id
                LEFT JOIN orders o ON oi.order_id = o.id AND o.status IN ($placeholders)
                WHERE p.status != 'hidden'
                  AND (p.name LIKE ? OR p.category LIKE ?)
                GROUP BY p.id
                ORDER BY items_sold DESC
                LIMIT 50";
        
        $params = array_merge($statuses, [$search, $search]);
        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // Decode images
        foreach ($products as &$product) {
            if (isset($product['images']) && is_string($product['images'])) {
                $product['images'] = json_decode($product['images'], true);
            }
        }
        
        return $products;
    } catch (Exception $e) {
        error_log("searchProductsWithSales error: " . $e->getMessage());
        return [];
    }
}

