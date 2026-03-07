<?php
/**
 * API Endpoint: Validate Coupon
 */

header('Content-Type: application/json; charset=utf-8');

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Only POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

$code = sanitize(isset($input['code']) ? $input['code'] : '');
$subtotal = intval(isset($input['subtotal']) ? $input['subtotal'] : 0);

if (empty($code)) {
    echo json_encode(['success' => false, 'error' => 'يرجى إدخال كود الخصم']);
    exit;
}

if ($subtotal <= 0) {
    echo json_encode(['success' => false, 'error' => 'قيمة الطلب غير صحيحة']);
    exit;
}

// Validate coupon
$result = validateCoupon($code, $subtotal);

if ($result['valid']) {
    echo json_encode([
        'success' => true,
        'discount' => $result['discount'],
        'message' => $result['message'],
        'coupon_id' => $result['coupon']['id'],
        'new_total' => $subtotal - $result['discount']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'error' => $result['error']
    ]);
}
