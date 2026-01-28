<?php
session_start();
include("db.php");

// التحقق من تسجيل الدخول
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// جلب بيانات المستخدم الحالي للتحقق من الصلاحيات
$user_id = $_SESSION['user'];
$stmt_user = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
$stmt_user->bind_param("s", $user_id);
$stmt_user->execute();
$user_data = $stmt_user->get_result()->fetch_assoc();
$stmt_user->close();

// التحقق من صلاحية الحذف
if(!$user_data || $user_data['can_delete'] != 1){
    exit("❌ ليست لديك صلاحية حذف العامل!");
}

// التحقق من وجود معرف العامل
$employee_id = $_GET['id'] ?? '';
if(!$employee_id){
    exit("❌ لم يتم تحديد العامل للحذف!");
}

// التحقق من وجود العامل في قاعدة البيانات
$stmt_check = $conn->prepare("SELECT * FROM workers_data WHERE employee_id=?");
$stmt_check->bind_param("s", $employee_id);
$stmt_check->execute();
$worker = $stmt_check->get_result()->fetch_assoc();
$stmt_check->close();

if(!$worker){
    exit("❌ العامل غير موجود!");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>حذف العامل</title>
<style>
body { font-family: Arial, sans-serif; background:#f0f2f5; margin:0; padding:0; }
.container { max-width:500px; margin:50px auto; background:#fff; padding:20px; border-radius:12px; box-shadow:0 0 15px rgba(0,0,0,0.2); text-align:center; }
h2 { color:#c00000; margin-bottom:20px; }
button { padding:10px 20px; border:none; border-radius:6px; cursor:pointer; font-size:16px; margin:5px; }
.confirm-btn { background:#c00000; color:#fff; }
.cancel-btn { background:#6c757d; color:#fff; }
.confirm-btn:hover { opacity:0.9; }
.cancel-btn:hover { opacity:0.9; }
</style>
<script>
function confirmDeletion(){
    if(confirm("⚠ هل أنت متأكد من حذف هذا العامل؟")){
        window.location.href = "delete_worker_confirm.php?id=<?= htmlspecialchars($employee_id) ?>";
    }
}
</script>
</head>
<body>
<div class="container">
<h2>حذف العامل: <?= htmlspecialchars($worker['full_name']) ?></h2>
<p>هل تريد حقاً حذف هذا العامل؟ لن تتمكن من التراجع عن هذه العملية.</p>
<button class="confirm-btn" onclick="confirmDeletion()">حذف العامل</button>
<a href="index_workers.php"><button class="cancel-btn">إلغاء</button></a>
</div>
</body>
</html>