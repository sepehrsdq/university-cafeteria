<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

$allowed = ['SUPERUSER','ADMIN_A','STAFF_B'];
$role    = $_SESSION['role'] ?? '';
$userId  = (int)($_SESSION['user_id'] ?? 0);

if (!in_array($role, $allowed, true)) {
    echo json_encode(['status'=>'error','message'=>'دسترسی غیرمجاز'], JSON_UNESCAPED_UNICODE);
    exit();
}

try {
    // ورودی: student_id از POST (JSON)
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $studentId = (int)($data['student_id'] ?? 0);
    if ($studentId <= 0) {
        echo json_encode(['status'=>'error','message'=>'شناسه دانشجو ارسال نشده یا نامعتبر است'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // جنسیت دانشجو
    $stmt = $pdo->prepare("SELECT gender FROM users WHERE id=? LIMIT 1");
    $stmt->execute([$studentId]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$student || empty($student['gender'])) {
        echo json_encode(['status'=>'error','message'=>'جنسیت دانشجو مشخص نیست'], JSON_UNESCAPED_UNICODE);
        exit();
    }
    // نرمال‌سازی مقدار جنسیت پارامتر
    $gender = strtolower(trim((string)$student['gender'])); // male | female

    // بدنه کوئری (نکته: نرمال‌سازی سمت ستون سلف هم انجام شده)
    $sql = "
        SELECT
            dfp.id,
            dfp.date,
            dfp.meal_type,
            dfp.count,
            dfp.is_active,
            f.name          AS food_name,
            f.is_available  AS food_available,
            f.price         AS price,
            s.id            AS self_id,
            s.name          AS self_name,
            s.gender        AS self_gender,
            s.is_active     AS self_active
        FROM daily_food_plans dfp
        JOIN foods f         ON dfp.food_id = f.id
        JOIN self_services s ON dfp.self_id = s.id
        WHERE
            (
              LOWER(TRIM(s.gender)) = :g
              OR LOWER(TRIM(s.gender)) = 'both'
            )
            AND dfp.is_active = 1
            AND f.is_available = 1
            AND s.is_active = 1
            AND dfp.count > 0
            -- فقط آینده (فردا به بعد). قانون دقیق ۲۴ ساعت موقع ثبت چک می‌شود
            AND dfp.date > CURDATE()
            -- حذف گزینه‌هایی که این دانشجو برای همان «روز+وعده» رزرو فعال دارد
            AND NOT EXISTS (
                SELECT 1
                FROM reservations r
                JOIN daily_food_plans dfp2 ON dfp2.id = r.daily_food_plan_id
                WHERE r.student_id = :sid
                  AND r.status = 'reserved'
                  AND dfp2.date = dfp.date
                  AND dfp2.meal_type = dfp.meal_type
            )
    ";

    // اگر کارمند سلف است → فقط سلف‌های assign‌شده
    if ($role === 'STAFF_B') {
        $sql .= "
            AND dfp.self_id IN (
                SELECT self_id FROM assigned_staffs
                WHERE staff_id = :staff AND is_active = 1
            )
        ";
    }

    $sql .= " ORDER BY dfp.date ASC, dfp.meal_type ASC ";

    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':g',   $gender,    PDO::PARAM_STR);
    $stmt->bindValue(':sid', $studentId, PDO::PARAM_INT);
    if ($role === 'STAFF_B') {
        $stmt->bindValue(':staff', $userId, PDO::PARAM_INT);
    }
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['status'=>'success','data'=>$rows], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'خطا در دریافت برنامه غذایی',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
