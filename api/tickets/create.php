<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$sender_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

$data = json_decode(file_get_contents("php://input"), true);

$subject = $data['subject'] ?? null;
$receiver_id = $data['receiver_id'] ?? null; // ممکن است null باشد
$first_message = $data['message'] ?? null;

if (!$subject || !$first_message) {
    echo json_encode([
        'status' => 'error',
        'message' => 'موضوع یا پیام وارد نشده است'
    ]);
    exit();
}

// اگر دانشجو است و receiver_id خالی است → ممنوع
if ($role === 'STUDENT' && empty($receiver_id)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'دانشجو نمی‌تواند تیکت بدون گیرنده ثبت کند'
    ]);
    exit();
}

// بررسی گیرنده
if (!empty($receiver_id)) {
    // گرفتن نقش گیرنده
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$receiver_id]);
    $receiver = $stmt->fetch();

    if (!$receiver) {
        echo json_encode([
            'status' => 'error',
            'message' => 'گیرنده وجود ندارد یا غیرفعال است'
        ]);
        exit();
    }

    // اگر فرستنده دانشجو باشد → نباید گیرنده هم دانشجو باشد
    if ($role === 'STUDENT' && $receiver['role'] === 'STUDENT') {
        echo json_encode([
            'status' => 'error',
            'message' => 'دانشجو نمی‌تواند به دانشجوی دیگر تیکت ارسال کند'
        ]);
        exit();
    }
}

try {
    $pdo->beginTransaction();

    // ساخت تیکت
    $stmt = $pdo->prepare("INSERT INTO tickets (sender_id, receiver_id, subject) VALUES (?, ?, ?)");
    $stmt->execute([$sender_id, $receiver_id, $subject]);
    $ticket_id = $pdo->lastInsertId();

    // پیام اولیه
    $stmtMsg = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
    $stmtMsg->execute([$ticket_id, $sender_id, $first_message]);

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'تیکت با موفقیت ثبت شد',
        'ticket_id' => $ticket_id
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در ثبت تیکت',
        'details' => $e->getMessage()
    ]);
}
