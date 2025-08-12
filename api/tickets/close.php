<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$data = json_decode(file_get_contents("php://input"), true);
$ticket_id = $data['ticket_id'] ?? null;
$current_user_id = $_SESSION['user_id'];
$current_role = $_SESSION['role'];

if (!$ticket_id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه تیکت ارسال نشده است']);
    exit();
}

// فقط مدیران مجاز
$allowed_roles = ['SUPERUSER', 'ADMIN_A', 'STAFF_B'];
if (!in_array($current_role, $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'شما مجاز به بستن تیکت نیستید']);
    exit();
}

try {
    // بررسی وجود تیکت
    $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo json_encode(['status' => 'error', 'message' => 'تیکت یافت نشد']);
        exit();
    }

    if ($ticket['status'] === 'closed') {
        echo json_encode(['status' => 'error', 'message' => 'تیکت قبلاً بسته شده است']);
        exit();
    }

    // به‌روزرسانی وضعیت
    $stmt = $pdo->prepare("UPDATE tickets SET status = 'closed' WHERE id = ?");
    $stmt->execute([$ticket_id]);

    echo json_encode(['status' => 'success', 'message' => 'تیکت با موفقیت بسته شد']);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در بستن تیکت',
        'details' => $e->getMessage()
    ]);
}
