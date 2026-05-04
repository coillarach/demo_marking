<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/lib.php';

$configFiles = glob(CONFIGS_DIR . '*.php') ?: [];
$modules = [];
foreach ($configFiles as $f) {
    $meta = extract_config_meta($f);
    if ($meta['MODULE_ID'] !== '') {
        $modules[] = $meta;
    }
}
usort($modules, fn($a,$b) => strcmp($a['MODULE_ID'], $b['MODULE_ID']));
$deployTargets = load_deploy_targets();
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Dashboard</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<div class="page-header">
  <h1>Marking Tool — Global Admin</h1>
  <span><span style="color:rgba(255,255,255,.6);font-size:.8rem;"><?= htmlspecialchars($currentAdminUser) ?></span>
    &nbsp;|&nbsp; <a href="module_login.php">Module editor</a>
    &nbsp;|&nbsp; <a href="admin_password.php">Change password</a>
    &nbsp;|&nbsp; <a href="logout.php">Sign out</a></span>
</div>
<main>

<div class="card">
  <h2>Module Configurations</h2>

  <?php if (empty($modules)): ?>
    <p style="color:#64748b;font-size:.9rem;">No module configs found yet.</p>
  <?php else: ?>
  <div class="module-grid">
    <?php foreach ($modules as $m): ?>
    <div class="module-card">
      <div class="mc-id"><?= htmlspecialchars($m['MODULE_ID']) ?></div>
      <div class="mc-name"><?= htmlspecialchars($m['MODULE_NAME']) ?></div>
      <div class="mc-meta">
        <span><?= htmlspecialchars($m['MODULE_LEVEL']) ?></span>
        <span>Pass <?= (int)$m['PASS_MARK'] ?>%</span>
        <?php if (isset($deployTargets[$m['MODULE_ID']])): ?>
          <span title="Last deployed to: <?= htmlspecialchars($deployTargets[$m['MODULE_ID']]) ?>">Deployed</span>
        <?php endif; ?>
      </div>
      <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem;">
        <a class="btn-secondary" href="basics.php?config=<?= urlencode($m['MODULE_ID']) ?>">Edit basics &amp; users</a>
        <a class="btn-secondary" href="deploy.php?config=<?= urlencode($m['MODULE_ID']) ?>">Deploy</a>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div style="margin-top:.75rem;">
    <a class="btn-primary" href="basics.php?new=1">+ Create new module</a>
  </div>
</div>

</main>
</body>
</html>
