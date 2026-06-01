<?php
/**
 * Module configuration — SET11112 Web Design and Development (PG)
 */

define('MODULE_ID',    'SET11112');
define('MODULE_NAME',  'Web Design and Development');
define('MODULE_LEVEL', 'PG');
define('PASS_MARK',    50);

define('DATA_DIR',        __DIR__ . '/data/');
define('SUBMISSIONS_DIR', __DIR__ . '/submissions/');

define('MARK_WEIGHTS', [
    'tech'   => 0.4,
    'design' => 0.2,
    'demo'   => 0.25,
    'report' => 0.15,
]);

define('TIMER_TOTAL', 600); // 10 minutes
define('TIMER_BREAK', 480); // alert at 8:00 remaining

define('MARKERS', [
    '40000001' => ['name' => 'Brian Davison', 'password' => password_hash('changeme1', PASSWORD_DEFAULT)],
    '40000002' => ['name' => 'Marker 2',      'password' => password_hash('changeme2', PASSWORD_DEFAULT)],
    '40000003' => ['name' => 'Marker 3',      'password' => password_hash('changeme3', PASSWORD_DEFAULT)],
    '40000004' => ['name' => 'Marker 4',      'password' => password_hash('changeme4', PASSWORD_DEFAULT)],
    '40000005' => ['name' => 'Marker 5',      'password' => password_hash('changeme5', PASSWORD_DEFAULT)],
]);

// ── Tier ranges ───────────────────────────────────────────────────────────────
define('TIER_RANGES', [
    'advanced3'     => ['label' => 'Advanced (3+ features)',    'cls' => 'tier-advanced',     'min' => 83, 'max' => 90, 'mid' => 87],
    'advanced2'     => ['label' => 'Advanced (2 features)',     'cls' => 'tier-advanced',     'min' => 77, 'max' => 82, 'mid' => 79],
    'advanced1'     => ['label' => 'Advanced (1 feature)',      'cls' => 'tier-advanced',     'min' => 70, 'max' => 76, 'mid' => 73],
    'intermediate4' => ['label' => 'Intermediate (4 features)', 'cls' => 'tier-intermediate', 'min' => 68, 'max' => 69, 'mid' => 69],
    'intermediate3' => ['label' => 'Intermediate (3 features)', 'cls' => 'tier-intermediate', 'min' => 66, 'max' => 67, 'mid' => 67],
    'intermediate2' => ['label' => 'Intermediate (2 features)', 'cls' => 'tier-intermediate', 'min' => 63, 'max' => 65, 'mid' => 64],
    'intermediate1' => ['label' => 'Intermediate (1 feature)',  'cls' => 'tier-intermediate', 'min' => 60, 'max' => 62, 'mid' => 61],
    'basic4'        => ['label' => 'Basic (all 4)',             'cls' => 'tier-basic',        'min' => 55, 'max' => 59, 'mid' => 57],
    'basic3'        => ['label' => 'Basic (3 features)',        'cls' => 'tier-basic',        'min' => 50, 'max' => 54, 'mid' => 52],
    'basic2'        => ['label' => 'Basic (2 features)',        'cls' => 'tier-basic',        'min' => 45, 'max' => 49, 'mid' => 47],
    'basic1'        => ['label' => 'Basic (1 feature)',         'cls' => 'tier-basic',        'min' => 40, 'max' => 44, 'mid' => 42],
    'fail'          => ['label' => 'Fail / non-functional',     'cls' => 'tier-fail',         'min' => 0,  'max' => 39, 'mid' => 20],
]);

// ── Tier rules (evaluated in order; first match wins) ─────────────────────────
define('TIER_RULES', [
    ['key' => 'advanced3',     'minAdv' => 3],
    ['key' => 'advanced2',     'minAdv' => 2],
    ['key' => 'advanced1',     'minAdv' => 1],
    ['key' => 'intermediate4', 'minInter' => 4],
    ['key' => 'intermediate3', 'minInter' => 3],
    ['key' => 'intermediate2', 'minInter' => 2],
    ['key' => 'intermediate1', 'minInter' => 1],
    ['key' => 'basic4',        'minBasic' => 4],
    ['key' => 'basic3',        'minBasic' => 3],
    ['key' => 'basic2',        'minBasic' => 2],
    ['key' => 'basic1',        'minBasic' => 1],
    ['key' => 'fail'],
]);

