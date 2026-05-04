<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id      = trim($_POST['marker_id'] ?? '');
    $pw      = $_POST['password'] ?? '';
    $markers = MARKERS;
    if (isset($markers[$id]) && password_verify($pw, $markers[$id]['password'])) {
        $_SESSION['marking_auth'] = true;
        $_SESSION['marker_id']    = $id;
        $_SESSION['marker_name']  = $markers[$id]['name'];
        header('Location: demo.php');
        exit;
    }
    $error = 'Incorrect marker ID or password.';
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= MODULE_ID ?> — Demo Marking</title>
<link rel="stylesheet" href="static/login.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h1><?= MODULE_ID ?> — Demo Marking</h1>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="field">
        <label for="marker_id">Marker ID</label>
        <input type="text" id="marker_id" name="marker_id" inputmode="numeric"
               pattern="\d{8}" maxlength="8" autofocus required placeholder="8-digit ID">
      </div>
      <div class="field">
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required>
      </div>
      <button class="btn-primary" type="submit">Sign in</button>
    </form>
  </div>
</div>
</body>
</html>
