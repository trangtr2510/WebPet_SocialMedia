<?php

include(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');
require_once(__DIR__ . '/../models/User.php');
require_once(__DIR__ . '/../models/ProductImage.php');
require_once(__DIR__ . '/../models/OrderItem.php');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class ProductController {
    private $product;
    private $category;
    private $productImage;
    private $conn;
    private $user;
    
    public function __construct($db) {
        $this->conn = $db;
        $this->product = new Product($db);
        $this->user = new User($db);
        $this->category = new Category($db);
        $this->productImage = new ProductImage($db);
        $this->orderItemModel = new OrderItem($db);
    }

    // Method xử lý toggle sản phẩm
    public function toggleProduct() {
        header('Content-Type: application/json');
       
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Kiểm tra xem user đã đăng nhập chưa
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'User not logged in']);
            return;
        }

        // Lấy thông tin user từ session
        $userId = $_SESSION['user_id'];
        $userType = $_SESSION['user_type'] ?? null;
        
        // Tạo mảng user để kiểm tra quyền
        $currentUser = [
            'user_id' => $userId,
            'user_type' => $userType
        ];
       
        $input = json_decode(file_get_contents('php://input'), true);
        $productId = $input['product_id'] ?? null;
        $isActive = $input['is_active'] ?? null;
        $reason = $input['reason'] ?? null;
       
        if (!$productId || $isActive === null) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            return;
        }
       
        try {
            if ($isActive == 0) {
                // Disable sản phẩm - cả admin và employee đều có thể tắt
                if (!$this->user->isAdmin($currentUser) && !$this->user->isEmployee($currentUser)) {
                    echo json_encode(['success' => false, 'message' => 'You do not have permission to disable products']);
                    return;
                }
                
                if (!$reason) {
                    echo json_encode(['success' => false, 'message' => 'Reason is required when disabling product']);
                    return;
                }
               
                $result = $this->product->disableProduct($productId, $reason, $userId);
            } else {
                // Enable sản phẩm - chỉ admin mới có thể bật
                if (!$this->user->isAdmin($currentUser)) {
                    echo json_encode(['success' => false, 'message' => 'Only admin can enable products']);
                    return;
                }
                
                $result = $this->product->enableProduct($productId);
            }
           
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update product']);
            }
           
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    // Hiển thị form thêm sản phẩm
    public function showAddForm() {
        $categories = $this->category->getAllCategories();
        $parentCategories = $this->category->getParentCategories();
    
        require_once(__DIR__ . '/../../views/admin/products/product_manager.php');
    }
    
    // Lấy danh mục con theo danh mục cha (AJAX)
    public function getChildCategories() {
        if (isset($_GET['parent_id'])) {
            $parent_id = $_GET['parent_id'];
            $childCategories = $this->category->getChildCategories($parent_id);
            
            header('Content-Type: application/json');
            echo json_encode($childCategories);
        }
    }
    
    // Lấy tên danh mục cha (AJAX)
    public function getParentCategoryName() {
        if (isset($_GET['category_id'])) {
            $category_id = $_GET['category_id'];
            $category = $this->category->getCategoryByID($category_id);
            
            header('Content-Type: application/json');
            
            if ($category && $category['parent_category_id']) {
                $parentCategory = $this->category->getCategoryByID($category['parent_category_id']);
                echo json_encode(['parent_name' => $parentCategory['category_name']]);
            } 
            else if ($category) {
                echo json_encode(['parent_name' => $category['category_name']]);
            } 
            else {
                echo json_encode(['parent_name' => null]);
            }
        }
    }

    public function getParentCategoryListAjax() {
        $categories = $this->category->getParentCategories();
        header('Content-Type: application/json');
        echo json_encode($categories);
    }

    public function addProduct() {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
                  strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        try {
            // Remove the strict POST check or make it more flexible
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception("Phương thức yêu cầu không hợp lệ");
            }

            // Check if it's an add product request
            if (!isset($_POST['add_product']) && !isset($_POST['form_mode'])) {
                throw new Exception("Không tìm thấy dữ liệu form");
            }

            if (!$this->conn) {
                throw new Exception("Kết nối database thất bại");
            }

            $this->conn->autocommit(false);

            $requiredFields = ['product_name', 'category_child', 'product_price', 'product_weight'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Thiếu thông tin bắt buộc: $field");
                }
            }

            if (empty($_FILES['product_images']['name'][0])) {
                throw new Exception("Vui lòng chọn ít nhất một ảnh");
            }

            $category_id = $_POST['category_child'];
            $categoryInfo = $this->category->getCategoryByID($category_id);
            if (!$categoryInfo) {
                throw new Exception("Danh mục không tồn tại");
            }

            $parentCategory = $categoryInfo['parent_category_id'] ?
                $this->category->getCategoryByID($categoryInfo['parent_category_id']) : null;
            $isPet = $parentCategory && strtolower($parentCategory['category_name']) === 'pet';

            $selectedSizes = '';
            if (!$isPet && !empty($_POST['selected_sizes'])) {
                $selectedSizes = $_POST['selected_sizes'];
            }

            $productData = [
                'product_name' => trim($_POST['product_name']),
                'description' => trim($_POST['business_description'] ?? ''),
                'category_id' => $category_id,
                'price' => floatval($_POST['product_price']),
                'stock_quantity' => 0,
                'min_stock_level' => 0,
                'weight' => floatval($_POST['product_weight'] ?? 0),
                'is_active' => 1,
                'created_by' => $_SESSION['user_id'] ?? 1,
                'age' => $isPet ? trim($_POST['main_age'] ?? '') : '',
                'material' => !$isPet ? trim($_POST['product_material'] ?? '') : null,
                'size' => $selectedSizes ?? ''
            ];

            $product_id = $this->product->createProduct($productData);
            if (!$product_id) {
                throw new Exception("Không thể tạo sản phẩm");
            }

            $this->handleImageUpload($product_id, $isPet);
            $this->conn->commit();

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Thêm sản phẩm thành công!',
                    'redirect' => 'product_manager.php'
                ]);
                exit;
            } else {
                $_SESSION['success_message'] = 'Thêm sản phẩm thành công!';
                header('Location: product_manager.php');
                exit;
            }

        } catch (Exception $e) {
            if ($this->conn) {
                $this->conn->rollback();
            }

            error_log("AddProduct Error: " . $e->getMessage());
            error_log("POST data: " . print_r($_POST, true));
            error_log("FILES data: " . print_r($_FILES, true));

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            } else {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: product_manager.php');
                exit;
            }
        }
    }

    // Cải tiến method getProductForEdit để đổ đầy đủ dữ liệu gender
    public function getProductForEdit() {
        if (!isset($_GET['product_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Thiếu product_id']);
            return;
        }
        
        $product_id = $_GET['product_id'];
        
        try {
            // Lấy thông tin sản phẩm với thông tin danh mục
            $product = $this->product->getProductWithCategoryInfo($product_id);
            
            if (!$product) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
                return;
            }
            
            // Lấy danh sách ảnh của sản phẩm, sắp xếp theo display_order
            $images = $this->productImage->getImagesByProductId($product_id);
            
            // Cải tiến: Đảm bảo tất cả ảnh đều có đầy đủ thông tin
            foreach ($images as &$image) {
                // Đảm bảo có original_name
                if (!isset($image['original_name']) || empty($image['original_name'])) {
                    if (isset($image['alt_text']) && !empty($image['alt_text'])) {
                        $image['original_name'] = $image['alt_text'];
                    } else {
                        $image['original_name'] = $image['image_url'];
                    }
                }
                
                // Đảm bảo các trường pet-specific tồn tại
                if (!isset($image['gender'])) $image['gender'] = '';
                if (!isset($image['color'])) $image['color'] = '';
                if (!isset($image['age'])) $image['age'] = '';
                
                // Đảm bảo có image_id
                if (!isset($image['image_id'])) $image['image_id'] = 0;
                
                // Đảm bảo có display_order
                if (!isset($image['display_order'])) $image['display_order'] = 1;
                
                // Đảm bảo có is_primary
                if (!isset($image['is_primary'])) $image['is_primary'] = 0;
            }
            unset($image); // Phá vỡ reference
            
            // Lấy thông tin danh mục hiện tại
            $category = $this->category->getCategoryByID($product['category_id']);
            if (!$category) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Danh mục không tồn tại']);
                return;
            }
            
            // Lấy danh sách tất cả danh mục cha để đổ vào select
            $parentCategories = $this->category->getParentCategories();
            
            // Xử lý thông tin danh mục cha/con
            $parentCategory = null;
            $selectedParentId = null;
            $selectedChildId = null;
            
            if ($category['parent_category_id']) {
                // Đây là danh mục con
                $parentCategory = $this->category->getCategoryByID($category['parent_category_id']);
                $selectedParentId = $category['parent_category_id'];
                $selectedChildId = $category['category_id'];
            } else {
                // Đây là danh mục gốc
                $selectedParentId = $category['category_id'];
                $selectedChildId = null;
            }
            
            // Lấy danh sách danh mục con
            $childCategories = [];
            if ($selectedParentId) {
                $childCategories = $this->category->getChildCategories($selectedParentId);
            }
            
            // Xác định xem có phải thú cưng không
            $isPet = false;
            if ($parentCategory) {
                $isPet = strtolower($parentCategory['category_name']) === 'pet';
            } else {
                $isPet = strtolower($category['category_name']) === 'pet';
            }
            
            // Lấy tên ảnh chính
            $primaryImageName = '';
            foreach ($images as $image) {
                if ($image['is_primary']) {
                    $primaryImageName = $image['original_name'];
                    break;
                }
            }
            
            // Chuẩn bị dữ liệu sizes cho sản phẩm
            $selectedSizes = [];
            if (!$isPet && !empty($product['size'])) {
                $selectedSizes = array_map('trim', explode(',', $product['size']));
            }
            
            // Cải tiến: Tạo cấu trúc dữ liệu pet-specific cho frontend
            $petData = null;
            if ($isPet) {
                $petData = $this->organizePetData($images);
            }
            
            // Chuẩn bị response data
            $response = [
                'success' => true,
                'product' => [
                    'product_id' => $product['product_id'],
                    'product_name' => $product['product_name'],
                    'description' => $product['description'] ?? '',
                    'price' => $product['price'],
                    'weight' => $product['weight'] ?? 0,
                    'age' => $product['age'] ?? '',
                    'material' => $product['material'] ?? '',
                    'size' => $product['size'] ?? '',
                    'category_id' => $product['category_id']
                ],
                'images' => $images,
                'category' => $category,
                'parentCategory' => $parentCategory,
                'parentCategories' => $parentCategories,
                'childCategories' => $childCategories,
                'selectedParentId' => $selectedParentId,
                'selectedChildId' => $selectedChildId,
                'isPet' => $isPet,
                'primaryImageName' => $primaryImageName,
                'selectedSizes' => $selectedSizes,
                'petData' => $petData
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("GetProductForEdit Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }

    // Method mới để tổ chức dữ liệu pet
    private function organizePetData($images) {
        $petData = [
            'main' => ['gender' => '', 'color' => '', 'age' => ''],
            'auxiliaries' => []
        ];
        
        foreach ($images as $image) {
            if ($image['is_primary']) {
                $petData['main'] = [
                    'gender' => $image['gender'] ?? '',
                    'color' => $image['color'] ?? '',
                    'age' => $image['age'] ?? ''
                ];
            } else {
                $petData['auxiliaries'][] = [
                    'display_order' => $image['display_order'],
                    'gender' => $image['gender'] ?? '',
                    'color' => $image['color'] ?? '',
                    'age' => $image['age'] ?? '',
                    'image_id' => $image['image_id']
                ];
            }
        }
        
        return $petData;
    }

    // Cải tiến method updateExistingImages
    private function updateExistingImages($product_id, $isPet) {
        // Lấy danh sách ảnh hiện tại
        $existingImages = $this->productImage->getImagesByProductId($product_id);

        // Đếm ảnh phụ (không phải ảnh chính) để ánh xạ đúng với form
        $subImageIndex = 1;

        foreach ($existingImages as $image) {
            $image_id = $image['image_id'];
            $is_primary = $image['is_primary'];

            $gender = $color = null;

            if ($isPet) {
                if ($is_primary) {
                    // Ảnh chính
                    $gender = $_POST['main_gender'] ?? null;
                    $color = $_POST['main_color'] ?? null;
                } else {
                    // Ảnh phụ — dùng chỉ số tăng dần (không dùng display_order)
                    $gender = $_POST['image_gender_' . $subImageIndex] ?? null;
                    $color = $_POST['image_color_' . $subImageIndex] ?? null;
                    $subImageIndex++;
                }
            }

            // Cập nhật thông tin ảnh
            $result = $this->productImage->updateImageInfo($image_id, $gender, $color);
            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công']);
            if (!$result) {
                error_log("Failed to update image info for image_id: $image_id");
                echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật']);
            }
        }
    }

    // Cải tiến method handleImageUpload
    private function handleImageUpload($product_id, $isPet, $replaceAll = true) {
        if (!isset($_FILES['product_images']) || $_FILES['product_images']['error'][0] === UPLOAD_ERR_NO_FILE) {
            return;
        }

        $upload_dir = __DIR__ . '/../../public/uploads/product/';
        if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
            throw new Exception('Không thể tạo thư mục upload');
        }

        $allowed_mime_types = [
            'image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/gif',
            'image/bmp', 'image/svg+xml', 'image/x-icon', 'image/heic', 'image/avif'
        ];
        $max_size = 5 * 1024 * 1024;

        if (!is_array($_FILES['product_images']['name'])) {
            throw new Exception('Dữ liệu upload không hợp lệ');
        }

        $imageCount = count($_FILES['product_images']['name']);
        $primary_image_original_name = $_POST['primary_image_name'] ?? '';
        
        // Tính toán display_order bắt đầu
        $startDisplayOrder = 1;
        if (!$replaceAll) {
            $maxOrder = $this->productImage->getMaxDisplayOrder($product_id);
            $startDisplayOrder = $maxOrder + 1;
        }

        for ($i = 0; $i < $imageCount; $i++) {
            if ($_FILES['product_images']['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = $_FILES['product_images']['name'][$i];
            $file_tmp = $_FILES['product_images']['tmp_name'][$i];
            $file_size = $_FILES['product_images']['size'][$i];

            if (empty($originalName) || !is_uploaded_file($file_tmp)) {
                continue;
            }

            $mime_type = mime_content_type($file_tmp);
            if (!in_array($mime_type, $allowed_mime_types)) {
                throw new Exception("File $originalName không phải là ảnh hợp lệ");
            }

            if ($file_size > $max_size) {
                throw new Exception("Ảnh $originalName vượt quá dung lượng cho phép 5MB");
            }

            // Tạo tên file an toàn
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $filename = $safeName . '_' . time() . '_' . $i . '.' . $extension;
            $filepath = $upload_dir . $filename;

            if (!move_uploaded_file($file_tmp, $filepath)) {
                throw new Exception("Không thể lưu ảnh $originalName");
            }

            // Xác định is_primary và display_order
            $is_primary = ($originalName === $primary_image_original_name) ? 1 : 0;
            $display_order = $startDisplayOrder + $i;

            // Nếu là ảnh chính và đang thay thế tất cả
            if ($replaceAll && $is_primary) {
                $display_order = 1;
            }

            // Lấy dữ liệu pet-specific
            $gender = $color = $age = null;
            if ($isPet) {
                if ($is_primary) {
                    $gender = $_POST['main_gender'] ?? null;
                    $color = $_POST['main_color'] ?? null;
                    $age = $_POST['main_age'] ?? null;
                } else {
                    $gender = $_POST['image_gender_' . $display_order] ?? null;
                    $color = $_POST['image_color_' . $display_order] ?? null;
                    $age = $_POST['image_age_' . $display_order] ?? null;
                }
            }

            // Lưu ảnh vào database
            $imageResult = $this->productImage->addImage(
                $product_id,
                $filename,
                $originalName,
                $is_primary,
                $display_order,
                $gender,
                $color,
                $age
            );

            if (!$imageResult) {
                error_log("ERROR saving image to database: " . print_r($this->conn->errorInfo(), true));
                throw new Exception("Không thể lưu ảnh vào DB: " . $originalName);
            }
        }
    }

    // Method mới để xóa ảnh
    public function deleteImage() {
        if (!isset($_POST['image_id'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Thiếu image_id']);
            return;
        }
        
        $image_id = $_POST['image_id'];
        
        try {
            // Lấy thông tin ảnh trước khi xóa
            $image = $this->productImage->getImageById($image_id);
            
            if (!$image) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Ảnh không tồn tại']);
                return;
            }
            
            // Xóa file vật lý
            $filepath = __DIR__ . '/../../public/uploads/product/' . $image['image_url'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Xóa record trong database
            $result = $this->productImage->deleteImage($image_id);
            
            if ($result) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Xóa ảnh thành công']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Không thể xóa ảnh']);
            }
            
        } catch (Exception $e) {
            error_log("DeleteImage Error: " . $e->getMessage());
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()]);
        }
    }
    
    public function updateProduct() {
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['product_id'])) {
                throw new Exception("Yêu cầu không hợp lệ");
            }
            
            if (!$this->conn) {
                throw new Exception("Kết nối database thất bại");
            }
            
            $this->conn->autocommit(false);
            
            $product_id = $_POST['product_id'];
            $requiredFields = ['product_name', 'category_child', 'product_price', 'product_weight'];
            foreach ($requiredFields as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Thiếu thông tin: $field");
                }
            }
            
            $category_id = $_POST['category_child'];
            $categoryInfo = $this->category->getCategoryByID($category_id);
            if (!$categoryInfo) {
                throw new Exception("Danh mục không tồn tại");
            }
            
            $parentCategory = $categoryInfo['parent_category_id'] ?
                $this->category->getCategoryByID($categoryInfo['parent_category_id']) : null;
            $isPet = $parentCategory && strtolower($parentCategory['category_name']) === 'pet';
            
            $selectedSizes = '';
            if (!$isPet && !empty($_POST['selected_sizes'])) {
                $selectedSizes = $_POST['selected_sizes'];
            }
            
            // Cập nhật thông tin sản phẩm
            $productData = [
                'product_name' => trim($_POST['product_name']),
                'description' => trim($_POST['business_description'] ?? ''),
                'category_id' => $category_id,
                'price' => floatval($_POST['product_price']),
                'stock_quantity' => 0,
                'min_stock_level' => 0,
                'weight' => floatval($_POST['product_weight'] ?? 0),
                'is_active' => 1,
                'age' => $isPet ? trim($_POST['main_age'] ?? '') : null,
                'material' => !$isPet ? trim($_POST['product_material'] ?? '') : null,
                'size' => $selectedSizes
            ];
            
            $result = $this->product->updateProduct($product_id, $productData);
            if (!$result) {
                throw new Exception("Không thể cập nhật sản phẩm");
            }
            
            // Xử lý ảnh
            $this->handleImageUpdate($product_id, $isPet);
            
            $this->conn->commit();
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Cập nhật sản phẩm thành công!',
                    'redirect' => 'product_manager.php'
                ]);
                exit;
            } else {
                $_SESSION['success_message'] = 'Cập nhật sản phẩm thành công!';
                header('Location: product_manager.php');
                exit;
            }
            
        } catch (Exception $e) {
            if ($this->conn) {
                $this->conn->rollback();
            }
            
            error_log("UpdateProduct Error: " . $e->getMessage());
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
                exit;
            } else {
                $_SESSION['error_message'] = $e->getMessage();
                header('Location: product_manager.php');
                exit;
            }
        }
    }

    private function handleImageUpdate($product_id, $isPet) {
        // Nếu chọn thay thế tất cả ảnh
        if (isset($_POST['replace_all_images']) && $_POST['replace_all_images'] == '1') {
            if (!empty($_FILES['product_images']['name'][0])) {
                // Xóa tất cả ảnh cũ
                $this->productImage->deleteAllImagesByProductId($product_id);
                // Upload ảnh mới
                $this->handleImageUpload($product_id, $isPet);
            }
            return;
        }
        
        // Nếu không thay thế tất cả, chỉ cập nhật thông tin ảnh hiện tại
        $this->updateExistingImages($product_id, $isPet);
        
        // Thêm ảnh mới nếu có
        if (!empty($_FILES['product_images']['name'][0])) {
            $this->handleImageUpload($product_id, $isPet, false); // false = không xóa ảnh cũ
        }
    }

    // Thêm method mới vào ProductImage class
    public function updateImageInfo($image_id, $gender = null, $color = null, $age = null) {
        $query = "UPDATE product_images 
                SET gender = ?, color = ?, age = ?
                WHERE image_id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$gender, $color, $age, $image_id]);
    }

    public function getMaxDisplayOrder($product_id) {
        $query = "SELECT MAX(display_order) as max_order FROM product_images WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['max_order'] ?? 0;
    }

    public function deleteAllImagesByProductId($product_id) {
        // Xóa file vật lý trước
        $images = $this->getImagesByProductId($product_id);
        foreach ($images as $image) {
            $filepath = __DIR__ . '/../../public/uploads/product/' . $image['image_url'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        
        // Xóa record trong database
        $query = "DELETE FROM product_images WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$product_id]);
    }
    
    public function deleteProduct() {
        try {
            // Kiểm tra đăng nhập
            if (!isset($_SESSION['user_id'])) {
                echo json_encode(['success' => false, 'message' => 'Not logged in']);
                return;
            }

            // Kiểm tra quyền admin
            $current_user = $this->user->getUserByID($_SESSION['user_id']);
            if (!$this->user->isAdmin($current_user)) {
                echo json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']);
                return;
            }

            // Lấy product ID
            if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
                echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
                return;
            }

            $product_id = (int)$_GET['id'];

            // Kiểm tra sản phẩm có tồn tại không
            $existing_product = $this->product->getProductByID($product_id);
            if (!$existing_product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                return;
            }

            $product_id = (int)$_GET['id'];
            $existing_product = $this->product->getProductByID($product_id);
            if (!$existing_product) {
                echo json_encode(['success' => false, 'message' => 'Product not found']);
                return;
            }

            // 🛑 Kiểm tra xem sản phẩm đang nằm trong đơn xử lý
            if ($this->orderItemModel->isProductInProcessingOrders($product_id)) {
                echo ('Không thể xóa sản phẩm vì đang có đơn hàng ở trạng thái "đang xử lý" chứa sản phẩm này.');
                return;
            }

            // Bắt đầu transaction
            $this->conn->begin_transaction();

            try {
                // Bước 1: Xóa tất cả ảnh của sản phẩm (cả file và record trong DB)
                $delete_images_result = $this->productImage->deleteAllImagesByProductId($product_id);
                
                if (!$delete_images_result) {
                    throw new Exception('Failed to delete product images');
                }

                // Bước 2: Xóa sản phẩm
                $delete_product_result = $this->product->deleteProduct($product_id);
                
                if (!$delete_product_result) {
                    throw new Exception('Failed to delete product');
                }

                // Commit transaction
                $this->conn->commit();
                
                echo "Deleted successfully";
                
            } catch (Exception $e) {
                // Rollback nếu có lỗi
                $this->conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log('Error deleting product: ' . $e->getMessage());
            echo "Delete failed: " . $e->getMessage();
        }
    }
    
    // public function getParentCategoryListAjax() {
    //     try {
    //         $parentCategories = $this->category->getParentCategories();
    //         header('Content-Type: application/json');
    //         echo json_encode($parentCategories);
    //     } catch (Exception $e) {
    //         header('Content-Type: application/json');
    //         echo json_encode(['error' => $e->getMessage()]);
    //     }
    // }

    public function filterProducts() {
        try {
            // Get filter parameters
            $search = $_GET['search'] ?? '';
            $parentCategoryId = $_GET['parent_category'] ?? '';
            $childCategoryId = $_GET['child_category'] ?? '';
            $status = $_GET['status'] ?? '';
            $page = $_GET['page'] ?? 1;
            $limit = $_GET['limit'] ?? 50;
            
            // Build the WHERE clause
            $whereConditions = [];
            $params = [];
            $types = '';
            
            // Search condition
            if (!empty($search)) {
                $whereConditions[] = "(p.product_name LIKE ? OR p.product_id LIKE ?)";
                $searchParam = '%' . $search . '%';
                $params[] = $searchParam;
                $params[] = $searchParam;
                $types .= 'ss';
            }
            
            // Category conditions
            if (!empty($childCategoryId)) {
                $whereConditions[] = "p.category_id = ?";
                $params[] = $childCategoryId;
                $types .= 'i';
            } elseif (!empty($parentCategoryId)) {
                // Get all child categories of the parent
                $childCategories = $this->category->getChildCategories($parentCategoryId);
                if (!empty($childCategories)) {
                    $childIds = array_column($childCategories, 'category_id');
                    $childIds[] = $parentCategoryId; // Include parent itself
                    $placeholders = str_repeat('?,', count($childIds) - 1) . '?';
                    $whereConditions[] = "p.category_id IN ($placeholders)";
                    $params = array_merge($params, $childIds);
                    $types .= str_repeat('i', count($childIds));
                } else {
                    $whereConditions[] = "p.category_id = ?";
                    $params[] = $parentCategoryId;
                    $types .= 'i';
                }
            }
            
            // Status condition
            if ($status !== '') {
                $whereConditions[] = "p.is_active = ?";
                $params[] = (int)$status;
                $types .= 'i';
            }
            
            // Build the query
            $whereClause = '';
            if (!empty($whereConditions)) {
                $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
            }
            
            // Calculate offset for pagination
            $offset = ($page - 1) * $limit;
            
            // Main query
            $query = "
                SELECT p.*, c.category_name, c.parent_category_id,
                    pc.category_name as parent_category_name
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                LEFT JOIN categories pc ON c.parent_category_id = pc.category_id
                $whereClause
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?
            ";
            
            // Add pagination parameters
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = $this->conn->prepare($query);
            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            $products = $result->fetch_all(MYSQLI_ASSOC);
            
            // Get total count for pagination
            $countQuery = "
                SELECT COUNT(*) as total
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.category_id
                " . str_replace(['LIMIT ? OFFSET ?'], [''], $whereClause);
            
            $countStmt = $this->conn->prepare($countQuery);
            if (!empty($params) && count($params) > 2) {
                // Remove the last 2 parameters (limit and offset)
                $countParams = array_slice($params, 0, -2);
                $countTypes = substr($types, 0, -2);
                $countStmt->bind_param($countTypes, ...$countParams);
            }
            
            $countStmt->execute();
            $totalResult = $countStmt->get_result();
            $totalCount = $totalResult->fetch_assoc()['total'];
            
            // Format response
            $response = [
                'success' => true,
                'products' => $products,
                'pagination' => [
                    'current_page' => (int)$page,
                    'per_page' => (int)$limit,
                    'total' => (int)$totalCount,
                    'total_pages' => ceil($totalCount / $limit)
                ],
                'filters_applied' => [
                    'search' => $search,
                    'parent_category' => $parentCategoryId,
                    'child_category' => $childCategoryId,
                    'status' => $status
                ]
            ];
            
            header('Content-Type: application/json');
            echo json_encode($response);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getProductStats() {
        try {
            $query = "
                SELECT 
                    COUNT(*) as total_products,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_products,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive_products,
                    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock,
                    SUM(CASE WHEN stock_quantity <= min_stock_level AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock
                FROM products
            ";
            
            $result = $this->conn->query($query);
            $stats = $result->fetch_assoc();
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    public function getProduct() {
        if (isset($_GET['id'])) {
            $result = $this->product->getProductByID($_GET['id']);
            echo json_encode($result);
        }
    }
    
    public function getProductsByCategory() {
        if (isset($_GET['category_id'])) {
            $result = $this->product->getAllProductByCategory($_GET['category_id']);
            echo json_encode($result);
        }
    }
    
    public function getAllProducts() {
        $result = $this->product->getAllProduct();
        echo json_encode($result);
    }

    public function removeImage() {
        if (isset($_GET['image_id'])) {
            $image_id = $_GET['image_id'];
            
            try {
                // Lấy thông tin ảnh trước khi xóa
                $image = $this->productImage->getImageById($image_id);
                
                if (!$image) {
                    throw new Exception("Ảnh không tồn tại");
                }
                
                // Xóa file ảnh khỏi server
                $image_path = __DIR__ . '/../../public/uploads/product/' . $image['image_url'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
                
                // Xóa record trong database
                $result = $this->productImage->deleteImage($image_id);
                
                if ($result) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Xóa ảnh thành công']);
                } else {
                    throw new Exception("Không thể xóa ảnh khỏi database");
                }
                
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        }
    }
}

// CHỈ XỬ LÝ ROUTING KHI ĐƯỢC GỌI TRỰC TIẾP
if (basename($_SERVER['PHP_SELF']) === 'ProductController.php') {
    $controller = new ProductController($conn);
    $action = $_GET['action'] ?? 'showAddForm';

    // Trong phần routing của ProductController.php
    switch ($action) {
        case 'getParentCategoryListAjax':
            $controller->getParentCategoryListAjax();
            break;
        case 'toggleProduct':
            $controller->toggleProduct();
            break;
        case 'removeImage':
            $controller->removeImage();
            break;
        case 'showAddForm':
            $controller->showAddForm();
            break;
        case 'add_product':
            $controller->addProduct();
            break;
        case 'getProductForEdit':
            $controller->getProductForEdit();
            break;
        case 'update_product':
            $controller->updateProduct();
            break;
        case 'getChildCategories':
            $controller->getChildCategories();
            break;
        case 'getParentCategoryName':
            $controller->getParentCategoryName();
            break;
        case 'delete':
            $controller->deleteProduct();
            break;
        case 'get':
            $controller->getProduct();
            break;
        case 'byCategory':
            $controller->getProductsByCategory();
            break;
        case 'all':
            $controller->getAllProducts();
            break;
        case 'filter':
            $controller->filterProducts();
            break;
        case 'stats':
            $controller->getProductStats();
            break;
        default:
            echo "Invalid action.";
    }
}
?>