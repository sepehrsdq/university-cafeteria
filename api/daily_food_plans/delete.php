<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

$allowed_roles = ['STAFF_B','SUPERUSER'];
if (!in_array($role, $allowed_roles)) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه ارسال نشده']);
    exit();
}

try {
    // دریافت اطلاعات سطر مورد نظر
    $stmt = $pdo->prepare("SELECT * FROM daily_food_plans WHERE id = ? AND is_active = 1");
    $stmt->execute([$id]);
    $plan = $stmt->fetch();

    if (!$plan) {
        echo json_encode(['status' => 'error', 'message' => 'رکورد یافت نشد یا قبلاً غیرفعال شده']);
        exit();
    }

    // اگر نقش STAFF_B هست، بررسی کن که به این سلف دسترسی داره یا نه
    if ($role === 'STAFF_B') {
        $accessCheck = $pdo->prepare("
            SELECT 1 FROM assigned_staffs
            WHERE staff_id = ? AND self_id = ? AND is_active = 1
        ");
        $accessCheck->execute([$user_id, $plan['self_id']]);
        if ($accessCheck->rowCount() === 0) {
            echo json_encode(['status' => 'error', 'message' => 'شما مجاز به حذف این سلف نیستید']);
            exit();
        }
    }

    // غیرفعال‌سازی
    $update = $pdo->prepare("UPDATE daily_food_plans SET is_active = 0 WHERE id = ?");
    $update->execute([$id]);

    echo json_encode(['status' => 'success', 'message' => 'برنامه غذایی با موفقیت غیرفعال شد']);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در غیرفعال‌سازی',
        'details' => $e->getMessage()
    ]);
}
