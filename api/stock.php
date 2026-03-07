<?php
/**
 * Stock Management API
 * إدارة المخزون - للأدمن فقط
 */

require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';

// Initialize session
initSecureSession();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Check admin auth
if (!validateAdminSession()) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'error' => 'Unauthorized'));
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'error' => 'Method not allowed'));
    exit;
}

// Get action
$action = isset($_POST['action']) ? $_POST['action'] : '';
$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if (!$productId) {
    echo json_encode(array('success' => false, 'error' => 'Product ID required'));
    exit;
}

try {
    $pdo = db();
    
    switch ($action) {
        case 'get':
            // Get current stock
            $stmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE id = ?");
            $stmt->execute(array($productId));
            $product = $stmt->fetch();
            
            if ($product) {
                echo json_encode(array(
                    'success' => true,
                    'stock' => intval($product['stock']),
                    'product_name' => $product['name']
                ));
            } else {
                echo json_encode(array('success' => false, 'error' => 'Product not found'));
            }
            break;
            
        case 'set':
            // Set stock to specific value
            $newStock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
            $newStock = max(0, $newStock); // Prevent negative stock
            
            $stmt = $pdo->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute(array($newStock, $productId));
            
            // Auto-update status based on stock
            if ($newStock == 0) {
                $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ? AND status = 'available'")->execute(array($productId));
            } else {
                $pdo->prepare("UPDATE products SET status = 'available' WHERE id = ? AND status = 'sold'")->execute(array($productId));
            }
            
            echo json_encode(array(
                'success' => true,
                'stock' => $newStock,
                'message' => 'تم تحديث المخزون'
            ));
            break;
            
        case 'add':
            // Add to stock
            $amount = isset($_POST['amount']) ? intval($_POST['amount']) : 1;
            $amount = max(1, $amount);
            
            $stmt = $pdo->prepare("UPDATE products SET stock = stock + ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute(array($amount, $productId));
            
            // Get new stock
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute(array($productId));
            $newStock = intval($stmt->fetchColumn());
            
            // Update status if needed
            if ($newStock > 0) {
                $pdo->prepare("UPDATE products SET status = 'available' WHERE id = ? AND status = 'sold'")->execute(array($productId));
            }
            
            echo json_encode(array(
                'success' => true,
                'stock' => $newStock,
                'added' => $amount,
                'message' => "تمت إضافة $amount قطعة"
            ));
            break;
            
        case 'subtract':
            // Subtract from stock
            $amount = isset($_POST['amount']) ? intval($_POST['amount']) : 1;
            $amount = max(1, $amount);
            
            // Get current stock first
            $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt->execute(array($productId));
            $currentStock = intval($stmt->fetchColumn());
            
            // Calculate new stock (minimum 0)
            $newStock = max(0, $currentStock - $amount);
            $actualSubtracted = $currentStock - $newStock;
            
            $stmt = $pdo->prepare("UPDATE products SET stock = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute(array($newStock, $productId));
            
            // Update status if out of stock
            if ($newStock == 0) {
                $pdo->prepare("UPDATE products SET status = 'sold' WHERE id = ? AND status = 'available'")->execute(array($productId));
            }
            
            echo json_encode(array(
                'success' => true,
                'stock' => $newStock,
                'subtracted' => $actualSubtracted,
                'message' => "تم خصم $actualSubtracted قطعة"
            ));
            break;
            
        default:
            echo json_encode(array('success' => false, 'error' => 'Invalid action'));
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error' => 'Database error: ' . $e->getMessage()));
}
?>
