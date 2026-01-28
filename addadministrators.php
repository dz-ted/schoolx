<?php
session_start();
include("db.php");

$success = $error = '';

// التأكد من تسجيل الدخول
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// ===== حماية حسب صلاحية الإضافة =====
if(!($_SESSION['can_add'] ?? 0)){
    exit("❌ ليس لديك صلاحية الوصول لهذه الصفحة");
}

// المرحلة الحالية من الجلسة
$stage = $_SESSION['stage'] ?? '';

if(isset($_POST['submit'])){
    $employee_id = $_POST['employee_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $employee_number = $_POST['employee_number'] ?? '';
    $role = $_POST['role'] ?? '';

    $personal_id = $_POST['personal_id'] ?? '';
    $birth_date = $_POST['birth_date'] ?: null;
    $nationality = $_POST['nationality'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';
    $num_children = ($marital_status=="متزوج") ? ($_POST['num_children'] ?: 0) : 0;
    $driving_license = $_POST['driving_license'] ?? '';
    $hire_date = $_POST['hire_date'] ?: null;

    $experience = $_POST['experience'] ?: 0;
    $degree = $_POST['degree'] ?? '';
    $major = $_POST['major'] ?? '';
    $university = $_POST['university'] ?? '';
    $graduation_year = $_POST['graduation_year'] ?: null;
    $courses = $_POST['courses'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $year_of_assignment = $_POST['year_of_assignment'] ?: null;

    $phone_mobile = $_POST['phone_mobile'] ?? '';
    $phone_home = $_POST['phone_home'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';
    $address_qatar = $_POST['address_qatar'] ?? '';
    $address_home = $_POST['address_home'] ?? '';

    $teams_account = $_POST['teams_account'] ?? '';
    $attendance_platform = $_POST['attendance_platform'] ?? '';

    $conn->begin_transaction();
    try {
        // ===== personal_data =====
        $stmt1 = $conn->prepare("INSERT INTO administration_personal_data (employee_id, full_name, employee_number, personal_id, birth_date, nationality, marital_status, num_children, driving_license, hire_date, stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt1->bind_param(
            "sssssssssss",
            $employee_id, $full_name, $employee_number, $personal_id, $birth_date, $nationality, $marital_status, $num_children, $driving_license, $hire_date, $stage
        );
        $stmt1->execute();

        // ===== qualifications =====
        $stmt2 = $conn->prepare("INSERT INTO administration_qualifications (employee_id, experience, degree, major, university, graduation_year, courses, skills, year_of_assignment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt2->bind_param(
            "sssssssss",
            $employee_id, $experience, $degree, $major, $university, $graduation_year, $courses, $skills, $year_of_assignment
        );
        $stmt2->execute();

        // ===== contact =====
        $stmt3 = $conn->prepare("INSERT INTO administration_contact_info (employee_id, phone_mobile, phone_home, emergency_phone, address_qatar, address_home) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt3->bind_param("ssssss", $employee_id, $phone_mobile, $phone_home, $emergency_phone, $address_qatar, $address_home);
        $stmt3->execute();

        // ===== role =====
        $stmt4 = $conn->prepare("INSERT INTO administration_roles (employee_id, role) VALUES (?, ?)");
        $stmt4->bind_param("ss", $employee_id, $role);
        $stmt4->execute();

        // ===== accounts =====
        $stmt5 = $conn->prepare("INSERT INTO administration_accounts (employee_id, teams_account, attendance_platform) VALUES (?, ?, ?)");
        $stmt5->bind_param("sss", $employee_id, $teams_account, $attendance_platform);
        $stmt5->execute();

        // ===== رفع الصورة =====
        if(isset($_FILES['photo']) && $_FILES['photo']['error']===UPLOAD_ERR_OK){
            $uploadDir = "uplodadmin/";
            if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
            $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
            if(in_array($ext,['png','jpg','jpeg'])){
                $fileName = $employee_id.".".$ext;
                move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir.$fileName);
                $stmtP = $conn->prepare("UPDATE administration_personal_data SET photo=? WHERE employee_id=?");
                $stmtP->bind_param("ss", $fileName, $employee_id);
                $stmtP->execute();
            }
        }

        $conn->commit();
        $success = "✅ تم إضافة الإداري بنجاح!";
    } catch(Exception $e){
        $conn->rollback();
        $error = "❌ حدث خطأ أثناء الحفظ: ".$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إضافة إداري</title>
<style>
/* حافظنا على كامل التصميم السابق */
body{margin:0;font-family:Arial;background:linear-gradient(to bottom,#4b2e83,#f0e6ff);}
.container{width:90%;margin:30px auto;background:#fff;padding:25px;border-radius:12px;border-top:6px solid #4b2e83}
h1{text-align:center;color:#4b2e83}
.success{text-align:center;color:green;font-weight:bold}
.error{text-align:center;color:red;font-weight:bold}
.back{background:#4b2e83;color:#fff;padding:7px 18px;border-radius:6px;text-decoration:none;display:inline-block;margin-bottom:10px}
input,select,textarea{width:100%;padding:10px;margin-bottom:12px;border-radius:6px;border:1px solid #ccc}
button[type=submit]{background:#4b2e83;color:#fff;padding:12px 25px;border:none;border-radius:8px;cursor:pointer}
.tab{display:none}.tab.active{display:block}
.tab-buttons{text-align:center;margin-bottom:20px}
.tab-buttons button{padding:10px 18px;border:none;border-radius:6px;margin:5px;cursor:pointer}
.tab-buttons button.active{background:#4b2e83;color:#fff}
.hidden{display:none}
</style>
<script>
function showTab(i){
    document.querySelectorAll('.tab').forEach(t=>t.classList.remove('active'));
    document.querySelectorAll('.tab-buttons button').forEach(b=>b.classList.remove('active'));
    document.querySelectorAll('.tab')[i].classList.add('active');
    document.querySelectorAll('.tab-buttons button')[i].classList.add('active');
}
function toggleChildren(){
    var m=document.querySelector("[name=marital_status]").value;
    document.getElementById("childrenField").style.display=(m==="متزوج")?"block":"none";
}
window.onload=()=>{showTab(0);toggleChildren();}
</script>
</head>
<body>
<div class="container">
<a class="back" href="indexadministrators.php">⬅ رجوع</a>
<h1>➕ إضافة إداري جديد</h1>
<?php if($success) echo "<div class='success'>$success</div>"; ?>
<?php if($error) echo "<div class='error'>$error</div>"; ?>

<form method="POST" enctype="multipart/form-data">
<div class="tab-buttons">
<button type="button" onclick="showTab(0)">🎯 الدور</button>
<button type="button" onclick="showTab(1)">👤 البيانات</button>
<button type="button" onclick="showTab(2)">📞 التواصل</button>
<button type="button" onclick="showTab(3)">🎓 المؤهلات</button>
<button type="button" onclick="showTab(4)">💻 الحسابات</button>
<button type="button" onclick="showTab(5)">🖼 الصورة</button>
</div>

<div class="tab active">
<select name="role" required>
<option value="">اختر الدور</option>
<option value="مدير">مدير</option>
<option value="نائب المدير الإداري">نائب المدير الإداري</option>
<option value="النائب الأكاديمي">النائب الأكاديمي</option>
<option value="سكرتير">سكرتير</option>
<option value="محاسب">محاسب</option>
<option value="منسق شؤون الطلاب">منسق شؤون الطلاب</option>
<option value="أخصائي اجتماعي">أخصائي اجتماعي</option>
<option value="أخصائي نفسي">أخصائي نفسي</option>
<option value="طبيب المدرسة">طبيب المدرسة</option>
</select>
</div>

<div class="tab">
<input type="text" name="full_name" placeholder="الاسم الرباعي" required>
<input type="text" name="employee_number" placeholder="الرقم الوظيفي" required>
<input type="text" name="employee_id" placeholder="رقم الهوية" required>
<input type="text" name="personal_id" placeholder="رقم الجواز">
 
<input type="text" name="birth_date" 
       value="<?= (!empty($data['birth_date']) && $data['birth_date'] != '0000-00-00') ? $data['birth_date'] : '' ?>" 
       placeholder="تاريخ الميلاد" 
       onfocus="(this.type='date')" 
       onblur="if(!this.value)this.type='text'">









<input type="text" name="nationality" placeholder="الجنسية">
<select name="marital_status" onchange="toggleChildren()">
<option value="غير متزوج">غير متزوج</option>
<option value="متزوج">متزوج</option>
</select>
<div id="childrenField" class="hidden">
<input type="number" name="num_children" min="0" placeholder="عدد الأبناء">
</div>
<select name="driving_license">
<option value="يوجد">يوجد</option>
<option value="لا يوجد">لا يوجد</option>
</select>
<input type="text" name="hire_date" 
       value="<?= (!empty($data['hire_date']) && $data['hire_date'] != '0000-00-00') ? $data['hire_date'] : '' ?>" 
       placeholder="تاريخ التعيين" 
       onfocus="(this.type='date')" 
       onblur="if(!this.value)this.type='text'">
</div>

<div class="tab">
<input type="text" name="phone_mobile" placeholder="رقم الهاتف في قطر">
<input type="text" name="phone_home" placeholder="رقم الهاتف في البلد">
<input type="text" name="emergency_phone" placeholder="رقم الطوارئ">
<input type="text" name="address_qatar" placeholder="عنوانه في قطر">
<input type="text" name="address_home" placeholder="عنوانه في البلد">
</div>

<div class="tab">
<input type="number" name="experience" placeholder="سنوات الخبرة" min="0">
<input type="text" name="degree" placeholder="المؤهل العلمي">
<input type="text" name="major" placeholder="التخصص الدراسي">
<input type="text" name="university" placeholder="الجامعة/الكلية">
<input type="number" name="graduation_year" placeholder="سنة التخرج" min="1900" max="2100">
<textarea name="courses" placeholder="الدورات التدريبية"></textarea>
<textarea name="skills" placeholder="المهارات الإضافية"></textarea>
<input type="number" name="year_of_assignment" style="display:none;" min="1900" max="2100">
</div>

<div class="tab">
<input type="text" name="teams_account" placeholder="حساب Teams">
<input type="text" name="attendance_platform" placeholder="حساب برنامج الغياب">
</div>

<div class="tab">
<input type="file" name="photo" accept="image/png, image/jpeg, image/jpg">
</div>

<button type="submit" name="submit">💾 حفظ</button>
</form>
</div>
</body>
</html>