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
// $username = $_SESSION['username'];
// $full_name = $_SESSION['full_name'];
// $img = (!empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

// Khởi tạo các class
$product = new Product($conn);
$category = new Category($conn);
$productImages = new ProductImage($conn);
$cart = new Cart($conn);

// Lấy giỏ hàng của user với thông tin chi tiết sản phẩm
$cartItems = $cart->getCartWithProductDetails($user_id);

// Tính tổng số lượng và tổng giá trị
$totalQuantity = $cart->getTotalQuantity($user_id);
$totalValue = $cart->getTotalValue($user_id);

// Format giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . '₫';
}

// Tính giá gốc (giả sử có discount)
function getOriginalPrice($price) {
    return $price * 1.5; // Giả sử giá gốc cao hơn 50%
}

// Lấy ảnh chính của sản phẩm
function getPrimaryImage($product_id, $productImages) {
    $images = $productImages->getImagesByProduct($product_id);
    foreach ($images as $image) {
        if ($image['is_primary']) {
            return $image['image_url'];
        }
    }
    return !empty($images) ? $images[0]['image_url'] : 'https://via.placeholder.com/80x80';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/styles.css">
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <title>Monito - Giỏ hàng</title>
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

    
    <main>
        <div class="shopping-cart">
            <div class="cart-container">
                <!-- Shopping Cart Section -->
                <div class="cart-section">
                    <div class="cart-header">
                        <input type="checkbox" class="select-all-checkbox" id="select-all">
                        <label for="select-all">Tất cả (<?php echo count($cartItems); ?> sản phẩm)</label>
                        <div style="margin-left: auto; display: flex; gap: 2rem; font-size: 0.9rem; color: #666;">
                            <span>Đơn giá</span>
                            <span>Số lượng</span>
                            <span>Thành tiền</span>
                        </div>
                    </div>
                    
                    <div class="cart-items">
                        <?php if (empty($cartItems)): ?>
                            <div class="empty-cart">
                                <i class="fa-solid fa-shopping-cart" style="font-size: 4rem; color: #ccc;"></i>
                                <h3>Giỏ hàng trống</h3>
                                <p>Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($cartItems as $item): ?>
                                <div class="cart-item" data-product-id="<?php echo $item['product_id']; ?>">
                                    <input type="checkbox" class="item-checkbox" checked>
                                    <img src="../../public/uploads/product/<?php echo getPrimaryImage($item['product_id'], $productImages); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                         class="item-image">
                                    <div class="item-details">
                                        <h4><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                        <div>
                                            <span class="item-badge">NOW</span>
                                            <span class="shipping-info">Giao siêu tốc 2h</span>
                                        </div>
                                        <?php if (isset($item['material']) && !empty($item['material'])): ?>
                                            <div class="product-info">Chất liệu: <?php echo htmlspecialchars($item['material']); ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($item['size']) && !empty($item['size'])): ?>
                                            <div class="product-info">Kích thước: <?php echo htmlspecialchars($item['size']); ?></div>
                                        <?php endif; ?>
                                        <?php if (isset($item['age']) && !empty($item['age'])): ?>
                                            <div class="product-info">Độ tuổi: <?php echo htmlspecialchars($item['age']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="item-price">
                                        <div class="current-price"><?php echo formatPrice($item['price']); ?></div>
                                        <div class="original-price"><?php echo formatPrice(getOriginalPrice($item['price'])); ?></div>
                                        <div class="price-note">Giá chưa áp dụng khuyến mãi</div>
                                    </div>
                                    <div class="quantity-controls">
                                        <button class="quantity-btn" onclick="updateQuantity(this, -1)">-</button>
                                        <input type="number" class="quantity-input" 
                                               value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock_quantity']; ?>">
                                        <button class="quantity-btn" onclick="updateQuantity(this, 1)">+</button>
                                    </div>
                                    <div class="total-price"><?php echo formatPrice($item['price'] * $item['quantity']); ?></div>
                                    <button class="remove-btn" onclick="removeItem(this)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <a href="../../index.php" class="continue-shopping">
                        <i class="fa-solid fa-arrow-left"></i>
                        Tiếp tục mua sắm
                    </a>
                </div>

                <!-- Right Sidebar -->
                <div class="sidebar">
                    <div class="delivery-info">
                        <div class="delivery-header">
                            <i class="fa-solid fa-map-marker-alt delivery-icon"></i>
                            <span class="delivery-title">Giao tới</span>
                            <a href="#" class="change-btn" onclick="openAddressModal()">Thay đổi</a>
                        </div>
                        <div class="address-info">
                            <div class="customer-name"><?php echo htmlspecialchars($full_name); ?> | <?php echo htmlspecialchars($phone); ?></div>
                            <div class="address"><?php echo htmlspecialchars($_SESSION['address'] ?? 'Chưa có địa chỉ'); ?></div>
                        </div>
                    </div>

                    <div class="summary-section">
                        <div class="summary-row">
                            <span class="summary-label">Tạm tính</span>
                            <span class="summary-value" id="subtotal"><?php echo formatPrice($totalValue); ?></span>
                        </div>
                        <div class="summary-row">
                            <span class="summary-label">Giảm giá</span>
                            <span class="summary-value">0₫</span>
                        </div>
                        <div class="summary-row summary-total">
                            <span class="total-label">Tổng tiền thanh toán</span>
                            <span class="total-value" id="total-value"><?php echo $totalValue > 0 ? formatPrice($totalValue) : 'Vui lòng chọn sản phẩm'; ?></span>
                        </div>
                        <div class="vat-note">(Đã bao gồm VAT nếu có)</div>
                        <a href="./checkout.php">
                            <button class="checkout-btn" <?php echo $totalValue > 0 ? '' : 'disabled'; ?>>
                                Mua Hàng (<?php echo $totalQuantity; ?>)
                            </button>
                        </a>
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
            <form id="addressForm" method="POST" action="../../app/controllers/UserController.php">
                <input type="hidden" name="action" value="update_address">
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Họ tên *</label>
                        <input type="text" class="form-input" name="full_name" 
                               value="<?php echo htmlspecialchars($full_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Số điện thoại *</label>
                        <input type="tel" class="form-input" name="phone" 
                               value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Địa chỉ chi tiết *</label>
                    <textarea class="form-textarea" name="address" placeholder="Nhập số nhà, tên đường..." required><?php echo htmlspecialchars($_SESSION['address'] ?? ''); ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Tỉnh/Thành phố *</label>
                        <select class="form-input" name="province" required>
                            <option value="">Chọn Tỉnh/Thành phố</option>
                            <option value="hanoi">Hà Nội</option>
                            <option value="hcm">Hồ Chí Minh</option>
                            <option value="danang">Đà Nẵng</option>
                            <option value="haiphong">Hải Phòng</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Quận/Huyện *</label>
                        <select class="form-input" name="district" required>
                            <option value="">Chọn Quận/Huyện</option>
                            <option value="bactuliem">Bắc Từ Liêm</option>
                            <option value="namtuliem">Nam Từ Liêm</option>
                            <option value="caugiay">Cầu Giấy</option>
                            <option value="dongda">Đống Đa</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Phường/Xã *</label>
                    <select class="form-input" name="ward" required>
                        <option value="">Chọn Phường/Xã</option>
                        <option value="conhue2">Cổ Nhuế 2</option>
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
    <script src="../../public/js/addressForm.js"></script>
    <script src="../../public/js/cart.js"></script>
    
    <script>
        // Fixed JavaScript functions for cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            updateCartTotals();
            
            // Add event listeners for checkboxes
            const selectAllCheckbox = document.getElementById('select-all');
            const itemCheckboxes = document.querySelectorAll('.item-checkbox');
            
            selectAllCheckbox.addEventListener('change', function() {
                itemCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateCartTotals();
            });
            
            itemCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateCartTotals);
            });
        });

        function updateCartTotals() {
            const checkedItems = document.querySelectorAll('.item-checkbox:checked');
            let total = 0;
            let quantity = 0;
            
            checkedItems.forEach(checkbox => {
                const cartItem = checkbox.closest('.cart-item');
                const priceText = cartItem.querySelector('.current-price').textContent;
                const price = parseFloat(priceText.replace(/[^\d]/g, ''));
                const itemQuantity = parseInt(cartItem.querySelector('.quantity-input').value);
                
                total += price * itemQuantity;
                quantity += itemQuantity;
            });
            
            document.getElementById('subtotal').textContent = formatPrice(total);
            document.getElementById('total-value').textContent = total > 0 ? formatPrice(total) : 'Vui lòng chọn sản phẩm';
            
            const checkoutBtn = document.querySelector('.checkout-btn');
            checkoutBtn.disabled = total === 0;
            checkoutBtn.textContent = `Mua Hàng (${quantity})`;
        }

        function formatPrice(price) {
            return new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND',
                minimumFractionDigits: 0
            }).format(price).replace('₫', '₫');
        }

        // AJAX function to update quantity
        function updateQuantity(button, change) {
            const quantityInput = button.parentElement.querySelector('.quantity-input');
            const currentValue = parseInt(quantityInput.value);
            const newValue = currentValue + change;
            const maxValue = parseInt(quantityInput.getAttribute('max'));
            
            if (newValue >= 1 && newValue <= maxValue) {
                quantityInput.value = newValue;
                
                // Update total price for this item
                const cartItem = button.closest('.cart-item');
                const priceText = cartItem.querySelector('.current-price').textContent;
                const price = parseFloat(priceText.replace(/[^\d]/g, ''));
                const totalPriceElement = cartItem.querySelector('.total-price');
                totalPriceElement.textContent = formatPrice(price * newValue);
                
                // Update cart totals
                updateCartTotals();
                
                // Send AJAX request to update database
                const productId = cartItem.getAttribute('data-product-id');
                updateCartInDatabase(productId, newValue);
            }
        }

        // AJAX function to remove item
        function removeItem(button) {
            const cartItem = button.closest('.cart-item');
            const productId = cartItem.getAttribute('data-product-id');
            
            if (confirm('Bạn có chắc chắn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
                // Send AJAX request to remove from database
                removeFromCart(productId, cartItem);
            }
        }

        // Fixed AJAX functions
        function updateCartInDatabase(productId, quantity) {
            // Show loading state
            showLoading();
            
            fetch('../../app/controllers/CartController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=update&product_id=${productId}&quantity=${quantity}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                hideLoading();
                
                if (data.status === 'success') {
                    showToast('Cập nhật giỏ hàng thành công', 'success');
                    updateCartCount();
                } else {
                    showToast(data.message || 'Có lỗi xảy ra khi cập nhật giỏ hàng', 'error');
                    // Reload page to reset state
                    setTimeout(() => location.reload(), 1500);
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showToast('Có lỗi xảy ra khi cập nhật giỏ hàng', 'error');
                setTimeout(() => location.reload(), 1500);
            });
        }

        function removeFromCart(productId, cartItem) {
            // Show loading state
            showLoading();
            
            fetch('../../app/controllers/CartController.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=delete&product_id=${productId}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                hideLoading();
                
                if (data.status === 'success') {
                    // Remove from UI with animation
                    cartItem.style.animation = 'fadeOut 0.5s ease-out';
                    setTimeout(() => {
                        cartItem.remove();
                        updateCartTotals();
                        updateCartCount();
                        
                        // Check if cart is empty
                        const remainingItems = document.querySelectorAll('.cart-item');
                        if (remainingItems.length === 0) {
                            showEmptyCart();
                        }
                    }, 500);
                    
                    showToast('Xóa sản phẩm khỏi giỏ hàng thành công', 'success');
                } else {
                    showToast(data.message || 'Có lỗi xảy ra khi xóa sản phẩm', 'error');
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                showToast('Có lỗi xảy ra khi xóa sản phẩm', 'error');
            });
        }

        function updateCartCount() {
            fetch('../../app/controllers/CartController.php?action=count')
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    const cartBadge = document.querySelector('.cart_badge');
                    if (cartBadge) {
                        cartBadge.textContent = data.count;
                        if (data.count === 0) {
                            cartBadge.style.display = 'none';
                        } else {
                            cartBadge.style.display = 'block';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error updating cart count:', error);
            });
        }

        // Utility functions
        function showLoading() {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.innerHTML = `
                <div class="loading-spinner">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <p>Đang xử lý...</p>
                </div>
            `;
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            `;
            document.body.appendChild(overlay);
        }

        function hideLoading() {
            const overlay = document.getElementById('loading-overlay');
            if (overlay) {
                overlay.remove();
            }
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-content">
                    <i class="fa-solid ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            toast.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#4CAF50' : '#f44336'};
                color: white;
                padding: 15px 20px;
                border-radius: 5px;
                z-index: 10000;
                box-shadow: 0 2px 10px rgba(0,0,0,0.2);
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        }

        function showEmptyCart() {
            const cartItems = document.querySelector('.cart-items');
            cartItems.innerHTML = `
                <div class="empty-cart">
                    <i class="fa-solid fa-shopping-cart" style="font-size: 4rem; color: #ccc;"></i>
                    <h3>Giỏ hàng trống</h3>
                    <p>Hãy thêm sản phẩm vào giỏ hàng để tiếp tục mua sắm!</p>
                </div>
            `;
        }

        // Modal functions
        function openAddressModal() {
            document.getElementById('addressModal').style.display = 'flex';
        }

        function closeAddressModal() {
            document.getElementById('addressModal').style.display = 'none';
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; transform: translateX(0); }
                to { opacity: 0; transform: translateX(100px); }
            }
            
            @keyframes slideIn {
                from { transform: translateX(100%); opacity: 0; }
                to { transform: translateX(0); opacity: 1; }
            }
            
            @keyframes slideOut {
                from { transform: translateX(0); opacity: 1; }
                to { transform: translateX(100%); opacity: 0; }
            }
            
            .loading-spinner {
                background: white;
                padding: 20px;
                border-radius: 10px;
                text-align: center;
            }
            
            .loading-spinner i {
                font-size: 24px;
                margin-bottom: 10px;
                color: #007bff;
            }
            
            .toast-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }
        `;
        document.head.appendChild(style);
    </script>

    <style>
        
    </style>
</body>
</html>