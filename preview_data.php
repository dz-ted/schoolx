<?php
session_start();
include("db.php");

if(!isset($_SESSION['user']) || !isset($_GET['id'])){
    exit("لا يمكن عرض البيانات");
}

$employee_id = $_GET['id'];

// جلب البيانات من جميع الجداول
$tables = ['personal_data', 'qualifications', 'contact_info', 'job_data', 'admin_data', 'accounts'];
$data = [];
foreach($tables as $table){
    $stmt = $conn->prepare("SELECT * FROM $table WHERE employee_id=?");
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $data[$table] = $res->fetch_assoc() ?? [];
}

// صورة الموظف
$image_path = "uploads/" . ($data['admin_data']['image'] ?? 'avatar.png');
?>

<div class="modal">
<div class="modal-content">
    <span class="modal-close" onclick="closeModal()">✖</span>

    <!-- Header -->
    <div class="modal-header">
        <img src="<?php echo $image_path; ?>" alt="صورة الموظف">
        <div class="header-info">
            <h2><?php echo htmlspecialchars($data['personal_data']['full_name'] ?? ''); ?></h2>
            <p>رقم الهوية: <?php echo htmlspecialchars($employee_id); ?></p>
            <p>المسمى الوظيفي: <?php echo htmlspecialchars($data['personal_data']['job_title'] ?? ''); ?></p>
            <p>القسم: <?php echo htmlspecialchars($data['personal_data']['department'] ?? ''); ?></p>
        </div>
    </div>

    <div style="display:flex; flex-direction:column; gap:10px;">
        <button onclick="printPage()" class="action-btn">🖨 طباعة الصفحة</button>
        <button onclick="downloadExcel()" class="action-btn" style="background:#555;">📊 تحميل Excel</button>
    </div>

    <!-- Tabs -->
    <div style="text-align:center; margin-bottom:25px;">
        <button class="tab-btn active" id="btnPersonal">البيانات الشخصية</button>
        <button class="tab-btn inactive" id="btnAdmin">البيانات الإدارية</button>
    </div>

    <!-- Personal Section -->
    <div class="section" id="personalSection" style="display:block;">
        <div class="card">
            <h3>المعلومات الأساسية</h3>
            <p><span>الاسم الرباعي:</span><br><?php echo htmlspecialchars($data['personal_data']['full_name'] ?? ''); ?></p>
            <p><span>رقم الجواز:</span><br><?php echo htmlspecialchars($data['personal_data']['personal_id'] ?? ''); ?></p>
            <p><span>تاريخ الميلاد:</span><br><?php echo htmlspecialchars($data['personal_data']['birth_date'] ?? ''); ?></p>
            <p><span>الجنسية:</span><br><?php echo htmlspecialchars($data['personal_data']['nationality'] ?? ''); ?></p>
            <p><span>التعليم:</span><br><?php echo htmlspecialchars($data['personal_data']['gender'] ?? ''); ?></p>
            <p><span>الحالة الزوجية:</span><br><?php echo htmlspecialchars($data['personal_data']['marital_status'] ?? ''); ?></p>
            <p><span>عدد الأبناء:</span><br><?php echo htmlspecialchars($data['personal_data']['num_children'] ?? ''); ?></p>
            <p><span>رخصة القيادة:</span><br><?php echo htmlspecialchars($data['personal_data']['driving_license'] ?? ''); ?></p>
            <p><span>تاريخ التوظيف:</span><br><?php echo htmlspecialchars($data['personal_data']['hire_date'] ?? ''); ?></p>
        </div>

        <div class="card">
            <h3>المؤهلات والخبرة</h3>
            <p><span>الخبرة:</span><br><?php echo htmlspecialchars($data['qualifications']['experience'] ?? ''); ?></p>
            <p><span>الدرجة العلمية:</span><br><?php echo htmlspecialchars($data['qualifications']['degree'] ?? ''); ?></p>
            <p><span>التخصص:</span><br><?php echo htmlspecialchars($data['qualifications']['major'] ?? ''); ?></p>
            <p><span>الجامعة:</span><br><?php echo htmlspecialchars($data['qualifications']['university'] ?? ''); ?></p>
            <p><span>سنة التخرج:</span><br><?php echo htmlspecialchars($data['qualifications']['graduation_year'] ?? ''); ?></p>
            <p><span>الدورات:</span><br><?php echo htmlspecialchars($data['qualifications']['courses'] ?? ''); ?></p>
            <p><span>المهارات:</span><br><?php echo htmlspecialchars($data['qualifications']['skills'] ?? ''); ?></p>
        </div>

        <div class="card">
            <h3>التواصل والحسابات</h3>
            <p><span>هاتف جوال:</span><br><?php echo htmlspecialchars($data['contact_info']['phone_mobile'] ?? ''); ?></p>
            <p><span>هاتف منزل:</span><br><?php echo htmlspecialchars($data['contact_info']['phone_home'] ?? ''); ?></p>
            <p><span>هاتف طوارئ:</span><br><?php echo htmlspecialchars($data['contact_info']['emergency_phone'] ?? ''); ?></p>
            <p><span>العنوان في قطر:</span><br><?php echo htmlspecialchars($data['contact_info']['address_qatar'] ?? ''); ?></p>
            <p><span>العنوان في المنزل:</span><br><?php echo htmlspecialchars($data['contact_info']['address_home'] ?? ''); ?></p>
            <p><span>حساب التيمز:</span><br><?php echo htmlspecialchars($data['accounts']['teams_account'] ?? ''); ?></p>
            <p><span>برنامج الغياب:</span><br><?php echo htmlspecialchars($data['accounts']['attendance_platform'] ?? ''); ?></p>
        </div>

        <div class="card">
            <h3>الوظيفة</h3>
            <p><span>المسمى الوظيفي:</span><br><?php echo htmlspecialchars($data['personal_data']['job_title'] ?? ''); ?></p>
            <p><span>القسم:</span><br><?php echo htmlspecialchars($data['personal_data']['department'] ?? ''); ?></p>
            <p><span>الراتب الأساسي:</span><br><?php echo htmlspecialchars($data['job_data']['base_salary'] ?? 0); ?></p>
            <p><span>البدلات:</span><br><?php echo htmlspecialchars($data['job_data']['allowances'] ?? 0); ?></p>
            <p><span>نوع العقد:</span><br><?php echo htmlspecialchars($data['job_data']['contract_type'] ?? ''); ?></p>
            <p><span>الحساب البنكي:</span><br><?php echo htmlspecialchars($data['contact_info']['bank_account'] ?? ''); ?></p>
        </div>
    </div>

    <!-- Admin Section -->
    <div class="section" id="adminSection" style="display:none;">

        <div class="card">
            <h3>الإجازات</h3>
            <p><span>عارضة:</span><br><?php echo htmlspecialchars($data['admin_data']['leave_casual'] ?? 0); ?></p>
            <p><span>مرضية:</span><br><?php echo htmlspecialchars($data['admin_data']['leave_sick'] ?? 0); ?></p>
            <p><span>بدون عذر:</span><br><?php echo htmlspecialchars($data['admin_data']['leave_unexcused'] ?? 0); ?></p>
        </div>

        <div class="card">
            <h3>العهدات</h3>
            <p><span>قلم:</span><br><?php echo htmlspecialchars($data['admin_data']['assets_pen'] ?? ''); ?></p>
            <p><span>حاسوب:</span><br><?php echo htmlspecialchars($data['admin_data']['assets_pc'] ?? ''); ?></p>
            <p><span>هاتف:</span><br><?php echo htmlspecialchars($data['admin_data']['assets_phone'] ?? ''); ?></p>
            <p><span>سيارة:</span><br><?php echo htmlspecialchars($data['admin_data']['assets_car'] ?? ''); ?></p>
        </div>

        <div class="card">
            <h3>الإنذارات والملاحظات</h3>
            <p><span>تنبيه شفوي:</span><br><?php echo htmlspecialchars($data['admin_data']['note_verbal'] ?? ''); ?></p>
            <p><span>تنبيه كتابي:</span><br><?php echo htmlspecialchars($data['admin_data']['note_written'] ?? ''); ?></p>
            <p><span>إنذار:</span><br><?php echo htmlspecialchars($data['admin_data']['note_warning'] ?? ''); ?></p>
            <p><span>خصم (أيام):</span><br><?php echo htmlspecialchars($data['admin_data']['note_discount'] ?? ''); ?></p>
            <p><span>حالة المدرس:</span><br><?php echo htmlspecialchars($data['admin_data']['teacher_status'] ?? ''); ?></p>
        </div>

        <div class="card">
            <h3>حصص النصاب</h3>
            <p><span>حصص الاحتياط:</span><br><?php echo htmlspecialchars($data['admin_data']['classes_reserve'] ?? 0); ?></p>
            <p><span>حصص زائدة:</span><br><?php echo htmlspecialchars($data['admin_data']['classes_extra'] ?? 0); ?></p>
            <p><span>حصص فوق النصاب:</span><br><?php echo htmlspecialchars($data['admin_data']['classes_overload'] ?? 0); ?></p>
        </div>

        <!-- التقديرات السنوية -->
       <div class="card">
    <h3>التقديرات السنوية</h3>
    <?php
    $stmt_notes = $conn->prepare("SELECT * FROM annual_notes WHERE employee_id=? ORDER BY year DESC");
    $stmt_notes->bind_param("s", $employee_id);
    $stmt_notes->execute();
    $res_notes = $stmt_notes->get_result();

    while($row = $res_notes->fetch_assoc()){
        $year = $row['year'];
        $rating = htmlspecialchars($row['rating']);
        $note = htmlspecialchars($row['manual_note']);
        echo "<button class='year-btn' onclick='alert(\"السنة: $year\\nالتقدير: $rating\\nالملاحظة: $note\")'>السنة: $year</button>";
    }

    $stmt_notes->close();
    ?>
