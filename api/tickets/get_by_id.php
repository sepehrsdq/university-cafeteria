<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$data = json_decode(file_get_contents("php://input"), true);
$ticket_id = $data['ticket_id'] ?? null;

$user_id = $_SESSION['user_id'] ?? null;
$role    = $_SESSION['role'] ?? null;

if (!$ticket_id || !$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه تیکت ارسال نشده یا سشن نامعتبر است']);
    exit();
}

try {
    // SUPERUSER هر تیکتی را می‌بیند، بقیه فقط تیکت‌های مشارکتی خودشان
    if ($role === 'SUPERUSER') {
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ?");
        $stmt->execute([$ticket_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tickets WHERE id = ? AND (sender_id = ? OR receiver_id = ?)");
        $stmt->execute([$ticket_id, $user_id, $user_id]);
    }
    $ticket = $stmt->fetch();

    if (!$ticket) {
        echo json_encode(['status' => 'error', 'message' => 'تیکت یافت نشد یا دسترسی ندارید']);
        exit();
    }

    // مجوز بستن: سوپر‌یوزر یا گیرنده‌ی تیکت
    $canClose = ($role === 'SUPERUSER' || (int)$ticket['receiver_id'] === (int)$user_id) ? 1 : 0;

    // پیام‌ها
    $stmt2 = $pdo->prepare("
        SELECT tm.message, tm.created_at, u.name AS sender_name
        FROM ticket_messages tm
        JOIN users u ON tm.sender_id = u.id
        WHERE tm.ticket_id = ?
        ORDER BY tm.created_at ASC
    ");
    $stmt2->execute([$ticket_id]);
    $messages = $stmt2->fetchAll();

    echo json_encode([
        'status' => 'success',
        'ticket' => [
            'id'          => (int)$ticket['id'],
            'subject'     => $ticket['subject'],
            'status'      => $ticket['status'],
            'created_at'  => $ticket['created_at'],
            'sender_id'   => (int)$ticket['sender_id'],
            'receiver_id' => $ticket['receiver_id'] !== null ? (int)$ticket['receiver_id'] : null,
            'can_close'   => $canClose, // ✅ اضافه شد
        ],
        'messages' => $messages
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت اطلاعات تیکت',
        'details' => $e->getMessage()
    ]);
}
