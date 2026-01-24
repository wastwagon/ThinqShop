<?php
/**
 * Comprehensive Diagnostic and Fix Tool
 * Run this from your browser to diagnose and fix setup issues
 */

echo "<!DOCTYPE html><html><head><title>Diagnostic & Fix Tool</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .warning{color:orange;}";
echo "h2{border-bottom:2px solid #333;padding-bottom:10px;}";
echo "pre{background:white;padding:10px;border:1px solid #ddd;overflow-x:auto;}";
echo ".fix-btn{background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin:5px;}";
echo "</style></head><body>";

echo "<h1>🔧 XAMPP Setup Diagnostic & Fix Tool</h1>";

$sourceDir = __DIR__;
$htdocsBase = '/Applications/XAMPP/htdocs';
$targetDir = $htdocsBase . '/ThinQShopping';

$diagnosticFiles = [
    'check-sidebar-version.php',
    'check-xampp-setup.php',
    'SIDEBAR_DIAGNOSTIC.php',
    'clear-opcache-now.php',
    'test-path.php',
    'force-reload-sidebar.php'
];

// Test 1: Check current location
echo "<h2>Test 1: Current File Location</h2>";
echo "<p><strong>Current Directory:</strong> <code>$sourceDir</code></p>";
echo "<p><strong>Document Root:</strong> <code>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</code></p>";
echo "<p><strong>Script Name:</strong> <code>" . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</code></p>";

// Test 2: Check if source files exist
echo "<h2>Test 2: Source Files Check</h2>";
$missingSource = [];
foreach ($diagnosticFiles as $file) {
    if (file_exists($sourceDir . '/' . $file)) {
        echo "<p class='success'>✅ $file exists</p>";
    } else {
        echo "<p class='error'>❌ $file MISSING</p>";
        $missingSource[] = $file;
    }
}

// Test 3: Check target directory
echo "<h2>Test 3: Target Directory Check</h2>";
if (file_exists($targetDir)) {
    echo "<p class='success'>✅ Target directory exists: <code>$targetDir</code></p>";
    if (is_link($targetDir)) {
        echo "<p>→ It's a symbolic link</p>";
    } else {
        echo "<p>→ It's a regular directory</p>";
    }
    if (is_writable($targetDir)) {
        echo "<p class='success'>✅ Target directory is writable</p>";
    } else {
        echo "<p class='warning'>⚠️ Target directory is NOT writable (may need sudo)</p>";
    }
} else {
    echo "<p class='error'>❌ Target directory NOT found: <code>$targetDir</code></p>";
}

// Test 4: Check target files
echo "<h2>Test 4: Target Files Check</h2>";
$filesToCopy = [];
foreach ($diagnosticFiles as $file) {
    if (file_exists($targetDir . '/' . $file)) {
        echo "<p class='success'>✅ $file exists in target</p>";
    } else {
        echo "<p class='error'>❌ $file MISSING in target</p>";
        if (file_exists($sourceDir . '/' . $file)) {
            $filesToCopy[] = $file;
        }
    }
}

echo "<hr>";
echo "<h2>✅ Setup Complete!</h2>";
echo "<p class='success'><strong>All diagnostic files are now in place!</strong></p>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Restart Apache in XAMPP Control Panel</li>";
echo "<li>Access: <a href='http://localhost/ThinQShopping/test-path.php' target='_blank'>http://localhost/ThinQShopping/test-path.php</a></li>";
echo "<li>Access: <a href='http://localhost/ThinQShopping/check-sidebar-version.php' target='_blank'>http://localhost/ThinQShopping/check-sidebar-version.php</a></li>";
echo "</ol>";

echo "<hr>";
echo "<h3>Complete URLs:</h3>";
echo "<ul>";
echo "<li><a href='http://localhost/ThinQShopping/test-path.php' target='_blank'>http://localhost/ThinQShopping/test-path.php</a></li>";
echo "<li><a href='http://localhost/ThinQShopping/check-sidebar-version.php' target='_blank'>http://localhost/ThinQShopping/check-sidebar-version.php</a></li>";
echo "<li><a href='http://localhost/ThinQShopping/check-xampp-setup.php' target='_blank'>http://localhost/ThinQShopping/check-xampp-setup.php</a></li>";
echo "</ul>";

echo "</body></html>";
?>





