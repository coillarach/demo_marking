<?php
/**
 * Module configuration — SET11112P1 Web Design and Development: Part 1 (Design Report & Prototype) (PG)
 *
 * Part 1 of the Dark Sky Reserves coursework (40% of the module):
 *   - Design Prototype (30): scored in the 3-minute demonstration (Figma prototype walkthrough)
 *   - Report (70): marked from the PDF report, split across Technical Research (25),
 *     Critical Analysis (30) and Academic Writing (15)
 *
 * Students may use Figma AI, so the prototype itself is expected to look polished. The demo
 * questions therefore probe how the student controlled the design process, whether they kept
 * the design within what they can build in HTML and CSS, and whether they own the result.
 */

define('MODULE_ID',    'SET11112P1');
define('MODULE_NAME',  'Web Design and Development: Part 1 (Design Report & Prototype)');
define('MODULE_LEVEL', 'PG');
define('PASS_MARK',    50);

define('DATA_DIR',        __DIR__ . '/data/');
define('SUBMISSIONS_DIR', __DIR__ . '/submissions/');

define('MARK_WEIGHTS', [
    'tech'   => 0.3,  // Design Prototype — scored in the demo
    'design' => 0,    // not scored separately
    'demo'   => 0,    // the demo carries no marks of its own
    'report' => 0.7,  // split across REPORT_CRITERIA below
]);

// ── Report criteria — the report mark is split across these (weights sum to MARK_WEIGHTS['report']) ──
define('REPORT_CRITERIA', [
    'research' => [
        'label'       => 'Technical Research',
        'description' => 'Breadth and depth of research into web technologies, quality of sources and understanding of current trends.',
        'weight'      => 0.25,
    ],
    'analysis' => [
        'label'       => 'Critical Analysis',
        'description' => 'Quality of evaluation of the prescribed stack, identification of strengths and weaknesses, and justification of conclusions.',
        'weight'      => 0.3,
    ],
    'writing' => [
        'label'       => 'Academic Writing',
        'description' => 'Report structure, clarity of expression, referencing and professional presentation.',
        'weight'      => 0.15,
    ],
]);

// ── Wording overrides for the marking pages ───────────────────────────────────
define('UI_TEXT', [
    'tech_heading'             => 'Design Prototype Mark',
    'features_heading'         => 'Areas Demonstrated',
    'features_hint'            => '(tick the areas the student could explain)',
    'features_intro'           => 'This sets the mark range for the prototype and loads the Q&A questions below. Figma AI is permitted, so judge how well the student controlled the design process rather than how polished the prototype looks.',
    'setup_label'              => 'Prototype open and ready to present before the demo started',
    'presented_label'          => 'Student walked through their prototype without excessive prompting',
    'authorship_subject'       => 'submitted design work',
    'features_feedback_prefix' => 'Areas demonstrated',
    'report_label'             => 'Design Report',
    'report_note'              => 'The report should be 800–1000 words, excluding references.',
]);

define('TIMER_TOTAL', 180); // 3 minutes
define('TIMER_BREAK', 60);  // alert at 1:00 remaining (2 minutes of walkthrough, then Q&A)

define('MARKERS', [
    '40000001' => ['name' => 'Brian Davison', 'password' => password_hash('changeme1', PASSWORD_DEFAULT)],
    '40000002' => ['name' => 'Marker 2',      'password' => password_hash('changeme2', PASSWORD_DEFAULT)],
    '40000003' => ['name' => 'Marker 3',      'password' => password_hash('changeme3', PASSWORD_DEFAULT)],
    '40000004' => ['name' => 'Marker 4',      'password' => password_hash('changeme4', PASSWORD_DEFAULT)],
    '40000005' => ['name' => 'Marker 5',      'password' => password_hash('changeme5', PASSWORD_DEFAULT)],
]);

