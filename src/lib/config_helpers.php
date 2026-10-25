<?php
/**
 * Helpers for optional, per-module config features.
 * Shared by the deployed marking tool (via src/auth_check.php) and the admin
 * app (via admin/lib.php). Every optional constant read here falls back to the
 * behaviour modules had before the constant existed, so older configs are
 * unaffected.
 */

/**
 * Criteria the report mark is split across, keyed by slug:
 *   key => ['label' => string, 'description' => string, 'weight' => float]
 * Weights are fractions of the whole module (like MARK_WEIGHTS) and sum to
 * MARK_WEIGHTS['report']. Empty array means a single, unsplit report mark.
 */
function report_criteria(): array {
    if (!defined('REPORT_CRITERIA') || !is_array(REPORT_CRITERIA)) return [];
    return REPORT_CRITERIA;
}

/** Maximum mark (out of 100) available for one report criterion. */
function report_criterion_max(array $crit): float {
    return round(floatval($crit['weight'] ?? 0) * 100, 2);
}

/**
 * Wording a module can override in config via the UI_TEXT constant.
 * The defaults are the strings the marking pages used before this existed.
 */
function ui_text_defaults(): array {
    return [
        'tech_heading'             => 'Technical Implementation Mark',
        'features_heading'         => 'Features Present',
        'features_hint'            => '(tick what you can see working)',
        'features_intro'           => 'This determines the enhancement tier and drives the Q&A questions below.',
        'setup_label'              => 'Software running and set up before demo started',
        'presented_label'          => 'Student presented their features without excessive prompting',
        'authorship_subject'       => 'submitted code',
        'features_feedback_prefix' => 'Features implemented',
        'report_label'             => 'Critical Report',
        'report_note'              => 'Max 500 words.',
    ];
}

/** Human-readable names for the ui_text_defaults() keys (used by the admin editor). */
function ui_text_labels(): array {
    return [
        'tech_heading'             => 'Demo mark section heading',
        'features_heading'         => 'Features section heading',
        'features_hint'            => 'Features section hint',
        'features_intro'           => 'Features section introduction',
        'setup_label'              => 'Setup question',
        'presented_label'          => 'Presentation question',
        'authorship_subject'       => 'What authorship refers to',
        'features_feedback_prefix' => 'Feedback prefix for ticked features',
        'report_label'             => 'Name of the report in feedback',
        'report_note'              => 'Note shown above the report mark',
    ];
}

/** Returns the configured wording for $key, or the default if not overridden. */
function ui_text(string $key): string {
    $defaults = ui_text_defaults();
    if (defined('UI_TEXT') && is_array(UI_TEXT)) {
        $v = UI_TEXT[$key] ?? '';
        if (is_string($v) && trim($v) !== '') return $v;
    }
    return $defaults[$key] ?? '';
}
