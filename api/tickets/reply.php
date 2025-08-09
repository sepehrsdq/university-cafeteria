<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['role'] ?? null;

$data = json_decode(file_get_contents("php://input"), true);
$ticket_id = $data['ticket_id'] ?? null;
$message   = $data['message'] ?? null;

if (!$ticket_id || !$message) {
    echo json_encode(['status' => 'error','message' => 'شناسه تیکت یا متن پیام وارد نشده است']);
    exit();
}

// وجود تیکت و باز بودنش
$stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND status = 'open'");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();

if (!$ticket) {
    echo json_encode(['status' => 'error','message' => 'تیکت وجود ندارد یا بسته شده است']);
    exit();
}

// قبلاً فقط مشارکت‌کننده می‌توانست پاسخ دهد؛ الان SUPERUSER هم مجاز است
$imParticipant = ($ticket['sender_id'] == $user_id) || ($ticket['receiver_id'] == $user_id);
if (!$imParticipant && $role !== 'SUPERUSER') {
    echo json_encode(['status' => 'error','message' => 'شما مجاز به پاسخ به این تیکت نیستید']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("INSERT INTO ticket_messages (ticket_id, sender_id, message) VALUES (?, ?, ?)");
    $stmt->execute([$ticket_id, $user_id, $message]);

    // به‌روزرسانی updated_at
    $upd = $pdo->prepare("UPDATE tickets SET updated_at = NOW() WHERE id = ?");
    $upd->execute([$ticket_id]);

    $pdo->commit();
    echo json_encode(['status' => 'success','message' => 'پاسخ با موفقیت ثبت شد']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error','message' => 'خطا در ثبت پاسخ','details' => $e->getMessage()]);
}
