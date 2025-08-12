<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// فقط SUPERUSER و ADMIN_A اجازه دیدن لیست کاربران دارند
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A'&& $_SESSION['role'] !== 'STAFF_B') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// فیلتر role از query string (اختیاری)
$role_filter = $_GET['role'] ?? null;

try {
    if ($role_filter) {
        $stmt = $pdo->prepare("SELECT id, username, name, role, is_active FROM users WHERE role = ?");
        $stmt->execute([$role_filter]);
    } else {
        $stmt = $pdo->query("SELECT id, username, name, role, is_active FROM users");
    }

    $users = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $users]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در دریافت کاربران', 'details' => $e->getMessage()]);
}
