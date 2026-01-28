<?php
$servername = "localhost";
$username = "root"; // عدّل حسب المستخدم لديك
$password = "";     // عدّل حسب كلمة المرور
$dbname = "employees_db";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);
$conn->set_charset("utf8mb4");

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>