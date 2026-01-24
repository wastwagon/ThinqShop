<?php
/**
 * Find and Move PHPMailer - Checks multiple locations
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Finding PHPMailer</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #0dcaf0; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    ul { list-style-type: none; padding-left: 0; }
    li { padding: 5px 0; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔍 Finding PHPMailer</h1>";

$rootPath = __DIR__;
$vendorPath = __DIR__ . '/vendor';

// Step 1: Check multiple possible locations
echo "<div class='step'>";
echo "<h3>Step 1: Searching for PHPMailer-master folder</h3>";

$possibleLocations = [
    $rootPath . '/PHPMailer-master',
    $rootPath . '/phpmailer-master',
    $rootPath . '/PHPMailer',
    $rootPath . '/phpmailer',
    '/Users/OceanCyber/Downloads/ThinQShopping/PHPMailer-master',
    '/Users/OceanCyber/Downloads/PHPMailer-master',
    $vendorPath . '/PHPMailer-master',
    $vendorPath . '/phpmailer/phpmailer',
];

$foundLocation = null;
$foundPath = null;

echo "<p>Checking possible locations:</p><ul>";

foreach ($possibleLocations as $location) {
    if (is_dir($location)) {
        $phpmailerFile = $location . '/src/PHPMailer.php';
        if (file_exists($phpmailerFile)) {
            echo "<li class='success'>✅ Found at: <code>$location</code></li>";
            $foundLocation = $location;
            $foundPath = $phpmailerFile;
            break;
        } else {
            echo "<li class='info'>⚠️ Directory exists but PHPMailer.php not found: <code>$location</code></li>";
        }
    } else {
        echo "<li>❌ Not found: <code>$location</code></li>";
    }
}

echo "</ul>";

if (!$foundLocation) {
    echo "<p class='error'><strong>PHPMailer-master folder not found in any expected location.</strong></p>";
    echo "<p>Let me check what's actually in the root directory:</p>";
    
    // List files in root
    $rootFiles = scandir($rootPath);
    echo "<pre>";
    echo "Files in root directory:\n";
    echo str_repeat("-", 60) . "\n";
    foreach ($rootFiles as $file) {
        if ($file != '.' && $file != '..') {
            $fullPath = $rootPath . '/' . $file;
            $type = is_dir($fullPath) ? '[DIR]' : '[FILE]';
            $size = is_file($fullPath) ? ' (' . number_format(filesize($fullPath)) . ' bytes)' : '';
            echo "$type $file$size\n";
        }
    }
    echo "</pre>";
    
    echo "<p class='info'><strong>Please check:</strong></p>";
    echo "<ol>";
    echo "<li>Is the folder named exactly <code>PHPMailer-master</code>?</li>";
    echo "<li>Is it in the root directory: <code>$rootPath</code>?</li>";
    echo "<li>Does it contain a <code>src</code> folder with <code>PHPMailer.php</code>?</li>";
    echo "</ol>";
} else {
    echo "<p class='success'><strong>✅ PHPMailer found!</strong></p>";
}
echo "</div>";

// Step 2: Move to correct location
if ($foundLocation) {
    echo "<div class='step'>";
    echo "<h3>Step 2: Moving to vendor directory</h3>";
    
    // Ensure vendor directory exists
    if (!is_dir($vendorPath)) {
        if (@mkdir($vendorPath, 0755, true)) {
            echo "<p class='success'>✅ Created vendor directory</p>";
        } else {
            echo "<p class='error'>❌ Failed to create vendor directory</p>";
        }
    }
    
    $targetPath = $vendorPath . '/phpmailer/phpmailer';
    $altTargetPath = $vendorPath . '/PHPMailer-master';
    
    // Check if already in correct location
    if (strpos($foundLocation, $vendorPath) === 0) {
        echo "<p class='info'>✅ PHPMailer is already in vendor directory</p>";
        echo "<p>Location: <code>" . str_replace(__DIR__ . '/', '', $foundLocation) . "</code></p>";
        
        // If it's in PHPMailer-master, we can leave it or rename it
        if (basename($foundLocation) === 'PHPMailer-master') {
            echo "<p class='info'>This location is fine - autoloader will find it</p>";
        }
    } else {
        // Need to move it
        echo "<p>Moving from: <code>" . str_replace(__DIR__ . '/', '', $foundLocation) . "</code></p>";
        echo "<p>Moving to: <code>vendor/phpmailer/phpmailer</code></p>";
        
        // Create phpmailer directory structure
        $phpmailerDir = $vendorPath . '/phpmailer';
        if (!is_dir($phpmailerDir)) {
            @mkdir($phpmailerDir, 0755, true);
        }
        
        // Try to move
        if (!is_dir($targetPath)) {
            if (@rename($foundLocation, $targetPath)) {
                echo "<p class='success'>✅ Successfully moved to vendor/phpmailer/phpmailer</p>";
                $foundLocation = $targetPath;
            } else {
                // Try alternative location
                if (@rename($foundLocation, $altTargetPath)) {
                    echo "<p class='success'>✅ Moved to vendor/PHPMailer-master (this is fine)</p>";
                    $foundLocation = $altTargetPath;
                } else {
                    // Try copying
                    echo "<p class='info'>Rename failed, trying copy method...</p>";
                    
                    function copyRecursive($src, $dst) {
                        if (!is_dir($dst)) {
                            @mkdir($dst, 0755, true);
                        }
                        $dir = opendir($src);
                        while (($file = readdir($dir)) !== false) {
                            if ($file != '.' && $file != '..') {
                                $srcFile = $src . '/' . $file;
                                $dstFile = $dst . '/' . $file;
                                if (is_dir($srcFile)) {
                                    copyRecursive($srcFile, $dstFile);
                                } else {
                                    @copy($srcFile, $dstFile);
                                }
                            }
                        }
                        closedir($dir);
                        return true;
                    }
                    
                    if (copyRecursive($foundLocation, $targetPath)) {
                        echo "<p class='success'>✅ Copied to vendor/phpmailer/phpmailer</p>";
                        
                        // Try to remove original
                        function deleteDir($dir) {
                            if (!is_dir($dir)) return false;
                            $files = array_diff(scandir($dir), ['.', '..']);
                            foreach ($files as $file) {
                                $path = $dir . '/' . $file;
                                is_dir($path) ? deleteDir($path) : @unlink($path);
                            }
                            return @rmdir($dir);
                        }
                        deleteDir($foundLocation);
                        echo "<p class='success'>✅ Removed original folder</p>";
                        $foundLocation = $targetPath;
                    } else {
                        echo "<p class='error'>❌ Copy failed. Please move manually.</p>";
                    }
                }
            }
        } else {
            echo "<p class='info'>⚠️ Target directory already exists. Checking if valid...</p>";
            if (file_exists($targetPath . '/src/PHPMailer.php')) {
                echo "<p class='success'>✅ PHPMailer already in correct location</p>";
                // Remove source if different
                if ($foundLocation != $targetPath && is_dir($foundLocation)) {
                    function deleteDir($dir) {
                        if (!is_dir($dir)) return false;
                        $files = array_diff(scandir($dir), ['.', '..']);
                        foreach ($files as $file) {
                            $path = $dir . '/' . $file;
                            is_dir($path) ? deleteDir($path) : @unlink($path);
                        }
                        return @rmdir($dir);
                    }
                    deleteDir($foundLocation);
                    echo "<p class='success'>✅ Removed duplicate</p>";
                }
                $foundLocation = $targetPath;
            }
        }
    }
    echo "</div>";
    
    // Step 3: Verify autoloader
    echo "<div class='step'>";
    echo "<h3>Step 3: Verifying autoloader</h3>";
    
    $autoloadPath = $vendorPath . '/autoload.php';
    if (file_exists($autoloadPath)) {
        echo "<p class='success'>✅ Autoloader exists</p>";
    } else {
        $autoloaderContent = <<<'AUTOLOAD'
<?php
/**
 * Simple Autoloader for PHPMailer
 */
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }
    
    $relative_class = substr($class, strlen($prefix));
    
    // Try multiple possible paths
    $possible_paths = [
        __DIR__ . '/phpmailer/phpmailer/src/',
        __DIR__ . '/PHPMailer-master/src/',
        __DIR__ . '/phpmailer/src/',
    ];
    
    foreach ($possible_paths as $base_dir) {
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
AUTOLOAD;
        
        if (@file_put_contents($autoloadPath, $autoloaderContent)) {
            echo "<p class='success'>✅ Created autoloader</p>";
        } else {
            echo "<p class='error'>❌ Failed to create autoloader</p>";
        }
    }
    echo "</div>";
    
    // Step 4: Final verification
    echo "<div class='step'>";
    echo "<h3>Step 4: Final Verification</h3>";
    
    $phpmailerFile = $foundLocation . '/src/PHPMailer.php';
    if (file_exists($phpmailerFile)) {
        echo "<p class='success'>✅ PHPMailer.php found</p>";
        
        // Try to load
        if (file_exists($autoloadPath)) {
            require_once $autoloadPath;
        }
        
        // Also try direct include
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            require_once $phpmailerFile;
            require_once dirname($phpmailerFile) . '/SMTP.php';
            require_once dirname($phpmailerFile) . '/Exception.php';
        }
        
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            echo "<p class='success'>✅ PHPMailer class loaded successfully!</p>";
            echo "<p class='success'><strong>Installation complete and working!</strong></p>";
            
            echo "<hr>";
            echo "<p><a href='test-email-debug.php' class='btn' style='background:#198754;'>Test Email Configuration</a></p>";
            echo "<p><a href='admin/settings/email-settings.php' class='btn'>Go to Email Settings</a></p>";
            echo "<p><a href='verify-phpmailer.php' class='btn' style='background:#6c757d;'>Verify Installation</a></p>";
        } else {
            echo "<p class='error'>❌ PHPMailer class not loading</p>";
        }
    } else {
        echo "<p class='error'>❌ PHPMailer.php not found</p>";
    }
    echo "</div>";
}

echo "</div></body></html>";
?>





