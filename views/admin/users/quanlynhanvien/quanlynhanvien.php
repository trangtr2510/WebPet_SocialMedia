<?php 
include(__DIR__ . '/../../../../config/config.php');
require_once(__DIR__ .'/../../../../app/models/User.php');

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$user_type = $is_logged_in ? $_SESSION['user_type'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quản lý nhân viên</title>
    <link rel="stylesheet" href="../../../../public/css/admin/style2.css" />
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
                        <span class="user-name"><?php echo htmlspecialchars($full_name); ?></span>
                        <span class="user-role"><?php echo htmlspecialchars($user_type); ?></span>
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
            <li>
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

        <h2>Quản lý nhân viên</h2>

        <table>
            <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên đăng nhập</th>
                <th>Họ tên</th>
                <th>Email</th>
                <th>SĐT</th>
                <th>Địa chỉ</th>
                <th>Loại</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
            <?php
            $result = $mysqli->query("SELECT * FROM users WHERE user_type = 'employee' ORDER BY user_id ASC");
            while ($row = $result->fetch_assoc()):
                $imgPath = "../../../../public/uploads/avatar/" . htmlspecialchars($row['img']);
            ?>
            <tr>
                <td><?= $row['user_id'] ?></td>
                <td>
                    <?php if (!empty($row['img']) && file_exists($imgPath)): ?>
                        <img src="<?= $imgPath ?>" alt="Ảnh đại diện" width="60" height="60">
                    <?php else: ?>
                        Không có ảnh
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td><?= htmlspecialchars($row['user_type']) ?></td>
                <td>
                    <?php
                    if ($row['is_active'] == 0) {
                        echo ($row['status'] === 'Blocked') ? '🔴 Đã cấm' : '⚪ Offline';
                    } else {
                        echo '🟢 Online';
                    }
                    ?>
                </td>
                <td class="action-link">
                    <?php if ($row['is_active'] == 1): ?>
                        <a class="ban" href="./camnhanvien.php?id=<?= $row['user_id'] ?>" onclick="return confirm('Bạn có chắc muốn cấm tài khoản này?')">🚫 Cấm</a>
                    <?php else: ?>
                        <a class="unban" href="./mokhoanhanvien.php?id=<?= $row['user_id'] ?>" onclick="return confirm('Mở lại tài khoản này?')">🔓 Mở cấm</a>
                    <?php endif; ?>
                    <a class="edit" href="./suataikhoan.php?id=<?= $row['user_id'] ?>">✏️ Sửa</a>
                    <a class="delete" href="./xoanhanvien.php?id=<?= $row['user_id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa tài khoản này?')">❌ Xóa</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>

        <!-- Nút Thêm Nhân Viên nằm ở giữa và bên dưới -->
        <div class="button-container">
            <a href="./themnhanvien.php" class="add-button">Thêm nhân viên</a>
        </div>
    </main>

</body>
</html>
