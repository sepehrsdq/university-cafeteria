<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط SUPERUSER و ADMIN_A مجاز به دیدن سلف خاص هستند
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'شما اجازه دسترسی ندارید']);
    exit();
}

// مدیریت OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// گرفتن ID از ورودی
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه سلف ارسال نشده است']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT id, name, type, gender, is_active FROM self_services WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row) {
        echo json_encode(['status' => 'success', 'data' => $row]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'سلف مورد نظر یافت نشد']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت اطلاعات سلف', 'details' => $e->getMessage()]);
}
?>