// ── Tier ranges (percent of the Design Prototype mark) ────────────────────────
// PG, pass mark 50: any tier with at least one area demonstrated passes. Everything
// below 50 is a single fail tier; the marker chooses within it using the spec's
// descriptors (marginal fail 40–49, clear fail below 40).
define('TIER_RANGES', [
    'advanced2'     => ['label' => 'Advanced (both areas)',        'cls' => 'tier-advanced',     'min' => 77, 'max' => 90, 'mid' => 82],
    'advanced1'     => ['label' => 'Advanced (1 area)',            'cls' => 'tier-advanced',     'min' => 70, 'max' => 76, 'mid' => 73],
    'intermediate3' => ['label' => 'Intermediate (all 3 areas)',   'cls' => 'tier-intermediate', 'min' => 66, 'max' => 69, 'mid' => 67],
    'intermediate2' => ['label' => 'Intermediate (2 areas)',       'cls' => 'tier-intermediate', 'min' => 63, 'max' => 65, 'mid' => 64],
    'intermediate1' => ['label' => 'Intermediate (1 area)',        'cls' => 'tier-intermediate', 'min' => 60, 'max' => 62, 'mid' => 61],
    'basic2'        => ['label' => 'Basic (3–4 areas)',            'cls' => 'tier-basic',        'min' => 55, 'max' => 59, 'mid' => 57],
    'basic1'        => ['label' => 'Basic (1–2 areas)',            'cls' => 'tier-basic',        'min' => 50, 'max' => 54, 'mid' => 52],
    'fail'          => ['label' => 'Fail / unable to explain work', 'cls' => 'tier-fail',        'min' => 0,  'max' => 49, 'mid' => 35],
]);

// ── Tier rules (evaluated in order; first match wins) ─────────────────────────
define('TIER_RULES', [
    ['key' => 'advanced2',     'minAdv' => 2],
    ['key' => 'advanced1',     'minAdv' => 1],
    ['key' => 'intermediate3', 'minInter' => 3],
    ['key' => 'intermediate2', 'minInter' => 2],
    ['key' => 'intermediate1', 'minInter' => 1],
    ['key' => 'basic2',        'minBasic' => 3],
    ['key' => 'basic1',        'minBasic' => 1],
    ['key' => 'fail'],
]);

// ── Feedback strings ──────────────────────────────────────────────────────────
define('FEEDBACK_TIER_LABELS', [
    'advanced2'     => 'Both advanced areas were demonstrated: a critical review of the AI-assisted output and clear ownership of the design.',
    'advanced1'     => 'One advanced area was demonstrated, showing strong critical control of the design process.',
    'intermediate3' => 'All three intermediate areas were demonstrated: translating the design into HTML and CSS, responsive layout and accessibility.',
    'intermediate2' => 'Two intermediate areas were demonstrated.',
    'intermediate1' => 'One intermediate area was demonstrated.',
    'basic2'        => 'Most of the core areas of the design process were explained clearly.',
    'basic1'        => 'Some of the core areas of the design process were explained, but understanding was limited.',
    'fail'          => 'The student was unable to explain how the design was produced or to show control of the design process.',
]);

// ── Knowledge-check questions — not used in Part 1 ────────────────────────────
define('KNOWLEDGE_QUESTIONS', []);
define('KQ_CRITERION_MAP', []);

