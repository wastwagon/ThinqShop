<?php
/**
 * Email Settings Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/email-service.php';

$db = new Database();
$conn = $db->getConnection();

// Check if email_settings table exists
try {
    $conn->query("SELECT 1 FROM email_settings LIMIT 1");
} catch (PDOException $e) {
    // Table doesn't exist - show error message
    $pageTitle = 'Database Migration Required - Admin - ' . APP_NAME;
    ob_start();
    ?>
    <div class="container-fluid">
        <div class="alert alert-warning">
            <h4>Database Tables Not Found</h4>
            <p>The email_settings table has not been created yet. Please run the database migration first.</p>
            <p><a href="<?php echo BASE_URL; ?>/database/migrations/" class="btn btn-primary">Run Database Migration</a></p>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    include __DIR__ . '/../../layouts/admin-layout.php';
    exit;
}

$success = '';
$error = '';

// Handle settings save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $conn->beginTransaction();
        
        $settings = [
            'smtp_enabled' => isset($_POST['smtp_enabled']) ? '1' : '0',
            'smtp_host' => sanitize($_POST['smtp_host'] ?? ''),
            'smtp_port' => sanitize($_POST['smtp_port'] ?? '587'),
            'smtp_encryption' => sanitize($_POST['smtp_encryption'] ?? 'tls'),
            'smtp_username' => sanitize($_POST['smtp_username'] ?? ''),
            'from_email' => sanitize($_POST['from_email'] ?? ''),
            'from_name' => sanitize($_POST['from_name'] ?? ''),
            'reply_to_email' => sanitize($_POST['reply_to_email'] ?? '')
        ];
        
        // Handle password separately - only update if new password is provided
        $newPassword = $_POST['smtp_password'] ?? '';
        if (!empty($newPassword)) {
            $settings['smtp_password'] = $newPassword; // Don't sanitize password
        }
        
        foreach ($settings as $key => $value) {
            $stmt = $conn->prepare("
                INSERT INTO email_settings (setting_key, setting_value, updated_at)
                VALUES (?, ?, NOW())
                ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
            ");
            $stmt->execute([$key, $value, $value]);
        }
        
        $conn->commit();
        $success = 'Email settings saved successfully';
    } catch (Exception $e) {
        $conn->rollBack();
        $error = 'Failed to save settings: ' . $e->getMessage();
    }
}

// Handle test email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $testEmail = sanitize($_POST['test_email_address'] ?? '');
    if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // Validate SMTP settings before attempting to send
        $settings = EmailService::getEmailSettings();
        
        if (!$settings['smtp_enabled']) {
            $error = 'SMTP is disabled. Please enable SMTP to send emails.';
        } elseif (empty($settings['smtp_username']) || empty($settings['smtp_password'])) {
            $error = 'SMTP username and password are required. Please fill in your SMTP credentials and save settings before sending a test email.';
        } elseif (empty($settings['smtp_host'])) {
            $error = 'SMTP host is required. Please configure your SMTP host.';
        } else {
            $result = EmailService::send(
                $testEmail,
                'Test Email from ' . APP_NAME,
                '<h2>Test Email</h2><p>This is a test email from your ' . APP_NAME . ' platform.</p><p>If you received this email, your email settings are configured correctly.</p><p><strong>Sent at:</strong> ' . date('Y-m-d H:i:s') . '</p>',
                true
            );
            
            if ($result['status']) {
                $success = 'Test email sent successfully to ' . $testEmail . '. Please check your inbox (and spam folder).';
                
                // Log debug info if available
                if (isset($result['debug']) && !empty($result['debug'])) {
                    error_log("Email Debug Info: " . $result['debug']);
                }
            } else {
                $errorMsg = 'Failed to send test email: ' . $result['message'];
                
                // Include debug info in error message if available
                if (isset($result['debug']) && !empty($result['debug'])) {
                    $errorMsg .= '<br><br><strong>Debug Information:</strong><br>';
                    $errorMsg .= '<pre style="background: #f8f9fa; padding: 10px; border-radius: 5px; font-size: 12px; max-height: 200px; overflow-y: auto;">' . htmlspecialchars($result['debug']) . '</pre>';
                }
                
                $error = $errorMsg;
            }
        }
    }
}

// Get current settings
try {
    $stmt = $conn->query("SELECT setting_key, setting_value FROM email_settings");
    $settings = [];
    foreach ($stmt->fetchAll() as $row) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    error_log("Email settings query error: " . $e->getMessage());
    $settings = [];
}

// Set defaults
$settings = array_merge([
    'smtp_enabled' => '1',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => '587',
    'smtp_encryption' => 'tls',
    'smtp_username' => '',
    'smtp_password' => '',
    'from_email' => defined('BUSINESS_EMAIL') ? BUSINESS_EMAIL : '',
    'from_name' => defined('BUSINESS_NAME') ? BUSINESS_NAME : APP_NAME,
    'reply_to_email' => defined('BUSINESS_EMAIL') ? BUSINESS_EMAIL : ''
], $settings);

// Prepare content for layout
ob_start();
?>
<div class="container-fluid">
    <div class="page-title-section mb-4">
        <h1 class="page-title">Email Settings</h1>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5>SMTP Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="smtp_enabled" class="form-check-input" id="smtp_enabled" 
                                   <?php echo $settings['smtp_enabled'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="smtp_enabled">Enable SMTP</label>
                        </div>
                        
                        <?php if ($settings['smtp_enabled'] && (empty($settings['smtp_username']) || empty($settings['smtp_password']))): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>SMTP credentials required:</strong> Please fill in your SMTP username and password to send emails.
                            </div>
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label">SMTP Host <span class="text-danger">*</span></label>
                            <input type="text" name="smtp_host" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" required>
                            <small class="text-muted">e.g., smtp.gmail.com, smtp.mailtrap.io</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SMTP Port <span class="text-danger">*</span></label>
                            <input type="number" name="smtp_port" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['smtp_port']); ?>" required>
                            <small class="text-muted">Common ports: 587 (TLS), 465 (SSL), 25</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Encryption <span class="text-danger">*</span></label>
                            <select name="smtp_encryption" class="form-select" required>
                                <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                                <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SMTP Username <span class="text-danger">*</span></label>
                            <input type="text" name="smtp_username" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['smtp_username']); ?>" 
                                   placeholder="your-email@gmail.com" required>
                            <small class="text-muted">Your email address (for Gmail) or SMTP username</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">SMTP Password <span class="text-danger">*</span></label>
                            <input type="password" name="smtp_password" class="form-control" 
                                   value="" 
                                   placeholder="<?php echo empty($settings['smtp_password']) ? 'Enter SMTP password' : 'Enter new password or leave blank to keep current'; ?>"
                                   <?php echo empty($settings['smtp_password']) ? 'required' : ''; ?>>
                            <small class="text-muted">
                                <?php if (empty($settings['smtp_password'])): ?>
                                    <strong class="text-danger">Required:</strong> For Gmail, use an App Password (not your regular password). 
                                    <a href="https://support.google.com/accounts/answer/185833" target="_blank">Learn how to create an App Password</a>
                                <?php else: ?>
                                    Leave blank to keep current password. For Gmail, use an App Password.
                                <?php endif; ?>
                            </small>
                        </div>

                        <hr>

                        <h6>Email Identity</h6>

                        <div class="mb-3">
                            <label class="form-label">From Email <span class="text-danger">*</span></label>
                            <input type="email" name="from_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['from_email']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">From Name <span class="text-danger">*</span></label>
                            <input type="text" name="from_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['from_name']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Reply-To Email</label>
                            <input type="email" name="reply_to_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['reply_to_email']); ?>">
                        </div>

                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" name="save_settings" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5>Test Email Configuration</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Test Email Address</label>
                            <input type="email" name="test_email_address" class="form-control" 
                                   placeholder="Enter email address to send test email" required>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" name="test_email" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Send Test Email
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5>Help & Instructions</h5>
                </div>
                <div class="card-body">
                    <h6>Gmail Setup</h6>
                    <ol>
                        <li>Enable 2-Factor Authentication</li>
                        <li>Generate an App Password</li>
                        <li>Use the app password in SMTP Password field</li>
                        <li>SMTP Host: smtp.gmail.com</li>
                        <li>Port: 587 (TLS) or 465 (SSL)</li>
                    </ol>

                    <h6 class="mt-4">Mailtrap (Testing)</h6>
                    <ul>
                        <li>SMTP Host: smtp.mailtrap.io</li>
                        <li>Port: 2525</li>
                        <li>Username/Password from Mailtrap dashboard</li>
                    </ul>

                    <h6 class="mt-4">Common Issues</h6>
                    <ul>
                        <li>Check firewall allows SMTP ports</li>
                        <li>Verify credentials are correct</li>
                        <li>Check spam folder for test emails</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Email Settings - Admin - ' . APP_NAME;
include __DIR__ . '/../../layouts/admin-layout.php';
?>
