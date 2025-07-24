<?php
session_start();
require_once(__DIR__ . '/../../config/config.php');
require_once(__DIR__ . '/../models/Order.php');
require_once(__DIR__ . '/../models/OrderItem.php');
require_once(__DIR__ . '/../models/Cart.php');
require_once(__DIR__ . '/../models/Product.php');

// Cấu hình VNPay - PHẢI GIỐNG HỆT VỚI FILE GỬI
$vnp_HashSecret = "NOH6MBGNLQL9O9OMMFMZ2AX8NIEP50W1";

// Lấy các tham số từ VNPay
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
$vnp_Amount = ($_GET['vnp_Amount'] ?? 0) / 100; // Chia cho 100 để có số tiền gốc
$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_TransactionStatus = $_GET['vnp_TransactionStatus'] ?? '';
$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
$vnp_TransactionNo = $_GET['vnp_TransactionNo'] ?? '';
$vnp_BankCode = $_GET['vnp_BankCode'] ?? '';
$vnp_PayDate = $_GET['vnp_PayDate'] ?? '';

// Tạo lại chữ ký để so sánh
$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

// QUAN TRỌNG: Loại bỏ vnp_SecureHash khỏi data để tính toán
unset($inputData['vnp_SecureHash']);

// QUAN TRỌNG: Sắp xếp theo key (bắt buộc)
ksort($inputData);

// Tạo query string - PHẢI GIỐNG HỆT VỚI CÁCH TẠO KHI GỬI
$query = "";
foreach ($inputData as $key => $value) {
    $query .= urlencode($key) . "=" . urlencode($value) . '&';
}
$query = rtrim($query, '&'); // Xóa dấu & cuối cùng

// Tính secure hash
$secureHash = hash_hmac('sha512', $query, $vnp_HashSecret);

