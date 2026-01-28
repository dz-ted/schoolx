<?php
session_start();


// إذا جاء الطلب من كرت المدير التنفيذي مع stage
if(isset($_GET['stage']) && !empty($_GET['stage'])){
    // تأكد من أنها مرحلة صحيحة فقط
    $allowed_stages = ['ابتدائي','اعدادي','ثانوي'];
    if(in_array($_GET['stage'], $allowed_stages)){
        $_SESSION['stage'] = $_GET['stage'];
    }
}

// الآن يمكن الاعتماد على $_SESSION['stage']
$stage = $_SESSION['stage'] ?? '';
$is_stage_admin = $_SESSION['is_stage_admin'] ?? 0;


// ✅ منع الكاش لمنع الرجوع بعد تسجيل الخروج
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include("db.php");

// التأكد من تسجيل الدخول
if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

if(!isset($_SESSION['username'])){
    header("Location: login.php");
    exit();
}

// المرحلة من الجلسة
$stage = $_SESSION['stage'] ?? '';
$is_stage_admin = $_SESSION['is_stage_admin'] ?? 0;

// دالة لجلب عدد الموظفين حسب الجدول والمرحلة
function getCount($conn, $table, $stage){
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE 'stage'");
    if($result->num_rows > 0){
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM $table WHERE stage=?");
        $stmt->bind_param("s", $stage);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    } else {
        return 0;
    }
}

$count_teachers = getCount($conn, 'personal_data', $stage);
$count_admins = getCount($conn, 'administration_personal_data', $stage);
$count_supervisors = getCount($conn, 'supervisors_data', $stage);
$count_workers = getCount($conn, 'workers_data', $stage);
?>

<!DOCTYPE html>
<html lang="ar">
<head>
<meta charset="UTF-8">
<title>لوحة تحكم الموظفين</title>
<link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap" rel="stylesheet">
<style>
/* --- كل التنسيقات كما هي --- */
* { margin:0; padding:0; box-sizing:border-box; font-family: 'Almarai', sans-serif; }
body { background: linear-gradient(135deg,#f5f7fa,#c3cfe2); min-height:100vh; }
.container { max-width:1300px; margin:50px auto; padding:20px; }
header { text-align:center; margin-bottom:40px; }
header h1 { color:#004080; font-size:36px; text-shadow:1px 1px 4px rgba(0,0,0,0.2); margin-bottom:20px; }
header img { height: 200px; border-radius: 12px; display: block; margin: 90px auto 0 auto; transition: 0.3s; }
header img:hover { transform: translateY(-5px) scale(1.05); box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
.top-btn { display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, #ff4b5c, #ff1e00); color: #fff; font-weight: bold; font-size: 16px; border-radius: 12px; text-decoration: none; box-shadow: 0 8px 20px rgba(255,75,92,0.4); transition: 0.4s; position: relative; overflow: hidden; }
.top-btn::before { content: ""; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%; background: rgba(255,255,255,0.2); transform: rotate(45deg) translate(-100%, -100%); transition: 0.5s; }
.top-btn:hover::before { transform: rotate(45deg) translate(0, 0); }
.top-btn:hover { transform: translateY(-3px) scale(1.05); box-shadow: 0 12px 25px rgba(255,75,92,0.6); }
.grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:30px; }
.card { background:#fff; border-radius:20px; padding:40px 20px; text-align:center; box-shadow:0 15px 35px rgba(0,0,0,0.15); transition:0.5s; cursor:pointer; position:relative; overflow:hidden; }
.card::before { content:""; position:absolute; top:0; left:0; width:100%; height:100%; background:linear-gradient(135deg,rgba(128,0,0,0.1),rgba(0,64,128,0.1)); z-index:0; transition:0.5s; }
.card:hover::before { transform:rotate(20deg) scale(1.3); }
.card:hover { transform: translateY(-15px) scale(1.03); }
.card h2 { font-size:28px; margin-bottom:15px; color:#800000; position:relative; z-index:1; }
.card p { font-size:24px; color:#004080; margin-bottom:25px; font-weight:bold; position:relative; z-index:1; }
.card a { display:inline-block; text-decoration:none; background:#800000; color:#fff; padding:14px 30px; border-radius:12px; font-weight:bold; font-size:16px; transition:0.4s; position:relative; z-index:1; }
.card a:hover { background:#a00000; transform:translateY(-3px); }
.count { font-size:40px; color:#004080; font-weight:bold; margin-bottom:15px; }
footer { text-align:center; margin-top:60px; color:#004080; font-weight:bold; font-size:16px; }
@media(max-width:768px){ .grid { grid-template-columns:1fr; } }
</style>
</head>
<body>

<div class="container">
  <a href="logout.php" class="top-btn">🚪 خروج</a>

  <!-- ===== يظهر فقط لمن هو مدير المرحلة ===== -->
<?php if($_SESSION['is_stage_admin'] == 1): ?>
<a href="add_users.php" class="top-btn">➕  إدارة الحسابات</a>
<?php endif; ?>

<?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'general_manager'): ?>
    <a href="manager.php" class="top-btn">⬅ العودة للمدير التنفيذي</a>
<?php endif; ?>

<header>
    <h1>لوحة تحكم موظفي مرحلتك: <?php echo $stage; ?></h1>
</header>

<div class="grid">
    <div class="card">
        <h2>العمال</h2>
        <div class="count" id="workersCount"><?php echo $count_workers; ?></div>
        <a href="index_workers.php">دخول</a>
    </div>
    <div class="card">
        <h2>المشرفين</h2>
        <div class="count" id="supervisorsCount"><?php echo $count_supervisors; ?></div>
        <a href="index_supervisors.php">دخول</a>
    </div>
    <div class="card">
        <h2>المعلمين</h2>
        <div class="count" id="teachersCount"><?php echo $count_teachers; ?></div>
        <a href="indexteacher.php">دخول</a>
    </div>
    <div class="card">
        <h2>الإداريين</h2>
        <div class="count" id="adminsCount"><?php echo $count_admins; ?></div>
        <a href="indexadministrators.php">دخول</a>
    </div>
</div>

<header>
    <img src="1.png" alt="شعار المدرسة">
</header>
<footer>
    ©️ جميع الحقوق محفوظة - ثانوية الفرقان الخاصة للبنين [أ.وائل العمري]
</footer>

<script>
function animateCount(id, target){
    let count = 0;
    const speed = 20;
    const step = Math.ceil(target/50);
    const el = document.getElementById(id);
    const interval = setInterval(()=>{
        count += step;
        if(count >= target){ count = target; clearInterval(interval); }
        el.textContent = count;
    }, speed);
}

animateCount('teachersCount', <?php echo $count_teachers; ?>);
animateCount('adminsCount', <?php echo $count_admins; ?>);
animateCount('supervisorsCount', <?php echo $count_supervisors; ?>);
animateCount('workersCount', <?php echo $count_workers; ?>);
</script>

</body>
</html>