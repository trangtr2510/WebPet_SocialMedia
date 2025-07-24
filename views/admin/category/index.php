<?php
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

// Sửa danh mục
$editing = false;
$editData = [];

if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $resultEdit = $stmt->get_result();
    if ($resultEdit->num_rows > 0) {
        $editData = $resultEdit->fetch_assoc();
        $editing = true;
    }
    $stmt->close();
}

// Cập nhật danh mục
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    $id = intval($_POST['category_id']);
    $name = $_POST['category_name'] ?? '';
    $desc = $_POST['description'] ?? '';
    $parent = $_POST['parent_category_id'] ?: NULL;
    $active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;

    $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ?, parent_category_id = ?, is_active = ? WHERE category_id = ?");
    $stmt->bind_param("ssiii", $name, $desc, $parent, $active, $id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']));
    exit;
}

// Xóa danh mục
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    header("Location: " . htmlspecialchars($_SERVER['PHP_SELF']));
    exit;
}

// Lấy danh sách danh mục
$sql = "SELECT * FROM categories ORDER BY category_id ASC";
$result = $conn->query($sql);

$categories = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}

// Danh sách danh mục cha
$parentCategories = [];
$parentQuery = $conn->prepare("SELECT category_id, category_name FROM categories WHERE parent_category_id IS NULL");
$parentQuery->execute();
$parentQuery->bind_result($id, $name);
while ($parentQuery->fetch()) {
    $parentCategories[] = ['id' => $id, 'name' => $name];
}
$parentQuery->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8" />
    <title>Quản lý danh mục thú cưng</title>
    <link rel="stylesheet" href="../../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../../public/css/admin/admin.css">
    <script type="text/javascript" src="../../../public/js/admin.js" defer></script>
    <style>
        .main_admin {
            margin: 30px auto;
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
        <h1>Quản lý danh mục thú cưng</h1>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tên danh mục</th>
                    <th>Mô tả</th>
                    <th>Danh mục cha</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($categories) > 0): ?>
                    <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td><?= $cat['category_id'] ?></td>
                            <td><?= htmlspecialchars($cat['category_name']) ?></td>
                            <td><?= htmlspecialchars($cat['description']) ?></td>
                            <td>
                                <?php
                                $parentName = "NULL";
                                foreach ($categories as $p) {
                                    if ($p['category_id'] == $cat['parent_category_id']) {
                                        $parentName = $p['category_name'];
                                        break;
                                    }
                                }
                                echo htmlspecialchars($parentName);
                                ?>
                            </td>
                            <td><?= $cat['is_active'] ? "Hoạt động" : "Không hoạt động" ?></td>
                            <td>
                                <a href="./suadanhmuc.php?id=<?= $cat['category_id'] ?>">Sửa</a> /
                                <a href="?action=delete&id=<?= $cat['category_id'] ?>" onclick="return confirm('Bạn có chắc chắn muốn xóa?')">Xóa</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6">Không có danh mục nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="button-container">
            <a href="themdanhmuc.php" class="btn-them-moi">Thêm Danh Mục Mới</a>
        </div>

        <?php if ($editing): ?>
            <h2>Cập nhật danh mục</h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="update" />
                <input type="hidden" name="category_id" value="<?= $editData['category_id'] ?>" />

                <div class="form-group">
                    <label for="category_name">Tên danh mục:</label>
                    <input type="text" name="category_name" id="category_name" required value="<?= htmlspecialchars($editData['category_name']) ?>" />
                </div>

                <div class="form-group">
                    <label for="description">Mô tả:</label>
                    <textarea name="description" id="description" rows="3"><?= htmlspecialchars($editData['description']) ?></textarea>
                </div>

                <div class="form-group">
                    <label for="parent_category_id">Danh mục cha:</label>
                    <select name="parent_category_id" id="parent_category_id">
                        <option value="">-- Không có --</option>
                        <?php foreach ($parentCategories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $editData['parent_category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="is_active">Trạng thái:</label>
                    <select name="is_active" id="is_active">
                        <option value="1" <?= $editData['is_active'] == 1 ? 'selected' : '' ?>>Hoạt động</option>
                        <option value="0" <?= $editData['is_active'] == 0 ? 'selected' : '' ?>>Không hoạt động</option>
                    </select>
                </div>

                <div class="form-group">
                    <button class="btn-them-moi">Cập nhật</button>
                </div>
            </form>
        <?php endif; ?>
    </main>
    
</body>
</html>
