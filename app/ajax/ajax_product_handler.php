<?php
// ajax_product_handler.php - Separate file for AJAX requests only

// Start session and clear any output
session_start();

// Clear any previous output
while (ob_get_level()) {
    ob_end_clean();
}

// Turn off error display for clean JSON
ini_set('display_errors', 0);
error_reporting(0);

// Set JSON headers immediately
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    // Include necessary files
    include(__DIR__ . '/../../config/config.php');
    require_once(__DIR__ . '/../models/Product.php');
    require_once(__DIR__ . '/../models/Category.php');
    require_once(__DIR__ . '/../models/ProductImage.php');
    require_once(__DIR__ . '/../controllers/ProductController.php');

    // Check if it's a valid request
    if (!isset($_GET['action'])) {
        throw new Exception('No action specified');
    }

    $action = $_GET['action'];
    $controller = new ProductController($conn);

    switch ($action) {
        case 'add_product':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Invalid request method');
            }
            $controller->addProduct();
            break;
            
        case 'getChildCategories':
            $controller->getChildCategories();
            break;
            
        case 'getParentCategoryName':
            $controller->getParentCategoryName();
            break;
            
        case 'update':
            $controller->updateProduct();
            break;
            
        case 'delete':
            $controller->deleteProduct();
            break;
            
        case 'search':
            $controller->searchProduct();
            break;
            
        case 'get':
            $controller->getProduct();
            break;
            
        case 'byCategory':
            $controller->getProductsByCategory();
            break;
            
        case 'all':
            $controller->getAllProducts();
            break;
            
        default:
            throw new Exception('Invalid action: ' . $action);
    }

} catch (Exception $e) {
    // Log error for debugging
    error_log("AJAX Error: " . $e->getMessage());
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Ensure no additional output
exit;
?>