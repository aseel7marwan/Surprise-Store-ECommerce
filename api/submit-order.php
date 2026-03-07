<?php
/**
 * API Endpoint: Submit Order
 * Saves order to MySQL database and returns order number for Telegram message
 */

// Disable error display and start output buffering
error_reporting(0);
ini_set('display_errors', 0);
ob_start();

header('Content-Type: application/json; charset=utf-8');

// Clean any output that might have been generated
ob_clean();

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/logging.php';

// Clean again after includes
ob_clean();

// Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// SECURITY: Rate limiting for orders (max 10 per hour per IP)
if (isOrderRateLimited()) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'تجاوزت الحد المسموح للطلبات. حاول بعد ساعة.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Validate required fields
if (empty($input['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'السلة فارغة']);
    exit;
}

// Prepare order data
$orderData = [
    'customer_name' => sanitize(isset($input['customer_name']) ? $input['customer_name'] : ''),
    'customer_phone' => sanitize(isset($input['customer_phone']) ? $input['customer_phone'] : ''),
    'customer_city' => sanitize(isset($input['customer_city']) ? $input['customer_city'] : ''),
    'customer_area' => sanitize(isset($input['customer_area']) ? $input['customer_area'] : ''),
    'customer_address' => sanitize(isset($input['customer_address']) ? $input['customer_address'] : ''),
    'contact_method' => sanitize(isset($input['contact_method']) ? $input['contact_method'] : ''),
    'contact_value' => sanitize(isset($input['contact_value']) ? $input['contact_value'] : ''),
    'items' => $input['items'],
    'subtotal' => intval(isset($input['subtotal']) ? $input['subtotal'] : 0),
    'packaging_total' => intval(isset($input['packaging_total']) ? $input['packaging_total'] : 0),
    'delivery_fee' => intval(isset($input['delivery_fee']) ? $input['delivery_fee'] : 0),
    'total' => intval(isset($input['total']) ? $input['total'] : 0),
    'uploaded_images' => isset($input['uploaded_images']) ? $input['uploaded_images'] : [],
    'notes' => sanitize(isset($input['notes']) ? $input['notes'] : ''),
    'terms_consent' => isset($input['terms_consent']) ? ($input['terms_consent'] ? 1 : 0) : 0,
    'consent_timestamp' => isset($input['consent_timestamp']) ? sanitize($input['consent_timestamp']) : null
];

// Validate required fields
if (empty($orderData['customer_name'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'يرجى إدخال الاسم الكامل']);
    exit;
}

if (empty($orderData['customer_phone'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'يرجى إدخال رقم الهاتف']);
    exit;
}

if (empty($orderData['customer_city'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'يرجى اختيار المحافظة']);
    exit;
}

if (empty($orderData['customer_address'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'يرجى إدخال العنوان']);
    exit;
}

// ============ STOCK VALIDATION ============
// Check stock availability before processing order
if (isDbConnected()) {
    foreach ($orderData['items'] as $item) {
        $itemId = isset($item['id']) ? $item['id'] : null;
        if ($itemId) {
            $stmt = db()->prepare("SELECT stock, name, status FROM products WHERE id = ?");
            $stmt->execute([$itemId]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Check if product is sold out
                if ($product['status'] === 'sold' || $product['stock'] <= 0) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'error' => "عذراً، المنتج '{$product['name']}' غير متوفر حالياً"
                    ]);
                    exit;
                }
                
                // Check if requested quantity exceeds stock
                $requestedQty = isset($item['quantity']) ? intval($item['quantity']) : 1;
                if ($product['stock'] < $requestedQty) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false, 
                        'error' => "عذراً، المنتج '{$product['name']}' متوفر بكمية {$product['stock']} فقط"
                    ]);
                    exit;
                }
            }
        }
    }
}

