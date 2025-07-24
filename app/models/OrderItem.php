<?php
class OrderItem
{
    private $conn;
    private $table = 'order_items';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function createOrderItem($data)
    {
        try {
            $sql = "INSERT INTO " . $this->table . " 
                    (order_id, product_id, quantity, unit_price, total_price, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "iiidds",
                $data['order_id'],
                $data['product_id'],
                $data['quantity'],
                $data['unit_price'],
                $data['total_price'],
                $data['created_at']
            );

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error creating order item: " . $e->getMessage());
            return false;
        }
    }

    public function getOrderItemsByOrderId($order_id)
    {
        try {
            $sql = "SELECT oi.*, p.product_name, p.description, p.price, pi.image_url 
                    FROM " . $this->table . " oi
                    LEFT JOIN products p ON oi.product_id = p.product_id
                    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                    WHERE oi.order_id = ?
                    ORDER BY oi.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            
            return $items;
        } catch (Exception $e) {
            error_log("Error getting order items: " . $e->getMessage());
            return [];
        }
    }

    public function isProductInProcessingOrders($product_id) 
    {
        $sql = "SELECT COUNT(*) as total 
                FROM order_items oi
                INNER JOIN orders o ON oi.order_id = o.order_id
                WHERE oi.product_id = ? AND o.status = 'processing'";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return ($result['total'] ?? 0) > 0;
    }

    public function getOrderItemById($order_item_id)
    {
        try {
            $sql = "SELECT oi.*, p.product_name, p.description, pi.image_url 
                    FROM " . $this->table . " oi
                    LEFT JOIN products p ON oi.product_id = p.product_id
                    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                    WHERE oi.order_item_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $order_item_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting order item: " . $e->getMessage());
            return false;
        }
    }

    public function updateOrderItem($order_item_id, $data)
    {
        try {
            $sql = "UPDATE " . $this->table . " 
                    SET quantity = ?, unit_price = ?, total_price = ? 
                    WHERE order_item_id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                "iddi",
                $data['quantity'],
                $data['unit_price'],
                $data['total_price'],
                $order_item_id
            );

            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating order item: " . $e->getMessage());
            return false;
        }
    }

    public function deleteOrderItem($order_item_id)
    {
        try {
            $sql = "DELETE FROM " . $this->table . " WHERE order_item_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $order_item_id);
            
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error deleting order item: " . $e->getMessage());
            return false;
        }
    }

    public function getTotalItemsByOrderId($order_id)
    {
        try {
            $sql = "SELECT SUM(quantity) as total_quantity FROM " . $this->table . " WHERE order_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return $row['total_quantity'] ?? 0;
        } catch (Exception $e) {
            error_log("Error getting total items: " . $e->getMessage());
            return 0;
        }
    }

    public function getOrderItemsWithDetails($order_id)
    {
        try {
            $sql = "SELECT 
                        oi.*,
                        p.product_name,
                        p.description,
                        pi.image_url,
                        c.category_name
                    FROM " . $this->table . " oi
                    LEFT JOIN products p ON oi.product_id = p.product_id
                    LEFT JOIN product_images pi ON p.product_id = pi.product_id AND pi.is_primary = 1
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE oi.order_id = ?
                    ORDER BY oi.created_at DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $items = [];
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            
            return $items;
        } catch (Exception $e) {
            error_log("Error getting order items with details: " . $e->getMessage());
            return [];
        }
    }
}
?>