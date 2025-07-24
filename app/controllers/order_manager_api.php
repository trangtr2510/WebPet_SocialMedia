<?php
/**
 * API endpoint for order management AJAX requests
 * File: order_manager_api.php
 */

require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../controllers/OrderController.php');

// Set JSON header
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Only allow GET and POST requests
$method = $_SERVER['REQUEST_METHOD'];
if (!in_array($method, ['GET', 'POST'])) {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Check if request is AJAX
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
    strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Only AJAX requests allowed']);
    exit;
}

try {
    // Initialize database connection
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Initialize OrderController
    $orderController = new OrderController($db);
    
    // Handle GET requests (filtering, search, pagination)
    if ($method === 'GET') {
        handleGetRequest($orderController);
    }
    
    // Handle POST requests (bulk actions)
    if ($method === 'POST') {
        handlePostRequest($orderController);
    }
    
} catch (Exception $e) {
    error_log("Order Manager API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Server error occurred',
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle GET requests - filtering, search, pagination
 */
function handleGetRequest($orderController) {
    try {
        $tab = $_GET['tab'] ?? 'pending';
        
        // Validate tab
        $validTabs = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($tab, $validTabs)) {
            throw new Exception('Invalid tab specified');
        }
        
        // Get orders using the controller method
        $result = $orderController->getOrdersForManager($tab);
        
        // Format response
        $response = [
            'success' => true,
            'message' => 'Orders loaded successfully',
            'data' => [
                'orders' => formatOrdersForJSON($result['orders']),
                'total_orders' => $result['total_orders'],
                'total_pages' => $result['total_pages'],
                'current_page' => $result['current_page'],
                'search' => $result['search'],
                'sort_by' => $result['sort_by'],
                'range_from' => $result['range_from'],
                'range_to' => $result['range_to']
            ]
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        throw new Exception('Error processing GET request: ' . $e->getMessage());
    }
}

/**
 * Handle POST requests - bulk actions
 */
function handlePostRequest($orderController) {
    try {
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON input');
        }
        
        $action = $input['action'] ?? '';
        $orderIds = $input['order_ids'] ?? [];
        
        if (empty($action) || empty($orderIds)) {
            throw new Exception('Missing required parameters');
        }
        
        // Validate order IDs
        $orderIds = array_filter($orderIds, 'is_numeric');
        if (empty($orderIds)) {
            throw new Exception('No valid order IDs provided');
        }
        
        $result = handleBulkAction($orderController, $action, $orderIds);
        
        echo json_encode([
            'success' => true,
            'message' => $result['message'],
            'data' => $result['data'] ?? []
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Error processing POST request: ' . $e->getMessage());
    }
}

/**
 * Handle bulk actions on orders
 */
function handleBulkAction($orderController, $action, $orderIds) {
    $successCount = 0;
    $errors = [];
    
    foreach ($orderIds as $orderId) {
        try {
            switch ($action) {
                case 'bulk_confirm':
                    $result = $orderController->confirmOrder($orderId);
                    if ($result) $successCount++;
                    break;
                    
                case 'bulk_cancel':
                    $result = $orderController->cancelOrder($orderId);
                    if ($result) $successCount++;
                    break;
                    
                case 'bulk_ship':
                    $result = $orderController->shipOrder($orderId);
                    if ($result) $successCount++;
                    break;
                    
                case 'bulk_deliver':
                    $result = $orderController->deliverOrder($orderId);
                    if ($result) $successCount++;
                    break;
                    
                default:
                    throw new Exception('Invalid bulk action');
            }
        } catch (Exception $e) {
            $errors[] = "Order ID {$orderId}: " . $e->getMessage();
        }
    }
    
    $totalCount = count($orderIds);
    $message = "Successfully processed {$successCount}/{$totalCount} orders";
    
    if (!empty($errors)) {
        $message .= '. Errors: ' . implode(', ', $errors);
    }
    
    return [
        'message' => $message,
        'data' => [
            'success_count' => $successCount,
            'total_count' => $totalCount,
            'errors' => $errors
        ]
    ];
}

/**
 * Format orders data for JSON response
 */
function formatOrdersForJSON($orders) {
    if (empty($orders)) {
        return [];
    }
    
    return array_map(function($order) {
        return [
            'order_id' => (int)$order['order_id'],
            'order_number' => $order['order_number'],
            'customer_name' => $order['customer_name'] ?? 'N/A',
            'customer_email' => $order['customer_email'] ?? '',
            'customer_avatar' => $order['customer_avatar'] ?? '',
            'total_amount' => (float)$order['total_amount'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'] ?? 'pending',
            'shipping_address' => $order['shipping_address'] ?? '',
            'notes' => $order['notes'] ?? '',
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at']
        ];
    }, $orders);
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate price range
 */
function validatePrice($price) {
    return is_numeric($price) && $price >= 0;
}

/**
 * Log API activity
 */
function logActivity($action, $details = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'action' => $action,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'details' => $details
    ];
    
    error_log("Order Manager API: " . json_encode($logData));
}
?>