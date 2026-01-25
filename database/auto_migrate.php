<?php
/**
 * Auto-Migration Script (Robust V3)
 * Runs on container startup to ensure database schema is correct.
 * Handles Foreign Key constraints via ordering retries and explicit checks.
 */

$maxTries = 50; 
$success = false;

echo "[Auto-Migrate] Waiting for Database connection...\n";

$host = getenv('DB_HOST') ?: 'mysql';
$db   = getenv('DB_NAME') ?: 'thinjupz_db';
$user = getenv('DB_USER') ?: 'thinquser';
$pass = getenv('DB_PASS') ?: 'thinqpass';

$pdo = null;
for ($i = 0; $i < $maxTries; $i++) {
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $success = true;
        echo "[Auto-Migrate] Database connected successfully!\n";
        break;
    } catch (PDOException $e) {
        if ($i % 5 == 0) echo "[Auto-Migrate] Waiting for MySQL... ($i/$maxTries)\n";
        sleep(1);
    }
}

if (!$success) {
    echo "[Auto-Migrate] ERROR: Could not connect to database after $maxTries attempts.\n";
    exit(0);
}

// 1. Load SQL Dump
$sqlFile = '/var/www/html/thinjupz_db.sql';
if (!file_exists($sqlFile)) {
    echo "[Auto-Migrate] Warning: SQL dump file not found at $sqlFile\n";
    exit(0);
}

// Disable FK Checks explicitly and verify
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$stmt = $pdo->query("SELECT @@FOREIGN_KEY_CHECKS");
$fkStatus = $stmt->fetchColumn();
echo "[Auto-Migrate] Foreign Key Checks status: $fkStatus (Should be 0)\n";

$sqlContent = file_get_contents($sqlFile);

// 2. Identify Tables
// Robust regex to handle 'CREATE TABLE `name`' or 'CREATE TABLE IF NOT EXISTS `name`' with varying whitespace
preg_match_all('/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`?(\w+)`?/i', $sqlContent, $matches);
$tablesInDump = array_unique($matches[1]);

echo "[Auto-Migrate] Found definition for tables: " . implode(', ', $tablesInDump) . "\n";

$retryQueue = [];

function createTable($pdo, $sqlContent, $table, &$retryQueue) {
    try {
        // Check if table exists
        $check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() > 0) {
            echo "[Auto-Migrate] Table '$table' already exists. Skipping.\n";
            return true; 
        }

        echo "[Auto-Migrate] Table '$table' is missing. extracting definition...\n";

        // Find start: CREATE TABLE `table`
        // We regex match the start to get the exact position
        if (!preg_match("/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?`$table`\s*\(?/", $sqlContent, $m, PREG_OFFSET_CAPTURE)) {
             echo "[Auto-Migrate] Error: Could not locate CREATE start for '$table'.\n";
             return false;
        }
        $startPos = $m[0][1];

        // Find end: The first ';' after startPos
        // NOTE: This assumes no ';' inside comments/strings in the CREATE block. 
        // For schema dumps this is usually doing fine.
        $endPos = strpos($sqlContent, ";", $startPos);
        if ($endPos === false) {
            echo "[Auto-Migrate] Error: Could not find end of definition for '$table'.\n";
            return false;
        }

        $createSql = substr($sqlContent, $startPos, $endPos - $startPos + 1);
        
        $pdo->exec($createSql);
        echo "[Auto-Migrate] SUCCESS: Created table '$table'.\n";
        return true;

    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // Check for Missing Table dependency errors (1215, 1824, etc)
        if (strpos($msg, '1215') !== false || strpos($msg, '1824') !== false) {
            echo "[Auto-Migrate] Dependency error for '$table'. Adding to retry queue.\n";
            $retryQueue[] = $table;
            return false;
        } else {
            echo "[Auto-Migrate] CRITICAL ERROR creating '$table': $msg\n";
            return false;
        }
    }
}

// First Pass
foreach ($tablesInDump as $table) {
    createTable($pdo, $sqlContent, $table, $retryQueue);
}

