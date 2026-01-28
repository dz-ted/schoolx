<?php
session_start();
include("db.php");

// التأكد من تسجيل الدخول
if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

// صلاحيات الأزرار من الجلسة
$can_add = $_SESSION['can_add'] ?? 0;
$can_edit = $_SESSION['can_edit'] ?? 0;
$can_delete = $_SESSION['can_delete'] ?? 0;
$can_admin_data = $_SESSION['can_admin_data'] ?? 0;

// الحصول على المرحلة من الجلسة مع إزالة الفراغات
$current_stage = isset($_SESSION['stage']) ? trim($_SESSION['stage']) : null;

// قائمة المراحل الصحيحة بالعربي
$allowed_stages = ['ابتدائي', 'اعدادي', 'ثانوي'];

// التحقق من المرحلة بدون حساسية للحروف
$stage_valid = false;
foreach ($allowed_stages as $stage) {
    if (mb_strtolower(trim($stage), 'UTF-8') === mb_strtolower($current_stage, 'UTF-8')) {
        $current_stage = $stage; // نستخدم النسخة الرسمية من القائمة
        $stage_valid = true;
        break;
    }
}

// إذا لم تكن المرحلة صالحة نتركها فارغة
if (!$stage_valid) {
    $current_stage = null;
}

// معالجة البحث
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// جلب بيانات المدرسين فقط إذا كانت المرحلة صالحة
$result = null;
if ($current_stage !== null) {
    $sql = "SELECT p.employee_id, p.full_name, p.job_title, p.department, a.image
            FROM personal_data p
            LEFT JOIN admin_data a ON p.employee_id = a.employee_id
            WHERE (p.full_name LIKE ? OR p.employee_id LIKE ?) AND TRIM(p.stage) = ?
            ORDER BY p.full_name ASC";

    $stmt = $conn->prepare($sql);
    $like_search = "%$search%";
    $stmt->bind_param("sss", $like_search, $like_search, $current_stage);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>صفحة المدرسين</title>
<style>
body {
    margin:0;
    font-family: Arial,sans-serif;
    background: linear-gradient(to bottom, #8A1538, #fff) no-repeat;
    background-size: cover;
    min-height: 100vh;
}
.container { width:95%; margin:20px auto; }
h1 {
    color:#fff; background:#8A1538; text-align:center;
    padding:15px; border-radius:8px; margin-bottom:25px;
}
.top-actions {
    display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;
}
.search-form { display:flex; gap:10px; align-items:center; }
.search-input { padding:8px; border-radius:6px; border:1px solid #ccc; width:250px; }
.search-btn, .refresh-btn, .top-btn, .excel-btn, .print-btn {
    background:#8A1538; color:#fff; border:none;
    padding:8px 15px; border-radius:6px;
    cursor:pointer; transition:0.3s; text-decoration:none;
    font-size:14px;
}
.search-btn:hover, .refresh-btn:hover, .top-btn:hover, .excel-btn:hover, .print-btn:hover {
    background:#a00000;
}
.header-row {
    display:flex; justify-content:space-between;
    background:#8A1538; color:#fff; padding:10px 15px;
    border-radius:8px; font-weight:bold; margin-bottom:10px;
    position: sticky; top:0; z-index:10;
}
.header-row div { flex:1; text-align:center; }

.card {
    display:flex; align-items:center; gap:15px;
    background:#fce8ed; padding:15px; border-radius:10px;
    box-shadow:0 4px 10px rgba(128,0,0,0.15);
    margin-bottom:12px; transition: transform 0.2s;
}
.card:hover { transform:translateY(-3px); }
.card img {
    width:60px; height:60px; border-radius:50%;
    object-fit:cover; border:2px solid #8A1538;
}
.card .info {
    flex:1; display:flex; justify-content:space-between;
    align-items:center; border-right:2px solid #8A1538;
    border-left:2px solid #8A1538; padding:0 10px;
}
.card .info div {
    flex:1; text-align:center; font-weight:bold; color:#5a0d22;
}
.card .actions { display:flex; gap:5px; }
.card .actions button {
    background:#8A1538; color:#fff; border:none;
    padding:6px 12px; border-radius:5px;
    cursor:pointer; transition:0.3s;
}
.card .actions button:hover { background:#a00000; }
</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function openModal(employee_id){
    $.ajax({
        url:'preview_data.php',
        type:'GET',
        data:{id:employee_id},
        success:function(data){
            $('body').append(data);
        }
    });
}
function closeModal(){ $('.modal').remove(); }
function refreshPage(){ window.location.href = window.location.pathname; }
</script>
</head>
<body>

<header style="
    display: flex; align-items: center; justify-content: center; gap: 15px;
    background: #fff; padding: 15px 25px; margin: 20px auto;
    max-width: 1000px; border-radius: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
">
    <img src="1.png" alt="شعار المدرسة"
         style="height:60px; width:auto; border-radius:10px; object-fit:contain; border:2px solid #ccc;">
    <h2 style="margin:0; color:#800000; font-family:'Cairo', sans-serif;">
       قائمة مدرسي ال<?php echo $current_stage ?? 'المرحلة'; ?>
    </h2>
</header>

<div class="container">
<div class="top-actions">
    <form method="GET" class="search-form">
        <input type="text" name="search" class="search-input" placeholder="بحث بالاسم أو الرقم" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="search-btn">🔍 بحث</button>
        <button type="button" class="refresh-btn" onclick="refreshPage()">📋 قائمة المدرسين</button>
    </form>

    <div style="display:flex; gap:10px;">
        <a href="print_teachers.php" target="_blank" class="print-btn">🖨 طباعة القائمة</a>
        <a href="export_xls.php" class="excel-btn">📊 تصدير Excel</a>
        <?php if($can_add): ?>
        <a href="add.php" class="top-btn">➕ إضافة مدرس</a>
        <?php endif; ?>
        <a href="index.php" class="top-btn">🚪 خروج</a>
    </div>
</div>

<div class="header-row">
    <div>اسم المدرس</div>
    <div>المسمى الوظيفي</div>
    <div>القسم</div>
    <div>الإعدادات</div>
</div>

<?php if($result && $result->num_rows > 0): ?>
    <?php while($row = $result->fetch_assoc()): 
        $img = !empty($row['image']) ? "uploads/".$row['image'] : "uploads/avatar.png";
    ?>
        <div class="card">
            <img src="<?php echo $img; ?>" alt="صورة الموظف">
            <div class="info">
                <div><?php echo htmlspecialchars($row['full_name']); ?></div>
                <div><?php echo htmlspecialchars($row['job_title']); ?></div>
                <div><?php echo htmlspecialchars($row['department']); ?></div>
            </div>
            <div class="actions">
                <button onclick="openModal('<?php echo $row['employee_id']; ?>')">👁 معاينة</button>
                <?php if($can_edit): ?>
                <a href="edit.php?id=<?php echo $row['employee_id']; ?>"><button>✏ تعديل</button></a>
                <?php endif; ?>
                <?php if($can_admin_data): ?>
                <a href="admin_data.php?id=<?php echo $row['employee_id']; ?>"><button>🗂 بيانات إدارية</button></a>
                <?php endif; ?>
                <?php if($can_delete): ?>
                <a href="delete.php?id=<?php echo $row['employee_id']; ?>" onclick="return confirm('هل أنت متأكد من الحذف؟');"><button>🗑 حذف</button></a>
                <?php endif; ?>
            </div>
        </div>
    <?php endwhile; ?>
<?php else: ?>
    <p style="text-align:center; margin-top:30px;">لا توجد بيانات لعرضها</p>
<?php endif; ?>
</div>
</body>
</html>