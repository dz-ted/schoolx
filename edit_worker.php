<?php
session_start();
include("db.php");

$success = $error = '';

if(!isset($_SESSION['user'])){
    exit("⛔ لا يمكن الوصول");
}

$employee_id = $_GET['id'] ?? '';
if(!$employee_id) exit("رقم العامل مفقود");

/* =======================
   جلب بيانات العامل
======================= */
$stmt = $conn->prepare("SELECT * FROM workers_data WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$worker) exit("العامل غير موجود");

/* =======================
   البيانات الإدارية
======================= */
$stmtA = $conn->prepare("SELECT * FROM worker_admin WHERE employee_id=?");
$stmtA->bind_param("s",$employee_id);
$stmtA->execute();
$admin = $stmtA->get_result()->fetch_assoc();
$stmtA->close();

$stmtAD = $conn->prepare("SELECT * FROM worker_admin_data WHERE employee_id=?");
$stmtAD->bind_param("s",$employee_id);
$stmtAD->execute();
$admin_data = $stmtAD->get_result()->fetch_assoc();
$stmtAD->close();

/* =======================
   فك JSON
======================= */
$cleaning = json_decode($admin_data['cleaning_tasks'] ?? '{}', true);
$cleaning_places = json_decode($admin_data['cleaning_places'] ?? '[]', true);
$presence_places = json_decode($admin_data['presence_places'] ?? '[]', true);
$assets = json_decode($admin_data['assets'] ?? '[]', true);
$buses = json_decode($admin_data['buses'] ?? '[]', true);
$ratings = json_decode($admin_data['annual_ratings'] ?? '{}', true);

/* =======================
   حفظ التعديلات
======================= */
if(isset($_POST['submit'])){
    $full_name = $_POST['full_name'] ?? '';
    $birth_date = $_POST['birth_date'] ?? null;
if($birth_date == '' || $birth_date == '0000-00-00'){
    $birth_date = null; // يسمح بترك التاريخ فارغاً
}
    $nationality = $_POST['nationality'] ?? '';
    $marital_status = $_POST['marital_status'] ?? 'غير متزوج';
    $num_children = ($marital_status=="متزوج") ? $_POST['num_children'] ?? 0 : 0;
    $driving_license = $_POST['driving_license'] ?? 'لا يوجد';
    $job_title = $_POST['job_title'] ?? '';
    $experience = $_POST['experience'] ?? 0;
    $degree = $_POST['degree'] ?? '';
    $major = $_POST['major'] ?? '';
    $university = $_POST['university'] ?? '';
    $graduation_year = $_POST['graduation_year'] ?? null;
    $courses = $_POST['courses'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $year_of_assignment = $_POST['year_of_assignment'] ?? null;
    $phone_mobile = $_POST['phone_mobile'] ?? '';
    $phone_home = $_POST['phone_home'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';
    $address_qatar = $_POST['address_qatar'] ?? '';
    $address_home = $_POST['address_home'] ?? '';
    $employee_number = $_POST['employee_number'] ?? '';
    $sponsorship = $_POST['sponsorship'] ?? '';
    $role = $_POST['role'] ?? 'عامل';
    $stage = $_POST['stage'] ?? $worker['stage'];

    // رفع الصورة إذا تم اختيارها
    $photo = $worker['photo'] ?? 'avatar.png';
    if(isset($_FILES['photo']) && $_FILES['photo']['error']==0){
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo = $employee_id . '.' . $ext;
        $uploadDir = 'uploads/workers/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photo);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE workers_data SET
            full_name=?, birth_date=?, nationality=?, marital_status=?, num_children=?, 
            driving_license=?, job_title=?, experience=?, degree=?, major=?, university=?,
            graduation_year=?, courses=?, skills=?, year_of_assignment=?, phone_mobile=?, 
            phone_home=?, emergency_phone=?, address_qatar=?, address_home=?, employee_number=?,
            sponsorship=?, photo=?, stage=?, role=?
            WHERE employee_id=?");
        $stmt->bind_param(
            "sssssssisssssssissssssssss",
            $full_name, $birth_date, $nationality, $marital_status, $num_children,
            $driving_license, $job_title, $experience, $degree, $major, $university,
            $graduation_year, $courses, $skills, $year_of_assignment, $phone_mobile,
            $phone_home, $emergency_phone, $address_qatar, $address_home, $employee_number,
            $sponsorship, $photo, $stage, $role, $employee_id
        );
        $stmt->execute();
        $conn->commit();
        $success = "✅ تم تحديث بيانات العامل بنجاح!";
    } catch(Exception $e){
        $conn->rollback();
        $error = "❌ حدث خطأ أثناء التحديث: ".$e->getMessage();
    }
}

/* =======================
   دالة آمنة للعرض
======================= */
function safe($v){ return htmlspecialchars($v ?? ''); }
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل بيانات العامل</title>
<style>
body { margin:0; font-family:Arial, sans-serif; background: linear-gradient(to bottom, #1e3c72, #2a5298);}
.container { width: 90%; margin:30px auto; background: #fff; padding:20px; box-shadow:0 0 15px rgba(0,0,128,0.4); border-radius:12px; border-top:6px solid #003366;}
h1 { color:#003366; text-align:center; margin-bottom:20px;}
.success { color:green; font-weight:bold;}
.error { color:red; font-weight:bold;}
.back { display:inline-block; margin-bottom:10px; text-decoration:none; color:#fff; background:#003366; padding:7px 18px; border-radius:6px; transition:0.3s;}
.back:hover { background:#0055aa;}
form { margin-top:20px; }
form input, form select, form textarea { width:100%; margin-bottom:12px; padding:10px; border-radius:6px; border:1px solid #ccc; font-size:14px; transition:border 0.3s;}
form input:focus, form select:focus, form textarea:focus { border:1px solid #003366; outline:none; box-shadow:0 0 5px rgba(0,51,102,0.3);}
form button { background:#003366; color:#fff; border:none; padding:12px 25px; border-radius:8px; cursor:pointer; font-size:15px; transition:0.3s;}
form button:hover { background:#0055aa;}
.tab { display:none;}
.tab.active { display:block;}
.tab-buttons { margin-bottom:20px; text-align:center;}
.tab-buttons button { background:#ddd; border:none; padding:10px 15px; cursor:pointer; margin:5px; border-radius:6px; font-weight:bold; transition:0.3s;}
.tab-buttons button.active { background:#003366; color:#fff; transform:scale(1.05);}
.hidden { display:none; }
img.photo-preview { width:120px; height:120px; border-radius:50%; border:3px solid #004080; display:block; margin:auto 0 15px;}
</style>
<script>
function showTab(tabIndex){
    var tabs=document.querySelectorAll('.tab');
    var btns=document.querySelectorAll('.tab-buttons button');
    tabs.forEach((tab)=>tab.classList.remove('active'));
    btns.forEach((btn)=>btn.classList.remove('active'));
    tabs[tabIndex].classList.add('active');
    btns[tabIndex].classList.add('active');
}
function toggleChildrenField(){
    var marital=document.querySelector("select[name='marital_status']");
    var childrenField=document.getElementById("childrenField");
    if(marital.value==="متزوج"){childrenField.classList.remove("hidden");} else{childrenField.classList.add("hidden");}
}
window.onload=function(){ showTab(0); toggleChildrenField(); };
</script>
</head>
<body>
<div class="container">
<a class="back" href="index_workers.php">⬅ الرجوع</a>
<h1>✏️ تعديل بيانات العامل</h1>
<?php if($success) echo "<div class='success'>$success</div>"; ?>
<?php if($error) echo "<div class='error'>$error</div>"; ?>
<form method="POST" enctype="multipart/form-data">
<label>المرحلة</label>
<select name="stage">
    <option value="<?= safe($worker['stage']) ?>" selected><?= safe($worker['stage']) ?></option>
</select>

<div class="tab-buttons">
    <button type="button" onclick="showTab(0)">👤 البيانات الشخصية</button>
    <button type="button" onclick="showTab(1)">🎓 المؤهلات والخبرات</button>
    <button type="button" onclick="showTab(2)">📞 التواصل</button>
    <button type="button" onclick="showTab(3)">💼 البيانات الوظيفية</button>
</div>

<div class="tab" id="tab-personal">
<img src="uploads/workers/<?= safe($worker['photo']) ?>" class="photo-preview">
<label>الصورة الشخصية (اختياري)</label><input type="file" name="photo" accept="image/*">
<label>الاسم الرباعي</label><input type="text" name="full_name" value="<?= safe($worker['full_name']) ?>" required>
<label>رقم الهوية</label><input type="text" name="employee_id" value="<?= safe($worker['employee_id']) ?>" readonly>
<label>تاريخ الميلاد</label>
<input type="date" name="birth_date" value="<?= isset($worker['birth_date']) && $worker['birth_date']!='0000-00-00' ? htmlspecialchars($worker['birth_date']) : '' ?>">
<label>الجنسية</label><input type="text" name="nationality" value="<?= safe($worker['nationality']) ?>">
<label>الحالة الزوجية</label>
<select name="marital_status" onchange="toggleChildrenField()">
<option value="غير متزوج" <?= $worker['marital_status']!="متزوج"?'selected':'' ?>>غير متزوج</option>
<option value="متزوج" <?= $worker['marital_status']=="متزوج"?'selected':'' ?>>متزوج</option>
</select>
<div id="childrenField" class="<?= $worker['marital_status']=="متزوج"?'':'hidden' ?>">
<label>عدد الأبناء</label><input type="number" name="num_children" min="0" value="<?= safe($worker['num_children']) ?>">
</div>
<label>رخصة القيادة</label>
<select name="driving_license">
<option value="يوجد" <?= $worker['driving_license']=="يوجد"?'selected':'' ?>>يوجد</option>
<option value="لا يوجد" <?= $worker['driving_license']=="لا يوجد"?'selected':'' ?>>لا يوجد</option>
</select>
<label>المسمى الوظيفي</label><input type="text" name="job_title" value="<?= safe($worker['job_title']) ?>">
</div>

<div class="tab" id="tab-qualifications">
<label>سنوات الخبرة</label><input type="number" name="experience" min="0" value="<?= safe($worker['experience']) ?>">
<label>المؤهل العلمي</label><input type="text" name="degree" value="<?= safe($worker['degree']) ?>">
<label>التخصص الدراسي</label><input type="text" name="major" value="<?= safe($worker['major']) ?>">
<label>الجامعة / الكلية</label><input type="text" name="university" value="<?= safe($worker['university']) ?>">
<label>سنة التخرج</label><input type="number" name="graduation_year" min="1900" max="2100" value="<?= safe($worker['graduation_year']) ?>">
<label>الدورات التدريبية</label><textarea name="courses"><?= safe($worker['courses']) ?></textarea>
<label>المهارات الإضافية</label><textarea name="skills"><?= safe($worker['skills']) ?></textarea>
<label>سنة التعيين</label><input type="number" name="year_of_assignment" min="1900" max="2100" value="<?= safe($worker['year_of_assignment']) ?>">
</div>

<div class="tab" id="tab-contact">
<label>رقم الهاتف في قطر</label><input type="text" name="phone_mobile" value="<?= safe($worker['phone_mobile']) ?>">
<label>رقم الهاتف في البلد</label><input type="text" name="phone_home" value="<?= safe($worker['phone_home']) ?>">
<label>رقم الطوارئ</label><input type="text" name="emergency_phone" value="<?= safe($worker['emergency_phone']) ?>">
<label>عنوانه في قطر</label><input type="text" name="address_qatar" value="<?= safe($worker['address_qatar']) ?>">
<label>عنوانه في البلد</label><input type="text" name="address_home" value="<?= safe($worker['address_home']) ?>">
</div>

<div class="tab" id="tab-job">
<label>الرقم الوظيفي</label><input type="text" name="employee_number" value="<?= safe($worker['employee_number']) ?>">
<label>الكفالة</label><input type="text" name="sponsorship" value="<?= safe($worker['sponsorship']) ?>">
<label>الوظيفة</label>
<select name="role">
<option value="سائق" <?= $worker['role']=="سائق"?'selected':'' ?>>سائق</option>
<option value="عامل" <?= $worker['role']=="عامل"?'selected':'' ?>>عامل</option>
<option value="عامل وسائق" <?= $worker['role']=="عامل وسائق"?'selected':'' ?>>عامل وسائق</option>
<option value="حارس" <?= $worker['role']=="حارس"?'selected':'' ?>>حارس</option>
</select>
</div>

<button type="submit" name="submit">💾 حفظ التعديلات</button>
</form>
</div>
</body>
</html>