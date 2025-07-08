<?php
// controllers/RegisterController.php

session_start();
include('../../config/config.php');
include('../models/User.php');

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $full_name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role = "customer";

    // Mã hoá mật khẩu
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Chuyển full_name thành username (viết thường, bỏ dấu, không khoảng trắng)
    function createUsername($full_name) {
        // Bỏ dấu tiếng Việt
        $normalized = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $full_name);
        // Bỏ khoảng trắng, ký tự đặc biệt, chuyển về thường
        $normalized = preg_replace('/[^A-Za-z0-9]/', '', strtolower($normalized));
        return $normalized;
    }

    $username = trim($_POST['username']);

    // Nếu username không nhập hoặc rỗng → sinh từ full name
    if (empty($username)) {
        $base_username = createUsername($full_name);
        $username = $base_username;
        $suffix = 1;
        while ($userModel->usernameExists($username)) {
            $username = $base_username . $suffix;
            $suffix++;
        }
    }

    $userModel = new User($conn);
    $existingUser = $userModel->emailExists($email);

    if ($existingUser) {
        header("Location: ../../views/auth/login_register.php?message=Email đã tồn tại!");
        exit();
    } elseif($userModel-> usernameExists($username)){
        header("Location: ../../views/auth/login_register.php?message=Email đã tồn tại!&username=" . urlencode($username));
        exit();
    }
    else {
        $created = $userModel->register($username, $full_name, $email, $password_hash, $role);
        if ($created) {
            header("Location: ../../views/auth/login_register.php?message=Đăng ký thành công! Hãy đăng nhập.");
            exit();
        } else {
            header("Location: ../../views/auth/login_register.php?message=Lỗi khi đăng ký!");
            exit();
        }
    }
}
