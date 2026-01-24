<?php
/**
 * Download and Install PHPMailer - Direct Method
 * This will download and extract PHPMailer files
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300);
set_time_limit(300);

echo "<!DOCTYPE html><html><head><title>Installing PHPMailer</title>";
echo "<meta http-equiv='refresh' content='3'>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .info { color: #0dcaf0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>📥 Downloading and Installing PHPMailer</h1>";

$vendorPath = __DIR__ . '/vendor';
$phpmailerPath = $vendorPath . '/phpmailer/phpmailer';
$zipUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip';

$step = $_GET['step'] ?? 1;

// Step 1: Download
if ($step == 1) {
    echo "<div class='step'>";
    echo "<h3>Step 1: Downloading PHPMailer...</h3>";
    
    // Ensure vendor directory exists
    if (!is_dir($vendorPath)) {
        @mkdir($vendorPath, 0755, true);
    }
    
    // Try multiple download locations (temp directory first - usually writable)
    $downloadPaths = [
        sys_get_temp_dir() . '/phpmailer-' . uniqid() . '.zip',
        __DIR__ . '/phpmailer-temp-' . time() . '.zip',
        $vendorPath . '/phpmailer-download.zip',
    ];
    
    echo "<p class='info'>Downloading from GitHub...</p>";
    
    $zipContent = false;
    
    // Try file_get_contents
    if (ini_get('allow_url_fopen')) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 120,
                'user_agent' => 'Mozilla/5.0',
                'follow_location' => 1
            ]
        ]);
        $zipContent = @file_get_contents($zipUrl, false, $context);
    }
    
    // Try curl
    if ($zipContent === false && function_exists('curl_init')) {
        $ch = curl_init($zipUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        $zipContent = curl_exec($ch);
        curl_close($ch);
    }
    
    if ($zipContent !== false && strlen($zipContent) > 1000) {
        echo "<p class='info'>✅ Downloaded (" . number_format(strlen($zipContent)) . " bytes). Saving...</p>";
        
        // Try to save to multiple locations
        $saved = false;
        $savedPath = null;
        
        foreach ($downloadPaths as $path) {
            if (@file_put_contents($path, $zipContent)) {
                $savedPath = $path;
                $saved = true;
                echo "<p class='success'>✅ Saved to: " . basename($path) . "</p>";
                break;
            }
        }
        
        if ($saved) {
            // Store in session for step 2
            session_start();
            $_SESSION['phpmailer_zip'] = $savedPath;
            echo "<p>Redirecting to extraction...</p>";
            echo "<script>setTimeout(function(){ window.location.href='?step=2'; }, 1000);</script>";
        } else {
            echo "<p class='error'>❌ Cannot save downloaded file. All locations failed.</p>";
            echo "<p>Please fix permissions or download manually:</p>";
            echo "<ol>";
            echo "<li>Download from: <a href='$zipUrl' target='_blank'>GitHub</a></li>";
            echo "<li>Extract the ZIP file</li>";
            echo "<li>Copy the 'PHPMailer-master' folder to: <code>$vendorPath/PHPMailer-master/</code></li>";
            echo "<li>Then refresh <a href='verify-phpmailer.php'>verification page</a></li>";
            echo "</ol>";
        }
    } else {
        echo "<p class='error'>❌ Download failed</p>";
        echo "<p>Please check your internet connection or download manually from: <a href='$zipUrl' target='_blank'>GitHub</a></p>";
    }
    echo "</div>";
}

// Step 2: Extract
if ($step == 2) {
    session_start();
    echo "<div class='step'>";
    echo "<h3>Step 2: Extracting PHPMailer...</h3>";
    
    // Get zip file from session (saved in step 1)
    $zipFile = isset($_SESSION['phpmailer_zip']) ? $_SESSION['phpmailer_zip'] : null;
    
    // Fallback to vendor directory
    if (!$zipFile || !file_exists($zipFile)) {
        $zipFile = $vendorPath . '/phpmailer-download.zip';
    }
    
    if (!$zipFile || !file_exists($zipFile)) {
        echo "<p class='error'>❌ Zip file not found. Please go back to step 1.</p>";
        echo "<p><a href='?step=1'>Retry Download</a></p>";
    } elseif (class_exists('ZipArchive')) {
        $zip = new ZipArchive;
        if ($zip->open($zipFile) === TRUE) {
            // Extract to vendor
            if (@$zip->extractTo($vendorPath)) {
                $zip->close();
                echo "<p class='success'>✅ Extraction successful</p>";
                
                // Organize folder structure
                $extractedPath = $vendorPath . '/PHPMailer-master';
                if (is_dir($extractedPath)) {
                    // Create phpmailer directory structure
                    if (!is_dir($vendorPath . '/phpmailer')) {
                        @mkdir($vendorPath . '/phpmailer', 0755, true);
                    }
                    if (!is_dir($phpmailerPath)) {
                        if (@rename($extractedPath, $phpmailerPath)) {
                            echo "<p class='success'>✅ Organized folder structure</p>";
                        } else {
                            echo "<p class='info'>⚠️ Could not rename, but files are at: PHPMailer-master</p>";
                        }
                    } else {
                        // Copy files
                        function copyRecursive($src, $dst) {
                            $dir = opendir($src);
                            @mkdir($dst, 0755, true);
                            while (($file = readdir($dir)) !== false) {
                                if ($file != '.' && $file != '..') {
                                    if (is_dir($src . '/' . $file)) {
                                        copyRecursive($src . '/' . $file, $dst . '/' . $file);
                                    } else {
                                        @copy($src . '/' . $file, $dst . '/' . $file);
                                    }
                                }
                            }
                            closedir($dir);
                        }
                        copyRecursive($extractedPath, $phpmailerPath);
                        echo "<p class='success'>✅ Copied files to correct location</p>";
                    }
                }
                
                // Clean up zip file
                if (file_exists($zipFile)) {
                    @unlink($zipFile);
                }
                if (isset($_SESSION['phpmailer_zip']) && file_exists($_SESSION['phpmailer_zip'])) {
                    @unlink($_SESSION['phpmailer_zip']);
                }
                unset($_SESSION['phpmailer_zip']);
                
                echo "<p>Redirecting to verification...</p>";
                echo "<script>setTimeout(function(){ window.location.href='verify-phpmailer.php'; }, 2000);</script>";
            } else {
                echo "<p class='error'>❌ Extraction failed. Permission issue.</p>";
                echo "<p>Please run: <code>chmod -R 755 $vendorPath</code></p>";
            }
        } else {
            echo "<p class='error'>❌ Failed to open zip file</p>";
        }
    } else {
        echo "<p class='error'>❌ ZipArchive class not available</p>";
    }
    echo "</div>";
}

echo "</div></body></html>";
?>

