<?php
/**
 * Simple Path Test - Verify where your project is located
 */

echo "<h2>Project Path Test</h2>";
echo "<p><strong>Current File Location:</strong> " . __FILE__ . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Not set') . "</p>";
echo "<p><strong>Script Name:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Not set') . "</p>";
echo "<p><strong>Request URI:</strong> " . ($_SERVER['REQUEST_URI'] ?? 'Not set') . "</p>";
echo "<p><strong>Current URL:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "</p>";

echo "<hr>";
echo "<h3>Your Project Location:</h3>";
echo "<p>Based on this file, your project is at: <strong>" . dirname(__FILE__) . "</strong></p>";

echo "<hr>";
echo "<h3>XAMPP htdocs Location:</h3>";
echo "<p>XAMPP typically serves files from:</p>";
echo "<ul>";
echo "<li><strong>Mac:</strong> <code>/Applications/XAMPP/htdocs/</code></li>";
echo "<li><strong>Windows:</strong> <code>C:\\xampp\\htdocs\\</code></li>";
echo "</ul>";

echo "<hr>";
echo "<h3>Solution:</h3>";
if (strpos(__FILE__, 'htdocs') === false) {
    echo "<p style='color: red;'><strong>⚠️ Your project is NOT in XAMPP htdocs!</strong></p>";
    echo "<p>You have two options:</p>";
    echo "<h4>Option 1: Move/Copy Project to htdocs (Recommended)</h4>";
    echo "<p><strong>Mac:</strong></p>";
    echo "<pre>sudo cp -R " . dirname(__FILE__) . " /Applications/XAMPP/htdocs/ThinQShopping</pre>";
    echo "<p><strong>Windows:</strong></p>";
    echo "<pre>Copy the ThinQShopping folder to C:\\xampp\\htdocs\\</pre>";
    echo "<h4>Option 2: Create Symbolic Link (Mac/Linux)</h4>";
    echo "<pre>sudo ln -s " . dirname(__FILE__) . " /Applications/XAMPP/htdocs/ThinQShopping</pre>";
} else {
    echo "<p style='color: green;'><strong>✅ Your project IS in XAMPP htdocs!</strong></p>";
}

echo "<hr>";
echo "<h3>After moving, access these URLs:</h3>";
echo "<ul>";
echo "<li><a href='check-sidebar-version.php'>check-sidebar-version.php</a></li>";
echo "<li><a href='check-xampp-setup.php'>check-xampp-setup.php</a></li>";
echo "<li><a href='SIDEBAR_DIAGNOSTIC.php'>SIDEBAR_DIAGNOSTIC.php</a></li>";
echo "</ul>";

?>






