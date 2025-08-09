<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط سوپر یوزر و مدیر A مجاز به ویرایش غذا هستند
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'شما اجازه ویرایش غذا را ندارید']);
    exit();
}

// دریافت داده‌ها
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$name = $data['name'] ?? null;
$price = $data['price'] ?? null;
$image_url = $data['image_url'] ?? null;
$is_available = isset($data['is_available']) ? (int)$data['is_available'] : 1;

if (!$id || !$name || !$price) {
    echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
    exit();
}

try {
    $stmt = $pdo->prepare("UPDATE foods SET name = ?, price = ?, image_url = ?, is_available = ? WHERE id = ?");
    $stmt->execute([$name, $price, $image_url, $is_available, $id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'غذایی با این شناسه یافت نشد یا تغییری اعمال نشد']);
        exit();
    }

    echo json_encode(['status' => 'success', 'message' => 'اطلاعات غذا به‌روزرسانی شد']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در به‌روزرسانی غذا', 'details' => $e->getMessage()]);
}
