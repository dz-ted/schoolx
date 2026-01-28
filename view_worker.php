<?php
session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    exit("⛔ لا يمكن الوصول");
}

$employee_id = $_GET['id'] ?? '';
if(!$employee_id) exit("رقم العامل مفقود");

/* =======================
   جلب بيانات العامل
======================= */
$stmt = $conn->prepare("SELECT * FROM workers_data WHERE employee_id=?");
$stmt->bind_param("s",$employee_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$worker) exit("العامل غير موجود");

/* =======================
   البيانات الإدارية
======================= */
$stmtA = $conn->prepare("SELECT * FROM worker_admin WHERE employee_id=?");
$stmtA->bind_param("s",$employee_id);
$stmtA->execute();
$admin = $stmtA->get_result()->fetch_assoc();
$stmtA->close();

$stmtAD = $conn->prepare("SELECT * FROM worker_admin_data WHERE employee_id=?");
$stmtAD->bind_param("s",$employee_id);
$stmtAD->execute();
$admin_data = $stmtAD->get_result()->fetch_assoc();
$stmtAD->close();

/* =======================
   فك JSON
======================= */
$cleaning = json_decode($admin_data['cleaning_tasks'] ?? '{}', true);
$cleaning_places = json_decode($admin_data['cleaning_places'] ?? '[]', true);
$presence_places = json_decode($admin_data['presence_places'] ?? '[]', true);
$assets = json_decode($admin_data['assets'] ?? '[]', true);
$buses = json_decode($admin_data['buses'] ?? '[]', true);
$ratings = json_decode($admin_data['annual_ratings'] ?? '{}', true);

