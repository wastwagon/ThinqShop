<?php
/**
 * Shipping Settings & Methods Management
 * Admin Dashboard - ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = false;

// Process shipping method add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_method'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $methodId = intval($_POST['method_id'] ?? 0);
        $methodName = sanitize($_POST['method_name'] ?? '');
        $methodCode = sanitize($_POST['method_code'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $basePrice = floatval($_POST['base_price'] ?? 0);
        $perKgPrice = floatval($_POST['per_kg_price'] ?? 0);
        $minDays = intval($_POST['min_days'] ?? 0);
        $maxDays = intval($_POST['max_days'] ?? 0);
        $availableCountries = $_POST['available_countries'] ?? [];
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $sortOrder = intval($_POST['sort_order'] ?? 0);
        
        if (empty($methodName) || empty($methodCode) || $basePrice <= 0 || $minDays <= 0 || $maxDays < $minDays) {
            $errors[] = 'Please fill all required fields correctly.';
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                $countriesJson = json_encode($availableCountries);
                
                if ($methodId > 0) {
                    // Update existing
                    $stmt = $conn->prepare("
                        UPDATE shipping_methods 
                        SET method_name = ?, method_code = ?, description = ?, base_price = ?, 
                            per_kg_price = ?, min_days = ?, max_days = ?, available_countries = ?,
                            is_active = ?, sort_order = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$methodName, $methodCode, $description, $basePrice, $perKgPrice, 
                                   $minDays, $maxDays, $countriesJson, $isActive, $sortOrder, $methodId]);
                } else {
                    // Insert new
                    $stmt = $conn->prepare("
                        INSERT INTO shipping_methods (
                            method_name, method_code, description, base_price, per_kg_price,
                            min_days, max_days, available_countries, is_active, sort_order, created_at
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$methodName, $methodCode, $description, $basePrice, $perKgPrice, 
                                   $minDays, $maxDays, $countriesJson, $isActive, $sortOrder]);
                }
                
                $conn->commit();
                $success = true;
                redirect('/admin/logistics/shipping-settings.php', 'Shipping method saved successfully!', 'success');
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Shipping Method Save Error: " . $e->getMessage());
                $errors[] = 'Failed to save shipping method: ' . $e->getMessage();
            }
        }
    }
}

// Process shipping settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        try {
            $conn->beginTransaction();
            
            $settings = [
                'free_shipping_threshold' => floatval($_POST['free_shipping_threshold'] ?? 0),
                'cod_fee_percentage' => floatval($_POST['cod_fee_percentage'] ?? 0),
                'insurance_enabled' => isset($_POST['insurance_enabled']) ? '1' : '0',
                'insurance_rate' => floatval($_POST['insurance_rate'] ?? 0),
                'fuel_surcharge_enabled' => isset($_POST['fuel_surcharge_enabled']) ? '1' : '0',
                'fuel_surcharge_rate' => floatval($_POST['fuel_surcharge_rate'] ?? 0),
                'overseas_surcharge' => floatval($_POST['overseas_surcharge'] ?? 0),
                'dimensional_factor' => floatval($_POST['dimensional_factor'] ?? 5000),
                'currency_conversion_rate' => floatval($_POST['currency_conversion_rate'] ?? 1)
            ];
            
            foreach ($settings as $key => $value) {
                $stmt = $conn->prepare("
                    INSERT INTO shipping_settings (setting_key, setting_value, updated_at)
                    VALUES (?, ?, NOW())
                    ON DUPLICATE KEY UPDATE setting_value = ?, updated_at = NOW()
                ");
                $stmt->execute([$key, $value, $value]);
            }
            
            $conn->commit();
            $success = true;
            redirect('/admin/logistics/shipping-settings.php', 'Shipping settings updated successfully!', 'success');
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Shipping Settings Save Error: " . $e->getMessage());
            $errors[] = 'Failed to save settings: ' . $e->getMessage();
        }
    }
}

// Delete shipping method
if (isset($_GET['delete_method'])) {
    $methodId = intval($_GET['delete_method']);
    try {
        $stmt = $conn->prepare("DELETE FROM shipping_methods WHERE id = ?");
        $stmt->execute([$methodId]);
        redirect('/admin/logistics/shipping-settings.php', 'Shipping method deleted successfully!', 'success');
    } catch (Exception $e) {
        error_log("Shipping Method Delete Error: " . $e->getMessage());
        redirect('/admin/logistics/shipping-settings.php', 'Failed to delete shipping method.', 'danger');
    }
}

// Get shipping methods
$stmt = $conn->query("SELECT * FROM shipping_methods ORDER BY sort_order ASC, method_name ASC");
$shippingMethods = $stmt->fetchAll();

// Get shipping settings
$stmt = $conn->query("SELECT setting_key, setting_value FROM shipping_settings");
$settingsData = $stmt->fetchAll();
$shippingSettings = [];
foreach ($settingsData as $setting) {
    $shippingSettings[$setting['setting_key']] = $setting['setting_value'];
}

// Get method for editing
$editMethod = null;
if (isset($_GET['edit_method'])) {
    $methodId = intval($_GET['edit_method']);
    $stmt = $conn->prepare("SELECT * FROM shipping_methods WHERE id = ?");
    $stmt->execute([$methodId]);
    $editMethod = $stmt->fetch();
}

// Available countries
$countries = [
    'GH' => 'Ghana',
    'CN' => 'China',
    'NG' => 'Nigeria',
    'US' => 'United States',
    'UK' => 'United Kingdom',
    'ZA' => 'South Africa',
    'KE' => 'Kenya',
    'TZ' => 'Tanzania',
    'UG' => 'Uganda'
];

ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Shipping Settings & Methods</h1>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Shipping Methods -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Shipping Methods</h5>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#methodModal">
                    <i class="fas fa-plus me-1"></i>Add Method
                </button>
            </div>
            <div class="card-body">
                <?php if (empty($shippingMethods)): ?>
                    <p class="text-muted">No shipping methods configured. Add your first method below.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Timeline</th>
                                    <th>Base Price</th>
                                    <th>Per Kg</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shippingMethods as $method): 
                                    $countriesList = json_decode($method['available_countries'] ?? '[]', true);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($method['method_name']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($method['method_code']); ?></small>
                                        <?php if ($method['description']): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($method['description']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $method['min_days']; ?>-<?php echo $method['max_days']; ?> days</td>
                                    <td><?php echo formatCurrency($method['base_price']); ?></td>
                                    <td><?php echo formatCurrency($method['per_kg_price']); ?>/kg</td>
                                    <td>
                                        <?php if ($method['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?edit_method=<?php echo $method['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete_method=<?php echo $method['id']; ?>" 
                                           class="btn btn-sm btn-outline-danger"
                                           onclick="return confirm('Are you sure you want to delete this shipping method?');">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Shipping Settings -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Shipping Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="save_settings" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Free Shipping Threshold (GHS)</label>
                        <input type="number" step="0.01" name="free_shipping_threshold" class="form-control" 
                               value="<?php echo htmlspecialchars($shippingSettings['free_shipping_threshold'] ?? '500.00'); ?>">
                        <small class="form-text text-muted">Minimum order amount for free shipping</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">COD Fee Percentage (%)</label>
                        <input type="number" step="0.1" name="cod_fee_percentage" class="form-control" 
                               value="<?php echo htmlspecialchars($shippingSettings['cod_fee_percentage'] ?? '2.5'); ?>">
                        <small class="form-text text-muted">Cash on Delivery fee percentage</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Overseas Surcharge (%)</label>
                        <input type="number" step="0.1" name="overseas_surcharge" class="form-control" 
                               value="<?php echo htmlspecialchars($shippingSettings['overseas_surcharge'] ?? '15.0'); ?>">
                        <small class="form-text text-muted">Additional charge for international shipping</small>
                    </div>
                    
                    <hr>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="insurance_enabled" class="form-check-input" id="insurance_enabled"
                               <?php echo ($shippingSettings['insurance_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="insurance_enabled">Enable Shipping Insurance</label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Insurance Rate (%)</label>
                        <input type="number" step="0.1" name="insurance_rate" class="form-control" 
                               value="<?php echo htmlspecialchars($shippingSettings['insurance_rate'] ?? '0.5'); ?>">
                        <small class="form-text text-muted">Insurance rate as percentage of shipment value</small>
                    </div>
                    
                    <hr>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="fuel_surcharge_enabled" class="form-check-input" id="fuel_surcharge_enabled"
                               <?php echo ($shippingSettings['fuel_surcharge_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="fuel_surcharge_enabled">Enable Fuel Surcharge</label>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Fuel Surcharge Rate (%)</label>
                        <input type="number" step="0.1" name="fuel_surcharge_rate" class="form-control" 
                               value="<?php echo htmlspecialchars($shippingSettings['fuel_surcharge_rate'] ?? '3.0'); ?>">
                        <small class="form-text text-muted">Fuel surcharge as percentage of shipping cost</small>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <label class="form-label">Dimensional Factor (Volumetric Weight)</label>
                        <input type="number" step="1" name="dimensional_factor" class="form-control" 
                               value="<?php echo htmlspecialchars($shippingSettings['dimensional_factor'] ?? '5000'); ?>">
                        <small class="form-text text-muted">
                            Used for volumetric weight calculation: (L × W × H) / Factor. 
                            Common values: 5000 (international metric), 139 (UPS/FedEx domestic), 166 (USPS)
                        </small>
                    </div>
                    
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-save me-1"></i>Save Settings
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Method Modal -->
<div class="modal fade" id="methodModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title"><?php echo $editMethod ? 'Edit' : 'Add'; ?> Shipping Method</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="save_method" value="1">
                    <input type="hidden" name="method_id" value="<?php echo $editMethod['id'] ?? 0; ?>">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Method Name <span class="text-danger">*</span></label>
                            <input type="text" name="method_name" class="form-control" required
                                   value="<?php echo htmlspecialchars($editMethod['method_name'] ?? ''); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Method Code <span class="text-danger">*</span></label>
                            <input type="text" name="method_code" class="form-control" required
                                   value="<?php echo htmlspecialchars($editMethod['method_code'] ?? ''); ?>"
                                   <?php echo $editMethod ? 'readonly' : ''; ?>>
                            <small class="form-text text-muted">Unique code (e.g., economy, standard, express)</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($editMethod['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Base Price (GHS) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="base_price" class="form-control" required min="0"
                                   value="<?php echo htmlspecialchars($editMethod['base_price'] ?? ''); ?>">
                            <small class="form-text text-muted">Price for first 1kg</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Per Kg Price (GHS) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="per_kg_price" class="form-control" required min="0"
                                   value="<?php echo htmlspecialchars($editMethod['per_kg_price'] ?? ''); ?>">
                            <small class="form-text text-muted">Price for each additional kg</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Min Days <span class="text-danger">*</span></label>
                            <input type="number" name="min_days" class="form-control" required min="1"
                                   value="<?php echo htmlspecialchars($editMethod['min_days'] ?? ''); ?>">
                            <small class="form-text text-muted">Minimum delivery days</small>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Max Days <span class="text-danger">*</span></label>
                            <input type="number" name="max_days" class="form-control" required min="1"
                                   value="<?php echo htmlspecialchars($editMethod['max_days'] ?? ''); ?>">
                            <small class="form-text text-muted">Maximum delivery days</small>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Available Countries</label>
                        <div class="row">
                            <?php 
                            $selectedCountries = $editMethod ? json_decode($editMethod['available_countries'] ?? '[]', true) : [];
                            foreach ($countries as $code => $name): 
                            ?>
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="available_countries[]" 
                                           value="<?php echo $code; ?>" id="country_<?php echo $code; ?>"
                                           <?php echo in_array($code, $selectedCountries) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="country_<?php echo $code; ?>">
                                        <?php echo htmlspecialchars($name); ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                                       <?php echo !$editMethod || $editMethod['is_active'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" class="form-control" min="0"
                                   value="<?php echo htmlspecialchars($editMethod['sort_order'] ?? '0'); ?>">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Method
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($editMethod): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var methodModal = new bootstrap.Modal(document.getElementById('methodModal'));
    methodModal.show();
});
</script>
<?php endif; ?>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Shipping Settings - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>

