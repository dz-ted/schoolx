<?php
session_start();
include("db.php");

if(!isset($_SESSION['user'])){
    header("Location: login.php");
    exit();
}

$sql = "SELECT full_name, job_title, department FROM personal_data ORDER BY full_name ASC";
$result = $conn->query($sql);

// عند الضغط على زر التصدير
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    // إعداد الترويسات
    header("Content-Type: text/csv; charset=utf-8");
    header("Content-Disposition: attachment; filename=teachers_list.csv");
    header("Pragma: no-cache");
    header("Expires: 0");

    // فتح stream مؤقت للكتابة
    $output = fopen("php://output", "w");

    // كتابة BOM لجعل Excel يتعرف على UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // كتابة العناوين
    fputcsv($output, ['اسم المدرس', 'المسمى الوظيفي', 'القسم']);

    // كتابة البيانات
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            fputcsv($output, [$row['full_name'], $row['job_title'], $row['department']]);
        }
    }

    fclose($output);
    exit();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>طباعة قائمة المدرسين</title>
<style>
body {
    font-family: "Cairo", Arial, sans-serif;
    direction: rtl;
    margin: 40px;
}
h2 {
    text-align: center;
    color: #8A1538;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 25px;
}
th, td {
    border: 1px solid #8A1538;
    padding: 10px;
    text-align: center;
    font-size: 16px;
}
th {
    background-color: #8A1538;
    color: white;
}
tr:nth-child(even) { background-color: #f9e6eb; }
tr:nth-child(odd) { background-color: #fff; }
.print-btn {
    display: inline-block;
    margin: 20px 5px;
    padding: 10px 20px;
    background: #8A1538;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}
.print-btn:hover {
    background: #a00000;
}
</style>
</head>
<body>
    <h2>قائمة المدرسين</h2>

    <div style="text-align:center;">
        <button class="print-btn" onclick="window.print()">🖨 طباعة الصفحة</button>
        <a href="?export=excel"><button class="print-btn">📥 تصدير إلى Excel</button></a>
    </div>

    <table>
        <thead>
            <tr>
                <th>اسم المدرس</th>
                <th>المسمى الوظيفي</th>
                <th>القسم</th>
            </tr>
        </thead>
        <tbody>
            <?php if($result->num_rows > 0): ?>
                <?php while($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['job_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="3">لا توجد بيانات لعرضها</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>