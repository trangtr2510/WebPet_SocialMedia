<?php
session_start();
include_once(__DIR__ .'/../../config/config.php');
include_once(__DIR__ .'/../../app/models/Product.php');
include_once(__DIR__ .'/../../app/models/User.php');
include_once(__DIR__ .'/../../app/models/ProductImage.php');

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login_register.php');
    exit();
}

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {

    // CHỈ xử lý nếu là AJAX
    require_once(__DIR__ . '/../../app/models/Post.php');
    $postModel = new Post($conn);

    // Xử lý upload ảnh
    $imagePath = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../../public/uploads/posts/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = 'posts/' . $filename;
        }
    }

    $postData = [
        'title' => $_POST['title'] ?? '',
        'content' => $_POST['content'] ?? '',
        'author_id' => $_POST['author_id'] ?? '',
        'featured_image' => $imagePath,
        'post_type' => 'news',
        'status' => $_POST['status'] ?? 'chờ xác nhận',
        'published_at' => date('Y-m-d H:i:s')
    ];

    if ($postModel->create($postData)) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Đăng bài viết thành công'
        ]);
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi khi tạo bài viết'
        ]);
    }

    exit;
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Social Media</title>

    <!-- Link CSS -->
    <link rel="stylesheet" href="../../public/css/socila_media.css">
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/chat.css">
    <!-- Swiper slider link -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

</head>

