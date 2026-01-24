<?php
/**
 * Check Sidebar Version - Verify which version is being served
 */

require_once __DIR__ . '/config/constants.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$_SERVER['PHP_SELF'] = 'test.php';
$_SERVER['REQUEST_URI'] = '/test';

echo "<h2>Sidebar Version Check</h2>";
echo "<p>This shows which version of the sidebar is actually being rendered.</p>";

echo "<hr>";
echo "<h3>Admin Sidebar:</h3>";
$_SESSION['admin_id'] = 1;
$_SESSION['admin_username'] = 'admin';

ob_start();
include __DIR__ . '/includes/admin-sidebar.php';
$output = ob_get_clean();

// Check for version comment
if (preg_match('/DEBUG: Sidebar version ([\d.]+)/', $output, $matches)) {
    echo "<p><strong>Version Found:</strong> " . $matches[1] . "</p>";
    if (version_compare($matches[1], '2.1', '>=')) {
        echo "<p style='color: green;'>✅ Latest version (2.1+) - Menu items should be present</p>";
    } else {
        echo "<p style='color: red;'>❌ Old version - Menu items missing</p>";
    }
} else {
    echo "<p style='color: red;'>❌ No version comment found - This is the OLD cached version!</p>";
}

// Check for menu items
$hasTickets = strpos($output, 'Ticket Management') !== false;
$hasEmailSettings = strpos($output, 'Email Settings') !== false;
$hasEmailTemplates = strpos($output, 'Email Templates') !== false;

echo "<p>Ticket Management: " . ($hasTickets ? '<span style="color: green;">✅ FOUND</span>' : '<span style="color: red;">❌ NOT FOUND</span>') . "</p>";
echo "<p>Email Settings: " . ($hasEmailSettings ? '<span style="color: green;">✅ FOUND</span>' : '<span style="color: red;">❌ NOT FOUND</span>') . "</p>";
echo "<p>Email Templates: " . ($hasEmailTemplates ? '<span style="color: green;">✅ FOUND</span>' : '<span style="color: red;">❌ NOT FOUND</span>') . "</p>";

if (!$hasTickets || !$hasEmailSettings || !$hasEmailTemplates) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ MENU ITEMS MISSING - OpCache is serving old version!</p>";
    echo "<p><strong>Action Required:</strong> Restart your web server!</p>";
}

echo "<hr>";
echo "<h3>User Sidebar:</h3>";
$_SESSION['user_id'] = 1;

ob_start();
include __DIR__ . '/includes/user-sidebar.php';
$userOutput = ob_get_clean();

if (preg_match('/DEBUG: Sidebar version ([\d.]+)/', $userOutput, $matches)) {
    echo "<p><strong>Version Found:</strong> " . $matches[1] . "</p>";
    if (version_compare($matches[1], '2.1', '>=')) {
        echo "<p style='color: green;'>✅ Latest version (2.1+)</p>";
    }
}

$hasUserTickets = strpos($userOutput, 'Support Tickets') !== false;
echo "<p>Support Tickets: " . ($hasUserTickets ? '<span style="color: green;">✅ FOUND</span>' : '<span style="color: red;">❌ NOT FOUND</span>') . "</p>";

if (!$hasUserTickets) {
    echo "<p style='color: red; font-weight: bold;'>⚠️ MENU ITEM MISSING - OpCache is serving old version!</p>";
    echo "<p><strong>Action Required:</strong> Restart your web server!</p>";
}

echo "<hr>";
echo "<h3>File Modification Times:</h3>";
$files = [
    'includes/admin-sidebar.php',
    'includes/user-sidebar.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $mtime = filemtime($path);
        echo "<p>$file: " . date('Y-m-d H:i:s', $mtime) . "</p>";
    }
}

echo "<hr>";
echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>If version shows 2.1+ and items are FOUND: Menu items should be visible</li>";
echo "<li>If version is old or items are NOT FOUND: <strong>RESTART YOUR WEB SERVER</strong></li>";
echo "<li>After restart, clear browser cache and refresh</li>";
echo "</ol>";

?>






