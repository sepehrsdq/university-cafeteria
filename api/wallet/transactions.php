<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, type, amount, description, created_at FROM wallet_transactions WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $transactions = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $transactions
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت لیست تراکنش‌ها',
        'details' => $e->getMessage()
    ]);
}
?>
