<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$sender_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.id, 
            t.subject,
            t.status, 
            t.receiver_id, 
            t.created_at,
            u.name AS receiver_name
        FROM tickets t
        LEFT JOIN users u ON t.receiver_id = u.id
        WHERE t.sender_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$sender_id]);
    $tickets = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $tickets
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت تیکت‌ها',
        'details' => $e->getMessage()
    ]);
}
