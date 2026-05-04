<?php
session_name('admin_tool');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

if (!empty($_SESSION['admin_auth'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pw   = $_POST['password'] ?? '';
    if ($user === ADMIN_USERNAME && password_verify($pw, ADMIN_PASSWORD)) {
        $_SESSION['admin_auth'] = true;
        $_SESSION['admin_user'] = $user;
        header('Location: index.php');
        exit;
    }
    $error = 'Incorrect username or password.';
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Login</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<div class="login-wrap">
  <div class="login-card">
    <h1>Marking Tool — Admin</h1>
    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post">
      <div class="field">
        <label for="username">Username</label>
        <input type="text" id="username" name="username" autofocus required>
      </div>
      <div class="field">
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required>
      </div>
      <button class="btn-primary" type="submit" style="width:100%;margin-top:.5rem;">Sign in</button>
    </form>
    <p style="font-size:.8rem;color:#64748b;margin-top:1rem;text-align:center;">
      <a href="module_login.php">Module config editor &rarr;</a>
    </p>
  </div>
</div>
</body>
</html>