function safe($v){
    return htmlspecialchars($v ?? '');
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>معاينة بيانات العامل</title>
<style>
body{font-family:Cairo,Tahoma;background:#f2f4f8;margin:0}
.container{max-width:1000px;margin:30px auto;background:#fff;padding:25px;border-radius:12px}
h2{text-align:center;color:#800000}
.section{margin-bottom:25px;border:1px solid #ddd;border-radius:10px;padding:15px;background:#fafafa}
.section h3{margin-top:0;color:#004080;border-bottom:2px solid #800000;padding-bottom:5px}
table{width:100%;border-collapse:collapse}
td{padding:8px;border-bottom:1px solid #ddd}
.label{font-weight:bold;color:#333;width:30%}
ul{margin:0;padding-right:20px}
.badge{display:inline-block;background:#004080;color:#fff;padding:4px 10px;border-radius:6px;margin:3px;font-size:13px}
.button-group{text-align:center;margin-bottom:20px}
.button-group button,a{background:#004080;color:#fff;padding:10px 18px;margin:5px;border-radius:6px;text-decoration:none;border:none;cursor:pointer;transition:0.3s}
.button-group button:hover,a:hover{background:#0055aa}
</style>
<script>
function printWorker(){
    window.print();
}
</script>
</head>
<body>

<div class="container">

<div class="button-group">
    <button onclick="printWorker()">🖨️ طباعة</button>
    <a href="export_worker_excel.php?id=<?= urlencode($employee_id) ?>">📊 تصدير Excel</a>
    <a href="index_workers.php">⬅ رجوع</a>
</div>

<div style="text-align:center;margin:15px">
<img src="uploads/workers/<?= safe($worker['photo'] ?? 'avatar.png') ?>" style="width:120px;height:120px;border-radius:50%;border:3px solid #004080">
</div>

<h2><?= safe($worker['full_name']) ?></h2>

<!-- ================= البيانات الشخصية ================= -->
<div class="section">
<h3>👤 البيانات الشخصية</h3>
<table>
<tr><td class="label">رقم الهوية</td><td><?= safe($worker['employee_id']) ?></td></tr>
<tr><td class="label">الجنسية</td><td><?= safe($worker['nationality']) ?></td></tr>
<tr><td class="label">تاريخ الميلاد</td><td><?= safe($worker['birth_date']) ?></td></tr>
<tr><td class="label">الحالة الزوجية</td><td><?= safe($worker['marital_status']) ?></td></tr>
<tr><td class="label">عدد الأبناء</td><td><?= safe($worker['num_children']) ?></td></tr>
<tr><td class="label">رخصة القيادة</td><td><?= safe($worker['driving_license']) ?></td></tr>
</table>
</div>

<!-- ================= المؤهلات ================= -->
<div class="section">
<h3>🎓 المؤهلات والخبرات</h3>
<table>
<tr><td class="label">المؤهل</td><td><?= safe($worker['degree']) ?></td></tr>
<tr><td class="label">التخصص</td><td><?= safe($worker['major']) ?></td></tr>
<tr><td class="label">الجامعة</td><td><?= safe($worker['university']) ?></td></tr>
<tr><td class="label">سنة التخرج</td><td><?= safe($worker['graduation_year']) ?></td></tr>
<tr><td class="label">سنوات الخبرة</td><td><?= safe($worker['experience']) ?></td></tr>
<tr><td class="label">الدورات التدريبية</td><td><?= safe($worker['courses']) ?></td></tr>
<tr><td class="label">المهارات الإضافية</td><td><?= safe($worker['skills']) ?></td></tr>
<tr><td class="label">سنة التعيين</td><td><?= safe($worker['year_of_assignment']) ?></td></tr>
</table>
</div>

<!-- ================= التواصل ================= -->
<div class="section">
<h3>📞 بيانات التواصل</h3>
<table>
<tr><td class="label">هاتف قطر</td><td><?= safe($worker['phone_mobile']) ?></td></tr>
<tr><td class="label">هاتف البلد</td><td><?= safe($worker['phone_home']) ?></td></tr>
<tr><td class="label">هاتف الطوارئ</td><td><?= safe($worker['emergency_phone']) ?></td></tr>
<tr><td class="label">عنوان قطر</td><td><?= safe($worker['address_qatar']) ?></td></tr>
<tr><td class="label">عنوان البلد</td><td><?= safe($worker['address_home']) ?></td></tr>
</table>
</div>

<!-- ================= البيانات الإدارية ================= -->
<div class="section">
<h3>🧹 المهام اليومية (النظافة)</h3>
<?php foreach([10,11,12] as $c): ?>
<strong>صفوف <?= $c ?>:</strong>
<?= !empty($cleaning[$c]) ? implode(', ', $cleaning[$c]) : '—' ?><br>
<?php endforeach; ?>
</div>

<div class="section">
<h3>📍 أماكن النظافة</h3>
<?php foreach($cleaning_places as $p): ?><span class="badge"><?= safe($p) ?></span><?php endforeach; ?>
</div>

<div class="section">
<h3>📌 أماكن التواجد</h3>
<?php foreach($presence_places as $p): ?><span class="badge"><?= safe($p) ?></span><?php endforeach; ?>
</div>

<div class="section">
<h3>🎒 العهدات</h3>
<?php foreach($assets as $a): ?><span class="badge"><?= safe($a) ?></span><?php endforeach; ?>
</div>

<div class="section">
<h3>🚌 الباص</h3>
<?= implode(', ', $buses ?: ['—']) ?>
</div>

<div class="section">
<h3>📊 التقديرات السنوية</h3>
<?php foreach($ratings as $year=>$r): ?>
<b><?= $year ?>:</b> <?= safe($r['rating']) ?> — <?= safe($r['note']) ?><br>
<?php endforeach; ?>
</div>

<div class="section">
<h3>⚠️ الملاحظات الإدارية</h3>
<ul>
<?php if($admin['note_verbal']??0): ?><li>تنبيه شفوي</li><?php endif; ?>
<?php if($admin['note_written']??0): ?><li>تنبيه كتابي</li><?php endif; ?>
<?php if($admin['note_warning']??0): ?><li>إنذار</li><?php endif; ?>
<?php if(($admin['note_discount']??0)>0): ?><li>خصم <?= $admin['note_discount'] ?> أيام</li><?php endif; ?>
</ul>
</div>

</div>
</body>
</html>