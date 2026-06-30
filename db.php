<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); 
}

$host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'inventory_db';

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("فشل الاتصال بقاعدة البيانات: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function sanitize($data) {
    global $conn;
    return mysqli_real_escape_string($conn, trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')));
}
?>