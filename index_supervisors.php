<?php
session_start();
include("db.php");
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// المرحلة الحالية من الجلسة
$current_stage = $_SESSION['stage'] ?? '';

// تصدير Excel
if(isset($_POST['export_excel'])){
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $headers = ['رقم الهوية','الاسم','رقم الجواز','تاريخ الميلاد','الجنسية','نوع التعليم','الحالة الزوجية','عدد الأبناء','رخصة القيادة','المسمى الوظيفي','سنوات الخبرة','المؤهل العلمي','التخصص','الجامعة','سنة التخرج','الدورات','المهارات','سنة التعيين','الهاتف في قطر','الهاتف في البلد','هاتف الطوارئ','عنوان قطر','عنوان البلد','الرقم الوظيفي','الكفالة','Teams','برنامج الغياب','الصورة'];
    $sheet->fromArray($headers, NULL, 'A1');

    $result = $conn->query("SELECT * FROM supervisors_data WHERE stage='". $conn->real_escape_string($current_stage) ."'");
    $rowIndex = 2;
    while($row = $result->fetch_assoc()){
        $sheet->fromArray(array_values($row), NULL, 'A'.$rowIndex);
        $rowIndex++;
    }

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="supervisors.xlsx"');
    $writer->save('php://output');
    exit();
}

