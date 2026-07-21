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

function provenance_read(string $submissionsDir): array {
    $file = $submissionsDir . '/_meta.json';
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function provenance_write(string $submissionsDir, array $meta): void {
    if (!is_dir($submissionsDir)) mkdir($submissionsDir, 0755, true);
    file_put_contents($submissionsDir . '/_meta.json', json_encode($meta, JSON_PRETTY_PRINT), LOCK_EX);
}

/** Moves $sourcePath into submissions/<matric>.<ext>, refusing to clobber an
 *  existing file unless $overwrite is set. Records provenance either way. */
function place_submission(string $deployTarget, string $matric, string $ext, string $sourcePath, string $originalFilename, string $matchedVia, bool $overwrite): array {
    $submissionsDir = $deployTarget . '/submissions';
    if (!is_dir($submissionsDir)) mkdir($submissionsDir, 0755, true);
    $destFile   = $submissionsDir . '/' . $matric . '.' . $ext;
    $provenance = provenance_read($submissionsDir);
    if (file_exists($destFile) && !$overwrite) {
        $existing = $provenance[$matric . '.' . $ext]['original_filename'] ?? 'an earlier upload';
        return ['ok' => false, 'error' => "$matric.$ext already has a submission (originally \"$existing\"). Tick \"overwrite\" to replace it."];
    }
    if (!rename($sourcePath, $destFile)) {
        return ['ok' => false, 'error' => 'Could not move the submission file into place.'];
    }
    $provenance[$matric . '.' . $ext] = [
        'original_filename' => $originalFilename,
        'matched_via'        => $matchedVia,
        'matched_at'          => date('c'),
    ];
    provenance_write($submissionsDir, $provenance);
    return ['ok' => true];
}

function list_pending_submissions(string $deployTarget): array {
    $pending = [];
    foreach (glob($deployTarget . '/submissions_pending/*', GLOB_ONLYDIR) ?: [] as $batchDir) {
        $batch = basename($batchDir);
        if (!is_valid_batch_id($batch)) continue;
        foreach (glob($batchDir . '/*') ?: [] as $f) {
            if (!is_file($f)) continue;
            $pending[] = ['batch' => $batch, 'file' => basename($f), 'path' => $f];
        }
    }
    return $pending;
}

$uploadErrors  = [];
$uploadSummary = '';
$reviewError   = '';
$reviewSuccess = '';
$reassignError   = '';
$reassignSuccess = '';

// Large submission zips for bigger cohorts routinely exceed post_max_size —
// check for that specifically so it isn't misreported as a CSRF failure.
if ($sizeError = post_size_exceeded()) {
    $uploadErrors[] = $sizeError;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($uploadErrors)) {
    if (($_POST['_csrf'] ?? '') !== $_SESSION['module_csrf']) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }
    $action = $_POST['_action'] ?? '';

    if ($action === 'upload_submissions') {
        if ($deployTarget === '') {
            $uploadErrors[] = 'No deployment target recorded for this module.';
        } elseif (!class_exists('ZipArchive')) {
            $uploadErrors[] = 'Zip support (PHP zip extension) is not available on this server — contact your system administrator.';
        } elseif (empty($_FILES['zip_file']['tmp_name'])) {
            $uploadErrors[] = 'No file was uploaded.';
        } else {
            $zip = new ZipArchive();
            if ($zip->open($_FILES['zip_file']['tmp_name']) !== true) {
                $uploadErrors[] = 'Could not open the uploaded file as a zip archive.';
            } else {
                $knownMatrics   = read_marksheet_matrics($studentsFile);
                $submissionsDir = $deployTarget . '/submissions';
                $batchId  = date('Y-m-d_His') . '-' . bin2hex(random_bytes(3));
                $batchDir = $deployTarget . '/submissions_pending/' . $batchId;

                $autoCount = 0; $skipCount = 0; $reviewCount = 0;

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if ($entryName === false || str_ends_with($entryName, '/')) continue;
                    $base = basename($entryName); // zip-slip safety: never trust the entry's own path
                    if ($base === '' || str_starts_with($base, '.')) continue;
                    $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['html', 'pdf'], true)) { $skipCount++; continue; }

                    $bytes = $zip->getFromIndex($i);
                    if ($bytes === false) { $skipCount++; continue; }

                    $match  = match_submission_filename($base, $knownMatrics);
                    $placed = false;
                    if ($match['status'] === 'matched') {
                        $destFile = $submissionsDir . '/' . $match['matric'] . '.' . $ext;
                        if (!file_exists($destFile)) {
                            if (!is_dir($submissionsDir)) mkdir($submissionsDir, 0755, true);
                            file_put_contents($destFile, $bytes, LOCK_EX);
                            $provenance = provenance_read($submissionsDir);
                            $provenance[$match['matric'] . '.' . $ext] = [
                                'original_filename' => $base,
                                'matched_via'        => 'auto',
                                'matched_at'          => date('c'),
                            ];
                            provenance_write($submissionsDir, $provenance);
                            $autoCount++;
                            $placed = true;
                        }
                    }
                    if (!$placed) {
                        if (!is_dir($batchDir)) mkdir($batchDir, 0755, true);
                        file_put_contents($batchDir . '/' . $base, $bytes, LOCK_EX);
                        $reviewCount++;
                    }
                }
                $zip->close();
                $uploadSummary = "$autoCount auto-matched, $skipCount unsupported file" . ($skipCount !== 1 ? 's' : '') . " skipped, $reviewCount awaiting review below.";
            }
        }
    } elseif ($action === 'resolve_pending') {
        $batch     = $_POST['pending_batch'] ?? '';
        $file      = basename($_POST['pending_file'] ?? '');
        $matric    = preg_replace('/[^0-9]/', '', $_POST['matric'] ?? '');
        $overwrite = !empty($_POST['overwrite']);
        if ($deployTarget === '') {
            $reviewError = 'No deployment target recorded for this module.';
        } elseif (!is_valid_batch_id($batch)) {
            $reviewError = 'Invalid batch.';
        } elseif (!preg_match('/^\d{8}$/', $matric)) {
            $reviewError = 'Enter a valid 8-digit matric number.';
        } else {
            $pendingBase = realpath($deployTarget . '/submissions_pending');
            $srcPath     = $pendingBase !== false ? realpath($pendingBase . '/' . $batch . '/' . $file) : false;
            if ($srcPath === false || $pendingBase === false || !str_starts_with($srcPath, $pendingBase . DIRECTORY_SEPARATOR)) {
                $reviewError = 'File not found — it may already have been resolved.';
            } else {
                $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));
                if (!in_array($ext, ['html', 'pdf'], true)) {
                    $reviewError = 'Unsupported file type.';
                } else {
                    $result = place_submission($deployTarget, $matric, $ext, $srcPath, basename($srcPath), 'manual', $overwrite);
                    if ($result['ok']) {
                        @rmdir(dirname($srcPath));
                        $reviewSuccess = "Assigned to $matric.$ext.";
                    } else {
                        $reviewError = $result['error'];
                    }
                }
            }
        }
    } elseif ($action === 'discard_pending') {
        $batch = $_POST['pending_batch'] ?? '';
        $file  = basename($_POST['pending_file'] ?? '');
        if (!is_valid_batch_id($batch)) {
            $reviewError = 'Invalid batch.';
        } else {
            $pendingBase = realpath($deployTarget . '/submissions_pending');
            $srcPath     = $pendingBase !== false ? realpath($pendingBase . '/' . $batch . '/' . $file) : false;
            if ($srcPath !== false && $pendingBase !== false && str_starts_with($srcPath, $pendingBase . DIRECTORY_SEPARATOR)) {
                unlink($srcPath);
                @rmdir(dirname($srcPath));
                $reviewSuccess = 'Discarded.';
            } else {
                $reviewError = 'File not found.';
            }
        }
    } elseif ($action === 'reassign') {
        $oldMatric = preg_replace('/[^0-9]/', '', $_POST['old_matric'] ?? '');
        $ext       = ($_POST['ext'] ?? '') === 'pdf' ? 'pdf' : 'html';
        $newMatric = preg_replace('/[^0-9]/', '', $_POST['new_matric'] ?? '');
        $overwrite = !empty($_POST['overwrite']);
        if ($deployTarget === '') {
            $reassignError = 'No deployment target recorded for this module.';
        } elseif (!preg_match('/^\d{8}$/', $oldMatric) || !preg_match('/^\d{8}$/', $newMatric)) {
            $reassignError = 'Invalid matric number.';
        } elseif ($oldMatric === $newMatric) {
            $reassignError = 'New matric is the same as the current one.';
        } else {
            $submissionsDir = $deployTarget . '/submissions';
            $srcFile = $submissionsDir . '/' . $oldMatric . '.' . $ext;
            if (!file_exists($srcFile)) {
                $reassignError = 'Source submission not found — it may already have been reassigned.';
            } else {
                $provenance       = provenance_read($submissionsDir);
                $originalFilename = $provenance[$oldMatric . '.' . $ext]['original_filename'] ?? ($oldMatric . '.' . $ext);
                $result = place_submission($deployTarget, $newMatric, $ext, $srcFile, $originalFilename, 'manual', $overwrite);
                if ($result['ok']) {
                    $provenance = provenance_read($submissionsDir);
                    unset($provenance[$oldMatric . '.' . $ext]);
                    provenance_write($submissionsDir, $provenance);
                    $reassignSuccess = "Reassigned to $newMatric.$ext.";
                } else {
                    $reassignError = $result['error'];
                }
            }
        }
    }
}

