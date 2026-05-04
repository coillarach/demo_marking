<?php
/**
 * Module configuration — SET09102 Software Engineering (UG)
 */

define('MODULE_ID',    'SET09102');
define('MODULE_NAME',  'Software Engineering');
define('MODULE_LEVEL', 'UG');
define('PASS_MARK',    40);

define('DATA_DIR',        __DIR__ . '/data/');
define('SUBMISSIONS_DIR', __DIR__ . '/submissions/');

define('MARK_WEIGHTS', [
    'tech'          => 0.45,  // Understanding of Code — tier-driven §6
    'communication' => 0.10,  // Communication & Presentation — §7 band selector
    'testing'       => 0.15,  // Testing & Quality — KQ-driven
    'process'       => 0.10,  // Development Process — KQ-driven
    'ai_usage'      => 0.20,  // AI Tool Usage — KQ-driven
]);

define('REPORT_WEIGHT', 0.20); // Report weight kept separate — not part of presentation mark

define('TIMER_TOTAL', 1080); // 18 minutes
define('TIMER_BREAK',  480); // alert at 8 minutes remaining

define('MARKERS', [
    '40000001' => ['name' => 'Brian Davison', 'password' => password_hash('changeme1', PASSWORD_DEFAULT)],
    '40000002' => ['name' => 'Marker 2',      'password' => password_hash('changeme2', PASSWORD_DEFAULT)],
    '40000003' => ['name' => 'Marker 3',      'password' => password_hash('changeme3', PASSWORD_DEFAULT)],
    '40000004' => ['name' => 'Marker 4',      'password' => password_hash('changeme4', PASSWORD_DEFAULT)],
    '40000005' => ['name' => 'Marker 5',      'password' => password_hash('changeme5', PASSWORD_DEFAULT)],
]);

// ── DESIGN_CRITERIA — empty; suppresses scored grid in §3 ─────────────────────
define('DESIGN_CRITERIA', []);

// ── Demo criteria labels — drives report.php and demo.php summary ─────────────
define('DEMO_CRITERIA_LABELS', [
    'tech'          => 'Understanding of Code',
    'testing'       => 'Testing & Quality',
    'process'       => 'Development Process',
    'ai_usage'      => 'AI Tool Usage',
    'communication' => 'Communication & Presentation',
]);

// ── Tier ranges — constrain §6 Understanding of Code mark ─────────────────────
define('TIER_RANGES', [
    'advanced3'     => ['label' => 'Advanced (3+ features)',      'cls' => 'tier-advanced',     'min' => 80, 'max' => 100, 'mid' => 90],
    'advanced2'     => ['label' => 'Advanced (2 features)',       'cls' => 'tier-advanced',     'min' => 75, 'max' => 79,  'mid' => 77],
    'advanced1'     => ['label' => 'Advanced (1 feature)',        'cls' => 'tier-advanced',     'min' => 70, 'max' => 74,  'mid' => 72],
    'intermediate2' => ['label' => 'Intermediate (2+ features)',  'cls' => 'tier-intermediate', 'min' => 65, 'max' => 69,  'mid' => 67],
    'intermediate1' => ['label' => 'Intermediate (1 feature)',    'cls' => 'tier-intermediate', 'min' => 60, 'max' => 64,  'mid' => 62],
    'basic5'        => ['label' => 'Core (all 5 features)',       'cls' => 'tier-basic',        'min' => 55, 'max' => 59,  'mid' => 57],
    'basic4'        => ['label' => 'Core (4 features)',           'cls' => 'tier-basic',        'min' => 50, 'max' => 54,  'mid' => 52],
    'basic3'        => ['label' => 'Core (3 features)',           'cls' => 'tier-basic',        'min' => 45, 'max' => 49,  'mid' => 47],
    'basic2'        => ['label' => 'Core (2 features)',           'cls' => 'tier-basic',        'min' => 40, 'max' => 44,  'mid' => 42],
    'basic1'        => ['label' => 'Core (1 feature)',            'cls' => 'tier-basic',        'min' => 35, 'max' => 39,  'mid' => 37],
    'fail'          => ['label' => 'Fail / non-functional',       'cls' => 'tier-fail',         'min' => 0,  'max' => 34,  'mid' => 17],
]);

