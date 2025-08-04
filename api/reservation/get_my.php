<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

if ($_SESSION['role'] !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

try {
    $stmt = $pdo->prepare("
        SELECT r.id AS reservation_id,
               r.status,
               dfp.date,
               dfp.meal_type AS type,
               f.name AS food_name,
               f.price,
               ss.name AS self_name
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        JOIN foods f ON dfp.food_id = f.id
        JOIN self_services ss ON dfp.self_id = ss.id
        WHERE r.student_id = ?
        ORDER BY dfp.date DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $reservations = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $reservations]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت لیست رزرو',
        'details' => $e->getMessage()
    ]);
}
