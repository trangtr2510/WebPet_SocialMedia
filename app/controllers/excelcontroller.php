<?php
// Debug version của ExcelController.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log all output to file for debugging
ob_start();

require_once '../../vendor/autoload.php';
include(__DIR__ . '/../../config/config.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;

// Log parameters
file_put_contents('debug.log', "Parameters: " . print_r($_REQUEST, true) . "\n", FILE_APPEND);

class ExcelController {
    private $conn;
    private $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    public function __construct($database) {
        // Kiểm tra và khởi tạo connection đúng cách
        if ($database === null || is_string($database)) {
            // Nếu database không hợp lệ, tạo connection mới
            $this->conn = $this->createDatabaseConnection();
        } else {
            // Nếu database đã là connection object
            $this->conn = $database;
        }
        
        // Log để debug
        file_put_contents('debug.log', "Database connection type: " . gettype($this->conn) . "\n", FILE_APPEND);
        
        if ($this->conn && is_object($this->conn)) {
            file_put_contents('debug.log', "Database connection class: " . get_class($this->conn) . "\n", FILE_APPEND);
        }
    }
    
    /**
     * Tạo kết nối database mới
     */
    private function createDatabaseConnection() {
        try {
            // Thay đổi thông tin database theo cấu hình của bạn
            $servername = "localhost";
            $username = "root";  // Thay đổi username
            $password = "";      // Thay đổi password
            $dbname = "petshop_socialmedia"; // Thay đổi tên database
            
            $conn = new mysqli($servername, $username, $password, $dbname);
            
            if ($conn->connect_error) {
                file_put_contents('debug.log', "Connection failed: " . $conn->connect_error . "\n", FILE_APPEND);
                return null;
            }
            
            // Set charset
            $conn->set_charset("utf8");
            
            file_put_contents('debug.log', "New database connection created successfully\n", FILE_APPEND);
            return $conn;
            
        } catch (Exception $e) {
            file_put_contents('debug.log', "Error creating connection: " . $e->getMessage() . "\n", FILE_APPEND);
            return null;
        }
    }
    
    /**
     * Kiểm tra connection có hợp lệ không
     */
    private function isValidConnection($conn) {
        return $conn && is_object($conn) && $conn instanceof mysqli && !$conn->connect_error;
    }
    
    
    public function exportStatistics() {
        try {
            $timePeriod = $_REQUEST['timePeriod'] ?? 'day';
            $selectedDate = $_REQUEST['selectedDate'] ?? date('Y-m-d');
            $selectedMonth = $_REQUEST['selectedMonth'] ?? date('m');
            $selectedYear = $_REQUEST['selectedYear'] ?? date('Y');
            $selectedQuarter = $_REQUEST['selectedQuarter'] ?? 1;
            
            file_put_contents('debug.log', "Processing: $timePeriod, $selectedDate, $selectedMonth, $selectedYear, $selectedQuarter\n", FILE_APPEND);
            
            // Get statistics data
            $data = $this->getStatisticsData($timePeriod, $selectedDate, $selectedMonth, $selectedYear, $selectedQuarter);
            
            file_put_contents('debug.log', "Data retrieved: " . print_r($data, true) . "\n", FILE_APPEND);
            
            // Check if data is empty
            if (empty($data)) {
                throw new Exception('No data found for the selected period');
            }
            
            // Create Excel file
            $this->createExcelFile($data, $timePeriod);
            
        } catch (Exception $e) {
            file_put_contents('debug.log', "Error: " . $e->getMessage() . "\n", FILE_APPEND);
            
            // Clear any output buffer
            if (ob_get_contents()) {
                ob_clean();
            }
            
            http_response_code(500);
            header('Content-Type: text/plain');
            echo 'Error: ' . $e->getMessage();
            exit;
        }
    }
    
    private function getStatisticsData($timePeriod, $selectedDate, $selectedMonth, $selectedYear, $selectedQuarter) {
        $data = [];
        
        switch ($timePeriod) {
            case 'day':
                $data = $this->getDayStatistics($selectedDate);
                break;
            case 'month':
                $data = $this->getMonthStatistics($selectedMonth, $selectedYear);
                break;
            case 'quarter':
                $data = $this->getQuarterStatistics($selectedQuarter, $selectedYear);
                break;
            case 'year':
                $data = $this->getYearStatistics($selectedYear);
                break;
        }
        
        return $data;
    }
    
    // Test với dữ liệu giả nếu database trống
    private function getDayStatistics($selectedDate) {
        file_put_contents('debug.log', "Getting day stats for: $selectedDate\n", FILE_APPEND);
        
        $previousDate = date('Y-m-d', strtotime($selectedDate . ' -1 day'));
        
        // Test database connection
        if (!$this->conn) {
            file_put_contents('debug.log', "No database connection, using dummy data\n", FILE_APPEND);
            return $this->getDummyData('Ngày', $selectedDate, $previousDate);
        }
        
        try {
            // Get current day statistics
            $currentStats = $this->getOrderStatsByDate($selectedDate);
            $previousStats = $this->getOrderStatsByDate($previousDate);
            
            // Get product statistics
            $productStats = $this->getProductStatsByDate($selectedDate);
            $previousProductStats = $this->getProductStatsByDate($previousDate);
            
            // If no real data, use dummy data
            if (empty($currentStats['total_orders']) && empty($productStats)) {
                file_put_contents('debug.log', "No real data found, using dummy data\n", FILE_APPEND);
                return $this->getDummyData('Ngày', $selectedDate, $previousDate);
            }
            
            return [
                'period_type' => 'Ngày',
                'current_period' => $selectedDate,
                'previous_period' => $previousDate,
                'current_stats' => $currentStats,
                'previous_stats' => $previousStats,
                'product_stats' => $productStats,
                'previous_product_stats' => $previousProductStats
            ];
            
        } catch (Exception $e) {
            file_put_contents('debug.log', "Database error: " . $e->getMessage() . ", using dummy data\n", FILE_APPEND);
            return $this->getDummyData('Ngày', $selectedDate, $previousDate);
        }
    }
    
    // Tạo dữ liệu giả để test
    private function getDummyData($periodType, $currentPeriod, $previousPeriod) {
        return [
            'period_type' => $periodType,
            'current_period' => $currentPeriod,
            'previous_period' => $previousPeriod,
            'current_stats' => [
                'total_orders' => 15,
                'total_revenue' => 2500000,
                'status_counts' => [
                    'pending' => 3,
                    'processing' => 2,
                    'shipped' => 4,
                    'delivered' => 5,
                    'cancelled' => 1
                ]
            ],
            'previous_stats' => [
                'total_orders' => 12,
                'total_revenue' => 2000000,
                'status_counts' => [
                    'pending' => 2,
                    'processing' => 3,
                    'shipped' => 3,
                    'delivered' => 3,
                    'cancelled' => 1
                ]
            ],
            'product_stats' => [
                ['product_name' => 'Thức ăn cho chó', 'total_quantity' => 10, 'total_sales' => 500000],
                ['product_name' => 'Thức ăn cho mèo', 'total_quantity' => 8, 'total_sales' => 400000],
                ['product_name' => 'Đồ chơi cho pet', 'total_quantity' => 5, 'total_sales' => 250000]
            ],
            'previous_product_stats' => [
                ['product_name' => 'Thức ăn cho chó', 'total_quantity' => 8, 'total_sales' => 400000],
                ['product_name' => 'Thức ăn cho mèo', 'total_quantity' => 6, 'total_sales' => 300000]
            ]
        ];
    }
    
    private function getOrderStatsByDate($date) {
        if (!$this->conn) return ['total_orders' => 0, 'total_revenue' => 0, 'status_counts' => []];
        
        $sql = "SELECT 
                    status,
                    COUNT(*) as order_count,
                    COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END), 0) as total_revenue
                FROM orders 
                WHERE DATE(order_date) = ? 
                GROUP BY status";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return ['total_orders' => 0, 'total_revenue' => 0, 'status_counts' => []];
        }
        
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->formatOrderStats($results);
    }
    
    private function formatOrderStats($results) {
        $stats = [
            'total_orders' => 0,
            'total_revenue' => 0,
            'status_counts' => []
        ];
        
        // Initialize all statuses with 0
        foreach ($this->statuses as $status) {
            $stats['status_counts'][$status] = 0;
        }
        
        foreach ($results as $result) {
            $stats['total_orders'] += $result['order_count'];
            $stats['total_revenue'] += $result['total_revenue'];
            $stats['status_counts'][$result['status']] = $result['order_count'];
        }
        
        return $stats;
    }
    
    private function getProductStatsByDate($date) {
        if (!$this->conn) return [];
        
        $sql = "SELECT 
                    p.product_name,
                    SUM(oi.quantity) as total_quantity,
                    SUM(oi.total_price) as total_sales
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE DATE(o.order_date) = ? AND o.status = 'delivered'
                GROUP BY oi.product_id, p.product_name
                ORDER BY total_quantity DESC";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Product query prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return [];
        }
        
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $results;
    }
    
    // Thêm các methods khác tương tự cho month, quarter, year...
    // private function getMonthStatistics($selectedMonth, $selectedYear) {
    //     // Implement tương tự như getDayStatistics
    //     return $this->getDummyData('Tháng', "Tháng $selectedMonth/$selectedYear", "Tháng trước");
    // }
    
    // private function getQuarterStatistics($selectedQuarter, $selectedYear) {
    //     return $this->getDummyData('Quý', "Quý $selectedQuarter/$selectedYear", "Quý trước");
    // }
    
    // private function getYearStatistics($selectedYear) {
    //     return $this->getDummyData('Năm', "Năm $selectedYear", "Năm " . ($selectedYear - 1));
    // }
    
    private function getPreviousQuarter($quarter, $year) {
        if ($quarter == 1) {
            return ['quarter' => 4, 'year' => $year - 1];
        } else {
            return ['quarter' => $quarter - 1, 'year' => $year];
        }
    }
    
    private function createExcelFile($data, $timePeriod) {
        file_put_contents('debug.log', "Creating Excel file...\n", FILE_APPEND);
        
        // Clear any previous output
        if (ob_get_contents()) {
            ob_clean();
        }
        
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Set document properties
        $spreadsheet->getProperties()
            ->setCreator("Order Management System")
            ->setTitle("Thống kê đơn hàng")
            ->setDescription("Báo cáo thống kê đơn hàng theo " . $data['period_type']);
        
        // Header styling
        $headerStyle = [
            'font' => ['bold' => true, 'size' => 14],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        
        $subHeaderStyle = [
            'font' => ['bold' => true, 'size' => 12],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]
        ];
        
        // Title
        $sheet->setCellValue('A1', 'BÁO CÁO THỐNG KÊ ĐƠN HÀNG');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->applyFromArray($headerStyle);
        
        // Period info
        $sheet->setCellValue('A2', 'Thời gian: ' . $data['current_period']);
        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('E2', 'So sánh với: ' . $data['previous_period']);
        $sheet->mergeCells('E2:H2');
        
        $currentRow = 4;
        
        // Order statistics section
        $sheet->setCellValue('A' . $currentRow, 'THỐNG KÊ ĐƠN HÀNG');
        $sheet->mergeCells('A' . $currentRow . ':H' . $currentRow);
        $sheet->getStyle('A' . $currentRow)->applyFromArray($subHeaderStyle);
        $currentRow++;
        
        // Headers for order stats
        $headers = ['Trạng thái', 'Kỳ hiện tại', 'Kỳ trước', 'Chênh lệch', 'Tỷ lệ thay đổi'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . $currentRow, $header);
            $sheet->getStyle($col . $currentRow)->applyFromArray($subHeaderStyle);
            $col++;
        }
        $currentRow++;
        
        // Status translations
        $statusLabels = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'shipped' => 'Đã gửi',
            'delivered' => 'Đã giao',
            'cancelled' => 'Đã hủy'
        ];
        
        // Order statistics data
        foreach ($this->statuses as $status) {
            $currentCount = $data['current_stats']['status_counts'][$status] ?? 0;
            $previousCount = $data['previous_stats']['status_counts'][$status] ?? 0;
            $difference = $currentCount - $previousCount;
            $changeRate = $previousCount > 0 ? round(($difference / $previousCount) * 100, 2) : 0;
            
            $sheet->setCellValue('A' . $currentRow, $statusLabels[$status]);
            $sheet->setCellValue('B' . $currentRow, $currentCount);
            $sheet->setCellValue('C' . $currentRow, $previousCount);
            $sheet->setCellValue('D' . $currentRow, $difference);
            $sheet->setCellValue('E' . $currentRow, $changeRate . '%');
            
            $sheet->getStyle('A' . $currentRow . ':E' . $currentRow)->applyFromArray($dataStyle);
            $currentRow++;
        }
        
        // Auto-resize columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Output file
        $filename = 'Thong_ke_don_hang_' . $data['period_type'] . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        file_put_contents('debug.log', "Setting headers for file: $filename\n", FILE_APPEND);
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');
        header('Expires: 0');
        
        $writer = new Xlsx($spreadsheet);
        
        file_put_contents('debug.log', "Saving Excel file...\n", FILE_APPEND);
        
        $writer->save('php://output');
        
        file_put_contents('debug.log', "Excel file sent successfully\n", FILE_APPEND);
        
        exit;
    }
    
    /**
     * Lấy thống kê sản phẩm theo tháng (tất cả các ngày trong tháng)
     */
    private function getProductStatsByMonth($selectedMonth, $selectedYear) {
        if (!$this->conn) return [];
        
        $sql = "SELECT 
                    p.product_name,
                    DATE(o.order_date) as order_date,
                    SUM(oi.quantity) as daily_quantity,
                    SUM(oi.total_price) as daily_sales
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE MONTH(o.order_date) = ? 
                AND YEAR(o.order_date) = ? 
                AND o.status = 'delivered'
                GROUP BY oi.product_id, p.product_name, DATE(o.order_date)
                ORDER BY p.product_name, order_date";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Product monthly query prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return [];
        }
        
        $stmt->bind_param("ii", $selectedMonth, $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->formatProductMonthlyStats($results);
    }

    /**
     * Lấy thống kê sản phẩm theo quý
     */
    private function getProductStatsByQuarter($selectedQuarter, $selectedYear) {
        if (!$this->conn) return [];
        
        $quarterMonths = $this->getQuarterMonths($selectedQuarter);
        
        $sql = "SELECT 
                    p.product_name,
                    MONTH(o.order_date) as order_month,
                    SUM(oi.quantity) as monthly_quantity,
                    SUM(oi.total_price) as monthly_sales
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE MONTH(o.order_date) IN (" . implode(',', $quarterMonths) . ") 
                AND YEAR(o.order_date) = ? 
                AND o.status = 'delivered'
                GROUP BY oi.product_id, p.product_name, MONTH(o.order_date)
                ORDER BY p.product_name, order_month";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Product quarterly query prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return [];
        }
        
        $stmt->bind_param("i", $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->formatProductQuarterlyStats($results);
    }

    /**
     * Lấy thống kê sản phẩm theo năm
     */
    private function getProductStatsByYear($selectedYear) {
        if (!$this->conn) return [];
        
        $sql = "SELECT 
                    p.product_name,
                    MONTH(o.order_date) as order_month,
                    SUM(oi.quantity) as monthly_quantity,
                    SUM(oi.total_price) as monthly_sales
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.order_id
                JOIN products p ON oi.product_id = p.product_id
                WHERE YEAR(o.order_date) = ? 
                AND o.status = 'delivered'
                GROUP BY oi.product_id, p.product_name, MONTH(o.order_date)
                ORDER BY p.product_name, order_month";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Product yearly query prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return [];
        }
        
        $stmt->bind_param("i", $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->formatProductYearlyStats($results);
    }

    // ==================== THỐNG KÊ DOANH THU ====================

    /**
     * Lấy thống kê doanh thu theo ngày
     */
    private function getRevenueByDate($selectedDate) {
        if (!$this->conn) return ['total_revenue' => 0, 'delivered_orders' => 0];
        
        $sql = "SELECT 
                    COUNT(*) as delivered_orders,
                    COALESCE(SUM(total_amount), 0) as total_revenue
                FROM orders 
                WHERE DATE(order_date) = ? 
                AND status = 'delivered'";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Revenue daily query prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return ['total_revenue' => 0, 'delivered_orders' => 0];
        }
        
        $stmt->bind_param("s", $selectedDate);
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        
        return $data;
    }

    /**
     * Lấy thống kê doanh thu theo tháng (chi tiết từng ngày)
     */
    private function getRevenueByMonth($selectedMonth, $selectedYear) {
        if (!$this->conn) return [];
        
        $sql = "SELECT 
                    DATE(order_date) as order_date,
                    COUNT(*) as delivered_orders,
                    COALESCE(SUM(total_amount), 0) as daily_revenue
                FROM orders 
                WHERE MONTH(order_date) = ? 
                AND YEAR(order_date) = ? 
                AND status = 'delivered'
                GROUP BY DATE(order_date)
                ORDER BY order_date";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Revenue monthly query prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return [];
        }
        
        $stmt->bind_param("ii", $selectedMonth, $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $results;
    }

    /**
     * Lấy thống kê doanh thu theo quý
     */
    private function getRevenueByQuarter($selectedQuarter, $selectedYear) {
        if (!$this->conn) return [];
        
        $quarterMonths = $this->getQuarterMonths($selectedQuarter);
        
        $sql = "SELECT 
                    MONTH(order_date) as order_month,
                    COUNT(*) as delivered_orders,
                    COALESCE(SUM(total_amount), 0) as monthly_revenue
                FROM orders 
                WHERE MONTH(order_date) IN (" . implode(',', $quarterMonths) . ") 
                AND YEAR(order_date) = ? 
                AND status = 'delivered'
                GROUP BY MONTH(order_date)
                ORDER BY order_month";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Revenue quarterly query prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return [];
        }
        
        $stmt->bind_param("i", $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $results;
    }

    /**
     * Lấy thống kê doanh thu theo năm
     */
    private function getRevenueByYear($selectedYear) {
        if (!$this->conn) return [];
        
        $sql = "SELECT 
                    MONTH(order_date) as order_month,
                    COUNT(*) as delivered_orders,
                    COALESCE(SUM(total_amount), 0) as monthly_revenue
                FROM orders 
                WHERE YEAR(order_date) = ? 
                AND status = 'delivered'
                GROUP BY MONTH(order_date)
                ORDER BY order_month";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            file_put_contents('debug.log', "Revenue yearly query prepare failed: " . $this->conn->error . "\n", FILE_APPEND);
            return [];
        }
        
        $stmt->bind_param("i", $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $results;
    }

    // ==================== CẬP NHẬT CÁC HÀM CHÍNH ====================

    /**
     * Cập nhật hàm getMonthStatistics để có đầy đủ dữ liệu
     */
    private function getMonthStatistics($selectedMonth, $selectedYear) {
        file_put_contents('debug.log', "Getting month stats for: $selectedMonth/$selectedYear\n", FILE_APPEND);
        
        $previousMonth = $selectedMonth == 1 ? 12 : $selectedMonth - 1;
        $previousYear = $selectedMonth == 1 ? $selectedYear - 1 : $selectedYear;
        
        if (!$this->conn) {
            file_put_contents('debug.log', "No database connection for month stats, using dummy data\n", FILE_APPEND);
            return $this->getDummyMonthData($selectedMonth, $selectedYear, $previousMonth, $previousYear);
        }
        
        try {
            // Lấy thống kê tổng quan theo tháng
            $currentStats = $this->getOrderStatsByMonth($selectedMonth, $selectedYear);
            $previousStats = $this->getOrderStatsByMonth($previousMonth, $previousYear);
            
            // Lấy thống kê sản phẩm chi tiết
            $productStats = $this->getProductStatsByMonth($selectedMonth, $selectedYear);
            $previousProductStats = $this->getProductStatsByMonth($previousMonth, $previousYear);
            
            // Lấy thống kê doanh thu chi tiết
            $revenueStats = $this->getRevenueByMonth($selectedMonth, $selectedYear);
            $previousRevenueStats = $this->getRevenueByMonth($previousMonth, $previousYear);
            
            return [
                'period_type' => 'Tháng',
                'current_period' => "Tháng $selectedMonth/$selectedYear",
                'previous_period' => "Tháng $previousMonth/$previousYear",
                'current_stats' => $currentStats,
                'previous_stats' => $previousStats,
                'product_stats' => $productStats,
                'previous_product_stats' => $previousProductStats,
                'revenue_stats' => $revenueStats,
                'previous_revenue_stats' => $previousRevenueStats
            ];
            
        } catch (Exception $e) {
            file_put_contents('debug.log', "Database error in month stats: " . $e->getMessage() . "\n", FILE_APPEND);
            return $this->getDummyMonthData($selectedMonth, $selectedYear, $previousMonth, $previousYear);
        }
    }

    /**
     * Cập nhật hàm getQuarterStatistics
     */
    private function getQuarterStatistics($selectedQuarter, $selectedYear) {
        file_put_contents('debug.log', "Getting quarter stats for: Q$selectedQuarter/$selectedYear\n", FILE_APPEND);
        
        $previousQuarter = $this->getPreviousQuarter($selectedQuarter, $selectedYear);
        
        if (!$this->conn) {
            return $this->getDummyQuarterData($selectedQuarter, $selectedYear, $previousQuarter);
        }
        
        try {
            $currentStats = $this->getOrderStatsByQuarter($selectedQuarter, $selectedYear);
            $previousStats = $this->getOrderStatsByQuarter($previousQuarter['quarter'], $previousQuarter['year']);
            
            $productStats = $this->getProductStatsByQuarter($selectedQuarter, $selectedYear);
            $previousProductStats = $this->getProductStatsByQuarter($previousQuarter['quarter'], $previousQuarter['year']);
            
            $revenueStats = $this->getRevenueByQuarter($selectedQuarter, $selectedYear);
            $previousRevenueStats = $this->getRevenueByQuarter($previousQuarter['quarter'], $previousQuarter['year']);
            
            return [
                'period_type' => 'Quý',
                'current_period' => "Quý $selectedQuarter/$selectedYear",
                'previous_period' => "Quý {$previousQuarter['quarter']}/{$previousQuarter['year']}",
                'current_stats' => $currentStats,
                'previous_stats' => $previousStats,
                'product_stats' => $productStats,
                'previous_product_stats' => $previousProductStats,
                'revenue_stats' => $revenueStats,
                'previous_revenue_stats' => $previousRevenueStats
            ];
            
        } catch (Exception $e) {
            file_put_contents('debug.log', "Database error in quarter stats: " . $e->getMessage() . "\n", FILE_APPEND);
            return $this->getDummyQuarterData($selectedQuarter, $selectedYear, $previousQuarter);
        }
    }

    /**
     * Cập nhật hàm getYearStatistics
     */
    private function getYearStatistics($selectedYear) {
        file_put_contents('debug.log', "Getting year stats for: $selectedYear\n", FILE_APPEND);
        
        $previousYear = $selectedYear - 1;
        
        if (!$this->conn) {
            return $this->getDummyYearData($selectedYear, $previousYear);
        }
        
        try {
            $currentStats = $this->getOrderStatsByYear($selectedYear);
            $previousStats = $this->getOrderStatsByYear($previousYear);
            
            $productStats = $this->getProductStatsByYear($selectedYear);
            $previousProductStats = $this->getProductStatsByYear($previousYear);
            
            $revenueStats = $this->getRevenueByYear($selectedYear);
            $previousRevenueStats = $this->getRevenueByYear($previousYear);
            
            return [
                'period_type' => 'Năm',
                'current_period' => "Năm $selectedYear",
                'previous_period' => "Năm $previousYear",
                'current_stats' => $currentStats,
                'previous_stats' => $previousStats,
                'product_stats' => $productStats,
                'previous_product_stats' => $previousProductStats,
                'revenue_stats' => $revenueStats,
                'previous_revenue_stats' => $previousRevenueStats
            ];
            
        } catch (Exception $e) {
            file_put_contents('debug.log', "Database error in year stats: " . $e->getMessage() . "\n", FILE_APPEND);
            return $this->getDummyYearData($selectedYear, $previousYear);
        }
    }

    // ==================== CÁC HÀM HỖ TRỢ ====================

    /**
     * Lấy thống kê đơn hàng theo tháng
     */
    private function getOrderStatsByMonth($selectedMonth, $selectedYear) {
        if (!$this->conn) return ['total_orders' => 0, 'total_revenue' => 0, 'status_counts' => []];
        
        $sql = "SELECT 
                    status,
                    COUNT(*) as order_count,
                    COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END), 0) as total_revenue
                FROM orders 
                WHERE MONTH(order_date) = ? AND YEAR(order_date) = ?
                GROUP BY status";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['total_orders' => 0, 'total_revenue' => 0, 'status_counts' => []];
        
        $stmt->bind_param("ii", $selectedMonth, $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->formatOrderStats($results);
    }

    /**
     * Lấy thống kê đơn hàng theo quý
     */
    private function getOrderStatsByQuarter($selectedQuarter, $selectedYear) {
        if (!$this->conn) return ['total_orders' => 0, 'total_revenue' => 0, 'status_counts' => []];
        
        $quarterMonths = $this->getQuarterMonths($selectedQuarter);
        
        $sql = "SELECT 
                    status,
                    COUNT(*) as order_count,
                    COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END), 0) as total_revenue
                FROM orders 
                WHERE MONTH(order_date) IN (" . implode(',', $quarterMonths) . ") 
                AND YEAR(order_date) = ?
                GROUP BY status";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['total_orders' => 0, 'total_revenue' => 0, 'status_counts' => []];
        
        $stmt->bind_param("i", $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->formatOrderStats($results);
    }

    /**
     * Lấy thống kê đơn hàng theo năm
     */
    private function getOrderStatsByYear($selectedYear) {
        if (!$this->conn) return ['total_orders' => 0, 'total_revenue' => 0, 'status_counts' => []];
        
        $sql = "SELECT 
                    status,
                    COUNT(*) as order_count,
                    COALESCE(SUM(CASE WHEN status = 'delivered' THEN total_amount ELSE 0 END), 0) as total_revenue
                FROM orders 
                WHERE YEAR(order_date) = ?
                GROUP BY status";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return ['total_orders' => 0, 'total_revenue' => 0, 'status_counts' => []];
        
        $stmt->bind_param("i", $selectedYear);
        $stmt->execute();
        $result = $stmt->get_result();
        $results = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        
        return $this->formatOrderStats($results);
    }

    /**
     * Lấy các tháng trong quý
     */
    private function getQuarterMonths($quarter) {
        $quarters = [
            1 => [1, 2, 3],
            2 => [4, 5, 6],
            3 => [7, 8, 9],
            4 => [10, 11, 12]
        ];
        
        return $quarters[$quarter] ?? [1, 2, 3];
    }

    /**
     * Format dữ liệu sản phẩm theo tháng
     */
    private function formatProductMonthlyStats($results) {
        $formatted = [];
        foreach ($results as $result) {
            $productName = $result['product_name'];
            if (!isset($formatted[$productName])) {
                $formatted[$productName] = [
                    'product_name' => $productName,
                    'daily_stats' => [],
                    'total_quantity' => 0,
                    'total_sales' => 0
                ];
            }
            
            $formatted[$productName]['daily_stats'][] = [
                'date' => $result['order_date'],
                'quantity' => $result['daily_quantity'],
                'sales' => $result['daily_sales']
            ];
            
            $formatted[$productName]['total_quantity'] += $result['daily_quantity'];
            $formatted[$productName]['total_sales'] += $result['daily_sales'];
        }
        
        return array_values($formatted);
    }

    /**
     * Format dữ liệu sản phẩm theo quý
     */
    private function formatProductQuarterlyStats($results) {
        $formatted = [];
        $monthNames = [
            1 => 'Tháng 1', 2 => 'Tháng 2', 3 => 'Tháng 3',
            4 => 'Tháng 4', 5 => 'Tháng 5', 6 => 'Tháng 6',
            7 => 'Tháng 7', 8 => 'Tháng 8', 9 => 'Tháng 9',
            10 => 'Tháng 10', 11 => 'Tháng 11', 12 => 'Tháng 12'
        ];
        
        foreach ($results as $result) {
            $productName = $result['product_name'];
            if (!isset($formatted[$productName])) {
                $formatted[$productName] = [
                    'product_name' => $productName,
                    'monthly_stats' => [],
                    'total_quantity' => 0,
                    'total_sales' => 0
                ];
            }
            
            $formatted[$productName]['monthly_stats'][] = [
                'month' => $monthNames[$result['order_month']],
                'quantity' => $result['monthly_quantity'],
                'sales' => $result['monthly_sales']
            ];
            
            $formatted[$productName]['total_quantity'] += $result['monthly_quantity'];
            $formatted[$productName]['total_sales'] += $result['monthly_sales'];
        }
        
        return array_values($formatted);
    }

    /**
     * Format dữ liệu sản phẩm theo năm
     */
    private function formatProductYearlyStats($results) {
        return $this->formatProductQuarterlyStats($results); // Sử dụng chung format
    }

    // ==================== DUMMY DATA CHO TEST ====================

    private function getDummyMonthData($selectedMonth, $selectedYear, $previousMonth, $previousYear) {
        return [
            'period_type' => 'Tháng',
            'current_period' => "Tháng $selectedMonth/$selectedYear",
            'previous_period' => "Tháng $previousMonth/$previousYear",
            'current_stats' => [
                'total_orders' => 45,
                'total_revenue' => 7500000,
                'status_counts' => [
                    'pending' => 8, 'confirmed' => 5, 'processing' => 6,
                    'shipped' => 12, 'delivered' => 12, 'cancelled' => 2
                ]
            ],
            'previous_stats' => [
                'total_orders' => 38,
                'total_revenue' => 6200000,
                'status_counts' => [
                    'pending' => 6, 'confirmed' => 4, 'processing' => 5,
                    'shipped' => 10, 'delivered' => 10, 'cancelled' => 3
                ]
            ],
            'product_stats' => [
                [
                    'product_name' => 'Thức ăn cho chó',
                    'daily_stats' => [
                        ['date' => '2024-01-01', 'quantity' => 5, 'sales' => 250000],
                        ['date' => '2024-01-02', 'quantity' => 3, 'sales' => 150000]
                    ],
                    'total_quantity' => 30,
                    'total_sales' => 1500000
                ]
            ],
            'revenue_stats' => [
                ['order_date' => '2024-01-01', 'delivered_orders' => 3, 'daily_revenue' => 450000],
                ['order_date' => '2024-01-02', 'delivered_orders' => 2, 'daily_revenue' => 320000]
            ]
        ];
    }

    private function getDummyQuarterData($selectedQuarter, $selectedYear, $previousQuarter) {
        return [
            'period_type' => 'Quý',
            'current_period' => "Quý $selectedQuarter/$selectedYear",
            'previous_period' => "Quý {$previousQuarter['quarter']}/{$previousQuarter['year']}",
            'current_stats' => [
                'total_orders' => 135,
                'total_revenue' => 22500000,
                'status_counts' => [
                    'pending' => 24, 'confirmed' => 15, 'processing' => 18,
                    'shipped' => 36, 'delivered' => 36, 'cancelled' => 6
                ]
            ],
            'previous_stats' => [
                'total_orders' => 114,
                'total_revenue' => 18600000,
                'status_counts' => [
                    'pending' => 18, 'confirmed' => 12, 'processing' => 15,
                    'shipped' => 30, 'delivered' => 30, 'cancelled' => 9
                ]
            ]
        ];
    }

    private function getDummyYearData($selectedYear, $previousYear) {
    return [
        'period_type' => 'Năm',
        'current_period' => "Năm $selectedYear",
        'previous_period' => "Năm $previousYear",
        'current_stats' => [
            'total_orders' => 540,
            'total_revenue' => 90000000,
            'status_counts' => [
                'pending' => 96, 'confirmed' => 60, 'processing' => 72,
                'shipped' => 144, 'delivered' => 144, 'cancelled' => 24
            ]
        ],
        'previous_stats' => [
            'total_orders' => 456,
            'total_revenue' => 74400000,
            'status_counts' => [
                'pending' => 72, 'confirmed' => 48, 'processing' => 60,
                'shipped' => 120, 'delivered' => 120, 'cancelled' => 36
            ]
        ]
    ];
    }
}

// Khởi tạo và chạy
try {
    // Kiểm tra database connection
    if (!isset($database)) {
        file_put_contents('debug.log', "Database variable not set, trying to connect manually...\n", FILE_APPEND);
        // Thử kết nối database manually nếu cần
        $database = null; // hoặc tạo connection mới
    }
    
    $excelController = new ExcelController($database);
    $excelController->exportStatistics();
    
} catch (Exception $e) {
    file_put_contents('debug.log', "Fatal error: " . $e->getMessage() . "\n", FILE_APPEND);
    
    // Clear output buffer
    if (ob_get_contents()) {
        ob_clean();
    }
    
    header('Content-Type: text/plain');
    echo 'Fatal Error: ' . $e->getMessage();
    exit;
}
?>