<?php
session_start();
include_once('./config/config.php');
include_once('./app/models/User.php');
include_once ('./app/models/Product.php');
include_once ('./app/models/ProductImage.php');

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

// Khởi tạo các class
$product = new Product($conn);
$productImages = new ProductImage($conn);

// Lấy tất cả sản phẩm
// $products = $product->getAllProduct();
// Lấy 8 sản phẩm mới nhất có category là 'pet'
$products = $product->getLatestProductsByParentCategory(1, 8);
// Lấy 8 sản phẩm mới nhất có category là 'product'
$productSP = $product->getLatestProductsByParentCategory(8, 8);

$active_products = array_filter($products, function($product) {
    return isset($product['is_active']) && $product['is_active'] == 1;
});

$active_productsSP = array_filter($productSP, function($productSP) {
    return isset($productSP['is_active']) && $productSP['is_active'] == 1;
});

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./public/css/styles.css">
    <link rel="stylesheet" href="./fontawesome-free-6.4.2-web/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet" />
    <title>Monito - Home</title>
</head>

<body>
    <header class="header">
        <nav class="nav">
            <div class="container nav_container">
                <button class="hamburger_btn" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="nav_left">
                    <a href="./index.php" class="nav_logo">
                        <img src="./public/images/logo/logo.png" alt="PetShop Logo">
                    </a>
                    <ul class="nav_list">
                        <li class="nav_item"><a href="./index.php" class="nav_link">Home</a></li>
                        <li class="nav_item">
                            <a href="./views/pages/category.php" class="nav_link">Category</a>
                            <div class="category_dropdown">
                                <a href="./views/pages/category.php?category_id=1" class="category_dropdown_item">
                                    <i class="fa-solid fa-paw"></i> Thú cưng
                                </a>
                                <a href="./views/pages/category.php?category_id=8" class="category_dropdown_item">
                                    <i class="fa-solid fa-box"></i> Sản phẩm
                                </a>
                            </div>
                        </li>
                        <li class="nav_item">
                            <a href="./views/pages/social_media.php" class="nav_link">Social Media</a>
                        </li>
                        <li class="nav_item"><a href="#" class="nav_link">About</a></li>
                        <li class="nav_item"><a href="./views/pages/contact.php" class="nav_link">Contact</a></li>
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
                                    <img src="./public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="Avatar" class="user_avatar">
                                    <!-- Hoặc nếu không có avatar, dùng placeholder -->
                                    <!-- <div class="user_avatar placeholder"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div> -->
                                    <p class="user_name"><?php echo htmlspecialchars($full_name); ?><br>
                                    <?php if ($is_logged_in): ?>
                                        <span class="user_name"><?php echo htmlspecialchars($username); ?></span>
                                    <?php endif; ?></p>
                                </div>
                                <?php if ($is_logged_in && ($userModel->isAdmin($_SESSION) || $userModel->isEmployee($_SESSION))): ?>
                                    <a href="./views/admin/dashboard.php" class="auth_btn myOrder">
                                        <i class="fa-solid fa-gear"></i>
                                        Quản lý
                                    </a>
                                    <?php else: ?>
                                        <a href="./views/pages/myAccount.php?tab=my-orders" class="auth_btn myOrder">
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
        
        <div class="container header_wrapper_container">
            <div class="header_wrapper">
                <h1 class="header_main_title">Một người bạn nữa<br>Vui hơn hàng nghìn lần!</h1>
                <p class="header_p">Có một thú cưng có nghĩa là bạn sẽ có nhiều niềm vui hơn, một người bạn mới,<br>một người hạnh phúc sẽ luôn bên bạn để cùng vui chơi.<br>Chúng tôi có nhiều loại thú cưng khác nhau có thể đáp ứng nhu cầu của bạn!</p>
                <div class="header_btns">
                    <a href="https://www.youtube.com/watch?v=CxWWkNpKFvk" class="btn btn_outlined">Video Giới thiệu <i class="fa-regular fa-circle-play"></i></a>
                    <a href="./views/pages/category.php" class="btn btn_bg">Khám Phá Ngay</a>
                </div>
            </div>
        </div>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile_overlay"></div>

    <!-- Mobile Menu Sidebar -->
    <div class="mobile_menu" id="mobileMenu">
        <div class="mobile_menu_header">
            <a href="./index.php" class="mobile_menu_logo">
                <img src="./public/images/logo/logo.png" alt="PetShop Logo">
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
                <a href="./index.php" class="mobile_nav_link">Home</a>
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
        <section class="section__container intro__container">
            <h2 class="section__header">Tìm hiểu thêm về chúng tôi.</h2>
            <div class="intro__grid">
                <div class="intro__card">
                    <div class="intro__image">
                        <img src="./public/images/intro/intro-1.png" alt="intro" />
                    </div>
                    <h4>Nhà thú cưng</h4>
                    <p>
                        Nhà thú cưng là không gian riêng tư, ấm áp giúp các bé thú cưng nghỉ ngơi và cảm thấy an toàn.
                    </p>
                    <a href="./views/pages/category.php?category_id=5">Xem thêm</a>
                </div>
                <div class="intro__card">
                    <div class="intro__image">
                        <img src="./public/images/intro/intro-2.png" alt="intro" />
                    </div>
                    <h4>Thức ăn thú cưng</h4>
                    <p>
                        Thức ăn hạt cho thú cưng tiện lợi, giàu dinh dưỡng, giúp bé phát triển khỏe mạnh mỗi ngày.
                    </p>
                    <a href="./views/pages/category.php?category_id=6">Xem thêm</a>
                </div>
                <div class="intro__card">
                    <div class="intro__image">
                        <img src="./public/images/intro/intro-3.png" alt="intro" />
                    </div>
                    <h4>Mỹ phẩm cho thú cưng</h4>
                    <p>
                        Mỹ phẩm cho thú cưng giúp chăm sóc da lông mềm mượt, sạch sẽ và luôn thơm tho tự nhiên.
                    </p>
                    <a href="./views/pages/category.php?category_id=7">Xem thêm</a>
                </div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="section_header">
                    <div class="section_header_left">
                        <p class="section_header_p">Có gì mới không?</p>
                        <h2 class="section_header_h2">Hãy xem một số thú cưng của chúng tôi.</h2>
                    </div>
                    <div class="section_header_right">
                        <a href="./views/pages/category.php" class="btn_outlined btn">Xem thêm <i class="fa-solid fa-hand-point-right"></i></a>
                    </div>
                </div>
                <div class="row">
                    <?php if (!empty($active_products)): ?>
                        <?php foreach ($active_products as $productItem): ?>
                            <?php
                            // Lấy ảnh đầu tiên của sản phẩm
                            $images = $productImages->getImagesByProduct($productItem['product_id']);
                            $mainImage = !empty($images) ? $images[0]['image_url'] : 'default.png';
                            // Lấy gender của ảnh đầu tiên của sản phẩm
                            $gender = $productImages->getImagesByProduct($productItem['product_id']);
                            $mainGender = !empty($gender) ? $gender[0]['gender'] : 'Đực';
                            ?>
                        
                            <div class="column">
                                <div class="card">
                                    <img src="./public/uploads/product/<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($productItem['product_name']); ?>">
                                    <i class="fa-solid fa-eye eye-icon" onclick="viewProduct(<?php echo $productItem['product_id']; ?>)"></i>
                                    <div class="card_body">
                                        <h3 class="card_body_title"><?php echo htmlspecialchars($productItem['product_name'] . ' - ' . $productItem['color']); ?></h3>
                                        <div class="card_body_details">
                                            <div class="card_body_details_gender">
                                                Gend: <?php echo htmlspecialchars($mainGender ?? 'N/A'); ?>
                                            </div>
                                            <div class="card_body_details_age">
                                                Age: <?php echo htmlspecialchars($productItem['age'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <p class="card_body_price">
                                            <?php echo number_format($productItem['price'], 0, ',', '.'); ?> VND
                                        </p>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-products">
                            <p>Hiện tại chưa có sản phẩm pet nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- <div class="row">
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog7.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="banner">
                    <div class="banner_wrapper">
                        <img src="./public/images/banner/banner-2-img.png" alt="" class="banner_img">
                        <div class="banner_content">
                            <h1 class="banner_title"><span class="banner_title_big_text">Một người bạn nữa<br>Vui hơn hàng nghìn lần!</h1>
                            <p class="banner_p">Có một thú cưng có nghĩa là bạn sẽ có nhiều niềm vui hơn, một người bạn mới,<br>một người hạnh phúc sẽ luôn bên bạn để cùng vui chơi.</p>
                            <div class="banner_btns">
                                <a href="https://www.youtube.com/watch?v=CxWWkNpKFvk" class="btn btn_outlined">Video Giới thiệu <i
                                        class="fa-regular fa-circle-play"></i></a>
                                <a href="./views/pages/category.php" class="btn btn_bg">Khám Phá Ngay</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="section_header">
                    <div class="section_header_left">
                        <p class="section_header_p">Khó để chọn sản phẩm phù hợp cho thú cưng của bạn?</p>
                        <h2 class="section_header_h2_2">Sản phẩm của chúng tôi</h2>
                    </div>
                    <div class="section_header_right">
                        <a href="./views/pages/category.php" class="btn_outlined btn">Xem thêm <i class="fa-solid fa-hand-point-right"></i></a>
                    </div>
                </div>
                <div class="row">
                    <?php if (!empty($active_productsSP)): ?>
                        <?php foreach ($active_productsSP as $productItem): ?>
                            <?php
                            // Lấy ảnh đầu tiên của sản phẩm
                            $images = $productImages->getImagesByProduct($productItem['product_id']);
                            $mainImage = !empty($images) ? $images[0]['image_url'] : 'default.png';
                            ?>
                        
                            <div class="column">
                                <div class="card">
                                    <img src="./public/uploads/product/<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($productItem['product_name']); ?>">
                                    <i class="fa-solid fa-eye eye-icon" onclick="viewProduct(<?php echo $productItem['product_id']; ?>)"></i>
                                    <div class="card_body">
                                        <h3 class="card_body_title"><?php echo htmlspecialchars($productItem['product_name'] . ' - ' . $productItem['color']); ?></h3>
                                        <div class="card_body_details">
                                            <div class="card_body_details_gender">
                                                Weight: <?php echo htmlspecialchars($productItem['weight'] ?? 'N/A'); ?> kg
                                            </div>
                                            <div class="card_body_details_age">
                                                Material: <?php echo htmlspecialchars($productItem['material'] ?? 'N/A'); ?>
                                            </div>
                                        </div>
                                        <p class="card_body_price">
                                            <?php echo number_format($productItem['price'], 0, ',', '.'); ?> VND
                                        </p>
                                        <?php if ($productItem['stock_quantity'] > 0): ?>
                                            <div class="card_body_gift" onclick="addToCart(<?php echo $productItem['product_id']; ?>, <?php echo $productItem['stock_quantity']; ?>)">
                                                <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                                <p class="card_body_gift_p">Add to cart</p>
                                            </div>
                                            <?php else: ?>
                                            <div class="card_body_gift disabled">
                                                <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                                <p class="card_body_gift_p">Out of Stock</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-products">
                            <p>Hiện tại chưa có sản phẩm nào.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <!-- <div class="row">
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/product/product3.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                                <div class="card_body_gift">
                                    <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                    <p class="card_body_gift_p">Add to cart</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                                <div class="card_body_gift">
                                    <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                    <p class="card_body_gift_p">Add to cart</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                                <div class="card_body_gift">
                                    <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                    <p class="card_body_gift_p">Add to cart</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                                <div class="card_body_gift">
                                    <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                    <p class="card_body_gift_p">Add to cart</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                                <div class="card_body_gift">
                                    <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                    <p class="card_body_gift_p">Add to cart</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                                <div class="card_body_gift">
                                    <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                    <p class="card_body_gift_p">Add to cart</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                                <div class="card_body_gift">
                                    <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                    <p class="card_body_gift_p">Add to cart</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/pet/dog8.png" alt="">
                            <i class="fa-solid fa-eye eye-icon"></i>
                            <div class="card_body">
                                <h3 class="card_body_title">MO231 - Pomeranian White</h3>
                                <div class="card_body_details">
                                    <div class="card_body_details_gender">Gend: Female</div>
                                    <div class="card_body_details_age">Age: 3months</div>
                                </div>
                                <p class="card_body_price">6.900.000 VND</p>
                                <div class="card_body_gift">
                                    <img src="./public/uploads/icon/trolley.png" alt="" class="card_body_gift_icon">
                                    <p class="card_body_gift_p">Add to cart</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div> -->
            </div>
        </section>
        <section class="section__container service__container" id="service">
            <h2 class="section__header">What we can do for you</h2>
            <div class="service__flex">
                <div class="service__card">
                    <div>
                        <img src="./public/images/service/service-1.png" alt="service" />
                    </div>
                    <p>Emergency Care</p>
                </div>
                <div class="service__card">
                    <div>
                        <img src="./public/images/service/service-2.png" alt="service" />
                    </div>
                    <p>Vaccination Services</p>
                </div>
                <div class="service__card">
                    <div>
                        <img src="./public/images/service/service-3.png" alt="service" />
                    </div>
                    <p>Nutrition Counseling</p>
                </div>
                <div class="service__card">
                    <div>
                        <img src="./public/images/service/service-4.png" alt="service" />
                    </div>
                    <p>Behavioral Consultation</p>
                </div>
                <div class="service__card">
                    <div>
                        <img src="./public/images/service/service-5.png" alt="service" />
                    </div>
                    <p>Pet Boarding Services</p>
                </div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="banner_2">
                    <div class="banner_2_wrapper">
                        <div class="banner_2_content">
                            <h1 class="banner_title">
                                <span class="banner_title_big_text">
                                    Nhận con nuôi
                                    <img src="./public/uploads/icon/fontisto_paw.png" alt="paw icon" class="icon_paw" />
                                </span>
                                Chúng tôi cần giúp đỡ. Họ cũng vậy.
                            </h1>
                            <p class="banner_2_p">Nhận nuôi một con thú cưng và cho nó một ngôi nhà, nó sẽ yêu bạn một cách vô điều kiện.</p>
                            <div class="banner_2_btns">
                                <a href="https://www.youtube.com/watch?v=CxWWkNpKFvk" class="btn btn_outlined">Video Giới thiệu <i
                                        class="fa-regular fa-circle-play"></i></a>
                                <a href="./views/pages/category.php" class="btn btn_bg">Khám Phá Ngay</a>
                            </div>
                        </div>
                        <img src="./public/images/banner/banner-3-img.png" alt="" class="banner_2_img">
                    </div>
                </div>
            </div>
        </section>
        <section class="section">
            <div class="container">
                <div class="section_header">
                    <div class="section_header_left">
                        <p class="section_header_p">Bạn đã biết chưa?</p>
                        <h2 class="section_header_h2_2">Kiến thức hữu ích về thú cưng</h2>
                    </div>
                    <div class="section_header_right">
                        <a href="./views/pages/category.php" class="btn_outlined btn">Xem thêm <i class="fa-solid fa-hand-point-right"></i></a>
                    </div>
                </div>
                <div class="row">
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/post/blog2.png" alt="">
                            <div class="card_body">
                                <p class="card_body_category">Kiến thức về thú cưng</p>
                                <h3 class="card_body_title">What is a Pomeranian? How to Identify Pomeranian Dogs</h3>
                                <p class="card_body_text">The Pomeranian, also known as the Pomeranian (Pom dog), is
                                    always in the top of the cutest pets. Not only that, the small, lovely, smart,
                                    friendly, and skillful circus dog breed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/post/blog3.png" alt="">
                            <div class="card_body">
                                <p class="card_body_category">Kiến thức về thú cưng</p>
                                <h3 class="card_body_title">What is a Pomeranian? How to Identify Pomeranian Dogs</h3>
                                <p class="card_body_text">The Pomeranian, also known as the Pomeranian (Pom dog), is
                                    always in the top of the cutest pets. Not only that, the small, lovely, smart,
                                    friendly, and skillful circus dog breed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/post/blog2.png" alt="">
                            <div class="card_body">
                                <p class="card_body_category">Kiến thức về thú cưng</p>
                                <h3 class="card_body_title">What is a Pomeranian? How to Identify Pomeranian Dogs</h3>
                                <p class="card_body_text">The Pomeranian, also known as the Pomeranian (Pom dog), is
                                    always in the top of the cutest pets. Not only that, the small, lovely, smart,
                                    friendly, and skillful circus dog breed.</p>
                            </div>
                        </div>
                    </div>
                    <div class="column">
                        <div class="card">
                            <img src="./public/uploads/post/blog1.png" alt="">
                            <div class="card_body">
                                <p class="card_body_category">Kiến thức về thú cưng</p>
                                <h3 class="card_body_title">What is a Pomeranian? How to Identify Pomeranian Dogs</h3>
                                <p class="card_body_text">The Pomeranian, also known as the Pomeranian (Pom dog), is
                                    always in the top of the cutest pets. Not only that, the small, lovely, smart,
                                    friendly, and skillful circus dog breed.</p>
                            </div>
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
                        <a href="#"><img src="./public/images/logo/logo.png" alt=""></a>
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
    
    <script src="./public/js/index.js"></script>
    <script src="./public/js/addToCart.js"></script>
</body>
</html>