// SECURITY: Recalculate totals server-side (don't trust client-side values)
$serverSubtotal = 0;
$serverPackagingTotal = 0;
if (isDbConnected()) {
    foreach ($orderData['items'] as &$item) {
        $itemId = isset($item['id']) ? $item['id'] : null;
        if ($itemId) {
            $stmt = db()->prepare("SELECT price, packaging_price, packaging_enabled, options FROM products WHERE id = ?");
            $stmt->execute([$itemId]);
            $product = $stmt->fetch();
            
            if ($product) {
                // Use server price, not client price
                $serverPrice = intval($product['price']);
                $quantity = intval($item['quantity'] ?? 1);
                
                // === BOX OPTIONS PRICE VERIFICATION ===
                $boxPrice = 0;
                $pOpts = !empty($product['options']) ? json_decode($product['options'], true) : [];
                if (!empty($item['selectedOptions']['box_selected']) && !empty($pOpts['box_options']['enabled'])) {
                    $boxIdx = $item['selectedOptions']['box_selected_idx'] ?? $item['selectedOptions']['box_selected'] ?? null;
                    if ($boxIdx !== null && isset($pOpts['box_options']['items'][$boxIdx])) {
                        $boxPrice = intval($pOpts['box_options']['items'][$boxIdx]['price'] ?? 0);
                        // Store server-verified box price back into item
                        $item['selectedOptions']['box_price'] = $boxPrice;
                    }
                }
                
                $serverSubtotal += ($serverPrice + $boxPrice) * $quantity;
                
                // Validate packaging
                if (!empty($item['selectedOptions']['packaging_selected']) && $product['packaging_enabled']) {
                    $serverPackagingTotal += intval($product['packaging_price']) * $quantity;
                }
                
                // Update item with correct price
                $item['price'] = $serverPrice + $boxPrice;
            }
        }
    }
    
    // ============ COUPON VALIDATION ============
    $serverDiscount = 0;
    $couponId = null;
    $couponCode = sanitize($input['coupon_code'] ?? '');
    
    if (!empty($couponCode)) {
        $couponResult = validateCoupon($couponCode, $serverSubtotal);
        if ($couponResult['valid']) {
            $serverDiscount = intval($couponResult['discount']);
            $couponId = $couponResult['coupon']['id'];
        }
    }

    // Update totals with server-calculated values
    $orderData['subtotal'] = $serverSubtotal;
    $orderData['packaging_total'] = $serverPackagingTotal;
    $orderData['discount'] = $serverDiscount;
    $orderData['coupon_code'] = !empty($couponCode) && $serverDiscount > 0 ? $couponCode : null;
    $orderData['total'] = $serverSubtotal + $serverPackagingTotal + $orderData['delivery_fee'] - $serverDiscount;
}

// Record order for rate limiting
recordOrder();

// Save order to database
$order = saveOrder($orderData);

if ($order) {
    // MERGE customer data for comprehensive notification
    $fullOrder = array_merge($order, [
        'customer_name' => $orderData['customer_name'],
        'customer_phone' => $orderData['customer_phone'],
        'customer_city' => $orderData['customer_city'],
        'customer_area' => $orderData['customer_area'],
        'customer_address' => $orderData['customer_address'],
        'contact_method' => $orderData['contact_method'],
        'contact_value' => $orderData['contact_value'],
        'notes' => $orderData['notes'],
        'subtotal' => $orderData['subtotal'],
        'packaging_total' => $orderData['packaging_total'],
        'discount' => $orderData['discount'],
        'coupon_code' => $orderData['coupon_code'],
        'delivery_fee' => $orderData['delivery_fee'],
        'terms_consent' => isset($orderData['terms_consent']) ? $orderData['terms_consent'] : 0
    ]);

    // Increment coupon usage if applicable
    if (!empty($couponId)) {
        useCoupon($couponId);
    }
    
    // Send Telegram notification (handles auto-discovery and detailed formatting)
    notifyNewOrder($fullOrder, $orderData['items']);
    
    // Generate message for customer to send via Telegram
    $settings = getSettings();
    $message = generateTelegramOrderMessage($order, $orderData, $settings);
    
    // Create Telegram link with pre-filled message
    $telegramUsername = isset($settings['telegram_order_username']) ? $settings['telegram_order_username'] : TELEGRAM_ORDER_USERNAME;
    $encodedMessage = urlencode($message);
    $telegramDmLink = "https://t.me/{$telegramUsername}?text={$encodedMessage}";
    
    // Log successful order
    logOrder('طلب جديد', $order['order_number'], [
        'customer' => $orderData['customer_name'],
        'city' => $orderData['customer_city'],
        'total' => $order['total'],
        'items_count' => count($orderData['items'])
    ]);
    
    echo json_encode([
        'success' => true,
        'order_number' => $order['order_number'],
        'order_id' => $order['id'],
        'message' => $message,
        'telegram_dm' => $telegramDmLink,
        'telegram_username' => $telegramUsername
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'فشل في حفظ الطلب']);
}

