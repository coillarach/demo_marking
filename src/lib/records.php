<?php
/**
 * Config-independent data-reading and submission-matching helpers.
 * Shared by the deployed marking tool (src/*.php, via src/auth_check.php)
 * and the admin control-plane app (admin/lib.php requires this file directly).
 * Every function here takes an explicit path — none of them touch DATA_DIR,
 * SUBMISSIONS_DIR, or any other module-config constant.
 */

function read_marksheet_csv(string $csvFile): array {
    if (!file_exists($csvFile)) return ['header' => null, 'rows' => [], 'col' => []];
    $handle = fopen($csvFile, 'r');
    $header = fgetcsv($handle, escape: '\\');
    if (!$header) { fclose($handle); return ['header' => null, 'rows' => [], 'col' => []]; }
    $header[0] = ltrim($header[0], "\xEF\xBB\xBF"); // strip UTF-8 BOM if present
    $usernameIdx = array_search('Username', $header);
    if ($usernameIdx === false) { fclose($handle); return ['header' => $header, 'rows' => [], 'col' => []]; }
    $rows = [];
    while (($row = fgetcsv($handle, escape: '\\')) !== false) {
        $matric = trim($row[$usernameIdx] ?? '');
        if ($matric !== '') $rows[$matric] = $row;
    }
    fclose($handle);
    return ['header' => $header, 'rows' => $rows, 'col' => array_flip($header)];
}

function read_marksheet_matrics(string $csvFile): array {
    // PHP casts numeric-string keys to int; callers (JSON for the autocomplete,
    // strict in_array in filename matching) need them as strings.
    $matrics = array_map('strval', array_keys(read_marksheet_csv($csvFile)['rows']));
    sort($matrics);
    return $matrics;
}

function read_marksheet_participant_map(string $csvFile): array {
    $map = [];
    foreach (read_marksheet_csv($csvFile)['rows'] as $matric => $row) {
        $ident = trim($row[0] ?? '');
        if ($ident !== '') $map[$matric] = $ident;
    }
    return $map;
}

function glob_json_records(string $dataDir): array {
    $records = [];
    foreach (glob(rtrim($dataDir, '/\\') . '/*.json') ?: [] as $file) {
        $rec = json_decode(file_get_contents($file), true);
        if ($rec) $records[] = $rec;
    }
    usort($records, fn($a, $b) => strcmp($a['matric'] ?? '', $b['matric'] ?? ''));
    return $records;
}

function find_orphan_records(array $records, array $marksheetRows): array {
    if (!$marksheetRows) return [];
    $orphans = [];
    foreach ($records as $r) {
        $matric = $r['matric'] ?? '';
        if ($matric !== '' && !isset($marksheetRows[$matric])) {
            $orphans[] = $r;
        }
    }
    return $orphans;
}

function has_submission_file(string $submissionsDir, string $matric): bool {
    $base = rtrim($submissionsDir, '/\\') . '/';
    return file_exists($base . $matric . '.html') || file_exists($base . $matric . '.pdf');
}

/** Returns 'html', 'pdf', or null. If both exist, 'pdf' wins (matches report.php's viewer default). */
function submission_extension(string $submissionsDir, string $matric): ?string {
    $base = rtrim($submissionsDir, '/\\') . '/';
    if (file_exists($base . $matric . '.pdf'))  return 'pdf';
    if (file_exists($base . $matric . '.html')) return 'html';
    return null;
}

// ─── Submission filename matching ──────────────────────────────────
//
// Students are asked to include their 8-digit matric number in their
// submission's filename. In practice that fails in predictable ways (a
// mistyped digit, the matric concatenated with other text, or the
// instruction ignored entirely) — these helpers never guess past what's
// genuinely unambiguous; anything else is left for a human to resolve.

/** Distinct runs of exactly 8 digits found in the filename. A 9+ digit run
 *  (concatenation with extra digits) is not a clean 8-digit candidate and is
 *  not returned — it's treated the same as finding nothing. */
function extract_matric_candidates(string $filename): array {
    if (!preg_match_all('/\d+/', $filename, $m)) return [];
    $candidates = [];
    foreach ($m[0] as $run) {
        if (strlen($run) === 8) $candidates[$run] = true;
    }
    return array_map('strval', array_keys($candidates)); // keys are cast to int by PHP
}

/** status: matched | ambiguous | not_found | no_candidate */
function match_submission_filename(string $filename, array $knownMatrics): array {
    $candidates = extract_matric_candidates($filename);
    if (count($candidates) === 0) {
        return ['status' => 'no_candidate', 'matric' => null, 'candidates' => []];
    }
    if (count($candidates) > 1) {
        return ['status' => 'ambiguous', 'matric' => null, 'candidates' => $candidates];
    }
    $matric = $candidates[0];
    if (!in_array($matric, $knownMatrics, true)) {
        return ['status' => 'not_found', 'matric' => null, 'candidates' => [$matric]];
    }
    return ['status' => 'matched', 'matric' => $matric, 'candidates' => [$matric]];
}
