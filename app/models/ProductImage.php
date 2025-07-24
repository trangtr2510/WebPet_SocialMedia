<?php

class ProductImage {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Thêm ảnh sản phẩm với gender và color
    public function addImage($product_id, $image_url, $alt_text = '', $is_primary = false, $display_order = 0, $gender = null, $color = null) {
        $query = "INSERT INTO product_images (product_id, image_url, alt_text, is_primary, display_order, gender, color)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            die('Prepare failed: ' . $this->conn->error);
        }

        $gender = $gender ?? '';
        $color = $color ?? '';

        $stmt->bind_param(
            'issiiss',
            $product_id,
            $image_url,
            $alt_text,
            $is_primary,
            $display_order,
            $gender,
            $color
        );

        $result = $stmt->execute();

        if ($result) {
            return $this->conn->insert_id;
        }
        return false;
    }

    // Cải tiến method updateImage
    public function updateImage($image_id, $image_url, $alt_text, $is_primary, $display_order, $gender = null, $color = null, $age = null) {
        $query = "UPDATE product_images
                SET image_url = ?, alt_text = ?, is_primary = ?, display_order = ?, gender = ?, color = ? 
                WHERE image_id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            $image_url, 
            $alt_text, 
            $is_primary, 
            $display_order, 
            $gender, 
            $color, 
            $image_id
        ]);
    }

    public function updateImageInfo($image_id, $gender, $color) {
        $query = "UPDATE product_images 
                SET gender = ?, color = ? 
                WHERE image_id = ?";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$gender, $color, $image_id]);
    }

    public function getImageById($image_id) {
        $query = "SELECT * FROM product_images WHERE image_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $image_id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc(); // Trả về 1 dòng ảnh
    }

    // Lấy ảnh theo sản phẩm
    public function getImagesByProduct($product_id) {
        $query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY display_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        return $images;
    }

    // Lấy ảnh chính của sản phẩm
    public function getPrimaryImageByProduct($product_id) {
        $query = "SELECT * FROM product_images WHERE product_id = ? AND is_primary = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    // Xoá ảnh
    public function deleteImage($image_id) {
        $query = "DELETE FROM product_images WHERE image_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$image_id]);
    }

    // Xoá tất cả ảnh của sản phẩm
    public function deleteImagesByProduct($product_id) {
        $query = "DELETE FROM product_images WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$product_id]);
    }

    // Hàm đếm số lượng ảnh theo product_id
    public function countImagesByProduct($product_id) {
        $query = "SELECT COUNT(*) as total FROM product_images WHERE product_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row ? (int)$row['total'] : 0;
    }

    // Lấy ảnh theo gender
    public function getImagesByGender($product_id, $gender) {
        $query = "SELECT * FROM product_images WHERE product_id = ? AND gender = ? ORDER BY display_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $product_id, $gender);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        return $images;
    }

    // Lấy ảnh theo color
    public function getImagesByColor($product_id, $color) {
        $query = "SELECT * FROM product_images WHERE product_id = ? AND color = ? ORDER BY display_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("is", $product_id, $color);
        $stmt->execute();
        $result = $stmt->get_result();
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        return $images;
    }

    public function getGenderByProduct($product_id) {
        $query = "SELECT gender FROM product_images WHERE product_id = ? AND gender IS NOT NULL LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $gender = [];
        while ($row = $result->fetch_assoc()) {
            $gender[] = $row;
        }
        return $gender;
    }

    // Thêm vào ProductImage.php
    public function getImagesByProductId($product_id) {
        $query = "SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, display_order ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        
        return $images;
    }
     // Method cải tiến để xóa tất cả ảnh của sản phẩm (cả file và database record)
    public function deleteAllImagesByProductId($product_id) {
        try {
            // Lấy danh sách ảnh trước khi xóa để xóa file
            $images = $this->getImagesByProductId($product_id);
            
            // Xóa file ảnh từ server
            foreach ($images as $image) {
                // Kiểm tra xem có field image_url hay image_path
                $image_filename = isset($image['image_url']) ? $image['image_url'] : (isset($image['image_path']) ? $image['image_path'] : '');
                
                if (!empty($image_filename)) {
                    $file_path = __DIR__ . '/../../public/uploads/product/' . $image_filename;
                    
                    // Xóa file nếu tồn tại
                    if (file_exists($file_path)) {
                        if (!unlink($file_path)) {
                            error_log("Cannot delete image file: " . $file_path);
                        }
                    }
                }
            }
            
            // Xóa record từ database
            $query = "DELETE FROM product_images WHERE product_id = ?";
            $stmt = $this->conn->prepare($query);
            
            if (!$stmt) {
                error_log('Prepare failed: ' . $this->conn->error);
                return false;
            }
            
            $stmt->bind_param("i", $product_id);
            return $stmt->execute();
            
        } catch (Exception $e) {
            error_log('Error deleting product images: ' . $e->getMessage());
            return false;
        }
    }
}

?>