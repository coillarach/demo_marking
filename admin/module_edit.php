<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

$saved  = isset($_GET['saved']);
$errors = [];
if (!empty($_SESSION['module_errors'])) {
    $errors = $_SESSION['module_errors'];
    unset($_SESSION['module_errors']);
}

// Load the module config
$configFile = realpath(CONFIGS_DIR . $currentModuleId . '.php');
if (!$configFile || !str_starts_with($configFile, realpath(CONFIGS_DIR))) {
    die('Module config not found. Contact the global admin.');
}
include $configFile;

// CSRF
if (empty($_SESSION['module_csrf'])) {
    $_SESSION['module_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['module_csrf'];

// Read current values from constants
$weights = defined('MARK_WEIGHTS') ? MARK_WEIGHTS : ['tech'=>0.4,'design'=>0.2,'demo'=>0.25,'report'=>0.15];
$timerTotal = defined('TIMER_TOTAL') ? TIMER_TOTAL : 300;
$timerBreak = defined('TIMER_BREAK') ? TIMER_BREAK : 180;
$tierRanges = defined('TIER_RANGES') ? TIER_RANGES : [];
$tierRules  = defined('TIER_RULES')  ? TIER_RULES  : [];
$fbLabels   = defined('FEEDBACK_TIER_LABELS') ? FEEDBACK_TIER_LABELS : [];
$questions      = defined('QUESTIONS')        ? QUESTIONS        : [];
$kqSections     = defined('KNOWLEDGE_QUESTIONS') ? KNOWLEDGE_QUESTIONS : [];
$designCriteria = defined('DESIGN_CRITERIA')  ? DESIGN_CRITERIA  : [];

$tierCssOptions = ['tier-advanced','tier-intermediate','tier-basic','tier-partial','tier-fail'];

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES); }
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($currentModuleId) ?> — Module Config</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<div class="page-header">
  <h1><?= h($currentModuleId) ?> — Module Configuration</h1>
  <span>
    <span style="color:rgba(255,255,255,.6);font-size:.8rem;"><?= h($currentMarkerName) ?></span>
    <?php if ($moduleReturnUrl !== ''): ?>
    &nbsp;|&nbsp; <a href="<?= htmlspecialchars($moduleReturnUrl) ?>">Return to marking</a>
    <?php endif; ?>
    &nbsp;|&nbsp; <a href="students.php">Students</a>
    &nbsp;|&nbsp; <a href="change_password.php">Change password</a>
    &nbsp;|&nbsp; <a href="module_logout.php">Sign out</a>
  </span>
</div>
<main class="wide">

