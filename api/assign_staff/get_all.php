<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط نقش‌های مجاز
$allowed_roles = ['SUPERUSER', 'ADMIN_A'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'شما اجازه مشاهده این اطلاعات را ندارید']);
    exit();
}

try {
    $stmt = $pdo->query("
        SELECT a.id, a.staff_id, u.name AS staff_name, a.self_id, s.name AS self_name, a.is_active
        FROM assigned_staffs a
        JOIN users u ON a.staff_id = u.id
        JOIN self_services s ON a.self_id = s.id
    ");
    $result = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $result]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در واکشی اطلاعات', 'details' => $e->getMessage()]);
}
