<?php
class Product {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // 1. Create product
    public function createProduct($data) {
        $query = "INSERT INTO products 
        (product_name, description, category_id, price, stock_quantity, min_stock_level, weight, is_active, created_by, gender, color, age)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['product_name'],
            $data['description'],
            $data['category_id'],
            $data['price'],
            $data['stock_quantity'],
            $data['min_stock_level'],
            $data['weight'],
            $data['is_active'],
            $data['created_by'],
            $data['gender'],
            $data['color'],
            $data['age']
        ]);
    }

    // 2. Update product
    public function updateProduct($id, $data) {
        $query = "UPDATE products SET 
            product_name = ?, description = ?, category_id = ?, price = ?, stock_quantity = ?, 
            min_stock_level = ?, weight = ?, is_active = ?, gender = ?, color = ?, age = ?
            WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $data['product_name'],
            $data['description'],
            $data['category_id'],
            $data['price'],
            $data['stock_quantity'],
            $data['min_stock_level'],
            $data['weight'],
            $data['is_active'],
            $data['gender'],
            $data['color'],
            $data['age'],
            $id
        ]);
    }

    // 3. Delete product
    public function deleteProduct($id) {
        $query = "DELETE FROM products WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$id]);
    }

    // 4. Search product
    public function searchProduct($keyword) {
        $query = "SELECT * FROM products WHERE product_name LIKE ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(["%$keyword%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 5. Get product by ID
    public function getProductByID($id) {
        $query = "SELECT * FROM products WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->get_result(); // Lấy kết quả dạng MySQLi_Result
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        return $products;
    }

    // 6. Get all products by category
    public function getAllProductByCategory($category_id) {
        $query = "SELECT * FROM products WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$category_id]);
        $result = $stmt->get_result(); // Lấy kết quả dạng MySQLi_Result
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        return $products;
    }

    // 7. Get all products
    public function getAllProduct() {
        $query = "SELECT * FROM products";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result(); // Lấy kết quả dạng MySQLi_Result
        $products = [];

        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        return $products;
    }

}
?>
