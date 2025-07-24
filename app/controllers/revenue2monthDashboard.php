<?php
include(__DIR__ . '/../../config/config.php');

class Revenue2MonthDashboard
{
    private $conn;
    private $labels = [1, 4, 7, 10, 13, 16, 19, 22, 25, 28, 31];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getRevenueComparison()
    {
        $month = $_GET['selectMonth'] ?? date('n');
        $year = $_GET['selectYear'] ?? date('Y');
        $previousMonthDate = date('Y-n', strtotime("$year-$month-01 -1 month"));
        list($prevYear, $prevMonth) = explode('-', $previousMonthDate);

        $currentData = $this->getRevenueByDays((int)$year, (int)$month);
        $previousData = $this->getRevenueByDays((int)$prevYear, (int)$prevMonth);

        $result = [
            'labels' => array_map('strval', $this->labels),
            'current_month' => $currentData,
            'previous_month' => $previousData,
            'selected_month' => "Tháng $month/$year",
            'previous_month_label' => "Tháng $prevMonth/$prevYear"
        ];

        // Ghi log vào file
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'request' => [
                'month' => $month,
                'year' => $year
            ],
            'response' => $result
        ];

        $logDir = __DIR__ . '/../../logs';
        if (!file_exists($logDir)) {
            mkdir($logDir, 0777, true);
        }

        file_put_contents($logDir . '/revenue2month.log', json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'data' => $result]);
    }

    private function getRevenueByDays($year, $month)
    {
        $data = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        foreach ($this->labels as $day) {
            // Nếu mốc ngày không tồn tại trong tháng (ví dụ: 31/06)
            if ($day > $daysInMonth) {
                $data[] = 0;
                continue;
            }

            $startDate = "$year-$month-" . str_pad($day, 2, '0', STR_PAD_LEFT);

            $nextDay = $day + 2;
            // Nếu vượt quá số ngày trong tháng, thì dùng ngày cuối tháng
            $endDay = min($nextDay, $daysInMonth);
            $endDate = "$year-$month-" . str_pad($endDay, 2, '0', STR_PAD_LEFT);

            $query = "
                SELECT COALESCE(SUM(total_amount), 0) as total
                FROM orders
                WHERE DATE(order_date) BETWEEN ? AND ?
                AND status = 'delivered'
            ";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('ss', $startDate, $endDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $data[] = (float)$row['total'];
        }

        return $data;
    }

}

// Gọi hàm xử lý nếu có action phù hợp
if (isset($_GET['action']) && $_GET['action'] === 'getRevenue2Month') {
    try {
        if (!isset($conn)) {
            throw new Exception("Database connection not found");
        }

        $controller = new Revenue2MonthDashboard($conn);
        $controller->getRevenueComparison();

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
