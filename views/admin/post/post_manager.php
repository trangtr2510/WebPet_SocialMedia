<?php
include(__DIR__ . '/../../../config/config.php');
require_once(__DIR__ .'/../../../app/models/Post.php');
require_once(__DIR__ .'/../../../app/models/User.php');

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$user_type = $is_logged_in ? $_SESSION['user_type'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$postModel = new Post($conn);
$userModel = new User($conn);

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../../views/auth/login_register.php");
    exit;
}

// Lấy thông tin người dùng
$user = $userModel->getUserById($_SESSION['user_id']);

// Kiểm tra vai trò
if (!$userModel->isAdmin($user) && !$userModel->isEmployee($user)) {
    // Nếu không phải admin hoặc employee thì chuyển về login_register
    header("Location: ../../../views/auth/login_register.php");
    exit;
}

// Lấy thông tin pagination và filter
$page = $_GET['page'] ?? 1;
$limit = 10;
$search = $_GET['search'] ?? '';
$filter_type = $_GET['filter_type'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'created_at';
$tab = $_GET['tab'] ?? 'pending';

// Lấy dữ liệu cho tab pending
$pendingPosts = $postModel->searchPosts($search, $filter_type, $sort_by, 'chờ xác nhận', $page, $limit);
$pendingCount = $postModel->countPendingPosts();

// Lấy dữ liệu cho tab approved
$approvedPosts = $postModel->searchPosts($search, $filter_type, $sort_by, 'đã xuất bản', $page, $limit);
$approvedCount = $postModel->countApprovedPosts();

// Lấy dữ liệu cho tab refused
$refusedPosts = $postModel->searchPosts($search, $filter_type, $sort_by, 'từ chối', $page, $limit);
$refusedCount = $postModel->countRefusedPosts();

// Tính toán pagination
$totalPendingPages = ceil($pendingCount / $limit);
$totalApprovedPages = ceil($approvedCount / $limit);
$totalRefusedPages = ceil($refusedCount / $limit);

// Helper function để format ngày
function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

// Helper function để truncate text
function truncateText($text, $length = 100) {
    return strlen($text) > $length ? substr($text, 0, $length) . '...' : $text;
}

// Helper function để lấy đường dẫn ảnh
function getImagePath($image) {
    return !empty($image) ? "../../../public/uploads/post/" . $image : "../../../public/uploads/post/default.jpg";
}

// Helper function để lấy avatar path
function getAvatarPath($avatar) {
    return !empty($avatar) ? "../../../public/uploads/avatar/" . $avatar : "../../../public/uploads/avatar/default.jpg";
}

// Function để kiểm tra hành động nào có thể thực hiện với post
function getAvailableActions($status) {
    switch ($status) {
        case 'chờ xác nhận':
            return ['approve', 'refusal', 'delete'];
        case 'đã xuất bản':
            return ['refusal', 'delete'];
        case 'từ chối':
            return ['approve', 'delete'];
        default:
            return ['delete'];
    }
}

// Function để kiểm tra xem có thể thực hiện bulk action không
function canPerformBulkAction($tab, $action) {
    $allowedActions = [
        'pending' => ['approve', 'refusal', 'delete'],
        'approved' => ['refusal', 'delete'],
        'refused' => ['approve', 'delete']
    ];
    
    return isset($allowedActions[$tab]) && in_array($action, $allowedActions[$tab]);
}

// Function để lấy số trang total dựa vào tab
function getTotalPages($tab, $totalPendingPages, $totalApprovedPages, $totalRefusedPages) {
    switch ($tab) {
        case 'pending':
            return $totalPendingPages;
        case 'approved':
            return $totalApprovedPages;
        case 'refused':
            return $totalRefusedPages;
        default:
            return $totalPendingPages;
    }
}

$totalPages = getTotalPages($tab, $totalPendingPages, $totalApprovedPages, $totalRefusedPages);
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Management Interface</title>
    <link rel="stylesheet" href="../../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../../public/css/admin/admin.css">
  <script type="text/javascript" src="../../../public/js/admin.js" defer></script>
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
            <li>
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
            <li class="active">
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

    <div class="pm-container">
        <h2 style="margin: 1rem 0rem; color: #003459;">Post Manager</h2>
        
        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="success-message">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <!-- Tab Navigation -->
        <div class="pm-tab-navigation">
            <button class="pm-tab-nav-item <?php echo $tab === 'pending' ? 'pm-active' : ''; ?>" onclick="showTab('pending')">
                Chờ xét duyệt <span class="count-badge"><?php echo $pendingCount; ?></span>
            </button>
            <button class="pm-tab-nav-item <?php echo $tab === 'approved' ? 'pm-active' : ''; ?>" onclick="showTab('approved')">
                Đã xét duyệt <span class="count-badge"><?php echo $approvedCount; ?></span>
            </button>
            <button class="pm-tab-nav-item <?php echo $tab === 'refused' ? 'pm-active' : ''; ?>" onclick="showTab('refused')">
                Từ chối <span class="count-badge"><?php echo $refusedCount; ?></span>
            </button>
        </div>

        <!-- Search and Filter Form -->
        <form method="GET" action="post_manager.php">
            <input type="hidden" name="tab" value="<?php echo $tab; ?>">
            <div class="pm-posts-filters">
                <div class="pm-search-box">
                    <input type="text" name="search" placeholder="Type any keyword to search" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <div>
                    <span>Filter By</span>
                    <select name="filter_type" class="pm-filter-select">
                        <option value="">All Types</option>
                        <option value="news" <?php echo $filter_type === 'news' ? 'selected' : ''; ?>>News</option>
                        <option value="discussion" <?php echo $filter_type === 'discussion' ? 'selected' : ''; ?>>Discussion</option>
                    </select>
                </div>
                <div>
                    <span>Sort By</span>
                    <select name="sort_by" class="pm-filter-select">
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Colums</option>
                        <option value="title" <?php echo $sort_by === 'title' ? 'selected' : ''; ?>>Title</option>
                        <option value="author_id" <?php echo $sort_by === 'author_id' ? 'selected' : ''; ?>>Author</option>
                        <option value="like_count" <?php echo $sort_by === 'like_count' ? 'selected' : ''; ?>>Likes</option>
                    </select>
                </div>
                <button type="submit" class="pm-apply-btn">Apply</button>
                <a href="post_manager.php?tab=<?php echo $tab; ?>" class="pm-reset-btn">Reset</a>
            </div>
        </form>

        <!-- Pending Posts Tab -->
        <div id="pending-tab" class="pm-tab-content <?php echo $tab === 'pending' ? 'pm-active' : ''; ?>" style="display: <?php echo $tab === 'pending' ? 'block' : 'none'; ?>;">
            <div class="pm-community-header">
                <span class="pm-breadcrumb">Post</span> / Chờ xét duyệt
            </div>

            <form method="POST" action="../../../app/controllers/PostController.php" id="pending-form">
                <input type="hidden" name="action" value="">
                <input type="hidden" name="tab" value="pending">
                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                
                <div class="pm-table-controls">
                    <div class="pm-bulk-select">
                        <label>
                            <input type="checkbox" id="select-all-pending" onchange="toggleSelectAll('pending')">
                            Check all (<span id="selected-count-pending">0</span>)
                        </label>
                    </div>
                    <div class="pm-bulk-actions">
                        <span>With selected:</span>
                        <button type="button" class="pm-edit-btn" onclick="bulkAction('approve')" title="Approve selected posts">
                            <i class="fa-solid fa-check"></i> Accept
                        </button>
                        <button type="button" class="pm-delete-btn" onclick="bulkAction('refusal')" title="Refuse selected posts">
                            <i class="fa-solid fa-ban"></i> Refusal
                        </button>
                        <button type="button" class="pm-delete-btn" onclick="bulkAction('delete')" title="Delete selected posts">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>

                <div class="pm-table-container">
                    <table class="pm-data-table">
                        <thead>
                            <tr>
                                <th class="pm-checkbox"></th>
                                <th class="pm-post-id">ID</th>
                                <th>Ảnh</th>
                                <th>Tiêu đề</th>
                                <th>Nội dung</th>
                                <th>Loại</th>
                                <th class="pm-status">Trạng thái</th>
                                <th class="pm-like-count">Likes</th>
                                <th>Tác giả</th>
                                <th class="pm-dates">Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($post = $pendingPosts->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="post_ids[]" value="<?php echo $post['post_id']; ?>" class="post-checkbox-pending">
                                </td>
                                <td><?php echo $post['post_id']; ?></td>
                                <td>
                                    <img src="<?php echo getImagePath($post['featured_image']); ?>" alt="Post Image" class="post-image">
                                </td>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo truncateText(htmlspecialchars($post['content'])); ?></td>
                                <td><?php echo htmlspecialchars($post['post_type']); ?></td>
                                <td>
                                    <span class="status-badge status-pending">
                                        <?php echo $post['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $post['like_count'] ?? 0; ?></td>
                                <td>
                                    <div class="author-info">
                                        <img src="<?php echo getAvatarPath($post['author_avatar']); ?>" alt="Author Avatar" class="author-avatar">
                                        <span><?php echo htmlspecialchars($post['author_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo formatDate($post['created_at']); ?></td>
                                <td>
                                    <div class="pm-post-actions">
                                        <div class="action-buttons">
                                            <a href="../../../app/controllers/PostController.php?action=single_approve&id=<?php echo $post['post_id']; ?>&tab=pending&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-approve" title="Approve">
                                               <i class="fa-solid fa-check"></i> Accept
                                            </a>
                                            <a href="../../../app/controllers/PostController.php?action=single_refusal&id=<?php echo $post['post_id']; ?>&tab=pending&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-delete" title="Refuse" onclick="return confirm('Are you sure?')">
                                               <i class="fa-solid fa-ban"></i> Refusal
                                            </a>
                                            <a href="../../../app/controllers/PostController.php?action=single_delete&id=<?php echo $post['post_id']; ?>&tab=pending&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-delete" title="Delete" onclick="return confirm('Are you sure?')">
                                               <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <!-- <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewPost(<?php echo $post['post_id']; ?>)" title="View">
                                                <i class="fas fa-eye"></i> Details
                                            </button> -->
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Pagination for Pending -->
            <div class="pagination pm-pagination">
                <a href="?tab=pending&page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo $filter_type; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-angle-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPendingPages; ?></span>
                <a href="?tab=pending&page=<?php echo min($totalPendingPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo $filter_type; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page >= $totalPendingPages ? 'disabled' : ''; ?>">
                    Next <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>

        <!-- Approved Posts Tab -->
        <div id="approved-tab" class="pm-tab-content <?php echo $tab === 'approved' ? 'pm-active' : ''; ?>" style="display: <?php echo $tab === 'approved' ? 'block' : 'none'; ?>;">
            <div class="pm-community-header">
                <span class="pm-breadcrumb">Post</span> / Đã xét duyệt
            </div>

            <form method="POST" action="../../../app/controllers/PostController.php" id="approved-form">
                <input type="hidden" name="action" value="">
                <input type="hidden" name="tab" value="approved">
                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                
                <div class="pm-table-controls">
                    <div class="pm-bulk-select">
                        <label>
                            <input type="checkbox" id="select-all-approved" onchange="toggleSelectAll('approved')">
                            Check all (<span id="selected-count-approved">0</span>)
                        </label>
                    </div>
                    <div class="pm-bulk-actions">
                        <span>With selected:</span>
                        <!-- <button type="button" class="pm-delete-btn" onclick="bulkAction('refusal')" title="Refuse selected posts">
                            <i class="fa-solid fa-ban"></i> Refusal
                        </button> -->
                        <button type="button" class="pm-delete-btn" onclick="bulkAction('delete')" title="Delete selected posts">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                        <!-- <button type="button" class="pm-export-btn" onclick="exportSelected()">
                            <i class="fas fa-download"></i> Export
                        </button> -->
                    </div>
                </div>

                <div class="pm-table-container">
                    <table class="pm-data-table">
                        <thead>
                            <tr>
                                <th class="pm-checkbox"></th>
                                <th class="pm-post-id">ID</th>
                                <th>Ảnh</th>
                                <th>Tiêu đề</th>
                                <th>Nội dung</th>
                                <th>Loại</th>
                                <th class="pm-status">Trạng thái</th>
                                <th class="pm-like-count">Likes</th>
                                <th>Tác giả</th>
                                <th class="pm-dates">Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($post = $approvedPosts->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="post_ids[]" value="<?php echo $post['post_id']; ?>" class="post-checkbox-approved">
                                </td>
                                <td><?php echo $post['post_id']; ?></td>
                                <td>
                                    <img src="<?php echo getImagePath($post['featured_image']); ?>" alt="Post Image" class="post-image">
                                </td>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo truncateText(htmlspecialchars($post['content'])); ?></td>
                                <td><?php echo htmlspecialchars($post['post_type']); ?></td>
                                <td>
                                    <span class="status-badge status-approved">
                                        <?php echo $post['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $post['like_count'] ?? 0; ?></td>
                                <td>
                                    <div class="author-info">
                                        <img src="<?php echo getAvatarPath($post['author_avatar']); ?>" alt="Author Avatar" class="author-avatar">
                                        <span><?php echo htmlspecialchars($post['author_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo formatDate($post['created_at']); ?></td>
                                <td>
                                    <div class="pm-post-actions">
                                        <div class="action-buttons">
                                            <!-- <a href="post_manager.php?action=single_refusal&id=<?php echo $post['post_id']; ?>&tab=approved&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-delete" title="Refuse" onclick="return confirm('Are you sure?')">
                                               <i class="fa-solid fa-ban"></i> Refusal
                                            </a> -->
                                            <a href="post_manager.php?action=single_delete&id=<?php echo $post['post_id']; ?>&tab=approved&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-delete" title="Delete" onclick="return confirm('Are you sure?')">
                                               <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <!-- <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewPost(<?php echo $post['post_id']; ?>)" title="View">
                                                <i class="fas fa-eye"></i> Details
                                            </button> -->
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Pagination for Approved -->
            <div class="pagination pm-pagination">
                <a href="?tab=approved&page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo $filter_type; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-angle-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalApprovedPages; ?></span>
                <a href="?tab=approved&page=<?php echo min($totalApprovedPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo $filter_type; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page >= $totalApprovedPages ? 'disabled' : ''; ?>">
                    Next <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>

        <!-- Refused Posts Tab -->
        <div id="refused-tab" class="pm-tab-content <?php echo $tab === 'refused' ? 'pm-active' : ''; ?>" style="display: <?php echo $tab === 'refused' ? 'block' : 'none'; ?>;">
            <div class="pm-community-header">
                <span class="pm-breadcrumb">Post</span> / Từ chối
            </div>

            <form method="POST" action="../../../app/controllers/PostController.php" id="pending-form">
                <input type="hidden" name="action" value="">
                <input type="hidden" name="tab" value="pending">
                <input type="hidden" name="redirect_url" value="<?php echo $_SERVER['REQUEST_URI']; ?>">
                
                <div class="pm-table-controls">
                    <div class="pm-bulk-select">
                        <label>
                            <input type="checkbox" id="select-all-pending" onchange="toggleSelectAll('pending')">
                            Check all (<span id="selected-count-pending">0</span>)
                        </label>
                    </div>
                    <div class="pm-bulk-actions">
                        <span>With selected:</span>
                        <button type="button" class="pm-edit-btn" onclick="bulkAction('approve')" title="Approve selected posts">
                            <i class="fa-solid fa-check"></i> Accept
                        </button>
                        <!-- <button type="button" class="pm-delete-btn" onclick="bulkAction('refusal')" title="Refuse selected posts">
                            <i class="fa-solid fa-ban"></i> Refusal
                        </button> -->
                        <button type="button" class="pm-delete-btn" onclick="bulkAction('delete')" title="Delete selected posts">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>

                <div class="pm-table-container">
                    <table class="pm-data-table">
                        <thead>
                            <tr>
                                <th class="pm-checkbox"></th>
                                <th class="pm-post-id">ID</th>
                                <th>Ảnh</th>
                                <th>Tiêu đề</th>
                                <th>Nội dung</th>
                                <th>Loại</th>
                                <th class="pm-status">Trạng thái</th>
                                <th class="pm-like-count">Likes</th>
                                <th>Tác giả</th>
                                <th class="pm-dates">Ngày tạo</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($post = $refusedPosts->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="post_ids[]" value="<?php echo $post['post_id']; ?>" class="post-checkbox-pending">
                                </td>
                                <td><?php echo $post['post_id']; ?></td>
                                <td>
                                    <img src="<?php echo getImagePath($post['featured_image']); ?>" alt="Post Image" class="post-image">
                                </td>
                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                <td><?php echo truncateText(htmlspecialchars($post['content'])); ?></td>
                                <td><?php echo htmlspecialchars($post['post_type']); ?></td>
                                <td>
                                    <span class="status-badge status-pending">
                                        <?php echo $post['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $post['like_count'] ?? 0; ?></td>
                                <td>
                                    <div class="author-info">
                                        <img src="<?php echo getAvatarPath($post['author_avatar']); ?>" alt="Author Avatar" class="author-avatar">
                                        <span><?php echo htmlspecialchars($post['author_name']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo formatDate($post['created_at']); ?></td>
                                <td>
                                    <div class="pm-post-actions">
                                        <div class="action-buttons">
                                            <a href="../../../app/controllers/PostController.php?action=single_approve&id=<?php echo $post['post_id']; ?>&tab=pending&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-approve" title="Approve">
                                               <i class="fa-solid fa-check"></i> Accept
                                            </a>
                                            <a href="../../../app/controllers/PostController.php?action=single_delete&id=<?php echo $post['post_id']; ?>&tab=pending&redirect_url=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>" class="btn-small pm-action-btn pm-delete" title="Delete" onclick="return confirm('Are you sure?')">
                                               <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <button type="button" class="btn-small pm-action-btn btn-view" onclick="viewPost(<?php echo $post['post_id']; ?>)" title="View">
                                                <i class="fas fa-eye"></i> Details
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </form>

            <!-- Pagination for Pending -->
            <div class="pagination pm-pagination">
                <a href="?tab=pending&page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo $filter_type; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fa-solid fa-angle-left"></i> Previous
                </a>
                <span class="page-info">Page <?php echo $page; ?> of <?php echo $totalPendingPages; ?></span>
                <a href="?tab=pending&page=<?php echo min($totalPendingPages, $page + 1); ?>&search=<?php echo urlencode($search); ?>&filter_type=<?php echo $filter_type; ?>&sort_by=<?php echo $sort_by; ?>" 
                   class="pm-page-btn page-btn <?php echo $page >= $totalPendingPages ? 'disabled' : ''; ?>">
                    Next <i class="fa-solid fa-angle-right"></i>
                </a>
            </div>
        </div>
    </div>
    <!-- Post Detail Modal -->
    <div id="post-detail-modal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Post Details</h2>
            <div id="post-detail-content">
                <!-- Post details will be loaded here -->
            </div>
        </div>
    </div>

    <script src="../../../public/js/post_manager.js"></script>
    <script>
        function bulkAction(action) {
            document.querySelector('#pending-form input[name="action"]').value = action;
            document.querySelector('#pending-form').submit();
        }
    </script>
</body>

</html>