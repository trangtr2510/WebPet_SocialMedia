<?php
include(__DIR__ . '/../../config/config.php');

class RevenueDashboard
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getRevenueByMonth()
    {
        $type = $_GET['type'] ?? 'quarter'; // quarter hoặc year
        $year = $_GET['year'] ?? date('Y');

        if ($type === 'quarter') {
            $quarter = $_GET['quarter'] ?? ceil(date('n') / 3);
            return $this->getQuarterlyRevenue($year, $quarter);
        } elseif ($type === 'year') {
            return $this->getYearlyRevenue($year);
        } else {
            throw new Exception("Invalid type parameter");
        }
    }

    private function getQuarterlyRevenue($year, $quarter)
    {
        $quarterMonths = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        ];

        $months = $quarterMonths[$quarter];
        $revenue = [];

        foreach ($months as $month) {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as total_sales
                FROM orders 
                WHERE MONTH(order_date) = ? AND YEAR(order_date) = ? AND status = 'delivered'
            ");
            $stmt->bind_param("ii", $month, $year);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $revenue[] = [
                'month' => $month,
                'total' => (float)$result['total_sales']
            ];
        }

        return [
            'label' => "Quý $quarter/$year",
            'data' => $revenue
        ];
    }

    private function getYearlyRevenue($year)
    {
        $revenue = [];

        for ($month = 1; $month <= 12; $month++) {
            $stmt = $this->conn->prepare("
                SELECT COALESCE(SUM(total_amount), 0) as total_sales
                FROM orders 
                WHERE MONTH(order_date) = ? AND YEAR(order_date) = ? AND status = 'delivered'
            ");
            $stmt->bind_param("ii", $month, $year);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $revenue[] = [
                'month' => $month,
                'total' => (float)$result['total_sales']
            ];
        }

        return [
            'label' => "Năm $year",
            'data' => $revenue
        ];
    }

    public function api()
    {
        try {
            $data = $this->getRevenueByMonth();
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

// Gọi controller nếu có action
if (isset($_GET['action']) && $_GET['action'] === 'getRevenue') {
    try {
        if (!isset($conn)) {
            throw new Exception("Database connection not found");
        }

        $controller = new RevenueDashboard($conn);
        $controller->api();
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
