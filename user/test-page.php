<?php
/**
 * Test Page for Debugging
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "1. Starting test...<br>";

// Check if files exist
echo "Current __DIR__: " . __DIR__ . "<br>";
echo "Expected base: /Applications/XAMPP/xamppfiles/htdocs/ThinQShopping<br>";

$files = [
    'auth-check' => __DIR__ . '/../includes/auth-check.php',
    'database' => __DIR__ . '/../config/database.php',
    'functions' => __DIR__ . '/../includes/functions.php',
    'layout' => __DIR__ . '/../includes/layouts/user-layout.php',
];

foreach ($files as $name => $path) {
    if (file_exists($path)) {
        echo "2. ✅ $name exists<br>";
    } else {
        echo "2. ❌ $name NOT FOUND: $path<br>";
    }
}

// Try to require auth-check
try {
    require_once __DIR__ . '/../includes/auth-check.php';
    echo "3. ✅ auth-check loaded<br>";
    echo "4. Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
} catch (Exception $e) {
    echo "3. ❌ Error loading auth-check: " . $e->getMessage() . "<br>";
}

// Try database
try {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    echo "5. ✅ Database connection successful<br>";
} catch (Exception $e) {
    echo "5. ❌ Database error: " . $e->getMessage() . "<br>";
}

echo "6. Test complete!";
?>
