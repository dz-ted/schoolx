<?php
session_start();
include("db.php");

// التأكد من أن المستخدم هو المدير التنفيذي
if(!isset($_SESSION['username']) || $_SESSION['role']!=='general_manager'){
    header("Location: login.php");
    exit();
}

// دالة لجلب عدد الموظفين حسب المرحلة والفئة
function getCount($conn,$stage,$table){
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM $table WHERE stage=?");
    $stmt->bind_param("s",$stage);
    $stmt->execute();
    $total = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt->close();
    return $total;
}

// المراحل
$stages = ['ابتدائي'=>'primary','اعدادي'=>'middle','ثانوي'=>'secondary'];

// جمع البيانات لكل مرحلة
$phaseData = [];
$totalWorkers = $totalTeachers = $totalAdmins = $totalSupervisors = 0;

foreach($stages as $name=>$role){
    $workers = getCount($conn,$name,'workers_data');
    $teachers = getCount($conn,$name,'personal_data');
    $admins = getCount($conn,$name,'administration_personal_data');
    $supervisors = getCount($conn,$name,'supervisors_data'); // جدول المشرفين
    $phaseData[$name] = [
        'workers'=>$workers,
        'teachers'=>$teachers,
        'admins'=>$admins,
        'supervisors'=>$supervisors
    ];
    $totalWorkers += $workers;
    $totalTeachers += $teachers;
    $totalAdmins += $admins;
    $totalSupervisors += $supervisors;
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>لوحة تحكم المدير التنفيذي – مدرسة الفرقان</title>
<link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body{margin:0;font-family:'Almarai',sans-serif;background:#f5f7fa;}
.container{max-width:1400px;margin:40px auto;padding:20px;}
h1{text-align:center;color:#004080;margin-bottom:50px;font-size:38px;letter-spacing:1px;text-shadow:1px 1px 3px rgba(0,0,0,0.2);}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:25px;margin-bottom:50px;}
.card{background:#fff;border-radius:20px;padding:25px;box-shadow:0 15px 40px rgba(0,0,0,0.1);transition:0.5s;cursor:pointer;position:relative;overflow:hidden;}
.card:hover{transform:translateY(-10px) scale(1.03);box-shadow:0 30px 60px rgba(0,0,0,0.25);}
.card h2{text-align:center;color:#004080;margin-bottom:15px;font-size:28px;}
.progress-container{background:#e0e0e0;border-radius:12px;margin:10px 0;overflow:hidden;height:20px;}
.progress-bar{height:100%;border-radius:12px;text-align:right;color:#fff;font-weight:bold;padding-right:8px;line-height:20px;width:0;}
.footer{margin-top:50px;text-align:center;color:#004080;font-weight:bold;font-size:16px;}
.progress-text{position:absolute;right:10px;top:0;height:100%;display:flex;align-items:center;font-weight:bold;color:#fff;}
</style>
</head>
<body>

<div class="container">
<!-- زر خروج المدير التنفيذي يظهر فقط للمدير العام -->
<?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'general_manager'): ?>
    <a href="logout.php" class="top-btn manager-logout-btn">
        🚪 خروج    
    </a>
<?php endif; ?>

<style>
/* تصميم الزر الخاص بالمدير التنفيذي */
.manager-logout-btn {
    display: inline-block;
    padding: 10px 28px;
    background: linear-gradient(135deg, #c0392b, #e74c3c);
    color: #fff;
    font-weight: bold;
    font-size: 18px;
    border-radius: 12px;
    text-decoration: none;
    box-shadow: 0 8px 25px rgba(192,57,43,0.5);
    transition: 0.4s;
    position: relative;
    overflow: hidden;
}

.manager-logout-btn::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: rgba(255,255,255,0.15);
    transform: rotate(45deg) translate(-100%, -100%);
    transition: 0.5s;
}

.manager-logout-btn:hover::before {
    transform: rotate(45deg) translate(0,0);
}

.manager-logout-btn:hover {
    transform: translateY(-3px) scale(1.05);
    box-shadow: 0 12px 30px rgba(192,57,43,0.6);
}
</style>

<h1>لوحة تحكم المدير التنفيذي – مدرسة الفرقان الخاصة</h1>

<!-- كروت المراحل (بدون نسب مئوية) -->
<div class="grid">
<?php foreach($phaseData as $phase=>$data): ?>
<div class="card" onclick="window.location.href='index.php?stage=<?= $phase ?>'">
    <h2><?= $phase ?></h2>
    <div>العمال: <?= $data['workers'] ?></div>
    <div class="progress-container"><div class="progress-bar" id="workers_<?= $phase ?>" style="background:#f39c12;"><span class="progress-text" id="workers_text_<?= $phase ?>">0</span></div></div>
    <div>المعلمين: <?= $data['teachers'] ?></div>
    <div class="progress-container"><div class="progress-bar" id="teachers_<?= $phase ?>" style="background:#27ae60;"><span class="progress-text" id="teachers_text_<?= $phase ?>">0</span></div></div>
    <div>الإداريين: <?= $data['admins'] ?></div>
    <div class="progress-container"><div class="progress-bar" id="admins_<?= $phase ?>" style="background:#2980b9;"><span class="progress-text" id="admins_text_<?= $phase ?>">0</span></div></div>
    <div>المشرفين: <?= $data['supervisors'] ?></div>
    <div class="progress-container"><div class="progress-bar" id="supervisors_<?= $phase ?>" style="background:#8e44ad;"><span class="progress-text" id="supervisors_text_<?= $phase ?>">0</span></div></div>
</div>
<?php endforeach; ?>
</div>

<!-- كروت الإجمالي (يبقى كما هو مع النسب) -->
<div class="grid">
<div class="card">
    <h2>إجمالي العمال</h2>
    <canvas id="totalWorkersChart" width="400" height="200"></canvas>
</div>
<div class="card">
    <h2>إجمالي المعلمين</h2>
    <canvas id="totalTeachersChart" width="400" height="200"></canvas>
</div>
<div class="card">
    <h2>إجمالي الإداريين</h2>
    <canvas id="totalAdminsChart" width="400" height="200"></canvas>
</div>
<div class="card">
    <h2>إجمالي المشرفين</h2>
    <canvas id="totalSupervisorsChart" width="400" height="200"></canvas>
</div>
</div>

<div class="footer">
©️   جميع الحقوق محفوظة - ثانوية الفرقان الخاصة للبنين   - أ.وائل العمري
</div>

<script>
// تحريك الأشرطة لكل مرحلة وفئة
function animateBar(idBar,idText,target){
    let count=0;
    const bar=document.getElementById(idBar);
    const text=document.getElementById(idText);
    const interval=setInterval(()=>{
        if(count>=target){count=target;clearInterval(interval);}
        bar.style.width = count + 'px';
        text.innerText = count;
        count++;
    },20);
}

<?php foreach($phaseData as $phase=>$data): ?>
animateBar('workers_<?= $phase ?>','workers_text_<?= $phase ?>',<?= $data['workers'] ?>);
animateBar('teachers_<?= $phase ?>','teachers_text_<?= $phase ?>',<?= $data['teachers'] ?>);
animateBar('admins_<?= $phase ?>','admins_text_<?= $phase ?>',<?= $data['admins'] ?>);
animateBar('supervisors_<?= $phase ?>','supervisors_text_<?= $phase ?>',<?= $data['supervisors'] ?>);
<?php endforeach; ?>

// مخططات الإجمالي كما كانت
const phases = <?= json_encode(array_keys($phaseData)) ?>;
const phaseDataValues = <?= json_encode(array_values($phaseData)) ?>;

new Chart(document.getElementById('totalWorkersChart').getContext('2d'),{
    type:'pie',
    data:{labels:phases,datasets:[{data:phaseDataValues.map(p=>p.workers),backgroundColor:['#b0f86c','#e76868','#66c7d8']}]},
    options:{responsive:true}
});
new Chart(document.getElementById('totalTeachersChart').getContext('2d'),{
    type:'pie',
    data:{labels:phases,datasets:[{data:phaseDataValues.map(p=>p.teachers),backgroundColor:['#b0f86c','#e76868','#66c7d8']}]},
    options:{responsive:true}
});
new Chart(document.getElementById('totalAdminsChart').getContext('2d'),{
    type:'pie',
    data:{labels:phases,datasets:[{data:phaseDataValues.map(p=>p.admins),backgroundColor:['#b0f86c','#e76868','#66c7d8']}]},
    options:{responsive:true}
});
new Chart(document.getElementById('totalSupervisorsChart').getContext('2d'),{
    type:'pie',
    data:{labels:phases,datasets:[{data:phaseDataValues.map(p=>p.supervisors),backgroundColor:['#b0f86c','#e76868','#66c7d8']}]},
    options:{responsive:true}
});
</script>

</body>
</html>