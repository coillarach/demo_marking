<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/lib.php';

$configParam = preg_replace('/[^A-Z0-9]/', '', strtoupper($_GET['config'] ?? ''));
if ($configParam === '') {
    header('Location: index.php');
    exit;
}

$configsBase = realpath(CONFIGS_DIR);
$configFile  = $configsBase . DIRECTORY_SEPARATOR . $configParam . '.php';
if (!file_exists($configFile)) {
    header('Location: index.php');
    exit;
}

$meta          = extract_config_meta($configFile);
$targets       = load_deploy_targets();
$savedTarget   = $targets[$configParam] ?? '';
$projectRoot   = realpath(__DIR__ . '/..');
$suggestedBase = defined('DEPLOY_BASE') ? DEPLOY_BASE : ($projectRoot ? dirname($projectRoot) : '/var/www/html');

// CSRF
if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['admin_csrf'];

$result = null; // set on POST

// ── Recursive directory copy ───────────────────────────────────────
function deploy_copy_dir(string $src, string $dst): array {
    $errors = [];
    if (!is_dir($src)) {
        return ["Source directory not found: $src"];
    }
    if (!is_dir($dst) && !mkdir($dst, 0755, true)) {
        return ["Could not create directory: $dst"];
    }
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($iter as $item) {
        $target = $dst . DIRECTORY_SEPARATOR . $iter->getSubPathname();
        if ($item->isDir()) {
            if (!is_dir($target) && !mkdir($target, 0755, true)) {
                $errors[] = "Could not create directory: $target";
            }
        } else {
            if (!copy($item->getPathname(), $target)) {
                $errors[] = "Could not copy: " . $item->getSubPathname();
            }
        }
    }
    return $errors;
}

// ── POST: perform deployment ───────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_csrf'] ?? '') !== $_SESSION['admin_csrf']) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $targetDir = rtrim(trim($_POST['target_dir'] ?? ''), '/\\');
    $errors    = [];

    // Validate path
    if ($targetDir === '') {
        $errors[] = 'Deployment directory is required.';
    } elseif (strpos($targetDir, "\0") !== false || !str_starts_with($targetDir, '/')) {
        $errors[] = 'Deployment directory must be an absolute path (starting with /).';
    } else {
        // Block deploying into the project source tree
        $projectRoot = realpath(__DIR__ . '/..');
        if ($projectRoot && (
            $targetDir === $projectRoot ||
            str_starts_with($targetDir . '/', $projectRoot . '/')
        )) {
            $errors[] = 'Cannot deploy into the project source tree. Choose a directory outside ' . $projectRoot . '.';
        }
    }

    if (empty($errors)) {
        $srcDir = realpath(__DIR__ . '/../src');

        // Create target if needed
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            $errors[] = "Could not create target directory: $targetDir";
        }
    }

    if (empty($errors)) {
        // Copy src/* → target
        $errors = array_merge($errors, deploy_copy_dir($srcDir, $targetDir));
    }

    if (empty($errors)) {
        // Copy module config as config.php then append deployment URL constants
        if (!copy($configFile, $targetDir . '/config.php')) {
            $errors[] = 'Could not copy module config to target directory.';
        } else {
            $moduleUrl = '/' . basename($targetDir);
            $adminUrl  = defined('ADMIN_URL') ? ADMIN_URL : '/admin';
            file_put_contents(
                $targetDir . '/config.php',
                "\ndefine('ADMIN_URL',  " . var_export($adminUrl,   true) . ");\n" .
                "define('MODULE_URL', " . var_export($moduleUrl, true) . ");\n",
                FILE_APPEND | LOCK_EX
            );
        }
    }

    if (empty($errors)) {
        // Ensure data/ and submissions/ exist (never overwrite contents)
        foreach (['data', 'submissions'] as $sub) {
            $path = $targetDir . '/' . $sub;
            if (!is_dir($path) && !mkdir($path, 0755, true)) {
                $errors[] = "Could not create $sub/ directory.";
            }
        }
    }

    if (empty($errors)) {
        save_deploy_target($configParam, $targetDir);
        $savedTarget = $targetDir;
        $result = ['ok' => true, 'target' => $targetDir];
    } else {
        $result = ['ok' => false, 'errors' => $errors];
    }
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Deploy <?= htmlspecialchars($configParam) ?></title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<div class="page-header">
  <h1>Deploy — <?= htmlspecialchars($meta['MODULE_ID'] ?: $configParam) ?></h1>
  <span><span style="color:rgba(255,255,255,.6);font-size:.8rem;"><?= htmlspecialchars($currentAdminUser) ?></span>
    &nbsp;|&nbsp; <a href="index.php">Dashboard</a>
    &nbsp;|&nbsp; <a href="logout.php">Sign out</a></span>
</div>
<main>

<?php if ($result !== null): ?>
  <?php if ($result['ok']): ?>
  <div class="alert alert-success">
    <strong><?= htmlspecialchars($configParam) ?> deployed successfully.</strong><br>
    <span style="font-family:monospace;font-size:.85rem;"><?= htmlspecialchars($result['target']) ?></span>
  </div>
  <?php else: ?>
  <div class="alert alert-danger">
    <strong>Deployment failed.</strong>
    <?php foreach ($result['errors'] as $e): ?>
      <div style="margin-top:.3rem;"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
<?php endif; ?>

<div class="card">
  <h2>Module</h2>
  <table style="font-size:.9rem;border-collapse:collapse;">
    <tr><td style="color:#64748b;padding:.2rem .75rem .2rem 0;width:120px;">ID</td><td><strong><?= htmlspecialchars($meta['MODULE_ID']) ?></strong></td></tr>
    <tr><td style="color:#64748b;padding:.2rem .75rem .2rem 0;">Name</td><td><?= htmlspecialchars($meta['MODULE_NAME']) ?></td></tr>
    <tr><td style="color:#64748b;padding:.2rem .75rem .2rem 0;">Level</td><td><?= htmlspecialchars($meta['MODULE_LEVEL']) ?></td></tr>
    <tr><td style="color:#64748b;padding:.2rem .75rem .2rem 0;">Pass mark</td><td><?= (int)$meta['PASS_MARK'] ?>%</td></tr>
  </table>
</div>

<div class="card">
  <h2>Deployment</h2>
  <p style="font-size:.8rem;color:#475569;margin-bottom:1rem;">
    Copies all shared source files from <code>src/</code> to the target directory, then copies
    <code>configs/<?= htmlspecialchars($configParam) ?>.php</code> as <code>config.php</code>.
    The <code>data/</code> and <code>submissions/</code> directories are created if they do not exist.
    Existing student data is never overwritten.
  </p>

  <form method="post">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <div class="field">
      <label for="target_dir">Deployment directory (absolute path)</label>
      <input type="text" id="target_dir" name="target_dir" required
             placeholder="<?= htmlspecialchars($suggestedBase) ?>/<?= strtolower(htmlspecialchars($configParam)) ?>"
             value="<?= htmlspecialchars($savedTarget) ?>">
      <?php if ($savedTarget !== ''): ?>
        <span style="font-size:.75rem;color:#64748b;">Last used: <code><?= htmlspecialchars($savedTarget) ?></code></span>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:1rem;align-items:center;margin-top:.5rem;">
      <button type="submit" class="btn-primary">Deploy</button>
      <a href="index.php" class="btn-secondary">Cancel</a>
    </div>
  </form>
</div>

</main>
</body>
</html>
