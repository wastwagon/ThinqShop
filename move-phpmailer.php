<?php
/**
 * Move PHPMailer from root to correct location
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Moving PHPMailer</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #0dcaf0; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>📦 Moving PHPMailer to Correct Location</h1>";

$rootPath = __DIR__;
$vendorPath = __DIR__ . '/vendor';
$sourcePath = $rootPath . '/PHPMailer-master';
$targetPath = $vendorPath . '/phpmailer/phpmailer';
$altTargetPath = $vendorPath . '/PHPMailer-master';

$results = [];

// Step 1: Check if PHPMailer-master exists in root
echo "<div class='step'>";
echo "<h3>Step 1: Checking for PHPMailer-master folder</h3>";

if (is_dir($sourcePath)) {
    echo "<p class='success'>✅ Found PHPMailer-master in root directory</p>";
    
    // Check if it has the required files
    $phpmailerFile = $sourcePath . '/src/PHPMailer.php';
    if (file_exists($phpmailerFile)) {
        echo "<p class='success'>✅ PHPMailer files verified</p>";
        $results['step1'] = true;
    } else {
        echo "<p class='error'>❌ PHPMailer.php not found in src folder</p>";
        $results['step1'] = false;
    }
} else {
    echo "<p class='error'>❌ PHPMailer-master folder not found in root directory</p>";
    echo "<p>Expected location: <code>$sourcePath</code></p>";
    $results['step1'] = false;
}
echo "</div>";

// Step 2: Ensure vendor directory exists
if ($results['step1']) {
    echo "<div class='step'>";
    echo "<h3>Step 2: Setting up vendor directory</h3>";
    
    if (!is_dir($vendorPath)) {
        if (@mkdir($vendorPath, 0755, true)) {
            echo "<p class='success'>✅ Created vendor directory</p>";
        } else {
            echo "<p class='error'>❌ Failed to create vendor directory</p>";
            $results['step2'] = false;
        }
    } else {
        echo "<p class='success'>✅ Vendor directory exists</p>";
    }
    
    if (is_dir($vendorPath)) {
        $results['step2'] = true;
    } else {
        $results['step2'] = false;
    }
    echo "</div>";
}

// Step 3: Move PHPMailer to vendor
if (($results['step1'] ?? false) && ($results['step2'] ?? false)) {
    echo "<div class='step'>";
    echo "<h3>Step 3: Moving PHPMailer to vendor directory</h3>";
    
    // Try to create phpmailer/phpmailer structure
    $phpmailerDir = $vendorPath . '/phpmailer';
    if (!is_dir($phpmailerDir)) {
        @mkdir($phpmailerDir, 0755, true);
    }
    
    // Try to rename/move to standard structure first
    if (!is_dir($targetPath)) {
        if (@rename($sourcePath, $targetPath)) {
            echo "<p class='success'>✅ Moved to: vendor/phpmailer/phpmailer</p>";
            $results['step3'] = true;
        } else {
            // Try moving to alternative location
            if (@rename($sourcePath, $altTargetPath)) {
                echo "<p class='success'>✅ Moved to: vendor/PHPMailer-master</p>";
                echo "<p class='info'>Using alternative location (this is fine, autoloader will find it)</p>";
                $results['step3'] = true;
            } else {
                // Try copying instead
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
                
                if (copyRecursive($sourcePath, $targetPath)) {
                    echo "<p class='success'>✅ Copied to: vendor/phpmailer/phpmailer</p>";
                    // Try to remove source
                    function deleteDir($dir) {
                        if (!is_dir($dir)) return false;
                        $files = array_diff(scandir($dir), ['.', '..']);
                        foreach ($files as $file) {
                            $path = $dir . '/' . $file;
                            is_dir($path) ? deleteDir($path) : @unlink($path);
                        }
                        return @rmdir($dir);
                    }
                    deleteDir($sourcePath);
                    echo "<p class='success'>✅ Removed original folder from root</p>";
                    $results['step3'] = true;
                } else {
                    echo "<p class='error'>❌ Failed to copy. Permission issue.</p>";
                    $results['step3'] = false;
                }
            }
        }
    } else {
        echo "<p class='info'>⚠️ Target directory already exists. Checking if it's valid...</p>";
        if (file_exists($targetPath . '/src/PHPMailer.php')) {
            echo "<p class='success'>✅ PHPMailer already in correct location</p>";
            // Remove source if it exists
            if (is_dir($sourcePath)) {
                function deleteDir($dir) {
                    if (!is_dir($dir)) return false;
                    $files = array_diff(scandir($dir), ['.', '..']);
                    foreach ($files as $file) {
                        $path = $dir . '/' . $file;
                        is_dir($path) ? deleteDir($path) : @unlink($path);
                    }
                    return @rmdir($dir);
                }
                deleteDir($sourcePath);
                echo "<p class='success'>✅ Removed duplicate from root</p>";
            }
            $results['step3'] = true;
        } else {
            echo "<p class='error'>❌ Target exists but is invalid. Please remove it first.</p>";
            $results['step3'] = false;
        }
    }
    echo "</div>";
}

// Step 4: Verify autoloader
echo "<div class='step'>";
echo "<h3>Step 4: Verifying autoloader</h3>";

$autoloadPath = $vendorPath . '/autoload.php';
if (file_exists($autoloadPath)) {
    echo "<p class='success'>✅ Autoloader exists</p>";
    $results['step4'] = true;
} else {
    // Create autoloader
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
        $results['step4'] = true;
    } else {
        echo "<p class='error'>❌ Failed to create autoloader</p>";
        $results['step4'] = false;
    }
}
echo "</div>";

// Step 5: Final verification
echo "<div class='step'>";
echo "<h3>Step 5: Final Verification</h3>";

// Check if files are in place
$checkPaths = [
    $targetPath . '/src/PHPMailer.php',
    $altTargetPath . '/src/PHPMailer.php',
];

$found = false;
$foundPath = null;
foreach ($checkPaths as $path) {
    if (file_exists($path)) {
        echo "<p class='success'>✅ PHPMailer files found at: " . str_replace(__DIR__ . '/', '', $path) . "</p>";
        $found = true;
        $foundPath = $path;
        break;
    }
}

if ($found) {
    // Try to load
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
    }
    
    // Also try direct include
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') && $foundPath) {
        require_once $foundPath;
        require_once dirname($foundPath) . '/SMTP.php';
        require_once dirname($foundPath) . '/Exception.php';
    }
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<p class='success'>✅ PHPMailer class loaded successfully!</p>";
        echo "<p class='success'><strong>Installation complete and working!</strong></p>";
        $results['step5'] = true;
    } else {
        echo "<p class='error'>❌ PHPMailer class not loading</p>";
        $results['step5'] = false;
    }
} else {
    echo "<p class='error'>❌ PHPMailer files not found in vendor directory</p>";
    $results['step5'] = false;
}
echo "</div>";

// Summary
echo "<div class='step'>";
echo "<h3>Summary</h3>";

$successCount = count(array_filter($results));
$totalSteps = count($results);

echo "<p>Steps completed: <strong>$successCount / $totalSteps</strong></p>";

if (($results['step5'] ?? false) && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "<p class='success' style='font-size: 18px;'><strong>✅ SUCCESS! PHPMailer is installed and ready!</strong></p>";
    echo "<hr>";
    echo "<p><a href='test-email-debug.php' class='btn' style='background:#198754;'>Test Email Configuration</a></p>";
    echo "<p><a href='admin/settings/email-settings.php' class='btn'>Go to Email Settings</a></p>";
    echo "<p><a href='verify-phpmailer.php' class='btn' style='background:#6c757d;'>Verify Installation</a></p>";
} else {
    echo "<p class='error'><strong>❌ Installation incomplete</strong></p>";
    echo "<p>Please check the errors above and try again.</p>";
}
echo "</div>";

echo "</div></body></html>";
?>