/**
 * Generate formatted message for Telegram DM
 */
function generateTelegramOrderMessage($order, $orderData, $settings) {
    $msg = "📦 طلب جديد من Surprise!\n";
    $msg .= "━━━━━━━━━━━━━━━━━━\n";
    $msg .= "🔢 رقم الطلب: {$order['order_number']}\n\n";
    
    $msg .= "🛒 المنتجات:\n";
    foreach ($orderData['items'] as $index => $item) {
        $itemTotal = $item['price'] * $item['quantity'];
        $msg .= ($index + 1) . ". {$item['name']} × {$item['quantity']}\n";
        $msg .= "   💰 " . number_format($itemTotal) . " د.ع\n";
        
        // Add selected options if present
        if (!empty($item['selectedOptions'])) {
            $opts = $item['selectedOptions'];
            $optParts = [];
            if (!empty($opts['color'])) $optParts[] = "اللون: {$opts['color']}";
            if (!empty($opts['size'])) $optParts[] = "الحجم: {$opts['size']}";
            if (!empty($opts['age'])) $optParts[] = "العمر: {$opts['age']}";
            if (!empty($opts['custom_text'])) $optParts[] = "النص: {$opts['custom_text']}";

            // مجموعات الخيارات المتعددة
            if (!empty($opts['color_groups']) && is_array($opts['color_groups'])) {
                foreach ($opts['color_groups'] as $groupLabel => $value) $optParts[] = "{$groupLabel}: {$value}";
            }
            if (!empty($opts['size_groups']) && is_array($opts['size_groups'])) {
                foreach ($opts['size_groups'] as $groupLabel => $value) $optParts[] = "{$groupLabel}: {$value}";
            }
            if (!empty($opts['age_groups']) && is_array($opts['age_groups'])) {
                foreach ($opts['age_groups'] as $groupLabel => $value) $optParts[] = "{$groupLabel}: {$value}";
            }

            // الحقول الإضافية
            if (!empty($opts['extra_fields']) && is_array($opts['extra_fields'])) {
                foreach ($opts['extra_fields'] as $field) {
                    if (!empty($field['label']) && !empty($field['value'])) {
                        $optParts[] = "{$field['label']}: {$field['value']}";
                    }
                }
            }
            
            if (!empty($optParts)) {
                $msg .= "   🎛️ " . implode(' | ', $optParts) . "\n";
            }
            
            // نظام بطاقات الرسائل المتعددة
            if (!empty($opts['gift_cards']) && is_array($opts['gift_cards'])) {
                foreach ($opts['gift_cards'] as $card) {
                    if (!empty($card['label']) && !empty($card['message'])) {
                        $label = htmlspecialchars($card['label'], ENT_QUOTES, 'UTF-8');
                        $cardMsg = htmlspecialchars($card['message'], ENT_QUOTES, 'UTF-8');
                        $msg .= "   🎁 $label: \"$cardMsg\"\n";
                    }
                }
            } elseif (isset($opts['gift_card_enabled'])) {
                // Compatibility for older formats
                $status = $opts['gift_card_enabled'] ? 'نعم' : 'لا';
                $msg .= "   🎁 بطاقة رسالة: {$status}\n";
                if ($opts['gift_card_enabled'] && !empty($opts['gift_card_message'])) {
                    $giftMsg = htmlspecialchars($opts['gift_card_message'], ENT_QUOTES, 'UTF-8');
                    $msg .= "   📝 نص الرسالة: \"{$giftMsg}\"\n";
                }
            } elseif (!empty($opts['gift_card_message'])) {
                $giftMsg = htmlspecialchars($opts['gift_card_message'], ENT_QUOTES, 'UTF-8');
                $msg .= "   💌 رسالة البطاقة: \"{$giftMsg}\"\n";
            }
            
            // إضافة التغليف
            if (!empty($opts['packaging_selected'])) {
                $pPrice = intval($opts['packaging_price'] ?? 0);
                $pDesc = !empty($opts['packaging_description']) ? " ({$opts['packaging_description']})" : "";
                $msg .= "   🎁 التغليف: نعم{$pDesc} (+ " . number_format($pPrice) . " د.ع)\n";
            } else {
                $msg .= "   🎁 التغليف: لا\n";
            }
        }
        
        if (!empty($item['hasCustomImage'])) {
            // التحقق من وجود صور متعددة
            if (!empty($item['customImages']) && is_array($item['customImages'])) {
                $imgCount = count($item['customImages']);
                $msg .= "   📷 {$imgCount} صور مرفقة للطباعة\n";
            } else {
                $msg .= "   📷 صورة مرفقة للطباعة\n";
            }
        }
    }
    
    $msg .= "\n━━━━━━━━━━━━━━━━━━\n";
    $msg .= "📋 بيانات التوصيل:\n";
    
    if (!empty($orderData['customer_name'])) {
        $msg .= "👤 الاسم: {$orderData['customer_name']}\n";
    }
    if (!empty($orderData['customer_phone'])) {
        $msg .= "📱 الهاتف: {$orderData['customer_phone']}\n";
    }
    $msg .= "📍 المحافظة: {$orderData['customer_city']}\n";
    if (!empty($orderData['customer_area'])) {
        $msg .= "🏙️ المنطقة/الحي: {$orderData['customer_area']}\n";
    }
    $msg .= "🏠 العنوان: {$orderData['customer_address']}\n";
    
    $msg .= "\n━━━━━━━━━━━━━━━━━━\n";
    $msg .= "� المنتجات: " . number_format($orderData['subtotal']) . " د.ع\n";
    if (!empty($orderData['packaging_total']) && $orderData['packaging_total'] > 0) {
        $msg .= "🎁 إجمالي التغليف: " . number_format($orderData['packaging_total']) . " د.ع\n";
    }
    $msg .= "�🚚 التوصيل: " . number_format($orderData['delivery_fee']) . " د.ع\n";
    if (!empty($orderData['discount']) && $orderData['discount'] > 0) {
        $msg .= "🎁 الخصم: -" . number_format($orderData['discount']) . " د.ع (" . ($orderData['coupon_code'] ?? '') . ")\n";
    }
    $msg .= "💵 الإجمالي النهائي: " . number_format($order['total']) . " د.ع\n";
    
    if (!empty($orderData['notes'])) {
        $msg .= "\n📝 ملاحظات: {$orderData['notes']}\n";
    }
    
    // إضافة وسيلة التواصل
    if (!empty($orderData['contact_method']) && !empty($orderData['contact_value'])) {
        $methodLabel = '';
        switch($orderData['contact_method']) {
            case 'instagram': $methodLabel = 'انستقرام'; break;
            case 'whatsapp': $methodLabel = 'واتساب'; break;
            case 'telegram': $methodLabel = 'تيليجرام'; break;
            default: $methodLabel = $orderData['contact_method'];
        }
        $msg .= "\n📱 وسيلة التواصل: {$methodLabel}\n";
        $msg .= "🔗 المعرّف/الرقم: {$orderData['contact_value']}\n";
    }
    
    $msg .= "\n✨ شكراً لاختياركم Surprise!";
    
    return $msg;
}
