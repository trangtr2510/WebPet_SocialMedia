<?php
    $server = 'localhost';
    $user = 'root';
    $pass = '';
    $database = 'petshop_socialmedia';

    $conn = new mysqli($server, $user, $pass, $database);
    if ($conn->connect_error) {
        die("Kết nối thất bại: " . $conn->connect_error);
    }
?>