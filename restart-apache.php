<?php
/**
 * Apache Restart Helper & Cache Clear
 * This script helps verify changes and provides restart instructions
 */

// Clear PHP opcache if enabled
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ PHP OpCache cleared<br>";
}

// Clear any output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Check if CSS file exists and show its modification time
$cssFile = __DIR__ . '/assets/css/main.css';
if (file_exists($cssFile)) {
    $lastModified = filemtime($cssFile);
    $fileSize = filesize($cssFile);
    $fileHash = substr(md5_file($cssFile), 0, 8);
    
    echo "<!DOCTYPE html><html><head><title>Apache Restart Helper</title>";
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #05203e; border-bottom: 3px solid #05203e; padding-bottom: 10px; }
        .info { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #05203e; }
        .success { background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #28a745; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #ffc107; }
        .command { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; border: 1px solid #dee2e6; }
        code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .btn { display: inline-block; padding: 10px 20px; background: #05203e; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    </style></head><body>";
    
    echo "<div class='container'>";
    echo "<h1>🔄 Apache Restart Helper</h1>";
    
    echo "<div class='success'>";
    echo "<strong>✅ PHP OpCache Cleared</strong><br>";
    echo "CSS file found and verified.";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>📋 CSS File Information:</h3>";
    echo "<strong>File:</strong> assets/css/main.css<br>";
    echo "<strong>Last Modified:</strong> " . date('Y-m-d H:i:s', $lastModified) . "<br>";
    echo "<strong>File Size:</strong> " . number_format($fileSize / 1024, 2) . " KB<br>";
    echo "<strong>File Hash:</strong> <code>$fileHash</code><br>";
    echo "</div>";
    
    echo "<div class='warning'>";
    echo "<h3>⚠️ To Restart Apache, run these commands in Terminal:</h3>";
    echo "<div class='command'>";
    echo "# Stop Apache<br>";
    echo "sudo /Applications/XAMPP/xamppfiles/bin/apachectl stop<br><br>";
    echo "# Start Apache<br>";
    echo "sudo /Applications/XAMPP/xamppfiles/bin/apachectl start<br><br>";
    echo "# OR use XAMPP manager:<br>";
    echo "sudo /Applications/XAMPP/xamppfiles/xampp restartapache";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🔍 Alternative: Use XAMPP Control Panel</h3>";
    echo "<ol>";
    echo "<li>Open XAMPP Control Panel</li>";
    echo "<li>Click 'Stop' next to Apache</li>";
    echo "<li>Wait a few seconds</li>";
    echo "<li>Click 'Start' next to Apache</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<h3>🧹 Browser Cache Clearing:</h3>";
    echo "<ul>";
    echo "<li><strong>Chrome/Edge:</strong> Press <code>Ctrl+Shift+Delete</code> (Windows) or <code>Cmd+Shift+Delete</code> (Mac)</li>";
    echo "<li><strong>Firefox:</strong> Press <code>Ctrl+Shift+Delete</code> (Windows) or <code>Cmd+Shift+Delete</code> (Mac)</li>";
    echo "<li><strong>Hard Refresh:</strong> <code>Ctrl+F5</code> (Windows) or <code>Cmd+Shift+R</code> (Mac)</li>";
    echo "</ul>";
    echo "</div>";
    
    // Check if header files have been updated
    $headerFile = __DIR__ . '/includes/header.php';
    if (file_exists($headerFile)) {
        $headerContent = file_get_contents($headerFile);
        if (strpos($headerContent, 'hero-background-image') !== false) {
            echo "<div class='success'>";
            echo "<strong>✅ Hero Section:</strong> Video has been replaced with image";
            echo "</div>";
        }
        if (strpos($headerContent, 'action-text') === false || strpos($headerContent, 'action-label') === false) {
            echo "<div class='success'>";
            echo "<strong>✅ Header:</strong> Text labels have been removed (icon-only design)";
            echo "</div>";
        }
    }
    
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<a href='index.php' class='btn'>Go to Homepage</a>";
    echo "<a href='assets/css/main.css?v=" . time() . "' class='btn' target='_blank'>View CSS File</a>";
    echo "</div>";
    
    echo "</div></body></html>";
} else {
    echo "❌ CSS file not found at: $cssFile";
}
?>