<?php if ($saved): ?>
  <div class="alert alert-success">Saved successfully.</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="post" action="save_module.php" id="module-form">
  <input type="hidden" name="_csrf" value="<?= h($csrf) ?>">

  <div class="card">
    <div class="tab-bar">
      <button type="button" class="tab-btn active" data-tab="tab-weights">Weights &amp; Timers</button>
      <button type="button" class="tab-btn" data-tab="tab-features">Features</button>
      <button type="button" class="tab-btn" data-tab="tab-design">Design &amp; Usability</button>
      <button type="button" class="tab-btn" data-tab="tab-tiers">Tiers</button>
      <button type="button" class="tab-btn" data-tab="tab-knowledge">Knowledge Questions</button>
    </div>

    <!-- ── Tab 1: Weights & Timers ─────────────────────────────────── -->
    <div id="tab-weights" class="tab-panel active">
      <h3>Mark Weights <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h3>
      <div class="help-box" hidden>
        <p>Each weight sets what proportion of the total 100-mark assessment is allocated to that component. The four values must add up to exactly 1.0 — for example, setting Technical to 0.40 means the technical component is worth 40 out of 100 marks. All subtotals and percentage labels on the marking form are calculated automatically from these values.</p>
      </div>
      <div class="weight-grid">
        <?php
        $weightLabels = [
            'tech'          => 'Technical',
            'design'        => 'Design',
            'demo'          => 'Demo',
            'report'        => 'Report',
            'communication' => 'Communication',
            'testing'       => 'Testing & Quality',
            'process'       => 'Development Process',
            'ai_usage'      => 'AI Tool Usage',
        ];
        foreach ($weights as $k => $v):
            $label = $weightLabels[$k] ?? ucwords(str_replace('_', ' ', $k));
        ?>
        <div class="field">
          <label for="w_<?= h($k) ?>"><?= h($label) ?></label>
          <input type="number" id="w_<?= h($k) ?>" name="weights[<?= h($k) ?>]"
                 min="0" max="1" step="0.01" required
                 value="<?= sprintf('%.2f', $v) ?>"
                 class="weight-input"
                 oninput="checkWeightSum()">
        </div>
        <?php endforeach; ?>
      </div>
      <div class="weight-sum" id="weight-sum-msg"></div>

      <h3 style="margin-top:1.25rem;">Timer <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h3>
      <div class="help-box" hidden>
        <p>The total time controls how long the demonstration session runs, in seconds — for example, 300 seconds is 5 minutes. The alert time sets how many seconds must remain on the clock when a warning is shown to the marker — for example, 180 means the alert fires when 3 minutes are left. Both values drive the countdown timer displayed during marking.</p>
      </div>
      <div class="field-row" style="max-width:360px;">
        <div class="field">
          <label for="timer_total">Total time (seconds)</label>
          <input type="number" id="timer_total" name="timer_total" min="60" max="3600" required
                 value="<?= (int)$timerTotal ?>">
        </div>
        <div class="field">
          <label for="timer_break">Alert at (seconds left)</label>
          <input type="number" id="timer_break" name="timer_break" min="0" max="3600" required
                 value="<?= (int)$timerBreak ?>">
        </div>
      </div>
    </div>

    <!-- ── Tab 2: Features ────────────────────────────────────────── -->
    <div id="tab-features" class="tab-panel">
      <h3>Assessment Features <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h3>
      <div class="help-box" hidden>
        <p>Features are the assessed parts of the student's submission. Each feature needs a unique <strong>key</strong> (a short code with no spaces, e.g. <code>login</code> or <code>search_filter</code>), a <strong>label</strong> shown to markers, and a <strong>tier</strong> (Basic, Intermediate, or Advanced). The tier classification determines how many features of each level a student must demonstrate to reach a given mark band.</p>
        <p>Each feature also has question rows used during the Q&amp;A. The four columns are: the question to ask; what a good unprompted answer looks like; what a partial or prompted answer looks like; and what it looks like when the student cannot answer. Markers select one of these responses during the session and the matching text is included in the generated feedback.</p>
      </div>
      <div class="accordion" id="features-container">
        <?php $fi = 0; foreach ($questions as $fkey => $feat): ?>
        <details class="accordion-item" open>
          <summary>
            <span class="summary-label"><?= h($feat['label'] ?? $fkey) ?> <span style="font-size:.75rem;font-weight:400;color:#94a3b8;">(<?= h($feat['tier'] ?? '') ?>)</span></span>
            <span>
              <button type="button" class="btn-remove" onclick="removeFeat(this)">Remove</button>
              <span class="summary-arrow">&#9654;</span>
            </span>
          </summary>
          <div class="accordion-body">
            <div class="field-row" style="margin-bottom:.75rem;">
              <div class="field">
                <label>Feature key (slug)</label>
                <input type="text" name="features[<?= $fi ?>][key]"
                       pattern="[a-zA-Z_][a-zA-Z0-9_]*" required
                       value="<?= h($fkey) ?>">
              </div>
              <div class="field">
                <label>Label</label>
                <input type="text" name="features[<?= $fi ?>][label]" required
                       value="<?= h($feat['label'] ?? '') ?>">
              </div>
              <div class="field" style="max-width:180px;">
                <label>Tier</label>
                <select name="features[<?= $fi ?>][tier]">
                  <?php foreach (['basic','intermediate','advanced'] as $t): ?>
                    <option value="<?= $t ?>" <?= ($feat['tier'] ?? '') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <h3 style="margin-top:0;">Questions</h3>
            <table class="dyn-table q-table">
              <thead>
                <tr>
                  <th>Question</th>
                  <th>Answered well</th>
                  <th>With prompting</th>
                  <th>Could not answer</th>
                  <th class="col-action"></th>
                </tr>
              </thead>
              <tbody class="q-body">
                <?php $qi = 0; foreach ($feat['questions'] ?? [] as $q): ?>
                <tr>
                  <td><textarea name="features[<?= $fi ?>][questions][<?= $qi ?>][q]" rows="2"><?= h($q['q'] ?? '') ?></textarea></td>
                  <td><textarea name="features[<?= $fi ?>][questions][<?= $qi ?>][well]" rows="2"><?= h($q['well'] ?? '') ?></textarea></td>
                  <td><textarea name="features[<?= $fi ?>][questions][<?= $qi ?>][prompt]" rows="2"><?= h($q['prompt'] ?? '') ?></textarea></td>
                  <td><textarea name="features[<?= $fi ?>][questions][<?= $qi ?>][no]" rows="2"><?= h($q['no'] ?? '') ?></textarea></td>
                  <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>
                </tr>
                <?php $qi++; endforeach; ?>
              </tbody>
            </table>
            <button type="button" class="btn-add" onclick="addQuestion(this, <?= $fi ?>)">+ Add question</button>
          </div>
        </details>
        <?php $fi++; endforeach; ?>
      </div>
      <button type="button" class="btn-add" style="margin-top:.5rem;" onclick="addFeature()">+ Add feature</button>
    </div>

    <!-- ── Tab 3: Design & Usability ────────────────────────────── -->
    <div id="tab-design" class="tab-panel">
      <h3>Design &amp; Usability Criteria <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h3>
      <div class="help-box" hidden>
        <p>Each criterion appears as a scored row in the Design &amp; Usability section of the marking form. Give each criterion a unique <strong>key</strong> (letters, digits and underscores only), a <strong>label</strong> shown to markers, and an optional <strong>hint</strong> (small text beneath the label). Set <strong>Max score</strong> to 2 (options: 0 or 2) or 4 (options: 0, 2, or 4). The total is capped at the design weight × 100.</p>
        <p>Feedback fields control what text is included in the generated feedback comment for each score value. Leave a field blank to omit that score level from the feedback.</p>
      </div>
      <div class="accordion" id="design-container">
        <?php $di = 0; foreach ($designCriteria as $dkey => $dc): ?>
        <details class="accordion-item" open>
          <summary>
            <span class="summary-label"><?= h($dc['label'] ?? $dkey) ?></span>
            <span>
              <button type="button" class="btn-remove" onclick="removeDesignCrit(this)">Remove</button>
              <span class="summary-arrow">&#9654;</span>
            </span>
          </summary>
          <div class="accordion-body">
            <div class="field-row" style="margin-bottom:.75rem;">
              <div class="field" style="max-width:180px;">
                <label>Key</label>
                <input type="text" name="design_criteria[<?= $di ?>][key]"
                       pattern="[a-zA-Z_][a-zA-Z0-9_]*" required value="<?= h($dkey) ?>">
              </div>
              <div class="field">
                <label>Label</label>
                <input type="text" name="design_criteria[<?= $di ?>][label]" required
                       value="<?= h($dc['label'] ?? '') ?>">
              </div>
              <div class="field">
                <label>Hint (optional)</label>
                <input type="text" name="design_criteria[<?= $di ?>][description]"
                       value="<?= h($dc['description'] ?? '') ?>">
              </div>
              <div class="field" style="max-width:130px;">
                <label>Max score</label>
                <select name="design_criteria[<?= $di ?>][max]" onchange="toggleFb4(this)">
                  <option value="2" <?= ((int)($dc['max'] ?? 2)) !== 4 ? 'selected' : '' ?>>2 (0 / 2)</option>
                  <option value="4" <?= ((int)($dc['max'] ?? 2)) === 4 ? 'selected' : '' ?>>4 (0 / 2 / 4)</option>
                </select>
              </div>
            </div>
            <h3 style="margin-top:0;">Feedback text per score</h3>
            <table class="dyn-table">
              <thead><tr><th style="width:50px;">Score</th><th>Feedback (blank = omit from comment)</th></tr></thead>
              <tbody>
                <tr><td>0</td><td><textarea name="design_criteria[<?= $di ?>][fb_0]" rows="2"><?= h($dc['feedback'][0] ?? '') ?></textarea></td></tr>
                <tr><td>2</td><td><textarea name="design_criteria[<?= $di ?>][fb_2]" rows="2"><?= h($dc['feedback'][2] ?? '') ?></textarea></td></tr>
                <tr class="fb4-row" <?= ((int)($dc['max'] ?? 2)) !== 4 ? 'style="display:none"' : '' ?>>
                  <td>4</td><td><textarea name="design_criteria[<?= $di ?>][fb_4]" rows="2"><?= h($dc['feedback'][4] ?? '') ?></textarea></td>
                </tr>
              </tbody>
            </table>
          </div>
        </details>
        <?php $di++; endforeach; ?>
      </div>
      <button type="button" class="btn-add" style="margin-top:.5rem;" onclick="addDesignCrit()">+ Add criterion</button>
    </div>

    <!-- ── Tab 4: Tiers ───────────────────────────────────────────── -->
    <div id="tab-tiers" class="tab-panel">

      <h3>Tier Ranges <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h3>
      <div class="help-box" hidden>
        <p>Tier ranges define the named mark bands. Each tier needs a unique <strong>key</strong> (e.g. <code>advanced1</code>), a <strong>label</strong> shown to markers, and a <strong>CSS class</strong> that controls the colour highlight on the marking form. The <strong>Min</strong> and <strong>Max</strong> columns are the lowest and highest marks awarded for that tier; the <strong>Mid</strong> is used as the default suggested mark when no fine adjustment is made.</p>
        <p>CSS class options: <em>tier-advanced</em> (blue), <em>tier-intermediate</em> (purple), <em>tier-basic</em> (green), <em>tier-partial</em> (amber), <em>tier-fail</em> (red).</p>
      </div>
      <table class="dyn-table" id="ranges-table">
        <thead>
          <tr>
            <th class="col-key">Key</th>
            <th>Label</th>
            <th class="col-cls">CSS Class</th>
            <th class="col-num">Min</th>
            <th class="col-num">Max</th>
            <th class="col-num">Mid</th>
            <th class="col-action"></th>
          </tr>
        </thead>
        <tbody id="ranges-body">
          <?php $ri = 0; foreach ($tierRanges as $rkey => $rdata): ?>
          <tr>
            <td><input type="text" name="tiers[<?= $ri ?>][key]" value="<?= h($rkey) ?>" required pattern="[a-zA-Z_][a-zA-Z0-9_]*"></td>
            <td><input type="text" name="tiers[<?= $ri ?>][label]" value="<?= h($rdata['label'] ?? '') ?>" required></td>
            <td>
              <select name="tiers[<?= $ri ?>][cls]">
                <?php foreach ($tierCssOptions as $cls): ?>
                  <option value="<?= $cls ?>" <?= ($rdata['cls'] ?? '') === $cls ? 'selected' : '' ?>><?= $cls ?></option>
                <?php endforeach; ?>
              </select>
            </td>
            <td><input type="number" name="tiers[<?= $ri ?>][min]" value="<?= (int)($rdata['min'] ?? 0) ?>" min="0" max="100"></td>
            <td><input type="number" name="tiers[<?= $ri ?>][max]" value="<?= (int)($rdata['max'] ?? 100) ?>" min="0" max="100"></td>
            <td><input type="number" name="tiers[<?= $ri ?>][mid]" value="<?= (int)($rdata['mid'] ?? 50) ?>" min="0" max="100"></td>
            <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>
          </tr>
          <?php $ri++; endforeach; ?>
        </tbody>
      </table>
      <button type="button" class="btn-add" onclick="addTierRange()">+ Add tier</button>

      <h3>Tier Rules <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h3>
      <div class="help-box" hidden>
        <p>Rules decide which tier a student is placed in based on how many Basic, Intermediate, and Advanced features they demonstrated. Rules are checked <strong>in order from top to bottom</strong> and the first rule whose thresholds are all met is applied — so place the most specific rules first and the most general last.</p>
        <p>The <strong>tier key</strong> must match a key from the Tier Ranges table. The threshold columns set the minimum count required for each feature type — leave a field blank to mean "no minimum for this type". A rule with all three fields blank acts as a catch-all fallback and should appear as the last row.</p>
      </div>
      <table class="dyn-table" id="rules-table">
        <thead>
          <tr>
            <th class="col-key">Tier key</th>
            <th class="col-num">Min adv.</th>
            <th class="col-num">Min inter.</th>
            <th class="col-num">Min basic</th>
            <th class="col-action"></th>
          </tr>
        </thead>
        <tbody id="rules-body">
          <?php $rui = 0; foreach ($tierRules as $rule): ?>
          <tr>
            <td><input type="text" name="rules[<?= $rui ?>][key]" value="<?= h($rule['key'] ?? '') ?>" required pattern="[a-zA-Z_][a-zA-Z0-9_]*"></td>
            <td><input type="number" name="rules[<?= $rui ?>][min_adv]"   min="0" max="99" value="<?= isset($rule['minAdv'])   ? (int)$rule['minAdv']   : '' ?>"></td>
            <td><input type="number" name="rules[<?= $rui ?>][min_inter]" min="0" max="99" value="<?= isset($rule['minInter']) ? (int)$rule['minInter'] : '' ?>"></td>
            <td><input type="number" name="rules[<?= $rui ?>][min_basic]" min="0" max="99" value="<?= isset($rule['minBasic']) ? (int)$rule['minBasic'] : '' ?>"></td>
            <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>
          </tr>
          <?php $rui++; endforeach; ?>
        </tbody>
      </table>
      <button type="button" class="btn-add" onclick="addTierRule()">+ Add rule</button>

      <h3>Feedback Labels <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h3>
      <div class="help-box" hidden>
        <p>Each tier key should have a corresponding feedback sentence that is inserted into the Moodle feedback comment when a student is placed in that tier. Use the same keys as the Tier Ranges table above. The sentence should describe what the student demonstrated — for example: <em>"Both intermediate enhancements were present and functional."</em></p>
      </div>
      <table class="dyn-table" id="fb-table">
        <thead>
          <tr>
            <th class="col-key">Tier key</th>
            <th>Feedback text</th>
            <th class="col-action"></th>
          </tr>
        </thead>
        <tbody id="fb-body">
          <?php $fbi = 0; foreach ($fbLabels as $fbkey => $fbtext): ?>
          <tr>
            <td><input type="text" name="feedback_keys[<?= $fbi ?>]" value="<?= h($fbkey) ?>" required pattern="[a-zA-Z_][a-zA-Z0-9_]*"></td>
            <td><textarea name="feedback_vals[<?= $fbi ?>]" rows="2"><?= h($fbtext) ?></textarea></td>
            <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>
          </tr>
          <?php $fbi++; endforeach; ?>
        </tbody>
      </table>
      <button type="button" class="btn-add" onclick="addFeedbackRow()">+ Add feedback label</button>
    </div>

    <!-- ── Tab 4: Knowledge Questions ────────────────────────────── -->
    <div id="tab-knowledge" class="tab-panel">
      <h3>Knowledge Check Sections <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h3>
      <div class="help-box" hidden>
        <p>Knowledge check sections are optional technology-area questions asked separately from the feature Q&amp;A — for example, sections covering HTML, CSS, PHP, JavaScript, and SQL. Each section has a <strong>key</strong> (short code, no spaces) and a <strong>label</strong> displayed to the marker.</p>
        <p>Within each section, question and hint pairs are shown to the marker as prompts. The <strong>hint</strong> is a model answer for the marker's reference only — it is not shown to students. Responses are recorded and can reduce the technical mark through a knowledge factor if the student demonstrates poor understanding of their own code.</p>
      </div>
      <div class="accordion" id="kq-container">
        <?php $ksi = 0; foreach ($kqSections as $kskey => $kssec): ?>
        <details class="accordion-item" open>
          <summary>
            <span class="summary-label"><?= h($kssec['label'] ?? $kskey) ?></span>
            <span>
              <button type="button" class="btn-remove" onclick="removeFeat(this)">Remove</button>
              <span class="summary-arrow">&#9654;</span>
            </span>
          </summary>
          <div class="accordion-body">
            <div class="field-row" style="margin-bottom:.75rem;">
              <div class="field">
                <label>Section key</label>
                <input type="text" name="kq[<?= $ksi ?>][key]"
                       pattern="[a-zA-Z_][a-zA-Z0-9_]*" required
                       value="<?= h($kskey) ?>">
              </div>
              <div class="field">
                <label>Label</label>
                <input type="text" name="kq[<?= $ksi ?>][label]" required
                       value="<?= h($kssec['label'] ?? '') ?>">
              </div>
            </div>
            <h3 style="margin-top:0;">Questions</h3>
            <table class="dyn-table kq-table">
              <thead>
                <tr>
                  <th>Question</th>
                  <th>Hint / model answer</th>
                  <th class="col-action"></th>
                </tr>
              </thead>
              <tbody class="kq-body">
                <?php $kqi = 0; foreach ($kssec['questions'] ?? [] as $kq): ?>
                <tr>
                  <td><textarea name="kq[<?= $ksi ?>][questions][<?= $kqi ?>][q]" rows="2"><?= h($kq['q'] ?? '') ?></textarea></td>
                  <td><textarea name="kq[<?= $ksi ?>][questions][<?= $kqi ?>][hint]" rows="2"><?= h($kq['hint'] ?? '') ?></textarea></td>
                  <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>
                </tr>
                <?php $kqi++; endforeach; ?>
              </tbody>
            </table>
            <button type="button" class="btn-add" onclick="addKqQuestion(this, <?= $ksi ?>)">+ Add question</button>
          </div>
        </details>
        <?php $ksi++; endforeach; ?>
      </div>
      <button type="button" class="btn-add" style="margin-top:.5rem;" onclick="addKqSection()">+ Add section</button>
    </div>

  </div><!-- end .card -->

  <div style="display:flex;gap:1rem;align-items:center;margin-top:-.5rem;">
    <button type="submit" class="btn-primary">Save</button>
    <span id="save-hint" style="font-size:.8rem;color:#64748b;"></span>
  </div>
