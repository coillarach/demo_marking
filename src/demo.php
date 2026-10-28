<?php
require_once __DIR__ . '/auth_check.php';
$studentsFileReady = file_exists(DATA_DIR . 'students.csv');
$matricList = readStudentList();
$seMode     = array_key_exists('communication', MARK_WEIGHTS);
$techMax    = round(MARK_WEIGHTS['tech']   * 100);
$designMax  = $seMode ? 0 : round(MARK_WEIGHTS['design'] * 100);
$demoMax    = $seMode ? round(MARK_WEIGHTS['communication'] * 100) : round(MARK_WEIGHTS['demo'] * 100);
$reportMax  = round((MARK_WEIGHTS['report'] ?? (hasReport() ? REPORT_WEIGHT : 0)) * 100);
$sessionMax    = $seMode ? 100 : ($techMax + $designMax + $demoMax);
// A module that doesn't score demonstration quality (weight 0) hides that section.
// The elements stay in the DOM because the page script reads them.
$hideDemoSection = !$seMode && $demoMax == 0;
// Minutes of Q&A left once the presentation phase ends (TIMER_BREAK seconds remain)
$qaMins = (int)round(TIMER_BREAK / 60);
$preloadMatric = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['matric'] ?? '');
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= MODULE_ID ?> - Demo Marking Sheet</title>
<link rel="stylesheet" href="static/marking.css?date=31May2026   ">
<link rel="stylesheet" href="static/demo.css?date=31May2026">
<script src="static/matric-autocomplete.js"></script>
</head>
<body>

<?php
$navLinks = [];
if (hasReport()) $navLinks[] = ['label' => 'Report form', 'href' => 'report.php'];
$navLinks[] = ['label' => 'Export CSV', 'href' => 'export.php'];
$navLinks[] = ['label' => 'Configure', 'href' => 'configure.php'];
if (defined('ADMIN_URL')) $navLinks[] = ['label' => 'Documentation', 'href' => ADMIN_URL . '/docs.php', 'external' => true];
$navLinks[] = ['label' => 'Sign out', 'href' => 'logout.php'];
render_page_header(MODULE_ID . ' - Demo Marking', $navLinks, $currentMarkerName);
?>

<main>

<?php if (!$studentsFileReady): ?>
<div class="alert alert-warning">
  The Moodle offline grading marksheet has not been uploaded. Student autocomplete and participant IDs in the CSV export will be unavailable. Upload it via <a href="configure.php">Configure</a>.
</div>
<?php endif; ?>

<!-- ── Section 0: Identification ─────────────────────────────────── -->
<div class="card">
  <h2><span class="step">0</span> Session Details</h2>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
    <div class="field">
      <label>Marker</label>
      <div class="field-static"><?= htmlspecialchars($currentMarkerName) ?></div>
    </div>
    <div class="field">
      <label for="matric">Student Matriculation Number</label>
      <input type="text" id="matric" placeholder="e.g. 40123456" maxlength="20" required>
    </div>
  </div>
  <div id="load-status" style="margin-top:.5rem;font-size:.8rem;display:none;"></div>
</div>

<div id="main-sections">

<!-- ── Section 1: Presentation ────────────────────────────────────── -->
<div class="card">
  <?php $presMins = round((TIMER_TOTAL - TIMER_BREAK) / 60); ?>
  <h2><span class="step">1</span> Presentation (first <?= $presMins ?> minute<?= $presMins !== 1 ? 's' : '' ?> - observe)</h2>
  <div class="field">
    <label><?= htmlspecialchars(ui_text('setup_label')) ?></label>
    <div class="radio-group">
      <label><input type="radio" name="setup_ready" value="yes"> Yes</label>
      <label><input type="radio" name="setup_ready" value="no"> No - had to wait</label>
    </div>
  </div>
  <div class="field">
    <label><?= htmlspecialchars(ui_text('presented_label')) ?></label>
    <div class="radio-group">
      <label><input type="radio" name="presented_clearly" value="yes"> Yes</label>
      <label><input type="radio" name="presented_clearly" value="partial"> Partial - needed prompting</label>
      <label><input type="radio" name="presented_clearly" value="no"> No - largely unable to present</label>
    </div>
  </div>
</div>

<!-- ── Section 2: Features Present ───────────────────────────────── -->
<div class="card">
  <h2><span class="step">2</span> <?= htmlspecialchars(ui_text('features_heading')) ?> <small style="font-weight:400;font-size:.8rem;color:#64748b;"><?= htmlspecialchars(ui_text('features_hint')) ?></small></h2>
  <p style="font-size:.8rem;color:#64748b;margin-bottom:.75rem;"><?= htmlspecialchars(ui_text('features_intro')) ?></p>

  <div class="feature-grid" id="feature-grid">
    <?php foreach (QUESTIONS as $slug => $feature): ?>
    <label class="feature-item tier-<?= htmlspecialchars($feature['tier']) ?>" id="fi-<?= htmlspecialchars($slug) ?>">
      <input type="checkbox" name="features" value="<?= htmlspecialchars($slug) ?>" id="f-<?= htmlspecialchars($slug) ?>">
      <div><div class="fi-label"><?= htmlspecialchars($feature['label']) ?></div><div class="fi-tier"><?= htmlspecialchars(ucfirst($feature['tier'])) ?></div></div>
    </label>
    <?php endforeach; ?>
  </div>

  <div id="tier-indicator" style="margin-top:1rem;">No features ticked - select features above</div>
</div>

<?php if (!empty(DESIGN_CRITERIA)): ?>
<!-- ── Section 3: Design & Usability ──────────────────────────────── -->
<div class="card" id="section-design">
  <h2><span class="step">3</span> Design &amp; Usability <small style="font-weight:400;font-size:.8rem;color:#64748b;">(<?= $designMax ?>% of total - observe during presentation)</small></h2>

  <?php foreach (DESIGN_CRITERIA as $dKey => $dCrit): ?>
  <div class="design-row">
    <div class="d-label"><?= htmlspecialchars($dCrit['label']) ?><?php if (!empty($dCrit['description'])): ?><small><?= htmlspecialchars($dCrit['description']) ?></small><?php endif; ?></div>
    <div class="design-score-group">
      <label><input type="radio" name="<?= htmlspecialchars($dKey) ?>" value="0" checked> 0</label>
      <label><input type="radio" name="<?= htmlspecialchars($dKey) ?>" value="2"> 2</label>
      <?php if ((int)$dCrit['max'] >= 4): ?>
      <label><input type="radio" name="<?= htmlspecialchars($dKey) ?>" value="4"> 4</label>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <div class="design-total-row">
    <span>Design mark (capped at <?= $designMax ?>):</span>
    <span id="design-total-display">0</span> <span>/ <?= $designMax ?></span>
  </div>
</div>
<?php endif; ?>

<!-- ── Section 4: Knowledge Check ────────────────────────────────── -->
<div class="card" id="card-kq"<?= empty(KNOWLEDGE_QUESTIONS) ? ' style="display:none"' : '' ?>>
  <h2><span class="step">4</span> Knowledge Check <small style="font-weight:400;font-size:.8rem;color:#64748b;">(expand highlighted categories to ask at least one question)</small></h2>
  <p style="font-size:.8rem;color:#64748b;margin-bottom:.9rem;">
    Expand a technology section and select questions to ask. Categories marked <strong>Required</strong> must have at least one question answered before saving. The student's responses will be included in the feedback comment.
  </p>
  <?php foreach (KNOWLEDGE_QUESTIONS as $techKey => $techGroup):
    $reqGroup = $techGroup['require_one'] ?? null;
  ?>
  <details class="kq-group" data-tech="<?= htmlspecialchars($techKey) ?>"<?= $reqGroup ? ' data-require-group="' . htmlspecialchars($reqGroup) . '"' : '' ?>>
    <summary class="kq-summary">
      <?= htmlspecialchars($techGroup['label']) ?>
      <?php if ($reqGroup): ?><span class="kq-req-badge">Required</span><?php endif; ?>
    </summary>
    <div class="kq-body">
      <?php foreach ($techGroup['questions'] as $qi => $question): ?>
      <div class="kq-item">
        <p class="kq-q"><?= htmlspecialchars($question['q']) ?></p>
        <p class="kq-hint"><?= htmlspecialchars($question['hint']) ?></p>
        <div class="kq-answers">
          <label class="kqa-correct"><input type="radio" name="kq_<?= $techKey ?>_<?= $qi ?>" value="correct"> Correct</label>
          <label class="kqa-partial"><input type="radio" name="kq_<?= $techKey ?>_<?= $qi ?>" value="partial"> Partial</label>
          <label class="kqa-no"><input type="radio" name="kq_<?= $techKey ?>_<?= $qi ?>" value="no"> Could not answer</label>
        </div>
      </div>
      <?php endforeach; ?>
      <button type="button" class="btn-add-kq" onclick="addCustomKQ(this, '<?= htmlspecialchars($techKey) ?>')">+ Add custom question</button>
    </div>
  </details>
  <?php endforeach; ?>
</div>

