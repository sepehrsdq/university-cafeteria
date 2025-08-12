<?php

require_once '../bootstrap.php';
require_once '../db.php';
require_once '../auth/auth.php';

$role = $_SESSION['role'];
$session_user_id = $_SESSION['user_id'];

$user_id = $_GET['user_id'] ?? null;
$from_date = $_GET['from_date'] ?? null;
$to_date = $_GET['to_date'] ?? null;

// دانشجو فقط خودش را ببیند
if ($role === 'STUDENT') {
    $user_id = $session_user_id;
} elseif (!in_array($role, ['SUPERUSER', 'ADMIN_A','STAFF_B'])) {
    echo json_encode(['status' => 'error', 'message' => 'دسترسی غیرمجاز']);
    exit();
}

try {
    $query = "
        SELECT wt.id, u.name AS user_name, wt.amount, wt.type, wt.description, wt.created_at
        FROM wallet_transactions wt
        JOIN users u ON wt.user_id = u.id
        WHERE u.role = 'STUDENT'
    ";

    $params = [];

    if ($user_id) {
        $query .= " AND wt.user_id = ?";
        $params[] = $user_id;
    }

    if ($from_date) {
        $query .= " AND wt.created_at >= ?";
        $params[] = $from_date;
    }

   if ($to_date) {
    $query .= " AND wt.created_at <= ?";
    $params[] = $to_date . ' 23:59:59';
}

    $query .= " ORDER BY wt.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'data' => $transactions]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'خطا در دریافت گزارش کیف پول',
        'details' => $e->getMessage()
    ]);
}
?>
