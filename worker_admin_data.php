<?php
session_start();
include("db.php");

if (!isset($_SESSION['user'])) exit("⛔ لا يمكن الوصول إلى هذه الصفحة");
if (!($_SESSION['can_edit'] ?? 0)) exit("⛔ لا تملك صلاحية تعديل البيانات الإدارية");

$employee_id = $_GET['id'] ?? '';
if (!$employee_id) exit("رقم العامل مفقود");

// جلب بيانات العامل
$stmt = $conn->prepare("SELECT * FROM workers_data WHERE employee_id=?");
$stmt->bind_param("s", $employee_id);
$stmt->execute();
$worker = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$worker) exit("العامل غير موجود");

// جلب بيانات worker_admin_data
$stmt_admin_data = $conn->prepare("SELECT * FROM worker_admin_data WHERE employee_id=?");
$stmt_admin_data->bind_param("s",$employee_id);
$stmt_admin_data->execute();
$admin_data = $stmt_admin_data->get_result()->fetch_assoc();
$stmt_admin_data->close();

// جلب بيانات worker_admin
$stmt_admin = $conn->prepare("SELECT * FROM worker_admin WHERE employee_id=?");
$stmt_admin->bind_param("s",$employee_id);
$stmt_admin->execute();
$admin = $stmt_admin->get_result()->fetch_assoc();
$stmt_admin->close();

// تحميل البيانات السابقة
$cleaning_saved = json_decode($admin_data['cleaning_tasks'] ?? '[]', true) ?: [];
$buses_saved = json_decode($admin_data['buses'] ?? '[]', true) ?: [];
$places_saved = json_decode($admin_data['cleaning_places'] ?? '[]', true) ?: [];
$presence_saved = json_decode($admin_data['presence_places'] ?? '[]', true) ?: [];
$assets_saved = json_decode($admin_data['assets'] ?? '[]', true) ?: [];
$ratings_saved = json_decode($admin_data['annual_ratings'] ?? '[]', true) ?: [];