// Kiểm tra chữ ký
if ($secureHash == $vnp_SecureHash) {
    // Kiểm tra kết quả thanh toán
    if ($vnp_ResponseCode == '00' && $vnp_TransactionStatus == '00') {
        // Thanh toán thành công
        // Khởi tạo models
        $orderModel = new Order($conn);
        $orderItemModel = new OrderItem($conn);
        $cartModel = new Cart($conn);
        $productModel = new Product($conn);
        
        // Kiểm tra xem đơn hàng đã được xử lý chưa
        $existingOrder = $orderModel->getOrderByNumber($vnp_TxnRef);
        
        if (isset($_SESSION['checkout_data'])) {
            try {

                if (!$existingOrder) {
                    // Bắt đầu transaction
                    $conn->begin_transaction();

                    try {
                        $checkout_data = $_SESSION['checkout_data'];

                        // Chuẩn bị dữ liệu đơn hàng theo format của PaymentController
                        $user_id = $checkout_data['user_id'] ?? null;
                        
                        // Lấy giỏ hàng trước khi tạo đơn
                        $cart_items = [];
                        if ($user_id) {
                            // User đã đăng nhập - lấy từ database
                            $cart_items = $cartModel->getCartByUserId($user_id);
                        } else {
                            // Guest user - lấy từ session
                            $cart_items = $_SESSION['cart'] ?? [];
                        }

                        if (empty($cart_items)) {
                            throw new Exception("Giỏ hàng trống");
                        }

                        // Tính tổng tiền từ cart items để verify
                        $calculated_total = 0;
                        foreach ($cart_items as $item) {
                            $unit_price = isset($item['price']) ? $item['price'] : ($item['product_price'] ?? 0);
                            $quantity = $item['quantity'] ?? 1;
                            $calculated_total += $unit_price * $quantity;
                        }

                        // Kiểm tra tổng tiền có khớp không (cho phép sai lệch nhỏ do làm tròn)
                        if (abs($calculated_total - $vnp_Amount) > 1) {
                            error_log("Amount mismatch: Calculated: $calculated_total, VNPay: $vnp_Amount");
                        }

                        // Tạo shipping address JSON
                        $shipping_address = json_encode([
                            'first_name' => $checkout_data['first_name'] ?? '',
                            'last_name' => $checkout_data['last_name'] ?? '',
                            'company_name' => $checkout_data['company_name'] ?? '',
                            'country' => $checkout_data['country'] ?? '',
                            'street_address' => $checkout_data['street_address'] ?? '',
                            'city' => $checkout_data['city'] ?? '',
                            'state' => $checkout_data['state'] ?? '',
                            'zip_code' => $checkout_data['zip_code'] ?? '',
                            'phone' => $checkout_data['phone'] ?? '',
                            'email' => $checkout_data['email'] ?? ''
                        ]);

                        // Tạo notes chi tiết
                        $notes = sprintf(
                            "Thanh toán qua VNPay - Mã GD: %s - Ngân hàng: %s - Thời gian: %s",
                            $vnp_TransactionNo,
                            $vnp_BankCode,
                            $vnp_PayDate
                        );

                        // Dữ liệu đơn hàng - tuân thủ structure của Order model
                        $order_data = [
                            'userCustomer_id' => $user_id,
                            'order_number' => $vnp_TxnRef,
                            'order_date' => date('Y-m-d H:i:s'),
                            'status' => 'pending', // Trạng thái đơn hàng
                            'total_amount' => $vnp_Amount,
                            'payment_status' => 'paid', // Thanh toán đã hoàn thành
                            'shipping_address' => $shipping_address,
                            'notes' => $notes,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];

                        // Tạo đơn hàng bằng Order model
                        $order_id = $orderModel->createOrder($order_data);
                        if (!$order_id) {
                            throw new Exception("Không thể tạo đơn hàng");
                        }

                        // Tạo order items - tuân thủ structure của OrderItem model
                        foreach ($cart_items as $item) {
                            $unit_price = isset($item['price']) ? $item['price'] : ($item['product_price'] ?? 0);
                            $quantity = $item['quantity'] ?? 1;
                            $total_price = $unit_price * $quantity;

                            $order_item_data = [
                                'order_id' => $order_id,
                                'product_id' => $item['product_id'],
                                'quantity' => $quantity,
                                'unit_price' => $unit_price,
                                'total_price' => $total_price,
                                'created_at' => date('Y-m-d H:i:s')
                            ];

                            if (!$orderItemModel->createOrderItem($order_item_data)) {
                                throw new Exception("Không thể tạo chi tiết đơn hàng cho sản phẩm ID: " . $item['product_id']);
                            }

                            // ↓↓↓ Trừ stock
                            $product_id = $item['product_id'];
                            $stock_quantity = $productModel->getStockQuantity($product_id); // Bạn cần tạo hàm này
                            $new_stock = $stock_quantity - $quantity;

                            if ($new_stock < 0) {
                                throw new Exception("Sản phẩm ID $product_id không đủ hàng trong kho.");
                            }

                            $productModel->updateStockQuantity($product_id, $new_stock); // Bạn cần tạo hàm này
                        }

                        // Xóa giỏ hàng sau khi tạo đơn thành công
                        if ($user_id) {
                            // User đã đăng nhập - xóa từ database
                            if (!$cartModel->clearCartByUserId($user_id)) {
                                error_log("Warning: Could not clear cart for user $user_id");
                            }
                        } else {
                            // Guest user - xóa từ session
                            unset($_SESSION['cart']);
                        }

                        // Commit transaction
                        $conn->commit();

                        // Lưu thông tin đơn hàng thành công vào session để hiển thị
                        $_SESSION['order_success'] = [
                            'order_id' => $order_id,
                            'order_number' => $vnp_TxnRef,
                            'total_amount' => $vnp_Amount,
                            'payment_method' => 'VNPay',
                            'transaction_no' => $vnp_TransactionNo,
                            'bank_code' => $vnp_BankCode,
                            'pay_date' => $vnp_PayDate,
                            'status' => 'success'
                        ];

                        // Xóa checkout data vì đã xử lý xong
                        unset($_SESSION['checkout_data']);

                        // Redirect tới trang thành công
                        header("Location: ../../views/pages/order_success.php?order_id=" . $order_id . "&order_number=" . $vnp_TxnRef);
                        exit;

                    } catch (Exception $e) {
                        // Rollback transaction nếu có lỗi
                        $conn->rollback();
                        error_log("VNPay order processing error: " . $e->getMessage());
                        
                        // Redirect về checkout với thông báo lỗi
                        header("Location: ../../views/pages/checkout.php?error=processing_failed&message=" . urlencode("Lỗi xử lý đơn hàng: " . $e->getMessage()));
                        exit;
                    }

                } else {
                    // Đơn hàng đã tồn tại - có thể là user refresh trang
                    error_log("Order already exists: " . $vnp_TxnRef);
                    
                    // Vẫn redirect tới trang thành công với thông tin đơn hàng có sẵn
                    $_SESSION['order_success'] = [
                        'order_id' => $existingOrder['order_id'],
                        'order_number' => $existingOrder['order_number'],
                        'total_amount' => $existingOrder['total_amount'],
                        'payment_method' => 'VNPay',
                        'transaction_no' => $vnp_TransactionNo,
                        'bank_code' => $vnp_BankCode,
                        'pay_date' => $vnp_PayDate,
                        'status' => 'existing'
                    ];
                    
                    header("Location: ../../views/pages/order_success.php?order_id=" . $existingOrder['order_id'] . "&order_number=" . $vnp_TxnRef);
                    exit;
                }
                
            } catch (Exception $e) {
                error_log("VNPay return handler error: " . $e->getMessage());
                header("Location: ../../views/pages/checkout.php?error=system_error&message=" . urlencode("Lỗi hệ thống khi xử lý đơn hàng"));
                exit;
            }
        } else {
            // FALLBACK: Session checkout_data bị mất - thử khôi phục từ database
            error_log("VNPay return: No checkout data in session, attempting recovery for order: " . $vnp_TxnRef);
            
            // Nếu đơn hàng đã tồn tại, redirect tới success page
            if ($existingOrder) {
                $_SESSION['order_success'] = [
                    'order_id' => $existingOrder['order_id'],
                    'order_number' => $existingOrder['order_number'],
                    'total_amount' => $existingOrder['total_amount'],
                    'payment_method' => 'VNPay',
                    'transaction_no' => $vnp_TransactionNo,
                    'bank_code' => $vnp_BankCode,
                    'pay_date' => $vnp_PayDate,
                    'status' => 'recovered'
                ];
                
                header("Location: ../../views/pages/order_success.php?order_id=" . $existingOrder['order_id'] . "&order_number=" . $vnp_TxnRef);
                exit;
            }
            
            // Nếu không có session và không có order existing, thử khôi phục từ user session
            if (isset($_SESSION['user_id'])) {
                $user_id = $_SESSION['user_id'];
                
                try {
                    // Lấy cart hiện tại của user để tạo đơn hàng mới
                    $cart_items = $cartModel->getCartByUserId($user_id);
                    
                    if (!empty($cart_items)) {
                        // Tính tổng để verify với VNPay amount
                        $calculated_total = 0;
                        foreach ($cart_items as $item) {
                            $unit_price = isset($item['price']) ? $item['price'] : ($item['product_price'] ?? 0);
                            $quantity = $item['quantity'] ?? 1;
                            $calculated_total += $unit_price * $quantity;
                        }
                        
                        // Nếu amount khớp (cho phép sai lệch 1 đồng do làm tròn)
                        if (abs($calculated_total - $vnp_Amount) <= 1) {
                            // Bắt đầu transaction để tạo đơn hàng
                            $conn->begin_transaction();
                            
                            try {
                                // Tạo shipping address mặc định (sẽ được update sau)
                                $shipping_address = json_encode([
                                    'first_name' => '',
                                    'last_name' => '',
                                    'company_name' => '',
                                    'country' => '',
                                    'street_address' => '',
                                    'city' => '',
                                    'state' => '',
                                    'zip_code' => '',
                                    'phone' => '',
                                    'email' => ''
                                ]);
                                
                                $notes = sprintf(
                                    "Đơn hàng được khôi phục từ session - Thanh toán VNPay - Mã GD: %s - Ngân hàng: %s",
                                    $vnp_TransactionNo,
                                    $vnp_BankCode
                                );
                                
                                // Tạo order data
                                $order_data = [
                                    'userCustomer_id' => $user_id,
                                    'order_number' => $vnp_TxnRef,
                                    'order_date' => date('Y-m-d H:i:s'),
                                    'status' => 'pending',
                                    'total_amount' => $vnp_Amount,
                                    'payment_status' => 'paid',
                                    'shipping_address' => $shipping_address,
                                    'notes' => $notes,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                ];
                                
                                $order_id = $orderModel->createOrder($order_data);
                                if (!$order_id) {
                                    throw new Exception("Không thể tạo đơn hàng khôi phục");
                                }
                                
                                // Tạo order items
                                foreach ($cart_items as $item) {
                                    $unit_price = isset($item['price']) ? $item['price'] : ($item['product_price'] ?? 0);
                                    $quantity = $item['quantity'] ?? 1;
                                    $total_price = $unit_price * $quantity;
                                    
                                    $order_item_data = [
                                        'order_id' => $order_id,
                                        'product_id' => $item['product_id'],
                                        'quantity' => $quantity,
                                        'unit_price' => $unit_price,
                                        'total_price' => $total_price,
                                        'created_at' => date('Y-m-d H:i:s')
                                    ];
                                    
                                    if (!$orderItemModel->createOrderItem($order_item_data)) {
                                        throw new Exception("Không thể tạo order item cho product ID: " . $item['product_id']);
                                    }

                                    // ↓↓↓ Trừ stock
                                    $product_id = $item['product_id'];
                                    $stock_quantity = $productModel->getStockQuantity($product_id); // Bạn cần tạo hàm này
                                    if($stock_quantity > $quantity){
                                        $new_stock = $stock_quantity - $quantity;
                                    }

                                    if ($new_stock < 0) {
                                        throw new Exception("Sản phẩm ID $product_id không đủ hàng trong kho.");
                                    }

                                    $productModel->updateStockQuantity($product_id, $new_stock); // Bạn cần tạo hàm này
                                }
                                
                                // Xóa cart sau khi tạo đơn thành công
                                $cartModel->clearCartByUserId($user_id);
                                
                                $conn->commit();
                                
                                // Set success session
                                $_SESSION['order_success'] = [
                                    'order_id' => $order_id,
                                    'order_number' => $vnp_TxnRef,
                                    'total_amount' => $vnp_Amount,
                                    'payment_method' => 'VNPay',
                                    'transaction_no' => $vnp_TransactionNo,
                                    'bank_code' => $vnp_BankCode,
                                    'pay_date' => $vnp_PayDate,
                                    'status' => 'recovered'
                                ];
                                
                                error_log("Successfully recovered VNPay order: " . $vnp_TxnRef . " for user: " . $user_id);
                                header("Location: ../../views/pages/order_success.php?order_id=" . $order_id . "&order_number=" . $vnp_TxnRef);
                                exit;
                                
                            } catch (Exception $e) {
                                $conn->rollback();
                                error_log("Failed to recover VNPay order: " . $e->getMessage());
                                throw $e;
                            }
                        } else {
                            error_log("Amount mismatch in recovery: Cart=$calculated_total, VNPay=$vnp_Amount");
                        }
                    } else {
                        error_log("No cart items found for user recovery: " . $user_id);
                    }
                } catch (Exception $e) {
                    error_log("Error during order recovery: " . $e->getMessage());
                }
            }
            
            // Nếu không thể khôi phục, thông báo lỗi
            error_log("Cannot recover VNPay order: " . $vnp_TxnRef . " - redirecting to error page");
            header("Location: ../../views/pages/checkout.php?error=session_expired&message=" . urlencode("Không thể khôi phục đơn hàng. Vui lòng liên hệ hỗ trợ với mã giao dịch: " . $vnp_TxnRef));
            exit;
        }
        
    } else {
        // Thanh toán thất bại - xử lý các mã lỗi từ VNPay
        $error_messages = [
            '07' => 'Giao dịch bị nghi ngờ (liên quan tới lừa đảo)',
            '09' => 'Thẻ/Tài khoản chưa đăng ký dịch vụ InternetBanking',
            '10' => 'Xác thực thông tin thẻ/tài khoản không đúng quá 3 lần',
            '11' => 'Đã hết hạn chờ thanh toán',
            '12' => 'Thẻ/Tài khoản bị khóa',
            '13' => 'Nhập sai mật khẩu xác thực giao dịch (OTP)',
            '24' => 'Khách hàng hủy giao dịch',
            '51' => 'Tài khoản không đủ số dư',
            '65' => 'Tài khoản đã vượt quá hạn mức giao dịch trong ngày',
            '75' => 'Ngân hàng thanh toán đang bảo trì',
            '79' => 'Nhập sai mật khẩu thanh toán quá số lần quy định'
        ];
        
        $error_message = isset($error_messages[$vnp_ResponseCode]) ? 
                        $error_messages[$vnp_ResponseCode] : 
                        'Thanh toán thất bại - Mã lỗi: ' . $vnp_ResponseCode;
        
        error_log("VNPay payment failed - Code: $vnp_ResponseCode, Transaction: $vnp_TxnRef");
        
        header("Location: ../../views/pages/checkout.php?error=payment_failed&message=" . urlencode($error_message));
        exit;
    }
    
} else {
    // Chữ ký không hợp lệ - có thể bị tấn công
    error_log("VNPay signature verification failed - Expected: " . $secureHash . " - Received: " . $vnp_SecureHash);
    header("Location: ../../views/pages/checkout.php?error=invalid_signature&message=" . urlencode("Xác thực thanh toán không hợp lệ"));
    exit;
}
?>