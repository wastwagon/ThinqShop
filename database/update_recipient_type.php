<?php
/**
 * Database Migration: Add mobile_money to recipient_type ENUM
 * Run this file once via browser or command line to update the database
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Database Migration - Update Recipient Type</title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
            h2 { color: #333; }
            .success { color: green; font-weight: bold; }
            .error { color: red; font-weight: bold; }
            .info { color: #666; }
            a { color: #007bff; text-decoration: none; }
            a:hover { text-decoration: underline; }
        </style>
    </head>
    <body>";
    
    echo "<h2>Updating Database Schema...</h2>";
    
    // Update money_transfers table
    echo "<p>Updating money_transfers table...</p>";
    $sql1 = "ALTER TABLE `money_transfers` 
             MODIFY COLUMN `recipient_type` ENUM('bank_account','alipay','wechat_pay','mobile_money') NOT NULL";
    $conn->exec($sql1);
    echo "<p class='success'>✓ money_transfers table updated successfully!</p>";
    
    // Update saved_recipients table
    echo "<p>Updating saved_recipients table...</p>";
    $sql2 = "ALTER TABLE `saved_recipients` 
             MODIFY COLUMN `recipient_type` ENUM('bank_account','alipay','wechat_pay','mobile_money') NOT NULL";
    $conn->exec($sql2);
    echo "<p class='success'>✓ saved_recipients table updated successfully!</p>";
    
    // Verify the changes
    echo "<h3>Verification:</h3>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM money_transfers WHERE Field = 'recipient_type'");
    $column = $stmt->fetch();
    echo "<p><strong>money_transfers.recipient_type:</strong> <span class='info'>" . htmlspecialchars($column['Type']) . "</span></p>";
    
    $stmt = $conn->query("SHOW COLUMNS FROM saved_recipients WHERE Field = 'recipient_type'");
    $column = $stmt->fetch();
    echo "<p><strong>saved_recipients.recipient_type:</strong> <span class='info'>" . htmlspecialchars($column['Type']) . "</span></p>";
    
    echo "<hr>";
    echo "<h2 class='success'>✓ Database migration completed successfully!</h2>";
    echo "<p>You can now use the money transfer form with mobile_money option.</p>";
    echo "<p><a href='/ThinQShopping/modules/money-transfer/transfer-form/'>← Go to Money Transfer Form</a></p>";
    
    echo "</body></html>";
    
} catch (PDOException $e) {
    echo "<h2 class='error'>Error:</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    
    // Check if error is because column already has the value
    if (strpos($e->getMessage(), 'Duplicate') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "<p class='info'>Note: This error might indicate the update was already applied. Please verify the column type above.</p>";
    }
    
    echo "<p><a href='/ThinQShopping/modules/money-transfer/transfer-form/'>← Go to Money Transfer Form</a></p>";
    echo "</body></html>";
}
