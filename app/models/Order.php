<?php
class Order
{
    private $conn;
    private $table = 'orders';
   
    public function __construct($db)
    {
        $this->conn = $db;
    }
   
    public function createOrder($data)
    {
        try {
            // Sửa lỗi: Loại bỏ các trường không tồn tại trong database
            $sql = "INSERT INTO " . $this->table . "
                    (userCustomer_id, order_number, order_date, status, total_amount,
                     payment_status, shipping_address, notes, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
           
            $stmt = $this->conn->prepare($sql);
            
            // Sửa lỗi: Loại bỏ processed_by khỏi bind_param vì không có trong schema
            $stmt->bind_param(
                "isssdsssss",
                $data['userCustomer_id'],
                $data['order_number'],
                $data['order_date'],
                $data['status'],
                $data['total_amount'],
                $data['payment_status'],
                $data['shipping_address'],
                $data['notes'],
                $data['created_at'],
                $data['updated_at']
            );
           
            if ($stmt->execute()) {
                return $this->conn->insert_id;
            }
            return false;
        } catch (Exception $e) {
            error_log("Error creating order: " . $e->getMessage());
            return false;
        }
    }

    // Thêm vào OrderModel class
    public function getOrderByIdCustomer($orderId) {
        try {
            // Chỉ sử dụng order_id vì không có cột id
            $sql = "SELECT * FROM orders WHERE order_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            
            // DEBUG: Log thông tin order
            error_log("DEBUG getOrderById - Input order_id: " . $orderId);
            error_log("DEBUG getOrderById - Found order: " . ($order ? 'YES' : 'NO'));
            if ($order) {
                error_log("DEBUG getOrderById - Order status: " . ($order['status'] ?? 'NULL'));
                error_log("DEBUG getOrderById - Order columns: " . implode(', ', array_keys($order)));
            }
            
            return $order;
        } catch (Exception $e) {
            error_log("Error in getOrderById: " . $e->getMessage());
            return false;
        }
    }
   
    public function getOrderById($order_id)
    {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE order_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
           
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting order: " . $e->getMessage());
            return false;
        }
    }
   
    public function getOrderByNumber($order_number)
    {
        try {
            $sql = "SELECT * FROM " . $this->table . " WHERE order_number = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("s", $order_number);
            $stmt->execute();
           
            $result = $stmt->get_result();
            return $result->fetch_assoc();
        } catch (Exception $e) {
            error_log("Error getting order by number: " . $e->getMessage());
            return false;
        }
    }
   
    public function updateOrderStatus($order_id, $status, $payment_status = null)
    {
        try {
            if ($payment_status) {
                $sql = "UPDATE " . $this->table . "
                        SET status = ?, payment_status = ?, updated_at = NOW()
                        WHERE order_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("ssi", $status, $payment_status, $order_id);
            } else {
                $sql = "UPDATE " . $this->table . "
                        SET status = ?, updated_at = NOW()
                        WHERE order_id = ?";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param("si", $status, $order_id);
            }
           
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Error updating order status: " . $e->getMessage());
            return false;
        }
    }
    
    // UPDATED METHOD: Hỗ trợ search, sorting và pagination
    public function getOrdersByStatus($status, $search = '', $sort_by = 'created_at', $page = 1, $limit = 10)
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Base query với JOIN để lấy thông tin customer
            $sql = "SELECT o.*, 
                           u.full_name as customer_name, 
                           u.email as customer_email, 
                           u.img as customer_avatar
                    FROM " . $this->table . " o 
                    LEFT JOIN users u ON o.userCustomer_id = u.user_id 
                    WHERE o.status = ?";
            
            $params = [$status];
            $types = "s";
            
            // Thêm điều kiện search nếu có
            if (!empty($search)) {
                $sql .= " AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= "sss";
            }
            
            // Thêm ORDER BY
            $allowedSorts = ['created_at', 'order_number', 'total_amount', 'updated_at'];
            if (in_array($sort_by, $allowedSorts)) {
                $sql .= " ORDER BY o.{$sort_by} DESC";
            } else {
                $sql .= " ORDER BY o.created_at DESC";
            }
            
