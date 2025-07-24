<?php
// Kiểm tra xem có thông báo nào được gửi từ login.php 
$loginMessage = isset($_GET['message']) ? $_GET['message'] : '';
$username = isset($_GET['username']) ? htmlspecialchars($_GET['username']) : '';
$full_name = isset($_GET['name']) ? htmlspecialchars($_GET['name']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in || Sign up</title>
    <link rel="stylesheet" href="../../fontawesome-free-6.4.2-web/css/all.min.css">
    <link rel="stylesheet" href="../../public/css/login_register_style.css">
</head>
<body>
    <?php if (!empty($loginMessage)): ?>
        <div class="alert">
            <p><?php echo htmlspecialchars($loginMessage); ?></p>
        </div>
    <?php endif; ?>

    <div class="container" id="container">
        <div class="form-container sign-up-container">
            <form action="../../app/controllers/RegisterController.php" method="POST">
                <h1>Create Account</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>or use your email for registration</span>
                <div class="infield">
                    <input type="text" placeholder="Name" name="name" id="fullNameInput" />
                    <label></label>
                </div>

                <div class="infield infield_btn">
                    <input type="text" id="username" name="username" placeholder="User Name"
                        value="<?php echo isset($_GET['username']) ? htmlspecialchars($_GET['username']) : ''; ?>" required />
                    <button type="button" class="btn" id="generateUsername" style="padding: 0.3rem 0.7rem;"><i class="fa-solid fa-arrow-rotate-left btn_icon"></i></button>
                    <label></label>
                </div>

                <div class="infield">
                    <input type="email" placeholder="Email" name="email"/>
                    <label></label>
                </div>
                <div class="infield">
                    <input type="password" placeholder="Password" name="password"/>
                    <label></label>
                </div>
                <button>Sign Up</button>
                <p class="switch-login">Already have an account? Sign In</p>
            </form>
        </div>
        <div class="form-container sign-in-container">
            <form action="../../app/controllers/LoginController.php" method="POST">
                <h1>Sign in</h1>
                <div class="social-container">
                    <a href="#" class="social"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social"><i class="fab fa-google-plus-g"></i></a>
                    <a href="#" class="social"><i class="fab fa-linkedin-in"></i></a>
                </div>
                <span>or use your account</span>
                <div class="infield">
                    <input type="text" placeholder="Email or User name" name="email"/>
                    <label></label>
                </div>
                <div class="infield">
                    <input type="password" placeholder="Password" name="password"/>
                    <label></label>
                </div>
                <a href="#" class="forgot">Forgot your password?</a>
                <button>Sign In</button>
                <p class="switch-register">Don't have an account? Sign Up</p>
            </form>
        </div>
        <div class="overlay-container" id="overlayCon">
            <div class="overlay">
                <div class="overlay-panel overlay-left">
                    <h1>Welcome Back!</h1>
                    <p>To keep connected with us please login with your personal info</p>
                    <button>Sign In</button>
                </div>
                <div class="overlay-panel overlay-right">
                    <h1>Hello, Friend!</h1>
                    <p>Enter your personal details and start journey with us</p>
                    <button>Sign Up</button>
                </div>
            </div>
            <button id="overlayBtn"></button>
        </div>
    </div>

    <!-- js -->
    <script src="../../public/js/login_register.js"></script>

</body>
</html>