<body>
    <!-- Navbar -->
    <header class="header header_category">
        <nav class="nav nav_category">
            <div class="container nav_container">
                    <button class="hamburger_btn" type="button" style = "background: white;">
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
                <a href="./views/pages/category.html" class="mobile_nav_link">
                    Category
                    <i class="fa-solid fa-chevron-down" style="float: right; transition: transform 0.3s ease;"></i>
                </a>
                <div class="mobile_category_dropdown" id="mobileCategoryDropdown">
                    <a href="#" class="mobile_category_item">Thú cưng</a>
                    <a href="#" class="mobile_category_item">Sản phẩm</a>
                </div>
            </li>
            <li class="mobile_nav_item message_nav_item">
                <a href="#" class="mobile_nav_link">Message</a>
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

    <!-- Main section -->
    <main>
        <div class="container main-container">
            <div class="main-left">
                <!-- profile start -->
                <a class="profile">
                    <div class="profile-picture" id="my-profile-picture">
                        <img src="../../public/uploads/avatar/KH001_1742482686.jpg" alt="">
                    </div>
                    <div class="profile-handle">
                        <h4>Changgg</h4>
                        <p class="text-fry">
                            @changthuiii
                        </p>
                    </div>
                </a>
                <!-- profile end -->

                <!-- Aside bar -->
                <aside>
                    <a class="menu-item active">
                        <span><img src="../../public/svg/house-door.svg" alt=""></span>
                        <h3>Home</h3>
                    </a>

                    <a class="menu-item" id="post-management-trigger">
                        <span><img src="../../public/svg/pencil-square.svg" alt=""></span>
                        <h3>Quản lý bài viết</h3>
                    </a>

                    <a class="menu-item" id="bookmark-button">
                        <span><img src="../../public/svg/bookmarks.svg" alt=""></span>
                        <h3>Book Marks</h3>
                    </a>

                    <a class="menu-item" id="theme">
                        <span><img src="../../public/svg/palette.svg" alt=""></span>
                        <h3>Theme</h3>
                    </a>

                    <!-- Add post btn -->
                    <label for="add-post" class="btn btn-primary btn-lg" id="create-lg">Create a port</label>

                </aside>
            </div>
            <div class="main-middle">
                <div class="middle-container">
                    <form id="post_input" action="social_media.php" method="POST" class="add-post input-post" enctype="multipart/form-data">
                        <div class="profile-picture" id="my-profile-picture">
                            <img src="../../public/uploads/avatar/<?php echo $img; ?>" alt="">
                        </div>
                        
                        <input type="hidden" name="author_id" value="<?php echo $_SESSION['user_id'] ?? ''; ?>">
                        
                        <input type="text" name="content" placeholder="Post content" id="post-title" required>
                        
                        <!-- <textarea name="content" placeholder="What's on your mind?" id="post-content" required></textarea> -->
                         
                        <div class="post-image-upload">
                            <label for="post-image">
                                <i class="fas fa-camera"></i> Add Photo
                            </label>
                            <input type="file" name="image" id="post-image" accept="image/*">
                            <div id="image-preview"></div>
                        </div>
                        
                        <input type="submit" name="submit_post" value="Post" class="btn btn-primary">
                    </form>

                    <!-- feed area start -->
                    <div class="feeds">
                        <?php
                        // Include Post model
                        include_once(__DIR__ .'/../../app/models/Post.php');
                        $postModel = new Post($conn);
                        $posts = $postModel->getPublishedPostsWithLikes(); // Updated method

                        if ($posts->num_rows > 0) {
                            while ($post = $posts->fetch_assoc()) {
                                // Get author info
                                $author = $userModel->getUserById($post['author_id']);
                                $authorName = $author['full_name'] ?? 'Unknown';
                                $authorAvatar = !empty($author['img']) ? $author['img'] : 'default.jpg';
                                $postTime = date('M j, Y', strtotime($post['published_at']));
                                
                                // Check if current user liked this post
                                $isLiked = false;
                                if ($is_logged_in) {
                                    $isLiked = $postModel->isLikedByUser($post['post_id'], $_SESSION['user_id']);
                                }
                                
                                $likeCount = $post['like_count'] ?? 0;
                                ?>
                                <div class="feed" data-post-id="<?php echo $post['post_id']; ?>">
                                    <div class="feed-top">
                                        <div class="user">
                                            <div class="profile-picture">
                                                <img src="../../public/uploads/avatar/<?php echo $authorAvatar; ?>" alt="">
                                            </div>
                                            <div class="info">
                                                <h3><?php echo htmlspecialchars($authorName); ?></h3>
                                                <div class="time text-gry">
                                                    <small><?php echo $postTime; ?></small>
                                                </div>
                                            </div>
                                        </div>
                                        <?php if ($is_logged_in && $_SESSION['user_id'] == $post['author_id']) { ?>
                                            <span class="edit">
                                                <img src="../../public/svg/three-dots.svg" alt="">
                                                <ul class="edit-menu">
                                                    <!-- <li class="edit-post-btn">
                                                        <i class="fa fa-pen"></i> Edit
                                                    </li> -->
                                                    <li class="delete-post-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                                        <i class="fa-solid fa-trash"></i> Delete
                                                    </li>
                                                </ul>
                                            </span>
                                        <?php } ?>
                                    </div>
                                    
                                    <?php if (!empty($post['title'])) { ?>
                                        <div class="feed-title">
                                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if (!empty($post['content'])) { ?>
                                        <div class="feed-content">
                                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                        </div>
                                    <?php } ?>
                                    
                                    <?php if (!empty($post['featured_image'])) { ?>
                                        <div class="feed-img">
                                            <img src="../../public/uploads/post/<?php echo $post['featured_image']; ?>" alt="">
                                        </div>
                                    <?php } ?>
                                    
                                    <div class="action-button">
                                        <div class="interaction-button">
                                            <span class="like-btn" data-post-id="<?php echo $post['post_id']; ?>">
                                                <i class="fa fa-heart <?php echo $isLiked ? 'liked' : ''; ?>"></i>
                                            </span>
                                            <span><i class="fa fa-comment-dots"></i></span>
                                            <span><i class="fa-solid fa-share"></i></span>
                                        </div>
                                        <div class="bookmark">
                                            <i class="fa fa-bookmark"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="liked-by">
                                        <p><b><span class="like-count"><?php echo $likeCount; ?></span> people</b> liked this</p>
                                    </div>
                                    
                                    <?php if (!empty($post['tags'])) { ?>
                                        <div class="tags">
                                            <?php 
                                            $tags = explode(',', $post['tags']);
                                            foreach ($tags as $tag) {
                                                echo '<span class="hashtag">#' . trim(htmlspecialchars($tag)) . '</span>';
                                            }
                                            ?>
                                        </div>
                                    <?php } ?>
                                    
                                    <div class="comments text-gry">
                                        View all comments (15)
                                    </div>
                                </div>
                                <?php
                            }
                        } else {
                            echo '<div class="no-posts"><br>No posts yet. Be the first to post!</div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <div class="main-right">
                <!-- Start Message -->
                <div class="messages">
                    <div class="wrapper2">
                        <section class="users" id="users-section">
                            <header>
                                <?php
                                    include_once(__DIR__ .'/../../config/config.php');
                                    $sql = mysqli_query($conn, "SELECT * FROM users WHERE user_id = {$_SESSION['user_id']}");
                                    if(mysqli_num_rows($sql) > 0){
                                        $row = mysqli_fetch_assoc($sql);
                                    }
                                ?>
                                <div class="content">
                                    <img src="../../public/uploads/avatar/<?php echo $row['img'] ?>" alt="">
                                    <div class="details">
                                        <span><?php echo $row['full_name'] . " " . $row['username']?></span>
                                        <p><?php echo $row['status'] ?></p>
                                    </div>
                                </div>
                                <a href="#" class="logout" onclick="confirmLogout()"> Logout</a>
                            </header>
                            <p style="color: red;
                                    font-size: 12px;
                                    font-style: italic;
                                    margin-top: 10px;
                                    margin-bottom: 5px;">
                                    *Lưu ý: Tin nhắn sẽ hết hạn sau 1 ngày
                            </p>
                            <div class="search">
                                <span class="text">Enter name to search...</span>
                                <input type="text" placeholder="Enter name to search..." id="search-input">
                                <button><i class="fas fa-search"></i></button>
                            </div>
                            <div class="users-list" id="users-list">
                                
                            </div>
                        </section>
                        
                        <section class="chat-area" id="chat-area">
                            <header>
                                <?php
                                    include_once(__DIR__ .'/../../config/config.php');
                                    $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
                                    $sql = mysqli_query($conn, "SELECT * FROM users WHERE user_id = {$user_id}");
                                    if(mysqli_num_rows($sql) > 0){
                                        $row = mysqli_fetch_assoc($sql);
                                    }
                                ?>
                                <a href="#" class="back-icon" id="back-btn"><i class="fas fa-arrow-left"></i></a>
                                <img src="../../public/uploads/avatar/<?php echo $row['img'] ?>" alt="" id="chat-avatar">
                                <div class="details">
                                    <span id="chat-name"><?php echo $row['full_name'] . " " . $row['username']?></span>
                                    <p><?php echo $row['status'] ?></p>
                                </div>
                            </header>
                            <div class="chat-box" id="chat-box">
                                
                            </div>
                            <form action="#" class="typing-area" id="message-form">
                                <input type="text" name = "outgoing_id" value = "<?php echo $_SESSION['user_id']; ?>" hidden>
                                <input type="text" name = "incoming_id" class="recipient_id" value = "<?php echo $user_id; ?>" hidden>
                                <input type="text" name = "message" class = "input-field" placeholder="Type a message here....." id="message-input">
                                <button><i class="fa-duotone fa-solid fa-paper-plane"></i></button>
                            </form>
                        </section>
                    </div>
                </div>
                <!-- end Message -->
                 
            </div>
        </div>
    </main>
    <!-- Add post popup -->
    <div class="popup add-post-popup" id="addPostPopup">
        <div class="popup-box">
            <h1><i class="fa fa-edit"></i> Add New Post</h1>
            <span class="close" id="closePostPopup">
                <i class="fa-regular fa-circle-xmark"></i>
            </span>
            
            <div class="message_popup" id="messageDiv"></div>
            
            <form id="postForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="create">
                <div class="row post-title">
                    <label for="post-title">
                        <i class="fa fa-heading"></i> Title *
                    </label>
                    <input type="text" 
                           id="post-title" 
                           name="title" 
                           placeholder="What's on your mind?"
                           required>
                </div>
                
                <div class="row post-content">
                    <label for="post-content">
                        <i class="fa fa-align-left"></i> Content *
                    </label>
                    <textarea id="post-content" 
                              name="content" 
                              placeholder="Write your post content here..."
                              required></textarea>
                </div>
                
                <div class="row post-img">
                    <label>
                        <i class="fa fa-image"></i> Featured Image
                    </label>
                    <div class="upload-area">
                        <div id="image-preview_popup" style="margin-top: 10px;"></div>
                        <img src="" id="postIMG" style="display: none;">
                        <label for="feed-pic-upload" class="feed-upload-button">
                            <span><i class="fa fa-upload"></i></span>
                            Choose Image
                        </label>
                        <input type="file" 
                               accept="image/jpg, image/png, image/jpeg" 
                               id="feed-pic-upload" 
                               name="image">
                        <p style="color: #666; font-size: 12px; margin-top: 10px;">
                            Supported formats: JPG, PNG, JPEG (Max 5MB)
                        </p>
                    </div>
                </div>
                
                <div class="row">
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="fa fa-paper-plane"></i> Add Post
                    </button>
                </div>
            </form>
            
            <div class="loading" id="loadingDiv">
                <div class="spinner"></div>
                <p>Creating post...</p>
            </div>
        </div>
    </div>

    <!-- Edit post popup -->
    <div class="popup edit-post-popup" id="editPostPopup">
        <div class="popup-box">
            <h1><i class="fa fa-edit"></i> Edit Post</h1>
            <span class="close" id="closeEditPostPopup">
                <i class="fa-regular fa-circle-xmark"></i>
            </span>
        
            <div class="message_popup" id="editMessageDiv"></div>
        
            <form id="editPostForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="post_id" id="edit-post-id">
                
                <div class="row post-title">
                    <label for="edit-post-title">
                        <i class="fa fa-heading"></i> Title *
                    </label>
                    <input type="text"
                        id="edit-post-title"
                        name="title"
                        placeholder="What's on your mind?"
                        required>
                </div>
            
                <div class="row post-content">
                    <label for="edit-post-content">
                        <i class="fa fa-align-left"></i> Content *
                    </label>
                    <textarea id="edit-post-content"
                            name="content"
                            placeholder="Write your post content here..."
                            required></textarea>
                </div>
            
                <div class="row post-img">
                    <label>
                        <i class="fa fa-image"></i> Featured Image
                    </label>
                    <div class="upload-area">
                        <div id="edit-image-preview" style="margin-top: 10px;"></div>
                        <img src="" id="editPostIMG" style="display: none;">
                        <label for="edit-feed-pic-upload" class="feed-upload-button">
                            <span><i class="fa fa-upload"></i></span>
                            Choose New Image
                        </label>
                        <input type="file"
                            accept="image/jpg, image/png, image/jpeg"
                            id="edit-feed-pic-upload"
                            name="image">
                        <p style="color: #666; font-size: 12px; margin-top: 10px;">
                            Supported formats: JPG, PNG, JPEG (Max 5MB)
                        </p>
                    </div>
                </div>
            
                <div class="row">
                    <button type="submit" class="btn btn-primary" id="editSubmitBtn">
                        <i class="fa fa-save"></i> Update Post
                    </button>
                </div>
            </form>
        
            <div class="loading" id="editLoadingDiv">
                <div class="spinner"></div>
                <p>Updating post...</p>
            </div>
        </div>
    </div>

    <!-- theme customize Popup -->
    <div class="popup theme-customize">
        <div class="popup_theme">
            <div class="popup-box theme-customize-popup-box">
                <h2>Customize Your Theme</h2>
                <p>Manege Your Font Size, Color and Background</p>

                <!-- Font size -->
                <div class="font-size">
                    <h4>Font Size</h4>
                    <div class="form_font_size">
                        <div>
                            <h6>Aa</h6>
                        </div>
                        <div class="choose-size">
                            <span class="font-size-1"></span>
                            <span class="font-size-2 active"></span>
                            <span class="font-size-3"></span>
                            <span class="font-size-4"></span>
                            <span class="font-size-5"></span>
                        </div>
                        <div>
                            <h3>Aa</h3>
                        </div>
                    </div>
                </div>
                <!-- primary color -->
                <div class="colors">
                    <h4>Color</h4>
                    <div class="choose-color">
                        <span class="color-1"></span>
                        <span class="color-2"></span>
                        <span class="color-3"></span>
                        <span class="color-4"></span>
                        <span class="color-5"></span>
                    </div>
                </div>
                <!-- Background color -->
                <div class="background">
                    <h4>Background</h4>
                    <div class="choose-bg">
                        <div class="bg1 active">
                            <span></span>
                            <h5>Light</h5>
                        </div>
                        <div class="bg2">
                            <span></span>
                            <h5>Dark</h5>
                        </div>
                    </div>
                </div>
            </div>
            <span class="close"><i class="fa-regular fa-circle-xmark"></i></span>
        </div>
    </div>
    
    <!-- Post Management Overlay -->
    <div class="post-management-overlay" id="post-management-overlay">
        <div class="post-manager-container">
            <div class="post-manager-header">
                <h3>Bài viết của bạn</h3>
                <div class="search-container">
                    <input type="text" 
                        id="post-search-input" 
                        placeholder="Tìm kiếm bài viết..." 
                        class="search-input">
                    <button type="button" id="search-btn" class="search-btn">
                        <i class="fas fa-search"></i>
                    </button>
                    <button type="button" id="clear-search" class="clear-search-btn" style="display: none;">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <button class="close-btn" id="close-post-management">&times;</button>
            </div>
        
            <div class="post-tabs">
                <button class="tab-button active" data-status="đã xuất bản">
                    Đã duyệt
                </button>
                <button class="tab-button" data-status="chờ xác nhận">
                    Chờ duyệt
                </button>
                <button class="tab-button" data-status="từ chối">
                    Từ chối
                </button>
            </div>
            
            <div class="search-info" id="search-info" style="display: none;">
                <span id="search-results-text"></span>
            </div>
        
            <div class="tab-content" id="post-manager-content">
                <div class="loading">Đang tải bài viết</div>
            </div>
        </div>
    </div>

    <!-- Liked Posts Management Overlay -->
    <div class="like_post-management-overlay" id="like_post-management-overlay">
        <div class="like_post-management-container">
            <div class="like_post-management-header">
                <h2><i class="fa fa-heart"></i> My Liked Posts</h2>
                <span class="close-overlay" id="close-liked-posts-overlay">
                    <i class="fa fa-times"></i>
                </span>
            </div>
            
            <div class="like_post-management-content">
                <!-- Loading State -->
                <div class="loading-state" id="liked-posts-loading">
                    <div class="loading-spinner">
                        <i class="fa fa-spinner fa-spin"></i>
                    </div>
                    <p>Loading your liked posts...</p>
                </div>
                
                <!-- Error State -->
                <div class="error-state" id="liked-posts-error" style="display: none;">
                    <div class="error-icon">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <p class="error-message">Failed to load liked posts</p>
                    <button class="btn-retry" onclick="loadLikedPosts()">
                        <i class="fa fa-refresh"></i> Retry
                    </button>
                </div>
                
                <!-- Empty State -->
                <div class="empty-state" id="liked-posts-empty" style="display: none;">
                    <div class="empty-icon">
                        <i class="fa fa-heart-o"></i>
                    </div>
                    <h3>No Liked Posts Yet</h3>
                    <p>Start liking posts to see them here!</p>
                </div>
                
                <!-- Stats Bar -->
                <div class="stats-bar" id="liked-posts-stats" style="display: none;">
                    <div class="stat-item">
                        <span class="stat-number" id="total-liked-count">0</span>
                        <span class="stat-label">Total Liked</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="showing-count">0</span>
                        <span class="stat-label">Showing</span>
                    </div>
                </div>
                
                <!-- Posts Grid -->
                <div class="liked-posts-grid" id="liked-posts-grid" style="display: none;">
                    <!-- Posts will be dynamically loaded here -->
                </div>
                
                <!-- Pagination -->
                <div class="pagination-container" id="liked-posts-pagination" style="display: none;">
                    <div class="pagination">
                        <button class="page-btn" id="prev-page-btn" onclick="goToPrevPage()">
                            <i class="fa fa-chevron-left"></i> Previous
                        </button>
                        <span class="page-info">
                            Page <span id="current-page">1</span> of <span id="total-pages">1</span>
                        </span>
                        <button class="page-btn" id="next-page-btn" onclick="goToNextPage()">
                            Next <i class="fa fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- link swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <!-- link JS -->
    <script>
        const currentUserId_Post = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    </script>
    <script src = "../../public/js/chat.js"></script>
    <script src="../../public/js/socila_media.js"></script>
    <script src="../../public/js/index.js"></script>
    <script src = "../../public/js/showMessageDialog.js"></script>
    
</body>

</html>