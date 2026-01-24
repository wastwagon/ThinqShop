<?php
/**
 * Admin Shipments Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle status update
if (isset($_GET['update_status']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipmentId = intval($_GET['update_status']);
    $newStatus = sanitize($_POST['status'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/logistics/shipments.php', 'Invalid security token.', 'danger');
    }
    
    $validStatuses = ['booked', 'received_from_supplier', 'available_for_pickup', 'pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/logistics/shipments.php', 'Invalid status.', 'danger');
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
        
        // Get shipment details for notification
        $stmt = $conn->prepare("SELECT user_id, tracking_number FROM shipments WHERE id = ?");
        $stmt->execute([$shipmentId]);
        $shipment = $stmt->fetch();
        
        $conn->commit();
        
        logAdminAction($_SESSION['admin_id'], 'update_shipment_status', 'shipments', $shipmentId, ['status' => $newStatus]);
        
        // Send notification to user
        if ($shipment && file_exists(__DIR__ . '/../../includes/notification-helper.php')) {
            require_once __DIR__ . '/../../includes/notification-helper.php';
            
            $statusMessages = [
                'booked' => 'Your shipment has been booked',
                'received_from_supplier' => 'Your shipment has been received from supplier',
                'available_for_pickup' => 'Your shipment is available for pickup',
                'pickup_scheduled' => 'Pickup has been scheduled for your shipment',
                'picked_up' => 'Your shipment has been picked up',
                'in_transit' => 'Your shipment is in transit',
                'out_for_delivery' => 'Your shipment is out for delivery',
                'delivered' => 'Your shipment has been delivered',
                'cancelled' => 'Your shipment has been cancelled'
            ];
            
            $statusMessage = $statusMessages[$newStatus] ?? 'Your shipment status has been updated';
            $message = $statusMessage . '. Tracking: ' . $shipment['tracking_number'];
            if (!empty($location)) {
                $message .= ' - Location: ' . $location;
            }
            if (!empty($notes)) {
                $message .= ' - ' . $notes;
            }
            
            NotificationHelper::createUserNotification(
                $shipment['user_id'],
                'shipment',
                'Shipment Status Updated',
                $message,
                BASE_URL . '/user/shipments/view.php?id=' . $shipmentId
            );
        }
        
        redirect('/admin/logistics/shipments.php', 'Shipment status updated successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update Shipment Status Error: " . $e->getMessage());
        redirect('/admin/logistics/shipments.php', 'Failed to update shipment status.', 'danger');
    }
}

// Handle tracking number search
$trackingSearch = sanitize($_GET['tracking'] ?? '');

// If tracking number is provided, search for it and update status if needed
if (!empty($trackingSearch)) {
    $stmt = $conn->prepare("
        SELECT s.id, s.status, 
               s.forwarding_warehouse_id, fw.warehouse_name as forwarding_warehouse_name,
               s.destination_warehouse_id, dw.warehouse_name as destination_warehouse_name
        FROM shipments s
        LEFT JOIN warehouses fw ON s.forwarding_warehouse_id = fw.id
        LEFT JOIN warehouses dw ON s.destination_warehouse_id = dw.id
        WHERE s.tracking_number = ? 
        LIMIT 1
    ");
    $stmt->execute([$trackingSearch]);
    $foundShipment = $stmt->fetch();
    
    if ($foundShipment) {
        $shipmentId = $foundShipment['id'];
        $currentStatus = $foundShipment['status'];
        $forwardingWarehouseName = $foundShipment['forwarding_warehouse_name'] ?? 'Forwarding Warehouse';
        $destinationWarehouseName = $foundShipment['destination_warehouse_name'] ?? 'Lapaz Office - Accra - Ghana';
        
        // Progressive status updates based on current status
        $statusUpdated = false;
        $newStatus = null;
        $location = null;
        $notes = null;
        $successMessage = null;
        
        try {
            $conn->beginTransaction();
            
            // Scan 1: "booked" → "received_from_supplier"
            if ($currentStatus === 'booked') {
                $newStatus = 'received_from_supplier';
                $location = $forwardingWarehouseName;
                $notes = 'Parcel scanned and received at warehouse';
                $successMessage = 'Shipment found and marked as "Received From Supplier"!';
                
                // Update shipment status
                $stmt = $conn->prepare("UPDATE shipments SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $shipmentId]);
                
                // Add tracking entry
                $stmt = $conn->prepare("
                    INSERT INTO shipment_tracking (shipment_id, status, location, notes, admin_id, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$shipmentId, $newStatus, $location, $notes, $_SESSION['admin_id']]);
                
                $statusUpdated = true;
                
                // Log admin action
                if (function_exists('logAdminAction')) {
                    logAdminAction($_SESSION['admin_id'], 'scan_receive_shipment', 'shipments', $shipmentId, [
                        'tracking_number' => $trackingSearch,
                        'old_status' => 'booked',
                        'new_status' => 'received_from_supplier'
                    ]);
                }
            }
            // Scan 2: "received_from_supplier" → "available_for_pickup"
            elseif ($currentStatus === 'received_from_supplier') {
                $newStatus = 'available_for_pickup';
                $location = $destinationWarehouseName;
                $notes = 'Parcel arrived at destination warehouse and is available for pickup';
                $successMessage = 'Shipment found and marked as "Available for Pickup"!';
                
                // Update shipment status
                $stmt = $conn->prepare("UPDATE shipments SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $shipmentId]);
                
                // Add tracking entry
                $stmt = $conn->prepare("
                    INSERT INTO shipment_tracking (shipment_id, status, location, notes, admin_id, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$shipmentId, $newStatus, $location, $notes, $_SESSION['admin_id']]);
                
                $statusUpdated = true;
                
                // Log admin action
                if (function_exists('logAdminAction')) {
                    logAdminAction($_SESSION['admin_id'], 'scan_available_pickup', 'shipments', $shipmentId, [
                        'tracking_number' => $trackingSearch,
                        'old_status' => 'received_from_supplier',
                        'new_status' => 'available_for_pickup'
                    ]);
                }
            }
            // Scan 3: "available_for_pickup" → "picked_up"
            elseif ($currentStatus === 'available_for_pickup') {
                $newStatus = 'picked_up';
                $location = $destinationWarehouseName;
                $notes = 'Parcel picked up by customer';
                $successMessage = 'Shipment found and marked as "Picked Up"!';
                
                // Update shipment status
                $stmt = $conn->prepare("UPDATE shipments SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $shipmentId]);
                
                // Add tracking entry
                $stmt = $conn->prepare("
                    INSERT INTO shipment_tracking (shipment_id, status, location, notes, admin_id, created_at)
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$shipmentId, $newStatus, $location, $notes, $_SESSION['admin_id']]);
                
                $statusUpdated = true;
                
                // Log admin action
                if (function_exists('logAdminAction')) {
                    logAdminAction($_SESSION['admin_id'], 'scan_picked_up', 'shipments', $shipmentId, [
                        'tracking_number' => $trackingSearch,
                        'old_status' => 'available_for_pickup',
                        'new_status' => 'picked_up'
                    ]);
                }
            }
            
            $conn->commit();
            
            // Redirect with appropriate message
            if ($statusUpdated) {
                redirect('/admin/logistics/shipments/view.php?id=' . $shipmentId, $successMessage, 'success');
            } else {
                // Status is already "picked_up" or later, or cancelled - just redirect
                redirect('/admin/logistics/shipments/view.php?id=' . $shipmentId, 'Shipment found!', 'success');
            }
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Auto-update shipment status error: " . $e->getMessage());
            // Still redirect even if update fails, but with warning
            redirect('/admin/logistics/shipments/view.php?id=' . $shipmentId, 'Shipment found, but status update failed. Please update manually.', 'warning');
        }
    } else {
        // Show error message
        $searchError = 'Shipment with tracking number "' . htmlspecialchars($trackingSearch) . '" not found.';
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "s.status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM shipments s $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalShipments = $countStmt->fetch()['total'];
$totalPages = ceil($totalShipments / $perPage);

// Get shipments
$sql = "SELECT s.*, u.email, u.phone 
        FROM shipments s
        LEFT JOIN users u ON s.user_id = u.id
        $whereClause
        ORDER BY s.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$shipments = $stmt->fetchAll();

$pageTitle = 'Shipments - Admin - ' . APP_NAME;

// Use admin layout
ob_start();
?>

<div class="container-fluid">
    <h2 class="mb-4">Shipments Management</h2>
    
    <?php if (isset($searchError)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $searchError; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" id="searchForm" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label class="form-label">
                        <i class="fas fa-barcode me-1"></i> Search by Tracking Number
                    </label>
                    <div class="input-group">
                        <input type="text" 
                               name="tracking" 
                               id="tracking_search" 
                               class="form-control" 
                               placeholder="Enter or scan tracking number"
                               value="<?php echo htmlspecialchars($trackingSearch); ?>">
                        <button type="button" 
                                class="btn btn-primary" 
                                id="scan_tracking_btn"
                                title="Scan barcode">
                            <i class="fas fa-camera"></i>
                        </button>
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <small class="form-text text-muted">
                        Enter tracking number manually or scan the barcode from the parcel
                    </small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" id="status_filter" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="booked" <?php echo $statusFilter === 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="received_from_supplier" <?php echo $statusFilter === 'received_from_supplier' ? 'selected' : ''; ?>>Received from Supplier</option>
                        <option value="available_for_pickup" <?php echo $statusFilter === 'available_for_pickup' ? 'selected' : ''; ?>>Available for Pickup</option>
                        <option value="pickup_scheduled" <?php echo $statusFilter === 'pickup_scheduled' ? 'selected' : ''; ?>>Pickup Scheduled</option>
                        <option value="picked_up" <?php echo $statusFilter === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                        <option value="in_transit" <?php echo $statusFilter === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                        <option value="out_for_delivery" <?php echo $statusFilter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <?php if (!empty($trackingSearch)): ?>
                        <a href="<?php echo BASE_URL; ?>/admin/logistics/shipments.php?status=<?php echo $statusFilter; ?>" 
                           class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times"></i> Clear Search
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Camera Modal Container -->
    <div id="camera_modal_container"></div>
    
    <!-- Shipments Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($shipments)): ?>
                <p class="text-muted text-center py-5">No shipments found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tracking #</th>
                                <th>Customer</th>
                                <th>Weight</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shipments as $shipment): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($shipment['tracking_number']); ?></code></td>
                                <td>
                                    <div><?php echo htmlspecialchars($shipment['email'] ?? 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($shipment['phone'] ?? ''); ?></small>
                                </td>
                                <td><?php echo $shipment['weight']; ?> kg</td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $shipment['service_type'])); ?></td>
                                <td>$<?php echo number_format($shipment['total_price'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $shipment['status'] === 'delivered' ? 'success' : 
                                            ($shipment['status'] === 'cancelled' ? 'danger' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $shipment['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($shipment['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>/admin/logistics/shipments/view.php?id=<?php echo $shipment['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateStatusModal<?php echo $shipment['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Update Status Modal -->
                                    <div class="modal fade" id="updateStatusModal<?php echo $shipment['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="?update_status=<?php echo $shipment['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Shipment Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status <span class="text-danger">*</span></label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="booked" <?php echo $shipment['status'] === 'booked' ? 'selected' : ''; ?>>Booked</option>
                                                                <option value="received_from_supplier" <?php echo $shipment['status'] === 'received_from_supplier' ? 'selected' : ''; ?>>Received from Supplier</option>
                                                                <option value="available_for_pickup" <?php echo $shipment['status'] === 'available_for_pickup' ? 'selected' : ''; ?>>Available for Pickup</option>
                                                                <option value="pickup_scheduled" <?php echo $shipment['status'] === 'pickup_scheduled' ? 'selected' : ''; ?>>Pickup Scheduled</option>
                                                                <option value="picked_up" <?php echo $shipment['status'] === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                                                                <option value="in_transit" <?php echo $shipment['status'] === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                                                <option value="out_for_delivery" <?php echo $shipment['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                                                <option value="delivered" <?php echo $shipment['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Location (Optional)</label>
                                                            <input type="text" name="location" class="form-control" 
                                                                   placeholder="Current location">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Notes</label>
                                                            <textarea name="notes" class="form-control" rows="3"></textarea>
                                                        </div>
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Shipments pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php 
                        $paginationParams = [];
                        if ($statusFilter !== 'all') $paginationParams[] = 'status=' . urlencode($statusFilter);
                        if (!empty($trackingSearch)) $paginationParams[] = 'tracking=' . urlencode($trackingSearch);
                        $paginationQuery = !empty($paginationParams) ? '&' . implode('&', $paginationParams) : '';
                        ?>
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo $paginationQuery; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $paginationQuery; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo $paginationQuery; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Add QuaggaJS for barcode scanning
$additionalCSS = [
    'https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.css'
];

$additionalJS = [
    'https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js'
];

$inlineJS = '
// Barcode scanning functionality
let cameraStream = null;
let quaggaInitialized = false;

document.getElementById("scan_tracking_btn").addEventListener("click", function() {
    if (!("mediaDevices" in navigator && "getUserMedia" in navigator.mediaDevices)) {
        alert("Camera access is not supported in your browser. Please enter the tracking number manually.");
        return;
    }
    
    // Request camera permission
    navigator.mediaDevices.getUserMedia({ 
        video: { 
            facingMode: "environment",
            width: { ideal: 1280 },
            height: { ideal: 720 }
        } 
    })
    .then(stream => {
        cameraStream = stream;
        showCameraModal();
    })
    .catch(err => {
        alert("Camera access denied. Please allow camera access to scan tracking numbers, or enter the tracking number manually.");
        console.error("Camera error:", err);
    });
});

function showCameraModal() {
    const container = document.getElementById("camera_modal_container");
    container.innerHTML = `
        <div class="modal fade show" id="cameraModal" tabindex="-1" style="display: block; z-index: 1055;" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-camera me-2"></i>Scan Tracking Number
                        </h5>
                        <button type="button" class="btn-close" onclick="closeCameraModal()"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div id="scanner_container" style="position: relative; width: 100%; max-width: 640px; margin: 0 auto;">
                            <div id="interactive" style="width: 100%; height: 400px; border: 2px solid #ddd; border-radius: 8px; background: #000;"></div>
                            <div id="scan_overlay" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.8);">
                                Point camera at barcode
                            </div>
                        </div>
                        <p class="mt-3 text-muted">Position the barcode within the camera view</p>
                        <div id="scan_result" class="mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeCameraModal()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <button type="button" class="btn btn-primary" onclick="manualEntry()">
                            <i class="fas fa-keyboard"></i> Enter Manually
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show" style="z-index: 1050;"></div>
    `;
    
    // Initialize QuaggaJS
    setTimeout(() => {
        initQuagga();
    }, 100);
}

function initQuagga() {
    if (quaggaInitialized) {
        return;
    }
    
    Quagga.init({
        inputStream: {
            name: "Live",
            type: "LiveStream",
            target: document.querySelector("#interactive"),
            constraints: {
                width: 640,
                height: 480,
                facingMode: "environment"
            }
        },
        decoder: {
            readers: [
                "code_128_reader",
                "ean_reader",
                "ean_8_reader",
                "code_39_reader",
                "code_39_vin_reader",
                "codabar_reader",
                "upc_reader",
                "upc_e_reader",
                "i2of5_reader"
            ]
        },
        locate: true
    }, function(err) {
        if (err) {
            console.error("Quagga initialization error:", err);
            document.getElementById("scan_result").innerHTML = `
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    Scanner initialization failed. Please try entering the tracking number manually.
                </div>
            `;
            return;
        }
        
        quaggaInitialized = true;
        Quagga.start();
        
        // Listen for detection
        Quagga.onDetected(function(result) {
            const code = result.codeResult.code;
            if (code) {
                document.getElementById("tracking_search").value = code.trim();
                document.getElementById("scan_result").innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> 
                        Tracking number detected: <strong>${code}</strong>
                    </div>
                `;
                
                // Auto-submit after short delay
                setTimeout(() => {
                    closeCameraModal();
                    document.getElementById("searchForm").submit();
                }, 1000);
            }
        });
    });
}

function closeCameraModal() {
    if (quaggaInitialized) {
        Quagga.stop();
        quaggaInitialized = false;
    }
    
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    
    document.getElementById("camera_modal_container").innerHTML = "";
}

function manualEntry() {
    const trackingNumber = prompt("Please enter the tracking number:");
    if (trackingNumber && trackingNumber.trim()) {
        document.getElementById("tracking_search").value = trackingNumber.trim();
        closeCameraModal();
        document.getElementById("searchForm").submit();
    }
}

// Allow Enter key to submit search
document.getElementById("tracking_search").addEventListener("keypress", function(e) {
    if (e.key === "Enter") {
        e.preventDefault();
        document.getElementById("searchForm").submit();
    }
});
';

include __DIR__ . '/../../layouts/admin-layout.php';
?>







