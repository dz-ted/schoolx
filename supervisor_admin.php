<?php
session_start();
include("db.php"); // يجب أن يُعطي $conn (mysqli)

require 'vendor/autoload.php';
$success = $error = '';

// ===== حماية الجلسة =====
if(!isset($_SESSION['user'])){
    exit("⛔ لا يمكن الوصول إلى هذه الصفحة");
}

// ===== صلاحية التعديل =====
if(!($_SESSION['can_edit'] ?? 0)){
    exit("⛔ لا تملك صلاحية تعديل البيانات الإدارية");
}

// ===== المرحلة =====
$current_stage = $_SESSION['stage'] ?? '';
if(!$current_stage){
    exit("⛔ المرحلة غير معرّفة");
}

// ===== employee_id =====
$employee_id = $_GET['id'] ?? '';
if(!$employee_id) exit("رقم الموظف مفقود!");

// ===== جلب بيانات المشرف الأساسية (مع التحقق من المرحلة) =====
$stmt_sd = $conn->prepare("
    SELECT * FROM supervisors_data 
    WHERE employee_id = ? AND stage = ?
");
$stmt_sd->bind_param("ss", $employee_id, $current_stage);
$stmt_sd->execute();
$res_sd = $stmt_sd->get_result();
$supervisor = $res_sd->fetch_assoc();
$stmt_sd->close();

if(!$supervisor){
    exit("⛔ هذا المشرف غير تابع لمرحلتك");
}

// اسم العرض
$employee_name = $supervisor['full_name'] ?? $employee_id;

// ===== supervisor_admin =====
$stmt = $conn->prepare("SELECT * FROM supervisor_admin WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
$admin = $res->fetch_assoc();
$stmt->close();

if(!$admin){
    $stmt_i = $conn->prepare("INSERT INTO supervisor_admin (employee_id, image) VALUES (?, 'avatar.png')");
    $stmt_i->bind_param("s", $employee_id);
    $stmt_i->execute();
    $stmt_i->close();

    $stmt = $conn->prepare("SELECT * FROM supervisor_admin WHERE employee_id = ?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $admin = $res->fetch_assoc();
    $stmt->close();
}

// ===== الصفوف =====
$stmt = $conn->prepare("SELECT class_level, class_number FROM supervisor_classes WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
$current_classes = [];
while($r = $res->fetch_assoc()){
    $current_classes[(int)$r['class_level']][] = (int)$r['class_number'];
}
$stmt->close();

// ===== مناوبات =====
$stmt = $conn->prepare("SELECT duty FROM supervisor_shifts_morning WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
$morning_shifts = [];
while($r = $res->fetch_assoc()) $morning_shifts[] = $r['duty'];
$stmt->close();

$stmt = $conn->prepare("SELECT duty FROM supervisor_shifts_break WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
$break_shifts = [];
while($r = $res->fetch_assoc()) $break_shifts[] = $r['duty'];
$stmt->close();

// ===== السنوات =====
$current_year = date("Y");
$years = [];
for($i=0;$i<5;$i++) $years[] = $current_year-$i;

// ===== التقديرات =====
$annual_notes = [];
$stmt = $conn->prepare("SELECT year, rating, manual_note FROM annual_notes WHERE employee_id = ?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
while($row = $res->fetch_assoc()){
    $annual_notes[(int)$row['year']] = [
        'rating'=>$row['rating'],
        'manual_note'=>$row['manual_note']
    ];
}
$stmt->close();

// ===== حفظ POST =====
if($_SERVER['REQUEST_METHOD']==='POST'){

    $image_name = $admin['image'] ?? 'avatar.png';

    $leave_casual    = (int)($_POST['leave_casual'] ?? 0);
    $leave_sick      = (int)($_POST['leave_sick'] ?? 0);
    $leave_unexcused = (int)($_POST['leave_unexcused'] ?? 0);
    $supervisor_status = trim($_POST['teacher_status'] ?? '');
    $note_verbal   = trim($_POST['note_verbal'] ?? '');
    $note_written  = trim($_POST['note_written'] ?? '');
    $note_warning  = trim($_POST['note_warning'] ?? '');
    $note_discount = (int)($_POST['note_discount'] ?? 0);
    $assets_pen   = trim($_POST['assets_pen'] ?? '');
    $assets_pc    = trim($_POST['assets_pc'] ?? '');
    $assets_phone = trim($_POST['assets_phone'] ?? '');
    $assets_car   = trim($_POST['assets_car'] ?? '');

    $stmt_upd = $conn->prepare("
        UPDATE supervisor_admin SET
        leave_casual=?, leave_sick=?, leave_unexcused=?,
        supervisor_status=?, note_verbal=?, note_written=?, note_warning=?, note_discount=?,
        assets_pen=?, assets_pc=?, assets_phone=?, assets_car=?, image=?
        WHERE employee_id=?
    ");
    $stmt_upd->bind_param(
        "iiississssssss",
        $leave_casual, $leave_sick, $leave_unexcused,
        $supervisor_status, $note_verbal, $note_written, $note_warning, $note_discount,
        $assets_pen, $assets_pc, $assets_phone, $assets_car, $image_name,
        $employee_id
    );
    $stmt_upd->execute();
    $stmt_upd->close();

    echo "<script>
        alert('✅ تم حفظ البيانات الإدارية بنجاح');
        window.location.href='supervisor_admin.php?id=".urlencode($employee_id)."';
    </script>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>البيانات الإدارية للمشرف</title>
</head>
<body>
<!-- الواجهة كما هي تمامًا بدون أي تغيير -->
</body>
</html>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>البيانات الإدارية للمشرف</title>
<style>
/* احتفظت بنفس ستايلك تقريباً مع ألوان خفيفة */
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
.multiselect { height:130px; } /* حجم صناديق الاختيار المتعدد */
.note { color: #007700; font-weight:bold; margin-bottom:10px; }
.error { color: #aa0000; font-weight:bold; margin-bottom:10px; }
.success { color: #007700; font-weight:bold; margin-bottom:10px; }
</style>
</head>
<body>
<div style="text-align:right; max-width:1000px; margin:20px auto;">
    <a href="index_supervisors.php" style="text-decoration:none; background:#004080; color:#fff; padding:10px 20px; border-radius:6px;">العودة للرئيسية</a>
</div>

<form method="POST" enctype="multipart/form-data">
    <h2>البيانات الإدارية للمشرف: <?php echo htmlspecialchars($employee_name); ?></h2>

    <?php if($error) echo "<div class='error'>".$error."</div>"; ?>
    <?php if($success) echo "<div class='success'>".$success."</div>"; ?>

    <div style="margin-bottom:30px; text-align:center;">
        <?php
           $displayImage = !empty($supervisor['photo']) ? $supervisor['photo'] : 'avatar.png';
        ?>
        <img src="uploads/supervisors/<?php echo htmlspecialchars($displayImage); ?>" 
             style="width:140px; height:140px; border-radius:50%; object-fit:cover; border:3px solid #800000;"><br>
         
    </div>

    <h3>إجازات</h3>
    <section class="grid">
        <div><label>إجازات عارضة:</label><input type="number" name="leave_casual" value="<?php echo htmlspecialchars($admin['leave_casual'] ?? 0); ?>"></div>
        <div><label>إجازات مرضية:</label><input type="number" name="leave_sick" value="<?php echo htmlspecialchars($admin['leave_sick'] ?? 0); ?>"></div>
        <div><label>إجازات بدون عذر:</label><input type="number" name="leave_unexcused" value="<?php echo htmlspecialchars($admin['leave_unexcused'] ?? 0); ?>"></div>
    </section>

    <!-- تم حذف تبويب الحصص كما طلبت -->

    <h3>حالة المشرف</h3>
    <div style="margin-bottom:20px;">
        <label>حالة المشرف:</label>
        <select name="teacher_status">
            <option value="">اختر الحالة</option>
            <option value="نشط" <?php if(($admin['supervisor_status']??'')=='نشط') echo 'selected'; ?>>نشط</option>
            <option value="غير نشط" <?php if(($admin['supervisor_status']??'')=='غير نشط') echo 'selected'; ?>>غير نشط</option>
            <option value="إجازة" <?php if(($admin['supervisor_status']??'')=='إجازة') echo 'selected'; ?>>إجازة</option>
            <option value="متقاعد" <?php if(($admin['supervisor_status']??'')=='متقاعد') echo 'selected'; ?>>متقاعد</option>
        </select>
    </div>

    <h3>ملاحظات إدارية</h3>
    <section class="grid">
        <div><label>تنبيه شفوي:</label><input type="text" name="note_verbal" value="<?php echo htmlspecialchars($admin['note_verbal'] ?? ''); ?>"></div>
        <div><label>تنبيه كتابي:</label><input type="text" name="note_written" value="<?php echo htmlspecialchars($admin['note_written'] ?? ''); ?>"></div>
        <div><label>إنذار:</label><input type="text" name="note_warning" value="<?php echo htmlspecialchars($admin['note_warning'] ?? ''); ?>"></div>
        <div><label>الخصم:</label><input type="number" name="note_discount" value="<?php echo htmlspecialchars($admin['note_discount'] ?? 0); ?>"></div>
    </section>

    <h3>عهدات</h3>
    <section class="grid">
        <div><label>قلم:</label><input type="text" name="assets_pen" value="<?php echo htmlspecialchars($admin['assets_pen'] ?? ''); ?>"></div>
        <div><label>حاسوب:</label><input type="text" name="assets_pc" value="<?php echo htmlspecialchars($admin['assets_pc'] ?? ''); ?>"></div>
        <div><label>هاتف:</label><input type="text" name="assets_phone" value="<?php echo htmlspecialchars($admin['assets_phone'] ?? ''); ?>"></div>
        <div><label>سيارة:</label><input type="text" name="assets_car" value="<?php echo htmlspecialchars($admin['assets_car'] ?? ''); ?>"></div>
    </section>

    <!-- تبويبات جديدة: الصفوف و المناوبات -->
    <h3>الصفوف التي يشرف عليها</h3>
    <p class="note">اختر الأرقام المناسبة لكل صف (يمكن اختيار أكثر من عنصر بالضغط مع Ctrl أو Shift)</p>
    <section class="grid">
        <div>
            <label>الصف 10</label>
            <select name="classes_10[]" multiple class="multiselect">
                <?php for($i=1;$i<=7;$i++): $sel = in_array($i, $current_classes[10] ?? []) ? 'selected' : ''; ?>
                    <option value="<?php echo $i; ?>" <?php echo $sel; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label>الصف 11</label>
            <select name="classes_11[]" multiple class="multiselect">
                <?php for($i=1;$i<=7;$i++): $sel = in_array($i, $current_classes[11] ?? []) ? 'selected' : ''; ?>
                    <option value="<?php echo $i; ?>" <?php echo $sel; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <div>
            <label>الصف 12</label>
            <select name="classes_12[]" multiple class="multiselect">
                <?php for($i=1;$i<=7;$i++): $sel = in_array($i, $current_classes[12] ?? []) ? 'selected' : ''; ?>
                    <option value="<?php echo $i; ?>" <?php echo $sel; ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </section>

    <h3>المناوبات</h3>
    <div class="note">اختر المناوبات من كل قسم (يمكن اختيار أكثر من عنصر)</div>
    <section class="grid">
        <div>
            <label>المناوبات الصباحية  </label>
            <select name="morning_shifts[]" multiple class="multiselect">
                <?php
                $morning_opts = ['تفتيش','دور أرضي','دور علوي','أمام المدرسة'];
                foreach($morning_opts as $opt){
                    $sel = in_array($opt, $morning_shifts) ? 'selected' : '';
                    echo "<option value=\"".htmlspecialchars($opt)."\" $sel>".htmlspecialchars($opt)."</option>";
                }
                ?>
            </select>
        </div>
        <div>
            <label>مناوبات الفرصة</label>
            <select name="break_shifts[]" multiple class="multiselect">
                <?php
                $break_opts = ['دور أرضي','دور علوي','مقصف','ملعب'];
                foreach($break_opts as $opt){
                    $sel = in_array($opt, $break_shifts) ? 'selected' : '';
                    echo "<option value=\"".htmlspecialchars($opt)."\" $sel>".htmlspecialchars($opt)."</option>";
                }
                ?>
            </select>
        </div>
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

<?php
if($success){
    echo "<script>
        alert('✅ تم إضافة البيانات الإدارية بنجاح للمشرف: " . addslashes($employee_name) . "');
    </script>";
}
?>

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