<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// فقط مدیر A و سوپر یوزر دسترسی دارند
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// دریافت داده‌ها
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$staff_id = $data['staff_id'] ?? null;
$self_id = $data['self_id'] ?? null;

if (!$id || !$staff_id || !$self_id) {
    echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص ارسال شده است']);
    exit();
}

// بررسی وجود assigned_staff
$stmtCheck = $pdo->prepare("SELECT * FROM assigned_staffs WHERE id = ?");
$stmtCheck->execute([$id]);
if ($stmtCheck->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'انتصاب مورد نظر وجود ندارد']);
    exit();
}

// بررسی وجود کاربر و نقشش
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'STAFF_B'");
$stmtUser->execute([$staff_id]);
if ($stmtUser->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'کاربر مسئول (STAFF_B) یافت نشد یا نقش اشتباه است']);
    exit();
}

// بررسی وجود سلف
$stmtSelf = $pdo->prepare("SELECT * FROM self_services WHERE id = ?");
$stmtSelf->execute([$self_id]);
if ($stmtSelf->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'سلف انتخاب‌شده وجود ندارد']);
    exit();
}

// جلوگیری از انتصاب تکراری فعال
$stmtDup = $pdo->prepare("SELECT id FROM assigned_staffs WHERE self_id = ? AND is_active = 1 AND id != ?");
$stmtDup->execute([$self_id, $id]);
if ($stmtDup->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'این سلف در حال حاضر مسئول فعال دارد']);
    exit();
}

try {
    // ویرایش و فعال‌سازی مجدد
    $stmt = $pdo->prepare("UPDATE assigned_staffs SET staff_id = ?, self_id = ?, is_active = 1 WHERE id = ?");
    $stmt->execute([$staff_id, $self_id, $id]);

    echo json_encode(['status' => 'success', 'message' => 'انتصاب با موفقیت ویرایش شد']);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در ویرایش انتصاب',
        'details' => $e->getMessage()
    ]);
}
