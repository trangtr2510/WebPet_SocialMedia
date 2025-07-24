<?php
// File: ProductAjaxController.php
session_start();
header('Content-Type: application/json');
include(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');
require_once(__DIR__ . '/../models/ProductImage.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Kiểm tra dữ liệu cần thiết
        $requiredFields = ['product_name', 'category_child', 'product_price', 'product_weight'];
        foreach ($requiredFields as $field) {
            if (empty($_POST[$field])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => "Thiếu thông tin bắt buộc: $field"
                ]);
                exit;
            }
        }

        // Kiểm tra ảnh
        if (empty($_FILES['product_images']['name'][0])) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Vui lòng chọn ít nhất một ảnh'
            ]);
            exit;
        }

        // Khởi tạo các model
        $product = new Product($conn);
        $category = new Category($conn);
        $productImage = new ProductImage($conn);

        // Lấy thông tin category để kiểm tra loại sản phẩm
        $category_id = $_POST['category_child'];
        $categoryInfo = $category->getCategoryByID($category_id);
        if (!$categoryInfo) {
            throw new Exception("Danh mục không tồn tại");
        }

        $parentCategory = $categoryInfo['parent_category_id'] ? 
            $category->getCategoryByID($categoryInfo['parent_category_id']) : null;
        $isPet = $parentCategory && strtolower($parentCategory['category_name']) === 'pet';

        // Xử lý sizes cho sản phẩm (không phải pet)
        $selectedSizes = '';
        if (!$isPet && !empty($_POST['selected_sizes'])) {
            $selectedSizes = $_POST['selected_sizes'];
        }

        // Chuẩn bị dữ liệu sản phẩm
        $productData = [
            'product_name' => trim($_POST['product_name']),
            'description' => trim($_POST['business_description'] ?? ''),
            'category_id' => $category_id,
            'price' => floatval($_POST['product_price']),
            'stock_quantity' => 0,
            'min_stock_level' => 0,
            'weight' => floatval($_POST['product_weight']),
            'is_active' => 1,
            'created_by' => $_SESSION['user_id'] ?? 1,
            'age' => $isPet ? trim($_POST['main_age'] ?? '') : '',
            'material' => !$isPet ? trim($_POST['product_material'] ?? '') : null,
            'size' => $selectedSizes
        ];

        // Bắt đầu transaction
        $conn->autocommit(false);

        // Tạo sản phẩm
        $product_id = $product->createProduct($productData);
        if (!$product_id) {
            throw new Exception("Không thể tạo sản phẩm");
        }

        // Xử lý upload ảnh
        $success = handleImageUpload($product_id, $isPet, $conn, $productImage);
        if (!$success) {
            throw new Exception("Không thể upload ảnh");
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Thêm sản phẩm thành công!',
            'redirect' => 'product_manager.php'
        ]);

    } catch (Exception $e) {
        // Rollback transaction nếu có lỗi
        if ($conn) {
            $conn->rollback();
        }
        
        error_log("ProductAjaxController Error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Lỗi máy chủ: ' . $e->getMessage()
        ]);
    }
}

function handleImageUpload($product_id, $isPet, $conn, $productImage) {
    if (!isset($_FILES['product_images']) || $_FILES['product_images']['error'][0] === UPLOAD_ERR_NO_FILE) {
        return false;
    }

    $upload_dir = __DIR__ . '/../../public/uploads/product/';
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        throw new Exception('Không thể tạo thư mục upload');
    }

    $allowed_mime_types = [
        'image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/gif',
        'image/bmp', 'image/svg+xml', 'image/x-icon', 'image/heic', 'image/avif'
    ];
    $max_size = 5 * 1024 * 1024; // 5MB

    $imageCount = count($_FILES['product_images']['name']);
    $primary_image_original_name = $_POST['primary_image_name'] ?? '';

    for ($i = 0; $i < $imageCount; $i++) {
        if ($_FILES['product_images']['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $originalName = $_FILES['product_images']['name'][$i];
        $file_tmp = $_FILES['product_images']['tmp_name'][$i];
        $file_size = $_FILES['product_images']['size'][$i];

        // Kiểm tra MIME type
        $mime_type = mime_content_type($file_tmp);
        if (!in_array($mime_type, $allowed_mime_types)) {
            throw new Exception("File $originalName không phải là ảnh hợp lệ");
        }

        // Kiểm tra kích thước
        if ($file_size > $max_size) {
            throw new Exception("Ảnh $originalName vượt quá dung lượng cho phép 5MB");
        }

        // Tạo tên file an toàn
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9-_]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
        $filename = $safeName . '_' . time() . '_' . $i . '.' . $extension;
        $filepath = $upload_dir . $filename;

        // Di chuyển file
        if (!move_uploaded_file($file_tmp, $filepath)) {
            throw new Exception("Không thể lưu ảnh $originalName");
        }

        // Xác định ảnh chính
        $is_primary = ($originalName === $primary_image_original_name) ? 1 : 0;

        // Lấy thông tin pet nếu cần
        $gender = $color = $age = null;
        if ($isPet) {
            if ($is_primary) {
                $gender = $_POST['main_gender'] ?? null;
                $color = $_POST['main_color'] ?? null;
                $age = $_POST['main_age'] ?? null;
            } else {
                $gender = $_POST['image_gender_' . ($i + 1)] ?? $_POST['image_gender_' . $i] ?? null;
                $color = $_POST['image_color_' . ($i + 1)] ?? $_POST['image_color_' . $i] ?? null;
                $age = $_POST['image_age_' . ($i + 1)] ?? $_POST['image_age_' . $i] ?? null;
            }
        }

        // Lưu vào database
        $imageResult = $productImage->addImage(
            $product_id,
            $filename,
            $originalName,
            $is_primary,
            $i + 1,
            $gender,
            $color,
            $age
        );

        if (!$imageResult) {
            error_log("ERROR saving image to database: " . print_r($conn->errorInfo(), true));
            throw new Exception("Không thể lưu ảnh vào DB: " . $originalName);
        }
    }

    return true;
}
?>