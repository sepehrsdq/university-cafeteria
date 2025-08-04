<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// فقط SUPERUSER و ADMIN_A اجازه دارن اطلاعات کاربر بگیرن
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// بررسی وجود id
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه کاربر ارسال نشده است']);
    exit();
}

$id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT id, username, name, role, is_active FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if ($user) {
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'کاربر یافت نشد']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت اطلاعات کاربر', 'details' => $e->getMessage()]);
}
