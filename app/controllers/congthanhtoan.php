<?php
session_start();
include_once(__DIR__ . '/../../config/config.php');
include_once(__DIR__ . '/../models/Order.php');
include_once(__DIR__ . '/../models/OrderItem.php');
include_once(__DIR__ . '/../models/Cart.php');

class PaymentController
{
    private $conn;
    private $orderModel;
    private $orderItemModel;
    private $cartModel;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->orderModel = new Order($db);
        $this->orderItemModel = new OrderItem($db);
        $this->cartModel = new Cart($db);
    }
    
    public function vnpay_payment()
    {
        date_default_timezone_set('Asia/Ho_Chi_Minh');
        $data = $_POST; 
        $code_cart = rand(00, 9999);
        $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
        $vnp_Returnurl = "http://web.local/WebsitePet/app/controllers/vnpay_return.php";
        $vnp_TmnCode = "1VYBIYQP"; //Mã website tại VNPAY 
        $vnp_HashSecret = "NOH6MBGNLQL9O9OMMFMZ2AX8NIEP50W1"; //Chuỗi bí mật

        $vnp_TxnRef = $code_cart; //Mã đơn hàng. Trong thực tế Merchant cần insert đơn hàng vào DB và gửi mã này sang VNPAY
        $vnp_OrderInfo = 'Thanh toán đơn hàng test';
        $vnp_OrderType = 'billpayment';
        $sotien_formatted = $_POST['sotien'] ?? '0';
        $sotien_clean = (int) str_replace(['.', ',', '₫', ' VNĐ', ' '], '', $sotien_formatted);
        $vnp_Amount = $sotien_clean * 100;
        $vnp_Locale = 'vn';
        // $vnp_BankCode = 'NCB';
        $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];

        $inputData = array(
            "vnp_Version" => "2.1.0",
            "vnp_TmnCode" => $vnp_TmnCode,
            "vnp_Amount" => $vnp_Amount,
            "vnp_Command" => "pay",
            "vnp_CreateDate" => date('YmdHis'),
            "vnp_ExpireDate" => date('YmdHis', strtotime('+15 minutes')), // ✅ Thêm dòng này
            "vnp_CurrCode" => "VND",
            "vnp_IpAddr" => $vnp_IpAddr,
            "vnp_Locale" => $vnp_Locale,
            "vnp_OrderInfo" => $vnp_OrderInfo,
            "vnp_OrderType" => $vnp_OrderType,
            "vnp_ReturnUrl" => $vnp_Returnurl,
            "vnp_TxnRef" => $vnp_TxnRef,
        );

        if (isset($vnp_BankCode) && $vnp_BankCode != "") {
        $inputData['vnp_BankCode'] = $vnp_BankCode;
        }
        if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
        $inputData['vnp_Bill_State'] = $vnp_Bill_State;
        }

        //var_dump($inputData);
        ksort($inputData);
        $query = "";
        $i = 0;
        $hashdata = "";
        foreach ($inputData as $key => $value) {
        if ($i == 1) {
            $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
        } else {
            $hashdata .= urlencode($key) . "=" . urlencode($value);
            $i = 1;
        }
        $query .= urlencode($key) . "=" . urlencode($value) . '&';
        }

        $vnp_Url = $vnp_Url . "?" . $query;
        if (isset($vnp_HashSecret)) {
        $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //  
        $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
        }
        $returnData = array(
        'code' => '00',
        'message' => 'success',
        'data' => $vnp_Url
        );
        if (isset($_POST['redirect'])) {
        header('Location: ' . $vnp_Url);
        die();
        } else {
        echo json_encode($returnData);
        }
    }

}

// Xử lý request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['redirect'])) {
    try {
        $paymentController = new PaymentController($conn);
        $paymentController->vnpay_payment();
    } catch (Exception $e) {
        echo "Lỗi xử lý thanh toán: " . $e->getMessage();
        // Redirect về trang checkout với thông báo lỗi
        header("Location: ../../views/pages/checkout.php?error=1");
        exit;
    }
}
?>