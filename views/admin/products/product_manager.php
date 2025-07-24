<?php
// Khởi tạo session an toàn
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include config và kiểm tra kết nối
include(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ .'/../../../app/models/Product.php');

// Kiểm tra biến $conn có tồn tại không
if (!isset($conn)) {
    die("Database connection not found. Please check config.php");
}

// Include các model và controller
include_once(__DIR__ .'/../../../app/models/Product.php');
include_once(__DIR__ .'/../../../app/models/Category.php');
include_once(__DIR__ .'/../../../app/models/User.php');
include_once(__DIR__ .'/../../../app/models/ProductImage.php');
include_once(__DIR__ .'/../../../app/controllers/ProductController.php');

// Thông tin session
$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$user_type = $is_logged_in ? $_SESSION['user_type'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$productModel  = new Product($conn);
// Kiểm tra quyền admin
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

$current_user = null;
$is_admin = false;

if ($is_logged_in) {
    $current_user = $userModel->getUserByID($_SESSION['user_id']);
    $is_admin = $userModel->isAdmin($current_user);
}

// Khởi tạo các class - đảm bảo $conn tồn tại
try {
    $userModel = new User($conn);
    $product = new Product($conn);
    $categoryModel = new Category($conn);
    $productImages = new ProductImage($conn);
    $productController = new ProductController($conn);
} catch (Exception $e) {
    die("Error initializing classes: " . $e->getMessage());
}

// Lấy danh mục cha và con
$parentCategories = $categoryModel->getParentCategories();
// Lấy tất cả sản phẩm
$products = $product->getAllProduct();

$action = $_GET['action'] ?? '';

// XỬ LÝ CÁC ACTION AJAX - KHÔNG CẦN RENDER HTML
if ($action === 'getChildCategories') {
    $productController->getChildCategories();
    exit;
}

if ($action === 'getParentCategoryName') {
    $productController->getParentCategoryName();
    exit;
}

if ($action === 'getProductForEdit') {
    $productController->getProductForEdit();
    exit;
}

if ($action === 'toggleProduct') {
    $productController->toggleProduct();
    exit;
}

// Chỉ bật error reporting trong development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// TIẾP TỤC RENDER HTML FORM
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home</title>
  <link rel="stylesheet" href="../../../public/css/admin/admin.css">
  <link rel="stylesheet" href="../../../fontawesome-free-6.4.2-web/css/all.min.css">
  <script type="text/javascript" src="../../../public/js/admin.js" defer></script>
  <script type="text/javascript" src="../../../public/js/showMessageDialog.js" defer></script>
    
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
            <li class="active">
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
            <li>
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

    <main class="main_admin">
        <div class="products-header">
            <h1 class="products-title">Products</h1>
            <button class="add-product-btn">+ Add Product</button>
        </div>

        <div class="filters-section">
            <input type="text" class="search-box" placeholder="Search products...">
            <div class="filter-group">
                <label>Category Parent:</label>
                <select class="filter-select Category_Parent">
                    <option>All Categories</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Category Child:</label>
                <select class="filter-select Category_Child">
                    <option>All Categories</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Status:</label>
                <select class="filter-select status_filter">
                    <option>All Status</option>
                </select>
            </div>
            <div class="filter-group">
                <button class="filter-group_btn">Search</button>
            </div>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $prod): ?>
                <?php
                    // Lấy ảnh sản phẩm chính, nếu có
                    $images = $productImages->getImagesByProduct($prod['product_id']);
                    $primaryImage = 'default-product.png'; // ảnh mặc định
                    foreach ($images as $img) {
                        if ($img['is_primary']) {
                            $primaryImage = $img['image_url'];
                            break;
                        }
                    }

                    // Trạng thái sản phẩm và lý do disable
                    $statusClass = '';
                    $statusText = '';
                    $disableInfo = null;
                    
                    if ($prod['is_active']) {
                        $statusClass = 'status-active';
                        $statusText = 'Active';
                    } else {
                        $statusClass = 'status-draft';
                        $statusText = 'Disabled';
                        // Lấy thông tin lý do disable
                        $disableInfo = $productModel->getDisableReason($prod['product_id']);
                    }

                    // Tính phần trăm tồn kho (ví dụ)
                    $stockPercent = 0;
                    $stockText = '';
                    if ($prod['stock_quantity'] > 0) {
                        $stockPercent = min(100, ($prod['stock_quantity'] / max(1, $prod['min_stock_level'])) * 100);
                        if ($stockPercent > 50) {
                            $stockClass = 'stock-high';
                            $stockText = $prod['stock_quantity'] . ' stock • High';
                        } else {
                            $stockClass = 'stock-low';
                            $stockText = $prod['stock_quantity'] . ' stock • Low';
                        }
                    } else {
                        $stockClass = '';
                        $stockText = 'Out of Stock';
                    }
                ?>
                <div class="product-card <?php echo !$prod['is_active'] ? 'disabled-product' : ''; ?>">
                    <div class="product-header">
                        <div class="product-image">
                            <img src="../../../public/uploads/product/<?php echo htmlspecialchars($primaryImage); ?>" alt="<?php echo htmlspecialchars($prod['product_name']); ?>">
                            <?php if (!$prod['is_active']): ?>
                                <div class="disabled-overlay">
                                    <span class="disabled-label">DISABLED</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($prod['product_name']); ?></h3>
                            <p class="product-sku">SKU: <?php echo htmlspecialchars($prod['product_id']); ?></p>
                            <span class="product-status <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                            
                            <?php if (!$prod['is_active'] && $disableInfo): ?>
                                <div class="disable-info">
                                    <div class="disable-reason">
                                        <strong>Reason:</strong> <?php echo htmlspecialchars($disableInfo['reason']); ?>
                                    </div>
                                    <div class="disable-details">
                                        <?php if ($disableInfo['full_name']): ?>
                                            <span class="disabled-by">By: <?php echo htmlspecialchars($disableInfo['full_name']); ?></span>
                                        <?php endif; ?>
                                        
                                        <?php if ($disableInfo['disabled_at']): ?>
                                            <span class="disabled-date">
                                                <?php echo date('M j, Y H:i', strtotime($disableInfo['disabled_at'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($disableInfo['email']): ?>
                                        <span class="disabled-by">Email: <?php echo htmlspecialchars($disableInfo['email']); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <label class="toggle-switch">
                            <input type="checkbox" 
                                data-product-id="<?php echo $prod['product_id']; ?>" 
                                <?php echo $prod['is_active'] ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="product-tags">
                        <!-- Ví dụ hiển thị category hoặc tag -->
                        <?php
                            // Lấy danh mục con
                            $category = $categoryModel->getCategoryByID($prod['category_id']);
                            $parentCategoryName = '';
                            $categoryName = '';
                            if ($category) {
                                $categoryName = $category['category_name'];
                                if (!empty($category['parent_category_id'])) {
                                    $parentCategory = $categoryModel->getCategoryByID($category['parent_category_id']);
                                    if ($parentCategory) {
                                        $parentCategoryName = $parentCategory['category_name'];
                                    }
                                }
                            }
                            // Tạo chuỗi hiển thị
                            $categoryDisplay = $parentCategoryName ? $parentCategoryName . ' > ' . $categoryName : $categoryName;
                        ?>
                        <span class="tag"><?php echo htmlspecialchars($categoryDisplay); ?></span>
                        
                        <!-- Bạn có thể thêm tag khác tùy ý -->
                    </div>
                    <div class="product-pricing">
                        <div class="price-section">
                            <div class="price-label">Retail</div>
                            <div class="price-value">$<?php echo number_format($prod['price'], 2); ?></div>
                        </div>
                        <div class="price-section">
                            <div class="price-label">Wholesale</div>
                            <div class="price-value">$<?php echo number_format($prod['price'] * 0.8, 2); ?></div>
                        </div>
                    </div>
                    <div class="product-stock">
                        <?php if ($prod['stock_quantity'] > 0): ?>
                            <div class="stock-indicator">
                                <div class="stock-fill <?php echo $stockClass; ?>" style="width: <?php echo $stockPercent; ?>%;"></div>
                            </div>
                            <span class="stock-text"><?php echo $stockText; ?></span>
                        <?php else: ?>
                            <div class="out-of-stock">Out of Stock</div>
                        <?php endif; ?>
                    </div>
                    <?php
                        $imageCount = $productImages->countImagesByProduct($prod['product_id']);
                    ?>
                    <div class="variants-info">Variants (<?php echo $imageCount; ?>)</div>
                    <div class="product-actions">
                        <div class="action-buttons">
                            <?php if ($is_admin): ?>
                                <button class="action-btn delete-product-btn" 
                                        data-product-id="<?php echo $prod['product_id']; ?>"
                                        data-product-name="<?php echo htmlspecialchars($prod['product_name']); ?>">
                                    Delete
                                </button>
                            <?php endif; ?>
                            <button class="action-btn edit-product" data-product-id="<?php echo $prod['product_id']; ?>">Edit</button>
                            <button class="action-btn warehouse-entry-product-btn" data-product-id="<?php echo $prod['product_id']; ?>">Nhập kho</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <div id="productDeleteConfirmDialog" class="product-delete-dialog" style="display: none;">
            <div class="product-delete-dialog-overlay"></div>
            <div class="product-delete-dialog-content">
                <div class="product-delete-dialog-header">
                    <h3>Confirm Delete</h3>
                    <button class="product-delete-dialog-close">&times;</button>
                </div>
                <div class="product-delete-dialog-body">
                    <p>Are you sure you want to delete the product "<span id="productDeleteName"></span>"?</p>
                    <p class="warning-text">This action cannot be undone. All associated images will also be deleted.</p>
                </div>
                <div class="product-delete-dialog-footer">
                    <button class="btn btn-cancel" id="cancelProductDelete">Cancel</button>
                    <button class="btn btn-delete" id="confirmProductDelete">Delete</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Form Nhập Kho -->
    <div id="warehouseEntryModal" class="warehouse-entry-modal" style="display: none;">
        <div class="warehouse-entry-modal-content">
            <div class="warehouse-entry-modal-header">
                <h3>Nhập Kho Sản Phẩm</h3>
                <span class="warehouse-entry-close-btn" onclick="closeWarehouseEntryModal()">&times;</span>
            </div>
            <div class="warehouse-entry-modal-body">
                <form id="warehouseEntryForm">
                    <input type="hidden" id="warehouse_entry_product_id" name="product_id">
                    
                    <div class="warehouse-entry-form-group">
                        <label for="warehouse_entry_product_name">Tên sản phẩm:</label>
                        <input type="text" id="warehouse_entry_product_name" name="product_name" readonly>
                    </div>
                    
                    <div class="warehouse-entry-form-group">
                        <label for="warehouse_entry_current_stock">Số lượng hiện tại:</label>
                        <input type="number" id="warehouse_entry_current_stock" readonly>
                    </div>
                    
                    <div class="warehouse-entry-form-group">
                        <label for="warehouse_entry_import_quantity">Số lượng nhập thêm:</label>
                        <input type="number" id="warehouse_entry_import_quantity" name="import_quantity" min="1" required>
                    </div>
                    
                    <div class="warehouse-entry-form-group">
                        <label for="warehouse_entry_new_total">Tổng số lượng mới:</label>
                        <input type="number" id="warehouse_entry_new_total" readonly>
                    </div>
                    
                    <div class="warehouse-entry-form-group">
                        <label for="warehouse_entry_note">Ghi chú (tùy chọn):</label>
                        <textarea id="warehouse_entry_note" name="note" rows="3"></textarea>
                    </div>
                    
                    <div class="warehouse-entry-form-actions">
                        <button type="button" onclick="closeWarehouseEntryModal()" class="warehouse-entry-btn-cancel">Hủy</button>
                        <button type="submit" class="warehouse-entry-btn-submit" name="skip-validation">Cập nhật kho</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add new popup -->
    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" class="form-container_add_newProduct">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error_message'] ?>
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Hidden input để lưu product_id khi edit -->
        <input type="hidden" name="product_id" id="product_id" value="">
        <input type="hidden" name="form_mode" id="form_mode" value="add"> <!-- add hoặc edit -->
        
        <div class="left-column_add_newProduct">
                <!-- Description Section -->
                <div class="section_add_newProduct">
                    <h2 class="section-title_add_newProduct">
                        <i class="fa-solid fa-arrow-left" style="margin-left: 10px; display: none;"></i> Thông tin cơ bản
                    </h2>
                    
                    <div class="form-group_add_newProduct">
                        <label class="form-label_add_newProduct" for="product_name">Tên sản phẩm/thú cưng <span style="color: red;">*</span></label>
                        <input type="text" id="product_name" name="product_name" 
                            class="form-input_add_newProduct" placeholder="Nhập tên sản phẩm hoặc thú cưng" required>
                    </div>

                    <div class="form-group_add_newProduct">
                        <label class="form-label_add_newProduct" for="business_description">Mô tả chi tiết</label>
                        <textarea id="business_description" name="business_description" 
                                class="form-textarea_add_newProduct" placeholder="Nhập mô tả chi tiết về sản phẩm hoặc thú cưng"></textarea>
                        
                        <div class="availability-list_add_newProduct">
                            <div style="font-weight: 500; margin-bottom: 10px;">Lưu ý quan trọng:</div>
                            <div class="availability-item_add_newProduct">Vui lòng cung cấp thông tin chính xác để khách hàng có thể hiểu rõ về sản phẩm</div>
                            <div class="availability-item_add_newProduct">Đối với thú cưng, hãy bao gồm thông tin về tình trạng sức khỏe và tiêm chủng</div>
                        </div>
                    </div>
                </div>

                <!-- Category Section -->
                <div class="section_add_newProduct">
                    <h2 class="section-title_add_newProduct">
                        <i class="fas fa-tags"></i> Danh mục sản phẩm
                    </h2>
                    
                    <div class="form-group_add_newProduct">
                        <label class="form-label_add_newProduct" for="category_parent">Danh mục chính <span style="color: red;">*</span></label>
                        <select id="category_parent" name="category_parent" class="form-select_add_newProduct" required>
                            <option value="">Chọn danh mục chính</option>
                            <?php if (isset($parentCategories) && !empty($parentCategories)): ?>
                                <?php foreach ($parentCategories as $category): ?>
                                    <option value="<?= $category['category_id'] ?>"><?= htmlspecialchars($category['category_name']) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="form-group_add_newProduct">
                        <label class="form-label_add_newProduct" for="category_child">Danh mục con <span style="color: red;">*</span></label>
                        <select id="category_child" name="category_child" class="form-select_add_newProduct" required>
                            <option value="">Chọn danh mục con</option>
                        </select>
                    </div>
                </div>
        </div>
        <div class="right-column_add_newProduct">
            <div class="form-group_add_newProduct" id="replace_images_section" style="display: none;">
                <label class="form-label_add_newProduct">
                    <input type="checkbox" name="replace_all_images" value="1" id="replace_all_images">
                    Thay thế tất cả ảnh hiện tại
                </label>
            </div>
            <!-- Product Image Section -->
            <div class="section_add_newProduct">
                <h2 class="section-title_add_newProduct">
                    <i class="fas fa-images"></i> Hình ảnh sản phẩm
                </h2>
                
                <div class="image-preview_add_newProduct">
                    <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjMzMzIiByeD0iOCIvPgo8cGF0aCBkPSJNMzAgNDBMMzUgMzVMMzUgNjVMMzAgNjBWNDBaIiBmaWxsPSIjNjY2Ii8+CjxwYXRoIGQ9Ik03MCA0MEw2NSAzNUw2NSA2NUw3MCA2MFY0MFoiIGZpbGw9IiM2NjYiLz4KPHBhdGggZD0iTTQwIDMwTDQ1IDI1TDU1IDI1TDYwIDMwVjcwTDU1IDc1TDQ1IDc1TDQwIDcwVjMwWiIgZmlsbD0iIzY2NiIvPgo8L3N2Zz4K" alt="Preview Image">
                </div>

                <div class="upload-area_add_newProduct" >
                    <div class="upload-icon_add_newProduct">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text_add_newProduct">
                        Kéo thả hình ảnh vào đây hoặc 
                        <span class="upload-link_add_newProduct">click để chọn file</span>
                    </div>
                    <input type="file" name="product_images[]" multiple accept="image/*" style="display:none;" id="product_images_input">
                </div>

                <div class="image-grid_add_newProduct">
                    <div class="image-slot_add_newProduct"><span>+</span></div>
                    <div class="image-slot_add_newProduct"><span>+</span></div>
                    <div class="image-slot_add_newProduct"><span>+</span></div>
                    <div class="image-slot_add_newProduct"><span>+</span></div>
                    <div class="image-slot_add_newProduct"><span>+</span></div>
                </div>
            </div>

            <!-- Pet-specific fields -->
            <div class="pet-fields">
                <div class="section_add_newProduct" id = "gender_section">
                    <h2 class="section-title_add_newProduct">
                        <i class="fas fa-venus-mars"></i> Giới tính 
                        <span style="font-size: 12px; font-weight: 300;">(Ảnh phụ đếm từ trái sang phải)</span>
                    </h2>
                    <div class="gender-grid_add_newProduct">
                        <div class="form-group_add_newProduct">
                            <p>Ảnh chính</p>
                            <select name="main_gender" class="form-select_add_newProduct">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 1</p>
                            <select name="image_gender_1" class="form-select_add_newProduct">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 2</p>
                            <select name="image_gender_2" class="form-select_add_newProduct">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 3</p>
                            <select name="image_gender_3" class="form-select_add_newProduct">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 4</p>
                            <select name="image_gender_4" class="form-select_add_newProduct">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section_add_newProduct" id = "age_section">
                    <h2 class="section-title_add_newProduct">
                        <i class="fas fa-birthday-cake"></i> Tuổi 
                        <span style="font-size: 12px; font-weight: 300;">(Ảnh phụ đếm từ trái sang phải)</span>
                    </h2>
                    <div class="age-grid_add_newProduct">
                        <div class="form-group_add_newProduct">
                            <p>Ảnh chính</p>
                            <input type="text" name="main_age" class="age_pet" placeholder="VD: 2 tháng">
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 1</p>
                            <input type="text" name="image_age_1" class="age_pet" placeholder="VD: 2 tháng">
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 2</p>
                            <input type="text" name="image_age_2" class="age_pet" placeholder="VD: 2 tháng">
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 3</p>
                            <input type="text" name="image_age_3" class="age_pet" placeholder="VD: 2 tháng">
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 4</p>
                            <input type="text" name="image_age_4" class="age_pet" placeholder="VD: 2 tháng">
                        </div>
                    </div>
                </div>

                <div class="section_add_newProduct" id = "color_section">
                    <h2 class="section-title_add_newProduct">
                        <i class="fas fa-palette"></i> Màu sắc 
                        <span style="font-size: 12px; font-weight: 300;">(Ảnh phụ đếm từ trái sang phải)</span>
                    </h2>
                    <div class="color-grid_add_newProduct">
                        <div class="form-group_add_newProduct">
                            <p>Ảnh chính</p>
                            <input type="text" name="main_color" class="age_pet" placeholder="VD: Vàng đen">
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 1</p>
                            <input type="text" name="image_color_1" class="age_pet" placeholder="VD: Vàng đen">
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 2</p>
                            <input type="text" name="image_color_2" class="age_pet" placeholder="VD: Vàng đen">
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 3</p>
                            <input type="text" name="image_color_3" class="age_pet" placeholder="VD: Vàng đen">
                        </div>
                        <div class="form-group_add_newProduct">
                            <p>Ảnh phụ 4</p>
                            <input type="text" name="image_color_4" class="age_pet" placeholder="VD: Vàng đen">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product-specific fields -->
            <div class="product-fields" id = "product-fields" style = "display: block;">
                <div class="section_add_newProduct">
                    <div class="form-group_add_newProduct">
                        <label class="form-label_add_newProduct" for="product_material">
                            <i class="fas fa-cube"></i> Chất liệu
                        </label>
                        <input type="text" id="product_material" name="product_material" 
                                class="form-input_add_newProduct" placeholder="VD: Da thật, Cotton, Polyester">
                    </div>
                </div>

                <!-- Select Size Section -->
                <div class="section_add_newProduct">
                    <h2 class="section-title_add_newProduct">
                        <i class="fas fa-ruler"></i> Chọn kích thước
                    </h2>
                    <div class="size-grid_add_newProduct">
                        <div class="size-option_add_newProduct" data-size="XS">
                            XS
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_add_newProduct" data-size="S">
                            S
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_add_newProduct" data-size="M">
                            M
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_add_newProduct" data-size="L">
                            L
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_add_newProduct" data-size="XL">
                            XL
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_add_newProduct" data-size="XXL">
                            XXL
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="selected_sizes" id="selected_sizes">
                    <div class="help-text_add_newProduct">
                        <i class="fas fa-info-circle"></i> Vui lòng chọn các kích thước có sẵn cho sản phẩm
                    </div>
                    </div>
                </div>
                <!-- Price Section -->
                <div class="section_add_newProduct">
                    <div class="form-group_add_newProduct">
                        <label class="form-label_add_newProduct" for="product_price">
                            <i class="fas fa-tag"></i> Giá bán <span style="color: red;">*</span>
                        </label>
                        <input type="number" id="product_price" name="product_price" 
                            class="form-input_add_newProduct" placeholder="Nhập giá bán (VNĐ)" min="0" step="any" required>
                    </div>
                </div>
                <div class="section_add_newProduct">
                    <div class="form-group_add_newProduct" id="weight_section">
                        <label class="form-label_add_newProduct" for="product_weight">
                            <i class="fa-solid fa-scale-balanced"></i> Cân nặng (kg) <span style="color: red;">*</span>
                        </label>
                        <input type="number" id="product_weight" name="product_weight" 
                            class="form-input_add_newProduct" placeholder="Nhập cân nặng (VD: 1 hoặc 1.5)" 
                            min="0" step="any" required>
                    </div>
                </div>
            </div>
            </div>
        </div>
        <!-- Form Actions -->
         <div class="form-actions_add_newProduct">
            <button type="reset" class="btn_add_newProduct btn-danger_add_newProduct">
                <i class="fas fa-times"></i> Hủy
            </button>
            <button type="submit" name="add_product" class="btn_add_newProduct btn-primary_add_newProduct">
                <i class="fas fa-plus"></i> <span id="submit_text">Thêm sản phẩm</span>
            </button>
        </div>
        <input type="hidden" name="primary_image_name" id="primary_image_name">
    </form>

    <!-- Edit Product Form -->
    <form id="editProductForm" method="post" enctype="multipart/form-data" class="form-container_edit_product">
        <!-- Success/Error Messages -->
        <div id="alert-container" style="grid-column: 1 / -1;"></div>

        <!-- Hidden inputs -->
        <input type="hidden" name="product_id" id="edit_product_id" value="">
        <input type="hidden" name="form_mode" id="edit_form_mode" value="edit">
        
        <div class="left-column_edit_product">
            <!-- Basic Information Section -->
            <div class="section_edit_product">
                <h2 class="section-title_edit_product">
                    <i class="fas fa-info-circle"></i> Thông tin cơ bản
                </h2>
                
                <div class="form-group_edit_product">
                    <label class="form-label_edit_product" for="edit_product_name">
                        Tên sản phẩm/thú cưng <span style="color: red;">*</span>
                    </label>
                    <input type="text" id="edit_product_name" name="product_name" 
                        class="form-input_edit_product" placeholder="Nhập tên sản phẩm hoặc thú cưng" required>
                </div>

                <div class="form-group_edit_product">
                    <label class="form-label_edit_product" for="edit_business_description">Mô tả chi tiết</label>
                    <textarea id="edit_business_description" name="business_description" 
                            class="form-textarea_edit_product" placeholder="Nhập mô tả chi tiết về sản phẩm hoặc thú cưng"></textarea>
                    
                    <div class="availability-list_edit_product">
                        <div style="font-weight: 500; margin-bottom: 10px;">Lưu ý quan trọng:</div>
                        <div class="availability-item_edit_product">Vui lòng cung cấp thông tin chính xác để khách hàng có thể hiểu rõ về sản phẩm</div>
                        <div class="availability-item_edit_product">Đối với thú cưng, hãy bao gồm thông tin về tình trạng sức khỏe và tiêm chủng</div>
                    </div>
                </div>
            </div>

            <!-- Category Section -->
            <div class="section_edit_product">
                <h2 class="section-title_edit_product">
                    <i class="fas fa-tags"></i> Danh mục sản phẩm
                </h2>
                
                <div class="form-group_edit_product">
                    <label class="form-label_edit_product" for="edit_category_parent">
                        Danh mục chính <span style="color: red;">*</span>
                    </label>
                    <select id="edit_category_parent" name="category_parent" class="form-select_edit_product" required>
                        <option value="">Chọn danh mục chính</option>
                    </select>
                </div>

                <div class="form-group_edit_product">
                    <label class="form-label_edit_product" for="edit_category_child">
                        Danh mục con <span style="color: red;">*</span>
                    </label>
                    <select id="edit_category_child" name="category_child" class="form-select_edit_product" required>
                        <option value="">Chọn danh mục con</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="right-column_edit_product">
            <!-- Replace Images Option -->
            <div class="form-group_edit_product replace-images-section" id="replace_images_section">
                <label class="form-label_edit_product">
                    <input type="checkbox" name="replace_all_images" value="1" id="replace_all_images">
                    Thay thế tất cả ảnh hiện tại
                </label>
                <div class="help-text_edit_product">
                    <i class="fas fa-info-circle"></i> 
                    Chọn tùy chọn này để thay thế tất cả ảnh hiện tại bằng ảnh mới
                </div>
            </div>

            <!-- Product Image Section -->
            <div class="section_edit_product">
                <h2 class="section-title_edit_product">
                    <i class="fas fa-images"></i> Hình ảnh sản phẩm
                </h2>
                
                <div class="image-preview_edit_product">
                    <img id="preview_image" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTAwIiBoZWlnaHQ9IjEwMCIgdmlld0JveD0iMCAwIDEwMCAxMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIxMDAiIGhlaWdodD0iMTAwIiBmaWxsPSIjMzMzIiByeD0iOCIvPgo8cGF0aCBkPSJNMzAgNDBMMzUgMzVMMzUgNjVMMzAgNjBWNDBaIiBmaWxsPSIjNjY2Ii8+CjxwYXRoIGQ9Ik03MCA0MEw2NSAzNUw2NSA2NUw3MCA2MFY0MFoiIGZpbGw9IiM2NjYiLz4KPHBhdGggZD0iTTQwIDMwTDQ1IDI1TDU1IDI1TDYwIDMwVjcwTDU1IDc1TDQ1IDc1TDQwIDcwVjMwWiIgZmlsbD0iIzY2NiIvPgo8L3N2Zz4K" alt="Preview Image">
                </div>

                <div class="upload-area_edit_product" id="upload_area">
                    <div class="upload-icon_edit_product">
                        <i class="fas fa-cloud-upload-alt"></i>
                    </div>
                    <div class="upload-text_edit_product">
                        Kéo thả hình ảnh vào đây hoặc 
                        <span class="upload-link_edit_product">click để chọn file</span>
                    </div>
                    <input type="file" name="product_images[]" multiple accept="image/*" style="display:none;" id="edit_product_images_input">
                </div>

                <div class="image-grid_edit_product" id="image_grid">
                    <div class="image-slot_edit_product" data-slot="0"><span>+</span></div>
                    <div class="image-slot_edit_product" data-slot="1"><span>+</span></div>
                    <div class="image-slot_edit_product" data-slot="2"><span>+</span></div>
                    <div class="image-slot_edit_product" data-slot="3"><span>+</span></div>
                    <div class="image-slot_edit_product" data-slot="4"><span>+</span></div>
                </div>
            </div>

            <!-- Pet-specific fields -->
            <div class="pet-fields" id="pet_fields">
                <div class="section_edit_product">
                    <h2 class="section-title_edit_product">
                        <i class="fas fa-venus-mars"></i> Giới tính 
                        <span style="font-size: 12px; font-weight: 300;">(Ảnh phụ đếm từ trái sang phải)</span>
                    </h2>
                    <div class="gender-grid_edit_product">
                        <div class="form-group_edit_product">
                            <p>Ảnh chính</p>
                            <select name="main_gender" id="edit_main_gender" class="form-select_edit_product">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 1</p>
                            <select name="image_gender_1" id="edit_image_gender_1" class="form-select_edit_product">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 2</p>
                            <select name="image_gender_2" id="edit_image_gender_2" class="form-select_edit_product">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 3</p>
                            <select name="image_gender_3" id="edit_image_gender_3" class="form-select_edit_product">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 4</p>
                            <select name="image_gender_4" id="edit_image_gender_4" class="form-select_edit_product">
                                <option value="">Chọn giới tính</option>
                                <option value="male">Đực</option>
                                <option value="female">Cái</option>
                                <option value="unknown">Chưa biết</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="section_edit_product">
                    <h2 class="section-title_edit_product">
                        <i class="fas fa-birthday-cake"></i> Tuổi 
                        <span style="font-size: 12px; font-weight: 300;">(Ảnh phụ đếm từ trái sang phải)</span>
                    </h2>
                    <div class="age-grid_edit_product">
                        <div class="form-group_edit_product">
                            <p>Ảnh chính</p>
                            <input type="text" name="main_age" id="edit_main_age" class="form-input_edit_product" placeholder="VD: 2 tháng">
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 1</p>
                            <input type="text" name="image_age_1" id="edit_image_age_1" class="form-input_edit_product" placeholder="VD: 2 tháng">
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 2</p>
                            <input type="text" name="image_age_2" id="edit_image_age_2" class="form-input_edit_product" placeholder="VD: 2 tháng">
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 3</p>
                            <input type="text" name="image_age_3" id="edit_image_age_3" class="form-input_edit_product" placeholder="VD: 2 tháng">
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 4</p>
                            <input type="text" name="image_age_4" id="edit_image_age_4" class="form-input_edit_product" placeholder="VD: 2 tháng">
                        </div>
                    </div>
                </div>

                <div class="section_edit_product">
                    <h2 class="section-title_edit_product">
                        <i class="fas fa-palette"></i> Màu sắc 
                        <span style="font-size: 12px; font-weight: 300;">(Ảnh phụ đếm từ trái sang phải)</span>
                    </h2>
                    <div class="color-grid_edit_product">
                        <div class="form-group_edit_product">
                            <p>Ảnh chính</p>
                            <input type="text" name="main_color" id="edit_main_color" class="form-input_edit_product" placeholder="VD: Vàng đen">
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 1</p>
                            <input type="text" name="image_color_1" id="edit_image_color_1" class="form-input_edit_product" placeholder="VD: Vàng đen">
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 2</p>
                            <input type="text" name="image_color_2" id="edit_image_color_2" class="form-input_edit_product" placeholder="VD: Vàng đen">
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 3</p>
                            <input type="text" name="image_color_3" id="edit_image_color_3" class="form-input_edit_product" placeholder="VD: Vàng đen">
                        </div>
                        <div class="form-group_edit_product">
                            <p>Ảnh phụ 4</p>
                            <input type="text" name="image_color_4" id="edit_image_color_4" class="form-input_edit_product" placeholder="VD: Vàng đen">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Product-specific fields -->
            <div class="product-fields" id="product_fields">
                <div class="section_edit_product">
                    <div class="form-group_edit_product">
                        <label class="form-label_edit_product" for="edit_product_material">
                            <i class="fas fa-cube"></i> Chất liệu
                        </label>
                        <input type="text" id="edit_product_material" name="product_material" 
                                class="form-input_edit_product" placeholder="VD: Da thật, Cotton, Polyester">
                    </div>
                </div>

                <!-- Size Selection -->
                <div class="section_edit_product">
                    <h2 class="section-title_edit_product">
                        <i class="fas fa-ruler"></i> Chọn kích thước
                    </h2>
                    <div class="size-grid_edit_product">
                        <div class="size-option_edit_product" data-size="XS">
                            XS
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_edit_product" data-size="S">
                            S
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_edit_product" data-size="M">
                            M
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_edit_product" data-size="L">
                            L
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_edit_product" data-size="XL">
                            XL
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                        <div class="size-option_edit_product" data-size="XXL">
                            XXL
                            <div class="checkmark">
                                <i class="fas fa-check"></i>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="selected_sizes" id="edit_selected_sizes">
                    <div class="help-text_edit_product">
                        <i class="fas fa-info-circle"></i> Vui lòng chọn các kích thước có sẵn cho sản phẩm
                    </div>
                </div>

                <!-- Price Section -->
                <div class="section_edit_product">
                    <div class="form-group_edit_product">
                        <label class="form-label_edit_product" for="edit_product_price">
                            <i class="fas fa-tag"></i> Giá bán <span style="color: red;">*</span>
                        </label>
                        <input type="number" id="edit_product_price" name="product_price" 
                            class="form-input_edit_product" placeholder="Nhập giá bán (VNĐ)" min="0" step="any" required>
                    </div>
                </div>

                <!-- Weight Section -->
                <div class="section_edit_product">
                    <div class="form-group_edit_product" id="edit_weight_section">
                        <label class="form-label_edit_product" for="edit_product_weight">
                            <i class="fa-solid fa-scale-balanced"></i> Cân nặng (kg) <span style="color: red;">*</span>
                        </label>
                        <input type="number" id="edit_product_weight" name="product_weight" 
                            class="form-input_edit_product" placeholder="Nhập cân nặng (VD: 1 hoặc 1.5)" 
                            min="0" step="any" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions_edit_product">
            <button type="button" class="btn_edit_product btn-danger_edit_product" onclick="closeEditForm()">
                <i class="fas fa-times"></i> Hủy
            </button>
            <button type="submit" name="update_product" class="btn_edit_product btn-primary_edit_product">
                <i class="fas fa-save"></i> Cập nhật sản phẩm
            </button>
        </div>
        <input type="hidden" name="primary_image_name" id="edit_primary_image_name">
    </form>

    <!-- Modal HTML - Thêm vào cuối file HTML -->
    <div id="disableProductModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Disable Product</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <p>Please provide a reason for disabling this product:</p>
                <textarea id="disableReason" placeholder="Enter reason..." rows="4" style="width: 100%; margin: 10px 0;"></textarea>
                <div class="modal-buttons">
                    <button type="button" id="confirmDisable" class="btn btn-danger">Confirm Disable</button>
                    <button type="button" id="cancelDisable" class="btn btn-secondary">Cancel</button>
                </div>
            </div>
        </div>
    </div>
        
    <script src = "../../../public/js/product_manager.js"></script>
    <script src="../../../public/js/showMessageDialog.js"></script>
</body>
</html>