<?php
/**
 * Admin General Settings
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get current settings
$stmt = $conn->query("SELECT * FROM settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

$errors = [];
$success = false;
$tab = $_GET['tab'] ?? 'general';

// Process settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        try {
            $conn->beginTransaction();
            
            // Update settings based on tab
            if ($tab === 'general') {
                $keys = ['site_name', 'site_email', 'site_phone', 'site_address', 'site_whatsapp'];
                foreach ($keys as $key) {
                    $value = sanitize($_POST[$key] ?? '');
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, updated_at) 
                        VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                    ");
                    $stmt->execute([$key, $value, $value]);
                }
            } elseif ($tab === 'paystack') {
                $keys = ['paystack_public_key', 'paystack_secret_key', 'paystack_mode'];
                foreach ($keys as $key) {
                    $value = sanitize($_POST[$key] ?? '');
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, updated_at) 
                        VALUES (?, ?, NOW())
                        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                    ");
                    $stmt->execute([$key, $value, $value]);
                }
            } elseif ($tab === 'exchange') {
                $rate = floatval($_POST['ghs_to_cny_rate'] ?? 0);
                if ($rate > 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO settings (setting_key, setting_value, updated_at) 
                        VALUES ('ghs_to_cny_rate', ?, NOW())
                        ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                    ");
                    $stmt->execute([$rate, $rate]);
                    
                    // Also update exchange_rates table (using the correct schema)
                    $stmt = $conn->prepare("
                        INSERT INTO exchange_rates (rate_ghs_to_cny, valid_from, admin_id, is_active, created_at) 
                        VALUES (?, NOW(), ?, 1, NOW())
                    ");
                    $stmt->execute([$rate, $_SESSION['admin_id']]);
                }
            }
            
            $conn->commit();
            $success = true;
            
            // Reload settings
            $stmt = $conn->query("SELECT * FROM settings");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            
            if (function_exists('logAdminAction')) {
                logAdminAction($_SESSION['admin_id'], 'update_settings', 'settings', 0, ['tab' => $tab]);
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Settings Update Error: " . $e->getMessage());
            $errors[] = 'Failed to update settings.';
        }
    }
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Settings</h1>
</div>

<!-- Settings Tabs -->
<ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'general' ? 'active' : ''; ?>" 
               href="?tab=general">General</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'paystack' ? 'active' : ''; ?>" 
               href="?tab=paystack">Paystack</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo $tab === 'exchange' ? 'active' : ''; ?>" 
               href="?tab=exchange">Exchange Rates</a>
        </li>
    </ul>
    
    <!-- General Settings -->
    <?php if ($tab === 'general'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">General Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site Name</label>
                            <input type="text" name="site_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_name'] ?? APP_NAME); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site Email</label>
                            <input type="email" name="site_email" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Site Phone</label>
                            <input type="tel" name="site_phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_phone'] ?? ''); ?>"
                                   placeholder="+233 XX XXX XXXX">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="tel" name="site_whatsapp" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['site_whatsapp'] ?? ''); ?>"
                                   placeholder="+233 XX XXX XXXX">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Site Address</label>
                        <textarea name="site_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings['site_address'] ?? ''); ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Paystack Settings -->
    <?php if ($tab === 'paystack'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Paystack Payment Gateway Settings</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    These settings override the configuration file. Use with caution.
                </div>
                
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Public Key</label>
                        <input type="text" name="paystack_public_key" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['paystack_public_key'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Secret Key</label>
                        <input type="password" name="paystack_secret_key" class="form-control" 
                               value="<?php echo htmlspecialchars($settings['paystack_secret_key'] ?? ''); ?>">
                        <small class="form-text text-muted">Leave blank to keep current value</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Mode</label>
                        <select name="paystack_mode" class="form-select">
                            <option value="test" <?php echo ($settings['paystack_mode'] ?? 'test') === 'test' ? 'selected' : ''; ?>>Test Mode</option>
                            <option value="live" <?php echo ($settings['paystack_mode'] ?? '') === 'live' ? 'selected' : ''; ?>>Live Mode</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Exchange Rate Settings -->
    <?php if ($tab === 'exchange'): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Exchange Rate Settings</h5>
            </div>
            <div class="card-body">
                <?php if ($success && $tab === 'exchange'): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <i class="fas fa-check-circle"></i> Exchange rate updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errors) && $tab === 'exchange'): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="?tab=exchange">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">GHS to CNY Rate <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text bg-light">1 GHS =</span>
                            <input type="number" step="0.0001" min="0.0001" name="ghs_to_cny_rate" class="form-control" 
                                   value="<?php echo htmlspecialchars($settings['ghs_to_cny_rate'] ?? ''); ?>"
                                   placeholder="e.g., 1.8500" required>
                            <span class="input-group-text bg-light">CNY</span>
                        </div>
                        <small class="form-text text-muted mt-2 d-block">
                            <i class="fas fa-info-circle"></i> Enter the current exchange rate (e.g., 1.8500 means 1 Ghana Cedi = 1.85 Chinese Yuan)
                        </small>
                    </div>
                    
                    <?php
                    // Get rate history
                    try {
                        $stmt = $conn->query("SELECT * FROM exchange_rates ORDER BY created_at DESC LIMIT 5");
                        $rateHistory = $stmt->fetchAll();
                        if (!empty($rateHistory)):
                    ?>
                    <div class="mb-3">
                        <h6>Recent Rate History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Rate (GHS to CNY)</th>
                                        <th>Valid From</th>
                                        <th>Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rateHistory as $hist): ?>
                                    <tr>
                                        <td><?php echo number_format($hist['rate_ghs_to_cny'], 4); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($hist['valid_from'])); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($hist['created_at'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <?php 
                        endif;
                    } catch (Exception $e) {
                        // Table might not exist yet, ignore
                    }
                    ?>
                    
                    <button type="submit" class="btn btn-primary">Update Exchange Rate</button>
                </form>
            </div>
        </div>
    <?php endif; ?>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Settings - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';







