<?php
include(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../models/Product.php');
require_once(__DIR__ . '/../models/Category.php');
require_once(__DIR__ . '/../models/User.php');
require_once(__DIR__ . '/../models/ProductImage.php');
require_once(__DIR__ . '/../models/OrderItem.php');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class FilterController {
    private $product;
    private $category;
    private $productImage;
    private $conn;
    private $user;
    private $orderItemModel;

    public function __construct($db) {
        $this->conn = $db;
        $this->product = new Product($db);
        $this->user = new User($db);
        $this->category = new Category($db);
        $this->productImage = new ProductImage($db);
        $this->orderItemModel = new OrderItem($db);
    }

    /**
     * Lọc sản phẩm theo các tiêu chí
     */
    public function filterProducts($filters = []) {
        try {
            // Log filters for debugging
            error_log("FilterProducts called with filters: " . json_encode($filters));
            
            // Base query - Kiểm tra bảng có tồn tại không
            $query = "SELECT DISTINCT p.*, pi.image_url
                     FROM products p 
                     LEFT JOIN product_images pi ON p.product_id = pi.product_id 
                     WHERE p.is_active = 1";
            
            $params = [];
            $whereConditions = [];

            // Filter by gender
            if (!empty($filters['gender']) && is_array($filters['gender'])) {
                $genderConditions = [];
                foreach ($filters['gender'] as $gender) {
                    if (!empty($gender)) {
                        $genderConditions[] = "(p.material LIKE ? OR p.description LIKE ?)";
                        $params[] = "%$gender%";
                        $params[] = "%$gender%";
                    }
                }
                if (!empty($genderConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $genderConditions) . ")";
                }
            }

            // Filter by color
            if (!empty($filters['color']) && is_array($filters['color'])) {
                $colorConditions = [];
                foreach ($filters['color'] as $color) {
                    if (!empty($color)) {
                        $colorConditions[] = "(p.description LIKE ? OR p.product_name LIKE ?)";
                        $params[] = "%$color%";
                        $params[] = "%$color%";
                    }
                }
                if (!empty($colorConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $colorConditions) . ")";
                }
            }

            // Filter by price range
            if (isset($filters['min_price']) && is_numeric($filters['min_price']) && $filters['min_price'] > 0) {
                $whereConditions[] = "p.price >= ?";
                $params[] = floatval($filters['min_price']);
            }
            if (isset($filters['max_price']) && is_numeric($filters['max_price']) && $filters['max_price'] > 0) {
                $whereConditions[] = "p.price <= ?";
                $params[] = floatval($filters['max_price']);
            }

            // Filter by breed/size
            if (!empty($filters['breed']) && is_array($filters['breed'])) {
                $breedConditions = [];
                foreach ($filters['breed'] as $breed) {
                    if (!empty($breed)) {
                        $breedConditions[] = "p.size = ?";
                        $params[] = $breed;
                    }
                }
                if (!empty($breedConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $breedConditions) . ")";
                }
            }

            // Filter by category
            if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
                $whereConditions[] = "p.category_id = ?";
                $params[] = intval($filters['category_id']);
            }

            // Add where conditions to query
            if (!empty($whereConditions)) {
                $query .= " AND " . implode(" AND ", $whereConditions);
            }

            // Sorting
            $orderBy = $this->getSortOrder($filters['sort'] ?? 'popular');
            $query .= " ORDER BY " . $orderBy;

            // Pagination
            $limit = isset($filters['limit']) && is_numeric($filters['limit']) ? intval($filters['limit']) : 12;
            $page = isset($filters['page']) && is_numeric($filters['page']) ? intval($filters['page']) : 1;
            $offset = ($page - 1) * $limit;
            
            $query .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            // Log final query for debugging
            error_log("Final query: " . $query);
            error_log("Params: " . json_encode($params));

            // Prepare and execute
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            // Build types string for bind_param
            $types = "";
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= "i";
                } elseif (is_float($param)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            
            $products = [];
            while ($row = $result->fetch_assoc()) {
                $products[] = $row;
            }

            $total = $this->getFilteredProductCount($filters);
            
            error_log("Found " . count($products) . " products, total: " . $total);

            return [
                'success' => true,
                'data' => $products,
                'total' => $total,
                'filters_applied' => $filters
            ];

        } catch (Exception $e) {
            error_log("Filter products error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return [
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lọc sản phẩm: ' . $e->getMessage(),
                'data' => [],
                'debug' => [
                    'error' => $e->getMessage(),
                    'filters' => $filters
                ]
            ];
        }
    }

    /**
     * Đếm tổng số sản phẩm sau khi lọc
     */
    private function getFilteredProductCount($filters = []) {
        try {
            $query = "SELECT COUNT(DISTINCT p.product_id) as total 
                     FROM products p 
                     WHERE p.is_active = 1";
            
            $params = [];
            $whereConditions = [];

            // Apply same filters as filterProducts method
            if (!empty($filters['gender']) && is_array($filters['gender'])) {
                $genderConditions = [];
                foreach ($filters['gender'] as $gender) {
                    if (!empty($gender)) {
                        $genderConditions[] = "(p.material LIKE ? OR p.description LIKE ?)";
                        $params[] = "%$gender%";
                        $params[] = "%$gender%";
                    }
                }
                if (!empty($genderConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $genderConditions) . ")";
                }
            }

            if (!empty($filters['color']) && is_array($filters['color'])) {
                $colorConditions = [];
                foreach ($filters['color'] as $color) {
                    if (!empty($color)) {
                        $colorConditions[] = "(p.description LIKE ? OR p.product_name LIKE ?)";
                        $params[] = "%$color%";
                        $params[] = "%$color%";
                    }
                }
                if (!empty($colorConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $colorConditions) . ")";
                }
            }

            if (isset($filters['min_price']) && is_numeric($filters['min_price']) && $filters['min_price'] > 0) {
                $whereConditions[] = "p.price >= ?";
                $params[] = floatval($filters['min_price']);
            }
            if (isset($filters['max_price']) && is_numeric($filters['max_price']) && $filters['max_price'] > 0) {
                $whereConditions[] = "p.price <= ?";
                $params[] = floatval($filters['max_price']);
            }

            if (!empty($filters['breed']) && is_array($filters['breed'])) {
                $breedConditions = [];
                foreach ($filters['breed'] as $breed) {
                    if (!empty($breed)) {
                        $breedConditions[] = "p.size = ?";
                        $params[] = $breed;
                    }
                }
                if (!empty($breedConditions)) {
                    $whereConditions[] = "(" . implode(" OR ", $breedConditions) . ")";
                }
            }

            if (!empty($filters['category_id']) && is_numeric($filters['category_id'])) {
                $whereConditions[] = "p.category_id = ?";
                $params[] = intval($filters['category_id']);
            }

            // Add where conditions to query
            if (!empty($whereConditions)) {
                $query .= " AND " . implode(" AND ", $whereConditions);
            }

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Count prepare failed: " . $this->conn->error);
            }

            // Build types string
            $types = "";
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= "i";
                } elseif (is_float($param)) {
                    $types .= "d";
                } else {
                    $types .= "s";
                }
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            if (!$stmt->execute()) {
                throw new Exception("Count execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            
            return intval($row['total'] ?? 0);

        } catch (Exception $e) {
            error_log("Get filtered product count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Xác định thứ tự sắp xếp
     */
    private function getSortOrder($sort) {
        switch ($sort) {
            case 'price_low_high':
                return "p.price ASC";
            case 'price_high_low':
                return "p.price DESC";
            case 'newest':
                return "p.created_at DESC";
            case 'name_asc':
                return "p.product_name ASC";
            case 'name_desc':
                return "p.product_name DESC";
            case 'popular':
            default:
                // Sắp xếp theo số lượng đã bán (popular)
                return "(SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.product_id = p.product_id) DESC, p.created_at DESC";
        }
    }

    /**
     * Xử lý AJAX request cho filter
     */
    public function handleFilterRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
            return;
        }

        // Lấy dữ liệu từ request
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
            return;
        }

        // Chuẩn bị filters
        $filters = [];
        
        if (isset($input['gender']) && is_array($input['gender'])) {
            $filters['gender'] = array_filter($input['gender']);
        }
        
        if (isset($input['color']) && is_array($input['color'])) {
            $filters['color'] = array_filter($input['color']);
        }
        
        if (isset($input['min_price']) && is_numeric($input['min_price'])) {
            $filters['min_price'] = floatval($input['min_price']);
        }
        
        if (isset($input['max_price']) && is_numeric($input['max_price'])) {
            $filters['max_price'] = floatval($input['max_price']);
        }
        
        if (isset($input['breed']) && is_array($input['breed'])) {
            $filters['breed'] = array_filter($input['breed']);
        }
        
        if (isset($input['category_id']) && is_numeric($input['category_id'])) {
            $filters['category_id'] = intval($input['category_id']);
        }
        
        if (isset($input['sort'])) {
            $filters['sort'] = $input['sort'];
        }
        
        if (isset($input['page']) && is_numeric($input['page'])) {
            $filters['page'] = intval($input['page']);
        }
        
        if (isset($input['limit']) && is_numeric($input['limit'])) {
            $filters['limit'] = intval($input['limit']);
        }

        // Thực hiện filter
        $result = $this->filterProducts($filters);
        
        // Trả về JSON response
        header('Content-Type: application/json');
        echo json_encode($result);
    }

    /**
     * Lấy danh sách các option cho filter
     */
    public function getFilterOptions() {
        try {
            // Lấy các màu sắc có sẵn
            $colors = ['Red', 'Apricot', 'Black', 'Black & White', 'Silver', 'Tan'];
            
            // Lấy các size có sẵn
            $sizes = ['Small', 'Medium', 'Large'];
            
            // Lấy gender options
            $genders = ['Male', 'Female'];
            
            // Lấy price range
            $priceQuery = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM products WHERE is_active = 1";
            $result = $this->conn->query($priceQuery);
            $priceRange = $result->fetch_assoc();
            
            return [
                'success' => true,
                'data' => [
                    'colors' => $colors,
                    'sizes' => $sizes,
                    'genders' => $genders,
                    'price_range' => $priceRange
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Get filter options error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Có lỗi khi lấy options cho filter'
            ];
        }
    }
}

// XỬ LÝ ROUTING KHI ĐƯỢC GỌI TRỰC TIẾP
if (basename($_SERVER['PHP_SELF']) === 'FilterController.php') {
    $controller = new FilterController($conn);
    $action = $_GET['action'] ?? 'filter';
    
    switch ($action) {
        case 'filter':
            $controller->handleFilterRequest();
            break;
            
        case 'options':
            $result = $controller->getFilterOptions();
            header('Content-Type: application/json');
            echo json_encode($result);
            break;
            
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Action not found']);
            break;
    }
}
?>