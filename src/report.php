<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/feedback.php';

$message = '';
$record  = null;
$matric  = trim($_GET['matric'] ?? '');

if ($matric) {
    $matric = preg_replace('/[^a-zA-Z0-9]/', '', $matric);
    $file   = DATA_DIR . $matric . '.json';
    if (file_exists($file)) {
        $record = json_decode(file_get_contents($file), true);
    } else {
        $message = "No demo record found for matric <strong>" . htmlspecialchars($matric) . "</strong>. Run the demo marking step first.";
    }
}

$hasHtml = $record && file_exists(SUBMISSIONS_DIR . $matric . '.html');
$hasPdf  = $record && file_exists(SUBMISSIONS_DIR . $matric . '.pdf');
$hasAny  = $hasHtml || $hasPdf;

$matricList = readStudentList();
if (empty($matricList)) {
    $files      = glob(DATA_DIR . '*.json') ?: [];
    $matricList = array_map(fn($f) => basename($f, '.json'), $files);
    sort($matricList);
}

if ($matric === '' && !empty($matricList)) {
    header('Location: report.php?matric=' . urlencode($matricList[0]));
    exit;
}

$navIdx   = ($matric !== '') ? array_search($matric, $matricList) : false;
$navTotal = count($matricList);
$navPos   = ($navIdx !== false) ? $navIdx + 1 : null;
$navPrev  = ($navIdx !== false && $navIdx > 0)             ? $matricList[$navIdx - 1] : null;
$navNext  = ($navIdx !== false && $navIdx < $navTotal - 1) ? $matricList[$navIdx + 1] : null;
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= MODULE_ID ?> — Report Marking</title>
<link rel="stylesheet" href="static/marking.css">
<link rel="stylesheet" href="static/report.css">
<script src="static/matric-autocomplete.js"></script>
</head>
<body>
<div class="page-header">
  <h1><?= MODULE_ID ?> — Report Marking</h1>
  <span><span style="color:rgba(255,255,255,.6);font-size:.8rem;"><?= htmlspecialchars($currentMarkerName) ?></span> &nbsp;|&nbsp; <a href="demo.php">Demo form</a> &nbsp;|&nbsp; <a href="export.php">Export CSV</a> &nbsp;|&nbsp; <a href="configure.php">Configure</a> &nbsp;|&nbsp; <a href="logout.php">Sign out</a></span>
</div>
<main class="report-split">

<div class="viewer-pane">
<?php if ($matric !== ''): ?>
  <div class="viewer-toolbar">
    <?php if ($hasHtml && $hasPdf): ?>
      <button class="btn-view active" id="btn-html" onclick="showView('html')">HTML</button>
      <button class="btn-view"        id="btn-pdf"  onclick="showView('pdf')">PDF</button>
    <?php elseif ($hasHtml): ?>
      <span class="viewer-format-label">HTML</span>
    <?php elseif ($hasPdf): ?>
      <span class="viewer-format-label">PDF</span>
    <?php endif; ?>

    <span class="viewer-nav">
      <?php if ($navPrev): ?>
        <a href="report.php?matric=<?= urlencode($navPrev) ?>" class="vnav-btn">&#8592; PREV</a>
      <?php else: ?>
        <span class="vnav-btn vnav-disabled">&#8592; PREV</span>
      <?php endif; ?>
      <span class="vnav-pos"><?= $navPos !== null ? $navPos . ' of ' . $navTotal : '— of ' . $navTotal ?></span>
      <span class="vnav-matric"><?= htmlspecialchars($matric) ?></span>
      <?php if ($navNext): ?>
        <a href="report.php?matric=<?= urlencode($navNext) ?>" class="vnav-btn">NEXT &#8594;</a>
      <?php else: ?>
        <span class="vnav-btn vnav-disabled">NEXT &#8594;</span>
      <?php endif; ?>
    </span>
  </div>

  <?php if ($hasAny): ?>
    <iframe id="report-iframe"
            src="submission.php?matric=<?= urlencode($matric) ?>&amp;fmt=<?= $hasHtml ? 'html' : 'pdf' ?>"
            title="Student report"></iframe>
  <?php else: ?>
    <div class="viewer-no-submission">No submission file on record for <?= htmlspecialchars($matric) ?></div>
  <?php endif; ?>
