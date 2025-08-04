<?php
session_start();
session_unset();
session_destroy();
require_once '../bootstrap.php'; // اضافه کن

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'message' => 'خروج با موفقیت انجام شد'
]);