<!-- ── Section 5: Q&A ─────────────────────────────────────────────── -->
<div class="card">
  <h2><span class="step">5</span> Q&amp;A (last <?= $qaMins ?> minute<?= $qaMins !== 1 ? 's' : '' ?>)</h2>
  <p style="font-size:.8rem;color:#64748b;margin-bottom:1rem;">
    Questions are shown for ticked features. Higher-tier features are shown first. Lower-tier sections are marked <em>cursory</em> when a higher tier is present - a brief check is sufficient.
  </p>
  <div id="qa-container">
    <p style="color:#94a3b8;font-size:.875rem;">Tick features in Section 2 to load questions.</p>
  </div>
</div>

<!-- ── Section 6: Technical Mark ──────────────────────────────────── -->
<div class="card">
  <h2><span class="step">6</span> <?php if ($seMode): ?>Understanding of Code<?php else: ?><?= htmlspecialchars(ui_text('tech_heading')) ?><?php endif; ?> <small style="font-weight:400;font-size:.8rem;color:#64748b;">(<?= $techMax ?>% of total)</small></h2>
  <p id="tech-range-hint" style="margin-bottom:.5rem;">Tick features above to see the suggested range.</p>

  <!-- Row 1: raw mark entry -->
  <div class="mark-row">
    <div class="field" style="margin:0;">
      <label for="tech-mark">Raw mark (%)</label>
      <input type="number" id="tech-mark" min="0" max="100" step="1" value="0">
    </div>
  </div>

  <!-- Row 2: knowledge penalty display (hidden until Q&A / KQ answered) -->
  <div class="penalty-row" id="penalty-row" style="display:none">
    <span>Knowledge factor: <strong id="kf-display">1.00</strong></span>
    <span class="penalty-arrow">&#8594;</span>
    <span>Adjusted: <strong id="kf-adjusted-pct">0%</strong></span>
  </div>

  <!-- Row 3: final mark (pre-populated from penalty, marker can override) -->
  <div class="mark-row">
    <div class="field" style="margin:0;">
      <label for="tech-final">Final mark (%) <small style="font-weight:400;color:#64748b">override if needed</small></label>
      <input type="number" id="tech-final" min="0" max="100" step="1" value="0">
    </div>
    <span class="weighted-label">Weighted: <span id="tech-weighted">0</span> / <?= $techMax ?></span>
  </div>

  <div class="field" style="margin-top:.75rem;" id="tech-note-field">
    <label for="tech-note">Marker comment <small id="tech-note-hint" style="font-weight:400;color:#64748b">(optional)</small></label>
    <textarea id="tech-note" placeholder="e.g. Code quality was notably strong; all features well-implemented and explained"></textarea>
  </div>
</div>

<!-- ── Section 7: Demo Quality / Communication ────────────────────── -->
<div class="card"<?= $hideDemoSection ? ' style="display:none"' : '' ?>>
  <h2><span class="step">7</span> <?php if ($seMode): ?>Communication &amp; Presentation<?php else: ?>Demonstration Quality<?php endif; ?> <small style="font-weight:400;font-size:.8rem;color:#64748b;">(<?= $demoMax ?>% of total<?php if (!$seMode): ?> - holistic<?php endif; ?>)</small></h2>
  <div id="presentation-reminder" class="presentation-reminder" style="display:none"></div>
  <?php if ($seMode): ?>
  <p style="font-size:.8rem;color:#475569;margin-bottom:.75rem;">Select a band to reflect the student's overall communication quality during the demo.</p>
  <?php else: ?>
  <p style="font-size:.8rem;color:#475569;margin-bottom:.75rem;">Slight bias towards the student's ability to explain their code.</p>
  <?php endif; ?>
  <div class="demo-band-row">
    <div>
      <div class="field" style="margin-bottom:.25rem;">
        <label for="demo-band">Quality band</label>
        <select id="demo-band">
          <option value="">- select -</option>
          <?php if ($seMode): ?>
          <option value="excellent" data-min="9"  data-max="10" data-mid="9">Excellent (9–10)</option>
          <option value="good"      data-min="7"  data-max="8"  data-mid="7">Good (7–8)</option>
          <option value="adequate"  data-min="4"  data-max="6"  data-mid="5">Adequate (4–6)</option>
          <option value="limited"   data-min="2"  data-max="3"  data-mid="3">Limited (2–3)</option>
          <option value="poor"      data-min="0"  data-max="1"  data-mid="1">Poor (0–1)</option>
          <?php else: ?>
          <option value="excellent" data-min="23" data-max="25" data-mid="24">Excellent (23–25)</option>
          <option value="good"      data-min="18" data-max="22" data-mid="20">Good (18–22)</option>
          <option value="adequate"  data-min="12" data-max="17" data-mid="14">Adequate (12–17)</option>
          <option value="limited"   data-min="6"  data-max="11" data-mid="8">Limited (6–11)</option>
          <option value="poor"      data-min="0"  data-max="5"  data-mid="2">Poor (0–5)</option>
          <?php endif; ?>
        </select>
      </div>
    </div>
    <div>
      <div class="field" style="margin-bottom:.25rem;"><label>&nbsp;</label></div>
      <div id="band-descriptor" class="band-descriptor">Select a band to see the descriptor.</div>
    </div>
  </div>
  <div class="demo-fine-row">
    <label for="demo-mark-input">Fine-tune mark:</label>
    <input type="number" id="demo-mark-input" min="0" max="<?= $demoMax ?>" step="1" value="0" disabled>
    <span id="demo-mark-display">0 / <?= $demoMax ?></span>
  </div>
  <div class="field" style="margin-top:.75rem;" id="demo-comment-field">
    <label for="demo-comment">Marker comment <small id="demo-comment-hint" style="font-weight:400;color:#64748b">(optional)</small></label>
    <textarea id="demo-comment" placeholder="e.g. Fluent presentation; answered all questions confidently and unprompted"></textarea>
  </div>
</div>

<?php if ($seMode): ?>
<!-- ── Section 8: Mark Summary ────────────────────────────────────── -->
<div class="card" id="section-summary">
  <h2><span class="step">8</span> Mark Summary</h2>
  <table id="summary-table" style="width:100%;border-collapse:collapse;font-size:.875rem;">
    <thead>
      <tr style="border-bottom:2px solid #e2e8f0;text-align:left;">
        <th style="padding:.4rem .6rem;">Criterion</th>
        <th style="padding:.4rem .6rem;">Raw mark</th>
        <th style="padding:.4rem .6rem;">KQ factor</th>
        <th style="padding:.4rem .6rem;">Adjusted</th>
        <th style="padding:.4rem .6rem;">Weight</th>
        <th style="padding:.4rem .6rem;">Contribution</th>
      </tr>
    </thead>
    <tbody id="summary-rows"></tbody>
    <tfoot>
      <tr style="border-top:2px solid #e2e8f0;font-weight:600;">
        <td colspan="5" style="padding:.5rem .6rem;">Presentation mark (out of 100)</td>
        <td id="summary-total" style="padding:.5rem .6rem;">-</td>
      </tr>
    </tfoot>
  </table>
  <div class="field" style="margin-top:1rem;">
    <label>Overall comment <small style="font-weight:400;color:#64748b;">(optional)</small></label>
    <textarea id="overall_comment" rows="3" placeholder="Summary observations…"></textarea>
  </div>
</div>
<?php endif; ?>

<!-- ── Mark summary ───────────────────────────────────────────────── -->
<div class="mark-summary" id="mark-summary">
<?php
$sumIdMap = ['tech' => 'sum-tech', 'design' => 'sum-design', 'demo' => 'sum-demo',
             'communication' => 'sum-demo', 'testing' => 'sum-testing',
             'process' => 'sum-process', 'ai_usage' => 'sum-ai'];
foreach (DEMO_CRITERIA_LABELS as $key => $label) {
    if ($key === 'design' && empty(DESIGN_CRITERIA)) continue;
    $max   = round((MARK_WEIGHTS[$key] ?? 0) * 100);
    $sumId = $sumIdMap[$key] ?? 'sum-' . $key;
    $short = htmlspecialchars(explode(' ', $label)[0]); // first word as short label
    echo "  <div class=\"ms-item\"><div class=\"ms-label\">{$short} ({$max})</div><div class=\"ms-val\" id=\"{$sumId}\">0</div></div>\n";
}
if (!$seMode): ?>
  <div class="ms-item"><div class="ms-label">Report (<?= $reportMax ?>)</div><div class="ms-val">-</div></div>
<?php endif; ?>
  <div class="ms-item ms-total"><div class="ms-label"><?= $seMode ? 'Presentation mark' : 'Session subtotal' ?></div><div class="ms-val" id="sum-total">0 / <?= $seMode ? 100 : $sessionMax ?></div></div>
</div>

</div><!-- end #main-sections -->

