<?php
if (!function_exists('array_is_list')) {
    function array_is_list(array $a): bool {
        return $a === [] || array_keys($a) === range(0, count($a) - 1);
    }
}

function php_val(mixed $v, int $depth = 1): string {
    if (is_bool($v))   return $v ? 'true' : 'false';
    if (is_null($v))   return 'null';
    if (is_int($v))    return (string)$v;
    if (is_float($v)) {
        $s = rtrim(rtrim(sprintf('%.10f', $v), '0'), '.');
        return strpos($s, '.') !== false ? $s : $s . '.0';
    }
    if (is_string($v)) return "'" . addcslashes($v, "'\\") . "'";
    if (is_array($v))  return php_array_export($v, $depth);
    return var_export($v, true);
}

function php_array_export(array $arr, int $depth = 1): string {
    if (empty($arr)) return '[]';
    $pad  = str_repeat('    ', $depth);
    $pad0 = str_repeat('    ', $depth - 1);
    $isList = array_is_list($arr);
    $lines  = [];
    foreach ($arr as $k => $v) {
        $key    = $isList ? '' : php_val($k) . ' => ';
        $lines[] = $pad . $key . php_val($v, $depth + 1) . ',';
    }
    return "[\n" . implode("\n", $lines) . "\n{$pad0}]";
}

function extract_config_meta(string $file): array {
    $content = file_get_contents($file);
    $meta = ['MODULE_ID' => '', 'MODULE_NAME' => '', 'MODULE_LEVEL' => '', 'PASS_MARK' => 0];
    foreach (['MODULE_ID', 'MODULE_NAME', 'MODULE_LEVEL'] as $c) {
        if (preg_match("/define\s*\(\s*'$c'\s*,\s*'([^']*)'\s*\)/", $content, $m))
            $meta[$c] = $m[1];
    }
    if (preg_match("/define\s*\(\s*'PASS_MARK'\s*,\s*(\d+)\s*\)/", $content, $m))
        $meta['PASS_MARK'] = (int)$m[1];
    return $meta;
}

function generate_config(array $d): string {
    $id   = $d['module_id'];
    $name = $d['module_name'];
    $lvl  = $d['module_level'];
    $pass = (int)$d['pass_mark'];

    $out  = "<?php\n";
    $out .= "/**\n * Module configuration — $id $name ($lvl)\n */\n\n";
    $out .= "define('MODULE_ID',    " . php_val($id)   . ");\n";
    $out .= "define('MODULE_NAME',  " . php_val($name) . ");\n";
    $out .= "define('MODULE_LEVEL', " . php_val($lvl)  . ");\n";
    $out .= "define('PASS_MARK',    $pass);\n\n";
    $out .= "define('DATA_DIR',        __DIR__ . '/data/');\n";
    $out .= "define('SUBMISSIONS_DIR', __DIR__ . '/submissions/');\n\n";
    $out .= "define('MARK_WEIGHTS', " . php_array_export($d['mark_weights']) . ");\n\n";
    $out .= "define('TIMER_TOTAL', " . (int)$d['timer_total'] . ");\n";
    $out .= "define('TIMER_BREAK', " . (int)$d['timer_break'] . ");\n\n";
    $out .= "define('MARKERS', " . php_array_export($d['markers']) . ");\n\n";
    $out .= "// ── Tier ranges ───────────────────────────────────────────────────────────────\n";
    $out .= "define('TIER_RANGES', " . php_array_export($d['tier_ranges']) . ");\n\n";
    $out .= "// ── Tier rules (evaluated in order; first match wins) ─────────────────────────\n";
    $out .= "define('TIER_RULES', " . php_array_export($d['tier_rules']) . ");\n\n";
    $out .= "// ── Feedback strings ──────────────────────────────────────────────────────────\n";
    $out .= "define('FEEDBACK_TIER_LABELS', " . php_array_export($d['feedback_tier_labels']) . ");\n\n";
    $out .= "// ── Knowledge-check questions ─────────────────────────────────────────────────\n";
    $out .= "define('KNOWLEDGE_QUESTIONS', " . php_array_export($d['knowledge_questions']) . ");\n\n";
    $out .= "// ── Feature Q&A question banks ────────────────────────────────────────────────\n";
    $out .= "define('QUESTIONS', " . php_array_export($d['questions']) . ");\n\n";
    $out .= "// ── Design & Usability criteria ──────────────────────────────────────────────\n";
    $out .= "define('DESIGN_CRITERIA', " . php_array_export($d['design_criteria']) . ");\n";
    return $out;
}

