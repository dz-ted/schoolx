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

// جمع بيانات POST
$employee_id = $_POST['employee_id'] ?? '';
$stage = $_POST['stage'] ?? '';

if(!$employee_id) exit("رقم الموظف مفقود!");

// جمع البيانات من النموذج
$full_name = $_POST['full_name'] ?? '';
$personal_id = $_POST['personal_id'] ?? '';
$birth_date = $_POST['birth_date'] ?? '';
$nationality = $_POST['nationality'] ?? '';
$gender = $_POST['gender'] ?? '';
$marital_status = $_POST['marital_status'] ?? '';
$num_children = (int)($_POST['num_children'] ?? 0);
$driving_license = $_POST['driving_license'] ?? '';
$employee_number = $_POST['employee_number'] ?? '';
$sponsorship = $_POST['sponsorship'] ?? '';
$job_title = $_POST['job_title'] ?? '';
$experience = (int)($_POST['experience'] ?? 0);
$degree = $_POST['degree'] ?? '';
$major = $_POST['major'] ?? '';
$university = $_POST['university'] ?? '';
$graduation_year = (int)($_POST['graduation_year'] ?? 0);
$courses = $_POST['courses'] ?? '';
$skills = $_POST['skills'] ?? '';
$year_of_assignment = (int)($_POST['year_of_assignment'] ?? 0);
$phone_mobile = $_POST['phone_mobile'] ?? '';
$phone_home = $_POST['phone_home'] ?? '';
$emergency_phone = $_POST['emergency_phone'] ?? '';
$address_qatar = $_POST['address_qatar'] ?? '';
$address_home = $_POST['address_home'] ?? '';
$teams_account = $_POST['teams_account'] ?? '';
$attendance_platform = $_POST['attendance_platform'] ?? '';

// التعامل مع الصورة
$photo = '';
if(isset($_FILES['photo']) && $_FILES['photo']['error'] == 0){
    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
    $photo = $employee_id . '.' . $ext;
    $uploadDir = 'uploads/supervisors/';
    if(!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $photo);
}

// تحديث البيانات
$conn->begin_transaction();
try {
    if($photo){
        $stmt = $conn->prepare("UPDATE supervisors_data SET 
            full_name=?, personal_id=?, birth_date=?, nationality=?, gender=?, marital_status=?, num_children=?, driving_license=?,
            job_title=?, experience=?, degree=?, major=?, university=?, graduation_year=?, courses=?, skills=?, year_of_assignment=?,
            phone_mobile=?, phone_home=?, emergency_phone=?, address_qatar=?, address_home=?, employee_number=?, sponsorship=?, teams_account=?, attendance_platform=?, photo=?
            WHERE employee_id=? AND stage=?");
        $stmt->bind_param(
            "sssssssississssssisssssssssss",
            $full_name, $personal_id, $birth_date, $nationality, $gender, $marital_status, $num_children, $driving_license,
            $job_title, $experience, $degree, $major, $university, $graduation_year, $courses, $skills, $year_of_assignment,
            $phone_mobile, $phone_home, $emergency_phone, $address_qatar, $address_home, $employee_number, $sponsorship, $teams_account, $attendance_platform,
            $photo,
            $employee_id, $stage
        );
    } else {
        $stmt = $conn->prepare("UPDATE supervisors_data SET 
            full_name=?, personal_id=?, birth_date=?, nationality=?, gender=?, marital_status=?, num_children=?, driving_license=?,
            job_title=?, experience=?, degree=?, major=?, university=?, graduation_year=?, courses=?, skills=?, year_of_assignment=?,
            phone_mobile=?, phone_home=?, emergency_phone=?, address_qatar=?, address_home=?, employee_number=?, sponsorship=?, teams_account=?, attendance_platform=?
            WHERE employee_id=? AND stage=?");
        $stmt->bind_param(
            "sssssssississssssissssssssss",
            $full_name, $personal_id, $birth_date, $nationality, $gender, $marital_status, $num_children, $driving_license,
            $job_title, $experience, $degree, $major, $university, $graduation_year, $courses, $skills, $year_of_assignment,
            $phone_mobile, $phone_home, $emergency_phone, $address_qatar, $address_home, $employee_number, $sponsorship, $teams_account, $attendance_platform,
            $employee_id, $stage
        );
    }

    $stmt->execute();
    $stmt->close();
    $conn->commit();

    echo "<script>
        alert('✅ تم حفظ التعديلات بنجاح!');
        window.location.href = 'edit_supervisor.php?id=" . htmlspecialchars($employee_id) . "';
    </script>";

} catch(Exception $e){
    $conn->rollback();
    exit("❌ حدث خطأ أثناء الحفظ: " . $e->getMessage());
}
?>