// ── Feedback strings ──────────────────────────────────────────────────────────
define('FEEDBACK_TIER_LABELS', [
    'advanced3'     => 'Three or more advanced enhancements were present and functional.',
    'advanced2'     => 'Two advanced enhancements were present and functional.',
    'advanced1'     => 'One advanced enhancement was present and functional.',
    'intermediate4' => 'All four intermediate enhancements were present and functional.',
    'intermediate3' => 'Three intermediate enhancements were present and functional.',
    'intermediate2' => 'Two intermediate enhancements were present and functional.',
    'intermediate1' => 'One intermediate enhancement was present and functional.',
    'basic4'        => 'All four basic enhancements were complete and working.',
    'basic3'        => 'Three basic enhancements were complete and working.',
    'basic2'        => 'Two basic enhancements were complete and working.',
    'basic1'        => 'One basic enhancement was complete and working.',
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
                'q'    => 'The species listing in index.php is ordered by common_name. How would you change the query to reverse this order?',
                'hint' => 'Append DESC after the column name in the ORDER BY clause: ORDER BY common_name DESC. ASC is the default direction.',
            ],
            [
                'q'    => 'The observations table has a gbif_species_key column that links back to the species table. What is this called, and what does it enforce?',
                'hint' => 'It is a foreign key; it links each observation row to a species record and the database enforces referential integrity — you cannot have an observation that points to a species that does not exist.',
            ],
            [
                'q'    => 'What does a JOIN do in SQL, and why would you use one when displaying observations on a species detail page?',
                'hint' => 'A JOIN combines columns from two tables into one result set based on a matching column; here joining observations to species on gbif_species_key lets you retrieve the species name alongside each observation record without a second query.',
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

    'static_page' => [
        'label' => 'Static HTML Page',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'    => 'What is this page about, and how have you linked it from the rest of the site?',
                'well' => 'Clearly described the page content and explained how it is integrated into the site navigation.',
                'prompt' => 'Described the page with some prompting; navigation link was identified.',
                'no'   => 'Could not explain the purpose of the page or how it connects to the rest of the site.',
            ],
            [
                'q'    => 'Point to two or three semantic HTML elements you used on this page and explain why you chose them.',
                'well' => 'Identified multiple semantic elements (e.g. <article>, <section>, <aside>) and gave clear reasons for using them.',
                'prompt' => 'Identified at least one semantic element with some prompting.',
                'no'   => 'Could not identify or explain any semantic HTML elements on the page.',
            ],
            [
                'q'    => 'How did you ensure the page styling is consistent with the rest of the site?',
                'well' => 'Explained clearly how shared CSS, includes, or class naming achieves visual consistency.',
                'prompt' => 'Described consistency with some prompting.',
                'no'   => 'Could not explain how visual consistency across pages was achieved.',
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

    'image_urls' => [
        'label' => 'Image URLs & Thumbnails',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'    => 'Where did you get the image URLs from, and how did you handle copyright or attribution requirements? (Probe: Wikimedia Commons)',
                'well' => 'Identified the image source clearly and demonstrated appropriate handling of licensing/attribution.',
                'prompt' => 'Identified the source with prompting; attribution handling was partial.',
                'no'   => 'Could not explain the image source or attribution approach.',
            ],
            [
                'q'    => 'How do you display the thumbnail in PHP, and what happens if image_url is NULL for a species?',
                'well' => 'Explained the PHP output and NULL-handling logic clearly — e.g. placeholder fallback or conditional display.',
                'prompt' => 'Described the display with prompting; NULL handling was identified.',
                'no'   => 'Could not explain how the image is displayed or how NULL values are handled.',
            ],
            [
                'q'    => 'How have you styled the thumbnails with CSS? (Probe: sizing, object-fit, consistent layout)',
                'well' => 'Explained the CSS properties used to size and position thumbnails consistently.',
                'prompt' => 'Described the CSS approach with some prompting.',
                'no'   => 'Could not explain how the thumbnails are styled.',
            ],
        ],
    ],

    'observations' => [
        'label' => 'Observations on Species Page',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'    => 'Walk me through the SQL query that fetches observations for this species.',
                'well' => 'Explained the query confidently, including the WHERE clause using gbif_species_key and the use of a prepared statement.',
                'prompt' => 'Could explain the query with some prompting.',
                'no'   => 'Could not explain the SQL query used to fetch observations.',
            ],
            [
                'q'    => 'What is the relationship between the species and observations tables? How does your code use that relationship?',
                'well' => 'Demonstrated clear understanding of the foreign key relationship and how the species key links the two tables.',
                'prompt' => 'Described the relationship with prompting.',
                'no'   => 'Could not explain the relationship between the two tables.',
            ],
            [
                'q'    => 'How do you handle NULL values in locality or observation_date, and how is the observations table structured in your HTML?',
                'well' => 'Explained NULL handling clearly (e.g. ternary/null coalescing) and described the HTML table structure.',
                'prompt' => 'Described NULL handling or layout with some prompting.',
                'no'   => 'Could not explain how NULL values or the HTML structure are managed.',
            ],
        ],
    ],

    'image_modal' => [
        'label' => 'Image Modal / Popup',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'    => 'How does clicking an image trigger the modal? Walk me through the JavaScript.',
                'well' => 'Explained the event listener, how the modal element is shown, and how the image src is passed to it.',
                'prompt' => 'Described the trigger mechanism with some prompting.',
                'no'   => 'Could not explain the JavaScript behind the modal trigger.',
            ],
            [
                'q'    => 'How is the modal styled? (Probe: fixed positioning, z-index, overlay background)',
                'well' => 'Explained the CSS clearly — fixed position, z-index stacking, semi-transparent overlay.',
                'prompt' => 'Identified the main CSS properties with prompting.',
                'no'   => 'Could not explain how the modal is styled.',
            ],
            [
                'q'    => 'How do you close the modal? (Probe: click outside, Escape key, close button)',
                'well' => 'Described one or more close mechanisms and the JavaScript/CSS that implements them.',
                'prompt' => 'Identified a close mechanism with prompting.',
                'no'   => 'Could not explain how the modal is closed.',
            ],
        ],
    ],

    'search_filter' => [
        'label' => 'Search / Filter',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'    => 'Walk me through the SQL that powers the search or filter. How does it work?',
                'well' => 'Explained the search SQL clearly, including WHERE clause, LIKE or exact match, and how user input reaches the query.',
                'prompt' => 'Described the SQL with prompting.',
                'no'   => 'Could not explain the SQL behind the search or filter.',
            ],
            [
                'q'    => 'How do you prevent SQL injection in the search? (Probe: prepared statements, parameterised queries)',
                'well' => 'Demonstrated clear understanding of SQL injection risks and their mitigation using prepared statements.',
                'prompt' => 'Identified the approach with prompting.',
                'no'   => 'Could not explain how SQL injection is prevented.',
            ],
            [
                'q'    => 'How does the search form preserve its state on the page — does the filter persist after submission, and how?',
                'well' => 'Explained how the selected filter value is read back from $_GET and re-applied to the form control.',
                'prompt' => 'Described state persistence with prompting.',
                'no'   => 'Could not explain how filter state is preserved across requests.',
            ],
        ],
    ],

    'sort' => [
        'label' => 'Sort Functionality',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'    => 'How does sorting work — is it client-side JavaScript or server-side SQL? Why did you choose that approach?',
                'well' => 'Clearly explained the chosen approach and gave a reasoned justification.',
                'prompt' => 'Identified the approach with prompting; justification was partial.',
                'no'   => 'Could not explain how sorting is implemented or why.',
            ],
            [
                'q'    => 'If server-side: how do you safely pass the sort column into the SQL query without enabling injection? If client-side: walk me through the JavaScript comparison function.',
                'well' => 'Explained the safety mechanism (allowlist for server-side, or the comparator logic for client-side) clearly.',
                'prompt' => 'Described the approach with prompting.',
                'no'   => 'Could not explain injection prevention (server) or the comparison logic (client).',
            ],
            [
                'q'    => 'How does the page indicate to the user which column is currently sorted and in which direction?',
                'well' => 'Explained the UI feedback mechanism — e.g. arrow icons, link state, or highlighted column header.',
                'prompt' => 'Described the UI feedback with prompting.',
                'no'   => 'Could not explain how sort state is communicated to the user.',
            ],
        ],
    ],

    'login' => [
        'label' => 'User Authentication',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'How are passwords stored? (Probe: password_hash, bcrypt)',
                'well' => 'Demonstrated clear understanding of secure password storage using hashing — password_hash() with PASSWORD_DEFAULT.',
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
                'q'    => 'How do you protect pages that require login? (Probe: session check, redirect)',
                'well' => 'Explained access control clearly — checking session state and redirecting unauthorised users.',
                'prompt' => 'Described access control with prompting.',
                'no'   => 'Could not explain how restricted pages are protected.',
            ],
        ],
    ],

    'crud' => [
        'label' => 'CRUD for Observations',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'Walk me through the PHP that handles creating or editing an observation record.',
                'well' => 'Explained the CRUD PHP confidently — reading $_POST, using a prepared statement for INSERT/UPDATE, and redirecting after success.',
                'prompt' => 'Described the code with prompting.',
                'no'   => 'Could not explain the PHP behind the create or update operation.',
            ],
            [
                'q'    => 'How do you prevent unauthorised access to the edit and delete functionality?',
                'well' => 'Demonstrated clear understanding of access control for write operations — session check, login requirement, or ownership verification.',
                'prompt' => 'Identified the approach with prompting.',
                'no'   => 'Could not explain how CRUD operations are protected from unauthorised use.',
            ],
            [
                'q'    => 'How do you prevent SQL injection in your insert or update queries?',
                'well' => 'Explained use of prepared statements with parameter binding clearly.',
                'prompt' => 'Identified the approach with prompting.',
                'no'   => 'Could not explain SQL injection prevention in write operations.',
            ],
        ],
    ],

    'interactive_map' => [
        'label' => 'Interactive Observation Map',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'What library or API did you use for the map, and why?',
                'well' => 'Identified the library/API (e.g. Leaflet, Google Maps) and gave a clear reason for choosing it.',
                'prompt' => 'Named the library with prompting.',
                'no'   => 'Could not identify the library or API used.',
            ],
            [
                'q'    => 'How does the map know where to place the observation markers? (Probe: latitude/longitude from the database)',
                'well' => 'Explained clearly how observation coordinates (latitude, longitude) are read from the database and passed to the map.',
                'prompt' => 'Described coordinate handling with prompting.',
                'no'   => 'Could not explain how map markers are positioned.',
            ],
            [
                'q'    => 'How does the map integrate with the PHP and database layer?',
                'well' => 'Explained the integration — e.g. PHP outputs coordinates as JSON/JS, or observations are fetched via AJAX.',
                'prompt' => 'Described integration with prompting.',
                'no'   => 'Could not explain how the map connects to the data layer.',
            ],
        ],
    ],

    'date_filter' => [
        'label' => 'Date Range Filter',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'What HTML input type did you use for the date fields, and how do you read them in PHP?',
                'well' => 'Identified <input type="date"> and explained how $_GET or $_POST captures the values for use in the query.',
                'prompt' => 'Identified the input type and retrieval method with prompting.',
                'no'   => 'Could not explain the HTML or PHP side of date input handling.',
            ],
            [
                'q'    => 'Walk me through how the date range is applied in the SQL query.',
                'well' => 'Explained the BETWEEN or >= / <= SQL clause and the use of a prepared statement clearly.',
                'prompt' => 'Described the SQL approach with prompting.',
                'no'   => 'Could not explain how the date filter is applied in SQL.',
            ],
            [
                'q'    => 'How do you validate or sanitise the date inputs before they reach the database?',
                'well' => 'Explained validation — e.g. checking format with DateTime::createFromFormat, or using prepared statements to prevent injection.',
                'prompt' => 'Identified the validation approach with prompting.',
                'no'   => 'Could not explain how date inputs are validated or sanitised.',
            ],
        ],
    ],

    'charts' => [
        'label' => 'Charts / Visualisation',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'What library did you use for the charts, and how did you include it in the page?',
                'well' => 'Named the library (e.g. Chart.js, D3) and explained how it was included — CDN link, npm, or local copy.',
                'prompt' => 'Named the library with prompting; inclusion method was identified.',
                'no'   => 'Could not identify the charting library or how it was included.',
            ],
            [
                'q'    => 'What data does the chart visualise, and how is that data extracted from the database in PHP?',
                'well' => 'Described the SQL query clearly and explained what the chart represents.',
                'prompt' => 'Described the data and query with prompting.',
                'no'   => 'Could not explain what the chart shows or how the data is fetched.',
            ],
            [
                'q'    => 'How is the data passed from PHP to the JavaScript that renders the chart?',
                'well' => 'Explained the mechanism clearly — e.g. json_encode() output embedded in a <script> tag, or a separate AJAX endpoint.',
                'prompt' => 'Described the data handoff with prompting.',
                'no'   => 'Could not explain how PHP data reaches the JavaScript chart code.',
            ],
        ],
    ],

    'pagination' => [
        'label' => 'Pagination',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'How do you calculate the total number of pages, and how is the current page passed between requests?',
                'well' => 'Explained the COUNT(*) query, dividing by page size with ceil(), and reading the page number from $_GET.',
                'prompt' => 'Described the calculation and page passing with prompting.',
                'no'   => 'Could not explain how total pages are calculated or how the current page is tracked.',
            ],
            [
                'q'    => 'Walk me through how LIMIT and OFFSET are used in your SQL query.',
                'well' => 'Explained LIMIT (items per page) and OFFSET (calculated from page number) clearly, using prepared statements.',
                'prompt' => 'Described LIMIT/OFFSET with prompting.',
                'no'   => 'Could not explain the LIMIT/OFFSET approach.',
            ],
            [
                'q'    => 'How are the page navigation links generated in PHP or HTML?',
                'well' => 'Explained the loop or logic that generates Previous/Next or numbered links with the correct page parameter.',
                'prompt' => 'Described link generation with prompting.',
                'no'   => 'Could not explain how the pagination navigation is built.',
            ],
        ],
    ],

    'image_upload' => [
        'label' => 'Image Upload',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'    => 'What HTML and PHP are involved in receiving an uploaded file?',
                'well' => 'Explained the enctype="multipart/form-data" form attribute, <input type="file">, and reading $_FILES in PHP.',
                'prompt' => 'Identified the key elements with prompting.',
                'no'   => 'Could not explain the HTML or PHP required for file uploads.',
            ],
            [
                'q'    => 'How do you validate the uploaded file? (Probe: MIME type check, size limit, extension allowlist)',
                'well' => 'Described validation clearly — checking MIME type, file size, and restricting to safe extensions.',
                'prompt' => 'Identified at least one validation step with prompting.',
                'no'   => 'Could not explain any file validation steps.',
            ],
            [
                'q'    => 'Where is the file stored on the server, and how do you update image_url in the database after upload?',
                'well' => 'Explained move_uploaded_file(), the target directory, and the UPDATE query that saves the path.',
                'prompt' => 'Described the storage and database update with prompting.',
                'no'   => 'Could not explain where uploads are stored or how the database is updated.',
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

