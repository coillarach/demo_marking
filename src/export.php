<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/feedback.php';

function format_duration(?int $secs): string {
    if ($secs === null) return '-';
    return sprintf('%d:%02d', intdiv($secs, 60), $secs % 60);
}

// ─── Load all records ─────────────────────────────────────────────
$records = glob_json_records(DATA_DIR);

// ─── Load marksheet (shared by download and dashboard) ────────────
$marksheet = readMarksheetData();
$msHeader  = $marksheet['header'];
$msRows    = $marksheet['rows'];
$msCol     = $marksheet['col'];

// ─── Handle CSV download ──────────────────────────────────────────
if (isset($_GET['download'])) {
    $verboseQA = !empty($_GET['verbose_qa']);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . MODULE_ID . '_marks_' . date('Y-m-d') . '.csv"');

    $out = fopen('php://output', 'w');

    // Write header: preserve marksheet columns, append extra marking columns
    if ($msHeader) {
        fputcsv($out, array_merge($msHeader, ['Duration', 'Misconduct rationale']), escape: '\\');
    } else {
        fputcsv($out, [
            'Identifier','Full name','Email address','Username',
            'Status','Grade','Maximum grade','Marking workflow state',
            'Grade can be changed','Last modified (submission)',
            'Last modified (grade)','Feedback comments',
            'Duration','Misconduct rationale',
        ], escape: '\\');
    }

    // Index marking records by matric
    $recordMap = [];
    foreach ($records as $r) {
        $recordMap[$r['matric'] ?? ''] = $r;
    }

    // ── Iterate marksheet rows in original order ──────────────────────
    foreach ($msRows as $matric => $origRow) {
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
                if (isset($msCol['Feedback comments']))        $row[$msCol['Feedback comments']]        = buildFeedbackComment($r, $verboseQA);

                $row[] = format_duration(isset($r['demo_duration']) ? (int)$r['demo_duration'] : null);
                $row[] = ($r['authorship'] ?? 'pass') === 'fail' ? trim($r['misconduct_comment'] ?? '') : '';
            } else {
                $row[] = '';  // Duration
                $row[] = '';  // Misconduct rationale
            }
            fputcsv($out, $row, escape: '\\');
        }
    }

    fclose($out);
    exit;
}

// ─── Dashboard view ───────────────────────────────────────────────

// Students with marking records not present in the marksheet
$orphanRecords = find_orphan_records($records, $msRows);

$completeCount = count(array_filter($records, function($r) {
    if (!hasReport()) return !empty($r['complete']);
    return !empty($r['complete']) && array_key_exists('report_mark', $r);
}));
$authFailCount = count(array_filter($records, fn($r) => ($r['authorship'] ?? '') === 'fail'));
$noReportCount = count(array_filter($records, function($r) {
    if (!hasReport()) return false;
    $m = $r['matric'] ?? '';
    return !array_key_exists('report_mark', $r) && has_submission_file(SUBMISSIONS_DIR, $m);
}));
$noSubmissionCount = count(array_filter($records, function($r) {
    if (!hasReport()) return false;
    return !has_submission_file(SUBMISSIONS_DIR, $r['matric'] ?? '');
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
<link rel="stylesheet" href="static/marking.css?date=31May26">
<link rel="stylesheet" href="static/export.css">
</head>
<body>
<?php
$navLinks = [['label' => 'Demo form', 'href' => 'demo.php']];
if (hasReport()) $navLinks[] = ['label' => 'Report form', 'href' => 'report.php'];
$navLinks[] = ['label' => 'Configure', 'href' => 'configure.php'];
if (defined('ADMIN_URL')) $navLinks[] = ['label' => 'Documentation', 'href' => ADMIN_URL . '/docs.php', 'external' => true];
$navLinks[] = ['label' => 'Sign out', 'href' => 'logout.php'];
render_page_header(MODULE_ID . ' - Marks Export', $navLinks, $currentMarkerName);
?>
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
  <form method="get" action="export.php" style="display:inline-flex;align-items:center;gap:1rem;flex-wrap:wrap;margin-top:.25rem;">
    <input type="hidden" name="download" value="1">
    <label style="font-size:.85rem;display:flex;align-items:center;gap:.4rem;cursor:pointer;">
      <input type="checkbox" name="verbose_qa" value="1">
      Include full question text for unanswered Q&amp;A
    </label>
    <button type="submit" class="btn-primary">Download Moodle CSV</button>
  </form>
</div>

<?php if ($orphanRecords): ?>
<div class="card">
  <div class="alert alert-warning">
    <strong><?= count($orphanRecords) ?> student record<?= count($orphanRecords) !== 1 ? 's' : '' ?> not in Moodle marksheet — excluded from CSV export</strong>
    <p style="margin:.5rem 0 .4rem;">The following students have marking records but do not appear in the downloaded Moodle marksheet. They cannot be imported into Moodle and have been omitted from the CSV file.</p>
    <ul style="margin:.25rem 0 .5rem;padding-left:1.25rem;">
      <?php foreach ($orphanRecords as $or):
            $orMatric = htmlspecialchars($or['matric'] ?? '');
            $orMarker = htmlspecialchars($or['marker'] ?? 'unknown');
            $orTier   = htmlspecialchars($or['tier']   ?? '-'); ?>
      <li>Matric: <strong><?= $orMatric ?></strong> — marked by <?= $orMarker ?> (tier: <?= $orTier ?>)</li>
      <?php endforeach; ?>
    </ul>
    <p>Possible cause: the student's Moodle record may be suspended (e.g. due to non-payment of fees), which removes them from the module enrolment list. Once the issue is resolved in Moodle, download a fresh marksheet and re-upload it here before exporting again.</p>
  </div>
</div>
<?php endif; ?>

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
        $hasSubmission  = hasReport() && has_submission_file(SUBMISSIONS_DIR, $r['matric'] ?? '');
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
