
<?php
session_start();
include_once(__DIR__ .'/../../config/config.php');
include_once(__DIR__ .'/../../app/models/Product.php');
include_once(__DIR__ .'/../../app/models/User.php');
include_once(__DIR__ .'/../../app/models/ProductImage.php');
include_once(__DIR__ .'/../../app/models/Category.php');
include_once(__DIR__ .'/../../app/models/Cart.php');

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$email = $is_logged_in ? $_SESSION['email'] : '';
$address = $is_logged_in ? $_SESSION['address'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$phone = $is_logged_in ? $_SESSION['phone'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../views/auth/login_register.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Khởi tạo các class
$product = new Product($conn);
$category = new Category($conn);
$productImages = new ProductImage($conn);
$cart = new Cart($conn);

// Lấy thông tin user
$user_info = $userModel->getUserById($user_id);

// Lấy giỏ hàng của user với thông tin chi tiết sản phẩm
$cartItems = $cart->getCartWithProductDetails($user_id);

// Tính tổng số lượng và tổng giá trị
$totalQuantity = $cart->getTotalQuantity($user_id);
$totalValue = $cart->getTotalValue($user_id);

// Tính thuế và phí vận chuyển
$subtotal = $totalValue;
$shipping = 50000; // 50,000 VNĐ phí vận chuyển
$tax = $subtotal * 0.1; // Thuế 10%
$discount = 100000; // Giảm giá 100,000 VNĐ
$final_total = $subtotal + $shipping + $tax - $discount;

// Format giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Xử lý form submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Xử lý đặt hàng ở đây
    // Tạo order mới, lưu thông tin giao hàng, etc.
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/styles.css">
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <title>Monito - CheckOut</title>
    <style>
        button[name="redirect"] {
            background-color: #27ae60;
            color: white;
            border: none;
            padding: 12px 20px;
            font-size: 16px;
            border-radius: 8px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.3s ease, transform 0.2s ease;
        }

        button[name="redirect"]:hover {
            background-color: #219150;
            transform: scale(1.03);
        }

        button[name="redirect"] i {
            font-size: 16px;
        }
    </style>
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
                <a href="./views/pages/category.html" class="mobile_nav_link">
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

    <div class="checkout-container">
        <div class="checkout-header">
            <h1>Checkout</h1>
            <div class="breadcrumb">
                <a href="../../index.php">Home</a> / 
                <a href="./cart.php">Shopping Cart</a> / 
                <span>Checkout</span>
            </div>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="empty-cart">
                <i class="fa-solid fa-shopping-cart" style="font-size: 4rem; color: #ddd; margin-bottom: 20px;"></i>
                <h3>Giỏ hàng của bạn đang trống</h3>
                <p>Vui lòng thêm sản phẩm vào giỏ hàng trước khi thanh toán.</p>
                <a href="./category.php" class="checkout-btn" style="max-width: 200px; margin: 20px auto; display: block;">
                    Tiếp tục mua sắm
                </a>
            </div>
        <?php else: ?>
            <form method="POST" class="checkout-content">
                <div class="billing-section">
                    <h3>Thông tin giao hàng</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="first_name">Họ <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" required 
                                   value="<?php echo isset($user_info['first_name']) ? htmlspecialchars($user_info['first_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Tên <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" required 
                                   value="<?php echo isset($user_info['last_name']) ? htmlspecialchars($user_info['last_name']) : ''; ?>">
                        </div>
                    </div>

                    <!-- <div class="form-group">
                        <label for="company_name">Tên công ty (Tùy chọn)</label>
                        <input type="text" id="company_name" name="company_name" placeholder="Nhập tên công ty">
                    </div> -->

                    <div class="form-group">
                        <label for="country">Quốc gia <span class="required">*</span></label>
                        <select id="country" name="country" required>
                            <option value="">Chọn quốc gia</option>
                            <option value="VN" selected>Việt Nam</option>
                            <option value="US">Hoa Kỳ</option>
                            <option value="JP">Nhật Bản</option>
                            <option value="KR">Hàn Quốc</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="street_address">Địa chỉ <span class="required">*</span></label>
                        <input type="text" id="street_address" name="street_address" required 
                               value="<?php echo htmlspecialchars($address); ?>" placeholder="Nhập địa chỉ chi tiết">
                    </div>

                    <!-- <div class="form-row">
                        <div class="form-group">
                            <label for="city">Thành phố <span class="required">*</span></label>
                            <select id="city" name="city" required>
                                <option value="">Chọn thành phố</option>
                                <option value="hanoi">Hà Nội</option>
                                <option value="hcm">TP. Hồ Chí Minh</option>
                                <option value="danang">Đà Nẵng</option>
                                <option value="haiphong">Hải Phòng</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="state">Tỉnh/Thành <span class="required">*</span></label>
                            <select id="state" name="state" required>
                                <option value="">Chọn tỉnh/thành</option>
                                <option value="hanoi">Hà Nội</option>
                                <option value="hcm">TP. Hồ Chí Minh</option>
                                <option value="danang">Đà Nẵng</option>
                            </select>
                        </div>
                    </div> -->

                    <div class="form-row">
                        <div class="form-group">
                            <label for="zip_code">Mã bưu điện <span class="required">*</span></label>
                            <input type="text" id="zip_code" name="zip_code" required placeholder="Nhập mã bưu điện">
                        </div>
                        <div class="form-group">
                            <label for="phone">Số điện thoại <span class="required">*</span></label>
                            <input type="tel" id="phone" name="phone" required 
                                   value="<?php echo htmlspecialchars($phone); ?>" placeholder="Nhập số điện thoại">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="required">*</span></label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo htmlspecialchars($email); ?>" placeholder="Nhập email">
                    </div>
                    <!-- <div class="delivery-options">
                        <h4>Phương thức giao hàng</h4>
                        <div class="delivery-option selected">
                            <input type="radio" id="same_address" name="delivery_address" value="same" checked>
                            <label for="same_address">Giao hàng theo địa chỉ thanh toán</label>
                        </div>
                        <div class="delivery-option">
                            <input type="radio" id="different_address" name="delivery_address" value="different">
                            <label for="different_address">Sử dụng địa chỉ giao hàng khác</label>
                        </div>
                    </div> -->
                </div>

                <div class="order-summary">
                    <h3>Tóm tắt đơn hàng</h3>
                    
                    <div class="cart-items">
                        <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item">
                                <img src="../../public/uploads/product/<?php echo htmlspecialchars($item['product_image'] ?? 'https://via.placeholder.com/60x60'); ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                     class="item-image">
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="item-quantity">Số lượng: <?php echo $item['quantity']; ?></div>
                                </div>
                                <div class="item-price">
                                    <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="summary-details">
                        <div class="summary-row">
                            <span class="summary-label">Tạm tính (<?php echo $totalQuantity; ?> sản phẩm)</span>
                            <span class="summary-value"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Phí vận chuyển</span>
                            <span class="summary-value"><?php echo formatPrice($shipping); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Thuế (10%)</span>
                            <span class="summary-value"><?php echo formatPrice($tax); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label discount">Giảm giá</span>
                            <span class="summary-value discount">-<?php echo formatPrice($discount); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Tổng cộng</span>
                            <span class="summary-value"><?php echo formatPrice($subtotal); ?></span>
                        </div>
                    </div>

                    <form action="../../app/controllers/congthanhtoan.php" method="POST" style="display: none;">
                        <input type="hidden" name = "sotien" value = "<?php echo formatPrice($subtotal); ?>">
                        <button style="display: none;" type="submit" class="checkout-btn" name="checkout_submit">
                            <i class="fa-solid fa-lock" style="margin-right: 8px;"></i>
                            Tiến hành thanh toán
                        </button>
                    </form>
                    <form action="../../app/controllers/congthanhtoan.php" method="POST">
                        <input type="hidden" name = "sotien" value = "<?php echo formatPrice($subtotal); ?>">
                        <button type="submit" name="redirect"><i class="fa-solid fa-lock" style="margin-right: 8px;"></i> Tiến hành thanh toán</button>
                    </form>
                    
                    <div style="text-align: center; margin-top: 15px; color: #666; font-size: 12px;">
                        <i class="fa-solid fa-shield-alt"></i> Thanh toán an toàn & bảo mật
                    </div>
                </div>
            </form>
        <?php endif; ?>
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
                        <li><a href="#"><i class="fa-brands fa-facebook-f"></i></a></li>
                        <li><a href="#"><i class="fa-brands fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fa-brands fa-youtube"></i></a></li>
                        <li><a href="#"><i class="fa-brands fa-pinterest"></i></a></li>
                        <li><a href="#"><i class="fa-brands fa-instagram"></i></a></li>
                        <li><a href="#"><i class="fa-brands fa-tiktok"></i></a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="../../public/js/index.js"></script>
     <script>
        // Handle delivery option selection
        document.querySelectorAll('input[name="delivery_address"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.delivery-option').forEach(option => {
                    option.classList.remove('selected');
                });
                this.closest('.delivery-option').classList.add('selected');
            });
        });

    </script>
</body>
</html>