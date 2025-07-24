<?php
include_once('../../config/config.php');
include_once(__DIR__ .'/../../app/models/User.php');
include_once('../../app/models/Product.php');
include_once(__DIR__ . '/../../app/models/ProductImage.php');

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

$productImages = new ProductImage($conn);

$productModel = new Product($conn);

// Lấy category_id từ URL
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;

if ($category_id === 1 || $category_id === 8) {
    // Nếu là Thú cưng hoặc Sản phẩm, lấy theo category cha
    $products = $productModel->getAllProductByParentCategory($category_id);
} elseif ($category_id > 0) {
    // Các danh mục khác (có giá trị hợp lệ)
    $products = $productModel->getAllProductByCategory($category_id);
} else {
    // Không truyền category_id hoặc là 0 hoặc âm
    $products = $productModel->getAllProduct();
}

$active_products = array_filter($products, function($product) {
    return isset($product['is_active']) && $product['is_active'] == 1;
});

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../public/css/styles.css">
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />
    <title>Category</title>
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
                    <div class="search_box">
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

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <ul class="breadcrumb_list">
                <li class="breadcrumb_item">Home</li>
                <li class="breadcrumb_item">Dog</li>
                <li class="breadcrumb_item">Small Dog</li>
            </ul>
        </div>
    </div>

    <main>
        <section class="section">
            <div class="container">
                <div class="banner banner_category">
                    <div class="banner_wrapper banner_wrapper_category">
                        <img src="../../public/images/banner/banner-4-img.png" alt="" class="banner_img">
                        <div class="banner_content banner_content_category">
                            <h1 class="banner_title banner_title_category"><span class="banner_title_big_text">Một người bạn nữa<br>Vui hơn hàng nghìn lần!</h1>
                            <p class="banner_p banner_p_category">Có một thú cưng có nghĩa là bạn sẽ có nhiều niềm vui hơn, một người bạn mới,<br>một người hạnh phúc sẽ luôn bên bạn để cùng vui chơi.<br>Chúng tôi có nhiều loại thú cưng khác nhau có thể đáp ứng nhu cầu của bạn!</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section">
            <!-- Replace the section_header and row sections with this code -->
            <div class="container">
                <div class="category_layout">
                    <aside class="filter_sidebar">
                        <h3 class="filter_sidebar_title">Filter</h3>

                        <!-- Gender Filter -->
                        <div class="filter_group">
                            <h4 class="filter_group_title">Gender</h4>
                            <div class="filter_options">
                                <div class="filter_option">
                                    <input type="checkbox" id="male">
                                    <label for="male">Male</label>
                                </div>
                                <div class="filter_option">
                                    <input type="checkbox" id="female">
                                    <label for="female">Female</label>
                                </div>
                            </div>
                        </div>

                        <!-- Color Filter -->
                        <div class="filter_group">
                            <h4 class="filter_group_title">Color</h4>
                            <div class="filter_options">
                                <div class="filter_option">
                                    <div class="color_dot red"></div>
                                    <label>Red</label>
                                </div>
                                <div class="filter_option">
                                    <div class="color_dot apricot"></div>
                                    <label>Apricot</label>
                                </div>
                                <div class="filter_option">
                                    <div class="color_dot black"></div>
                                    <label>Black</label>
                                </div>
                                <div class="filter_option">
                                    <div class="color_dot black_white"></div>
                                    <label>Black & White</label>
                                </div>
                                <div class="filter_option">
                                    <div class="color_dot silver"></div>
                                    <label>Silver</label>
                                </div>
                                <div class="filter_option">
                                    <div class="color_dot tan"></div>
                                    <label>Tan</label>
                                </div>
                            </div>
                        </div>

                        <!-- Price Filter -->
                        <div class="filter_group">
                            <h4 class="filter_group_title">Price</h4>
                            <div class="price_filter">
                                <div class="price_filter_inputs">
                                    <input type="text" placeholder="Min" class="price_filter_input">
                                    <input type="text" placeholder="Max" class="price_filter_input">
                                </div>
                                <span class="price_filter_currency">$</span>
                            </div>
                        </div>

                        <!-- Breed Filter -->
                        <div class="filter_group">
                            <h4 class="filter_group_title">Breed</h4>
                            <div class="filter_options">
                                <div class="filter_option">
                                    <input type="checkbox" id="small">
                                    <label for="small">Small</label>
                                </div>
                                <div class="filter_option">
                                    <input type="checkbox" id="medium">
                                    <label for="medium">Medium</label>
                                </div>
                                <div class="filter_option">
                                    <input type="checkbox" id="large">
                                    <label for="large">Large</label>
                                </div>
                            </div>
                        </div>
                    </aside>

                    <div class="products_container">
                        <div class="products_header">
                            <div>
                                <h2 class="products_title">Small Dog</h2>
                                <p class="products_count">52 puppies</p>
                            </div>
                            <select class="sort_dropdown">
                                <option>Sort by: Popular</option>
                                <option>Price: Low to High</option>
                                <option>Price: High to Low</option>
                                <option>Newest</option>
                            </select>
                        </div>

                        <div class="products_grid">
                            <?php if (!empty($active_products)): ?>
                                <?php foreach ($active_products  as $productItem): ?>
                                    <?php
                                    // Lấy ảnh đầu tiên
                                    $images = $productImages->getImagesByProduct($productItem['product_id']);
                                    $mainImage = !empty($images) ? $images[0]['image_url'] : 'default.png';

                                    // Giới tính ảnh đầu tiên
                                    $mainGender = !empty($images) ? $images[0]['gender'] : 'N/A';

                                    // Lấy danh mục cha (parent_category_id)
                                    $categoryId = $productItem['category_id'];
                                    $category = $productModel->getCategoryById($categoryId); // Bạn cần định nghĩa hàm này trong model Product
                                    $parentId = $category['parent_category_id'] ?? null;
                                    ?>
                                    <div class="product_card">
                                        <img class = "img_product"src="../../public/uploads/product/<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($productItem['product_name']); ?>">
                                        <i class="fa-solid fa-eye eye-icon" onclick="viewProduct(<?php echo $productItem['product_id']; ?>)"></i>
                                        <div class="product_details">
                                            <h3 class="product_code">
                                                <?php echo htmlspecialchars($productItem['product_code'] ?? $productItem['product_name']); ?>
                                            </h3>
                                            <div class="product_meta">
                                                <span class="product_gender">Genre: <?php echo htmlspecialchars($mainGender); ?></span>
                                                <span class="product_age">Age: <?php echo htmlspecialchars($productItem['age'] ?? 'N/A'); ?> months</span>
                                            </div>
                                            <p class="product_price"><?php echo number_format($productItem['price'], 0, ',', '.'); ?> VND</p>
                                            
                                            <?php if ($parentId == 8): ?>
                                                <?php if ($productItem['stock_quantity'] > 0): ?>
                                                    <div class="card_body_gift" onclick="addToCart(<?php echo $productItem['product_id']; ?>, <?php echo $productItem['stock_quantity']; ?>)">
                                                        <img src="../../public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                                        <p class="card_body_gift_p">Add to cart</p>
                                                    </div>
                                                    <?php else: ?>
                                                    <div class="card_body_gift disabled">
                                                        <img src="../../public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                                        <p class="card_body_gift_p">Out of Stock</p>
                                                    </div>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-products">
                                    <p>Hiện tại chưa có sản phẩm nào.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="pagination" id="pagination">
                            <!-- Pagination will be dynamically loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </section>
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
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </form>
                    <br />
                    <h4>Follow Us</h4>
                    <ul class="footer__socials">
                        <li>
                            <a href="#"><i class="ri-facebook-fill"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-twitter-fill"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-youtube-fill"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-pinterest-line"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-instagram-line"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-tiktok-fill"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="../../public/js/category.js"></script>
    <script src="../../public/js/index.js"></script>
    <script src="../../public/js/addToCart.js"></script>
    <script src="../../public/js/filter.js"></script>
</body>

</html>