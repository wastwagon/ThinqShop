<?php
/**
 * Restart Apache and Fix Sidebar Files
 */

echo "<h2>Apache Restart and Fix Script</h2>";

// Check if running as root or with sudo
$isRoot = (posix_geteuid() == 0);

echo "<h3>1. Restarting Apache...</h3>";

if ($isRoot || function_exists('exec')) {
    echo "<p>Attempting to restart Apache...</p>";
    
    // Stop Apache
    $commands = [
        'sudo /Applications/XAMPP/xamppfiles/bin/apachectl stop',
        'sleep 2',
        'sudo /Applications/XAMPP/xamppfiles/bin/apachectl start'
    ];
    
    foreach ($commands as $cmd) {
        if (function_exists('exec')) {
            exec($cmd . ' 2>&1', $output, $return);
            if ($return === 0) {
                echo "<p style='color: green;'>✅ Executed: " . htmlspecialchars($cmd) . "</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Command may need manual execution: " . htmlspecialchars($cmd) . "</p>";
            }
        }
    }
    
    echo "<p><strong>Note:</strong> If Apache didn't restart automatically, please restart it manually in XAMPP Control Panel.</p>";
} else {
    echo "<p style='color: orange;'>⚠️ Cannot restart Apache automatically. Please restart manually in XAMPP Control Panel.</p>";
}

echo "<h3>2. Checking Root-Level Sidebar Files...</h3>";

// Check and copy root-level sidebar files if needed
$sourceDir = __DIR__;
$rootFiles = [
    'admin-sidebar.php',
    'user-sidebar.php'
];

foreach ($rootFiles as $file) {
    $targetPath = $sourceDir . '/' . $file;
    $sourcePath = $sourceDir . '/../ThinQShopping/' . $file;
    
    // Try to find in Downloads folder
    $downloadsPath = '/Users/OceanCyber/Downloads/ThinQShopping/' . $file;
    
    if (file_exists($downloadsPath)) {
        if (!file_exists($targetPath)) {
            if (@copy($downloadsPath, $targetPath)) {
                echo "<p style='color: green;'>✅ Created: $file</p>";
                @chmod($targetPath, 0644);
            } else {
                echo "<p style='color: red;'>❌ Failed to create: $file (may need manual copy)</p>";
            }
        } else {
            // File exists, check if it has menu items
            $content = file_get_contents($targetPath);
            if ($file === 'admin-sidebar.php') {
                $hasItems = strpos($content, 'Ticket Management') !== false;
            } else {
                $hasItems = strpos($content, 'Support Tickets') !== false;
            }
            
            if (!$hasItems && file_exists($downloadsPath)) {
                if (@copy($downloadsPath, $targetPath)) {
                    echo "<p style='color: green;'>✅ Updated: $file</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ $file exists but may need manual update</p>";
                }
            } else {
                echo "<p style='color: green;'>✅ $file exists and is up to date</p>";
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Source file not found: $file</p>";
    }
}

echo "<h3>3. Clear OpCache (if available)...</h3>";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "<p style='color: green;'>✅ OpCache cleared</p>";
} else {
    echo "<p>OpCache not available</p>";
}

echo "<hr>";
echo "<h3>✅ Done!</h3>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>If Apache didn't restart automatically, restart it manually in XAMPP Control Panel</li>";
echo "<li>Clear browser cache (Ctrl+Shift+R or Cmd+Shift+R)</li>";
echo "<li>Refresh your dashboard</li>";
echo "</ol>";

echo "<p><a href='check-sidebar-version.php'>Check Sidebar Version</a></p>";

?>