<?php else: ?>
  <div class="viewer-no-submission">Select a student to view their submission.</div>
<?php endif; ?>
</div>

<div class="form-pane">

<!-- Student lookup -->
<div class="card">
  <h2>Student Lookup</h2>
  <form method="get" style="display:flex;gap:.75rem;align-items:flex-end;">
    <div class="field" style="margin:0;flex:1;">
      <label for="matric-in">Matriculation number</label>
      <input type="text" id="matric-in" name="matric" value="<?= htmlspecialchars($matric) ?>" placeholder="e.g. 40123456" required>
    </div>
    <button class="btn-primary" type="submit" style="padding:.45rem 1rem;">Load</button>
  </form>
  <?php if ($message): ?>
  <div class="alert alert-warning" style="margin-top:.75rem;"><?= $message ?></div>
  <?php endif; ?>
</div>

<?php if ($record): ?>

<!-- Demo marks breakdown -->
<?php
$techW   = floatval($record['tech_weighted']    ?? 0);
$designT = intval($record['design']['total']    ?? 0);
$demoM   = intval($record['demo_mark']          ?? 0);
$reportM = floatval($record['report_mark']      ?? 0);
$authorship = $record['authorship'] ?? 'pass';
?>
<div class="card">
  <h2>Demo Marks &amp; Feedback — <?= htmlspecialchars($record['matric']) ?></h2>
  <p style="font-size:.75rem;color:#64748b;margin-bottom:1rem;">
    Tier: <strong><?= htmlspecialchars($record['tier'] ?? 'unknown') ?></strong> &nbsp;|&nbsp;
    Demo marker: <strong><?= htmlspecialchars($record['marker'] ?? '?') ?></strong>
    <?php if (!empty($record['report_marker']) && $record['report_marker'] !== $record['marker']): ?>
      &nbsp;|&nbsp; Report marker: <strong><?= htmlspecialchars($record['report_marker']) ?></strong>
    <?php endif; ?>
    &nbsp;|&nbsp; Demo saved: <strong><?= htmlspecialchars(substr($record['demo_saved'] ?? '', 0, 16)) ?></strong>
  </p>

  <?php if ($authorship === 'fail'): ?>
    <span class="fb-auth-fail">&#x26A0; Authorship NOT confirmed — referred to academic misconduct procedure</span>
  <?php else: ?>
    <span class="fb-auth-pass">&#x2713; Authorship confirmed at demonstration</span>
  <?php endif; ?>

<?php
  $techMax    = round(MARK_WEIGHTS['tech']   * 100);
  $designMax  = round(MARK_WEIGHTS['design'] * 100);
  $demoMax    = round(MARK_WEIGHTS['demo']   * 100);
  $sessionMax = $techMax + $designMax + $demoMax;
?>
  <div class="fb-section">
    <div class="fb-header">
      <span class="fb-title">Technical Implementation</span>
      <span class="fb-mark"><?= $techW ?> <span>/ <?= $techMax ?></span></span>
    </div>
    <p class="fb-text"><?= htmlspecialchars(techFeedback($record)) ?></p>
  </div>

  <div class="fb-section">
    <div class="fb-header">
      <span class="fb-title">Design &amp; Usability</span>
      <span class="fb-mark"><?= $designT ?> <span>/ <?= $designMax ?></span></span>
    </div>
    <p class="fb-text"><?= htmlspecialchars(designFeedback($record)) ?></p>
  </div>

  <div class="fb-section">
    <div class="fb-header">
      <span class="fb-title">Demonstration Quality</span>
      <span class="fb-mark"><?= $demoM ?> <span>/ <?= $demoMax ?></span></span>
    </div>
    <p class="fb-text"><?= htmlspecialchars(demoFeedback($record)) ?></p>
  </div>

  <div class="fb-total">
    <span>Demo session subtotal</span>
    <span class="ft-val"><?= $techW + $designT + $demoM ?> / <?= $sessionMax ?></span>
  </div>