</form>
</main>

<script>
// ── Help boxes ────────────────────────────────────────────────────
function toggleHelp(btn) {
  const box = btn.parentElement.nextElementSibling;
  box.hidden = !box.hidden;
  btn.setAttribute('aria-expanded', String(!box.hidden));
}

// ── Tab switching ──────────────────────────────────────────────────
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById(btn.dataset.tab).classList.add('active');
  });
});

// ── Weight sum ─────────────────────────────────────────────────────
function checkWeightSum() {
  const sum = [...document.querySelectorAll('.weight-input')]
    .reduce((s, el) => s + (parseFloat(el.value) || 0), 0);
  const msg  = document.getElementById('weight-sum-msg');
  const ok   = Math.abs(sum - 1.0) < 0.001;
  msg.textContent = 'Sum: ' + sum.toFixed(4) + (ok ? ' ✓' : ' — must equal 1.0');
  msg.className   = 'weight-sum ' + (ok ? 'weight-ok' : 'weight-bad');
}
checkWeightSum();

// ── Generic row remove ─────────────────────────────────────────────
function removeRow(btn)       { btn.closest('tr').remove(); }
function removeFeat(btn)      { btn.closest('details').remove(); }
function removeDesignCrit(btn){ btn.closest('details').remove(); }

// ── Design criteria ────────────────────────────────────────────────
let designIdx = <?= $di ?>;