<!-- ── Authorship ─────────────────────────────────────────────────── -->
<div class="card" id="card-authorship">
  <h2>Authorship Confirmation</h2>
  <p style="font-size:.875rem;color:#475569;margin-bottom:.75rem;">
    Based on the demonstration and Q&amp;A, confirm whether there is sufficient evidence that the <?= htmlspecialchars(ui_text('authorship_subject')) ?> is the student's own work.
  </p>
  <label style="display:flex;align-items:flex-start;gap:.6rem;cursor:pointer;">
    <input type="checkbox" id="authorship-fail" style="margin-top:.2rem;width:1.1rem;height:1.1rem;flex-shrink:0;cursor:pointer;">
    <span><strong>Refer for academic misconduct</strong> - authorship of the <?= htmlspecialchars(ui_text('authorship_subject')) ?> could not be confirmed</span>
  </label>
  <div id="authorship-warning" class="alert alert-danger" style="display:none;margin-top:.75rem;">
    <strong>Academic Misconduct:</strong> Do not award a mark. Complete the form and submit - a flag will be recorded. Follow the standard referral procedure after the session.
    <div style="margin-top:.75rem;">
      <label for="misconduct-comment" style="font-weight:600;font-size:.875rem;">Rationale / observations <span style="font-weight:400;">(required for referral)</span></label>
      <textarea id="misconduct-comment" rows="3"
                style="width:100%;margin-top:.35rem;padding:.45rem .65rem;border:1px solid #fca5a5;border-radius:4px;font-size:.875rem;font-family:inherit;resize:vertical;"
                placeholder="Describe what led to the referral…"></textarea>
    </div>
  </div>
</div>

<!-- ── Submit ─────────────────────────────────────────────────────── -->
<div class="card">
  <div class="submit-row">
    <button class="btn-primary" id="btn-submit">Save &amp; submit</button>
    <button class="btn-reset" type="button" onclick="if(confirm('Reset the form? All entries will be lost.')) resetForm()">Reset form</button>
    <span id="save-status"></span>
  </div>
</div>

</main>

<div id="reauth-modal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;">
  <div style="background:#fff;border-radius:8px;padding:1.75rem;width:340px;max-width:92%;box-shadow:0 8px 32px rgba(0,0,0,.18);">
    <h2 style="margin:0 0 .4rem;font-size:1.1rem;">Session expired</h2>
    <p style="font-size:.85rem;color:#475569;margin-bottom:1rem;">Please sign in again — your entered data has been preserved.</p>
    <div id="reauth-error" style="display:none;color:#dc2626;margin-bottom:.75rem;font-size:.875rem;"></div>
    <div class="field"><label for="reauth-id">Marker ID</label><input type="text" id="reauth-id" maxlength="8" inputmode="numeric"></div>
    <div class="field"><label for="reauth-pw">Password</label><input type="password" id="reauth-pw"></div>
    <div style="display:flex;gap:.5rem;margin-top:1rem;">
      <button class="btn-primary" id="reauth-submit">Sign in</button>
      <button class="btn-reset" type="button" id="reauth-cancel">Cancel</button>
    </div>
  </div>
</div>

<!-- ── Demo timer (fixed left margin) ────────────────────────────── -->
<div id="demo-timer">
  <div id="timer-phase">Ready</div>
  <div id="timer-display"><?= floor(TIMER_TOTAL / 60) ?>:<?= str_pad(TIMER_TOTAL % 60, 2, '0', STR_PAD_LEFT) ?></div>
  <button id="btn-timer-start" onclick="timerStart()">Start</button>
  <button id="btn-timer-reset" onclick="timerReset()" style="display:none">Reset</button>
</div>

<script>
// ─── Config from PHP ──────────────────────────────────────────────
const CONFIG = <?= json_encode([
    'features'       => QUESTIONS,
    'tierRanges'     => TIER_RANGES,
    'tierRules'      => TIER_RULES,
    'weights'        => MARK_WEIGHTS,
    'criteriaLabels' => DEMO_CRITERIA_LABELS,
    'timerTotal'     => TIMER_TOTAL,
    'timerBreak'     => TIMER_BREAK,
]) ?>;
const DESIGN_KEYS  = <?= json_encode(array_keys(DESIGN_CRITERIA)) ?>;
const MARKER_NAME  = <?= json_encode($currentMarkerName) ?>;
const MARKER_ID    = <?= json_encode($currentMarkerId) ?>;
<?php
$kqRequiredGroups = [];
foreach (KNOWLEDGE_QUESTIONS as $key => $group) {
    if (!empty($group['require_one'])) $kqRequiredGroups[$key] = $group['require_one'];
}
?>
const KQ_REQUIRED_GROUPS = <?= json_encode($kqRequiredGroups) ?>;
<?php if ($seMode): ?>
const SE_MODE = true;
const KQ_CRITERION_MAP = <?= json_encode(KQ_CRITERION_MAP) ?>;
const CRIT_WEIGHTS = {
    tech:          <?= MARK_WEIGHTS['tech'] ?>,
    testing:       <?= MARK_WEIGHTS['testing'] ?>,
    process:       <?= MARK_WEIGHTS['process'] ?>,
    ai_usage:      <?= MARK_WEIGHTS['ai_usage'] ?>,
    communication: <?= MARK_WEIGHTS['communication'] ?>,
};
const DEMO_MAX = <?= $demoMax ?>;
<?php else: ?>
const SE_MODE = false;
const DEMO_MAX = <?= $demoMax ?>;
<?php endif; ?>

// ─── Band descriptors ─────────────────────────────────────────────
const BAND_DESCRIPTORS = {
  excellent: 'Presented fluently with no setup issues. Explained all aspects of their code confidently and answered every question without hesitation.',
  good:      'Clear presentation. Explained most code well; minor gaps under questioning but recovered without significant prompting.',
  adequate:  'Some hesitation in presentation. Explained core features but required prompting on some questions.',
  limited:   'Significant gaps. Could not explain several key sections without prompting; limited confidence under questioning.',
  poor:      'Unable to adequately explain the code. Little evidence during the demo that the student wrote the submitted work.'
};

// ─── State ────────────────────────────────────────────────────────
let currentTierKey  = null;
let techAdjustedPct = null; // penalty-adjusted recommendation for tech-final
let loadedMatric    = null; // matric number of the currently loaded record
let formSnapshot    = null; // JSON snapshot of collectData() at last save/load

// ─── Dirty tracking ───────────────────────────────────────────────
function captureSnapshot() {
  const data = collectData();
  delete data.matric;
  formSnapshot = JSON.stringify(data);
}

function isDirty() {
  if (!formSnapshot) return false;
  const data = collectData();
  delete data.matric;
  return JSON.stringify(data) !== formSnapshot;
}

// ─── Load-status indicator ────────────────────────────────────────
function setLoadStatus(state, matric) {
  const el = document.getElementById('load-status');
  el.className = '';
  switch (state) {
    case 'loading':
      el.textContent = 'Loading record…';
      el.style.color = '#64748b';
      el.style.display = 'block';
      break;
    case 'loaded':
      el.textContent = '✓ Existing record loaded - editing ' + (matric || '');
      el.style.color = '#065f46';
      el.style.display = 'block';
      break;
    case 'new':
      el.textContent = 'New student - no existing record';
      el.style.color = '#64748b';
      el.style.display = 'block';
      break;
    case 'error':
      el.textContent = 'Could not check for existing record';
      el.style.color = '#dc2626';
      el.style.display = 'block';
      break;
    case 'hidden':
    default:
      el.style.display = 'none';
      break;
  }
}