// جلب بيانات المشرفين حسب المرحلة فقط
$supervisors = $conn->query("SELECT * FROM supervisors_data WHERE stage='". $conn->real_escape_string($current_stage) ."' ORDER BY full_name ASC");
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>الصفحة الرئيسية للمشرفين</title>
<style>
/* نسخ كامل CSS الأصلي كما هو */
body {
   margin:0;
    font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #8bb4d1ff, #17177aff);
    min-height: 100vh;
}
header {
    background:#fff;
    padding:15px 30px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    box-shadow:0 2px 12px rgba(0,0,0,0.08);
    position: sticky;
    top:0;
    z-index:100;
}
header h1 {
    margin:0;
    font-size:26px;
    color:#333;
    font-weight:bold;
}
header .buttons {
    display:flex;
    gap:10px;
    flex-wrap:wrap;
}
header .buttons form, header .buttons a { display:inline-block; }
header button, header a.button {
    background:#FF6F00;
    color:#fff;
    border:none;
    padding:10px 18px;
    border-radius:8px;
    cursor:pointer;
    font-weight:bold;
    text-decoration:none;
    transition:0.3s;
}
header button:hover, header a.button:hover { background:#FF8F33; }

.search-bar {
    width:100%;
    max-width:400px;
    margin:15px auto;
    display:flex;
}
.search-bar input {
    width:100%;
    padding:10px 15px;
    border-radius:8px 0 0 8px;
    border:1px solid #ccc;
    outline:none;
    font-size:14px;
}
.search-bar button {
    padding:10px 15px;
    border:none;
    border-radius:0 8px 8px 0;
    background:#1E88E5;
    color:#fff;
    cursor:pointer;
    transition:0.3s;
}
.search-bar button:hover { background:#1565C0; }

.container {
        width:95%;
    margin:20px auto;
    display:grid;
    grid-template-columns: repeat(auto-fill,minmax(220px,1fr));
    gap:20px;
    background: rgba(255,255,255,0.1);
    padding:10px;
    border-radius:12px;
}

.card {
     background: rgba(255, 255, 255, 0.85);
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.08);
    overflow:hidden;
    transition: transform 0.3s, box-shadow 0.3s;
    display:flex;
    flex-direction:column;
    align-items:center;
    text-align:center;
    padding:15px 10px;
}
.card:hover {
       transform: translateY(-5px);
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
}

.card img {
    width:100px;
    height:100px;
    border-radius:50%;
    object-fit:cover;
    margin-bottom:10px;
    border:2px solid #FF6F00;
}

.card .info h3 {
    margin:5px 0;
    font-size:16px;
    color:#FF6F00;
    font-weight:bold;
}
.card .info p {
    margin:2px 0;
    font-size:13px;
    color:#555;
}

.card .actions {
    display:flex;
    justify-content:center;
    gap:8px;
    margin-top:10px;
    flex-wrap:wrap;
}
.card .actions a {
    text-decoration:none;
    font-size:12px;
    color:#fff;
    padding:5px 10px;
    border-radius:5px;
    transition:0.3s;
}
.card .actions a.view { background:#1976D2; }
.card .actions a.edit { background:#F57C00; }
.card .actions a.delete { background:#D32F2F; }
.card .actions a.admin { background:#6C757D; }
.card .actions a:hover { opacity:0.85; }

@media(max-width:600px){
    .container { grid-template-columns: repeat(auto-fill,minmax(180px,1fr)); }
}
</style>
<script>
function filterCards() {
    let input = document.getElementById('searchInput').value.toLowerCase();
    let cards = document.querySelectorAll('.card');
    cards.forEach(card => {
        let name = card.querySelector('h3').textContent.toLowerCase();
        let id = card.querySelector('.id').textContent.toLowerCase();
        if(name.includes(input) || id.includes(input)){
            card.style.display='flex';
        } else {
            card.style.display='none';
        }
    });
}
</script>
</head>
<body>

<header>
<h1>المشرفون - مرحلتك: <?php echo htmlspecialchars($current_stage); ?></h1>
<div class="buttons">
<?php if($_SESSION['can_add'] ?? 0): ?>
<a href="add_supervisor.php" class="button">➕ إضافة مشرف</a>
<?php endif; ?>
<form method="POST" style="display:inline;">
<button type="submit" name="export_excel">⬇ تصدير Excel</button>
</form>
<a href="index.php" class="button">🚪 تسجيل الخروج</a>
</div>
</header>

<div class="search-bar">
    <button onclick="filterCards()">🔍</button>
    <input type="text" id="searchInput" onkeyup="filterCards()" placeholder="ابحث بالاسم أو الرقم الوظيفي...">
</div>

<div class="container">
<?php while($row = $supervisors->fetch_assoc()): 
    // ✅ التحقق من وجود employee_id قبل أي زر
    if(empty($row['employee_id'])) continue; 
?>
<div class="card">
<img src="uploads/supervisors/<?php echo htmlspecialchars($row['photo']); ?>" alt="صورة المشرف">
<div class="info">
<h3><?php echo htmlspecialchars($row['full_name']); ?></h3>
<p class="id">رقم الهوية: <?php echo htmlspecialchars($row['employee_id']); ?></p>
<p>الرقم الوظيفي: <?php echo htmlspecialchars($row['employee_number']); ?></p>
<p>الهاتف: <?php echo htmlspecialchars($row['phone_mobile']); ?></p>
</div>
<div class="actions">
<?php if($_SESSION['can_view'] ?? 1): ?>
<a href="view_supervisor.php?id=<?php echo urlencode($row['employee_id']); ?>" class="view">👁 معاينة</a>
<?php endif; ?>
<?php if($_SESSION['can_edit'] ?? 0): ?>
<a href="edit_supervisor.php?id=<?php echo urlencode($row['employee_id']); ?>" class="edit">✏ تعديل</a>
<?php endif; ?>
<?php if($_SESSION['can_delete'] ?? 0): ?>
<a href="supervisor_delete.php?id=<?php echo urlencode($row['employee_id']); ?>" 
   onclick="return confirm('⚠️ هل أنت متأكد من حذف جميع البيانات للمشرف: <?php echo addslashes($row['full_name']); ?> ؟');"
   class="delete">حذف</a>
<?php endif; ?>
<?php if($_SESSION['can_admin_data'] ?? 0): ?>
<a href="supervisor_admin.php?id=<?php echo urlencode($row['employee_id']); ?>" class="admin">📊 البيانات الإدارية</a>
<?php endif; ?>
</div>
</div>
<?php endwhile; ?>
</div>

</body>
</html>