function toggleFb4(sel) {
  const row = sel.closest('.accordion-body').querySelector('.fb4-row');
  if (row) row.style.display = sel.value === '4' ? '' : 'none';
}

function addDesignCrit() {
  const idx = designIdx++;
  const d = document.createElement('details');
  d.className = 'accordion-item';
  d.open = true;
  d.innerHTML = `
    <summary>
      <span class="summary-label">New criterion</span>
      <span>
        <button type="button" class="btn-remove" onclick="removeDesignCrit(this)">Remove</button>
        <span class="summary-arrow">&#9654;</span>
      </span>
    </summary>
    <div class="accordion-body">
      <div class="field-row" style="margin-bottom:.75rem;">
        <div class="field" style="max-width:180px;">
          <label>Key</label>
          <input type="text" name="design_criteria[${idx}][key]" pattern="[a-zA-Z_][a-zA-Z0-9_]*" required>
        </div>
        <div class="field">
          <label>Label</label>
          <input type="text" name="design_criteria[${idx}][label]" required>
        </div>
        <div class="field">
          <label>Hint (optional)</label>
          <input type="text" name="design_criteria[${idx}][description]">
        </div>
        <div class="field" style="max-width:130px;">
          <label>Max score</label>
          <select name="design_criteria[${idx}][max]" onchange="toggleFb4(this)">
            <option value="2" selected>2 (0 / 2)</option>
            <option value="4">4 (0 / 2 / 4)</option>
          </select>
        </div>
      </div>
      <h3 style="margin-top:0;">Feedback text per score</h3>
      <table class="dyn-table">
        <thead><tr><th style="width:50px;">Score</th><th>Feedback (blank = omit from comment)</th></tr></thead>
        <tbody>
          <tr><td>0</td><td><textarea name="design_criteria[${idx}][fb_0]" rows="2"></textarea></td></tr>
          <tr><td>2</td><td><textarea name="design_criteria[${idx}][fb_2]" rows="2"></textarea></td></tr>
          <tr class="fb4-row" style="display:none">
            <td>4</td><td><textarea name="design_criteria[${idx}][fb_4]" rows="2"></textarea></td>
          </tr>
        </tbody>
      </table>
    </div>`;
  document.getElementById('design-container').appendChild(d);
}

