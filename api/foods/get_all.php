<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط SUPERUSER و ADMIN_A دسترسی دارند
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A'&& $_SESSION['role'] !== 'STAFF_B') {
    echo json_encode(['status' => 'error', 'message' => 'شما اجازه مشاهده لیست غذا را ندارید']);
    exit();
}

try {
    $stmt = $pdo->query("SELECT id, name, price, image_url, is_available FROM foods ORDER BY id DESC");
    $foods = $stmt->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => $foods
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت لیست غذا',
        'details' => $e->getMessage()
    ]);
}
?>
