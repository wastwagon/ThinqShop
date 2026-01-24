<?php
/**
 * Move PHPMailer Now - Direct action
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Moving PHPMailer</title>";
echo "<meta http-equiv='refresh' content='2'>";
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
echo "<h1>📦 Moving PHPMailer to Vendor Directory</h1>";

$rootPath = __DIR__;
$sourcePath = $rootPath . '/PHPMailer-master';
$vendorPath = $rootPath . '/vendor';
$targetPath = $vendorPath . '/phpmailer/phpmailer';
$altTargetPath = $vendorPath . '/PHPMailer-master';

$step = $_GET['step'] ?? 1;

if ($step == 1) {
    echo "<div class='step'>";
    echo "<h3>Step 1: Checking PHPMailer-master folder</h3>";
    
    if (is_dir($sourcePath)) {
        echo "<p class='success'>✅ Found PHPMailer-master in root directory</p>";
        
        // Check if it has the required files
        $phpmailerFile = $sourcePath . '/src/PHPMailer.php';
        if (file_exists($phpmailerFile)) {
            echo "<p class='success'>✅ PHPMailer files verified</p>";
            echo "<p>Redirecting to move operation...</p>";
            echo "<script>setTimeout(function(){ window.location.href='?step=2'; }, 1000);</script>";
        } else {
            echo "<p class='error'>❌ PHPMailer.php not found in src folder</p>";
            echo "<p>Please verify the folder structure is correct.</p>";
        }
    } else {
        echo "<p class='error'>❌ PHPMailer-master folder not found</p>";
        echo "<p>Expected at: <code>$sourcePath</code></p>";
    }
    echo "</div>";
}

if ($step == 2) {
    echo "<div class='step'>";
    echo "<h3>Step 2: Setting up vendor directory</h3>";
    
    // Ensure vendor directory exists
    if (!is_dir($vendorPath)) {
        if (@mkdir($vendorPath, 0755, true)) {
            echo "<p class='success'>✅ Created vendor directory</p>";
        } else {
            echo "<p class='error'>❌ Failed to create vendor directory</p>";
        }
    } else {
        echo "<p class='success'>✅ Vendor directory exists</p>";
    }
    
    // Create phpmailer directory structure
    $phpmailerDir = $vendorPath . '/phpmailer';
    if (!is_dir($phpmailerDir)) {
        @mkdir($phpmailerDir, 0755, true);
    }
    
    echo "<p>Redirecting to move operation...</p>";
    echo "<script>setTimeout(function(){ window.location.href='?step=3'; }, 1000);</script>";
    echo "</div>";
}

if ($step == 3) {
    echo "<div class='step'>";
    echo "<h3>Step 3: Moving PHPMailer</h3>";
    
    $moved = false;
    
    // Try to rename/move to standard structure
    if (!is_dir($targetPath)) {
        if (@rename($sourcePath, $targetPath)) {
            echo "<p class='success'>✅ Successfully moved to vendor/phpmailer/phpmailer</p>";
            $moved = true;
        } else {
            // Try alternative location
            if (@rename($sourcePath, $altTargetPath)) {
                echo "<p class='success'>✅ Moved to vendor/PHPMailer-master</p>";
                $moved = true;
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
                
                if (copyRecursive($sourcePath, $targetPath)) {
                    echo "<p class='success'>✅ Copied to vendor/phpmailer/phpmailer</p>";
                    
                    // Remove original
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
                    echo "<p class='success'>✅ Removed original folder</p>";
                    $moved = true;
                } else {
                    echo "<p class='error'>❌ Copy failed. Permission issue.</p>";
                }
            }
        }
    } else {
        echo "<p class='info'>⚠️ Target directory already exists. Checking if valid...</p>";
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
            $moved = true;
        }
    }
    
    if ($moved) {
        echo "<p>Redirecting to verification...</p>";
        echo "<script>setTimeout(function(){ window.location.href='verify-phpmailer.php'; }, 2000);</script>";
    }
    echo "</div>";
}

echo "</div></body></html>";
?>

