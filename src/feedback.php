<?php
/**
 * Feedback generation functions — shared by report.php and export.php
 */

function techFeedback(array $r): string {
    $tier       = $r['tier'] ?? 'unknown';
    $features   = $r['features'] ?? [];
    $note       = trim($r['tech_note'] ?? '');
    $authorship = $r['authorship'] ?? 'pass';

    if ($authorship === 'fail') {
        $comment = trim($r['misconduct_comment'] ?? '');
        $out = 'Authorship of the submitted code could not be confirmed at the demonstration. This case has been referred under the academic misconduct procedure.';
        if ($comment !== '') $out .= ' Marker\'s observations: ' . $comment;
        return $out;
    }

    $tierLabels    = FEEDBACK_TIER_LABELS;
    $featureLabels = array_map(fn($f) => $f['label'], QUESTIONS);

    $listed     = array_map(fn($f) => $featureLabels[$f] ?? $f, $features);
    $featureStr = implode(', ', $listed);

    $out = $tierLabels[$tier] ?? 'Enhancement tier: ' . $tier . '.';
    if ($featureStr) $out .= ' Features implemented: ' . $featureStr . '.';

    $qa              = $r['qa'] ?? [];
    $couldNotExplain = [];
    $withPrompting   = [];
    foreach ($qa as $key => $val) {
        if (str_ends_with($key, '_text')) continue;
        if (preg_match('/^qa_([a-z_]+)_cust_\d+$/', $key, $m)) {
            $fLabel = $featureLabels[$m[1]] ?? null;
        } elseif (preg_match('/^qa_([a-z_]+)_\d+$/', $key, $m)) {
            $fLabel = $featureLabels[$m[1] ?? ''] ?? ($m[1] ?? '');
        } else {
            continue;
        }
        if ($val === 'no'     && $fLabel && !in_array($fLabel, $couldNotExplain)) $couldNotExplain[] = $fLabel;
        if ($val === 'prompt' && $fLabel && !in_array($fLabel, $withPrompting))   $withPrompting[]   = $fLabel;
    }
    if ($couldNotExplain) $out .= ' Could not explain: '      . implode(', ', array_unique($couldNotExplain)) . '.';
    if ($withPrompting)   $out .= ' Required prompting on: '  . implode(', ', array_unique($withPrompting))   . '.';
    if ($note)            $out .= ' ' . $note;

    return $out;
}

function designFeedback(array $r): string {
    $d     = $r['design'] ?? [];
    $parts = [];
    foreach (DESIGN_CRITERIA as $key => $crit) {
        $score = (int)($d[$key] ?? 0);
        $text  = $crit['feedback'][$score] ?? '';
        if ($text !== '') $parts[] = $text;
    }
    return implode(' ', $parts);
}

function demoFeedback(array $r): string {
    $band      = $r['demo_band'] ?? '';
    $mark      = intval($r['demo_mark'] ?? 0);
    $setup     = $r['setup_ready'] ?? null;
    $presented = $r['presented_clearly'] ?? null;

    $descs = [
        'excellent' => 'The student presented fluently with everything set up in advance, explained all aspects of their code confidently, and answered every question without hesitation.',
        'good'      => 'The student gave a clear presentation, explained most of their code well, and showed only minor gaps under questioning.',
        'adequate'  => 'The student demonstrated their features adequately but required some prompting to explain the code and design decisions.',
        'limited'   => 'The student showed limited ability to explain the code without prompting, with significant gaps in their explanations.',
        'poor'      => 'The student was unable to adequately explain significant portions of the submitted code during the demonstration.',
    ];

    $demoMax = round(MARK_WEIGHTS['demo'] * 100);
    $out = $descs[$band] ?? "Demonstration quality mark: $mark/$demoMax.";
    if ($setup     === 'no') $out .= ' Note: time was lost at the start of the demonstration waiting for the environment to be set up.';
    if ($presented === 'no') $out .= ' The student was largely unable to present their features independently.';

    $comment = trim($r['demo_comment'] ?? '');
    if ($comment) $out .= ' ' . $comment;

    return $out;
}

