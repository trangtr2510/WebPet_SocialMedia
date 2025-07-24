<?php
session_start();
include_once(__DIR__ .'/../../config/config.php');
include_once(__DIR__ .'/../../app/models/Product.php');
include_once(__DIR__ .'/../../app/models/User.php');
include_once(__DIR__ .'/../../app/models/ProductImage.php');
include_once(__DIR__ .'/../../app/models/Category.php'); // Thêm Category model

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

// Khởi tạo các class
$product = new Product($conn);
$category = new Category($conn); // Khởi tạo Category model
// Lấy tất cả sản phẩm
$products = $product->getAllProduct();
$productImages = new ProductImage($conn);

// Lấy product_id từ URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header("Location: ../pages/category.php");
    exit;
}

// Lấy thông tin sản phẩm
$productData = $product->getProductByID($product_id);
if (empty($productData)) {
    header("Location: ../pages/category.php");
    exit;
}

$currentProduct = $productData[0]; // Lấy sản phẩm đầu tiên

// Lấy thông tin category và parent category
$categoryInfo = null;
$parentCategoryId = null;
$isCartInterface = false; // Biến để xác định giao diện

if ($currentProduct['category_id']) {
    $categoryInfo = $category->getCategoryByID($currentProduct['category_id']);
    if ($categoryInfo) {
        // Nếu có parent_category_id, lấy parent category
        if ($categoryInfo['parent_category_id']) {
            $parentCategoryId = $categoryInfo['parent_category_id'];
        } else {
            // Nếu không có parent, chính nó là parent category
            $parentCategoryId = $categoryInfo['category_id'];
        }
        
        // Kiểm tra nếu parent category là 8 thì hiển thị giao diện giỏ hàng
        if ($parentCategoryId == 8) {
            $isCartInterface = true;
        }
    }
}

// Lấy danh sách ảnh của sản phẩm
$images = $productImages->getImagesByProduct($product_id);

// Lấy ảnh chính (primary image)
$primaryImage = '';
$galleryImages = [];
foreach ($images as $image) {
    if ($image['is_primary']) {
        $primaryImage = $image['image_url'];
    }
    $galleryImages[] = $image;
}

// Nếu không có ảnh chính, lấy ảnh đầu tiên
if (empty($primaryImage) && !empty($galleryImages)) {
    $primaryImage = $galleryImages[0]['image_url'];
}

// Format giá tiền
function formatPrice($price) {
    return number_format($price, 0, ',', '.') . ' VND';
}

// Format ngày tháng
function formatDate($date) {
    return date('d-M-Y', strtotime($date));
}

