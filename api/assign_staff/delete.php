<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// بررسی نقش
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// دریافت ورودی
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه انتصاب ارسال نشده است']);
    exit();
}

// بررسی وجود رکورد
$stmt = $pdo->prepare("SELECT * FROM assigned_staffs WHERE id = ?");
$stmt->execute([$id]);
$assignment = $stmt->fetch();

if (!$assignment) {
    echo json_encode(['status' => 'error', 'message' => 'چنین انتصابی وجود ندارد']);
    exit();
}

try {
    // حذف منطقی
    $stmt = $pdo->prepare("UPDATE assigned_staffs SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'انتصاب با موفقیت غیرفعال شد']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در غیرفعال‌سازی انتصاب', 'details' => $e->getMessage()]);
}
