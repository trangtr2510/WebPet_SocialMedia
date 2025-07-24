<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include(__DIR__ . '/../../config/config.php');

class OrderStatusPercentageController
{
    private $conn;
    private $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getOrderStatusStatistics()
    {
        $timePeriod = $_GET['timePeriod'] ?? 'day';
        switch ($timePeriod) {
            case 'day':
                return $this->getStatsByDate();
            case 'month':
                return $this->getStatsByMonth();
            case 'quarter':
                return $this->getStatsByQuarter();
            case 'year':
                return $this->getStatsByYear();
            default:
                return $this->getStatsByDate();
        }
    }

    private function getStatsByDate()
    {
        $month = $_GET['selectMonth'] ?? date('n');
        $day = $_GET['selectDay'] ?? date('j');
        $year = date('Y');

        $selectedDate = "$year-$month-$day";

        return $this->getPercentageByCondition("DATE(order_date) = ?", [$selectedDate]);
    }

    private function getStatsByMonth()
    {
        $month = $_GET['selectMonthOnly'] ?? date('n');
        $year = $_GET['selectYearForMonth'] ?? date('Y');

        return $this->getPercentageByCondition("MONTH(order_date) = ? AND YEAR(order_date) = ?", [$month, $year]);
    }

    private function getStatsByQuarter()
    {
        $quarter = $_GET['selectQuarter'] ?? ceil(date('n') / 3);
        $year = $_GET['selectYearForQuarter'] ?? date('Y');

        $startMonth = ($quarter - 1) * 3 + 1;
        $endMonth = $startMonth + 2;

        return $this->getPercentageByCondition(
            "MONTH(order_date) BETWEEN ? AND ? AND YEAR(order_date) = ?",
            [$startMonth, $endMonth, $year]
        );
    }

    private function getStatsByYear()
    {
        $year = $_GET['selectYear'] ?? date('Y');
        return $this->getPercentageByCondition("YEAR(order_date) = ?", [$year]);
    }

    private function getPercentageByCondition($whereClause, $params)
    {
        // Tổng tất cả đơn hàng
        $totalQuery = "SELECT COUNT(*) as total FROM orders WHERE $whereClause";
        $stmt = $this->conn->prepare($totalQuery);
        $stmt->bind_param(str_repeat('i', count($params)), ...$params);
        $stmt->execute();
        $total = $stmt->get_result()->fetch_assoc()['total'];

        $percentages = [];
        foreach ($this->statuses as $status) {
            $statusQuery = "SELECT COUNT(*) as total FROM orders WHERE $whereClause AND status = ?";
            $types = str_repeat('i', count($params)) . 's';
            $stmt = $this->conn->prepare($statusQuery);
            $allParams = [...$params, $status];
            $stmt->bind_param($types, ...$allParams);
            $stmt->execute();
            $count = $stmt->get_result()->fetch_assoc()['total'];

            $percentage = $total > 0 ? round(($count / $total) * 100, 2) : 0;
            $percentages[$status] = [
                'count' => $count,
                'percentage' => $percentage
            ];
        }

        return [
            'total_orders' => $total,
            'status_distribution' => $percentages
        ];
    }

    public function apiGetOrderStatusStats()
    {
        try {
            $data = $this->getOrderStatusStatistics();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $data
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

// Gọi controller nếu cần
if (isset($_GET['action']) && $_GET['action'] === 'getOrderStatusStats') {
    if (!isset($conn)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Missing DB connection']);
        exit;
    }

    $controller = new OrderStatusPercentageController($conn);
    $controller->apiGetOrderStatusStats();
}
?>
