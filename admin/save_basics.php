<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

// CSRF
if (empty($_SESSION['admin_csrf']) || ($_POST['_csrf'] ?? '') !== $_SESSION['admin_csrf']) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$errors = [];

// Validate MODULE_ID
$moduleId = strtoupper(trim($_POST['module_id'] ?? ''));
if (!preg_match('/^[A-Z][A-Z0-9]{2,11}$/', $moduleId)) {
    $errors[] = 'Module ID must be 3–12 uppercase letters/digits starting with a letter.';
}

$moduleName  = trim($_POST['module_name']  ?? '');
$moduleLevel = trim($_POST['module_level'] ?? 'UG');
$passMark    = (int)($_POST['pass_mark']   ?? 0);

if ($moduleName === '') $errors[] = 'Module name is required.';
if (!in_array($moduleLevel, ['UG','PG'])) $errors[] = 'Invalid module level.';
if ($passMark < 0 || $passMark > 100)    $errors[] = 'Pass mark must be 0–100.';

$origId = strtoupper(trim($_POST['original_id'] ?? ''));
$isNew  = $origId === '';

// Load original config to preserve detail sections (and existing password hashes)
$existingMarkerHashes = [];
$details = null;

$configsDir = realpath(CONFIGS_DIR);

if (!$isNew) {
    if (!preg_match('/^[A-Z][A-Z0-9]{2,11}$/', $origId)) {
        $errors[] = 'Invalid original module ID.';
    } else {
        $origFile = $configsDir . DIRECTORY_SEPARATOR . $origId . '.php';
        if (file_exists($origFile)) {
            include $origFile;
            // Collect existing marker hashes
            if (defined('MARKERS')) {
                foreach (MARKERS as $mid => $mdata) {
                    $existingMarkerHashes[$mid] = $mdata['password'] ?? '';
                }
            }
            // Collect detail constants
            $details = [
                'mark_weights'         => defined('MARK_WEIGHTS')         ? MARK_WEIGHTS         : null,
                'timer_total'          => defined('TIMER_TOTAL')          ? TIMER_TOTAL          : null,
                'timer_break'          => defined('TIMER_BREAK')          ? TIMER_BREAK          : null,
                'tier_ranges'          => defined('TIER_RANGES')          ? TIER_RANGES          : null,
                'tier_rules'           => defined('TIER_RULES')           ? TIER_RULES           : null,
                'feedback_tier_labels' => defined('FEEDBACK_TIER_LABELS') ? FEEDBACK_TIER_LABELS : null,
                'demo_criteria_labels' => defined('DEMO_CRITERIA_LABELS') ? DEMO_CRITERIA_LABELS : null,
                'kq_criterion_map'     => defined('KQ_CRITERION_MAP')     ? KQ_CRITERION_MAP     : null,
                'questions'            => defined('QUESTIONS')            ? QUESTIONS            : null,
                'knowledge_questions'  => defined('KNOWLEDGE_QUESTIONS')  ? KNOWLEDGE_QUESTIONS  : null,
                'design_criteria'      => defined('DESIGN_CRITERIA')      ? DESIGN_CRITERIA      : null,
            ];
        }
    }
}

// Fall back to defaults for any missing detail sections
$defaults = default_config_details();
if ($details === null) $details = $defaults;
foreach ($defaults as $k => $v) {
    if ($details[$k] === null) $details[$k] = $v;
}

// Parse markers
$markers = [];
foreach ($_POST['markers'] ?? [] as $entry) {
    $mid  = trim($entry['id'] ?? '');
    $name = trim($entry['name'] ?? '');
    $pw   = $entry['password'] ?? '';

    if (!preg_match('/^\d{8}$/', $mid)) {
        $errors[] = "Marker ID '{$mid}' must be exactly 8 digits.";
        continue;
    }
    if ($name === '') {
        $errors[] = "Marker name for ID '{$mid}' is required.";
        continue;
    }

    if ($pw !== '') {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
    } elseif (isset($existingMarkerHashes[$mid])) {
        $hash = $existingMarkerHashes[$mid];
    } else {
        $errors[] = "Password is required for new marker ID '{$mid}'.";
        continue;
    }

    $markers[$mid] = ['name' => $name, 'password' => $hash];
}

if (!empty($errors)) {
    $_SESSION['basics_errors'] = $errors;
    $_SESSION['basics_post']   = $_POST;
    $back = $isNew ? 'basics.php?new=1' : 'basics.php?config=' . urlencode($origId);
    header('Location: ' . $back);
    exit;
}

// Build complete config data
$data = [
    'module_id'    => $moduleId,
    'module_name'  => $moduleName,
    'module_level' => $moduleLevel,
    'pass_mark'    => $passMark,
    'markers'      => $markers,
] + $details;

$content = generate_config($data);

try {
    safe_write_config(CONFIGS_DIR, $moduleId, $content);
    // Rename: delete old file if module ID changed
    if (!$isNew && $origId !== $moduleId) {
        $oldFile = $configsDir . DIRECTORY_SEPARATOR . $origId . '.php';
        if (file_exists($oldFile)) unlink($oldFile);
    }
} catch (RuntimeException $e) {
    $_SESSION['basics_errors'] = ['File write error: ' . $e->getMessage()];
    $back = $isNew ? 'basics.php?new=1' : 'basics.php?config=' . urlencode($origId);
    header('Location: ' . $back);
    exit;
}

header('Location: basics.php?config=' . urlencode($moduleId) . '&saved=1');
exit;
