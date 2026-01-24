<?php
/**
 * Complete PHPMailer Installation Script
 * Handles everything in one go
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);

session_start();

echo "<!DOCTYPE html><html><head><title>Installing PHPMailer</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #0dcaf0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #0d6efd; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    .btn:hover { background: #0b5ed7; }
    .progress { background: #e9ecef; height: 30px; border-radius: 15px; overflow: hidden; margin: 20px 0; }
    .progress-bar { background: #0d6efd; height: 100%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🚀 Complete PHPMailer Installation</h1>";

$vendorPath = __DIR__ . '/vendor';
$phpmailerPath = $vendorPath . '/phpmailer/phpmailer';
$autoloadPath = $vendorPath . '/autoload.php';

$results = [];
$progress = 0;

// Step 1: Create vendor directory
echo "<div class='step'>";
echo "<h3>Step 1: Creating vendor directory</h3>";
if (!is_dir($vendorPath)) {
    if (@mkdir($vendorPath, 0755, true)) {
        echo "<p class='success'>✅ Vendor directory created</p>";
        $results['step1'] = true;
        $progress += 20;
    } else {
        echo "<p class='error'>❌ Failed to create vendor directory</p>";
        echo "<p>Please run: <code>mkdir -p $vendorPath && chmod 755 $vendorPath</code></p>";
        $results['step1'] = false;
    }
} else {
    echo "<p class='success'>✅ Vendor directory already exists</p>";
    $results['step1'] = true;
    $progress += 20;
}
echo "</div>";

// Step 2: Download PHPMailer
echo "<div class='step'>";
echo "<h3>Step 2: Downloading PHPMailer</h3>";

$zipUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip';
$tempZip = sys_get_temp_dir() . '/phpmailer-' . time() . '.zip';

$zipContent = false;

// Try file_get_contents
if (ini_get('allow_url_fopen')) {
    echo "<p class='info'>Attempting download via file_get_contents...</p>";
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0'
        ]
    ]);
    $zipContent = @file_get_contents($zipUrl, false, $context);
}

// Try curl if file_get_contents failed
if ($zipContent === false && function_exists('curl_init')) {
    echo "<p class='info'>Trying curl...</p>";
    $ch = curl_init($zipUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $zipContent = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $zipContent = false;
    }
}

if ($zipContent !== false && strlen($zipContent) > 1000) {
    if (@file_put_contents($tempZip, $zipContent)) {
        echo "<p class='success'>✅ Downloaded successfully (" . number_format(strlen($zipContent)) . " bytes)</p>";
        $results['step2'] = true;
        $progress += 20;
        $_SESSION['phpmailer_zip'] = $tempZip;
    } else {
        echo "<p class='error'>❌ Failed to save downloaded file</p>";
        $results['step2'] = false;
    }
} else {
    echo "<p class='error'>❌ Failed to download PHPMailer</p>";
    echo "<p>Please download manually from: <a href='$zipUrl' target='_blank'>GitHub</a></p>";
    $results['step2'] = false;
}
echo "</div>";

// Step 3: Extract
if ($results['step2'] ?? false) {
    echo "<div class='step'>";
    echo "<h3>Step 3: Extracting PHPMailer</h3>";
    
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($tempZip) === TRUE) {
            // Try to extract
            if (@$zip->extractTo($vendorPath)) {
                $zip->close();
                
                // Rename folder
                $extractedPath = $vendorPath . '/PHPMailer-master';
                if (is_dir($extractedPath)) {
                    if (is_dir($phpmailerPath)) {
                        // Remove old
                        function deleteDir($dir) {
                            if (!is_dir($dir)) return false;
                            $files = array_diff(scandir($dir), ['.', '..']);
                            foreach ($files as $file) {
                                $path = $dir . '/' . $file;
                                is_dir($path) ? deleteDir($path) : @unlink($path);
                            }
                            return @rmdir($dir);
                        }
                        deleteDir($phpmailerPath);
                    }
                    if (@rename($extractedPath, $phpmailerPath)) {
                        echo "<p class='success'>✅ Extracted and organized successfully</p>";
                        $results['step3'] = true;
                        $progress += 20;
                    } else {
                        echo "<p class='warning'>⚠️ Extracted but couldn't rename. Using PHPMailer-master folder.</p>";
                        $phpmailerPath = $extractedPath;
                        $results['step3'] = true;
                        $progress += 20;
                    }
                } else {
                    echo "<p class='error'>❌ Extraction folder not found</p>";
                    $results['step3'] = false;
                }
                
                @unlink($tempZip);
            } else {
                echo "<p class='error'>❌ Failed to extract. Permission denied.</p>";
                echo "<p>Please run: <code>sudo chmod -R 755 $vendorPath</code></p>";
                $results['step3'] = false;
            }
        } else {
            echo "<p class='error'>❌ Failed to open zip file</p>";
            $results['step3'] = false;
        }
    } else {
        echo "<p class='error'>❌ ZipArchive class not available</p>";
        $results['step3'] = false;
    }
    echo "</div>";
}

// Step 4: Create autoloader
echo "<div class='step'>";
echo "<h3>Step 4: Creating autoloader</h3>";

// Adjust path if folder wasn't renamed
$actualPhpmailerPath = is_dir($phpmailerPath) ? $phpmailerPath : $vendorPath . '/PHPMailer-master';

$autoloaderContent = <<<'AUTOLOAD'
<?php
/**
 * Simple Autoloader for PHPMailer
 */
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/phpmailer/phpmailer/src/';
    
    // Also try alternative path if main path doesn't exist
    if (!file_exists($base_dir . 'PHPMailer.php')) {
        $base_dir = __DIR__ . '/PHPMailer-master/src/';
    }
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});
AUTOLOAD;

