<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

if (empty($_SESSION['module_csrf']) || ($_POST['_csrf'] ?? '') !== $_SESSION['module_csrf']) {
    http_response_code(403);
    die('Invalid CSRF token.');
}

$errors = [];

// ── Parse weights ──────────────────────────────────────────────────
$rawW = $_POST['weights'] ?? [];
$weights = [];
foreach ($rawW as $k => $v) {
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $k)) continue;
    $weights[$k] = round((float)$v, 10);
}
$wSum = array_sum($weights);
if (abs($wSum - 1.0) > 0.001) {
    $errors[] = 'Mark weights must sum to 1.0 (current sum: ' . number_format($wSum, 4) . ').';
}

$timerTotal = max(60, (int)($_POST['timer_total'] ?? 300));
$timerBreak = max(0,  (int)($_POST['timer_break'] ?? 180));

// ── Parse features (QUESTIONS) ────────────────────────────────────
$questions = [];
foreach ($_POST['features'] ?? [] as $feat) {
    $key = trim($feat['key'] ?? '');
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) continue;
    $tier = in_array($feat['tier'] ?? '', ['basic','intermediate','advanced'])
        ? $feat['tier'] : 'basic';
    $qs = [];
    foreach ($feat['questions'] ?? [] as $q) {
        $qtext = trim($q['q'] ?? '');
        if ($qtext === '') continue;
        $qs[] = [
            'q'      => $qtext,
            'well'   => trim($q['well']   ?? ''),
            'prompt' => trim($q['prompt'] ?? ''),
            'no'     => trim($q['no']     ?? ''),
        ];
    }
    $questions[$key] = [
        'label'     => trim($feat['label'] ?? $key),
        'tier'      => $tier,
        'questions' => $qs,
    ];
}

// ── Parse tier ranges ──────────────────────────────────────────────
$validCls = ['tier-advanced','tier-intermediate','tier-basic','tier-partial','tier-fail'];
$tierRanges = [];
foreach ($_POST['tiers'] ?? [] as $tier) {
    $key = trim($tier['key'] ?? '');
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) continue;
    $cls = in_array($tier['cls'] ?? '', $validCls) ? $tier['cls'] : 'tier-basic';
    $tierRanges[$key] = [
        'label' => trim($tier['label'] ?? $key),
        'cls'   => $cls,
        'min'   => max(0, min(100, (int)($tier['min'] ?? 0))),
        'max'   => max(0, min(100, (int)($tier['max'] ?? 100))),
        'mid'   => max(0, min(100, (int)($tier['mid'] ?? 50))),
    ];
}

// ── Parse tier rules ───────────────────────────────────────────────
$tierRules = [];
foreach ($_POST['rules'] ?? [] as $rule) {
    $key = trim($rule['key'] ?? '');
    if ($key === '') continue;
    $r = ['key' => $key];
    if (isset($rule['min_adv'])   && $rule['min_adv']   !== '') $r['minAdv']   = (int)$rule['min_adv'];
    if (isset($rule['min_inter']) && $rule['min_inter'] !== '') $r['minInter'] = (int)$rule['min_inter'];
    if (isset($rule['min_basic']) && $rule['min_basic'] !== '') $r['minBasic'] = (int)$rule['min_basic'];
    $tierRules[] = $r;
}

// ── Parse feedback labels ──────────────────────────────────────────
$fbKeys = $_POST['feedback_keys'] ?? [];
$fbVals = $_POST['feedback_vals'] ?? [];
$feedbackLabels = [];
foreach ($fbKeys as $i => $key) {
    $key = trim($key);
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) continue;
    $feedbackLabels[$key] = trim($fbVals[$i] ?? '');
}

// ── Parse knowledge questions ──────────────────────────────────────
$kqSections = [];
foreach ($_POST['kq'] ?? [] as $sec) {
    $key = trim($sec['key'] ?? '');
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) continue;
    $qs = [];
    foreach ($sec['questions'] ?? [] as $q) {
        $qtext = trim($q['q'] ?? '');
        if ($qtext === '') continue;
        $qs[] = ['q' => $qtext, 'hint' => trim($q['hint'] ?? '')];
    }
    $kqSections[$key] = [
        'label'     => trim($sec['label'] ?? $key),
        'questions' => $qs,
    ];
}

