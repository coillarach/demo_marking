<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/feedback.php';

function format_duration(?int $secs): string {
    if ($secs === null) return '-';
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

    $marksheet    = readMarksheetData();
    $msHeader     = $marksheet['header'];
    $msRows       = $marksheet['rows'];
    $msCol        = $marksheet['col'];

    // Write header: preserve marksheet columns, append extra marking columns
    if ($msHeader) {
        fputcsv($out, array_merge($msHeader, ['Duration', 'Misconduct rationale']));
    } else {
        fputcsv($out, [
            'Identifier','Full name','Email address','Username',
            'Status','Grade','Maximum grade','Marking workflow state',
            'Grade can be changed','Last modified (submission)',
            'Last modified (grade)','Feedback comments',
            'Duration','Misconduct rationale',
        ]);
    }

    // Index marking records by matric
    $recordMap = [];
    foreach ($records as $r) {
        $recordMap[$r['matric'] ?? ''] = $r;
    }

    $written = [];

    // ── Pass 1: iterate marksheet rows in original order ─────────────
    foreach ($msRows as $matric => $origRow) {
        $written[] = $matric;
        $r = $recordMap[$matric] ?? null;

        if ($msHeader) {
            $row = $origRow;
            while (count($row) < count($msHeader)) $row[] = '';

            if ($r !== null) {
                $reportM = hasReport() ? floatval($r['report_mark'] ?? 0) : 0.0;
                $sessionTotal = 0.0;
                foreach (DEMO_CRITERIA_LABELS as $key => $_) {
                    if ($key === 'design' && empty(DESIGN_CRITERIA)) continue;
                    $sessionTotal += getCriterionMark($r, $key);
                }
                $total    = round($sessionTotal + $reportM, 1);
                $complete = !empty($r['complete']) && (!hasReport() || array_key_exists('report_mark', $r));
                $state    = $complete ? 'Marking completed' : 'In marking';
                $grade    = ($r['authorship'] ?? 'pass') === 'fail' ? '' : $total;

                if (isset($msCol['Grade']))                    $row[$msCol['Grade']]                    = $grade;
                if (isset($msCol['Marking workflow state']))   $row[$msCol['Marking workflow state']]   = $state;
                if (isset($msCol['Last modified (grade)']))    $row[$msCol['Last modified (grade)']]    = date('d/m/Y H:i');
                if (isset($msCol['Feedback comments']))        $row[$msCol['Feedback comments']]        = buildFeedbackComment($r);

                $row[] = format_duration(isset($r['demo_duration']) ? (int)$r['demo_duration'] : null);
                $row[] = ($r['authorship'] ?? 'pass') === 'fail' ? trim($r['misconduct_comment'] ?? '') : '';
            } else {
                $row[] = '';  // Duration
                $row[] = '';  // Misconduct rationale
            }
            fputcsv($out, $row);
        }
    }

    // ── Pass 2: marked students not in the marksheet (fallback) ──────
    foreach ($records as $r) {
        $matric = $r['matric'] ?? '';
        if (in_array($matric, $written, true)) continue;

        $reportM = hasReport() ? floatval($r['report_mark'] ?? 0) : 0.0;
        $sessionTotal = 0.0;
        foreach (DEMO_CRITERIA_LABELS as $key => $_) {
            if ($key === 'design' && empty(DESIGN_CRITERIA)) continue;
            $sessionTotal += getCriterionMark($r, $key);
        }
        $total    = round($sessionTotal + $reportM, 1);
        $complete = !empty($r['complete']) && (!hasReport() || array_key_exists('report_mark', $r));
        $state    = $complete ? 'Marking completed' : 'In marking';
        $grade    = ($r['authorship'] ?? 'pass') === 'fail' ? '' : $total;

        fputcsv($out, [
            'Participant ' . $matric,
            '', '', $matric, '',
            $grade, '100.00', $state, 'Yes', '',
            date('d/m/Y H:i'),
            buildFeedbackComment($r),
            format_duration(isset($r['demo_duration']) ? (int)$r['demo_duration'] : null),
            ($r['authorship'] ?? 'pass') === 'fail' ? trim($r['misconduct_comment'] ?? '') : '',
        ]);
    }

    fclose($out);
    exit;
}

// ─── Dashboard view ───────────────────────────────────────────────
$completeCount = count(array_filter($records, function($r) {
    if (!hasReport()) return !empty($r['complete']);
    return !empty($r['complete']) && array_key_exists('report_mark', $r);
}));
$authFailCount = count(array_filter($records, fn($r) => ($r['authorship'] ?? '') === 'fail'));
$noReportCount = count(array_filter($records, function($r) {
    if (!hasReport()) return false;
    $m = $r['matric'] ?? '';
    return !array_key_exists('report_mark', $r)
        && (file_exists(SUBMISSIONS_DIR . $m . '.html') || file_exists(SUBMISSIONS_DIR . $m . '.pdf'));
}));
$noSubmissionCount = count(array_filter($records, function($r) {
    if (!hasReport()) return false;
    $m = $r['matric'] ?? '';
    return !file_exists(SUBMISSIONS_DIR . $m . '.html') && !file_exists(SUBMISSIONS_DIR . $m . '.pdf');
}));
$passMark  = PASS_MARK;
$passCount = count(array_filter($records, function($r) use ($passMark) {
    if (($r['authorship'] ?? '') === 'fail') return false;
    if (empty($r['complete'])) return false;
    if (hasReport() && !array_key_exists('report_mark', $r)) return false;
    $reportM2 = hasReport() ? floatval($r['report_mark'] ?? 0) : 0.0;
    $sessionTotal2 = 0.0;
    foreach (DEMO_CRITERIA_LABELS as $key => $_) {
        if ($key === 'design' && empty(DESIGN_CRITERIA)) continue;
        $sessionTotal2 += getCriterionMark($r, $key);
    }
    $total = round($sessionTotal2 + $reportM2, 1);
    return $total >= $passMark;
}));
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= MODULE_ID ?> - Export Marks</title>
<link rel="stylesheet" href="static/marking.css">
<link rel="stylesheet" href="static/export.css">
</head>
<body>
<div class="page-header">
  <h1><?= MODULE_ID ?> - Marks Export</h1>
  <span><span style="color:rgba(255,255,255,.6);font-size:.8rem;"><?= htmlspecialchars($currentMarkerName) ?></span> &nbsp;|&nbsp; <a href="demo.php">Demo form</a> &nbsp;|&nbsp; <?php if (hasReport()): ?><a href="report.php">Report form</a> &nbsp;|&nbsp; <?php endif; ?><a href="configure.php">Configure</a> &nbsp;|&nbsp; <a href="logout.php">Sign out</a></span>
