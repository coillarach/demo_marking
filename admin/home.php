<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/lib.php';

$targets = load_deploy_targets();
$modules = [];
foreach ($targets as $moduleId => $path) {
    $configFile = realpath(CONFIGS_DIR . $moduleId . '.php');
    $meta = $configFile
        ? extract_config_meta($configFile)
        : ['MODULE_ID' => $moduleId, 'MODULE_NAME' => '', 'MODULE_LEVEL' => 'UG', 'PASS_MARK' => 0];
    $modules[] = [
        'id'    => $moduleId,
        'name'  => $meta['MODULE_NAME'],
        'level' => $meta['MODULE_LEVEL'],
        'login' => '/' . basename($path) . '/login.php',
    ];
}
usort($modules, fn($a, $b) => strcmp($a['id'], $b['id']));
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Demo Marking Tool</title>
<link rel="stylesheet" href="static/admin.css">
<style>
  .home-wrap { max-width: 720px; margin: 3rem auto; padding: 0 1rem; }
  .home-title { font-size: 1.4rem; font-weight: 700; color: #1e3a5f; margin-bottom: .25rem; }
  .home-sub   { font-size: .9rem; color: #64748b; margin-bottom: 2rem; }
  .home-section-label { font-size: .72rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .07em; color: #94a3b8; margin-bottom: .6rem; }
  .module-list { display: flex; flex-direction: column; gap: .6rem; margin-bottom: 1.75rem; }
  .module-row  { background: #fff; border-radius: 7px; box-shadow: 0 1px 3px rgba(0,0,0,.08);
    border-left: 4px solid #2563eb; padding: .85rem 1.1rem;
    display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
  .module-row .mr-id   { font-weight: 700; color: #1e3a5f; font-size: .95rem; }
  .module-row .mr-name { font-size: .82rem; color: #64748b; margin-top: .1rem; }
  .module-row .mr-level{ display: inline-block; font-size: .72rem; background: #f1f5f9;
    color: #475569; border-radius: 3px; padding: .1rem .4rem; margin-left: .4rem; }
  .module-row .mr-info { flex: 1; min-width: 0; }
  .admin-row { background: #fff; border-radius: 7px; box-shadow: 0 1px 3px rgba(0,0,0,.08);
    border-left: 4px solid #64748b; padding: .85rem 1.1rem;
    display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
  .admin-row .ar-label { font-weight: 700; color: #1e3a5f; font-size: .95rem; }
  .no-modules { font-size: .875rem; color: #64748b; padding: .75rem 0; }
</style>
</head>
<body>
<div class="home-wrap">
  <p class="home-title">Demo Marking Tool</p>
  <p class="home-sub">Select your role to sign in.</p>

  <p class="home-section-label">Module Marking</p>
  <div class="module-list">
  <?php if (empty($modules)): ?>
    <p class="no-modules">No modules have been deployed yet. Ask the global admin to deploy a module first.</p>
  <?php else: ?>
    <?php foreach ($modules as $m): ?>
    <div class="module-row">
      <div class="mr-info">
        <div class="mr-id"><?= htmlspecialchars($m['id']) ?><span class="mr-level"><?= htmlspecialchars($m['level']) ?></span></div>
        <?php if ($m['name'] !== ''): ?>
        <div class="mr-name"><?= htmlspecialchars($m['name']) ?></div>
        <?php endif; ?>
      </div>
      <a class="btn-primary" href="<?= htmlspecialchars($m['login']) ?>">Sign in</a>
    </div>
    <?php endforeach; ?>
  <?php endif; ?>
  </div>

  <p class="home-section-label">Administration</p>
  <div class="admin-row">
    <span class="ar-label">Global admin</span>
    <a class="btn-secondary" href="login.php">Sign in</a>
  </div>
</div>
</body>
</html>
