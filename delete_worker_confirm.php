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

// الحذف
$stmt_del = $conn->prepare("DELETE FROM workers_data WHERE employee_id=?");
$stmt_del->bind_param("s", $employee_id);
if($stmt_del->execute()){
    $stmt_del->close();
    // حذف الصورة الشخصية إذا موجودة
    if(!empty($worker['photo']) && $worker['photo'] != 'avatar.png'){
        $photo_path = "uploads/workers/" . $worker['photo'];
        if(file_exists($photo_path)) unlink($photo_path);
    }
    // إعادة التوجيه مع رسالة نجاح
    header("Location: index_workers.php?msg=تم حذف العامل بنجاح");
    exit();
}else{
    $stmt_del->close();
    exit("❌ حدث خطأ أثناء حذف العامل");
}
?>