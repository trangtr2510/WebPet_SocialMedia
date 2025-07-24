<?php
class Product {
    private $conn;
    
    public function __construct($db) {
        $this->conn = $db;
    }

    public function disableProduct($productId, $reason, $disabledBy = null) {
        try {
            // Bắt đầu transaction
            $this->conn->autocommit(false);

            // Cập nhật trạng thái sản phẩm
            $query = "UPDATE products SET is_active = 0 WHERE product_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $productId);
            $stmt->execute();

            // Thêm lý do vào bảng product_disable_reasons
            $query = "INSERT INTO product_disable_reasons (product_id, reason, disabled_by) VALUES (?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("isi", $productId, $reason, $disabledBy);
            $stmt->execute();

            // Commit transaction
            $this->conn->commit();
            $this->conn->autocommit(true); // bật lại auto commit
            return true;

        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->conn->rollback();
            $this->conn->autocommit(true); // bật lại auto commit
            error_log("Error disabling product: " . $e->getMessage());
            return false;
        }
    }
    
    // Method để enable sản phẩm
    public function enableProduct($productId) {
        try {
            $query = "UPDATE products SET is_active = 1 WHERE product_id = ?";
            $stmt = $this->conn->prepare($query);
            return $stmt->execute([$productId]);
        } catch (Exception $e) {
            error_log("Error enabling product: " . $e->getMessage());
            return false;
        }
    }

