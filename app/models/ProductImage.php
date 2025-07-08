<?php
class ProductImage {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Thêm ảnh sản phẩm
    public function addImage($product_id, $image_url, $alt_text = '', $is_primary = false, $display_order = 0) {
        $query = "INSERT INTO product_images (product_id, image_url, alt_text, is_primary, display_order)
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$product_id, $image_url, $alt_text, $is_primary, $display_order]);
    }

    // Sửa ảnh sản phẩm
    public function updateImage($image_id, $image_url, $alt_text, $is_primary, $display_order) {
        $query = "UPDATE product_images 
                  SET image_url = ?, alt_text = ?, is_primary = ?, display_order = ?
                  WHERE image_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$image_url, $alt_text, $is_primary, $display_order, $image_id]);
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
    
    // Lấy gender theo sản phẩm
    public function getGenderByProduct($product_id) {
        $query = "SELECT gender FROM product_images WHERE product_id = ? ORDER BY display_order ASC";
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

    // Xoá ảnh
    public function deleteImage($image_id) {
        $query = "DELETE FROM product_images WHERE image_id = ?";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([$image_id]);
    }
}
?>