// ── Feature counters ───────────────────────────────────────────────
let featIdx = <?= $fi ?>;
let qCounts = {<?php $fi2=0; foreach ($questions as $f): echo $fi2.':'.count($f['questions'] ?? []).','; $fi2++; endforeach; ?>};

function addFeature() {
  const idx = featIdx++;
  qCounts[idx] = 0;
  const div = document.createElement('details');
  div.className = 'accordion-item';
  div.open = true;
  div.innerHTML = `
    <summary>
      <span class="summary-label">New feature</span>
      <span>
        <button type="button" class="btn-remove" onclick="removeFeat(this)">Remove</button>
        <span class="summary-arrow">&#9654;</span>
      </span>
    </summary>
    <div class="accordion-body">
      <div class="field-row" style="margin-bottom:.75rem;">
        <div class="field">
          <label>Feature key (slug)</label>
          <input type="text" name="features[${idx}][key]" pattern="[a-zA-Z_][a-zA-Z0-9_]*" required>
        </div>
        <div class="field">
          <label>Label</label>
          <input type="text" name="features[${idx}][label]" required>
        </div>
        <div class="field" style="max-width:180px;">
          <label>Tier</label>
          <select name="features[${idx}][tier]">
            <option value="basic">Basic</option>
            <option value="intermediate">Intermediate</option>
            <option value="advanced">Advanced</option>
          </select>
        </div>
      </div>
      <h3 style="margin-top:0;">Questions</h3>
      <table class="dyn-table q-table">
        <thead><tr><th>Question</th><th>Answered well</th><th>With prompting</th><th>Could not answer</th><th class="col-action"></th></tr></thead>
        <tbody class="q-body"></tbody>
      </table>
      <button type="button" class="btn-add" onclick="addQuestion(this, ${idx})">+ Add question</button>
    </div>`;
  document.getElementById('features-container').appendChild(div);
  div.querySelector('input').focus();
}

