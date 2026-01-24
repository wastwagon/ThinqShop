<?php
/**
 * Admin Authentication Check
 * Redirects to admin login if not authenticated
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

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    $baseUrl = rtrim(trim(BASE_URL, '"\''), '/');
    header('Location: ' . $baseUrl . '/login.php');
    exit;
}

// Verify admin user exists and is active
require_once __DIR__ . '/../config/database.php';
$db = new Database();
$conn = $db->getConnection();

$stmt = $conn->prepare("SELECT * FROM admin_users WHERE id = ? AND is_active = 1");
$stmt->execute([$_SESSION['admin_id']]);
$adminUser = $stmt->fetch();

if (!$adminUser) {
    session_destroy();
    $baseUrl = rtrim(trim(BASE_URL, '"\''), '/');
    header('Location: ' . $baseUrl . '/login.php?error=unauthorized');
    exit;
}

// Update last login (once per session)
if (!isset($_SESSION['last_login_updated'])) {
    $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
    $updateStmt->execute([$_SESSION['admin_id']]);
    $_SESSION['last_login_updated'] = true;
}