// ─── Load record into form ────────────────────────────────────────
function loadRecord(r) {
  const matric = r.matric || '';

  // Full reset (clears loadedMatric / formSnapshot too)
  resetForm();

  // Session details
  document.getElementById('matric').value = matric;


  // Presentation
  if (r.setup_ready) {
    const el = document.querySelector(`input[name=setup_ready][value="${r.setup_ready}"]`);
    if (el) el.checked = true;
  }
  if (r.presented_clearly) {
    const el = document.querySelector(`input[name=presented_clearly][value="${r.presented_clearly}"]`);
    if (el) el.checked = true;
  }
  updatePresentationReminder();

  // Features → tier → Q&A
  (r.features || []).forEach(f => {
    const cb = document.getElementById(`f-${f}`);
    if (cb) {
      cb.checked = true;
      cb.closest('.feature-item').classList.add('checked');
    }
  });
  updateTier();
  buildQA();

  // Un-cursory feature sections that have saved Q&A answers
  const featuresWithAnswers = new Set();
  Object.keys(r.qa || {}).forEach(name => {
    Object.keys(CONFIG.features).forEach(fk => {
      if (name.startsWith(`qa_${fk}_`)) featuresWithAnswers.add(fk);
    });
  });
  featuresWithAnswers.forEach(fk => {
    const wrap = document.querySelector(`.qa-feature[data-feature="${fk}"]`);
    if (wrap && wrap.classList.contains('cursory')) {
      const toggle = wrap.querySelector('.cursory-toggle input[type=checkbox]');
      if (toggle) { toggle.checked = false; toggleCursory(toggle); }
    }
  });

  // Standard Q&A answers
  const qaEntries = Object.entries(r.qa || {});
  qaEntries.forEach(([name, val]) => {
    if (name.includes('_cust_')) return; // handle custom items separately
    const el = document.querySelector(`input[name="${name}"][value="${val}"]`);
    if (el) el.checked = true;
  });

  // Custom Q&A items
  const custQAByFeature = {};
  qaEntries.forEach(([name, val]) => {
    const m = name.match(/^qa_(.+)_cust_(\d+)$/);
    if (!m) return;
    const [, fk, idxStr] = m;
    if (!custQAByFeature[fk]) custQAByFeature[fk] = {};
    const idx = parseInt(idxStr);
    if (!custQAByFeature[fk][idx]) custQAByFeature[fk][idx] = {};
    custQAByFeature[fk][idx].answer = val;
  });
  qaEntries.forEach(([name, val]) => {
    const m = name.match(/^qa_(.+)_cust_(\d+)_text$/);
    if (!m) return;
    const [, fk, idxStr] = m;
    const idx = parseInt(idxStr);
    if (!custQAByFeature[fk]) custQAByFeature[fk] = {};
    if (!custQAByFeature[fk][idx]) custQAByFeature[fk][idx] = {};
    custQAByFeature[fk][idx].text = val;
  });
  Object.entries(custQAByFeature).forEach(([fk, byIdx]) => {
    Object.keys(byIdx).map(Number).sort((a,b) => a-b).forEach(idx => {
      const wrap = document.querySelector(`.qa-feature[data-feature="${fk}"]`);
      if (!wrap) return;
      const addBtn = wrap.querySelector('.btn-add-qa');
      if (!addBtn) return;
      addCustomQA(addBtn, fk);
      const item = addBtn.previousElementSibling;
      if (!item) return;
      const { answer, text } = byIdx[idx];
      if (text) item.querySelector('.qa-custom-q').value = text;
      if (answer) {
        const radio = item.querySelector(`input[type=radio][value="${answer}"]`);
        if (radio) radio.checked = true;
      }
    });
  });

  // Design scores
  if (r.design) {
    DESIGN_KEYS.forEach(f => {
      const val = String(r.design[f] ?? 0);
      const el = document.querySelector(`input[name=${f}][value="${val}"]`);
      if (el) el.checked = true;
    });
    updateDesign();
  }

  // Knowledge check answers
  const kqEntries = Object.entries(r.kq || {});
  kqEntries.forEach(([name, val]) => {
    if (name.includes('_cust_')) return;
    const el = document.querySelector(`input[name="${name}"][value="${val}"]`);
    if (el) el.checked = true;
  });

  // Custom KQ items
  const custKQByTech = {};
  kqEntries.forEach(([name, val]) => {
    const m = name.match(/^kq_(.+)_cust_(\d+)$/);
    if (!m) return;
    const [, tk, idxStr] = m;
    if (!custKQByTech[tk]) custKQByTech[tk] = {};
    const idx = parseInt(idxStr);
    if (!custKQByTech[tk][idx]) custKQByTech[tk][idx] = {};
    custKQByTech[tk][idx].answer = val;
  });
  kqEntries.forEach(([name, val]) => {
    const m = name.match(/^kq_(.+)_cust_(\d+)_text$/);
    if (!m) return;
    const [, tk, idxStr] = m;
    const idx = parseInt(idxStr);
    if (!custKQByTech[tk]) custKQByTech[tk] = {};
    if (!custKQByTech[tk][idx]) custKQByTech[tk][idx] = {};
    custKQByTech[tk][idx].text = val;
  });
  Object.entries(custKQByTech).forEach(([tk, byIdx]) => {
    const details = document.querySelector(`.kq-group[data-tech="${tk}"]`);
    if (!details) return;
    const addBtn = details.querySelector('.btn-add-kq');
    if (!addBtn) return;
    Object.keys(byIdx).map(Number).sort((a,b) => a-b).forEach(idx => {
      addCustomKQ(addBtn, tk);
      const item = addBtn.previousElementSibling;
      if (!item) return;
      const { answer, text } = byIdx[idx];
      if (text) item.querySelector('.kq-custom-q').value = text;
      if (answer) {
        const radio = item.querySelector(`input[type=radio][value="${answer}"]`);
        if (radio) radio.checked = true;
      }
    });
  });

  // Tech mark: set raw, compute penalty, then apply saved final if different
  const techMark = document.getElementById('tech-mark');
  techMark.value = r.tech_percent ?? 0;
  techMark.dataset.edited = '1';
  delete document.getElementById('tech-final').dataset.edited;
  updateTechWeighted(); // sets tech-final to penalty-adjusted value

  // Override tech-final with saved value if it differs from what penalty calculated
  const savedFinalPct = r.tech_weighted != null ? Math.round(parseFloat(r.tech_weighted) / CONFIG.weights.tech) : null;
  if (savedFinalPct !== null) {
    const finalInp = document.getElementById('tech-final');
    const calcFinal = parseInt(finalInp.value) || 0;
    if (savedFinalPct !== calcFinal) {
      finalInp.value = savedFinalPct;
      finalInp.dataset.edited = '1';
      const weighted = (savedFinalPct * CONFIG.weights.tech).toFixed(1);
      document.getElementById('tech-weighted').textContent = weighted;
      document.getElementById('sum-tech').textContent = weighted;
      updateTotal();
      setCommentRequired('tech-note-field', 'tech-note-hint', techAdjustedPct !== null && savedFinalPct !== techAdjustedPct);
    }
  }
  if (r.tech_note) document.getElementById('tech-note').value = r.tech_note;

  // Demo quality
  if (r.demo_band) {
    const bandSel = document.getElementById('demo-band');
    bandSel.value = r.demo_band;
    bandSel.dispatchEvent(new Event('change')); // sets demoBandMid, enables input, shows descriptor
    if (r.demo_mark != null) {
      document.getElementById('demo-mark-input').value = r.demo_mark;
      updateDemoDisplay();
    }
  }
  if (r.demo_comment) document.getElementById('demo-comment').value = r.demo_comment;

  // Authorship
  const authFail = r.authorship === 'fail';
  document.getElementById('authorship-fail').checked = authFail;
  document.getElementById('misconduct-comment').value = r.misconduct_comment ?? '';
  updateAuthorshipWarning();

  // SE mode: restore overall_comment; summary table updates via updateTotal() already called above
  if (SE_MODE) {
    const oc = document.getElementById('overall_comment');
    if (oc && r.overall_comment) oc.value = r.overall_comment;
    updateSummaryTable();
  }

  // Re-evaluate comment-required states from loaded values
  const finalPct = parseInt(document.getElementById('tech-final').value) || 0;
  setCommentRequired('tech-note-field', 'tech-note-hint', techAdjustedPct !== null && finalPct !== techAdjustedPct);
  const demoVal = parseInt(document.getElementById('demo-mark-input').value) || 0;
  setCommentRequired('demo-comment-field', 'demo-comment-hint', demoBandMid !== null && demoVal !== demoBandMid);

  loadedMatric = matric;
  captureSnapshot();
  setLoadStatus('loaded', matric);
  updateKQRequiredBadges();
}

// ─── Fetch and load record ────────────────────────────────────────
async function fetchAndLoad(matric) {
  setLoadStatus('loading');
  const res = await fetch(`load_record.php?matric=${encodeURIComponent(matric)}`, {
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  });
  if (res.status === 401) {
    setLoadStatus('hidden');
    showReauthModal(() => fetchAndLoad(matric));
    return;
  }
  try {
    const json = await res.json();
    if (!json.found) {
      resetForm();
      document.getElementById('matric').value = matric;
      loadedMatric = matric;
      captureSnapshot();
      setLoadStatus('new');
      return;
    }
    loadRecord(json);
  } catch (_) {
    setLoadStatus('error');
  }
}

// ─── Handle matric field change ───────────────────────────────────
function handleMatricChange() {
  const val = document.getElementById('matric').value.trim();
  if (!/^\d{8}$/.test(val)) return;
  if (val === loadedMatric) return;

  if (isDirty()) {
    if (!confirm(`Discard unsaved changes and load student ${val}?`)) {
      // Restore previous matric
      document.getElementById('matric').value = loadedMatric || '';
      return;
    }
    // User confirmed discard - clear snapshot so beforeunload doesn't fire a second prompt
    formSnapshot = null;
  }

  fetchAndLoad(val);
}

// ─── Authorship gate ──────────────────────────────────────────────
function updateAuthorshipWarning() {
  const checked = document.getElementById('authorship-fail').checked;
  const hasComment = document.getElementById('misconduct-comment').value.trim() !== '';
  document.getElementById('authorship-warning').style.display = (checked || hasComment) ? 'block' : 'none';
}
document.getElementById('authorship-fail').addEventListener('change', updateAuthorshipWarning);

// ─── Matric auto-load ─────────────────────────────────────────────
document.getElementById('matric').addEventListener('change', handleMatricChange);

// ─── Unsaved-changes guard ────────────────────────────────────────
window.addEventListener('beforeunload', e => {
  if (isDirty()) { e.preventDefault(); e.returnValue = ''; }
});

// ─── Feature checkboxes → tier + Q&A ─────────────────────────────
document.querySelectorAll('input[name=features]').forEach(cb => {
  cb.addEventListener('change', () => {
    const fi = cb.closest('.feature-item');
    fi.classList.toggle('checked', cb.checked);
    updateTier();
    buildQA();
  });
});

function getChecked(tier) {
  return [...document.querySelectorAll('input[name=features]:checked')]
    .filter(cb => {
      const f = CONFIG.features[cb.value];
      return f && f.tier === tier;
    });
}