function addQuestion(btn, fi) {
  if (!qCounts[fi]) qCounts[fi] = 0;
  const qi  = qCounts[fi]++;
  const tbody = btn.previousElementSibling.querySelector('.q-body');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><textarea name="features[${fi}][questions][${qi}][q]" rows="2"></textarea></td>
    <td><textarea name="features[${fi}][questions][${qi}][well]" rows="2"></textarea></td>
    <td><textarea name="features[${fi}][questions][${qi}][prompt]" rows="2"></textarea></td>
    <td><textarea name="features[${fi}][questions][${qi}][no]" rows="2"></textarea></td>
    <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>`;
  tbody.appendChild(tr);
  tr.querySelector('textarea').focus();
}

// ── Tier ranges ────────────────────────────────────────────────────
let rangeIdx = <?= $ri ?>;
const tierCssOpts = <?= json_encode($tierCssOptions) ?>;

function addTierRange() {
  const idx  = rangeIdx++;
  const opts = tierCssOpts.map(c => `<option value="${c}">${c}</option>`).join('');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" name="tiers[${idx}][key]" required pattern="[a-zA-Z_][a-zA-Z0-9_]*"></td>
    <td><input type="text" name="tiers[${idx}][label]" required></td>
    <td><select name="tiers[${idx}][cls]">${opts}</select></td>
    <td><input type="number" name="tiers[${idx}][min]" min="0" max="100" value="0"></td>
    <td><input type="number" name="tiers[${idx}][max]" min="0" max="100" value="100"></td>
    <td><input type="number" name="tiers[${idx}][mid]" min="0" max="100" value="50"></td>
    <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>`;
  document.getElementById('ranges-body').appendChild(tr);
  tr.querySelector('input').focus();
}

