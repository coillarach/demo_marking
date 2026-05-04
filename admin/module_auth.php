<?php
session_name('module_tool');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['module_auth'])) {
    header('Location: module_login.php');
    exit;
}
$currentModuleId    = $_SESSION['module_id']          ?? '';
$currentMarkerId    = $_SESSION['module_marker_id']   ?? '';
$currentMarkerName  = $_SESSION['module_marker_name'] ?? '';
$moduleReturnUrl    = $_SESSION['module_return_url']  ?? '';
