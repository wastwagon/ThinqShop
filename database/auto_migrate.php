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

$sqlContent = file_get_contents($sqlFile);

// 2. Extract Table definitions and Data
// This is a simple regex parser for standard mysqldump output
// It assumes specific formatting: "CREATE TABLE `name`" and "INSERT INTO `name`"

// Find all CREATE TABLE statements to identify tables
preg_match_all('/CREATE TABLE `(\w+)`/', $sqlContent, $matches);
$tablesInDump = array_unique($matches[1]);

echo "[Auto-Migrate] Found tables in dump: " . implode(', ', $tablesInDump) . "\n";

foreach ($tablesInDump as $table) {
    try {
        // Check if table exists
        $check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() > 0) {
            echo "[Auto-Migrate] Table '$table' already exists. Skipping.\n";
            continue;
        }

        echo "[Auto-Migrate] Table '$table' missing. Restoring...\n";

        // Extract CREATE TABLE statement
        // Look for "DROP TABLE IF EXISTS `table`;" followed by "CREATE TABLE `table` ... ;"
        // We use a regex that captures from CREATE up to the closing semicolon matching the creation block
        // Note: This regex is sensitive to formatting.
        
        $pattern = "/CREATE TABLE `$table` \(.*?\)( ENGINE=.*?)?;/s";
        if (preg_match($pattern, $sqlContent, $createMatch)) {
            $createSql = $createMatch[0];
            $pdo->exec($createSql);
            echo "[Auto-Migrate] Created table '$table'.\n";
        } else {
             echo "[Auto-Migrate] Warning: Could not parse CREATE statement for '$table'.\n";
             continue;
        }

        // Extract INSERT statements
        // Look for "INSERT INTO `$table` VALUES ..."
        // Note: mysqldump often puts all data for a table in one huge INSERT or multiple.
        // We find all occurrences.
        
        $insertPattern = "/INSERT INTO `$table` VALUES .*?;/s";
        if (preg_match_all($insertPattern, $sqlContent, $insertMatches)) {
            foreach ($insertMatches[0] as $insertSql) {
                // Execute insert. Since table is fresh, no conflict.
                // We might need to split extremely long lines if PDO complains, but for <10MB it's usually fine.
                try {
                    $pdo->exec($insertSql);
                } catch (PDOException $e) {
                     echo "[Auto-Migrate] Error inserting data for '$table': " . $e->getMessage() . "\n";
                }
            }
            echo "[Auto-Migrate] Imported data for '$table'.\n";
        }

    } catch (PDOException $e) {
        echo "[Auto-Migrate] Error processing '$table': " . $e->getMessage() . "\n";
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
