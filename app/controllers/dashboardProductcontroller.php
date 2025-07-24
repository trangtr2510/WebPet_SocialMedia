<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include(__DIR__ . '/../../config/config.php');

class DashboardProductController
{
    private $conn;
    
    public function __construct($db)
    {
        $this->conn = $db;
    }
    
    /**
     * Get top selling products statistics based on time period
     */
    public function getTopSellingProductsStats()
    {
        $timePeriod = $_GET['timePeriod'] ?? 'day';
        $result = [];
        
        switch ($timePeriod) {
            case 'day':
                $result = $this->getDailyTopProductsStats();
                break;
            case 'month':
                $result = $this->getMonthlyTopProductsStats();
                break;
            case 'quarter':
                $result = $this->getQuarterlyTopProductsStats();
                break;
            case 'year':
                $result = $this->getYearlyTopProductsStats();
                break;
            default:
                $result = $this->getDailyTopProductsStats();
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Get daily top products statistics
     */
    private function getDailyTopProductsStats()
    {
        $month = $_GET['selectMonth'] ?? date('n');
        $day = $_GET['selectDay'] ?? date('j');
        $year = date('Y');
        
        $selectedDate = "$year-$month-$day";
        
        // Get top 5 products for selected day
        $query = "
            SELECT 
                p.product_id,
                p.product_name,
                SUM(oi.quantity) as total_quantity_sold,
                SUM(oi.total_price) as total_revenue,
                COUNT(DISTINCT oi.order_id) as total_orders
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE DATE(o.order_date) = ?
              AND o.status = 'delivered'
              AND o.payment_status = 'paid'
            GROUP BY p.product_id, p.product_name
            ORDER BY total_quantity_sold DESC
            LIMIT 5
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $selectedDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topProducts = [];
        while ($row = $result->fetch_assoc()) {
            $topProducts[] = $row;
        }
        
        // Get total products sold for the day
        $totalQuery = "
            SELECT SUM(oi.quantity) as total_sold
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            WHERE DATE(o.order_date) = ?
              AND o.status = 'delivered'
              AND o.payment_status = 'paid'
        ";
        
        $totalStmt = $this->conn->prepare($totalQuery);
        $totalStmt->bind_param('s', $selectedDate);
        $totalStmt->execute();
        $totalResult = $totalStmt->get_result()->fetch_assoc();
        $totalSold = $totalResult['total_sold'] ?? 0;
        
        // Calculate percentages
        foreach ($topProducts as &$product) {
            $product['percentage'] = $totalSold > 0 
                ? round(($product['total_quantity_sold'] / $totalSold) * 100, 2)
                : 0;
        }
        
        return [
            'top_products' => $topProducts,
            'total_sold' => $totalSold,
            'period_text' => date('d/m/Y', strtotime($selectedDate)),
            'time_period' => 'day'
        ];
    }
    
    /**
     * Get monthly top products statistics
     */
    private function getMonthlyTopProductsStats()
    {
        $month = $_GET['selectMonthOnly'] ?? date('n');
        $year = $_GET['selectYearForMonth'] ?? date('Y');
        
        $currentMonth = "$year-$month";
        
        // Get top 5 products for selected month
        $query = "
            SELECT 
                p.product_id,
                p.product_name,
                SUM(oi.quantity) as total_quantity_sold,
                SUM(oi.total_price) as total_revenue,
                COUNT(DISTINCT oi.order_id) as total_orders
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE DATE_FORMAT(o.order_date, '%Y-%c') = ?
              AND o.status = 'delivered'
              AND o.payment_status = 'paid'
            GROUP BY p.product_id, p.product_name
            ORDER BY total_quantity_sold DESC
            LIMIT 5
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $currentMonth);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topProducts = [];
        while ($row = $result->fetch_assoc()) {
            $topProducts[] = $row;
        }
        
        // Get total products sold for the month
        $totalQuery = "
            SELECT SUM(oi.quantity) as total_sold
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            WHERE DATE_FORMAT(o.order_date, '%Y-%c') = ?
              AND o.status = 'delivered'
              AND o.payment_status = 'paid'
        ";
        
        $totalStmt = $this->conn->prepare($totalQuery);
        $totalStmt->bind_param('s', $currentMonth);
        $totalStmt->execute();
        $totalResult = $totalStmt->get_result()->fetch_assoc();
        $totalSold = $totalResult['total_sold'] ?? 0;
        
        // Calculate percentages
        foreach ($topProducts as &$product) {
            $product['percentage'] = $totalSold > 0 
                ? round(($product['total_quantity_sold'] / $totalSold) * 100, 2)
                : 0;
        }
        
        return [
            'top_products' => $topProducts,
            'total_sold' => $totalSold,
            'period_text' => "Tháng $month/$year",
            'time_period' => 'month'
        ];
    }
    
    /**
     * Get quarterly top products statistics
     */
    private function getQuarterlyTopProductsStats()
    {
        $quarter = $_GET['selectQuarter'] ?? ceil(date('n') / 3);
        $year = $_GET['selectYearForQuarter'] ?? date('Y');
        
        // Determine start and end months for the quarter
        $quarterMonths = [
            1 => [1, 3],
            2 => [4, 6], 
            3 => [7, 9],
            4 => [10, 12]
        ];
        
        $startMonth = $quarterMonths[$quarter][0];
        $endMonth = $quarterMonths[$quarter][1];
        
        $startDate = "$year-$startMonth-01";
        $endDate = date('Y-m-t', strtotime("$year-$endMonth-01"));
        
        // Get top 5 products for selected quarter
        $query = "
            SELECT 
                p.product_id,
                p.product_name,
                SUM(oi.quantity) as total_quantity_sold,
                SUM(oi.total_price) as total_revenue,
                COUNT(DISTINCT oi.order_id) as total_orders
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE DATE(o.order_date) BETWEEN ? AND ?
              AND o.status = 'delivered'
              AND o.payment_status = 'paid'
            GROUP BY p.product_id, p.product_name
            ORDER BY total_quantity_sold DESC
            LIMIT 5
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topProducts = [];
        while ($row = $result->fetch_assoc()) {
            $topProducts[] = $row;
        }
        
        // Get total products sold for the quarter
        $totalQuery = "
            SELECT SUM(oi.quantity) as total_sold
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            WHERE DATE(o.order_date) BETWEEN ? AND ?
              AND o.status = 'delivered'
              AND o.payment_status = 'paid'
        ";
        
        $totalStmt = $this->conn->prepare($totalQuery);
        $totalStmt->bind_param('ss', $startDate, $endDate);
        $totalStmt->execute();
        $totalResult = $totalStmt->get_result()->fetch_assoc();
        $totalSold = $totalResult['total_sold'] ?? 0;
        
        // Calculate percentages
        foreach ($topProducts as &$product) {
            $product['percentage'] = $totalSold > 0 
                ? round(($product['total_quantity_sold'] / $totalSold) * 100, 2)
                : 0;
        }
        
        return [
            'top_products' => $topProducts,
            'total_sold' => $totalSold,
            'period_text' => "Quý $quarter/$year",
            'time_period' => 'quarter'
        ];
    }
    
    /**
     * Get yearly top products statistics
     */
    private function getYearlyTopProductsStats()
    {
        $year = $_GET['selectYear'] ?? date('Y');
        
        // Get top 5 products for selected year
        $query = "
            SELECT 
                p.product_id,
                p.product_name,
                SUM(oi.quantity) as total_quantity_sold,
                SUM(oi.total_price) as total_revenue,
                COUNT(DISTINCT oi.order_id) as total_orders
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            INNER JOIN products p ON oi.product_id = p.product_id
            WHERE YEAR(o.order_date) = ?
              AND o.status = 'delivered'
              AND o.payment_status = 'paid'
            GROUP BY p.product_id, p.product_name
            ORDER BY total_quantity_sold DESC
            LIMIT 5
        ";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $topProducts = [];
        while ($row = $result->fetch_assoc()) {
            $topProducts[] = $row;
        }
        
        // Get total products sold for the year
        $totalQuery = "
            SELECT SUM(oi.quantity) as total_sold
            FROM order_items oi
            INNER JOIN orders o ON oi.order_id = o.order_id
            WHERE YEAR(o.order_date) = ?
              AND o.status = 'delivered'
              AND o.payment_status = 'paid'
        ";
        
        $totalStmt = $this->conn->prepare($totalQuery);
        $totalStmt->bind_param('i', $year);
        $totalStmt->execute();
        $totalResult = $totalStmt->get_result()->fetch_assoc();
        $totalSold = $totalResult['total_sold'] ?? 0;
        
        // Calculate percentages
        foreach ($topProducts as &$product) {
            $product['percentage'] = $totalSold > 0 
                ? round(($product['total_quantity_sold'] / $totalSold) * 100, 2)
                : 0;
        }
        
        return [
            'top_products' => $topProducts,
            'total_sold' => $totalSold,
            'period_text' => "Năm $year",
            'time_period' => 'year'
        ];
    }
    
    /**
     * Get dashboard data for frontend display
     */
    public function getDashboardData()
    {
        $timePeriod = $_GET['timePeriod'] ?? 'day';
        $stats = [];
        
        switch ($timePeriod) {
            case 'day':
                $stats = $this->getDailyTopProductsStats();
                break;
            case 'month':
                $stats = $this->getMonthlyTopProductsStats();
                break;
            case 'quarter':
                $stats = $this->getQuarterlyTopProductsStats();
                break;
            case 'year':
                $stats = $this->getYearlyTopProductsStats();
                break;
            default:
                $stats = $this->getDailyTopProductsStats();
        }
        
        $formattedData = [];
        foreach ($stats['top_products'] as $index => $product) {
            $formattedData[] = [
                'rank' => str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                'name' => $product['product_name'],
                'quantity_sold' => number_format($product['total_quantity_sold']),
                'revenue' => number_format($product['total_revenue'], 2),
                'percentage' => $product['percentage'] . '%',
                'total_orders' => $product['total_orders']
            ];
        }
        
        return [
            'products' => $formattedData,
            'period_text' => $stats['period_text'],
            'time_period' => $stats['time_period'],
            'total_sold' => number_format($stats['total_sold'])
        ];
    }
    
    /**
     * API endpoint for AJAX calls
     */
    public function apiGetTopSellingProductsStats()
    {
        try {
            $timePeriod = $_GET['timePeriod'] ?? 'day';
            $result = [];
            
            switch ($timePeriod) {
                case 'day':
                    $result = $this->getDailyTopProductsStats();
                    break;
                case 'month':
                    $result = $this->getMonthlyTopProductsStats();
                    break;
                case 'quarter':
                    $result = $this->getQuarterlyTopProductsStats();
                    break;
                case 'year':
                    $result = $this->getYearlyTopProductsStats();
                    break;
                default:
                    $result = $this->getDailyTopProductsStats();
            }
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
}

// Use the controller
if (isset($_GET['action']) && $_GET['action'] === 'getTopSellingProductsStats') {
    try {
        if (!isset($conn)) {
            throw new Exception("Database connection not found in config.php");
        }
        
        $controller = new DashboardProductController($conn);
        $controller->apiGetTopSellingProductsStats();
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}