</div>

    </div>

<script>
function closeModal(){
    const modal = document.querySelector('.modal');
    if(modal) modal.remove();
}

let personalBtn = document.getElementById('btnPersonal');
let adminBtn = document.getElementById('btnAdmin');
let personalSection = document.getElementById('personalSection');
let adminSection = document.getElementById('adminSection');

personalBtn.addEventListener('click', ()=>{
    personalSection.style.display='block';
    adminSection.style.display='none';
    personalBtn.classList.add('active'); personalBtn.classList.remove('inactive');
    adminBtn.classList.add('inactive'); adminBtn.classList.remove('active');
});

adminBtn.addEventListener('click', ()=>{
    adminSection.style.display='block';
    personalSection.style.display='none';
    adminBtn.classList.add('active'); adminBtn.classList.remove('inactive');
    personalBtn.classList.add('inactive'); personalBtn.classList.remove('active');
});

function printPage() {
    personalSection.style.display='block';
    adminSection.style.display='block';
    window.print();
    personalSection.style.display='block';
    adminSection.style.display='none';
}

function downloadExcel() {
    let tableHTML = "<table border='1' style='border-collapse:collapse;'><tr><th colspan='2'>البيانات الشخصية</th></tr>";
    document.querySelectorAll("#personalSection .card").forEach(card=>{
        card.querySelectorAll("p").forEach(p=>{
            let key = p.querySelector("span").innerText;
            let val = p.innerText.replace(key,'').trim();
            tableHTML += "<tr><td>"+key+"</td><td>"+val+"</td></tr>";
        });
    });
    tableHTML += "<tr><th colspan='2'>البيانات الإدارية</th></tr>";
    document.querySelectorAll("#adminSection .card").forEach(card=>{
        card.querySelectorAll("p").forEach(p=>{
            let key = p.querySelector("span").innerText;
            let val = p.innerText.replace(key,'').trim();
            tableHTML += "<tr><td>"+key+"</td><td>"+val+"</td></tr>";
        });
    });
    tableHTML += "</table>";
    let teacherName = "<?php echo htmlspecialchars($data['personal_data']['full_name'] ?? ''); ?>".replace(/\s+/g,'_');
    let blob = new Blob([tableHTML], {type:'application/vnd.ms-excel'});
    let link = document.createElement("a");
    link.href = URL.createObjectURL(blob);
    link.download = teacherName + ".xls";
    link.click();
}

