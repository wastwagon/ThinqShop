<?php
/**
 * User Shipments - Premium Design
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get shipments
$stmt = $conn->prepare("SELECT * FROM shipments WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$userId]);
$shipments = $stmt->fetchAll();

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-shipments.css'
];

ob_start();
?>



<?php if (empty($shipments)): ?>
    <div class="card border-1 shadow-sm rounded-4 text-center py-5 bg-white">
        <div class="card-body py-5">
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center mb-1">
                    <i class="fas fa-truck fa-2x text-muted opacity-30"></i>
                </div>
            </div>
            <p class="fw-700 text-dark mb-1 small">No shipments found</p>
            <p class="text-muted mb-3 mx-auto x-small" style="max-width: 320px;">
                You haven't booked any shipments yet.
            </p>
            <a href="<?php echo BASE_URL; ?>/modules/logistics/booking/" class="btn btn-primary btn--sm rounded-pill">
                Book Shipment
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($shipments as $shipment): 
            $statusClass = 'bg-secondary-soft text-secondary';
            if($shipment['status'] === 'delivered') $statusClass = 'bg-success-soft text-success';
            elseif($shipment['status'] === 'booked') $statusClass = 'bg-info-soft text-info';
            elseif($shipment['status'] === 'shipped') $statusClass = 'bg-primary-soft text-primary';
        ?>
        <div class="col-12">
            <div class="shipment-card-premium shadow-sm" onclick="window.location.href='<?php echo BASE_URL; ?>/public/track-parcel.php?tracking=<?php echo urlencode($shipment['tracking_number']); ?>'">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-primary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                <i class="fas fa-truck"></i>
                            </div>
                            <div>
                                <div class="tracking-pill-premium mb-0"><?php echo htmlspecialchars($shipment['tracking_number']); ?></div>
                                <span class="text-muted x-small fw-700 text-uppercase"><?php echo date('M d, Y', strtotime($shipment['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <span class="meta-label-premium">Service</span>
                        <div class="small fw-700 text-dark">
                            <?php echo ucfirst(str_replace('_', ' ', $shipment['service_type'])); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-4">
                            <div>
                                <span class="meta-label-premium">Weight</span>
                                <span class="weight-badge"><?php echo $shipment['weight']; ?> KG</span>
                            </div>
                            <div>
                                <span class="meta-label-premium">Price</span>
                                <span class="small fw-700 text-primary"><?php echo formatCurrency($shipment['total_price']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 text-end">
                        <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                            <span class="status-indicator-ship <?php echo $statusClass; ?>">
                                <?php echo str_replace('_', ' ', strtoupper($shipment['status'])); ?>
                            </span>
                            <a href="<?php echo BASE_URL; ?>/public/track-parcel.php?tracking=<?php echo urlencode($shipment['tracking_number']); ?>" class="btn btn-outline-primary btn--sm rounded-pill">
                                TRACK
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$pageTitle = 'Logistics - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
