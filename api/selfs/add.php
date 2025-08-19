<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط ADMIN_A یا SUPERUSER مجاز هستند
if ($_SESSION['role'] !== 'ADMIN_A' && $_SESSION['role'] !== 'SUPERUSER') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// مدیریت OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// دریافت داده‌ها
$data  = json_decode(file_get_contents("php://input"), true);
$name  = $data['name'] ?? null;
$type  = $data['type'] ?? null; // 'student' یا 'staff'
$gender = $data['gender'] ?? null; // مقدار اختیاری: 'male' | 'female' | 'both' | ...

if (!$name || !$type) {
    echo json_encode(['status' => 'error', 'message' => 'نام یا نوع سلف وارد نشده است']);
    exit();
}

try {
    // اگر gender نیومده باشه، اجازه بده DB مقدار پیش‌فرض خودش رو اعمال کنه (Backward compatible)
    if ($gender === null || $gender === '') {
        $stmt = $pdo->prepare("INSERT INTO self_services (name, type, is_active) VALUES (?, ?, 1)");
        $stmt->execute([$name, $type]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO self_services (name, type, gender, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$name, $type, $gender]);
    }

    echo json_encode(['status' => 'success', 'message' => 'سلف با موفقیت اضافه شد']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در افزودن سلف', 'details' => $e->getMessage()]);
}
?>
