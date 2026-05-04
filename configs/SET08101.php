<?php
/**
 * Module configuration — SET08101 Web Technologies (UG)
 */

define('MODULE_ID',    'SET08101');
define('MODULE_NAME',  'Web Technologies');
define('MODULE_LEVEL', 'UG');
define('PASS_MARK',    40);

define('DATA_DIR',        __DIR__ . '/data/');
define('SUBMISSIONS_DIR', __DIR__ . '/submissions/');

define('MARK_WEIGHTS', [
    'tech'   => 0.4,
    'design' => 0.2,
    'demo'   => 0.25,
    'report' => 0.15,
]);

define('TIMER_TOTAL', 300); // 5 minutes
define('TIMER_BREAK', 180); // alert at 3:00 remaining

define('MARKERS', [
    '40000001' => ['name' => 'Brian Davison', 'password' => password_hash('changeme1', PASSWORD_DEFAULT)],
    '40000002' => ['name' => 'Marker 2',      'password' => password_hash('changeme2', PASSWORD_DEFAULT)],
    '40000003' => ['name' => 'Marker 3',      'password' => password_hash('changeme3', PASSWORD_DEFAULT)],
    '40000004' => ['name' => 'Marker 4',      'password' => password_hash('changeme4', PASSWORD_DEFAULT)],
    '40000005' => ['name' => 'Marker 5',      'password' => password_hash('changeme5', PASSWORD_DEFAULT)],
]);

// ── Tier ranges ───────────────────────────────────────────────────────────────
define('TIER_RANGES', [
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
]);

// ── Tier rules (evaluated in order; first match wins) ─────────────────────────
define('TIER_RULES', [
    ['key' => 'advanced3',     'minAdv' => 3],
    ['key' => 'advanced2',     'minAdv' => 2],
    ['key' => 'advanced1',     'minAdv' => 1],
    ['key' => 'intermediate2', 'minInter' => 2],
    ['key' => 'intermediate1', 'minInter' => 1],
    ['key' => 'basic3',        'minBasic' => 3],
    ['key' => 'basic2',        'minBasic' => 2],
    ['key' => 'basic1',        'minBasic' => 1],
]);

// ── Feedback strings ──────────────────────────────────────────────────────────
define('FEEDBACK_TIER_LABELS', [
    'advanced3'     => 'All three advanced enhancements were present and functional.',
    'advanced2'     => 'Two advanced enhancements were present and functional.',
    'advanced1'     => 'One advanced enhancement was present and functional.',
    'intermediate2' => 'Both intermediate enhancements were present and functional.',
    'intermediate1' => 'One intermediate enhancement was present and functional.',
    'basic3'        => 'All three basic enhancements were complete and working.',
    'basic2'        => 'Two basic enhancements were complete and working.',
    'basic1'        => 'One basic enhancement was complete and working.',
    'partial'       => 'Some basic enhancements were attempted but not all were fully functional.',
    'fail'          => 'The submission did not meet the basic requirements, or the student was unable to demonstrate a working implementation.',
]);

