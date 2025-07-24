<?php
// controllers/LoginController.php

include('../../config/config.php'); // Kết nối CSDL
include('../models/User.php');      // Gọi model
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $emailOrUsername = trim($_POST['email']); // Từ input login
    $password = $_POST['password'];

    // Kiểm tra rỗng
    if (empty($emailOrUsername)) {
        header("Location: ../../views/auth/login_register.php?message=Vui lòng nhập email hoặc username");
        exit();
    }

    if (empty($password)) {
        header("Location: ../../views/auth/login_register.php?message=Vui lòng nhập mật khẩu");
        exit();
    }

    // Nếu người dùng nhập định dạng email thì kiểm tra email hợp lệ
    if (strpos($emailOrUsername, '@') !== false && !filter_var($emailOrUsername, FILTER_VALIDATE_EMAIL)) {
        header("Location: ../../views/auth/login_register.php?message=Email không hợp lệ");
        exit();
    }

    $userModel = new User($conn);
    $user = $userModel->login($emailOrUsername);

    if ($user) {
        // Kiểm tra tài khoản bị khóa
        if (isset($user['is_active']) && $user['is_active'] == 0) {
            header("Location: ../../views/auth/login_register.php?message=Tài khoản của bạn đã bị cấm");
            exit();
        }

        // Kiểm tra mật khẩu
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['address'] = $user['address'];
            $_SESSION['phone'] = $user['phone'];
            $_SESSION['img'] = $user['img'];

            // Cập nhật thời gian đăng nhập
            $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $stmt->bind_param("i", $user['user_id']);
            $stmt->execute();

            header("Location: ../../index.php");
            exit();
        } else {
            header("Location: ../../views/auth/login_register.php?message=Mật khẩu không đúng");
            exit();
        }
    } else {
        header("Location: ../../views/auth/login_register.php?message=Email hoặc Username không tồn tại");
        exit();
    }
}
