<?php
require_once 'db.php'; // include your DB connection
require_once 'category.php';

$category = new Category($conn);

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'create':
        $result = $category->createCategory($_POST['name'], $_POST['desc'], $_POST['parent']);
        echo $result ? "Category created" : "Error creating category";
        break;

    case 'update':
        $result = $category->updateCategory($_POST['id'], $_POST['name'], $_POST['desc'], $_POST['parent'], $_POST['is_active']);
        echo $result ? "Category updated" : "Update failed";
        break;

    case 'delete':
        $result = $category->deleteCategory($_GET['id']);
        echo $result ? "Deleted" : "Delete failed";
        break;

    case 'search':
        $results = $category->searchCategories($_GET['keyword']);
        echo json_encode($results);
        break;

    case 'get':
        $result = $category->getAllCategoriesByID($_GET['id']);
        echo json_encode($result);
        break;

    default:
        echo "No valid action.";
}
?>
