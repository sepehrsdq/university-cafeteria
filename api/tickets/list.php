<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role    = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if (!$role || !$user_id) {
    echo json_encode(['status' => 'error', 'message' => 'نشست نامعتبر است']);
    exit();
}

try {
    if ($role === 'SUPERUSER') {
        // سوپر یوزر همه تیکت‌ها را می‌بیند
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.subject,
                t.status,
                t.created_at,
                t.sender_id,
                t.receiver_id,                -- برای تشخیص عمومی (null)
                u1.name AS sender_name,
                u1.role AS sender_role,       -- برای فیلتر تب‌ها
                u2.name AS receiver_name,
                u2.role AS receiver_role
            FROM tickets t
            LEFT JOIN users u1 ON t.sender_id = u1.id
            LEFT JOIN users u2 ON t.receiver_id = u2.id
            ORDER BY t.created_at DESC
        ");
        $stmt->execute();
    } elseif ($role === 'ADMIN_A' || $role === 'STAFF_B') {
        // مدیر A و مسئول B: هم ارسال‌شده‌ها هم دریافت‌شده‌ها
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.subject,
                t.status,
                t.created_at,
                t.sender_id,
                t.receiver_id,
                u1.name AS sender_name,
                u1.role AS sender_role,
                u2.name AS receiver_name,
                u2.role AS receiver_role
            FROM tickets t
            LEFT JOIN users u1 ON t.sender_id = u1.id
            LEFT JOIN users u2 ON t.receiver_id = u2.id
            WHERE t.sender_id = ? OR t.receiver_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
    } elseif ($role === 'STUDENT') {
        // دانشجو: هم ارسال‌شده‌ها هم دریافت‌شده‌ها
        // (تا اعلان‌های عمومی/تک‌به‌تک سوپر یوزر را هم در Inbox ببیند)
        $stmt = $pdo->prepare("
            SELECT 
                t.id,
                t.subject,
                t.status,
                t.created_at,
                t.sender_id,
                t.receiver_id,
                u1.name AS sender_name,
                u1.role AS sender_role,
                u2.name AS receiver_name,
                u2.role AS receiver_role
            FROM tickets t
            LEFT JOIN users u1 ON t.sender_id = u1.id
            LEFT JOIN users u2 ON t.receiver_id = u2.id
            WHERE t.sender_id = ? OR t.receiver_id = ?
            ORDER BY t.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'نقش شما مجاز به مشاهده تیکت‌ها نیست']);
        exit();
    }

    $tickets = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'me_id'  => $user_id,  // برای کلاینت جهت تشخیص سریع
        'data'   => $tickets
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'خطا در دریافت لیست تیکت‌ها',
        'details' => $e->getMessage()
    ]);
}