<?php
session_start();
include("db.php"); // الاتصال بقاعدة البيانات

// إزالة حد الوقت
set_time_limit(0);
ini_set('max_execution_time', 0);

// تحميل مكتبة PhpSpreadsheet
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

// ملف Excel
$excelFile = 'xl.xlsx';
if(!file_exists($excelFile)){
    exit("ملف Excel غير موجود!");
}

// قراءة ملف Excel
$spreadsheet = IOFactory::load($excelFile);
$sheet = $spreadsheet->getActiveSheet();
$rows = $sheet->toArray();

// بدء من الصف الثاني إذا الصف الأول هو العناوين
for($i = 1; $i < count($rows); $i++){
    $employee_id = trim($rows[$i][0]);
    $drive_link = trim($rows[$i][1]);

    if(!$employee_id){
        echo "رقم موظف فارغ في الصف ".($i+1)."<br>";
        continue;
    }

    $image_name = 'avatar.png'; // افتراضي

    // تحويل رابط Google Drive إلى رابط مباشر
    if(preg_match('/open\?id=([a-zA-Z0-9_-]+)/', $drive_link, $matches)){
        $fileId = $matches[1];
        $direct_link = "https://drive.google.com/uc?export=download&id=".$fileId;

        // جلب محتوى الصورة
        $image_contents = @file_get_contents($direct_link);
        if($image_contents){
            // اكتشاف نوع الصورة
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime_type = $finfo->buffer($image_contents);
            $ext = '';
            switch($mime_type){
                case 'image/jpeg': $ext = 'jpg'; break;
                case 'image/png': $ext = 'png'; break;
                case 'image/gif': $ext = 'gif'; break;
                default: $ext = 'jpg'; // افتراضي إذا النوع غير معروف
            }

            $image_name = $employee_id.'.'.$ext;
            file_put_contents("uploads/$image_name", $image_contents);
        }
    }

    // التحقق من وجود سجل في admin_data
    $stmt_check = $conn->prepare("SELECT employee_id FROM admin_data WHERE employee_id=?");
    $stmt_check->bind_param("s", $employee_id);
    $stmt_check->execute();
    $res_check = $stmt_check->get_result();

    if($res_check->num_rows > 0){
        // تحديث الصورة
        $stmt_up = $conn->prepare("UPDATE admin_data SET image=? WHERE employee_id=?");
        $stmt_up->bind_param("ss", $image_name, $employee_id);
        $stmt_up->execute();
    } else {
        // إدراج سجل جديد
        $stmt_ins = $conn->prepare("INSERT INTO admin_data (employee_id, image) VALUES (?, ?)");
        $stmt_ins->bind_param("ss", $employee_id, $image_name);
        $stmt_ins->execute();
    }

    echo "تم تحديث الصورة للموظف $employee_id<br>";
}

echo "<br>تم الانتهاء من جميع الصور.";
?>