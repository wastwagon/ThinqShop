<?php
/**
 * Ship Now Confirmation Page
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$trackingNumber = $_GET['tracking'] ?? '';

if (empty($trackingNumber)) {
    redirect('/modules/logistics/ship-now/', 'Invalid tracking number.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get shipment details
$stmt = $conn->prepare("
    SELECT s.*, 
           fw.warehouse_name as forwarding_warehouse_name, fw.city as forwarding_city, fw.country as forwarding_country,
           dw.warehouse_name as destination_warehouse_name, dw.city as destination_city, dw.country as destination_country
    FROM shipments s
    LEFT JOIN warehouses fw ON s.forwarding_warehouse_id = fw.id
    LEFT JOIN warehouses dw ON s.destination_warehouse_id = dw.id
    WHERE s.tracking_number = ? AND s.user_id = ?
");
$stmt->execute([$trackingNumber, $userId]);
$shipment = $stmt->fetch();

if (!$shipment) {
    redirect('/modules/logistics/ship-now/', 'Shipment not found.', 'danger');
}

// Prepare content
ob_start();
?>

<div class="page-title-section">
    <h1 class="page-title">Shipment Confirmed</h1>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Shipment Request Confirmed</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-success">
                    <strong>Success!</strong> Your shipment request has been created successfully.
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Tracking Number:</strong><br>
                        <span class="h5 text-primary"><?php echo htmlspecialchars($shipment['tracking_number']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-primary"><?php echo ucfirst($shipment['status']); ?></span>
                    </div>
                </div>
                
                <hr>
                
                <h6 class="fw-bold mb-3">Shipment Details</h6>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Forwarding Warehouse:</strong></div>
                    <div class="col-md-8"><?php echo htmlspecialchars($shipment['forwarding_warehouse_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Destination Warehouse:</strong></div>
                    <div class="col-md-8"><?php echo htmlspecialchars($shipment['destination_warehouse_name'] ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Shipping Method:</strong></div>
                    <div class="col-md-8"><?php echo strtoupper($shipment['shipping_method_type'] ?? 'N/A'); ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Estimated Delivery:</strong></div>
                    <div class="col-md-8"><?php echo $shipment['estimated_delivery'] ? date('F j, Y', strtotime($shipment['estimated_delivery'])) : 'TBD'; ?></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Total Price:</strong></div>
                    <div class="col-md-8"><strong class="text-primary">$<?php echo number_format($shipment['total_price'], 2); ?></strong></div>
                </div>
                
                <?php if (!empty($shipment['notes'])): ?>
                <hr>
                <h6 class="fw-bold mb-3">Notes</h6>
                <p class="text-muted"><?php echo nl2br(htmlspecialchars($shipment['notes'])); ?></p>
                <?php endif; ?>
                
                <div class="mt-4">
                    <a href="/user/shipments.php" class="btn btn-primary">
                        <i class="fas fa-list me-2"></i>View All Shipments
                    </a>
                    <a href="/modules/logistics/ship-now/" class="btn btn-outline-secondary">
                        <i class="fas fa-plus me-2"></i>Create Another Shipment
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Shipment Confirmed - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/user-layout.php';
?>

