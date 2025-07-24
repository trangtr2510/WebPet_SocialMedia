<?php
// THÊM DEBUG INFO VÀO ĐẦU FILE
error_log("=== REQUEST DEBUG INFO ===");
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
error_log("GET params: " . print_r($_GET, true));
error_log("POST params: " . print_r($_POST, true));
error_log("============================");

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../../app/controllers/myAccountController.php');
include_once(__DIR__ .'/../../config/config.php');
include_once(__DIR__ .'/../../app/models/User.php');
include_once(__DIR__ . '/../../app/controllers/OrderControllerCustomer.php');

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../../auth/login_register.php');
    exit();
}

// Lấy danh sách đơn hàng của người dùng
$orderController = new OrderControllerCustomer($conn);
$userOrders = [];
if ($is_logged_in) {
    // Sử dụng order model để lấy đơn hàng của user hiện tại
    $orderModel = new Order($conn);
    $userOrders = $orderModel->getOrdersWithDetails($_SESSION['user_id']);
}

// Lấy thông tin người dùng
$controller = new MyAccountController($conn);
$userProfile = $controller->getUserProfile();

if (!$userProfile) {
    echo "Không thể tải thông tin người dùng";
    exit();
}

// Tách tên và họ
$nameParts = explode(' ', $userProfile['full_name'] ?? '');
$firstName = $nameParts[0] ?? '';
$lastName = isset($nameParts[1]) ? implode(' ', array_slice($nameParts, 1)) : '';

// XỬ LÝ CÁC REQUEST (POST VÀ GET)
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$orderAction = $_GET['order_action'] ?? $_POST['order_action'] ?? ''; // Thêm GET order_action

// Xử lý order_action từ cả GET và POST
if (!empty($orderAction)) {
    // Đảm bảo response là JSON
    header('Content-Type: application/json');

    switch ($orderAction) {
        case 'customer_cancel_order':
        case 'customer_confirm_received':
        case 'customer_get_order_detail':
            $orderController->handleRequest();
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => 'Order action không hợp lệ: ' . $orderAction
            ]);
    }
    exit();
}

// Xử lý các POST request khác
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Đảm bảo response là JSON
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_profile':
                $data = [
                    'firstName' => $_POST['firstName'] ?? '',
                    'lastName' => $_POST['lastName'] ?? '',
                    'email' => $_POST['email'] ?? '',
                    'phone' => $_POST['phone'] ?? '',
                    'gender' => $_POST['gender'] ?? '',
                    'date_of_birth' => $_POST['date_of_birth'] ?? null
                ];
                
                try {
                    $result = $controller->updateProfile($data);
                    echo json_encode($result);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                    ]);
                }
                exit();
                break;

            case 'update_address':
                $data = [
                    'address' => $_POST['address'] ?? ''
                ];
                
                try {
                    $result = $controller->updateAddress($data);
                    echo json_encode($result);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                    ]);
                }
                exit();
                break;
                
            case 'update_avatar':
                if (isset($_FILES['avatar'])) {
                    try {
                        $result = $controller->updateAvatar($_FILES['avatar']);
                        echo json_encode($result);
                    } catch (Exception $e) {
                        echo json_encode([
                            'success' => false,
                            'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                        ]);
                    }
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Không có file được upload'
                    ]);
                }
                exit();
                break;

            case 'change_password':
                $data = [
                    'current_password' => $_POST['current_password'] ?? '',
                    'new_password' => $_POST['new_password'] ?? '',
                    'confirm_password' => $_POST['confirm_password'] ?? ''
                ];
                
                try {
                    $result = $controller->changePassword($data);
                    echo json_encode($result);
                } catch (Exception $e) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
                    ]);
                }
                exit();
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Action không hợp lệ'
                ]);
                exit();
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Thiếu action parameter'
        ]);
        exit();
    }
}

$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'personal-info';

