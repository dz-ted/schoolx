<?php
require 'vendor/autoload.php'; // مكتبة PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    exit("⛔ لا يمكن الوصول");
}

$employee_id = $_GET['id'] ?? '';
if(!$employee_id) exit("رقم العامل مفقود");

// جلب بيانات العامل
$stmt = $conn->prepare("SELECT * FROM workers_data WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

// جلب البيانات الإدارية
$stmtAD = $conn->prepare("SELECT * FROM worker_admin_data WHERE employee_id=?");
$stmtAD->bind_param("s",$employee_id);
$stmtAD->execute();
$admin_data = $stmtAD->get_result()->fetch_assoc();
$stmtAD->close();

// فك JSON للبيانات الإدارية
$cleaning = json_decode($admin_data['cleaning_tasks'] ?? '{}', true);
$cleaning_places = json_decode($admin_data['cleaning_places'] ?? '[]', true);
$presence_places = json_decode($admin_data['presence_places'] ?? '[]', true);
$assets = json_decode($admin_data['assets'] ?? '[]', true);
$buses = json_decode($admin_data['buses'] ?? '[]', true);
$ratings = json_decode($admin_data['annual_ratings'] ?? '{}', true);

function safe($v){ return $v ?? ''; }

// إنشاء ملف Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("بيانات العامل");

// تنسيق الأعمدة
$sheet->getColumnDimension('A')->setWidth(25);
$sheet->getColumnDimension('B')->setWidth(30);
$sheet->getColumnDimension('C')->setWidth(50);

$row = 1;

// دالة لإضافة عنوان قسم
function addSectionTitle($sheet, &$row, $title){
    $sheet->setCellValue("A$row", $title);
    $sheet->mergeCells("A$row:C$row");
    $sheet->getStyle("A$row")->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $sheet->getStyle("A$row")->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
          ->getStartColor()->setRGB('004080');
    $row++;
}

// دالة لإضافة حقل
function addField($sheet, &$row, $field, $value){
    $sheet->setCellValue("A$row", $field);
    $sheet->setCellValue("B$row", $value);
    $row++;
}

// البيانات الشخصية
addSectionTitle($sheet, $row, "البيانات الشخصية");
addField($sheet, $row, "رقم الهوية", safe($worker['employee_id']));
addField($sheet, $row, "الاسم الرباعي", safe($worker['full_name']));
addField($sheet, $row, "الجنسية", safe($worker['nationality']));
addField($sheet, $row, "تاريخ الميلاد", safe($worker['birth_date']));
addField($sheet, $row, "الحالة الزوجية", safe($worker['marital_status']));
addField($sheet, $row, "عدد الأبناء", safe($worker['num_children']));
addField($sheet, $row, "رخصة القيادة", safe($worker['driving_license']));

// المؤهلات والخبرات
addSectionTitle($sheet, $row, "المؤهلات والخبرات");
addField($sheet, $row, "المؤهل", safe($worker['degree']));
addField($sheet, $row, "التخصص", safe($worker['major']));
addField($sheet, $row, "الجامعة", safe($worker['university']));
addField($sheet, $row, "سنة التخرج", safe($worker['graduation_year']));
addField($sheet, $row, "سنوات الخبرة", safe($worker['experience']));
addField($sheet, $row, "الدورات التدريبية", safe($worker['courses']));
addField($sheet, $row, "المهارات الإضافية", safe($worker['skills']));
addField($sheet, $row, "سنة التعيين", safe($worker['year_of_assignment']));

// التواصل
addSectionTitle($sheet, $row, "بيانات التواصل");
addField($sheet, $row, "هاتف قطر", safe($worker['phone_mobile']));
addField($sheet, $row, "هاتف البلد", safe($worker['phone_home']));
addField($sheet, $row, "هاتف الطوارئ", safe($worker['emergency_phone']));
addField($sheet, $row, "عنوان قطر", safe($worker['address_qatar']));
addField($sheet, $row, "عنوان البلد", safe($worker['address_home']));

// البيانات الإدارية
addSectionTitle($sheet, $row, "المهام اليومية (النظافة)");
foreach([10,11,12] as $c){
    addField($sheet, $row, "صفوف $c", !empty($cleaning[$c]) ? implode(', ', $cleaning[$c]) : '—');
}

addSectionTitle($sheet, $row, "أماكن النظافة");
addField($sheet, $row, "الأماكن", implode(', ', $cleaning_places ?: ['—']));

addSectionTitle($sheet, $row, "أماكن التواجد");
addField($sheet, $row, "الأماكن", implode(', ', $presence_places ?: ['—']));

addSectionTitle($sheet, $row, "العهدات");
addField($sheet, $row, "العهدات", implode(', ', $assets ?: ['—']));

addSectionTitle($sheet, $row, "الباص");
addField($sheet, $row, "الباص", implode(', ', $buses ?: ['—']));

addSectionTitle($sheet, $row, "التقديرات السنوية");
foreach($ratings as $year=>$r){
    addField($sheet, $row, $year, ($r['rating']??'') . " — " . ($r['note']??''));
}

// تصدير الملف
$filename = preg_replace('/[^\w\-ء-ي ]/u', '', $worker['full_name']) . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;