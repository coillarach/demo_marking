<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$id   = trim($body['marker_id'] ?? '');
$pw   = $body['password'] ?? '';

if (isset(MARKERS[$id]) && password_verify($pw, MARKERS[$id]['password'])) {
    $_SESSION['marking_auth'] = true;
    $_SESSION['marker_id']    = $id;
    $_SESSION['marker_name']  = MARKERS[$id]['name'];
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Incorrect marker ID or password']);
}
