<?php
/**
 * اتصال به دیتابیس.
 *
 * مقادیر از متغیرهای محیطی خوانده می‌شوند تا هیچ رمزی داخل مخزن نماند:
 *   DB_HOST, DB_NAME, DB_USER, DB_PASS
 */
$host    = getenv('DB_HOST') ?: 'localhost';
$db      = getenv('DB_NAME') ?: 'university_cafeteria';
$user    = getenv('DB_USER') ?: 'root';
$pass    = getenv('DB_PASS') ?: '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (Exception $e) {
    error_log('database connection failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'database connection failed']);
    exit;
}
