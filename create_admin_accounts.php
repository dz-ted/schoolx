<?php
include("db.php");

$users = [
    [
        'employee_id' => '100001',
        'username' => 'mustafa_rashwan',
        'password' => 'Admin123!'
    ],
    [
        'employee_id' => '100002',
        'username' => 'mustafa_abou',
        'password' => 'Deputy123!'
    ]
];

foreach ($users as $u) {
    $hash = password_hash($u['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("
        INSERT INTO administration_accounts 
        (employee_id, teams_account, attendance_platform, username, password)
        VALUES (?, '', '', ?, ?)
    ");
    $stmt->bind_param("sss", $u['employee_id'], $u['username'], $hash);
    $stmt->execute();
}

echo "تمت إضافة الحسابات بنجاح";