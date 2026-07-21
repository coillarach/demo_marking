<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

$targets      = load_deploy_targets();
$deployTarget = isset($targets[$currentModuleId]) ? rtrim($targets[$currentModuleId], '/\\') : '';
if ($deployTarget === '') { http_response_code(404); exit; }

$file = false;

if (isset($_GET['pending'])) {
    $parts = explode('/', $_GET['pending'], 2);
    if (count($parts) === 2 && is_valid_batch_id($parts[0])) {
        [$batch, $filename] = $parts;
        $base        = basename($filename);
        $pendingBase = realpath($deployTarget . '/submissions_pending');
        $candidate   = $pendingBase !== false ? realpath($pendingBase . '/' . $batch . '/' . $base) : false;
        if ($candidate !== false && $pendingBase !== false && str_starts_with($candidate, $pendingBase . DIRECTORY_SEPARATOR)) {
            $file = $candidate;
        }
    }
} elseif (isset($_GET['matric'], $_GET['ext'])) {
    $matric = preg_replace('/[^0-9]/', '', $_GET['matric']);
    $ext    = ($_GET['ext'] === 'pdf') ? 'pdf' : 'html';
    if (preg_match('/^\d{8}$/', $matric)) {
        $submissionsBase = realpath($deployTarget . '/submissions');
        $candidate       = $submissionsBase !== false ? realpath($submissionsBase . '/' . $matric . '.' . $ext) : false;
        if ($candidate !== false && $submissionsBase !== false && str_starts_with($candidate, $submissionsBase . DIRECTORY_SEPARATOR)) {
            $file = $candidate;
        }
    }
}

if ($file === false) { http_response_code(404); exit; }

$ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
if (!in_array($ext, ['html', 'pdf'], true)) { http_response_code(404); exit; }

header('Content-Type: ' . ($ext === 'pdf' ? 'application/pdf' : 'text/html; charset=utf-8'));
header('Content-Length: ' . filesize($file));
readfile($file);
exit;
