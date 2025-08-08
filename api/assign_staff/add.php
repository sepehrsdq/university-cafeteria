<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط ADMIN_A یا SUPERUSER مجاز هستند
if ($_SESSION['role'] !== 'ADMIN_A' && $_SESSION['role'] !== 'SUPERUSER') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// مدیریت OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// دریافت داده‌ها
$data = json_decode(file_get_contents("php://input"), true);
$staff_id = $data['staff_id'] ?? null;
$self_id  = $data['self_id']  ?? null;

if (!$staff_id || !$self_id) {
    echo json_encode(['status' => 'error', 'message' => 'شناسه مسئول یا سلف وارد نشده است.']);
    exit();
}

try {
    // بررسی وجود user با نقش STAFF_B
    $checkUser = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'STAFF_B' AND is_active = 1");
    $checkUser->execute([$staff_id]);
    if ($checkUser->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'کاربر مورد نظر معتبر نیست یا نقش او STAFF_B نیست.']);
        exit();
    }

    // بررسی وجود سلف و فعال بودن
    $checkSelf = $pdo->prepare("SELECT * FROM self_services WHERE id = ? AND is_active = 1");
    $checkSelf->execute([$self_id]);
    if ($checkSelf->rowCount() === 0) {
        echo json_encode(['status' => 'error', 'message' => 'سلف مورد نظر وجود ندارد یا غیرفعال است.']);
        exit();
    }

    // بررسی تکراری نبودن انتصاب فعال
    $checkDuplicate = $pdo->prepare("SELECT id FROM assigned_staffs WHERE self_id = ? AND is_active = 1");
    $checkDuplicate->execute([$self_id]);
    if ($checkDuplicate->rowCount() > 0) {
        echo json_encode(['status' => 'error', 'message' => 'برای این سلف در حال حاضر یک مسئول فعال وجود دارد.']);
        exit();
    }

    // درج در جدول (فعال پیش‌فرض)
    $stmt = $pdo->prepare("INSERT INTO assigned_staffs (staff_id, self_id, is_active) VALUES (?, ?, 1)");
    $stmt->execute([$staff_id, $self_id]);

    echo json_encode(['status' => 'success', 'message' => 'مسئول با موفقیت به سلف اختصاص یافت.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در ثبت انتصاب', 'details' => $e->getMessage()]);
}