// ── Build review queue ───────────────────────────────────────────────
$reviewRows = [];
$knownMatrics = $deployTarget !== '' ? read_marksheet_matrics($studentsFile) : [];
if ($deployTarget !== '') {
    $submissionsDir = $deployTarget . '/submissions';
    $provenance     = provenance_read($submissionsDir);
    foreach (list_pending_submissions($deployTarget) as $p) {
        $ext   = strtolower(pathinfo($p['file'], PATHINFO_EXTENSION));
        $match = match_submission_filename($p['file'], $knownMatrics);
        $existingOriginal = null;
        if ($match['status'] === 'matched' && in_array($ext, ['html', 'pdf'], true)) {
            $destKey = $match['matric'] . '.' . $ext;
            if (file_exists($submissionsDir . '/' . $destKey)) {
                $existingOriginal = $provenance[$destKey]['original_filename'] ?? 'an earlier upload';
            }
        }
        $reviewRows[] = [
            'batch' => $p['batch'], 'file' => $p['file'], 'ext' => $ext,
            'match' => $match, 'existing_original' => $existingOriginal,
        ];
    }
}

// ── Build current-submissions table ────────────────────────────────
$currentSubs = [];
if ($deployTarget !== '') {
    $submissionsDir = $deployTarget . '/submissions';
    $provenance     = provenance_read($submissionsDir);
    foreach (['html', 'pdf'] as $ext) {
        foreach (glob($submissionsDir . '/*.' . $ext) ?: [] as $f) {
            $matric = basename($f, '.' . $ext);
            if (!preg_match('/^\d{8}$/', $matric)) continue;
            $prov = $provenance[$matric . '.' . $ext] ?? null;
            $currentSubs[] = [
                'matric'             => $matric,
                'ext'                => $ext,
                'original_filename'  => $prov['original_filename'] ?? '(placed before tracking was added)',
                'matched_via'        => $prov['matched_via'] ?? 'unknown',
            ];
        }
    }
    usort($currentSubs, fn($a, $b) => strcmp($a['matric'], $b['matric']));
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($currentModuleId) ?> — Submissions</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<?php
$navLinks = [];
if ($moduleReturnUrl !== '') $navLinks[] = ['label' => 'Return to marking', 'href' => $moduleReturnUrl];
$navLinks[] = ['label' => 'Students',      'href' => 'students.php'];
$navLinks[] = ['label' => 'Configuration', 'href' => 'module_edit.php'];
$navLinks[] = ['label' => 'Documentation', 'href' => 'docs.php'];
$navLinks[] = ['label' => 'Sign out',      'href' => 'module_logout.php'];
render_page_header($currentModuleId . ' — Submissions', $navLinks, $currentMarkerName);
?>
<main class="wide">

<?php if ($deployTarget === ''): ?>
  <div class="alert alert-warning">
    <strong>No deployment target recorded.</strong>
    This module must be deployed before submissions can be managed here.
  </div>
<?php else: ?>

<div class="card">
  <h2>Upload Submissions <button type="button" class="info-btn" onclick="toggleHelp(this)" aria-label="Show help" aria-expanded="false">&#x2139;</button></h2>
  <div class="help-box" hidden>
    <p>Upload the zip from Moodle's "Download all submissions". Students are asked to include their 8-digit
    matric number in their filename — files with exactly one clean 8-digit match to a student on the current
    marksheet are placed automatically. Everything else (a typo'd digit, the matric mixed in with other text,
    more than one candidate found, or no matric at all) is left in the review queue below for you to resolve
    by hand, with a link to open and read the file first.</p>
  </div>
  <?php if (!empty($uploadErrors)): ?>
    <div class="alert alert-danger"><?php foreach ($uploadErrors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?></div>
  <?php endif; ?>
  <?php if ($uploadSummary): ?>
    <div class="alert alert-success"><?= htmlspecialchars($uploadSummary) ?></div>
  <?php endif; ?>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
    <input type="hidden" name="_action" value="upload_submissions">
    <div class="field" style="max-width:420px;">
      <label for="zip_file">Submissions zip file</label>
      <input type="file" id="zip_file" name="zip_file" accept=".zip" required>
    </div>
    <div class="btn-row"><button type="submit" class="btn-primary">Upload</button></div>
  </form>
</div>

<?php if ($reviewError):   ?><div class="alert alert-danger"><?= htmlspecialchars($reviewError) ?></div><?php endif; ?>
<?php if ($reviewSuccess): ?><div class="alert alert-success"><?= htmlspecialchars($reviewSuccess) ?></div><?php endif; ?>

<div class="card">
  <h2>Review Queue — <?= count($reviewRows) ?> file<?= count($reviewRows) !== 1 ? 's' : '' ?> awaiting a match</h2>
  <?php if (empty($reviewRows)): ?>
    <p style="color:#64748b;font-size:.9rem;">Nothing waiting for review.</p>
  <?php else: ?>
  <datalist id="known-matrics">
    <?php foreach ($knownMatrics as $m): ?><option value="<?= htmlspecialchars($m) ?>"><?php endforeach; ?>
  </datalist>
  <table class="dyn-table">
    <thead><tr><th>File</th><th>Reason</th><th>Assign to matric</th><th></th><th class="col-action"></th></tr></thead>
    <tbody>
    <?php foreach ($reviewRows as $row):
        $viewHref = 'view_submission.php?pending=' . urlencode($row['batch'] . '/' . $row['file']);
        $suggested = $row['match']['matric'] ?? '';
        $reasonText = match ($row['match']['status']) {
            'matched'      => $row['existing_original'] !== null ? 'Would overwrite an existing submission' : 'Ready — click Save',
            'ambiguous'    => 'Multiple possible matric numbers found: ' . implode(', ', $row['match']['candidates']),
            'not_found'    => 'Matric ' . ($row['match']['candidates'][0] ?? '') . ' found in filename but is not on the current marksheet (possible typo)',
            'no_candidate' => 'No 8-digit matric number found in the filename',
            default        => '',
        };
    ?>
    <tr>
      <td><?= htmlspecialchars($row['file']) ?> &nbsp;<a href="<?= htmlspecialchars($viewHref) ?>" target="_blank" rel="noopener">View</a></td>
      <td style="font-size:.8rem;color:#64748b;">
        <?= htmlspecialchars($reasonText) ?>
        <?php if ($row['existing_original'] !== null): ?>
          <br>Existing file: <?= htmlspecialchars($row['existing_original']) ?>
          &nbsp;<a href="view_submission.php?matric=<?= urlencode($suggested) ?>&amp;ext=<?= urlencode($row['ext']) ?>" target="_blank" rel="noopener">View</a>
        <?php endif; ?>
      </td>
      <td>
        <form method="post" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="_action" value="resolve_pending">
          <input type="hidden" name="pending_batch" value="<?= htmlspecialchars($row['batch']) ?>">
          <input type="hidden" name="pending_file" value="<?= htmlspecialchars($row['file']) ?>">
          <input type="text" name="matric" list="known-matrics" inputmode="numeric" pattern="\d{8}" maxlength="8"
                 value="<?= htmlspecialchars($suggested) ?>" placeholder="8-digit matric" style="width:9rem;">
          <?php if ($row['existing_original'] !== null): ?>
          <label style="font-size:.78rem;display:flex;align-items:center;gap:.3rem;"><input type="checkbox" name="overwrite" value="1"> overwrite</label>
          <?php endif; ?>
          <button type="submit" class="btn-primary" style="padding:.35rem .8rem;">Save</button>
        </form>
      </td>
      <td></td>
      <td class="col-action">
        <form method="post" onsubmit="return confirm('Discard this file? It cannot be undone.');">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="_action" value="discard_pending">
          <input type="hidden" name="pending_batch" value="<?= htmlspecialchars($row['batch']) ?>">
          <input type="hidden" name="pending_file" value="<?= htmlspecialchars($row['file']) ?>">
          <button type="submit" class="btn-remove">Discard</button>
        </form>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php if ($reassignError):   ?><div class="alert alert-danger"><?= htmlspecialchars($reassignError) ?></div><?php endif; ?>
<?php if ($reassignSuccess): ?><div class="alert alert-success"><?= htmlspecialchars($reassignSuccess) ?></div><?php endif; ?>

<div class="card">
  <h2>Current Submissions — <?= count($currentSubs) ?></h2>
  <p style="font-size:.8rem;color:#475569;margin-bottom:1rem;">
    If a submission ever turns out to be filed under the wrong student — including the rare case where a
    mistyped matric coincidentally matches a different real student — reassign it here.
  </p>
  <?php if (empty($currentSubs)): ?>
    <p style="color:#64748b;font-size:.9rem;">No submissions placed yet.</p>
  <?php else: ?>
  <datalist id="known-matrics-2">
    <?php foreach ($knownMatrics as $m): ?><option value="<?= htmlspecialchars($m) ?>"><?php endforeach; ?>
  </datalist>
  <table class="dyn-table">
    <thead><tr><th>Matric</th><th>Ext</th><th>Original filename</th><th>Matched via</th><th>Reassign to</th></tr></thead>
    <tbody>
    <?php foreach ($currentSubs as $s): ?>
      <tr>
        <td><?= htmlspecialchars($s['matric']) ?></td>
        <td><?= htmlspecialchars($s['ext']) ?></td>
        <td><?= htmlspecialchars($s['original_filename']) ?>
          &nbsp;<a href="view_submission.php?matric=<?= urlencode($s['matric']) ?>&amp;ext=<?= urlencode($s['ext']) ?>" target="_blank" rel="noopener">View</a>
        </td>
        <td><?= htmlspecialchars($s['matched_via']) ?></td>
        <td>
          <form method="post" style="display:flex;gap:.4rem;align-items:center;flex-wrap:wrap;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="_action" value="reassign">
            <input type="hidden" name="old_matric" value="<?= htmlspecialchars($s['matric']) ?>">
            <input type="hidden" name="ext" value="<?= htmlspecialchars($s['ext']) ?>">
            <input type="text" name="new_matric" list="known-matrics-2" inputmode="numeric" pattern="\d{8}" maxlength="8"
                   placeholder="new matric" style="width:8rem;">
            <label style="font-size:.78rem;display:flex;align-items:center;gap:.3rem;"><input type="checkbox" name="overwrite" value="1"> overwrite</label>
            <button type="submit" class="btn-secondary" style="padding:.3rem .7rem;">Reassign</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php endif; ?>
</main>
<script>
function toggleHelp(btn) {
  const box = btn.parentElement.nextElementSibling;
  box.hidden = !box.hidden;
  btn.setAttribute('aria-expanded', String(!box.hidden));
}
</script>
</body>
</html>