// Retry Pass (Handling dependencies)
$maxRetries = 3; // Allow 3 loops to resolve deep chains
for ($r = 1; $r <= $maxRetries; $r++) {
    if (empty($retryQueue)) break;
    
    echo "[Auto-Migrate] Retry Loop #$r for " . count($retryQueue) . " tables...\n";
    $currentQueue = $retryQueue;
    $retryQueue = []; // Clear for next pass
    
    foreach ($currentQueue as $table) {
        createTable($pdo, $sqlContent, $table, $retryQueue);
    }
}

if (!empty($retryQueue)) {
    echo "[Auto-Migrate] WARNING: Failed to create the following tables after retries: " . implode(', ', $retryQueue) . "\n";
}

// 3. Import Data (Only for tables we successfully created or exist)
// We scan for INSERTs.
foreach ($tablesInDump as $table) {
    if (in_array($table, $retryQueue)) continue; // Skip failed tables

    // Simple check if table is empty to avoid dups
    try {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        if ($count > 0) continue; // Skip data import if has data
    } catch(Exception $e) { continue; }

    echo "[Auto-Migrate] Importing data for '$table'...\n";
    
    // Determine the INSERT syntax used for this table
    // Try explicit backticks first: INSERT INTO `table`
    $searchPattern = "INSERT INTO `$table`";
    if (strpos($sqlContent, $searchPattern) === false) {
        // Fallback to no backticks: INSERT INTO table
        $searchPattern = "INSERT INTO $table";
    }
    
    // Execution Loop: Quote-aware statement extraction
    $len = strlen($sqlContent);
    $inSingleQuote = false;
    $inDoubleQuote = false;
    $escaped = false;
    
    // Find all inserts for this table
    $offset = strpos($sqlContent, $searchPattern);
    if ($offset === false) continue;

    for ($i = $offset; $i < $len; $i++) {
        $c = $sqlContent[$i];
        
        if ($escaped) {
            $escaped = false;
            continue;
        }
        
        if ($c === "\\") {
            $escaped = true;
            continue;
        }
        
        if ($c === "'" && !$inDoubleQuote) {
            $inSingleQuote = !$inSingleQuote;
        } elseif ($c === '"' && !$inSingleQuote) {
            $inDoubleQuote = !$inDoubleQuote;
        } elseif ($c === ';' && !$inSingleQuote && !$inDoubleQuote) {
            // Found a real statement terminator!
            $sql = substr($sqlContent, $offset, $i - $offset + 1);
            
            // Only execute if it matches our table pattern
            if (strpos($sql, $searchPattern) === 0) {
                try {
                    $pdo->exec($sql);
                } catch (Exception $e) {
                    // echo "[Auto-Migrate] Warning for '$table': " . $e->getMessage() . "\n";
                }
            }
            
            // Move to the next potential statement for THIS table
            $nextOffset = strpos($sqlContent, $searchPattern, $i + 1);
            if ($nextOffset === false) break;
            $i = $nextOffset - 1; 
            $offset = $nextOffset;
        }
    }
}

// 4. Default Admin Seed
try {
   $adminCheck = $pdo->query("SHOW TABLES LIKE 'admin_users'");
   if ($adminCheck->rowCount() > 0) {
       $chk = $pdo->query("SELECT COUNT(*) FROM admin_users");
       if ($chk->fetchColumn() == 0) {
           echo "[Auto-Migrate] Seeding default admin user...\n";
           $passHash = password_hash('admin123', PASSWORD_DEFAULT);
           $insert = "INSERT INTO `admin_users` (`username`, `email`, `password`, `role`) VALUES 
                      ('admin', 'admin@thinqshopping.app', '$passHash', 'superadmin')";
           $pdo->exec($insert);
       }
   }
} catch (Exception $e) {}

$pdo->exec("SET FOREIGN_KEY_CHECKS=1");
echo "[Auto-Migrate] Migration complete.\n";
?>
