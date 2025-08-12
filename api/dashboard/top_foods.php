<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

$allowed = ['SUPERUSER','ADMIN_A','STAFF_B'];
if (!in_array($role, $allowed)) {
    echo json_encode(['status'=>'error','message'=>'شما دسترسی ندارید']); exit();
}

$input = json_decode(file_get_contents("php://input"), true);
$limit = isset($input['limit']) ? (int)$input['limit'] : 3;
$days  = isset($input['days'])  ? (int)$input['days']  : null;

$timeCond = "";
$params = [];

if ($days !== null) {
    $timeCond = " AND r.created_at >= NOW() - INTERVAL ? DAY";
    $params[] = $days;
}

try {
    if ($role === 'STAFF_B') {
        $sql = "
          SELECT f.name, COUNT(*) AS reservation_count
          FROM reservations r
          JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
          JOIN foods f ON dfp.food_id = f.id
          JOIN assigned_staffs ast 
            ON ast.self_id = dfp.self_id 
           AND ast.staff_id = ? 
           AND ast.is_active = 1
          WHERE 1=1 $timeCond
          GROUP BY f.name
          ORDER BY reservation_count DESC
          LIMIT $limit
        ";
        array_unshift($params, $user_id);
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        $sql = "
          SELECT f.name, COUNT(*) AS reservation_count
          FROM reservations r
          JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
          JOIN foods f ON dfp.food_id = f.id
          WHERE 1=1 $timeCond
          GROUP BY f.name
          ORDER BY reservation_count DESC
          LIMIT $limit
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }

    echo json_encode(['status'=>'success','data'=>$stmt->fetchAll()]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'خطا در دریافت غذاهای پرطرفدار','details'=>$e->getMessage()]);
}
