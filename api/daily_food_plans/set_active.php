<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role    = $_SESSION['role'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

$allowed_roles = ['STAFF_B','SUPERUSER'];
if (!in_array($role, $allowed_roles, true)) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه ارسال نشده']);
    exit();
}

try {
    // رکورد باید وجود داشته باشد و در حال حاضر غیرفعال باشد
    $stmt = $pdo->prepare("
        SELECT *
        FROM daily_food_plans
        WHERE id = ? AND is_active = 0
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();

    if (!$plan) {
        echo json_encode(['status' => 'error', 'message' => 'رکورد یافت نشد یا قبلاً فعال شده']);
        exit();
    }

    // اگر نقش STAFF_B است، باید به سلفِ این رکورد دسترسی داشته باشد
    if ($role === 'STAFF_B') {
        $accessCheck = $pdo->prepare("
            SELECT 1
            FROM assigned_staffs
            WHERE staff_id = ? AND self_id = ? AND is_active = 1
            LIMIT 1
        ");
        $accessCheck->execute([$user_id, $plan['self_id']]);
        if ($accessCheck->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'شما مجاز به تغییر وضعیت این سلف نیستید']);
            exit();
        }
    }

    // فعال‌سازی
    $update = $pdo->prepare("UPDATE daily_food_plans SET is_active = 1 WHERE id = ?");
    $update->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'برنامه غذایی با موفقیت فعال شد']);

} catch (Exception $e) {
    echo json_encode([
        'status'  => 'error',
        'message' => 'خطا در فعال‌سازی',
        'details' => $e->getMessage(),
    ]);
}
