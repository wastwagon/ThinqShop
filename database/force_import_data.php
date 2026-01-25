<?php
/**
 * Force Data Import Script
 * USAGE: php database/force_import_data.php
 * 
 * This script blindly reads the SQL dump and executes all INSERT statements.
 * It is designed to fill empty tables if the auto-migrator failed.
 */

// Connection
$host = getenv('DB_HOST') ?: 'mysql';
$db   = getenv('DB_NAME') ?: 'thinjupz_db';
$user = getenv('DB_USER') ?: 'thinquser';
$pass = getenv('DB_PASS') ?: 'thinqpass';

echo "[Force-Import] Connecting to $host...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Disable FKs to allow out-of-order inserts
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    echo "[Force-Import] Connected. Foreign Key Checks Disabled.\n";
} catch (PDOException $e) {
    die("[Force-Import] ERROR: " . $e->getMessage() . "\n");
}

$sqlFile = '/var/www/html/thinjupz_db.sql';
if (!file_exists($sqlFile)) {
    die("[Force-Import] ERROR: Dump file not found at $sqlFile\n");
}

echo "[Force-Import] Reading $sqlFile...\n";
$sqlContent = file_get_contents($sqlFile);

// Identify tables to report progress
preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sqlContent, $matches);
$tables = array_unique($matches[1]);
echo "[Force-Import] Found " . count($tables) . " tables in dump.\n";

$totalInserts = 0;
$errors = 0;

foreach ($tables as $table) {
    echo "[Force-Import] Processing '$table'...";
    
    // Check if table is empty
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        if ($count > 0) {
            echo " Skipped (Has $count rows)\n";
            // Uncomment to force overwrite:
            // $pdo->exec("TRUNCATE TABLE `$table`");
            continue; 
        }
    } catch (Exception $e) {
        echo " SKIP (Table doesn't exist?)\n";
        continue;
    }

    // Try finding inserts with backticks
    $pattern = "INSERT INTO `$table`";
    if (strpos($sqlContent, $pattern) === false) {
        $pattern = "INSERT INTO $table";
    }

    $offset = 0;
    $tableInserts = 0;
    
    while (($start = strpos($sqlContent, $pattern, $offset)) !== false) {
        $end = strpos($sqlContent, ";", $start);
        if ($end === false) break;
        
        $sql = substr($sqlContent, $start, $end - $start + 1);
        
        try {
            $pdo->exec($sql);
            $tableInserts++;
            $totalInserts++;
        } catch (Exception $e) {
            // echo " X"; // Silent fail for dups if we didn't check count
            $errors++;
        }
        
        $offset = $end + 1;
    }
    
    echo " Imported $tableInserts batches.\n";
}

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo "\n[Force-Import] DONE. Total Batches: $totalInserts. Errors: $errors.\n";
?>
