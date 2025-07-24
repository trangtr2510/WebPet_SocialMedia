<?php 
include(__DIR__ . '/../../../../config/config.php');

if (isset($_GET['id'])) {
    $user_id = (int) $_GET['id'];
    $sql = "DELETE FROM users WHERE user_id = $user_id";
    if ($mysqli->query($sql)) {
        header("Location: ./quanlykhachhang.php");
        exit;
    } else {
        echo "Lỗi: " . $mysqli->error;
    }
}
?>
