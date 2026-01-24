<?php
/**
 * XAMPP Setup Check
 * Verify local server configuration
 */

echo "<!DOCTYPE html><html><head><title>XAMPP Setup Check</title>";
echo "<style>body{font-family:monospace;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;font-weight:bold;} .error{color:red;font-weight:bold;} .info{color:blue;}";
echo "h2{border-bottom:2px solid #333;padding-bottom:10px;}";
echo "pre{background:white;padding:10px;border:1px solid #ddd;}";
echo "</style></head><body>";

echo "<h1>XAMPP Local Server Setup Check</h1>";

// Check PHP version
echo "<h2>1. PHP Configuration</h2>";
echo "<p>PHP Version: <strong>" . PHP_VERSION . "</strong></p>";
echo "<p>Server Software: <strong>" . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</strong></p>";
echo "<p>Document Root: <strong>" . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</strong></p>";
echo "<p>Script Filename: <strong>" . ($_SERVER['SCRIPT_FILENAME'] ?? 'Unknown') . "</strong></p>";

// Check OpCache
echo "<h2>2. OpCache Status</h2>";
if (function_exists('opcache_get_status')) {
    $status = opcache_get_status(false);
    if ($status && $status['opcache_enabled']) {
        echo "<p class='info'>OpCache is <strong>ENABLED</strong></p>";
        echo "<p>OpCache Memory Used: " . round($status['memory_usage']['used_memory'] / 1024 / 1024, 2) . " MB</p>";
        echo "<p>OpCache Hits: " . ($status['opcache_statistics']['hits'] ?? 0) . "</p>";
        echo "<p>OpCache Misses: " . ($status['opcache_statistics']['misses'] ?? 0) . "</p>";
        
        // Check if our sidebar files are cached
        $sidebarFiles = [
            __DIR__ . '/includes/admin-sidebar.php',
            __DIR__ . '/includes/user-sidebar.php'
        ];
        
        echo "<h3>Cached Sidebar Files:</h3>";
        if (isset($status['scripts'])) {
            foreach ($sidebarFiles as $file) {
                if (isset($status['scripts'][$file])) {
                    $cached = $status['scripts'][$file];
                    echo "<p><span class='error'>⚠️ CACHED:</span> " . basename($file) . "</p>";
                    echo "<pre>Last Used: " . date('Y-m-d H:i:s', $cached['last_used']) . "\n";
                    echo "Hits: " . $cached['hits'] . "\n";
                    echo "Memory Consumption: " . round($cached['memory_consumption'] / 1024, 2) . " KB</pre>";
                } else {
                    echo "<p><span class='success'>✅ NOT CACHED:</span> " . basename($file) . "</p>";
                }
            }
        }
        
        echo "<p><a href='?clear_opcache=1' style='background:#dc3545;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;margin-top:10px;'>Clear OpCache Now</a></p>";
        
        if (isset($_GET['clear_opcache'])) {
            opcache_reset();
            echo "<p class='success'>✅ OpCache cleared! <strong>Now restart Apache in XAMPP Control Panel.</strong></p>";
        }
    } else {
        echo "<p class='success'>OpCache is <strong>DISABLED</strong> - No caching issues!</p>";
    }
} else {
    echo "<p class='info'>OpCache functions not available</p>";
}

