<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// بررسی نقش
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// دریافت id غذا
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه غذا ارسال نشده است']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE foods SET is_available = 0 WHERE id = ?");
    $stmt->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'غذا غیرفعال شد']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در غیرفعال‌سازی غذا', 'details' => $e->getMessage()]);
}
?>
