<?php
/**
 * Web-based migration runner
 */
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

echo "<h1>System Migration: User Identifiers</h1>";

try {
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
        echo "<p>No users found without identifiers.</p>";
    } else {
        echo "<p>Found " . count($users) . " users without identifiers.</p><ul>";
        
        $successCount = 0;
        
        foreach ($users as $user) {
            $userId = $user['id'];
            $firstName = $user['first_name'] ?? 'User';
            
            // Generate identifier
            $identifier = generateUserIdentifier($firstName, $conn);
            
            // Update user
            $updateStmt = $conn->prepare("UPDATE users SET user_identifier = ? WHERE id = ?");
            $updateStmt->execute([$identifier, $userId]);
            
            echo "<li>✓ User ID {$userId}: <strong>{$identifier}</strong></li>";
            $successCount++;
        }
        echo "</ul><p>Migration completed! Successfully updated: {$successCount} users.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='user/dashboard.php'>Return to Dashboard</a>";
