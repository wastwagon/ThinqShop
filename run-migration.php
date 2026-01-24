<?php
/**
 * Run Database Migration
 * Creates tickets and email tables
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Database Migration</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;}";
echo "h2{border-bottom:2px solid #333;padding-bottom:10px;}";
echo "pre{background:white;padding:10px;border:1px solid #ddd;overflow-x:auto;}";
echo "</style></head><body>";

echo "<h1>Database Migration Tool</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>Step 1: Reading Migration File</h2>";
    $migrationFile = __DIR__ . '/database/migrations/email_notifications_tickets.sql';
    
    if (!file_exists($migrationFile)) {
        // Try source location
        $migrationFile = __DIR__ . '/../../database/migrations/email_notifications_tickets.sql';
    }
    
    if (!file_exists($migrationFile)) {
        echo "<p class='error'>❌ Migration file not found!</p>";
        echo "<p>Looking for: $migrationFile</p>";
        die();
    }
    
    echo "<p class='success'>✅ Migration file found: $migrationFile</p>";
    
    $sql = file_get_contents($migrationFile);
    
    // Remove single-line comments but preserve multi-line INSERT statements
    $sql = preg_replace('/--.*$/m', '', $sql);
    
    // Split by semicolon, but be careful with multi-line statements
    // First, let's handle INSERT statements specially since they can span multiple lines
    $statements = [];
    $currentStatement = '';
    $inString = false;
    $stringChar = '';
    
    for ($i = 0; $i < strlen($sql); $i++) {
        $char = $sql[$i];
        $nextChar = ($i < strlen($sql) - 1) ? $sql[$i + 1] : '';
        
        // Track string boundaries
        if (($char === '"' || $char === "'") && ($i === 0 || $sql[$i - 1] !== '\\')) {
            if (!$inString) {
                $inString = true;
                $stringChar = $char;
            } elseif ($char === $stringChar) {
                $inString = false;
                $stringChar = '';
            }
        }
        
        $currentStatement .= $char;
        
        // If we hit a semicolon and we're not in a string, end the statement
        if ($char === ';' && !$inString) {
            $trimmed = trim($currentStatement);
            if (!empty($trimmed) && strlen($trimmed) > 10) {
                $statements[] = $trimmed;
            }
            $currentStatement = '';
        }
    }
    
    // Add any remaining statement
    if (!empty(trim($currentStatement))) {
        $statements[] = trim($currentStatement);
    }
    
    echo "<h2>Step 2: Executing Migration</h2>";
    $successCount = 0;
    $errorCount = 0;
    
    // Note: CREATE TABLE statements auto-commit in MySQL, so we don't use transactions
    // Each statement will be executed individually
    
    foreach ($statements as $statement) {
        if (empty($statement) || strlen($statement) < 10) {
            continue;
        }
        
        try {
            $conn->exec($statement);
            $successCount++;
            echo "<p class='success'>✅ Executed: " . htmlspecialchars(substr($statement, 0, 50)) . "...</p>";
        } catch (PDOException $e) {
            // Ignore "table already exists" or "duplicate entry" errors
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate entry') !== false ||
                strpos($e->getMessage(), 'Duplicate key') !== false) {
                echo "<p>⚠️ Already exists (skipping): " . htmlspecialchars(substr($statement, 0, 50)) . "...</p>";
                $successCount++; // Count as success since it's already there
                continue;
            }
            $errorCount++;
            echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<pre>" . htmlspecialchars(substr($statement, 0, 200)) . "</pre>";
        }
    }
    
    echo "<h2>Step 3: Verification</h2>";
    
    $tables = ['tickets', 'ticket_messages', 'email_templates', 'email_settings', 'email_verification_tokens', 'admin_notifications', 'notification_settings'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $conn->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "<p class='success'>✅ Table '$table' exists</p>";
            } else {
                echo "<p class='error'>❌ Table '$table' does NOT exist</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Error checking table '$table': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h2>Summary</h2>";
    echo "<p>Successfully executed: <strong>$successCount</strong> statements</p>";
    echo "<p>Errors: <strong>$errorCount</strong></p>";
    
    if ($errorCount === 0) {
        echo "<p class='success'><strong>✅ Migration completed successfully!</strong></p>";
        echo "<p><a href='admin/tickets/'>Go to Ticket Management</a></p>";
        echo "<p><a href='admin/settings/email-settings.php'>Go to Email Settings</a></p>";
        echo "<p><a href='admin/settings/email-templates.php'>Go to Email Templates</a></p>";
    } else {
        echo "<p class='error'><strong>⚠️ Migration completed with errors. Please review above.</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Fatal Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "</body></html>";
?>

