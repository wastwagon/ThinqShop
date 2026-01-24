<?php
/**
 * Test Tickets Page - Debug Version
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Testing Tickets Page</h1>";

// Test 1: Check if constants file exists
echo "<h2>Test 1: Constants File</h2>";
$constantsFile = __DIR__ . '/config/constants.php';
if (file_exists($constantsFile)) {
    echo "<p style='color:green'>✅ Constants file exists</p>";
    require_once $constantsFile;
    echo "<p>BASE_URL: " . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "</p>";
    echo "<p>APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NOT DEFINED') . "</p>";
} else {
    echo "<p style='color:red'>❌ Constants file NOT found at: $constantsFile</p>";
    die();
}

// Test 2: Check session
echo "<h2>Test 2: Session</h2>";
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME ?? 'thinQ_session');
    session_start();
}
echo "<p>Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "</p>";

// Test 3: Check admin auth
echo "<h2>Test 3: Admin Auth Check</h2>";
$authFile = __DIR__ . '/includes/admin-auth-check.php';
if (file_exists($authFile)) {
    echo "<p style='color:green'>✅ Auth check file exists</p>";
    // Don't require it yet, just check
} else {
    echo "<p style='color:red'>❌ Auth check file NOT found at: $authFile</p>";
}

// Test 4: Check database
echo "<h2>Test 4: Database</h2>";
$dbFile = __DIR__ . '/config/database.php';
if (file_exists($dbFile)) {
    echo "<p style='color:green'>✅ Database file exists</p>";
    try {
        require_once $dbFile;
        $db = new Database();
        $conn = $db->getConnection();
        echo "<p style='color:green'>✅ Database connection successful</p>";
        
        // Test tickets table
        $stmt = $conn->query("SHOW TABLES LIKE 'tickets'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>✅ Tickets table exists</p>";
        } else {
            echo "<p style='color:red'>❌ Tickets table does NOT exist</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ Database file NOT found at: $dbFile</p>";
}

// Test 5: Check functions
echo "<h2>Test 5: Functions</h2>";
$functionsFile = __DIR__ . '/includes/functions.php';
if (file_exists($functionsFile)) {
    echo "<p style='color:green'>✅ Functions file exists</p>";
    require_once $functionsFile;
    echo "<p>sanitize function: " . (function_exists('sanitize') ? 'EXISTS' : 'NOT FOUND') . "</p>";
} else {
    echo "<p style='color:red'>❌ Functions file NOT found at: $functionsFile</p>";
}

// Test 6: Check layout file
echo "<h2>Test 6: Layout File</h2>";
$layoutFile = __DIR__ . '/includes/layouts/admin-layout.php';
if (file_exists($layoutFile)) {
    echo "<p style='color:green'>✅ Layout file exists</p>";
} else {
    echo "<p style='color:red'>❌ Layout file NOT found at: $layoutFile</p>";
}

// Test 7: Check sidebar and header
echo "<h2>Test 7: Sidebar and Header</h2>";
$sidebarFile = __DIR__ . '/includes/admin-sidebar.php';
$headerFile = __DIR__ . '/includes/admin-header.php';
echo "<p>Sidebar: " . (file_exists($sidebarFile) ? '<span style="color:green">✅ EXISTS</span>' : '<span style="color:red">❌ NOT FOUND</span>') . "</p>";
echo "<p>Header: " . (file_exists($headerFile) ? '<span style="color:green">✅ EXISTS</span>' : '<span style="color:red">❌ NOT FOUND</span>') . "</p>";

// Test 8: Try to include the actual tickets page
echo "<h2>Test 8: Try Including Tickets Page</h2>";
$ticketsFile = __DIR__ . '/admin/tickets/index.php';
if (file_exists($ticketsFile)) {
    echo "<p style='color:green'>✅ Tickets page file exists</p>";
    echo "<p>Attempting to include (this may show errors)...</p>";
    echo "<hr>";
    
    // Set up session for testing
    $_SESSION['admin_id'] = 1;
    $_SESSION['admin_username'] = 'admin';
    
    // Try to capture output
    ob_start();
    try {
        include $ticketsFile;
        $output = ob_get_clean();
        if (!empty($output)) {
            echo "<p style='color:green'>✅ Page output generated (length: " . strlen($output) . " bytes)</p>";
            echo "<details><summary>View Output (first 500 chars)</summary><pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre></details>";
        } else {
            echo "<p style='color:red'>❌ Page output is EMPTY - This is the problem!</p>";
        }
    } catch (Exception $e) {
        ob_end_clean();
        echo "<p style='color:red'>❌ Error including page: " . $e->getMessage() . "</p>";
        echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
    } catch (Error $e) {
        ob_end_clean();
        echo "<p style='color:red'>❌ Fatal error: " . $e->getMessage() . "</p>";
        echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p style='color:red'>❌ Tickets page file NOT found at: $ticketsFile</p>";
}

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p>If all tests pass but page is still blank, check:</p>";
echo "<ul>";
echo "<li>PHP error logs in XAMPP</li>";
echo "<li>Browser console for JavaScript errors</li>";
echo "<li>Check if output buffering is interfering</li>";
echo "</ul>";
?>





