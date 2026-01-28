<?php
session_start();
include("db.php");

// ===== التحقق من تسجيل الدخول =====
if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

// ===== التحقق من صلاحية مدير المرحلة =====
if(!isset($_SESSION['is_stage_admin']) || $_SESSION['is_stage_admin'] != 1){
    // إعادة توجيه لمن ليس له صلاحية
    header("Location: index.php"); // أو رسالة: exit("ليس لديك صلاحية الوصول لهذه الصفحة");
    exit();
}

$stage = $_SESSION['stage'] ?? '';
$success = $error = '';

// ===== تعديل الصلاحيات من الـModal =====
if(isset($_POST['save_edit'])){
    $id = intval($_POST['id']);
    $can_add = isset($_POST['can_add']) ? 1 : 0;
    $can_edit = isset($_POST['can_edit']) ? 1 : 0;
    $can_delete = isset($_POST['can_delete']) ? 1 : 0;
    $can_admin_data = isset($_POST['can_admin_data']) ? 1 : 0;

    $stmt = $conn->prepare("
        UPDATE users SET can_add=?, can_edit=?, can_delete=?, can_admin_data=?
        WHERE id=? AND stage=?
    ");
    $stmt->bind_param("iiiiss",$can_add,$can_edit,$can_delete,$can_admin_data,$id,$stage);
    if($stmt->execute()) $success="✅ تم تعديل الصلاحيات بنجاح";
    else $error="❌ خطأ أثناء تعديل الصلاحيات: ".$stmt->error;
    $stmt->close();
}

// ===== إضافة أو تعديل مشرف كامل =====
if(isset($_POST['id_full'])){ // نميز فورم الإضافة/التعديل الكامل
    $id = $_POST['id_full'] ?? '';
    $username = $_POST['username_full'] ?? '';
    $password = $_POST['password_full'] ?? '';
    $position = $_POST['position_full'] ?? 'مشرف نظام';

    $can_add = isset($_POST['can_add_full']) ? 1 : 0;
    $can_edit = isset($_POST['can_edit_full']) ? 1 : 0;
    $can_delete = isset($_POST['can_delete_full']) ? 1 : 0;
    $can_admin_data = isset($_POST['can_admin_data_full']) ? 1 : 0;

    $role = match($stage){
        'ابتدائي' => 'primary',
        'اعدادي'  => 'middle',
        'ثانوي'   => 'secondary',
        default   => 'supervisor'
    };

    if($id){ // تعديل
        if($password){
            $hashed = password_hash($password,PASSWORD_DEFAULT);
            $stmt=$conn->prepare("
                UPDATE users SET username=?, password=?, position=?, can_add=?, can_edit=?, can_delete=?, can_admin_data=?
                WHERE id=? AND stage=?
            ");
            $stmt->bind_param("sssiiiiis",$username,$hashed,$position,$can_add,$can_edit,$can_delete,$can_admin_data,$id,$stage);
        } else {
            $stmt=$conn->prepare("
                UPDATE users SET username=?, position=?, can_add=?, can_edit=?, can_delete=?, can_admin_data=?
                WHERE id=? AND stage=?
            ");
            $stmt->bind_param("ssiiiis",$username,$position,$can_add,$can_edit,$can_delete,$can_admin_data,$id,$stage);
        }
        if($stmt->execute()) $success="✅ تم تعديل المشرف بنجاح";
        else $error="❌ خطأ أثناء التعديل: ".$stmt->error;
        $stmt->close();
    } else { // إضافة
        if(!$password) $error="❌ كلمة المرور مطلوبة";
        else{
            $hashed = password_hash($password,PASSWORD_DEFAULT);
            $stmt = $conn->prepare("
                INSERT INTO users (username,password,role,stage,position,can_add,can_edit,can_delete,can_admin_data)
                VALUES (?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param("sssssiiii",$username,$hashed,$role,$stage,$position,$can_add,$can_edit,$can_delete,$can_admin_data);
            if($stmt->execute()) $success="✅ تم إضافة المشرف بنجاح";
            else $error="❌ خطأ أثناء الإضافة: ".$stmt->error;
            $stmt->close();
        }
    }
}

// ===== حذف =====
if(isset($_GET['delete'])){
    $id=intval($_GET['delete']);
    $stmt=$conn->prepare("DELETE FROM users WHERE id=? AND stage=?");
    $stmt->bind_param("is",$id,$stage);
    $stmt->execute();
    $stmt->close();
    $success="✅ تم الحذف";
}

// ===== جلب المستخدمين =====
$stmt=$conn->prepare("SELECT * FROM users WHERE stage=?");
$stmt->bind_param("s",$stage);
$stmt->execute();
$users=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>إدارة مشرفي الموقع</title>
<style>
body{font-family:'Almarai',sans-serif;background:#f0f4f8;margin:0;}
.container{max-width:1200px;margin:40px auto;background:#fff;padding:30px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,.1);}
h1{text-align:center;color:#004080;}
table{width:100%;border-collapse:collapse;margin-top:30px;}
th,td{padding:12px;text-align:center;}
th{background:#004080;color:#fff;}
tr:nth-child(even){background:#f7f9fc;}
.badge{padding:4px 8px;border-radius:6px;font-size:13px;}
.ok{background:#e0f7e9;color:#008a3b;}
.no{background:#fdecea;color:#c62828;}
.btn{padding:6px 12px;border:none;border-radius:6px;cursor:pointer;color:#fff;}
.edit{background:#009688;}
.delete{background:#e53935;}
.back{display:inline-block;margin-bottom:15px;background:#004080;color:#fff;padding:10px 20px;border-radius:8px;text-decoration:none;}
form label{display:block;margin:10px 0;}
form input[type=text], form input[type=password], form select{width:100%;padding:10px;margin-bottom:15px;border-radius:6px;border:1px solid #ccc;}
form input[type=checkbox]{margin-right:8px;}
form button{background:#004080;color:#fff;border:none;padding:12px 25px;border-radius:8px;cursor:pointer;font-size:15px;transition:0.3s;margin-top:5px;}
form button:hover{background:#0066cc;}
.modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);}
.modal-content{background:#fff;width:400px;margin:8% auto;padding:25px;border-radius:12px;}
.modal h3{margin-top:0;color:#004080;}
</style>
</head>
<body>
<div class="container">
<a href="index.php" class="back">⬅ العودة للرئيسية</a>
<h1>إدارة مشرفي الموقع – <?= htmlspecialchars($stage) ?></h1>

<?php if($success) echo "<div class='ok' style='padding:10px;margin-bottom:15px;'>$success</div>"; ?>
<?php if($error) echo "<div class='no' style='padding:10px;margin-bottom:15px;'>$error</div>"; ?>

<!-- ===== فورم إضافة/تعديل مشرف كامل ===== -->
<form method="POST">
<input type="hidden" name="id_full" value="">
<label>اسم المستخدم</label>
<input type="text" name="username_full" required>
<label>كلمة المرور</label>
<input type="password" name="password_full" placeholder="اتركها فارغة إذا للتعديل فقط">
<label>الصفة</label>
<select name="position_full">
<option value="مدير نظام">مدير نظام</option>
<option value="مشرف نظام" selected>مشرف نظام</option>
</select>
<label>صلاحيات المشرف:</label>
<input type="checkbox" name="can_add_full" checked> إضافة
<input type="checkbox" name="can_edit_full"> تعديل
<input type="checkbox" name="can_delete_full"> حذف
<input type="checkbox" name="can_admin_data_full"> إدارة بيانات
<button type="submit">💾 حفظ المشرف</button>
</form>

<!-- ===== جدول المشرفين ===== -->
<table>
<thead>
<tr>
<th>اسم المستخدم</th>
<th>الصلاحيات</th>
<th>تعديل</th>
<th>حذف</th>
</tr>
</thead>
<tbody>
<?php foreach($users as $u): ?>
<tr>
<td><?= htmlspecialchars($u['username']) ?></td>
<td>
<span class="badge <?= $u['can_add']?'ok':'no' ?>">إضافة</span>
<span class="badge <?= $u['can_edit']?'ok':'no' ?>">تعديل</span>
<span class="badge <?= $u['can_delete']?'ok':'no' ?>">حذف</span>
<span class="badge <?= $u['can_admin_data']?'ok':'no' ?>">إدارة</span>
</td>
<td>
<button class="btn edit"
onclick="openModal(<?= $u['id'] ?>,<?= $u['can_add'] ?>,<?= $u['can_edit'] ?>,<?= $u['can_delete'] ?>,<?= $u['can_admin_data'] ?>)">
تعديل
</button>
</td>
<td>
<a class="btn delete" href="?delete=<?= $u['id'] ?>" onclick="return confirm('تأكيد الحذف؟')">حذف</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<!-- ===== Modal تعديل الصلاحيات ===== -->
<div class="modal" id="editModal">
<div class="modal-content">
<h3>تعديل الصلاحيات</h3>
<form method="POST">
<input type="hidden" name="id" id="edit_id">
<label><input type="checkbox" name="can_add" id="can_add"> إضافة</label>
<label><input type="checkbox" name="can_edit" id="can_edit"> تعديل</label>
<label><input type="checkbox" name="can_delete" id="can_delete"> حذف</label>
<label><input type="checkbox" name="can_admin_data" id="can_admin_data"> إدارة بيانات</label>
<button type="submit" name="save_edit">💾 حفظ التعديل</button>
</form>
</div>
</div>

<script>
function openModal(id,a,e,d,ad){
document.getElementById('edit_id').value=id;
document.getElementById('can_add').checked = a==1;
document.getElementById('can_edit').checked = e==1;
document.getElementById('can_delete').checked = d==1;
document.getElementById('can_admin_data').checked = ad==1;
document.getElementById('editModal').style.display='block';
}
window.onclick=e=>{
if(e.target.classList.contains('modal')){
e.target.style.display='none';
}
}
</script>
</body>
</html>