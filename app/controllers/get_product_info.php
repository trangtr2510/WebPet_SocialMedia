<?php
include(__DIR__ . '/../../config/config.php'); // $conn được khởi tạo ở đây
require_once(__DIR__ . '/../models/Product.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// Kiểm tra method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }
    
    // Sử dụng kết nối $conn từ config.php
    $query = "SELECT product_id, product_name, stock_quantity FROM products WHERE product_id = ? AND is_active = 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    $product = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_product_info.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
