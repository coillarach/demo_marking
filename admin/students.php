<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

if (empty($_SESSION['module_csrf'])) {
    $_SESSION['module_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['module_csrf'];

$targets    = load_deploy_targets();
$deployTarget = isset($targets[$currentModuleId]) ? rtrim($targets[$currentModuleId], '/\\') : '';
$studentsFile = $deployTarget !== '' ? $deployTarget . '/data/students.txt' : '';

$errors  = [];
$success = false;
$count   = 0;

function parse_student_lines(string $raw): array {
    $lines = preg_split('/\r\n|\r|\n/', $raw);
    $out = [];
    foreach ($lines as $line) {
        $trimmed = rtrim($line);
        if ($trimmed === '') continue;
        $out[] = $trimmed;
    }
    return $out;
}

// Read current list for display
$currentText = '';
if ($studentsFile !== '' && file_exists($studentsFile)) {
    $currentText = file_get_contents($studentsFile);
    $count = count(array_filter(
        parse_student_lines($currentText),
        fn($l) => $l[0] !== '#'
    ));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_csrf'] ?? '') !== $_SESSION['module_csrf']) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    if ($deployTarget === '') {
        $errors[] = 'No deployment target recorded for this module. Deploy the module first.';
    } else {
        $realTarget = realpath($deployTarget);
        if ($realTarget === false || $realTarget !== realpath($deployTarget)) {
            $errors[] = 'Deployment directory not found: ' . $deployTarget;
        } elseif (!is_writable($realTarget . '/data')) {
            $errors[] = 'The data/ directory is not writable. Check server permissions.';
        } else {
            $source = $_POST['source'] ?? 'text';
            $raw = '';

            if ($source === 'upload') {
                if (empty($_FILES['students_file']['tmp_name'])) {
                    $errors[] = 'No file was uploaded.';
                } else {
                    $raw = file_get_contents($_FILES['students_file']['tmp_name']);
                    if ($raw === false) $errors[] = 'Could not read uploaded file.';
                }
            } else {
                $raw = $_POST['students_text'] ?? '';
            }

            if (empty($errors)) {
                $lines = parse_student_lines($raw);
                $content = implode("\n", $lines) . (count($lines) ? "\n" : '');
                if (file_put_contents($studentsFile, $content, LOCK_EX) === false) {
                    $errors[] = 'Failed to write students file.';
                } else {
                    $currentText = $content;
                    $count = count(array_filter($lines, fn($l) => $l[0] !== '#'));
                    $success = true;
                }
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
<title><?= htmlspecialchars($currentModuleId) ?> — Students</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<div class="page-header">
  <h1><?= htmlspecialchars($currentModuleId) ?> — Students</h1>
  <span>
    <span style="color:rgba(255,255,255,.6);font-size:.8rem;"><?= htmlspecialchars($currentMarkerName) ?></span>
    <?php if ($moduleReturnUrl !== ''): ?>
    &nbsp;|&nbsp; <a href="<?= htmlspecialchars($moduleReturnUrl) ?>">Return to marking</a>
    <?php endif; ?>
    &nbsp;|&nbsp; <a href="module_edit.php">Configuration</a>
    &nbsp;|&nbsp; <a href="module_logout.php">Sign out</a>
  </span>
</div>
<main>

<?php if ($deployTarget === ''): ?>
  <div class="alert alert-warning">
    <strong>No deployment target recorded.</strong>
    This module must be deployed before the student list can be managed here.
    <a href="../admin/deploy.php?config=<?= urlencode($currentModuleId) ?>">Deploy now</a>
  </div>
<?php else: ?>

<?php if ($success): ?>
  <div class="alert alert-success">
    <strong>Student list saved.</strong> <?= $count ?> student<?= $count !== 1 ? 's' : '' ?> loaded.
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <?php foreach ($errors as $e): ?>
      <div><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card">
  <h2>Current list — <?= $count ?> student<?= $count !== 1 ? 's' : '' ?></h2>
  <p style="font-size:.8rem;color:#475569;margin-bottom:1rem;">
    One matric number per line. Lines beginning with <code>#</code> are treated as comments and ignored.
    Saving replaces the entire list.
  </p>

  <form method="post">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="source" value="text" id="source-field">
    <div class="field">
      <label for="students_text">Matric numbers</label>
      <textarea id="students_text" name="students_text" rows="16"
                style="font-family:monospace;font-size:.85rem;"><?= htmlspecialchars($currentText) ?></textarea>
    </div>
    <div class="btn-row">
      <button type="submit" class="btn-primary">Save list</button>
      <label class="btn-secondary" style="cursor:pointer;">
        Upload file <input type="file" name="students_file" accept=".txt" style="display:none"
                           onchange="handleUpload(this)">
      </label>
    </div>
  </form>
</div>

<script>
function handleUpload(input) {
    if (!input.files.length) return;
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('students_text').value = e.target.result;
        document.getElementById('source-field').value = 'text';
    };
    reader.readAsText(input.files[0]);
}
</script>

<?php endif; ?>
</main>
</body>
</html>
