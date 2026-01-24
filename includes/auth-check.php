<?php
/**
 * Authentication Check
 * Redirects to login if user is not authenticated
 */

// Load constants first
if (!defined('SESSION_NAME')) {
    require_once __DIR__ . '/../config/constants.php';
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $currentPage = $_SERVER['REQUEST_URI'] ?? '/';
    $_SESSION['redirect_after_login'] = $currentPage;
    $baseUrl = rtrim(trim(BASE_URL, '"\''), '/');
    header('Location: ' . $baseUrl . '/login.php');
    exit;
}

// Load user data
require_once __DIR__ . '/functions.php';
$currentUser = getCurrentUser();

if (!$currentUser || !$currentUser['is_active']) {
    session_destroy();
    $baseUrl = rtrim(trim(BASE_URL, '"\''), '/');
    header('Location: ' . $baseUrl . '/login.php?error=account_inactive');
    exit;
}

