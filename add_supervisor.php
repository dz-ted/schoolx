<?php
session_start();
include("db.php");

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$success = $error = '';

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// ===== حفظ مشرف واحد مع صورة =====
if(isset($_POST['submit']) && !isset($_POST['download_excel']) && !isset($_POST['upload_excel'])){
    $employee_id = $_POST['employee_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $personal_id = $_POST['personal_id'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $nationality = $_POST['nationality'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $marital_status = $_POST['marital_status'] ?? '';
    $num_children = ($marital_status=="متزوج") ? $_POST['num_children'] ?? 0 : 0;
    $driving_license = $_POST['driving_license'] ?? '';
    $job_title = $_POST['job_title'] ?? '';
    $experience = $_POST['experience'] ?? 0;
    $degree = $_POST['degree'] ?? '';
    $major = $_POST['major'] ?? '';
    $university = $_POST['university'] ?? '';
    $graduation_year = $_POST['graduation_year'] ?? 0;
    $courses = $_POST['courses'] ?? '';
    $skills = $_POST['skills'] ?? '';
    $year_of_assignment = $_POST['year_of_assignment'] ?? 0;
    $phone_mobile = $_POST['phone_mobile'] ?? '';
    $phone_home = $_POST['phone_home'] ?? '';
    $emergency_phone = $_POST['emergency_phone'] ?? '';
    $address_qatar = $_POST['address_qatar'] ?? '';
    $address_home = $_POST['address_home'] ?? '';
    $employee_number = $_POST['employee_number'] ?? '';
    $sponsorship = $_POST['sponsorship'] ?? '';
    $teams_account = $_POST['teams_account'] ?? '';
    $attendance_platform = $_POST['attendance_platform'] ?? '';
    $stage = $_POST['stage'] ?? $_SESSION['stage'] ?? ''; // ✅ إضافة المرحلة

    // ===== رفع الصورة =====
    $photo = 'avatar.png';
    if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo = $employee_id . '.' . $ext;
        $uploadDir = 'uploads/supervisors/';
        if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photo);
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("INSERT INTO supervisors_data 
        (employee_id, full_name, personal_id, birth_date, nationality, gender, marital_status, num_children, driving_license, job_title, experience, degree, major, university, graduation_year, courses, skills, year_of_assignment, phone_mobile, phone_home, emergency_phone, address_qatar, address_home, employee_number, sponsorship, teams_account, attendance_platform, photo, stage)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param(
            "sssssssississssssisssssssssss",
            $employee_id, $full_name, $personal_id, $birth_date, $nationality, $gender,
            $marital_status, $num_children, $driving_license, $job_title,
            $experience, $degree, $major, $university, $graduation_year,
            $courses, $skills, $year_of_assignment,
            $phone_mobile, $phone_home, $emergency_phone, $address_qatar, $address_home,
            $employee_number, $sponsorship, $teams_account, $attendance_platform, $photo, $stage
        );
        $stmt->execute();
        $conn->commit();
        $success = "✅ تم إضافة المشرف بنجاح!";
    } catch(Exception $e){
        $conn->rollback();
        $error = "❌ حدث خطأ أثناء الحفظ: " . $e->getMessage();
    }
}

// ===== تحميل Excel فارغ =====
if(isset($_POST['download_excel'])){
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $headers = [
        'رقم الهوية', 'الاسم الرباعي', 'رقم الجواز', 'تاريخ الميلاد', 'الجنسية', 'نوع التعليم', 'الحالة الزوجية', 'عدد الأبناء', 'رخصة القيادة', 'المسمى الوظيفي',
        'سنوات الخبرة', 'المؤهل العلمي', 'التخصص الدراسي', 'الجامعة / الكلية', 'سنة التخرج', 'الدورات التدريبية', 'المهارات الإضافية', 'سنة التعيين',
        'رقم الهاتف في قطر', 'رقم الهاتف في البلد', 'رقم الطوارئ', 'عنوانه في قطر', 'عنوانه في البلد', 'الرقم الوظيفي', 'الكفالة', 'حساب Teams', 'حساب برنامج الغياب', 'المرحلة'
    ];
    $sheet->fromArray($headers, NULL, 'A1');
    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="supervisors_template.xlsx"');
    $writer->save('php://output');
    exit();
}

