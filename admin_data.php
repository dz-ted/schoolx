<?php
session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    exit("لا يمكن الوصول إلى هذه الصفحة");
}

$employee_id = $_GET['id'] ?? '';
if(!$employee_id) exit("رقم الموظف مفقود!");

// --- جلب مرحلة الموظف من job_data ---
$stmt_stage = $conn->prepare("SELECT stage FROM job_data WHERE employee_id=?");
$stmt_stage->bind_param("s", $employee_id);
$stmt_stage->execute();
$res_stage = $stmt_stage->get_result();
$job_row = $res_stage->fetch_assoc();

if (!$job_row) {
    exit("⚠ الموظف غير موجود في البيانات الوظيفية!");
}

$employee_stage = trim($job_row['stage']);
$user_stage = $_SESSION['stage'] ?? '';

if ($employee_stage !== $user_stage) {
    exit("⛔ لا تملك صلاحية الوصول إلى بيانات هذا الموظف.");
}

// --- جلب بيانات admin_data ---
$stmt = $conn->prepare("SELECT * FROM admin_data WHERE employee_id=?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
$admin_data = $res->fetch_assoc();

// --- جلب اسم الموظف ---
$stmt_name = $conn->prepare("SELECT full_name FROM personal_data WHERE employee_id=?");
$stmt_name->bind_param("s", $employee_id);
$stmt_name->execute();
$res_name = $stmt_name->get_result();
$personal = $res_name->fetch_assoc();
$employee_name = $personal['full_name'] ?? $employee_id;

// إذا لم يكن موجودًا، ننشئ صفًا جديد مع صورة افتراضية
if(!$admin_data){
    $stmt_insert = $conn->prepare("INSERT INTO admin_data (employee_id, image) VALUES (?, 'avatar.png')");
    $stmt_insert->bind_param("s", $employee_id);
    $stmt_insert->execute();
    $admin_data = [
        'image'=>'avatar.png','teacher_status'=>'',
        'leave_casual'=>0,'leave_sick'=>0,'leave_unexcused'=>0,
        'classes_reserve'=>0,'classes_extra'=>0,'classes_overload'=>0,
        'note_verbal'=>'','note_written'=>'','note_warning'=>'','note_discount'=>0,
        'assets_pen'=>'','assets_pc'=>'','assets_phone'=>'','assets_car'=>''
    ];
}

// ===== التقديرات السنوية =====
$current_year = date("Y");
$years = [];
for($i = 0; $i < 5; $i++){
    $years[] = $current_year - $i;
}

// ===== جلب التقديرات والملاحظات =====
$annual_notes = [];
$stmt_notes = $conn->prepare("SELECT * FROM annual_notes WHERE employee_id=?");
$stmt_notes->bind_param("s", $employee_id);
$stmt_notes->execute();
$res_notes = $stmt_notes->get_result();
while($row = $res_notes->fetch_assoc()){
    $annual_notes[$row['year']] = [
        'rating' => $row['rating'],
        'manual_note' => $row['manual_note']
    ];
}
$stmt_notes->close();

// ===== معالجة POST =====
if($_SERVER['REQUEST_METHOD']==='POST'){

    // رفع الصورة
    $image_name = $admin_data['image'];
    if(isset($_FILES['image']) && $_FILES['image']['tmp_name']){
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_image_name = $employee_id . '.' . $ext;
        if(move_uploaded_file($_FILES['image']['tmp_name'], "uploads/$new_image_name")){
            $image_name = $new_image_name;
        }
    }

    // --- جمع كل الحقول ---
    $leave_casual = (int)($_POST['leave_casual'] ?? 0);
    $leave_sick = (int)($_POST['leave_sick'] ?? 0);
    $leave_unexcused = (int)($_POST['leave_unexcused'] ?? 0);
    $classes_reserve = (int)($_POST['classes_reserve'] ?? 0);
    $classes_extra = (int)($_POST['classes_extra'] ?? 0);
    $classes_overload = (int)($_POST['classes_overload'] ?? 0);
    $teacher_status = (string)($_POST['teacher_status'] ?? '');
    $note_verbal = (string)($_POST['note_verbal'] ?? '');
    $note_written = (string)($_POST['note_written'] ?? '');
    $note_warning = (string)($_POST['note_warning'] ?? '');
    $note_discount = (int)($_POST['note_discount'] ?? 0);
    $assets_pen = (string)($_POST['assets_pen'] ?? '');
    $assets_pc = (string)($_POST['assets_pc'] ?? '');
    $assets_phone = (string)($_POST['assets_phone'] ?? '');
    $assets_car = (string)($_POST['assets_car'] ?? '');

    // --- تحديث جدول admin_data ---
    $stmt_update = $conn->prepare("
        UPDATE admin_data SET 
            leave_casual=?, leave_sick=?, leave_unexcused=?,
            classes_reserve=?, classes_extra=?, classes_overload=?,
            teacher_status=?, note_verbal=?, note_written=?, note_warning=?,
            note_discount=?, 
            assets_pen=?, assets_pc=?, assets_phone=?, assets_car=?, image=?
        WHERE employee_id=?
    ");
    $stmt_update->bind_param(
        "iiiiiissssssissss",
        $leave_casual, $leave_sick, $leave_unexcused,
        $classes_reserve, $classes_extra, $classes_overload,
        $teacher_status, $note_verbal, $note_written, $note_warning,
        $note_discount,
        $assets_pen, $assets_pc, $assets_phone, $assets_car, $image_name,
        $employee_id
    );
    $stmt_update->execute();
    $stmt_update->close();

    // ===== حفظ التقديرات السنوية =====
    foreach($years as $y){
        $rating_val = $_POST["rating_$y"] ?? '';
        $manual_note = $_POST["note_nails_$y"] ?? '';

        $stmt_note = $conn->prepare("
            INSERT INTO annual_notes (employee_id, year, rating, manual_note)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating=?, manual_note=?
        ");
        $stmt_note->bind_param("sissss", $employee_id, $y, $rating_val, $manual_note, $rating_val, $manual_note);
        $stmt_note->execute();
        $stmt_note->close();
    }

    header("Location: admin_data.php?id=$employee_id");
    exit;
}
?>

<!-- ================== HTML ================== -->
<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>البيانات الإدارية</title>
<style>
body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f2f5; direction: rtl; }
h2,h3 { font-family: 'Cairo', sans-serif; }
h2 { color:#800000; text-align:center; margin-bottom:25px; }
h3 { color:#004080; border-bottom:2px solid #800000; padding-bottom:5px; margin-top:25px; }
form { max-width:1000px; margin:0 auto; background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 15px rgba(0,0,0,0.1); }
input[type="text"], input[type="number"], select { width:100%; padding:10px; border:1px solid #ccc; border-radius:6px; transition: all 0.3s ease; }
input[type="text"]:focus, input[type="number"]:focus, select:focus { border-color:#800000; box-shadow:0 0 8px rgba(128,0,0,0.3); outline:none; }
button { transition: all 0.3s ease; padding:10px 20px; background:#004080; color:#fff; border:none; border-radius:6px; cursor:pointer; }
button:hover { background:#0060c0; }
section.grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:15px; }
label { font-weight:bold; margin-bottom:5px; display:block; }
.tab { display:none; }
.tab.active { display:block; }
.tab-buttons { text-align:center; margin-bottom:20px; }
.tab-buttons button { padding:8px 15px; border:none; border-radius:6px; background: rgba(219, 147, 147, 0.9); margin:3px; cursor:pointer; transition:0.3s; font-weight:bold;}
.tab-buttons button.active { background:#800000; color:#fff; transform:scale(1.05);}
</style>
</head>
<body>

<div style="text-align:right; max-width:1000px; margin:20px auto;">
    <a href="indexteacher.php" style="text-decoration:none; background:#004080; color:#fff; padding:10px 20px; border-radius:6px;">العودة للرئيسية</a>
</div>

<form method="POST" enctype="multipart/form-data">
<h2>البيانات الإدارية للموظف: <?php echo htmlspecialchars($employee_name); ?></h2>

<div style="margin-bottom:30px; text-align:center;">
    <img src="uploads/<?php echo htmlspecialchars($admin_data['image'] ?? 'avatar.png'); ?>" 
         style="width:140px; height:140px; border-radius:50%; object-fit:cover; border:3px solid #800000;"><br>
    <input type="file" name="image" accept="image/*" style="margin-top:10px;">
</div>

<h3>إجازات</h3>
<section class="grid">
    <div><label>إجازات عارضة:</label><input type="number" name="leave_casual" value="<?php echo htmlspecialchars($admin_data['leave_casual']); ?>"></div>
    <div><label>إجازات مرضية:</label><input type="number" name="leave_sick" value="<?php echo htmlspecialchars($admin_data['leave_sick']); ?>"></div>
    <div><label>إجازات بدون عذر:</label><input type="number" name="leave_unexcused" value="<?php echo htmlspecialchars($admin_data['leave_unexcused']); ?>"></div>
</section>

<h3>حصص</h3>
<section class="grid">
    <div><label>حصص احتياطي:</label><input type="number" name="classes_reserve" value="<?php echo htmlspecialchars($admin_data['classes_reserve']); ?>"></div>
    <div><label>حصص زائدة:</label><input type="number" name="classes_extra" value="<?php echo htmlspecialchars($admin_data['classes_extra']); ?>"></div>
    <div><label>فوق النصاب:</label><input type="number" name="classes_overload" value="<?php echo htmlspecialchars($admin_data['classes_overload']); ?>"></div>
</section>

<h3>حالة المدرس</h3>
<div style="margin-bottom:20px;">
    <label>حالة المدرس:</label>
    <select name="teacher_status">
        <option value="">اختر الحالة</option>
        <option value="نشط" <?php if($admin_data['teacher_status']=='نشط') echo 'selected'; ?>>نشط</option>
        <option value="غير نشط" <?php if($admin_data['teacher_status']=='غير نشط') echo 'selected'; ?>>غير نشط</option>
        <option value="إجازة" <?php if($admin_data['teacher_status']=='إجازة') echo 'selected'; ?>>إجازة</option>
        <option value="متقاعد" <?php if($admin_data['teacher_status']=='متقاعد') echo 'selected'; ?>>متقاعد</option>
    </select>
</div>

<h3>ملاحظات إدارية</h3>
<section class="grid">
    <div><label>تنبيه شفوي:</label><input type="text" name="note_verbal" value="<?php echo htmlspecialchars($admin_data['note_verbal']); ?>"></div>
    <div><label>تنبيه كتابي:</label><input type="text" name="note_written" value="<?php echo htmlspecialchars($admin_data['note_written']); ?>"></div>
    <div><label>إنذار:</label><input type="text" name="note_warning" value="<?php echo htmlspecialchars($admin_data['note_warning']); ?>"></div>
    <div><label>خصم (أيام):</label><input type="number" name="note_discount" value="<?php echo htmlspecialchars($admin_data['note_discount']); ?>"></div>
</section>

<h3>عهدات</h3>
<section class="grid">
    <div><label>قلم:</label><input type="text" name="assets_pen" value="<?php echo htmlspecialchars($admin_data['assets_pen']); ?>"></div>
    <div><label>حاسوب:</label><input type="text" name="assets_pc" value="<?php echo htmlspecialchars($admin_data['assets_pc']); ?>"></div>
    <div><label>هاتف:</label><input type="text" name="assets_phone" value="<?php echo htmlspecialchars($admin_data['assets_phone']); ?>"></div>
    <div><label>سيارة:</label><input type="text" name="assets_car" value="<?php echo htmlspecialchars($admin_data['assets_car']); ?>"></div>
</section>

<h3>التقدير السنوي</h3>
<div class="tab-buttons">
<?php foreach($years as $index => $y): ?>
    <button type="button" onclick="showTab(<?php echo $index; ?>)" <?php echo $index===0?'class="active"':''; ?>><?php echo $y; ?></button>
<?php endforeach; ?>
</div>

<?php foreach($years as $index => $y): ?>
<div class="tab <?php echo $index===0?'active':''; ?>">
    <label>تقدير السنة <?php echo $y; ?>:</label>
    <select name="rating_<?php echo $y; ?>">
        <option value="">اختر التقدير</option>
        <option value="ممتاز" <?php if(($annual_notes[$y]['rating']??'')=='ممتاز') echo 'selected'; ?>>ممتاز</option>
        <option value="جيد جدًا" <?php if(($annual_notes[$y]['rating']??'')=='جيد جدًا') echo 'selected'; ?>>جيد جدًا</option>
        <option value="جيد" <?php if(($annual_notes[$y]['rating']??'')=='جيد') echo 'selected'; ?>>جيد</option>
    </select>

    <div style="margin-top:8px;">
        <label>ملاحظات يدوية:</label>
        <input type="text" name="note_nails_<?php echo $y; ?>" value="<?php echo htmlspecialchars($annual_notes[$y]['manual_note'] ?? ''); ?>">
    </div>
</div>
<?php endforeach; ?>

<div style="text-align:center; margin-top:30px;">
    <button type="submit">حفظ البيانات الإدارية</button>
</div>
</form>

<script>
function showTab(tabIndex){
    var tabs=document.querySelectorAll('.tab');
    var btns=document.querySelectorAll('.tab-buttons button');
    tabs.forEach((tab)=>tab.classList.remove('active'));
    btns.forEach((btn)=>btn.classList.remove('active'));
    tabs[tabIndex].classList.add('active');
    btns[tabIndex].classList.add('active');
}
</script>

</body>
</html>