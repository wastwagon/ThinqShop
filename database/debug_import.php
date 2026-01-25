<?php
/**
 * Debug Import Script (Fresh File)
 * USAGE: php database/debug_import.php
 */

$host = getenv('DB_HOST') ?: 'mysql';
$db   = getenv('DB_NAME') ?: 'thinjupz_db';
$user = getenv('DB_USER') ?: 'thinquser';
$pass = getenv('DB_PASS') ?: 'thinqpass';

echo "\n!!! STARTING DEBUG IMPORT !!!\n";
echo "1. Connecting to Database ($host)...\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET FOREIGN_KEY_CHECKS=0");
    echo "   -> Success. FK Checks Disabled.\n";
} catch (PDOException $e) {
    die("   -> ERROR: " . $e->getMessage() . "\n");
}

$sqlFile = '/var/www/html/thinjupz_db.sql';
echo "2. Checking SQL File: $sqlFile\n";

if (!file_exists($sqlFile)) {
    die("   -> ERROR: File not found!\n");
}

$sqlContent = file_get_contents($sqlFile);
$size = strlen($sqlContent);
echo "   -> File loaded. Size: " . number_format($size) . " bytes.\n";

if ($size < 1000) {
    echo "   -> WARNING: File seems too small to be a full dump.\n";
}

// Debug Regex for Products
echo "3. Scanning for 'products' inserts...\n";
$chkPattern = "/INSERT INTO `?products`?/";
preg_match_all($chkPattern, $sqlContent, $chkMatches);
$count = count($chkMatches[0]);
echo "   -> Found $count 'INSERT INTO products' statements via Regex.\n";

if ($count == 0) {
    echo "   -> CRITICAL: No product inserts found in the file content seen by PHP.\n";
    echo "   -> Dumping first 500 chars of file to check content:\n";
    echo substr($sqlContent, 0, 500) . "\n...\n";
}

// Processing
echo "4. Attempting Import...\n";
preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sqlContent, $matches);
$tables = array_unique($matches[1]);

foreach ($tables as $table) {
    // Only focus on tables we know are empty or important for now to save output noise
    if (!in_array($table, ['products', 'orders', 'users', 'cart', 'categories'])) {
        // continue; // Uncomment to speed up if needed, but let's try all
    }

    $pattern = "INSERT INTO `$table`";
    if (strpos($sqlContent, $pattern) === false) {
        $pattern = "INSERT INTO $table";
    }

    // Quick check if this table has inserts
    if (strpos($sqlContent, $pattern) === false) {
        // echo "   [skip $table - no inserts found]\n";
        continue;
    }

    echo "   -> Processing '$table': ";
    
    // Execution Loop
    $offset = 0;
    $batches = 0;
    $errs = 0;
    
    while (($start = strpos($sqlContent, $pattern, $offset)) !== false) {
        $end = strpos($sqlContent, ";", $start);
        if ($end === false) break;
        
        $sql = substr($sqlContent, $start, $end - $start + 1);
        try {
            $pdo->exec($sql);
            $batches++;
        } catch (Exception $e) {
            echo "\n      [SQL Error] " . $e->getMessage() . "\n";
            $errs++;
        }
        $offset = $end + 1;
    }
    
    echo "Imported $batches batches. ($errs skipped)\n";
}

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo "\n!!! FINISHED !!!\n";
?>