function updateTier() {
  const adv   = getChecked('advanced').length;
  const inter = getChecked('intermediate').length;
  const bas   = getChecked('basic').length;
  const total = adv + inter + bas;

  let key = null;
  if (total > 0) {
    for (const rule of CONFIG.tierRules) {
      if (
        (rule.minAdv   == null || adv   >= rule.minAdv) &&
        (rule.minInter == null || inter >= rule.minInter) &&
        (rule.minBasic == null || bas   >= rule.minBasic)
      ) {
        key = rule.key;
        break;
      }
    }
  }

  currentTierKey = key;

  const ind = document.getElementById('tier-indicator');
  ind.className = '';
  if (!key) {
    ind.textContent = 'No features ticked - select features above';
    document.getElementById('tech-range-hint').textContent = 'Tick features above to see the suggested range.';
    return;
  }

  const t = CONFIG.tierRanges[key];
  ind.classList.add(t.cls);
  ind.textContent = `Tier: ${t.label} → suggested mark ${t.min}–${t.max}%`;

  const hint = document.getElementById('tech-range-hint');
  hint.textContent = `Suggested range: ${t.min}–${t.max}% (midpoint ${t.mid}%). Adjust within ±4 using the field below.`;

  const techInput = document.getElementById('tech-mark');
  // Only auto-populate if the marker hasn't typed a custom value
  if (!techInput.dataset.edited) {
    techInput.value = t.mid;
    techInput.min   = Math.max(0, t.min - 4);
    techInput.max   = Math.min(100, t.max + 4);
    delete document.getElementById('tech-final').dataset.edited;
    updateTechWeighted();
  }
}

// ─── Presentation reminder in Section 7 ──────────────────────────
function updatePresentationReminder() {
  const setup     = document.querySelector('input[name=setup_ready]:checked')?.value;
  const presented = document.querySelector('input[name=presented_clearly]:checked')?.value;

  if (!setup && !presented) {
    document.getElementById('presentation-reminder').style.display = 'none';
    return;
  }

  const setupLabels = {
    yes: 'Site was set up and ready before the demo started.',
    no:  'Setup was not complete at the start - time was lost waiting for the environment.',
  };
  const presentedLabels = {
    yes:     'Student presented their features independently without prompting.',
    partial: 'Student needed some prompting to present their features.',
    no:      'Student was largely unable to present their features independently.',
  };

  const items = [];
  if (setup)     items.push(setupLabels[setup]);
  if (presented) items.push(presentedLabels[presented]);

  const el = document.getElementById('presentation-reminder');
  el.innerHTML = '<strong>From Section 2:</strong> ' + items.join(' ');
  el.style.display = '';
}

document.querySelectorAll('input[name=setup_ready], input[name=presented_clearly]').forEach(r => {
  r.addEventListener('change', updatePresentationReminder);
});

// ─── Technical mark ───────────────────────────────────────────────
const techInput = document.getElementById('tech-mark');
techInput.addEventListener('input', () => {
  techInput.dataset.edited = '1';
  delete document.getElementById('tech-final').dataset.edited;
  updateTechWeighted();
});

document.getElementById('tech-final').addEventListener('input', () => {
  document.getElementById('tech-final').dataset.edited = '1';
  const finalPct = parseInt(document.getElementById('tech-final').value) || 0;
  const weighted = (finalPct * CONFIG.weights.tech).toFixed(1);
  document.getElementById('tech-weighted').textContent = weighted;
  document.getElementById('sum-tech').textContent = weighted;
  updateTotal();
  setCommentRequired('tech-note-field', 'tech-note-hint', techAdjustedPct !== null && finalPct !== techAdjustedPct);
});

function computeKnowledgeFactor() {
  let nWell = 0, nPrompt = 0, nNo = 0;
  // Q&A responses (non-disabled = non-cursory sections only)
  document.querySelectorAll('.qa-item input[type=radio]:checked').forEach(r => {
    if (r.disabled) return;
    if (r.value === 'well')   nWell++;
    if (r.value === 'prompt') nPrompt++;
    if (r.value === 'no')     nNo++;
  });
  // Knowledge check responses - SE mode only counts tech-mapped topics for §6 factor
  document.querySelectorAll('input[type=radio][name^=kq_]:checked').forEach(r => {
    if (SE_MODE) {
      const topicKey = getTopicKey(r.name);
      if (!topicKey || KQ_CRITERION_MAP[topicKey] !== 'tech') return;
    }
    if (r.value === 'correct') nWell++;
    if (r.value === 'partial') nPrompt++;
    if (r.value === 'no')      nNo++;
  });
  const nAnswered = nWell + nPrompt + nNo;
  return {
    factor:    nAnswered > 0 ? (nWell * 1.0 + nPrompt * 0.65) / nAnswered : 1.0,
    nAnswered,
  };
}

<?php if ($seMode): ?>
// ─── SE mode helpers ──────────────────────────────────────────────
function getTopicKey(inputName) {
  const body = inputName.slice(3); // strip 'kq_'
  let best = null;
  for (const key of Object.keys(KQ_CRITERION_MAP)) {
    if (body.startsWith(key + '_') && (best === null || key.length > best.length))
      best = key;
  }
  return best;
}

function computeKQFactors() {
  const topicScores = {};
  for (const key of Object.keys(KQ_CRITERION_MAP)) topicScores[key] = { nWell: 0, nTotal: 0 };

  document.querySelectorAll('input[type=radio][name^=kq_]:checked').forEach(r => {
    const tk = getTopicKey(r.name);
    if (!tk || !topicScores[tk]) return;
    topicScores[tk].nTotal++;
    if (r.value === 'correct') topicScores[tk].nWell += 1.0;
    else if (r.value === 'partial') topicScores[tk].nWell += 0.65;
  });

  const critScores = {};
  for (const crit of Object.keys(CRIT_WEIGHTS)) critScores[crit] = { nWell: 0, nTotal: 0 };
  for (const [tk, ck] of Object.entries(KQ_CRITERION_MAP)) {
    critScores[ck].nWell  += topicScores[tk].nWell;
    critScores[ck].nTotal += topicScores[tk].nTotal;
  }

  // Q&A answers also feed the 'tech' factor
  document.querySelectorAll('.qa-item input[type=radio]:checked').forEach(r => {
    if (r.disabled) return;
    critScores['tech'].nTotal++;
    if (r.value === 'well')   critScores['tech'].nWell += 1.0;
    if (r.value === 'prompt') critScores['tech'].nWell += 0.65;
  });

  const factors = {};
  for (const crit of Object.keys(CRIT_WEIGHTS)) {
    const c = critScores[crit];
    factors[crit] = c.nTotal > 0 ? c.nWell / c.nTotal : 1.0;
  }
  return factors;
}

function updateSummaryTable() {
  const factors     = computeKQFactors();
  const techRaw     = parseInt(document.getElementById('tech-mark').value)  || 0;
  const techFinal   = parseInt(document.getElementById('tech-final').value) || 0;
  const demoMark    = parseInt(document.getElementById('demo-mark-input').value) || 0;
  const testingMark = Math.round(100 * factors['testing']);
  const processMark = Math.round(100 * factors['process']);
  const aiMark      = Math.round(100 * factors['ai_usage']);

  setText('sum-testing', (testingMark * CRIT_WEIGHTS.testing).toFixed(1));
  setText('sum-process', (processMark * CRIT_WEIGHTS.process).toFixed(1));
  setText('sum-ai',      (aiMark      * CRIT_WEIGHTS.ai_usage).toFixed(1));

  const markForKey = {
    tech: techFinal, testing: testingMark, process: processMark, ai_usage: aiMark, communication: demoMark,
  };
  const rawForKey = {
    tech: techRaw + '%', testing: '100%', process: '100%', ai_usage: '100%', communication: demoMark,
  };
  const adjForKey = {
    tech: techFinal + '%', testing: testingMark + '%', process: processMark + '%',
    ai_usage: aiMark + '%', communication: `${demoMark} / ${DEMO_MAX}`,
  };
  const rows = Object.entries(CONFIG.criteriaLabels).map(([key, label]) => ({
    label,
    raw:      rawForKey[key]  ?? '-',
    factor:   key !== 'communication' ? (factors[key] ?? null) : null,
    adjusted: adjForKey[key] ?? '-',
    weight:   CRIT_WEIGHTS[key] ?? 0,
    // communication mark is already in weighted form (0–DEMO_MAX); others are 0–100 × weight
    contrib:  key === 'communication'
      ? (markForKey[key] ?? 0)
      : parseFloat(((markForKey[key] ?? 0) * (CRIT_WEIGHTS[key] ?? 0)).toFixed(1)),
  }));

  const tbody = document.getElementById('summary-rows');
  if (!tbody) return;
  tbody.innerHTML = '';
  let total = 0;
  for (const r of rows) {
    total += r.contrib;
    const fCell = r.factor !== null ? r.factor.toFixed(2) : '-';
    tbody.innerHTML += `<tr style="border-bottom:1px solid #f1f5f9;">
      <td style="padding:.35rem .6rem;">${r.label}</td>
      <td style="padding:.35rem .6rem;">${r.raw}</td>
      <td style="padding:.35rem .6rem;">${fCell}</td>
      <td style="padding:.35rem .6rem;">${r.adjusted}</td>
      <td style="padding:.35rem .6rem;">${Math.round(r.weight * 100)}%</td>
      <td style="padding:.35rem .6rem;">${r.contrib.toFixed(1)}</td>
    </tr>`;
  }

  const presTotal = parseFloat(total.toFixed(1));
  setText('summary-total', presTotal.toFixed(1));
  setText('sum-total', `${presTotal.toFixed(1)} / 100`);
}
<?php endif; ?>