// Function để format trạng thái đơn hàng
function getStatusText($status) {
    switch ($status) {
        case 'pending': return 'Chờ xử lý';
        case 'processing': return 'Đang xử lý';
        case 'shipped': return 'Đang giao hàng';
        case 'delivered': return 'Đã giao hàng';
        case 'cancelled': return 'Đã hủy';
        default: return ucfirst($status);
    }
}

function getStatusClass($status) {
    switch ($status) {
        case 'pending': return 'pending';
        case 'processing': return 'processing';
        case 'shipped': return 'shipped';
        case 'delivered': return 'delivered';
        case 'cancelled': return 'cancelled';
        default: return 'pending';
    }
}

// Thêm vào đầu file PHP (trước HTML)
$ordersPerPage = 4;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$totalOrders = count($userOrders);
$totalPages = ceil($totalOrders / $ordersPerPage);
$offset = ($currentPage - 1) * $ordersPerPage;
$ordersToShow = array_slice($userOrders, $offset, $ordersPerPage);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <link rel="stylesheet" href="../../public/css/styles.css">
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
    <title>Monito - Home</title>
</head>

<body>
    <header class="header header_category">
        <nav class="nav nav_category">
            <div class="container nav_container">
                <button class="hamburger_btn" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="nav_left">
                    <a href="../../index.php" class="nav_logo">
                        <img src="../../public/images/logo/logo.png" alt="PetShop Logo">
                    </a>
                    <ul class="nav_list">
                        <li class="nav_item"><a href="../../index.php" class="nav_link">Home</a></li>
                        <li class="nav_item">
                            <a href="./category.php" class="nav_link">Category</a>
                            <div class="category_dropdown">
                                <a href="./category.php?category_id=1" class="category_dropdown_item">
                                    <i class="fa-solid fa-paw"></i> Thú cưng
                                </a>
                                <a href="./category.php?category_id=8" class="category_dropdown_item">
                                    <i class="fa-solid fa-box"></i> Sản phẩm
                                </a>
                            </div>
                        </li>
                        <li class="nav_item">
                            <a href="./social_media.php" class="nav_link">Social Media</a>
                        </li>
                        <li class="nav_item"><a href="#" class="nav_link">About</a></li>
                        <li class="nav_item"><a href="./contact.php" class="nav_link">Contact</a></li>
                    </ul>
                </div>
                <div class="nav_right">
                    <!-- Search Box -->
                    <div class="search_box" style = "display: none;">
                        <input type="text" class="search_input" placeholder="Tìm kiếm sản phẩm...">
                        <i class="fa-solid fa-search search_icon"></i>
                    </div>
                    
                    <!-- Account Dropdown -->
                    <div class="account_dropdown">
                        <button class="icon_btn account_btn">
                            <i class="fa-solid fa-user"></i>
                            
                        </button>
                        <div class="notify_box_login_register">
                            <?php if ($is_logged_in): ?>
                                <div class="user_info">
                                    <img src="../../public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="Avatar" class="user_avatar">
                                    <!-- Hoặc nếu không có avatar, dùng placeholder -->
                                    <!-- <div class="user_avatar placeholder"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div> -->
                                    <p class="user_name"><?php echo htmlspecialchars($full_name); ?><br>
                                    <?php if ($is_logged_in): ?>
                                        <span class="user_name"><?php echo htmlspecialchars($username); ?></span>
                                    <?php endif; ?></p>
                                </div>
                                <?php if ($is_logged_in && ($userModel->isAdmin($_SESSION) || $userModel->isEmployee($_SESSION))): ?>
                                    <a href="../../views/admin/dashboard.php" class="auth_btn myOrder">
                                        <i class="fa-solid fa-gear"></i>
                                        Quản lý
                                    </a>
                                    <?php else: ?>
                                        <a href="./myAccount.php?tab=my-orders" class="auth_btn myOrder">
                                            <i class="fa-solid fa-shopping-bag"></i>
                                            Đơn hàng cá nhân
                                        </a>
                                    <?php endif; ?>
                                <a href="./myAccount.php" class="auth_btn myAccount">
                                    <i class="fa-solid fa-user-cog"></i>
                                    Tài khoản
                                </a>
                                <a href="../../app/controllers/LogoutController.php" class="auth_btn logout">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    Đăng xuất
                                </a>
                            <?php else: ?>
                                <a href="#" class="auth_btn login">Đăng nhập</a>
                                <a href="#" class="auth_btn register">Đăng ký</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Cart -->
                    <button class="icon_btn cart_btn">
                        <i class="fa-solid fa-shopping-cart"></i>
                        <span class="cart_badge">3</span>
                    </button>
                </div>
            </div>
        </nav>
    </header>
    
    <!-- Mobile Menu Overlay -->
    <div class="mobile_overlay"></div>

    <!-- Mobile Menu Sidebar -->
    <div class="mobile_menu" id="mobileMenu">
        <div class="mobile_menu_header">
            <a href="../../index.php" class="mobile_menu_logo">
                <img src="../../public/images/logo/logo.png" alt="PetShop Logo">
            </a>
            <button class="close_btn" type="button">
                <i class="fa-solid fa-angle-left"></i>
            </button>
        </div>

        <!-- Mobile Search -->
        <div class="mobile_search_box">
            <input type="text" class="mobile_search_input" placeholder="Tìm kiếm sản phẩm...">
        </div>

        <!-- Mobile Navigation -->
        <ul class="mobile_nav_list">
            <li class="mobile_nav_item">
                <a href="../../index.php" class="mobile_nav_link">Home</a>
            </li>
            <li class="mobile_nav_item">
                <a href="../../views/pages/category.html" class="mobile_nav_link">
                    Category
                    <i class="fa-solid fa-chevron-down" style="float: right; transition: transform 0.3s ease;"></i>
                </a>
                <div class="mobile_category_dropdown" id="mobileCategoryDropdown">
                    <a href="#" class="mobile_category_item">Thú cưng</a>
                    <a href="#" class="mobile_category_item">Sản phẩm</a>
                </div>
            </li>
            <li class="mobile_nav_item">
                <a href="#" class="mobile_nav_link">Social Media</a>
            </li>
            <li class="mobile_nav_item">
                <a href="#" class="mobile_nav_link">About</a>
            </li>
            <li class="mobile_nav_item">
                <a href="#" class="mobile_nav_link">Contact</a>
            </li>
        </ul>

        <!-- Mobile Auth Buttons -->
        <!-- <div class="mobile_auth_btns">
            <a href="#" class="mobile_auth_btn mobile_login">Đăng nhập</a>
            <a href="#" class="mobile_auth_btn mobile_register">Đăng ký</a>
        </div> -->

        <!-- Mobile Cart Info -->
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px; text-align: center;">
            <i class="fa-solid fa-shopping-cart" style="font-size: 24px; color: #007bff; margin-bottom: 10px;"></i>
            <p style="margin: 0; color: #666;">Giỏ hàng: <strong>3 sản phẩm</strong></p>
        </div>
    </div>

    <main>
       <div class="account-page">
            <div class="container_account">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">My Account</h1>
                    <nav class="breadcrumb_account">
                        <a href="/" class="breadcrumb-link">Home</a>
                        <span class="breadcrumb-separator">/</span>
                        <span class="breadcrumb-current">My Account</span>
                    </nav>
                </div>

                <!-- Account Content -->
                <div class="account-content">
                    <!-- Sidebar -->
                    <div class="account-sidebar">
                        <div class="sidebar-menu">
                            <button class="menu-item <?php echo ($activeTab === 'personal-info') ? 'active' : ''; ?>" data-tab="personal-info">
                                <i class="fa-solid fa-user"></i>
                                Personal Information
                            </button>
                            <button class="menu-item <?php echo ($activeTab === 'my-orders') ? 'active' : ''; ?>" data-tab="my-orders">
                                <i class="fa-solid fa-box"></i>
                                My Orders
                            </button>
                            <button class="menu-item" data-tab="manage-address">
                                <i class="fa-solid fa-location-dot"></i>
                                Manage Address
                            </button>
                            <!-- <button class="menu-item" data-tab="payment-method">
                                <i class="fa-solid fa-credit-card"></i>
                                Payment Method
                            </button> -->
                            <button class="menu-item" data-tab="password-manager">
                                <i class="fa-solid fa-key"></i>
                                Password Manager
                            </button>
                            <button class="menu-item logout-btn">
                                <i class="fa-solid fa-sign-out-alt"></i>
                                Logout
                            </button>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="account-main">
                        <!-- Personal Information Tab -->
                        <div class="tab-content <?php echo ($activeTab === 'personal-info') ? 'active' : ''; ?>" id="personal-info">
                            <div class="profile-section">
                                <div class="profile-avatar">
                                    <img src="../../public/uploads/avatar/<?php echo htmlspecialchars($userProfile['img'] ?? 'default.jpg'); ?>" alt="Profile Picture" class="avatar-img" id="avatarPreview">
                                    <button class="edit-avatar-btn" onclick="document.getElementById('avatarInput').click()">
                                        <i class="fa-solid fa-camera"></i>
                                    </button>
                                    <input type="file" id="avatarInput" accept="image/*" style="display: none;" onchange="updateAvatar(this)">
                                </div>
                                
                                <form class="profile-form" id="profileForm">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="firstName">First Name *</label>
                                            <input type="text" id="firstName" name="firstName" value="<?php echo htmlspecialchars($firstName); ?>" required>
                                        </div>
                                        <div class="form-group">
                                            <label for="lastName">Last Name *</label>
                                            <input type="text" id="lastName" name="lastName" value="<?php echo htmlspecialchars($lastName); ?>" required>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="email">Email *</label>
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userProfile['email'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone">Phone *</label>
                                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userProfile['phone'] ?? ''); ?>" required>
                                    </div>

                                    <div class="form-group">
                                        <label for="date_of_birth">Date of Birth *</label>
                                        <input type="date" id="date_of_birth" name="date_of_birth" 
                                            value="<?php echo htmlspecialchars($userProfile['date_of_birth'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="gender">Gender *</label>
                                        <select id="gender" name="gender" required>
                                            <option value="male" <?php echo ($userProfile['gender'] ?? '') == 'male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="female" <?php echo ($userProfile['gender'] ?? '') == 'female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="other" <?php echo ($userProfile['gender'] ?? '') == 'other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    
                                    <button type="submit" class="update-btn">Lưu thay đổi</button>
                                </form>
                            </div>
                        </div>

                        <!-- My Orders Tab -->
                        <div class="tab-content <?php echo ($activeTab === 'my-orders') ? 'active' : ''; ?>" id="my-orders">
                            <div class="orders-section">
                                <h2>Đơn hàng của tôi</h2>
                                
                                <!-- Order Search -->
                                <div class="order-search">
                                    <input type="text" class="search-order-input" placeholder="Tìm kiếm đơn hàng..." id="orderSearchInput">
                                    <i class="fa-solid fa-search search-order-icon"></i>
                                </div>
                                
                                <div class="orders-list" id="ordersList">
                                    <?php if (empty($userOrders)): ?>
                                        <div class="no-orders">
                                            <p>Bạn chưa có đơn hàng nào</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($ordersToShow as $order): ?>
                                            <div class="order-item" data-order-number="<?php echo htmlspecialchars($order['order_number']); ?>">
                                                <div class="order-header">
                                                    <span class="order-id">#<?php echo htmlspecialchars($order['order_number']); ?></span>
                                                    <span class="order-date"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                                                    <span class="order-status <?php echo getStatusClass($order['status']); ?>">
                                                        <?php echo getStatusText($order['status']); ?>
                                                    </span>
                                                </div>
                                                <div class="order-details">
                                                    <div class="order-info">
                                                        <p><?php echo isset($order['total_items']) ? $order['total_items'] : '0'; ?> sản phẩm</p>
                                                        <p class="order-total">$<?php echo number_format($order['total_amount'], 2); ?></p>
                                                        <?php if (!empty($order['notes'])): ?>
                                                            <p class="order-notes"><?php echo htmlspecialchars($order['notes']); ?></p>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="order-actions">
                                                        <!-- Nút Detail cho tất cả đơn hàng -->
                                                        <button class="order-btn detail-btn" onclick="viewOrderDetail(<?php echo $order['order_id']; ?>)">
                                                            <i class="fa-solid fa-eye"></i>
                                                            Chi tiết
                                                        </button>
                                                        
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <!-- Nút Hủy đơn hàng cho trạng thái pending -->
                                                            <button class="order-btn cancel-btn" onclick="cancelOrder(<?php echo $order['order_id']; ?>)">
                                                                <i class="fa-solid fa-times"></i>
                                                                Hủy đơn hàng
                                                            </button>
                                                        <?php elseif ($order['status'] === 'shipped'): ?>
                                                            <!-- Nút Đã nhận cho trạng thái shipped -->
                                                            <button class="order-btn received-btn" onclick="confirmReceived(<?php echo $order['order_id']; ?>)">
                                                                <i class="fa-solid fa-check"></i>
                                                                Đã nhận hàng
                                                            </button>
                                                        <?php elseif ($order['status'] === 'delivered'): ?>
                                                            <!-- Nút Đánh giá cho đơn hàng đã giao -->
                                                            <button class="order-btn review-btn" onclick="reviewOrder(<?php echo $order['order_id']; ?>)">
                                                                <i class="fa-solid fa-star"></i>
                                                                Đánh giá
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($totalPages > 1): ?>
                                    <div class="pagination-container">
                                        <div class="pagination">
                                            <!-- Previous button -->
                                            <?php if ($currentPage > 1): ?>
                                                <a href="?page=<?php echo ($currentPage - 1); ?>" class="pagination-btn prev-btn">
                                                    <i class="fa-solid fa-chevron-left"></i>
                                                    Trước
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- Page numbers -->
                                            <?php
                                            $startPage = max(1, $currentPage - 2);
                                            $endPage = min($totalPages, $currentPage + 2);
                                            ?>
                                            
                                            <?php if ($startPage > 1): ?>
                                                <a href="?page=1" class="pagination-btn">1</a>
                                                <?php if ($startPage > 2): ?>
                                                    <span class="pagination-dots">...</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                                <a href="?page=<?php echo $i; ?>" 
                                                class="pagination-btn <?php echo ($i == $currentPage) ? 'active' : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            <?php endfor; ?>
                                            
                                            <?php if ($endPage < $totalPages): ?>
                                                <?php if ($endPage < $totalPages - 1): ?>
                                                    <span class="pagination-dots">...</span>
                                                <?php endif; ?>
                                                <a href="?page=<?php echo $totalPages; ?>" class="pagination-btn"><?php echo $totalPages; ?></a>
                                            <?php endif; ?>
                                            
                                            <!-- Next button -->
                                            <?php if ($currentPage < $totalPages): ?>
                                                <a href="?page=<?php echo ($currentPage + 1); ?>" class="pagination-btn next-btn">
                                                    Sau
                                                    <i class="fa-solid fa-chevron-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Page info -->
                                        <div class="page-info">
                                            Hiển thị <?php echo (($currentPage - 1) * $ordersPerPage + 1); ?> - 
                                            <?php echo min($currentPage * $ordersPerPage, $totalOrders); ?> 
                                            trong tổng số <?php echo $totalOrders; ?> đơn hàng
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Address Management Tab -->
                        <div class="tab-content <?php echo ($activeTab === 'manage-address') ? 'active' : ''; ?>" id="manage-address">
                            <div class="address-section">
                                <h2>Address Information</h2>
                                <p class="section-description">Manage your shipping address information</p>
                                
                                <form class="address-form" id="addressForm">
                                    <div class="form-group">
                                        <label for="address">Full Address *</label>
                                        <textarea id="address" name="address" rows="4" placeholder="Enter your complete address (Street, District, City, Province)" required><?php echo htmlspecialchars($userProfile['address'] ?? ''); ?></textarea>
                                        <small class="form-hint">Please provide your complete address including street, district, city, and province</small>
                                    </div>
                                    
                                    <div class="address-preview">
                                        <h3>Current Address:</h3>
                                        <div class="current-address">
                                            <?php if (!empty($userProfile['address'])): ?>
                                                <p><?php echo nl2br(htmlspecialchars($userProfile['address'])); ?></p>
                                            <?php else: ?>
                                                <p class="no-address">No address saved yet</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="update-btn">
                                        <i class="fa-solid fa-save"></i>
                                        Save Address
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- tab change pass -->
                        <div class="tab-content" id="password-manager">
                            <div class="password-section">
                                <h2>Password Manager</h2>
                                <form class="password-form" id="passwordForm">
                                    <div class="form-group">
                                        <label for="currentPassword">Current Password *</label>
                                        <div class="password-input-wrapper">
                                            <input type="password" id="currentPassword" name="currentPassword" required>
                                            <button type="button" class="toggle-password" data-target="currentPassword">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="newPassword">New Password *</label>
                                        <div class="password-input-wrapper">
                                            <input type="password" id="newPassword" name="newPassword" required minlength="6">
                                            <button type="button" class="toggle-password" data-target="newPassword">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                        <small class="password-hint">Password must be at least 6 characters long</small>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="confirmPassword">Confirm New Password *</label>
                                        <div class="password-input-wrapper">
                                            <input type="password" id="confirmPassword" name="confirmPassword" required minlength="6">
                                            <button type="button" class="toggle-password" data-target="confirmPassword">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <button type="submit" class="update-btn">Update Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>


    <footer class="footer">
        <div class="container footer_container">
            <div class="footer_top">
                <h2 class="footer_top_heading">Register now so you don't miss our programs</h2>
                <form action="#" class="footer_top_form">
                    <input type="text" class="footer_top_form_input" placeholder="Enter your email...">
                    <button class="footer_top_form_btn">Register</button>
                </form>
            </div>
            <div class="section__container footer__container">
                <div class="footer__col">
                    <div class="footer__logo">
                        <a href="#"><img src="../../public/images/logo/logo.png" alt=""></a>
                    </div>
                </div>
                <div class="footer__col">
                    <h4>Company</h4>
                    <ul class="footer__links">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Services</a></li>
                        <li><a href="#">Store</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Faq</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer__col">
                    <h4>Address</h4>
                    <ul class="footer__links">
                        <li><a href="#">New Delhi, India</a></li>
                        <li><a href="#">View on Maps</a></li>
                    </ul>
                    <br />
                    <h4>Inquiries</h4>
                    <ul class="footer__links">
                        <li><a href="#">+91 0987654321</a></li>
                        <li><a href="#">info@website.com</a></li>
                    </ul>
                </div>
                <div class="footer__col">
                    <h4>Newsletter</h4>
                    <p>Stay updated with out latest news</p>
                    <form action="/">
                        <input type="text" placeholder="Your email" />
                        <button class="btn">
                            <i class="fa-solid fa-arrow-right"></i>
                        </button>
                    </form>
                    <br />
                    <h4>Follow Us</h4>
                    <ul class="footer__socials">
                        <li>
                            <a href="#"><i class="fa-brands fa-facebook-f"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-brands fa-twitter"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-brands fa-youtube"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-brands fa-pinterest"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-brands fa-instagram"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-brands fa-tiktok"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- Initialize Swiper -->
    <!-- <script src="../../public/js/details.js"></script> -->
    <script src="../../public/js/index.js"></script>
    <script src="../../public/js/myAccount.js"></script>
    <script src="../../public/js/showMessageDialog.js"></script>

    <!-- Order Detail Modal - Thêm vào cuối file HTML trước thẻ </body> -->
    <div id="orderDetailModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Chi tiết đơn hàng</h2>
                <span class="close" onclick="closeOrderDetailModal()">&times;</span>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <!-- Content will be loaded here via AJAX -->
                <div class="loading">Đang tải...</div>
            </div>
        </div>
    </div>

</body>

</html>