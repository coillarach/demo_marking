<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

$targets      = load_deploy_targets();
$deployTarget = isset($targets[$currentModuleId]) ? rtrim($targets[$currentModuleId], '/\\') : '';

$archiveId = $_GET['archive'] ?? '';
if (!is_valid_archive_id($archiveId)) {
    http_response_code(400);
    die('Invalid archive id.');
}
if ($deployTarget === '') {
    http_response_code(404);
    die('No deployment target recorded for this module.');
}

$archivesBase = realpath($deployTarget . '/archives');
$archiveDir   = $archivesBase !== false ? $archivesBase . DIRECTORY_SEPARATOR . $archiveId : false;
$real         = $archiveDir !== false ? realpath($archiveDir) : false;
if ($real === false || $archivesBase === false || !str_starts_with($real, $archivesBase . DIRECTORY_SEPARATOR)) {
    http_response_code(404);
    die('Archive not found.');
}
$archiveDir = $real;

$meta     = [];
$metaFile = $archiveDir . '/meta.json';
if (file_exists($metaFile)) {
    $meta = json_decode(file_get_contents($metaFile), true) ?: [];
}

$marksheet      = read_marksheet_csv($archiveDir . '/data/students.csv');
$records        = glob_json_records($archiveDir . '/data');
$submissionsDir = $archiveDir . '/submissions';
$nameIdx        = $marksheet['col']['Full name'] ?? null;

$overview = [];
foreach ($records as $r) {
    $matric = $r['matric'] ?? '';
    $overview[] = [
        'matric'         => $matric,
        'name'           => ($nameIdx !== null && isset($marksheet['rows'][$matric])) ? ($marksheet['rows'][$matric][$nameIdx] ?? '') : '',
        'marker'         => $r['marker'] ?? '',
        'complete'       => !empty($r['complete']),
        'tier'           => $r['tier'] ?? '',
        'has_submission' => has_submission_file($submissionsDir, $matric),
    ];
}
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($currentModuleId) ?> — Archive <?= htmlspecialchars($archiveId) ?></title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<?php
$navLinks = [
    ['label' => 'Archives',      'href' => 'archives.php'],
    ['label' => 'Students',      'href' => 'students.php'],
    ['label' => 'Documentation', 'href' => 'docs.php'],
    ['label' => 'Sign out',      'href' => 'module_logout.php'],
];
render_page_header($currentModuleId . ' — Archive ' . $archiveId, $navLinks, $currentMarkerName);
?>
<main class="wide">

<div class="card">
  <h2>Archive Details</h2>
  <table style="font-size:.9rem;border-collapse:collapse;">
    <tr><td style="color:#64748b;padding:.2rem .75rem .2rem 0;width:120px;">Archived at</td><td><?= htmlspecialchars($meta['archived_at'] ?? $archiveId) ?></td></tr>
    <tr><td style="color:#64748b;padding:.2rem .75rem .2rem 0;">Archived by</td><td><?= htmlspecialchars($meta['archived_by'] ?? '-') ?></td></tr>
    <tr><td style="color:#64748b;padding:.2rem .75rem .2rem 0;">Students</td><td><?= (int)($meta['student_count'] ?? count($overview)) ?></td></tr>
  </table>
  <?php if (file_exists($archiveDir . '/data/students.csv')): ?>
  <p style="margin-top:.75rem;">
    <a class="btn-secondary" href="archive_download.php?archive=<?= urlencode($archiveId) ?>">Download archived students.csv</a>
  </p>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Archived Records — <?= count($overview) ?></h2>
  <?php if (empty($overview)): ?>
    <p style="color:#64748b;">No mark records in this archive.</p>
  <?php else: ?>
  <table class="dyn-table">
    <thead><tr><th>Matric</th><th>Name</th><th>Marker</th><th>Complete</th><th>Tier</th><th>Submission</th></tr></thead>
    <tbody>
    <?php foreach ($overview as $o): ?>
      <tr>
        <td><?= htmlspecialchars($o['matric']) ?></td>
        <td><?= htmlspecialchars($o['name']) ?></td>
        <td><?= htmlspecialchars($o['marker']) ?></td>
        <td><?= $o['complete'] ? 'Yes' : 'In progress' ?></td>
        <td><?= htmlspecialchars($o['tier']) ?></td>
        <td><?= $o['has_submission'] ? 'Yes' : 'No' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

</main>
</body>
</html>
