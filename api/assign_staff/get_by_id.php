<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط SUPERUSER و ADMIN_A مجاز هستند
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// دریافت id
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه ارسال نشده است']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.staff_id,
            u.name AS staff_name,
            a.self_id,
            s.name AS self_name,
            s.type AS self_type
        FROM assigned_staffs a
        JOIN users u ON a.staff_id = u.id
        JOIN self_services s ON a.self_id = s.id
        WHERE a.id = ?
    ");
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'موردی با این شناسه یافت نشد']);
        exit();
    }

    $assignment = $stmt->fetch();

    echo json_encode(['status' => 'success', 'data' => $assignment]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت اطلاعات', 'details' => $e->getMessage()]);
}
