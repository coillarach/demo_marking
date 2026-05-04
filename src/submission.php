<?php
require_once __DIR__ . '/auth_check.php';

$matric = preg_replace('/[^0-9]/', '', $_GET['matric'] ?? '');
$fmt    = ($_GET['fmt'] ?? 'html') === 'pdf' ? 'pdf' : 'html';

if ($matric === '') { http_response_code(400); exit; }

$file = SUBMISSIONS_DIR . $matric . '.' . $fmt;
$real = realpath($file);
$base = realpath(SUBMISSIONS_DIR);

if ($real === false || $base === false || !str_starts_with($real, $base . DIRECTORY_SEPARATOR)) {
    http_response_code(404); exit;
}

header('Content-Type: ' . ($fmt === 'pdf' ? 'application/pdf' : 'text/html; charset=utf-8'));
header('Content-Length: ' . filesize($real));
readfile($real);
exit;
