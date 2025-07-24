<?php
session_start();
require_once(__DIR__ . '/../../config/config.php');

$order_id = $_GET['order_id'] ?? '';
$order_number = $_GET['order_number'] ?? '';

// Lấy thông tin đơn hàng từ database
$order_info = null;
$order_items = [];

if ($order_id) {
    try {
        // Lấy thông tin đơn hàng
        $stmt = $conn->prepare("
            SELECT o.*, 
                   CASE 
                       WHEN o.shipping_address IS NOT NULL AND o.shipping_address != '' 
                       THEN o.shipping_address 
                       ELSE NULL 
                   END as shipping_info
            FROM orders o 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order_info = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order_info) {
            // Lấy chi tiết đơn hàng
            $stmt = $conn->prepare("
                SELECT oi.*, p.name as product_name, p.image as product_image
                FROM order_items oi
                JOIN products p ON oi.product_id = p.id
                WHERE oi.order_id = ?
            ");
            $stmt->execute([$order_id]);
            $order_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        error_log("Error fetching order details: " . $e->getMessage());
    }
}

// Lấy thông tin từ session nếu có
$session_order_info = $_SESSION['order_success'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - Pet Shop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .success-icon {
            font-size: 4rem;
            color: #28a745;
        }
        .order-summary {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 2rem;
        }
        .order-item {
            border-bottom: 1px solid #dee2e6;
            padding: 1rem 0;
        }
        .order-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="text-center mb-4">
                    <i class="fas fa-check-circle success-icon"></i>
                    <h1 class="mt-3 mb-2">Đặt hàng thành công!</h1>
                    <p class="text-muted">Cảm ơn bạn đã tin tưởng và mua sắm tại Pet Shop</p>
                </div>

                <?php if ($order_info || $session_order_info): ?>
                <div class="order-summary mb-4">
                    <h3 class="mb-3">Thông tin đơn hàng</h3>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Mã đơn hàng:</strong></div>
                        <div class="col-sm-8"><?= htmlspecialchars($order_info['order_number'] ?? $order_number ?? 'N/A') ?></div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Tổng tiền:</strong></div>
                        <div class="col-sm-8 text-primary">
                            <strong><?= number_format($order_info['total_amount'] ?? $session_order_info['total_amount'] ?? 0, 0, ',', '.') ?> VNĐ</strong>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Phương thức thanh toán:</strong></div>
                        <div class="col-sm-8">
                            <?php 
                            $payment_method = 'VNPay';
                            if (isset($session_order_info['bank_code'])) {
                                $payment_method .= ' (' . $session_order_info['bank_code'] . ')';
                            }
                            echo htmlspecialchars($payment_method);
                            ?>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Trạng thái thanh toán:</strong></div>
                        <div class="col-sm-8">
                            <span class="badge bg-success">Đã thanh toán</span>
                        </div>
                    </div>
                    
                    <?php if (isset($session_order_info['transaction_no'])): ?>
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Mã giao dịch:</strong></div>
                        <div class="col-sm-8"><?= htmlspecialchars($session_order_info['transaction_no']) ?></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="row mb-3">
                        <div class="col-sm-4"><strong>Ngày đặt:</strong></div>
                        <div class="col-sm-8">
                            <?= date('d/m/Y H:i:s', strtotime($order_info['created_at'] ?? 'now')) ?>
                        </div>
                    </div>
                </div>

                <?php if (!empty($order_items)): ?>
                <div class="order-summary mb-4">
                    <h3 class="mb-3">Chi tiết đơn hàng</h3>
                    
                    <?php foreach ($order_items as $item): ?>
                    <div class="order-item">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <?php if (!empty($item['product_image'])): ?>
                                <img src="<?= htmlspecialchars($item['product_image']) ?>" 
                                     alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                     class="img-fluid rounded" style="max-height: 60px;">
                                <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 60px;">
                                    <i class="fas fa-image text-muted"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <h5 class="mb-1"><?= htmlspecialchars($item['product_name']) ?></h5>
                                <small class="text-muted">Đơn giá: <?= number_format($item['unit_price'], 0, ',', '.') ?> VNĐ</small>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="badge bg-secondary">x<?= $item['quantity'] ?></span>
                            </div>
                            <div class="col-md-2 text-end">
                                <strong><?= number_format($item['total_price'], 0, ',', '.') ?> VNĐ</strong>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <?php 
                // Hiển thị địa chỉ giao hàng nếu có
                if (isset($order_info['shipping_info']) && !empty($order_info['shipping_info'])) {
                    $shipping_data = json_decode($order_info['shipping_info'], true);
                    if ($shipping_data): 
                ?>
                <div class="order-summary mb-4">
                    <h3 class="mb-3">Địa chỉ giao hàng</h3>
                    <p class="mb-1">
                        <strong><?= htmlspecialchars(($shipping_data['first_name'] ?? '') . ' ' . ($shipping_data['last_name'] ?? '')) ?></strong>
                    </p>
                    <?php if (!empty($shipping_data['company_name'])): ?>
                    <p class="mb-1"><?= htmlspecialchars($shipping_data['company_name']) ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><?= htmlspecialchars($shipping_data['street_address'] ?? '') ?></p>
                    <p class="mb-1">
                        <?= htmlspecialchars(($shipping_data['city'] ?? '') . ', ' . ($shipping_data['state'] ?? '') . ' ' . ($shipping_data['zip_code'] ?? '')) ?>
                    </p>
                    <p class="mb-1"><?= htmlspecialchars($shipping_data['country'] ?? '') ?></p>
                    <?php if (!empty($shipping_data['phone'])): ?>
                        <p class="mb-0"><i class="fas fa-phone"></i> <?= htmlspecialchars($shipping_data['phone']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($shipping_data['email'])): ?>
                    <p class="mb-0"><i class="fas fa-envelope"></i> <?= htmlspecialchars($shipping_data['email']) ?></p>
                    <?php endif; ?>
                </div>
                <?php 
                    endif; 
                } 
                ?>
                
                <div class="text-center mt-4">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Lưu ý:</strong> Chúng tôi sẽ gửi email xác nhận và thông tin theo dõi đơn hàng đến địa chỉ email của bạn trong vòng 24 giờ.
                    </div>
                    
                    <div class="d-grid gap-2 d-md-block">
                        <a href="/WebsitePet/views/pages/category.php" class="btn btn-primary me-2">
                            <i class="fas fa-shopping-bag me-1"></i>
                            Tiếp tục mua sắm
                        </a>
                        
                        <a href="/WebsitePet/index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-history me-1"></i>
                            Về trang chủ
                        </a>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Cần hỗ trợ?</h5>
                        <p class="mb-2">
                            <i class="fas fa-phone text-primary me-1"></i>
                            Hotline: <a href="tel:+84123456789" class="text-decoration-none">+84 123 456 789</a>
                        </p>
                        <p class="mb-0">
                            <i class="fas fa-envelope text-primary me-1"></i>
                            Email: <a href="mailto:support@petshop.vn" class="text-decoration-none">support@petshop.vn</a>
                        </p>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Trường hợp không có thông tin đơn hàng -->
                <div class="alert alert-warning text-center">
                    <i class="fas fa-exclamation-triangle mb-2" style="font-size: 2rem;"></i>
                    <h4>Không tìm thấy thông tin đơn hàng</h4>
                    <p>Có thể đơn hàng chưa được xử lý hoàn tất hoặc đã xảy ra lỗi trong quá trình thanh toán.</p>
                    
                    <?php if ($order_number): ?>
                    <div class="mt-3">
                        <p><strong>Mã đơn hàng:</strong> <?= htmlspecialchars($order_number) ?></p>
                        <small class="text-muted">Vui lòng liên hệ với chúng tôi nếu cần hỗ trợ về đơn hàng này.</small>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="/WebsitePet/views/pages/category.php" class="btn btn-primary me-2">
                            <i class="fas fa-shopping-bag me-1"></i>
                            Tiếp tục mua sắm
                        </a>
                        <a href="/WebsitePet/views/pages/contact.php" class="btn btn-outline-secondary">
                            <i class="fas fa-headset me-1"></i>
                            Liên hệ hỗ trợ
                        </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer thông tin bảo mật -->
    <div class="container-fluid bg-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 text-center mb-3">
                    <i class="fas fa-shield-alt text-success" style="font-size: 2rem;"></i>
                    <h5 class="mt-2">Bảo mật thanh toán</h5>
                    <p class="text-muted small">Giao dịch được bảo vệ bởi VNPay với công nghệ mã hóa SSL 256-bit</p>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <i class="fas fa-truck text-primary" style="font-size: 2rem;"></i>
                    <h5 class="mt-2">Giao hàng nhanh</h5>
                    <p class="text-muted small">Miễn phí giao hàng cho đơn hàng trên 500.000 VNĐ trong nội thành</p>
                </div>
                <div class="col-md-4 text-center mb-3">
                    <i class="fas fa-undo text-warning" style="font-size: 2rem;"></i>
                    <h5 class="mt-2">Đổi trả dễ dàng</h5>
                    <p class="text-muted small">Chính sách đổi trả trong vòng 7 ngày nếu sản phẩm có lỗi</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Script tự động ẩn session success message sau khi hiển thị -->
    <script>
        // Xóa session order_success để tránh hiển thị lại khi refresh
        <?php if (isset($_SESSION['order_success'])): ?>
        // Có thể thêm AJAX call để xóa session nếu cần
        console.log('Order successfully processed');
        <?php unset($_SESSION['order_success']); ?>
        <?php endif; ?>
        
        // Auto scroll to top
        window.scrollTo(0, 0);
        
        // Print functionality (optional)
        function printOrder() {
            window.print();
        }
        
        // Share functionality (optional)
        function shareOrder() {
            if (navigator.share) {
                navigator.share({
                    title: 'Đặt hàng thành công - Pet Shop',
                    text: 'Tôi vừa đặt hàng thành công tại Pet Shop!',
                    url: window.location.href
                });
            } else {
                // Fallback - copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(() => {
                    alert('Link đã được sao chép vào clipboard!');
                });
            }
        }
    </script>
</body>
</html>

<?php
// Log successful order completion for analytics
if ($order_info || $session_order_info) {
    $log_data = [
        'action' => 'order_success_viewed',
        'order_id' => $order_info['id'] ?? 'session',
        'order_number' => $order_info['order_number'] ?? $order_number,
        'total_amount' => $order_info['total_amount'] ?? $session_order_info['total_amount'] ?? 0,
        'user_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    // Ghi log (có thể lưu vào database hoặc file log)
    error_log("ORDER_SUCCESS: " . json_encode($log_data));
}

// Cleanup - xóa các session tạm thời nếu còn
if (isset($_SESSION['temp_order_data'])) {
    unset($_SESSION['temp_order_data']);
}
if (isset($_SESSION['payment_processing'])) {
    unset($_SESSION['payment_processing']);
}
?>