// ── Knowledge-check questions ─────────────────────────────────────────────────
define('KNOWLEDGE_QUESTIONS', [

    'html' => [
        'label' => 'HTML5',
        'questions' => [
            [
                'q'    => 'What is the difference between a <th> and a <td> cell in a table, and why does it matter?',
                'hint' => '<th> (table header) marks a heading cell — typically bold and centred by default; screen readers announce header cells as headings, providing context for the data cells that follow; <td> (table data) contains the actual data values; using <th> correctly is important for accessibility and semantic meaning.',
            ],
            [
                'q'    => 'The starter code uses <header>, <nav>, <main>, and <footer> instead of <div> elements. What is the advantage?',
                'hint' => 'Semantic elements convey meaning about page structure; help screen readers navigate landmarks; improve SEO; make code more readable.',
            ],
            [
                'q'    => 'A <form> can have method="get" or method="post". What is the difference, and why would you use POST for a contact form?',
                'hint' => 'GET appends data to the URL (visible, length-limited, bookmarkable); POST sends data in the request body (not visible in URL, no length limit, appropriate for form submissions containing user messages or sensitive data).',
            ],
        ],
    ],

    'css' => [
        'label' => 'CSS3',
        'questions' => [
            [
                'q'    => 'The reset stylesheet sets box-sizing: border-box on all elements. What does this change about how element widths are calculated?',
                'hint' => 'By default (content-box) padding and border are added on top of the declared width; border-box includes them within it, making layout arithmetic predictable — a 200px element stays 200px regardless of padding.',
            ],
            [
                'q'    => 'What is the difference between a class selector (.name) and an ID selector (#name)?',
                'hint' => 'IDs must be unique per page; classes can be reused on multiple elements; IDs have higher specificity in the cascade so they override class rules.',
            ],
            [
                'q'    => 'What is a CSS media query, and how would you use one to make a layout responsive?',
                'hint' => '@media (max-width: Xpx) { ... } applies rules only at specific viewport widths; used to change layout at breakpoints — e.g. switching a multi-column grid to a single column on mobile.',
            ],
        ],
    ],

    'php' => [
        'label' => 'PHP',
        'questions' => [
            [
                'q'    => 'Why does the starter code use require_once rather than require or include?',
                'hint' => 'require causes a fatal error if the file is missing (unlike include which just warns); the _once suffix prevents the file being loaded twice — important because including the same file twice would redefine functions and cause a fatal error.',
            ],
            [
                'q'    => 'Why does the starter code wrap every database value in the e() function before outputting it to the page?',
                'hint' => 'e() calls htmlspecialchars(), which converts characters like <, >, &, and " into HTML entities; this prevents Cross-Site Scripting (XSS) — without it, malicious content stored in the database could execute as JavaScript in the visitor\'s browser.',
            ],
            [
                'q'    => 'How do PHP sessions work? What function starts a session, and how do you store and read data in one?',
                'hint' => 'Call session_start() at the top of every page that uses sessions; store data with $_SESSION[\'key\'] = value and read it back the same way; PHP creates a server-side session file and sends the browser a cookie containing the session ID.',
            ],
        ],
    ],

    'js' => [
        'label' => 'JavaScript',
        'questions' => [
            [
                'q'    => 'In a form\'s submit event handler, how do you stop the form from actually submitting to the server when validation fails?',
                'hint' => 'Call event.preventDefault() inside the handler; this cancels the browser\'s default submission behaviour so the page stays in place and error messages can be shown.',
            ],
            [
                'q'    => 'What is the difference between document.getElementById() and document.querySelector()?',
                'hint' => 'getElementById selects a single element by its id attribute; querySelector accepts any CSS selector (class, tag, attribute, compound) and returns the first match — more flexible but slightly slower for simple ID lookups.',
            ],
            [
                'q'    => 'What is the difference between let, const, and var?',
                'hint' => 'var is function-scoped and hoisted to the top of its function; let and const are block-scoped; const cannot be reassigned (though object/array contents can still change); modern JavaScript prefers let and const.',
            ],
        ],
    ],

    'sql' => [
        'label' => 'SQL',
        'questions' => [
            [
                'q'    => 'The query in index.php ends with ORDER BY name. What does this do, and how would you reverse the order?',
                'hint' => 'Sorts the result set alphabetically by the name column in ascending order; appending DESC reverses it to descending.',
            ],
            [
                'q'    => 'The fauna table has a vent_id column described as a foreign key. What is a foreign key and what does it enforce?',
                'hint' => 'A foreign key links each row in one table to a row in another — here every fauna record references the vent it belongs to; the database enforces referential integrity, preventing fauna records that point to non-existent vents.',
            ],
            [
                'q'    => 'What does a JOIN do in SQL, and why would you use one?',
                'hint' => 'A JOIN combines columns from two or more tables into a single result set based on a related column — typically a foreign key; without a JOIN you would need separate queries and manual matching in code; for example, joining fauna to vents on vent_id lets you retrieve the vent name alongside each fauna record in one query.',
            ],
        ],
    ],

]);

