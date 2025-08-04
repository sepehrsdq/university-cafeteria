<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// مدیریت OPTIONS برای CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// فقط SUPERUSER اجازه ساخت کاربر جدید دارد
if ($_SESSION['role'] !== 'SUPERUSER') {
    echo json_encode(['status' => 'error', 'message' => 'فقط سوپر یوزر می‌تواند کاربر جدید بسازد']);
    exit();
}

// دریافت داده‌های ورودی
$data = json_decode(file_get_contents("php://input"), true);
$username = $data['username'] ?? null;
$password = $data['password'] ?? null;
$role     = $data['role'] ?? null;
$name     = $data['name'] ?? null;

if (!$username || !$password || !$role) {
    echo json_encode(['status' => 'error', 'message' => 'نام کاربری، رمز و نقش الزامی هستند']);
    exit();
}

// هش کردن رمز عبور
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, name, is_active) VALUES (?, ?, ?, ?, 1)");
    $stmt->execute([$username, $hashed_password, $role, $name]);

    echo json_encode(['status' => 'success', 'message' => 'کاربر با موفقیت افزوده شد']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در افزودن کاربر', 'details' => $e->getMessage()]);
}
