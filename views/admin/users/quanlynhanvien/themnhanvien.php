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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username   = trim($_POST['username']);
    $password   = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $full_name  = trim($_POST['full_name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $address    = trim($_POST['address']);
    $user_type  = 'employee'; // Luôn là nhân viên
    $status     = $_POST['status']; // Online hoặc Offline

    // Kiểm tra status không hợp lệ thì gán lại mặc định
    if (!in_array($status, ['Online', 'Offline'])) {
        $status = 'Offline';
    }

    // Online thì active = 1, Offline thì active = 0
    $is_active = ($status === 'Online') ? 1 : 0;

    // Kiểm tra email trùng
    $check_email = $mysqli->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        $error = "❌ Email đã tồn tại. Vui lòng dùng email khác.";
    } else {
        // Xử lý ảnh
        $img_name = '';
        if (isset($_FILES['img']) && $_FILES['img']['error'] == 0) {
            $img_name = basename($_FILES['img']['name']);
            $upload_dir = '../../../../public/uploads/avatar/';
            $target_file = $upload_dir . $img_name;
            move_uploaded_file($_FILES['img']['tmp_name'], $target_file);
        }

        // Thêm vào DB
        $stmt = $mysqli->prepare("INSERT INTO users (username, password_hash, full_name, email, phone, address, user_type, is_active, status, img) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssss", $username, $password, $full_name, $email, $phone, $address, $user_type, $is_active, $status, $img_name);

            if ($stmt->execute()) {
                header("Location: ./quanlynhanvien.php");
                exit();
            } else {
                $error = "❌ Lỗi thêm: " . $stmt->error;
            }
        $stmt->close();
    }
    $check_email->close();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thêm nhân viên</title>
    <link rel="stylesheet" href="../../../../public/css/admin/style_actions.css">
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
        <h2>Thêm Nhân Viên Mới</h2>

        <?php if ($success): ?>
            <p style="color: green;"><?= $success ?></p>
        <?php elseif ($error): ?>
            <p style="color: red;"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label>Tên đăng nhập:</label><br>
            <input type="text" name="username" required><br><br>

            <label>Mật khẩu:</label><br>
            <input type="password" name="password" required><br><br>

            <label>Họ tên:</label><br>
            <input type="text" name="full_name" required><br><br>

            <label>Email:</label><br>
            <input type="email" name="email" required><br><br>

            <label>Số điện thoại:</label><br>
            <input type="text" name="phone" required><br><br>

            <label>Địa chỉ:</label><br>
            <input type="text" name="address"><br><br>

            <label>Ảnh đại diện:</label><br>
            <input type="file" name="img"><br><br>

            <label>Trạng thái:</label><br>
            <select name="status" required>
                <option value="Online">🟢 Online</option>
                <option value="Offline">⚪ Offline</option>
            </select><br><br>

            <input type="submit" value="Thêm Nhân Viên"> 
        </form>

        <br>
        <a href="./quanlynhanvien.php">← Trở về danh sách nhân viên</a>
    </main>
</body>
</html>
