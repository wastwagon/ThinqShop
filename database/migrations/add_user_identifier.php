<?php
/**
 * Migration: Add user_identifier column to users table
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../config/database.php';

$db = new Database();
$conn = $db->getConnection();

try {
    echo "Adding user_identifier column to users table...\n";
    
    // Check if column already exists
    $stmt = $conn->query("SHOW COLUMNS FROM users LIKE 'user_identifier'");
    if ($stmt->rowCount() > 0) {
        echo "Column 'user_identifier' already exists. Skipping...\n";
    } else {
        // Add user_identifier column
        $conn->exec("
            ALTER TABLE users 
            ADD COLUMN user_identifier VARCHAR(50) NULL AFTER id,
            ADD UNIQUE KEY user_identifier (user_identifier),
            ADD KEY idx_user_identifier (user_identifier)
        ");
        echo "✓ Column 'user_identifier' added successfully.\n";
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

