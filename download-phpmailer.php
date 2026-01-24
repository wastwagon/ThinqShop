<?php
/**
 * Download and Install PHPMailer
 * This script downloads PHPMailer directly from GitHub
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300); // 5 minutes

echo "<!DOCTYPE html><html><head><title>Download PHPMailer</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #0dcaf0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #0d6efd; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    .btn:hover { background: #0b5ed7; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>📥 Download and Install PHPMailer</h1>";

$vendorPath = __DIR__ . '/vendor';
$phpmailerPath = $vendorPath . '/phpmailer/phpmailer';
$autoloadPath = $vendorPath . '/autoload.php';

if (isset($_GET['download'])) {
    echo "<h2>Downloading PHPMailer...</h2>";
    
    // Create vendor directory if it doesn't exist
    if (!is_dir($vendorPath)) {
        if (!@mkdir($vendorPath, 0755, true)) {
            echo "<p class='error'>❌ Failed to create vendor directory. Please create it manually with proper permissions:</p>";
            echo "<div class='code-block'>mkdir -p $vendorPath<br>chmod 755 $vendorPath</div>";
            echo "<p>Or run: <code>sudo chmod -R 755 " . dirname($vendorPath) . "</code></p>";
            echo "</div></body></html>";
            exit;
        }
    }
    
    // Check if directory is writable
    if (!is_writable($vendorPath)) {
        echo "<p class='error'>❌ Vendor directory is not writable. Please fix permissions:</p>";
        echo "<div class='code-block'>chmod 755 $vendorPath</div>";
        echo "<p>Or run: <code>sudo chmod -R 755 $vendorPath</code></p>";
        echo "</div></body></html>";
        exit;
    }
    
    // Use system temp directory for download
    $tempDir = sys_get_temp_dir();
    $zipFile = $tempDir . '/phpmailer-' . time() . '.zip';
    
    // PHPMailer GitHub release URL (latest stable)
    $zipUrl = 'https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip';
    
    echo "<p class='info'>Downloading from GitHub...</p>";
    
    // Download the zip file to temp directory
    $zipContent = @file_get_contents($zipUrl);
    
    if ($zipContent === false) {
        echo "<p class='error'>❌ Failed to download PHPMailer. Please check your internet connection.</p>";
        echo "<p>Alternative: Download manually from <a href='https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip' target='_blank'>GitHub</a> and extract to: <code>$phpmailerPath</code></p>";
    } else {
        // Write to temp directory first
        if (@file_put_contents($zipFile, $zipContent) === false) {
            echo "<p class='error'>❌ Failed to save downloaded file. Permission denied.</p>";
            echo "<p><strong>Solution:</strong> Please run these commands in terminal:</p>";
            echo "<div class='code-block'>";
            echo "cd /Applications/XAMPP/xamppfiles/htdocs/ThinQShopping<br>";
            echo "sudo chmod -R 755 vendor<br>";
            echo "sudo chown -R _www:staff vendor<br>";
            echo "</div>";
            echo "<p>Or manually create the vendor directory and set permissions.</p>";
            echo "</div></body></html>";
            exit;
        }
        echo "<p class='success'>✅ Download complete</p>";
        
        // Extract zip file
        echo "<p class='info'>Extracting files...</p>";
        
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($zipFile) === TRUE) {
                // Extract to vendor/phpmailer/
                $zip->extractTo($vendorPath);
                $zip->close();
                
                // Rename extracted folder
                $extractedPath = $vendorPath . '/PHPMailer-master';
                if (is_dir($extractedPath)) {
                    if (is_dir($phpmailerPath)) {
                        // Remove old directory
                        function deleteDirectory($dir) {
                            if (!file_exists($dir)) return true;
                            if (!is_dir($dir)) return unlink($dir);
                            foreach (scandir($dir) as $item) {
                                if ($item == '.' || $item == '..') continue;
                                if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) return false;
                            }
                            return rmdir($dir);
                        }
                        deleteDirectory($phpmailerPath);
                    }
                    rename($extractedPath, $phpmailerPath);
                }
                
                // Remove zip file
                @unlink($zipFile);
                
                echo "<p class='success'>✅ Extraction complete</p>";
                
                // Create autoloader
                echo "<p class='info'>Creating autoloader...</p>";
                
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
                
                file_put_contents($autoloadPath, $autoloaderContent);
                echo "<p class='success'>✅ Autoloader created</p>";
                
                // Verify installation
                require_once $autoloadPath;
                if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                    echo "<p class='success'>✅ PHPMailer installed successfully!</p>";
                    echo "<p><a href='test-email-debug.php' class='btn'>Test Email Configuration</a></p>";
                    echo "<p><a href='admin/settings/email-settings.php' class='btn'>Go to Email Settings</a></p>";
                } else {
                    echo "<p class='error'>❌ PHPMailer files extracted but class not found. Please check the installation.</p>";
                }
            } else {
                echo "<p class='error'>❌ Failed to extract zip file</p>";
            }
        } else {
            echo "<p class='error'>❌ ZipArchive class not available. Please install php-zip extension or extract manually.</p>";
            echo "<p>Downloaded file: <code>$zipFile</code></p>";
            echo "<p>Please extract it manually to: <code>$phpmailerPath</code></p>";
        }
    }
} else {
    echo "<p>This script will download and install PHPMailer from GitHub.</p>";
    echo "<p><strong>Requirements:</strong></p>";
    echo "<ul>";
    echo "<li>Internet connection</li>";
    echo "<li>PHP ZipArchive extension (usually included)</li>";
    echo "<li>Write permissions to the vendor directory</li>";
    echo "</ul>";
    
    echo "<p><a href='?download=1' class='btn' style='background: #198754;'>Download and Install PHPMailer</a></p>";
    
    // Check requirements
    echo "<h2>System Check</h2>";
    echo "<ul>";
    echo "<li>ZipArchive available: " . (class_exists('ZipArchive') ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
    echo "<li>Vendor directory writable: " . (is_writable(dirname($vendorPath)) ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
    echo "<li>Allow URL fopen: " . (ini_get('allow_url_fopen') ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><a href='test-email-debug.php' class='btn'>Back to Email Debug Tool</a></p>";
echo "<p><a href='install-phpmailer-manual.php' class='btn' style='background: #ffc107;'>Manual Installation Guide</a></p>";

echo "</div></body></html>";
?>

