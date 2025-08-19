<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط ADMIN_A یا SUPERUSER مجاز هستند
if ($_SESSION['role'] !== 'ADMIN_A' && $_SESSION['role'] !== 'SUPERUSER') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// دریافت داده‌ها
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه سلف ارسال نشده است']);
    exit();
}

// بررسی وجود سلف
$check = $pdo->prepare("SELECT id FROM self_services WHERE id = ?");
$check->execute([$id]);

if ($check->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'سلفی با این شناسه یافت نشد']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE self_services SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'سلف با موفقیت غیرفعال شد']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در غیرفعال‌سازی سلف', 'details' => $e->getMessage()]);
}
?>
