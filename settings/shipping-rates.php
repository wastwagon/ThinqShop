<?php
/**
 * Admin Shipping Rates Management
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = '';

// Handle shipping rate operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add' || $action === 'edit') {
            $id = $action === 'edit' ? intval($_POST['id'] ?? 0) : 0;
            $methodType = sanitize($_POST['method_type'] ?? '');
            $rateId = sanitize($_POST['rate_id'] ?? '');
            $rateName = sanitize($_POST['rate_name'] ?? '');
            $rateValue = floatval($_POST['rate_value'] ?? 0);
            $rateType = sanitize($_POST['rate_type'] ?? 'kg');
            $currency = sanitize($_POST['currency'] ?? 'USD');
            $duration = sanitize($_POST['duration'] ?? '');
            $description = sanitize($_POST['description'] ?? '');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $sortOrder = intval($_POST['sort_order'] ?? 0);
            
            // Validation
            if (empty($methodType) || !in_array($methodType, ['air', 'sea'])) {
                $errors[] = 'Invalid shipping method type.';
            }
            if (empty($rateId)) $errors[] = 'Rate ID is required.';
            if (empty($rateName)) $errors[] = 'Rate name is required.';
            if ($rateValue <= 0) $errors[] = 'Rate value must be greater than 0.';
            if (!in_array($rateType, ['kg', 'cbm', 'unit'])) {
                $errors[] = 'Invalid rate type.';
            }
            
            if (empty($errors)) {
                try {
                    if ($action === 'add') {
                        // Check if rate_id already exists for this method_type
                        $stmt = $conn->prepare("SELECT id FROM shipping_rates WHERE method_type = ? AND rate_id = ?");
                        $stmt->execute([$methodType, $rateId]);
                        if ($stmt->fetch()) {
                            $errors[] = 'Rate ID already exists for this shipping method.';
                        } else {
                            $stmt = $conn->prepare("
                                INSERT INTO shipping_rates (
                                    method_type, rate_id, rate_name, rate_value, rate_type,
                                    currency, duration, description, is_active, sort_order, created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $stmt->execute([
                                $methodType, $rateId, $rateName, $rateValue, $rateType,
                                $currency, $duration, $description, $isActive, $sortOrder
                            ]);
                            $success = 'Shipping rate added successfully.';
                            logAdminAction($_SESSION['admin_id'], 'add_shipping_rate', 'shipping_rates', $conn->lastInsertId());
                        }
                    } else {
                        // Check if rate_id already exists for this method_type (excluding current record)
                        $stmt = $conn->prepare("SELECT id FROM shipping_rates WHERE method_type = ? AND rate_id = ? AND id != ?");
                        $stmt->execute([$methodType, $rateId, $id]);
                        if ($stmt->fetch()) {
                            $errors[] = 'Rate ID already exists for this shipping method.';
                        } else {
                            $stmt = $conn->prepare("
                                UPDATE shipping_rates SET
                                    method_type = ?, rate_id = ?, rate_name = ?, rate_value = ?,
                                    rate_type = ?, currency = ?, duration = ?, description = ?,
                                    is_active = ?, sort_order = ?, updated_at = NOW()
                                WHERE id = ?
                            ");
                            $stmt->execute([
                                $methodType, $rateId, $rateName, $rateValue, $rateType,
                                $currency, $duration, $description, $isActive, $sortOrder, $id
                            ]);
                            $success = 'Shipping rate updated successfully.';
                            logAdminAction($_SESSION['admin_id'], 'update_shipping_rate', 'shipping_rates', $id);
                        }
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    // Check if rate is in use
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM shipments WHERE shipping_rate_id = (SELECT rate_id FROM shipping_rates WHERE id = ?)");
                    $stmt->execute([$id]);
                    $inUse = $stmt->fetch()['count'] > 0;
                    
                    if ($inUse) {
                        $errors[] = 'Cannot delete shipping rate that is in use by shipments.';
                    } else {
                        $stmt = $conn->prepare("DELETE FROM shipping_rates WHERE id = ?");
                        $stmt->execute([$id]);
                        $success = 'Shipping rate deleted successfully.';
                        logAdminAction($_SESSION['admin_id'], 'delete_shipping_rate', 'shipping_rates', $id);
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get all shipping rates grouped by method type
try {
    // Check if table exists first
    $tableCheck = $conn->query("SHOW TABLES LIKE 'shipping_rates'");
    if ($tableCheck->rowCount() > 0) {
        $stmt = $conn->prepare("SELECT * FROM shipping_rates ORDER BY method_type, sort_order, rate_name");
        $stmt->execute();
        $allRates = $stmt->fetchAll();
    } else {
        // Table doesn't exist - show message
        $allRates = [];
        $errors[] = 'Shipping rates table does not exist. Please run the migration: database/migrations/create_shipping_rates_table.php';
    }
} catch (PDOException $e) {
    // If table doesn't exist, use empty array
    error_log("Shipping rates table error: " . $e->getMessage());
    $allRates = [];
    $errors[] = 'Error accessing shipping rates table: ' . $e->getMessage();
}

$airRates = array_filter($allRates, function($rate) { return $rate['method_type'] === 'air'; });
$seaRates = array_filter($allRates, function($rate) { return $rate['method_type'] === 'sea'; });

// Prepare content for layout
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Shipping Rates Management</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#rateModal">
            <i class="fas fa-plus me-2"></i>Add Shipping Rate
        </button>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo htmlspecialchars($success); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <!-- Air Rates -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-plane me-2"></i>Air Shipping Rates</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Rate ID</th>
                            <th>Name</th>
                            <th>Rate</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Sort</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($airRates)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No air rates found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($airRates as $rate): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($rate['rate_id']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($rate['rate_name']); ?></strong></td>
                                <td>$<?php echo number_format($rate['rate_value'], 2); ?>/<?php echo strtoupper($rate['rate_type']); ?></td>
                                <td><span class="badge bg-info"><?php echo strtoupper($rate['rate_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($rate['duration'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $rate['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $rate['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo $rate['sort_order']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editRate(<?php echo htmlspecialchars(json_encode($rate)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this rate?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $rate['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Sea Rates -->
    <div class="card">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-ship me-2"></i>Sea Shipping Rates</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Rate ID</th>
                            <th>Name</th>
                            <th>Rate</th>
                            <th>Type</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Sort</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($seaRates)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No sea rates found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($seaRates as $rate): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($rate['rate_id']); ?></code></td>
                                <td><strong><?php echo htmlspecialchars($rate['rate_name']); ?></strong></td>
                                <td>$<?php echo number_format($rate['rate_value'], 2); ?>/<?php echo strtoupper($rate['rate_type']); ?></td>
                                <td><span class="badge bg-info"><?php echo strtoupper($rate['rate_type']); ?></span></td>
                                <td><?php echo htmlspecialchars($rate['duration'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $rate['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $rate['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo $rate['sort_order']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editRate(<?php echo htmlspecialchars(json_encode($rate)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this rate?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $rate['id']; ?>">
                                            <button type="submit" class="btn btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Rate Modal -->
<div class="modal fade" id="rateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="rateForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="rateModalTitle">Add Shipping Rate</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" id="rateAction" value="add">
                    <input type="hidden" name="id" id="rateId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Shipping Method <span class="text-danger">*</span></label>
                            <select name="method_type" id="method_type" class="form-select" required>
                                <option value="air">Air</option>
                                <option value="sea">Sea</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Rate ID <span class="text-danger">*</span></label>
                            <input type="text" name="rate_id" id="rate_id" class="form-control" required placeholder="e.g., air_express">
                            <small class="form-text text-muted">Unique identifier (e.g., air_express, sea_standard)</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Rate Name <span class="text-danger">*</span></label>
                            <input type="text" name="rate_name" id="rate_name" class="form-control" required placeholder="e.g., Express Air (3-5 days)">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Rate Value <span class="text-danger">*</span></label>
                            <input type="number" name="rate_value" id="rate_value" class="form-control" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Rate Type <span class="text-danger">*</span></label>
                            <select name="rate_type" id="rate_type" class="form-select" required>
                                <option value="kg">Per Kilogram (kg)</option>
                                <option value="cbm">Per CBM</option>
                                <option value="unit">Per Unit</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Currency</label>
                            <input type="text" name="currency" id="currency" class="form-control" value="USD" maxlength="10" readonly>
                            <small class="form-text text-muted">All rates are in USD ($)</small>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Duration</label>
                            <input type="text" name="duration" id="duration" class="form-control" placeholder="e.g., 3-5 days">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                                <label class="form-check-label" for="is_active">Active</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="description" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Rate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editRate(rate) {
    document.getElementById('rateModalTitle').textContent = 'Edit Shipping Rate';
    document.getElementById('rateAction').value = 'edit';
    document.getElementById('rateId').value = rate.id;
    document.getElementById('method_type').value = rate.method_type || 'air';
    document.getElementById('rate_id').value = rate.rate_id || '';
    document.getElementById('rate_name').value = rate.rate_name || '';
    document.getElementById('rate_value').value = rate.rate_value || '';
    document.getElementById('rate_type').value = rate.rate_type || 'kg';
    document.getElementById('currency').value = rate.currency || 'USD';
    document.getElementById('duration').value = rate.duration || '';
    document.getElementById('description').value = rate.description || '';
    document.getElementById('sort_order').value = rate.sort_order || 0;
    document.getElementById('is_active').checked = rate.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('rateModal')).show();
}

// Reset form when modal is closed
document.getElementById('rateModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('rateForm').reset();
    document.getElementById('rateModalTitle').textContent = 'Add Shipping Rate';
    document.getElementById('rateAction').value = 'add';
    document.getElementById('rateId').value = '';
    document.getElementById('currency').value = 'USD';
    document.getElementById('is_active').checked = true;
});
</script>

<?php
$content = ob_get_clean();

// Include layout
$pageTitle = 'Shipping Rates Management - Admin - ' . APP_NAME;
include __DIR__ . '/../../layouts/admin-layout.php';
?>

