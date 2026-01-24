<?php
/**
 * View Shipment Details - Admin
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$shipmentId = intval($_GET['id'] ?? 0);

if ($shipmentId <= 0) {
    redirect('/admin/logistics/shipments.php', 'Invalid shipment ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();

// Get shipment details with warehouse information
$stmt = $conn->prepare("
    SELECT s.*, 
           u.email, u.phone as user_phone,
           pa.full_name as pickup_name, pa.phone as pickup_phone, pa.street as pickup_street, 
           pa.city as pickup_city, pa.region as pickup_region, pa.landmark as pickup_landmark,
           da.full_name as delivery_name, da.phone as delivery_phone, da.street as delivery_street, 
           da.city as delivery_city, da.region as delivery_region, da.landmark as delivery_landmark,
           fw.warehouse_name as forwarding_warehouse_name, fw.city as forwarding_warehouse_city,
           dw.warehouse_name as destination_warehouse_name, dw.city as destination_warehouse_city
    FROM shipments s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN addresses pa ON s.pickup_address_id = pa.id
    LEFT JOIN addresses da ON s.delivery_address_id = da.id
    LEFT JOIN warehouses fw ON s.forwarding_warehouse_id = fw.id
    LEFT JOIN warehouses dw ON s.destination_warehouse_id = dw.id
    WHERE s.id = ?
");
$stmt->execute([$shipmentId]);
$shipment = $stmt->fetch();

if (!$shipment) {
    redirect('/admin/logistics/shipments.php', 'Shipment not found.', 'danger');
}

// Get tracking history
$stmt = $conn->prepare("
    SELECT st.*, au.username as admin_username
    FROM shipment_tracking st
    LEFT JOIN admin_users au ON st.admin_id = au.id
    WHERE st.shipment_id = ?
    ORDER BY st.created_at ASC
");
$stmt->execute([$shipmentId]);
$trackingHistory = $stmt->fetchAll();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/logistics/shipments/view.php?id=' . $shipmentId, 'Invalid security token.', 'danger');
    }
    
    $newStatus = sanitize($_POST['status'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    $validStatuses = ['booked', 'received_from_supplier', 'available_for_pickup', 'pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/logistics/shipments/view.php?id=' . $shipmentId, 'Invalid status.', 'danger');
    }
    
    try {
        $conn->beginTransaction();
        
        // Update shipment status
        $stmt = $conn->prepare("UPDATE shipments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $shipmentId]);
        
        // Add tracking entry
        $stmt = $conn->prepare("
            INSERT INTO shipment_tracking (shipment_id, status, location, notes, admin_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$shipmentId, $newStatus, $location, $notes, $_SESSION['admin_id']]);
        
        $conn->commit();
        
        logAdminAction($_SESSION['admin_id'], 'update_shipment_status', 'shipments', $shipmentId, ['status' => $newStatus]);
        redirect('/admin/logistics/shipments/view.php?id=' . $shipmentId, 'Shipment status updated successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update Shipment Status Error: " . $e->getMessage());
        redirect('/admin/logistics/shipments/view.php?id=' . $shipmentId, 'Failed to update shipment status.', 'danger');
    }
}

$pageTitle = 'Shipment Details - Admin - ' . APP_NAME;

// Use admin layout
ob_start();
?>

<div class="container-fluid">
    <div class="page-title-section mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <h1 class="page-title">Shipment #<?php echo htmlspecialchars($shipment['tracking_number']); ?></h1>
            <div>
                <a href="<?php echo BASE_URL; ?>/admin/logistics/shipments.php" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-arrow-left"></i> Back to Shipments
                </a>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
                    <i class="fas fa-edit"></i> Update Status
                </button>
            </div>
        </div>
    </div>
    
    <!-- Shipment Details -->
    <div class="row">
        <div class="col-lg-8">
            <!-- Customer & Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Shipment Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Tracking Number:</strong><br>
                            <code class="h5"><?php echo htmlspecialchars($shipment['tracking_number']); ?></code>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Status:</strong><br>
                            <span class="badge bg-<?php 
                                echo $shipment['status'] === 'delivered' ? 'success' : 
                                    ($shipment['status'] === 'cancelled' ? 'danger' : 'info'); 
                            ?> fs-6">
                                <?php echo ucfirst(str_replace('_', ' ', $shipment['status'])); ?>
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Service Type:</strong><br>
                            <?php echo ucfirst(str_replace('_', ' ', $shipment['service_type'])); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Payment Status:</strong><br>
                            <span class="badge bg-<?php 
                                echo $shipment['payment_status'] === 'success' ? 'success' : 
                                    ($shipment['payment_status'] === 'failed' ? 'danger' : 'warning'); 
                            ?>">
                                <?php echo ucfirst($shipment['payment_status']); ?>
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Payment Method:</strong><br>
                            <?php echo ucfirst(str_replace('_', ' ', $shipment['payment_method'])); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Booking Date:</strong><br>
                            <?php echo date('F d, Y h:i A', strtotime($shipment['created_at'])); ?>
                        </div>
                        <?php if ($shipment['pickup_date']): ?>
                        <div class="col-md-6 mb-3">
                            <strong>Pickup Date:</strong><br>
                            <?php echo date('F d, Y', strtotime($shipment['pickup_date'])); ?>
                            <?php if ($shipment['pickup_time_slot']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($shipment['pickup_time_slot']); ?></small>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($shipment['estimated_delivery']): ?>
                        <div class="col-md-6 mb-3">
                            <strong>Estimated Delivery:</strong><br>
                            <?php echo date('F d, Y', strtotime($shipment['estimated_delivery'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Addresses -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Pickup Address</h6>
                        </div>
                        <div class="card-body">
                            <strong><?php echo htmlspecialchars($shipment['pickup_name'] ?? 'N/A'); ?></strong><br>
                            <?php echo htmlspecialchars($shipment['pickup_street'] ?? ''); ?><br>
                            <?php if ($shipment['pickup_landmark']): ?>
                                <?php echo htmlspecialchars($shipment['pickup_landmark']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($shipment['pickup_city'] ?? ''); ?>, 
                            <?php echo htmlspecialchars($shipment['pickup_region'] ?? ''); ?><br>
                            <?php if ($shipment['pickup_phone']): ?>
                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($shipment['pickup_phone']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2 text-success"></i>Delivery Address</h6>
                        </div>
                        <div class="card-body">
                            <strong><?php echo htmlspecialchars($shipment['delivery_name'] ?? 'N/A'); ?></strong><br>
                            <?php echo htmlspecialchars($shipment['delivery_street'] ?? ''); ?><br>
                            <?php if ($shipment['delivery_landmark']): ?>
                                <?php echo htmlspecialchars($shipment['delivery_landmark']); ?><br>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($shipment['delivery_city'] ?? ''); ?>, 
                            <?php echo htmlspecialchars($shipment['delivery_region'] ?? ''); ?><br>
                            <?php if ($shipment['delivery_phone']): ?>
                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($shipment['delivery_phone']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tracking History -->
            <?php if (!empty($trackingHistory)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-route me-2"></i>Tracking History</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($trackingHistory as $index => $track): ?>
                        <div class="d-flex mb-3">
                            <div class="flex-shrink-0">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <i class="fas fa-<?php echo $index === count($trackingHistory) - 1 ? 'check' : 'circle'; ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1"><?php echo ucfirst(str_replace('_', ' ', $track['status'])); ?></h6>
                                <?php if ($track['location']): ?>
                                    <p class="mb-1 text-muted"><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($track['location']); ?></p>
                                <?php endif; ?>
                                <?php if ($track['notes']): ?>
                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($track['notes']); ?></p>
                                <?php endif; ?>
                                <small class="text-muted">
                                    <?php echo date('F d, Y h:i A', strtotime($track['created_at'])); ?>
                                    <?php if ($track['admin_username']): ?>
                                        <br>Updated by: <?php echo htmlspecialchars($track['admin_username']); ?>
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <?php if ($index < count($trackingHistory) - 1): ?>
                        <div class="ms-5 mb-3" style="border-left: 2px solid #dee2e6; height: 20px; margin-left: 20px;"></div>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Customer Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Customer Information</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Email:</strong><br><?php echo htmlspecialchars($shipment['email'] ?? 'N/A'); ?></p>
                    <?php if ($shipment['user_phone']): ?>
                        <p class="mb-0"><strong>Phone:</strong><br><?php echo htmlspecialchars($shipment['user_phone']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Package Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-box me-2"></i>Package Details</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Weight:</strong> <?php echo number_format($shipment['weight'], 2); ?> kg</p>
                    <?php if ($shipment['dimensions']): ?>
                        <p class="mb-0"><strong>Dimensions:</strong> <?php echo htmlspecialchars($shipment['dimensions']); ?></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Payment Details -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Payment Details</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Base Price:</strong> $<?php echo number_format($shipment['base_price'], 2); ?></p>
                    <?php if ($shipment['weight_price'] > 0): ?>
                        <p class="mb-2"><strong>Weight Price:</strong> $<?php echo number_format($shipment['weight_price'], 2); ?></p>
                    <?php endif; ?>
                    <?php if ($shipment['cod_amount'] > 0): ?>
                        <p class="mb-2"><strong>COD Amount:</strong> $<?php echo number_format($shipment['cod_amount'], 2); ?></p>
                    <?php endif; ?>
                    <hr>
                    <p class="mb-0 h5"><strong>Total:</strong> $<?php echo number_format($shipment['total_price'], 2); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="update_status" value="1">
                <div class="modal-header">
                    <h5 class="modal-title">Update Shipment Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" id="statusSelect" class="form-select" required>
                            <option value="booked" <?php echo $shipment['status'] === 'booked' ? 'selected' : ''; ?>>Booked</option>
                            <option value="received_from_supplier" <?php echo $shipment['status'] === 'received_from_supplier' ? 'selected' : ''; ?>>Received from Supplier</option>
                            <option value="available_for_pickup" <?php echo $shipment['status'] === 'available_for_pickup' ? 'selected' : ''; ?>>Available for Pickup</option>
                            <option value="pickup_scheduled" <?php echo $shipment['status'] === 'pickup_scheduled' ? 'selected' : ''; ?>>Pickup Scheduled</option>
                            <option value="picked_up" <?php echo $shipment['status'] === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                            <option value="in_transit" <?php echo $shipment['status'] === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                            <option value="out_for_delivery" <?php echo $shipment['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $shipment['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $shipment['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" id="locationInput" class="form-control" placeholder="Current location (optional)">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tracking Number (for scanning)</label>
                        <div class="input-group">
                            <input type="text" id="scanTrackingInput" class="form-control" placeholder="Scan or enter tracking number">
                            <button type="button" class="btn btn-outline-primary" id="scanTrackingBtn" title="Scan tracking number">
                                <i class="fas fa-camera"></i> Scan
                            </button>
                        </div>
                        <small class="form-text text-muted">Scan tracking number to verify package arrival</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Additional notes (optional)"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Camera scan functionality
document.getElementById('scanTrackingBtn').addEventListener('click', function() {
    if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
        // Request camera permission
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
            .then(function(stream) {
                // Camera access granted - for now, prompt for manual entry
                // In production, you would integrate a barcode scanner library like QuaggaJS or ZXing
                const trackingNumber = prompt('Please enter the tracking number from the package:');
                if (trackingNumber) {
                    document.getElementById('scanTrackingInput').value = trackingNumber.trim();
                    // If tracking number matches shipment, auto-fill location
                    const shipmentTracking = '<?php echo htmlspecialchars($shipment['tracking_number']); ?>';
                    if (trackingNumber.trim() === shipmentTracking) {
                        document.getElementById('locationInput').value = '<?php echo htmlspecialchars($shipment['forwarding_warehouse_name'] ?? 'Forwarding Warehouse'); ?>';
                        // Auto-select "Received from Supplier" status
                        document.getElementById('statusSelect').value = 'received_from_supplier';
                    }
                }
                // Stop camera stream
                stream.getTracks().forEach(track => track.stop());
            })
            .catch(function(err) {
                console.error('Camera access error:', err);
                // Fallback to manual entry
                const trackingNumber = prompt('Camera access denied. Please enter the tracking number manually:');
                if (trackingNumber) {
                    document.getElementById('scanTrackingInput').value = trackingNumber.trim();
                }
            });
    } else {
        // Fallback for browsers without camera support
        const trackingNumber = prompt('Camera not available. Please enter the tracking number manually:');
        if (trackingNumber) {
            document.getElementById('scanTrackingInput').value = trackingNumber.trim();
        }
    }
});

// Auto-update location when "Received from Supplier" is selected
document.getElementById('statusSelect').addEventListener('change', function() {
    if (this.value === 'received_from_supplier' && !document.getElementById('locationInput').value) {
        document.getElementById('locationInput').value = '<?php echo htmlspecialchars($shipment['forwarding_warehouse_name'] ?? 'Forwarding Warehouse'); ?>';
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../../layouts/admin-layout.php';
?>


