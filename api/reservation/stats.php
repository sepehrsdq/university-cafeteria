<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط نقش‌های مجاز
$allowed_roles = ['SUPERUSER', 'ADMIN_A', 'STAFF_B'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

$role    = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

try {
    // --- شرط فیلتر برای STAFF_B: فقط سلف‌های منصوب (active) ---
    $where  = '';
    $params = [];

    if ($role === 'STAFF_B') {
        // رزروها باید به برنامه‌های غذایی‌ای تعلق داشته باشند که self_id آن‌ها
        // در فهرست سلف‌های منصوب به کارمند فعلی باشد.
        $where  = " WHERE dfp.self_id IN (
                        SELECT self_id FROM assigned_staffs
                        WHERE staff_id = ? AND is_active = 1
                    )";
        $params = [$user_id];
    }

    // 1) آمار کلی (total)
    $sqlTotal = "
        SELECT COUNT(*) AS total
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        $where
    ";
    $stmt = $pdo->prepare($sqlTotal);
    $stmt->execute($params);
    $total = (int)($stmt->fetch()['total'] ?? 0);

    // 2) آمار بر اساس وضعیت
    $sqlByStatus = "
        SELECT r.status, COUNT(*) AS count
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        $where
        GROUP BY r.status
    ";
    $stmt = $pdo->prepare($sqlByStatus);
    $stmt->execute($params);
    $statusStats = $stmt->fetchAll();

    // 3) آمار بر اساس وعده
    $sqlByMeal = "
        SELECT dfp.meal_type, COUNT(*) AS count
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        $where
        GROUP BY dfp.meal_type
    ";
    $stmt = $pdo->prepare($sqlByMeal);
    $stmt->execute($params);
    $mealStats = $stmt->fetchAll();

    // 4) آمار روزانه ۷ روز گذشته
    //   - ابتدا شرط تاریخ را به where اضافه می‌کنیم (با رعایت پارامترها)
    $where7 = $where;
    $params7 = $params;
    $where7 .= ($where7 ? " AND " : " WHERE ") . " dfp.date >= CURDATE() - INTERVAL 7 DAY ";

    $sqlDaily = "
        SELECT dfp.date, COUNT(*) AS count
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        $where7
        GROUP BY dfp.date
        ORDER BY dfp.date ASC
    ";
    $stmt = $pdo->prepare($sqlDaily);
    $stmt->execute($params7);
    $dailyStats = $stmt->fetchAll();

    echo json_encode([
        'status'          => 'success',
        'total_reservations' => $total,
        'by_status'       => $statusStats,
        'by_meal_type'    => $mealStats,
        'last_7_days'     => $dailyStats
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'خطا در دریافت آمار',
        'details' => $e->getMessage()
    ]);
}
