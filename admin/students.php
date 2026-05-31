<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

if (empty($_SESSION['module_csrf'])) {
    $_SESSION['module_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['module_csrf'];

$targets    = load_deploy_targets();
$deployTarget = isset($targets[$currentModuleId]) ? rtrim($targets[$currentModuleId], '/\\') : '';
$studentsFile = $deployTarget !== '' ? $deployTarget . '/data/students.csv' : '';

$errors  = [];
$success = false;
$count   = 0;

function count_csv_students(string $file): int {
    if (!file_exists($file)) return 0;
    $handle = fopen($file, 'r');
    $header = fgetcsv($handle, escape: '\\');
    $idx = $header ? array_search('Username', $header) : false;
    if ($idx === false) { fclose($handle); return 0; }
    $n = 0;
    while (($row = fgetcsv($handle, escape: '\\')) !== false) {
        if (trim($row[$idx] ?? '') !== '') $n++;
    }
    fclose($handle);
    return $n;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['_csrf'] ?? '') !== $_SESSION['module_csrf']) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    if ($deployTarget === '') {
        $errors[] = 'No deployment target recorded for this module. Deploy the module first.';
    } elseif (empty($_FILES['marksheet_file']['tmp_name'])) {
        $errors[] = 'No file was uploaded.';
    } else {
        $raw = file_get_contents($_FILES['marksheet_file']['tmp_name']);
        if ($raw === false) {
            $errors[] = 'Could not read uploaded file.';
        } else {
            $tmp = tmpfile();
            fwrite($tmp, $raw);
            rewind($tmp);
            $header = fgetcsv($tmp, escape: '\\');
            $usernameIdx = $header ? array_search('Username', $header) : false;
            fclose($tmp);
            if ($usernameIdx === false) {
                $errors[] = 'The uploaded file does not appear to be a Moodle offline grading marksheet (no "Username" column found).';
            } elseif (!is_writable($deployTarget . '/data')) {
                $errors[] = 'The data/ directory is not writable. Check server permissions.';
            } elseif (file_put_contents($studentsFile, $raw, LOCK_EX) === false) {
                $errors[] = 'Failed to write students.csv.';
            } else {
                $success = true;
                $count   = count_csv_students($studentsFile);
            }
        }
    }
}

if (!$success) {
    $count = count_csv_students($studentsFile);
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
    <strong>Marksheet saved.</strong> <?= $count ?> student<?= $count !== 1 ? 's' : '' ?> loaded.
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
  <h2>Moodle Offline Grading Marksheet — <?= $count ?> student<?= $count !== 1 ? 's' : '' ?></h2>
  <p style="font-size:.8rem;color:#475569;margin-bottom:1rem;">
    Upload the offline grading marksheet exported from Moodle for this assessment. The file provides
    the participant identifiers used in the marks CSV export and the student list used for autocomplete
    during marking. Uploading a new file replaces the existing one.
  </p>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <div class="field" style="max-width:420px;">
      <label for="marksheet_file">Marksheet CSV file</label>
      <input type="file" id="marksheet_file" name="marksheet_file" accept=".csv" required>
    </div>
    <div class="btn-row" style="margin-top:.75rem;">
      <button type="submit" class="btn-primary">Upload marksheet</button>
    </div>
  </form>
</div>

<?php endif; ?>
</main>
</body>
</html>
