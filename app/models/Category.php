<?php
class Category {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create
    public function createCategory($category_name, $description, $parent_id) {
        $query = "INSERT INTO categories (category_name, description, parent_category_id)
                  VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$category_name, $description, $parent_id]);
    }

    // Update
    public function updateCategory($id, $category_name, $description, $parent_id, $is_active) {
        $query = "UPDATE categories SET category_name = ?, description = ?, parent_category_id = ?, is_active = ?
                  WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$category_name, $description, $parent_id, $is_active, $id]);
    }

    // Delete
    public function deleteCategory($id) {
        $query = "DELETE FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // Search
    public function searchCategories($keyword) {
        $query = "SELECT * FROM categories WHERE category_name LIKE ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(["%$keyword%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get by ID
    public function getAllCategoriesByID($id) {
        $query = "SELECT * FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all
    public function getAllCategories() {
        $query = "SELECT * FROM categories";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
?>
