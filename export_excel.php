<?php
session_start();
include("db.php"); // الاتصال بقاعدة البيانات
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$message = '';

if(isset($_POST['upload'])){
    $file = $_FILES['excel_file']['tmp_name'];
    if($file){
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $header = array_map('trim', $rows[0]); // الصف الأول كعناوين
        $expectedHeader = [
            'الاسم الرباعي', 'رقم الموظف', 'رقم الهوية / الجواز', 'تاريخ الميلاد', 'الجنسية', 'الجنس',
            'الحالة الزوجية', 'عدد الأبناء', 'رخصة القيادة', 'تاريخ التعيين', 'المسمى الوظيفي', 'القسم / التخصص',
            'الراتب الأساسي', 'البدلات', 'نوع العقد',
            'سنوات الخبرة', 'المؤهل العلمي', 'التخصص الدراسي', 'الجامعة / الكلية', 'سنة التخرج',
            'الدورات التدريبية', 'المهارات الإضافية', 'سنة التعيين',
            'رقم الهاتف في قطر', 'رقم الهاتف في البلد', 'رقم الطوارئ', 'عنوانه في قطر', 'عنوانه في البلد', 'رقم الحساب البنكي',
            'الرقم الوظيفي', 'الراتب', 'الرتبة / المستوى', 'الكفالة',
            'حساب التيمز', 'حساب منصة الغياب'
        ];

        if($header !== $expectedHeader){
            $message = "❌ الأعمدة في الملف لا تطابق القالب المطلوب.";
        } else {
            $successCount = 0;
            $failCount = 0;

            $conn->begin_transaction();
            try{
                for($i=1; $i<count($rows); $i++){
                    $row = $rows[$i];

                    $employee_id = $row[1]; // رقم الموظف

                    // البيانات الشخصية
                    $stmt1 = $conn->prepare("INSERT INTO personal_data (employee_id, full_name, personal_id, birth_date, nationality, gender, marital_status, num_children, driving_license, hire_date, job_title, department, base_salary, allowances, contract_type)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE full_name=VALUES(full_name), personal_id=VALUES(personal_id), birth_date=VALUES(birth_date),
                        nationality=VALUES(nationality), gender=VALUES(gender), marital_status=VALUES(marital_status), num_children=VALUES(num_children),
                        driving_license=VALUES(driving_license), hire_date=VALUES(hire_date), job_title=VALUES(job_title), department=VALUES(department),
                        base_salary=VALUES(base_salary), allowances=VALUES(allowances), contract_type=VALUES(contract_type)");
                    $stmt1->bind_param("sssssssiisssddd",
                        $row[0], $row[1], $row[2], $row[3], $row[4], $row[5],
                        $row[6], $row[7], $row[8], $row[9], $row[10], $row[11],
                        $row[12], $row[13], $row[14]
                    );
                    $stmt1->execute();

                    // المؤهلات والخبرات
                    $stmt2 = $conn->prepare("INSERT INTO qualifications (employee_id, experience, degree, major, university, graduation_year, courses, skills, year_of_assignment)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE experience=VALUES(experience), degree=VALUES(degree), major=VALUES(major), university=VALUES(university),
                        graduation_year=VALUES(graduation_year), courses=VALUES(courses), skills=VALUES(skills), year_of_assignment=VALUES(year_of_assignment)");
                    $stmt2->bind_param("sissssssi",
                        $row[1], $row[15], $row[16], $row[17], $row[18], $row[19],
                        $row[20], $row[21], $row[22]
                    );
                    $stmt2->execute();

                    // التواصل
                    $stmt3 = $conn->prepare("INSERT INTO contact_info (employee_id, phone_mobile, phone_home, emergency_phone, address_qatar, address_home, bank_account)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                        ON DUPLICATE KEY UPDATE phone_mobile=VALUES(phone_mobile), phone_home=VALUES(phone_home), emergency_phone=VALUES(emergency_phone),
                        address_qatar=VALUES(address_qatar), address_home=VALUES(address_home), bank_account=VALUES(bank_account)");
                    $stmt3->bind_param("sssssss",
                        $row[1], $row[23], $row[24], $row[25], $row[26], $row[27], $row[28]
                    );
                    $stmt3->execute();

                    // البيانات الوظيفية
                   // البيانات الوظيفية
$stmt4 = $conn->prepare("INSERT INTO job_data (employee_id, employee_number, job_title, department, salary, job_level, sponsorship)
    VALUES (?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE employee_number=VALUES(employee_number), job_title=VALUES(job_title), department=VALUES(department),
    salary=VALUES(salary), job_level=VALUES(job_level), sponsorship=VALUES(sponsorship)");
$stmt4->bind_param("ssssdds",
    $row[1], $row[29], $row[10], $row[11], $row[30], $row[31], $row[32]
);
$stmt4->execute();

                    // الحسابات
                    $stmt5 = $conn->prepare("INSERT INTO accounts (employee_id, teams_account, attendance_platform)
                        VALUES (?, ?, ?)
                        ON DUPLICATE KEY UPDATE teams_account=VALUES(teams_account), attendance_platform=VALUES(attendance_platform)");
                    $stmt5->bind_param("sss",
                        $row[1], $row[33], $row[34]
                    );
                    $stmt5->execute();

                    $successCount++;
                }
                $conn->commit();
                $message = "✅ تم رفع الملف بنجاح. عدد الصفوف المضافة/المحدثة: $successCount";
            } catch(Exception $e){
                $conn->rollback();
                $message = "❌ حدث خطأ أثناء رفع الملف: ".$e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>رفع بيانات الموظفين من Excel</title>
<style>
body{ font-family: Arial; background: #f8f8f8; padding: 30px; }
.container{ max-width: 600px; margin: auto; background: #fff; padding: 20px; border-radius: 10px; box-shadow:0 0 10px rgba(0,0,0,0.1);}
h1{ color: #800000; text-align:center; }
input[type=file]{ width:100%; padding:10px; margin:10px 0; }
button{ background:#800000; color:#fff; padding:12px 20px; border:none; border-radius:8px; cursor:pointer; }
button:hover{ background:#a00000; }
.success{ color:green; font-weight:bold; }
.error{ color:red; font-weight:bold; }
.back{ display:inline-block; margin-bottom:10px; text-decoration:none; color:#fff; background:#800000; padding:7px 18px; border-radius:6px; transition:0.3s;}
.back:hover{ background:#a00000; }
</style>
</head>
<body>
<div class="container">
<a class="back" href="add.php">⬅ الرجوع لصفحة الإضافة</a>
<h1>رفع بيانات الموظفين من Excel</h1>
<?php if($message) echo "<div class='success'>$message</div>"; ?>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="excel_file" accept=".xlsx" required>
    <button type="submit" name="upload">رفع الملف</button>
</form>
<p>تأكد من أن الأعمدة في الملف مطابقة للقالب المرسل للمدرسين.</p>
</div>
</body>
</html>