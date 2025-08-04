<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط سوپر یوزر اجازه غیرفعالسازی دارد
if ($_SESSION['role'] !== 'SUPERUSER') {
    echo json_encode(['status' => 'error', 'message' => 'شما اجازه غیرفعال کردن کاربر را ندارید']);
    exit();
}

// دریافت ورودی
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه کاربر ارسال نشده است']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'کاربری با این شناسه یافت نشد یا قبلاً غیرفعال شده']);
    } else {
        echo json_encode(['status' => 'success', 'message' => 'کاربر با موفقیت غیرفعال شد']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در غیرفعالسازی کاربر', 'details' => $e->getMessage()]);
}
