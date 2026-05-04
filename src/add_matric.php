<?php
require_once __DIR__ . '/auth_check.php';

header('Content-Type: application/json');
$body   = json_decode(file_get_contents('php://input'), true);
$matric = trim($body['matric'] ?? '');

if (!preg_match('/^\d{8}$/', $matric)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid matric']);
    exit;
}

$existing = readStudentList();

if (in_array($matric, $existing)) {
    echo json_encode(['ok' => true, 'added' => false]);
    exit;
}

file_put_contents(DATA_DIR . 'students.txt', $matric . PHP_EOL, FILE_APPEND | LOCK_EX);
echo json_encode(['ok' => true, 'added' => true]);
