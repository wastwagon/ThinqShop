<?php
/**
 * Verify PHPMailer Installation
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>PHPMailer Verification</title>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; }
</style></head><body>";
echo "<div class='container'>";
echo "<h1>PHPMailer Installation Verification</h1>";

$vendorPath = __DIR__ . '/vendor';
$autoloadPath = $vendorPath . '/autoload.php';

// Check vendor directory
echo "<h3>1. Vendor Directory</h3>";
if (is_dir($vendorPath)) {
    echo "<p class='success'>✅ Vendor directory exists</p>";
} else {
    echo "<p class='error'>❌ Vendor directory not found</p>";
}

// Check autoloader
echo "<h3>2. Autoloader</h3>";
if (file_exists($autoloadPath)) {
    echo "<p class='success'>✅ Autoloader exists</p>";
    require_once $autoloadPath;
} else {
    echo "<p class='error'>❌ Autoloader not found</p>";
}

// Check PHPMailer files
echo "<h3>3. PHPMailer Files</h3>";
$possiblePaths = [
    $vendorPath . '/phpmailer/phpmailer/src/PHPMailer.php',
    $vendorPath . '/PHPMailer-master/src/PHPMailer.php',
    $vendorPath . '/phpmailer/src/PHPMailer.php',
];

$found = false;
$foundPath = null;
foreach ($possiblePaths as $path) {
    if (file_exists($path)) {
        echo "<p class='success'>✅ PHPMailer found at: " . str_replace(__DIR__ . '/', '', $path) . "</p>";
        $found = true;
        $foundPath = $path;
        break;
    }
}

if (!$found) {
    echo "<p class='error'>❌ PHPMailer files not found</p>";
    echo "<p>Please run: <a href='download-and-install.php'>download-and-install.php</a> to install PHPMailer</p>";
}

// Check if class loads
echo "<h3>4. Class Loading</h3>";

// Try to load PHPMailer if files exist but class not loaded
if (!$found && file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

// Also try direct include if autoloader didn't work
if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer') && $foundPath) {
    require_once $foundPath;
    require_once dirname($foundPath) . '/SMTP.php';
    require_once dirname($foundPath) . '/Exception.php';
}

if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
    echo "<p class='success'>✅ PHPMailer class loaded successfully!</p>";
    echo "<p class='success'><strong>Installation is complete and working!</strong></p>";
    echo "<hr>";
    echo "<p><a href='test-email-debug.php' style='background:#198754;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Test Email</a></p>";
    echo "<p><a href='admin/settings/email-settings.php' style='background:#0d6efd;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Email Settings</a></p>";
} else {
    echo "<p class='error'>❌ PHPMailer class not loaded</p>";
    if (!$found) {
        echo "<p>Please run: <a href='download-and-install.php'>download-and-install.php</a> to install PHPMailer</p>";
    } else {
        echo "<p>Files found but class not loading. Please check the autoloader.</p>";
    }
}

echo "</div></body></html>";
?>

