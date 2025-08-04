<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$allowed_roles = ['SUPERUSER', 'ADMIN_A', 'STAFF_B'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$food_id = $data['food_id'] ?? null;
$self_id = $data['self_id'] ?? null;
$date = $data['date'] ?? null;
$meal_type = $data['meal_type'] ?? null;
$count = $data['count'] ?? null;

if (!$id || !$food_id || !$self_id || !$date || !$meal_type || !$count) {
    echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
    exit();
}

try {
    // بررسی وجود رکورد
    $stmt = $pdo->prepare("SELECT * FROM daily_food_plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();

    if (!$plan) {
        echo json_encode(['status' => 'error', 'message' => 'رکورد موردنظر یافت نشد یا غیرفعال است']);
        exit();
    }

    // بررسی فعال بودن غذا
    $foodCheck = $pdo->prepare("SELECT id FROM foods WHERE id = ? AND is_available = 1");
    $foodCheck->execute([$food_id]);
    if ($foodCheck->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'غذای انتخابی فعال نیست']);
        exit();
    }

    // بررسی فعال بودن سلف
    $selfCheck = $pdo->prepare("SELECT id FROM self_services WHERE id = ? AND is_active = 1");
    $selfCheck->execute([$self_id]);
    if ($selfCheck->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'سلف انتخابی فعال نیست']);
        exit();
    }

    // فقط STAFF_B باید دسترسی به سلف داشته باشه
    if ($role === 'STAFF_B') {
        $accessCheck = $pdo->prepare("
            SELECT 1 FROM assigned_staffs
            WHERE staff_id = ? AND self_id = ? AND is_active = 1
        ");
        $accessCheck->execute([$user_id, $self_id]);
        if ($accessCheck->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'شما مجاز به ویرایش این سلف نیستید']);
            exit();
        }
    }

    // ویرایش
    $update = $pdo->prepare("
        UPDATE daily_food_plans
        SET food_id = ?, self_id = ?, date = ?, meal_type = ?, count = ?
        WHERE id = ?
    ");
    $update->execute([$food_id, $self_id, $date, $meal_type, $count, $id]);

    echo json_encode(['status' => 'success', 'message' => 'برنامه غذایی با موفقیت ویرایش شد']);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در ویرایش برنامه',
        'details' => $e->getMessage()
    ]);
}
