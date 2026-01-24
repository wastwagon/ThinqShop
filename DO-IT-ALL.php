<?php
/**
 * DO IT ALL - Complete PHPMailer Installation
 * This script handles everything automatically
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
set_time_limit(300);

session_start();

echo "<!DOCTYPE html><html><head><title>Installing PHPMailer - Please Wait</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #0dcaf0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #0d6efd; font-size: 12px; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    .btn:hover { background: #0b5ed7; }
    .spinner { border: 4px solid #f3f3f3; border-top: 4px solid #0d6efd; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; display: inline-block; margin: 10px; }
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🚀 Automatic PHPMailer Installation</h1>";
echo "<p>This script will handle everything automatically. Please wait...</p>";

$vendorPath = __DIR__ . '/vendor';
$phpmailerPath = $vendorPath . '/phpmailer/phpmailer';
$autoloadPath = $vendorPath . '/autoload.php';

$allSteps = [];
$finalStatus = 'processing';

// ============================================
// STEP 1: Create vendor directory
// ============================================
echo "<div class='step'>";
echo "<h3>Step 1: Creating vendor directory</h3>";

if (!is_dir($vendorPath)) {
    // Try multiple methods to create directory
    $created = false;
    
    // Method 1: Direct mkdir
    if (@mkdir($vendorPath, 0755, true)) {
        $created = true;
    }
    
    // Method 2: Try with different permissions
    if (!$created) {
        if (@mkdir($vendorPath, 0777, true)) {
            @chmod($vendorPath, 0755);
            $created = true;
        }
    }
    
    if ($created) {
        echo "<p class='success'>✅ Vendor directory created</p>";
        $allSteps['step1'] = true;
    } else {
        echo "<p class='error'>❌ Failed to create vendor directory</p>";
        echo "<p>Directory path: <code>$vendorPath</code></p>";
        echo "<p>Parent directory writable: " . (is_writable(dirname($vendorPath)) ? "Yes" : "No") . "</p>";
        $allSteps['step1'] = false;
    }
} else {
    echo "<p class='success'>✅ Vendor directory already exists</p>";
    $allSteps['step1'] = true;
}
echo "</div>";

// ============================================
// STEP 2: Download PHPMailer
// ============================================
if ($allSteps['step1']) {
    echo "<div class='step'>";
    echo "<h3>Step 2: Downloading PHPMailer from GitHub</h3>";
    
    $zipUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip';
    $tempZip = sys_get_temp_dir() . '/phpmailer-' . uniqid() . '.zip';
    
    $zipContent = false;
    $downloadMethod = '';
    
    // Method 1: file_get_contents
    if (ini_get('allow_url_fopen')) {
        echo "<p class='info'>Trying file_get_contents...</p>";
        $context = stream_context_create([
            'http' => [
                'timeout' => 60,
                'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'follow_location' => 1
            ]
        ]);
        $zipContent = @file_get_contents($zipUrl, false, $context);
        if ($zipContent !== false) {
            $downloadMethod = 'file_get_contents';
        }
    }
    
    // Method 2: curl
    if ($zipContent === false && function_exists('curl_init')) {
        echo "<p class='info'>Trying curl...</p>";
        $ch = curl_init($zipUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)');
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        $zipContent = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200 && $zipContent !== false && strlen($zipContent) > 1000) {
            $downloadMethod = 'curl';
        } else {
            $zipContent = false;
        }
    }
    
    if ($zipContent !== false && strlen($zipContent) > 1000) {
        echo "<p class='success'>✅ Downloaded via $downloadMethod (" . number_format(strlen($zipContent)) . " bytes)</p>";
        
        // Save to temp directory
        if (@file_put_contents($tempZip, $zipContent)) {
            $_SESSION['phpmailer_zip'] = $tempZip;
            echo "<p class='success'>✅ File saved to temporary location</p>";
            $allSteps['step2'] = true;
        } else {
            echo "<p class='error'>❌ Failed to save downloaded file</p>";
            $allSteps['step2'] = false;
        }
    } else {
        echo "<p class='error'>❌ Failed to download PHPMailer</p>";
        echo "<p>Please download manually from: <a href='$zipUrl' target='_blank'>GitHub</a></p>";
        $allSteps['step2'] = false;
    }
    echo "</div>";
}

// ============================================
// STEP 3: Extract PHPMailer
// ============================================
if (($allSteps['step2'] ?? false) && isset($_SESSION['phpmailer_zip'])) {
    echo "<div class='step'>";
    echo "<h3>Step 3: Extracting PHPMailer</h3>";
    
    $tempZip = $_SESSION['phpmailer_zip'];
    
    if (class_exists('ZipArchive') && file_exists($tempZip)) {
        $zip = new ZipArchive;
        if ($zip->open($tempZip) === TRUE) {
            // Extract to vendor
            if (@$zip->extractTo($vendorPath)) {
                $zip->close();
                echo "<p class='success'>✅ Extraction successful</p>";
                
                // Handle folder structure
                $extractedPath = $vendorPath . '/PHPMailer-master';
                if (is_dir($extractedPath)) {
                    // Try to rename to standard structure
                    if (!is_dir($phpmailerPath)) {
                        if (@rename($extractedPath, $phpmailerPath)) {
                            echo "<p class='success'>✅ Organized folder structure</p>";
                        } else {
                            echo "<p class='warning'>⚠️ Could not rename folder, but files are extracted</p>";
                            $phpmailerPath = $extractedPath; // Use extracted path
                        }
                    } else {
                        // Remove old and rename new
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
                        @rename($extractedPath, $phpmailerPath);
                    }
                }
                
                @unlink($tempZip);
                $allSteps['step3'] = true;
            } else {
                echo "<p class='error'>❌ Failed to extract. Permission issue.</p>";
                $allSteps['step3'] = false;
            }
        } else {
            echo "<p class='error'>❌ Failed to open zip file</p>";
            $allSteps['step3'] = false;
        }
    } else {
        echo "<p class='error'>❌ ZipArchive not available or zip file missing</p>";
        $allSteps['step3'] = false;
    }
    echo "</div>";
}

// ============================================
// STEP 4: Create autoloader
// ============================================
echo "<div class='step'>";
echo "<h3>Step 4: Creating autoloader</h3>";

// Determine actual PHPMailer path
$actualPath = is_dir($phpmailerPath) ? 'phpmailer/phpmailer' : 'PHPMailer-master';

$autoloaderContent = <<<'AUTOLOAD'
<?php
/**
 * Simple Autoloader for PHPMailer
 */
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    
    // Try standard path first
    $base_dir = __DIR__ . '/phpmailer/phpmailer/src/';
    
    // Fallback to alternative path
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

