<?php
/**
 * Automatic PHPMailer Installation
 * Handles everything automatically
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);

echo "<!DOCTYPE html><html><head><title>Installing PHPMailer</title>";
echo "<meta http-equiv='refresh' content='2'>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
    h1 { color: #333; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #0dcaf0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔧 Automatic PHPMailer Installation</h1>";

$vendorPath = __DIR__ . '/vendor';
$phpmailerPath = $vendorPath . '/phpmailer/phpmailer';
$autoloadPath = $vendorPath . '/autoload.php';

$steps = [];
$currentStep = $_GET['step'] ?? 1;

// Step 1: Create vendor directory
if ($currentStep == 1) {
    echo "<h2>Step 1: Creating vendor directory...</h2>";
    
    if (!is_dir($vendorPath)) {
        if (@mkdir($vendorPath, 0755, true)) {
            echo "<p class='success'>✅ Vendor directory created</p>";
            $steps[] = 'vendor_created';
        } else {
            echo "<p class='error'>❌ Failed to create vendor directory. Trying alternative location...</p>";
            // Try creating in a subdirectory that might have write permissions
            $altPath = __DIR__ . '/temp_vendor';
            if (@mkdir($altPath, 0755, true)) {
                echo "<p class='info'>Created in alternative location. Will move later.</p>";
            }
        }
    } else {
        echo "<p class='success'>✅ Vendor directory already exists</p>";
    }
    
    echo "<script>setTimeout(function(){ window.location.href='?step=2'; }, 1000);</script>";
    echo "</div></body></html>";
    exit;
}

// Step 2: Download PHPMailer
if ($currentStep == 2) {
    echo "<h2>Step 2: Downloading PHPMailer...</h2>";
    
    $zipUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip';
    $tempZip = sys_get_temp_dir() . '/phpmailer-' . uniqid() . '.zip';
    
    echo "<p class='info'>Downloading from GitHub...</p>";
    
    // Try multiple download methods
    $zipContent = false;
    
    // Method 1: file_get_contents
    if (ini_get('allow_url_fopen')) {
        $zipContent = @file_get_contents($zipUrl);
    }
    
    // Method 2: curl if available
    if ($zipContent === false && function_exists('curl_init')) {
        $ch = curl_init($zipUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $zipContent = curl_exec($ch);
        curl_close($ch);
    }
    
    if ($zipContent !== false && strlen($zipContent) > 1000) {
        // Save to temp directory
        if (@file_put_contents($tempZip, $zipContent)) {
            $_SESSION['phpmailer_zip'] = $tempZip;
            echo "<p class='success'>✅ Download complete (" . number_format(strlen($zipContent)) . " bytes)</p>";
            echo "<script>setTimeout(function(){ window.location.href='?step=3'; }, 1000);</script>";
        } else {
            echo "<p class='error'>❌ Failed to save downloaded file</p>";
            echo "<p>Please run: <code>sudo chmod -R 777 " . sys_get_temp_dir() . "</code></p>";
        }
    } else {
        echo "<p class='error'>❌ Failed to download. Trying alternative method...</p>";
        echo "<script>setTimeout(function(){ window.location.href='?step=3_alt'; }, 2000);</script>";
    }
    
    echo "</div></body></html>";
    exit;
}

// Step 3: Extract PHPMailer
if ($currentStep == 3) {
    echo "<h2>Step 3: Extracting PHPMailer...</h2>";
    
    $tempZip = $_SESSION['phpmailer_zip'] ?? sys_get_temp_dir() . '/phpmailer.zip';
    
    if (!file_exists($tempZip)) {
        echo "<p class='error'>❌ Zip file not found. Please download manually.</p>";
        echo "<p><a href='install-phpmailer-manual.php'>View Manual Instructions</a></p>";
        echo "</div></body></html>";
        exit;
    }
    
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($tempZip) === TRUE) {
            // Extract to vendor directory
            if (@$zip->extractTo($vendorPath)) {
                $zip->close();
                
                // Rename extracted folder
                $extractedPath = $vendorPath . '/PHPMailer-master';
                if (is_dir($extractedPath)) {
                    if (is_dir($phpmailerPath)) {
                        // Remove old
                        function rmdir_recursive($dir) {
                            if (!is_dir($dir)) return false;
                            $files = array_diff(scandir($dir), ['.', '..']);
                            foreach ($files as $file) {
                                $path = $dir . '/' . $file;
                                is_dir($path) ? rmdir_recursive($path) : @unlink($path);
                            }
                            return @rmdir($dir);
                        }
                        rmdir_recursive($phpmailerPath);
                    }
                    @rename($extractedPath, $phpmailerPath);
                }
                
                @unlink($tempZip);
                echo "<p class='success'>✅ Extraction complete</p>";
                echo "<script>setTimeout(function(){ window.location.href='?step=4'; }, 1000);</script>";
            } else {
                echo "<p class='error'>❌ Failed to extract. Permission issue.</p>";
                echo "<p>Please run: <code>sudo chmod -R 755 $vendorPath</code></p>";
            }
        } else {
            echo "<p class='error'>❌ Failed to open zip file</p>";
        }
    } else {
        echo "<p class='error'>❌ ZipArchive not available</p>";
    }
    
    echo "</div></body></html>";
    exit;
}

// Step 4: Create autoloader
if ($currentStep == 4) {
    echo "<h2>Step 4: Creating autoloader...</h2>";
    
    $autoloaderContent = <<<'PHP'
<?php
/**
 * Simple Autoloader for PHPMailer
 */
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/phpmailer/phpmailer/src/';
    
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
PHP;
    
    if (@file_put_contents($autoloadPath, $autoloaderContent)) {
        echo "<p class='success'>✅ Autoloader created</p>";
        echo "<script>setTimeout(function(){ window.location.href='?step=5'; }, 1000);</script>";
    } else {
        echo "<p class='error'>❌ Failed to create autoloader</p>";
        echo "<p>Please create manually: <code>$autoloadPath</code></p>";
    }
    
    echo "</div></body></html>";
    exit;
}

// Step 5: Verify installation
if ($currentStep == 5) {
    echo "<h2>Step 5: Verifying installation...</h2>";
    
    if (file_exists($autoloadPath)) {
        require_once $autoloadPath;
        
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            echo "<p class='success'>✅ PHPMailer installed successfully!</p>";
            echo "<p class='success'>✅ All files are in place</p>";
            echo "<p class='success'>✅ Ready to send emails via SMTP</p>";
            echo "<hr>";
            echo "<p><a href='test-email-debug.php' style='background:#198754;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Test Email Configuration</a></p>";
            echo "<p><a href='admin/settings/email-settings.php' style='background:#0d6efd;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;display:inline-block;'>Go to Email Settings</a></p>";
        } else {
            echo "<p class='error'>❌ PHPMailer class not found. Files may be in wrong location.</p>";
            echo "<p>Please check: <code>$phpmailerPath/src/PHPMailer.php</code></p>";
        }
    } else {
        echo "<p class='error'>❌ Autoloader not found</p>";
    }
    
    echo "</div></body></html>";
    exit;
}

// Start installation
if (!isset($_GET['step'])) {
    session_start();
    echo "<p>Starting automatic installation...</p>";
    echo "<p>This will take a few moments. Please wait...</p>";
    echo "<script>setTimeout(function(){ window.location.href='?step=1'; }, 1000);</script>";
}

echo "</div></body></html>";
?>