function setCommentRequired(fieldId, hintId, required) {
  const hint = document.getElementById(hintId);
  hint.textContent = required ? '(required - mark adjusted from midpoint)' : '(optional)';
  hint.style.color  = required ? '#dc2626' : '#64748b';
  document.getElementById(fieldId).classList.toggle('comment-required', required);
}

function updateTechWeighted() {
  const { factor, nAnswered } = computeKnowledgeFactor();

  const rawPct      = parseFloat(techInput.value) || 0;
  const adjustedPct = Math.round(rawPct * factor); // integer

  const penaltyRow = document.getElementById('penalty-row');
  if (nAnswered > 0) {
    penaltyRow.style.display = '';
    document.getElementById('kf-display').textContent      = factor.toFixed(2);
    document.getElementById('kf-adjusted-pct').textContent = adjustedPct + '%';
  } else {
    penaltyRow.style.display = 'none';
  }

  techAdjustedPct = adjustedPct;

  const finalInp = document.getElementById('tech-final');
  finalInp.min = Math.max(0,   adjustedPct - 4);
  finalInp.max = Math.min(100, adjustedPct + 4);
  if (!finalInp.dataset.edited) {
    finalInp.value = adjustedPct;
  }

  const finalPct = parseInt(finalInp.value) || 0;
  const weighted = (finalPct * CONFIG.weights.tech).toFixed(1);
  document.getElementById('tech-weighted').textContent = weighted;
  document.getElementById('sum-tech').textContent = weighted;
  updateTotal();

  setCommentRequired('tech-note-field', 'tech-note-hint', finalPct !== techAdjustedPct);
}

function updateKQRequiredBadges() {
  if (!Object.keys(KQ_REQUIRED_GROUPS).length) return;
  const satisfied = {};
  for (const group of new Set(Object.values(KQ_REQUIRED_GROUPS))) satisfied[group] = false;
  for (const [topicKey, group] of Object.entries(KQ_REQUIRED_GROUPS)) {
    if (satisfied[group]) continue;
    if (document.querySelectorAll(`input[type=radio][name^="kq_${topicKey}_"]:checked`).length) {
      satisfied[group] = true;
    }
  }
  document.querySelectorAll('.kq-group[data-require-group]').forEach(details => {
    const badge = details.querySelector('.kq-req-badge');
    if (badge) badge.hidden = satisfied[details.dataset.requireGroup] ?? false;
  });
}
updateKQRequiredBadges();

// Q&A and KQ radio changes trigger penalty recalculation
document.getElementById('qa-container').addEventListener('change', e => {
  if (e.target.matches('input[type=radio]')) updateTechWeighted();
});
document.getElementById('card-kq').addEventListener('change', e => {
  if (e.target.matches('input[type=radio]')) { updateTechWeighted(); updateKQRequiredBadges(); }
});

// ─── Design mark ──────────────────────────────────────────────────
document.querySelectorAll('#section-design input[type=radio]').forEach(r => {
  r.addEventListener('change', updateDesign);
});

function updateDesign() {
  if (SE_MODE) { updateTotal(); return; }
  let total = 0;
  DESIGN_KEYS.forEach(f => {
    const sel = document.querySelector(`input[name=${f}]:checked`);
    if (sel) total += parseInt(sel.value);
  });
  total = Math.min(Math.round(CONFIG.weights.design * 100), total);
  setText('design-total-display', total);
  setText('sum-design', total);
  updateTotal();
}

// ─── Demo quality ─────────────────────────────────────────────────
const demoBandSel  = document.getElementById('demo-band');
const demoMarkInp  = document.getElementById('demo-mark-input');
const demoMarkDisp = document.getElementById('demo-mark-display');
const bandDesc     = document.getElementById('band-descriptor');

let demoBandMid = null;

demoBandSel.addEventListener('change', () => {
  const opt = demoBandSel.selectedOptions[0];
  if (!opt.value) {
    demoMarkInp.disabled = true;
    demoMarkInp.value = 0;
    demoBandMid = null;
    bandDesc.textContent = 'Select a band to see the descriptor.';
    demoMarkDisp.textContent = `0 / ${DEMO_MAX}`;
    setText('sum-demo', '0');
    setCommentRequired('demo-comment-field', 'demo-comment-hint', false);
    updateTotal();
    return;
  }
  const min = parseInt(opt.dataset.min);
  const max = parseInt(opt.dataset.max);
  demoBandMid = parseInt(opt.dataset.mid);
  demoMarkInp.min = min;
  demoMarkInp.max = max;
  demoMarkInp.value = demoBandMid;
  demoMarkInp.disabled = false;
  bandDesc.textContent = BAND_DESCRIPTORS[opt.value] || '';
  updateDemoDisplay();
});

demoMarkInp.addEventListener('input', updateDemoDisplay);

function updateDemoDisplay() {
  const v = parseInt(demoMarkInp.value) || 0;
  demoMarkDisp.textContent = `${v} / ${DEMO_MAX}`;
  setText('sum-demo', v);
  updateTotal();
  setCommentRequired('demo-comment-field', 'demo-comment-hint', demoBandMid !== null && v !== demoBandMid);
}

// ─── Running total ────────────────────────────────────────────────
// Summary tiles exist only for criteria the module scores (see DEMO_CRITERIA_LABELS),
// so writes go through setText, which tolerates a missing element.
function setText(id, v) {
  const el = document.getElementById(id);
  if (el) el.textContent = v;
}

function updateTotal() {
  if (SE_MODE) { updateSummaryTable(); return; }
  const tech   = parseFloat(document.getElementById('sum-tech').textContent)   || 0;
  const design = parseFloat(document.getElementById('sum-design')?.textContent) || 0;
  const demo   = parseFloat(document.getElementById('sum-demo')?.textContent)   || 0;
  const sessionMax = Math.round(((CONFIG.weights.tech ?? 0) + (CONFIG.weights.design ?? 0) + (CONFIG.weights.demo ?? 0)) * 100);
  document.getElementById('sum-total').textContent = `${(tech + design + demo).toFixed(1)} / ${sessionMax}`;
}

// ─── Custom Q&A questions ─────────────────────────────────────────
const qaCustomCount = {};

function addCustomQA(btn, featureKey) {
  const idx = qaCustomCount[featureKey] ?? 0;
  qaCustomCount[featureKey] = idx + 1;

  const nameBase = `qa_${featureKey}_cust_${idx}`;
  const item = document.createElement('div');
  item.className = 'qa-item qa-custom-item';
  item.innerHTML = `
    <input type="text" class="qa-custom-q" name="${nameBase}_text" placeholder="Custom question…">
    <div class="qa-answers" style="margin-top:.4rem;">
      <label class="ans-well"><input type="radio" name="${nameBase}" value="well"> Explained well</label>
      <label><input type="radio" name="${nameBase}" value="prompt"> With prompting</label>
      <label class="ans-no"><input type="radio" name="${nameBase}" value="no"> Could not explain</label>
    </div>`;
  btn.before(item);
  item.querySelector('.qa-custom-q').focus();
}

// ─── Custom KQ questions ──────────────────────────────────────────
const kqCustomCount = {};

function addCustomKQ(btn, techKey) {
  const idx = kqCustomCount[techKey] ?? 0;
  kqCustomCount[techKey] = idx + 1;

  const nameBase = `kq_${techKey}_cust_${idx}`;
  const item = document.createElement('div');
  item.className = 'kq-item kq-custom-item';
  item.innerHTML = `
    <input type="text" class="kq-custom-q" name="${nameBase}_text" placeholder="Custom question…">
    <div class="kq-answers" style="margin-top:.4rem;">
      <label class="kqa-correct"><input type="radio" name="${nameBase}" value="correct"> Correct</label>
      <label class="kqa-partial"><input type="radio" name="${nameBase}" value="partial"> Partial</label>
      <label class="kqa-no"><input type="radio" name="${nameBase}" value="no"> Could not answer</label>
    </div>`;
  btn.before(item);
  item.querySelector('.kq-custom-q').focus();
}

// ─── Cursory toggle ───────────────────────────────────────────────
function toggleCursory(cb) {
  const wrap = cb.closest('.qa-feature');
  const skip = cb.checked;
  wrap.classList.toggle('cursory', skip);
  wrap.querySelectorAll('input[type=radio]').forEach(r => r.disabled = skip);
}

// ─── Q&A builder ──────────────────────────────────────────────────
// Reconciles #qa-container with the ticked features instead of rebuilding it:
// sections for newly ticked features are created, sections for unticked features
// are removed (which discards their answers), and every other section is kept
// as-is so answers already entered are never lost.
function qaSectionHasAnswers(wrap) {
  return !!wrap.querySelector('input[type=radio]:checked')
      || [...wrap.querySelectorAll('.qa-custom-q')].some(i => i.value.trim() !== '');
}

