<?php
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../models/Order.php');
require_once(__DIR__ . '/../models/User.php');
require_once(__DIR__ . '/../models/OrderItem.php');
require_once(__DIR__ . '/../models/Product.php');

class OrderControllerCustomer
{
    private $orderModel;
    private $userModel;
    private $orderItemModel;
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->orderModel = new Order($db);
        $this->userModel = new User($db);
        $this->orderItemModel = new OrderItem($db);
        $this->productModel = new Product($db);
    }
    
    public function handleRequest() 
    {
        // Chỉ start session nếu chưa được start
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Check if user is logged in and has proper permissions
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này.']);
            return;
        }

        // Lấy action từ cả GET và POST - thêm kiểm tra nhiều tham số khác nhau
        $action = $_GET['order_action'] ?? $_POST['order_action'] ?? '';

        // Debug log để kiểm tra action - CHỈ KHI DEVELOPMENT
        if (defined('DEBUG_MODE') && DEBUG_MODE) {
            error_log("OrderControllerCustomer - Action received: " . $action);
            error_log("GET params: " . print_r($_GET, true));
            error_log("POST params: " . print_r($_POST, true));
        }

        // Đảm bảo không có output trước đây
        if (ob_get_level()) {
            ob_clean();
        }

        switch ($action) {
            case 'customer_cancel_order':
                $this->customerCancelOrder();
                break;
                
            case 'customer_confirm_received':
                $this->customerConfirmReceived();
                break;
                
            case 'customer_get_order_detail':
                $this->customerGetOrderDetail();
                break;
                
            default:
                // echo json_encode([
                //     'success' => false,
                //     'message' => 'Action không hợp lệ: ' . $action,
                //     'debug' => [
                //         'received_action' => $action,
                //         'get_order_action' => $_GET['order_action'] ?? 'not_set',
                //         'post_order_action' => $_POST['order_action'] ?? 'not_set',
                //         'get_action' => $_GET['action'] ?? 'not_set',
                //         'post_action' => $_POST['action'] ?? 'not_set',
                //         'request_method' => $_SERVER['REQUEST_METHOD']
                //     ]
                // ]);
        }
    }

    private function customerCancelOrder($order = null)
    {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này.']);
            return;
        }

        if (!$order) {
            $orderId = $_POST['order_id'] ?? 0;

            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.']);
                return;
            }

            $order = $this->orderModel->getOrderByIdCustomer($orderId);
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.']);
                return;
            }

            if ($order['userCustomer_id'] != $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
                return;
            }
        }

        if (empty($order['status'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Trạng thái đơn hàng không hợp lệ.',
                'debug' => [
                    'order_status' => $order['status'],
                    'order_keys' => array_keys($order)
                ]
            ]);
            return;
        }

        try {
            $orderIdToUpdate = $order['order_id'] ?? $order['id'];

            // 🔁 Lấy danh sách sản phẩm trong đơn hàng
            $orderItems = $this->orderItemModel->getOrderItemsByOrderId($orderIdToUpdate);

            foreach ($orderItems as $item) {
                $productId = $item['product_id'];
                $quantity = $item['quantity'];

                // 🔁 Lấy tồn kho hiện tại
                $currentStock = $this->productModel->getStockQuantity($productId);

                // ➕ Cộng lại số lượng sản phẩm đã đặt
                $newStock = $currentStock + $quantity;

                // ✅ Cập nhật tồn kho
                $this->productModel->updateStockQuantity($productId, $newStock);
            }

            // ✅ Cập nhật trạng thái đơn hàng
            if ($this->orderModel->updateOrderStatus($orderIdToUpdate, 'cancelled')) {
                echo json_encode([
                    'success' => true,
                    'message' => "Đã hủy đơn hàng #{$order['order_number']} thành công.",
                    'new_status' => 'cancelled',
                    'new_status_text' => 'Đã hủy'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể hủy đơn hàng. Vui lòng thử lại.']);
            }
        } catch (Exception $e) {
            error_log("Error canceling customer order: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi hủy đơn hàng.']);
        }
    }

    private function customerConfirmReceived($order = null)
    {
        // Nếu không có order được truyền vào, lấy từ POST
        if (!$order) {
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để thực hiện hành động này.']);
                return;
            }

            $orderId = $_POST['order_id'] ?? 0;
            
            if (!$orderId) {
                echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ.']);
                return;
            }

            $order = $this->orderModel->getOrderByIdCustomer($orderId);
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng.']);
                return;
            }

            // Kiểm tra quyền sở hữu đơn hàng
            if ($order['userCustomer_id'] != $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này.']);
                return;
            }
        }

        // Chỉ cho phép xác nhận nhận hàng khi đơn hàng ở trạng thái 'shipped'
        // if ($order['status'] !== 'shipped') {
        //     echo json_encode([
        //         'success' => false, 
        //         'message' => 'Chỉ có thể xác nhận nhận hàng khi đơn hàng đang được vận chuyển.'
        //     ]);
        //     return;
        // }

        try {
            // Cập nhật trạng thái thành 'delivered' và payment_status thành 'paid'
            if ($this->orderModel->updateOrderStatus($order['order_id'], 'delivered', 'paid')) {
                echo json_encode([
                    'success' => true, 
                    'message' => "Đã xác nhận nhận hàng cho đơn hàng #{$order['order_number']} thành công.",
                    'new_status' => 'delivered',
                    'new_status_text' => 'Đã giao đến khách hàng',
                    'new_payment_status' => 'paid',
                    'new_payment_status_text' => 'Đã thanh toán'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể xác nhận nhận hàng. Vui lòng thử lại.']);
            }
        } catch (Exception $e) {
            error_log("Error confirming received order: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi xác nhận nhận hàng.']);
        }
    }

    private function customerGetOrderDetail()
    {
        // Bắt đầu output buffering để tránh output không mong muốn
        ob_start();
        
        // Clear any previous output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Set header trước khi có bất kỳ output nào
        header('Content-Type: application/json; charset=utf-8');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Bạn cần đăng nhập để xem chi tiết đơn hàng.']);
            exit(); // Thêm exit để đảm bảo không có code nào khác chạy
        }

        $orderId = $_GET['order_id'] ?? 0;
        
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
            exit();
        }

        try {
            // Get order details
            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
                exit();
            }

            // Kiểm tra quyền sở hữu đơn hàng
            if ($order['userCustomer_id'] != $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xem đơn hàng này.']);
                exit();
            }

            // Get customer information
            $customer = $this->userModel->getUserById($order['userCustomer_id']);
            
            // Get order items
            $orderItems = $this->orderItemModel->getOrderItemsByOrderId($orderId);

            // Build HTML response for customer view
            $html = $this->buildCustomerOrderDetailHtml($order, $customer, $orderItems);
            
            echo json_encode(['success' => true, 'html' => $html]);
            exit();
            
        } catch (Exception $e) {
            error_log("Error getting customer order detail: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi tải chi tiết đơn hàng']);
            exit();
        }
    }

    private function buildCustomerOrderDetailHtml($order, $customer, $orderItems)
    {
        // Sử dụng output buffering một cách an toàn
        ob_start();
        ?>
        <div class="customer-order-detail-container">
            <div class="customer-order-detail-info">
                <!-- Header Section -->
                <div class="order-header">
                    <div class="order-title">
                        <h3><i class="fas fa-receipt"></i> Đơn hàng #<?php echo htmlspecialchars($order['order_number']); ?></h3>
                        <small class="order-date">
                            <i class="fas fa-calendar"></i> 
                            <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                        </small>
                    </div>
                    <div class="order-status-group">
                        <span class="status-badge order-status <?php echo $this->getStatusBadgeClass($order['status']); ?>">
                            <i class="fas fa-box"></i>
                            <?php echo $this->getStatusText($order['status']); ?>
                        </span>
                        <span class="status-badge payment-status <?php echo $this->getStatusBadgeClass($order['payment_status']); ?>">
                            <i class="fas fa-credit-card"></i>
                            <?php echo $this->getPaymentStatusText($order['payment_status']); ?>
                        </span>
                    </div>
                </div>
                <!-- Main Content Grid -->
                <div class="order-content-grid">
                    <!-- Delivery Information Card -->
                    <div class="info-card delivery-card">
                        <div class="card-header">
                            <h4><i class="fas fa-shipping-fast"></i> Thông tin giao hàng</h4>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <i class="fas fa-signature info-icon"></i>
                                <div class="info-content">
                                    <label>Người nhận</label>
                                    <span><?php echo htmlspecialchars($customer['full_name'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-phone info-icon"></i>
                                <div class="info-content">
                                    <label>Điện thoại</label>
                                    <span><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-map-marker-alt info-icon"></i>
                                <div class="info-content">
                                    <label>Địa chỉ giao hàng</label>
                                    <span><?php echo htmlspecialchars($customer['address'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Information Card -->
                    <div class="info-card order-card">
                        <div class="card-header">
                            <h4><i class="fas fa-info-circle"></i> Thông tin đơn hàng</h4>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <i class="fas fa-plus-circle info-icon"></i>
                                <div class="info-content">
                                    <label>Ngày đặt hàng</label>
                                    <span><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                                </div>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-sync info-icon"></i>
                                <div class="info-content">
                                    <label>Cập nhật lần cuối</label>
                                    <span><?php echo date('d/m/Y H:i', strtotime($order['updated_at'])); ?></span>
                                </div>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-sticky-note info-icon"></i>
                                <div class="info-content">
                                    <label>Ghi chú</label>
                                    <span><?php echo htmlspecialchars($order['notes'] ?? 'Không có ghi chú'); ?></span>
                                </div>
                            </div>
                            <div class="info-row total-row">
                                <i class="fas fa-money-bill-wave info-icon"></i>
                                <div class="info-content">
                                    <label>Tổng tiền</label>
                                    <span class="total-amount"><?php echo number_format($order['total_amount'], 0, '.', ',') . ' VNĐ'; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Items Section -->
                <?php foreach ($orderItems as $item): ?>
                    <div class="item-card">
                        <div class="item-image-container">
                            <?php if (!empty($item['image_url'])): ?>
                                <img src="/WebsitePet/public/uploads/product/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                    alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Sản phẩm không còn tồn tại'); ?>" 
                                    class="item-image">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="item-details">
                            <div class="item-name">
                                <?php 
                                    if (!isset($item['product_name']) || is_null($item['product_name'])) {
                                        echo '<span class="text-danger">Sản phẩm không còn tồn tại</span>';
                                    } else {
                                        echo htmlspecialchars($item['product_name']);
                                    }
                                ?>
                            </div>

                            <div class="item-meta">
                                <span class="quantity">
                                    <i class="fas fa-cube"></i>
                                    Số lượng: <?php echo $item['quantity']; ?>
                                </span>
                                <span class="price">
                                    <i class="fas fa-tag"></i>
                                    <?php 
                                        if (!isset($item['price']) || is_null($item['price'])) {
                                            echo '<span class="text-muted">Không xác định</span>';
                                        } else {
                                            echo number_format($item['price'], 0, '.', ',') . ' VNĐ';
                                        }
                                    ?>
                                </span>
                            </div>
                        </div>

                        <div class="item-total">
                            <?php 
                                if (!isset($item['price']) || is_null($item['price'])) {
                                    echo '<span class="text-muted">Không xác định</span>';
                                } else {
                                    echo number_format($item['quantity'] * $item['price'], 0, '.', ',') . ' VNĐ';
                                }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Order Timeline -->
                <div class="timeline-section">
                    <div class="section-header">
                        <h4><i class="fas fa-history"></i> Trạng thái đơn hàng</h4>
                    </div>
                    <div class="timeline-container">
                        <?php echo $this->buildOrderTimeline($order); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .text-danger {
            color: #dc3545;
        }
        .text-muted {
            color: #6c757d;
            font-style: italic;
        }

        .customer-order-detail-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fb;
            border-radius: 12px;
            overflow: hidden;
        }

        .customer-order-detail-info {
            max-height: 80vh;
            overflow-y: auto;
            padding: 24px;
            background: white;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f1f5f9;
        }

        .customer-actions {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            padding: 16px;
            background: #f7fafc;
            border-radius: 10px;
            border-left: 4px solid #4299e1;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-cancel {
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            color: white;
        }

        .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(229, 62, 62, 0.4);
        }

        .btn-confirm {
            background: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
            color: white;
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(56, 161, 105, 0.4);
        }

        .timeline-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            margin-top: 24px;
        }

        .timeline-container {
            padding: 24px;
        }

        .timeline-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px 0;
            border-left: 2px solid #e2e8f0;
            padding-left: 32px;
            position: relative;
        }

        .timeline-item:last-child {
            border-left: 2px solid transparent;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 20px;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #e2e8f0;
        }

        .timeline-item.active::before {
            background: #4299e1;
        }

        .timeline-item.completed::before {
            background: #38a169;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .timeline-desc {
            color: #718096;
            font-size: 0.9rem;
        }

        /* Additional styles for order header and content */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 2px solid #e2e8f0;
        }

        .order-title h3 {
            margin: 0;
            color: #2d3748;
            font-size: 1.5rem;
        }

        .order-date {
            color: #718096;
            font-size: 0.9rem;
            margin-top: 4px;
            display: block;
        }

        .order-status-group {
            display: flex;
            gap: 12px;
            flex-direction: column;
            align-items: flex-end;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .order-content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .info-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .card-header {
            background: #f7fafc;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .card-header h4 {
            margin: 0;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .card-content {
            padding: 20px;
        }

        .info-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .info-row:last-child {
            margin-bottom: 0;
        }

        .info-icon {
            color: #4299e1;
            width: 20px;
            text-align: center;
        }

        .info-content {
            flex: 1;
        }

        .info-content label {
            display: block;
            font-weight: 600;
            color: #4a5568;
            font-size: 0.9rem;
            margin-bottom: 2px;
        }

        .info-content span {
            color: #2d3748;
            font-size: 0.95rem;
        }

        .total-row .total-amount {
            font-size: 1.2rem !important;
            font-weight: 700 !important;
            color: #e53e3e !important;
        }

        .items-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 24px;
        }

        .section-header {
            background: #f7fafc;
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-header h4 {
            margin: 0;
            color: #2d3748;
            font-size: 1.1rem;
        }

        .item-count {
            background: #4299e1;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .items-container {
            padding: 20px;
        }

        .item-card {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            margin-bottom: 12px;
            transition: all 0.3s ease;
        }

        .item-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }

        .item-card:last-child {
            margin-bottom: 0;
        }

        .item-image-container {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            overflow: hidden;
            background: #f7fafc;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .item-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .no-image {
            color: #cbd5e0;
            font-size: 2rem;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .item-meta {
            display: flex;
            gap: 20px;
            color: #718096;
            font-size: 0.9rem;
        }

        .item-total {
            font-weight: 700;
            color: #e53e3e;
            font-size: 1.1rem;
        }

        @media (max-width: 768px) {
            .order-content-grid {
                grid-template-columns: 1fr;
            }
            
            .order-header {
                flex-direction: column;
                gap: 16px;
            }
            
            .order-status-group {
                align-items: flex-start;
            }
            
            .item-card {
                flex-direction: column;
                text-align: center;
            }
            
            .item-meta {
                justify-content: center;
            }
        }
        </style>
        <?php
        
        $html = ob_get_clean();
        return $html;
    }

    private function buildOrderTimeline($order)
    {
        $timeline = [
            'pending' => [
                'title' => 'Đơn hàng được tạo',
                'desc' => 'Đơn hàng đang chờ xác nhận từ cửa hàng',
                'icon' => 'fas fa-clock'
            ],
            'processing' => [
                'title' => 'Đã xác nhận',
                'desc' => 'Cửa hàng đã xác nhận và đang chuẩn bị hàng',
                'icon' => 'fas fa-check-circle'
            ],
            'shipped' => [
                'title' => 'Đang vận chuyển',
                'desc' => 'Đơn hàng đã được giao cho đơn vị vận chuyển',
                'icon' => 'fas fa-truck'
            ],
            'delivered' => [
                'title' => 'Đã giao hàng',
                'desc' => 'Đơn hàng đã được giao thành công đến khách hàng',
                'icon' => 'fas fa-home'
            ]
        ];

        $currentStatus = $order['status'];
        $statusOrder = ['pending', 'processing', 'shipped', 'delivered'];
        $currentIndex = array_search($currentStatus, $statusOrder);

        ob_start();
        foreach ($timeline as $status => $info) {
            $itemIndex = array_search($status, $statusOrder);
            $class = '';
            
            if ($itemIndex < $currentIndex || ($currentStatus === 'delivered' && $status === 'delivered')) {
                $class = 'completed';
            } elseif ($status === $currentStatus) {
                $class = 'active';
            }
            
            echo "<div class='timeline-item {$class}'>";
            echo "<div class='timeline-content'>";
            echo "<div class='timeline-title'><i class='{$info['icon']}'></i> {$info['title']}</div>";
            echo "<div class='timeline-desc'>{$info['desc']}</div>";
            echo "</div>";
            echo "</div>";
        }
        return ob_get_clean();
    }

    private function getStatusBadgeClass($status)
    {
        // Thêm hàm helper này nếu chưa có
        $classes = [
            'pending' => 'status-pending',
            'processing' => 'status-processing',
            'shipped' => 'status-shipped',
            'delivered' => 'status-delivered',
            'cancelled' => 'status-cancelled',
            'paid' => 'status-paid',
            'unpaid' => 'status-unpaid'
        ];
        return $classes[$status] ?? 'status-default';
    }

    private function getStatusText($status)
    {
        // Thêm hàm helper này nếu chưa có
        $texts = [
            'pending' => 'Chờ xác nhận',
            'processing' => 'Đã xác nhận',
            'shipped' => 'Đang vận chuyển',
            'delivered' => 'Đã giao hàng',
            'cancelled' => 'Đã hủy'
        ];
        return $texts[$status] ?? 'Không xác định';
    }

    private function getPaymentStatusText($status)
    {
        // Thêm hàm helper này nếu chưa có
        $texts = [
            'paid' => 'Đã thanh toán',
            'unpaid' => 'Chưa thanh toán',
            'refunded' => 'Đã hoàn tiền'
        ];
        return $texts[$status] ?? 'Không xác định';
    }

}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new OrderControllerCustomer($conn);
    $controller->handleRequest();
}

