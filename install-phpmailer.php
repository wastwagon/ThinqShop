<?php
/**
 * PHPMailer Installation Helper
 * This script helps install PHPMailer if composer is not available
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Install PHPMailer</title>";
echo "<style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 20px; background: #f5f5f5; }
    .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #0dcaf0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #0d6efd; }
    .code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: 'Courier New', monospace; }
    .btn { display: inline-block; padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
    .btn:hover { background: #0b5ed7; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>📧 PHPMailer Installation Helper</h1>";

$vendorPath = __DIR__ . '/vendor';
$autoloadPath = $vendorPath . '/autoload.php';
$phpmailerPath = $vendorPath . '/phpmailer/phpmailer';

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

echo "<h2>Installation Methods</h2>";

// Method 1: Check if composer is available
echo "<h3>Method 1: Using Composer (Recommended)</h3>";

$composerAvailable = false;
$composerPath = '';

// Check common composer locations
$composerPaths = [
    'composer',
    '/usr/local/bin/composer',
    '/usr/bin/composer',
    '/opt/homebrew/bin/composer'
];

foreach ($composerPaths as $path) {
    $output = [];
    $returnVar = 0;
    @exec("which $path 2>/dev/null", $output, $returnVar);
    if ($returnVar === 0 && !empty($output)) {
        $composerPath = $output[0];
        $composerAvailable = true;
        break;
    }
}

if ($composerAvailable) {
    echo "<p class='success'>✅ Composer found at: <code>$composerPath</code></p>";
    echo "<p>Run this command in your terminal:</p>";
    echo "<div class='code-block'>cd /Applications/XAMPP/xamppfiles/htdocs/ThinQShopping<br>";
    echo "$composerPath require phpmailer/phpmailer</div>";
    echo "<p>Or click the button below to attempt automatic installation:</p>";
    
    if (isset($_GET['install']) && $_GET['install'] === 'composer') {
        echo "<h4>Installing via Composer...</h4>";
        $command = "cd " . escapeshellarg(__DIR__) . " && $composerPath require phpmailer/phpmailer 2>&1";
        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);
        
        echo "<pre>";
        echo htmlspecialchars(implode("\n", $output));
        echo "</pre>";
        
        if (file_exists($autoloadPath)) {
            echo "<p class='success'>✅ Installation successful! <a href='test-email-debug.php'>Test Email Configuration</a></p>";
        } else {
            echo "<p class='error'>❌ Installation may have failed. Please check the output above.</p>";
        }
    } else {
        echo "<p><a href='?install=composer' class='btn'>Install PHPMailer via Composer</a></p>";
    }
} else {
    echo "<p class='warning'>⚠️ Composer not found in common locations.</p>";
    echo "<p>You can install composer from: <a href='https://getcomposer.org/download/' target='_blank'>https://getcomposer.org/download/</a></p>";
}

// Method 2: Manual download instructions
echo "<h3>Method 2: Manual Installation</h3>";
echo "<p>If composer is not available, you can manually download PHPMailer:</p>";
echo "<ol>";
echo "<li>Download PHPMailer from: <a href='https://github.com/PHPMailer/PHPMailer/releases' target='_blank'>GitHub Releases</a></li>";
echo "<li>Extract the files to: <code>$vendorPath/phpmailer/phpmailer/</code></li>";
echo "<li>Create an autoloader or manually include the files</li>";
echo "</ol>";

// Method 3: Create a simple autoloader
echo "<h3>Method 3: Quick Fix - Create Simple Autoloader</h3>";
echo "<p>This will create a basic autoloader that works with PHPMailer if you download it manually:</p>";

if (isset($_GET['create_autoloader'])) {
    if (!is_dir($vendorPath)) {
        mkdir($vendorPath, 0755, true);
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
    
    file_put_contents($autoloadPath, $autoloaderContent);
    echo "<p class='success'>✅ Autoloader created at: <code>$autoloadPath</code></p>";
    echo "<p class='info'>Now you need to download PHPMailer manually and place it in: <code>$phpmailerPath/</code></p>";
} else {
    echo "<p><a href='?create_autoloader=1' class='btn'>Create Autoloader File</a></p>";
}

// Check current status
echo "<h2>Current Status</h2>";
echo "<ul>";
echo "<li>Vendor directory exists: " . (is_dir($vendorPath) ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
echo "<li>Autoload file exists: " . (file_exists($autoloadPath) ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
echo "<li>PHPMailer directory exists: " . (is_dir($phpmailerPath) ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";

if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
    echo "<li>PHPMailer class available: " . (class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? "<span class='success'>✅ Yes</span>" : "<span class='error'>❌ No</span>") . "</li>";
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='test-email-debug.php' class='btn'>Back to Email Debug Tool</a></p>";
echo "<p><a href='admin/settings/email-settings.php' class='btn'>Email Settings</a></p>";

echo "</div></body></html>";
?>





