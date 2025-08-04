<?php
session_start();

$allowed_hosts = ['localhost', '127.0.0.1', '192.168.100.2'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$parsed = parse_url($origin);
$host = $parsed['host'] ?? '';

if (in_array($host, $allowed_hosts, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
}

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}
