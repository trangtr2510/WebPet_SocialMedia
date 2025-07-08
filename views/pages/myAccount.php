<?php
// Bao gồm controller
include_once(__DIR__ . '/../../app/controllers/myAccountController.php');
include_once(__DIR__ .'/../../config/config.php');
include_once(__DIR__ .'/../../app/models/User.php');

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login_register.php');
    exit();
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
                            <a href="./views/pages/category.html" class="nav_link">Category</a>
                        </li>
                        <li class="nav_item">
                            <a href="#" class="nav_link">Social Media</a>
                        </li>
                        <li class="nav_item"><a href="#" class="nav_link">About</a></li>
                        <li class="nav_item"><a href="#" class="nav_link">Contact</a></li>
                    </ul>
                </div>
                <div class="nav_right">
                    <!-- Search Box -->
                    <div class="search_box">
                        <input type="text" class="search_input" placeholder="Tìm kiếm sản phẩm...">
                        <i class="fa-solid fa-search search_icon"></i>
                    </div>
                    
                    <!-- Account Dropdown -->
                    <div class="account_dropdown">
                        <button class="icon_btn account_btn">
                            <i class="fa-solid fa-user"></i>
                            
                        </button>
                        <div class="notify_box">
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
                                    <a href="./views/pages/admin_dashboard.php" class="auth_btn myOrder">
                                        <i class="fa-solid fa-gear"></i>
                                        Quản lý
                                    </a>
                                <?php else: ?>
                                    <a href="./views/pages/orders.php" class="auth_btn myOrder" style='display: none;'>
                                        <i class="fa-solid fa-shopping-bag"></i>
                                        Đơn hàng cá nhân
                                    </a>
                                <?php endif; ?>
                                <a href="./views/pages/myAccount.php" class="auth_btn myAccount">
                                    <i class="fa-solid fa-user-cog"></i>
                                    Tài khoản
                                </a>
                                <a href="./app/controllers/LogoutController.php" class="auth_btn logout">
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
                <a href="../../views/pages/category.html" class="mobile_nav_link">Category</a>
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
        <div class="mobile_auth_btns">
            <a href="#" class="mobile_auth_btn mobile_login">Đăng nhập</a>
            <a href="#" class="mobile_auth_btn mobile_register">Đăng ký</a>
        </div>

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
                                    
                                    <button type="submit" class="update-btn">Update Changes</button>
                                </form>
                            </div>
                        </div>

                        <!-- My Orders Tab -->
                        <div class="tab-content <?php echo ($activeTab === 'my-orders') ? 'active' : ''; ?>" id="my-orders">
                            <div class="orders-section">
                                <h2>My Orders</h2>
                                
                                <!-- Order Search -->
                                <div class="order-search">
                                    <input type="text" class="search-order-input" placeholder="Search orders...">
                                    <i class="fa-solid fa-search search-order-icon"></i>
                                </div>
                                
                                <div class="orders-list">
                                    <div class="order-item">
                                        <div class="order-header">
                                            <span class="order-id">#12345</span>
                                            <span class="order-date">Jan 15, 2024</span>
                                            <span class="order-status pending">Pending</span>
                                        </div>
                                        <div class="order-details">
                                            <div class="order-info">
                                                <p>Dog Food Premium - 2kg</p>
                                                <p class="order-total">$45.99</p>
                                            </div>
                                            <div class="order-actions">
                                                <button class="order-btn cancel-btn">
                                                    <i class="fa-solid fa-times"></i>
                                                    Cancel Order
                                                </button>
                                                <button class="order-btn pay-btn">
                                                    <i class="fa-solid fa-credit-card"></i>
                                                    Pay Now
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="order-item">
                                        <div class="order-header">
                                            <span class="order-id">#12344</span>
                                            <span class="order-date">Jan 10, 2024</span>
                                            <span class="order-status delivered">Delivered</span>
                                        </div>
                                        <div class="order-details">
                                            <div class="order-info">
                                                <p>Cat Toy Set</p>
                                                <p class="order-total">$23.50</p>
                                            </div>
                                            <div class="order-actions">
                                                <button class="order-btn reorder-btn">
                                                    <i class="fa-solid fa-redo"></i>
                                                    Re-order
                                                </button>
                                                <button class="order-btn review-btn">
                                                    <i class="fa-solid fa-star"></i>
                                                    Review
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Manage Address Tab -->
                        <div class="tab-content" id="manage-address">
                            <div class="address-section">
                                <h2>Manage Address</h2>
                                <button class="add-address-btn" onclick="openAddressModal()">
                                    <i class="fa-solid fa-plus"></i>
                                    Add New Address
                                </button>
                                <div class="address-list">
                                    <div class="address-item">
                                        <div class="address-header">
                                            <span class="address-type">Home</span>
                                            <span class="default-badge">Default</span>
                                        </div>
                                        <div class="address-details">
                                            <p>Bessie Cooper</p>
                                            <p>123 Main Street, Apt 4B</p>
                                            <p>New York, NY 10001</p>
                                            <p>+0123 456 789</p>
                                        </div>
                                        <div class="address-actions">
                                            <button class="edit-btn">Edit</button>
                                            <button class="delete-btn">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Tab -->
                        <!-- <div class="tab-content" id="payment-method">
                            <div class="payment-section">
                                <h2>Payment Method</h2>
                                <button class="add-payment-btn">
                                    <i class="fa-solid fa-plus"></i>
                                    Add New Payment Method
                                </button>
                                <div class="payment-list">
                                    <div class="payment-item">
                                        <div class="payment-icon">
                                            <i class="fa-brands fa-cc-visa"></i>
                                        </div>
                                        <div class="payment-details">
                                            <p>**** **** **** 1234</p>
                                            <p>Expires 12/25</p>
                                        </div>
                                        <div class="payment-actions">
                                            <button class="edit-btn">Edit</button>
                                            <button class="delete-btn">Delete</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> -->

                        <!-- Password Manager Tab -->
                        <div class="tab-content" id="password-manager">
                            <div class="password-section">
                                <h2>Password Manager</h2>
                                <form class="password-form">
                                    <div class="form-group">
                                        <label for="currentPassword">Current Password</label>
                                        <input type="password" id="currentPassword" name="currentPassword" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="newPassword">New Password</label>
                                        <input type="password" id="newPassword" name="newPassword" required>
                                    </div>
                                    <div class="form-group">
                                        <label for="confirmPassword">Confirm New Password</label>
                                        <input type="password" id="confirmPassword" name="confirmPassword" required>
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

    <!-- Address Change Modal -->
    <div class="modal-overlay" id="addressModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Thay đổi địa chỉ giao hàng</h3>
                <button class="modal-close" onclick="closeAddressModal()">&times;</button>
            </div>
            <form id="addressForm">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Họ tên *</label>
                        <input type="text" class="form-input" id="customerName" value="Trần Trang" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" class="form-input" id="phoneNumber" value="0397507701" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Địa chỉ chi tiết *</label>
                    <textarea class="form-textarea" id="detailAddress" placeholder="Nhập số nhà, tên đường..."
                        required>Nhà Số 10 - Ngách 2 - Ngõ 103, Phường Cổ Nhuế 2, Quận Bắc Từ Liêm, Hà Nội</textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tỉnh/Thành phố *</label>
                        <select class="form-input" id="province" required>
                            <option value="">Chọn Tỉnh/Thành phố</option>
                            <option value="hanoi" selected>Hà Nội</option>
                            <option value="hcm">Hồ Chí Minh</option>
                            <option value="danang">Đà Nẵng</option>
                            <option value="haiphong">Hải Phòng</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quận/Huyện *</label>
                        <select class="form-input" id="district" required>
                            <option value="">Chọn Quận/Huyện</option>
                            <option value="bactuliem" selected>Bắc Từ Liêm</option>
                            <option value="namtuliem">Nam Từ Liêm</option>
                            <option value="caugiay">Cầu Giấy</option>
                            <option value="dongda">Đống Đa</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phường/Xã *</label>
                    <select class="form-input" id="ward" required>
                        <option value="">Chọn Phường/Xã</option>
                        <option value="conhue2" selected>Cổ Nhuế 2</option>
                        <option value="conhue1">Cổ Nhuế 1</option>
                        <option value="phucdinh">Phúc Dinh</option>
                        <option value="xuanhoa">Xuân Hòa</option>
                    </select>
                </div>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeAddressModal()">Hủy</button>
                    <button type="submit" class="btn-save">Lưu địa chỉ</button>
                </div>
            </form>
        </div>
    </div>

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
    <script src="../../public/js/details.js"></script>
    <script src="../../public/js/index.js"></script>
    <script src="../../public/js/myAccount.js"></script>
    <script src="../../public/js/showMessageDialog.js"></script>

</body>

</html>