// ── Tier rules (evaluated in order; first match wins) ─────────────────────────
define('TIER_RULES', [
    ['key' => 'advanced3',     'minAdv'   => 3],
    ['key' => 'advanced2',     'minAdv'   => 2],
    ['key' => 'advanced1',     'minAdv'   => 1],
    ['key' => 'intermediate2', 'minInter' => 2],
    ['key' => 'intermediate1', 'minInter' => 1],
    ['key' => 'basic5',        'minBasic' => 5],
    ['key' => 'basic4',        'minBasic' => 4],
    ['key' => 'basic3',        'minBasic' => 3],
    ['key' => 'basic2',        'minBasic' => 2],
    ['key' => 'basic1',        'minBasic' => 1],
]);

// ── Feedback strings ──────────────────────────────────────────────────────────
define('FEEDBACK_TIER_LABELS', [
    'advanced3'     => 'Three or more advanced features were present and functional.',
    'advanced2'     => 'Two advanced features were present and functional.',
    'advanced1'     => 'One advanced feature was present and functional.',
    'intermediate2' => 'Two or more intermediate features were present and functional.',
    'intermediate1' => 'One intermediate feature was present and functional.',
    'basic5'        => 'All five core features were present and functional.',
    'basic4'        => 'Four core features were present and functional.',
    'basic3'        => 'Three core features were present and functional.',
    'basic2'        => 'Two core features were present and functional.',
    'basic1'        => 'One core feature was present and functional.',
    'fail'          => 'The submission did not meet the basic requirements, or the student was unable to demonstrate a working implementation.',
]);

// ── KQ_CRITERION_MAP — links each KQ topic to the criterion it penalises ──────
// 'tech' is penalised by Q&A answers AND code_quality + architecture KQ topics.
// Other criteria start at 100 and are penalised only by their mapped KQ topics.
// 'communication' has no KQ mapping — §7 band selector is used directly.
define('KQ_CRITERION_MAP', [
    'code_quality' => 'tech',
    'architecture' => 'tech',
    'processes'    => 'process',
    'lifecycles'   => 'process',
    'agile'        => 'process',
    'testing'      => 'testing',
    'building'     => 'process',
    'devops'       => 'process',
    'metrics'      => 'testing',
    'ai_tools'     => 'ai_usage',
]);

