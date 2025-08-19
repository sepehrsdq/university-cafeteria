<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط سوپر یوزر و مدیر A اجازه ویرایش دارند
if ($_SESSION['role'] !== 'SUPERUSER' && $_SESSION['role'] !== 'ADMIN_A') {
    echo json_encode(['status' => 'error', 'message' => 'شما اجازه ویرایش سلف را ندارید']);
    exit();
}

// OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// دریافت داده‌ها
$data = json_decode(file_get_contents("php://input"), true);

$id        = $data['id'] ?? null;
$name      = $data['name'] ?? null;
$type      = $data['type'] ?? null;
$is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
$gender    = array_key_exists('gender', $data) ? $data['gender'] : null; // اختیاری

if (!$id || !$name || !$type) {
    echo json_encode(['status' => 'error', 'message' => 'اطلاعات ناقص است']);
    exit();
}

// بررسی اینکه آیا سلف وجود دارد یا نه
$check = $pdo->prepare("SELECT id FROM self_services WHERE id = ?");
$check->execute([$id]);

if ($check->rowCount() === 0) {
    echo json_encode(['status' => 'error', 'message' => 'سلفی با این شناسه وجود ندارد']);
    exit();
}

try {
    // ساخت داینامیک SET تا اگر gender نیومد، تغییری بهش نخوره (Backward compatible)
    $fields = ["name = ?", "type = ?", "is_active = ?"];
    $values = [$name, $type, $is_active];

    if ($gender !== null && $gender !== '') {
        $fields[] = "gender = ?";
        $values[] = $gender;
    }

    $values[] = $id; // برای WHERE

    $sql = "UPDATE self_services SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);

    echo json_encode(['status' => 'success', 'message' => 'اطلاعات سلف با موفقیت به‌روزرسانی شد']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در به‌روزرسانی اطلاعات سلف', 'details' => $e->getMessage()]);
}
?>
