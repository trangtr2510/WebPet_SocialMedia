<?php
include(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ .'/../../../app/models/Order.php');
require_once(__DIR__ .'/../../../app/models/User.php');

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$user_type = $is_logged_in ? $_SESSION['user_type'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$orderModel = new Order($conn);
$userModel = new User($conn);

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../views/auth/login_register.php");
    exit;
}

// Lấy thông tin người dùng
$user = $userModel->getUserById($_SESSION['user_id']);

// Kiểm tra vai trò
if (!$userModel->isAdmin($user) && !$userModel->isEmployee($user)) {
    // Nếu không phải admin hoặc employee thì chuyển về login_register
    header("Location: ../../../views/auth/login_register.php");
    exit;
}

// Lấy thông tin pagination và filter
$page = $_GET['page'] ?? 1;
$limit = 10;
$search = $_GET['search'] ?? '';
$filter_status = $_GET['filter_status'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$tab = $_GET['tab'] ?? 'pending';

// Lấy dữ liệu cho tab pending (chờ xử lý)
$pendingOrders = $orderModel->getOrdersByStatus('pending', $search, $sort_by, $page, $limit);
$pendingCount = $orderModel->countOrderBySpecificStatus('pending');

// Lấy dữ liệu cho tab pending (chờ xử lý)
$processingOrders = $orderModel->getOrdersByStatus('processing', $search, $sort_by, $page, $limit);
$processingCount = $orderModel->countOrderBySpecificStatus('processing');

// Lấy dữ liệu cho tab processing (đang xử lý)
$shippedOrders = $orderModel->getOrdersByStatus('shipped', $search, $sort_by, $page, $limit);
$shippedCount = $orderModel->countOrderBySpecificStatus('shipped');

// Lấy dữ liệu cho tab completed (hoàn thành)
$completedOrders = $orderModel->getOrdersByStatus('delivered', $search, $sort_by, $page, $limit);
$completedCount = $orderModel->countOrderBySpecificStatus('delivered');

// Lấy dữ liệu cho tab completed (hoàn thành)
$cancelledOrders = $orderModel->getOrdersByStatus('cancelled', $search, $sort_by, $page, $limit);
$cancelledCount = $orderModel->countOrderBySpecificStatus('cancelled');

// Tính toán pagination
$totalPendingPages = ceil($pendingCount / $limit);
$totalProcessingPages = ceil($processingCount / $limit);
$totalShippedPages = ceil($shippedCount / $limit);
$totalCompletedPages = ceil($completedCount / $limit);
$totalCancelledPages = ceil($cancelledCount / $limit);

// Helper function để format ngày
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

// Helper function để format tiền
function formatMoney($amount) {
    return number_format($amount, 0, '.', ',') . ' VNĐ';
}

// Helper function để lấy avatar path
function getAvatarPath($avatar) {
    return !empty($avatar) ? "../../../public/uploads/avatar/" . $avatar : "../../../public/uploads/avatar/default.jpg";
}

// Function để lấy badge class theo status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending': // Chờ xử lý
            return 'status-pending';
        case 'processing': // Chờ xử lý
            return 'status-approved';
        case 'shipped':    // Đang xử lý
            return 'status-shipped';
        case 'delivered':  // Hoàn thành
            return 'status-completed';
        case 'cancelled':  // Đã hủy
            return 'status-refused';
        default:
            return 'status-pending'; // Mặc định
    }
}

// Function để lấy số trang total dựa vào tab
function getTotalPages($tab, $totalPendingPages, $totalProcessingPages, $totalShippedPages, $totalCompletedPages, $totalCancelledPages) {
    switch ($tab) {
        case 'pendding':
            return $totalPendingPages;
        case 'processing':
            return $totalProcessingPages;
        case 'shipped':
            return $totalShippedPages;
        case 'delivered':
            return $totalCompletedPages;
        case 'cancelled':
            return $totalCancelledPages;
        default:
            return $totalPendingPages;
    }
}