// ── Tier rules ─────────────────────────────────────────────────────
let ruleIdx = <?= $rui ?>;

function addTierRule() {
  const idx = ruleIdx++;
  const tr  = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" name="rules[${idx}][key]" required pattern="[a-zA-Z_][a-zA-Z0-9_]*"></td>
    <td><input type="number" name="rules[${idx}][min_adv]"   min="0" max="99" placeholder="—"></td>
    <td><input type="number" name="rules[${idx}][min_inter]" min="0" max="99" placeholder="—"></td>
    <td><input type="number" name="rules[${idx}][min_basic]" min="0" max="99" placeholder="—"></td>
    <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>`;
  document.getElementById('rules-body').appendChild(tr);
  tr.querySelector('input').focus();
}

// ── Feedback labels ────────────────────────────────────────────────
let fbIdx = <?= $fbi ?>;

function addFeedbackRow() {
  const idx = fbIdx++;
  const tr  = document.createElement('tr');
  tr.innerHTML = `
    <td><input type="text" name="feedback_keys[${idx}]" required pattern="[a-zA-Z_][a-zA-Z0-9_]*"></td>
    <td><textarea name="feedback_vals[${idx}]" rows="2"></textarea></td>
    <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>`;
  document.getElementById('fb-body').appendChild(tr);
  tr.querySelector('input').focus();
}

// ── Knowledge questions ────────────────────────────────────────────
let kqSecIdx = <?= $ksi ?>;
let kqCounts = {<?php $ks2=0; foreach ($kqSections as $s): echo $ks2.':'.count($s['questions'] ?? []).','; $ks2++; endforeach; ?>};

function addKqSection() {
  const idx = kqSecIdx++;
  kqCounts[idx] = 0;
  const div = document.createElement('details');
  div.className = 'accordion-item';
  div.open = true;
  div.innerHTML = `
    <summary>
      <span class="summary-label">New section</span>
      <span>
        <button type="button" class="btn-remove" onclick="removeFeat(this)">Remove</button>
        <span class="summary-arrow">&#9654;</span>
      </span>
    </summary>
    <div class="accordion-body">
      <div class="field-row" style="margin-bottom:.75rem;">
        <div class="field"><label>Section key</label><input type="text" name="kq[${idx}][key]" pattern="[a-zA-Z_][a-zA-Z0-9_]*" required></div>
        <div class="field"><label>Label</label><input type="text" name="kq[${idx}][label]" required></div>
      </div>
      <h3 style="margin-top:0;">Questions</h3>
      <table class="dyn-table kq-table">
        <thead><tr><th>Question</th><th>Hint / model answer</th><th class="col-action"></th></tr></thead>
        <tbody class="kq-body"></tbody>
      </table>
      <button type="button" class="btn-add" onclick="addKqQuestion(this, ${idx})">+ Add question</button>
    </div>`;
  document.getElementById('kq-container').appendChild(div);
  div.querySelector('input').focus();
}

function addKqQuestion(btn, ksi) {
  if (!kqCounts[ksi]) kqCounts[ksi] = 0;
  const qi    = kqCounts[ksi]++;
  const tbody = btn.previousElementSibling.querySelector('.kq-body');
  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td><textarea name="kq[${ksi}][questions][${qi}][q]" rows="2"></textarea></td>
    <td><textarea name="kq[${ksi}][questions][${qi}][hint]" rows="2"></textarea></td>
    <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>`;
  tbody.appendChild(tr);
  tr.querySelector('textarea').focus();
}
</script>
</body>
</html>
