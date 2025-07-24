<?php
// vnpay_debug.php - Tool debug chi tiết
session_start();
include_once(__DIR__ . '/../../config/config.php');

$vnp_HashSecret = "1X2OLL0XP2AAAOFBUYVYKU11GAPH6TE3";

echo "<!DOCTYPE html>";
echo "<html><head><title>VNPay Debug</title>";
echo "<style>
body { font-family: Arial; margin: 20px; }
.section { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
.error { background: #ffebee; border-left: 4px solid #f44336; }
.success { background: #e8f5e9; border-left: 4px solid #4caf50; }
.warning { background: #fff3e0; border-left: 4px solid #ff9800; }
.code { background: #263238; color: #fff; padding: 10px; border-radius: 3px; font-family: monospace; overflow-x: auto; }
table { width: 100%; border-collapse: collapse; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background: #f2f2f2; }
</style>";
echo "</head><body>";

echo "<h1>🔍 VNPay Debug Tool</h1>";

// 1. Kiểm tra GET parameters
echo "<div class='section'>";
echo "<h2>📥 GET Parameters từ VNPay:</h2>";
if (empty($_GET)) {
    echo "<div class='warning'>⚠️ Không có GET parameters. Đây có thể là lần đầu truy cập.</div>";
} else {
    echo "<table>";
    echo "<tr><th>Parameter</th><th>Value</th><th>Length</th></tr>";
    foreach ($_GET as $key => $value) {
        echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td><td>" . strlen($value) . "</td></tr>";
    }
    echo "</table>";
}
echo "</div>";

if (!empty($_GET)) {
    // 2. Tách các tham số VNPay
    echo "<div class='section'>";
    echo "<h2>🔑 VNPay Parameters:</h2>";
    $vnpParams = array();
    $otherParams = array();
    
    foreach ($_GET as $key => $value) {
        if (substr($key, 0, 4) == "vnp_") {
            $vnpParams[$key] = $value;
        } else {
            $otherParams[$key] = $value;
        }
    }
    
    if (!empty($vnpParams)) {
        echo "<table>";
        echo "<tr><th>VNP Parameter</th><th>Value</th></tr>";
        foreach ($vnpParams as $key => $value) {
            echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    }
    
    if (!empty($otherParams)) {
        echo "<h3>Non-VNP Parameters:</h3>";
        echo "<table>";
        echo "<tr><th>Parameter</th><th>Value</th></tr>";
        foreach ($otherParams as $key => $value) {
            echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // 3. Lấy hash từ VNPay
    $receivedHash = $_GET['vnp_SecureHash'] ?? '';
    echo "<div class='section'>";
    echo "<h2>🔐 Hash từ VNPay:</h2>";
    echo "<div class='code'>$receivedHash</div>";
    echo "</div>";

    // 4. Chuẩn bị data để tính hash
    echo "<div class='section'>";
    echo "<h2>⚙️ Chuẩn bị dữ liệu cho hash:</h2>";
    
    // Loại bỏ vnp_SecureHash
    $inputData = $vnpParams;
    unset($inputData['vnp_SecureHash']);
    
    echo "<h3>📋 Data sau khi loại bỏ vnp_SecureHash:</h3>";
    echo "<table>";
    echo "<tr><th>Key</th><th>Value</th><th>URL Encoded Key</th><th>URL Encoded Value</th></tr>";
    foreach ($inputData as $key => $value) {
        echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td><td>" . urlencode($key) . "</td><td>" . urlencode($value) . "</td></tr>";
    }
    echo "</table>";
    
    // Loại bỏ giá trị rỗng
    $filteredData = array_filter($inputData, function($value) {
        return $value !== null && $value !== '';
    });
    
    if (count($filteredData) != count($inputData)) {
        echo "<h3>🧹 Data sau khi loại bỏ giá trị rỗng:</h3>";
        echo "<table>";
        echo "<tr><th>Key</th><th>Value</th></tr>";
        foreach ($filteredData as $key => $value) {
            echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
        }
        echo "</table>";
        $inputData = $filteredData;
    } else {
        echo "<div class='success'>✅ Không có giá trị rỗng nào cần loại bỏ.</div>";
    }
    
    // Sắp xếp
    ksort($inputData);
    echo "<h3>🔤 Data sau khi sắp xếp theo key:</h3>";
    echo "<table>";
    echo "<tr><th>Order</th><th>Key</th><th>Value</th></tr>";
    $order = 1;
    foreach ($inputData as $key => $value) {
        echo "<tr><td>$order</td><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
        $order++;
    }
    echo "</table>";
    echo "</div>";

    // 5. Tạo hash string theo từng bước
    echo "<div class='section'>";
    echo "<h2>🔗 Tạo Hash String:</h2>";
    
    $hashParts = array();
    foreach ($inputData as $key => $value) {
        $hashParts[] = urlencode($key) . "=" . urlencode($value);
    }
    
    echo "<h3>📝 Các phần của hash string:</h3>";
    echo "<ol>";
    foreach ($hashParts as $part) {
        echo "<li><code>" . htmlspecialchars($part) . "</code></li>";
    }
    echo "</ol>";
    
    $hashString = implode('&', $hashParts);
    echo "<h3>🔗 Hash string hoàn chỉnh:</h3>";
    echo "<div class='code'>" . htmlspecialchars($hashString) . "</div>";
    echo "<p><strong>Length:</strong> " . strlen($hashString) . " characters</p>";
    echo "</div>";

    // 6. Tính hash với secret key
    echo "<div class='section'>";
    echo "<h2>🔐 Tính toán Hash:</h2>";
    echo "<p><strong>Secret Key:</strong> <code>$vnp_HashSecret</code></p>";
    echo "<p><strong>Hash Algorithm:</strong> HMAC-SHA512</p>";
    
    $calculatedHash = hash_hmac('sha512', $hashString, $vnp_HashSecret);
    
    echo "<h3>🧮 Hash tính được:</h3>";
    echo "<div class='code'>$calculatedHash</div>";
    echo "</div>";

    // 7. So sánh hash
    echo "<div class='section'>";
    echo "<h2>⚖️ So sánh Hash:</h2>";
    
    echo "<table>";
    echo "<tr><th>Loại</th><th>Hash Value</th><th>Length</th></tr>";
    echo "<tr><td>Hash từ VNPay</td><td class='code'>" . htmlspecialchars($receivedHash) . "</td><td>" . strlen($receivedHash) . "</td></tr>";
    echo "<tr><td>Hash tính được</td><td class='code'>" . htmlspecialchars($calculatedHash) . "</td><td>" . strlen($calculatedHash) . "</td></tr>";
    echo "</table>";
    
    if ($calculatedHash === $receivedHash) {
        echo "<div class='success'>";
        echo "<h3>✅ HASH KHỚP - Chữ ký hợp lệ!</h3>";
        echo "</div>";
    } else {
        echo "<div class='error'>";
        echo "<h3>❌ HASH KHÔNG KHỚP - Chữ ký không hợp lệ!</h3>";
        echo "</div>";
        
        // So sánh từng ký tự
        echo "<h3>🔍 So sánh từng ký tự:</h3>";
        $maxLen = max(strlen($receivedHash), strlen($calculatedHash));
        echo "<table>";
        echo "<tr><th>Position</th><th>VNPay Hash</th><th>Calculated Hash</th><th>Match</th></tr>";
        
        for ($i = 0; $i < $maxLen; $i++) {
            $vnpChar = isset($receivedHash[$i]) ? $receivedHash[$i] : 'N/A';
            $calcChar = isset($calculatedHash[$i]) ? $calculatedHash[$i] : 'N/A';
            $match = ($vnpChar === $calcChar) ? '✅' : '❌';
            
            echo "<tr><td>$i</td><td>$vnpChar</td><td>$calcChar</td><td>$match</td></tr>";
            
            if ($i > 20 && $vnpChar !== $calcChar) {
                echo "<tr><td colspan='4'>... (continuing differences) ...</td></tr>";
                break;
            }
        }
        echo "</table>";
    }
    echo "</div>";

    // 8. Kiểm tra kết quả giao dịch
    if ($calculatedHash === $receivedHash) {
        echo "<div class='section'>";
        echo "<h2>📊 Kết quả giao dịch:</h2>";
        
        $responseCode = $_GET['vnp_ResponseCode'] ?? '';
        $transactionStatus = $_GET['vnp_TransactionStatus'] ?? '';
        $amount = ($_GET['vnp_Amount'] ?? 0) / 100;
        $txnRef = $_GET['vnp_TxnRef'] ?? '';
        
        echo "<table>";
        echo "<tr><td><strong>Mã phản hồi</strong></td><td>$responseCode</td></tr>";
        echo "<tr><td><strong>Trạng thái giao dịch</strong></td><td>$transactionStatus</td></tr>";
        echo "<tr><td><strong>Số tiền</strong></td><td>" . number_format($amount) . " VNĐ</td></tr>";
        echo "<tr><td><strong>Mã giao dịch</strong></td><td>$txnRef</td></tr>";
        echo "</table>";
        
        if ($responseCode == '00' && $transactionStatus == '00') {
            echo "<div class='success'><h3>✅ Giao dịch thành công!</h3></div>";
        } else {
            echo "<div class='error'><h3>❌ Giao dịch thất bại!</h3></div>";
        }
        echo "</div>";
    }
}

// 9. Test tạo hash mẫu
echo "<div class='section'>";
echo "<h2>🧪 Test Hash Generation:</h2>";
echo "<p>Test với dữ liệu mẫu để kiểm tra thuật toán:</p>";

$sampleData = array(
    'vnp_Amount' => '10000000',
    'vnp_Command' => 'pay',
    'vnp_CreateDate' => '20231221120000',
    'vnp_CurrCode' => 'VND',
    'vnp_IpAddr' => '127.0.0.1',
    'vnp_Locale' => 'vn',
    'vnp_OrderInfo' => 'Test payment',
    'vnp_OrderType' => 'billpayment',
    'vnp_ReturnUrl' => 'http://localhost/return',
    'vnp_TmnCode' => '18NMDM3B',
    'vnp_TxnRef' => '12345678',
    'vnp_Version' => '2.1.0'
);

ksort($sampleData);

$sampleHashString = '';
$i = 0;
foreach ($sampleData as $key => $value) {
    if ($i == 1) {
        $sampleHashString .= '&' . urlencode($key) . "=" . urlencode($value);
    } else {
        $sampleHashString .= urlencode($key) . "=" . urlencode($value);
        $i = 1;
    }
}

$sampleHash = hash_hmac('sha512', $sampleHashString, $vnp_HashSecret);

echo "<p><strong>Sample Hash String:</strong></p>";
echo "<div class='code'>" . htmlspecialchars($sampleHashString) . "</div>";
echo "<p><strong>Sample Hash:</strong></p>";
echo "<div class='code'>$sampleHash</div>";
echo "</div>";

echo "<div class='section'>";
echo "<h2>🔧 Hướng dẫn sửa lỗi:</h2>";
echo "<ol>";
echo "<li>Kiểm tra URL return có chính xác không</li>";
echo "<li>Đảm bảo secret key đúng: <code>1X2OLL0XP2AAAOFBUYVYKU11GAPH6TE3</code></li>";
echo "<li>Kiểm tra encoding (UTF-8)</li>";
echo "<li>Đảm bảo không có space thừa trong dữ liệu</li>";
echo "<li>Kiểm tra thứ tự sắp xếp parameters</li>";
echo "</ol>";
echo "</div>";

echo "</body></html>";
?>