// Mark a section cursory (or not) when the set of ticked tiers changes. A section
// that already has answers is never silently skipped: it gets the toggle, unchecked.
function applyCursory(wrap, cursory) {
  const header = wrap.querySelector('.qa-feature-header');
  const label  = header.querySelector('.cursory-toggle');
  if (!cursory) {
    if (label) label.remove();
    wrap.classList.remove('cursory');
    wrap.querySelectorAll('input[type=radio]').forEach(r => r.disabled = false);
    return;
  }
  if (label) return; // already cursory-capable; keep the marker's own choice
  const skip = !qaSectionHasAnswers(wrap);
  const toggle = document.createElement('label');
  toggle.className = 'cursory-toggle';
  toggle.innerHTML = `<input type="checkbox"${skip ? ' checked' : ''} onchange="toggleCursory(this)">
    Skip &mdash; cursory check sufficient`;
  header.appendChild(toggle);
  toggleCursory(toggle.querySelector('input'));
}

function createQASection(featureKey, tier) {
  const fq = CONFIG.features[featureKey];
  const wrap = document.createElement('div');
  wrap.className = 'qa-feature';
  wrap.dataset.feature = featureKey;

  const badgeCls = { basic:'badge-basic', intermediate:'badge-intermediate', advanced:'badge-advanced' }[tier];
  wrap.innerHTML = `
    <div class="qa-feature-header">
      <span class="tier-badge ${badgeCls}">${tier}</span>
      ${fq.label}
    </div>
    <div class="qa-feature-body" id="qa-body-${featureKey}"></div>
  `;

  const body = wrap.querySelector(`#qa-body-${featureKey}`);
  fq.questions.forEach((item, idx) => {
    const qid = `qa_${featureKey}_${idx}`;
    body.innerHTML += `
      <div class="qa-item">
        <div class="qa-q">${item.q}</div>
        <div class="qa-answers">
          <label class="ans-well"><input type="radio" name="${qid}" value="well"> Explained well</label>
          <label><input type="radio" name="${qid}" value="prompt"> With prompting</label>
          <label class="ans-no"><input type="radio" name="${qid}" value="no"> Could not explain</label>
        </div>
      </div>`;
  });

  // Add custom question button at the bottom of the feature body
  const addBtn = document.createElement('button');
  addBtn.type = 'button';
  addBtn.className = 'btn-add-qa';
  addBtn.textContent = '+ Add custom question';
  addBtn.addEventListener('click', () => addCustomQA(addBtn, featureKey));
  body.appendChild(addBtn);
  return wrap;
}

function buildQA() {
  const container = document.getElementById('qa-container');

  const checked = [...document.querySelectorAll('input[name=features]:checked')]
    .map(cb => cb.value)
    .filter(k => CONFIG.features[k]);

  // Drop sections for unticked features (their answers go with them)
  container.querySelectorAll('.qa-feature').forEach(wrap => {
    const fk = wrap.dataset.feature;
    if (!checked.includes(fk)) {
      wrap.remove();
      delete qaCustomCount[fk]; // custom items are gone; numbering restarts if re-ticked
    }
  });
  container.querySelectorAll(':scope > :not(.qa-feature)').forEach(el => el.remove()); // placeholder text

  if (checked.length === 0) {
    container.innerHTML = '<p class="qa-placeholder" style="color:#94a3b8;font-size:.875rem;">Tick features in Section 3 to load questions.</p>';
    return;
  }

  // Determine highest tier present
  const hasAdvanced     = checked.some(k => CONFIG.features[k].tier === 'advanced');
  const hasIntermediate = checked.some(k => CONFIG.features[k].tier === 'intermediate');

  // Order: advanced first, then intermediate, then basic
  const order = ['advanced','intermediate','basic'];
  const byTier = { advanced: [], intermediate: [], basic: [] };
  checked.forEach(k => byTier[CONFIG.features[k].tier]?.push(k));

  order.forEach(tier => {
    byTier[tier].forEach(featureKey => {
      let wrap = container.querySelector(`.qa-feature[data-feature="${featureKey}"]`);
      if (!wrap) wrap = createQASection(featureKey, tier);

      // Cursory if a higher tier is present
      const cursory = (tier === 'basic' && (hasAdvanced || hasIntermediate))
                   || (tier === 'intermediate' && hasAdvanced);
      applyCursory(wrap, cursory);

      container.appendChild(wrap); // (re)appending moves existing nodes, keeping their state
    });
  });
}

// ─── Collect form data ────────────────────────────────────────────
function collectData() {
  const authorship         = document.getElementById('authorship-fail').checked ? 'fail' : 'pass';
  const misconduct_comment = document.getElementById('misconduct-comment').value.trim();
  const features = [...document.querySelectorAll('input[name=features]:checked')].map(cb => cb.value);

  // Q&A answers (pre-defined and custom)
  const qaAnswers = {};
  document.querySelectorAll('.qa-item').forEach(item => {
    const radio = item.querySelector('input[type=radio]:checked');
    if (radio) qaAnswers[radio.name] = radio.value;
    const text = item.querySelector('.qa-custom-q');
    if (text && text.value.trim()) qaAnswers[text.name] = text.value.trim();
  });

  // Design scores
  const design = {};
  DESIGN_KEYS.forEach(f => {
    design[f] = parseInt(document.querySelector(`input[name=${f}]:checked`)?.value ?? 0);
  });
  design.total = Math.min(Math.round((CONFIG.weights.design ?? 0) * 100), Object.values(design).reduce((a,b) => a+b, 0));

  const demoMark     = parseInt(document.getElementById('demo-mark-input').value) || 0;
  const techPct      = parseFloat(document.getElementById('tech-mark').value) || 0;
  const techFinalPct = parseInt(document.getElementById('tech-final').value) || 0;
  const { factor: techKnowledgeFactor } = computeKnowledgeFactor();
  const techWeighted = parseFloat((techFinalPct * CONFIG.weights.tech).toFixed(1));

  // Knowledge check answers (pre-defined and custom)
  const kq = {};
  document.querySelectorAll('input[type=radio][name^=kq_]:checked').forEach(r => {
    kq[r.name] = r.value;
  });
  document.querySelectorAll('input.kq-custom-q').forEach(inp => {
    if (inp.value.trim()) kq[inp.name] = inp.value.trim();
  });

  // SE mode: compute per-criterion marks
  let seExtra = null;
  if (SE_MODE) {
    const kqf         = computeKQFactors();
    const testingMark = Math.round(100 * kqf['testing']);
    const processMark = Math.round(100 * kqf['process']);
    const aiMark      = Math.round(100 * kqf['ai_usage']);
    const presTotal   = parseFloat((
      techFinalPct * CRIT_WEIGHTS.tech +
      testingMark  * CRIT_WEIGHTS.testing +
      processMark  * CRIT_WEIGHTS.process +
      aiMark       * CRIT_WEIGHTS.ai_usage +
      demoMark
    ).toFixed(1));
    seExtra = {
      kq_factors: {
        tech:     parseFloat(kqf['tech'].toFixed(2)),
        testing:  parseFloat(kqf['testing'].toFixed(2)),
        process:  parseFloat(kqf['process'].toFixed(2)),
        ai_usage: parseFloat(kqf['ai_usage'].toFixed(2)),
      },
      testing_mark:      testingMark,
      process_mark:      processMark,
      ai_usage_mark:     aiMark,
      presentation_mark: presTotal,
      overall_comment:   document.getElementById('overall_comment')?.value.trim() ?? '',
      session_subtotal:  presTotal,
    };
  }

  const result = {
    mode:              'demo',
    marker:            MARKER_NAME,
    matric:            document.getElementById('matric').value.trim(),
    authorship,
    misconduct_comment,
    setup_ready:       document.querySelector('input[name=setup_ready]:checked')?.value ?? null,
    presented_clearly: document.querySelector('input[name=presented_clearly]:checked')?.value ?? null,
    features,
    tier:                  currentTierKey,
    tech_percent:          techPct,
    tech_knowledge_factor: parseFloat(techKnowledgeFactor.toFixed(2)),
    tech_weighted:         techWeighted,
    tech_note:             document.getElementById('tech-note').value.trim(),
    design,
    demo_band:         document.getElementById('demo-band').value,
    demo_mark:         demoMark,
    demo_comment:      document.getElementById('demo-comment').value.trim(),
    qa:                qaAnswers,
    kq,
    session_subtotal:  parseFloat((techWeighted + design.total + demoMark).toFixed(1)),
    timer_start:       timerStartedAt,
  };

  if (seExtra) Object.assign(result, seExtra);
  return result;
}

// ─── Validate ─────────────────────────────────────────────────────
function validate(data) {
  const errs = [];
if (!/^\d{8}$/.test(data.matric)) errs.push('Enter a valid 8-digit matriculation number.');

  // Section 6: comment required if final mark differs from the penalty-adjusted recommendation
  if (techAdjustedPct !== null) {
    const finalPct = parseInt(document.getElementById('tech-final').value) || 0;
    if (finalPct !== techAdjustedPct && !data.tech_note) {
      errs.push('Section 6: a marker comment is required when the recommended mark has been adjusted.');
    }
  }

  // Section 7: comment required if demo mark differs from band midpoint
  if (demoBandMid !== null) {
    if (data.demo_mark !== demoBandMid && !data.demo_comment) {
      errs.push('Section 7: a marker comment is required when the recommended mark has been adjusted.');
    }
  }

  // Knowledge Check: block save if any required badge is still visible
  const unsatisfiedGroups = new Set();
  document.querySelectorAll('.kq-group[data-require-group] .kq-req-badge').forEach(badge => {
    if (!badge.hidden) unsatisfiedGroups.add(badge.closest('.kq-group').dataset.requireGroup);
  });
  for (const group of unsatisfiedGroups) {
    const el = document.querySelector(`.kq-group[data-require-group="${group}"] .kq-summary`);
    const label = el ? el.firstChild.textContent.trim() : group;
    errs.push(`Knowledge Check: at least one question must be asked in the "${label}" area.`);
  }

  return errs;
}

