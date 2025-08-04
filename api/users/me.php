<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

try {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];

    $stmt = $pdo->prepare("SELECT id, username, name, role, balance, is_active FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'کاربر یافت نشد']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت اطلاعات کاربر', 'details' => $e->getMessage()]);
}
?>
