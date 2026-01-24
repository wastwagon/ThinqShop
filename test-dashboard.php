<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start session
session_start();
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';

require 'admin/dashboard.php';
