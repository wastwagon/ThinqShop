<?php
/**
 * Clear OpCache NOW - Emergency Cache Clear
 */

// Force clear opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OpCache cleared!<br>";
} else {
    echo "⚠️ OpCache not available<br>";
}

// Invalidate specific files
$files = [
    __DIR__ . '/includes/admin-sidebar.php',
    __DIR__ . '/includes/user-sidebar.php',
    __DIR__ . '/admin-sidebar.php',
    __DIR__ . '/user-sidebar.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file, true);
            echo "✅ Invalidated: " . basename($file) . "<br>";
        }
        // Touch file to update modification time
        touch($file);
        echo "✅ Touched: " . basename($file) . " (Modified: " . date('Y-m-d H:i:s', filemtime($file)) . ")<br>";
    }
}

// Clear stat cache
clearstatcache(true);

echo "<hr>";
echo "<h2>✅ All caches cleared!</h2>";
echo "<p><strong>IMPORTANT:</strong> You MUST restart your web server now!</p>";
echo "<p>If using XAMPP: Stop Apache, then Start Apache again</p>";
echo "<p>If using cPanel: Restart PHP-FPM or Apache</p>";
echo "<p>After restart, clear browser cache (Ctrl+Shift+R) and refresh your dashboard.</p>";

?>






