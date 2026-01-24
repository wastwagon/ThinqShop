<?php
/**
 * View Order Details
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$orderId = intval($_GET['id'] ?? 0);

if ($orderId <= 0) {
    redirect('/user/dashboard.php', 'Invalid order ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, a.*
    FROM orders o
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/user/dashboard.php', 'Order not found.', 'danger');
}

// Get order items with product info
$stmt = $conn->prepare("
    SELECT oi.*, p.slug as product_slug, p.id as product_id
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();

// Get order tracking
$stmt = $conn->prepare("SELECT * FROM order_tracking WHERE order_id = ? ORDER BY created_at ASC");
$stmt->execute([$orderId]);
$trackingHistory = $stmt->fetchAll();

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-order-view.css?v=' . time()
];

// Prepare content for layout
ob_start();
?>

<div class="mb-5">
    <a href="<?php echo BASE_URL; ?>/user/orders/" class="btn btn-light btn--sm rounded-pill mb-4">
        <i class="fas fa-arrow-left me-2"></i> Back to Orders
    </a>
    
    <div class="order-header-premium shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
            <div>
                <span class="text-muted x-small fw-700 text-uppercase letter-spacing-1 mb-1 d-block">Order Reference</span>
                <h3 class="fw-800 text-dark mb-1">REQ-<?php echo htmlspecialchars($order['order_number']); ?></h3>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted x-small fw-700 text-uppercase"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                    <span class="text-muted x-small fw-700 text-uppercase lh-1 mt-1">•</span>
                    <span class="text-muted x-small fw-700 text-uppercase"><?php echo count($orderItems); ?> Items</span>
                </div>
            </div>
            <div class="text-md-end">
                <?php 
                $sClass = 'bg-secondary-soft text-secondary';
                if($order['status'] === 'delivered') $sClass = 'bg-success-soft text-success';
                elseif($order['status'] === 'pending') $sClass = 'bg-warning-soft text-warning';
                elseif($order['status'] === 'processing' || $order['status'] === 'shipped') $sClass = 'bg-info-soft text-info';
                ?>
                <div class="status-indicator-premium <?php echo $sClass; ?> mb-2">
                    <?php echo str_replace('_', ' ', strtoupper($order['status'])); ?>
                </div>
                <div class="fw-800 text-primary h4 mb-0"><?php echo formatCurrency($order['total']); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Delivery Activity -->
        <div class="card-premium-view p-4 p-md-5 shadow-sm mb-4">
            <h6 class="fw-800 text-dark mb-5 text-uppercase letter-spacing-1 small"><i class="fas fa-route me-2 text-primary"></i>Tracking Updates</h6>
            
            <div class="timeline-premium ps-4">
                <?php if (empty($trackingHistory)): ?>
                    <div class="timeline-item-premium active">
                        <h6 class="fw-700 text-dark mb-1 small text-uppercase">Order Placed</h6>
                        <p class="text-muted x-small mb-0">Your order has been received and is being prepared.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($trackingHistory) as $index => $track): ?>
                        <div class="timeline-item-premium <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="d-flex justify-content-between mb-1">
                                <h6 class="fw-800 text-dark mb-0 small text-uppercase"><?php echo strtoupper(str_remove_snake($track['status'])); ?></h6>
                                <span class="text-muted x-small fw-800"><?php echo date('M d, H:i', strtotime($track['created_at'])); ?></span>
                            </div>
                            <p class="text-muted x-small fw-bold mb-0 text-uppercase"><?php echo htmlspecialchars($track['notes']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Manifest -->
        <div class="card-premium-view shadow-sm">
            <div class="p-4 border-bottom bg-white d-flex justify-content-between align-items-center">
                <h6 class="fw-700 text-dark mb-0 text-uppercase small">Package Contents</h6>
                <span class="badge bg-light text-dark rounded-pill px-3 py-1 fw-700 x-small">Secured</span>
            </div>
            <div class="p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr class="bg-light">
                                <th class="ps-4 border-0 py-3 x-small fw-700 text-muted">Item</th>
                                <th class="border-0 py-3 x-small fw-700 text-muted text-center">Qty</th>
                                <th class="pe-4 border-0 py-3 x-small fw-700 text-muted text-end">Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderItems as $item): ?>
                            <tr>
                                <td class="ps-4 py-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="product-thumb-premium">
                                            <i class="fas fa-box text-muted opacity-30"></i>
                                        </div>
                                        <div>
                                            <div class="fw-700 text-dark small mb-0"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                            <div class="text-muted x-small">#<?php echo str_pad($item['product_id'], 6, '0', STR_PAD_LEFT); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 text-center">
                                    <span class="badge bg-light text-dark rounded-pill px-3 py-1 fw-800">x<?php echo $item['quantity']; ?></span>
                                </td>
                                <td class="pe-4 py-4 text-end">
                                    <span class="fw-800 text-dark small"><?php echo formatCurrency($item['price']); ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Financial Summary -->
        <div class="card-premium-view p-4 shadow-sm mb-4">
            <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-1 small">Payment Breakdown</h6>
            <div class="summary-item-premium">
                <span class="text-muted x-small fw-700">SUBTOTAL</span>
                <span class="fw-700 text-dark small"><?php echo formatCurrency($order['subtotal']); ?></span>
            </div>
            <div class="summary-item-premium">
                <span class="text-muted x-small fw-700">SHIPPING FEE</span>
                <span class="fw-700 text-dark small"><?php echo formatCurrency($order['shipping_fee'] ?? 0); ?></span>
            </div>
            <?php if (($order['tax'] ?? 0) > 0): ?>
            <div class="summary-item-premium">
                <span class="text-muted x-small fw-700">TAX</span>
                <span class="fw-700 text-dark small"><?php echo formatCurrency($order['tax']); ?></span>
            </div>
            <?php endif; ?>
            <div class="summary-total-premium">
                <span class="fw-700 text-dark small">TOTAL</span>
                <span class="fw-800 text-primary h5 mb-0"><?php echo formatCurrency($order['total']); ?></span>
            </div>

            <div class="mt-5 p-3 rounded-4 bg-light border-0">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-white rounded-circle p-2 shadow-sm"><i class="fas fa-shield-alt text-primary"></i></div>
                    <div>
                        <div class="x-small fw-800 text-dark text-uppercase letter-spacing-1">via <?php echo strtoupper($order['payment_method']); ?></div>
                        <div class="badge bg-<?php echo $order['payment_status'] === 'success' ? 'success' : 'warning'; ?> text-white x-small rounded-pill px-2 fw-800">
                            <?php echo strtoupper($order['payment_status']); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-premium-view p-4 shadow-sm">
            <h6 class="fw-700 text-dark mb-4 text-uppercase small">Shipping Details</h6>
            <div class="d-flex align-items-start gap-3">
                <div class="bg-primary-soft text-primary rounded-circle p-2 flex-shrink-0"><i class="fas fa-map-marker-alt"></i></div>
                <div>
                    <div class="fw-700 text-dark small mb-1"><?php echo htmlspecialchars($order['full_name']); ?></div>
                    <div class="text-muted x-small lh-base">
                        <?php echo htmlspecialchars($order['street'] ?? $order['address_line1'] ?? ''); ?><br>
                        <?php echo htmlspecialchars($order['city']); ?>, <?php echo htmlspecialchars($order['region'] ?? $order['state'] ?? ''); ?><br>
                        <?php echo htmlspecialchars($order['country'] ?? 'Ghana'); ?>
                    </div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-top border-light">
                <div class="x-small fw-700 text-muted text-uppercase mb-1">Contact Phone</div>
                <div class="small fw-700 text-dark"><?php echo htmlspecialchars($order['phone'] ?? 'Unspecified'); ?></div>
            </div>
        </div>

        <!-- Action Center -->
        <div class="action-center-premium text-center shadow-sm">
            <h6 class="fw-700 text-primary mb-2 text-uppercase small">Need Help?</h6>
            <p class="x-small text-dark mb-4">Dedicated support for this order.</p>
            <a href="<?php echo BASE_URL; ?>/user/tickets/create.php?order=<?php echo $order['order_number']; ?>" class="btn btn-primary btn--sm rounded-pill w-100 py-2">
                Contact Support
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Order Details - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
