<?php
// controllers/LoginController.php

include('../../config/config.php'); // Kết nối CSDL
include('../models/User.php');      // Gọi model
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $emailOrUsername = $_POST['email']; // từ input login
    $password = $_POST['password'];

    $userModel = new User($conn);
    $user = $userModel->login($emailOrUsername);

    if ($user) {
        if (password_verify($password, $user['password_hash'])) {
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
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
