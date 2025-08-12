<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

$allowed = ['SUPERUSER','ADMIN_A','STAFF_B'];
if (!in_array($role, $allowed)) {
    echo json_encode(['status'=>'error','message'=>'دسترسی غیرمجاز']); exit();
}

try {
    if ($role === 'STAFF_B') {
        // مجموع رزروها و امروز، محدود به سلف‌های منصوب
        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS total_reservations
            FROM reservations r
            JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
            JOIN assigned_staffs ast 
              ON ast.self_id = dfp.self_id
             AND ast.staff_id = ?
             AND ast.is_active = 1
            WHERE r.status = 'reserved'
        ");
        $stmt->execute([$user_id]);
        $total = (int)($stmt->fetch()['total_reservations'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT COUNT(*) AS today_reservations
            FROM reservations r
            JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
            JOIN assigned_staffs ast 
              ON ast.self_id = dfp.self_id
             AND ast.staff_id = ?
             AND ast.is_active = 1
            WHERE DATE(r.created_at) = CURDATE() AND r.status = 'reserved'
        ");
        $stmt->execute([$user_id]);
        $today = (int)($stmt->fetch()['today_reservations'] ?? 0);

        echo json_encode(['status'=>'success','data'=>[
            'total_reservations' => $total,
            'today_reservations' => $today
        ]]);
    } else {
        // نسخه عمومی قبلی
        $stmt = $pdo->query("SELECT COUNT(*) AS total_reservations FROM reservations WHERE status='reserved'");
        $total = (int)($stmt->fetch()['total_reservations'] ?? 0);

        $stmt = $pdo->query("SELECT COUNT(*) AS today_reservations FROM reservations WHERE DATE(created_at)=CURDATE() AND status='reserved'");
        $today = (int)($stmt->fetch()['today_reservations'] ?? 0);

        echo json_encode(['status'=>'success','data'=>[
            'total_reservations' => $total,
            'today_reservations' => $today
        ]]);
    }
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'خطا در دریافت آمار رزروها','details'=>$e->getMessage()]);
}
