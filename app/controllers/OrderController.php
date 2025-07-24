<?php
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../models/Order.php');
require_once(__DIR__ . '/../models/User.php');
require_once(__DIR__ . '/../models/OrderItem.php');

class OrderController
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
    }

    /**
     * Xử lý tìm kiếm và lọc đơn hàng
     */
    public function searchAndFilterOrders()
    {
        try {
            // Lấy parameters từ GET request
            $status = $_GET['status'] ?? 'pending';
            $search = trim($_GET['search'] ?? '');
            $sort_by = $_GET['sort_by'] ?? 'created_at';
            $page = max(1, (int)($_GET['page'] ?? 1));
            $limit = 10;
            
            // Lọc theo khoảng ngày
            $date_from = $_GET['date_from'] ?? '';
            $date_to = $_GET['date_to'] ?? '';
            
            // Lọc theo khoảng giá
            $price_from = $_GET['price_from'] ?? '';
            $price_to = $_GET['price_to'] ?? '';
            
            // Validate và format date
            $filters = [];
            
            if (!empty($date_from)) {
                $date_from_formatted = $this->validateAndFormatDate($date_from);
                if ($date_from_formatted) {
                    $filters['date_from'] = $date_from_formatted;
                }
            }
            
            if (!empty($date_to)) {
                $date_to_formatted = $this->validateAndFormatDate($date_to, true); // true để set end of day
                if ($date_to_formatted) {
                    $filters['date_to'] = $date_to_formatted;
                }
            }
            
            // Validate price range
            if (!empty($price_from) && is_numeric($price_from) && $price_from >= 0) {
                $filters['price_from'] = (float)$price_from;
            }
            
            if (!empty($price_to) && is_numeric($price_to) && $price_to >= 0) {
                $filters['price_to'] = (float)$price_to;
            }
            
            // Gọi method mới trong Order model
            $orders = $this->orderModel->searchOrdersWithFilters(
                $status, 
                $search, 
                $filters, 
                $sort_by, 
                $page, 
                $limit
            );
            
            $totalCount = $this->orderModel->countOrdersWithFilters($status, $search, $filters);
            $totalPages = ceil($totalCount / $limit);
            
            // Return data hoặc redirect với results
            return [
                'orders' => $orders,
                'totalCount' => $totalCount,
                'totalPages' => $totalPages,
                'currentPage' => $page,
                'filters' => $filters
            ];
            
        } catch (Exception $e) {
            error_log("Error in searchAndFilterOrders: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate và format ngày tháng
     */
    private function validateAndFormatDate($date, $endOfDay = false)
    {
        try {
            // Các format date có thể nhận
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
            
            foreach ($formats as $format) {
                $dateTime = DateTime::createFromFormat($format, $date);
                if ($dateTime && $dateTime->format($format) === $date) {
                    if ($endOfDay) {
                        $dateTime->setTime(23, 59, 59);
                    } else {
                        $dateTime->setTime(0, 0, 0);
                    }
                    return $dateTime->format('Y-m-d H:i:s');
                }
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Error validating date: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Xử lý AJAX request cho search real-time
     */
    public function ajaxSearch()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            
            try {
                $input = json_decode(file_get_contents('php://input'), true);
                
                $status = $input['status'] ?? 'pending';
                $search = trim($input['search'] ?? '');
                $filters = $input['filters'] ?? [];
                $sort_by = $input['sort_by'] ?? 'created_at';
                $page = max(1, (int)($input['page'] ?? 1));
                $limit = 10;
                
                $orders = $this->orderModel->searchOrdersWithFilters(
                    $status, 
                    $search, 
                    $filters, 
                    $sort_by, 
                    $page, 
                    $limit
                );
                
                $totalCount = $this->orderModel->countOrdersWithFilters($status, $search, $filters);
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'orders' => $orders,
                        'totalCount' => $totalCount,
                        'totalPages' => ceil($totalCount / $limit),
                        'currentPage' => $page
                    ]
                ]);
                
            } catch (Exception $e) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Có lỗi xảy ra khi tìm kiếm: ' . $e->getMessage()
                ]);
            }
            
            exit;
        }
    }

    /**
     * Export filtered orders to CSV
     */
    public function exportFilteredOrders()
    {
        try {
            $status = $_GET['status'] ?? 'pending';
            $search = trim($_GET['search'] ?? '');
            $filters = [];
            
            // Parse filters from GET parameters
            if (!empty($_GET['date_from'])) {
                $date_from = $this->validateAndFormatDate($_GET['date_from']);
                if ($date_from) $filters['date_from'] = $date_from;
            }
            
            if (!empty($_GET['date_to'])) {
                $date_to = $this->validateAndFormatDate($_GET['date_to'], true);
                if ($date_to) $filters['date_to'] = $date_to;
            }
            
            if (!empty($_GET['price_from']) && is_numeric($_GET['price_from'])) {
                $filters['price_from'] = (float)$_GET['price_from'];
            }
            
            if (!empty($_GET['price_to']) && is_numeric($_GET['price_to'])) {
                $filters['price_to'] = (float)$_GET['price_to'];
            }
            
            // Get all matching orders without pagination
            $orders = $this->orderModel->searchOrdersWithFilters($status, $search, $filters, 'created_at', 1, 9999);
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="orders_' . $status . '_' . date('Y-m-d') . '.csv"');
            
            // Output CSV
            $output = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($output, [
                'ID',
                'Số đơn hàng',
                'Khách hàng',
                'Email',
                'Tổng tiền',
                'Trạng thái',
                'Thanh toán',
                'Ngày tạo',
                'Ngày cập nhật'
            ]);
            
            // CSV Data
            foreach ($orders as $order) {
                fputcsv($output, [
                    $order['order_id'],
                    $order['order_number'],
                    $order['customer_name'] ?? 'N/A',
                    $order['customer_email'] ?? 'N/A',
                    number_format($order['total_amount'], 0, '.', ',') . ' VNĐ',
                    ucfirst($order['status']),
                    ucfirst($order['payment_status']),
                    date('d/m/Y H:i', strtotime($order['created_at'])),
                    date('d/m/Y H:i', strtotime($order['updated_at']))
                ]);
            }
            
            fclose($output);
            exit;
            
        } catch (Exception $e) {
            error_log("Error exporting orders: " . $e->getMessage());
            header("Location: " . $_SERVER['HTTP_REFERER'] . "?error=export_failed");
            exit;
        }
    }

    public function handleRequest()
    {
        // Chỉ start session nếu chưa được start
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if user is logged in and has proper permissions
        if (!isset($_SESSION['user_id'])) {
            $this->redirectWithError("Bạn cần đăng nhập để thực hiện hành động này.", "../../../views/auth/login_register.php");
            return;
        }

        $user = $this->userModel->getUserById($_SESSION['user_id']);
        if (!$this->userModel->isAdmin($user) && !$this->userModel->isEmployee($user)) {
            $this->redirectWithError("Bạn không có quyền thực hiện hành động này.", "../../../views/auth/login_register.php");
            return;
        }

        $action = $_GET['action'] ?? $_POST['action'] ?? '';
        
        switch ($action) {
            case 'single_ship':
                $this->singleShip();
                break;
            case 'single_confirm':
                $this->singleConfirm();
                break;
            case 'single_complete':
                $this->singleComplete();
                break;
            case 'single_cancel':
                $this->singleCancel();
                break;
            case 'bulk_ship':
                $this->bulkShip();
                break;
            case 'bulk_confirm':
                $this->bulkConfirm();
                break;
            case 'bulk_complete':
                $this->bulkComplete();
                break;
            case 'bulk_cancel':
                $this->bulkCancel();
                break;
            case 'get_order_detail':
                $this->getOrderDetail();
                break;
            case 'ajax_search':
                $this->ajaxSearch(); // Đã có trong class
                break;
            case 'export':
                $this->exportFilteredOrders();
                break;

            case 'filter':
            case 'search':
                $this->searchAndFilterOrders();
                break;
            default:
                $this->redirectWithError("Hành động không hợp lệ.", $this->getRedirectUrl());
        }
    }

    private function singleShip()
    {
        $orderId = $_GET['id'] ?? 0;

        if (!$orderId) {
            $this->redirectWithError("ID đơn hàng không hợp lệ.", $this->getRedirectUrl());
            return;
        }

        $order = $this->orderModel->getOrderById($orderId);
        if (!$order) {
            $this->redirectWithError("Không tìm thấy đơn hàng.", $this->getRedirectUrl());
            return;
        }

        // Chỉ ship đơn hàng đang ở trạng thái 'processing'
        if ($order['status'] !== 'processing') {
            $this->redirectWithError("Chỉ có thể chuyển đơn hàng đang xử lý sang trạng thái đang vận chuyển.", $this->getRedirectUrl());
            return;
        }

        // Cập nhật trạng thái sang 'shipped'
        if ($this->orderModel->updateOrderStatus($orderId, 'shipped')) {
            $this->redirectWithSuccess("Đã chuyển đơn hàng #{$order['order_number']} sang trạng thái đang vận chuyển.");
        } else {
            $this->redirectWithError("Không thể cập nhật đơn hàng. Vui lòng thử lại.");
        }
    }

    private function singleConfirm()
    {
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            $this->redirectWithError("ID đơn hàng không hợp lệ.", $this->getRedirectUrl());
            return;
        }

        $order = $this->orderModel->getOrderById($orderId);
        if (!$order) {
            $this->redirectWithError("Không tìm thấy đơn hàng.", $this->getRedirectUrl());
            return;
        }

        if ($order['status'] !== 'pending') {
            $this->redirectWithError("Chỉ có thể xác nhận đơn hàng ở trạng thái chờ xử lý.", $this->getRedirectUrl());
            return;
        }

        if ($this->orderModel->updateOrderStatus($orderId, 'shipped')) {
            $this->redirectWithSuccess("Đã xác nhận đơn hàng #{$order['order_number']} thành công.");
        } else {
            $this->redirectWithError("Không thể xác nhận đơn hàng. Vui lòng thử lại.");
        }
    }

    private function singleComplete()
    {
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            $this->redirectWithError("ID đơn hàng không hợp lệ.", $this->getRedirectUrl());
            return;
        }

        $order = $this->orderModel->getOrderById($orderId);
        if (!$order) {
            $this->redirectWithError("Không tìm thấy đơn hàng.", $this->getRedirectUrl());
            return;
        }

        if ($order['status'] !== 'shipped') {
            $this->redirectWithError("Chỉ có thể hoàn thành đơn hàng ở trạng thái đang giao.", $this->getRedirectUrl());
            return;
        }

        if ($this->orderModel->updateOrderStatus($orderId, 'delivered', 'paid')) {
            $this->redirectWithSuccess("Đã hoàn thành đơn hàng #{$order['order_number']} thành công.");
        } else {
            $this->redirectWithError("Không thể hoàn thành đơn hàng. Vui lòng thử lại.");
        }
    }

    private function singleCancel()
    {
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            $this->redirectWithError("ID đơn hàng không hợp lệ.", $this->getRedirectUrl());
            return;
        }

        $order = $this->orderModel->getOrderById($orderId);
        if (!$order) {
            $this->redirectWithError("Không tìm thấy đơn hàng.", $this->getRedirectUrl());
            return;
        }

        if ($order['status'] === 'completed') {
            $this->redirectWithError("Không thể hủy đơn hàng đã hoàn thành.", $this->getRedirectUrl());
            return;
        }

        if ($order['status'] === 'cancelled') {
            $this->redirectWithError("Đơn hàng đã được hủy trước đó.", $this->getRedirectUrl());
            return;
        }

        if ($this->orderModel->updateOrderStatus($orderId, 'cancelled')) {
            $this->redirectWithSuccess("Đã hủy đơn hàng #{$order['order_number']} thành công.");
        } else {
            $this->redirectWithError("Không thể hủy đơn hàng. Vui lòng thử lại.");
        }
    }

    private function bulkShip()
    {
        $orderIds = $_POST['order_ids'] ?? [];

        if (empty($orderIds)) {
            $this->redirectWithError("Vui lòng chọn ít nhất một đơn hàng để chuyển trạng thái.", $this->getRedirectUrl());
            return;
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($orderIds as $orderId) {
            $order = $this->orderModel->getOrderById($orderId);
            if ($order && $order['status'] === 'processing') {
                if ($this->orderModel->updateOrderStatus($orderId, 'shipped')) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }

        if ($successCount > 0) {
            $message = "Đã chuyển trạng thái thành công $successCount đơn hàng sang 'Đang vận chuyển'.";
            if ($errorCount > 0) {
                $message .= " $errorCount đơn hàng không hợp lệ hoặc đã ở trạng thái khác.";
            }
            $this->redirectWithSuccess($message);
        } else {
            $this->redirectWithError("Không thể chuyển trạng thái đơn hàng nào. Vui lòng kiểm tra trạng thái hiện tại.");
        }
    }

    private function bulkConfirm()
    {
        $orderIds = $_POST['order_ids'] ?? [];
        
        if (empty($orderIds)) {
            $this->redirectWithError("Vui lòng chọn ít nhất một đơn hàng để xác nhận.", $this->getRedirectUrl());
            return;
        }

        $successCount = 0;
        $errorCount = 0;
        
        foreach ($orderIds as $orderId) {
            $order = $this->orderModel->getOrderById($orderId);
            if ($order && $order['status'] === 'pending') {
                if ($this->orderModel->updateOrderStatus($orderId, 'processing')) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }

        if ($successCount > 0) {
            $message = "Đã xác nhận thành công $successCount đơn hàng.";
            if ($errorCount > 0) {
                $message .= " $errorCount đơn hàng không thể xác nhận.";
            }
            $this->redirectWithSuccess($message);
        } else {
            $this->redirectWithError("Không thể xác nhận đơn hàng nào. Vui lòng kiểm tra trạng thái đơn hàng.");
        }
    }

    private function bulkComplete()
    {
        $orderIds = $_POST['order_ids'] ?? [];
        
        if (empty($orderIds)) {
            $this->redirectWithError("Vui lòng chọn ít nhất một đơn hàng để hoàn thành.", $this->getRedirectUrl());
            return;
        }

        $successCount = 0;
        $errorCount = 0;
        
        foreach ($orderIds as $orderId) {
            $order = $this->orderModel->getOrderById($orderId);
            if ($order && $order['status'] === 'shipped') {
                if ($this->orderModel->updateOrderStatus($orderId, 'delivered', 'paid')) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }

        if ($successCount > 0) {
            $message = "Đã hoàn thành thành công $successCount đơn hàng.";
            if ($errorCount > 0) {
                $message .= " $errorCount đơn hàng không thể hoàn thành.";
            }
            $this->redirectWithSuccess($message);
        } else {
            $this->redirectWithError("Không thể hoàn thành đơn hàng nào. Vui lòng kiểm tra trạng thái đơn hàng.");
        }
    }

    private function bulkCancel()
    {
        $orderIds = $_POST['order_ids'] ?? [];
        
        if (empty($orderIds)) {
            $this->redirectWithError("Vui lòng chọn ít nhất một đơn hàng để hủy.", $this->getRedirectUrl());
            return;
        }

        $successCount = 0;
        $errorCount = 0;
        
        foreach ($orderIds as $orderId) {
            $order = $this->orderModel->getOrderById($orderId);
            if ($order && $order['status'] !== 'completed' && $order['status'] !== 'cancelled') {
                if ($this->orderModel->updateOrderStatus($orderId, 'cancelled')) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            } else {
                $errorCount++;
            }
        }

        if ($successCount > 0) {
            $message = "Đã hủy thành công $successCount đơn hàng.";
            if ($errorCount > 0) {
                $message .= " $errorCount đơn hàng không thể hủy.";
            }
            $this->redirectWithSuccess($message);
        } else {
            $this->redirectWithError("Không thể hủy đơn hàng nào. Vui lòng kiểm tra trạng thái đơn hàng.");
        }
    }

    private function getOrderDetail()
    {
        $orderId = $_GET['id'] ?? 0;
        
        if (!$orderId) {
            echo json_encode(['success' => false, 'message' => 'ID đơn hàng không hợp lệ']);
            return;
        }

        try {
            // Get order details
            $order = $this->orderModel->getOrderById($orderId);
            if (!$order) {
                echo json_encode(['success' => false, 'message' => 'Không tìm thấy đơn hàng']);
                return;
            }

            // Get customer information
            $customer = $this->userModel->getUserById($order['userCustomer_id']);
            
            // Get order items
            $orderItems = $this->orderItemModel->getOrderItemsByOrderId($orderId);

            // Build HTML response
            $html = $this->buildOrderDetailHtml($order, $customer, $orderItems);
            
            echo json_encode(['success' => true, 'html' => $html]);
        } catch (Exception $e) {
            error_log("Error getting order detail: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra khi tải chi tiết đơn hàng']);
        }
    }

    private function buildOrderDetailHtml($order, $customer, $orderItems)
    {
        ob_start();
        ?>
        <div class="order-detail-container">
            <div class="order-detail-info">
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
                    <!-- Customer Information Card -->
                    <div class="info-card customer-card">
                        <div class="card-header">
                            <h4><i class="fas fa-user"></i> Thông tin khách hàng</h4>
                        </div>
                        <div class="card-content">
                            <div class="info-row">
                                <i class="fas fa-signature info-icon"></i>
                                <div class="info-content">
                                    <label>Họ và tên</label>
                                    <span><?php echo htmlspecialchars($customer['full_name'] ?? 'N/A'); ?></span>
                                </div>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-envelope info-icon"></i>
                                <div class="info-content">
                                    <label>Email</label>
                                    <span><?php echo htmlspecialchars($customer['email'] ?? 'N/A'); ?></span>
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
                                    <label>Ngày tạo</label>
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
                <div class="items-section">
                    <div class="section-header">
                        <h4><i class="fas fa-shopping-cart"></i> Sản phẩm trong đơn hàng</h4>
                        <span class="item-count"><?php echo count($orderItems); ?> sản phẩm</span>
                    </div>
                    
                    <div class="items-container">
                        <?php foreach ($orderItems as $item): ?>
                        <div class="item-card">
                            <div class="item-image-container">
                                <?php if (!empty($item['image_url'])): ?>
                                    <img src="/WebsitePet/public/uploads/product/<?php echo htmlspecialchars($item['image_url']); ?>" 
                                        alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                        class="item-image">
                                <?php else: ?>
                                    <div class="no-image">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="item-details">
                                <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                <div class="item-meta">
                                    <span class="quantity">
                                        <i class="fas fa-cube"></i>
                                        Số lượng: <?php echo $item['quantity']; ?>
                                    </span>
                                    <span class="price">
                                        <i class="fas fa-tag"></i>
                                        <?php echo number_format($item['price'], 0, '.', ',') . ' VNĐ'; ?>
                                    </span>
                                </div>
                            </div>
                            <div class="item-total">
                                <?php echo number_format($item['quantity'] * $item['price'], 0, '.', ',') . ' VNĐ'; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .order-detail-container {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fb;
            border-radius: 12px;
            overflow: hidden;
        }

        .order-detail-info {
            max-height: 75vh;
            overflow-y: auto;
            padding: 24px;
            background: white;
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f1f5f9;
        }

        .order-detail-info::-webkit-scrollbar {
            width: 6px;
        }

        .order-detail-info::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        .order-detail-info::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 3px;
        }

        .order-detail-info::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }

        /* Header Styles */
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 32px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            flex-wrap: wrap;
            gap: 16px;
        }

        .order-title h3 {
            margin: 0 0 8px 0;
            color: #2d3748;
            font-size: 1.75rem;
            font-weight: 700;
        }

        .order-title h3 i {
            color: #4299e1;
            margin-right: 8px;
        }

        .order-date {
            color: #718096;
            font-size: 0.9rem;
        }

        .order-date i {
            margin-right: 4px;
        }

        .order-status-group {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 10px;
            border-radius: 25px;
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }

        .status-badge:hover {
            transform: translateY(-1px);
        }

        /* Content Grid */
        .order-content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            margin-bottom: 32px;
        }

        .info-card {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .info-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
        }

        .card-header {
            padding: 20px 20px 0 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        .card-header h4 {
            margin: 0 0 16px 0;
            color: #2d3748;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .card-header h4 i {
            color: #4299e1;
        }

        .card-content {
            padding: 20px;
        }

        .info-row {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 16px;
            padding: 12px;
            border-radius: 8px;
            transition: background-color 0.2s ease;
        }

        .info-row:hover {
            background-color: #f7fafc;
        }

        .info-row.total-row {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            margin-top: 8px;
        }

        .info-row.total-row .info-icon {
            color: #ffd700;
        }

        .info-icon {
            color: #4299e1;
            font-size: 1.1rem;
            margin-top: 2px;
            min-width: 20px;
        }

        .info-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .info-content label {
            font-size: 0.85rem;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-row.total-row .info-content label {
            color: #e2e8f0;
        }

        .info-content span {
            font-size: 0.95rem;
            color: #2d3748;
            font-weight: 500;
        }

        .total-amount {
            color: #e53e3e !important;
            font-weight: 700 !important;
            font-size: 1.3rem !important;
        }

        .info-row.total-row .total-amount {
            color: #ffd700 !important;
        }

        /* Items Section */
        .items-section {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            border-radius: 12px 12px 0 0;
        }

        .section-header h4 {
            margin: 0;
            color: #2d3748;
            font-size: 1.3rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-header h4 i {
            color: #4299e1;
        }

        .item-count {
            background: #4299e1;
            color: white;
            padding: 6px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .items-container {
            padding: 24px;
        }

        .item-card {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            margin-bottom: 16px;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .item-card:hover {
            transform: translateX(4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border-color: #4299e1;
            background: white;
        }

        .item-card:last-child {
            margin-bottom: 0;
        }

        .item-image-container {
            flex-shrink: 0;
        }

        .item-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e2e8f0;
            transition: transform 0.2s ease;
        }

        .item-card:hover .item-image {
            transform: scale(1.05);
        }

        .no-image {
            width: 70px;
            height: 70px;
            background: #edf2f7;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #a0aec0;
            font-size: 1.5rem;
        }

        .item-details {
            flex: 1;
        }

        .item-name {
            font-weight: 600;
            color: #2d3748;
            font-size: 1.1rem;
            margin-bottom: 8px;
        }

        .item-meta {
            display: flex;
            gap: 24px;
            flex-wrap: wrap;
        }

        .item-meta span {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #718096;
            font-size: 0.9rem;
        }

        .item-meta i {
            color: #4299e1;
        }

        .item-total {
            font-weight: 700;
            color: #e53e3e;
            font-size: 1.2rem;
            text-align: right;
        }

        /* Status Colors */
        .status-pending { 
            background: linear-gradient(135deg, #ffd93d 0%, #ff9f00 100%);
            color: #744210; 
        }
        .status-approved { 
            background: linear-gradient(135deg, #74b9ff 0%, #0984e3 100%);
            color: white; 
        }
        .status-completed { 
            background: linear-gradient(135deg, #00b894 0%, #00a085 100%);
            color: white; 
        }
        .status-refused { 
            background: linear-gradient(135deg, #e17055 0%, #d63031 100%);
            color: white; 
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .order-detail-info {
                padding: 16px;
            }

            .order-header {
                flex-direction: column;
                align-items: stretch;
                gap: 16px;
            }

            .order-status-group {
                justify-content: flex-start;
            }

            .order-content-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .item-card {
                flex-direction: column;
                align-items: flex-start;
                gap: 16px;
            }

            .item-details {
                width: 100%;
            }

            .item-total {
                align-self: flex-end;
                text-align: right;
            }

            .item-meta {
                justify-content: space-between;
                width: 100%;
            }

            .section-header {
                flex-direction: column;
                gap: 12px;
                align-items: flex-start;
            }
        }

        @media (max-width: 480px) {
            .order-title h3 {
                font-size: 1.4rem;
            }

            .status-badge {
                font-size: 0.7rem;
                padding: 8px 12px;
            }

            .item-image,
            .no-image {
                width: 60px;
                height: 60px;
            }
        }
        </style>
        <?php
        return ob_get_clean();
    }

    private function getStatusText($status)
    {
        $statusTexts = [
            'pending'    => 'Chờ xác nhận',    // Chờ xác nhận
            'processing' => 'Đã xác nhận',   // Đã xác nhận, đang xử lý
            'shipped'    => 'Đã giao cho đơn vị vận chuyển',    // Đã giao cho đơn vị vận chuyển
            'delivered'  => 'Đã giao đến khách hàng',  // Đã giao đến khách hàng
            'cancelled'  => 'Đã hủy',    // Đã hủy
        ];
        return $statusTexts[$status] ?? 'Không xác định';
    }

    private function getPaymentStatusText($status)
    {
        $statusTexts = [
            'unpaid' => 'Chưa thanh toán',
            'paid' => 'Đã thanh toán',
            'refunded' => 'Đã hoàn tiền'
        ];
        return $statusTexts[$status] ?? 'Không xác định';
    }

    private function getStatusBadgeClass($status)
    {
        $classes = [
            'pending'    => 'status-pending',    // Chờ xác nhận
            'processing' => 'status-approved',   // Đã xác nhận, đang xử lý
            'shipped'    => 'status-shipped',    // Đã giao cho đơn vị vận chuyển
            'delivered'  => 'status-completed',  // Đã giao đến khách hàng
            'cancelled'  => 'status-refused',    // Đã hủy
            'unpaid' => 'status-pending',
            'paid' => 'status-completed',
            'refunded' => 'status-refused'
        ];
        return $classes[$status] ?? 'status-pending';
    }

    private function getRedirectUrl()
    {
        return $_GET['redirect_url'] ?? $_POST['redirect_url'] ?? '../../views/admin/orders/order_manager.php';
    }

    private function redirectWithSuccess($message)
    {
        $_SESSION['message'] = $message;
        $redirectUrl = $this->getRedirectUrl();
        header("Location: " . $redirectUrl);
        exit;
    }

    private function redirectWithError($message, $customUrl = null)
    {
        $_SESSION['error'] = $message;
        $redirectUrl = $customUrl ?? $this->getRedirectUrl();
        header("Location: " . $redirectUrl);
        exit;
    }
}

// Handle the request
if ($_SERVER['REQUEST_METHOD'] === 'GET' || $_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new OrderController($conn);
    $controller->handleRequest();
}
?>