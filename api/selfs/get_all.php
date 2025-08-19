<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role    = $_SESSION['role']    ?? null;
$user_id = $_SESSION['user_id'] ?? null;

try {
    if ($role === 'STAFF_B') {
        // فقط سلف‌های منصوب به این کارمند (و فعال بودن انتصاب)
        $stmt = $pdo->prepare("
            SELECT ss.id, ss.name, ss.type, ss.gender, ss.is_active
            FROM self_services ss
            JOIN assigned_staffs ast 
              ON ast.self_id = ss.id 
             AND ast.staff_id = ? 
             AND ast.is_active = 1
            -- اگر می‌خوای فقط سلف‌های فعال را بده: WHERE ss.is_active = 1
            WHERE ss.is_active IN (0,1)
            ORDER BY ss.is_active DESC, ss.name ASC
        ");
        $stmt->execute([$user_id]);

    } elseif (in_array($role, ['SUPERUSER', 'ADMIN_A'])) {
        // همه سلف‌ها
        $stmt = $pdo->query("
            SELECT id, name, type, gender, is_active
            FROM self_services
            ORDER BY is_active DESC, name ASC
        ");

    } else {
        // سایر نقش‌ها (مثلاً دانشجو): فقط سلف‌های فعال
        $stmt = $pdo->query("
            SELECT id, name, type, gender, is_active
            FROM self_services
            WHERE is_active = 1
            ORDER BY name ASC
        ");
    }

    $rows = $stmt->fetchAll();
    echo json_encode(['status' => 'success', 'data' => $rows]);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'خطا در دریافت سلف‌ها',
        'details' => $e->getMessage()
    ]);
}
