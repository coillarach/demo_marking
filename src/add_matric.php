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

echo json_encode(['ok' => true, 'added' => false]);