    public function getDisableReason($productId) {
        $query = "SELECT pdr.reason, pdr.disabled_by, pdr.disabled_at, u.full_name, u.email 
                FROM product_disable_reasons pdr 
                LEFT JOIN users u ON pdr.disabled_by = u.user_id 
                WHERE pdr.product_id = ? 
                ORDER BY pdr.disabled_at DESC 
                LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function getStockQuantity($product_id) {
        $sql = "SELECT stock_quantity FROM products WHERE product_id  = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['stock_quantity'] ?? 0;
    }

    public function updateStockQuantity($product_id, $new_stock) {
        $sql = "UPDATE products SET stock_quantity = ? WHERE product_id  = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $new_stock, $product_id);
        return $stmt->execute();
    }

    // Method để lấy thông tin chi tiết sản phẩm cho form nhập kho
    public function getProductForWarehouse($product_id) {
        $sql = "SELECT product_id, product_name, stock_quantity, price 
                FROM products 
                WHERE product_id = ? AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Method để cập nhật kho với transaction an toàn
    public function updateWarehouseStock($product_id, $import_quantity, $user_id, $note = '') {
        try {
            // Bắt đầu transaction
            $this->conn->autocommit(false);
            
            // Lấy số lượng hiện tại
            $current_stock = $this->getStockQuantity($product_id);
            if ($current_stock === false) {
                throw new Exception('Product not found');
            }
            
            // Tính số lượng mới
            $new_stock = $current_stock + $import_quantity;
            
            // Cập nhật số lượng
            if (!$this->updateStockQuantity($product_id, $new_stock)) {
                throw new Exception('Failed to update stock quantity');
            }
            
            // Ghi log (nếu bảng warehouse_logs tồn tại)
            $this->logWarehouseAction($product_id, $user_id, 'IMPORT', $import_quantity, $current_stock, $new_stock, $note);
            
            // Commit transaction
            $this->conn->commit();
            $this->conn->autocommit(true);
            
            return [
                'success' => true,
                'old_quantity' => $current_stock,
                'import_quantity' => $import_quantity,
                'new_quantity' => $new_stock
            ];
            
        } catch (Exception $e) {
            // Rollback nếu có lỗi
            $this->conn->rollback();
            $this->conn->autocommit(true);
            error_log("Error updating warehouse stock: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    // Method để ghi log warehouse (tùy chọn)
    private function logWarehouseAction($product_id, $user_id, $action_type, $quantity_change, $old_quantity, $new_quantity, $note = '') {
        try {
            $sql = "INSERT INTO warehouse_logs (product_id, user_id, action_type, quantity_change, old_quantity, new_quantity, note, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iisiiis", $product_id, $user_id, $action_type, $quantity_change, $old_quantity, $new_quantity, $note);
            $stmt->execute();
        } catch (Exception $e) {
            // Bỏ qua lỗi log nếu bảng không tồn tại
            error_log("Warehouse log error (can be ignored): " . $e->getMessage());
        }
    }

    // Method để lấy lịch sử nhập kho của sản phẩm
    public function getWarehouseHistory($product_id, $limit = 10) {
        try {
            $sql = "SELECT wl.*, u.username, u.full_name, p.product_name 
                    FROM warehouse_logs wl
                    LEFT JOIN users u ON wl.user_id = u.user_id
                    LEFT JOIN products p ON wl.product_id = p.product_id
                    WHERE wl.product_id = ?
                    ORDER BY wl.created_at DESC
                    LIMIT ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $product_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $history = [];
            while ($row = $result->fetch_assoc()) {
                $history[] = $row;
            }
            
            return $history;
        } catch (Exception $e) {
            error_log("Error getting warehouse history: " . $e->getMessage());
            return [];
        }
    }

    // 1. Create product - SỬA LỖI SQL
    public function createProduct($data) {
        $query = "INSERT INTO products
        (product_name, description, category_id, price, stock_quantity, min_stock_level, weight, is_active, created_by, age, material, size)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
       
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->conn->error);
            return false;
        }
        
        $result = $stmt->execute([
            $data['product_name'],
            $data['description'],
            $data['category_id'],
            $data['price'],
            $data['stock_quantity'],
            $data['min_stock_level'],
            $data['weight'],
            $data['is_active'],
            $data['created_by'],
            $data['age'],
            $data['material'],
            $data['size']
        ]);
       
         if ($result) {
            $product_id = $this->conn->insert_id;
            error_log("✅ Product created successfully with ID: " . $product_id);
            return $product_id;
        } else {
            error_log("❌ Execute failed:");
            error_log("Query: " . $query);
            error_log("Data: " . print_r($data, true));
            error_log("PDO Error Info: " . print_r($stmt->errorInfo(), true));
            return false;
        }
    }
    
    // 2. Update product
    public function updateProduct($id, $data) {
        $query = "UPDATE products SET
            product_name = ?, description = ?, category_id = ?, price = ?, stock_quantity = ?,
            min_stock_level = ?, weight = ?, is_active = ?, age = ?, material = ?, size = ?
            WHERE product_id = ?";
       
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log('Prepare failed: ' . $this->conn->error);
            return false;
        }
        
        return $stmt->execute([
            $data['product_name'],
            $data['description'],
            $data['category_id'],
            $data['price'],
            $data['stock_quantity'],
            $data['min_stock_level'],
            $data['weight'],
            $data['is_active'],
            $data['age'],
            $data['material'],
            $data['size'],
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
        $stmt->bind_param("s", $keyword);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
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

    public function getAllProductByParentCategory($parent_id) {
        $query = "SELECT p.* FROM products p
                JOIN categories c ON p.category_id = c.category_id
                WHERE c.parent_category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }

    public function getCategoryById($categoryId) {
        $query = "SELECT * FROM categories WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $categoryId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // 6. Get all products by category
    public function getAllProductByCategory($category_id) {
        $query = "SELECT * FROM products WHERE category_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
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
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }

    public function getLatestProductsByParentCategory($parentCategoryId, $limit = 8) {
        try {
            // Lấy các category_id con của parent
            $sql = "SELECT category_id FROM categories WHERE parent_category_id = ?";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed (1): " . $this->conn->error);
                return [];
            }

            $stmt->bind_param("i", $parentCategoryId);
            $stmt->execute();
            $result = $stmt->get_result();

            $childCategoryIds = [];
            while ($row = $result->fetch_assoc()) {
                $childCategoryIds[] = $row['category_id'];
            }

            $stmt->close();

            if (empty($childCategoryIds)) {
                return []; // Không có danh mục con
            }

            // Tạo dấu ? tương ứng với số lượng category_id con
            $placeholders = implode(',', array_fill(0, count($childCategoryIds), '?'));
            $types = str_repeat('i', count($childCategoryIds)) . 'i'; // Thêm 'i' cho LIMIT cuối

            $sql = "SELECT * FROM products WHERE category_id IN ($placeholders) ORDER BY created_at DESC LIMIT ?";

            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                error_log("Prepare failed (2): " . $this->conn->error);
                return [];
            }

            // Ghép params: các category_id + limit
            $params = array_merge($childCategoryIds, [$limit]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }

            return $products;

        } catch (Exception $e) {
            error_log("Error in getLatestProductsByParentCategory: " . $e->getMessage());
            return [];
        }
    }

    public function getProductWithCategoryInfo($product_id) {
        $query = "SELECT p.*, c.category_name, c.parent_category_id 
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                WHERE p.product_id = ?
                LIMIT 1";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            die("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $product = $result->fetch_assoc();

        $stmt->close();

        return $product;
    }
}

?>