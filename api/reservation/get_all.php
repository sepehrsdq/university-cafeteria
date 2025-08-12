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
        // فقط رزروهای مربوط به سلف‌های منصوب به این کارمند
        $sql = "
        SELECT 
            r.id,
            u.id AS student_id,
            u.name AS student_name,
            f.name AS food_name,
            dfp.meal_type,
            dfp.date,
            r.status,
            f.price,
            ss.name AS self_name
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        JOIN foods f ON dfp.food_id = f.id
        JOIN self_services ss ON dfp.self_id = ss.id
        JOIN users u ON r.student_id = u.id
        JOIN assigned_staffs ast 
          ON ast.self_id = dfp.self_id 
         AND ast.staff_id = ? 
         AND ast.is_active = 1
        ORDER BY dfp.date DESC, dfp.meal_type ASC
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } else {
        // قبلی برای سوپر/مدیر
        $stmt = $pdo->query("
            SELECT 
                r.id,
                u.id AS student_id,
                u.name AS student_name,
                f.name AS food_name,
                dfp.meal_type,
                dfp.date,
                r.status,
                f.price,
                ss.name AS self_name
            FROM reservations r
            JOIN users u ON r.student_id = u.id
            JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
            JOIN foods f ON dfp.food_id = f.id
            JOIN self_services ss ON dfp.self_id = ss.id
            ORDER BY dfp.date DESC, dfp.meal_type ASC
        ");
    }

    $rows = $stmt->fetchAll();
    echo json_encode(['status'=>'success','data'=>$rows]);

} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'خطا در دریافت لیست رزروها','details'=>$e->getMessage()]);
}
