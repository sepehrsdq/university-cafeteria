<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط SUPERUSER و ADMIN_A
if (!in_array($_SESSION['role'], ['SUPERUSER','ADMIN_A'])) {
    echo json_encode(['status'=>'error','message'=>'دسترسی غیرمجاز']); exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit(); }

$data = json_decode(file_get_contents("php://input"), true);
$user_id    = isset($data['user_id']) ? (int)$data['user_id'] : 0;
$amount     = isset($data['amount']) ? (int)$data['amount'] : 0;
$type       = $data['type'] ?? ''; // increase | decrease
$description= $data['description'] ?? '';

if ($user_id <= 0 || $amount <= 0 || !in_array($type, ['increase','decrease'])) {
    echo json_encode(['status'=>'error','message'=>'ورودی نامعتبر']); exit();
}

// کاربر باید فعال باشد
$u = $pdo->prepare("SELECT id, is_active FROM users WHERE id=?");
$u->execute([$user_id]);
$user = $u->fetch();
if (!$user || (int)$user['is_active'] !== 1) {
    echo json_encode(['status'=>'error','message'=>'کاربر یافت نشد یا غیرفعال است']); exit();
}

try {
    $pdo->beginTransaction();

    // اگر کاهش است، اول چک موجودی
    if ($type === 'decrease') {
        $chk = $pdo->prepare("SELECT balance FROM users WHERE id=? FOR UPDATE");
        $chk->execute([$user_id]);
        $cur = $chk->fetch();
        if (!$cur) { throw new Exception('کاربر یافت نشد'); }
        $balance = (int)$cur['balance'];
        if ($balance < $amount) {
            $pdo->rollBack();
            echo json_encode(['status'=>'error','message'=>'موجودی کاربر کافی نیست']); exit();
        }
        $stmt = $pdo->prepare("UPDATE users SET balance = balance - ? WHERE id=?");
        $stmt->execute([$amount, $user_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id=?");
        $stmt->execute([$amount, $user_id]);
    }

    $ins = $pdo->prepare("INSERT INTO wallet_transactions (user_id,type,amount,description) VALUES (?,?,?,?)");
    $ins->execute([$user_id, $type, $amount, $description]);

    $pdo->commit();
    echo json_encode(['status'=>'success','message'=>'تراکنش با موفقیت ثبت شد']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status'=>'error','message'=>'خطا در تنظیم موجودی','details'=>$e->getMessage()]);
}
