<?php
/**
 * Booking Confirmation Page
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force no cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$trackingNumber = $_GET['tracking'] ?? '';

if (empty($trackingNumber)) {
    redirect('/modules/logistics/booking/', 'Invalid tracking number.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get shipment details
$stmt = $conn->prepare("
    SELECT s.*, 
           pa.full_name as pickup_name, pa.phone as pickup_phone, pa.street as pickup_street, 
           pa.city as pickup_city, pa.region as pickup_region, 
           pa.landmark as pickup_landmark,
           da.full_name as delivery_name, da.phone as delivery_phone, da.street as delivery_street, 
           da.city as delivery_city, da.region as delivery_region,
           da.landmark as delivery_landmark
    FROM shipments s
    LEFT JOIN addresses pa ON s.pickup_address_id = pa.id
    LEFT JOIN addresses da ON s.delivery_address_id = da.id
    WHERE s.tracking_number = ? AND s.user_id = ?
");
$stmt->execute([$trackingNumber, $userId]);
$shipment = $stmt->fetch();

if (!$shipment) {
    redirect('/modules/logistics/booking/', 'Shipment not found.', 'danger');
}

// Get shipping method details - try to get from shipment notes or use default
// Note: shipments table might not have shipping_method_id, so we'll use service_type
$shippingMethod = null;
if (!empty($shipment['notes'])) {
    // Try to extract shipping method from notes if stored there
    $notes = json_decode($shipment['notes'], true);
    if (is_array($notes) && isset($notes['shipping_method_id'])) {
        $stmt = $conn->prepare("SELECT * FROM shipping_methods WHERE id = ?");
        $stmt->execute([$notes['shipping_method_id']]);
        $shippingMethod = $stmt->fetch();
    }
}

// If no method found, try to get from service_type or use default
if (!$shippingMethod) {
    // For now, we'll just display the service_type
    $shippingMethod = ['name' => ucfirst(str_replace('_', ' ', $shipment['service_type'] ?? 'standard'))];
}

// Get tracking history
$stmt = $conn->prepare("
    SELECT * FROM shipment_tracking 
    WHERE shipment_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$shipment['id']]);
$trackingHistory = $stmt->fetchAll();

// Start output buffering
ob_start();
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Success Message -->
            <div class="alert alert-success text-center">
                <i class="fas fa-check-circle fa-3x mb-3"></i>
                <h3>Shipment Booked Successfully!</h3>
                <p class="mb-0">Your shipment has been confirmed. Tracking Number: <strong><?php echo htmlspecialchars($trackingNumber); ?></strong></p>
            </div>
            
            <!-- Shipment Details -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-box me-2"></i>Shipment Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Tracking Number:</strong><br>
                            <span class="h5 text-primary"><?php echo htmlspecialchars($trackingNumber); ?></span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Booking Date:</strong><br>
                            <?php echo date('F d, Y h:i A', strtotime($shipment['created_at'])); ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Status:</strong><br>
                            <span class="badge bg-<?php 
                                echo $shipment['status'] === 'booked' ? 'info' : 
                                    ($shipment['status'] === 'delivered' ? 'success' : 
                                    ($shipment['status'] === 'cancelled' ? 'danger' : 'warning')); 
                            ?>">
                                <?php echo ucfirst($shipment['status']); ?>
                            </span>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Payment Status:</strong><br>
                            <span class="badge bg-<?php 
                                echo $shipment['payment_status'] === 'success' ? 'success' : 
                                    ($shipment['payment_status'] === 'pending' ? 'warning' : 'danger'); 
                            ?>">
                                <?php echo ucfirst($shipment['payment_status']); ?>
                            </span>
                        </div>
                        <?php if ($shipment['estimated_delivery']): ?>
                        <div class="col-md-6 mb-3">
                            <strong>Estimated Delivery:</strong><br>
                            <?php echo date('F d, Y', strtotime($shipment['estimated_delivery'])); ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($shipment['pickup_date']): ?>
                        <div class="col-md-6 mb-3">
                            <strong>Pickup Date:</strong><br>
                            <?php echo date('F d, Y', strtotime($shipment['pickup_date'])); ?>
                            <?php if ($shipment['pickup_time_slot']): ?>
                                <br><small class="text-muted"><?php echo htmlspecialchars($shipment['pickup_time_slot']); ?></small>
                            <?php endif; ?>
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
            
            <!-- Shipping & Payment Details -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Shipping Details</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($shippingMethod): ?>
                                <p class="mb-2"><strong>Method:</strong> <?php echo htmlspecialchars($shippingMethod['name']); ?></p>
                            <?php endif; ?>
                            <p class="mb-2"><strong>Weight:</strong> <?php echo number_format($shipment['weight'], 2); ?> kg</p>
                            <?php if ($shipment['dimensions']): ?>
                                <p class="mb-2"><strong>Dimensions:</strong> <?php echo htmlspecialchars($shipment['dimensions']); ?></p>
                            <?php endif; ?>
                            <?php if ($shipment['estimated_delivery']): ?>
                                <p class="mb-0"><strong>Estimated Delivery:</strong> <?php echo date('F d, Y', strtotime($shipment['estimated_delivery'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-receipt me-2"></i>Payment Details</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Payment Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $shipment['payment_method'])); ?></p>
                            <p class="mb-2"><strong>Base Price:</strong> <?php echo formatCurrency($shipment['base_price']); ?></p>
                            <?php if ($shipment['weight_price'] > 0): ?>
                                <p class="mb-2"><strong>Weight Price:</strong> <?php echo formatCurrency($shipment['weight_price']); ?></p>
                            <?php endif; ?>
                            <?php if ($shipment['cod_amount'] > 0): ?>
                                <p class="mb-2"><strong>COD Amount:</strong> <?php echo formatCurrency($shipment['cod_amount']); ?></p>
                            <?php endif; ?>
                            <hr>
                            <p class="mb-0 h5"><strong>Total:</strong> <?php echo formatCurrency($shipment['total_price']); ?></p>
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
                                    <i class="fas fa-<?php echo $index === 0 ? 'check' : 'circle'; ?>"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-1"><?php echo ucfirst($track['status']); ?></h6>
                                <?php if ($track['notes']): ?>
                                    <p class="mb-1 text-muted"><?php echo htmlspecialchars($track['notes']); ?></p>
                                <?php endif; ?>
                                <small class="text-muted"><?php echo date('F d, Y h:i A', strtotime($track['created_at'])); ?></small>
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
            
            <!-- Next Steps -->
            <div class="alert alert-info border-0 mb-4">
                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>What Happens Next?</h6>
                <ol class="mb-0 ps-3">
                    <li>You will receive a confirmation email with your tracking details</li>
                    <li>Our team will review and process your shipment</li>
                    <li>We'll collect your package from the pickup address</li>
                    <li>You can track your shipment status in "My Shipments"</li>
                    <?php if ($shipment['estimated_delivery']): ?>
                    <li>Expected delivery: <?php echo date('F d, Y', strtotime($shipment['estimated_delivery'])); ?></li>
                    <?php endif; ?>
                </ol>
            </div>
            
            <!-- Action Buttons -->
            <div class="text-center">
                <a href="/modules/logistics/my-shipments/" class="btn btn-primary me-2">
                    <i class="fas fa-box me-2"></i>View My Shipments
                </a>
                <a href="/modules/logistics/booking/" class="btn btn-outline-secondary">
                    <i class="fas fa-plus me-2"></i>Book Another Shipment
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Booking Confirmation - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/user-layout.php';
?>

