<?php
require_once 'db.php';
require_once 'ProductImage.php';

$image = new ProductImage($conn);
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $result = $image->addImage(
            $_POST['product_id'],
            $_POST['image_url'],
            $_POST['alt_text'] ?? '',
            $_POST['is_primary'] ?? false,
            $_POST['display_order'] ?? 0
        );
        echo $result ? "Image added" : "Add failed";
        break;

    case 'update':
        $result = $image->updateImage(
            $_POST['image_id'],
            $_POST['image_url'],
            $_POST['alt_text'],
            $_POST['is_primary'],
            $_POST['display_order']
        );
        echo $result ? "Image updated" : "Update failed";
        break;

    case 'getByProduct':
        $images = $image->getImagesByProduct($_GET['product_id']);
        echo json_encode($images);
        break;

    case 'delete':
        $result = $image->deleteImage($_GET['image_id']);
        echo $result ? "Deleted" : "Delete failed";
        break;

    default:
        echo "No valid action.";
}
?>
