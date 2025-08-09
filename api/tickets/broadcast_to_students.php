<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط سوپر یوزر
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SUPERUSER') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ورودی‌ها
$data = json_decode(file_get_contents("php://input"), true);
$subject = isset($data['subject']) ? trim($data['subject']) : null;
$message = isset($data['message']) ? trim($data['message']) : null;

if (empty($subject) || empty($message)) {
    echo json_encode(['status' => 'error', 'message' => 'موضوع و پیام الزامی است']);
    exit();
}

$sender_id = $_SESSION['user_id'];

try {
    // همه دانشجویان فعال
    $q = $pdo->prepare("SELECT id FROM users WHERE role = 'STUDENT' AND is_active = 1");
    $q->execute();
    $students = $q->fetchAll(PDO::FETCH_COLUMN);

    if (!$students || count($students) === 0) {
        echo json_encode(['status' => 'error', 'message' => 'دانشجوی فعالی یافت نشد']);
        exit();
    }

    $pdo->beginTransaction();

    // Prepared statements برای کارای تکراری
    $insTicket = $pdo->prepare("
        INSERT INTO tickets (sender_id, receiver_id, subject, status, created_at, updated_at)
        VALUES (?, ?, ?, 'open', NOW(), NOW())
    ");
    $insMsg = $pdo->prepare("
        INSERT INTO ticket_messages (ticket_id, sender_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $ok = 0;
    $fail = 0;
    foreach ($students as $studentId) {
        try {
            // ساخت تیکت
            $insTicket->execute([$sender_id, (int)$studentId, $subject]);
            $ticketId = (int)$pdo->lastInsertId();

            // پیام اول
            $insMsg->execute([$ticketId, $sender_id, $message]);
            $ok++;
        } catch (Exception $ie) {
            // اگر یکی خطا داد ادامه بدهیم
            $fail++;
            // می‌تونی لاگ کنی: error_log($ie->getMessage());
        }
    }

    $pdo->commit();

    echo json_encode([
        'status' => 'success',
        'message' => 'اعلان برای دانشجویان ارسال شد',
        'sent'    => $ok,
        'failed'  => $fail,
        'total'   => $ok + $fail
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در ارسال اعلان گروهی',
        'details' => $e->getMessage()
    ]);
}
