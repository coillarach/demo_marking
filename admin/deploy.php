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
            $adminUrl  = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/admin/deploy.php'), '/\\') ?: '/admin';
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
<?php render_page_header('Deploy — ' . ($meta['MODULE_ID'] ?: $configParam), [
    ['label' => 'Dashboard',     'href' => 'index.php'],
    ['label' => 'Documentation', 'href' => 'docs.php'],
    ['label' => 'Sign out',      'href' => 'logout.php'],
], $currentAdminUser); ?>
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
      <div style="display:flex;gap:.5rem;align-items:center;">
        <input type="text" id="target_dir" name="target_dir" required style="flex:1;"
               placeholder="<?= htmlspecialchars($suggestedBase) ?>/<?= strtolower(htmlspecialchars($configParam)) ?>"
               aria-describedby="target_dir_hint"
               value="<?= htmlspecialchars($savedTarget) ?>">
        <button type="button" class="btn-secondary" id="use-suggested">Use suggested</button>
      </div>
      <span id="target_dir_hint" style="font-size:.75rem;color:#64748b;">
        Leave empty and press <kbd>Tab</kbd> (or click <em>Use suggested</em>) to accept the greyed-out path.
      </span>
      <?php if ($savedTarget !== ''): ?>
        <br><span style="font-size:.75rem;color:#64748b;">Last used: <code><?= htmlspecialchars($savedTarget) ?></code></span>
      <?php endif; ?>
    </div>
    <div style="display:flex;gap:1rem;align-items:center;margin-top:.5rem;">
      <button type="submit" class="btn-primary">Deploy</button>
      <a href="index.php" class="btn-secondary">Cancel</a>
    </div>
  </form>
</div>

</main>
<script>
// Placeholders can't be accepted natively, so copy the suggested path into the field.
const targetInput  = document.getElementById('target_dir');
const useSuggested = document.getElementById('use-suggested');

function acceptSuggested() {
  targetInput.value = targetInput.placeholder;
  targetInput.focus();
  targetInput.setSelectionRange(targetInput.value.length, targetInput.value.length);
}

// Tab in an empty field accepts the suggestion and keeps focus there (like shell
// completion), so tabbing straight past the field can never silently fill it in:
// a second Tab moves on as normal. Shift+Tab is left alone.
targetInput.addEventListener('keydown', e => {
  if (e.key === 'Tab' && !e.shiftKey && targetInput.value === '') {
    e.preventDefault();
    acceptSuggested();
  }
});
useSuggested.addEventListener('click', acceptSuggested);
</script>
</body>
</html>
