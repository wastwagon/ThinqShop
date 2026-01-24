<?php
/**
 * Install PHPMailer via PHP exec commands
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Installing PHPMailer</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #0dcaf0; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Installing PHPMailer via Terminal Commands</h1>";

$sourcePath = '/Users/OceanCyber/Downloads/ThinQShopping/PHPMailer-master';
$htdocsPath = '/Applications/XAMPP/xamppfiles/htdocs/ThinQShopping';
$tempPath = $htdocsPath . '/PHPMailer-master';
$vendorPath = $htdocsPath . '/vendor';
$targetPath = $vendorPath . '/phpmailer/phpmailer';

$results = [];

// Step 1: Copy from Downloads
echo "<div class='step'>";
echo "<h3>Step 1: Copying from Downloads folder</h3>";

if (is_dir($sourcePath)) {
    echo "<p class='info'>Source found: $sourcePath</p>";
    
    // Use PHP to copy recursively
    function copyRecursive($src, $dst) {
        if (!is_dir($dst)) {
            @mkdir($dst, 0755, true);
        }
        $dir = opendir($src);
        $fileCount = 0;
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcFile = $src . '/' . $file;
                $dstFile = $dst . '/' . $file;
                if (is_dir($srcFile)) {
                    $fileCount += copyRecursive($srcFile, $dstFile);
                } else {
                    if (@copy($srcFile, $dstFile)) {
                        $fileCount++;
                    }
                }
            }
        }
        closedir($dir);
        return $fileCount;
    }
    
    // First copy to temp location in htdocs
    if (!is_dir($tempPath)) {
        echo "<p class='info'>Copying to temporary location...</p>";
        $fileCount = copyRecursive($sourcePath, $tempPath);
        
        if ($fileCount > 0 && file_exists($tempPath . '/src/PHPMailer.php')) {
            echo "<p class='success'>✅ Copied $fileCount files to htdocs</p>";
            $results['step1'] = true;
        } else {
            echo "<p class='error'>❌ Copy failed or incomplete</p>";
            $results['step1'] = false;
        }
    } else {
        echo "<p class='info'>✅ Already exists in htdocs</p>";
        $results['step1'] = true;
    }
} else {
    echo "<p class='error'>❌ Source not found: $sourcePath</p>";
    $results['step1'] = false;
}
echo "</div>";

// Step 2: Move to vendor
if ($results['step1'] ?? false) {
    echo "<div class='step'>";
    echo "<h3>Step 2: Moving to vendor directory</h3>";
    
    // Ensure vendor directory exists
    if (!is_dir($vendorPath)) {
        @mkdir($vendorPath, 0755, true);
    }
    
    // Create phpmailer directory structure
    $phpmailerDir = $vendorPath . '/phpmailer';
    if (!is_dir($phpmailerDir)) {
        @mkdir($phpmailerDir, 0755, true);
    }
    
    if (is_dir($tempPath)) {
        if (!is_dir($targetPath)) {
            // Move/copy to target
            if (@rename($tempPath, $targetPath)) {
                echo "<p class='success'>✅ Moved to vendor/phpmailer/phpmailer</p>";
                $results['step2'] = true;
            } else {
                // Try copying instead
                echo "<p class='info'>Rename failed, trying copy...</p>";
                $fileCount = copyRecursive($tempPath, $targetPath);
                if ($fileCount > 0 && file_exists($targetPath . '/src/PHPMailer.php')) {
                    echo "<p class='success'>✅ Copied to vendor/phpmailer/phpmailer</p>";
                    // Remove temp
                    function deleteDir($dir) {
                        if (!is_dir($dir)) return false;
                        $files = array_diff(scandir($dir), ['.', '..']);
                        foreach ($files as $file) {
                            $path = $dir . '/' . $file;
                            is_dir($path) ? deleteDir($path) : @unlink($path);
                        }
                        return @rmdir($dir);
                    }
                    deleteDir($tempPath);
                    $results['step2'] = true;
                } else {
                    echo "<p class='error'>❌ Copy failed</p>";
                    $results['step2'] = false;
                }
            }
        } else {
            echo "<p class='info'>✅ Target already exists</p>";
            if (file_exists($targetPath . '/src/PHPMailer.php')) {
                echo "<p class='success'>✅ PHPMailer already in correct location</p>";
                // Remove temp
                function deleteDir($dir) {
                    if (!is_dir($dir)) return false;
                    $files = array_diff(scandir($dir), ['.', '..']);
                    foreach ($files as $file) {
                        $path = $dir . '/' . $file;
                        is_dir($path) ? deleteDir($path) : @unlink($path);
                    }
                    return @rmdir($dir);
                }
                deleteDir($tempPath);
                $results['step2'] = true;
            } else {
                echo "<p class='error'>❌ Target exists but is invalid</p>";
                $results['step2'] = false;
            }
        }
    } else {
        echo "<p class='error'>❌ Temp folder not found</p>";
        $results['step2'] = false;
    }
    echo "</div>";
}

// Step 3: Verify autoloader
echo "<div class='step'>";
echo "<h3>Step 3: Verifying autoloader</h3>";

$autoloadPath = $vendorPath . '/autoload.php';
if (file_exists($autoloadPath)) {
    echo "<p class='success'>✅ Autoloader exists</p>";
    $results['step3'] = true;
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
        $results['step3'] = true;
    } else {
        echo "<p class='error'>❌ Failed to create autoloader</p>";
        $results['step3'] = false;
    }
}
echo "</div>";

// Step 4: Final verification
echo "<div class='step'>";
echo "<h3>Step 4: Final Verification</h3>";

$phpmailerFile = $targetPath . '/src/PHPMailer.php';
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
        $results['step4'] = true;
        
        echo "<hr>";
        echo "<p><a href='test-email-debug.php' class='btn' style='background:#198754;'>Test Email Configuration</a></p>";
        echo "<p><a href='admin/settings/email-settings.php' class='btn'>Go to Email Settings</a></p>";
        echo "<p><a href='verify-phpmailer.php' class='btn' style='background:#6c757d;'>Verify Installation</a></p>";
    } else {
        echo "<p class='error'>❌ PHPMailer class not loading</p>";
        $results['step4'] = false;
    }
} else {
    echo "<p class='error'>❌ PHPMailer.php not found in target location</p>";
    $results['step4'] = false;
}
echo "</div>";

// Summary
echo "<div class='step'>";
echo "<h3>Summary</h3>";
$successCount = count(array_filter($results));
$totalSteps = count($results);
echo "<p>Steps completed: <strong>$successCount / $totalSteps</strong></p>";

if (($results['step4'] ?? false) && class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "<p class='success' style='font-size: 18px;'><strong>✅ SUCCESS! PHPMailer is installed and ready!</strong></p>";
} else {
    echo "<p class='error'><strong>❌ Installation incomplete</strong></p>";
}
echo "</div>";

echo "</div></body></html>";
?>





