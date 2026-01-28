<?php
session_start();
include("db.php"); // الاتصال بقاعدة البيانات

$success = $error = '';

// ===== التحقق من تسجيل الدخول =====
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// ===== تحقق صلاحية الإضافة فقط =====
if(!($_SESSION['can_add'] ?? 0)){
    exit("<p style='text-align:center; margin-top:50px; font-weight:bold; color:red;'>🚫 ليس لديك صلاحية إضافة موظف جديد.</p>");
}

$current_stage = $_SESSION['stage']; // المرحلة الحالية للمستخدم
$allowed_stages = ['ابتدائي', 'اعدادي', 'ثانوي'];

// ===== حفظ موظف جديد =====
if(isset($_POST['submit'])){
    $employee_stage = trim($_POST['stage'] ?? $current_stage);

    if($employee_stage !== $current_stage){
        $error = "❌ لا يمكنك حفظ موظف في مرحلة غير مرحلتك ($current_stage)";
    } elseif(!in_array($employee_stage, $allowed_stages)){
        $error = "❌ المرحلة المختارة غير صالحة";
    } else {
        $employee_id = $_POST['employee_id'] ?? '';
        $full_name = $_POST['full_name'] ?? '';
        $personal_id = $_POST['personal_id'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $nationality = $_POST['nationality'] ?? '';
        $gender = $_POST['gender'] ?? '';
        $marital_status = $_POST['marital_status'] ?? '';
        $num_children = ($marital_status=="متزوج") ? $_POST['num_children'] ?? 0 : 0;
        $driving_license = $_POST['driving_license'] ?? '';
        $hire_date = $_POST['hire_date'] ?? '';
        $job_title = $_POST['job_title'] ?? '';
        $department = $_POST['department'] ?? '';
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

        $phone_mobile = $_POST['phone_mobile'] ?? '';
        $phone_home = $_POST['phone_home'] ?? '';
        $emergency_phone = $_POST['emergency_phone'] ?? '';
        $address_qatar = $_POST['address_qatar'] ?? '';
        $address_home = $_POST['address_home'] ?? '';
        $bank_account = $_POST['bank_account'] ?? '';

        $employee_number = $_POST['employee_number'] ?? '';
        $salary = $_POST['salary'] ?? 0;
        $job_level = $_POST['job_level'] ?? '';
        $sponsorship = $_POST['sponsorship'] ?? '';

        $teams_account = $_POST['teams_account'] ?? '';
        $attendance_platform = $_POST['attendance_platform'] ?? '';

        $conn->begin_transaction();
        try {
            // --- personal_data ---
            $stmt1 = $conn->prepare("INSERT INTO personal_data (employee_id, full_name, personal_id, birth_date, nationality, gender, marital_status, num_children, driving_license, hire_date, job_title, department, base_salary, allowances, stage) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt1->bind_param("ssssssssssssdds", $employee_id, $full_name, $personal_id, $birth_date, $nationality, $gender, $marital_status, $num_children, $driving_license, $hire_date, $job_title, $department, $base_salary, $allowances, $employee_stage);
            $stmt1->execute();

            // --- qualifications ---
            $stmt2 = $conn->prepare("INSERT INTO qualifications (employee_id, experience, degree, major, university, graduation_year, courses, skills, year_of_assignment) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt2->bind_param("sissssssi", $employee_id, $experience, $degree, $major, $university, $graduation_year, $courses, $skills, $year_of_assignment);
            $stmt2->execute();

            // --- contact_info ---
            $stmt3 = $conn->prepare("INSERT INTO contact_info (employee_id, phone_mobile, phone_home, emergency_phone, address_qatar, address_home, bank_account) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt3->bind_param("sssssss", $employee_id, $phone_mobile, $phone_home, $emergency_phone, $address_qatar, $address_home, $bank_account);
            $stmt3->execute();

            // --- job_data ---
            $stmt4 = $conn->prepare("INSERT INTO job_data (employee_id, employee_number, salary, job_level, sponsorship, contract_type, stage) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt4->bind_param("ssddsss", $employee_id, $employee_number, $salary, $job_level, $sponsorship, $contract_type, $employee_stage);
            $stmt4->execute();

            // --- accounts ---
            $stmt5 = $conn->prepare("INSERT INTO accounts (employee_id, teams_account, attendance_platform, stage) VALUES (?, ?, ?, ?)");
            $stmt5->bind_param("ssss", $employee_id, $teams_account, $attendance_platform, $employee_stage);
            $stmt5->execute();

            $conn->commit();
            $success = "✅ تم إضافة الموظف بنجاح!";
        } catch(Exception $e){
            $conn->rollback();
            $error = "❌ حدث خطأ أثناء الحفظ: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إضافة موظف</title>
<style>
/* الاحتفاظ بكل التنسيقات كما هي */
body { margin:0; font-family:Arial, sans-serif; background: linear-gradient(to bottom, #8A1538, #f8eaea);}
.container { width: 90%; margin:30px auto; background: #fff; padding:20px; box-shadow:0 0 15px rgba(128,0,0,0.4); border-radius:12px; border-top:6px solid #800000;}
h1 { color:#800000; text-align:center; margin-bottom:20px;}
.success { color:green; font-weight:bold;}
.error { color:red; font-weight:bold;}
.back { display:inline-block; margin-bottom:10px; text-decoration:none; color:#fff; background:#800000; padding:7px 18px; border-radius:6px; transition:0.3s;}
.back:hover { background:#a00000;}
form { margin-top:20px; }
form input, form select, form textarea { width:100%; margin-bottom:12px; padding:10px; border-radius:6px; border:1px solid #ccc; font-size:14px; transition:border 0.3s;}
form input:focus, form select:focus, form textarea:focus { border:1px solid #800000; outline:none; box-shadow:0 0 5px rgba(128,0,0,0.3);}
form button { background:#800000; color:#fff; border:none; padding:12px 25px; border-radius:8px; cursor:pointer; font-size:15px; transition:0.3s;}
form button:hover { background:#a00000;}
.tab { display:none;}
.tab.active { display:block;}
.tab-buttons { margin-bottom:20px; text-align:center;}
.tab-buttons button { background:#ddd; border:none; padding:10px 15px; cursor:pointer; margin:5px; border-radius:6px; font-weight:bold; transition:0.3s;}
.tab-buttons button.active { background:#800000; color:#fff; transform:scale(1.05);}
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
<a class="back" href="indexteacher.php">⬅ الرجوع</a>
<h1>➕ إضافة موظف جديد</h1>
<?php if($success) echo "<div class='success'>$success</div>"; ?>
<?php if($error) echo "<div class='error'>$error</div>"; ?>

<form method="POST">
<div>
    <label>اختر المرحلة</label>
    <select name="stage" required>
        <?php
        foreach($allowed_stages as $stage){
            $selected = ($stage==$current_stage) ? "selected" : "";
            echo "<option value='$stage' $selected>$stage</option>";
        }
        ?>
    </select>
</div>

<div class="tab-buttons">
    <button type="button" onclick="showTab(0)">👤 البيانات الشخصية</button>
    <button type="button" onclick="showTab(1)">🎓 المؤهلات والخبرات</button>
    <button type="button" onclick="showTab(2)">📞 التواصل</button>
    <button type="button" onclick="showTab(3)">💼 البيانات الوظيفية</button>
    <button type="button" onclick="showTab(4)">💻 الحسابات</button>
</div>

<!-- تبويب البيانات الشخصية -->
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
<label>تاريخ التعيين</label><input type="text" name="hire_date">
<label>المسمى الوظيفي</label><input type="text" name="job_title">
<label>القسم / التخصص</label><input type="text" name="department">
<label>الراتب الأساسي</label><input type="number" name="base_salary" step="0.01">
<label>البدلات</label><input type="number" name="allowances" step="0.01">
<div>
    <label>نوع العقد:</label>
    <select name="contract_type" required>
        <?php
        $contract_options = ['دوام كامل', 'دوام جزئي', 'مؤقت', 'عقد سنوي', 'غير محدد'];
        foreach($contract_options as $option){
            echo '<option value="'.htmlspecialchars($option).'">'.$option.'</option>';
        }
        ?>
    </select>
</div>
</div>

<!-- تبويب المؤهلات والخبرات -->
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

<!-- تبويب التواصل -->
<div class="tab" id="tab-contact">
<label>رقم الهاتف في قطر</label><input type="text" name="phone_mobile">
<label>رقم الهاتف في البلد</label><input type="text" name="phone_home">
<label>رقم الطوارئ</label><input type="text" name="emergency_phone">
<label>عنوانه في قطر</label><input type="text" name="address_qatar">
<label>عنوانه في البلد</label><input type="text" name="address_home">
<label>رقم الحساب البنكي</label><input type="text" name="bank_account">
</div>

<!-- تبويب البيانات الوظيفية -->
<div class="tab" id="tab-job">
<label>الرقم الوظيفي</label><input type="text" name="employee_number">
<label>الراتب</label><input type="number" name="salary" step="0.01">
<label>الرتبة / المستوى</label><input type="text" name="job_level">
<label>الكفالة</label><input type="text" name="sponsorship">
</div>

<!-- تبويب الحسابات -->
<div class="tab" id="tab-accounts">
<label>حساب Teams</label><input type="text" name="teams_account">
<label>حساب برنامج الغياب</label><input type="text" name="attendance_platform">
</div>

<button type="submit" name="submit">💾 حفظ</button>
</form>
</div>
</body>
</html>