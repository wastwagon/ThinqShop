<?php
/**
 * Admin Warehouse Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = '';

// Handle warehouse operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add' || $action === 'edit') {
            $id = $action === 'edit' ? intval($_POST['id'] ?? 0) : 0;
            $warehouseName = sanitize($_POST['warehouse_name'] ?? '');
            $warehouseCode = sanitize($_POST['warehouse_code'] ?? '');
            $receiverName = sanitize($_POST['receiver_name'] ?? '');
            $receiverPhone = sanitize($_POST['receiver_phone'] ?? '');
            $addressEnglish = sanitize($_POST['address_english'] ?? '');
            $addressChinese = sanitize($_POST['address_chinese'] ?? '');
            $district = sanitize($_POST['district'] ?? '');
            $city = sanitize($_POST['city'] ?? '');
            $country = sanitize($_POST['country'] ?? '');
            $warehouseType = sanitize($_POST['warehouse_type'] ?? 'forwarding');
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $sortOrder = intval($_POST['sort_order'] ?? 0);
            
            // Validation
            if (empty($warehouseName)) $errors[] = 'Warehouse name is required.';
            if (empty($receiverName)) $errors[] = 'Receiver name is required.';
            if (empty($receiverPhone)) $errors[] = 'Receiver phone is required.';
            if (empty($addressEnglish)) $errors[] = 'English address is required.';
            if (empty($city)) $errors[] = 'City is required.';
            if (empty($country)) $errors[] = 'Country is required.';
            if (!in_array($warehouseType, ['forwarding', 'destination'])) {
                $errors[] = 'Invalid warehouse type.';
            }
            
            if (empty($errors)) {
                try {
                    if ($action === 'add') {
                        $stmt = $conn->prepare("
                            INSERT INTO warehouses (
                                warehouse_name, warehouse_code, receiver_name, receiver_phone,
                                address_english, address_chinese, district, city, country,
                                warehouse_type, is_active, sort_order, created_at
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $stmt->execute([
                            $warehouseName, $warehouseCode, $receiverName, $receiverPhone,
                            $addressEnglish, $addressChinese, $district, $city, $country,
                            $warehouseType, $isActive, $sortOrder
                        ]);
                        $success = 'Warehouse added successfully.';
                        logAdminAction($_SESSION['admin_id'], 'add_warehouse', 'warehouses', $conn->lastInsertId());
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE warehouses SET
                                warehouse_name = ?, warehouse_code = ?, receiver_name = ?,
                                receiver_phone = ?, address_english = ?, address_chinese = ?,
                                district = ?, city = ?, country = ?, warehouse_type = ?,
                                is_active = ?, sort_order = ?, updated_at = NOW()
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $warehouseName, $warehouseCode, $receiverName, $receiverPhone,
                            $addressEnglish, $addressChinese, $district, $city, $country,
                            $warehouseType, $isActive, $sortOrder, $id
                        ]);
                        $success = 'Warehouse updated successfully.';
                        logAdminAction($_SESSION['admin_id'], 'update_warehouse', 'warehouses', $id);
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                try {
                    // Check if warehouse is in use
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM shipments WHERE forwarding_warehouse_id = ? OR destination_warehouse_id = ?");
                    $stmt->execute([$id, $id]);
                    $inUse = $stmt->fetch()['count'] > 0;
                    
                    if ($inUse) {
                        $errors[] = 'Cannot delete warehouse that is in use by shipments.';
                    } else {
                        $stmt = $conn->prepare("DELETE FROM warehouses WHERE id = ?");
                        $stmt->execute([$id]);
                        $success = 'Warehouse deleted successfully.';
                        logAdminAction($_SESSION['admin_id'], 'delete_warehouse', 'warehouses', $id);
                    }
                } catch (PDOException $e) {
                    $errors[] = 'Database error: ' . $e->getMessage();
                }
            }
        }
    }
}

// Get warehouse to edit
$editWarehouse = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM warehouses WHERE id = ?");
    $stmt->execute([$id]);
    $editWarehouse = $stmt->fetch();
}

// Get all warehouses
$stmt = $conn->prepare("SELECT * FROM warehouses ORDER BY warehouse_type, sort_order, warehouse_name");
$stmt->execute();
$warehouses = $stmt->fetchAll();

// Prepare content for layout
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Warehouse Management</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#warehouseModal">
            <i class="fas fa-plus me-2"></i>Add Warehouse
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
    
    <!-- Warehouses List -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Receiver</th>
                            <th>City, Country</th>
                            <th>Status</th>
                            <th>Sort</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($warehouses)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">No warehouses found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($warehouses as $warehouse): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($warehouse['warehouse_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($warehouse['warehouse_code'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $warehouse['warehouse_type'] === 'forwarding' ? 'primary' : 'info'; ?>">
                                        <?php echo ucfirst($warehouse['warehouse_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($warehouse['receiver_name']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($warehouse['receiver_phone']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($warehouse['city'] . ', ' . $warehouse['country']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $warehouse['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $warehouse['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo $warehouse['sort_order']; ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary" 
                                                onclick="editWarehouse(<?php echo htmlspecialchars(json_encode($warehouse)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this warehouse?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $warehouse['id']; ?>">
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

<!-- Warehouse Modal -->
<div class="modal fade" id="warehouseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="warehouseForm">
                <div class="modal-header">
                    <h5 class="modal-title" id="warehouseModalTitle">Add Warehouse</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" id="warehouseAction" value="add">
                    <input type="hidden" name="id" id="warehouseId">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Warehouse Name <span class="text-danger">*</span></label>
                            <input type="text" name="warehouse_name" id="warehouse_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Warehouse Code</label>
                            <input type="text" name="warehouse_code" id="warehouse_code" class="form-control">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Warehouse Type <span class="text-danger">*</span></label>
                            <select name="warehouse_type" id="warehouse_type" class="form-select" required>
                                <option value="forwarding">Forwarding</option>
                                <option value="destination">Destination</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sort Order</label>
                            <input type="number" name="sort_order" id="sort_order" class="form-control" value="0">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Receiver Name <span class="text-danger">*</span></label>
                            <input type="text" name="receiver_name" id="receiver_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Receiver Phone <span class="text-danger">*</span></label>
                            <input type="text" name="receiver_phone" id="receiver_phone" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address (English) <span class="text-danger">*</span></label>
                        <textarea name="address_english" id="address_english" class="form-control" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address (Chinese)</label>
                        <textarea name="address_chinese" id="address_chinese" class="form-control" rows="2"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">District</label>
                            <input type="text" name="district" id="district" class="form-control">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">City <span class="text-danger">*</span></label>
                            <input type="text" name="city" id="city" class="form-control" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Country <span class="text-danger">*</span></label>
                            <input type="text" name="country" id="country" class="form-control" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Warehouse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editWarehouse(warehouse) {
    document.getElementById('warehouseModalTitle').textContent = 'Edit Warehouse';
    document.getElementById('warehouseAction').value = 'edit';
    document.getElementById('warehouseId').value = warehouse.id;
    document.getElementById('warehouse_name').value = warehouse.warehouse_name || '';
    document.getElementById('warehouse_code').value = warehouse.warehouse_code || '';
    document.getElementById('warehouse_type').value = warehouse.warehouse_type || 'forwarding';
    document.getElementById('receiver_name').value = warehouse.receiver_name || '';
    document.getElementById('receiver_phone').value = warehouse.receiver_phone || '';
    document.getElementById('address_english').value = warehouse.address_english || '';
    document.getElementById('address_chinese').value = warehouse.address_chinese || '';
    document.getElementById('district').value = warehouse.district || '';
    document.getElementById('city').value = warehouse.city || '';
    document.getElementById('country').value = warehouse.country || '';
    document.getElementById('sort_order').value = warehouse.sort_order || 0;
    document.getElementById('is_active').checked = warehouse.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('warehouseModal')).show();
}

// Reset form when modal is closed
document.getElementById('warehouseModal').addEventListener('hidden.bs.modal', function() {
    document.getElementById('warehouseForm').reset();
    document.getElementById('warehouseModalTitle').textContent = 'Add Warehouse';
    document.getElementById('warehouseAction').value = 'add';
    document.getElementById('warehouseId').value = '';
});
</script>

<?php
$content = ob_get_clean();

// Include layout
$pageTitle = 'Warehouse Management - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>

