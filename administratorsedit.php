<?php
session_start();
include("db.php");

$success = $error = '';

if(!isset($_SESSION['user'])){
    header("Location: ../login.php");
    exit();
}

if(!isset($_GET['employee_id'])){
    exit();
}

$employee_id = $_GET['employee_id'];

/* ===== جلب البيانات ===== */
$stmt = $conn->prepare("
SELECT 
p.*,
q.experience,q.degree,q.major,q.university,q.graduation_year,q.courses,q.skills,q.year_of_assignment,
c.phone_mobile,c.phone_home,c.emergency_phone,c.address_qatar,c.address_home,
r.role,
a.teams_account,a.attendance_platform
FROM administration_personal_data p
LEFT JOIN administration_qualifications q ON p.employee_id=q.employee_id
LEFT JOIN administration_contact_info c ON p.employee_id=c.employee_id
LEFT JOIN administration_roles r ON p.employee_id=r.employee_id
LEFT JOIN administration_accounts a ON p.employee_id=a.employee_id
WHERE p.employee_id=?
");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
if(!$data) exit();

/* ===== حفظ التعديل ===== */
if(isset($_POST['submit'])){
$conn->begin_transaction();
try{

$full_name = $_POST['full_name'] ?? '';
$employee_number = $_POST['employee_number'] ?? '';
$personal_id = $_POST['personal_id'] ?? '';
$birth_date = $_POST['birth_date'] ?? null;
$nationality = $_POST['nationality'] ?? '';
$gender = $_POST['gender'] ?? '';
$marital_status = $_POST['marital_status'] ?? '';
$num_children = ($marital_status=="متزوج") ? ($_POST['num_children'] ?? 0) : 0;
$driving_license = $_POST['driving_license'] ?? '';
$hire_date = $_POST['hire_date'] ?? null;

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

$role = $_POST['role'] ?? '';
$teams_account = $_POST['teams_account'] ?? '';
$attendance_platform = $_POST['attendance_platform'] ?? '';

/* personal */
$stmt1 = $conn->prepare("UPDATE administration_personal_data SET 
full_name=?,employee_number=?,personal_id=?,birth_date=?,nationality=?,gender=?,marital_status=?,num_children=?,driving_license=?,hire_date=? 
WHERE employee_id=?");
$stmt1->bind_param("sssssssssss",$full_name,$employee_number,$personal_id,$birth_date,$nationality,$gender,$marital_status,$num_children,$driving_license,$hire_date,$employee_id);
$stmt1->execute();

/* qualifications */
$stmt2 = $conn->prepare("UPDATE administration_qualifications SET 
experience=?,degree=?,major=?,university=?,graduation_year=?,courses=?,skills=?,year_of_assignment=? 
WHERE employee_id=?");
$stmt2->bind_param("issssssis",$experience,$degree,$major,$university,$graduation_year,$courses,$skills,$year_of_assignment,$employee_id);
$stmt2->execute();

/* contact */
$stmt3 = $conn->prepare("UPDATE administration_contact_info SET 
phone_mobile=?,phone_home=?,emergency_phone=?,address_qatar=?,address_home=? 
WHERE employee_id=?");
$stmt3->bind_param("ssssss",$phone_mobile,$phone_home,$emergency_phone,$address_qatar,$address_home,$employee_id);
$stmt3->execute();

/* role */
$stmt4 = $conn->prepare("UPDATE administration_roles SET role=? WHERE employee_id=?");
$stmt4->bind_param("ss",$role,$employee_id);
$stmt4->execute();

/* accounts */
$stmt5 = $conn->prepare("UPDATE administration_accounts SET teams_account=?,attendance_platform=? WHERE employee_id=?");
$stmt5->bind_param("sss",$teams_account,$attendance_platform,$employee_id);
$stmt5->execute();

/* الصورة */
if(isset($_FILES['photo']) && $_FILES['photo']['error']===UPLOAD_ERR_OK){
    $uploadDir="uplodadmin/";
    if(!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
    $ext=strtolower(pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION));
    if(in_array($ext,['png','jpg','jpeg'])){
        $fileName=$employee_id.".".$ext;
        move_uploaded_file($_FILES['photo']['tmp_name'],$uploadDir.$fileName);
        $stmtP=$conn->prepare("UPDATE administration_personal_data SET photo=? WHERE employee_id=?");
        $stmtP->bind_param("ss",$fileName,$employee_id);
        $stmtP->execute();
    }
}

$conn->commit();
$success = "تم الحفظ بنجاح";
}catch(Exception $e){
$conn->rollback();
}
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تعديل إداري</title>
<style>
body{margin:0;font-family:Arial;background:linear-gradient(to bottom,#4b2e83,#f0e6ff);}
.container{width:90%;margin:30px auto;background:#fff;padding:25px;border-radius:12px;border-top:6px solid #4b2e83}
h1{text-align:center;color:#4b2e83}
.success{text-align:center;color:green;font-weight:bold}
.back{background:#4b2e83;color:#fff;padding:7px 18px;border-radius:6px;text-decoration:none}
.tab{display:none}.tab.active{display:block}
.tab-buttons{text-align:center;margin-bottom:20px}
.tab-buttons button{padding:10px 18px;border:none;border-radius:6px;margin:5px;cursor:pointer}
.tab-buttons button.active{background:#4b2e83;color:#fff}
input,select,textarea{width:100%;padding:10px;margin-bottom:12px;border-radius:6px;border:1px solid #ccc}
button[type=submit]{background:#4b2e83;color:#fff;padding:12px 25px;border:none;border-radius:8px}
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
document.getElementById("children").style.display=(m==="متزوج")?"block":"none";
}
window.onload=()=>{showTab(0);toggleChildren();}
</script>
</head>
<body>
<div class="container">
<a class="back" href="indexadministrators.php">⬅ رجوع</a>
<h1>✏️ تعديل إداري</h1>
<?php if($success) echo "<div class='success'>$success</div>"; ?>

<form method="POST" enctype="multipart/form-data">

<div class="tab-buttons">
<button type="button" onclick="showTab(0)">🎯 الدور</button>
<button type="button" onclick="showTab(1)">👤 البيانات</button>
<button type="button" onclick="showTab(2)">📞 التواصل</button>
<button type="button" onclick="showTab(3)">🎓 المؤهلات</button>
<button type="button" onclick="showTab(4)">💻 الحسابات</button>
<button type="button" onclick="showTab(5)">🖼 الصورة</button>
</div>

<!-- الدور الوظيفي -->
<div class="tab active">
<label>الدور الوظيفي</label>
<?php
$roles = [
    "مدير",
    "نائب المدير الإداري",
    "النائب الأكاديمي",
    "سكرتير",
    "محاسب",
    "منسق شؤون الطلاب",
    "أخصائي اجتماعي",
    "أخصائي نفسي",
    "طبيب المدرسة"
];
$current_role = $data['role'] ?? '';
?>
<select name="role" required>
    <option value="">اختر الدور</option>
    <?php foreach($roles as $role): ?>
        <option value="<?= $role ?>" <?= ($role === $current_role) ? "selected" : "" ?>>
            <?= $role ?>
        </option>
    <?php endforeach; ?>
</select>
</div>

<!-- البيانات الشخصية -->
<div class="tab">
<label>الاسم الرباعي</label>
<input name="full_name" value="<?= htmlspecialchars($data['full_name']) ?>">
<label>الرقم الوظيفي</label>
<input name="employee_number" value="<?= htmlspecialchars($data['employee_number']) ?>">
<label>رقم الهوية</label>
<input name="personal_id" value="<?= htmlspecialchars($data['personal_id']) ?>">
<label>تاريخ الميلاد</label>
<input type="date" name="birth_date" value="<?= ($data['birth_date'] && $data['birth_date'] != '0000-00-00') ? htmlspecialchars($data['birth_date']) : '' ?>">
<label>الجنسية</label>
<input name="nationality" value="<?= htmlspecialchars($data['nationality']) ?>">
<label>الحالة الزوجية</label>
<select name="marital_status" onchange="toggleChildren()">
<option value="غير متزوج" <?= $data['marital_status']=="غير متزوج"?"selected":"" ?>>غير متزوج</option>
<option value="متزوج" <?= $data['marital_status']=="متزوج"?"selected":"" ?>>متزوج</option>
</select>
<div id="children">
<label>عدد الأبناء</label>
<input name="num_children" value="<?= $data['num_children'] ?>">
</div>
<label>رخصة القيادة</label>
<select name="driving_license">
<option value="يوجد" <?= $data['driving_license']=="يوجد"?"selected":"" ?>>يوجد</option>
<option value="لا يوجد" <?= $data['driving_license']=="لا يوجد"?"selected":"" ?>>لا يوجد</option>
</select>
<label>تاريخ التعيين</label>
<input type="text" name="hire_date" 
       value="<?= (!empty($data['hire_date']) && $data['hire_date'] != '0000-00-00') ? $data['hire_date'] : '' ?>" 
       placeholder="تاريخ التعيين" 
       onfocus="(this.type='date')" 
       onblur="if(!this.value)this.type='text'">
</div>

<!-- التواصل -->
<div class="tab">
<label>رقم الهاتف في قطر</label>
<input name="phone_mobile" value="<?= htmlspecialchars($data['phone_mobile']) ?>">
<label>رقم الهاتف في البلد</label>
<input name="phone_home" value="<?= htmlspecialchars($data['phone_home']) ?>">
<label>رقم الطوارئ</label>
<input name="emergency_phone" value="<?= htmlspecialchars($data['emergency_phone']) ?>">
<label>عنوانه في قطر</label>
<input name="address_qatar" value="<?= htmlspecialchars($data['address_qatar']) ?>">
<label>عنوانه في البلد</label>
<input name="address_home" value="<?= htmlspecialchars($data['address_home']) ?>">
</div>

<!-- المؤهلات -->
<div class="tab">
<label>سنوات الخبرة</label>
<input name="experience" value="<?= htmlspecialchars($data['experience']) ?>">
<label>المؤهل العلمي</label>
<input name="degree" value="<?= htmlspecialchars($data['degree']) ?>">
<label>التخصص الدراسي</label>
<input name="major" value="<?= htmlspecialchars($data['major']) ?>">
<label>الجامعة / الكلية</label>
<input name="university" value="<?= htmlspecialchars($data['university']) ?>">
<label>سنة التخرج</label>
<input name="graduation_year" value="<?= htmlspecialchars($data['graduation_year']) ?>">
<label>الدورات التدريبية</label>
<textarea name="courses"><?= htmlspecialchars($data['courses']) ?></textarea>
<label>المهارات الإضافية</label>
<textarea name="skills"><?= htmlspecialchars($data['skills']) ?></textarea>
<label>سنة التعيين</label>
<input name="year_of_assignment" value="<?= htmlspecialchars($data['year_of_assignment']) ?>">
</div>

<!-- الحسابات -->
<div class="tab">
<label>حساب Teams</label>
<input name="teams_account" value="<?= htmlspecialchars($data['teams_account']) ?>">
<label>حساب برنامج الغياب</label>
<input name="attendance_platform" value="<?= htmlspecialchars($data['attendance_platform']) ?>">
</div>

<!-- الصورة -->
<div class="tab">
<?php if($data['photo']) echo "<img src='uplodadmin/{$data['photo']}' width='120'><br>"; ?>
<label>صورة الإداري</label>
<input type="file" name="photo">
</div>

<button type="submit" name="submit">💾 حفظ</button>
</form>
</div>
</body>
</html>