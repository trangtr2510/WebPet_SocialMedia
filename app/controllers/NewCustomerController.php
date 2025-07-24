<?php
include(__DIR__ . '/../../config/config.php');

class NewCustomerController
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function apiGetNewCustomerStats()
    {
        try {
            $timePeriod = $_GET['timePeriod'] ?? 'day';
            $result = [];

            switch ($timePeriod) {
                case 'day':
                    $result = $this->getDailyNewCustomers();
                    break;
                case 'month':
                    $result = $this->getMonthlyNewCustomers();
                    break;
                case 'quarter':
                    $result = $this->getQuarterlyNewCustomers();
                    break;
                case 'year':
                    $result = $this->getYearlyNewCustomers();
                    break;
                default:
                    $result = $this->getDailyNewCustomers();
            }

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'data' => $result
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    private function getDailyNewCustomers()
    {
        $month = $_GET['selectMonth'] ?? date('n');
        $day = $_GET['selectDay'] ?? date('j');
        $year = date('Y');

        $selectedDate = "$year-$month-$day";
        $previousDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));

        $current = $this->countCustomersOnDate($selectedDate);
        $previous = $this->countCustomersOnDate($previousDate);

        return [
            'current_total' => $current,
            'previous_total' => $previous,
            'change_percent' => $this->calculateChange($current, $previous),
            'period_text' => 'từ hôm qua',
            'selected_date' => date('d/m/Y', strtotime($selectedDate))
        ];
    }

    private function getMonthlyNewCustomers()
    {
        $month = $_GET['selectMonthOnly'] ?? date('n');
        $year = $_GET['selectYearForMonth'] ?? date('Y');

        $currentMonth = "$year-$month";
        $previousMonth = date('Y-n', strtotime("$currentMonth-01 -1 month"));

        $current = $this->countCustomersInMonth($currentMonth);
        $previous = $this->countCustomersInMonth($previousMonth);

        return [
            'current_total' => $current,
            'previous_total' => $previous,
            'change_percent' => $this->calculateChange($current, $previous),
            'period_text' => 'từ tháng trước',
            'selected_period' => "Tháng $month/$year"
        ];
    }

    private function getQuarterlyNewCustomers()
    {
        $quarter = $_GET['selectQuarter'] ?? ceil(date('n') / 3);
        $year = $_GET['selectYearForQuarter'] ?? date('Y');

        $months = [
            1 => [1, 3],
            2 => [4, 6],
            3 => [7, 9],
            4 => [10, 12]
        ];

        [$startMonth, $endMonth] = $months[$quarter];

        $startDate = "$year-$startMonth-01";
        $endDate = date('Y-m-t', strtotime("$year-$endMonth-01"));

        $previousQuarter = $quarter == 1 ? 4 : $quarter - 1;
        $previousYear = $quarter == 1 ? $year - 1 : $year;
        [$prevStartMonth, $prevEndMonth] = $months[$previousQuarter];

        $prevStartDate = "$previousYear-$prevStartMonth-01";
        $prevEndDate = date('Y-m-t', strtotime("$previousYear-$prevEndMonth-01"));

        $current = $this->countCustomersBetween($startDate, $endDate);
        $previous = $this->countCustomersBetween($prevStartDate, $prevEndDate);

        return [
            'current_total' => $current,
            'previous_total' => $previous,
            'change_percent' => $this->calculateChange($current, $previous),
            'period_text' => 'từ quý trước',
            'selected_period' => "Quý $quarter/$year"
        ];
    }

    private function getYearlyNewCustomers()
    {
        $year = $_GET['selectYear'] ?? date('Y');
        $previousYear = $year - 1;

        $current = $this->countCustomersInYear($year);
        $previous = $this->countCustomersInYear($previousYear);

        return [
            'current_total' => $current,
            'previous_total' => $previous,
            'change_percent' => $this->calculateChange($current, $previous),
            'period_text' => 'từ năm trước',
            'selected_period' => "Năm $year"
        ];
    }

    // Helper query functions
    private function countCustomersOnDate($date)
    {
        $query = "SELECT COUNT(*) as total FROM users WHERE DATE(registration_date) = ? AND user_type = 'customer'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    private function countCustomersInMonth($monthStr)
    {
        $query = "SELECT COUNT(*) as total FROM users WHERE DATE_FORMAT(registration_date, '%Y-%c') = ? AND user_type = 'customer'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('s', $monthStr);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    private function countCustomersBetween($start, $end)
    {
        $query = "SELECT COUNT(*) as total FROM users WHERE DATE(registration_date) BETWEEN ? AND ? AND user_type = 'customer'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('ss', $start, $end);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    private function countCustomersInYear($year)
    {
        $query = "SELECT COUNT(*) as total FROM users WHERE YEAR(registration_date) = ? AND user_type = 'customer'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $year);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }

    private function calculateChange($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? '+100' : '0';
        }
        $change = (($current - $previous) / abs($previous)) * 100;
        $sign = $change >= 0 ? '+' : '';
        return $sign . number_format($change, 1);
    }
}

// Route nếu được gọi trực tiếp
if (isset($_GET['action']) && $_GET['action'] === 'getNewCustomerStats') {
    if (!isset($conn)) {
        die(json_encode(['success' => false, 'error' => 'Không có kết nối CSDL']));
    }

    $controller = new NewCustomerController($conn);
    $controller->apiGetNewCustomerStats();
}
?>
