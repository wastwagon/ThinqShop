<?php
/**
 * Admin Email Settings
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../config/email.php';

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = false;

// Get current settings from database or .env
$settings = [
    'smtp_host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
    'smtp_port' => $_ENV['SMTP_PORT'] ?? '587',
    'smtp_user' => $_ENV['SMTP_USER'] ?? '',
    'smtp_pass' => $_ENV['SMTP_PASS'] ?? '',
    'smtp_from_email' => $_ENV['SMTP_FROM_EMAIL'] ?? BUSINESS_EMAIL,
    'smtp_from_name' => $_ENV['SMTP_FROM_NAME'] ?? BUSINESS_NAME,
    'smtp_encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
];

// Get settings from database if they exist
$stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'email_%'");
while ($row = $stmt->fetch()) {
    $key = str_replace('email_', '', $row['setting_key']);
    if (isset($settings[$key])) {
        $settings[$key] = $row['setting_value'];
    }
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        try {
            $conn->beginTransaction();
            
            // Update SMTP settings in database
            $smtpSettings = [
                'email_smtp_host' => sanitize($_POST['smtp_host'] ?? ''),
                'email_smtp_port' => sanitize($_POST['smtp_port'] ?? ''),
                'email_smtp_user' => sanitize($_POST['smtp_user'] ?? ''),
                'email_smtp_pass' => sanitize($_POST['smtp_pass'] ?? ''),
                'email_smtp_from_email' => sanitize($_POST['smtp_from_email'] ?? ''),
                'email_smtp_from_name' => sanitize($_POST['smtp_from_name'] ?? ''),
                'email_smtp_encryption' => sanitize($_POST['smtp_encryption'] ?? 'tls'),
            ];
            
            foreach ($smtpSettings as $key => $value) {
                $stmt = $conn->prepare("
                    INSERT INTO settings (setting_key, setting_value, updated_at) 
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                ");
                $stmt->execute([$key, $value, $value]);
            }
            
            $conn->commit();
            $success = 'Email settings saved successfully. Note: You may need to update your .env file for changes to take effect.';
            
            // Reload settings
            $stmt = $conn->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'email_%'");
            while ($row = $stmt->fetch()) {
                $key = str_replace('email_', '', $row['setting_key']);
                if (isset($settings[$key])) {
                    $settings[$key] = $row['setting_value'];
                }
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = 'Error saving settings: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Email Settings - ' . APP_NAME;
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Email Settings</h1>
        <a href="<?php echo BASE_URL; ?>/admin/email/templates.php" class="btn btn-secondary">
            <i class="fas fa-envelope me-2"></i>Email Templates
        </a>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <h5 class="mb-4">SMTP Configuration</h5>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="smtp_host" class="form-label">SMTP Host <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                               value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" required>
                        <small class="form-text text-muted">e.g., smtp.gmail.com</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="smtp_port" class="form-label">SMTP Port <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                               value="<?php echo htmlspecialchars($settings['smtp_port']); ?>" required>
                        <small class="form-text text-muted">Common: 587 (TLS), 465 (SSL), 25</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="smtp_user" class="form-label">SMTP Username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="smtp_user" name="smtp_user" 
                               value="<?php echo htmlspecialchars($settings['smtp_user']); ?>" required>
                        <small class="form-text text-muted">Your email address</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="smtp_pass" class="form-label">SMTP Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" 
                               value="<?php echo htmlspecialchars($settings['smtp_pass']); ?>" required>
                        <small class="form-text text-muted">App password for Gmail</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="smtp_from_email" class="form-label">From Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="smtp_from_email" name="smtp_from_email" 
                               value="<?php echo htmlspecialchars($settings['smtp_from_email']); ?>" required>
                        <small class="form-text text-muted">Sender email address</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="smtp_from_name" class="form-label">From Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="smtp_from_name" name="smtp_from_name" 
                               value="<?php echo htmlspecialchars($settings['smtp_from_name']); ?>" required>
                        <small class="form-text text-muted">Sender display name</small>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="smtp_encryption" class="form-label">Encryption <span class="text-danger">*</span></label>
                    <select class="form-select" id="smtp_encryption" name="smtp_encryption" required>
                        <option value="tls" <?php echo $settings['smtp_encryption'] === 'tls' ? 'selected' : ''; ?>>TLS</option>
                        <option value="ssl" <?php echo $settings['smtp_encryption'] === 'ssl' ? 'selected' : ''; ?>>SSL</option>
                        <option value="none" <?php echo $settings['smtp_encryption'] === 'none' ? 'selected' : ''; ?>>None</option>
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <strong>Note:</strong> For Gmail, you need to:
                    <ul class="mb-0">
                        <li>Enable 2-Factor Authentication</li>
                        <li>Generate an App Password</li>
                        <li>Use the app password in SMTP Password field</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Save Settings
                </button>
            </form>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>






