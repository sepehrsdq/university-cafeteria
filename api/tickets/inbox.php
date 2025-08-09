<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT 
            t.id,
            t.status,
            t.created_at,
            sender.name AS sender_name
        FROM tickets t
        LEFT JOIN users sender ON t.sender_id = sender.id
        WHERE t.receiver_id = ?
        ORDER BY t.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $tickets = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $tickets]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت تیکت‌ها',
        'details' => $e->getMessage()
    ]);
}
