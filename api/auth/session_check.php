<?php
require_once '../bootstrap.php';
require_once '../db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'دسترسی غیرمجاز یا سشن منقضی شده'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT id, username, name, role, balance, created_at, is_active FROM users WHERE id = :id AND is_active = 1");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode([
        'status' => 'error',
        'message' => 'کاربر یافت نشد یا غیرفعال است.'
    ]);
    exit;
}

echo json_encode([
    'status' => 'success',
    'user_id' => $user['id'],
    'username' => $user['username'],
    'name' => $user['name'],
    'role' => $user['role'],
    'balance' => $user['balance'],
    'created_at' => $user['created_at'],
    'is_active' => $user['is_active'],
]);
