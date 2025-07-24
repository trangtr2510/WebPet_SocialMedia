<?php
include(__DIR__ . '/../../config/config.php');
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
    $importQuantity = isset($_POST['import_quantity']) ? (int)$_POST['import_quantity'] : 0;
    $note = isset($_POST['note']) ? trim($_POST['note']) : '';

    // Validate dữ liệu
    if ($productId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    if ($importQuantity <= 0) {
        echo json_encode(['success' => false, 'message' => 'Import quantity must be greater than 0']);
        exit;
    }

    // Dùng kết nối $conn từ config
    $db = $conn; // $conn được khai báo trong config.php
    $product = new Product($db);

    // Lấy số lượng hiện tại
    $currentStock = $product->getStockQuantity($productId);
    if ($currentStock === false) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }

    // Tính số lượng mới
    $newStock = $currentStock + $importQuantity;

    // Bắt đầu transaction
    $db->autocommit(false);

    try {
        // Cập nhật số lượng trong kho
        $updateResult = $product->updateStockQuantity($productId, $newStock);
        if (!$updateResult) {
            throw new Exception('Failed to update stock quantity');
        }

        // Ghi log nhập kho (nếu có bảng)
        $logQuery = "INSERT INTO warehouse_logs 
            (product_id, user_id, action_type, quantity_change, old_quantity, new_quantity, note, created_at) 
            VALUES (?, ?, 'IMPORT', ?, ?, ?, ?, NOW())";

        $logStmt = $db->prepare($logQuery);
        $userId = $_SESSION['user_id'];

        try {
           $logStmt->bind_param("iiiiis", $productId, $userId, $importQuantity, $currentStock, $newStock, $note);
            $logStmt->execute();
        } catch (Exception $logError) {
            // Bỏ qua lỗi nếu bảng log không tồn tại
            error_log("Warehouse log error (can be ignored): " . $logError->getMessage());
        }

        // Commit
        $db->commit();
        $db->autocommit(true);

        echo json_encode([
            'success' => true,
            'message' => 'Warehouse updated successfully',
            'data' => [
                'old_quantity' => $currentStock,
                'import_quantity' => $importQuantity,
                'new_quantity' => $newStock
            ]
        ]);

    } catch (Exception $e) {
        $db->rollback();
        $db->autocommit(true);
        throw $e;
    }

} catch (Exception $e) {
    error_log("Error in update_warehouse.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update warehouse: ' . $e->getMessage()]);
}
