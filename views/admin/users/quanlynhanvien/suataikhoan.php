<?php 
include(__DIR__ . '/../../../../config/config.php');
require_once(__DIR__ .'/../../../../app/models/User.php');

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$username_nav = $is_logged_in ? $_SESSION['username'] : '';
$full_name_nav = $is_logged_in ? $_SESSION['full_name'] : '';
$user_type_nav = $is_logged_in ? $_SESSION['user_type'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

$user_id = (int)$_GET['id'];
$result = $mysqli->query("SELECT * FROM users WHERE user_id = $user_id");

if ($result->num_rows === 0) {
    die("Không tìm thấy nhân viên.");
}

$row = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $mysqli->real_escape_string($_POST['username']);
    $full_name = $mysqli->real_escape_string($_POST['full_name']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $phone = $mysqli->real_escape_string($_POST['phone']);
    $address = $mysqli->real_escape_string($_POST['address']);
    $user_type = $mysqli->real_escape_string($_POST['user_type']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $status = $is_active ? 'Online' : 'Offline';

    $img_name = $row['img']; // giữ ảnh cũ nếu không cập nhật

    // Nếu có tải ảnh mới
    if (!empty($_FILES['img']['name'])) {
        $target_dir = "../../../../public/uploads/avatar/";
        $img_name = basename($_FILES["img"]["name"]);
        $target_file = $target_dir . $img_name;

        if (move_uploaded_file($_FILES["img"]["tmp_name"], $target_file)) {
            // thành công
        } else {
            echo "<script>alert('Tải ảnh thất bại.');</script>";
        }
    }

    $update = $mysqli->query("UPDATE users SET 
        username = '$username',
        full_name = '$full_name',
        email = '$email',
        phone = '$phone',
        address = '$address',
        user_type = '$user_type',
        is_active = $is_active,
        status = '$status',
        img = '$img_name'
        WHERE user_id = $user_id");

        if ($update) {
            echo "<script>alert('Cập nhật thành công'); window.location.href='./quanlynhanvien.php';</script>";
        } else {
            echo "Lỗi cập nhật: " . $mysqli->error;
        }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Sửa tài khoản nhân viên</title>
    <link rel="stylesheet" href="../../../../public/css/admin/style3.css">
    <link rel="stylesheet" href="../../../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../../../public/css/admin/admin.css">
    <script type="text/javascript" src="../../../../public/js/admin.js" defer></script>
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
                        <img src="../../../../public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="User Avatar" class="user-avatar-collapsed">
                    </div>
                </div>
                <div class="logo">
                    <img src="../../../../public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="User Avatar" class="user-avatar">
                    <div class="user-details">
                        <span class="user-name"><?php echo htmlspecialchars($full_name_nav); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($user_type_nav); ?></span>
                    </div>
                </div>
            </li>
            <!-- Navigation Items -->
             <!-- Navigation Items -->
            <li>
                <a href="../../dashboard.php" data-tooltip="Dashboard">
                    <i class="fas fa-chart-line icon_nav"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="active">
                <a href="../../category/index.php" data-tooltip="Danh mục">
                    <i class="fa-solid fa-list icon_nav"></i>
                    <span>Danh mục</span>
                </a>
            </li>
            <li>
                <a href="../../products/product_manager.php" data-tooltip="Shop">
                    <i class="fa-solid fa-paw icon_nav"></i>
                    <span>Shop</span>
                </a>
            </li>
            <li>
                <a href="../../post/post_manager.php" data-tooltip="Diễn đàn">
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
                    <li class="active_child"><a href="./quanlynhanvien.php">Quản lý nhân viên</a></li>
                    <li><a href="../quanlykhachhang/quanlykhachhang.php#">Quản lý khách hàng</a></li>
                  </div>
                </ul>
            </li>
            <li>
                <a href="../../orders/order_manager.php" data-tooltip="Order">
                    <i class="fa-solid fa-truck"></i>
                    <span>Quản lý đơn hàng</span>
                </a>
            </li>
             <li>
                <a href="../../../../app/controllers/LogoutController.php" data-tooltip="Order">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Đăng xuất</span>
                </a>
            </li>
        </ul>
    </nav>

    <main class="main_admin">
        <h2>Sửa tài khoản nhân viên</h2>
        <form method="POST" enctype="multipart/form-data">
            <label>Tên đăng nhập:</label><br>
            <input type="text" name="username" value="<?= htmlspecialchars($row['username']) ?>" required><br><br>

            <label>Họ tên:</label><br>
            <input type="text" name="full_name" value="<?= htmlspecialchars($row['full_name']) ?>" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" value="<?= htmlspecialchars($row['email']) ?>" required><br><br>

            <label>SĐT:</label><br>
            <input type="text" name="phone" value="<?= htmlspecialchars($row['phone']) ?>" required><br><br>

            <label>Địa chỉ:</label><br>
            <input type="text" name="address" value="<?= htmlspecialchars($row['address']) ?>" required><br><br>

            <label>Loại người dùng:</label><br>
            <select name="user_type">
                <option value="employee" <?= $row['user_type'] === 'employee' ? 'selected' : '' ?>>Nhân viên</option>
                <option value="admin" <?= $row['user_type'] === 'admin' ? 'selected' : '' ?>>Quản trị</option>
            </select><br><br>

            <label>Trạng thái:</label><br>
            <input type="checkbox" name="is_active" <?= $row['is_active'] ? 'checked' : '' ?>> Đang hoạt động<br><br>

            <label>Ảnh đại diện:</label><br>
            <?php if (!empty($row['img']) && file_exists("../../../../public/uploads/avatar/" . $row['img'])): ?>
                <img src="../../../../public/uploads/avatar/<?= $row['img'] ?>" alt="Ảnh" width="80"><br>
            <?php endif; ?>
            <input type="file" name="img"><br><br>

            <button type="submit">💾 Lưu thay đổi</button>
            <a href="./quanlynhanvien.php" class="back-button">🔙 Quay lại</a>
        </form>
    </main>
</body>
</html>
