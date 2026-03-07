<?php
/**
 * Reviews API - إضافة وإدارة التقييمات
 */
header('Content-Type: application/json; charset=utf-8');

require_once '../includes/config.php';
require_once '../includes/functions.php';

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch ($method) {
        case 'POST':
            // Add new review
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input)) {
                $input = $_POST;
            }
            
            $productId = isset($input['product_id']) ? intval($input['product_id']) : 0;
            $rating = isset($input['rating']) ? intval($input['rating']) : 0;
            $comment = isset($input['comment']) ? trim($input['comment']) : '';
            $customerName = isset($input['customer_name']) ? trim($input['customer_name']) : '';
            
            // Validation
            if (!$productId) {
                echo json_encode(['success' => false, 'error' => 'معرف المنتج مطلوب']);
                exit;
            }
            
            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'error' => 'التقييم يجب أن يكون بين 1 و 5']);
                exit;
            }
            
            // Check product exists
            $product = getProduct($productId);
            if (!$product) {
                echo json_encode(['success' => false, 'error' => 'المنتج غير موجود']);
                exit;
            }
            
            // Add review
            $result = addReview($productId, $rating, $comment, $customerName);
            
            if ($result) {
                // Get updated stats
                $stats = getProductRatingStats($productId);
                echo json_encode([
                    'success' => true, 
                    'message' => 'تم إضافة تقييمك بنجاح! 🎉',
                    'stats' => $stats
                ]);
            } else {
                echo json_encode(['success' => false, 'error' => 'حدث خطأ أثناء إضافة التقييم']);
            }
            break;
            
        case 'GET':
            // Get reviews for a product
            $productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;
            
            if (!$productId) {
                echo json_encode(['success' => false, 'error' => 'معرف المنتج مطلوب']);
                exit;
            }
            
            $reviews = getProductReviews($productId);
            $stats = getProductRatingStats($productId);
            
            echo json_encode([
                'success' => true,
                'reviews' => $reviews,
                'stats' => $stats
            ]);
            break;
            
        case 'DELETE':
            // Delete review (admin only)
            // Session already started in config.php with proper settings
            if (empty($_SESSION['admin_logged_in'])) {
                echo json_encode(['success' => false, 'error' => 'غير مصرح']);
                exit;
            }
            
            $reviewId = isset($_GET['id']) ? intval($_GET['id']) : 0;
            
            if (!$reviewId) {
                echo json_encode(['success' => false, 'error' => 'معرف التقييم مطلوب']);
                exit;
            }
            
            $result = deleteReview($reviewId);
            echo json_encode(['success' => $result]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'حدث خطأ: ' . $e->getMessage()]);
}
