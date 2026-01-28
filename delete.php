<?php
// delete.php
session_start();
include("db.php");

// التأكد من صلاحية الدخول
if(!isset($_SESSION['user']) || !isset($_GET['id'])){
    exit("لا يمكن تنفيذ الحذف");
}

$employee_id = $_GET['id'];

// ===== صلاحية الحذف =====
if(!($_SESSION['can_delete'] ?? 0)){
    exit("<p style='text-align:center; margin-top:50px; font-weight:bold; color:red;'>🚫 ليس لديك صلاحية حذف الموظف.</p>");
}

// ===== حماية المرحلة =====
$stmt_stage = $conn->prepare("SELECT stage FROM job_data WHERE employee_id = ?");
$stmt_stage->bind_param("s", $employee_id);
$stmt_stage->execute();
$res_stage = $stmt_stage->get_result();

if($res_stage->num_rows === 0){
    exit("⚠ الموظف غير موجود.");
}

$employee_stage = $res_stage->fetch_assoc()['stage'] ?? '';
$user_stage = $_SESSION['stage'] ?? '';

if($employee_stage !== $user_stage){
    exit("⛔ لا يمكنك حذف موظف من مرحلة أخرى ($employee_stage).");
}

// ===== تنفيذ الحذف =====
if(isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
    $conn->begin_transaction();
    try {
        // حذف البيانات من جميع الجداول المرتبطة
        $tables = ['personal_data', 'job_data', 'contact_info', 'qualifications', 'accounts', 'admin_data', 'annual_notes'];
        foreach($tables as $table){
            $stmt = $conn->prepare("DELETE FROM $table WHERE employee_id = ?");
            $stmt->bind_param("s", $employee_id);
            $stmt->execute();
        }

        $conn->commit();
        header("Location: indexteacher.php?msg=deleted");
        exit();
    } catch(Exception $e){
        $conn->rollback();
        exit("حدث خطأ أثناء الحذف: " . $e->getMessage());
    }
} else {
    // عرض رسالة تأكيد الحذف
    echo '<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تأكيد الحذف</title>
<style>
body { font-family:sans-serif; background:#f8eaea; text-align:center; padding:50px; }
button { background:#800000; color:#fff; border:none; padding:10px 20px; margin:5px; border-radius:6px; cursor:pointer; transition:0.3s; }
button:hover { background:#a00000; }
a { text-decoration:none; }
</style>
</head>
<body>
<h2>هل أنت متأكد من حذف الموظف؟</h2>
<p>هذا الإجراء نهائي ولا يمكن التراجع عنه.</p>
<a href="delete.php?id='.htmlspecialchars($employee_id).'&confirm=yes"><button>نعم، احذف</button></a>
<a href="indexteacher.php"><button>إلغاء</button></a>
</body>
</html>';
}
?>