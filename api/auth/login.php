<?php
require_once '../bootstrap.php';
require_once '../db.php'; // مطمئن شو مسیر درسته

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// دریافت ورودی
$data = json_decode(file_get_contents("php://input"), true);

$username = $data['username'] ?? null;
$password = $data['password'] ?? null;

if (!$username || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'نام کاربری یا رمز عبور وارد نشده است.']);
    exit();
}

// جستجو در جدول users
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username AND is_active = 1");
$stmt->execute(['username' => $username]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['status' => 'error', 'message' => 'کاربر یافت نشد یا غیرفعال است.']);
    exit();
}

// بررسی رمز عبور
if (!password_verify($password, $user['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'رمز عبور اشتباه است.']);
    exit();
}

// ذخیره در سشن
$_SESSION['user_id'] = $user['id'];
$_SESSION['role'] = $user['role'];

echo json_encode(value: [
    'status' => 'success',
    'message' => 'ورود موفقیت‌آمیز',
    'user_id' => $user['id'],
    'role' => $user['role'],
    'name' => $user['name'],
    'username' => $user['username'],
    'balance' => $user['balance'],
    'created_at' => $user['created_at'],
    'is_active' => $user['is_active']
]);
