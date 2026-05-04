<?php
session_name('admin_tool');
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

if (empty($_SESSION['admin_auth'])) {
    header('Location: login.php');
    exit;
}
$currentAdminUser = $_SESSION['admin_user'] ?? 'admin';
