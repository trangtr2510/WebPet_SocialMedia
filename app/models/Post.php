<?php
class Post {
    private $conn;
    private $table = "posts";
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Get all posts
    public function getAll() {
        $query = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        return $result;
    }

    // Get all published posts
    public function getPublishedPosts() {
        $query = "SELECT * FROM {$this->table} WHERE status = 'đã xuất bản' ORDER BY created_at DESC";
        $result = $this->conn->query($query);
        return $result;
    }
    
    // Get posts by status with pagination
    public function getByStatus($status, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $query = "SELECT p.*, u.full_name as author_name, u.img as author_avatar 
                  FROM {$this->table} p 
                  LEFT JOIN users u ON p.author_id = u.user_id 
                  WHERE p.status = ? 
                  ORDER BY p.created_at DESC 
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sii", $status, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get pending posts (chờ xác nhận)
    public function getPendingPosts($page = 1, $limit = 10) {
        return $this->getByStatus('chờ xác nhận', $page, $limit);
    }
    
    // Get approved posts (đã xuất bản)
    public function getApprovedPosts($page = 1, $limit = 10) {
        return $this->getByStatus('đã xuất bản', $page, $limit);
    }
    
    // Get refused posts (từ chối)
    public function getRefusedPosts($page = 1, $limit = 10) {
        return $this->getByStatus('từ chối', $page, $limit);
    }
    
    // Count posts by status
    public function countByStatus($status) {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $status);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    
    // Count pending posts
    public function countPendingPosts() {
        return $this->countByStatus('chờ xác nhận');
    }
    
    // Count approved posts
    public function countApprovedPosts() {
        return $this->countByStatus('đã xuất bản');
    }
    
    // Count refused posts
    public function countRefusedPosts() {
        return $this->countByStatus('từ chối');
    }
    
    // Search posts with filters
    public function searchPosts($search = '', $type = '', $sort = 'created_at', $status = '', $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        
        $query = "SELECT p.*, u.full_name as author_name, u.img as author_avatar 
                  FROM {$this->table} p 
                  LEFT JOIN users u ON p.author_id = u.user_id 
                  WHERE 1=1";
        
        $params = [];
        $types = "";
        
        if (!empty($search)) {
            $query .= " AND (p.title LIKE ? OR p.content LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $types .= "ss";
        }
        
        if (!empty($type)) {
            $query .= " AND p.post_type = ?";
            $params[] = $type;
            $types .= "s";
        }
        
        if (!empty($status)) {
            $query .= " AND p.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        // Add sorting
        $allowedSorts = ['created_at', 'title', 'author_id', 'like_count'];
        if (in_array($sort, $allowedSorts)) {
            $query .= " ORDER BY p.{$sort} DESC";
        } else {
            $query .= " ORDER BY p.created_at DESC";
        }
        
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
    
    // Get single post by ID
    public function getById($id) {
        $query = "SELECT p.*, u.full_name as author_name, u.img as author_avatar 
                  FROM {$this->table} p 
                  LEFT JOIN users u ON p.author_id = u.user_id 
                  WHERE p.post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    // Create new post
    public function create($data) {
        $query = "INSERT INTO {$this->table} (title, content, post_type, featured_image, author_id, published_at, status)
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $status = $data['status'] ?? 'chờ xác nhận';
        $stmt->bind_param(
            "ssssiss",
            $data['title'],
            $data['content'],
            $data['post_type'],
            $data['featured_image'],
            $data['author_id'],
            $data['published_at'],
            $status
        );
        return $stmt->execute();
    }

    public function getUserPostsByStatus($userId, $status) {
        $query = "SELECT * FROM {$this->table} WHERE author_id = ? AND status = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            // Optional: Handle error
            return false;
        }

        $stmt->bind_param("is", $userId, $status);
        $stmt->execute();
        $result = $stmt->get_result();

        $posts = [];
        while ($row = $result->fetch_assoc()) {
            $posts[] = $row;
        }

        return $posts;
    }
    
    // Get post by ID
    public function getPostById($id) {
        $query = "SELECT p.*, u.full_name as author_name, u.img as author_avatar
                FROM {$this->table} p
                LEFT JOIN users u ON p.author_id = u.user_id
                WHERE p.post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->fetch_assoc();
    }

    // Update post (cải tiến version cũ)
    public function update($id, $data) {
        $query = "UPDATE {$this->table}
                SET title=?, content=?, post_type=?, status=?, featured_image=?, published_at=?
                WHERE post_id=?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "ssssssi",
            $data['title'],
            $data['content'],
            $data['post_type'],
            $data['status'],
            $data['featured_image'],
            $data['published_at'],
            $id
        );
        return $stmt->execute();
    }

    // Kiểm tra quyền sở hữu post
    public function isPostOwner($postId, $userId) {
        $query = "SELECT author_id FROM {$this->table} WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return $row['author_id'] == $userId;
        }
        
        return false;
    }
    
    // Update post status
    public function updateStatus($id, $status) {
        $query = "UPDATE {$this->table} SET status = ? WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $status, $id);
        return $stmt->execute();
    }
    
    // Bulk update status - with validation
    public function bulkUpdateStatus($ids, $newStatus) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->conn->prepare("UPDATE posts SET status = ? WHERE post_id IN ($placeholders)");
        $params = array_merge([$newStatus], $ids);
        $stmt->bind_param(str_repeat('s', 1) . str_repeat('i', count($ids)), ...$params);
        return $stmt->execute();
    }
    
