<?php
/**
 * Migration: Generate user identifiers for existing users
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

try {
    echo "Generating user identifiers for existing users...\n\n";
    
    // Get all users without identifiers
    $stmt = $conn->query("
        SELECT u.id, up.first_name 
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        WHERE u.user_identifier IS NULL OR u.user_identifier = ''
        ORDER BY u.id ASC
    ");
    $users = $stmt->fetchAll();
    
    if (empty($users)) {
        echo "No users found without identifiers.\n";
        exit(0);
    }
    
    echo "Found " . count($users) . " users without identifiers.\n\n";
    
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($users as $user) {
        $userId = $user['id'];
        $firstName = $user['first_name'] ?? 'User';
        
        try {
            // Generate identifier
            $identifier = generateUserIdentifier($firstName, $conn);
            
            // Update user
            $updateStmt = $conn->prepare("UPDATE users SET user_identifier = ? WHERE id = ?");
            $updateStmt->execute([$identifier, $userId]);
            
            echo "✓ User ID {$userId}: {$identifier}\n";
            $successCount++;
            
        } catch (Exception $e) {
            echo "✗ User ID {$userId}: Error - " . $e->getMessage() . "\n";
            $errorCount++;
        }
    }
    
    echo "\n";
    echo "Migration completed!\n";
    echo "Successfully updated: {$successCount} users\n";
    if ($errorCount > 0) {
        echo "Errors: {$errorCount} users\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

