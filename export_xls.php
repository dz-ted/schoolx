<?php
session_start();
include("db.php");

require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// أولًا: جلب كل السنوات الموجودة
$all_years = [];
$year_res = $conn->query("SELECT DISTINCT year FROM annual_notes ORDER BY year ASC");
while($y = $year_res->fetch_assoc()){
    $all_years[] = $y['year'];
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('بيانات المدرسين');

// العناوين
$headers = [
    'رقم الهوية','الاسم الكامل','رقم الجواز','تاريخ الميلاد','الجنسية','التعليم',
    'الحالة الاجتماعية','عدد الأطفال','رخصة القيادة','تاريخ التعيين','المسمى الوظيفي',
    'القسم','الراتب الأساسي','البدلات','نوع العقد','الخبرة (بالسنوات)','المؤهل',
    'التخصص','الجامعة','سنة التخرج','الدورات','المهارات','سنة التعيين (من المؤهلات)',
    'الراتب (الوظيفي)','الكفالة','الجوال','الهاتف المنزلي','هاتف الطوارئ',
    'عنوان قطر','عنوان الدولة الأم','رقم الحساب البنكي',
    // البيانات الإدارية
    'الإجازات - عارضة','الإجازات - مرضية','الإجازات - بدون عذر',
    'العهدات - قلم','العهدات - حاسوب','العهدات - هاتف','العهدات - سيارة',
    'تنبيه شفوي','تنبيه كتابي','إنذار','حالة المدرس',
    'حصص الاحتياط','حصص زائدة','حصص فوق النصاب'
];

// إضافة أعمدة السنوات والتقديرات
foreach($all_years as $year){
    $headers[] = "تقدير $year";
}

// كتابة العناوين
$sheet->fromArray($headers, NULL, 'A1');

// تنسيق العناوين
$sheet->getStyle('A1:ZZ1')->getFont()->setBold(true);
$sheet->getStyle('A1:ZZ1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
$sheet->getRowDimension(1)->setRowHeight(30);

// جلب البيانات
$row = 2;
$employees = $conn->query("SELECT * FROM personal_data ORDER BY full_name ASC");

if($employees && $employees->num_rows > 0){
    while($emp = $employees->fetch_assoc()){
        $emp_id = $emp['employee_id'];
        $qual = $conn->query("SELECT * FROM qualifications WHERE employee_id='$emp_id'")->fetch_assoc();
        $job = $conn->query("SELECT * FROM job_data WHERE employee_id='$emp_id'")->fetch_assoc();
        $contact = $conn->query("SELECT * FROM contact_info WHERE employee_id='$emp_id'")->fetch_assoc();
        $admin = $conn->query("SELECT * FROM admin_data WHERE employee_id='$emp_id'")->fetch_assoc();

        // إنشاء مصفوفة الصف الأساسي
        $rowData = [
            $emp['employee_id'],
            $emp['full_name'],
            $emp['personal_id'],
            $emp['birth_date'],
            $emp['nationality'],
            $emp['gender'],
            $emp['marital_status'],
            $emp['num_children'],
            $emp['driving_license'],
            $emp['hire_date'],
            $emp['job_title'],
            $emp['department'],
            $emp['base_salary'],
            $emp['allowances'],
            $emp['contract_type'],
            $qual['experience'] ?? '',
            $qual['degree'] ?? '',
            $qual['major'] ?? '',
            $qual['university'] ?? '',
            $qual['graduation_year'] ?? '',
            $qual['courses'] ?? '',
            $qual['skills'] ?? '',
            $qual['year_of_assignment'] ?? '',
            $job['salary'] ?? '',
            $job['sponsorship'] ?? '',
            $contact['phone_mobile'] ?? '',
            $contact['phone_home'] ?? '',
            $contact['emergency_phone'] ?? '',
            $contact['address_qatar'] ?? '',
            $contact['address_home'] ?? '',
            $contact['bank_account'] ?? '',
            $admin['leave_casual'] ?? '',
            $admin['leave_sick'] ?? '',
            $admin['leave_unexcused'] ?? '',
            $admin['assets_pen'] ?? '',
            $admin['assets_pc'] ?? '',
            $admin['assets_phone'] ?? '',
            $admin['assets_car'] ?? '',
            $admin['note_verbal'] ?? '',
            $admin['note_written'] ?? '',
            $admin['note_warning'] ?? '',
            $admin['teacher_status'] ?? '',
            $admin['classes_reserve'] ?? '',
            $admin['classes_extra'] ?? '',
            $admin['classes_overload'] ?? ''
        ];

        // إضافة تقديرات السنوات في الأعمدة المناسبة
        $notes = $conn->query("SELECT year,rating FROM annual_notes WHERE employee_id='$emp_id'");
        $year_ratings = [];
        while($n = $notes->fetch_assoc()){
            $year_ratings[$n['year']] = $n['rating'];
        }

        foreach($all_years as $year){
            $rowData[] = $year_ratings[$year] ?? '';
        }

        $sheet->fromArray($rowData, NULL, 'A'.$row);
        $row++;
    }
} else {
    $sheet->setCellValue('A2', 'لا توجد بيانات لعرضها');
}

// ضبط عرض الأعمدة تلقائيًا
foreach (range('A', 'ZZ') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// التفاف النصوص
$sheet->getStyle('A1:ZZ'.$row)->getAlignment()->setWrapText(true);

// إخراج الملف
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="بيانات_مدرسي_ثانوية_الفرقان.xlsx"');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>