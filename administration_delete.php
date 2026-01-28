<?php
session_start();
include("db.php"); // الاتصال بقاعدة البيانات

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// التأكد من وجود معرف الإداري
if(!isset($_GET['id']) || empty($_GET['id'])){
    exit("المعرف غير موجود، لا يمكن الحذف!");
}

$employee_id = $_GET['id'];

// بدء المعاملة
$conn->begin_transaction();
try {
    // حذف البيانات المرتبطة أولاً (اختياري حسب بنية قاعدة البيانات)
    $tables = [
        'administration_accounts',
        'administration_roles',
        'administration_contact_info',
        'administration_qualifications'
    ];

    foreach($tables as $table){
        $stmt = $conn->prepare("DELETE FROM $table WHERE employee_id=?");
        $stmt->bind_param("s", $employee_id);
        $stmt->execute();
    }

    // حذف الإداري نفسه
    $stmt = $conn->prepare("DELETE FROM administration_personal_data WHERE employee_id=?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();

    $conn->commit();

    // إعادة التوجيه مع رسالة نجاح
    header("Location: indexadministrators.php?success=تم الحذف بنجاح");
    exit();
} catch(Exception $e){
    $conn->rollback();
    exit("حدث خطأ أثناء الحذف: " . $e->getMessage());
}
?>