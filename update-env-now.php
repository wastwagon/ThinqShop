<?php
/**
 * Quick .env Update Script
 */

$envFile = __DIR__ . '/.env';

if (!file_exists($envFile)) {
    die("Error: .env file not found!");
}

// Read current .env file
$lines = file($envFile, FILE_IGNORE_NEW_LINES);

$updated = [];
foreach ($lines as $line) {
    // Update database credentials
    if (preg_match('/^DB_NAME=/', $line)) {
        $updated[] = 'DB_NAME=thinjupz_db';
    } elseif (preg_match('/^DB_USER=/', $line)) {
        $updated[] = 'DB_USER=thinjupz_user';
    } elseif (preg_match('/^DB_PASS=/', $line)) {
        $updated[] = 'DB_PASS=password';
    } elseif (preg_match('/^APP_URL=/', $line)) {
        $updated[] = 'APP_URL="https://thinqshopping.app"';
    } elseif (preg_match('/^APP_ENV=/', $line)) {
        $updated[] = 'APP_ENV="production"';
    } elseif (preg_match('/^APP_DEBUG=/', $line)) {
        $updated[] = 'APP_DEBUG="false"';
    } else {
        $updated[] = $line;
    }
}

// Write back to file
file_put_contents($envFile, implode("\n", $updated));

echo "<h2>✓ .env File Updated Successfully!</h2>";
echo "<p><strong>Database Name:</strong> thinjupz_db</p>";
echo "<p><strong>Database User:</strong> thinjupz_user</p>";
echo "<p><strong>Database Password:</strong> password</p>";
echo "<p><strong>App URL:</strong> https://thinqshopping.app</p>";
echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Delete this file (update-env-now.php) for security</li>";
echo "<li>Refresh your website: <a href='https://thinqshopping.app' target='_blank'>https://thinqshopping.app</a></li>";
echo "</ol>";

// Test connection
echo "<hr><h3>Testing Database Connection...</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "<p style='color: green; font-weight: bold;'>✓ SUCCESS! Database connection working!</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Connection failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please verify in cPanel that:</p>";
    echo "<ul>";
    echo "<li>The user 'thinjupz_user' is added to database 'thinjupz_db'</li>";
    echo "<li>The user has ALL PRIVILEGES</li>";
    echo "</ul>";
}