$success = $error = '';

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $conn->begin_transaction();

    try{
        // حفظ الوظيفة
        $job_role = trim($_POST['job_role'] ?? '');
        if($job_role==='') throw new Exception("يرجى اختيار الوظيفة");
        $stmt_upd = $conn->prepare("UPDATE workers_data SET role=? WHERE employee_id=?");
        $stmt_upd->bind_param("ss",$job_role,$employee_id);
        $stmt_upd->execute();
        $stmt_upd->close();

        // حفظ الباص (واحد فقط)
        $new_bus = $_POST['bus'] ?? '';
        $buses_json = json_encode($new_bus ? [$new_bus] : [], JSON_UNESCAPED_UNICODE);

        // الملاحظات الإدارية
        $notes = $_POST['admin_notes'] ?? [];
        $note_verbal = in_array("تنبيه_شفوي",$notes)?1:0;
        $note_written = in_array("تنبيه_كتابي",$notes)?1:0;
        $note_warning = in_array("انذار",$notes)?1:0;
        $note_discount = in_array("خصم_ايام",$notes)?intval($_POST['deduct_days_count']??0):0;

        if($admin){
            $upd_admin = $conn->prepare("UPDATE worker_admin SET note_verbal=?, note_written=?, note_warning=?, note_discount=? WHERE employee_id=?");
            $upd_admin->bind_param("iiiss",$note_verbal,$note_written,$note_warning,$note_discount,$employee_id);
            $upd_admin->execute();
            $upd_admin->close();
        } else {
            $ins_admin = $conn->prepare("INSERT INTO worker_admin (employee_id,note_verbal,note_written,note_warning,note_discount) VALUES (?,?,?,?,?)");
            $ins_admin->bind_param("siiii",$employee_id,$note_verbal,$note_written,$note_warning,$note_discount);
            $ins_admin->execute();
            $ins_admin->close();
        }

        // المهام اليومية
        $cleaning_data = [];
        foreach([10,11,12] as $c){
            $cleaning_data[$c] = $_POST["cleaning_classes_$c"] ?? [];
        }
        $cleaning_json = json_encode($cleaning_data, JSON_UNESCAPED_UNICODE);

        // أماكن النظافة مع الدمج
        $posted_places = $_POST['cleaning_places'] ?? [];
        $final_places = array_values(array_unique(array_merge($places_saved,$posted_places)));
        $cleaning_places_json = json_encode($final_places, JSON_UNESCAPED_UNICODE);

        // أماكن التواجد مع الدمج
        $posted_presence = $_POST['presence_places'] ?? [];
        $final_presence = array_values(array_unique(array_merge($presence_saved,$posted_presence)));
        $presence_places_json = json_encode($final_presence, JSON_UNESCAPED_UNICODE);

        // العهدات مع الدمج
        $posted_assets = $_POST['assets'] ?? [];
        $final_assets = array_values(array_unique(array_merge($assets_saved,$posted_assets)));
        $assets_json = json_encode($final_assets, JSON_UNESCAPED_UNICODE);

        // الإجازات وحالة العامل
        $leave_type = $_POST['leave_type'] ?? '';
        $supervisor_status = $_POST['supervisor_status'] ?? '';

        // إدخال أو تحديث worker_admin_data
        if($admin_data){
            $upd_admin_data = $conn->prepare("UPDATE worker_admin_data SET buses=?, cleaning_tasks=?, cleaning_places=?, presence_places=?, assets=?, leave_type=?, supervisor_status=? WHERE employee_id=?");
            $upd_admin_data->bind_param("ssssssss",$buses_json,$cleaning_json,$cleaning_places_json,$presence_places_json,$assets_json,$leave_type,$supervisor_status,$employee_id);
            $upd_admin_data->execute();
            $upd_admin_data->close();
        } else {
            $ins_admin_data = $conn->prepare("INSERT INTO worker_admin_data (employee_id,buses,cleaning_tasks,cleaning_places,presence_places,assets,leave_type,supervisor_status) VALUES (?,?,?,?,?,?,?,?)");
            $ins_admin_data->bind_param("ssssssss",$employee_id,$buses_json,$cleaning_json,$cleaning_places_json,$presence_places_json,$assets_json,$leave_type,$supervisor_status);
            $ins_admin_data->execute();
            $ins_admin_data->close();
        }

        // التقديرات السنوية (5 سنوات)
        $ratings = $_POST['rating'] ?? [];
        $rating_notes = $_POST['rating_note'] ?? [];
        $ratings_data = [];
        $current_year = date("Y");
        for($y=$current_year-4;$y<=$current_year;$y++){
            $note = $rating_notes[$y] ?? '';
            $ratings_data[$y] = ['rating'=>$ratings[$y]??'','note'=>$note];
        }
        $ratings_json = json_encode($ratings_data, JSON_UNESCAPED_UNICODE);
        $upd_ratings = $conn->prepare("UPDATE worker_admin_data SET annual_ratings=? WHERE employee_id=?");
        $upd_ratings->bind_param("ss",$ratings_json,$employee_id);
        $upd_ratings->execute();
        $upd_ratings->close();

        $conn->commit();
        header("Location: ".$_SERVER['REQUEST_URI']);
        exit;

    } catch(Exception $e){
        $conn->rollback();
        $error = "❌ حدث خطأ أثناء الحفظ: ".$e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>البيانات الإدارية للعامل</title>
<style>
body{font-family:'Cairo',Tahoma,Arial,sans-serif;background:#f0f2f5;margin:0;padding:0}
.container{max-width:950px;margin:30px auto;background:#fff;padding:25px;border-radius:12px;box-shadow:0 4px 15px rgba(0,0,0,0.1);}
h2{text-align:center;color:#800000;margin-bottom:30px;}
.success,.error{padding:10px;border-radius:6px;margin-bottom:15px;font-weight:bold;}
.success{background:#e6ffea;color:#006600;}
.error{background:#ffe6e6;color:#990000;}
.back{display:inline-block;margin-bottom:20px;text-decoration:none;background:#800000;color:#fff;padding:8px 15px;border-radius:6px;}
.section{background:#fafafa;border:1px solid #ddd;border-radius:10px;padding:20px;margin-bottom:20px;}
.section h3, .section-title{margin-top:0;color:#004080;border-bottom:2px solid #800000;padding-bottom:5px;}
label{display:block;font-weight:bold;margin-bottom:8px;}
select,input{width:100%;padding:8px;margin-top:5px;border-radius:5px;border:1px solid #ccc;}
select:focus,input:focus{border-color:#800000;outline:none;}
button{background:#004080;color:#fff;border:none;padding:12px 25px;border-radius:8px;cursor:pointer;font-size:16px;}
button:hover{background:#0060c0;}
.fields-container{display:flex;gap:15px;flex-wrap:wrap;}
.field{background:#e6f0ff;padding:10px;border-radius:8px;flex:1;min-width:180px;}
.toggle-btn{background:#004080;color:#fff;border:none;padding:8px 12px;border-radius:6px;cursor:pointer;font-weight:bold;width:100%;margin-bottom:5px;transition:0.3s;}
.toggle-btn:hover{background:#0060c0;}
.hidden-field{display:none;margin-top:10px;}
.multiselect{height:130px;width:100%;padding:6px;border-radius:5px;border:1px solid #ccc;}
.year-box{border:1px solid #ccc;border-radius:6px;margin-bottom:12px;}
.year-title{padding:10px;background:#f39c12;color:#fff;cursor:pointer;font-weight:bold;}
.year-content{display:none;padding:12px;}
.year-content label{display:block;margin-top:10px;font-weight:bold;}
.admin-notes-container {display: flex; flex-wrap: wrap; gap: 15px; padding: 10px 0; justify-content: flex-start; direction: rtl;}
.note-option {display: flex; align-items: center; flex: 1 1 180px; text-align: right;}
.note-option:hover {background: #d0ebff;}
.note-option input {margin-left:8px; margin-right:0; transform: scale(1.2); cursor:pointer;}
</style>
</head>
<body>
<div class="container">
<a class="back" href="index_workers.php">⬅ العودة</a>
<div style="text-align:center; margin-bottom:20px;">
    <img src="uploads/workers/<?php echo htmlspecialchars($worker['photo'] ?? 'avatar.png'); ?>" 
         alt="صورة العامل" 
         style="width:120px; height:120px; border-radius:50%; object-fit:cover; border:3px solid #004080;">
</div>
<h2>البيانات الإدارية للعامل: <?php echo htmlspecialchars($worker['full_name']); ?></h2>

<?php if($success): ?><div class="success"><?php echo $success; ?></div><?php endif; ?>
<?php if($error): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>

<form method="POST">

<!-- الوظيفة -->
<div class="section">
<h3>الوظيفة</h3>
<label for="job_role">الوظيفة المعتمدة</label>
<select name="job_role" id="job_role" required>
    <option value="">-- اختر الوظيفة --</option>
    <option value="عامل" <?php if($worker['role']=='عامل') echo 'selected'; ?>>عامل</option>
    <option value="سائق" <?php if($worker['role']=='سائق') echo 'selected'; ?>>سائق</option>
    <option value="عامل وسائق" <?php if($worker['role']=='عامل وسائق') echo 'selected'; ?>>عامل و سائق</option>
    <option value="حارس" <?php if($worker['role']=='حارس') echo 'selected'; ?>>حارس</option>
</select>
</div>

<!-- الباصات -->
<div class="section">
<h3>الباصات</h3>
<label>تعيين باص</label>
<select name="bus">
    <option value="">اختر الباص</option>
    <?php
    for($i=1; $i<=10; $i++){
        $bus_name = "باص $i";
        $selected = in_array($bus_name, $buses_saved) ? 'selected' : '';
        echo "<option value='".htmlspecialchars($bus_name)."' $selected>$bus_name</option>";
    }
    ?>
</select>
</div>

<!-- المهام اليومية - النظافة -->
<div class="section">
<h3>المهام اليومية</h3>
<div class="task-group">
<h4>النظافة</h4>
<div class="fields-container">
<?php 
$classes = [10,11,12];
foreach($classes as $c):
$saved = $cleaning_saved[$c] ?? [];
?>
<div class="field">
<button type="button" class="toggle-btn" onclick="toggleField('classes<?= $c ?>')">صفوف <?= $c ?> ▼</button>
<div id="classes<?= $c ?>" class="hidden-field">
<select name="cleaning_classes_<?= $c ?>[]" multiple class="multiselect">
<?php for($i=1;$i<=7;$i++): ?>
<option value="<?= $i ?>" <?= in_array($i,$saved)?'selected':'' ?>><?= $i ?></option>
<?php endfor; ?>
</select>
</div>
</div>
<?php endforeach; ?>
</div>
</div>

<!-- أماكن النظافة -->
<div class="section">
<h3>أماكن النظافة</h3>
<select id="cleaning_select" name="cleaning_places[]" multiple class="multiselect">
<?php foreach($places_saved as $place): ?>
<option value="<?= htmlspecialchars($place) ?>" selected><?= htmlspecialchars($place) ?></option>
<?php endforeach; ?>
</select>
<br><br>
<button type="button" onclick="addNewPlace()" style="background:#008000;">+ إضافة مكان جديد</button>
<button type="button" onclick="removePlace()" style="background:#aa0000;">− حذف مكان</button>
</div>

<!-- أماكن التواجد -->
<div class="section">
<h3>مكان التواجد</h3>
<select id="presence_select" name="presence_places[]" multiple class="multiselect">
<?php foreach($presence_saved as $place): ?>
<option value="<?= htmlspecialchars($place) ?>" selected><?= htmlspecialchars($place) ?></option>
<?php endforeach; ?>
</select>
<br><br>
<button type="button" onclick="addNewPresence()" style="background:#008000;">+ إضافة مكان جديد</button>
<button type="button" onclick="removePresence()" style="background:#aa0000;">− حذف مكان</button>
</div>

<!-- العهدات -->
<div class="section">
<h3>العهدات</h3>
<select id="assets_select" name="assets[]" multiple class="multiselect">
<?php foreach($assets_saved as $asset): ?>
<option value="<?= htmlspecialchars($asset) ?>" selected><?= htmlspecialchars($asset) ?></option>
<?php endforeach; ?>
</select>
<br><br>
<button type="button" onclick="addNewAsset()" style="background:#008000;">+ إضافة عهدة</button>
<button type="button" onclick="removeAsset()" style="background:#aa0000;">− حذف عهدة</button>
</div>

<!-- الإجازات -->
<div class="section">
<h3>الإجازات</h3>
<select name="leave_type">
<option value="">— اختر نوع الإجازة —</option>
<option value="عارضة" <?php if(($admin_data['leave_type']??'')=='عارضة') echo 'selected'; ?>>إجازة عارضة</option>
<option value="مرضية" <?php if(($admin_data['leave_type']??'')=='مرضية') echo 'selected'; ?>>إجازة مرضية</option>
<option value="بدون_عذر" <?php if(($admin_data['leave_type']??'')=='بدون_عذر') echo 'selected'; ?>>إجازة بدون عذر</option>
</select>
</div>

<!-- حالة العامل -->
<div class="section">
<h3>حالة العامل</h3>
<select name="supervisor_status">
<option value="">— اختر الحالة —</option>
<option value="نشط" <?php if(($admin_data['supervisor_status']??'')=='نشط') echo 'selected'; ?>>نشط</option>
<option value="غير_نشط" <?php if(($admin_data['supervisor_status']??'')=='غير_نشط') echo 'selected'; ?>>غير نشط</option>
<option value="اجازة" <?php if(($admin_data['supervisor_status']??'')=='اجازة') echo 'selected'; ?>>إجازة</option>
<option value="متقاعد" <?php if(($admin_data['supervisor_status']??'')=='متقاعد') echo 'selected'; ?>>متقاعد</option>
</select>
</div>

<!-- الملاحظات الإدارية -->
<div class="section">
<h3 class="section-title">الملاحظات الإدارية</h3>
<div class="admin-notes-container">
<label class="note-option">
<input type="checkbox" name="admin_notes[]" value="تنبيه_شفوي" <?php if($admin['note_verbal']??0) echo 'checked'; ?>>
<span>تنبيه شفوي</span>
</label>
<label class="note-option">
<input type="checkbox" name="admin_notes[]" value="تنبيه_كتابي" <?php if($admin['note_written']??0) echo 'checked'; ?>>
<span>تنبيه كتابي</span>
</label>
<label class="note-option">
<input type="checkbox" name="admin_notes[]" value="انذار" <?php if($admin['note_warning']??0) echo 'checked'; ?>>
<span>إنذار</span>
</label>
<label class="note-option">
<input type="checkbox" name="admin_notes[]" value="خصم_ايام" <?php if(($admin['note_discount']??0)>0) echo 'checked'; ?>>
<span>خصم أيام
<input type="number" name="deduct_days_count" value="<?= $admin['note_discount']??0 ?>" style="width:50px;">
</span>
</label>
</div>
</div>

<!-- التقديرات السنوية -->
<div class="section">
<h3>التقديرات السنوية</h3>
<?php
$current_year = date("Y");
for($y=$current_year-4;$y<=$current_year;$y++):
$rating = $ratings_saved[$y]['rating'] ?? '';
$note = $ratings_saved[$y]['note'] ?? '';
?>
<div class="year-box">
<div class="year-title" onclick="toggleYearContent(this)"><?= $y ?></div>
<div class="year-content">
<label>التقدير</label>
<select name="rating[<?= $y ?>]">
<option value="">— اختر —</option>
<option value="ممتاز" <?php if($rating=='ممتاز') echo 'selected'; ?>>ممتاز</option>
<option value="جيد جدا" <?php if($rating=='جيد جدا') echo 'selected'; ?>>جيد جداً</option>
<option value="جيد" <?php if($rating=='جيد') echo 'selected'; ?>>جيد</option>
<option value="مقبول" <?php if($rating=='مقبول') echo 'selected'; ?>>مقبول</option>
<option value="ضعيف" <?php if($rating=='ضعيف') echo 'selected'; ?>>ضعيف</option>
</select>
<label>ملاحظة</label>
<input type="text" name="rating_note[<?= $y ?>]" value="<?= htmlspecialchars($note) ?>">
</div>
</div>
<?php endfor; ?>
</div>

<button type="submit">💾 حفظ البيانات</button>
</form>
</div>

<script>
function toggleField(id){
    const el = document.getElementById(id);
    if(el.style.display=='none' || el.style.display=='') el.style.display='block';
    else el.style.display='none';
}
function addNewPlace(){
    const val = prompt('أدخل مكان النظافة الجديد:');
    if(val){
        const sel = document.getElementById('cleaning_select');
        const opt = document.createElement('option');
        opt.value = val;
        opt.text = val;
        opt.selected = true;
        sel.appendChild(opt);
    }
}
function removePlace(){
    const sel = document.getElementById('cleaning_select');
    for(let i=sel.options.length-1;i>=0;i--){
        if(sel.options[i].selected) sel.remove(i);
    }
}
function addNewPresence(){
    const val = prompt('أدخل مكان التواجد الجديد:');
    if(val){
        const sel = document.getElementById('presence_select');
        const opt = document.createElement('option');
        opt.value = val;
        opt.text = val;
        opt.selected = true;
        sel.appendChild(opt);
    }
}
function removePresence(){
    const sel = document.getElementById('presence_select');
    for(let i=sel.options.length-1;i>=0;i--){
        if(sel.options[i].selected) sel.remove(i);
    }
}
function addNewAsset(){
    const val = prompt('أدخل العهدة الجديدة:');
    if(val){
        const sel = document.getElementById('assets_select');
        const opt = document.createElement('option');
        opt.value = val;
        opt.text = val;
        opt.selected = true;
        sel.appendChild(opt);
    }
}
function removeAsset(){
    const sel = document.getElementById('assets_select');
    for(let i=sel.options.length-1;i>=0;i--){
        if(sel.options[i].selected) sel.remove(i);
    }
}
function toggleYearContent(el){
    const content = el.nextElementSibling;
    content.style.display = (content.style.display=='block')?'none':'block';
}
</script>
</body>
</html>