// ─── Submit ───────────────────────────────────────────────────────
async function doSave() {
  const data   = collectData();
  const btn    = document.getElementById('btn-submit');
  const status = document.getElementById('save-status');
  btn.disabled = true;
  status.textContent = 'Saving…';
  status.className   = '';

  let res;
  try {
    res = await fetch('save.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data),
    });
  } catch(e) {
    status.textContent = `Error: ${e.message}`;
    status.className   = 'save-error';
    btn.disabled = false;
    return;
  }

  if (res.status === 401) {
    btn.disabled = false;
    status.textContent = '';
    showReauthModal(doSave);
    return;
  }

  try {
    const json = await res.json();
    if (json.ok) {
      status.textContent = `✓ Saved - ${data.matric}`;
      status.className   = 'saved';
      captureSnapshot();
      btn.disabled = false;
    } else {
      throw new Error(json.error || 'Unknown error');
    }
  } catch(e) {
    status.textContent = `Error: ${e.message}`;
    status.className   = 'save-error';
    btn.disabled = false;
  }
}

document.getElementById('btn-submit').addEventListener('click', async () => {
  const data = collectData();
  const errs = validate(data);
  if (errs.length) {
    alert('Please fix the following:\n\n' + errs.join('\n'));
    return;
  }
  await doSave();
});

// ─── Reset ────────────────────────────────────────────────────────
function resetForm() {
  document.getElementById('matric').value = '';
  document.getElementById('authorship-fail').checked = false;
  document.getElementById('authorship-warning').style.display = 'none';
  document.getElementById('misconduct-comment').value = '';
  document.querySelectorAll('input[type=radio][name^=kq_]').forEach(r => r.checked = false);
  document.querySelectorAll('.kq-custom-item').forEach(el => el.remove());
  for (const k in kqCustomCount) delete kqCustomCount[k];
  for (const k in qaCustomCount) delete qaCustomCount[k];
  document.querySelectorAll('.kq-group[open]').forEach(d => d.removeAttribute('open'));
  document.querySelectorAll('input[name=features]').forEach(cb => {
    cb.checked = false;
    cb.closest('.feature-item').classList.remove('checked');
  });
  document.querySelectorAll('input[name=setup_ready], input[name=presented_clearly]').forEach(r => r.checked = false);
  document.getElementById('presentation-reminder').style.display = 'none';
  document.querySelectorAll('[name^=d_]').forEach(r => {
    if (r.value === '0') r.checked = true;
    else r.checked = false;
  });
  document.getElementById('demo-band').value = '';
  document.getElementById('demo-mark-input').value = 0;
  document.getElementById('demo-mark-input').disabled = true;
  document.getElementById('band-descriptor').textContent = 'Select a band to see the descriptor.';
  document.getElementById('tech-mark').value = 0;
  delete document.getElementById('tech-mark').dataset.edited;
  document.getElementById('tech-final').value = 0;
  delete document.getElementById('tech-final').dataset.edited;
  document.getElementById('penalty-row').style.display = 'none';
  document.getElementById('tech-note').value = '';
  document.getElementById('demo-comment').value = '';
  setCommentRequired('tech-note-field', 'tech-note-hint', false);
  setCommentRequired('demo-comment-field', 'demo-comment-hint', false);
  techAdjustedPct = null;
  demoBandMid = null;
  loadedMatric = null;
  formSnapshot = null;
  document.getElementById('save-status').textContent = '';
  document.getElementById('btn-submit').disabled = false;
  setLoadStatus('hidden');
  currentTierKey = null;
  updateTier();
  buildQA();
  updateDesign();
  updateTotal();
  document.getElementById('demo-mark-display').textContent = `0 / ${DEMO_MAX}`;
  setText('sum-demo', '0');
  if (SE_MODE) {
    const oc = document.getElementById('overall_comment');
    if (oc) oc.value = '';
    setText('sum-testing', '0');
    setText('sum-process', '0');
    setText('sum-ai', '0');
  }
  window.scrollTo(0,0);
  updateKQRequiredBadges();
}

// ─── Demo timer ───────────────────────────────────────────────────
const TIMER_TOTAL = CONFIG.timerTotal;
const TIMER_BREAK = CONFIG.timerBreak;

let timerInterval     = null;
let keepaliveInterval = null;
let timerRemaining    = TIMER_TOTAL;
let timerStartedAt    = null;

function timerBeep(freq, count) {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    for (let i = 0; i < count; i++) {
      const osc  = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.value = freq;
      const t = ctx.currentTime + i * 0.45;
      gain.gain.setValueAtTime(0.35, t);
      gain.gain.exponentialRampToValueAtTime(0.001, t + 0.35);
      osc.start(t);
      osc.stop(t + 0.35);
    }
  } catch(e) { /* AudioContext not available */ }
}

function timerFormat(s) {
  const m = Math.floor(s / 60);
  const sec = s % 60;
  return `${m}:${String(sec).padStart(2,'0')}`;
}

function timerRender() {
  const el      = document.getElementById('demo-timer');
  const display = document.getElementById('timer-display');
  const phase   = document.getElementById('timer-phase');
  display.textContent = timerFormat(timerRemaining);
  if (timerRemaining > TIMER_BREAK) {
    el.className = '';
    phase.textContent = 'Presentation';
  } else if (timerRemaining > 0) {
    el.className = 'phase-qa';
    phase.textContent = 'Q&A';
  } else {
    el.className = 'phase-done';
    phase.textContent = 'Time up';
  }
}

function timerStart() {
  if (timerInterval) return;
  timerStartedAt = new Date().toISOString();
  document.getElementById('btn-timer-start').style.display = 'none';
  document.getElementById('btn-timer-reset').style.display = 'block';

  keepaliveInterval = setInterval(() => {
    fetch('keepalive.php').catch(() => {});
  }, 5 * 60 * 1000);

  timerInterval = setInterval(() => {
    timerRemaining--;
    timerRender();

    if (timerRemaining === TIMER_BREAK) {
      timerBeep(880, 2);  // two high beeps - presentation over
    }
    if (timerRemaining === 0) {
      timerBeep(660, 3);  // three lower beeps - time up
      clearInterval(timerInterval);
      timerInterval = null;
      clearInterval(keepaliveInterval);
      keepaliveInterval = null;
    }
  }, 1000);
}

function timerReset() {
  clearInterval(timerInterval);
  timerInterval = null;
  clearInterval(keepaliveInterval);
  keepaliveInterval = null;
  timerRemaining = TIMER_TOTAL;
  document.getElementById('demo-timer').className = '';
  document.getElementById('timer-phase').textContent = 'Ready';
  document.getElementById('btn-timer-start').style.display = 'block';
  document.getElementById('btn-timer-reset').style.display = 'none';
  timerRender();
  document.getElementById('timer-phase').textContent = 'Ready';
}

// ─── Init ─────────────────────────────────────────────────────────
updateDesign();
updateTotal();

// ─── Re-auth modal ────────────────────────────────────────────────
let reauthCallback = null;

function showReauthModal(callback) {
  reauthCallback = callback;
  document.getElementById('reauth-id').value = MARKER_ID;
  document.getElementById('reauth-pw').value = '';
  document.getElementById('reauth-error').style.display = 'none';
  document.getElementById('reauth-modal').style.display = 'flex';
  document.getElementById('reauth-pw').focus();
}

function closeReauthModal() {
  document.getElementById('reauth-modal').style.display = 'none';
  reauthCallback = null;
}

document.getElementById('reauth-cancel').addEventListener('click', () => {
  closeReauthModal();
  document.getElementById('btn-submit').disabled = false;
  document.getElementById('save-status').textContent = 'Save cancelled — session expired. Reload to start fresh.';
  document.getElementById('save-status').className = 'save-error';
});

document.getElementById('reauth-submit').addEventListener('click', async () => {
  const id  = document.getElementById('reauth-id').value.trim();
  const pw  = document.getElementById('reauth-pw').value;
  const err = document.getElementById('reauth-error');
  const res = await fetch('reauth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ marker_id: id, password: pw }),
  });
  const json = await res.json();
  if (json.ok) {
    closeReauthModal();
    if (reauthCallback) reauthCallback();
  } else {
    err.textContent = json.error || 'Authentication failed';
    err.style.display = '';
  }
});

document.getElementById('reauth-pw').addEventListener('keydown', e => {
  if (e.key === 'Enter') document.getElementById('reauth-submit').click();
});
</script>
<script>initMatricAutocomplete('matric', <?= json_encode($matricList) ?>);
<?php if ($preloadMatric !== ''): ?>fetchAndLoad(<?= json_encode($preloadMatric) ?>);<?php endif; ?>
</script>
</body>
</html>
