<?php
/**
 * Run Procurement Category and Items Migration
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/constants.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "Starting procurement category migration...\n";
    
    // Read SQL file
    $sqlFile = __DIR__ . '/add_procurement_category_and_items.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            // Check if column/table already exists before adding
            if (stripos($statement, 'ADD COLUMN') !== false) {
                preg_match('/ADD COLUMN\s+`?(\w+)`?/i', $statement, $matches);
                if (!empty($matches[1])) {
                    $columnName = $matches[1];
                    preg_match('/ALTER TABLE\s+`?(\w+)`?/i', $statement, $tableMatches);
                    $tableName = !empty($tableMatches[1]) ? $tableMatches[1] : 'procurement_requests';
                    
                    // Check if column exists
                    $checkStmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM INFORMATION_SCHEMA.COLUMNS 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ? 
                        AND COLUMN_NAME = ?
                    ");
                    $checkStmt->execute([$tableName, $columnName]);
                    $result = $checkStmt->fetch();
                    
                    if ($result['count'] > 0) {
                        echo "Column `$columnName` already exists in `$tableName`, skipping...\n";
                        continue;
                    }
                }
            }
            
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE\s+(?:IF NOT EXISTS\s+)?`?(\w+)`?/i', $statement, $matches);
                if (!empty($matches[1])) {
                    $tableName = $matches[1];
                    
                    // Check if table exists
                    $checkStmt = $conn->prepare("
                        SELECT COUNT(*) as count 
                        FROM INFORMATION_SCHEMA.TABLES 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = ?
                    ");
                    $checkStmt->execute([$tableName]);
                    $result = $checkStmt->fetch();
                    
                    if ($result['count'] > 0) {
                        echo "Table `$tableName` already exists, skipping...\n";
                        continue;
                    }
                }
            }
            
            $conn->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
            
        } catch (PDOException $e) {
            // Check if error is about column/table already existing
            if (strpos($e->getMessage(), 'Duplicate column') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                echo "Already exists, skipping: " . substr($statement, 0, 50) . "...\n";
                continue;
            }
            throw $e;
        }
    }
    
    echo "\nMigration completed successfully!\n";
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    exit(1);
}