// فتح السنة بطريقة آمنة بدون تهنيج
function openYearModal(id) {
    const modalContent = document.getElementById(id).querySelector('.year-modal-content');
    const yearTitle = modalContent.querySelector('h3').innerText;
    const rating = modalContent.querySelector('p:nth-of-type(1)').innerText;
    const note = modalContent.querySelector('p:nth-of-type(2)').innerText;
    alert(yearTitle + "\n" + rating + "\n" + note);
}
</script>

<style>
.modal { 
    display:block; position:fixed; top:0; left:0; 
    width:100%; height:100%; background: rgba(0,0,0,0.6); 
    z-index:9999; overflow-y:auto; padding:30px 0;
}
.modal-content { 
    background:#fff; width:95%; max-width:1200px; margin:20px auto; 
    padding:35px; border-radius:15px; box-shadow:0 10px 30px rgba(0,0,0,0.3); 
    font-family: Arial, sans-serif;
}
.modal-close { 
    position:absolute; top:15px; right:20px; cursor:pointer; 
    font-size:28px; color:#8A1538; transition:0.2s; 
}
.modal-close:hover { color:#a00000; transform:rotate(90deg); }
.modal-header{ display:flex; align-items:center; gap:30px; margin-bottom:30px;
    border-bottom:2px solid #f0c0d0; padding-bottom:15px;
}
.modal-header img { width:130px; height:130px; border-radius:50%; border:3px solid #8A1538; object-fit:cover; }
.modal-header .header-info h2 { color:#8A1538; margin:0 0 10px 0; font-size:26px; font-weight:bold; }
.modal-header .header-info p { margin:5px 0; font-size:15px; }
.tab-btn { padding:12px 28px; margin:0 5px; border:none; border-radius:10px; cursor:pointer; font-weight:bold; transition:0.3s; font-size:15px; }
.tab-btn.active { background:#8A1538; color:#fff; }
.tab-btn.inactive { background:#e0e0e0; color:#333; }
.tab-btn:hover { opacity:0.85; }
.action-btn { padding:10px 20px; margin:0 5px 10px 5px; font-weight:bold; cursor:pointer; border:none; border-radius:8px; background:#8A1538; color:#fff; transition:0.3s; }
.action-btn:hover { opacity:0.85; }
.section { display:flex; flex-direction:column; gap:25px; }
.card { background:#fdf0f3; padding:25px; border-radius:15px; box-shadow:0 5px 25px rgba(0,0,0,0.15); margin-bottom:25px; }
.card h3 { color:#8A1538; margin-bottom:15px; font-size:20px; font-weight:bold; border-bottom:1px solid #f0c0d0; padding-bottom:7px; }
.card p { font-weight:bold; margin:12px 0; padding:8px; background:#fff; border-radius:8px; box-shadow:0 2px 8px rgba(0,0,0,0.05); }
.card p span { font-weight:bold; color:#555; display:block; margin-bottom:3px; }

/* أزرار السنوات */
.year-btn {
    background:#8A1538; color:#fff; border:none; border-radius:8px;
    padding:8px 15px; margin:5px 5px 10px 0; cursor:pointer; font-weight:bold;
    transition:0.3s;
}
.year-btn:hover { opacity:0.85; }

@media print {
    body * { visibility: hidden; }
    .modal, .modal * { visibility: visible; }
    .modal { position:absolute; top:0; left:0; width:100%; height:auto !important; overflow:visible !important; padding:0 !important; }
    .modal-content { padding:0 !important; overflow:visible !important; width:100% !important; box-shadow:none !important; border-radius:0 !important; }
    #personalSection, #adminSection { display:block !important; }
    .card { page-break-inside: avoid !important; page-break-after:auto !important; background:#fff !important; box-shadow:none !important; border-radius:0 !important; }
    .modal-close, .tab-btn, .action-btn, .year-btn { display:none !important; }
}
</style>