<?php
// them_danh_muc.php
$servername = "localhost";
$username = "root";
$password = "";
$database = "petshop_socialmedia";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
require_once(__DIR__ .'/../../../app/models/User.php');

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$user_type = $is_logged_in ? $_SESSION['user_type'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);


// Lấy danh mục cha
$parentCategories = [];
$parentQuery = $conn->prepare("SELECT category_id, category_name FROM categories WHERE parent_category_id IS NULL");
$parentQuery->execute();
$parentQuery->bind_result($id, $name);
while ($parentQuery->fetch()) {
    $parentCategories[] = ['id' => $id, 'name' => $name];
}
$parentQuery->close();

// Xử lý thêm mới
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['category_name'] ?? '';
    $desc = $_POST['description'] ?? '';
    $parent = $_POST['parent_category_id'] ?: NULL;
    $active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

    $stmt = $conn->prepare("INSERT INTO categories (category_name, description, parent_category_id, is_active) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssii", $name, $desc, $parent, $active);
    $stmt->execute();
    $stmt->close();

    header("Location: index.php"); // Quay lại trang chính sau khi thêm
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm danh mục mới</title>
    <link rel="stylesheet" href="../../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../../public/css/admin/admin.css">
    <script type="text/javascript" src="../../../public/js/admin.js" defer></script>
    <style>
        .main_admin {
            margin: 20px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 0 12px rgba(0, 0, 0, 0.1);
            font-family: Arial, sans-serif;
        }

        .main_admin h1, .main_admin h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .btn-back,
        .btn-them-moi,
        .btn-cap-nhat {
            display: inline-block;
            padding: 10px 20px;
            margin-top: 10px;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            border: none;
            border-radius: 6px;
            transition: background-color 0.3s ease;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-back:hover,
        .btn-them-moi:hover,
        .btn-cap-nhat:hover {
            background-color: #2980b9;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #444;
        }

        .form-group input[type="text"],
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            font-size: 14px;
            border-radius: 6px;
            border: 1px solid #ccc;
            box-sizing: border-box;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            border-color: #3498db;
            outline: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
        }

        table th, table td {
            padding: 12px 10px;
            border: 1px solid #ddd;
            text-align: center;
        }

        table th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }

        table tr:nth-child(even) {
            background-color: #fafafa;
        }

        table a {
            color: #3498db;
            text-decoration: none;
        }

        table a:hover {
            text-decoration: underline;
        }

        .button-container {
            text-align: right;
            margin-top: 20px;
        }
    </style>

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
             <!-- Navigation Items -->
            <li>
                <a href="../dashboard.php" data-tooltip="Dashboard">
                    <i class="fas fa-chart-line icon_nav"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="active">
                <a href="../category/index.php" data-tooltip="Danh mục">
                    <i class="fa-solid fa-list icon_nav"></i>
                    <span>Danh mục</span>
                </a>
            </li>
            <li>
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
        <a href="index.php" class="btn-back">Trở về trang danh mục</a>
        <h1>Thêm danh mục mới</h1>
        <form method="post" action="">
            <div class="form-group">
                <label for="category_name">Tên danh mục:</label>
                <input type="text" name="category_name" id="category_name" required />
            </div>

            <div class="form-group">
                <label for="description">Mô tả:</label>
                <textarea name="description" id="description" rows="3"></textarea>
            </div>

            <div class="form-group">
                <label for="parent_category_id">Danh mục cha:</label>
                <select name="parent_category_id" id="parent_category_id">
                    <option value="">-- Không có --</option>
                    <?php foreach ($parentCategories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label for="is_active">Trạng thái:</label>
                <select name="is_active" id="is_active">
                    <option value="1">Hoạt động</option>
                    <option value="0">Không hoạt động</option>
                </select>
            </div>

            <div class="form-group">
                <button class="btn-them-moi">Thêm mới</button>
            </div>
        </form>
    </main>
    
</body>
</html>
