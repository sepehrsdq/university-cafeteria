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
    echo json_encode(['status' => 'error', 'message' => 'فقط دانشجو می‌تواند غذا رزرو کند']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$daily_food_plan_id = $data['daily_food_plan_id'] ?? null;
$student_id = $_SESSION['user_id'];

if (!$daily_food_plan_id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه برنامه غذایی ارسال نشده است']);
    exit();
}

try {
    $pdo->beginTransaction();

    // دریافت جنسیت دانشجو
    $stmt = $pdo->prepare("SELECT gender FROM users WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$student || !$student['gender']) {
        echo json_encode(['status' => 'error', 'message' => 'جنسیت دانشجو مشخص نیست']);
        exit();
    }
    $student_gender = $student['gender'];

    // دریافت اطلاعات برنامه غذایی و سلف مرتبط
    $stmt = $pdo->prepare("
        SELECT dfp.id, dfp.count, dfp.date, dfp.meal_type, dfp.is_active,
               fd.price, fd.is_available,
               ss.is_active AS self_active, ss.gender AS self_gender
        FROM daily_food_plans dfp
        JOIN foods fd ON dfp.food_id = fd.id
        JOIN self_services ss ON dfp.self_id = ss.id
        WHERE dfp.id = ?
        FOR UPDATE
    ");
    $stmt->execute([$daily_food_plan_id]);
    $food_plan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$food_plan) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'برنامه غذایی پیدا نشد']);
        exit();
    }

    // بررسی فعال بودن و ظرفیت و دسترسی جنسیتی
    if ((int)$food_plan['is_active'] !== 1 ||
        (int)$food_plan['is_available'] !== 1 ||
        (int)$food_plan['self_active'] !== 1) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'غذا یا سلف غیرفعال است']);
        exit();
    }

    if ((int)$food_plan['count'] <= 0) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'ظرفیت این غذا تمام شده است']);
        exit();
    }

    if ($food_plan['self_gender'] !== 'both' && $food_plan['self_gender'] !== $student_gender) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'شما مجاز به رزرو غذا از این سلف نیستید']);
        exit();
    }

    $meal_datetime = new DateTime($food_plan['date']);
    $threshold = (new DateTime())->modify('+24 hours');
    if ($meal_datetime <= $threshold) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'امکان رزرو کمتر از ۲۴ ساعت قبل وجود ندارد']);
        exit();
    }

    // بررسی رزرو قبلی در همان وعده
    $stmt = $pdo->prepare("
        SELECT r.id, r.status
        FROM reservations r
        JOIN daily_food_plans dfp ON r.daily_food_plan_id = dfp.id
        WHERE r.student_id = ? AND dfp.date = ? AND dfp.meal_type = ?
        LIMIT 1
    ");
    $stmt->execute([$student_id, $food_plan['date'], $food_plan['meal_type']]);
    $existing_reservation = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_reservation) {
        if ($existing_reservation['status'] === 'reserved') {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'شما در این وعده یک رزرو فعال دارید. ابتدا آن را کنسل کنید.']);
            exit();
        } elseif ($existing_reservation['status'] === 'cancelled') {
            $stmt = $pdo->prepare("UPDATE reservations SET daily_food_plan_id = ?, status = 'reserved', created_at = NOW() WHERE id = ?");
            $stmt->execute([$daily_food_plan_id, $existing_reservation['id']]);

            $stmt = $pdo->prepare("UPDATE daily_food_plans SET count = count - 1 WHERE id = ? AND count > 0");
            $stmt->execute([$daily_food_plan_id]);
            if ($stmt->rowCount() === 0) {
                $pdo->rollBack();
                echo json_encode(['status' => 'error', 'message' => 'ظرفیت غذا پیش از ثبت رزرو تمام شده است']);
                exit();
            }

            $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
            $stmt->execute([$food_plan['price'], $student_id]);

            $mealFa = mealTypeToFarsi($food_plan['meal_type']);
            $dateFa = toPersianNumbers($food_plan['date']);
            $desc = "کسر مبلغ رزرو غذا برای تاریخ {$dateFa} وعده {$mealFa}";

            $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description, created_at) VALUES (?, 'decrease', ?, ?, NOW())");
            $stmt->execute([$student_id, $food_plan['price'], $desc]);

            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'رزرو کنسل شده دوباره فعال شد']);
            exit();
        }
    }

    // بررسی موجودی دانشجو
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ? FOR UPDATE");
    $stmt->execute([$student_id]);
    $balance = $stmt->fetchColumn();
    if ($balance === false) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'کاربر پیدا نشد']);
        exit();
    }
    if ($balance < $food_plan['price']) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'موجودی شما کافی نیست']);
        exit();
    }

    // ثبت رزرو جدید
    $stmt = $pdo->prepare("INSERT INTO reservations (student_id, daily_food_plan_id, status, created_at) VALUES (?, ?, 'reserved', NOW())");
    $stmt->execute([$student_id, $daily_food_plan_id]);

    $stmt = $pdo->prepare("UPDATE daily_food_plans SET count = count - 1 WHERE id = ? AND count > 0");
    $stmt->execute([$daily_food_plan_id]);
    if ($stmt->rowCount() === 0) {
        $pdo->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'ظرفیت غذا پیش از ثبت رزرو تمام شده است']);
        exit();
    }

    $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id = ?");
    $stmt->execute([$food_plan['price'], $student_id]);

    $mealFa = mealTypeToFarsi($food_plan['meal_type']);
    $dateFa = toPersianNumbers($food_plan['date']);
    $desc = "کسر مبلغ رزرو غذا برای تاریخ {$dateFa} وعده {$mealFa}";

    $stmt = $pdo->prepare("INSERT INTO wallet_transactions (user_id, type, amount, description, created_at) VALUES (?, 'decrease', ?, ?, NOW())");
    $stmt->execute([$student_id, $food_plan['price'], $desc]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'رزرو با موفقیت انجام شد']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در ثبت رزرو',
        'details' => $e->getMessage()
    ]);
}