if (@file_put_contents($autoloadPath, $autoloaderContent)) {
    echo "<p class='success'>✅ Autoloader created</p>";
    $allSteps['step4'] = true;
} else {
    echo "<p class='error'>❌ Failed to create autoloader</p>";
    echo "<p>Please create manually at: <code>$autoloadPath</code></p>";
    $allSteps['step4'] = false;
}
echo "</div>";

// ============================================
// STEP 5: Verify installation
// ============================================
echo "<div class='step'>";
echo "<h3>Step 5: Verifying installation</h3>";

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<p class='success'>✅ PHPMailer class loaded successfully!</p>";
        echo "<p class='success'>✅ Installation complete and verified!</p>";
        $allSteps['step5'] = true;
        $finalStatus = 'success';
    } else {
        // Try direct include as fallback
        $phpmailerFile = $phpmailerPath . '/src/PHPMailer.php';
        if (!file_exists($phpmailerFile)) {
            $phpmailerFile = $vendorPath . '/PHPMailer-master/src/PHPMailer.php';
        }
        
        if (file_exists($phpmailerFile)) {
            require_once $phpmailerFile;
            require_once dirname($phpmailerFile) . '/SMTP.php';
            require_once dirname($phpmailerFile) . '/Exception.php';
            
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                echo "<p class='success'>✅ PHPMailer loaded via direct include</p>";
                $allSteps['step5'] = true;
                $finalStatus = 'success';
            } else {
                echo "<p class='error'>❌ PHPMailer files found but class not loading</p>";
                $allSteps['step5'] = false;
                $finalStatus = 'failed';
            }
        } else {
            echo "<p class='error'>❌ PHPMailer.php not found</p>";
            $allSteps['step5'] = false;
            $finalStatus = 'failed';
        }
    }
} else {
    echo "<p class='error'>❌ Autoloader file not found</p>";
    $allSteps['step5'] = false;
    $finalStatus = 'failed';
}
echo "</div>";

// ============================================
// FINAL SUMMARY
// ============================================
echo "<div class='step'>";
echo "<h3>Installation Summary</h3>";

$successCount = count(array_filter($allSteps));
$totalSteps = count($allSteps);
$percentage = ($successCount / $totalSteps) * 100;

echo "<p>Steps completed: <strong>$successCount / $totalSteps</strong> ($percentage%)</p>";

if ($finalStatus === 'success') {
    echo "<p class='success' style='font-size: 18px;'><strong>✅ SUCCESS! PHPMailer is now installed and ready to use!</strong></p>";
    echo "<p>You can now send emails using your SMTP credentials.</p>";
    echo "<hr>";
    echo "<p><a href='test-email-debug.php' class='btn' style='background:#198754; font-size: 16px; padding: 15px 30px;'>Test Email Configuration</a></p>";
    echo "<p><a href='admin/settings/email-settings.php' class='btn' style='font-size: 16px; padding: 15px 30px;'>Go to Email Settings</a></p>";
} else {
    echo "<p class='warning'><strong>⚠️ Installation incomplete</strong></p>";
    echo "<p>Some steps failed. Here's what you can do:</p>";
    echo "<ol>";
    echo "<li>Fix permissions: <code>sudo chmod -R 755 $vendorPath</code></li>";
    echo "<li>Try manual installation: <a href='install-phpmailer-manual.php'>Manual Guide</a></li>";
    echo "<li>Or install via Composer: <code>composer require phpmailer/phpmailer</code></li>";
    echo "</ol>";
}
echo "</div>";

echo "</div></body></html>";
?>





