<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$allowed_roles = ['SUPERUSER', 'ADMIN_A', 'STAFF_B'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'دسترسی غیرمجاز'
    ]);
    exit();
}

try {
    // مرحله ۱: خودکار غیرفعال کردن غذاهایی که تاریخشون گذشته
    $pdo->query("
        UPDATE daily_food_plans
        SET is_active = 0
        WHERE date < CURDATE()
          AND is_active = 1
    ");

    // مرحله ۲: گرفتن لیست همه غذاها (چه فعال چه غیرفعال)
    if ($role === 'STAFF_B') {
        // فقط برنامه‌های سلف‌های خودش
        $stmt = $pdo->prepare("
            SELECT dfp.*, f.name AS food_name, s.name AS self_name
            FROM daily_food_plans dfp
            JOIN foods f ON dfp.food_id = f.id
            JOIN self_services s ON dfp.self_id = s.id
            WHERE dfp.self_id IN (
                SELECT self_id FROM assigned_staffs
                WHERE staff_id = ? AND is_active = 1
            )
            ORDER BY dfp.date DESC, dfp.meal_type
        ");
        $stmt->execute([$user_id]);
    } else {
        // ادمین‌ها و سوپریوزر همه برنامه‌ها رو می‌بینن
        $stmt = $pdo->query("
            SELECT dfp.*, f.name AS food_name, s.name AS self_name
            FROM daily_food_plans dfp
            JOIN foods f ON dfp.food_id = f.id
            JOIN self_services s ON dfp.self_id = s.id
            ORDER BY dfp.date DESC, dfp.meal_type
        ");
    }

    $data = $stmt->fetchAll();
    echo json_encode(['status' => 'success', 'data' => $data]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت اطلاعات',
        'details' => $e->getMessage()
    ]);
}
