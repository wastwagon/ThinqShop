<?php
/**
 * Email Debugging Tool
 * This script helps diagnose email sending issues
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/email-service.php';

echo "<!DOCTYPE html><html><head><title>Email Debug Tool</title>";
echo "<style>
    body { font-family: monospace; padding: 20px; background: #f5f5f5; }
    .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; }
    h1 { color: #333; border-bottom: 3px solid #0d6efd; padding-bottom: 10px; }
    .success { color: #198754; font-weight: bold; }
    .error { color: #dc3545; font-weight: bold; }
    .warning { color: #ffc107; font-weight: bold; }
    .info { color: #0dcaf0; }
    pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; border-left: 4px solid #0d6efd; }
    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
    th { background: #f8f9fa; font-weight: bold; }
    .test-section { margin: 30px 0; padding: 20px; background: #f8f9fa; border-radius: 5px; }
</style></head><body>";

echo "<div class='container'>";
echo "<h1>🔍 Email Configuration Debug Tool</h1>";

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check PHPMailer availability
    echo "<div class='test-section'>";
    echo "<h2>1. PHPMailer Check</h2>";
    
    $phpmailerPath = __DIR__ . '/vendor/autoload.php';
    if (file_exists($phpmailerPath)) {
        require_once $phpmailerPath;
        if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            echo "<p class='success'>✅ PHPMailer is installed and available</p>";
        } else {
            echo "<p class='error'>❌ PHPMailer file exists but class not found</p>";
        }
    } else {
        echo "<p class='warning'>⚠️ PHPMailer not found at: $phpmailerPath</p>";
        echo "<p class='info'>The system will use PHP mail() function as fallback (may not work with SMTP)</p>";
    }
    echo "</div>";
    
    // Get email settings
    echo "<div class='test-section'>";
    echo "<h2>2. Current Email Settings</h2>";
    
    $settings = EmailService::getEmailSettings();
    
    echo "<table>";
    echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
    
    $checks = [
        'smtp_enabled' => ['label' => 'SMTP Enabled', 'required' => false],
        'smtp_host' => ['label' => 'SMTP Host', 'required' => true],
        'smtp_port' => ['label' => 'SMTP Port', 'required' => true],
        'smtp_encryption' => ['label' => 'Encryption', 'required' => true],
        'smtp_username' => ['label' => 'SMTP Username', 'required' => true],
        'smtp_password' => ['label' => 'SMTP Password', 'required' => true, 'mask' => true],
        'from_email' => ['label' => 'From Email', 'required' => true],
        'from_name' => ['label' => 'From Name', 'required' => false]
    ];
    
    foreach ($checks as $key => $info) {
        $value = $settings[$key] ?? 'Not set';
        $masked = ($info['mask'] ?? false) && !empty($value) ? str_repeat('*', min(strlen($value), 8)) : $value;
        $status = '';
        
        if ($info['required'] && empty($value)) {
            $status = "<span class='error'>❌ Missing</span>";
        } elseif (!empty($value)) {
            $status = "<span class='success'>✅ Set</span>";
        } else {
            $status = "<span class='info'>ℹ️ Optional</span>";
        }
        
        echo "<tr>";
        echo "<td><strong>{$info['label']}</strong></td>";
        echo "<td>" . htmlspecialchars($masked) . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";
    
    // Test email sending
    echo "<div class='test-section'>";
    echo "<h2>3. Test Email Sending</h2>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
        $testEmail = filter_var($_POST['test_email'] ?? '', FILTER_VALIDATE_EMAIL);
        
        if (!$testEmail) {
            echo "<p class='error'>❌ Invalid email address</p>";
        } else {
            echo "<p class='info'>Attempting to send test email to: <strong>$testEmail</strong></p>";
            echo "<pre>";
            
            // Capture output
            ob_start();
            
            $result = EmailService::send(
                $testEmail,
                'Test Email from ' . APP_NAME . ' - ' . date('Y-m-d H:i:s'),
                '<h2>Test Email</h2><p>This is a test email from your ' . APP_NAME . ' platform.</p><p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p><p>If you received this email, your SMTP configuration is working correctly.</p>',
                true
            );
            
            $output = ob_get_clean();
            if ($output) {
                echo htmlspecialchars($output);
            }
            
            echo "</pre>";
            
            if ($result['status']) {
                echo "<p class='success'>✅ Email sent successfully!</p>";
                echo "<p class='info'>Please check your inbox and spam folder for the test email.</p>";
                
                if (isset($result['debug']) && !empty($result['debug'])) {
                    echo "<h3>SMTP Debug Output:</h3>";
                    echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
                }
            } else {
                echo "<p class='error'>❌ Failed to send email</p>";
                echo "<p class='error'><strong>Error:</strong> " . htmlspecialchars($result['message']) . "</p>";
                
                if (isset($result['debug']) && !empty($result['debug'])) {
                    echo "<h3>SMTP Debug Output:</h3>";
                    echo "<pre>" . htmlspecialchars($result['debug']) . "</pre>";
                }
            }
        }
    } else {
        echo "<form method='POST'>";
        echo "<p>Enter an email address to send a test email:</p>";
        echo "<input type='email' name='test_email' placeholder='your-email@example.com' required style='padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<button type='submit' style='padding: 10px 20px; background: #0d6efd; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;'>Send Test Email</button>";
        echo "</form>";
    }
    
    echo "</div>";
    
    // Check PHP mail() function
    echo "<div class='test-section'>";
    echo "<h2>4. PHP mail() Function Check</h2>";
    
    if (function_exists('mail')) {
        echo "<p class='success'>✅ PHP mail() function is available</p>";
        $mailTest = @mail('test@example.com', 'Test', 'Test', 'From: test@example.com');
        if ($mailTest) {
            echo "<p class='info'>ℹ️ mail() function executed (may not actually send without proper server configuration)</p>";
        } else {
            echo "<p class='warning'>⚠️ mail() function returned false</p>";
        }
    } else {
        echo "<p class='error'>❌ PHP mail() function is not available</p>";
    }
    echo "</div>";
    
    // Recommendations
    echo "<div class='test-section'>";
    echo "<h2>5. Recommendations</h2>";
    
    $issues = [];
    
    if (!file_exists($phpmailerPath)) {
        $issues[] = "Install PHPMailer for proper SMTP support: <code>composer require phpmailer/phpmailer</code>";
    }
    
    if (empty($settings['smtp_username']) || empty($settings['smtp_password'])) {
        $issues[] = "Fill in SMTP username and password in Email Settings";
    }
    
    if (empty($settings['smtp_host'])) {
        $issues[] = "Configure SMTP host";
    }
    
    if (!empty($issues)) {
        echo "<ul>";
        foreach ($issues as $issue) {
            echo "<li class='warning'>⚠️ $issue</li>";
        }
        echo "</ul>";
    } else {
        echo "<p class='success'>✅ All basic settings are configured</p>";
        echo "<p class='info'>If emails are still not being received:</p>";
        echo "<ul>";
        echo "<li>Check spam/junk folder</li>";
        echo "<li>Verify Gmail App Password is correct (if using Gmail)</li>";
        echo "<li>Check firewall allows outbound connections on SMTP ports (587, 465)</li>";
        echo "<li>Review SMTP debug output above for specific errors</li>";
        echo "<li>Check server error logs for detailed error messages</li>";
        echo "</ul>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</div></body></html>";
?>