// ── Knowledge-check questions — 10 SE topics, 3 questions each ────────────────
define('KNOWLEDGE_QUESTIONS', [

    'code_quality' => [
        'label' => 'Code Quality',
        'questions' => [
            [
                'q'    => 'Pick one SOLID principle and explain how you applied it in your project. What problem does it solve?',
                'hint' => 'SRP: each class has one reason to change. OCP: open for extension, closed for modification. LSP: subtypes substitutable for base types. ISP: interfaces should be narrow. DIP: depend on abstractions. Look for a concrete example from their codebase.',
            ],
            [
                'q'    => 'What is the Single Responsibility Principle? How can you tell when a class violates it?',
                'hint' => 'A class should have only one reason to change. Violation signals: the class does too many things (e.g. a ViewModel that also calls the database directly), the file is very long, or you struggle to give the class a concise name.',
            ],
            [
                'q'    => 'Explain the DRY principle. Give a concrete example from your code where you avoided duplication.',
                'hint' => 'Don\'t Repeat Yourself: each piece of knowledge should appear once. Examples: a shared base ViewModel, a utility method extracted from repeated logic, a shared Repository interface.',
            ],
        ],
    ],

    'architecture' => [
        'label' => 'Architecture & Design Patterns',
        'questions' => [
            [
                'q'    => 'How does MVVM separate the View from the ViewModel in your project? How does the View know when data has changed?',
                'hint' => 'The View binds to ViewModel properties via data binding. ViewModel implements INotifyPropertyChanged (via ObservableObject). When a property changes, the binding automatically updates the UI without the View holding any business logic.',
            ],
            [
                'q'    => 'Why use a Repository to abstract data access rather than calling the database directly from a ViewModel?',
                'hint' => 'The Repository hides the data source — the ViewModel doesn\'t know whether data comes from a local DB, API, or cache. This enables testing with mock repositories and allows the data source to change without touching the ViewModel.',
            ],
            [
                'q'    => 'Which SOLID principle does the Service Layer most directly demonstrate? Justify your answer.',
                'hint' => 'SRP: Services own business logic so ViewModels don\'t have to. Also DIP: ViewModels depend on service interfaces not concrete classes. Possibly OCP: new services can be added (new implementations injected) without modifying existing ViewModels.',
            ],
        ],
    ],

    'processes' => [
        'label'       => 'Processes & Workflow',
        'require_one' => 'dev_process',
        'questions' => [
            [
                'q'    => 'Describe your Git workflow. What branching strategy did you use and why?',
                'hint' => 'Look for an intentional strategy: feature branches merged via PR, trunk-based development, or similar. The key is that they didn\'t just commit everything to main and can explain why their approach supports quality.',
            ],
            [
                'q'    => 'Show me an example of a meaningful commit message from your repository. What makes it good?',
                'hint' => 'A good message says WHY not just WHAT, uses imperative mood, and is concise. E.g. "Prevent double-booking by validating date overlaps before persisting rental." Poor: "fixed stuff" or "update."',
            ],
            [
                'q'    => 'How does your development workflow ensure that broken code doesn\'t reach the main branch?',
                'hint' => 'Branch protection rules, CI checks required to pass before merge, PR review, automated tests that gate merging. Any combination of these.',
            ],
        ],
    ],

    'lifecycles' => [
        'label'       => 'Lifecycles & Stages',
        'require_one' => 'dev_process',
        'questions' => [
            [
                'q'    => 'What are the main phases of a software development lifecycle? Which phases were most relevant to your project?',
                'hint' => 'Requirements, Design, Implementation, Testing, Deployment, Maintenance. Student should relate phases to their actual project work — not just recite a textbook list.',
            ],
            [
                'q'    => 'What is the key difference between Waterfall and Agile approaches? What are the main trade-offs?',
                'hint' => 'Waterfall: sequential phases, changes expensive late in the process. Agile: iterative, embraces change, delivers incrementally. Trade-offs: predictability and documentation vs flexibility and speed of feedback.',
            ],
            [
                'q'    => 'Where does testing fit in the development lifecycle, and why does it matter when it happens?',
                'hint' => 'Shift-left testing (test early and often) catches bugs when they are cheapest to fix. Late testing means expensive rework. TDD integrates testing from the very start. The later a bug is found, the more it costs.',
            ],
        ],
    ],

    'agile' => [
        'label'       => 'Agile',
        'require_one' => 'dev_process',
        'questions' => [
            [
                'q'    => 'Which value from the Agile Manifesto did you find most relevant to your project, and why?',
                'hint' => 'The four values: individuals and interactions over processes and tools; working software over comprehensive documentation; customer collaboration over contract negotiation; responding to change over following a plan. Any defensible answer with a concrete reason.',
            ],
            [
                'q'    => 'What is a user story, and how does it differ from a traditional requirement specification?',
                'hint' => 'User story: "As a [role] I want [feature] so that [benefit]." Captures the why. Traditional spec: formal, detailed, often fixed. User stories are smaller, negotiable, and testable.',
            ],
            [
                'q'    => 'What is technical debt? Give an example of debt you either accumulated or consciously avoided in your project.',
                'hint' => 'Shortcuts taken now that must be paid back later — hard-coded values, missing tests, poor naming. Conscious avoidance: refactoring early, writing tests before shipping a feature, addressing SonarCloud warnings.',
            ],
        ],
    ],

    'testing' => [
        'label'       => 'Testing',
        'require_one' => 'testing_quality',
        'questions' => [
            [
                'q'    => 'Explain the Arrange-Act-Assert pattern. Walk me through an example from your test suite.',
                'hint' => 'Arrange: set up state and mocks. Act: call the method under test. Assert: verify the outcome. Student should be able to point to an actual test in their code and identify the three phases.',
            ],
            [
                'q'    => 'What is the difference between a unit test and an integration test in your project? Give an example of each.',
                'hint' => 'Unit: tests one class in isolation with mocked dependencies. Integration: tests real interactions between components (e.g. real database via DatabaseFixture). Both should exist in their project.',
            ],
            [
                'q'    => 'What is your test coverage percentage and why isn\'t 100% coverage always the right target?',
                'hint' => 'Should know their coverage figure (>60% is the coursework target). 100% is impractical and can encourage testing implementation details rather than behaviour. Focus on critical paths and business logic.',
            ],
        ],
    ],

    'building' => [
        'label'       => 'Building Applications',
        'require_one' => 'dev_process',
        'questions' => [
            [
                'q'    => 'What happens when you run `dotnet build`? What artefacts are produced and where?',
                'hint' => 'Compiles C# to IL, produces DLL/EXE, copies dependencies. For MAUI, also packages for the target platform (e.g. Android APK at bin/Debug/net10.0-android). Debug vs Release affects optimisation and symbols.',
            ],
            [
                'q'    => 'What is the purpose of Docker Compose in your development setup?',
                'hint' => 'Defines and orchestrates the multi-container environment (MAUI app container + PostgreSQL with PostGIS). `docker compose up` starts both services with correct networking and volumes. Ensures consistency across machines.',
            ],
            [
                'q'    => 'What is the difference between a Debug and Release build configuration?',
                'hint' => 'Debug: includes debug symbols, no optimisations, assertions enabled, larger binary — used during development. Release: optimised, smaller, faster — used for deployment. MAUI release builds produce the signed APK for distribution.',
            ],
        ],
    ],

    'devops' => [
        'label'       => 'DevOps',
        'require_one' => 'dev_process',
        'questions' => [
            [
                'q'    => 'Walk me through your GitHub Actions pipeline. What does each job or step do?',
                'hint' => 'Student should describe their workflow YAML — trigger (push or PR), jobs (build, test, possibly SonarCloud scan), and what commands run in each step. Look for evidence they understand it rather than just copied it.',
            ],
            [
                'q'    => 'What is Continuous Integration and what problem does it solve?',
                'hint' => 'CI automatically builds and tests code on every push or PR. It catches integration issues early, prevents "it works on my machine" problems, and gives fast feedback on regressions before they reach main.',
            ],
            [
                'q'    => 'What is the difference between Continuous Integration and Continuous Deployment?',
                'hint' => 'CI: automated build and test on every change — validates the code is correct. CD: automatically deploys passing builds to an environment. CI is a prerequisite for CD. This project requires CI; CD is optional.',
            ],
        ],
    ],

    'metrics' => [
        'label'       => 'Metrics',
        'require_one' => 'testing_quality',
        'questions' => [
            [
                'q'    => 'What software quality metrics have you tracked or measured in this project?',
                'hint' => 'Test coverage, code complexity, code smells (via SonarCloud), build success rate. At minimum the student should know their coverage figure. Bonus: cyclomatic complexity, duplications, security hotspots.',
            ],
            [
                'q'    => 'What is cyclomatic complexity and why is it used as a code quality metric?',
                'hint' => 'Measures the number of independent paths through code (branches, loops, conditions). High complexity means the code is harder to test, understand, and maintain. SonarCloud flags methods above a threshold.',
            ],
            [
                'q'    => 'How was test coverage measured in your project, and what was your target?',
                'hint' => 'Coverlet via `dotnet test --collect:"XPlat Code Coverage"`. Coverage report can be uploaded to CI as an artefact or to SonarCloud. Target per spec: >60%. Student should be able to show or describe the coverage output.',
            ],
        ],
    ],

    'ai_tools' => [
        'label'       => 'AI in Software Engineering',
        'require_one' => 'ai_usage',
        'questions' => [
            [
                'q'    => 'Which AI tools did you use in this project? How did you validate AI-generated code before accepting it?',
                'hint' => 'GitHub Copilot, ChatGPT, Claude, etc. Validation: reviewed for correctness, ran tests, checked understanding. Look for evidence of critical evaluation — not blind acceptance of suggestions.',
            ],
            [
                'q'    => 'Give a specific example of AI-generated code that you modified. What did you change and why?',
                'hint' => 'Student should be able to point to a concrete example. Modifications might correct logic errors, improve readability, adapt to project conventions, or address security issues flagged by review.',
            ],
            [
                'q'    => 'What are the risks of over-relying on AI tools in software development?',
                'hint' => 'Code you don\'t understand and can\'t maintain, security vulnerabilities, copyright issues, technical debt, false confidence in correctness, gradual erosion of core skills, hallucinated APIs.',
            ],
        ],
    ],

]);

