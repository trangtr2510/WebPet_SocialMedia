<?php
include(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../models/Post.php');
require_once(__DIR__ . '/../models/User.php');

session_start();

$postModel = new Post($conn);
$userModel = new User($conn);

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
// Kiểm tra đăng nhập cho các action cần thiết
function requireAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
}

// Helper function để redirect về trang trước
function redirectBack($defaultUrl = 'post_manager.php') {
    $redirectUrl = $_POST['redirect_url'] ?? $_GET['redirect_url'] ?? $defaultUrl;
    header("Location: " . $redirectUrl);
    exit();
}

// Helper function để set success message
function setSuccessMessage($message) {
    $_SESSION['message'] = $message;
}

// Helper function để set error message
function setErrorMessage($message) {
    $_SESSION['error'] = $message;
}

// Helper function để validate post action dựa trên trạng thái
function canPerformAction($status, $action) {
    $allowedActions = [
        'chờ xác nhận' => ['approve', 'refusal', 'delete'],
        'đã xuất bản' => ['refusal', 'delete'],
        'từ chối' => ['approve', 'delete']
    ];
    
    return isset($allowedActions[$status]) && in_array($action, $allowedActions[$status]);
}

// Helper function để validate IDs
function validateIds($ids) {
    if (empty($ids) || !is_array($ids)) {
        return false;
    }
    return array_filter($ids, function($id) {
        return is_numeric($id) && $id > 0;
    });
}

