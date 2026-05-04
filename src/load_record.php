<?php
require_once __DIR__ . '/auth_check.php';

header('Content-Type: application/json');
$matric = preg_replace('/[^0-9]/', '', trim($_GET['matric'] ?? ''));

if (!preg_match('/^\d{8}$/', $matric)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid matric']);
    exit;
}

$file = DATA_DIR . $matric . '.json';
if (!file_exists($file)) {
    echo json_encode(['found' => false]);
    exit;
}

$data = json_decode(file_get_contents($file), true);
if (!is_array($data)) {
    echo json_encode(['found' => false]);
    exit;
}
$data['found'] = true;
echo json_encode($data);