// ── Feature Q&A question banks ────────────────────────────────────────────────
define('QUESTIONS', [

    'css' => [
        'label' => 'CSS Stylesheet',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'    => 'Walk me through how you structured your CSS. What approach did you take?',
                'well' => 'Explained CSS structure clearly, demonstrating a good grasp of how stylesheets are organised.',
                'prompt' => 'Could describe CSS structure with some prompting.',
                'no'   => 'Unable to explain the structure of their CSS.',
            ],
            [
                'q'    => 'What selectors have you used, and why? (Probe: classes, IDs, pseudo-selectors)',
                'well' => 'Showed confident knowledge of CSS selectors and their appropriate use.',
                'prompt' => 'Identified some selectors but was uncertain about others.',
                'no'   => 'Could not explain their use of CSS selectors.',
            ],
            [
                'q'    => 'How did you implement responsive design? (Probe: flexbox, grid, media queries)',
                'well' => 'Explained their responsive design approach clearly, with good understanding of the underlying CSS mechanisms.',
                'prompt' => 'Described responsive techniques with some prompting.',
                'no'   => 'Could not explain how responsive design was achieved.',
            ],
        ],
    ],

    'map_page' => [
        'label' => 'Static Map Page',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'    => 'Where did the map image come from, and how did you handle any copyright/attribution requirements?',
                'well' => 'Clearly explained the image source and handled attribution correctly.',
                'prompt' => 'Addressed attribution with some prompting.',
                'no'   => 'Could not explain the image source or attribution.',
            ],
            [
                'q'    => 'How is this page linked from the rest of the site?',
                'well' => 'Explained site navigation confidently.',
                'prompt' => 'Could describe navigation with prompting.',
                'no'   => 'Could not explain how the page is integrated into the site.',
            ],
        ],
    ],

    'contact_form' => [
        'label' => 'Contact Form (JS + PHP)',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'    => 'Can you demonstrate the form validation? Walk me through the JavaScript that does this.',
                'well' => 'Demonstrated form validation confidently and explained the JavaScript clearly.',
                'prompt' => 'Demonstrated validation but needed prompting to explain the code.',
                'no'   => 'Could not explain the JavaScript validation code.',
            ],
            [
                'q'    => 'What does the PHP do when the form is submitted? (Probe: $_POST, sanitisation, response)',
                'well' => 'Explained the PHP form handling clearly, including use of superglobals and output handling.',
                'prompt' => 'Described PHP processing with some prompting.',
                'no'   => 'Could not explain the PHP form processing.',
            ],
            [
                'q'    => 'What feedback does the user receive if validation fails? If the form is valid?',
                'well' => 'Explained user feedback clearly for both valid and invalid states.',
                'prompt' => 'Described feedback with some prompting.',
                'no'   => 'Could not explain what user feedback is provided.',
            ],
        ],
    ],

    'fauna_display' => [
        'label' => 'Fauna on Vent Detail Page',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'    => 'Walk me through the SQL query that fetches the fauna for this vent.',
                'well' => 'Explained the fauna query confidently, demonstrating understanding of the foreign key relationship.',
                'prompt' => 'Could explain the query with some prompting.',
                'no'   => 'Could not explain the SQL query used to fetch fauna.',
            ],
            [
                'q'    => 'What is the relationship between the vents and fauna tables? How does your code use that relationship?',
                'well' => 'Demonstrated clear understanding of the foreign key relationship and how it is used in the query.',
                'prompt' => 'Described the relationship with prompting.',
                'no'   => 'Could not explain the relationship between the two tables.',
            ],
            [
                'q'    => 'How do you loop through and display the results?',
                'well' => 'Explained the PHP loop and output clearly.',
                'prompt' => 'Described the display logic with some prompting.',
                'no'   => 'Could not explain how the results are displayed.',
            ],
        ],
    ],

    'search_filter' => [
        'label' => 'Search / Filter',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'    => 'Walk me through the SQL that powers the search. How does filtering work?',
                'well' => 'Explained the search SQL clearly, including WHERE clause / LIKE usage.',
                'prompt' => 'Described the SQL with prompting.',
                'no'   => 'Could not explain the SQL behind the search.',
            ],
            [
                'q'    => 'How do you prevent SQL injection in the search? (Probe: prepared statements, parameterised queries)',
                'well' => 'Demonstrated clear understanding of SQL injection risks and their mitigation.',
                'prompt' => 'Identified the approach with prompting.',
                'no'   => 'Could not explain how SQL injection is prevented.',
            ],
        ],
    ],

    'login' => [
        'label' => 'Login / Session Management',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'How are passwords stored? (Probe: password_hash, bcrypt)',
                'well' => 'Demonstrated clear understanding of secure password storage using hashing.',
                'prompt' => 'Identified the approach with some prompting.',
                'no'   => 'Could not explain how passwords are stored securely.',
            ],
            [
                'q'    => 'How do sessions work in your implementation? (Probe: session_start, $_SESSION)',
                'well' => 'Explained session management clearly and correctly.',
                'prompt' => 'Described sessions with prompting.',
                'no'   => 'Could not explain how sessions are used.',
            ],
            [
                'q'    => 'How do you protect pages that require login?',
                'well' => 'Explained access control clearly — checking session state and redirecting unauthorised users.',
                'prompt' => 'Described access control with prompting.',
                'no'   => 'Could not explain how restricted pages are protected.',
            ],
        ],
    ],

    'crud' => [
        'label' => 'CRUD Functionality',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'Walk me through the PHP that handles a create or update operation.',
                'well' => 'Explained the CRUD PHP confidently, including prepared statements and POST handling.',
                'prompt' => 'Described the code with prompting.',
                'no'   => 'Could not explain the PHP behind the CRUD operation.',
            ],
            [
                'q'    => 'How do you prevent unauthorised access to the edit/delete functionality?',
                'well' => 'Demonstrated clear understanding of access control for CRUD operations.',
                'prompt' => 'Identified the approach with prompting.',
                'no'   => 'Could not explain how CRUD operations are protected.',
            ],
            [
                'q'    => 'How do you prevent SQL injection in your insert/update queries?',
                'well' => 'Explained use of prepared statements clearly.',
                'prompt' => 'Identified the approach with prompting.',
                'no'   => 'Could not explain SQL injection prevention in write operations.',
            ],
        ],
    ],

    'interactive_map' => [
        'label' => 'Interactive Map',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'What library or API did you use for the map?',
                'well' => 'Identified the library/API and explained why they chose it.',
                'prompt' => 'Named the library with prompting.',
                'no'   => 'Could not identify the library or API used.',
            ],
            [
                'q'    => 'How does the map know where to place the vent markers? (Probe: coordinates from DB or hardcoded)',
                'well' => 'Explained how vent coordinates are supplied to the map clearly.',
                'prompt' => 'Described coordinate handling with prompting.',
                'no'   => 'Could not explain how map markers are positioned.',
            ],
            [
                'q'    => 'How does the map integrate with the rest of the PHP/database layer?',
                'well' => 'Explained the integration between the map and server-side data clearly.',
                'prompt' => 'Described integration with prompting.',
                'no'   => 'Could not explain how the map connects to the data layer.',
            ],
        ],
    ],

]);