// ===== رفع Excel =====
if(isset($_POST['upload_excel'])){
    if(!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] != 0){
        $error = "❌ الرجاء اختيار ملف المشرفين قبل الرفع!";
    } else {
        $fileTmpPath = $_FILES['excel_file']['tmp_name'];
        $spreadsheet = IOFactory::load($fileTmpPath);
        $rows = $spreadsheet->getActiveSheet()->toArray();
        array_shift($rows); // إزالة رؤوس الأعمدة

        $conn->begin_transaction();
        try {
            foreach($rows as $row){
                $photoExcel = 'avatar.png';
                $stageExcel = $_SESSION['stage'] ?? ''; // ✅ إضافة المرحلة لكل مشرف من Excel
                $stmt = $conn->prepare("INSERT INTO supervisors_data 
                (employee_id, full_name, personal_id, birth_date, nationality, gender, marital_status, num_children, driving_license, job_title, experience, degree, major, university, graduation_year, courses, skills, year_of_assignment, phone_mobile, phone_home, emergency_phone, address_qatar, address_home, employee_number, sponsorship, teams_account, attendance_platform, photo, stage)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param(
                    "sssssssississssssisssssssssss",
                    $row[0], $row[1], $row[2], $row[3], $row[4], $row[5],
                    $row[6], $row[7], $row[8], $row[9],
                    $row[10], $row[11], $row[12], $row[13], $row[14],
                    $row[15], $row[16], $row[17],
                    $row[18], $row[19], $row[20], $row[21], $row[22],
                    $row[23], $row[24], $row[25], $row[26], $photoExcel, $stageExcel
                );
                $stmt->execute();
            }
            $conn->commit();
            $success = "✅ تم رفع بيانات المشرفين بنجاح!";
        } catch(Exception $e){
            $conn->rollback();
            $error = "❌ حدث خطأ أثناء رفع الملف: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إضافة مشرف جديد</title>
<style>
/* نفس التنسيق القديم بدون أي تعديل */
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
<a class="back" href="index_supervisors.php">⬅ الرجوع</a>
<h1>➕ إضافة مشرف جديد</h1>

<?php if($success) echo "<div class='success'>$success</div>"; ?>
<?php if($error) echo "<div class='error'>$error</div>"; ?>

<!-- أزرار Excel -->
<form method="POST" enctype="multipart/form-data">
    <button type="submit" name="download_excel">⬇ تحميل نموذج Excel فارغ</button>
    <input type="file" name="excel_file" style="margin-top:10px;">
    <button type="submit" name="upload_excel">⬆ رفع ملف Excel</button>
</form>

<form method="POST" enctype="multipart/form-data">
<input type="hidden" name="stage" value="<?php echo htmlspecialchars($_SESSION['stage'] ?? ''); ?>">

<div class="tab-buttons">
    <button type="button" onclick="showTab(0)">👤 البيانات الشخصية</button>
    <button type="button" onclick="showTab(1)">🎓 المؤهلات والخبرات</button>
    <button type="button" onclick="showTab(2)">📞 التواصل</button>
    <button type="button" onclick="showTab(3)">💼 البيانات الوظيفية</button>
    <button type="button" onclick="showTab(4)">💻 الحسابات</button>
</div>

<!-- باقي الـ tabs بدون أي تغيير -->
<!-- البيانات الشخصية -->
<div class="tab" id="tab-personal">
<label>الاسم الرباعي</label><input type="text" name="full_name" required>
<label>رقم الهوية</label><input type="text" name="employee_id" required>
<label>رقم الجواز</label><input type="text" name="personal_id">
<label>تاريخ الميلاد</label><input type="text" name="birth_date">
<label>الجنسية</label><input type="text" name="nationality">
<label>نوع التعليم</label>
<select name="gender">
<option value="تعليم حكومي">تعليم حكومي</option>
<option value="تعليم خاص">تعليم خاص</option>
<option value="تعليم في بلدي الأم">تعليم في بلدي الأم</option>
</select>
<label>الحالة الزوجية</label>
<select name="marital_status" onchange="toggleChildrenField()">
<option value="غير متزوج">غير متزوج</option>
<option value="متزوج">متزوج</option>
</select>
<div id="childrenField" class="hidden">
<label>عدد الأبناء</label><input type="number" name="num_children" min="0">
</div>
<label>رخصة القيادة</label>
<select name="driving_license">
<option value="يوجد">يوجد</option>
<option value="لا يوجد">لا يوجد</option>
</select>
<label>المسمى الوظيفي</label><input type="text" name="job_title">
<label>الصورة الشخصية</label><input type="file" name="photo" accept="image/*">
</div>

<!-- المؤهلات والخبرات -->
<div class="tab" id="tab-qualifications">
<label>سنوات الخبرة</label><input type="number" name="experience" min="0">
<label>المؤهل العلمي</label><input type="text" name="degree">
<label>التخصص الدراسي</label><input type="text" name="major">
<label>الجامعة / الكلية</label><input type="text" name="university">
<label>سنة التخرج</label><input type="number" name="graduation_year" min="1900" max="2100">
<label>الدورات التدريبية</label><textarea name="courses"></textarea>
<label>المهارات الإضافية</label><textarea name="skills"></textarea>
<label>سنة التعيين</label><input type="number" name="year_of_assignment" min="1900" max="2100">
</div>

<!-- التواصل -->
<div class="tab" id="tab-contact">
<label>رقم الهاتف في قطر</label><input type="text" name="phone_mobile">
<label>رقم الهاتف في البلد</label><input type="text" name="phone_home">
<label>رقم الطوارئ</label><input type="text" name="emergency_phone">
<label>عنوانه في قطر</label><input type="text" name="address_qatar">
<label>عنوانه في البلد</label><input type="text" name="address_home">
</div>

<!-- البيانات الوظيفية -->
<div class="tab" id="tab-job">
<label>الرقم الوظيفي</label><input type="text" name="employee_number">
<label>الكفالة</label><input type="text" name="sponsorship">
</div>

<!-- الحسابات -->
<div class="tab" id="tab-accounts">
<label>حساب Teams</label><input type="text" name="teams_account">
<label>حساب برنامج الغياب</label><input type="text" name="attendance_platform">
</div>

<button type="submit" name="submit">💾 حفظ</button>
</form>
</div>
</body>
</html>