function default_config_details(): array {
    return [
        'mark_weights' => ['tech' => 0.4, 'design' => 0.2, 'demo' => 0.25, 'report' => 0.15],
        'timer_total'  => 300,
        'timer_break'  => 180,
        'tier_ranges'  => [
            'advanced3'     => ['label' => 'Advanced (3 features)',     'cls' => 'tier-advanced',     'min' => 80, 'max' => 86, 'mid' => 83],
            'advanced2'     => ['label' => 'Advanced (2 features)',     'cls' => 'tier-advanced',     'min' => 75, 'max' => 79, 'mid' => 77],
            'advanced1'     => ['label' => 'Advanced (1 feature)',      'cls' => 'tier-advanced',     'min' => 70, 'max' => 74, 'mid' => 72],
            'intermediate2' => ['label' => 'Intermediate (2 features)', 'cls' => 'tier-intermediate', 'min' => 65, 'max' => 69, 'mid' => 67],
            'intermediate1' => ['label' => 'Intermediate (1 feature)',  'cls' => 'tier-intermediate', 'min' => 60, 'max' => 64, 'mid' => 62],
            'basic3'        => ['label' => 'Basic (3 features)',        'cls' => 'tier-basic',        'min' => 55, 'max' => 59, 'mid' => 57],
            'basic2'        => ['label' => 'Basic (2 features)',        'cls' => 'tier-basic',        'min' => 50, 'max' => 54, 'mid' => 52],
            'basic1'        => ['label' => 'Basic (1 feature)',         'cls' => 'tier-basic',        'min' => 45, 'max' => 49, 'mid' => 47],
            'partial'       => ['label' => 'Partial basic',             'cls' => 'tier-partial',      'min' => 40, 'max' => 44, 'mid' => 42],
            'fail'          => ['label' => 'Fail / non-functional',     'cls' => 'tier-fail',         'min' => 0,  'max' => 39, 'mid' => 20],
        ],
        'tier_rules' => [
            ['key' => 'advanced3',     'minAdv' => 3],
            ['key' => 'advanced2',     'minAdv' => 2],
            ['key' => 'advanced1',     'minAdv' => 1],
            ['key' => 'intermediate2', 'minInter' => 2],
            ['key' => 'intermediate1', 'minInter' => 1],
            ['key' => 'basic3',        'minBasic' => 3],
            ['key' => 'basic2',        'minBasic' => 2],
            ['key' => 'basic1',        'minBasic' => 1],
        ],
        'feedback_tier_labels' => [
            'advanced3'     => 'All three advanced enhancements were present and functional.',
            'advanced2'     => 'Two advanced enhancements were present and functional.',
            'advanced1'     => 'One advanced enhancement was present and functional.',
            'intermediate2' => 'Both intermediate enhancements were present and functional.',
            'intermediate1' => 'One intermediate enhancement was present and functional.',
            'basic3'        => 'All three basic enhancements were complete and working.',
            'basic2'        => 'Two basic enhancements were complete and working.',
            'basic1'        => 'One basic enhancement was complete and working.',
            'partial'       => 'Some basic enhancements were attempted but not all were fully functional.',
            'fail'          => 'The submission did not meet the basic requirements.',
        ],
        'questions'           => [],
        'knowledge_questions' => [],
        'design_criteria'     => [
            'd_css'       => ['label' => 'CSS stylesheet applied and effective',     'description' => '0 = absent / 2 = minimal but present / 4 = effective and considered', 'max' => 4, 'feedback' => [0 => 'No effective CSS stylesheet was observed.',                                         2 => 'A basic CSS stylesheet was present but limited in scope.',   4 => 'CSS stylesheet was effective and well-considered.']],
            'd_structure' => ['label' => 'Standard page structure present',          'description' => 'Visible header, footer, and navigation',                              'max' => 2, 'feedback' => [0 => 'Standard page structure (header, footer, navigation) was incomplete or absent.', 2 => 'Standard page structure (header, footer, navigation) was present.']],
            'd_colour'    => ['label' => 'Colour scheme appropriate to subject',     'description' => 'Coherent palette; not just browser defaults',                         'max' => 2, 'feedback' => [0 => 'Colour scheme was generic or not clearly considered.',                             2 => 'Colour scheme was appropriate to the subject.']],
            'd_responsive'=> ['label' => 'Responsive design',                        'description' => '0 = fixed/broken on resize / 2 = partially responsive / 4 = fully responsive', 'max' => 4, 'feedback' => [0 => 'The site did not respond well to different screen sizes.',         2 => 'Some responsive design was present but it was not fully implemented.', 4 => 'The site was fully responsive.']],
            'd_semantic'  => ['label' => 'Semantic HTML & basic accessibility',      'description' => 'Appropriate use of semantic tags; alt text on images',               'max' => 2, 'feedback' => [0 => 'Limited use of semantic HTML or basic accessibility features.',                2 => 'Semantic HTML and basic accessibility features (alt text, etc.) were used.']],
            'd_aria'      => ['label' => 'ARIA attributes used',                     'description' => 'Evidence of role, aria-label, aria-describedby etc.',                'max' => 2, 'feedback' => [0 => '',                                                                                  2 => 'ARIA attributes were used to enhance accessibility.']],
            'd_wcag'      => ['label' => 'WCAG evaluation evidence',                 'description' => 'Student can demonstrate use of WAVE, Lighthouse, or similar',        'max' => 2, 'feedback' => [0 => '',                                                                                  2 => 'Evidence of WCAG evaluation (e.g. WAVE or Lighthouse) was provided.']],
            'd_exceptional'=> ['label'=> 'Exceptional overall design quality',       'description' => 'Marker discretion — stands out from cohort',                         'max' => 2, 'feedback' => [0 => '',                                                                                  2 => 'The overall design quality was exceptional.']],
        ],
    ];
}

