<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

function readStudentList(): array {
    $file = DATA_DIR . 'students.txt';
    if (!file_exists($file)) return [];
    $lines   = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $matrics = array_values(array_filter(
        array_map('trim', $lines),
        fn($l) => $l !== '' && $l[0] !== '#'
    ));
    sort($matrics);
    return $matrics;
}

if (empty($_SESSION['marking_auth'])) {
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
               (!empty($_SERVER['CONTENT_TYPE']) &&
                str_contains($_SERVER['CONTENT_TYPE'], 'application/json'));
    if ($isAjax) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Session expired']);
        exit;
    }
    header('Location: login.php');
    exit;
}

$currentMarkerId   = $_SESSION['marker_id']   ?? '';
$currentMarkerName = $_SESSION['marker_name'] ?? '';
