<?php
require_once __DIR__ . '/module_auth.php';
require_once __DIR__ . '/lib.php';

$targets      = load_deploy_targets();
$deployTarget = isset($targets[$currentModuleId]) ? rtrim($targets[$currentModuleId], '/\\') : '';
$archives     = $deployTarget !== '' ? list_archives($deployTarget) : [];
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($currentModuleId) ?> — Archives</title>
<link rel="stylesheet" href="static/admin.css">
</head>
<body>
<?php
$navLinks = [];
if ($moduleReturnUrl !== '') $navLinks[] = ['label' => 'Return to marking', 'href' => $moduleReturnUrl];
$navLinks[] = ['label' => 'Students',       'href' => 'students.php'];
$navLinks[] = ['label' => 'Configuration',  'href' => 'module_edit.php'];
$navLinks[] = ['label' => 'Documentation',  'href' => 'docs.php'];
$navLinks[] = ['label' => 'Sign out',       'href' => 'module_logout.php'];
render_page_header($currentModuleId . ' — Archives', $navLinks, $currentMarkerName);
?>
<main>

<?php if ($deployTarget === ''): ?>
  <div class="alert alert-warning">No deployment target recorded for this module.</div>
<?php elseif (empty($archives)): ?>
  <div class="card">
    <p style="color:#64748b;font-size:.9rem;">
      No archives yet. Use <a href="students.php">Students</a> to archive the current module data
      at the end of an assessment period.
    </p>
  </div>
<?php else: ?>
<div class="card">
  <h2>Archived Data</h2>
  <p style="font-size:.8rem;color:#475569;margin-bottom:1rem;">
    Each archive is a snapshot of the marksheet, mark records, and submissions at the point it was
    taken — nothing here is current, but everything is inspectable.
  </p>
  <table class="dyn-table">
    <thead><tr><th>Archived</th><th>Students</th><th>Archived by</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($archives as $a):
        $when = $a['archived_at'] !== '' ? @date('d M Y H:i', strtotime($a['archived_at'])) : null;
    ?>
      <tr>
        <td><?= htmlspecialchars($when ?: $a['id']) ?></td>
        <td><?= (int)$a['student_count'] ?></td>
        <td><?= htmlspecialchars($a['archived_by']) ?></td>
        <td><a href="archive_view.php?archive=<?= urlencode($a['id']) ?>">View</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

</main>
</body>
</html>
