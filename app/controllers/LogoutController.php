<?php
// controllers/LogoutController.php

session_start();

// Hủy toàn bộ session
session_unset();
session_destroy();

// Chuyển hướng về trang login (hoặc index nếu bạn muốn)
header("Location: ../../views/auth/login_register.php"); // hoặc: header("Location: ../index.php");
exit();