function buildFeedbackComment(array $r): string {
    $weights   = MARK_WEIGHTS;
    $techMax   = round($weights['tech']   * 100);
    $designMax = round($weights['design'] * 100);
    $demoMax   = round($weights['demo']   * 100);
    $reportMax = round($weights['report'] * 100);

    $techW   = floatval($r['tech_weighted']    ?? 0);
    $designT = intval($r['design']['total']    ?? 0);
    $demoM   = intval($r['demo_mark']          ?? 0);
    $reportM = floatval($r['report_mark']      ?? 0);
    $total   = round($techW + $designT + $demoM + $reportM, 1);

    $authorship = $r['authorship'] ?? 'pass';
    $comment    = trim($r['misconduct_comment'] ?? '');
    $auth_line  = ($authorship === 'fail')
        ? 'AUTHORSHIP: NOT CONFIRMED - referred to academic misconduct procedure.'
          . ($comment !== '' ? ' Rationale: ' . $comment : '')
        : 'Authorship confirmed at demonstration.';

    // Knowledge check summary — labels come from config
    $kqData   = $r['kq'] ?? [];
    $kqLabels = array_map(fn($g) => $g['label'], KNOWLEDGE_QUESTIONS);
    $kqConcerns = [];
    if ($kqData) {
        $byTech = [];
        foreach ($kqData as $key => $val) {
            if (str_ends_with($key, '_text')) continue;
            if (preg_match('/^kq_([a-z]+)_(\d+)$/', $key, $m)) {
                $tech = $m[1];
                $qi   = (int)$m[2] + 1;
                if ($val === 'partial') $byTech[$tech]['partial'][] = 'Q' . $qi;
                if ($val === 'no')      $byTech[$tech]['no'][]      = 'Q' . $qi;
            } elseif (preg_match('/^kq_([a-z]+)_cust_\d+$/', $key, $m)) {
                $tech  = $m[1];
                $qText = trim($kqData[$key . '_text'] ?? '');
                $label = $qText ? '"' . $qText . '"' : 'custom question';
                if ($val === 'partial') $byTech[$tech]['partial'][] = $label;
                if ($val === 'no')      $byTech[$tech]['no'][]      = $label;
            }
        }
        foreach ($byTech as $tech => $issues) {
            $label = $kqLabels[$tech] ?? $tech;
            $parts = [];
            if (!empty($issues['partial'])) $parts[] = 'partial: ' . implode(', ', $issues['partial']);
            if (!empty($issues['no']))      $parts[] = 'could not answer: ' . implode(', ', $issues['no']);
            if ($parts) $kqConcerns[] = $label . ' - ' . implode('; ', $parts);
        }
    }
    $kq_section = $kqConcerns
        ? 'Knowledge check concerns: ' . implode('. ', $kqConcerns) . '.'
        : ($kqData ? 'Knowledge check: no concerns noted.' : '');

    $passMark   = PASS_MARK;
    $passStatus = ($total >= $passMark) ? 'PASS' : 'FAIL';

    $lines = [
        $auth_line,
        $kq_section,
        '',
        "Technical Implementation ({$techMax}%): {$techW}/{$techMax}",
        techFeedback($r),
        '',
        "Design & Usability ({$designMax}%): {$designT}/{$designMax}",
        designFeedback($r),
        '',
        "Demonstration Quality ({$demoMax}%): {$demoM}/{$demoMax}",
        demoFeedback($r),
        '',
        "Critical Report ({$reportMax}%): {$reportM}/{$reportMax}",
        trim($r['report_comment'] ?? '') ?: 'Report not yet marked.',
        '',
        "Overall: {$total}/100 — {$passStatus} (pass mark: {$passMark}%)",
    ];

    return implode("\n", $lines);
}