// ── Parse design criteria ──────────────────────────────────────────
$designCriteria = [];
foreach ($_POST['design_criteria'] ?? [] as $crit) {
    $key = trim($crit['key'] ?? '');
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) continue;
    $max = (int)($crit['max'] ?? 2) === 4 ? 4 : 2;
    $feedback = [
        0 => trim($crit['fb_0'] ?? ''),
        2 => trim($crit['fb_2'] ?? ''),
    ];
    if ($max === 4) $feedback[4] = trim($crit['fb_4'] ?? '');
    $designCriteria[$key] = [
        'label'       => trim($crit['label']       ?? $key),
        'description' => trim($crit['description'] ?? ''),
        'max'         => $max,
        'feedback'    => $feedback,
    ];
}

// ── Parse report criteria ──────────────────────────────────────────
// Optional split of the report mark. No rows = a single, unsplit report mark.
$reportCriteria = [];
foreach ($_POST['report_criteria'] ?? [] as $crit) {
    $key = trim($crit['key'] ?? '');
    if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) continue;
    $reportCriteria[$key] = [
        'label'       => trim($crit['label'] ?? '') ?: $key,
        'description' => trim($crit['description'] ?? ''),
        'weight'      => max(0.0, min(1.0, round((float)($crit['weight'] ?? 0), 10))),
    ];
}
if ($reportCriteria) {
    $reportWeight = $weights['report'] ?? 0.0;
    $critSum      = array_sum(array_column($reportCriteria, 'weight'));
    if ($reportWeight <= 0) {
        $errors[] = 'Report criteria are defined, but the Report weight is 0. Set a Report weight first, or remove the criteria.';
    } elseif (abs($critSum - $reportWeight) > 0.001) {
        $errors[] = 'Report criteria weights must sum to the Report weight of ' . number_format($reportWeight, 2)
                  . ' (current sum: ' . number_format($critSum, 4) . ').';
    }
}

// ── Parse wording overrides ────────────────────────────────────────
// Only known keys are accepted; blank means "use the default".
$uiText = [];
foreach (array_keys(ui_text_defaults()) as $uiKey) {
    $val = trim((string)($_POST['ui_text'][$uiKey] ?? ''));
    if ($val !== '') $uiText[$uiKey] = $val;
}

if (!empty($errors)) {
    $_SESSION['module_errors'] = $errors;
    header('Location: module_edit.php');
    exit;
}

// ── Load basics from existing config ──────────────────────────────
$configFile = realpath(CONFIGS_DIR . $currentModuleId . '.php');
if (!$configFile || !str_starts_with($configFile, realpath(CONFIGS_DIR))) {
    $_SESSION['module_errors'] = ['Module config not found.'];
    header('Location: module_edit.php');
    exit;
}
include $configFile;

// Build complete data combining basics (from constants) + new details
$data = [
    'module_id'    => defined('MODULE_ID')    ? MODULE_ID    : $currentModuleId,
    'module_name'  => defined('MODULE_NAME')  ? MODULE_NAME  : '',
    'module_level' => defined('MODULE_LEVEL') ? MODULE_LEVEL : 'UG',
    'pass_mark'    => defined('PASS_MARK')    ? PASS_MARK    : 40,
    'markers'      => defined('MARKERS')      ? MARKERS      : [],
    'mark_weights'         => $weights,
    'report_criteria'      => $reportCriteria,
    'ui_text'              => $uiText,
    // No editing UI for these yet — carry them through so saving doesn't drop them
    'demo_criteria_labels' => defined('DEMO_CRITERIA_LABELS') ? DEMO_CRITERIA_LABELS : [],
    'kq_criterion_map'     => defined('KQ_CRITERION_MAP')     ? KQ_CRITERION_MAP     : [],
    'timer_total'          => $timerTotal,
    'timer_break'          => $timerBreak,
    'tier_ranges'          => $tierRanges,
    'tier_rules'           => $tierRules,
    'feedback_tier_labels' => $feedbackLabels,
    'questions'            => $questions,
    'knowledge_questions'  => $kqSections,
    'design_criteria'      => $designCriteria,
];

$content = generate_config($data);

try {
    safe_write_config(CONFIGS_DIR, $currentModuleId, $content);
    sync_deployed_config($currentModuleId, $content);
} catch (RuntimeException $e) {
    $_SESSION['module_errors'] = ['File write error: ' . $e->getMessage()];
    header('Location: module_edit.php');
    exit;
}

header('Location: module_edit.php?saved=1');
exit;
