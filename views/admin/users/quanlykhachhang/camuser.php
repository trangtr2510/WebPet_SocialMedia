<?php 
include(__DIR__ . '/../../../../config/config.php');

if (isset($_GET['id'])) {
    $user_id = (int) $_GET['id'];

    // Kiểm tra xem user có tồn tại không
    $check = $mysqli->query("SELECT * FROM users WHERE user_id = $user_id");
    if ($check->num_rows > 0) {
        // Cập nhật is_active = 0 (Cấm tài khoản)
        $sql = "UPDATE users SET is_active = 0 WHERE user_id = $user_id";
        if ($mysqli->query($sql)) {
            header("Location: quanlykhachhang.php?msg=ban_success");
            exit;
        } else {
            echo "Lỗi khi cập nhật: " . $mysqli->error;
        }
    } else {
        echo "Người dùng không tồn tại.";
    }
} else {
    echo "Thiếu ID người dùng.";
}
?>
