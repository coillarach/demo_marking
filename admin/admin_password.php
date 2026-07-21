<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/lib.php';

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['admin_csrf'];

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_csrf'] ?? '') !== $_SESSION['admin_csrf']) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $currentPw = $_POST['current_password'] ?? '';
    $newPw     = $_POST['new_password']     ?? '';
    $confirmPw = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPw, ADMIN_PASSWORD)) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($newPw) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($newPw !== $confirmPw) {
        $errors[] = 'New passwords do not match.';
    } else {
        $configFile = __DIR__ . '/config.php';
        $content    = file_get_contents($configFile);
        $newHash    = password_hash($newPw, PASSWORD_DEFAULT);
        $updated    = preg_replace_callback(
            "/define\s*\(\s*'ADMIN_PASSWORD'\s*,\s*'[^']*'\s*\)\s*;[^\n]*/",
            fn($m) => "define('ADMIN_PASSWORD', '" . $newHash . "');",
            $content
        );
        if ($updated === $content || $updated === null) {
            $errors[] = 'Could not locate ADMIN_PASSWORD in config — update manually.';
        } else {
            file_put_contents($configFile, $updated, LOCK_EX);
            if (function_exists('opcache_invalidate')) opcache_invalidate($configFile, true);
            $success = true;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Change Password</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<?php render_page_header('Marking Tool — Global Admin', [
    ['label' => 'Dashboard',     'href' => 'index.php'],
    ['label' => 'Documentation', 'href' => 'docs.php'],
    ['label' => 'Sign out',      'href' => 'logout.php'],
], $currentAdminUser); ?>
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
  <h2>Change admin password</h2>
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
      <a href="index.php" class="btn-secondary">Cancel</a>
    </div>
  </form>
</div>

</main>
</body>
</html>
