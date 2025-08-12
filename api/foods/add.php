<?php


require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// مدیریت OPTIONS برای CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// فقط ADMIN_A یا SUPERUSER مجاز هستند
if ($_SESSION['role'] !== 'ADMIN_A' && $_SESSION['role'] !== 'SUPERUSER') {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

// دریافت داده‌ها
$data = json_decode(file_get_contents("php://input"), true);

$name = $data['name'] ?? null;
$price = $data['price'] ?? null;
$image_url = $data['image_url'] ?? ''; // اختیاری

// اعتبارسنجی
if (!$name || !$price) {
    echo json_encode(['status' => 'error', 'message' => 'نام، نوع وعده یا قیمت وارد نشده است.']);
    exit();
}

try {
    $is_available = 1;
    $stmt = $pdo->prepare("INSERT INTO foods (name, price, image_url, is_available) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $price, $image_url, $is_available]);


    echo json_encode(['status' => 'success', 'message' => 'غذا با موفقیت اضافه شد']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'خطا در افزودن غذا', 'details' => $e->getMessage()]);
}
?>
