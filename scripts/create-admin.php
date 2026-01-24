<?php
/**
 * Create/Reset Admin User Script
 * Usage: php scripts/create-admin.php
 */

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Default admin credentials
$username = 'admin';
$email = 'admin@thinqshopping.com';
$password = 'admin123';
$fullName = 'Administrator';
$role = 'super_admin';

// Hash the password
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

try {
    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing admin
        $stmt = $conn->prepare("
            UPDATE admin_users 
            SET password = ?, email = ?, full_name = ?, role = ?, is_active = 1 
            WHERE id = ?
        ");
        $stmt->execute([$passwordHash, $email, $fullName, $role, $existing['id']]);
        echo "✅ Admin user updated successfully!\n";
    } else {
        // Create new admin user
        $stmt = $conn->prepare("
            INSERT INTO admin_users (username, email, password, full_name, role, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$username, $email, $passwordHash, $fullName, $role]);
        echo "✅ Admin user created successfully!\n";
    }
    
    echo "\n";
    echo "Admin Credentials:\n";
    echo "==================\n";
    echo "Username: {$username}\n";
    echo "Email: {$email}\n";
    echo "Password: {$password}\n";
    echo "\n";
    echo "⚠️  IMPORTANT: Change this password immediately after first login!\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}






