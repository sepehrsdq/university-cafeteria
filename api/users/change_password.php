<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

// دریافت ورودی
$data = json_decode(file_get_contents("php://input"), true);
$old_password = $data['old_password'] ?? null;
$new_password = $data['new_password'] ?? null;

if (!$old_password || !$new_password) {
    echo json_encode(['status' => 'error', 'message' => 'رمز عبور قبلی یا جدید وارد نشده است']);
    exit();
}

// جدول هدف رو مشخص کن (users)
$table = 'users';

try {
    // گرفتن رمز فعلی
    $stmt = $pdo->prepare("SELECT password FROM $table WHERE id = ? AND is_active = 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($old_password, $user['password'])) {
        echo json_encode(['status' => 'error', 'message' => 'رمز فعلی اشتباه است']);
        exit();
    }

    // هش کردن و ذخیره رمز جدید
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $update = $pdo->prepare("UPDATE $table SET password = ? WHERE id = ?");
    $update->execute([$hashed_password, $user_id]);

    echo json_encode(['status' => 'success', 'message' => 'رمز عبور با موفقیت تغییر یافت']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در تغییر رمز عبور', 'details' => $e->getMessage()]);
}
