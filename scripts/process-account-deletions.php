<?php
/**
 * Process Account Deletions Cron Job
 * Permanently deletes accounts after 30-day grace period
 * 
 * Schedule: Run daily via cron
 * Example crontab: 0 2 * * * /usr/bin/php /path/to/scripts/process-account-deletions.php
 */

// Set execution time limit
set_time_limit(300); // 5 minutes max

// Load required files
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

$logFile = __DIR__ . '/deletion-log-' . date('Y-m-d') . '.txt';
$deletedCount = 0;
$errorCount = 0;

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    echo "[{$timestamp}] {$message}\n";
}

logMessage("=== Account Deletion Process Started ===");

try {
    // Find accounts scheduled for deletion
    $stmt = $conn->query("
        SELECT id, email, deletion_scheduled_for 
        FROM users 
        WHERE deletion_scheduled_for IS NOT NULL 
        AND deletion_scheduled_for <= NOW()
        AND deletion_requested_at IS NOT NULL
    ");
    
    $accountsToDelete = $stmt->fetchAll();
    
    logMessage("Found " . count($accountsToDelete) . " accounts to delete");
    
    foreach ($accountsToDelete as $account) {
        $userId = $account['id'];
        $userEmail = $account['email'];
        
        logMessage("Processing deletion for user ID: {$userId} ({$userEmail})");
        
        try {
            $conn->beginTransaction();
            
            // Delete user data in order (respecting foreign keys)
            
            // 1. Delete cart items
            $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted cart items");
            
            // 2. Delete wishlist items
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted wishlist items");
            
            // 3. Delete product reviews
            $stmt = $conn->prepare("DELETE FROM product_reviews WHERE user_id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted product reviews");
            
            // 4. Delete addresses
            $stmt = $conn->prepare("DELETE FROM addresses WHERE user_id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted addresses");
            
            // 5. Delete wallet transactions
            $stmt = $conn->prepare("DELETE FROM wallet_transactions WHERE user_id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted wallet transactions");
            
            // 6. Delete wallet
            $stmt = $conn->prepare("DELETE FROM user_wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted wallet");
            
            // 7. Delete notifications
            $stmt = $conn->prepare("DELETE FROM notifications WHERE user_id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted notifications");
            
            // 8. Update orders (don't delete, but anonymize)
            $stmt = $conn->prepare("
                UPDATE orders 
                SET user_id = NULL, 
                    customer_email = CONCAT('deleted_', id, '@deleted.local'),
                    updated_at = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);
            logMessage("  - Anonymized orders");
            
            // 9. Delete user profile
            $stmt = $conn->prepare("DELETE FROM user_profiles WHERE user_id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted user profile");
            
            // 10. Update deletion log
            $stmt = $conn->prepare("
                UPDATE account_deletion_logs 
                SET deletion_completed_at = NOW()
                WHERE user_id = ? 
                AND deletion_completed_at IS NULL
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            logMessage("  - Updated deletion log");
            
            // 11. Finally, delete the user account
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            logMessage("  - Deleted user account");
            
            $conn->commit();
            $deletedCount++;
            
            logMessage("✓ Successfully deleted user ID: {$userId}");
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errorCount++;
            logMessage("✗ Error deleting user ID {$userId}: " . $e->getMessage());
        }
    }
    
} catch (Exception $e) {
    logMessage("✗ Fatal error: " . $e->getMessage());
}

logMessage("=== Account Deletion Process Completed ===");
logMessage("Total deleted: {$deletedCount}");
logMessage("Total errors: {$errorCount}");

// Send summary email to admin if there were deletions or errors
if ($deletedCount > 0 || $errorCount > 0) {
    $adminEmail = BUSINESS_EMAIL ?? 'admin@example.com';
    $subject = "Account Deletion Report - " . date('Y-m-d');
    $body = "
        <h2>Account Deletion Report</h2>
        <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Accounts Deleted:</strong> {$deletedCount}</p>
        <p><strong>Errors:</strong> {$errorCount}</p>
        <p>Full log available at: {$logFile}</p>
    ";
    
    if (class_exists('EmailService')) {
        try {
            $emailService = new EmailService();
            $emailService->sendEmail($adminEmail, $subject, $body);
        } catch (Exception $e) {
            logMessage("Failed to send admin email: " . $e->getMessage());
        }
    }
}

exit(0);
