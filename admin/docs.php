<?php
require_once __DIR__ . '/lib.php';
?>
<!DOCTYPE html>
<html lang="en-GB">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Documentation — Demo Marking Tool</title>
<link rel="stylesheet" href="static/admin.css">
<style>
  .docs-toc { display: flex; flex-wrap: wrap; gap: .5rem 1.25rem; font-size: .85rem; margin-bottom: .5rem; }
  .docs-toc a { color: #2563eb; text-decoration: none; }
  .docs-toc a:hover { text-decoration: underline; }
  main h2 { scroll-margin-top: 4.5rem; }
  main h3 { scroll-margin-top: 4.5rem; }
  .card ol, .card ul { margin: .5rem 0 .75rem 1.25rem; }
  .card ol li, .card ul li { margin-bottom: .4rem; font-size: .88rem; }
  .card p { font-size: .88rem; margin-bottom: .6rem; line-height: 1.55; }
</style>
</head>
<body>
<?php render_page_header('Documentation', [['label' => 'Home', 'href' => 'home.php']]); ?>
<main>

<div class="card">
  <h2>Contents</h2>
  <nav class="docs-toc">
    <a href="#roles">Getting started &amp; roles</a>
    <a href="#configuring">Creating &amp; configuring a module</a>
    <a href="#marking">The marking process</a>
    <a href="#data">Data management</a>
  </nav>
</div>

<div class="card" id="roles">
  <h2>Getting started &amp; roles</h2>
  <p>There are three separate sign-in points, and each one only unlocks what that role needs:</p>
  <ul>
    <li><strong>Global admin</strong> (<code>admin/login.php</code>) — one shared account with full control over every module: creating modules, deploying them, and managing marker accounts. Use this to set up a new module for the first time.</li>
    <li><strong>Module editor</strong> (<code>admin/module_login.php</code>) — any marker on a module can sign in here with their marker ID and password to edit that one module's assessment rubric (weights, features, tiers, knowledge questions), upload the Moodle marksheet, upload submissions, view the current data overview, and archive the module — all scoped to that module only.</li>
    <li><strong>Marking tool</strong> (each deployed module's own <code>login.php</code>) — the day-to-day marking sign-in, used to open the demo marking form, the report form, and the CSV export. From inside the marking tool, <strong>Configure</strong> jumps straight into the module editor for that module without a separate sign-in.</li>
  </ul>
</div>

<div class="card" id="configuring">
  <h2>Creating &amp; configuring a module</h2>
  <ol>
    <li><strong>Create the module.</strong> As global admin, go to the Dashboard and click <em>+ Create new module</em>. Set the module ID (matching the Moodle module code, e.g. <code>SET08101</code>), name, level, pass mark, and the marker accounts who will mark it.</li>
    <li><strong>Deploy it.</strong> Click <em>Deploy</em> and choose an absolute filesystem path outside the project checkout. This copies the marking tool into a self-contained web application at that path, with its own empty <code>data/</code> and <code>submissions/</code> folders. Re-running deploy later is safe — it never deletes existing student data.</li>
    <li><strong>Configure the rubric.</strong> Sign in to the module editor (as global admin via "Edit basics &amp; users", or as a marker via <em>Module editor</em> sign-in) and work through the tabs: Weights &amp; Timers, Features, Design &amp; Usability, Tiers, Report &amp; Wording, and Knowledge Questions. Each section has its own <span class="info-btn">&#x2139;</span> help button with detailed guidance and worked examples for that section — the weights must sum to exactly 1.0, and tier ranges/rules determine which mark band a student lands in based on which features they demonstrated.</li>
    <li><strong>Upload the marksheet.</strong> See <a href="#data">Data management</a> below — this is required before markers can mark, since it supplies the student list.</li>
  </ol>
</div>

<div class="card" id="marking">
  <h2>The marking process</h2>
  <ol>
    <li>Markers sign in at the deployed module's own login page (not the admin tool).</li>
    <li><strong>Demo marking</strong> (<code>demo.php</code>) — the main technical demonstration session: select or search for a student by matric, run the timer, record which features were demonstrated, ask the feature Q&amp;A and knowledge-check questions, and score the design/usability criteria. Progress is saved automatically as you go.</li>
    <li><strong>Report marking</strong> (<code>report.php</code>, if the module has a report component) — views the student's submitted HTML or PDF report side-by-side with a mark and feedback entry form, with prev/next navigation through the student list. By default the report gets a single mark; if the assessment grades it against several criteria (for example research, analysis and writing), define them on the <em>Report &amp; Wording</em> tab of the module editor and markers will enter one mark per criterion. The same tab lets you reword headings that assume a code-based assessment.</li>
    <li><strong>Export</strong> (<code>export.php</code>) — a dashboard of every student's marking progress (complete, pending, authorship referrals, missing reports), plus a <em>Download Moodle CSV</em> button that produces a file ready to re-import into Moodle's offline grading marksheet, with a generated feedback comment per student. Tick "Include full question text for unanswered Q&amp;A" for more detailed feedback comments if needed. Records for students no longer in the marksheet (e.g. a suspended enrolment) are flagged separately and excluded from the CSV rather than silently included.</li>
  </ol>
</div>

<div class="card" id="data">
  <h2>Data management</h2>

  <h3>Uploading the Moodle marksheet</h3>
  <p>On the <em>Students</em> page in the module editor, upload the offline grading marksheet CSV exported from Moodle. This supplies the student list, names, and the identifiers used when generating the Moodle-import CSV later. Uploading a new file replaces the existing one — it does not merge.</p>

  <h3>Uploading submissions</h3>
  <p>On the <em>Submissions</em> page, upload the zip from Moodle's "Download all submissions". Students are asked to put their 8-digit matric number in their filename; files with exactly one clean match to a student on the current marksheet are placed automatically. Everything else lands in the <strong>review queue</strong> for you to resolve by hand — this covers every exception that comes up in practice:</p>

  <p><strong>Large cohorts:</strong> a full submissions zip can easily exceed 1GB once you have 100+ students with PDF reports. If the upload fails with a raw server error page (rather than a message from this app) or the app reports the upload exceeds the server's request size limit, the server's PHP configuration needs raising — see "Server requirements for uploading submissions" in the project README (<code>upload_max_filesize</code>, <code>post_max_size</code>, and <code>memory_limit</code> in <code>php.ini</code>, plus any web-server-level request body limit). This is a one-time server setup step, not something to work around per upload.</p>

  <h4 style="margin-top:0;font-size:.85rem;color:#334155;">Exception cases</h4>
  <ul>
    <li><strong>Typo'd matric</strong> — the number in the filename isn't on the marksheet. Open the file (there's a View link), confirm whose work it is, and type the correct matric.</li>
    <li><strong>Matric concatenated with other text</strong> — no clean 8-digit run was found, so nothing was guessed. Assign it manually.</li>
    <li><strong>Multiple files from one student</strong> — the second file for the same matric+format won't silently overwrite the first; it's held for review with a "would overwrite" note and a link to view the file that's already there, so you can pick the right one.</li>
    <li><strong>No matric in the filename at all</strong> — always lands in review; view the file to identify the student.</li>
  </ul>
  <p><strong>Rare edge case: a mistyped matric that coincidentally matches a different real student.</strong> This can't be caught automatically, because it passes every check cleanly — it's a genuinely valid match. If it happens, it usually surfaces later when that other student's real submission arrives and gets flagged as "would overwrite" (with the wrongly-placed file's original filename shown for comparison). The <strong>Current Submissions</strong> table at the bottom of the Submissions page lists every placed file with its original filename and a <em>Reassign</em> action, so you can move a wrongly-matched submission to its real owner at any time — not just during the initial review.</p>

  <h3>Current data overview &amp; deleting a record</h3>
  <p>The <em>Students</em> page also shows every current mark record — matric, marker, completeness, tier, and whether a report/submission exists — with a <em>Delete</em> action per row for clearing a mistaken entry (wrong student selected, a test record, etc.). This removes the mark record and any submission file for that matric; it does not change the marksheet itself, since that's Moodle-sourced enrolment data.</p>

  <h3>Archiving a module at the end of an assessment period</h3>
  <p>When a cohort's marking is finished and you're ready to start a new one, use <em>Archive current data</em> on the Students page. Type the module ID to confirm, then everything currently in <code>data/</code> and <code>submissions/</code> — the marksheet, every mark record, and every submission file — is moved into a timestamped archive. Nothing is deleted, and a fresh empty <code>data/</code>/<code>submissions/</code> is ready immediately for the next cohort. Past archives are browsable read-only from the <em>Archives</em> page — the marksheet and every record are viewable, and the archived marksheet can be downloaded — so old data stays inspectable (e.g. for appeals) without being current.</p>
</div>

</main>
</body>
</html>
