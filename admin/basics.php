<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/lib.php';

$isNew       = isset($_GET['new']);
$configParam = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['config'] ?? ''));
$saved       = isset($_GET['saved']);
$errors      = [];

// Flash errors from save_basics.php
if (!empty($_SESSION['basics_errors'])) {
    $errors = $_SESSION['basics_errors'];
    unset($_SESSION['basics_errors'], $_SESSION['basics_post']);
}

if (!$isNew && $configParam === '') {
    header('Location: index.php');
    exit;
}

// Load existing config
$configLoaded = false;
if (!$isNew) {
    $configFile = realpath(CONFIGS_DIR . $configParam . '.php');
    if (!$configFile || !str_starts_with($configFile, realpath(CONFIGS_DIR))) {
        $error = 'Config file not found.';
    } else {
        include $configFile;
        $configLoaded = true;
    }
}

// CSRF
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['admin_csrf'];

// Form values from loaded config or defaults
$fId      = $configLoaded ? (defined('MODULE_ID')    ? MODULE_ID    : $configParam) : '';
$fName    = $configLoaded ? (defined('MODULE_NAME')   ? MODULE_NAME  : '')           : '';
$fLevel   = $configLoaded ? (defined('MODULE_LEVEL')  ? MODULE_LEVEL : 'UG')         : 'UG';
$fPass    = $configLoaded ? (defined('PASS_MARK')     ? PASS_MARK    : 40)           : 40;
$fMarkers = $configLoaded ? (defined('MARKERS')       ? MARKERS      : [])           : [];
$origId   = $configLoaded ? $fId : '';
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — <?= $isNew ? 'New Module' : 'Edit ' . htmlspecialchars($fId) ?></title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<?php render_page_header($isNew ? 'New Module' : 'Edit ' . $fId, [
    ['label' => 'Dashboard',     'href' => 'index.php'],
    ['label' => 'Documentation', 'href' => 'docs.php'],
    ['label' => 'Sign out',      'href' => 'logout.php'],
], $currentAdminUser); ?>
<main>

<?php if ($saved): ?>
  <div class="alert alert-success">Saved successfully.</div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
      <div><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="post" action="save_basics.php" id="basics-form">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
  <input type="hidden" name="original_id" value="<?= htmlspecialchars($origId) ?>">

  <div class="card">
    <h2>Module Basics</h2>
    <div class="field-row">
      <div class="field">
        <label for="module_id">Module ID</label>
        <input type="text" id="module_id" name="module_id" required
               pattern="[A-Z][A-Z0-9]{2,11}"
               placeholder="e.g. SET08101"
               value="<?= htmlspecialchars($fId) ?>">
        <span style="font-size:.75rem;color:#64748b;">Uppercase letters and digits only; used as the config filename.</span>
      </div>
      <div class="field">
        <label for="module_name">Module Name</label>
        <input type="text" id="module_name" name="module_name" required
               value="<?= htmlspecialchars($fName) ?>">
      </div>
    </div>
    <div class="field-row">
      <div class="field">
        <label for="module_level">Level</label>
        <select id="module_level" name="module_level">
          <option value="UG"  <?= $fLevel === 'UG'  ? 'selected' : '' ?>>UG</option>
          <option value="PG"  <?= $fLevel === 'PG'  ? 'selected' : '' ?>>PG</option>
        </select>
      </div>
      <div class="field">
        <label for="pass_mark">Pass Mark (%)</label>
        <input type="number" id="pass_mark" name="pass_mark" min="0" max="100" required
               value="<?= (int)$fPass ?>">
      </div>
    </div>
  </div>

  <div class="card">
    <h2>Markers (Users)</h2>
    <p style="font-size:.8rem;color:#64748b;margin-bottom:.9rem;">These are the marker accounts for this module. Leave the password field blank to keep the existing password when editing.</p>
    <table class="dyn-table" id="markers-table">
      <thead>
        <tr>
          <th class="col-key">Marker ID (8 digits)</th>
          <th>Name</th>
          <th>Password</th>
          <th class="col-action"></th>
        </tr>
      </thead>
      <tbody id="markers-body">
        <?php $mi = 0; foreach ($fMarkers as $mid => $mdata): ?>
        <tr>
          <td><input type="text" name="markers[<?= $mi ?>][id]"
                     inputmode="numeric" pattern="\d{8}" maxlength="8"
                     value="<?= htmlspecialchars($mid) ?>" required></td>
          <td><input type="text" name="markers[<?= $mi ?>][name]"
                     value="<?= htmlspecialchars($mdata['name'] ?? '') ?>" required></td>
          <td><input type="password" name="markers[<?= $mi ?>][password]"
                     placeholder="(leave blank to keep)"></td>
          <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>
        </tr>
        <?php $mi++; endforeach; ?>
      </tbody>
    </table>
    <button type="button" class="btn-add" onclick="addMarkerRow()">+ Add marker</button>
  </div>

  <div style="display:flex;gap:1rem;align-items:center;">
    <button type="submit" class="btn-primary">Save</button>
    <a href="index.php" class="btn-secondary">Cancel</a>
  </div>
</form>
</main>

<script>
let markerIdx = <?= $mi ?? 0 ?>;

function removeRow(btn) {
    btn.closest('tr').remove();
}

function addMarkerRow() {
    const idx = markerIdx++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="markers[${idx}][id]"
                   inputmode="numeric" pattern="\\d{8}" maxlength="8" required></td>
        <td><input type="text" name="markers[${idx}][name]" required></td>
        <td><input type="password" name="markers[${idx}][password]"
                   placeholder="(required for new markers)"></td>
        <td class="col-action"><button type="button" class="btn-remove" onclick="removeRow(this)">&#x2715;</button></td>
    `;
    document.getElementById('markers-body').appendChild(tr);
    tr.querySelector('input').focus();
}
</script>
</body>
</html>
