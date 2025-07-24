<?php
include(__DIR__ . '/../../config/config.php');

class ProductsSoldController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getStatistics()
    {
        $timePeriod = $_GET['timePeriod'] ?? 'day';
        $result = [];

        switch ($timePeriod) {
            case 'day':
                $result = $this->getDailyStats();
                break;
            case 'month':
                $result = $this->getMonthlyStats();
                break;
            case 'quarter':
                $result = $this->getQuarterlyStats();
                break;
            case 'year':
                $result = $this->getYearlyStats();
                break;
            default:
                $result = $this->getDailyStats();
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $result
        ]);
    }

    private function getDailyStats()
    {
        $month = $_GET['selectMonth'] ?? date('n');
        $day = $_GET['selectDay'] ?? date('j');
        $year = date('Y');

        $selectedDate = "$year-$month-$day";
        $previousDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));

        $currentTotal = $this->getSoldQuantityByDate($selectedDate);
        $previousTotal = $this->getSoldQuantityByDate($previousDate);

        return [
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'change_percent' => $this->calculatePercentageChange($currentTotal, $previousTotal),
            'period_text' => 'từ hôm qua',
            'selected_date' => date('d/m/Y', strtotime($selectedDate))
        ];
    }

    private function getMonthlyStats()
    {
        $month = $_GET['selectMonthOnly'] ?? date('n');
        $year = $_GET['selectYearForMonth'] ?? date('Y');

        $currentMonth = "$year-$month";
        $previousMonth = date('Y-n', strtotime($currentMonth . '-01 -1 month'));

        $currentTotal = $this->getSoldQuantityByMonth($currentMonth);
        $previousTotal = $this->getSoldQuantityByMonth($previousMonth);

        return [
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'change_percent' => $this->calculatePercentageChange($currentTotal, $previousTotal),
            'period_text' => 'từ tháng trước',
            'selected_period' => "Tháng $month/$year"
        ];
    }

    private function getQuarterlyStats()
    {
        $quarter = $_GET['selectQuarter'] ?? ceil(date('n') / 3);
        $year = $_GET['selectYearForQuarter'] ?? date('Y');

        $quarterMonths = [
            1 => [1, 3],
            2 => [4, 6],
            3 => [7, 9],
            4 => [10, 12]
        ];

        $startMonth = $quarterMonths[$quarter][0];
        $endMonth = $quarterMonths[$quarter][1];

        $currentStart = "$year-$startMonth-01";
        $currentEnd = date('Y-m-t', strtotime("$year-$endMonth-01"));

        $prevQuarter = $quarter == 1 ? 4 : $quarter - 1;
        $prevYear = $quarter == 1 ? $year - 1 : $year;
        $prevStart = "$prevYear-{$quarterMonths[$prevQuarter][0]}-01";
        $prevEnd = date('Y-m-t', strtotime("$prevYear-{$quarterMonths[$prevQuarter][1]}-01"));

        $currentTotal = $this->getSoldQuantityBetweenDates($currentStart, $currentEnd);
        $previousTotal = $this->getSoldQuantityBetweenDates($prevStart, $prevEnd);

        return [
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'change_percent' => $this->calculatePercentageChange($currentTotal, $previousTotal),
            'period_text' => 'từ quý trước',
            'selected_period' => "Quý $quarter/$year"
        ];
    }

    private function getYearlyStats()
    {
        $year = $_GET['selectYear'] ?? date('Y');
        $previousYear = $year - 1;

        $currentTotal = $this->getSoldQuantityByYear($year);
        $previousTotal = $this->getSoldQuantityByYear($previousYear);

        return [
            'current_total' => $currentTotal,
            'previous_total' => $previousTotal,
            'change_percent' => $this->calculatePercentageChange($currentTotal, $previousTotal),
            'period_text' => 'từ năm trước',
            'selected_period' => "Năm $year"
        ];
    }

    private function getSoldQuantityByDate($date)
    {
        $query = "
            SELECT COALESCE(SUM(oi.quantity), 0) AS total_quantity
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE DATE(o.order_date) = ?
            AND o.status = 'delivered'
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $date);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int) $result['total_quantity'];
    }

    private function getSoldQuantityByMonth($yearMonth)
    {
        $query = "
            SELECT COALESCE(SUM(oi.quantity), 0) AS total_quantity
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE DATE_FORMAT(o.order_date, '%Y-%c') = ?
            AND o.status = 'delivered'
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $yearMonth);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int) $result['total_quantity'];
    }

    private function getSoldQuantityByYear($year)
    {
        $query = "
            SELECT COALESCE(SUM(oi.quantity), 0) AS total_quantity
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE YEAR(o.order_date) = ?
            AND o.status = 'delivered'
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $year);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int) $result['total_quantity'];
    }

    private function getSoldQuantityBetweenDates($startDate, $endDate)
    {
        $query = "
            SELECT COALESCE(SUM(oi.quantity), 0) AS total_quantity
            FROM order_items oi
            JOIN orders o ON oi.order_id = o.order_id
            WHERE DATE(o.order_date) BETWEEN ? AND ?
            AND o.status = 'delivered'
        ";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return (int) $result['total_quantity'];
    }

    private function calculatePercentageChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? '+100' : '0';
        }

        $change = (($current - $previous) / abs($previous)) * 100;
        $sign = $change >= 0 ? '+' : '';
        return $sign . number_format($change, 1);
    }
}

// API handler
if (isset($_GET['action']) && $_GET['action'] === 'getProductsSoldStats') {
    try {
        if (!isset($conn)) {
            throw new Exception("Missing DB connection");
        }

        $controller = new ProductsSoldController($conn);
        $controller->getStatistics();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}
