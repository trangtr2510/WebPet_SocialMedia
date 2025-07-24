<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include(__DIR__ . '/../../config/config.php');

class CountDeliveredOrdersController
{
    private $conn;
    
    public function __construct($db)
    {
        $this->conn = $db;
    }
    
    /**
     * Lấy thống kê tổng số lượng đơn hàng delivered theo thời gian
     */
    public function getDeliveredOrdersStatistics()
    {
        $timePeriod = $_GET['timePeriod'] ?? 'day';
        $result = [];
        
        switch ($timePeriod) {
            case 'day':
                $result = $this->getDailyDeliveredOrdersStats();
                break;
            case 'month':
                $result = $this->getMonthlyDeliveredOrdersStats();
                break;
            case 'quarter':
                $result = $this->getQuarterlyDeliveredOrdersStats();
                break;
            case 'year':
                $result = $this->getYearlyDeliveredOrdersStats();
                break;
            default:
                $result = $this->getDailyDeliveredOrdersStats();
        }
        
        header('Content-Type: application/json');
        echo json_encode($result);
    }
    
    /**
     * Thống kê theo ngày
     */
    private function getDailyDeliveredOrdersStats()
    {
        $month = $_GET['selectMonth'] ?? date('n');
        $day = $_GET['selectDay'] ?? date('j');
        $year = date('Y'); // Có thể thêm tham số năm nếu cần
        
        $selectedDate = "$year-$month-$day";
        $previousDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
        
        // Tổng số đơn hàng ngày được chọn
        $currentQuery = "
            SELECT COUNT(*) as total_orders
            FROM orders 
            WHERE DATE(order_date) = ? 
            AND status = 'delivered'
        ";
        
        $currentStmt = $this->conn->prepare($currentQuery);
        $currentStmt->bind_param('s', $selectedDate);
        $currentStmt->execute();
        $currentResult = $currentStmt->get_result()->fetch_assoc();
        $currentTotal = $currentResult['total_orders'];
        
        // Tổng số đơn hàng ngày trước đó
        $previousQuery = "
            SELECT COUNT(*) as total_orders
            FROM orders 
            WHERE DATE(order_date) = ? 
            AND status = 'delivered'
        ";
        
        $previousStmt = $this->conn->prepare($previousQuery);
        $previousStmt->bind_param('s', $previousDate);
        $previousStmt->execute();
        $previousResult = $previousStmt->get_result()->fetch_assoc();
        $previousTotal = $previousResult['total_orders'];
        
        // Tính phần trăm thay đổi
        $changePercent = $this->calculatePercentageChange($currentTotal, $previousTotal);
        
        return [
            'current_total' => number_format($currentTotal, 0),
            'previous_total' => number_format($previousTotal, 0),
            'change_percent' => $changePercent,
            'period_text' => 'từ hôm qua',
            'selected_date' => date('d/m/Y', strtotime($selectedDate))
        ];
    }
    
    /**
     * Thống kê theo tháng
     */
    private function getMonthlyDeliveredOrdersStats()
    {
        $month = $_GET['selectMonthOnly'] ?? date('n');
        $year = $_GET['selectYearForMonth'] ?? date('Y');
        
        // Tháng hiện tại
        $currentMonth = "$year-$month";
        
        // Tháng trước
        $previousMonth = date('Y-n', strtotime($currentMonth . '-01 -1 month'));
        
        // Tổng số đơn hàng tháng hiện tại
        $currentQuery = "
            SELECT COUNT(*) as total_orders
            FROM orders 
            WHERE DATE_FORMAT(order_date, '%Y-%c') = ? 
            AND status = 'delivered'
        ";
        
        $currentStmt = $this->conn->prepare($currentQuery);
        $currentStmt->bind_param('s', $currentMonth);
        $currentStmt->execute();
        $currentResult = $currentStmt->get_result()->fetch_assoc();
        $currentTotal = $currentResult['total_orders'];
        
        // Tổng số đơn hàng tháng trước
        $previousStmt = $this->conn->prepare($currentQuery);
        $previousStmt->bind_param('s', $previousMonth);
        $previousStmt->execute();
        $previousResult = $previousStmt->get_result()->fetch_assoc();
        $previousTotal = $previousResult['total_orders'];
        
        $changePercent = $this->calculatePercentageChange($currentTotal, $previousTotal);
        
        return [
            'current_total' => number_format($currentTotal, 0),
            'previous_total' => number_format($previousTotal, 0),
            'change_percent' => $changePercent,
            'period_text' => 'từ tháng trước',
            'selected_period' => "Tháng $month/$year"
        ];
    }
    
