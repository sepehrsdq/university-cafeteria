<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// چک نقش‌های مجاز
$allowed_roles = ['SUPERUSER', 'ADMIN_A', 'STAFF_B', 'STUDENT'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'شما اجازه دسترسی ندارید']);
    exit();
}

// دریافت ID از URL → مثلاً ?id=5
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه غذا ارسال نشده است']);
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt->execute([$id]);
    $food = $stmt->fetch();

    if ($food) {
        echo json_encode(['status' => 'success', 'data' => $food]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'غذا یافت نشد']);
    }
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در واکشی اطلاعات غذا',
        'details' => $e->getMessage()
    ]);
}
