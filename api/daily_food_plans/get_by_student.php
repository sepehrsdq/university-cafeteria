<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

$role = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

if ($role !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

try {
    // جنسیت کاربر
    $stmt = $pdo->prepare("SELECT gender FROM users WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user || empty($user['gender'])) {
        echo json_encode(['status' => 'error', 'message' => 'جنسیت کاربر مشخص نیست']);
        exit();
    }
    $user_gender = $user['gender'];

    // پارامترهای بازه تاریخ (YYYY-MM-DD)
    $from = isset($_GET['from']) ? trim($_GET['from']) : null;
    $to   = isset($_GET['to'])   ? trim($_GET['to'])   : null;

    $isValidDate = function($d) {
        if (!$d) return false;
        $dt = DateTime::createFromFormat('Y-m-d', $d);
        return $dt && $dt->format('Y-m-d') === $d;
    };

    // اگر بازه ندادند → پیش‌فرض: 21 روز قبل تا 35 روز بعد
    if (!$isValidDate($from) && !$isValidDate($to)) {
        $from = (new DateTime('today'))->modify('-21 days')->format('Y-m-d');
        $to   = (new DateTime('today'))->modify('+35 days')->format('Y-m-d');
    }

    $params = [
        ':gender' => $user_gender,
        ':from'   => $from,
        ':to'     => $to,
    ];

    // منطق فعال/غیرفعال:
    // - گذشته: هرچه بوده (بدون شرط is_active / is_available / s.is_active)
    // - آینده و امروز: فقط آیتم‌های فعال سه‌گانه
    //
    // (dfp.date < CURDATE()) OR (dfp.date >= CURDATE() AND ...active filters...)
    $sql = "
        SELECT
            dfp.id,
            dfp.date,
            dfp.meal_type,
            dfp.count,
            f.name  AS food_name,
            s.name  AS self_name,
            f.price AS price
        FROM daily_food_plans dfp
        JOIN foods f ON dfp.food_id = f.id
        JOIN self_services s ON dfp.self_id = s.id
        WHERE
            dfp.date BETWEEN :from AND :to
            AND (s.gender = :gender OR s.gender = 'both')
            AND (
                dfp.date < CURDATE()
                OR (
                    dfp.date >= CURDATE()
                    AND dfp.is_active = 1
                    AND f.is_available = 1
                    AND s.is_active = 1
                )
            )
        ORDER BY dfp.date ASC, dfp.meal_type
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $data]);
} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'خطا در دریافت برنامه غذایی',
        'details' => $e->getMessage()
    ]);
}
