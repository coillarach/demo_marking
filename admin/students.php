<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

if (empty($_SESSION['module_csrf'])) {
    $_SESSION['module_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['module_csrf'];

$targets      = load_deploy_targets();
$deployTarget = isset($targets[$currentModuleId]) ? rtrim($targets[$currentModuleId], '/\\') : '';
$studentsFile = $deployTarget !== '' ? $deployTarget . '/data/students.csv' : '';

$errors  = [];
$success = false;
$count   = 0;

$deleteError   = '';
$deleteSuccess = '';
$archiveError   = '';
$archiveSuccess = '';

if ($sizeError = post_size_exceeded()) {
    $errors[] = $sizeError;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors)) {
    if (($_POST['_csrf'] ?? '') !== $_SESSION['module_csrf']) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $action = $_POST['_action'] ?? 'upload_marksheet';

    if ($action === 'upload_marksheet') {
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
                }
            }
        }
    } elseif ($action === 'delete_record') {
        if ($deployTarget === '') {
            $deleteError = 'No deployment target recorded for this module.';
        } else {
            $matric = preg_replace('/[^0-9]/', '', $_POST['matric'] ?? '');
            if (!preg_match('/^\d{8}$/', $matric)) {
                $deleteError = 'Invalid matric number.';
            } else {
                $removed  = false;
                $jsonFile = $deployTarget . '/data/' . $matric . '.json';
                if (file_exists($jsonFile)) { unlink($jsonFile); $removed = true; }
                foreach (['html', 'pdf'] as $ext) {
                    $subFile = $deployTarget . '/submissions/' . $matric . '.' . $ext;
                    if (file_exists($subFile)) { unlink($subFile); $removed = true; }
                }
                $metaFile = $deployTarget . '/submissions/_meta.json';
                if (file_exists($metaFile)) {
                    $meta = json_decode(file_get_contents($metaFile), true) ?: [];
                    $changed = false;
                    foreach (['html', 'pdf'] as $ext) {
                        $key = $matric . '.' . $ext;
                        if (isset($meta[$key])) { unset($meta[$key]); $changed = true; }
                    }
                    if ($changed) file_put_contents($metaFile, json_encode($meta, JSON_PRETTY_PRINT), LOCK_EX);
                }
                $deleteSuccess = $removed
                    ? "Record for $matric deleted."
                    : "No record was found for $matric.";
            }
        }
    } elseif ($action === 'archive') {
        if ($deployTarget === '') {
            $archiveError = 'No deployment target recorded for this module.';
        } elseif (trim($_POST['confirm_module_id'] ?? '') !== $currentModuleId) {
            $archiveError = 'Typed module ID did not match — archive cancelled.';
        } else {
            $result = create_archive($deployTarget, $currentModuleId, $currentMarkerId);
            if ($result['ok']) {
                $archiveSuccess = 'Archived ' . $result['student_count'] . ' student record'
                    . ($result['student_count'] !== 1 ? 's' : '') . ' as ' . $result['archive_id'] . '.';
            } else {
                $archiveError = $result['error'];
            }
        }
    }
}

if ($studentsFile !== '') {
    $count = count(read_marksheet_matrics($studentsFile));
}

