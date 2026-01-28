<?php
session_start();
include("db.php");

// ===== التحقق من تسجيل الدخول =====
if(!isset($_SESSION['user'])){
    exit("⛔ لا يمكن الوصول إلى هذه الصفحة");
}

// ===== التحقق من صلاحية التعديل / الحذف =====
if(!($_SESSION['can_edit'] ?? 0)){
    exit("⛔ لا تملك صلاحية حذف المشرفين");
}

// ===== التحقق من المرحلة =====
$current_stage = $_SESSION['stage'] ?? '';
if(!$current_stage){
    exit("⛔ المرحلة غير معرّفة في الجلسة");
}

// ===== الحصول على employee_id =====
$employee_id = $_GET['id'] ?? '';
if(!$employee_id){
    exit("رقم المشرف مفقود!");
}

// ===== تحقق من وجود المشرف + تبعيته للمرحلة =====
$stmt = $conn->prepare("
    SELECT COUNT(*) AS cnt 
    FROM supervisors_data 
    WHERE employee_id = ? AND stage = ?
");
$stmt->bind_param("ss", $employee_id, $current_stage);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if($row['cnt'] == 0){
    exit("⛔ هذا المشرف غير موجود أو لا يتبع مرحلتك");
}

// ===== جلب صورة المشرف (إن وُجدت) =====
$stmt = $conn->prepare("SELECT image FROM supervisor_admin WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if(!empty($admin['image']) && $admin['image'] !== 'avatar.png'){
    $filePath = "uploads/supervisors/" . $admin['image'];
    if(file_exists($filePath)){
        unlink($filePath);
    }
}

// ===== حذف البيانات من الجداول المرتبطة =====
$tables = [
    'supervisor_classes',
    'supervisor_shifts_morning',
    'supervisor_shifts_break',
    'annual_notes',
    'supervisor_admin',
    'supervisors_data' // الأخير دائمًا
];

foreach($tables as $tbl){
    $stmt = $conn->prepare("DELETE FROM `$tbl` WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    if(!$stmt->execute()){
        exit("❌ خطأ أثناء الحذف من $tbl");
    }
    $stmt->close();
}

// ===== إعادة التوجيه =====
echo "<script>
    alert('✅ تم حذف المشرف وجميع بياناته بنجاح');
    window.location.href = 'index_supervisors.php';
</script>";
exit;