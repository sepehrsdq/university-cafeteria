<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// فقط SUPERUSER و ADMIN_A اجازه ویرایش دارن
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'شما اجازه ویرایش کاربر را ندارید']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$name = $data['name'] ?? null;
$username = $data['username'] ?? null;
$role = $data['role'] ?? null;
$is_active = $data['is_active'] ?? null;

if (!$id || !$username) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه یا نام کاربری وارد نشده است']);
    exit();
}

try {
    // فقط SUPERUSER می‌تونه نقش یا is_active رو تغییر بده
    $query = "UPDATE users SET username = ?, name = ?";
    $params = [$username, $name];

    if ($_SESSION['role'] === 'SUPERUSER') {
        $query .= ", role = ?, is_active = ?";
        $params[] = $role;
        $params[] = $is_active;
    }

    $query .= " WHERE id = ?";
    $params[] = $id;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    if ($stmt->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'موردی با این شناسه یافت نشد یا تغییری اعمال نشد']);
    exit();
}
    echo json_encode(['status' => 'success', 'message' => 'اطلاعات کاربر با موفقیت ویرایش شد']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در ویرایش کاربر', 'details' => $e->getMessage()]);
}
