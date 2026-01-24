<?php
/**
 * Order Confirmation Page
 * ThinQShopping Platform
 */

require_once __DIR__ . '/includes/auth-check.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$orderNumber = $_GET['order'] ?? '';

if (empty($orderNumber)) {
    redirect('/user/dashboard.php', 'Invalid order reference.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, a.*
    FROM orders o
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.order_number = ? AND o.user_id = ?
");
$stmt->execute([$orderNumber, $userId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/user/dashboard.php', 'Order not found.', 'danger');
}

// Get order items
$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$order['id']]);
$orderItems = $stmt->fetchAll();

// Get order tracking
$stmt = $conn->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at DESC");
$stmt->execute([$order['id']]);
$trackingHistory = $stmt->fetchAll();

$pageTitle = 'Order Confirmation - ' . APP_NAME;
include __DIR__ . '/includes/header.php';
?>

<div class="user-main-content py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Premium Success Celebration -->
                <div class="text-center mb-5">
                    <div class="success-animation mb-4">
                        <div class="success-icon-wrapper">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    <h1 class="display-6 fw-800 text-dark mb-2">Order Confirmed</h1>
                    <p class="text-muted fw-bold">YOUR TRANSACTION IS SECURED AND PROCESSING. REFERENCE: <span class="text-primary"><?php echo htmlspecialchars($orderNumber); ?></span></p>
                </div>
                
                <div class="row g-4 mb-5">
                    <!-- Order Summary Glass Card -->
                    <div class="col-md-7">
                        <div class="confirmation-card p-4">
                            <h5 class="fw-800 text-uppercase small letter-spacing-1 mb-4 border-bottom pb-3">Order Details</h5>
                            <div class="row g-3">
                                <div class="col-6">
                                    <span class="meta-label-premium">Order Reference</span>
                                    <span class="fw-800 text-dark">REQ-<?php echo substr($orderNumber, -8); ?></span>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="meta-label-premium">Date</span>
                                    <span class="fw-bold text-dark small"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                                </div>
                                <div class="col-6">
                                    <span class="meta-label-premium">Payment</span>
                                    <span class="status-indicator-premium bg-primary-soft text-primary"><?php echo strtoupper($order['payment_method']); ?></span>
                                </div>
                                <div class="col-6 text-end">
                                    <span class="meta-label-premium">Status</span>
                                    <span class="status-indicator-premium bg-warning-soft text-warning">PENDING</span>
                                </div>
                            </div>
                            
                            <hr class="my-4 opacity-5">
                            
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-bold text-muted">Total Amount</span>
                                <span class="display-6 fw-800 text-primary" style="font-size: 1.5rem;"><?php echo formatCurrency($order['total']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Logistic / Destination -->
                    <div class="col-md-5">
                        <div class="confirmation-card p-4 h-100">
                            <h5 class="fw-800 text-uppercase small letter-spacing-1 mb-4 border-bottom pb-3 text-primary">Destination</h5>
                            <div class="d-flex gap-3">
                                <div class="icon-square-premium bg-primary-soft text-primary"><i class="fas fa-map-marker-alt"></i></div>
                                <div>
                                    <div class="fw-800 text-dark mb-1"><?php echo htmlspecialchars($order['full_name']); ?></div>
                                    <div class="small text-muted fw-bold mb-0"><?php echo htmlspecialchars($order['street']); ?></div>
                                    <div class="small text-muted fw-bold"><?php echo htmlspecialchars($order['city']); ?>, GH</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Items Registry -->
                <div class="confirmation-card overflow-hidden mb-5">
                    <div class="p-4 bg-light border-bottom">
                        <h6 class="mb-0 fw-800 text-dark text-uppercase small letter-spacing-1">Itemized Registry</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead class="bg-light-soft">
                                <tr>
                                    <th class="ps-4">Item Details</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end pe-4">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr class="border-bottom border-light">
                                    <td class="ps-4 py-3">
                                        <div class="fw-800 text-dark"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                        <div class="x-small text-muted fw-bold"><?php echo htmlspecialchars($item['variant_details'] ?? 'DEFAULT VARIANT'); ?></div>
                                    </td>
                                    <td class="text-center fw-bold text-muted"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end pe-4 fw-800 text-dark"><?php echo formatCurrency($item['total']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Action Grid -->
                <div class="row g-3">
                    <div class="col-6">
                        <a href="<?php echo BASE_URL; ?>/user/orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-primary w-100 py-3 rounded-pill fw-800 shadow-sm">
                            <i class="fas fa-search me-2"></i> ANALYZE ORDER
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-outline-primary w-100 py-3 rounded-pill fw-800">
                            MARKETPLACE
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.confirmation-card {
    background: #ffffff;
    border-radius: 24px;
    border: 1px solid rgba(14, 41, 69, 0.08);
    box-shadow: 0 10px 30px rgba(14, 41, 69, 0.04);
}

.success-icon-wrapper {
    width: 80px;
    height: 80px;
    background: #0e2945;
    color: white;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    position: relative;
    z-index: 1;
}

.success-icon-wrapper::after {
    content: '';
    position: absolute;
    top: -10px;
    left: -10px;
    right: -10px;
    bottom: -10px;
    border: 2px solid #0e2945;
    border-radius: 50%;
    opacity: 0.2;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); opacity: 0.2; }
    50% { transform: scale(1.2); opacity: 0; }
    100% { transform: scale(1); opacity: 0.2; }
}

.bg-light-soft { background-color: #f8fafc; }
</style>

<?php include __DIR__ . '/includes/footer.php'; ?>