    // Delete post
    public function delete($id) {
        $query = "DELETE FROM {$this->table} WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    // Bulk delete - with validation
    public function bulkDelete($ids) {
        if (empty($ids) || !is_array($ids)) return false;
        
        // Validate IDs
        $validIds = array_filter($ids, function($id) {
            return is_numeric($id) && $id > 0;
        });
        
        if (empty($validIds)) return false;
        
        $placeholders = str_repeat('?,', count($validIds) - 1) . '?';
        $query = "DELETE FROM {$this->table} WHERE post_id IN ({$placeholders})";
        $stmt = $this->conn->prepare($query);
        
        $types = str_repeat('i', count($validIds));
        $stmt->bind_param($types, ...$validIds);
        
        return $stmt->execute();
    }
    
    // Check if post exists
    public function exists($id) {
        $query = "SELECT 1 FROM {$this->table} WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->num_rows > 0;
    }
    
    // Get post status
    public function getStatus($id) {
        $query = "SELECT status FROM {$this->table} WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result ? $result['status'] : null;
    }

     // Get like count for a post
    public function getLikeCount($postId) {
        $query = "SELECT COUNT(*) as like_count FROM post_likes WHERE post_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['like_count'];
    }
    
    // Check if user has liked a post
    public function isLikedByUser($postId, $userId) {
        $query = "SELECT * FROM post_likes WHERE post_id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $postId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    
    // Get published posts with like count
    public function getPublishedPostsWithLikes() {
        $query = "SELECT p.*, 
                  COUNT(pl.post_id) as like_count 
                  FROM posts p 
                  LEFT JOIN post_likes pl ON p.post_id = pl.post_id 
                  WHERE p.status = 'đã xuất bản' 
                  GROUP BY p.post_id 
                  ORDER BY p.published_at DESC";
        
        $result = $this->conn->query($query);
        return $result;
    }
    
    // Toggle like for a post
    public function toggleLike($postId, $userId) {
        // Check if already liked
        if ($this->isLikedByUser($postId, $userId)) {
            // Unlike
            $query = "DELETE FROM post_likes WHERE post_id = ? AND user_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $postId, $userId);
            $stmt->execute();
            return false; // unliked
        } else {
            // Like
            $query = "INSERT INTO post_likes (post_id, user_id) VALUES (?, ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $postId, $userId);
            $stmt->execute();
            return true; // liked
        }
    }

    /**
     * Get all liked posts by a specific user
     * @param int $userId
     * @return mysqli_result|false
     */
    public function getLikedPostsByUser($userId) {
        $query = "SELECT p.*, 
                  pl.liked_at,
                  u.full_name as author_name,
                  u.img as author_avatar,
                  COUNT(pl2.post_id) as like_count
                  FROM posts p 
                  INNER JOIN post_likes pl ON p.post_id = pl.post_id 
                  INNER JOIN users u ON p.author_id = u.user_id
                  LEFT JOIN post_likes pl2 ON p.post_id = pl2.post_id
                  WHERE pl.user_id = ? AND p.status = 'đã xuất bản'
                  GROUP BY p.post_id, pl.liked_at
                  ORDER BY pl.liked_at DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return $stmt->get_result();
    }
    
    /**
     * Get liked posts count for a user
     * @param int $userId
     * @return int
     */
    public function getLikedPostsCount($userId) {
        $query = "SELECT COUNT(*) as count 
                  FROM post_likes pl 
                  INNER JOIN posts p ON pl.post_id = p.post_id 
                  WHERE pl.user_id = ? AND p.status = 'đã xuất bản'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['count'];
    }
    
    /**
     * Get liked posts with pagination
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return mysqli_result|false
     */
    public function getLikedPostsPaginated($userId, $limit = 10, $offset = 0) {
        $query = "SELECT p.*, 
                  pl.liked_at,
                  u.full_name as author_name,
                  u.img as author_avatar,
                  COUNT(pl2.post_id) as like_count
                  FROM posts p 
                  INNER JOIN post_likes pl ON p.post_id = pl.post_id 
                  INNER JOIN users u ON p.author_id = u.user_id
                  LEFT JOIN post_likes pl2 ON p.post_id = pl2.post_id
                  WHERE pl.user_id = ? AND p.status = 'đã xuất bản'
                  GROUP BY p.post_id, pl.liked_at
                  ORDER BY pl.liked_at DESC
                  LIMIT ? OFFSET ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iii", $userId, $limit, $offset);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Search posts by keyword and status
    public function searchPosts_Customer($keyword, $status = null, $page = 1, $limit = 10) {
        $offset = ($page - 1) * $limit;
        $searchTerm = '%' . $keyword . '%';
        
        $query = "SELECT p.*, u.full_name as author_name, u.img as author_avatar
          FROM {$this->table} p
          LEFT JOIN users u ON p.author_id = u.user_id
          WHERE (p.title LIKE ? OR p.content LIKE ?)";
        $params = [$searchTerm, $searchTerm];
        $types = "ss";
        
        if ($status) {
            $query .= " AND p.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        // Chỉ hiển thị bài viết của user hiện tại nếu không phải admin
        if (!$this->isAdmin()) {
            $query .= " AND p.author_id = ?";
            $params[] = $_SESSION['user_id'];
            $types .= "i";
        }
        
        $query .= " ORDER BY p.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Count search results
    public function countSearchResults($keyword, $status = null) {
        $searchTerm = '%' . $keyword . '%';
        
        $query = "SELECT COUNT(*) as total FROM {$this->table} p
          WHERE (p.title LIKE ? OR p.content LIKE ?)";
        $params = [$searchTerm, $searchTerm];
        $types = "ss";
        
        if ($status) {
            $query .= " AND p.status = ?";
            $params[] = $status;
            $types .= "s";
        }
        
        // Chỉ đếm bài viết của user hiện tại nếu không phải admin
        if (!$this->isAdmin()) {
            $query .= " AND p.author_id = ?";
            $params[] = $_SESSION['user_id'];
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    // Count posts by status
    public function countByStatus_Customer($status) {
        $query = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = ?";
        $params = [$status];
        $types = "s";
        
        // Chỉ đếm bài viết của user hiện tại nếu không phải admin
        if (!$this->isAdmin()) {
            $query .= " AND author_id = ?";
            $params[] = $_SESSION['user_id'];
            $types .= "i";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc()['total'];
    }

    // Check if current user is admin
    private function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}