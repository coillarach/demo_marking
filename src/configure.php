<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['marking_auth'])) {
    header('Location: login.php');
    exit;
}

$markerId   = $_SESSION['marker_id'];
$markerName = $_SESSION['marker_name'];
session_write_close();

session_name('module_tool');
session_start();
$_SESSION['module_auth']        = true;
$_SESSION['module_id']          = MODULE_ID;
$_SESSION['module_marker_id']   = $markerId;
$_SESSION['module_marker_name'] = $markerName;
$_SESSION['module_return_url']  = MODULE_URL . '/demo.php';
session_write_close();

header('Location: ' . ADMIN_URL . '/module_edit.php');
exit;
