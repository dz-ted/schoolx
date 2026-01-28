<?php
session_start();
include("db.php");
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// التحقق من جلسة المستخدم
if(!isset($_SESSION['user'])){
    exit("لا يمكن الوصول إلى هذه الصفحة");
}

$employee_id = $_GET['id'] ?? '';
if(!$employee_id) exit("رقم الموظف مفقود!");

// ==================== جلب البيانات ====================

// البيانات الشخصية
$stmt = $conn->prepare("SELECT * FROM supervisors_data WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$personal = $res->fetch_assoc();
$res->free();
$stmt->close();

// البيانات الإدارية
$stmt = $conn->prepare("SELECT * FROM supervisor_admin WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$res->free();
$stmt->close();

// الصفوف
$stmt = $conn->prepare("SELECT class_level, class_number FROM supervisor_classes WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$current_classes = [];
while($r = $res->fetch_assoc()){
    $current_classes[(int)$r['class_level']][] = (int)$r['class_number'];
}
$res->free();
$stmt->close();

// المناوبات الصباحية
$stmt = $conn->prepare("SELECT duty FROM supervisor_shifts_morning WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$morning_shifts = [];
while($r = $res->fetch_assoc()) $morning_shifts[] = $r['duty'];
$res->free();
$stmt->close();

// مناوبات الفرصة
$stmt = $conn->prepare("SELECT duty FROM supervisor_shifts_break WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$break_shifts = [];
while($r = $res->fetch_assoc()) $break_shifts[] = $r['duty'];
$res->free();
$stmt->close();

// التقدير السنوي
$current_year = date("Y");
$years = [];
for($i=0;$i<5;$i++) $years[] = $current_year-$i;

$stmt = $conn->prepare("SELECT year,rating,manual_note FROM annual_notes WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$res = $stmt->get_result();
$annual_notes = [];
while($row = $res->fetch_assoc()){
    $annual_notes[(int)$row['year']] = ['rating'=>$row['rating'], 'manual_note'=>$row['manual_note']];
}
$res->free();
$stmt->close();

// ==================== ترجمة أسماء الأعمدة ====================
$personal_labels = [
    'full_name' => 'الاسم الرباعي',
    'employee_id' => 'رقم الهوية',
    'personal_id' => 'رقم الجواز',
    'birth_date' => 'تاريخ الميلاد',
    'nationality' => 'الجنسية',
    'gender' => 'نوع التعليم',
    'marital_status' => 'الحالة الزوجية',
    'num_children' => 'عدد الأبناء',
    'driving_license' => 'رخصة القيادة',
    'job_title' => 'المسمى الوظيفي',
    'experience' => 'سنوات الخبرة',
    'degree' => 'المؤهل العلمي',
    'major' => 'التخصص الدراسي',
    'university' => 'الجامعة / الكلية',
    'graduation_year' => 'سنة التخرج',
    'courses' => 'الدورات التدريبية',
    'skills' => 'المهارات الإضافية',
    'year_of_assignment' => 'سنة التعيين',
    'phone_mobile' => 'رقم الهاتف في قطر',
    'phone_home' => 'رقم الهاتف في البلد',
    'emergency_phone' => 'رقم الطوارئ',
    'address_qatar' => 'عنوانه في قطر',
    'address_home' => 'عنوانه في البلد',
    'employee_number' => 'الرقم الوظيفي',
    'sponsorship' => 'الكفالة',
    'teams_account' => 'حساب Teams',
    'attendance_platform' => 'حساب برنامج الغياب'
];

$admin_labels = [
    'note_verbal' => 'تنبيه شفوي',
    'note_written' => 'تنبيه كتابي',
    'note_warning' => 'إنذار',
    'assets_pen' => 'قلم',
    'assets_pc' => 'حاسوب',
    'assets_phone' => 'هاتف',
    'assets_car' => 'سيارة'
];

// ==================== إنشاء ملف Excel ====================

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('بيانات المشرف');

$sheet->setCellValue('A1', 'نوع البيانات')->getStyle('A1')->getFont()->setBold(true);
$sheet->setCellValue('B1', 'المعلومات')->getStyle('B1')->getFont()->setBold(true);
$row = 2;

// البيانات الشخصية
$sheet->setCellValue("A$row", "البيانات الشخصية")->getStyle("A$row")->getFont()->setBold(true);
$row++;
foreach($personal_labels as $key => $label){
    $sheet->setCellValue("A$row", $label);
    $sheet->setCellValue("B$row", $personal[$key] ?? '');
    $row++;
}

// البيانات الإدارية
$sheet->setCellValue("A$row", "البيانات الإدارية")->getStyle("A$row")->getFont()->setBold(true);
$row++;
foreach($admin_labels as $key => $label){
    $sheet->setCellValue("A$row", $label);
    $sheet->setCellValue("B$row", $admin[$key] ?? '');
    $row++;
}

// الصفوف حسب المرحلة
$sheet->setCellValue("A$row", "الصفوف حسب المرحلة")->getStyle("A$row")->getFont()->setBold(true);
$row++;
foreach($current_classes as $level => $classes){
    $sheet->setCellValue("A$row", "المرحلة $level");
    $sheet->setCellValue("B$row", implode(', ',$classes));
    $row++;
}

// المناوبات الصباحية
$sheet->setCellValue("A$row", "المناوبات الصباحية")->getStyle("A$row")->getFont()->setBold(true);
$row++;
foreach($morning_shifts as $shift){
    $sheet->setCellValue("A$row", "الصباحية");
    $sheet->setCellValue("B$row", $shift);
    $row++;
}

// مناوبات الفرصة
$sheet->setCellValue("A$row", "مناوبات الفرصة")->getStyle("A$row")->getFont()->setBold(true);
$row++;
foreach($break_shifts as $shift){
    $sheet->setCellValue("A$row", "الفرصة");
    $sheet->setCellValue("B$row", $shift);
    $row++;
}

// التقدير السنوي
$sheet->setCellValue("A$row", "التقدير السنوي")->getStyle("A$row")->getFont()->setBold(true);
$row++;
foreach($years as $year){
    $note = $annual_notes[$year] ?? ['rating'=>'', 'manual_note'=>''];
    $sheet->setCellValue("A$row", "السنة $year");
    $sheet->setCellValue("B$row", "التقييم: ".$note['rating']." | الملاحظة: ".$note['manual_note']);
    $row++;
}

// ==================== إخراج الملف ====================
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
// الحصول على اسم المشرف بدون مشاكل مع الأحرف الخاصة
$employee_name_safe = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $personal['full_name'] ?? 'مشرف');
header('Content-Disposition: attachment; filename="بيانات المشرف_'.$employee_name_safe.'.xlsx"');
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>