// ── Feature Q&A question banks ────────────────────────────────────────────────
define('QUESTIONS', [

    // ── Basic: the design process, in the student's own words ─────────────────
    'generation_process' => [
        'label' => 'Design generation process',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'Talk me through how you produced your design in Figma. Did you generate it in one go, or build it up in stages?',
                'well'   => 'Gave a clear, specific account of the process, including whether the design was generated in one pass or built up iteratively.',
                'prompt' => 'Described the process with some prompting; the sequence of steps was unclear at first.',
                'no'     => 'Could not describe how the design was produced.',
            ],
            [
                'q'      => 'What instructions or prompts did you give the AI, and how did they change as you went? (Probe: constraints, pages, colours, audience)',
                'well'   => 'Explained the instructions given and how they were refined, showing deliberate control of the tool.',
                'prompt' => 'Recalled some of the instructions with prompting; little evidence of refinement.',
                'no'     => 'Could not explain what instructions were given to the AI.',
            ],
            [
                'q'      => 'What did the AI produce that you rejected or regenerated, and why?',
                'well'   => 'Gave concrete examples of output that was rejected or regenerated, with sound reasons.',
                'prompt' => 'Identified something that was rejected with prompting; reasons were thin.',
                'no'     => 'Could not give any example of AI output that was rejected or changed.',
            ],
        ],
    ],

    'design_decisions' => [
        'label' => 'Design decisions',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'Pick one element of your design (layout, navigation, colour, typography or imagery) and explain why it is the way it is.',
                'well'   => 'Justified the chosen element clearly, with reasons tied to users and the dark sky subject.',
                'prompt' => 'Gave a justification with prompting; reasoning was partly generic.',
                'no'     => 'Could not justify the chosen design element.',
            ],
            [
                'q'      => 'What did you change by hand after the AI produced its output? Show me one example.',
                'well'   => 'Showed a specific manual change and explained why it was needed.',
                'prompt' => 'Identified a manual change with prompting.',
                'no'     => 'Could not identify any change they had made themselves.',
            ],
            [
                'q'      => 'How do your colour scheme and imagery suit a dark sky and conservation website?',
                'well'   => 'Linked the palette and imagery to the subject and audience convincingly.',
                'prompt' => 'Made the link to the subject with some prompting.',
                'no'     => 'Could not explain how the visual choices relate to the subject.',
            ],
        ],
    ],

    'user_journey' => [
        'label' => 'User journey and page content',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'Walk me through the journey from the site listing to a site detail page. What does the user see and do at each step?',
                'well'   => 'Described the journey clearly and in order, including how the user moves between pages.',
                'prompt' => 'Described the journey with some prompting.',
                'no'     => 'Could not describe how a user moves through the prototype.',
            ],
            [
                'q'      => 'What information does each page need to show? (Probe: site name, country, type, designation year, sky quality, location, observations)',
                'well'   => 'Identified the information each page needs and tied it to the site and observation details in the brief.',
                'prompt' => 'Identified the main content with prompting.',
                'no'     => 'Could not explain what information each page presents.',
            ],
            [
                'q'      => 'Which part of the design shows a visitor which celestial objects can be observed at a site, and how does it do that?',
                'well'   => 'Pointed to the observations content and explained how it is presented.',
                'prompt' => 'Located the observations content with prompting.',
                'no'     => 'Could not point to where observations are shown.',
            ],
        ],
    ],

    'scope_constraint' => [
        'label' => 'Keeping the design within scope',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'How did you decide how much to include in the design? What did you leave out or simplify, and why?',
                'well'   => 'Explained deliberate decisions to limit scope, with clear reasons linked to what can be built.',
                'prompt' => 'Described some simplification with prompting.',
                'no'     => 'Could not explain what was left out or why.',
            ],
            [
                'q'      => 'Is there anything in the prototype you are not confident you could build with HTML and CSS? How did you deal with that?',
                'well'   => 'Candidly identified difficult parts and explained how they were removed, simplified or planned for.',
                'prompt' => 'Identified difficult parts with prompting; plan for them was vague.',
                'no'     => 'Could not say which parts of the design might be hard to build.',
            ],
            [
                'q'      => 'How did you check the design against what you can realistically build in the time available?',
                'well'   => 'Described a sensible check of feasibility against their own skills and the time available.',
                'prompt' => 'Described a feasibility check with prompting.',
                'no'     => 'Had not considered whether the design could be built.',
            ],
        ],
    ],

    // ── Intermediate: from design to HTML and CSS ─────────────────────────────
    'html_css_mapping' => [
        'label' => 'Planning the HTML and CSS',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'      => 'Choose one page. How would you structure it using semantic HTML5 elements? (Probe: header, nav, main, section, article, footer)',
                'well'   => 'Described a sensible semantic structure and explained the choice of elements.',
                'prompt' => 'Described a structure with prompting; element choices were partly generic (e.g. div).',
                'no'     => 'Could not describe how the page would be structured in HTML.',
            ],
            [
                'q'      => 'How would you create the main layout of that page in CSS? (Probe: flexbox, grid, custom properties for the colour scheme)',
                'well'   => 'Explained a workable CSS approach to the layout and shared colours.',
                'prompt' => 'Outlined a CSS approach with prompting.',
                'no'     => 'Could not explain how the layout would be built in CSS.',
            ],
            [
                'q'      => 'Which part of your design would be hardest to build in HTML and CSS, and how would you approach it or simplify it?',
                'well'   => 'Identified the hardest part realistically and gave a credible plan or simplification.',
                'prompt' => 'Identified a difficult part with prompting; plan was vague.',
                'no'     => 'Could not identify any difficult part or a plan for it.',
            ],
        ],
    ],

    'responsive_layout' => [
        'label' => 'Responsive layout',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'      => 'How does your mobile layout differ from the desktop layout, and why?',
                'well'   => 'Explained the differences between layouts and the reasons for them.',
                'prompt' => 'Described the differences with prompting.',
                'no'     => 'Could not explain how the layouts differ.',
            ],
            [
                'q'      => 'How would you make the layout adapt in CSS? (Probe: media queries, flexible units, fluid images, grid auto-fit)',
                'well'   => 'Explained appropriate CSS techniques for adapting the layout across screen sizes.',
                'prompt' => 'Named some responsive techniques with prompting.',
                'no'     => 'Could not explain how a layout is made responsive in CSS.',
            ],
            [
                'q'      => 'What happens to the navigation and the site listing on a narrow screen? Did the AI handle this well, or did you have to change it?',
                'well'   => 'Described the small-screen behaviour and any changes they made to the AI output.',
                'prompt' => 'Described the small-screen behaviour with prompting.',
                'no'     => 'Could not describe how navigation and the listing behave on a small screen.',
            ],
        ],
    ],

    'accessibility' => [
        'label' => 'Accessibility',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'      => 'How did you check that text and background colours have sufficient contrast? (Probe: WCAG ratios, contrast checker)',
                'well'   => 'Described a real contrast check against WCAG and any adjustments made.',
                'prompt' => 'Knew contrast matters but needed prompting on how to check it.',
                'no'     => 'Had not considered or checked colour contrast.',
            ],
            [
                'q'      => 'How would a keyboard-only or screen-reader user move through your pages? (Probe: focus styles, heading structure, landmarks, alt text)',
                'well'   => 'Explained keyboard and screen-reader access using appropriate features (focus, headings, landmarks, alt text).',
                'prompt' => 'Identified some relevant features with prompting.',
                'no'     => 'Could not explain how the site could be used without a mouse or sight.',
            ],
            [
                'q'      => 'Did you find any accessibility problems in the AI-generated design, and how would you test the built site? (Probe: WAVE, Lighthouse)',
                'well'   => 'Gave an example of an accessibility issue in the AI output and a sound plan for testing.',
                'prompt' => 'Named a possible issue or a testing tool with prompting.',
                'no'     => 'Had not looked for accessibility problems and had no plan to test.',
            ],
        ],
    ],

    // ── Advanced: critical control and ownership ──────────────────────────────
    'critical_review_of_ai' => [
        'label' => 'Critical review of AI output',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'      => 'What did the AI get wrong or do badly, and how did you notice?',
                'well'   => 'Identified specific weaknesses in the AI output and explained how they were spotted.',
                'prompt' => 'Identified a weakness with prompting.',
                'no'     => 'Could not identify any weakness in the AI output.',
            ],
            [
                'q'      => 'Was there a suggestion from the AI that you decided not to use? What trade-offs were involved?',
                'well'   => 'Described a rejected suggestion and weighed the trade-offs thoughtfully.',
                'prompt' => 'Described a rejected suggestion with prompting; trade-offs were not explored.',
                'no'     => 'Could not describe any decision to reject the AI\'s suggestions.',
            ],
            [
                'q'      => 'How did you check the output against the coursework requirements and the needs of visitors to dark sky sites?',
                'well'   => 'Described a deliberate check against the brief and user needs.',
                'prompt' => 'Described some checking with prompting.',
                'no'     => 'Had not checked the output against the brief or users\' needs.',
            ],
        ],
    ],

    'ownership_evidence' => [
        'label' => 'Ownership of the design',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'      => 'Without looking at the design, tell me which decisions are yours rather than the AI\'s.',
                'well'   => 'Clearly separated their own decisions from the AI\'s, with specific examples.',
                'prompt' => 'Identified some of their own decisions with prompting.',
                'no'     => 'Could not say which decisions were their own.',
            ],
            [
                'q'      => 'How does the AI-use statement in your report match what you have described today?',
                'well'   => 'Gave an account consistent with the AI-use statement in the report.',
                'prompt' => 'Reconciled the two accounts with prompting; minor inconsistencies.',
                'no'     => 'The account differed materially from the report, or they could not recall the statement.',
            ],
            [
                'q'      => 'If I asked you to change something now (the spacing, a colour, the order of content), what would you change and how would you do it?',
                'well'   => 'Explained confidently how they would make the change and the effect it would have.',
                'prompt' => 'Explained the change with prompting.',
                'no'     => 'Could not explain how to make a change to their own design.',
            ],
        ],
    ],

]);

// ── Design & Usability criteria — not scored separately in Part 1 ─────────────
define('DESIGN_CRITERIA', []);

// ── Demo criteria labels — drives report.php and demo.php summary ─────────────
define('DEMO_CRITERIA_LABELS', [
    'tech' => 'Design Prototype',
]);