// Handle file upload
function handleFileUpload() {
    if (empty($_FILES['image'])) return null;
    
    $uploadDir = '../../public/uploads/post/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileName = uniqid() . '_' . basename($_FILES['image']['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
        return $fileName;
    }
    return null;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'like':
        requireAuth();
        header('Content-Type: application/json');
        
        $postId = $_POST['post_id'] ?? null;
        $userId = $_SESSION['user_id'];
        
        if (!$postId || !is_numeric($postId)) {
            echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
            exit();
        }
        
        try {
            // Check if user already liked this post
            $checkQuery = "SELECT * FROM post_likes WHERE user_id = ? AND post_id = ?";
            $checkStmt = $conn->prepare($checkQuery);
            $checkStmt->bind_param("ii", $userId, $postId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows > 0) {
                // Unlike - remove the like
                $deleteQuery = "DELETE FROM post_likes WHERE user_id = ? AND post_id = ?";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->bind_param("ii", $userId, $postId);
                $deleteStmt->execute();
                $liked = false;
            } else {
                // Like - add the like
                $insertQuery = "INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param("ii", $userId, $postId);
                $insertStmt->execute();
                $liked = true;
            }
            
            // Get updated like count
            $countQuery = "SELECT COUNT(*) as like_count FROM post_likes WHERE post_id = ?";
            $countStmt = $conn->prepare($countQuery);
            $countStmt->bind_param("i", $postId);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $likeCount = $countResult->fetch_assoc()['like_count'];
            
            echo json_encode([
                'success' => true,
                'liked' => $liked,
                'like_count' => $likeCount
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error']);
        }
        exit();
    
    case 'get_liked_posts':
        requireAuth();
        header('Content-Type: application/json');
        
        $userId = $_SESSION['user_id'];
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? max(1, min(50, intval($_GET['limit']))) : 10;
        $offset = ($page - 1) * $limit;
        
        try {
            // Get liked posts
            $likedPosts = $postModel->getLikedPostsPaginated($userId, $limit, $offset);
            $totalCount = $postModel->getLikedPostsCount($userId);
            
            $posts = [];
            while ($post = $likedPosts->fetch_assoc()) {
                // Format data
                $post['liked_at_formatted'] = date('M j, Y \a\t g:i A', strtotime($post['liked_at']));
                $post['published_at_formatted'] = date('M j, Y', strtotime($post['published_at']));
                $post['content_preview'] = strlen($post['content']) > 150 
                    ? substr($post['content'], 0, 150) . '...' 
                    : $post['content'];
                
                // Author avatar path
                $post['author_avatar_path'] = !empty($post['author_avatar']) 
                    ? '../../public/uploads/avatar/' . $post['author_avatar']
                    : '../../public/uploads/avatar/default.jpg';
                
                // Featured image path
                if (!empty($post['featured_image'])) {
                    $post['featured_image_path'] = '../../public/uploads/post/' . $post['featured_image'];
                }
                
                $posts[] = $post;
            }
            
            $response = [
                'success' => true,
                'posts' => $posts,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => ceil($totalCount / $limit),
                    'total_count' => $totalCount,
                    'per_page' => $limit
                ]
            ];
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Error fetching liked posts: ' . $e->getMessage()
            ]);
        }
        exit();

    case 'getUserPostsByStatus':
        requireAuth();
        header('Content-Type: application/json');
       
        try {
            $status = $_GET['status'] ?? 'đã xuất bản';
            $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
           
            // Check permissions: only view own posts (unless admin)
            if ($userId != $_SESSION['user_id'] && !isset($_SESSION['is_admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit();
            }
           
            // Get user posts by status
            $posts = $postModel->getUserPostsByStatus($userId, $status);
            $postsArray = [];
           
            if ($posts && is_array($posts)) {
                foreach ($posts as $row) {
                    // Get author information
                    $postId = $row['post_id']; 
                    $author = $userModel->getUserById($row['author_id']);
                    $row['author_name'] = $author['full_name'] ?? 'Unknown';
                    $row['author_avatar'] = !empty($author['img']) ? $author['img'] : 'default.jpg';
                    
                    // Format dates
                    $row['formatted_date'] = date('M j, Y g:i A', strtotime($row['created_at']));
                    
                    // Get like count for each post
                    $likeQuery = "SELECT COUNT(*) as like_count FROM post_likes WHERE post_id = ?";
                    $likeStmt = $conn->prepare($likeQuery);
                    $likeStmt->bind_param("i", $postId);
                    $likeStmt->execute();
                    $likeResult = $likeStmt->get_result();
                    $row['like_count'] = $likeResult->fetch_assoc()['like_count'] ?? 0;

                    $postsArray[] = $row;
                }
            }
           
            echo json_encode([
                'success' => true,
                'posts' => $postsArray,
                'count' => count($postsArray)
            ]);
           
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
        exit;

    case 'get':
        requireAuth();
        header('Content-Type: application/json');
        
        try {
            $postId = $_GET['id'] ?? null;
            
            if (!$postId) {
                throw new Exception('Post ID is required');
            }
            
            $post = $postModel->getById($postId);
            
            if (!$post) {
                throw new Exception('Post not found');
            }
            
            // Check if user owns the post or is admin
            if ($post['author_id'] != $_SESSION['user_id'] && !isset($_SESSION['is_admin'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                exit();
            }
            
            echo json_encode([
                'success' => true,
                'data' => $post
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    
    case 'search':
        requireAuth();
        
        $keyword = trim($_GET['keyword'] ?? $_POST['keyword'] ?? '');
        $status = $_GET['status'] ?? $_POST['status'] ?? 'đã xuất bản';
        $page = max(1, intval($_GET['page'] ?? $_POST['page'] ?? 1));
        $limit = 10;
        
        header('Content-Type: application/json');
        
        if (empty($keyword)) {
            // Nếu không có keyword, trả về posts theo status thông thường
            $posts = $postModel->getByStatus($status, $page, $limit);
            $totalPosts = $postModel->countByStatus_Customer($status);
        } else {
            // Tìm kiếm với keyword
            $posts = $postModel->searchPosts_Customer($keyword, $status, $page, $limit);
            $totalPosts = $postModel->countSearchResults($keyword, $status);
        }
        
        $postsData = [];
        if ($posts && $posts->num_rows > 0) {
            while ($row = $posts->fetch_assoc()) {
                $postId = $row['post_id'];

                // Get like count for each post
                $likeQuery = "SELECT COUNT(*) as like_count FROM post_likes WHERE post_id = ?";
                $likeStmt = $conn->prepare($likeQuery);
                $likeStmt->bind_param("i", $postId);
                $likeStmt->execute();
                $likeResult = $likeStmt->get_result();
                $row['like_count'] = $likeResult->fetch_assoc()['like_count'] ?? 0;

                $postsData[] = $row;
            }
        }
        
        $totalPages = ceil($totalPosts / $limit);
        
        echo json_encode([
            'success' => true,
            'posts' => $postsData,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_posts' => $totalPosts,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ],
            'search_info' => [
                'keyword' => $keyword,
                'status' => $status,
                'results_count' => count($postsData)
            ]
        ]);
        break;

    case 'approve':
        if (isset($_POST['post_ids'])) {
            $ids = validateIds($_POST['post_ids']);
            if ($ids) {
                // Kiểm tra trạng thái của từng post trước khi approve
                $validIds = [];
                foreach ($ids as $id) {
                    $post = $postModel->getById($id);
                    if ($post && canPerformAction($post['status'], 'approve')) {
                        $validIds[] = $id;
                    }
                }
                
                if (!empty($validIds)) {
                    $success = $postModel->bulkUpdateStatus($validIds, 'đã xuất bản');
                    if ($success) {
                        setSuccessMessage('Đã duyệt ' . count($validIds) . ' bài viết thành công!');
                    } else {
                        setErrorMessage('Có lỗi xảy ra khi duyệt bài viết!');
                    }
                } else {
                    setErrorMessage('Không có bài viết nào hợp lệ để duyệt!');
                }
            } else {
                setErrorMessage('Vui lòng chọn ít nhất một bài viết!');
            }
        }
        redirectBack();
        break;

    case 'refusal':
        if (isset($_POST['post_ids'])) {
            $ids = validateIds($_POST['post_ids']);
            if ($ids) {
                $validIds = [];
                foreach ($ids as $id) {
                    $post = $postModel->getById($id);
                    if ($post && canPerformAction($post['status'], 'refusal')) {
                        $validIds[] = $id;
                    }
                }
                
                if (!empty($validIds)) {
                    $success = $postModel->bulkUpdateStatus($validIds, 'từ chối');
                    if ($success) {
                        setSuccessMessage('Đã từ chối ' . count($validIds) . ' bài viết thành công!');
                    } else {
                        setErrorMessage('Có lỗi xảy ra khi từ chối bài viết!');
                    }
                } else {
                    setErrorMessage('Không có bài viết nào hợp lệ để từ chối!');
                }
            } else {
                setErrorMessage('Vui lòng chọn ít nhất một bài viết!');
            }
        }
        redirectBack();
        break;
        
    case 'delete':
        if (isset($_POST['post_ids'])) {
            $ids = validateIds($_POST['post_ids']);
            if ($ids) {
                $validIds = [];
                foreach ($ids as $id) {
                    $post = $postModel->getById($id);
                    if ($post && canPerformAction($post['status'], 'delete')) {
                        $validIds[] = $id;
                    }
                }
                
                if (!empty($validIds)) {
                    $success = $postModel->bulkDelete($validIds);
                    if ($success) {
                        setSuccessMessage('Đã xóa ' . count($validIds) . ' bài viết thành công!');
                    } else {
                        setErrorMessage('Có lỗi xảy ra khi xóa bài viết!');
                    }
                } else {
                    setErrorMessage('Không có bài viết nào hợp lệ để xóa!');
                }
            } else {
                setErrorMessage('Vui lòng chọn ít nhất một bài viết!');
            }
        }
        redirectBack();
        break;
        
    // Single actions
    case 'single_approve':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $post = $postModel->getById($id);
            
            if ($post && canPerformAction($post['status'], 'approve')) {
                $success = $postModel->updateStatus($id, 'đã xuất bản');
                if ($success) {
                    setSuccessMessage('Đã duyệt bài viết thành công!');
                } else {
                    setErrorMessage('Có lỗi xảy ra khi duyệt bài viết!');
                }
            } else {
                setErrorMessage('Không thể duyệt bài viết này!');
            }
        }
        redirectBack();
        break;
        
    case 'single_refusal':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $post = $postModel->getById($id);
            
            if ($post && canPerformAction($post['status'], 'refusal')) {
                $success = $postModel->updateStatus($id, 'từ chối');
                if ($success) {
                    setSuccessMessage('Đã từ chối bài viết thành công!');
                } else {
                    setErrorMessage('Có lỗi xảy ra khi từ chối bài viết!');
                }
            } else {
                setErrorMessage('Không thể từ chối bài viết này!');
            }
        }
        redirectBack();
        break;
        
    case 'single_delete':
        if (isset($_GET['id'])) {
            $id = intval($_GET['id']);
            $post = $postModel->getById($id);
            
            if ($post && canPerformAction($post['status'], 'delete')) {
                $success = $postModel->delete($id);
                if ($success) {
                    setSuccessMessage('Đã xóa bài viết thành công!');
                } else {
                    setErrorMessage('Có lỗi xảy ra khi xóa bài viết!');
                }
            } else {
                setErrorMessage('Không thể xóa bài viết này!');
            }
        }
        redirectBack();
        break;
    case 'single_Customer_delete':
        requireAuth();
        header('Content-Type: application/json');

        try {
            $postId = $_GET['id'] ?? null;
            $userId = $_SESSION['user_id'];

            if (!$postId || !is_numeric($postId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID bài viết không hợp lệ']);
                exit();
            }

            $post = $postModel->getById($postId);
            if (!$post) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Bài viết không tồn tại']);
                exit();
            }

            // Chỉ cho phép xóa nếu là tác giả bài viết
            if ($post['author_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa bài viết này']);
                exit();
            }

            $deleted = $postModel->delete($postId);
            if ($deleted) {
                echo json_encode(['success' => true, 'message' => 'Đã xóa bài viết thành công']);
            } else {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Xóa bài viết thất bại']);
            }

        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()]);
        }

        exit();
    
    // API endpoints (for JSON responses)
    case 'getAll':
        header('Content-Type: application/json');
        $result = $postModel->getAll();
        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $author = $userModel->getById($row['author_id']);
            $row['author_name'] = $author['full_name'] ?? 'Unknown';
            $row['author_avatar'] = !empty($author['img']) ? $author['img'] : 'default.jpg';
            $posts[] = $row;
        }
        echo json_encode($posts);
        break;

    case 'create':
        header('Content-Type: application/json');
        try {
            $data = [
                'title' => $_POST['title'] ?? '',
                'content' => $_POST['content'] ?? '',
                'post_type' => $_POST['post_type'] ?? 'news',
                'status' => $_POST['status'] ?? 'chờ xác nhận',
                'featured_image' => handleFileUpload(),
                'author_id' => $_SESSION['user_id'],
                'published_at' => date('Y-m-d H:i:s')
            ];
            
            if (empty($data['title']) || empty($data['content'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Title and content are required'
                ]);
                exit();
            }
            
            $success = $postModel->create($data);
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Post created successfully',
                    'post' => $data
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to create post'
                ]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ]);
        }
        break;

    case 'getById':
        header('Content-Type: application/json');
        $id = intval($_GET['id'] ?? 0);
        $post = $postModel->getById($id);
        if ($post) {
            echo json_encode($post);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Post not found']);
        }
        break;
        
    case 'update':
        header('Content-Type: application/json');
        try {
            $id = intval($_GET['id'] ?? 0);
            
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
                exit();
            }
            
            // Lấy thông tin post hiện tại
            $currentPost = $postModel->getPostById($id);
            if (!$currentPost) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Post not found']);
                exit();
            }
            
            // Kiểm tra quyền sở hữu post (nếu cần)
            if (isset($_SESSION['user_id']) && $currentPost['author_id'] != $_SESSION['user_id']) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You can only edit your own posts']);
                exit();
            }
            
            $data = [];
            $data['title'] = $_POST['title'] ?? $currentPost['title'];
            $data['content'] = $_POST['content'] ?? $currentPost['content'];
            $data['post_type'] = $_POST['post_type'] ?? $currentPost['post_type'];
            $data['status'] = "chờ xác nhận" ?? $currentPost['status'];
            $data['published_at'] = $currentPost['published_at']; // Giữ nguyên thời gian publish
            
            // Xử lý upload ảnh mới
            $featured_image = $currentPost['featured_image']; // Giữ ảnh cũ làm mặc định
            
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = '../../public/uploads/post/';
                $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
                $maxSize = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($_FILES['image']['type'], $allowedTypes)) {
                    throw new Exception('Invalid file type. Only JPG, PNG, JPEG allowed.');
                }
                
                if ($_FILES['image']['size'] > $maxSize) {
                    throw new Exception('File too large. Maximum size is 5MB.');
                }
                
                // Tạo tên file unique
                $fileExtension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
                $fileName = 'post_' . $id . '_' . time() . '.' . $fileExtension;
                $filePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['image']['tmp_name'], $filePath)) {
                    // Xóa ảnh cũ nếu có và khác ảnh mặc định
                    if ($currentPost['featured_image'] && file_exists($uploadDir . $currentPost['featured_image'])) {
                        unlink($uploadDir . $currentPost['featured_image']);
                    }
                    $featured_image = $fileName;
                } else {
                    throw new Exception('Failed to upload image.');
                }
            }
            
            $data['featured_image'] = $featured_image;
            
            $result = $postModel->update($id, $data);
            echo json_encode(['success' => $result]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
        break;
    default:
        setErrorMessage('Hành động không hợp lệ!');
        redirectBack();
        break;
}
?>