// ── Feature Q&A question banks — 15 MAUI features across 3 tiers ──────────────
define('QUESTIONS', [

    // ── Tier 1: Core Features (required for pass, 40-59%) ─────────────────────

    'user_auth' => [
        'label' => 'User Authentication Integration',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'Walk me through how JWT authentication works in your app. Where is the token stored after login, and how is it included in subsequent API requests?',
                'well'   => 'Confidently traced the full JWT flow: POST to /auth/token, token stored securely, Authorization header added to subsequent requests.',
                'prompt' => 'Could describe the flow with prompting.',
                'no'     => 'Could not explain JWT authentication or token handling.',
            ],
            [
                'q'      => 'What happens when a JWT token expires? How does your app detect and handle this?',
                'well'   => 'Explained token expiry detection (401 response or expiry check) and the refresh or re-login flow clearly.',
                'prompt' => 'Described the approach with some prompting.',
                'no'     => 'Could not explain how token expiry is handled.',
            ],
            [
                'q'      => 'How do you protect pages or routes that require authentication?',
                'well'   => 'Clearly explained the navigation guard, middleware check, or Shell route protection that prevents unauthenticated access.',
                'prompt' => 'Described access control with prompting.',
                'no'     => 'Could not explain how restricted areas are protected.',
            ],
        ],
    ],

    'item_management' => [
        'label' => 'Item Management',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'Walk me through creating a new item listing — from the MAUI UI through to the database.',
                'well'   => 'Traced the full path: View → ViewModel command → Repository → database, confidently identifying each layer\'s role.',
                'prompt' => 'Could describe the flow with prompting.',
                'no'     => 'Could not explain the item creation flow.',
            ],
            [
                'q'      => 'How do you ensure that only the owner of an item can edit or delete it?',
                'well'   => 'Clearly explained the ownership check — whether in the ViewModel, Service, Repository, or API layer.',
                'prompt' => 'Described the approach with prompting.',
                'no'     => 'Could not explain how ownership is enforced.',
            ],
        ],
    ],

    'basic_rental' => [
        'label' => 'Basic Rental Request',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'How does a user submit a rental request? Walk me through the flow from the UI to the data layer.',
                'well'   => 'Traced the flow through ViewModel, command/service, and repository or API call confidently.',
                'prompt' => 'Could describe the flow with prompting.',
                'no'     => 'Could not explain the rental request submission flow.',
            ],
            [
                'q'      => 'How do you display incoming rental requests (as an owner) versus outgoing requests (as a renter)?',
                'well'   => 'Explained how requests are filtered by user role or ownership and presented in separate views or tabs.',
                'prompt' => 'Described the approach with prompting.',
                'no'     => 'Could not explain how different request views are handled.',
            ],
        ],
    ],

    'mvvm_architecture' => [
        'label' => 'MVVM Architecture',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'Pick a ViewModel in your project. How does it communicate with the View? What roles do ObservableObject and Commands play?',
                'well'   => 'Explained the MVVM binding mechanism, ObservableProperty/RelayCommand pattern clearly, and showed confidence in how the View reacts to ViewModel changes.',
                'prompt' => 'Could explain the pattern with some prompting.',
                'no'     => 'Could not explain how the ViewModel communicates with the View.',
            ],
            [
                'q'      => 'Why use Commands instead of event handlers? What is the separation of concerns benefit?',
                'well'   => 'Articulated testability and separation benefits: Commands can be unit-tested without a UI; event handlers couple logic to the View.',
                'prompt' => 'Could describe the benefit with prompting.',
                'no'     => 'Could not explain the rationale for using Commands.',
            ],
            [
                'q'      => 'How does data flow from the Model through the ViewModel to the View in your implementation?',
                'well'   => 'Traced data flow clearly, including how property change notifications propagate to bindings in the View.',
                'prompt' => 'Could describe the flow with prompting.',
                'no'     => 'Could not trace the MVVM data flow.',
            ],
        ],
    ],

    'repository_pattern' => [
        'label' => 'Repository Pattern',
        'tier'  => 'basic',
        'questions' => [
            [
                'q'      => 'Show me your IRepository interface. What methods does it define, and why those methods specifically?',
                'well'   => 'Explained the interface design decisions confidently, including why abstraction is needed and what the interface enables.',
                'prompt' => 'Could explain the interface with prompting.',
                'no'     => 'Could not explain the Repository interface design.',
            ],
            [
                'q'      => 'How does the Repository Pattern help with testing? How are repositories injected into ViewModels?',
                'well'   => 'Clearly explained constructor injection, mock repositories for unit testing, and the decoupling benefit.',
                'prompt' => 'Described the testing benefit with prompting.',
                'no'     => 'Could not explain how the pattern aids testing or how DI works.',
            ],
        ],
    ],

    // ── Tier 2: Intermediate Features (required for merit, 60-69%) ────────────

    'location_discovery' => [
        'label' => 'Location-Based Discovery (PostGIS)',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'      => 'How does your app obtain the user\'s GPS coordinates? What happens if location permission is denied?',
                'well'   => 'Explained the platform location API, permission request flow, and fallback handling (e.g. error message, disabled feature).',
                'prompt' => 'Could describe the approach with prompting.',
                'no'     => 'Could not explain location acquisition or permission handling.',
            ],
            [
                'q'      => 'Walk me through the PostGIS query that finds nearby items. What does ST_DWithin do?',
                'well'   => 'Explained ST_DWithin, the geography type, the distance parameter, and how coordinates are passed from the app.',
                'prompt' => 'Described the query with prompting.',
                'no'     => 'Could not explain the spatial query.',
            ],
            [
                'q'      => 'Why is the spatial logic in LocationService rather than directly in the ViewModel?',
                'well'   => 'Articulated SRP (single responsibility), testability (service can be mocked), and reusability.',
                'prompt' => 'Could explain the rationale with prompting.',
                'no'     => 'Could not explain why the logic belongs in the Service.',
            ],
        ],
    ],

    'rental_workflow' => [
        'label' => 'Rental Workflow Management',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'      => 'Walk me through the rental state transitions. How does your app move a rental from Requested to Approved to Out for Rent?',
                'well'   => 'Traced the state transitions clearly, identifying where each transition is triggered and persisted.',
                'prompt' => 'Could describe the transitions with prompting.',
                'no'     => 'Could not explain the rental state workflow.',
            ],
            [
                'q'      => 'How do you prevent double-booking? Where is this check implemented?',
                'well'   => 'Explained the date overlap validation and clearly identified where the business rule lives (Service or Repository).',
                'prompt' => 'Could describe the approach with prompting.',
                'no'     => 'Could not explain double-booking prevention.',
            ],
            [
                'q'      => 'How is the total rental price calculated?',
                'well'   => 'Explained the calculation (daily rate × duration in days) and where it is computed.',
                'prompt' => 'Described the calculation with prompting.',
                'no'     => 'Could not explain price calculation.',
            ],
        ],
    ],

    'reviews' => [
        'label' => 'Reviews & Feedback',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'      => 'How does your app enforce that a review can only be submitted after the rental is completed?',
                'well'   => 'Explained the rental status check and clearly identified where it is enforced.',
                'prompt' => 'Described the enforcement with prompting.',
                'no'     => 'Could not explain how review timing is enforced.',
            ],
            [
                'q'      => 'How is the average rating calculated and displayed on item or user profiles?',
                'well'   => 'Explained the aggregation — whether via a database query (AVG), a computed property, or in-app calculation.',
                'prompt' => 'Described the approach with prompting.',
                'no'     => 'Could not explain how ratings are aggregated.',
            ],
        ],
    ],

    'service_layer' => [
        'label' => 'Service Layer',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'      => 'Pick a Service in your project. Walk me through a method. What business rule does it enforce?',
                'well'   => 'Explained a service method clearly, identifying the specific business logic it contains and why it belongs in the service.',
                'prompt' => 'Could describe the service with prompting.',
                'no'     => 'Could not explain what a service does or what logic it holds.',
            ],
            [
                'q'      => 'What is the difference between logic that belongs in a Service versus logic in a ViewModel?',
                'well'   => 'Articulated the separation clearly: services hold business rules and data operations; ViewModels coordinate UI state and delegate to services.',
                'prompt' => 'Could explain the distinction with prompting.',
                'no'     => 'Could not explain the Service vs ViewModel distinction.',
            ],
            [
                'q'      => 'How are services injected into ViewModels, and how does this support testability?',
                'well'   => 'Explained constructor injection, registering services in MauiProgram.cs, and how mock services enable unit testing of ViewModels.',
                'prompt' => 'Described dependency injection with prompting.',
                'no'     => 'Could not explain dependency injection for services.',
            ],
        ],
    ],

    'comprehensive_testing' => [
        'label' => 'Comprehensive Testing',
        'tier'  => 'intermediate',
        'questions' => [
            [
                'q'      => 'Walk me through a unit test from your test suite. Identify the Arrange, Act, and Assert phases.',
                'well'   => 'Pointed to a specific test and clearly identified all three AAA phases.',
                'prompt' => 'Could identify the phases with some prompting.',
                'no'     => 'Could not explain the AAA pattern or identify it in their tests.',
            ],
            [
                'q'      => 'How do you test code that depends on external services — for example, the database or LocationService?',
                'well'   => 'Explained DatabaseFixture for integration tests and mock/stub interfaces for unit tests.',
                'prompt' => 'Described the approach with prompting.',
                'no'     => 'Could not explain how external dependencies are handled in tests.',
            ],
            [
                'q'      => 'What is your test coverage percentage and how was it measured?',
                'well'   => 'Stated the coverage figure and explained the tooling (e.g. Coverlet, GitHub Actions artefact, SonarCloud).',
                'prompt' => 'Could describe coverage with prompting.',
                'no'     => 'Could not state their coverage or explain how it is measured.',
            ],
        ],
    ],

    // ── Tier 3: Advanced Features (bonus, 70-100%) ────────────────────────────

    'state_pattern' => [
        'label' => 'State Pattern Implementation',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'      => 'Walk me through a rental state transition using your State Pattern. How does a concrete state class handle a transition?',
                'well'   => 'Explained the IRentalState interface and how a concrete state (e.g. RequestedState) implements a valid transition and prevents invalid ones.',
                'prompt' => 'Could describe the pattern with prompting.',
                'no'     => 'Could not explain how the State Pattern is implemented.',
            ],
            [
                'q'      => 'How does the State Pattern prevent invalid state transitions?',
                'well'   => 'Explained that each state only implements transitions valid from that state; attempting an invalid transition throws an exception or is a no-op.',
                'prompt' => 'Described the guard mechanism with prompting.',
                'no'     => 'Could not explain how invalid transitions are prevented.',
            ],
            [
                'q'      => 'How does this approach compare to using a simple enum and switch statement?',
                'well'   => 'Articulated the OCP advantage: new states can be added as new classes without modifying existing code. Switch statements require editing in multiple places.',
                'prompt' => 'Could compare the approaches with prompting.',
                'no'     => 'Could not explain the advantage over an enum/switch approach.',
            ],
        ],
    ],

    'mediatr' => [
        'label' => 'MediatR Event-Driven Architecture',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'      => 'Walk me through a Command (e.g. RequestRentalCommand). How is it dispatched and handled?',
                'well'   => 'Explained the command object, calling mediator.Send(), and how the corresponding handler processes it.',
                'prompt' => 'Could describe the flow with prompting.',
                'no'     => 'Could not explain how MediatR commands work.',
            ],
            [
                'q'      => 'How does MediatR demonstrate the Open/Closed Principle?',
                'well'   => 'Explained that new handlers can be added (open for extension) without modifying the mediator or existing handlers (closed for modification).',
                'prompt' => 'Could explain the OCP connection with prompting.',
                'no'     => 'Could not explain the Open/Closed Principle in this context.',
            ],
            [
                'q'      => 'What are the benefits of event-driven architecture over direct service calls?',
                'well'   => 'Articulated loose coupling, extensibility, and audit trail benefits. Multiple handlers can respond to one event independently.',
                'prompt' => 'Could describe the benefits with prompting.',
                'no'     => 'Could not explain the benefits of event-driven architecture.',
            ],
        ],
    ],

    'overdue_detection' => [
        'label' => 'Overdue Detection',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'      => 'How does your app detect when a rental has become overdue?',
                'well'   => 'Explained the mechanism — background job, scheduled task, startup check, or polling — and where in the codebase this lives.',
                'prompt' => 'Could describe the detection approach with prompting.',
                'no'     => 'Could not explain how overdue detection works.',
            ],
            [
                'q'      => 'What triggers the transition from Out for Rent to Overdue, and how is this persisted?',
                'well'   => 'Explained the condition (return date passed), how the check runs, and how the new state is saved.',
                'prompt' => 'Described the trigger and persistence with prompting.',
                'no'     => 'Could not explain the overdue transition mechanism.',
            ],
        ],
    ],

    'advanced_spatial' => [
        'label' => 'Advanced Spatial Features',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'      => 'How does the map display item locations? Where does the location data come from?',
                'well'   => 'Explained the map library or API used, how coordinates are fetched from the database, and how markers are rendered.',
                'prompt' => 'Could describe the map integration with prompting.',
                'no'     => 'Could not explain how map markers are populated.',
            ],
            [
                'q'      => 'How does the search radius slider affect the PostGIS query?',
                'well'   => 'Explained how the slider value is passed as a parameter to ST_DWithin, dynamically changing the search radius.',
                'prompt' => 'Described the connection with prompting.',
                'no'     => 'Could not explain how the slider affects the spatial query.',
            ],
        ],
    ],

    'sonarcloud' => [
        'label' => 'SonarCloud Quality Analysis',
        'tier'  => 'advanced',
        'questions' => [
            [
                'q'      => 'Walk me through your SonarCloud integration. How is it triggered in your CI/CD pipeline?',
                'well'   => 'Explained the GitHub Actions step or job that runs the SonarCloud scan, including the scanner and project key configuration.',
                'prompt' => 'Could describe the integration with prompting.',
                'no'     => 'Could not explain how SonarCloud is integrated.',
            ],
            [
                'q'      => 'What quality gate result did you achieve? What specific issues did SonarCloud flag?',
                'well'   => 'Discussed concrete issues flagged (code smells, security hotspots, duplications) and whether the quality gate passed.',
                'prompt' => 'Could describe results with prompting.',
                'no'     => 'Could not explain what SonarCloud found or the quality gate result.',
            ],
            [
                'q'      => 'How did SonarCloud analysis affect your code quality decisions during development?',
                'well'   => 'Gave concrete examples of changes made in response to SonarCloud feedback.',
                'prompt' => 'Could describe the impact with prompting.',
                'no'     => 'Could not explain how SonarCloud influenced their development decisions.',
            ],
        ],
    ],

]);
