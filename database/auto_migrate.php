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

// 1. Fix Missing admin_users table
echo "[Auto-Migrate] Checking 'admin_users' table...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'admin_users'");
    if ($stmt->rowCount() == 0) {
        echo "[Auto-Migrate] 'admin_users' table missing. Creating...\n";
        $sql = "CREATE TABLE IF NOT EXISTS `admin_users` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(50) NOT NULL,
          `email` varchar(100) NOT NULL,
          `password` varchar(255) NOT NULL,
          `role` varchar(20) DEFAULT 'admin',
          `is_active` tinyint(1) DEFAULT 1,
          `last_login` datetime DEFAULT NULL,
          `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
          `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `username` (`username`),
          UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $pdo->exec($sql);
        
        // Insert default admin
        $passHash = password_hash('admin123', PASSWORD_DEFAULT);
        $insert = "INSERT INTO `admin_users` (`username`, `email`, `password`, `role`) VALUES 
                   ('admin', 'admin@thinqshopping.app', '$passHash', 'superadmin')";
        $pdo->exec($insert);
        echo "[Auto-Migrate] 'admin_users' table created and default admin added.\n";
    } else {
        echo "[Auto-Migrate] 'admin_users' table already exists. Skipping.\n";
    }
} catch (PDOException $e) {
    echo "[Auto-Migrate] Error checking/creating admin_users: " . $e->getMessage() . "\n";
}

echo "[Auto-Migrate] Migration checks complete.\n";
?>