            // Thêm LIMIT và OFFSET
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            return $orders;
        } catch (Exception $e) {
            error_log("Error getting orders by status: " . $e->getMessage());
            return [];
        }
    }
    
    // UPDATED METHOD: Đếm orders với search filter
    public function countOrderBySpecificStatus($status, $search = '')
    {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM " . $this->table . " o 
                    LEFT JOIN users u ON o.userCustomer_id = u.user_id 
                    WHERE o.status = ?";
            
            $params = [$status];
            $types = "s";
            
            // Thêm điều kiện search nếu có
            if (!empty($search)) {
                $sql .= " AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;  
                $params[] = $searchParam;
                $types .= "sss";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return (int)$row['count'];
        } catch (Exception $e) {
            error_log("Error counting specific order status: " . $e->getMessage());
            return 0;
        }
    }
    
    // Thêm method để lấy orders với thông tin chi tiết
    public function getOrdersWithDetails($user_id = null, $limit = null, $offset = null)
    {
        try {
            $sql = "SELECT o.*, COUNT(oi.order_item_id) as total_items
                    FROM " . $this->table . " o
                    LEFT JOIN order_items oi ON o.order_id = oi.order_id";
            
            $params = [];
            $types = "";
            
            if ($user_id) {
                $sql .= " WHERE o.userCustomer_id = ?";
                $params[] = $user_id;
                $types .= "i";
            }
            
            $sql .= " GROUP BY o.order_id ORDER BY o.created_at DESC";
            
            if ($limit) {
                $sql .= " LIMIT ?";
                $params[] = $limit;
                $types .= "i";
                
                if ($offset) {
                    $sql .= " OFFSET ?";
                    $params[] = $offset;
                    $types .= "i";
                }
            }
            
            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            
            $result = $stmt->get_result();
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            return $orders;
        } catch (Exception $e) {
            error_log("Error getting orders with details: " . $e->getMessage());
            return [];
        }
    }

    public function countOrdersByStatus($user_id = null)
    {
        try {
            $sql = "SELECT status, COUNT(*) as count
                    FROM " . $this->table;

            $params = [];
            $types = "";

            if ($user_id !== null) {
                $sql .= " WHERE userCustomer_id = ?";
                $params[] = $user_id;
                $types .= "i";
            }

            $sql .= " GROUP BY status";

            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $counts = [];
            while ($row = $result->fetch_assoc()) {
                $counts[$row['status']] = (int)$row['count'];
            }

            return $counts;
        } catch (Exception $e) {
            error_log("Error counting orders by status: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Tìm kiếm orders với nhiều bộ lọc
     * @param string $status - Trạng thái đơn hàng
     * @param string $search - Tìm kiếm theo order_number hoặc tên khách hàng
     * @param array $filters - Các bộ lọc khác (date_from, date_to, price_from, price_to)
     * @param string $sort_by - Sắp xếp theo
     * @param int $page - Trang hiện tại
     * @param int $limit - Số lượng per page
     * @return array
     */
    public function searchOrdersWithFilters($status, $search = '', $filters = [], $sort_by = 'created_at', $page = 1, $limit = 10)
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // Base query
            $sql = "SELECT o.*, 
                        u.full_name as customer_name, 
                        u.email as customer_email, 
                        u.img as customer_avatar
                    FROM " . $this->table . " o 
                    LEFT JOIN users u ON o.userCustomer_id = u.user_id 
                    WHERE o.status = ?";
            
            $params = [$status];
            $types = "s";
            
            // Tìm kiếm theo order_number hoặc tên khách hàng
            if (!empty($search)) {
                $sql .= " AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= "sss";
            }
            
            // Lọc theo khoảng ngày tạo
            if (!empty($filters['date_from'])) {
                $sql .= " AND o.created_at >= ?";
                $params[] = $filters['date_from'];
                $types .= "s";
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND o.created_at <= ?";
                $params[] = $filters['date_to'];
                $types .= "s";
            }
            
            // Lọc theo khoảng giá
            if (!empty($filters['price_from'])) {
                $sql .= " AND o.total_amount >= ?";
                $params[] = $filters['price_from'];
                $types .= "d";
            }
            
            if (!empty($filters['price_to'])) {
                $sql .= " AND o.total_amount <= ?";
                $params[] = $filters['price_to'];
                $types .= "d";
            }
            
            // Sắp xếp
            $allowedSorts = ['created_at', 'order_number', 'total_amount', 'updated_at'];
            if (in_array($sort_by, $allowedSorts)) {
                $sql .= " ORDER BY o.{$sort_by} DESC";
            } else {
                $sql .= " ORDER BY o.created_at DESC";
            }
            
            // Phân trang
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= "ii";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            return $orders;
            
        } catch (Exception $e) {
            error_log("Error in searchOrdersWithFilters: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Đếm số lượng orders với filters
     * @param string $status
     * @param string $search
     * @param array $filters
     * @return int
     */
    public function countOrdersWithFilters($status, $search = '', $filters = [])
    {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM " . $this->table . " o 
                    LEFT JOIN users u ON o.userCustomer_id = u.user_id 
                    WHERE o.status = ?";
            
            $params = [$status];
            $types = "s";
            
            // Tìm kiếm theo order_number hoặc tên khách hàng
            if (!empty($search)) {
                $sql .= " AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
                $searchParam = "%{$search}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= "sss";
            }
            
            // Lọc theo khoảng ngày tạo
            if (!empty($filters['date_from'])) {
                $sql .= " AND o.created_at >= ?";
                $params[] = $filters['date_from'];
                $types .= "s";
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND o.created_at <= ?";
                $params[] = $filters['date_to'];
                $types .= "s";
            }
            
            // Lọc theo khoảng giá
            if (!empty($filters['price_from'])) {
                $sql .= " AND o.total_amount >= ?";
                $params[] = $filters['price_from'];
                $types .= "d";
            }
            
            if (!empty($filters['price_to'])) {
                $sql .= " AND o.total_amount <= ?";
                $params[] = $filters['price_to'];
                $types .= "d";
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return (int)$row['count'];
            
        } catch (Exception $e) {
            error_log("Error in countOrdersWithFilters: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Tìm kiếm nhanh orders (cho autocomplete)
     * @param string $query
     * @param int $limit
     * @return array
     */
    public function quickSearchOrders($query, $limit = 10)
    {
        try {
            $sql = "SELECT o.order_id, o.order_number, o.total_amount, o.status,
                        u.full_name as customer_name, u.email as customer_email
                    FROM " . $this->table . " o 
                    LEFT JOIN users u ON o.userCustomer_id = u.user_id 
                    WHERE (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)
                    ORDER BY o.created_at DESC
                    LIMIT ?";
            
            $searchParam = "%{$query}%";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("sssi", $searchParam, $searchParam, $searchParam, $limit);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $orders = [];
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
            
            return $orders;
            
        } catch (Exception $e) {
            error_log("Error in quickSearchOrders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lấy thống kê orders theo filters
     * @param array $filters
     * @return array
     */
    public function getOrderStatsByFilters($filters = [])
    {
        try {
            $sql = "SELECT 
                        o.status,
                        COUNT(*) as count,
                        SUM(o.total_amount) as total_amount,
                        AVG(o.total_amount) as avg_amount
                    FROM " . $this->table . " o 
                    LEFT JOIN users u ON o.userCustomer_id = u.user_id 
                    WHERE 1=1";
            
            $params = [];
            $types = "";
            
            // Apply filters
            if (!empty($filters['search'])) {
                $sql .= " AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
                $searchParam = "%{$filters['search']}%";
                $params[] = $searchParam;
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= "sss";
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND o.created_at >= ?";
                $params[] = $filters['date_from'];
                $types .= "s";
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND o.created_at <= ?";
                $params[] = $filters['date_to'];
                $types .= "s";
            }
            
            if (!empty($filters['price_from'])) {
                $sql .= " AND o.total_amount >= ?";
                $params[] = $filters['price_from'];
                $types .= "d";
            }
            
            if (!empty($filters['price_to'])) {
                $sql .= " AND o.total_amount <= ?";
                $params[] = $filters['price_to'];
                $types .= "d";
            }
            
            $sql .= " GROUP BY o.status";
            
            $stmt = $this->conn->prepare($sql);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            
            $result = $stmt->get_result();
            $stats = [];
            while ($row = $result->fetch_assoc()) {
                $stats[$row['status']] = [
                    'count' => (int)$row['count'],
                    'total_amount' => (float)$row['total_amount'],
                    'avg_amount' => (float)$row['avg_amount']
                ];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Error in getOrderStatsByFilters: " . $e->getMessage());
            return [];
        }
    }

}
?>
