<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

$targets      = load_deploy_targets();
$deployTarget = isset($targets[$currentModuleId]) ? rtrim($targets[$currentModuleId], '/\\') : '';

$archiveId = $_GET['archive'] ?? '';
if (!is_valid_archive_id($archiveId) || $deployTarget === '') {
    http_response_code(400);
    exit;
}

$archivesBase = realpath($deployTarget . '/archives');
$file         = $archivesBase !== false ? $archivesBase . DIRECTORY_SEPARATOR . $archiveId . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'students.csv' : false;
$real         = $file !== false ? realpath($file) : false;
if ($real === false || $archivesBase === false || !str_starts_with($real, $archivesBase . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    exit;
}

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $currentModuleId . '_students_' . $archiveId . '.csv"');
header('Content-Length: ' . filesize($real));
readfile($real);
exit;
