<?php
/**
 * Verify Header Changes
 * This script checks if all premium header changes are in place
 */

header('Content-Type: text/html; charset=utf-8');

// Clear PHP opcache
if (function_exists('opcache_reset')) {
    opcache_reset();
}

echo "<!DOCTYPE html><html><head><title>Verify Header Changes</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #05203e; border-bottom: 3px solid #05203e; padding-bottom: 10px; }
    .check-item { padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745; background: #d4edda; }
    .check-item.fail { border-left-color: #dc3545; background: #f8d7da; }
    .check-item.warning { border-left-color: #ffc107; background: #fff3cd; }
    .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; font-size: 0.9em; }
    .btn { display: inline-block; padding: 10px 20px; background: #05203e; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    .summary { background: #e7f3ff; padding: 20px; border-radius: 5px; margin: 20px 0; border-left: 4px solid #05203e; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>✅ Verify Premium Header Changes</h1>";

$checks = [];
$allPassed = true;

// Check 1: CSS file exists and has premium styles
$cssFile = __DIR__ . '/assets/css/main.css';
if (file_exists($cssFile)) {
    $cssContent = file_get_contents($cssFile);
    
    $checks[] = [
        'name' => 'CSS File Exists',
        'status' => true,
        'message' => 'main.css found'
    ];
    
    // Check for premium header styles
    $premiumChecks = [
        'Premium Main Header' => strpos($cssContent, '/* Premium Main Header */') !== false,
        'Premium Search Bar' => strpos($cssContent, '/* Premium Search Bar */') !== false,
        'Premium User Actions' => strpos($cssContent, '/* Premium User Actions') !== false,
        'Premium Navigation Bar' => strpos($cssContent, '/* Premium Navigation Bar */') !== false,
        'Hero Background Image' => strpos($cssContent, '.hero-background-image') !== false,
        'Action Link Premium Styles' => strpos($cssContent, 'background: linear-gradient(135deg, rgba(255, 255, 255, 0.9)') !== false,
    ];
    
    foreach ($premiumChecks as $checkName => $passed) {
        $checks[] = [
            'name' => $checkName,
            'status' => $passed,
            'message' => $passed ? 'Found in CSS' : 'NOT FOUND in CSS'
        ];
        if (!$passed) $allPassed = false;
    }
} else {
    $checks[] = [
        'name' => 'CSS File Exists',
        'status' => false,
        'message' => 'main.css NOT FOUND'
    ];
    $allPassed = false;
}

// Check 2: Header PHP files
$headerFiles = [
    'includes/header.php' => __DIR__ . '/includes/header.php',
    'header.php' => __DIR__ . '/header.php',
];

foreach ($headerFiles as $fileName => $filePath) {
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        $headerChecks = [
            'Icon-only action links (no text)' => strpos($content, 'action-text') === false || strpos($content, 'action-label') === false,
            'Hero background image (not video)' => strpos($content, 'hero-background-image') !== false,
            'Cache-busting CSS version' => strpos($content, 'md5_file') !== false || strpos($content, '&rev=') !== false,
        ];
        
        foreach ($headerChecks as $checkName => $passed) {
            $checks[] = [
                'name' => "$fileName: $checkName",
                'status' => $passed,
                'message' => $passed ? 'Correct' : 'Needs update'
            ];
            if (!$passed) $allPassed = false;
        }
    }
}

// Check 3: Index files
$indexFiles = [
    'index.php' => __DIR__ . '/index.php',
    'includes/index.php' => __DIR__ . '/includes/index.php',
];

foreach ($indexFiles as $fileName => $filePath) {
    if (file_exists($filePath)) {
        $content = file_get_contents($filePath);
        
        $indexChecks = [
            'Hero uses image (not video)' => strpos($content, 'hero-background-image') !== false && strpos($content, '<video') === false,
        ];
        
        foreach ($indexChecks as $checkName => $passed) {
            $checks[] = [
                'name' => "$fileName: $checkName",
                'status' => $passed,
                'message' => $passed ? 'Correct' : 'Still using video'
            ];
            if (!$passed) $allPassed = false;
        }
    }
}

// Display results
echo "<div class='summary'>";
echo "<h2>Summary</h2>";
$passedCount = count(array_filter($checks, fn($c) => $c['status']));
$totalCount = count($checks);
echo "<p><strong>Checks Passed:</strong> $passedCount / $totalCount</p>";
if ($allPassed) {
    echo "<p style='color: #28a745; font-weight: bold;'>✅ All checks passed! Your premium header should be working.</p>";
} else {
    echo "<p style='color: #dc3545; font-weight: bold;'>⚠️ Some checks failed. Please review the details below.</p>";
}
echo "</div>";

echo "<h2>Detailed Checks</h2>";
foreach ($checks as $check) {
    $class = $check['status'] ? 'check-item' : 'check-item fail';
    $icon = $check['status'] ? '✅' : '❌';
    echo "<div class='$class'>";
    echo "<strong>$icon {$check['name']}</strong><br>";
    echo "<span>{$check['message']}</span>";
    echo "</div>";
}

// Instructions
echo "<div class='summary'>";
echo "<h2>📋 Next Steps</h2>";
if (!$allPassed) {
    echo "<p><strong>If checks failed:</strong></p>";
    echo "<ol>";
    echo "<li>Make sure all files have been saved</li>";
    echo "<li>Restart Apache using XAMPP Control Panel</li>";
    echo "<li>Clear browser cache (Ctrl+Shift+Delete or Cmd+Shift+Delete)</li>";
    echo "<li>Hard refresh the page (Ctrl+F5 or Cmd+Shift+R)</li>";
    echo "</ol>";
} else {
    echo "<p><strong>All changes are in place! If you still don't see them:</strong></p>";
    echo "<ol>";
    echo "<li><strong>Restart Apache:</strong> Open XAMPP Control Panel → Stop Apache → Wait 3 seconds → Start Apache</li>";
    echo "<li><strong>Clear Browser Cache:</strong> Press <code class='code'>Ctrl+Shift+Delete</code> (Windows) or <code class='code'>Cmd+Shift+Delete</code> (Mac)</li>";
    echo "<li><strong>Hard Refresh:</strong> Press <code class='code'>Ctrl+F5</code> (Windows) or <code class='code'>Cmd+Shift+R</code> (Mac)</li>";
    echo "<li><strong>Try Incognito/Private Mode:</strong> This bypasses all cache</li>";
    echo "</ol>";
}
echo "</div>";

echo "<div style='text-align: center; margin-top: 30px;'>";
echo "<a href='index.php' class='btn'>Go to Homepage</a>";
echo "<a href='restart-apache.php' class='btn'>Apache Restart Helper</a>";
echo "<a href='assets/css/main.css?v=" . time() . "' class='btn' target='_blank'>View CSS</a>";
echo "</div>";

echo "</div></body></html>";
?>




