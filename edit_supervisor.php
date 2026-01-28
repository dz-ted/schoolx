<?php
session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    exit("⛔ لا يمكن الوصول إلى هذه الصفحة");
}

// ===== حماية الوصول حسب صلاحية التعديل =====
if(!($_SESSION['can_edit'] ?? 0)){
    exit("⛔ لا تملك صلاحية تعديل المشرفين.");
}

require 'vendor/autoload.php';
$employee_id = $_GET['id'] ?? '';
if(!$employee_id) exit("رقم الموظف مفقود!");

// ===== جلب بيانات المشرف حسب المرحلة الحالية فقط =====
$current_stage = $_SESSION['stage'] ?? '';
$stmt = $conn->prepare("SELECT * FROM supervisors_data WHERE employee_id=? AND stage=?");
$stmt->bind_param("ss", $employee_id, $current_stage);
$stmt->execute();
$res = $stmt->get_result();
$personal = $res->fetch_assoc();
$stmt->close();

if(!$personal) exit("⛔ هذا المشرف غير موجود أو ليس تابعًا لمرحلتك.");

// جلب الحسابات الإدارية
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
<title>تعديل بيانات المشرف</title>
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
.note-text { min-height:50px; }
.supervisor-photo { width:150px; height:150px; object-fit:cover; border-radius:50%; border:3px solid #0d6efd; margin-bottom:20px; }
</style>
</head>
<body>
<div class="container text-center">

<form action="save_supervisor_edit.php" method="post" enctype="multipart/form-data">
<input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee_id); ?>">
<input type="hidden" name="stage" value="<?php echo htmlspecialchars($current_stage); ?>">

<div class="mb-3" style="text-align:right; display:flex; justify-content:flex-end; gap:10px;">
    <button type="submit" class="btn btn-primary btn-sm">💾 حفظ التعديلات</button>
    <a href="index_supervisors.php" class="btn btn-secondary btn-sm">⬅ العودة للمشرفين</a>
</div>

<?php 
$photo_file = 'uploads/supervisors/' . ($personal['photo'] ?? '');
if(!file_exists($photo_file) || empty($personal['photo'])) $photo_file = 'avatar.png';
?>
<img src="<?php echo htmlspecialchars($photo_file); ?>" alt="صورة المشرف" class="supervisor-photo">
<div class="mb-3">
    <label>تغيير الصورة:</label>
    <input type="file" name="photo" class="form-control form-control-sm">
</div>

<h2>تعديل بيانات المشرف: <?php echo htmlspecialchars($personal['full_name'] ?? $employee_id); ?></h2>

<!-- البيانات الشخصية -->
<div class="card">
<div class="card-header">البيانات الشخصية</div>
<div class="card-body section-grid">
    <div><label>الاسم الرباعي:</label> <input type="text" name="full_name" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['full_name'] ?? ''); ?>"></div>
    <div><label>رقم الهوية:</label> <input type="text" name="employee_id_text" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['employee_id'] ?? ''); ?>" disabled></div>
    <div><label>رقم الجواز:</label> <input type="text" name="personal_id" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['personal_id'] ?? ''); ?>"></div>
    <div><label>تاريخ الميلاد:</label> <input type="date" name="birth_date" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['birth_date'] ?? ''); ?>"></div>
    <div><label>الجنسية:</label> <input type="text" name="nationality" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['nationality'] ?? ''); ?>"></div>
   <div>
    <label>نوع التعليم:</label>
    <select name="gender" class="form-select form-select-sm">
        <?php 
        $education_types = ["تعليم حكومي","تعليم خاص","تعليم في بلدي الأم"];
        $current_gender = $personal['gender'] ?? '';
        foreach($education_types as $type): ?>
            <option value="<?php echo $type; ?>" <?php if($current_gender === $type) echo 'selected'; ?>>
                <?php echo $type; ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
    <div><label>الحالة الزوجية:</label> <input type="text" name="marital_status" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['marital_status'] ?? ''); ?>"></div>
    <div><label>عدد الأبناء:</label> <input type="number" name="num_children" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['num_children'] ?? 0); ?>"></div>
    <div><label>رخصة القيادة:</label> <input type="text" name="driving_license" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['driving_license'] ?? ''); ?>"></div>
 <div><label>الرقم الوظيفي:</label> <input type="text" name="employee_number" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['employee_number'] ?? ''); ?>"></div>
    <div><label>الكفالة:</label> <input type="text" name="sponsorship" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['sponsorship'] ?? ''); ?>"></div>
</div>
</div>

<!-- المؤهلات والخبرات -->
<div class="card">
<div class="card-header">المؤهلات والخبرات</div>
<div class="card-body section-grid">
    <div><label>المسمى الوظيفي:</label> <input type="text" name="job_title" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['job_title'] ?? ''); ?>"></div>
    <div><label>سنوات الخبرة:</label> <input type="number" name="experience" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['experience'] ?? ''); ?>"></div>
    <div><label>المؤهل العلمي:</label> <input type="text" name="degree" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['degree'] ?? ''); ?>"></div>
    <div><label>التخصص الدراسي:</label> <input type="text" name="major" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['major'] ?? ''); ?>"></div>
    <div><label>الجامعة / الكلية:</label> <input type="text" name="university" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['university'] ?? ''); ?>"></div>
    <div><label>سنة التخرج:</label> <input type="number" name="graduation_year" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['graduation_year'] ?? ''); ?>"></div>
    <div><label>الدورات التدريبية:</label> <textarea name="courses" class="form-control form-control-sm"><?php echo htmlspecialchars($personal['courses'] ?? ''); ?></textarea></div>
    <div><label>المهارات الإضافية:</label> <textarea name="skills" class="form-control form-control-sm"><?php echo htmlspecialchars($personal['skills'] ?? ''); ?></textarea></div>
    <div><label>سنة التعيين:</label> <input type="number" name="year_of_assignment" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['year_of_assignment'] ?? ''); ?>"></div>
</div>
</div>

<!-- التواصل -->
<div class="card">
<div class="card-header">التواصل</div>
<div class="card-body section-grid">
    <div><label>رقم الهاتف في قطر:</label> <input type="text" name="phone_mobile" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['phone_mobile'] ?? ''); ?>"></div>
    <div><label>رقم الهاتف في البلد:</label> <input type="text" name="phone_home" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['phone_home'] ?? ''); ?>"></div>
    <div><label>رقم الطوارئ:</label> <input type="text" name="emergency_phone" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['emergency_phone'] ?? ''); ?>"></div>
    <div><label>عنوانه في قطر:</label> <input type="text" name="address_qatar" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['address_qatar'] ?? ''); ?>"></div>
    <div><label>عنوانه في البلد:</label> <input type="text" name="address_home" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['address_home'] ?? ''); ?>"></div>
</div>
</div>

<!-- الحسابات -->
<div class="card">
<div class="card-header">الحسابات</div>
<div class="card-body section-grid">
    <div><label>حساب Teams:</label> <input type="text" name="teams_account" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['teams_account'] ?? ''); ?>"></div>
    <div><label>حساب برنامج الغياب:</label> <input type="text" name="attendance_platform" class="form-control form-control-sm" value="<?php echo htmlspecialchars($personal['attendance_platform'] ?? ''); ?>"></div>
</div>
</div>

</form>
</div>
</body>
</html>