<?php
session_start();
require_once(__DIR__ . '/../models/Cart.php');
include(__DIR__ . '/../../config/config.php');
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$cart = new Cart($conn);

// Set JSON response headers
header('Content-Type: application/json');

// Function to send JSON response
function sendJsonResponse($status, $message = '', $data = []) {
    $response = [
        'status' => $status,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    echo json_encode($response);
    exit;
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse('error', 'Bạn chưa đăng nhập.');
}

$customer_id = $_SESSION['user_id'];

// Handle GET request để đếm số lượng sản phẩm
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'count') {
    try {
        $cartItems = $cart->getCartByCustomer($customer_id);
        $totalItems = array_sum(array_column($cartItems, 'quantity'));
        
        sendJsonResponse('success', '', ['count' => $totalItems]);
    } catch (Exception $e) {
        error_log("Cart count error: " . $e->getMessage());
        sendJsonResponse('error', 'Có lỗi xảy ra khi đếm sản phẩm trong giỏ hàng.');
    }
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Validate input
    if (empty($action)) {
        sendJsonResponse('error', 'Hành động không được xác định.');
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 1);
    
    if (!$product_id) {
        sendJsonResponse('error', 'ID sản phẩm không hợp lệ.');
    }
    
    try {
        switch ($action) {
            case 'add':
                if ($quantity <= 0) {
                    sendJsonResponse('error', 'Số lượng phải lớn hơn 0.');
                }
                
                // Check product exists and get stock
                $productQuery = "SELECT stock_quantity FROM products WHERE product_id = ?";
                $stmt = $conn->prepare($productQuery);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $productData = $result->fetch_assoc();
                
                if (!$productData) {
                    sendJsonResponse('error', 'Sản phẩm không tồn tại.');
                }
                
                $stockQuantity = $productData['stock_quantity'];
                
                if ($stockQuantity <= 0) {
                    sendJsonResponse('error', 'Sản phẩm đã hết hàng.');
                }
                
                // Check existing quantity in cart
                $existing = $cart->searchProductInCart($customer_id, $product_id);
                $currentQuantityInCart = $existing ? $existing['quantity'] : 0;
                $newTotalQuantity = $currentQuantityInCart + $quantity;
                
                if ($newTotalQuantity > $stockQuantity) {
                    sendJsonResponse('error', "Số lượng yêu cầu vượt quá tồn kho. Tối đa: $stockQuantity, hiện có trong giỏ: $currentQuantityInCart");
                }
                
                if ($existing) {
                    $cart->updateCart($customer_id, $product_id, $newTotalQuantity);
                    sendJsonResponse('success', 'Cập nhật số lượng thành công.');
                } else {
                    $cart->createCart($customer_id, $product_id, $quantity);
                    sendJsonResponse('success', 'Thêm vào giỏ hàng thành công.');
                }
                break;
                
            case 'update':
                if ($quantity <= 0) {
                    sendJsonResponse('error', 'Số lượng phải lớn hơn 0.');
                }
                
                // Check product exists and get stock
                $productQuery = "SELECT stock_quantity FROM products WHERE product_id = ?";
                $stmt = $conn->prepare($productQuery);
                $stmt->bind_param("i", $product_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $productData = $result->fetch_assoc();
                
                if (!$productData) {
                    sendJsonResponse('error', 'Sản phẩm không tồn tại.');
                }
                
                if ($quantity > $productData['stock_quantity']) {
                    sendJsonResponse('error', "Số lượng yêu cầu vượt quá tồn kho. Tối đa: " . $productData['stock_quantity']);
                }
                
                // Check if item exists in cart
                $existing = $cart->searchProductInCart($customer_id, $product_id);
                if (!$existing) {
                    sendJsonResponse('error', 'Sản phẩm không có trong giỏ hàng.');
                }
                
                $cart->updateCart($customer_id, $product_id, $quantity);
                sendJsonResponse('success', 'Cập nhật số lượng thành công.');
                break;
                
            case 'delete':
                // Check if item exists in cart
                $existing = $cart->searchProductInCart($customer_id, $product_id);
                if (!$existing) {
                    sendJsonResponse('error', 'Sản phẩm không có trong giỏ hàng.');
                }
                
                $cart->deleteCart($customer_id, $product_id);
                sendJsonResponse('success', 'Xóa khỏi giỏ hàng thành công.');
                break;
                
            case 'get_count':
                $cartItems = $cart->getCartByCustomer($customer_id);
                $totalItems = array_sum(array_column($cartItems, 'quantity'));
                
                sendJsonResponse('success', '', ['count' => $totalItems]);
                break;
                
            case 'clear':
                // Clear entire cart
                $cartItems = $cart->getCartByCustomer($customer_id);
                foreach ($cartItems as $item) {
                    $cart->deleteCart($customer_id, $item['product_id']);
                }
                
                sendJsonResponse('success', 'Đã xóa toàn bộ giỏ hàng.');
                break;
                
            default:
                sendJsonResponse('error', 'Hành động không hợp lệ.');
        }
        
    } catch (Exception $e) {
        error_log("Cart operation error: " . $e->getMessage());
        sendJsonResponse('error', 'Có lỗi xảy ra khi xử lý giỏ hàng. Vui lòng thử lại.');
    }
} else {
    sendJsonResponse('error', 'Phương thức request không được hỗ trợ.');
}
?>