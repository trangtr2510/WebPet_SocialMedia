<?php 
include(__DIR__ . '/../../../../config/config.php');

if (isset($_GET['id'])) {
    $userId = intval($_GET['id']);
    $stmt = $mysqli->prepare("UPDATE users SET is_active = 0, status = 'Blocked' WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
    header("Location: ./quanlynhanvien.php");
    exit();
} else {
    echo "Thiếu ID!";
}
?>