function load_deploy_targets(): array {
    $file = rtrim(CONFIGS_DIR, '/\\') . DIRECTORY_SEPARATOR . 'deploy_targets.json';
    if (!file_exists($file)) return [];
    $data = json_decode(file_get_contents($file), true);
    return is_array($data) ? $data : [];
}

function save_deploy_target(string $moduleId, string $path): void {
    $targets = load_deploy_targets();
    $targets[$moduleId] = $path;
    $file = rtrim(CONFIGS_DIR, '/\\') . DIRECTORY_SEPARATOR . 'deploy_targets.json';
    file_put_contents($file, json_encode($targets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
}

function safe_write_config(string $configsDir, string $moduleId, string $content): void {
    if (!preg_match('/^[A-Z][A-Z0-9]{2,11}$/', $moduleId))
        throw new RuntimeException('Invalid module ID');
    $base = realpath($configsDir);
    if ($base === false) throw new RuntimeException('CONFIGS_DIR not found');
    $target = $base . DIRECTORY_SEPARATOR . $moduleId . '.php';
    if (!str_starts_with($target, $base . DIRECTORY_SEPARATOR))
        throw new RuntimeException('Path traversal detected');
    file_put_contents($target, $content, LOCK_EX);
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($target, true);
    }
}

function sync_deployed_config(string $moduleId, string $content): void {
    $targets = load_deploy_targets();
    if (!isset($targets[$moduleId])) return;
    $deployedConfig = rtrim($targets[$moduleId], '/\\') . DIRECTORY_SEPARATOR . 'config.php';
    if (!file_exists($deployedConfig)) return;

    // Preserve the ADMIN_URL / MODULE_URL defines appended by deploy.php
    $existing = file_get_contents($deployedConfig);
    $urlLines = '';
    if (preg_match("/define\s*\(\s*'ADMIN_URL'\s*,[^)]+\)\s*;/",  $existing, $m)) $urlLines .= "\n" . $m[0];
    if (preg_match("/define\s*\(\s*'MODULE_URL'\s*,[^)]+\)\s*;/", $existing, $m)) $urlLines .= "\n" . $m[0];
    if ($urlLines) $urlLines .= "\n";

    file_put_contents($deployedConfig, $content . $urlLines, LOCK_EX);
    if (function_exists('opcache_invalidate')) opcache_invalidate($deployedConfig, true);
}
