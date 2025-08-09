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

if ($_SESSION['role'] !== 'STUDENT') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$reservation_id = $data['reservation_id'] ?? null;

if (!$reservation_id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه رزرو ارسال نشده است']);
    exit();
}

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        SELECT r.id, r.daily_food_plan_id, dfp.date, dfp.meal_type, f.price,
               dfp.count, f.is_available, ss.is_active AS self_active
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        JOIN foods f ON dfp.food_id = f.id
        JOIN self_services ss ON dfp.self_id = ss.id
        WHERE r.id = ? AND r.student_id = ? AND r.status = 'reserved'
        FOR UPDATE
    ");
    $stmt->execute([$reservation_id, $_SESSION['user_id']]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'رزرو یافت نشد یا قبلاً لغو شده']);
        exit();
    }

    $mealTimeMap = [
        'breakfast' => '08:00:00',
        'lunch'     => '13:00:00',
        'dinner'    => '19:00:00'
    ];
    $mealTime = $mealTimeMap[$reservation['meal_type']] ?? '12:00:00';
    $reservationDateTime = new DateTime($reservation['date'] . ' ' . $mealTime);
    $now = new DateTime();
    $hoursDiff = ($reservationDateTime->getTimestamp() - $now->getTimestamp()) / 3600;

    if ($hoursDiff < 12) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'فقط تا ۱۲ ساعت قبل از وعده مجاز به لغو هستید.']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$reservation_id]);

    $stmt = $pdo->prepare("UPDATE daily_food_plans SET count = count + 1 WHERE id = ?");
    $stmt->execute([$reservation['daily_food_plan_id']]);

    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$reservation['price'], $_SESSION['user_id']]);

    $mealFa = mealTypeToFarsi($reservation['meal_type']);
    $dateFa = toPersianNumbers($reservation['date']);
    $desc = "برگشت مبلغ به‌خاطر کنسل رزرو غذا برای تاریخ {$dateFa} وعده {$mealFa}";

    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description, created_at) VALUES (?, 'increase', ?, ?, NOW())");
    $stmt->execute([$_SESSION['user_id'], $reservation['price'], $desc]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'رزرو لغو شد و مبلغ بازگردانده شد']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => 'خطا در لغو رزرو', 'details' => $e->getMessage()]);
}
