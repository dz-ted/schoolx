<?php
session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

if(!isset($_GET['id'])){
    exit("لا يمكن الوصول لهذه الصفحة بدون تحديد الإداري");
}

$employee_id = intval($_GET['id']);

$sql = "
SELECT p.*, q.experience, q.degree, q.major, q.university, q.graduation_year, q.courses, q.skills,
       c.phone_mobile, c.phone_home, c.emergency_phone, c.address_qatar, c.address_home,
       r.role,
       a.teams_account, a.attendance_platform
FROM administration_personal_data p
LEFT JOIN administration_qualifications q ON p.employee_id = q.employee_id
LEFT JOIN administration_contact_info c ON p.employee_id = c.employee_id
LEFT JOIN administration_roles r ON p.employee_id = r.employee_id
LEFT JOIN administration_accounts a ON p.employee_id = a.employee_id
WHERE p.employee_id = $employee_id
";
$result = $conn->query($sql);

if(!$result || $result->num_rows == 0){
    exit("لا توجد بيانات لهذا الإداري");
}

$admin = $result->fetch_assoc();

$avatar_path = !empty($admin['photo']) && file_exists("uplodadmin/".$admin['photo']) 
               ? "uplodadmin/".$admin['photo'] 
               : "uplodadmin/avatar.png";

$tabs = [
    "role" => [
        "title"=>"الدور الوظيفي",
        "fields"=>["role"]
    ],
    "personal"=>[
        "title"=>"البيانات الشخصية",
        "fields"=>["full_name","employee_number","employee_id","personal_id","birth_date","nationality","marital_status","hire_date","num_children","driving_license"]
    ],
    "contact"=>[
        "title"=>"التواصل",
        "fields"=>["phone_mobile","phone_home","emergency_phone","address_qatar","address_home"]
    ],
    "qualifications"=>[
        "title"=>"المؤهلات والخبرات",
        "fields"=>["experience","degree","major","university","graduation_year","courses","skills"]
    ],
    "accounts"=>[
        "title"=>"الحسابات",
        "fields"=>["teams_account","attendance_platform"]
    ]
];

$fields_ar = [
    "full_name"=>"الاسم الرباعي",
    "employee_number"=>"الرقم الوظيفي",
    "employee_id"=>"رقم الهوية",
    "personal_id"=>"رقم الجواز",
    "birth_date"=>"تاريخ الميلاد",
    "nationality"=>"الجنسية",
    "gender"=>"نوع التعليم",
    "marital_status"=>"الحالة الزوجية",
    "num_children"=>"عدد الأبناء",
    "driving_license"=>"رخصة القيادة",
    "phone_mobile"=>"رقم الهاتف في قطر",
    "phone_home"=>"رقم الهاتف في البلد",
    "emergency_phone"=>"رقم الطوارئ",
    "address_qatar"=>"عنوانه في قطر",
    "address_home"=>"عنوانه في البلد",
    "experience"=>"سنوات الخبرة",
    "degree"=>"المؤهل العلمي",
    "major"=>"التخصص الدراسي",
    "university"=>"الجامعة / الكلية",
    "graduation_year"=>"سنة التخرج",
    "courses"=>"الدورات التدريبية",
    "skills"=>"المهارات الإضافية",
    "role"=>"الدور الوظيفي",
    "teams_account"=>"حساب Teams",
    "attendance_platform"=>"حساب برنامج الغياب",
    "hire_date"=>"تاريخ التعيين",
];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>معاينة بيانات الإداري</title>
<link href="https://fonts.googleapis.com/css2?family=Almarai:wght@400;700&display=swap" rel="stylesheet">
<style>
/* حافظنا على كامل التصميم */
body { margin:0; font-family:'Almarai',sans-serif; background: linear-gradient(135deg,#667eea,#764ba2); color:#333; }
.container { width:95%; max-width:1200px; margin:30px auto; padding:25px; background:#fff; border-radius:20px; box-shadow:0 12px 30px rgba(0,0,0,0.2);}
h1 { text-align:center; color:#764ba2; margin-bottom:30px; font-size:2.2em; }
.admin-header { display:flex; flex-wrap:wrap; align-items:center; gap:30px; margin-bottom:25px; }
.admin-header img { width:180px; height:180px; object-fit:cover; border-radius:50%; border:4px solid #764ba2; box-shadow:0 5px 15px rgba(0,0,0,0.2);}
.tabs { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; justify-content:center; }
.tab-btn { padding:10px 18px; cursor:pointer; border:none; border-radius:10px; font-weight:bold; background:#764ba2; color:#fff; transition:0.3s; }
.tab-btn.active { background:#1e88e5; }
.tab-content { display:none; animation:fadeIn 0.3s; }
.tab-content.active { display:block; }
@keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
table { width:100%; border-collapse:collapse; margin-top:10px; }
th, td { text-align:right; padding:12px 15px; border-bottom:1px solid #ddd; font-size:1em; vertical-align: top; }
th { background:#764ba2; color:#fff; width:35%; }
.buttons { display:flex; justify-content:center; gap:15px; margin-top:25px; flex-wrap:wrap; }
.buttons button { padding:12px 18px; border:none; border-radius:10px; cursor:pointer; font-weight:bold; font-size:1em; transition:0.3s; box-shadow:0 5px 12px rgba(0,0,0,0.15); }
.buttons button:hover { transform:scale(1.05); opacity:0.9;}
.back { background:#555; color:#fff; }
.edit { background:#fbc02d; color:#fff; }
.delete { background:#e53935; color:#fff; }
@media(max-width:768px){ .admin-header { flex-direction:column; align-items:center; } th, td { font-size:0.95em; padding:10px; } }
</style>
<script>
function openTab(tabId){
    document.querySelectorAll('.tab-btn').forEach(btn=>btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(tab=>tab.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    document.querySelector('[data-tab="'+tabId+'"]').classList.add('active');
}
window.onload = function(){ openTab('role'); };
</script>
</head>
<body>
<div class="container">
<h1>معاينة بيانات الإداري</h1>
<div class="admin-header">
    <img src="<?php echo $avatar_path; ?>" alt="صورة الإداري">
    <div style="flex:1; min-width:300px;">
        <div class="tabs">
            <?php foreach($tabs as $id=>$tab): ?>
            <button class="tab-btn" data-tab="<?php echo $id; ?>" onclick="openTab('<?php echo $id; ?>')"><?php echo $tab['title']; ?></button>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php foreach($tabs as $id=>$tab): ?>
<div class="tab-content" id="<?php echo $id; ?>">
    <table>
        <?php foreach($tab['fields'] as $field): ?>
        <?php if(isset($admin[$field])): 
            $value = $admin[$field];
            if($field=="birth_date" && ($value=='0000-00-00' || empty($value))) $value='';
            if($field=="num_children" && ($admin['marital_status'] ?? '') != "متزوج") $value='';
        ?>
        <tr>
            <th><?php echo $fields_ar[$field] ?? $field; ?></th>
            <td><?php echo nl2br(htmlspecialchars($value)); ?></td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </table>
</div>
<?php endforeach; ?>

<div class="buttons">
    <button class="back" onclick="window.location.href='indexadministrators.php'">⬅ العودة</button>
    <button class="edit" onclick="window.location.href='administratorsedit.php?employee_id=<?php echo $admin['employee_id']; ?>'">✏ تعديل</button>
    <button class="delete" onclick="if(confirm('هل أنت متأكد من الحذف؟')) window.location.href='administration_delete.php?id=<?php echo $admin['employee_id']; ?>'">🗑 حذف</button>
</div>
</div>
</body>
</html>