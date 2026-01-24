<?php
/**
 * Copy PHPMailer from Downloads folder to XAMPP htdocs
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Copying PHPMailer</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #0dcaf0; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>📋 Copying PHPMailer from Downloads</h1>";

$downloadsPath = '/Users/OceanCyber/Downloads/ThinQShopping/PHPMailer-master';
$htdocsPath = '/Applications/XAMPP/xamppfiles/htdocs/ThinQShopping';
$sourcePath = $downloadsPath;
$vendorPath = $htdocsPath . '/vendor';
$targetPath = $vendorPath . '/phpmailer/phpmailer';
$altTargetPath = $vendorPath . '/PHPMailer-master';

// Step 1: Check Downloads folder
echo "<div class='step'>";
echo "<h3>Step 1: Checking Downloads folder</h3>";

if (is_dir($sourcePath)) {
    echo "<p class='success'>✅ Found PHPMailer-master in Downloads folder</p>";
    
    $phpmailerFile = $sourcePath . '/src/PHPMailer.php';
    if (file_exists($phpmailerFile)) {
        echo "<p class='success'>✅ PHPMailer files verified</p>";
        $found = true;
    } else {
        echo "<p class='error'>❌ PHPMailer.php not found in src folder</p>";
        $found = false;
    }
} else {
    echo "<p class='error'>❌ PHPMailer-master not found in Downloads folder</p>";
    echo "<p>Checked: <code>$sourcePath</code></p>";
    $found = false;
}
echo "</div>";

// Step 2: Copy to vendor
if ($found) {
    echo "<div class='step'>";
    echo "<h3>Step 2: Copying to vendor directory</h3>";
    
    // Ensure vendor directory exists
    if (!is_dir($vendorPath)) {
        if (@mkdir($vendorPath, 0755, true)) {
            echo "<p class='success'>✅ Created vendor directory</p>";
        } else {
            echo "<p class='error'>❌ Failed to create vendor directory</p>";
        }
    }
    
    // Create phpmailer directory structure
    $phpmailerDir = $vendorPath . '/phpmailer';
    if (!is_dir($phpmailerDir)) {
        @mkdir($phpmailerDir, 0755, true);
    }
    
    // Copy files
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
    
    if (!is_dir($targetPath)) {
        echo "<p class='info'>Copying files from Downloads to vendor...</p>";
        $fileCount = copyRecursive($sourcePath, $targetPath);
        
        if ($fileCount > 0) {
            echo "<p class='success'>✅ Copied $fileCount files to vendor/phpmailer/phpmailer</p>";
            
            // Verify
            if (file_exists($targetPath . '/src/PHPMailer.php')) {
                echo "<p class='success'>✅ PHPMailer.php verified in new location</p>";
                $copied = true;
            } else {
                echo "<p class='error'>❌ Copy may have failed - PHPMailer.php not found</p>";
                $copied = false;
            }
        } else {
            echo "<p class='error'>❌ Copy failed - no files copied</p>";
            $copied = false;
        }
    } else {
        echo "<p class='info'>⚠️ Target directory already exists</p>";
        if (file_exists($targetPath . '/src/PHPMailer.php')) {
            echo "<p class='success'>✅ PHPMailer already in correct location</p>";
            $copied = true;
        } else {
            echo "<p class='error'>❌ Target exists but is invalid</p>";
            $copied = false;
        }
    }
    echo "</div>";
    
    // Step 3: Verify autoloader
    if ($copied) {
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
                
                echo "<hr>";
                echo "<p><a href='test-email-debug.php' style='background:#198754;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Test Email Configuration</a></p>";
                echo "<p><a href='admin/settings/email-settings.php' style='background:#0d6efd;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Email Settings</a></p>";
                echo "<p><a href='verify-phpmailer.php' style='background:#6c757d;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Verify Installation</a></p>";
            } else {
                echo "<p class='error'>❌ PHPMailer class not loading</p>";
            }
        } else {
            echo "<p class='error'>❌ PHPMailer.php not found in target location</p>";
        }
        echo "</div>";
    }
}

echo "</div></body></html>";
?>





