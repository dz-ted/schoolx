<?php
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
  <meta charset="UTF-8">
  <title>لوحة التحكم</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
  <h2>مرحبًا بك <?php echo $_SESSION['user']; ?> في نظام إدارة الموظفين</h2>
  <a href="indexteacher.php" class="btn btn-info">📋 عرض الموظفين</a>
  <a href="add.php" class="btn btn-success">➕ إضافة موظف</a>
  <a href="logout.php" class="btn btn-danger">🚪 تسجيل الخروج</a>
</body>
</html>