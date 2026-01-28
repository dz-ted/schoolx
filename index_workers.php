<?php
session_start();
include("db.php");

// تحقق من تسجيل الدخول
if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

// صلاحيات المراحل
$user_stage = $_SESSION['stage'] ?? 'الابتدائي';
$can_edit = $_SESSION['can_edit'] ?? 0;
$can_delete = $_SESSION['can_delete'] ?? 0;
$can_admin_data = $_SESSION['can_admin_data'] ?? 0;

// بحث
$search = trim($_GET['search'] ?? '');

// Pagination
$perPage = 30;
$page = intval($_GET['page'] ?? 1);
$offset = ($page - 1) * $perPage;

// جلب عدد العمال
$total_sql = "SELECT COUNT(*) as total FROM workers_data WHERE stage LIKE ?";
$total_stmt = $conn->prepare($total_sql);
$stage_like = "%$user_stage%";
$total_stmt->bind_param("s",$stage_like);
$total_stmt->execute();
$total_res = $total_stmt->get_result()->fetch_assoc();
$total_workers = $total_res['total'];
$total_stmt->close();

// جلب بيانات العمال
$sql = "SELECT * FROM workers_data WHERE stage LIKE ?";
$params = [$stage_like];
$types = "s";

if($search !== ''){
    $sql .= " AND (full_name LIKE ? OR employee_id LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}
$sql .= " ORDER BY employee_id ASC LIMIT ?,?";
$params[] = $offset;
$params[] = $perPage;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types,...$params);
$stmt->execute();
$workers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// حساب الصفحات
$total_pages = ceil($total_workers / $perPage);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>الصفحة الرئيسية للعمال</title>
<style>
body{
    font-family: 'Cairo', Tahoma, Arial, sans-serif;
    background:#f0f2f5;
    margin:0;
    padding:0;
}
header{
    background:#004080;
    color:#fff;
    padding:15px 30px;
    text-align:center;
    font-size:24px;
    font-weight:bold;
}
.container{
    max-width:1200px;
    margin:20px auto;
    padding:0 15px;
}
.top-buttons{
    display:flex;
    justify-content:space-between; /* زر الإضافة على اليسار، زر العودة على اليمين */
    margin-bottom:20px;
}
.top-buttons a{
    padding:8px 15px;
    border-radius:6px;
    text-decoration:none;
    color:#fff;
    font-weight:bold;
}
.add-btn{background:#28a745;}
.home-btn{background:#6c757d;}
.search-box{
    display:flex;
    justify-content:flex-end;
    margin-bottom:20px;
}
.search-box input[type="text"]{
    padding:8px 12px;
    border-radius:6px;
    border:1px solid #ccc;
    width:250px;
    margin-left:10px;
}
.search-box button{
    padding:8px 12px;
    background:#004080;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
}
.cards{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(250px,1fr));
    gap:20px;
}
.card{
    background:#fff;
    border-radius:12px;
    box-shadow:0 4px 15px rgba(0,0,0,0.1);
    overflow:hidden;
    transition:transform 0.2s;
}
.card:hover{
    transform:translateY(-5px);
}
.card img{
    width:100px;
    height:100px;
    object-fit:cover;
    border-radius:50%;
    display:block;
    margin:15px auto 10px auto;
    border:2px solid #004080;
}
.card-content{
    padding:10px 15px 20px 15px;
    text-align:center;
}
.card-content h3{
    margin:5px 0;
    color:#004080;
}
.card-content p{
    margin:3px 0;
    color:#333;
}
.card-buttons{
    display:flex;
    justify-content:center;
    flex-wrap:wrap;
    gap:5px;
    margin-top:10px;
}
.card-buttons a, .card-buttons button{
    padding:6px 10px;
    border-radius:6px;
    text-decoration:none;
    color:#fff;
    font-weight:bold;
    cursor:pointer;
    border:none;
    font-size:14px;
}
.edit-btn{background:#28a745;}
.delete-btn{background:#dc3545;}
.view-btn{background:#17a2b8;}
.admin-btn-card{background:#f39c12;}
.card-buttons a:hover, .card-buttons button:hover{opacity:0.9;}
.pagination{
    text-align:center;
    margin-top:30px;
}
.pagination a{
    padding:8px 12px;
    margin:0 3px;
    background:#004080;
    color:#fff;
    border-radius:6px;
    text-decoration:none;
}
.pagination a.active{
    background:#f39c12;
    color:#000;
}
</style>
</head>
<body>

<header>قائمة العمال</header>

<div class="container">

<div class="top-buttons">
<?php if($can_edit): ?><a href="add_worker.php" class="add-btn">➕ إضافة عامل</a><?php endif; ?>
<a href="index.php" class="home-btn">🏠 العودة للصفحة الرئيسية</a>
</div>

<div class="search-box">
<form method="GET">
<input type="text" name="search" placeholder="بحث باسم أو رقم العامل" value="<?= htmlspecialchars($search) ?>">
<button type="submit">🔍 بحث</button>
</form>
</div>

<div class="cards-container">
<?php foreach($workers as $worker): ?>
    <div class="worker-card">
        <img src="uploads/workers/<?= htmlspecialchars($worker['photo'] ?? 'avatar.png') ?>" alt="صورة العامل">
        <div class="worker-info">
            <h3><?= htmlspecialchars($worker['full_name']) ?></h3>
            <p>رقم العامل: <?= htmlspecialchars($worker['employee_id']) ?></p>
            <p>الوظيفة: <?= htmlspecialchars($worker['role']) ?></p>
        </div>
        <div class="worker-actions">
            <?php if($can_edit): ?>
                <a href="edit_worker.php?id=<?= $worker['employee_id'] ?>" class="btn edit-btn">تعديل</a>
            <?php endif; ?>
            

            <?php if($can_delete): ?>
                <a href="delete_worker.php?id=<?= $worker['employee_id'] ?>" class="btn delete-btn" onclick="return confirm('هل أنت متأكد من حذف هذا العامل؟')">حذف</a>
            <?php endif; ?>

            <a href="view_worker.php?id=<?= $worker['employee_id'] ?>" class="btn view-btn">معاينة</a>

            <?php if($can_admin_data): ?>
                <a href="worker_admin_data.php?id=<?= $worker['employee_id'] ?>" class="btn admin-btn">البيانات الإدارية</a>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
</div>

<style>
.cards-container{
    display:flex;
    flex-wrap:wrap;
    gap:20px;
    justify-content:center;
    margin-top:20px;
}
.worker-card{
    background:#fff;
    border-radius:12px;
    box-shadow:0 4px 12px rgba(0,0,0,0.1);
    width:250px;
    padding:15px;
    text-align:center;
    display:flex;
    flex-direction:column;
    align-items:center;
}
.worker-card img{
    width:100px;
    height:100px;
    object-fit:cover;
    border-radius:50%;
    border:3px solid #004080;
    margin-bottom:10px;
}
.worker-info h3{
    margin:5px 0;
    font-size:18px;
    color:#004080;
}
.worker-info p{
    margin:3px 0;
    font-size:14px;
    color:#333;
}
.worker-actions{
    margin-top:10px;
    display:flex;
    flex-wrap:wrap;
    gap:5px;
    justify-content:center;
}
.worker-actions .btn{
    padding:6px 10px;
    font-size:13px;
    border-radius:6px;
    text-decoration:none;
    color:#fff;
    transition:0.3s;
}
.edit-btn{background:#0060c0;}
.edit-btn:hover{background:#004080;}
.delete-btn{background:#c00000;}
.delete-btn:hover{background:#800000;}
.view-btn{background:#00a000;}
.view-btn:hover{background:#007500;}
.admin-btn{background:#800080;}
.admin-btn:hover{background:#550055;}
</style>

<div class="pagination">
<?php for($p=1;$p<=$total_pages;$p++): ?>
<a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>" class="<?= $p==$page?'active':'' ?>"><?= $p ?></a>
<?php endfor; ?>
</div>

</div>

</body>
</html>