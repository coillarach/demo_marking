<?php
session_name('module_tool');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!empty($_SESSION['module_auth'])) {
    header('Location: module_edit.php');
    exit;
}

require_once __DIR__ . '/lib.php';
require_once __DIR__ . '/config.php';

// Build module list from configs directory
$configFiles = glob(CONFIGS_DIR . '*.php') ?: [];
$modules = [];
foreach ($configFiles as $f) {
    $meta = extract_config_meta($f);
    if ($meta['MODULE_ID'] !== '') {
        $modules[] = $meta['MODULE_ID'];
    }
}
sort($modules);

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $moduleId = trim($_POST['module_id'] ?? '');
    $markerId = trim($_POST['marker_id'] ?? '');
    $pw       = $_POST['password'] ?? '';

    if (!preg_match('/^[A-Z][A-Z0-9]{2,11}$/', $moduleId)) {
        $error = 'Invalid module selection.';
    } else {
        $configFile = realpath(CONFIGS_DIR . $moduleId . '.php');
        if (!$configFile || !str_starts_with($configFile, realpath(CONFIGS_DIR))) {
            $error = 'Module not found.';
        } else {
            include $configFile;
            $markers = defined('MARKERS') ? MARKERS : [];
            if (isset($markers[$markerId]) && password_verify($pw, $markers[$markerId]['password'])) {
                session_name('module_tool');
                $_SESSION['module_auth']        = true;
                $_SESSION['module_id']          = $moduleId;
                $_SESSION['module_marker_id']   = $markerId;
                $_SESSION['module_marker_name'] = $markers[$markerId]['name'];
                header('Location: module_edit.php');
                exit;
            }
            $error = 'Incorrect marker ID or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Module Config — Login</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h1>Module Config Editor</h1>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if (empty($modules)): ?>
      <div class="alert alert-warning">No module configs found. Ask the global admin to create a module first.</div>
    <?php else: ?>
    <form method="post">
      <div class="field">
        <label for="module_id">Module</label>
        <select id="module_id" name="module_id" required>
          <option value="">— select —</option>
          <?php foreach ($modules as $m): ?>
            <option value="<?= htmlspecialchars($m) ?>"
              <?= (($_POST['module_id'] ?? '') === $m) ? 'selected' : '' ?>>
              <?= htmlspecialchars($m) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label for="marker_id">Marker ID</label>
        <input type="text" id="marker_id" name="marker_id" inputmode="numeric"
               pattern="\d{8}" maxlength="8" required placeholder="8-digit ID"
               value="<?= htmlspecialchars($_POST['marker_id'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required>
      </div>
      <button class="btn-primary" type="submit" style="width:100%;margin-top:.5rem;">Sign in</button>
    </form>
    <?php endif; ?>
    <p style="font-size:.8rem;color:#64748b;margin-top:1rem;text-align:center;">
      <a href="login.php">Global admin &rarr;</a>
    </p>
  </div>
</div>
</body>
</html>
