<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/feedback.php';

function format_duration(?int $secs): string {
    if ($secs === null) return '—';
    return sprintf('%d:%02d', intdiv($secs, 60), $secs % 60);
}

// ─── Load all records ─────────────────────────────────────────────
$records = [];
foreach (glob(DATA_DIR . '*.json') as $file) {
    $rec = json_decode(file_get_contents($file), true);
    if ($rec) $records[] = $rec;
}
usort($records, fn($a,$b) => strcmp($a['matric'] ?? '', $b['matric'] ?? ''));

// ─── Handle CSV download ──────────────────────────────────────────
if (isset($_GET['download'])) {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . MODULE_ID . '_marks_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');
    // Moodle offline grading header
    fputcsv($out, [
        'Identifier','Full name','Email address','Username',
        'Status','Grade','Maximum grade','Marking workflow state',
        'Grade can be changed','Last modified (submission)',
        'Last modified (grade)','Feedback comments',
        'Duration',
        'Misconduct rationale',
    ]);

    foreach ($records as $r) {
        $matric = $r['matric'] ?? '';
        $techW  = floatval($r['tech_weighted'] ?? 0);
        $designT = intval($r['design']['total'] ?? 0);
        $demoM  = intval($r['demo_mark'] ?? 0);
        $reportM = floatval($r['report_mark'] ?? 0);
        $total  = round($techW + $designT + $demoM + $reportM, 1);

        $complete = !empty($r['complete']);
        $state    = $complete ? 'Marking completed' : 'In marking';

        // If authorship failed, grade is empty and workflow state flags it
        $grade = ($r['authorship'] ?? 'pass') === 'fail' ? '' : $total;

        fputcsv($out, [
            'Participant ' . $matric,   // Identifier
            '',                          // Full name (not stored — marker fills if needed)
            '',                          // Email
            $matric,                     // Username
            '',                          // Status
            $grade,                      // Grade
            '100.00',                    // Maximum grade
            $state,                      // Marking workflow state
            'Yes',                       // Grade can be changed
            '',                          // Last modified (submission)
            date('d/m/Y H:i'),           // Last modified (grade)
            buildFeedbackComment($r),    // Feedback comments
            format_duration(isset($r['demo_duration']) ? (int)$r['demo_duration'] : null),
            ($r['authorship'] ?? 'pass') === 'fail' ? trim($r['misconduct_comment'] ?? '') : '',
        ]);
    }
    fclose($out);
    exit;
}

// ─── Dashboard view ───────────────────────────────────────────────
$completeCount   = count(array_filter($records, fn($r) => !empty($r['complete'])));
$authFailCount   = count(array_filter($records, fn($r) => ($r['authorship'] ?? '') === 'fail'));
$noReportCount   = count(array_filter($records, fn($r) => empty($r['complete'])));
$passMark        = PASS_MARK;
$passCount       = count(array_filter($records, function($r) use ($passMark) {
    if (($r['authorship'] ?? '') === 'fail') return false;
    if (empty($r['complete'])) return false;
    $total = round(
        floatval($r['tech_weighted'] ?? 0) +
        intval($r['design']['total'] ?? 0) +
        intval($r['demo_mark'] ?? 0) +
        floatval($r['report_mark'] ?? 0), 1
    );
    return $total >= $passMark;
}));
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= MODULE_ID ?> — Export Marks</title>
<link rel="stylesheet" href="static/marking.css">
<link rel="stylesheet" href="static/export.css">
</head>
<body>
<div class="page-header">
  <h1><?= MODULE_ID ?> — Marks Export</h1>
  <span><span style="color:rgba(255,255,255,.6);font-size:.8rem;"><?= htmlspecialchars($currentMarkerName) ?></span> &nbsp;|&nbsp; <a href="demo.php">Demo form</a> &nbsp;|&nbsp; <a href="report.php">Report form</a> &nbsp;|&nbsp; <a href="configure.php">Configure</a> &nbsp;|&nbsp; <a href="logout.php">Sign out</a></span>
</div>
<main>

<div class="card">
  <h2>Overview</h2>
  <div class="stats">
    <div class="stat"><div class="s-label">Total records</div><div class="s-val"><?= count($records) ?></div></div>
    <div class="stat"><div class="s-label">Fully complete</div><div class="s-val"><?= $completeCount ?></div></div>
    <div class="stat"><div class="s-label">Passed (≥<?= $passMark ?>%)</div><div class="s-val"><?= $passCount ?></div></div>
    <div class="stat warn"><div class="s-label">Report not yet marked</div><div class="s-val"><?= $noReportCount ?></div></div>
    <div class="stat danger"><div class="s-label">Authorship failures</div><div class="s-val"><?= $authFailCount ?></div></div>
  </div>
  <p style="font-size:.8rem;color:#64748b;margin-bottom:1rem;">
    Pass mark: <strong><?= $passMark ?>%</strong> (<?= MODULE_LEVEL ?>).
    The CSV export includes all records. Rows with incomplete report marks will show <em>In marking</em> in the workflow state column.
    Rows where authorship failed will have an empty Grade.
  </p>
  <a class="btn-primary" href="export.php?download=1">Download Moodle CSV</a>
</div>

<?php if ($records): ?>
<div class="card">
  <h2>All Records</h2>
  <table>
    <thead>
      <tr>
        <th>Matric</th>
        <th>Demo Marker</th>
        <th>Report Marker</th>
        <th>Tier</th>
        <th>Tech</th>
        <th>Design</th>
        <th>Demo</th>
        <th>Duration</th>
        <th>Report</th>
        <th>Total</th>
        <th>Auth</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($records as $r):
        $tech   = floatval($r['tech_weighted'] ?? 0);
        $design = intval($r['design']['total'] ?? 0);
        $demo   = intval($r['demo_mark'] ?? 0);
        $report = floatval($r['report_mark'] ?? 0);
        $total  = round($tech + $design + $demo + $report, 1);
        $tier   = $r['tier'] ?? '—';
        $tierCls = str_starts_with($tier, 'advanced') ? 'tier-adv' : (str_starts_with($tier, 'intermediate') ? 'tier-mid' : 'tier-bas');
        $auth   = ($r['authorship'] ?? '') === 'pass' ? '<span class="badge badge-pass">Pass</span>' : '<span class="badge badge-fail">Fail</span>';
        $status = !empty($r['complete']) ? '<span class="badge badge-complete">Complete</span>' : '<span class="badge badge-pending">Report pending</span>';
    ?>
    <tr>
      <td><?= htmlspecialchars($r['matric'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['marker'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['report_marker'] ?? '—') ?></td>
      <td class="<?= $tierCls ?>"><?= htmlspecialchars($tier) ?></td>
      <td><?= $tech ?></td>
      <td><?= $design ?></td>
      <td><?= $demo ?></td>
      <td><?= format_duration(isset($r['demo_duration']) ? (int)$r['demo_duration'] : null) ?></td>
      <td><?= $report ?: '—' ?></td>
      <td><strong><?= ($r['authorship'] ?? '') === 'fail' ? '—' : $total ?></strong></td>
      <td><?= $auth ?></td>
      <td><?= $status ?></td>
      <td><a href="report.php?matric=<?= urlencode($r['matric'] ?? '') ?>">Edit</a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php else: ?>
<div class="card"><p style="color:#64748b;">No marking records found yet.</p></div>
<?php endif; ?>
</main>
</body>
</html>
