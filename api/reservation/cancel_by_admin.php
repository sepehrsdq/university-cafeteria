<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

header('Content-Type: application/json; charset=utf-8');

function toPersianNumbers($string) {
    $western_numbers = ['0','1','2','3','4','5','6','7','8','9'];
    $persian_numbers = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
    return str_replace($western_numbers, $persian_numbers, $string);
}

function mealTypeToFarsi($mealType) {
    $map = [
        'breakfast' => 'صبحانه',
        'lunch' => 'ناهار',
        'dinner' => 'شام'
    ];
    return $map[$mealType] ?? $mealType;
}

$allowed = ['SUPERUSER','ADMIN_A','STAFF_B'];
if (!in_array($_SESSION['role'], $allowed)) {
    echo json_encode(['status' => 'error','message' => 'دسترسی غیرمجاز']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$reservation_id = $data['reservation_id'] ?? null;

if (!$reservation_id) {
    echo json_encode(['status' => 'error','message' => 'شناسه رزرو ارسال نشده است']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT 
            r.id,
            r.student_id,
            r.status,
            dfp.id AS dfp_id,
            dfp.date,
            dfp.meal_type,
            f.price
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        JOIN foods f ON dfp.food_id = f.id
        WHERE r.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$reservation_id]);
    $row = $stmt->fetch();

    if (!$row) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error','message' => 'رزرو یافت نشد']);
        exit();
    }
    if ($row['status'] !== 'reserved') {
        $pdo->rollBack();
        echo json_encode(['status' => 'error','message' => 'این رزرو قابل لغو نیست']);
        exit();
    }

    $map = ['breakfast' => '08:00:00','lunch'=>'13:00:00','dinner'=>'19:00:00'];
    $mealTime = isset($map[$row['meal_type']]) ? $map[$row['meal_type']] : '12:00:00';
    $mealDT = new DateTime($row['date'].' '.$mealTime);
    $now = new DateTime();
    $hoursDiff = ($mealDT->getTimestamp() - $now->getTimestamp()) / 3600;

    if ($hoursDiff < 12) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error','message' => 'امکان لغو کمتر از ۱۲ ساعت قبل از وعده یا پس از موعد وجود ندارد']);
        exit();
    }

    $pdo->prepare("UPDATE reservations SET status='cancelled' WHERE id=?")
        ->execute([$reservation_id]);

    $pdo->prepare("UPDATE daily_food_plans SET count = count + 1 WHERE id=?")
        ->execute([$row['dfp_id']]);

    $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id=?")
        ->execute([(int)$row['price'], (int)$row['student_id']]);

    $mealFa = mealTypeToFarsi($row['meal_type']);
    $dateFa = toPersianNumbers($row['date']);
    $desc = "برگشت مبلغ به‌خاطر کنسل رزرو غذا (توسط ادمین) برای تاریخ {$dateFa} وعده {$mealFa}";

    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description, created_at) VALUES (?, 'increase', ?, ?, NOW())");
    $stmt->execute([(int)$row['student_id'], (int)$row['price'], $desc]);

    $pdo->commit();
    echo json_encode(['status' => 'success','message' => 'رزرو لغو شد و مبلغ بازگردانده شد']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error','message' => 'خطا در لغو رزرو','details' => $e->getMessage()]);
}
