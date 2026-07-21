<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

if (empty($_SESSION['module_csrf'])) {
    $_SESSION['module_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['module_csrf'];

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_csrf'] ?? '') !== $_SESSION['module_csrf']) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $currentPw  = $_POST['current_password'] ?? '';
    $newPw      = $_POST['new_password']     ?? '';
    $confirmPw  = $_POST['confirm_password'] ?? '';

    // Load config to verify current password and read full data
    $configFile = realpath(CONFIGS_DIR . $currentModuleId . '.php');
    if (!$configFile || !str_starts_with($configFile, realpath(CONFIGS_DIR))) {
        $errors[] = 'Module config not found.';
    } else {
        include $configFile;
        $markers = defined('MARKERS') ? MARKERS : [];

        if (!isset($markers[$currentMarkerId])) {
            $errors[] = 'Marker account not found in config.';
        } elseif (!password_verify($currentPw, $markers[$currentMarkerId]['password'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($newPw) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($newPw !== $confirmPw) {
            $errors[] = 'New passwords do not match.';
        } else {
            $markers[$currentMarkerId]['password'] = password_hash($newPw, PASSWORD_DEFAULT);

            $data = [
                'module_id'            => defined('MODULE_ID')    ? MODULE_ID    : $currentModuleId,
                'module_name'          => defined('MODULE_NAME')  ? MODULE_NAME  : '',
                'module_level'         => defined('MODULE_LEVEL') ? MODULE_LEVEL : 'UG',
                'pass_mark'            => defined('PASS_MARK')    ? PASS_MARK    : 40,
                'markers'              => $markers,
                'mark_weights'         => defined('MARK_WEIGHTS')         ? MARK_WEIGHTS         : [],
                'timer_total'          => defined('TIMER_TOTAL')          ? TIMER_TOTAL          : 300,
                'timer_break'          => defined('TIMER_BREAK')          ? TIMER_BREAK          : 180,
                'tier_ranges'          => defined('TIER_RANGES')          ? TIER_RANGES          : [],
                'tier_rules'           => defined('TIER_RULES')           ? TIER_RULES           : [],
                'feedback_tier_labels' => defined('FEEDBACK_TIER_LABELS') ? FEEDBACK_TIER_LABELS : [],
                'questions'            => defined('QUESTIONS')            ? QUESTIONS            : [],
                'knowledge_questions'  => defined('KNOWLEDGE_QUESTIONS')  ? KNOWLEDGE_QUESTIONS  : [],
                'design_criteria'      => defined('DESIGN_CRITERIA')      ? DESIGN_CRITERIA      : [],
            ];

            try {
                $newContent = generate_config($data);
                safe_write_config(CONFIGS_DIR, $currentModuleId, $newContent);
                sync_deployed_config($currentModuleId, $newContent);
                $success = true;
            } catch (RuntimeException $e) {
                $errors[] = 'File write error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($currentModuleId) ?> — Change Password</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<?php
$navLinks = [];
if ($moduleReturnUrl !== '') $navLinks[] = ['label' => 'Return to marking', 'href' => $moduleReturnUrl];
$navLinks[] = ['label' => 'Configuration', 'href' => 'module_edit.php'];
$navLinks[] = ['label' => 'Sign out',      'href' => 'module_logout.php'];
render_page_header($currentModuleId . ' — Change Password', $navLinks, $currentMarkerName);
?>
<main>

<?php if ($success): ?>
  <div class="alert alert-success"><strong>Password updated successfully.</strong></div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
      <div><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card" style="max-width:420px;">
  <h2>Change your password</h2>
  <form method="post">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <div class="field">
      <label for="current_password">Current password</label>
      <input type="password" id="current_password" name="current_password" required autofocus>
    </div>
    <div class="field">
      <label for="new_password">New password <span style="color:#64748b;font-weight:400;">(min 8 characters)</span></label>
      <input type="password" id="new_password" name="new_password" required minlength="8">
    </div>
    <div class="field">
      <label for="confirm_password">Confirm new password</label>
      <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
    </div>
    <div class="btn-row">
      <button type="submit" class="btn-primary">Update password</button>
      <a href="module_edit.php" class="btn-secondary">Cancel</a>
    </div>
  </form>
</div>

</main>
</body>
</html>
