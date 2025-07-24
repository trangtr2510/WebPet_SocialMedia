<?php
    $server = 'localhost';
    $user = 'root';
    $pass = '';
    $database = 'petshop_socialmedia';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $conn = new mysqli($server, $user, $pass, $database);
    $mysqli = new mysqli($server, $user, $pass, $database);
    $conn->set_charset("utf8mb4");
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }
?>