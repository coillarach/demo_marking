<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
session_destroy();
require_once __DIR__ . '/config.php';
$home = defined('ADMIN_URL') ? ADMIN_URL . '/home.php' : 'login.php';
header('Location: ' . $home);
exit;
