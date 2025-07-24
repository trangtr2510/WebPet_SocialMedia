<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../../vendor/autoload.php'; 

include_once(__DIR__ .'/../../config/config.php');
include_once(__DIR__ .'/../../app/models/User.php');

session_start();

$is_logged_in = isset($_SESSION['user_id']);
$username = $is_logged_in ? $_SESSION['username'] : '';
$full_name = $is_logged_in ? $_SESSION['full_name'] : '';
$img = ($is_logged_in && !empty($_SESSION['img'])) ? $_SESSION['img'] : 'default.jpg';

$userModel = new User($conn);

// Xử lý khi người dùng gửi form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $message = $conn->real_escape_string($_POST['message']);

    // Lưu vào cơ sở dữ liệu
    $sql = "INSERT INTO customer_contacts (name, email, phone, message, created_at) 
            VALUES ('$name', '$email', '$phone', '$message', NOW())";

    if ($conn->query($sql) === TRUE) {
        // Gửi email bằng PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Cấu hình SMTP
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com'; // SMTP Server của Gmail
            $mail->SMTPAuth   = true;
            $mail->Username   = 'changcherchou@gmail.com'; // Email 
            $mail->Password   = 'upknoiodhfilswjy'; //  mật khẩu ứng dụng
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            // Cấu hình email gửi
            $mail->setFrom($email, $name); // Email người gửi từ form
            $mail->addAddress('changcherchou@gmail.com', 'Admin PetPro'); // Email nhận
            $mail->addReplyTo($email, $name); // Trả lời đến email người gửi

            // Nội dung email
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Encoding = 'base64';
            $mail->Subject = "Liên hệ mới từ khách hàng - $name";
            $mail->Body    = "
                <h3>Thông tin khách hàng:</h3>
                <p><strong>Họ và tên:</strong> $name</p>
                <p><strong>Email:</strong> $email</p>
                <p><strong>Số điện thoại:</strong> $phone</p>
                <p><strong>Nội dung:</strong> $message</p>
                <br>
                <p>Vui lòng kiểm tra và phản hồi sớm nhất có thể.</p>
            ";

            // Gửi email
            if ($mail->send()) {
                $success_message = "Cám ơn bạn đã liên hệ. Chúng tôi sẽ phản hồi sớm nhất có thể.";
            } else {
                $error_message = "Email không thể gửi. Vui lòng thử lại.";
            }
        } catch (Exception $e) {
            $error_message = "Gửi email thất bại: {$mail->ErrorInfo}";
        }
    } else {
        $error_message = "Lỗi: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liên hệ - PetPro</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/styles.css">
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../style/contact_style.css">
    <style>
        /* Header section */
        .header-section {
            background-color: #fff;
            padding: 20px 0;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
        }

        .header-section h1 {
            font-size: 2.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .breadcrumb {
            color: #666;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #666;
            text-decoration: none;
        }

        /* Main container */
        .container_contact {
            max-width: 1200px;
            margin: 0 auto;
            padding: 60px 20px;
            display: flex;
            gap: 60px;
            align-items: flex-start;
        }

        /* Left side - Contact Form */
        .contact-form {
            flex: 1;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .contact-form h2 {
            font-size: 1.8rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .contact-form p {
            color: #666;
            margin-bottom: 30px;
            font-size: 0.9rem;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-group {
            flex: 1;
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 0.9rem;
            background: #f8f9fa;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #007bff;
            background: #fff;
        }

        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }

        .submit-btn {
            background-color: #8B4513;
            color: #fff;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
            background-color: #6B3410;
        }

        /* Right side - Contact Info */
        .contact-info {
            flex: 0 0 350px;
            background: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .info-section {
            margin-bottom: 40px;
        }

        .info-section h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
        }

        .info-item {
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .info-item strong {
            color: #333;
            font-weight: 600;
        }

        /* Social Media Section */
        .social-section {
            margin-top: 40px;
        }

        .social-section h3 {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .social-icons {
            display: flex;
            gap: 15px;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            background-color: #FFA500;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            text-decoration: none;
            font-size: 1.1rem;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .social-icon:hover {
            background-color: #FF8C00;
            transform: translateY(-2px);
        }

        /* Alert styles */
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container_contact {
                flex-direction: column;
                gap: 30px;
                padding: 30px 15px;
                align-items: center;
            }

            .contact-info {
                display: none;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .contact-form,
            .contact-info {
                padding: 30px 20px;
            }

            .header-section h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <header class="header header_category">
        <nav class="nav nav_category">
            <div class="container nav_container">
                <button class="hamburger_btn" type="button">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="nav_left">
                    <a href="../../index.php" class="nav_logo">
                        <img src="../../public/images/logo/logo.png" alt="PetShop Logo">
                    </a>
                    <ul class="nav_list">
                        <li class="nav_item"><a href="../../index.php" class="nav_link">Home</a></li>
                        <li class="nav_item">
                            <a href="./category.php" class="nav_link">Category</a>
                            <div class="category_dropdown">
                                <a href="./category.php?category_id=1" class="category_dropdown_item">
                                    <i class="fa-solid fa-paw"></i> Thú cưng
                                </a>
                                <a href="./category.php?category_id=8" class="category_dropdown_item">
                                    <i class="fa-solid fa-box"></i> Sản phẩm
                                </a>
                            </div>
                        </li>
                        <li class="nav_item">
                            <a href="./social_media.php" class="nav_link">Social Media</a>
                        </li>
                        <li class="nav_item"><a href="#" class="nav_link">About</a></li>
                        <li class="nav_item"><a href="./contact.php" class="nav_link">Contact</a></li>
                    </ul>
                </div>
                <div class="nav_right">
                    <!-- Search Box -->
                    <div class="search_box" style = "display: none;">
                        <input type="text" class="search_input" placeholder="Tìm kiếm sản phẩm...">
                        <i class="fa-solid fa-search search_icon"></i>
                    </div>
                    
                    <!-- Account Dropdown -->
                    <div class="account_dropdown">
                        <button class="icon_btn account_btn">
                            <i class="fa-solid fa-user"></i>
                            
                        </button>
                        <div class="notify_box_login_register">
                            <?php if ($is_logged_in): ?>
                                <div class="user_info">
                                    <img src="../../public/uploads/avatar/<?php echo htmlspecialchars($img); ?>" alt="Avatar" class="user_avatar">
                                    <!-- Hoặc nếu không có avatar, dùng placeholder -->
                                    <!-- <div class="user_avatar placeholder"><?php echo strtoupper(substr($full_name, 0, 1)); ?></div> -->
                                    <p class="user_name"><?php echo htmlspecialchars($full_name); ?><br>
                                    <?php if ($is_logged_in): ?>
                                        <span class="user_name"><?php echo htmlspecialchars($username); ?></span>
                                    <?php endif; ?></p>
                                </div>
                                <?php if ($is_logged_in && ($userModel->isAdmin($_SESSION) || $userModel->isEmployee($_SESSION))): ?>
                                    <a href="../../views/admin/dashboard.php" class="auth_btn myOrder">
                                        <i class="fa-solid fa-gear"></i>
                                        Quản lý
                                    </a>
                                    <?php else: ?>
                                        <a href="./myAccount.php?tab=my-orders" class="auth_btn myOrder">
                                            <i class="fa-solid fa-shopping-bag"></i>
                                            Đơn hàng cá nhân
                                        </a>
                                    <?php endif; ?>
                                <a href="./myAccount.php" class="auth_btn myAccount">
                                    <i class="fa-solid fa-user-cog"></i>
                                    Tài khoản
                                </a>
                                <a href="../../app/controllers/LogoutController.php" class="auth_btn logout">
                                    <i class="fa-solid fa-sign-out-alt"></i>
                                    Đăng xuất
                                </a>
                            <?php else: ?>
                                <a href="#" class="auth_btn login">Đăng nhập</a>
                                <a href="#" class="auth_btn register">Đăng ký</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Cart -->
                    <button class="icon_btn cart_btn">
                        <i class="fa-solid fa-shopping-cart"></i>
                        <span class="cart_badge">3</span>
                    </button>
                </div>
            </div>
        </nav>
    </header>

    <!-- Mobile Menu Overlay -->
    <div class="mobile_overlay"></div>

    <!-- Mobile Menu Sidebar -->
    <div class="mobile_menu" id="mobileMenu">
        <div class="mobile_menu_header">
            <a href="../../index.php" class="mobile_menu_logo">
                <img src="../../public/images/logo/logo.png" alt="PetShop Logo">
            </a>
            <button class="close_btn" type="button">
                <i class="fa-solid fa-angle-left"></i>
            </button>
        </div>

        <!-- Mobile Search -->
        <div class="mobile_search_box">
            <input type="text" class="mobile_search_input" placeholder="Tìm kiếm sản phẩm...">
        </div>

        <!-- Mobile Navigation -->
        <ul class="mobile_nav_list">
            <li class="mobile_nav_item">
                <a href="../../index.php" class="mobile_nav_link">Home</a>
            </li>
            <li class="mobile_nav_item">
                <a href="./views/pages/category.html" class="mobile_nav_link">
                    Category
                    <i class="fa-solid fa-chevron-down" style="float: right; transition: transform 0.3s ease;"></i>
                </a>
                <div class="mobile_category_dropdown" id="mobileCategoryDropdown">
                    <a href="#" class="mobile_category_item">Thú cưng</a>
                    <a href="#" class="mobile_category_item">Sản phẩm</a>
                </div>
            </li>
            <li class="mobile_nav_item">
                <a href="#" class="mobile_nav_link">Social Media</a>
            </li>
            <li class="mobile_nav_item">
                <a href="#" class="mobile_nav_link">About</a>
            </li>
            <li class="mobile_nav_item">
                <a href="#" class="mobile_nav_link">Contact</a>
            </li>
        </ul>

        <!-- Mobile Auth Buttons -->
        <div class="mobile_auth_btns">
            <a href="#" class="mobile_auth_btn mobile_login">Đăng nhập</a>
            <a href="#" class="mobile_auth_btn mobile_register">Đăng ký</a>
        </div>

        <!-- Mobile Cart Info -->
        <div style="margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 8px; text-align: center;">
            <i class="fa-solid fa-shopping-cart" style="font-size: 24px; color: #007bff; margin-bottom: 10px;"></i>
            <p style="margin: 0; color: #666;">Giỏ hàng: <strong>3 sản phẩm</strong></p>
        </div>
    </div>

    <!-- Header Section -->
    <div class="header-section">
        <h1>Contact Us</h1>
        <div class="breadcrumb">
            <a href="#">Home</a> / Contact Us
        </div>
    </div>

    <!-- Main Content -->
    <div class="container_contact">
        <!-- Left Side - Contact Form -->
        <div class="contact-form">
            <h2>Get in Touch</h2>
            <p>Your email address will not be published. Required fields are marked *</p>
            
            <form id="contactForm" method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Your Name *</label>
                        <input type="text" id="name" name="name" placeholder="Ex. John Doe" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" placeholder="example@gmail.com" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <input type="text" id="subject" name="subject" placeholder="Enter Subject" required>
                </div>
                
                <div class="form-group">
                    <label for="message">Your Message *</label>
                    <textarea id="message" name="message" placeholder="Enter here..." required></textarea>
                </div>
                
                <button type="submit" class="submit-btn">Send Message</button>
            </form>
        </div>

        <!-- Right Side - Contact Info -->
        <div class="contact-info">
            <div class="info-section">
                <h3>Address</h3>
                <div class="info-item">
                    4517 Washington Ave. Manchester, Kentucky 39495
                </div>
            </div>
            
            <div class="info-section">
                <h3>Contact</h3>
                <div class="info-item">
                    <strong>Phone:</strong> +0123456789
                </div>
                <div class="info-item">
                    <strong>Email:</strong> example@gmail.com
                </div>
            </div>
            
            <div class="info-section">
                <h3>Open Time</h3>
                <div class="info-item">
                    <strong>Monday - Friday:</strong> 10:00 - 20:00
                </div>
                <div class="info-item">
                    <strong>Saturday - Sunday:</strong> 11:00 - 18:00
                </div>
            </div>
            
            <div class="social-section">
                <h3>Stay Connected</h3>
                <div class="social-icons">
                    <a href="#" class="social-icon">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-icon">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="social-icon">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="#" class="social-icon">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-icon">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="map-container">
        <iframe
            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.4241674197876!2d106.66408937483384!3d10.77646358929867!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f1b7c3ed289%3A0xa06651894598e488!2zMjgzIEPDoWNoIE3huqFuZyBUaMOhbmcgVMOhbSwgUGjGsOG7nW5nIDEyLCBRdeG6rW4gMTAsIFRow6BuaCBwaOG7kSBI4buTIENow60gTWluaCwgVmlldG5hbQ!5e0!3m2!1sen!2s!4v1709967020370!5m2!1sen!2s"
            width="100%"
            height="100%"
            style="border:0;"
            allowfullscreen=""
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade">
        </iframe>
    </div>

    <footer class="footer">
        <div class="container footer_container">
            <div class="footer_top">
                <h2 class="footer_top_heading">Register now so you don't miss our programs</h2>
                <form action="#" class="footer_top_form">
                    <input type="text" class="footer_top_form_input" placeholder="Enter your email...">
                    <button class="footer_top_form_btn">Register</button>
                </form>
            </div>
            <div class="section__container footer__container">
                <div class="footer__col">
                    <div class="footer__logo">
                        <a href="#"><img src="../../public/images/logo/logo.png" alt=""></a>
                    </div>
                </div>
                <div class="footer__col">
                    <h4>Company</h4>
                    <ul class="footer__links">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Services</a></li>
                        <li><a href="#">Store</a></li>
                        <li><a href="#">Pricing</a></li>
                        <li><a href="#">Blog</a></li>
                        <li><a href="#">Faq</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>
                <div class="footer__col">
                    <h4>Address</h4>
                    <ul class="footer__links">
                        <li><a href="#">New Delhi, India</a></li>
                        <li><a href="#">View on Maps</a></li>
                    </ul>
                    <br />
                    <h4>Inquiries</h4>
                    <ul class="footer__links">
                        <li><a href="#">+91 0987654321</a></li>
                        <li><a href="#">info@website.com</a></li>
                    </ul>
                </div>
                <div class="footer__col">
                    <h4>Newsletter</h4>
                    <p>Stay updated with out latest news</p>
                    <form action="/">
                        <input type="text" placeholder="Your email" />
                        <button class="btn">
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </form>
                    <br />
                    <h4>Follow Us</h4>
                    <ul class="footer__socials">
                        <li>
                            <a href="#"><i class="ri-facebook-fill"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-twitter-fill"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-youtube-fill"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-pinterest-line"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-instagram-line"></i></a>
                        </li>
                        <li>
                            <a href="#"><i class="ri-tiktok-fill"></i></a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
    
    <script src="/WebsitePet/public/js/index.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('contactForm');
            
            form.addEventListener('submit', function(event) {
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const message = document.getElementById('message').value.trim();
                
                let isValid = true;
                
                // Simple validation
                if (name === '') {
                    isValid = false;
                    showError('name', 'Vui lòng nhập tên của bạn');
                } else {
                    clearError('name');
                }
                
                if (email === '') {
                    isValid = false;
                    showError('email', 'Vui lòng nhập email của bạn');
                } else if (!isValidEmail(email)) {
                    isValid = false;
                    showError('email', 'Vui lòng nhập email hợp lệ');
                } else {
                    clearError('email');
                }
                
                if (phone === '') {
                    isValid = false;
                    showError('phone', 'Vui lòng nhập số điện thoại của bạn');
                } else if (!isValidPhone(phone)) {
                    isValid = false;
                    showError('phone', 'Vui lòng nhập số điện thoại hợp lệ');
                } else {
                    clearError('phone');
                }
                
                if (message === '') {
                    isValid = false;
                    showError('message', 'Vui lòng nhập nội dung');
                } else {
                    clearError('message');
                }
                
                if (!isValid) {
                    event.preventDefault();
                }
            });
            
            function showError(fieldId, message) {
                const field = document.getElementById(fieldId);
                
                // Create error element if it doesn't exist
                let errorElement = field.nextElementSibling;
                if (!errorElement || !errorElement.classList.contains('error-message')) {
                    errorElement = document.createElement('div');
                    errorElement.className = 'error-message';
                    errorElement.style.color = 'red';
                    errorElement.style.fontSize = '0.85rem';
                    errorElement.style.marginTop = '5px';
                    field.parentNode.insertBefore(errorElement, field.nextSibling);
                }
                
                errorElement.textContent = message;
                field.style.borderColor = 'red';
            }
            
            function clearError(fieldId) {
                const field = document.getElementById(fieldId);
                const errorElement = field.nextElementSibling;
                
                if (errorElement && errorElement.classList.contains('error-message')) {
                    errorElement.remove();
                }
                
                field.style.borderColor = '';
            }
            
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            function isValidPhone(phone) {
                const phoneRegex = /^[0-9+\-\s]{7,15}$/;
                return phoneRegex.test(phone);
            }
        });

        function updateCartInfo() {
            fetch("total_cart.php")
                .then(response => response.json())
                .then(data => {
                    document.querySelector(".num").textContent = data.count;
                    document.querySelector(".total").textContent = data.total;
                })
                .catch(error => console.error("Lỗi khi lấy dữ liệu giỏ hàng:", error));
        }

        // Gọi hàm ngay khi trang tải và cập nhật sau mỗi 10 giây
        updateCartInfo();
    </script>
</body>
</html>