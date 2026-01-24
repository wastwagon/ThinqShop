<?php
/**
 * Copy PHPMailer - Handle permissions and multiple paths
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
    .warning { color: #ffc107; font-weight: bold; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>📋 Copying PHPMailer - Permission Fix</h1>";

$htdocsPath = __DIR__;
$vendorPath = $htdocsPath . '/vendor';
$targetPath = $vendorPath . '/phpmailer/phpmailer';

// Check multiple possible source locations
$possibleSources = [
    '/Users/OceanCyber/Downloads/ThinQShopping/PHPMailer-master',
    '/Users/OceanCyber/Downloads/PHPMailer-master',
    $htdocsPath . '/PHPMailer-master',
    '/Applications/XAMPP/xamppfiles/htdocs/ThinQShopping/PHPMailer-master',
];

echo "<div class='step'>";
echo "<h3>Step 1: Checking for PHPMailer-master</h3>";

$sourcePath = null;
$found = false;

foreach ($possibleSources as $path) {
    echo "<p>Checking: <code>$path</code> ... ";
    
    if (file_exists($path) && is_dir($path)) {
        echo "<span class='success'>✅ EXISTS</span></p>";
        
        // Check if readable
        if (is_readable($path)) {
            echo "<p class='info'>  └─ Readable: ✅ YES</p>";
            
            // Check for PHPMailer.php
            $phpmailerFile = $path . '/src/PHPMailer.php';
            if (file_exists($phpmailerFile)) {
                echo "<p class='success'>  └─ PHPMailer.php found: ✅ YES</p>";
                $sourcePath = $path;
                $found = true;
                break;
            } else {
                echo "<p class='error'>  └─ PHPMailer.php: ❌ NOT FOUND</p>";
            }
        } else {
            echo "<p class='warning'>  └─ Readable: ❌ NO (Permission issue)</p>";
        }
    } else {
        echo "<span class='error'>❌ NOT FOUND</span></p>";
    }
}

if (!$found) {
    echo "<p class='error'><strong>PHPMailer-master not found or not accessible</strong></p>";
    echo "<p class='info'>This might be a permissions issue. PHP running under Apache may not have access to your Downloads folder.</p>";
    echo "<p><strong>Solution:</strong> Please manually copy the PHPMailer-master folder to the XAMPP htdocs directory:</p>";
    echo "<ol>";
    echo "<li>Open Finder</li>";
    echo "<li>Navigate to: <code>/Users/OceanCyber/Downloads/ThinQShopping/PHPMailer-master</code></li>";
    echo "<li>Copy the entire <code>PHPMailer-master</code> folder</li>";
    echo "<li>Navigate to: <code>/Applications/XAMPP/xamppfiles/htdocs/ThinQShopping/</code></li>";
    echo "<li>Paste the folder there</li>";
    echo "<li>Then refresh this page</li>";
    echo "</ol>";
    echo "<p>Or run this command in Terminal:</p>";
    echo "<pre>cp -R /Users/OceanCyber/Downloads/ThinQShopping/PHPMailer-master /Applications/XAMPP/xamppfiles/htdocs/ThinQShopping/</pre>";
} else {
    echo "<p class='success'><strong>✅ Found PHPMailer-master at: $sourcePath</strong></p>";
    echo "</div>";
    
    // Step 2: Copy to vendor
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
        $errors = [];
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $srcFile = $src . '/' . $file;
                $dstFile = $dst . '/' . $file;
                
                if (is_dir($srcFile)) {
                    $result = copyRecursive($srcFile, $dstFile);
                    $fileCount += $result['count'];
                    $errors = array_merge($errors, $result['errors']);
                } else {
                    if (@copy($srcFile, $dstFile)) {
                        $fileCount++;
                    } else {
                        $errors[] = "Failed to copy: $srcFile";
                    }
                }
            }
        }
        closedir($dir);
        return ['count' => $fileCount, 'errors' => $errors];
    }
    
    if (!is_dir($targetPath)) {
        echo "<p class='info'>Copying files...</p>";
        $result = copyRecursive($sourcePath, $targetPath);
        
        if ($result['count'] > 0) {
            echo "<p class='success'>✅ Copied {$result['count']} files</p>";
            
            if (!empty($result['errors'])) {
                echo "<p class='warning'>⚠️ Some files had errors:</p>";
                echo "<pre>" . implode("\n", array_slice($result['errors'], 0, 10)) . "</pre>";
            }
            
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
            if (!empty($result['errors'])) {
                echo "<pre>" . implode("\n", $result['errors']) . "</pre>";
            }
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
                echo "<p><a href='test-email-debug.php' class='btn' style='background:#198754;'>Test Email Configuration</a></p>";
                echo "<p><a href='admin/settings/email-settings.php' class='btn'>Go to Email Settings</a></p>";
                echo "<p><a href='verify-phpmailer.php' class='btn' style='background:#6c757d;'>Verify Installation</a></p>";
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





