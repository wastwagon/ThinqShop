<?php
/**
 * Reset Admin Password Script
 * Usage: php scripts/reset-admin-password.php [new_password]
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$newPassword = $argv[1] ?? 'admin123';

$db = new Database();
$conn = $db->getConnection();

// Generate password hash
$passwordHash = password_hash($newPassword, PASSWORD_BCRYPT);

// Update admin password
$stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = 'admin'");
$stmt->execute([$passwordHash]);

echo "✅ Admin password reset successfully!\n";
echo "Username: admin\n";
echo "Password: $newPassword\n";
echo "\n⚠️  Please change this password after logging in!\n";








