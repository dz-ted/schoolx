<?php
session_start();
include("db.php"); // الاتصال بقاعدة البيانات

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// المرحلة من الجلسة
$stage = $_SESSION['stage'] ?? '';

// ترتيب المسمى الوظيفي حسب الأولوية
$role_order = [
    "مدير" => 1,
    "نائب المدير الإداري" => 2,
    "النائب الأكاديمي" => 3,
    "سكرتير" => 4,
    "محاسب" => 5,
    "منسق شؤون الطلاب" => 6,
    "أخصائي اجتماعي" => 7,
    "أخصائي نفسي" => 8,
    "طبيب المدرسة" => 9
];

// جلب بيانات الإداريين حسب المرحلة فقط
$sql = "SELECT p.employee_id, p.full_name, p.employee_number, p.photo, r.role
        FROM administration_personal_data p
        LEFT JOIN administration_roles r ON p.employee_id = r.employee_id
        WHERE p.stage = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $stage);
$stmt->execute();
$result = $stmt->get_result();

$administrators = [];
if($result && $result->num_rows > 0){
    while($row = $result->fetch_assoc()){
        $row['role_order'] = $role_order[$row['role']] ?? 99;
        $administrators[] = $row;
    }
    usort($administrators, function($a,$b){ return $a['role_order'] - $b['role_order']; });
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة تحكم الإداريين</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap');

body {
    margin:0; 
    font-family:'Almarai', sans-serif;
    background: linear-gradient(135deg,#667eea,#764ba2);
}

.container {
    width:95%; margin:30px auto; padding:20px;
}

h1 {
    text-align:center; 
    color:#fff; 
    font-size:2.4em; 
    margin-bottom:30px;
    text-shadow: 2px 2px 5px rgba(0,0,0,0.3);
}

.toolbar {
    display:flex; 
    justify-content:space-between; 
    align-items:center; 
    flex-wrap:wrap;
    margin-bottom:20px; 
    gap:10px;
}

.toolbar input, .toolbar select {
    padding:8px 12px; 
    border-radius:8px; 
    border:none; 
    outline:none;
    font-size:1em; 
    box-shadow:0 4px 10px rgba(0,0,0,0.15);
}

.toolbar .buttons {
    display:flex; 
    gap:10px; 
    flex-wrap:wrap;
}

.toolbar .buttons button {
    padding:8px 14px; 
    border:none; 
    border-radius:8px; 
    cursor:pointer; 
    font-weight:bold; 
    font-size:0.95em; 
    transition:0.3s; 
    box-shadow:0 4px 8px rgba(0,0,0,0.2);
    color:#fff;
}

.buttons .add { background:#43a047; }
.buttons .print { background:#1e88e5; }
.buttons .logout { background:#e53935; }

.buttons button:hover { transform:scale(1.05); opacity:0.9;}

.cards {
    display:grid; 
    grid-template-columns: repeat(auto-fill,minmax(300px,1fr)); 
    gap:20px;
}

.admin-card {
    perspective:1000px;
    display:flex;
    justify-content:center;
}

.admin-card-inner {
    background:#fff; 
    border-radius:15px; 
    padding:15px; 
    display:flex; 
    flex-direction:column;
    align-items:center; 
    transition: transform 0.3s ease;
    transform-style: preserve-3d;
    box-shadow:0 12px 25px rgba(0,0,0,0.15);
}
.admin-card:hover .admin-card-inner { 
    transform: rotateY(15deg) rotateX(2deg);
}
.admin-info img {
    width:100px; 
    height:100px; 
    object-fit:cover; 
    border-radius:50%; 
    border:3px solid #764ba2;
    margin-bottom:15px; 
    transition:0.3s;
}
.admin-info img:hover { transform: scale(1.15); }

.admin-text span {
    display:block; 
    text-align:center; 
    font-weight:700; 
    font-size:1.05em; 
    margin:3px 0;
}

.admin-text span.role { color:#764ba2; font-size:1.15em; text-transform:uppercase;}

.actions {
    display:flex; 
    gap:10px; 
    margin-top:15px;
}

.actions button {
    padding:8px 12px; 
    border:none; 
    border-radius:8px; 
    cursor:pointer;
    font-weight:bold; 
    font-size:0.9em; 
    transition:0.3s; 
    box-shadow:0 4px 8px rgba(0,0,0,0.2);
}

.actions button.view { background:#1e88e5; color:#fff; }
.actions button.edit { background:#fbc02d; color:#fff; }
.actions button.delete { background:#e53935; color:#fff; }
@media(max-width:768px){
    .cards { grid-template-columns: 1fr; }
}
</style>
<script>
function filterAdmins() {
    const search = document.getElementById('search').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const cards = document.querySelectorAll('.admin-card-inner');
    cards.forEach(card => {
        const name = card.querySelector('.admin-text span:first-child').innerText.toLowerCase();
        const role = card.querySelector('.admin-text .role').innerText;
        const matchesSearch = name.includes(search) || card.querySelector('.admin-text span:nth-child(2)').innerText.includes(search);
        const matchesRole = roleFilter === "" || role === roleFilter;
        if(matchesSearch && matchesRole) card.parentElement.style.display = 'flex';
        else card.parentElement.style.display = 'none';
    });
}
</script>
</head>
<body>
<div class="container">
<h1>لوحة تحكم الإداريين - مرحلتك: <?php echo htmlspecialchars($stage); ?></h1>

<div class="toolbar">
    <input type="text" id="search" placeholder="🔍 ابحث بالاسم أو الرقم الوظيفي" onkeyup="filterAdmins()">
    <select id="roleFilter" onchange="filterAdmins()">
        <option value="">فلترة حسب الدور الوظيفي</option>
        <?php foreach($role_order as $role=>$order): ?>
        <option value="<?php echo $role; ?>"><?php echo $role; ?></option>
        <?php endforeach; ?>
    </select>

    <div class="buttons">
        <?php if($_SESSION['can_add'] ?? 0): ?>
        <button class="add" onclick="window.location.href='addadministrators.php'">➕ إضافة إداري</button>
        <?php endif; ?>
        <button class="print" onclick="window.print()">🖨 طباعة</button>
        <button class="logout" onclick="window.location.href='index.php'">🚪 خروج</button>
    </div>
</div>

<div class="cards">
<?php if(empty($administrators)): ?>
<p style="grid-column:1/-1;text-align:center;font-weight:bold;font-size:1.2em;color:#fff;">لا توجد بيانات لعرضها</p>
<?php else: ?>
<?php foreach($administrators as $admin): ?>
<div class="admin-card">
<div class="admin-card-inner">
    <?php
    $avatar_path = (!empty($admin['photo']) && file_exists("uplodadmin/".$admin['photo'])) ? "uplodadmin/".$admin['photo'] : "uploads/avatar.png";
    ?>
    <div class="admin-info">
        <img src="<?php echo $avatar_path; ?>" alt="صورة الإداري">
    </div>
    <div class="admin-text">
        <span><?php echo htmlspecialchars($admin['full_name']); ?></span>
        <span>الرقم الوظيفي: <?php echo htmlspecialchars($admin['employee_number']); ?></span>
        <span class="role"><?php echo htmlspecialchars($admin['role']); ?></span>
    </div>
    <div class="actions">
        <button class="view" onclick="window.location.href='administration_view.php?id=<?php echo $admin['employee_id']; ?>'">👁 معاينة</button>
        <?php if($_SESSION['can_edit'] ?? 0): ?>
        <button class="edit" onclick="window.location.href='administratorsedit.php?employee_id=<?php echo $admin['employee_id']; ?>'">✏ تعديل</button>
        <?php endif; ?>
        <?php if($_SESSION['can_delete'] ?? 0): ?>
        <button class="delete" onclick="if(confirm('هل أنت متأكد من الحذف؟')) window.location.href='administration_delete.php?id=<?php echo $admin['employee_id']; ?>'">🗑 حذف</button>
        <?php endif; ?>
    </div>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</div>
</body>
</html>