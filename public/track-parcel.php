<?php
/**
 * Public Parcel Tracking
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$trackingNumber = $_GET['tracking'] ?? '';

if (empty($trackingNumber)) {
    $pageTitle = 'Track Parcel - ' . APP_NAME;
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <h3 class="mb-4">Track Your Parcel</h3>
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label class="form-label">Enter Tracking Number</label>
                                <input type="text" name="tracking" class="form-control form-control-lg" 
                                       placeholder="SHIP-YYYYMMDD-XXXXXX" required autofocus>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg w-100">Track Parcel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

$db = new Database();
$conn = $db->getConnection();

// Get shipment details
$stmt = $conn->prepare("
    SELECT s.*, u.email
    FROM shipments s
    LEFT JOIN users u ON s.user_id = u.id
    WHERE s.tracking_number = ?
");
$stmt->execute([$trackingNumber]);
$shipment = $stmt->fetch();

if (!$shipment) {
    $pageTitle = 'Track Parcel - ' . APP_NAME;
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container my-5">
        <div class="alert alert-danger">
            <h5>Shipment Not Found</h5>
            <p>The tracking number you entered is invalid. Please check and try again.</p>
            <a href="<?php echo BASE_URL; ?>/public/track-parcel.php" class="btn btn-primary">Try Again</a>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
    exit;
}

// Get tracking history
$stmt = $conn->prepare("
    SELECT * FROM shipment_tracking 
    WHERE shipment_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$shipment['id']]);
$trackingHistory = $stmt->fetchAll();

// Get addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE id IN (?, ?)");
$stmt->execute([$shipment['pickup_address_id'], $shipment['delivery_address_id']]);
$addresses = $stmt->fetchAll();
$pickupAddress = null;
$deliveryAddress = null;
foreach ($addresses as $addr) {
    if ($addr['id'] == $shipment['pickup_address_id']) $pickupAddress = $addr;
    if ($addr['id'] == $shipment['delivery_address_id']) $deliveryAddress = $addr;
}

$statusInfo = [
    'booked' => ['label' => 'Booked', 'icon' => 'calendar', 'color' => 'info'],
    'pickup_scheduled' => ['label' => 'Pickup Scheduled', 'icon' => 'clock', 'color' => 'warning'],
    'picked_up' => ['label' => 'Picked Up', 'icon' => 'check', 'color' => 'primary'],
    'in_transit' => ['label' => 'In Transit', 'icon' => 'truck', 'color' => 'info'],
    'out_for_delivery' => ['label' => 'Out for Delivery', 'icon' => 'truck-loading', 'color' => 'warning'],
    'delivered' => ['label' => 'Delivered', 'icon' => 'check-double', 'color' => 'success'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => 'times', 'color' => 'danger']
];

$currentStatus = $statusInfo[$shipment['status']] ?? ['label' => ucfirst($shipment['status']), 'icon' => 'info', 'color' => 'secondary'];

$pageTitle = 'Track Parcel #' . htmlspecialchars($trackingNumber) . ' - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Parcel Tracking</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h5 class="mb-2">Tracking Number</h5>
                        <code class="fs-4"><?php echo htmlspecialchars($trackingNumber); ?></code>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Shipment Details</h6>
                            <table class="table table-sm">
                                <tr>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-<?php echo $currentStatus['color']; ?>">
                                            <i class="fas fa-<?php echo $currentStatus['icon']; ?>"></i>
                                            <?php echo $currentStatus['label']; ?>
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Service:</th>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $shipment['service_type'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Weight:</th>
                                    <td><?php echo $shipment['weight']; ?> kg</td>
                                </tr>
                                <tr>
                                    <th>Total Price:</th>
                                    <td><?php echo formatCurrency($shipment['total_price']); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Addresses</h6>
                            <?php if ($pickupAddress): ?>
                            <p class="mb-2">
                                <strong>From:</strong><br>
                                <?php echo htmlspecialchars($pickupAddress['full_name']); ?><br>
                                <?php echo htmlspecialchars($pickupAddress['street']); ?><br>
                                <?php echo htmlspecialchars($pickupAddress['city'] . ', ' . $pickupAddress['region']); ?>
                            </p>
                            <?php endif; ?>
                            
                            <?php if ($deliveryAddress): ?>
                            <p class="mb-0">
                                <strong>To:</strong><br>
                                <?php echo htmlspecialchars($deliveryAddress['full_name']); ?><br>
                                <?php echo htmlspecialchars($deliveryAddress['street']); ?><br>
                                <?php echo htmlspecialchars($deliveryAddress['city'] . ', ' . $deliveryAddress['region']); ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Tracking Timeline -->
                    <h6 class="mb-3">Tracking History</h6>
                    <div class="timeline">
                        <?php foreach ($trackingHistory as $index => $track): 
                            $trackStatus = $statusInfo[$track['status']] ?? ['label' => ucfirst($track['status']), 'icon' => 'info', 'color' => 'secondary'];
                        ?>
                        <div class="mb-4 position-relative <?php echo $index < count($trackingHistory) - 1 ? 'pb-4 border-start border-primary' : ''; ?>" 
                             style="padding-left: 2rem;">
                            <div class="position-absolute start-0 top-0">
                                <i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i>
                            </div>
                            <div>
                                <strong>
                                    <i class="fas fa-<?php echo $trackStatus['icon']; ?> text-<?php echo $trackStatus['color']; ?>"></i>
                                    <?php echo $trackStatus['label']; ?>
                                </strong>
                                <small class="text-muted ms-2"><?php echo date('M d, Y h:i A', strtotime($track['created_at'])); ?></small>
                                <?php if ($track['location']): ?>
                                    <p class="mb-0 mt-1"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($track['location']); ?></p>
                                <?php endif; ?>
                                <?php if ($track['notes']): ?>
                                    <p class="mb-0 mt-1 text-muted"><?php echo htmlspecialchars($track['notes']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="text-center mt-4">
                        <a href="<?php echo BASE_URL; ?>/public/track-parcel.php" class="btn btn-outline-primary">Track Another Parcel</a>
                        <?php if (isLoggedIn()): ?>
                            <a href="<?php echo BASE_URL; ?>/user/shipments/" class="btn btn-primary ms-2">View My Shipments</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

