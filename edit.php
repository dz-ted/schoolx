<?php
session_start();
include("db.php");

if(!isset($_SESSION['user']) || !isset($_GET['id'])){
    exit("لا يمكن الوصول إلى هذه الصفحة");
}

$employee_id = $_GET['id'];

// ===== صلاحية التعديل =====
if(!($_SESSION['can_edit'] ?? 0)){
    exit("<p style='text-align:center; margin-top:50px; font-weight:bold; color:red;'>🚫 ليس لديك صلاحية تعديل الموظف.</p>");
}

// =====================
// حماية الصلاحية حسب المرحلة
// =====================
$stmt_stage = $conn->prepare("SELECT stage FROM job_data WHERE employee_id=?");
$stmt_stage->bind_param("s", $employee_id);
$stmt_stage->execute();
$res_stage = $stmt_stage->get_result();
$employee_stage = $res_stage->fetch_assoc()['stage'] ?? '';

if($employee_stage !== $_SESSION['stage']){
    exit("❌ لا يمكنك تعديل موظف في مرحلة أخرى");
}

// =====================
// جلب البيانات من جميع الجداول
// =====================
$tables = ['personal_data', 'contact_info', 'job_data', 'accounts', 'qualifications'];
$data = [];
foreach($tables as $table){
    $stmt = $conn->prepare("SELECT * FROM $table WHERE employee_id=?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data[$table] = $res->fetch_assoc() ?? [];
}

// =====================
// قوائم اختيار
// =====================
$marital_options = ['أعزب', 'متزوج', 'مطلق', 'أرمل'];
$contract_options = ['دوام كامل', 'دوام جزئي', 'مؤقت', 'عقد سنوي'];

// =====================
// جلب الصورة الحالية
// =====================
$stmt = $conn->prepare("SELECT image FROM admin_data WHERE employee_id=?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
$admin_data = $res->fetch_assoc();
$image_name = $admin_data['image'] ?? 'avatar.png';

// =====================
// معالجة POST لجميع البيانات + الصورة
// =====================
if($_SERVER['REQUEST_METHOD'] === 'POST'){

    // ===== حماية إضافية عند POST =====
    if($employee_stage !== $_SESSION['stage']){
        exit("❌ عملية غير مصرح بها للمرحلة");
    }

    // ===== جلب البيانات من POST =====
    $full_name = $_POST['full_name'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $personal_id = $_POST['personal_id'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';
    $num_children = $_POST['num_children'] ?? 0;
    $driving_license = $_POST['driving_license'] ?? '';

    $phone_mobile = $_POST['phone_mobile'] ?? '';
    $phone_home = $_POST['phone_home'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';
    $address_qatar = $_POST['address_qatar'] ?? '';
    $address_home = $_POST['address_home'] ?? '';
    $bank_account = $_POST['bank_account'] ?? '';

    $job_title = $_POST['job_title'] ?? '';
    $department = $_POST['department'] ?? '';
    $hire_date = $_POST['hire_date'] ?? '';
    $base_salary = $_POST['base_salary'] ?? 0;
    $allowances = $_POST['allowances'] ?? 0;
    $contract_type = $_POST['contract_type'] ?? '';

    $experience = $_POST['experience'] ?? 0;
    $degree = $_POST['degree'] ?? '';
    $major = $_POST['major'] ?? '';
    $university = $_POST['university'] ?? '';
    $graduation_year = $_POST['graduation_year'] ?? 0;
    $courses = $_POST['courses'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $year_of_assignment = $_POST['year_of_assignment'] ?? 0;

    $teams_account = $_POST['teams_account'] ?? '';
    $attendance_platform = $_POST['attendance_platform'] ?? '';

    // ===== تحديث الجداول =====
    $stmt = $conn->prepare("UPDATE personal_data SET full_name=?, birth_date=?, nationality=?, gender=?, personal_id=?, marital_status=?, num_children=?, driving_license=? WHERE employee_id=?");
    $stmt->bind_param("ssssssiss", $full_name, $birth_date, $nationality, $gender, $personal_id, $marital_status, $num_children, $driving_license, $employee_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE contact_info SET phone_mobile=?, phone_home=?, emergency_phone=?, address_qatar=?, address_home=?, bank_account=? WHERE employee_id=?");
    $stmt->bind_param("sssssss", $phone_mobile, $phone_home, $emergency_phone, $address_qatar, $address_home, $bank_account, $employee_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE job_data SET job_title=?, department=?, hire_date=?, base_salary=?, allowances=?, contract_type=? WHERE employee_id=?");
    $stmt->bind_param("sssddss", $job_title, $department, $hire_date, $base_salary, $allowances, $contract_type, $employee_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE qualifications SET experience=?, degree=?, major=?, university=?, graduation_year=?, courses=?, skills=?, year_of_assignment=? WHERE employee_id=?");
    $stmt->bind_param("isssssssi", $experience, $degree, $major, $university, $graduation_year, $courses, $skills, $year_of_assignment, $employee_id);
    $stmt->execute();

    $stmt = $conn->prepare("UPDATE accounts SET teams_account=?, attendance_platform=? WHERE employee_id=?");
    $stmt->bind_param("sss", $teams_account, $attendance_platform, $employee_id);
    $stmt->execute();

    echo "<p style='color:green;text-align:center;'>تم تعديل البيانات بنجاح!</p>";
    header("Refresh:1; url=edit.php?id=$employee_id");
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>تعديل بيانات الموظف</title>
<style>
body { font-family:sans-serif; background:#f7f7f7; }
.container { max-width:1000px; margin:20px auto; padding:20px; background:#fff; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
h2 { color:#800000; text-align:center; }
h3 { color:#004080; text-align:right; }
label { display:block; font-weight:bold; margin-bottom:5px; }
input[type=text], input[type=number], input[type=date], input[type=file], select { width:100%; padding:8px; border:1px solid #ccc; border-radius:6px; box-sizing:border-box; }
button { padding:12px 25px; background:#800000; color:#fff; border:none; border-radius:8px; cursor:pointer; font-size:16px; transition:0.3s; }
button:hover { background:#a00000; }
.back-btn { padding:10px 15px; background:#004080; color:#fff; border-radius:6px; text-decoration:none; font-weight:bold; float:right; margin-bottom:15px; transition:0.3s; }
.back-btn:hover { background:#0060c0; }
.grid2 { display:grid; grid-template-columns:1fr 1fr; gap:20px; direction:rtl; }
.profile-img { width:140px; height:140px; border-radius:50%; object-fit:cover; border:3px solid #800000; display:block; margin:0 auto 15px; }
hr { border:none; border-top:2px solid #800000; margin:25px 0; }
</style>
</head>
<body>

<div class="container">
<a href="indexteacher.php" class="back-btn">⬅ العودة إلى الصفحة الرئيسية</a>
<h2>تعديل بيانات الموظف: <?php echo htmlspecialchars($data['personal_data']['full_name'] ?? ''); ?></h2>

<form method="POST" enctype="multipart/form-data">

<!-- الصورة -->
<div style="text-align:center; margin-bottom:20px;">
    <img src="uploads/<?php echo htmlspecialchars($image_name); ?>" 
         style="width:140px; height:140px; border-radius:50%; object-fit:cover; border:3px solid #800000;"><br>
    <input type="file" name="image" accept="image/*" style="margin-top:10px;">
</div>

<!-- البيانات الشخصية -->
<h3>البيانات الشخصية</h3>
<div class="grid2">
    <div><label>الاسم الرباعي:</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($data['personal_data']['full_name'] ?? ''); ?>"></div>
    <div><label>رقم الجواز:</label><input type="text" name="personal_id" value="<?php echo htmlspecialchars($data['personal_data']['personal_id'] ?? ''); ?>"></div>
    <div><label>تاريخ الميلاد:</label><input type="text" name="birth_date" value="<?php echo htmlspecialchars($data['personal_data']['birth_date'] ?? ''); ?>"></div>
    <div><label>الجنسية:</label><input type="text" name="nationality" value="<?php echo htmlspecialchars($data['personal_data']['nationality'] ?? ''); ?>"></div>
    <div><label>نوع التعليم:</label><input type="text" name="gender" value="<?php echo htmlspecialchars($data['personal_data']['gender'] ?? ''); ?>"></div>
    <div>
        <label>الحالة الزوجية:</label>
        <select name="marital_status">
            <?php foreach($marital_options as $option): ?>
                <option value="<?php echo $option; ?>" <?php if(($data['personal_data']['marital_status'] ?? '')==$option) echo 'selected'; ?>><?php echo $option; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div><label>عدد الأبناء:</label><input type="number" name="num_children" value="<?php echo htmlspecialchars($data['personal_data']['num_children'] ?? 0); ?>"></div>
    <div><label>رخصة القيادة:</label><input type="text" name="driving_license" value="<?php echo htmlspecialchars($data['personal_data']['driving_license'] ?? ''); ?>"></div>
</div>

<hr>

<!-- بيانات الاتصال -->
<h3>بيانات الاتصال</h3>
<div class="grid2">
    <div><label>هاتف جوال:</label><input type="text" name="phone_mobile" value="<?php echo htmlspecialchars($data['contact_info']['phone_mobile'] ?? ''); ?>"></div>
    <div><label>هاتف المنزل:</label><input type="text" name="phone_home" value="<?php echo htmlspecialchars($data['contact_info']['phone_home'] ?? ''); ?>"></div>
    <div><label>هاتف طوارئ:</label><input type="text" name="emergency_phone" value="<?php echo htmlspecialchars($data['contact_info']['emergency_phone'] ?? ''); ?>"></div>
    <div><label>العنوان في قطر:</label><input type="text" name="address_qatar" value="<?php echo htmlspecialchars($data['contact_info']['address_qatar'] ?? ''); ?>"></div>
    <div><label>العنوان في المنزل:</label><input type="text" name="address_home" value="<?php echo htmlspecialchars($data['contact_info']['address_home'] ?? ''); ?>"></div>
    <div><label>الحساب البنكي:</label><input type="text" name="bank_account" value="<?php echo htmlspecialchars($data['contact_info']['bank_account'] ?? ''); ?>"></div>
</div>

<hr>

<!-- البيانات الوظيفية -->
<h3>البيانات الوظيفية</h3>
<div class="grid2">
    <div><label>المسمى الوظيفي:</label><input type="text" name="job_title" value="<?php echo htmlspecialchars($data['job_data']['job_title'] ?? ''); ?>"></div>
    <div><label>القسم:</label><input type="text" name="department" value="<?php echo htmlspecialchars($data['job_data']['department'] ?? ''); ?>"></div>
    <div><label>تاريخ التعيين:</label><input type="text" name="hire_date" value="<?php echo htmlspecialchars($data['job_data']['hire_date'] ?? ''); ?>"></div>
    <div><label>الراتب الأساسي:</label><input type="number" step="0.01" name="base_salary" value="<?php echo htmlspecialchars($data['job_data']['base_salary'] ?? 0); ?>"></div>
    <div><label>البدلات:</label><input type="number" step="0.01" name="allowances" value="<?php echo htmlspecialchars($data['job_data']['allowances'] ?? 0); ?>"></div>
    <div>
        <label>نوع العقد:</label>
        <select name="contract_type">
            <?php foreach($contract_options as $option): ?>
                <option value="<?php echo $option; ?>" <?php if(($data['job_data']['contract_type'] ?? '')==$option) echo 'selected'; ?>><?php echo $option; ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<hr>

<!-- المؤهلات والخبرة -->
<h3>المؤهلات والخبرة</h3>
<div class="grid2">
    <div><label>الخبرة:</label><input type="number" name="experience" value="<?php echo htmlspecialchars($data['qualifications']['experience'] ?? 0); ?>"></div>
    <div><label>الدرجة العلمية:</label><input type="text" name="degree" value="<?php echo htmlspecialchars($data['qualifications']['degree'] ?? ''); ?>"></div>
    <div><label>التخصص:</label><input type="text" name="major" value="<?php echo htmlspecialchars($data['qualifications']['major'] ?? ''); ?>"></div>
    <div><label>الجامعة:</label><input type="text" name="university" value="<?php echo htmlspecialchars($data['qualifications']['university'] ?? ''); ?>"></div>
    <div><label>سنة التخرج:</label><input type="number" name="graduation_year" value="<?php echo htmlspecialchars($data['qualifications']['graduation_year'] ?? 0); ?>"></div>
    <div><label>الدورات:</label><input type="text" name="courses" value="<?php echo htmlspecialchars($data['qualifications']['courses'] ?? ''); ?>"></div>
    <div><label>المهارات:</label><input type="text" name="skills" value="<?php echo htmlspecialchars($data['qualifications']['skills'] ?? ''); ?>"></div>
    <div><label>سنة التعيين:</label><input type="number" name="year_of_assignment" value="<?php echo htmlspecialchars($data['qualifications']['year_of_assignment'] ?? 0); ?>"></div>
</div>

<hr>

<!-- الحسابات -->
<h3>الحسابات</h3>
<div class="grid2">
    <div><label>حساب التيمز:</label><input type="text" name="teams_account" value="<?php echo htmlspecialchars($data['accounts']['teams_account'] ?? ''); ?>"></div>
    <div><label>برنامج الغياب:</label><input type="text" name="attendance_platform" value="<?php echo htmlspecialchars($data['accounts']['attendance_platform'] ?? ''); ?>"></div>
</div>

<hr>

<div style="text-align:center; margin-top:25px;">
    <button type="submit">حفظ التغييرات</button>
</div>

</form>
</div>
</body>
</html>