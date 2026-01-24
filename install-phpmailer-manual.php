<?php
/**
 * Manual PHPMailer Installation Instructions
 * Provides step-by-step instructions when automatic installation fails
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Manual PHPMailer Installation</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    h2 { color: #555; margin-top: 30px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #0dcaf0; }
    pre, .code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #0d6efd; font-family: 'Courier New', monospace; }
    .step { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 5px; border-left: 4px solid #0d6efd; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    .btn:hover { background: #0b5ed7; }
    ol li { margin: 10px 0; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>📦 Manual PHPMailer Installation Guide</h1>";

$vendorPath = __DIR__ . '/vendor';
$phpmailerPath = $vendorPath . '/phpmailer/phpmailer';
$autoloadPath = $vendorPath . '/autoload.php';

// Check if already installed
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo "<p class='success'>✅ PHPMailer is already installed!</p>";
        echo "<p><a href='test-email-debug.php' class='btn'>Test Email Configuration</a></p>";
        echo "</div></body></html>";
        exit;
    }
}

echo "<p>Since automatic installation failed due to permissions, follow these manual steps:</p>";

echo "<h2>Method 1: Using Terminal (Recommended)</h2>";
echo "<div class='step'>";
echo "<h3>Step 1: Open Terminal</h3>";
echo "<p>Open Terminal on your Mac and run these commands:</p>";
echo "<div class='code-block'>";
echo "cd /Applications/XAMPP/xamppfiles/htdocs/ThinQShopping<br><br>";
echo "# Fix permissions<br>";
echo "sudo chmod -R 755 vendor<br>";
echo "sudo chown -R _www:staff vendor<br><br>";
echo "# Install PHPMailer using composer (if available)<br>";
echo "composer require phpmailer/phpmailer<br><br>";
echo "# OR if composer is not available, download manually:<br>";
echo "curl -L https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip -o /tmp/phpmailer.zip<br>";
echo "unzip /tmp/phpmailer.zip -d vendor/<br>";
echo "mv vendor/PHPMailer-master vendor/phpmailer/phpmailer<br>";
echo "</div>";
echo "</div>";

echo "<h2>Method 2: Manual Download and Extract</h2>";
echo "<div class='step'>";
echo "<h3>Step 1: Download PHPMailer</h3>";
echo "<p>Download PHPMailer from GitHub:</p>";
echo "<p><a href='https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip' target='_blank' class='btn'>Download PHPMailer ZIP</a></p>";
echo "</div>";

echo "<div class='step'>";
echo "<h3>Step 2: Extract Files</h3>";
echo "<ol>";
echo "<li>Extract the downloaded ZIP file</li>";
echo "<li>Rename the extracted folder from <code>PHPMailer-master</code> to <code>phpmailer</code></li>";
echo "<li>Copy the <code>phpmailer</code> folder to: <code>$vendorPath/phpmailer/</code></li>";
echo "<li>The final path should be: <code>$phpmailerPath/</code></li>";
echo "</ol>";
echo "</div>";

echo "<div class='step'>";
echo "<h3>Step 3: Create Autoloader</h3>";
echo "<p>Create a file at: <code>$autoloadPath</code></p>";
echo "<p>With this content:</p>";
echo "<pre>";
echo htmlspecialchars('<?php
/**
 * Simple Autoloader for PHPMailer
 */
spl_autoload_register(function ($class) {
    $prefix = \'PHPMailer\\PHPMailer\\\';
    $base_dir = __DIR__ . \'/phpmailer/phpmailer/src/\';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace(\'\\\\\', \'/\', $relative_class) . \'.php\';
    
    if (file_exists($file)) {
        require $file;
    }
});
');
echo "</pre>";

if (isset($_GET['create_autoloader'])) {
    if (!is_dir($vendorPath)) {
        @mkdir($vendorPath, 0755, true);
    }
    
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
        echo "<p class='success'>✅ Autoloader created successfully!</p>";
    } else {
        echo "<p class='error'>❌ Failed to create autoloader. Please create it manually.</p>";
    }
} else {
    echo "<p><a href='?create_autoloader=1' class='btn'>Create Autoloader File</a></p>";
}
echo "</div>";

echo "<div class='step'>";
echo "<h3>Step 4: Verify Installation</h3>";
echo "<p>After completing the steps above, <a href='test-email-debug.php' class='btn'>run the debug tool</a> to verify PHPMailer is installed correctly.</p>";
echo "</div>";

echo "<h2>Quick Fix: Fix Permissions</h2>";
echo "<div class='step'>";
echo "<p>If you have terminal access, run these commands to fix permissions:</p>";
echo "<div class='code-block'>";
echo "cd /Applications/XAMPP/xamppfiles/htdocs/ThinQShopping<br>";
echo "sudo chmod -R 755 vendor<br>";
echo "sudo chown -R _www:staff vendor<br>";
echo "</div>";
echo "<p>Then try the automatic installation again: <a href='download-phpmailer.php' class='btn'>Try Automatic Installation</a></p>";
echo "</div>";

echo "<h2>Current Status</h2>";
echo "<ul>";
echo "<li>Vendor directory exists: " . (is_dir($vendorPath) ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
echo "<li>Vendor directory writable: " . (is_writable($vendorPath) ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
echo "<li>PHPMailer directory exists: " . (is_dir($phpmailerPath) ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
echo "<li>Autoload file exists: " . (file_exists($autoloadPath) ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    echo "<li>PHPMailer class available: " . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='test-email-debug.php' class='btn'>Back to Email Debug Tool</a></p>";
echo "<p><a href='download-phpmailer.php' class='btn'>Try Automatic Installation Again</a></p>";

echo "</div></body></html>";
?>





