<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$allowed_roles = ['STUDENT', 'STAFF_B', 'ADMIN_A', 'SUPERUSER'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$amount = isset($data['amount']) ? (int)$data['amount'] : 0;
$description = $data['description'] ?? 'شارژ کیف پول';

if ($amount <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'مقدار شارژ نامعتبر است']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $pdo->beginTransaction();

    // افزایش موجودی در جدول users
    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$amount, $user_id]);

    // ثبت تراکنش در wallet_transactions
    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description) VALUES (?, 'increase', ?, ?)");
    $stmt->execute([$user_id, $amount, $description]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'کیف پول با موفقیت شارژ شد']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در شارژ کیف پول',
        'details' => $e->getMessage()
    ]);
}
?>
