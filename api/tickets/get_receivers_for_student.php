<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

if ($_SESSION['role'] !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

try {
    // گرفتن ADMIN_A
    $stmt = $pdo->prepare("SELECT id, name, role FROM users WHERE role = 'ADMIN_A' AND is_active = 1");
    $stmt->execute();
    $admins = $stmt->fetchAll();

    // گرفتن STAFF_B + سلف‌ها
    $stmt = $pdo->prepare("
        SELECT u.id, u.name, u.role, ss.name AS self_name
        FROM users u
        JOIN assigned_staffs ast ON ast.staff_id = u.id AND ast.is_active = 1
        JOIN self_services ss ON ss.id = ast.self_id AND ss.is_active = 1
        WHERE u.role = 'STAFF_B' AND u.is_active = 1
        ORDER BY u.id
    ");
    $stmt->execute();
    $staffRows = $stmt->fetchAll();

    // گروه‌بندی سلف‌ها برای هر STAFF_B
    $staffs = [];
    foreach ($staffRows as $row) {
        if (!isset($staffs[$row['id']])) {
            $staffs[$row['id']] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'role' => $row['role'],
                'selfs' => []
            ];
        }
        $staffs[$row['id']]['selfs'][] = $row['self_name'];
    }

    $staffList = array_values($staffs);

    echo json_encode([
        'status' => 'success',
        'data' => [
            'admins' => $admins,
            'staffs' => $staffList
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت گیرنده‌ها',
        'details' => $e->getMessage()
    ]);
}