</div>
<main>

<div class="card">
  <h2>Overview</h2>
  <div class="stats">
    <div class="stat"><div class="s-label">Total records</div><div class="s-val"><?= count($records) ?></div></div>
    <div class="stat"><div class="s-label">Fully complete</div><div class="s-val"><?= $completeCount ?></div></div>
    <div class="stat"><div class="s-label">Passed (≥<?= $passMark ?>%)</div><div class="s-val"><?= $passCount ?></div></div>
    <?php if (hasReport()): ?>
    <div class="stat warn"><div class="s-label">Report not yet marked</div><div class="s-val"><?= $noReportCount ?></div></div>
    <div class="stat warn"><div class="s-label">No report submitted</div><div class="s-val"><?= $noSubmissionCount ?></div></div>
    <?php endif; ?>
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
        <?php if (hasReport()): ?><th>Report Marker</th><?php endif; ?>
        <th>Tier</th>
        <th>Tech</th>
        <th>Design</th>
        <th>Demo</th>
        <th>Duration</th>
        <?php if (hasReport()): ?><th>Report</th><?php endif; ?>
        <th>Total</th>
        <th>Auth</th>
        <th>Status</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($records as $r):
        $isReferral = ($r['authorship'] ?? '') === 'fail';
        $report = hasReport() ? floatval($r['report_mark'] ?? 0) : 0;
        $sessionTotal3 = 0.0;
        foreach (DEMO_CRITERIA_LABELS as $key => $_) {
            if ($key === 'design' && empty(DESIGN_CRITERIA)) continue;
            $sessionTotal3 += getCriterionMark($r, $key);
        }
        $total   = round($sessionTotal3 + $report, 1);
        $tier    = $r['tier'] ?? '-';
        $tierCls = str_starts_with($tier, 'advanced') ? 'tier-adv' : (str_starts_with($tier, 'intermediate') ? 'tier-mid' : 'tier-bas');
        $auth    = $isReferral
            ? '<span class="badge badge-fail">Misconduct Referral</span>'
            : '<span class="badge badge-pass">Confirmed</span>';
        $isReportMarked = !hasReport() || array_key_exists('report_mark', $r);
        $hasSubmission  = hasReport()
            && (file_exists(SUBMISSIONS_DIR . ($r['matric'] ?? '') . '.html')
             || file_exists(SUBMISSIONS_DIR . ($r['matric'] ?? '') . '.pdf'));
        $status = $isReferral
            ? '<span class="badge badge-fail">Referred</span>'
            : (($hasSubmission && !$isReportMarked)
                ? '<span class="badge badge-pending">Pending</span>'
                : ($total >= $passMark
                    ? '<span class="badge badge-pass">Pass</span>'
                    : '<span class="badge badge-fail">Fail</span>'));
        $markColspan = 5 + (hasReport() ? 1 : 0);
    ?>
    <tr<?= $isReferral ? ' class="misconduct-row"' : '' ?>>
      <td><?= htmlspecialchars($r['matric'] ?? '') ?></td>
      <td><?= htmlspecialchars($r['marker'] ?? '') ?></td>
      <?php if (hasReport()): ?><td><?= htmlspecialchars($r['report_marker'] ?? '-') ?></td><?php endif; ?>
      <td class="<?= $tierCls ?>"><?= htmlspecialchars($tier) ?></td>
      <?php if ($isReferral): ?>
      <td colspan="<?= $markColspan ?>" class="misconduct-label">No mark calculated — pending academic misconduct referral</td>
      <?php else: ?>
      <td><?= floatval($r['tech_weighted'] ?? 0) ?></td>
      <td><?= intval($r['design']['total'] ?? 0) ?></td>
      <td><?= intval($r['demo_mark'] ?? 0) ?></td>
      <td><?= format_duration(isset($r['demo_duration']) ? (int)$r['demo_duration'] : null) ?></td>
      <?php if (hasReport()): ?><td><?= $hasSubmission ? ($report ?: '-') : 'N/A' ?></td><?php endif; ?>
      <td><strong><?= $total ?></strong></td>
      <?php endif; ?>
      <td><?= $auth ?></td>
      <td><?= $status ?></td>
      <td>
        <a href="demo.php?matric=<?= urlencode($r['matric'] ?? '') ?>">Edit</a><?php if (hasReport()): ?> &nbsp;| <a href="report.php?matric=<?= urlencode($r['matric'] ?? '') ?>">Report</a><?php endif; ?>
      </td>
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