    /**
     * Thống kê theo quý
     */
    private function getQuarterlyDeliveredOrdersStats()
    {
        $quarter = $_GET['selectQuarter'] ?? ceil(date('n') / 3);
        $year = $_GET['selectYearForQuarter'] ?? date('Y');
        
        // Xác định tháng bắt đầu và kết thúc của quý
        $quarterMonths = [
            1 => [1, 3],
            2 => [4, 6], 
            3 => [7, 9],
            4 => [10, 12]
        ];
        
        $startMonth = $quarterMonths[$quarter][0];
        $endMonth = $quarterMonths[$quarter][1];
        
        // Quý hiện tại
        $currentStartDate = "$year-$startMonth-01";
        $currentEndDate = date('Y-m-t', strtotime("$year-$endMonth-01"));
        
        // Quý trước
        $previousQuarter = $quarter == 1 ? 4 : $quarter - 1;
        $previousYear = $quarter == 1 ? $year - 1 : $year;
        $prevStartMonth = $quarterMonths[$previousQuarter][0];
        $prevEndMonth = $quarterMonths[$previousQuarter][1];
        $previousStartDate = "$previousYear-$prevStartMonth-01";
        $previousEndDate = date('Y-m-t', strtotime("$previousYear-$prevEndMonth-01"));
        
        // Tổng số đơn hàng quý hiện tại
        $currentQuery = "
            SELECT COUNT(*) as total_orders
            FROM orders 
            WHERE DATE(order_date) BETWEEN ? AND ?
            AND status = 'delivered'
        ";
        
        $currentStmt = $this->conn->prepare($currentQuery);
        $currentStmt->bind_param('ss', $currentStartDate, $currentEndDate);
        $currentStmt->execute();
        $currentResult = $currentStmt->get_result()->fetch_assoc();
        $currentTotal = $currentResult['total_orders'];
        
        // Tổng số đơn hàng quý trước
        $previousStmt = $this->conn->prepare($currentQuery);
        $previousStmt->bind_param('ss', $previousStartDate, $previousEndDate);
        $previousStmt->execute();
        $previousResult = $previousStmt->get_result()->fetch_assoc();
        $previousTotal = $previousResult['total_orders'];
        
        $changePercent = $this->calculatePercentageChange($currentTotal, $previousTotal);
        
        return [
            'current_total' => number_format($currentTotal, 0),
            'previous_total' => number_format($previousTotal, 0),
            'change_percent' => $changePercent,
            'period_text' => 'từ quý trước',
            'selected_period' => "Quý $quarter/$year"
        ];
    }
    
    /**
     * Thống kê theo năm
     */
    private function getYearlyDeliveredOrdersStats()
    {
        $year = $_GET['selectYear'] ?? date('Y');
        $previousYear = $year - 1;
        
        // Tổng số đơn hàng năm hiện tại
        $currentQuery = "
            SELECT COUNT(*) as total_orders
            FROM orders 
            WHERE YEAR(order_date) = ? 
            AND status = 'delivered'
        ";
        
        $currentStmt = $this->conn->prepare($currentQuery);
        $currentStmt->bind_param('i', $year);
        $currentStmt->execute();
        $currentResult = $currentStmt->get_result()->fetch_assoc();
        $currentTotal = $currentResult['total_orders'];
        
        // Tổng số đơn hàng năm trước
        $previousStmt = $this->conn->prepare($currentQuery);
        $previousStmt->bind_param('i', $previousYear);
        $previousStmt->execute();
        $previousResult = $previousStmt->get_result()->fetch_assoc();
        $previousTotal = $previousResult['total_orders'];
        
        $changePercent = $this->calculatePercentageChange($currentTotal, $previousTotal);
        
        return [
            'current_total' => number_format($currentTotal, 0),
            'previous_total' => number_format($previousTotal, 0),
            'change_percent' => $changePercent,
            'period_text' => 'từ năm trước',
            'selected_period' => "Năm $year"
        ];
    }
    
    /**
     * Tính phần trăm thay đổi
     */
    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? '+100' : '0';
        }
        
        $change = (($current - $previous) / abs($previous)) * 100;
        $sign = $change >= 0 ? '+' : '';
        
        return $sign . number_format($change, 1);
    }
    
    /**
     * Lấy dữ liệu cho frontend hiển thị
     */
    public function getDashboardData()
    {
        $stats = $this->getDeliveredOrdersStatistics();
        
        // Trả về dữ liệu để hiển thị trong HTML
        return [
            'total_orders' => $stats['current_total'] . ' đơn hàng',
            'change_text' => $stats['change_percent'] . '% ' . $stats['period_text'],
            'change_class' => strpos($stats['change_percent'], '+') === 0 ? 'positive' : 'negative'
        ];
    }
    
    /**
     * API endpoint cho AJAX calls
     */
    public function apiGetDeliveredOrdersStats()
    {
        try {
            $timePeriod = $_GET['timePeriod'] ?? 'day';
            $result = [];
            
            switch ($timePeriod) {
                case 'day':
                    $result = $this->getDailyDeliveredOrdersStats();
                    break;
                case 'month':
                    $result = $this->getMonthlyDeliveredOrdersStats();
                    break;
                case 'quarter':
                    $result = $this->getQuarterlyDeliveredOrdersStats();
                    break;
                case 'year':
                    $result = $this->getYearlyDeliveredOrdersStats();
                    break;
                default:
                    $result = $this->getDailyDeliveredOrdersStats();
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

// Sử dụng controller
if (isset($_GET['action']) && $_GET['action'] === 'getDeliveredOrdersStats') {
    try {
        // Sử dụng kết nối từ config.php
        if (!isset($conn)) {
            throw new Exception("Database connection not found in config.php");
        }
        
        $controller = new CountDeliveredOrdersController($conn);
        $controller->apiGetDeliveredOrdersStats();
        
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

?>