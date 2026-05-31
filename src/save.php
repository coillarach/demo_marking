<?php
require_once __DIR__ . '/auth_check.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid JSON']);
    exit;
}

// Validate matric is present and safe to use as a filename
$matric = preg_replace('/[^a-zA-Z0-9]/', '', $data['matric'] ?? '');
if (strlen($matric) < 4 || strlen($matric) > 20) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid matric number']);
    exit;
}

$mode = $data['mode'] ?? 'demo'; // 'demo' or 'report'

// Demo saves only: enforce required KQ groups
if ($mode === 'demo' && defined('KNOWLEDGE_QUESTIONS')) {
    $required = [];
    foreach (KNOWLEDGE_QUESTIONS as $topicKey => $group) {
        if (!empty($group['require_one'])) {
            $required[$topicKey] = $group['require_one'];
        }
    }
    if ($required) {
        $satisfied = array_fill_keys(array_unique(array_values($required)), false);
        $kq = $data['kq'] ?? [];
        foreach ($required as $topicKey => $groupName) {
            if ($satisfied[$groupName]) continue;
            $prefix = 'kq_' . $topicKey . '_';
            foreach (array_keys($kq) as $name) {
                if (strncmp($name, $prefix, strlen($prefix)) === 0) {
                    $satisfied[$groupName] = true;
                    break;
                }
            }
        }
        foreach ($satisfied as $groupName => $ok) {
            if (!$ok) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => "Knowledge Check: required question group '$groupName' has no answer."]);
                exit;
            }
        }
    }
}

$file = DATA_DIR . $matric . '.json';

// If updating report data, merge with existing record
if ($mode === 'report' && file_exists($file)) {
    $existing = json_decode(file_get_contents($file), true) ?? [];
    $data = array_merge($existing, [
        'report_mark'      => $data['report_mark'] ?? null,
        'report_comment'   => $data['report_comment'] ?? '',
        'report_marker'    => $_SESSION['marker_name'] ?? '',
        'report_marker_id' => $_SESSION['marker_id']   ?? '',
        'complete'         => true,
        'report_saved'     => date('c'),
    ]);
} else {
    $existing = file_exists($file) ? json_decode(file_get_contents($file), true) ?? [] : [];
    $demoSaved = date('c');
    $duration  = null;
    if (!empty($data['timer_start'])) {
        $start = strtotime($data['timer_start']);
        if ($start !== false && $start > 0) {
            $duration = max(0, time() - $start);
        }
    }
    $data['matric']         = $matric;
    $data['marker']         = $_SESSION['marker_name'] ?? '';
    $data['marker_id']      = $_SESSION['marker_id']   ?? '';
    $data['demo_saved']     = $demoSaved;
    $data['demo_duration']  = $duration;
    $data['complete']       = hasReport() ? ($existing['complete'] ?? false) : true;
    $data = array_merge($existing, $data);
}

if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) === false) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not write file']);
    exit;
}

echo json_encode(['ok' => true, 'matric' => $matric]);
