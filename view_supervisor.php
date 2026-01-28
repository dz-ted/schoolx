<?php
session_start();
include("db.php");

// التحقق من جلسة المستخدم
if(!isset($_SESSION['user'])){
    exit("لا يمكن الوصول إلى هذه الصفحة");
}

require 'vendor/autoload.php';
$employee_id = $_GET['id'] ?? '';
if(!$employee_id) exit("رقم الموظف مفقود!");

// جلب البيانات الشخصية
$stmt = $conn->prepare("SELECT * FROM supervisors_data WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$personal = $res->fetch_assoc();
$stmt->close();

// جلب البيانات الإدارية
$stmt = $conn->prepare("SELECT * FROM supervisor_admin WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

// جلب الصفوف
$stmt = $conn->prepare("SELECT class_level, class_number FROM supervisor_classes WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$current_classes=[];
while($r=$res->fetch_assoc()){
    $lvl=(int)$r['class_level'];
    $num=(int)$r['class_number'];
    $current_classes[$lvl][]=$num;
}
$stmt->close();

// جلب المناوبات الصباحية
$stmt=$conn->prepare("SELECT duty FROM supervisor_shifts_morning WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res=$stmt->get_result();
$morning_shifts=[];
while($r=$res->fetch_assoc()) $morning_shifts[]=$r['duty'];
$stmt->close();

// جلب مناوبات الفرصة
$stmt=$conn->prepare("SELECT duty FROM supervisor_shifts_break WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res=$stmt->get_result();
$break_shifts=[];
while($r=$res->fetch_assoc()) $break_shifts[]=$r['duty'];
$stmt->close();

// التقدير السنوي
$current_year=date("Y");
$years=[];
for($i=0;$i<5;$i++) $years[]=$current_year-$i;

$annual_notes=[];
$stmt=$conn->prepare("SELECT year,rating,manual_note FROM annual_notes WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res=$stmt->get_result();
while($row=$res->fetch_assoc()) $annual_notes[(int)$row['year']] = ['rating'=>$row['rating'], 'manual_note'=>$row['manual_note']];
$stmt->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>معاينة بيانات المشرف</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body { font-family: 'Cairo', sans-serif; background-color: #f8f9fa; }
.container { max-width: 1100px; margin: 30px auto; }
h2 { text-align:center; color:#0d6efd; margin-bottom:30px; font-weight:700; }
.card { border-radius:12px; box-shadow:0 4px 12px rgba(0,0,0,0.08); margin-bottom:20px; }
.card-header { background-color:#0d6efd; color:#fff; font-weight:600; font-size:1.2rem; border-radius:12px 12px 0 0; }
.card-body div { margin-bottom:12px; word-break:break-word; }
label { font-weight:600; }
.section-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:15px; }
.badge-info { background-color:#0dcaf0; }
.badge-warning { background-color:#ffc107; }
.badge-success { background-color:#198754; }
.badge-danger { background-color:#dc3545; }
.supervisor-photo { width:150px; height:150px; object-fit:cover; border-radius:50%; border:3px solid #0d6efd; margin-bottom:20px; }
.note-text { min-height:50px; }
</style>
</head>
<body>
<div class="container text-center">
<div class="container text-center">

<!-- أزرار العمليات في أعلى الصفحة -->
<div class="mb-3" style="text-align:right; display:flex; justify-content:flex-end; gap:10px;">
    <button onclick="window.print();" class="btn btn-primary btn-sm">طباعة</button>
    <a href="supervisor_xls.php?id=<?php echo urlencode($employee_id); ?>" class="btn btn-success btn-sm">تصدير Excel</a>
    <a href="index_supervisors.php" class="btn btn-secondary btn-sm">العودة للمشرفين</a>
</div>
<?php 
$photo_file = 'uploads/supervisors/' . ($personal['photo'] ?? '');
if(!file_exists($photo_file) || empty($personal['photo'])) $photo_file = 'avatar.png';
?>
<img src="<?php echo htmlspecialchars($photo_file); ?>" alt="صورة المشرف" class="supervisor-photo">

<h2>معاينة بيانات المشرف: <?php echo htmlspecialchars($personal['full_name'] ?? $employee_id); ?></h2>

<!-- البيانات الشخصية -->
<div class="card">
<div class="card-header">البيانات الشخصية</div>
<div class="card-body section-grid">
    <div><label>الاسم الرباعي:</label> <?php echo htmlspecialchars($personal['full_name'] ?? ''); ?></div>
    <div><label>رقم الهوية:</label> <?php echo htmlspecialchars($personal['employee_id'] ?? ''); ?></div>
    <div><label>رقم الجواز:</label> <?php echo htmlspecialchars($personal['personal_id'] ?? ''); ?></div>
    <div><label>تاريخ الميلاد:</label> <?php echo htmlspecialchars($personal['birth_date'] ?? ''); ?></div>
    <div><label>الجنسية:</label> <?php echo htmlspecialchars($personal['nationality'] ?? ''); ?></div>
    <div><label>نوع التعليم:</label> <?php echo htmlspecialchars($personal['gender'] ?? ''); ?></div>
    <div><label>الحالة الزوجية:</label> <?php echo htmlspecialchars($personal['marital_status'] ?? ''); ?></div>
    <div><label>عدد الأبناء:</label> <?php echo htmlspecialchars($personal['num_children'] ?? 0); ?></div>
    <div><label>رخصة القيادة:</label> <?php echo htmlspecialchars($personal['driving_license'] ?? ''); ?></div>
</div>
</div>

<!-- المؤهلات والخبرات -->
<div class="card">
<div class="card-header">المؤهلات والخبرات</div>
<div class="card-body section-grid">
    <div><label>المسمى الوظيفي:</label> <?php echo htmlspecialchars($personal['job_title'] ?? ''); ?></div>
    <div><label>سنوات الخبرة:</label> <?php echo htmlspecialchars($personal['experience'] ?? ''); ?></div>
    <div><label>المؤهل العلمي:</label> <?php echo htmlspecialchars($personal['degree'] ?? ''); ?></div>
    <div><label>التخصص الدراسي:</label> <?php echo htmlspecialchars($personal['major'] ?? ''); ?></div>
    <div><label>الجامعة / الكلية:</label> <?php echo htmlspecialchars($personal['university'] ?? ''); ?></div>
    <div><label>سنة التخرج:</label> <?php echo htmlspecialchars($personal['graduation_year'] ?? ''); ?></div>
    <div><label>الدورات التدريبية:</label> <div class="note-text"><?php echo htmlspecialchars($personal['courses'] ?? ''); ?></div></div>
    <div><label>المهارات الإضافية:</label> <div class="note-text"><?php echo htmlspecialchars($personal['skills'] ?? ''); ?></div></div>
    <div><label>سنة التعيين:</label> <?php echo htmlspecialchars($personal['year_of_assignment'] ?? ''); ?></div>
</div>
</div>

<!-- التواصل -->
<div class="card">
<div class="card-header">التواصل</div>
<div class="card-body section-grid">
    <div><label>رقم الهاتف في قطر:</label> <?php echo htmlspecialchars($personal['phone_mobile'] ?? ''); ?></div>
    <div><label>رقم الهاتف في البلد:</label> <?php echo htmlspecialchars($personal['phone_home'] ?? ''); ?></div>
    <div><label>رقم الطوارئ:</label> <?php echo htmlspecialchars($personal['emergency_phone'] ?? ''); ?></div>
    <div><label>عنوانه في قطر:</label> <?php echo htmlspecialchars($personal['address_qatar'] ?? ''); ?></div>
    <div><label>عنوانه في البلد:</label> <?php echo htmlspecialchars($personal['address_home'] ?? ''); ?></div>
</div>
</div>

 

<!-- البيانات الإدارية (تم إضافة الحقول الناقصة) -->
<div class="card">
<div class="card-header">البيانات الإدارية</div>
<div class="card-body section-grid">
    <div><label>الرقم الوظيفي:</label> <?php echo htmlspecialchars($personal['employee_number'] ?? ''); ?></div>
    <div><label>الكفالة:</label> <?php echo htmlspecialchars($personal['sponsorship'] ?? ''); ?></div>
    <div><label>حساب Teams:</label> <?php echo htmlspecialchars($personal['teams_account'] ?? ''); ?></div>
    <div><label>حساب برنامج الغياب:</label> <?php echo htmlspecialchars($personal['attendance_platform'] ?? ''); ?></div>

    <!-- الإجازات -->
    <div><label>إجازات عارضة:</label> <?php echo htmlspecialchars($admin['leave_casual'] ?? 0); ?></div>
    <div><label>إجازات مرضية:</label> <?php echo htmlspecialchars($admin['leave_sick'] ?? 0); ?></div>
    <div><label>إجازات بدون عذر:</label> <?php echo htmlspecialchars($admin['leave_unexcused'] ?? 0); ?></div>

    <!-- حالة المشرف -->
    <div><label>حالة المشرف:</label> <?php echo htmlspecialchars($admin['supervisor_status'] ?? ''); ?></div>
</div>
</div>

<!-- الملاحظات الإدارية -->
<div class="card">
<div class="card-header">الملاحظات الإدارية</div>
<div class="card-body section-grid">
    <div class="note-text"><label>تنبيه شفوي:</label> <?php echo htmlspecialchars($admin['note_verbal'] ?? ''); ?></div>
    <div class="note-text"><label>تنبيه كتابي:</label> <?php echo htmlspecialchars($admin['note_written'] ?? ''); ?></div>
    <div class="note-text"><label>إنذار:</label> <?php echo htmlspecialchars($admin['note_warning'] ?? ''); ?></div>
    <div class="note-text"><label>خصم:</label> <?php echo htmlspecialchars($admin['note_discount'] ?? 0); ?> يوم</div>
</div>
</div>
<!-- الصفوف حسب المرحلة -->
<div class="card">
  <div class="card-header">الصفوف حسب المرحلة</div>
  <div class="card-body section-grid">
    <?php foreach($current_classes as $level => $classes): ?>
      <div style="display:flex; flex-direction:column; margin-bottom:10px;">
        <span style="font-weight:600;">المرحلة <?php echo $level; ?>:</span>
        <span>الصفوف <?php echo implode(', ', $classes); ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- المناوبات الصباحية -->
<div class="card">
<div class="card-header">المناوبات الصباحية</div>
<div class="card-body section-grid">
    <?php foreach($morning_shifts as $shift): ?>
        <div><?php echo htmlspecialchars($shift); ?></div>
    <?php endforeach; ?>
</div>
</div>

<!-- مناوبات الفرصة -->
<div class="card">
<div class="card-header">مناوبات الفرصة</div>
<div class="card-body section-grid">
    <?php foreach($break_shifts as $shift): ?>
        <div><?php echo htmlspecialchars($shift); ?></div>
    <?php endforeach; ?>
</div>
</div>

<!-- الملاحظات -->
<div class="card">
<div class="card-header">الملاحظات</div>
<div class="card-body section-grid">
    <div class="note-text"><label>تنبيه شفوي:</label> <?php echo htmlspecialchars($admin['note_verbal'] ?? ''); ?></div>
    <div class="note-text"><label>تنبيه كتابي:</label> <?php echo htmlspecialchars($admin['note_written'] ?? ''); ?></div>
    <div class="note-text"><label>إنذار:</label> <?php echo htmlspecialchars($admin['note_warning'] ?? ''); ?></div>
</div>
</div>

<!-- العهدات -->
<div class="card">
<div class="card-header">العهدات</div>
<div class="card-body section-grid">
    <div><label>قلم:</label> <?php echo htmlspecialchars($admin['assets_pen'] ?? ''); ?></div>
    <div><label>حاسوب:</label> <?php echo htmlspecialchars($admin['assets_pc'] ?? ''); ?></div>
    <div><label>هاتف:</label> <?php echo htmlspecialchars($admin['assets_phone'] ?? ''); ?></div>
    <div><label>سيارة:</label> <?php echo htmlspecialchars($admin['assets_car'] ?? ''); ?></div>
</div>
</div>

<h3>التقدير السنوي</h3>
<div class="card">
  <div class="card-header">التقدير السنوي</div>
  <div class="card-body" style="display:grid; grid-template-columns:repeat(auto-fit,minmax(250px,1fr)); gap:15px;">
    <?php foreach($years as $year): 
        $note = $annual_notes[$year] ?? ['rating'=>'','manual_note'=>''];
        $ratingText = trim($note['rating'] ?? '');
        // تحديد اللون حسب التقييم بدقة
        if($ratingText === 'ممتاز'){
            $ratingColor = 'green';
        } elseif($ratingText === 'جيد جداً'){
            $ratingColor = 'orange';
        } elseif($ratingText === 'جيد'){
            $ratingColor = 'red';
        } else {
            $ratingColor = 'orange';
        }
    ?>
    <div style="background:#f9f9f9; padding:12px; border-radius:10px; border:1px solid #ddd; box-shadow:0 2px 5px rgba(0,0,0,0.05);">
        <div style="font-weight:bold; font-size:16px; margin-bottom:5px;">السنة: <?php echo $year; ?></div>
        <div style="font-weight:bold; font-size:15px; margin-bottom:5px;">
            <span style="color:black;">التقييم:</span> 
            <span style="color:<?php echo $ratingColor; ?>;"><?php echo htmlspecialchars($note['rating']); ?></span>
        </div>
        <div style="font-size:14px; background:#fff; padding:8px; border-radius:6px; border:1px solid #ccc; min-height:50px;">ملاحظة: <?php echo htmlspecialchars($note['manual_note']); ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

</div>
</body>
</html>