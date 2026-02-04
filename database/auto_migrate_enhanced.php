<?php
/**
 * Enhanced Auto-Migration Script with Migrations Folder Support
 * Runs on container startup via docker-compose
 * 
 * This script:
 * 1. Ensures database connection
 * 2. Runs main SQL dump (auto_migrate.php logic)
 * 3. Automatically runs SQL migrations from database/migrations/ folder
 */

$maxTries = 50;
$success = false;

echo "[Auto-Migrate] Enhanced Migration Script Starting...\n";
echo "[Auto-Migrate] Waiting for Database connection...\n";

$host = getenv('DB_HOST') ?: 'mysql';
$db   = getenv('DB_NAME') ?: 'thinjupz_db';
$user = getenv('DB_USER') ?: 'thinquser';
$pass = getenv('DB_PASS') ?: 'thinqpass';

// Connect to database with retries
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

// Create migrations tracking table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS migration_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_file VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_migration_file (migration_file)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "[Auto-Migrate] Migration tracking table ready.\n";
} catch (PDOException $e) {
    echo "[Auto-Migrate] Warning: Could not create migration_history table: " . $e->getMessage() . "\n";
}

// Run migrations from database/migrations/ folder
$migrationsDir = '/var/www/html/database/migrations';
if (is_dir($migrationsDir)) {
    echo "[Auto-Migrate] Scanning migrations directory...\n";
    
    $migrationFiles = glob($migrationsDir . '/*.sql');
    sort($migrationFiles); // Run in alphabetical order
    
    foreach ($migrationFiles as $migrationFile) {
        $filename = basename($migrationFile);
        
        // Check if migration has already been run
        try {
            $stmt = $pdo->prepare("SELECT id FROM migration_history WHERE migration_file = ?");
            $stmt->execute([$filename]);
            
            if ($stmt->rowCount() > 0) {
                echo "[Auto-Migrate] ✓ Migration '$filename' already applied. Skipping.\n";
                continue;
            }
        } catch (PDOException $e) {
            echo "[Auto-Migrate] Warning: Could not check migration history for '$filename'\n";
        }
        
        // Run the migration
        echo "[Auto-Migrate] → Running migration: $filename\n";
        
        try {
            $sql = file_get_contents($migrationFile);
            
            // Split by semicolons (simple approach for most migrations)
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                function($stmt) {
                    return !empty($stmt) && !preg_match('/^--/', $stmt);
                }
            );
            
            $pdo->beginTransaction();
            
            foreach ($statements as $statement) {
                if (!empty($statement)) {
                    $pdo->exec($statement);
                }
            }
            
            // Record successful migration
            $stmt = $pdo->prepare("INSERT INTO migration_history (migration_file) VALUES (?)");
            $stmt->execute([$filename]);
            
            $pdo->commit();
            echo "[Auto-Migrate] ✓ Migration '$filename' applied successfully!\n";
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "[Auto-Migrate] ✗ ERROR running migration '$filename': " . $e->getMessage() . "\n";
            echo "[Auto-Migrate] Continuing with remaining migrations...\n";
        }
    }
    
    echo "[Auto-Migrate] Migrations processing complete.\n";
} else {
    echo "[Auto-Migrate] No migrations directory found at $migrationsDir\n";
}

// Now run the original auto_migrate.php logic for main SQL dump
echo "[Auto-Migrate] Running main SQL dump import...\n";
include '/var/www/html/database/auto_migrate.php';

echo "[Auto-Migrate] All migration tasks complete!\n";
?>