// ── Current data overview ────────────────────────────────────────────
$overview    = [];
$orphanCount = 0;
if ($deployTarget !== '') {
    $dataDir        = $deployTarget . '/data';
    $submissionsDir = $deployTarget . '/submissions';
    $marksheet      = read_marksheet_csv($studentsFile);
    $msRows         = $marksheet['rows'];
    $nameIdx        = $marksheet['col']['Full name'] ?? null;
    $records        = glob_json_records($dataDir);
    $orphans        = find_orphan_records($records, $msRows);
    $orphanMatrics  = array_flip(array_filter(array_map(fn($r) => $r['matric'] ?? '', $orphans)));
    $orphanCount    = count($orphans);

    foreach ($records as $r) {
        $matric = $r['matric'] ?? '';
        $overview[] = [
            'matric'         => $matric,
            'name'           => ($nameIdx !== null && isset($msRows[$matric])) ? ($msRows[$matric][$nameIdx] ?? '') : '',
            'marker'         => $r['marker'] ?? '',
            'complete'       => !empty($r['complete']),
            'tier'           => $r['tier'] ?? '',
            'has_report'     => array_key_exists('report_mark', $r),
            'has_submission' => has_submission_file($submissionsDir, $matric),
            'orphan'         => isset($orphanMatrics[$matric]),
        ];
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
<?php
$navLinks = [];
if ($moduleReturnUrl !== '') $navLinks[] = ['label' => 'Return to marking', 'href' => $moduleReturnUrl];
$navLinks[] = ['label' => 'Configuration',  'href' => 'module_edit.php'];
$navLinks[] = ['label' => 'Submissions',    'href' => 'submissions.php'];
$navLinks[] = ['label' => 'Archives',       'href' => 'archives.php'];
$navLinks[] = ['label' => 'Documentation',  'href' => 'docs.php'];
$navLinks[] = ['label' => 'Sign out',       'href' => 'module_logout.php'];
render_page_header($currentModuleId . ' — Students', $navLinks, $currentMarkerName);
?>
<main class="wide">

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
    <input type="hidden" name="_action" value="upload_marksheet">
    <div class="field" style="max-width:420px;">
      <label for="marksheet_file">Marksheet CSV file</label>
      <input type="file" id="marksheet_file" name="marksheet_file" accept=".csv" required>
    </div>
    <div class="btn-row" style="margin-top:.75rem;">
      <button type="submit" class="btn-primary">Upload marksheet</button>
    </div>
  </form>
</div>

<?php if ($deleteSuccess): ?>
  <div class="alert alert-success"><?= htmlspecialchars($deleteSuccess) ?></div>
<?php endif; ?>
<?php if ($deleteError): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($deleteError) ?></div>
<?php endif; ?>

<div class="card">
  <h2>Current Data Overview — <?= count($overview) ?> record<?= count($overview) !== 1 ? 's' : '' ?></h2>
  <?php if ($orphanCount > 0): ?>
    <div class="alert alert-warning">
      <?= $orphanCount ?> record<?= $orphanCount !== 1 ? 's' : '' ?> below belong to a matric not in the current marksheet
      (flagged <span class="badge badge-fail">orphan</span> — likely a Moodle enrolment change; export.php excludes these from the CSV).
    </div>
  <?php endif; ?>
  <?php if (empty($overview)): ?>
    <p style="color:#64748b;font-size:.9rem;">No marking records yet.</p>
  <?php else: ?>
  <table class="dyn-table">
    <thead>
      <tr>
        <th>Matric</th><th>Name</th><th>Marker</th><th>Complete</th><th>Tier</th>
        <th>Report</th><th>Submission</th><th></th><th class="col-action"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($overview as $o): ?>
      <tr>
        <td><?= htmlspecialchars($o['matric']) ?></td>
        <td><?= htmlspecialchars($o['name']) ?></td>
        <td><?= htmlspecialchars($o['marker']) ?></td>
        <td><?= $o['complete'] ? 'Yes' : 'In progress' ?></td>
        <td><?= htmlspecialchars($o['tier']) ?></td>
        <td><?= $o['has_report'] ? 'Marked' : '–' ?></td>
        <td><?= $o['has_submission'] ? 'Yes' : 'No' ?></td>
        <td><?= $o['orphan'] ? '<span class="badge badge-fail">orphan</span>' : '' ?></td>
        <td class="col-action">
          <form method="post" onsubmit="return confirm('Delete the mark record for <?= htmlspecialchars($o['matric']) ?>? This removes the JSON record and any submission file. It cannot be undone.');" style="display:inline;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="_action" value="delete_record">
            <input type="hidden" name="matric" value="<?= htmlspecialchars($o['matric']) ?>">
            <button type="submit" class="btn-remove">Delete</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php if ($archiveSuccess): ?>
  <div class="alert alert-success"><?= htmlspecialchars($archiveSuccess) ?></div>
<?php endif; ?>
<?php if ($archiveError): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($archiveError) ?></div>
<?php endif; ?>

<div class="card">
  <h2>Archive Current Module Data</h2>
  <p style="font-size:.8rem;color:#475569;margin-bottom:1rem;">
    Moves the current marksheet, mark records, and submissions into a timestamped archive so a new cohort
    can start with a clean <code>data/</code> and <code>submissions/</code>. Nothing is deleted — archived
    data stays fully inspectable via <a href="archives.php">Archives</a>. Type the module ID to confirm.
  </p>
  <form method="post" id="archive-form" style="display:flex;gap:.75rem;align-items:flex-end;flex-wrap:wrap;">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="_action" value="archive">
    <div class="field" style="margin:0;">
      <label for="confirm_module_id">Type <?= htmlspecialchars($currentModuleId) ?> to confirm</label>
      <input type="text" id="confirm_module_id" name="confirm_module_id" autocomplete="off" placeholder="<?= htmlspecialchars($currentModuleId) ?>">
    </div>
    <button type="submit" class="btn-primary" id="archive-btn" disabled>Archive current data</button>
  </form>
</div>
<script>
(function() {
  const input  = document.getElementById('confirm_module_id');
  const btn    = document.getElementById('archive-btn');
  const target = <?= json_encode($currentModuleId) ?>;
  input.addEventListener('input', () => { btn.disabled = input.value !== target; });
})();
</script>

<?php endif; ?>
</main>
</body>
</html>
