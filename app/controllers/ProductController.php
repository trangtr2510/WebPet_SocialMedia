<?php
require_once 'db.php'; // PDO connection
require_once 'product.php';

$product = new Product($conn);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $result = $product->createProduct($_POST);
        echo $result ? "Product created" : "Create failed";
        break;

    case 'update':
        $result = $product->updateProduct($_POST['product_id'], $_POST);
        echo $result ? "Product updated" : "Update failed";
        break;

    case 'delete':
        $result = $product->deleteProduct($_GET['id']);
        echo $result ? "Deleted successfully" : "Delete failed";
        break;

    case 'search':
        $result = $product->searchProduct($_GET['keyword']);
        echo json_encode($result);
        break;

    case 'get':
        $result = $product->getProductByID($_GET['id']);
        echo json_encode($result);
        break;

    case 'byCategory':
        $result = $product->getAllProductByCategory($_GET['category_id']);
        echo json_encode($result);
        break;

    case 'all':
        $result = $product->getAllProduct();
        echo json_encode($result);
        break;

    default:
        echo "Invalid action.";
}
?>
