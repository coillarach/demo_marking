<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib/records.php';
require_once __DIR__ . '/lib/config_helpers.php';
require_once __DIR__ . '/lib/layout.php';

function readStudentList(): array {
    return read_marksheet_matrics(DATA_DIR . 'students.csv');
}

/**
 * Returns the full marksheet data for use in CSV export.
 * 'header'   — column names from students.csv (BOM stripped)
 * 'rows'     — associative array: matric => full row array (preserving original column order)
 * 'col'      — map of column name => index
 */
function readMarksheetData(): array {
    return read_marksheet_csv(DATA_DIR . 'students.csv');
}

function buildParticipantMap(): array {
    return read_marksheet_participant_map(DATA_DIR . 'students.csv');
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

function hasReport(): bool {
    return defined('REPORT_WEIGHT') || (MARK_WEIGHTS['report'] ?? 0) > 0;
}