// ── Design & Usability criteria ──────────────────────────────────────────────
define('DESIGN_CRITERIA', [
    'd_css' => [
        'label' => 'CSS stylesheet applied and effective',
        'description' => '0 = absent / 2 = minimal but present / 4 = effective and considered',
        'max' => 4,
        'feedback' => [
            0 => 'No effective CSS stylesheet was observed.',
            2 => 'A basic CSS stylesheet was present but limited in scope.',
            4 => 'CSS stylesheet was effective and well-considered.',
        ],
    ],
    'd_structure' => [
        'label' => 'Standard page structure present',
        'description' => 'Visible header, footer, and navigation',
        'max' => 2,
        'feedback' => [
            0 => 'Standard page structure (header, footer, navigation) was incomplete or absent.',
            2 => 'Standard page structure (header, footer, navigation) was present.',
        ],
    ],
    'd_colour' => [
        'label' => 'Colour scheme appropriate to subject',
        'description' => 'Coherent palette; not just browser defaults',
        'max' => 2,
        'feedback' => [
            0 => 'Colour scheme was generic or not clearly considered.',
            2 => 'Colour scheme was appropriate to the subject.',
        ],
    ],
    'd_responsive' => [
        'label' => 'Responsive design',
        'description' => '0 = fixed/broken on resize / 2 = partially responsive / 4 = fully responsive',
        'max' => 4,
        'feedback' => [
            0 => 'The site did not respond well to different screen sizes.',
            2 => 'Some responsive design was present but it was not fully implemented.',
            4 => 'The site was fully responsive.',
        ],
    ],
    'd_semantic' => [
        'label' => 'Semantic HTML & basic accessibility',
        'description' => 'Appropriate use of semantic tags; alt text on images',
        'max' => 2,
        'feedback' => [
            0 => 'Limited use of semantic HTML or basic accessibility features.',
            2 => 'Semantic HTML and basic accessibility features (alt text, etc.) were used.',
        ],
    ],
    'd_aria' => [
        'label' => 'ARIA attributes used',
        'description' => 'Evidence of role, aria-label, aria-describedby etc.',
        'max' => 2,
        'feedback' => [
            0 => '',
            2 => 'ARIA attributes were used to enhance accessibility.',
        ],
    ],
    'd_wcag' => [
        'label' => 'WCAG evaluation evidence',
        'description' => 'Student can demonstrate use of WAVE, Lighthouse, or similar',
        'max' => 2,
        'feedback' => [
            0 => '',
            2 => 'Evidence of WCAG evaluation (e.g. WAVE or Lighthouse) was provided.',
        ],
    ],
    'd_exceptional' => [
        'label' => 'Exceptional overall design quality',
        'description' => 'Marker discretion — stands out from cohort',
        'max' => 2,
        'feedback' => [
            0 => '',
            2 => 'The overall design quality was exceptional.',
        ],
    ],
]);

// ── Demo criteria labels — drives report.php and demo.php summary ─────────────
define('DEMO_CRITERIA_LABELS', [
    'tech'   => 'Technical Implementation',
    'design' => 'Design & Usability',
    'demo'   => 'Demonstration Quality',
]);

