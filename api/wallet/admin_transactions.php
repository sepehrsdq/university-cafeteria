<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

if (!in_array($_SESSION['role'], ['SUPERUSER', 'ADMIN_A'])) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT 
            wt.id,
            u.id AS user_id,
            u.name AS user_name,
            u.role,
            wt.type,
            wt.amount,
            wt.description,
            wt.created_at
        FROM wallet_transactions wt
        JOIN users u ON wt.user_id = u.id
        ORDER BY wt.created_at DESC
    ");

    $transactions = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $transactions
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت تراکنش‌ها',
        'details' => $e->getMessage()
    ]);
}
?>
