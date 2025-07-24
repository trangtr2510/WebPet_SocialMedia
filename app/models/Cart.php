<?php
class Cart {
    private $conn;
    private $table = "shopping_cart";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Thêm sản phẩm vào giỏ hàng
    public function createCart($customer_id, $product_id, $quantity) {
        $query = "INSERT INTO $this->table (customer_id, product_id, quantity) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii", $customer_id, $product_id, $quantity);
        return $stmt->execute();
    }
    
    // Cập nhật số lượng sản phẩm trong giỏ
    // public function updateCart($customer_id, $product_id, $quantity) {
    //     $query = "UPDATE $this->table SET quantity = ?, updated_at = NOW() WHERE customer_id = ? AND product_id = ?";
    //     $stmt = $this->conn->prepare($query);
    //     $stmt->bind_param("iii", $quantity, $customer_id, $product_id);
    //     return $stmt->execute();
    // }

    public function updateCart($customer_id, $product_id, $quantity) {
        // 1. Lấy stock hiện tại
        $stock_query = "SELECT stock_quantity FROM products WHERE product_id  = ?";
        $stock_stmt = $this->conn->prepare($stock_query);
        $stock_stmt->bind_param("i", $product_id);
        $stock_stmt->execute();
        $stock_result = $stock_stmt->get_result();
        $stock_row = $stock_result->fetch_assoc();

        if (!$stock_row) {
            return false; // Không tìm thấy sản phẩm
        }

        $stock_quantity = $stock_row['stock_quantity'];

        // 2. So sánh
        if ($quantity > $stock_quantity) {
            return false; // Vượt quá số lượng tồn kho
            // Hoặc: $quantity = $stock_quantity; // Nếu muốn giới hạn tự động
        }

        // 3. Cập nhật nếu hợp lệ
        $query = "UPDATE $this->table SET quantity = ?, updated_at = NOW() WHERE customer_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii", $quantity, $customer_id, $product_id);
        return $stmt->execute();
    }
    
    // Xóa sản phẩm khỏi giỏ hàng
    public function deleteCart($customer_id, $product_id) {
        $query = "DELETE FROM $this->table WHERE customer_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $customer_id, $product_id);
        return $stmt->execute();
    }
    
    // Kiểm tra sản phẩm đã có trong giỏ chưa
    public function searchProductInCart($customer_id, $product_id) {
        $query = "SELECT * FROM $this->table WHERE customer_id = ? AND product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $customer_id, $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc(); // Trả về 1 dòng dữ liệu (hoặc null)
    }
    
    // Lấy toàn bộ giỏ hàng của khách hàng
    public function getCartByCustomer($customer_id) {
        $query = "SELECT * FROM $this->table WHERE customer_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cartItems = [];
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
        }
        return $cartItems;
    }
    
    // Lấy giỏ hàng với thông tin chi tiết sản phẩm
    public function getCartWithProductDetails($customer_id) {
        $query = "SELECT 
                    c.*, 
                    p.product_name, 
                    p.price, 
                    p.stock_quantity, 
                    pi.image_url AS product_image
                FROM $this->table c
                JOIN products p ON c.product_id = p.product_id
                LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                WHERE c.customer_id = ?
                ORDER BY c.added_at DESC";
                
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $cartItems = [];
        while ($row = $result->fetch_assoc()) {
            $cartItems[] = $row;
        }

        return $cartItems;
    }
    
    // Đếm tổng số lượng sản phẩm trong giỏ hàng
    public function getTotalQuantity($customer_id) {
        $query = "SELECT SUM(quantity) as total_quantity FROM $this->table WHERE customer_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total_quantity'] ?? 0;
    }
    
    // Tính tổng giá trị giỏ hàng
    public function getTotalValue($customer_id) {
        $query = "SELECT SUM(c.quantity * p.price) as total_value 
                  FROM $this->table c 
                  JOIN products p ON c.product_id = p.product_id 
                  WHERE c.customer_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['total_value'] ?? 0;
    }
    
    // Xóa toàn bộ giỏ hàng của khách hàng
    public function clearCart($customer_id) {
        $query = "DELETE FROM $this->table WHERE customer_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $customer_id);
        return $stmt->execute();
    }
    
    // Kiểm tra tồn kho trước khi thêm/cập nhật
    public function validateStock($product_id, $quantity) {
        $query = "SELECT stock_quantity FROM products WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        
        if (!$product) {
            return ['valid' => false, 'message' => 'Sản phẩm không tồn tại.'];
        }
        
        if ($product['stock_quantity'] < $quantity) {
            return ['valid' => false, 'message' => 'Số lượng yêu cầu vượt quá tồn kho.'];
        }
        
        return ['valid' => true, 'message' => 'OK'];
    }

    public function getCartByUserId($user_id)
    {
        try {
            $sql = "SELECT c.*, p.product_name as product_name, p.price as product_price 
                    FROM $this->table c 
                    JOIN products p ON c.product_id = p.product_id 
                    WHERE c.customer_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Error getting cart by user ID: " . $e->getMessage());
            return [];
        }
    }

    public function clearCartByUserId($user_id)
    {
        try {
            $sql = "DELETE FROM $this->table WHERE customer_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error clearing cart: " . $e->getMessage());
            return false;
        }
    }
}
?>