// Check file paths
echo "<h2>3. File Path Check</h2>";
$files = [
    'includes/admin-sidebar.php',
    'includes/user-sidebar.php',
    'admin-sidebar.php',
    'user-sidebar.php',
    'config/constants.php'
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    $exists = file_exists($path);
    $readable = $exists ? is_readable($path) : false;
    $mtime = $exists ? filemtime($path) : 0;
    
    echo "<p>" . ($exists ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . " ";
    echo "<strong>$file</strong> - ";
    echo $exists ? "EXISTS" : "NOT FOUND";
    if ($exists) {
        echo " | Modified: " . date('Y-m-d H:i:s', $mtime);
        echo " | " . ($readable ? "READABLE" : "NOT READABLE");
    }
    echo "</p>";
}

// Check BASE_URL
echo "<h2>4. Configuration Check</h2>";
if (file_exists(__DIR__ . '/config/constants.php')) {
    require_once __DIR__ . '/config/constants.php';
    echo "<p>BASE_URL: <strong>" . (defined('BASE_URL') ? BASE_URL : 'NOT DEFINED') . "</strong></p>";
    echo "<p>APP_NAME: <strong>" . (defined('APP_NAME') ? APP_NAME : 'NOT DEFINED') . "</strong></p>";
    
    // Check if BASE_URL matches current server
    $currentUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
    $currentPath = dirname($_SERVER['SCRIPT_NAME']);
    $expectedUrl = $currentUrl . $currentPath;
    
    echo "<p>Current URL: <strong>$currentUrl</strong></p>";
    echo "<p>Script Path: <strong>$currentPath</strong></p>";
    
    if (defined('BASE_URL')) {
        if (strpos($expectedUrl, BASE_URL) !== false || strpos(BASE_URL, $currentUrl) !== false) {
            echo "<p class='success'>✅ BASE_URL appears to be configured correctly</p>";
        } else {
            echo "<p class='error'>⚠️ BASE_URL might not match current server</p>";
        }
    }
}

// Check sidebar content
echo "<h2>5. Sidebar Content Verification</h2>";
$adminSidebar = file_get_contents(__DIR__ . '/includes/admin-sidebar.php');
$userSidebar = file_get_contents(__DIR__ . '/includes/user-sidebar.php');

echo "<h3>Admin Sidebar:</h3>";
$checks = [
    'Ticket Management' => strpos($adminSidebar, 'Ticket Management') !== false,
    'Email Settings' => strpos($adminSidebar, 'Email Settings') !== false,
    'Email Templates' => strpos($adminSidebar, 'Email Templates') !== false
];

foreach ($checks as $item => $found) {
    echo "<p>" . ($found ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . " $item</p>";
}

echo "<h3>User Sidebar:</h3>";
$hasTickets = strpos($userSidebar, 'Support Tickets') !== false;
echo "<p>" . ($hasTickets ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . " Support Tickets</p>";

// XAMPP restart instructions
echo "<h2>6. XAMPP Restart Instructions</h2>";
echo "<div style='background:#fff3cd;border:1px solid #ffc107;padding:15px;border-radius:5px;margin:10px 0;'>";
echo "<h3>To Restart Apache in XAMPP:</h3>";
echo "<ol>";
echo "<li>Open <strong>XAMPP Control Panel</strong></li>";
echo "<li>Find <strong>Apache</strong> in the list</li>";
echo "<li>Click the <strong>Stop</strong> button (red square)</li>";
echo "<li>Wait 3-5 seconds</li>";
echo "<li>Click the <strong>Start</strong> button (green play icon)</li>";
echo "<li>Wait for Apache to fully start (green indicator)</li>";
echo "<li>Clear your browser cache (Ctrl+Shift+R or Cmd+Shift+R)</li>";
echo "<li>Refresh your dashboard</li>";
echo "</ol>";
echo "</div>";

// Test sidebar rendering
echo "<h2>7. Sidebar Rendering Test</h2>";
echo "<p><a href='check-sidebar-version.php' target='_blank' style='background:#0d6efd;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Test Sidebar Rendering</a></p>";

echo "<hr>";
echo "<h2>Summary</h2>";
if (function_exists('opcache_get_status') && opcache_get_status(false) && opcache_get_status(false)['opcache_enabled']) {
    echo "<p class='error'><strong>⚠️ ACTION REQUIRED:</strong> OpCache is enabled and may be caching old sidebar files.</p>";
    echo "<p><strong>Steps to fix:</strong></p>";
    echo "<ol>";
    echo "<li>Click 'Clear OpCache Now' button above</li>";
    echo "<li>Restart Apache in XAMPP Control Panel</li>";
    echo "<li>Clear browser cache and refresh dashboard</li>";
    echo "</ol>";
} else {
    echo "<p class='success'>✅ OpCache is disabled - No caching issues expected</p>";
    echo "<p>If menu items still don't show, check browser cache and try incognito mode.</p>";
}

echo "</body></html>";

?>






