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
        $sql = "
            SELECT 
                dfp.id AS daily_food_plan_id,
                f.name AS food_name,
                dfp.date,
                dfp.meal_type,
                s.name AS self_name,
                dfp.count AS remaining_count
            FROM daily_food_plans dfp
            JOIN foods f ON dfp.food_id = f.id
            JOIN self_services s ON dfp.self_id = s.id
            JOIN assigned_staffs ast 
              ON ast.self_id = dfp.self_id 
             AND ast.staff_id = ? 
             AND ast.is_active = 1
            WHERE dfp.is_active = 1
            ORDER BY dfp.date DESC, dfp.meal_type
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
    } else {
        $stmt = $pdo->query("
            SELECT 
                dfp.id AS daily_food_plan_id,
                f.name AS food_name,
                dfp.date,
                dfp.meal_type,
                s.name AS self_name,
                dfp.count AS remaining_count
            FROM daily_food_plans dfp
            JOIN foods f ON dfp.food_id = f.id
            JOIN self_services s ON dfp.self_id = s.id
            WHERE dfp.is_active = 1
            ORDER BY dfp.date DESC, dfp.meal_type
        ");
    }

    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'خطا در دریافت موجودی غذاها','details'=>$e->getMessage()]);
}
