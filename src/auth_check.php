<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/config.php';

function readStudentList(): array {
    $file = DATA_DIR . 'students.csv';
    if (!file_exists($file)) return [];
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle);
    $usernameIdx = $header ? array_search('Username', $header) : false;
    if ($usernameIdx === false) { fclose($handle); return []; }
    $matrics = [];
    while (($row = fgetcsv($handle)) !== false) {
        $m = trim($row[$usernameIdx] ?? '');
        if ($m !== '') $matrics[] = $m;
    }
    fclose($handle);
    sort($matrics);
    return $matrics;
}

/**
 * Returns the full marksheet data for use in CSV export.
 * 'header'   — column names from students.csv (BOM stripped)
 * 'rows'     — associative array: matric => full row array (preserving original column order)
 * 'col'      — map of column name => index for grade-related columns
 */
function readMarksheetData(): array {
    $file = DATA_DIR . 'students.csv';
    if (!file_exists($file)) return ['header' => null, 'rows' => [], 'col' => []];
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle);
    if (!$header) { fclose($handle); return ['header' => null, 'rows' => [], 'col' => []]; }
    $header[0] = ltrim($header[0], "\xEF\xBB\xBF");  // strip UTF-8 BOM if present
    $usernameIdx = array_search('Username', $header);
    if ($usernameIdx === false) { fclose($handle); return ['header' => $header, 'rows' => [], 'col' => []]; }
    $rows = [];
    while (($row = fgetcsv($handle)) !== false) {
        $matric = trim($row[$usernameIdx] ?? '');
        if ($matric !== '') $rows[$matric] = $row;
    }
    fclose($handle);
    $col = [];
    foreach (['Grade', 'Marking workflow state', 'Last modified (grade)', 'Feedback comments'] as $name) {
        $idx = array_search($name, $header);
        if ($idx !== false) $col[$name] = $idx;
    }
    return ['header' => $header, 'rows' => $rows, 'col' => $col];
}

function buildParticipantMap(): array {
    $file = DATA_DIR . 'students.csv';
    if (!file_exists($file)) return [];
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle);
    $usernameIdx = $header ? array_search('Username', $header) : false;
    if ($usernameIdx === false) { fclose($handle); return []; }
    $map = [];
    while (($row = fgetcsv($handle)) !== false) {
        $matric = trim($row[$usernameIdx] ?? '');
        $ident  = trim($row[0] ?? '');
        if ($matric !== '' && $ident !== '') $map[$matric] = $ident;
    }
    fclose($handle);
    return $map;
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