</div>

<!-- Report mark entry -->
<?php $reportMax = round((MARK_WEIGHTS['report'] ?? (defined('REPORT_WEIGHT') ? REPORT_WEIGHT : 0)) * 100); ?>
<div class="card">
  <h2>Critical Report Mark</h2>
  <p style="font-size:.8rem;color:#475569;margin-bottom:.9rem;">Max 500 words. The report is <?= $reportMax ?>% of the total module mark.</p>
  <div class="field">
    <label for="report-mark">Report mark (0–<?= $reportMax ?>)</label>
    <input type="number" id="report-mark" min="0" max="<?= $reportMax ?>" step="0.5"
           value="<?= htmlspecialchars($record['report_mark'] ?? '') ?>">
  </div>
  <div class="field">
    <label for="report-comment">Report feedback (will appear in Moodle feedback column)</label>
    <textarea id="report-comment" rows="6"><?= htmlspecialchars($record['report_comment'] ?? '') ?></textarea>
  </div>

  <div class="total-row" id="total-row">
    <span class="t-label">Projected overall mark</span>
    <span class="t-val" id="projected-total">—</span>
  </div>

  <div style="display:flex;align-items:center;gap:1rem;margin-top:1rem;">
    <button class="btn-primary" id="btn-save">Save report mark</button>
    <span id="save-status"></span>
  </div>
</div>

<script>
<?php if ($hasHtml && $hasPdf): ?>
function showView(fmt) {
  document.getElementById('report-iframe').src = 'submission.php?matric=<?= urlencode($matric) ?>&fmt=' + fmt;
  document.getElementById('btn-html').classList.toggle('active', fmt === 'html');
  document.getElementById('btn-pdf').classList.toggle('active', fmt === 'pdf');
}
<?php endif; ?>

const demoSubtotal = <?= json_encode($techW + $designT + $demoM) ?>;

document.getElementById('report-mark').addEventListener('input', updateTotal);

function updateTotal() {
  const rMark = parseFloat(document.getElementById('report-mark').value);
  if (isNaN(rMark)) {
    document.getElementById('projected-total').textContent = '—';
    return;
  }
  const total = (demoSubtotal + rMark).toFixed(1);
  document.getElementById('projected-total').textContent = total + ' / 100';
}

updateTotal();

document.getElementById('btn-save').addEventListener('click', async () => {
  const btn    = document.getElementById('btn-save');
  const status = document.getElementById('save-status');
  const rMark  = parseFloat(document.getElementById('report-mark').value);

  const reportMax = <?= $reportMax ?>;
  if (isNaN(rMark) || rMark < 0 || rMark > reportMax) {
    alert('Enter a report mark between 0 and ' + reportMax + '.');
    return;
  }

  btn.disabled = true;
  status.textContent = 'Saving…';

  try {
    const res = await fetch('save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        mode:           'report',
        matric:         <?= json_encode($record['matric']) ?>,
        report_mark:    rMark,
        report_comment: document.getElementById('report-comment').value.trim(),
      }),
    });
    const json = await res.json();
    if (json.ok) {
      status.textContent = '✓ Saved';
      status.className   = 'saved';
      btn.disabled = false;
    } else {
      throw new Error(json.error);
    }
  } catch(e) {
    status.textContent = 'Error: ' + e.message;
    status.className   = 'save-error';
    btn.disabled = false;
  }
});
</script>

<?php endif; ?>

</div><!-- end .form-pane -->

</main>
<script>
initMatricAutocomplete('matric-in', <?= json_encode($matricList) ?>);
document.querySelector('form[method=get]').addEventListener('submit', function(e) {
  if (!/^\d{8}$/.test(document.getElementById('matric-in').value.trim())) {
    e.preventDefault();
    alert('Please enter a valid 8-digit matriculation number.');
  }
});
</script>
</body>
</html>
