<?php
/**
 * Auto-Migration Script
 * Runs on container startup to ensure database schema is correct.
 */

// Wait for MySQL to be ready
$maxTries = 50; // Wait up to 50 seconds (MySQL container takes time to start)
$success = false;

echo "[Auto-Migrate] Waiting for Database connection...\n";

// Manual connection params because env-loader might not be available in cli context properly if not careful, 
// but we will try to use the configured classes if possible, or raw PDO.
$host = getenv('DB_HOST') ?: 'mysql';
$db   = getenv('DB_NAME') ?: 'thinjupz_db';
$user = getenv('DB_USER') ?: 'thinquser';
$pass = getenv('DB_PASS') ?: 'thinqpass';

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
    // We don't exit/die here because we don't want to crash the container loop if it's just a transient issue, 
    // but effectively we can't migrate.
    exit(0);
}

// 1. Load SQL Dump
$sqlFile = '/var/www/html/thinjupz_db.sql';
if (!file_exists($sqlFile)) {
    echo "[Auto-Migrate] Warning: SQL dump file not found at $sqlFile\n";
    exit(0);
}

// Read entire file (for 10MB it's fine, for larger need streams but this is sufficient)
$sqlContent = file_get_contents($sqlFile);

// Find all CREATE TABLE statements to identify what tables we SHOULD have
preg_match_all('/CREATE TABLE `(\w+)`/', $sqlContent, $matches);
$tablesInDump = array_unique($matches[1]);

echo "[Auto-Migrate] Found definition for tables: " . implode(', ', $tablesInDump) . "\n";

foreach ($tablesInDump as $table) {
    try {
        // Check if table exists in DB
        $check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() > 0) {
            echo "[Auto-Migrate] Table '$table' already exists. Checking next.\n";
            continue;
        }

        echo "[Auto-Migrate] Table '$table' is missing. Validating dump content...\n";

        // Find the start of CREATE TABLE statement
        $searchStr = "CREATE TABLE `$table`";
        $startPos = strpos($sqlContent, $searchStr);
        
        if ($startPos === false) {
             echo "[Auto-Migrate] Error: Could not find definition for '$table'.\n";
             continue;
        }

        // Find the end of the statement (semicolon)
        // We look for the first semicolon AFTER the start position
        $endPos = strpos($sqlContent, ";", $startPos);
        
        if ($endPos === false) {
            echo "[Auto-Migrate] Error: Could not find end of definition for '$table'.\n";
            continue;
        }

        // Extract the CREATE SQL
        $createSql = substr($sqlContent, $startPos, $endPos - $startPos + 1);
        
        // Execute CREATE
        $pdo->exec($createSql);
        echo "[Auto-Migrate] SUCCESS: Created table '$table'.\n";


        // Now handle INSERTs
        // We look for "INSERT INTO `$table`"
        // Since there might be multiple INSERT statements, we loop
        $offset = 0;
        $insertCount = 0;
        while (($insertStart = strpos($sqlContent, "INSERT INTO `$table`", $offset)) !== false) {
            // Find end of this INSERT statement
            $insertEnd = strpos($sqlContent, ";", $insertStart);
            if ($insertEnd === false) break;

            $insertSql = substr($sqlContent, $insertStart, $insertEnd - $insertStart + 1);
            
            try {
                $pdo->exec($insertSql);
                $insertCount++;
            } catch (PDOException $e) {
                 // Duplicate entry or other error? Just log
                 echo "[Auto-Migrate] Insert error for '$table': " . $e->getMessage() . "\n";
            }
            
            $offset = $insertEnd + 1;
        }
        
        if ($insertCount > 0) {
            echo "[Auto-Migrate] Imported $insertCount data batches for '$table'.\n";
        }

    } catch (PDOException $e) {
        echo "[Auto-Migrate] CRITICAL ERROR processing '$table': " . $e->getMessage() . "\n";
    }
}


// 3. Special check for admin_users (since it might have been created manually or by previous script but missing data)
// If admin_users exists but is empty, seed it.
try {
   $chk = $pdo->query("SELECT COUNT(*) FROM admin_users");
   if ($chk->fetchColumn() == 0) {
       echo "[Auto-Migrate] Seeding default admin user...\n";
       $passHash = password_hash('admin123', PASSWORD_DEFAULT);
       $insert = "INSERT INTO `admin_users` (`username`, `email`, `password`, `role`) VALUES 
                  ('admin', 'admin@thinqshopping.app', '$passHash', 'superadmin')";
       $pdo->exec($insert);
   }
} catch (Exception $e) { /* Ignore if table doesn't exist yet (handled above) */ }

echo "[Auto-Migrate] Migration checks complete.\n";
exit(0);
?>
