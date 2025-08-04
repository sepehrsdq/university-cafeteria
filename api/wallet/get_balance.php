<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط دانشجو و کارمندان می‌تونن موجودی‌شون رو ببینن
$allowed_roles = ['STUDENT', 'STAFF_B', 'ADMIN_A', 'SUPERUSER'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();

    if ($result) {
        echo json_encode([
            'status' => 'success',
            'balance' => (int)$result['balance']
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'کاربر یافت نشد']);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت موجودی',
        'details' => $e->getMessage()
    ]);
}
?>
