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
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        return $categories;
    }
    
    // Get by ID
    public function getAllCategoriesByID($id) {
        $query = "SELECT * FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Get all
    public function getAllCategories() {
        $query = "SELECT * FROM categories ORDER BY parent_category_id, category_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        return $categories;
    }
    
    // Lấy danh mục cha (parent_category_id IS NULL)
    public function getParentCategories() {
        $query = "SELECT * FROM categories WHERE parent_category_id IS NULL AND is_active = 1 ORDER BY category_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        return $categories;
    }
    
    // Lấy danh mục con theo danh mục cha
    public function getChildCategories($parent_id) {
        $query = "SELECT * FROM categories WHERE parent_category_id = ? AND is_active = 1 ORDER BY category_name";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        return $categories;
    }
    
    // Lấy danh mục theo ID (trả về 1 bản ghi)
    public function getCategoryByID($id) {
        $query = "SELECT * FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    // Lấy đường dẫn đầy đủ của danh mục (breadcrumb)
    public function getCategoryPath($category_id) {
        $path = [];
        $current_id = $category_id;
        
        while ($current_id) {
            $category = $this->getCategoryByID($current_id);
            if ($category) {
                array_unshift($path, $category);
                $current_id = $category['parent_category_id'];
            } else {
                break;
            }
        }
        
        return $path;
    }
}
?>