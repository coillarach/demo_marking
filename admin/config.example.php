<?php
/**
 * Admin tool configuration.
 * Copy this file to config.php and set your credentials.
 * config.php is gitignored and must never be committed.
 */

define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', password_hash('changeme', PASSWORD_DEFAULT));
define('CONFIGS_DIR',    __DIR__ . '/../configs/');
define('DEPLOY_BASE',    '/var/www/html');
define('ADMIN_URL',      '/admin');         // URL path to reach this admin interface