$totalPages = getTotalPages($tab, $totalPendingPages, $totalProcessingPages, $totalShippedPages, $totalCompletedPages, $totalCancelledPages);


?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management Interface</title>
    <link rel="stylesheet" href="../../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../../public/css/admin/admin.css">
    <script type="text/javascript" src="../../../public/js/admin.js" defer></script>
    <!-- <script src="../../../public/js/order_manager.js"></script> -->
    <style>
        .pm-range-filter {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .pm-range-inputs {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .pm-range-inputs input {
            width: 100px;
            padding: 6px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .pm-range-inputs span {
            color: #666;
            font-weight: bold;
        }

        #rangeLabel {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        .pm-posts-filters {
            display: flex;
            align-items: end;
            gap: 15px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }

        /* Responsive cho mobile */
        @media (max-width: 768px) {
            .pm-posts-filters {
                flex-direction: column;
                align-items: stretch;
                gap: 10px;
            }
            
            .pm-range-inputs {
                justify-content: center;
            }
            
            .pm-range-inputs input {
                width: 80px;
            }
        }
    </style>
</head>

<body>
    <nav id="sidebar" class="sidebar">
        <ul>
            <!-- Toggle Button at Top -->
            <li class="toggle-section">
                <div class="toggle-container">
                    <button onclick="toggleSidebar()" id="toggle-btn" class="toggle-btn" data-tooltip="Toggle Menu">
                        <i class="fas fa-angle-double-left icon_rotate"></i>
                    </button>
                    <div class="collapsed-avatar">
                        <img src="../../../public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="User Avatar" class="user-avatar-collapsed">
                    </div>
                </div>
                <div class="logo">
                    <img src="../../../public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="User Avatar" class="user-avatar">
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($user_type); ?></span>
                    </div>
                </div>
            </li>
             <!-- Navigation Items -->
            <li>
                <a href="../dashboard.php" data-tooltip="Dashboard">
                    <i class="fas fa-chart-line icon_nav"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../category/index.php" data-tooltip="Danh mục">
                    <i class="fa-solid fa-list icon_nav"></i>
                    <span>Danh mục</span>
                </a>
            </li>
            <li>
                <a href="../products/product_manager.php" data-tooltip="Shop">
                    <i class="fa-solid fa-paw icon_nav"></i>
                    <span>Shop</span>
                </a>
            </li>
            <li>
                <a href="../post/post_manager.php" data-tooltip="Diễn đàn">
                    <i class="fa-solid fa-share-nodes icon_nav"></i>
                    <span>Diễn đàn</span>
                </a>
            </li>
            <li>
                <button onclick=toggleSubMenu(this) class="dropdown-btn">
                  <i class="fa-solid fa-user-group icon_nav"></i>
                  <span>Quản lý tài khoản</span>
                  <i class="fa-solid fa-chevron-down"></i>
                </button>
                <ul class="sub-menu">
                  <div>
                    <li><a href="../users/quanlynhanvien/quanlynhanvien.php">Quản lý nhân viên</a></li>
                    <li><a href="../users/quanlykhachhang/quanlykhachhang.php#">Quản lý khách hàng</a></li>
                  </div>
                </ul>
            </li>
            <li class="active">
                <a href="../orders/order_manager.php" data-tooltip="Order">
                    <i class="fa-solid fa-truck"></i>
                    <span>Quản lý đơn hàng</span>
                </a>
            </li>
             <li>
                <a href="../../../app/controllers/LogoutController.php" data-tooltip="Order">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </nav>

    <div class="pm-container">
        <h2 style="margin: 1rem 0rem; color: #003459;">Order Manager</h2>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Tab Navigation -->
        <div class="pm-tab-navigation">
            <button class="pm-tab-nav-item <?php echo $tab === 'pending' ? 'pm-active' : ''; ?>" onclick="showOrderTab('pending')">
                Chờ xử lý <span class="count-badge"><?php echo $pendingCount; ?></span>
            </button>
            <button class="pm-tab-nav-item <?php echo $tab === 'processing' ? 'pm-active' : ''; ?>" onclick="showOrderTab('processing')">
                Đang xử lý <span class="count-badge"><?php echo $processingCount; ?></span>
            </button>
            <button class="pm-tab-nav-item <?php echo $tab === 'shipped' ? 'pm-active' : ''; ?>" onclick="showOrderTab('shipped')">
                Đang giao <span class="count-badge"><?php echo $shippedCount; ?></span>
            </button>
            <button class="pm-tab-nav-item <?php echo $tab === 'delivered' ? 'pm-active' : ''; ?>" onclick="showOrderTab('delivered')">
                Hoàn thành <span class="count-badge"><?php echo $completedCount; ?></span>
            </button>
            <button class="pm-tab-nav-item <?php echo $tab === 'cancelled' ? 'pm-active' : ''; ?>" onclick="showOrderTab('cancelled')">
                Đã hủy <span class="count-badge"><?php echo $cancelledCount; ?></span>
            </button>
        </div>

        <!-- Search and Filter Form -->
        <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
            <div class="pm-posts-filters">
                <div class="pm-search-box">
                    <input type="text" name="search" placeholder="Tìm kiếm theo mã đơn hàng hoặc tên khách hàng" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <div>
                    <span>Sắp xếp theo</span>
                    <select name="sort_by" class="pm-filter-select" id="sortBySelect">
                        <option value="created_at">Ngày tạo</option>
                        <option value="total_amount">Tổng tiền</option>
                    </select>
                </div>
                
                <div class="pm-range-filter">
                    <span id="rangeLabel">Lọc theo khoảng</span>
                    <div class="pm-range-inputs">
                        <input type="text" name="range_from" placeholder="Từ" id="rangeFrom">
                        <span>-</span>
                        <input type="text" name="range_to" placeholder="Đến" id="rangeTo">
                    </div>
                </div>
                
                <button class="pm-apply-btn">Apply</button>
                <a href="order_manager.php?tab=<?php echo $tab; ?>" class="pm-reset-btn">Reset</a>
            </div>
        </form>

        <!-- Pending Orders Tab -->
        <div id="pending-tab" class="pm-tab-content <?php echo $tab === 'pending' ? 'pm-active' : ''; ?>" style="display: <?php echo $tab === 'pending' ? 'block' : 'none'; ?>;">
            <div class="pm-community-header">
                <span class="pm-breadcrumb">Order</span> / Chờ xử lý
            </div>

            <form method="POST" action="../../../app/controllers/OrderController.php" id="pending-form">
                <input type="hidden" name="action" value="">
                <input type="hidden" name="tab" value="pending">
                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                
                <div class="pm-table-controls">
                    <div class="pm-bulk-select">
                        <label>
                            <input type="checkbox" id="select-all-pending" onchange="toggleSelectAll('pending')">
                            Check all (<span id="selected-count-pending">0</span>)
                        </label>
                    </div>
                    <div class="pm-bulk-actions">
                        <span>With selected:</span>
                        <button type="button" class="pm-edit-btn" onclick="bulkOrderAction('confirm')" title="Xác nhận đơn hàng">
                            <i class="fa-solid fa-check"></i> Confirm
                        </button>
                        <button type="button" class="pm-delete-btn" onclick="bulkOrderAction('cancel')" title="Hủy đơn hàng">
                            <i class="fa-solid fa-ban"></i> Cancel
                        </button>
                    </div>
                </div>

                <div class="pm-table-container">
                    <table class="pm-data-table">
                        <thead>
                            <tr>
                                <th class="pm-checkbox"></th>
                                <th class="pm-post-id">ID</th>
                                <th>Số đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th class="pm-status">Trạng thái</th>
                                <th>Thanh toán</th>
                                <th class="pm-dates">Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingOrders as $order): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="order_ids[]" value="<?php echo $order['order_id']; ?>" class="post-checkbox-pending">
                                </td>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <div class="author-info">
                                        <img src="<?php echo getAvatarPath($order['customer_avatar'] ?? ''); ?>" alt="Customer Avatar" class="author-avatar">
                                        <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo formatMoney($order['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($order['created_at']); ?></td>
                                <td>
                                    <div class="pm-post-actions">
                                        <div class="action-buttons">
                                            <a href="../../../app/controllers/OrderController.php?action=single_confirm&id=<?php echo $order['order_id']; ?>&tab=pending&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-approve" title="Confirm">
                                               <i class="fa-solid fa-check"></i> Confirm
                                            </a>
                                            <a href="../../../app/controllers/OrderController.php?action=single_cancel&id=<?php echo $order['order_id']; ?>&tab=pending&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-delete" title="Cancel" onclick="return confirm('Are you sure?')">
                                               <i class="fa-solid fa-ban"></i> Cancel
                                            </a>
                                            <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewOrderDetail(<?php echo $order['order_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Pagination for Pending -->
            <div class="pagination pm-pagination">
                <a href="?tab=pending&page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-angle-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPendingPages; ?></span>
                <a href="?tab=pending&page=<?php echo min($totalPendingPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page >= $totalPendingPages ? 'disabled' : ''; ?>">
                    Next <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>

        <!-- Processing Orders Tab -->
        <div id="processing-tab" class="pm-tab-content <?php echo $tab === 'processing' ? 'pm-active' : ''; ?>" style="display: <?php echo $tab === 'processing' ? 'block' : 'none'; ?>;">
            <div class="pm-community-header">
                <span class="pm-breadcrumb">Order</span> / Đang xử lý
            </div>

            <form method="POST" action="../../../app/controllers/OrderController.php" id="processing-form">
                <input type="hidden" name="action" value="">
                <input type="hidden" name="tab" value="processing">
                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                
                <div class="pm-table-controls">
                    <div class="pm-bulk-select">
                        <label>
                            <input type="checkbox" id="select-all-processing" onchange="toggleSelectAll('processing')">
                            Check all (<span id="selected-count-processing">0</span>)
                        </label>
                    </div>
                    <div class="pm-bulk-actions">
                        <span>With selected:</span>
                        <button type="button" class="pm-edit-btn" onclick="bulkOrderAction('ship')" title="Giao hàng">
                            <i class="fa-solid fa-truck"></i> Ship
                        </button>
                        <button type="button" class="pm-delete-btn" onclick="bulkOrderAction('cancel')" title="Hủy đơn hàng">
                            <i class="fa-solid fa-ban"></i> Cancel
                        </button>
                    </div>
                </div>

                <div class="pm-table-container">
                    <table class="pm-data-table">
                        <thead>
                            <tr>
                                <th class="pm-checkbox"></th>
                                <th class="pm-post-id">ID</th>
                                <th>Số đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th class="pm-status">Trạng thái</th>
                                <th>Thanh toán</th>
                                <th class="pm-dates">Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($processingOrders as $order): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="order_ids[]" value="<?php echo $order['order_id']; ?>" class="post-checkbox-processing">
                                </td>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <div class="author-info">
                                        <img src="<?php echo getAvatarPath($order['customer_avatar'] ?? ''); ?>" alt="Customer Avatar" class="author-avatar">
                                        <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo formatMoney($order['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($order['created_at']); ?></td>
                                <td>
                                    <div class="pm-post-actions">
                                        <div class="action-buttons">
                                            <a href="../../../app/controllers/OrderController.php?action=single_ship&id=<?php echo $order['order_id']; ?>&tab=processing&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-approve" title="Ship">
                                               <i class="fa-solid fa-truck"></i> Ship
                                            </a>
                                            <a href="../../../app/controllers/OrderController.php?action=single_cancel&id=<?php echo $order['order_id']; ?>&tab=processing&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-delete" title="Cancel" onclick="return confirm('Are you sure?')">
                                               <i class="fa-solid fa-ban"></i> Cancel
                                            </a>
                                            <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewOrderDetail(<?php echo $order['order_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Pagination for Processing -->
            <div class="pagination pm-pagination">
                <a href="?tab=processing&page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-angle-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalProcessingPages; ?></span>
                <a href="?tab=processing&page=<?php echo min($totalProcessingPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page >= $totalProcessingPages ? 'disabled' : ''; ?>">
                    Next <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>

        <!-- Shipped Orders Tab -->
        <div id="shipped-tab" class="pm-tab-content <?php echo $tab === 'shipped' ? 'pm-active' : ''; ?>" style="display: <?php echo $tab === 'shipped' ? 'block' : 'none'; ?>;">
            <div class="pm-community-header">
                <span class="pm-breadcrumb">Order</span> / Đang giao
            </div>

            <form method="POST" action="../../../app/controllers/OrderController.php" id="shipped-form">
                <input type="hidden" name="action" value="">
                <input type="hidden" name="tab" value="shipped">
                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                
                <div class="pm-table-controls">
                    <div class="pm-bulk-select">
                        <label>
                            <input type="checkbox" id="select-all-shipped" onchange="toggleSelectAll('shipped')">
                            Check all (<span id="selected-count-shipped">0</span>)
                        </label>
                    </div>
                    <div class="pm-bulk-actions">
                        <span>With selected:</span>
                        <button type="button" class="pm-edit-btn" onclick="bulkOrderAction('complete')" title="Hoàn thành đơn hàng">
                            <i class="fa-solid fa-check"></i> Complete
                        </button>
                        <button type="button" class="pm-delete-btn" onclick="bulkOrderAction('cancel')" title="Hủy đơn hàng">
                            <i class="fa-solid fa-ban"></i> Cancel
                        </button>
                    </div>
                </div>

                <div class="pm-table-container">
                    <table class="pm-data-table">
                        <thead>
                            <tr>
                                <th class="pm-checkbox"></th>
                                <th class="pm-post-id">ID</th>
                                <th>Số đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th class="pm-status">Trạng thái</th>
                                <th>Thanh toán</th>
                                <th class="pm-dates">Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shippedOrders as $order): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="order_ids[]" value="<?php echo $order['order_id']; ?>" class="post-checkbox-shipped">
                                </td>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <div class="author-info">
                                        <img src="<?php echo getAvatarPath($order['customer_avatar'] ?? ''); ?>" alt="Customer Avatar" class="author-avatar">
                                        <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo formatMoney($order['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($order['created_at']); ?></td>
                                <td>
                                    <div class="pm-post-actions">
                                        <div class="action-buttons">
                                            <a href="../../../app/controllers/OrderController.php?action=single_complete&id=<?php echo $order['order_id']; ?>&tab=shipped&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-approve" title="Complete">
                                               <i class="fa-solid fa-check"></i> Complete
                                            </a>
                                            <a href="../../../app/controllers/OrderController.php?action=single_cancel&id=<?php echo $order['order_id']; ?>&tab=shipped&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-delete" title="Cancel" onclick="return confirm('Are you sure?')">
                                               <i class="fa-solid fa-ban"></i> Cancel
                                            </a>
                                            <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewOrderDetail(<?php echo $order['order_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Pagination for Shipped -->
            <div class="pagination pm-pagination">
                <a href="?tab=shipped&page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-angle-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalShippedPages; ?></span>
                <a href="?tab=shipped&page=<?php echo min($totalShippedPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page >= $totalShippedPages ? 'disabled' : ''; ?>">
                    Next <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>

        <!-- Completed Orders Tab -->
        <div id="delivered-tab" class="pm-tab-content <?php echo $tab === 'delivered' ? 'pm-active' : ''; ?>" style="display: <?php echo $tab === 'delivered' ? 'block' : 'none'; ?>;">
            <div class="pm-community-header">
                <span class="pm-breadcrumb">Order</span> / Hoàn thành
            </div>

            <div class="pm-table-container">
                <table class="pm-data-table">
                    <thead>
                        <tr>
                            <th class="pm-post-id">ID</th>
                            <th>Số đơn hàng</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th class="pm-status">Trạng thái</th>
                            <th>Thanh toán</th>
                            <th class="pm-dates">Ngày tạo</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedOrders as $order): ?>
                        <tr>
                            <td><?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                            <td>
                                <div class="author-info">
                                    <img src="<?php echo getAvatarPath($order['customer_avatar'] ?? ''); ?>" alt="Customer Avatar" class="author-avatar">
                                    <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span>
                                </div>
                            </td>
                            <td><?php echo formatMoney($order['total_amount']); ?></td>
                            <td>
                                <span class="status-badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo getStatusBadgeClass($order['payment_status']); ?>">
                                    <?php echo ucfirst($order['payment_status']); ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($order['created_at']); ?></td>
                            <td>
                                <div class="pm-post-actions">
                                    <div class="action-buttons">
                                        <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewOrderDetail(<?php echo $order['order_id']; ?>)" title="View Details">
                                            <i class="fas fa-eye"></i> Details
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Pending -->
            <div class="pagination pm-pagination">
                <a href="?tab=pending&page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-angle-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPendingPages; ?></span>
                <a href="?tab=pending&page=<?php echo min($totalPendingPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page >= $totalPendingPages ? 'disabled' : ''; ?>">
                    Next <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>

        <!-- Cancelled Orders Tab -->
        <div id="cancelled-tab" class="pm-tab-content <?php echo $tab === 'cancelled' ? 'pm-active' : ''; ?>" style="display: <?php echo $tab === 'cancelled' ? 'block' : 'none'; ?>;">
            <div class="pm-community-header">
                <span class="pm-breadcrumb">Order</span> / Đã hủy
            </div>
                <div class="pm-table-container">
                    <table class="pm-data-table">
                        <thead>
                            <tr>
                                <th class="pm-checkbox"></th>
                                <th class="pm-post-id">ID</th>
                                <th>Số đơn hàng</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th class="pm-status">Trạng thái</th>
                                <th>Thanh toán</th>
                                <th class="pm-dates">Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingOrders as $order): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="order_ids[]" value="<?php echo $order['order_id']; ?>" class="post-checkbox-pending">
                                </td>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['order_number']); ?></td>
                                <td>
                                    <div class="author-info">
                                        <img src="<?php echo getAvatarPath($order['customer_avatar'] ?? ''); ?>" alt="Customer Avatar" class="author-avatar">
                                        <span><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></span>
                                    </div>
                                </td>
                                <td><?php echo formatMoney($order['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['status']); ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo getStatusBadgeClass($order['payment_status']); ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($order['created_at']); ?></td>
                                <td>
                                    <div class="pm-post-actions">
                                        <div class="action-buttons">
                                            <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewOrderDetail(<?php echo $order['order_id']; ?>)" title="View Details">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <!-- Pagination for Pending -->
            <div class="pagination pm-pagination">
                <a href="?tab=pending&page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-angle-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPendingPages; ?></span>
                <a href="?tab=pending&page=<?php echo min($totalPendingPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_status=<?php echo $filter_status; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page >= $totalPendingPages ? 'disabled' : ''; ?>">
                    Next <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Order Detail Modal -->
    <div id="order-detail-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeOrderModal()">&times;</span>
            <h2>Chi tiết đơn hàng</h2>
            <div id="order-detail-content">
                <!-- Order details will be loaded here -->
            </div>
        </div>
    </div>
    <script src="../../../public/js/order_manager.js"></script>
</body>
</html>