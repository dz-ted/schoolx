<?php
session_start();
include("db.php");

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if($user && password_verify($password, $user['password'])){
        // تسجيل الجلسة
        $_SESSION['user'] = $user['username'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['stage'] = $user['stage'] ?? '';

        $_SESSION['can_add'] = $user['can_add'] ?? 0;
        $_SESSION['can_edit'] = $user['can_edit'] ?? 0;
        $_SESSION['can_delete'] = $user['can_delete'] ?? 0;
        $_SESSION['can_admin_data'] = $user['can_admin_data'] ?? 0;

        // ✅ تحديد من يظهر له زر إضافة المشرف
        $_SESSION['is_stage_admin'] = $user['is_stage_admin'] ?? 0;

        // ✅ إضافة دعم المدير التنفيذي
        $_SESSION['is_general_manager'] = 0;
        if($user['role'] === 'general_manager'){
            $_SESSION['is_general_manager'] = 1;
        }

        // توجيه المستخدم حسب الدور
        switch($user['role']){
            case 'general_manager':
                header("Location: manager.php"); // المدير العام
                break;
            case 'primary':
            case 'middle':
            case 'secondary':
                header("Location: index.php"); // صفحة المدير لكل مرحلة
                break;
            case 'supervisor':
                header("Location: index_supervisor.php"); // مشرف الموقع
                break;
            default:
                exit("ليس لديك صلاحية الدخول");
        }
        exit();
    } else {
        $error = "اسم المستخدم أو كلمة المرور غير صحيحة";
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8">
<title>تسجيل الدخول</title>
<style>
body { font-family: 'Almarai', sans-serif; background: linear-gradient(to right, #8A1538, #fff); display:flex; justify-content:center; align-items:center; height:100vh; margin:0; }
form { background:#fff; padding:25px; border-radius:12px; box-shadow:0 4px 10px rgba(0,0,0,0.2); display:flex; flex-direction:column; gap:15px; width:320px; }
input[type="text"], input[type="password"] { padding:10px; border-radius:8px; border:1px solid #ccc; font-size:16px; width:100%; }
button { background:#8A1538; color:#fff; padding:10px; border:none; border-radius:8px; font-weight:bold; cursor:pointer; transition:0.3s; }
button:hover { background:#a00000; }
p.error { color:red; text-align:center; font-weight:bold; }
</style>
</head>
<body>
<form method="POST">
    <h2 style="text-align:center; color:#004080;">تسجيل الدخول</h2>
    <input type="text" name="username" placeholder="اسم المستخدم" required>
    <input type="password" name="password" placeholder="كلمة المرور" required>
    <button type="submit">دخول</button>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
</form>
</body>
</html>