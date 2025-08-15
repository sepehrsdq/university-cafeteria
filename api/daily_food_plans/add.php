<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط نقش STAFF_B یا SUPERUSER اجازه دارد
if (!in_array($_SESSION['role'], ['STAFF_B', 'SUPERUSER'])) {
    echo json_encode(['status' => 'error', 'message' => 'فقط مسئول سلف یا سوپر یوزر اجازه ثبت برنامه را دارد']);
    exit();
}

// دریافت داده‌ها
$data = json_decode(file_get_contents("php://input"), true);
$self_id = $data['self_id'] ?? null;
$food_id = $data['food_id'] ?? null;
$date = $data['date'] ?? null;
$meal_type = $data['meal_type'] ?? null;
$count = $data['count'] ?? null;
$staff_id = $_SESSION['user_id'];

if (!$self_id || !$food_id || !$date || !$meal_type || !$count) {
    echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
    exit();
}

try {
    // چک سلف فقط برای STAFF_B انجام می‌شود، SUPERUSER آزاد است
    if ($_SESSION['role'] === 'STAFF_B') {
        $stmt = $pdo->prepare("
            SELECT 1 FROM assigned_staffs 
            WHERE self_id = ? AND staff_id = ? AND is_active = 1
        ");
        $stmt->execute([$self_id, $staff_id]);
        if ($stmt->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'شما مجاز به تعیین غذا برای این سلف نیستید']);
            exit();
        }
    }

    // بررسی فعال بودن غذا
    $stmt = $pdo->prepare("SELECT id FROM foods WHERE id = ? AND is_available = 1");
    $stmt->execute([$food_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'غذا فعال نیست یا وجود ندارد']);
        exit();
    }

    // بررسی فعال بودن سلف
    $stmt = $pdo->prepare("SELECT id FROM self_services WHERE id = ? AND is_active = 1");
    $stmt->execute([$self_id]);
    if ($stmt->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'سلف فعال نیست یا وجود ندارد']);
        exit();
    }

    // بررسی تکراری نبودن
    $stmt = $pdo->prepare("
        SELECT id FROM daily_food_plans 
        WHERE self_id = ? AND food_id = ? AND date = ? AND meal_type = ? AND is_active = 1
    ");
    $stmt->execute([$self_id, $food_id, $date, $meal_type]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'برنامه مشابه قبلاً ثبت شده است']);
        exit();
    }

    // ثبت نهایی با initial_count برابر count
    $stmt = $pdo->prepare("
        INSERT INTO daily_food_plans (
            self_id, food_id, date, meal_type, count, initial_count, is_active, created_by, created_at
        )
        VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())
    ");
    $stmt->execute([$self_id, $food_id, $date, $meal_type, $count, $count, $staff_id]);

    echo json_encode(['status' => 'success', 'message' => 'برنامه غذایی با موفقیت ثبت شد']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در ثبت برنامه غذایی', 'details' => $e->getMessage()]);
}
