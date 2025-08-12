<?php
require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

// فقط SUPERUSER و ADMIN_A و STAFF_B
if (!in_array($_SESSION['role'], ['SUPERUSER','ADMIN_A','STAFF_B'])) {
    echo json_encode(['status'=>'error','message'=>'دسترسی غیرمجاز']); exit();
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$limit = 20;

try {
    if ($q === '') {
        // برنگردون چیزی وقتی کوئری خالیه
        echo json_encode(['status'=>'success','data'=>[]]); exit();
    }

    $like = '%'.$q.'%';
    $currentUserId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT id, username, name, role, is_active
        FROM users
        WHERE (name LIKE ? OR username LIKE ?)
          AND id != ? -- حذف کاربر جاری از نتایج
        ORDER BY is_active DESC, id DESC
        LIMIT $limit
    ");
    $stmt->execute([$like, $like, $currentUserId]);
    $rows = $stmt->fetchAll();

    echo json_encode(['status'=>'success','data'=>$rows]);
} catch (Exception $e) {
    echo json_encode(['status'=>'error','message'=>'خطا در جستجو','details'=>$e->getMessage()]);
}