// Lấy thông tin category
$categoryName = '';
if ($currentProduct['category_id']) {
    $categoryQuery = "SELECT category_name FROM categories WHERE category_id = ?";
    $stmt = $conn->prepare($categoryQuery);
    $stmt->bind_param("i", $currentProduct['category_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $categoryName = $row['category_name'];
    }
}
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
    <style>
        /* CSS cho quantity selector */
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0;
        }
        
        .quantity-selector label {
            font-weight: 600;
            color: #333;
        }
        
        .quantity-input_detail {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 5px;
            overflow: hidden;
        }
        
        .quantity-btn {
            background: #f8f9fa;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            font-size: 18px;
            color: #666;
            transition: background-color 0.3s;
        }
        
        .quantity-btn:hover {
            background: #e9ecef;
        }
        
        .quantity-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .quantity-number {
            padding: 10px 10px;
            border: none;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
            width: 60px;
            background: white;
        }
        
        .add-to-cart-btn {
            background: #007bff;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
            width: 100%;
            margin-top: 10px;
        }
        
        .add-to-cart-btn:hover {
            background: #0056b3;
        }
        
        .add-to-cart-btn:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        
        .cart-interface {
            margin-top: 20px;
        }
        
        .total-price {
            font-size: 18px;
            font-weight: 600;
            color: #007bff;
            margin: 15px 0;
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
                            <a href="../../views/pages/category.html" class="nav_link">Category</a>
                            <div class="category_dropdown">
                                <a href="#" class="category_dropdown_item">
                                    <i class="fa-solid fa-paw"></i> Thú cưng
                                </a>
                                <a href="#" class="category_dropdown_item">
                                    <i class="fa-solid fa-box"></i> Sản phẩm
                                </a>
                            </div>
                        </li>
                        <li class="nav_item">
                            <a href="#" class="nav_link">Social Media</a>
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
                                        <a href="../../views/pages/myAccount.php?tab=my-orders" class="auth_btn myOrder">
                                            <i class="fa-solid fa-shopping-bag"></i>
                                            Đơn hàng cá nhân
                                        </a>
                                    <?php endif; ?>
                                <a href="../../views/pages/myAccount.php" class="auth_btn myAccount">
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

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <ul class="breadcrumb_list" >
                <li class="breadcrumb_item"><a href="/" style= 'text-decoration: none; color: #667479;'>Home</a></li>
                <li class="breadcrumb_item"><a href="../../views/pages/category.php" style= 'color: #667479; text-decoration: none;'>Category</a></li>
                <?php if ($categoryName): ?>
                    <li class="breadcrumb_item"><?php echo htmlspecialchars($categoryName); ?></li>
                <?php endif; ?>
                <li class="breadcrumb_item active"><?php echo htmlspecialchars($currentProduct['product_name']); ?></li>
            </ul>
        </div>
    </div>

    <main>
        <div class="section">
            <div class="container product2">
                <div class="product2_card">
                    <div class="product2_gallery">
                        <!-- Main Swiper -->
                        <div style="--swiper-navigation-color: #fff; --swiper-pagination-color: #fff"
                            class="swiper mySwiper2">
                            <div class="swiper-wrapper">
                                <?php if (!empty($galleryImages)): ?>
                                    <?php foreach ($galleryImages as $index => $image): ?>
                                        <div class="swiper-slide" 
                                             data-gender="<?php echo htmlspecialchars($image['gender'] ?? ''); ?>"
                                             data-color="<?php echo htmlspecialchars($image['color'] ?? ''); ?>"
                                             data-index="<?php echo $index; ?>">
                                            <img src="../../public/uploads/product/<?php echo htmlspecialchars($image['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($image['alt_text'] ?: $currentProduct['product_name']); ?>" />
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="swiper-slide">
                                        <img src="../../public/images/placeholder/no-image.jpg" alt="No Image Available" />
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="swiper-button-next"></div>
                            <div class="swiper-button-prev"></div>
                        </div>

                        <!-- Thumbnail Swiper -->
                        <div thumbsSlider="" class="swiper mySwiper">
                            <div class="swiper-wrapper">
                                <?php if (!empty($galleryImages)): ?>
                                    <?php foreach ($galleryImages as $index => $image): ?>
                                        <div class="swiper-slide" 
                                             data-gender="<?php echo htmlspecialchars($image['gender'] ?? ''); ?>"
                                             data-color="<?php echo htmlspecialchars($image['color'] ?? ''); ?>"
                                             data-index="<?php echo $index; ?>">
                                            <img src="../../public/uploads/product/<?php echo htmlspecialchars($image['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($image['alt_text'] ?: $currentProduct['product_name']); ?>" />
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="swiper-slide">
                                        <img src="../../public/images/placeholder/no-image.jpg" alt="No Image Available" />
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="product2_details">
                        <h1 class="product2_title"><?php echo htmlspecialchars($currentProduct['product_name']); ?></h1>
                        <h2 class="product2_price"><?php echo formatPrice($currentProduct['price']); ?></h2>
                        
                        <?php if ($isCartInterface): ?>
                            <!-- Giao diện giỏ hàng cho parent category = 8 -->
                            <div class="cart-interface">
                                <div class="quantity-selector">
                                    <label for="quantity">Quantity:</label>
                                    <div class="quantity-input_detail">
                                        <button type="button" class="quantity-btn" id="decrease-btn" onclick="decreaseQuantity()">-</button>
                                        <input type="number" id="quantity" class="quantity-number" value="1" min="1" max="<?php echo $currentProduct['stock_quantity']; ?>" readonly>
                                        <button type="button" class="quantity-btn" id="increase-btn" onclick="increaseQuantity()">+</button>
                                    </div>
                                </div>
                                
                                <div class="total-price">
                                    Total: <span id="total-price"><?php echo formatPrice($currentProduct['price']); ?></span>
                                </div>
                                
                                <button type="button" class="add-to-cart-btn" onclick="addToCart()" <?php echo $currentProduct['stock_quantity'] <= 0 ? 'disabled' : ''; ?>>
                                    <i class="fas fa-shopping-cart"></i> 
                                    <?php echo $currentProduct['stock_quantity'] <= 0 ? 'Out of Stock' : 'Add to Cart'; ?>
                                </button>
                            </div>
                        <?php else: ?>
                            <!-- Giao diện gốc cho các category khác -->
                            <div class="product2_btns">
                                <a href="./contact.php" class="btn btn_bg">Contact us</a>
                                <a href="./social_media.php" class="btn btn_outlined">
                                    <i class="fa-regular fa-comment-dots"></i> Chat with Monito
                                </a>
                            </div>
                        <?php endif; ?>

                        <ul class="product2_list">
                            <li class="product2_item" id="gender-item" style="display: none;">
                                <p class="product2_item_key">Gender</p>
                                <p class="product2_item_value" id="gender-value">-</p>
                            </li>
                            
                            <li class="product2_item" id="color-item" style="display: none;">
                                <p class="product2_item_key">Color</p>
                                <p class="product2_item_value" id="color-value">-</p>
                            </li>
                            
                            <?php if ($currentProduct['age']): ?>
                                <li class="product2_item">
                                    <p class="product2_item_key">Age</p>
                                    <p class="product2_item_value"><?php echo $currentProduct['age']; ?> months</p>
                                </li>
                            <?php endif; ?>
                            
                            <?php if ($currentProduct['weight']): ?>
                                <li class="product2_item">
                                    <p class="product2_item_key">Weight</p>
                                    <p class="product2_item_value"><?php echo $currentProduct['weight']; ?> kg</p>
                                </li>
                            <?php endif; ?>
                            
                            <li class="product2_item">
                                <p class="product2_item_key">Stock</p>
                                <p class="product2_item_value">
                                    <?php echo $currentProduct['stock_quantity'] > 0 ? $currentProduct['stock_quantity'] . ' available' : 'Out of stock'; ?>
                                </p>
                            </li>
                            
                            <?php if ($categoryName): ?>
                                <li class="product2_item">
                                    <p class="product2_item_key">Category</p>
                                    <p class="product2_item_value"><?php echo htmlspecialchars($categoryName); ?></p>
                                </li>
                            <?php endif; ?>
                            
                            <li class="product2_item">
                                <p class="product2_item_key">Published Date</p>
                                <p class="product2_item_value"><?php echo formatDate($currentProduct['created_at']); ?></p>
                            </li>
                            
                            <?php if ($currentProduct['description']): ?>
                                <li class="product2_item">
                                    <p class="product2_item_key">Description</p>
                                    <p class="product2_item_value product2_item_value_description"><?php echo nl2br(htmlspecialchars($currentProduct['description'])); ?></p>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="review-container">
                <div class="tab-navigation">
                    <button class="tab-button active" data-tab="reviews">Reviews</button>
                    <button class="tab-button" data-tab="puppies">See More Puppies</button>
                </div>

                <div id="reviews" class="tab-content active">
                    <!-- Review Summary -->
                    <div class="review-summary">
                        <div class="rating-overview">
                            <div class="rating-score">4.8</div>
                            <div class="rating-text">out of 5</div>
                            <div class="rating-stars">
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                            </div>
                            <div class="rating-text">(107 Reviews)</div>
                        </div>
                        <div class="rating-breakdown">
                            <div class="rating-bar">
                                <span class="rating-label">5 Star</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: 85%"></div>
                                </div>
                            </div>
                            <div class="rating-bar">
                                <span class="rating-label">4 Star</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: 12%"></div>
                                </div>
                            </div>
                            <div class="rating-bar">
                                <span class="rating-label">3 Star</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: 2%"></div>
                                </div>
                            </div>
                            <div class="rating-bar">
                                <span class="rating-label">2 Star</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: 1%"></div>
                                </div>
                            </div>
                            <div class="rating-bar">
                                <span class="rating-label">1 Star</span>
                                <div class="bar-container">
                                    <div class="bar-fill" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Review Form -->
                    <div class="add-review-form">
                        <h3>Add your review</h3>
                        <p style="color: #666; font-size: 14px; margin-bottom: 20px;">Your email address will not be
                            published. Required fields are marked *</p>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Name *</label>
                                <input type="text" id="name" placeholder="Ex: John Doe" required>
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" placeholder="example@gmail.com" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Your Rating *</label>
                            <div class="rating-input">
                                <i class="fas fa-star star" data-rating="1"></i>
                                <i class="fas fa-star star" data-rating="2"></i>
                                <i class="fas fa-star star" data-rating="3"></i>
                                <i class="fas fa-star star" data-rating="4"></i>
                                <i class="fas fa-star star" data-rating="5"></i>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="review-title">Add Review Title *</label>
                            <input type="text" id="review-title" placeholder="Write Title here" required>
                        </div>

                        <div class="form-group">
                            <label for="review-text">Add Detailed Review *</label>
                            <textarea id="review-text" placeholder="Write here" required></textarea>
                        </div>

                        <div class="form-group">
                            <label>Photo / Video (Optional)</label>
                            <div class="file-upload">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div>Drag a Photo or Video</div>
                                <button type="button"
                                    style="background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px; margin-top: 10px;">Browse</button>
                            </div>
                        </div>

                        <button type="submit" class="submit-btn">Submit</button>
                    </div>

                    <!-- Review Controls -->
                    <div class="review-controls">
                        <div class="review-count">Showing 1-4 of 24 results</div>
                        <div class="sort-dropdown">
                            <span>Sort by:</span>
                            <select class="sort-select">
                                <option>Newest</option>
                                <option>Oldest</option>
                                <option>Highest Rating</option>
                                <option>Lowest Rating</option>
                            </select>
                        </div>
                    </div>

                    <!-- Review List -->
                    <div class="review-list">
                        <div class="review-item">
                            <div class="review-header">
                                <img src="https://images.unsplash.com/photo-1494790108755-2616b612b786?w=150&h=150&fit=crop&crop=face"
                                    alt="Reviewer" class="reviewer-avatar">
                                <div class="reviewer-info">
                                    <div class="reviewer-name">Kristin Watson</div>
                                    <div class="reviewer-verified">
                                        <i class="fas fa-check-circle"></i>
                                        (Verified)
                                    </div>
                                </div>
                                <div class="review-date">1 month ago</div>
                            </div>
                            <div class="review-rating">
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <span style="margin-left: 10px; font-weight: 600;">5.0</span>
                            </div>
                            <div class="review-title">Love It: My Recent Clothing Purchase</div>
                            <div class="review-text">I recently picked up some new clothes and I have to say, I'm loving
                                them! From the fit to the fabric, everything about these pieces is just perfect. They're
                                comfortable, stylish, and exactly what I was looking for.</div>
                            <div class="review-images">
                                <img src="https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?w=200&h=200&fit=crop"
                                    alt="Review image" class="review-image">
                                <img src="https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?w=200&h=200&fit=crop"
                                    alt="Review image" class="review-image">
                                <img src="https://images.unsplash.com/photo-1441984904996-e0b6ba687e04?w=200&h=200&fit=crop"
                                    alt="Review image" class="review-image">
                            </div>
                        </div>

                        <div class="review-item">
                            <div class="review-header">
                                <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face"
                                    alt="Reviewer" class="reviewer-avatar">
                                <div class="reviewer-info">
                                    <div class="reviewer-name">Bessie Cooper</div>
                                    <div class="reviewer-verified">
                                        <i class="fas fa-check-circle"></i>
                                        (Verified)
                                    </div>
                                </div>
                                <div class="review-date">2 months ago</div>
                            </div>
                            <div class="review-rating">
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <i class="fas fa-star star"></i>
                                <span style="margin-left: 10px; font-weight: 600;">5.0</span>
                            </div>
                            <div class="review-title">Excellent Product, I like It!</div>
                            <div class="review-text">I recently treated myself to some new clothes, and I couldn't be
                                happier with my purchase! The fit is spot-on, and the fabric feels amazing against my
                                skin. These pieces are not only comfortable but incredibly stylish as well. They're
                                exactly what I've been searching for to elevate my wardrobe. I'm absolutely loving them!
                            </div>
                        </div>
                    </div>
                </div>

                <div id="puppies" class="tab-content">
                    <section class="section">
                        <div class="container">
                            <div class="section_header">
                                <div class="section_header_left">
                                    <p class="section_header_p">What's new?</p>
                                    <h2 class="section_header_h2_2">See more puppies</h2>
                                </div>
                                <div class="section_header_right">
                                    <a href="#" class="btn_outlined btn">See more <i
                                            class="fa-solid fa-hand-point-right"></i></a>
                                </div>
                            </div>
                            <div class="row">
                                <?php if (!empty($products)): ?>
                                    <?php 
                                    // Lấy 4 sản phẩm đầu tiên
                                    $limitedProducts = array_slice($products, 0, 4);
                                    foreach ($limitedProducts as $productItem): ?>
                                        <?php
                                        // Lấy ảnh đầu tiên của sản phẩm
                                        $images = $productImages->getImagesByProduct($productItem['product_id']);
                                        $mainImage = !empty($images) ? $images[0]['image_url'] : 'default.png';
                                        // Lấy gender của ảnh đầu tiên của sản phẩm
                                        $gender = $productImages->getGenderByProduct($productItem['product_id']);
                                        $mainGender = !empty($gender) ? $gender[0]['gender'] : 'Đực';
                                        ?>
                                        
                                        <div class="column">
                                            <div class="card">
                                                <img src="../../public/uploads/product/<?php echo htmlspecialchars($mainImage); ?>" alt="<?php echo htmlspecialchars($productItem['product_name']); ?>">
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
                                        <p>Hiện tại chưa có sản phẩm nào.</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>
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
    <script>
        const imageData = <?php echo json_encode($galleryImages); ?>;
    </script>
    <script>
        // Biến global để lưu giá gốc và stock
        const originalPrice = <?php echo $currentProduct['price']; ?>;
        const maxStock = <?php echo $currentProduct['stock_quantity']; ?>;
        const isCartInterface = <?php echo $isCartInterface ? 'true' : 'false'; ?>;
        const productId = <?php echo $product_id; ?>;
        const productSize = "<?php echo htmlspecialchars($currentProduct['size'] ?? ''); ?>";
    </script>
    <script src="/WebsitePet/public/js/details.js"></script>
    <script src="/WebsitePet/public/js/index.js"></script>
</body>

</html>