// Fix the path in autoloader
$autoloaderContent = str_replace("basename('$actualPhpmailerPath')", "'phpmailer/phpmailer'", $autoloaderContent);

if (@file_put_contents($autoloadPath, $autoloaderContent)) {
    echo "<p class='success'>✅ Autoloader created</p>";
    $results['step4'] = true;
    $progress += 20;
} else {
    echo "<p class='error'>❌ Failed to create autoloader</p>";
    echo "<p>Please create manually at: <code>$autoloadPath</code></p>";
    $results['step4'] = false;
}
echo "</div>";

// Step 5: Verify
echo "<div class='step'>";
echo "<h3>Step 5: Verifying installation</h3>";

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<p class='success'>✅ PHPMailer class loaded successfully!</p>";
        echo "<p class='success'>✅ Installation complete!</p>";
        $results['step5'] = true;
        $progress = 100;
    } else {
        echo "<p class='error'>❌ PHPMailer class not found</p>";
        echo "<p>Checking file structure...</p>";
        
        $srcPath = $phpmailerPath . '/src/PHPMailer.php';
        if (file_exists($srcPath)) {
            echo "<p class='info'>✅ PHPMailer.php found at: $srcPath</p>";
            echo "<p>Autoloader path may need adjustment.</p>";
        } else {
            echo "<p class='error'>❌ PHPMailer.php not found</p>";
        }
        $results['step5'] = false;
    }
} else {
    echo "<p class='error'>❌ Autoloader file not found</p>";
    $results['step5'] = false;
}
echo "</div>";

// Summary
echo "<div class='step'>";
echo "<h3>Installation Summary</h3>";
echo "<div class='progress'><div class='progress-bar' style='width: $progress%'>$progress%</div></div>";

$allSuccess = !in_array(false, $results);

if ($allSuccess) {
    echo "<p class='success'><strong>✅ Installation Successful!</strong></p>";
    echo "<p>PHPMailer is now installed and ready to use.</p>";
    echo "<p><a href='test-email-debug.php' class='btn' style='background:#198754;'>Test Email Configuration</a></p>";
    echo "<p><a href='admin/settings/email-settings.php' class='btn'>Go to Email Settings</a></p>";
} else {
    echo "<p class='warning'><strong>⚠️ Installation incomplete</strong></p>";
    echo "<p>Some steps failed. Please check the errors above and try the manual installation:</p>";
    echo "<p><a href='install-phpmailer-manual.php' class='btn' style='background:#ffc107;'>Manual Installation Guide</a></p>";
}
echo "</div>";

echo "</div></body></html>";
?>

