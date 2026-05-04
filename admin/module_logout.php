<?php
session_name('module_tool');
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
session_destroy();
header('Location: home.php');
exit;
