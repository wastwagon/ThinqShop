<?php
/**
 * View Order Details - Admin
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$orderId = intval($_GET['id'] ?? 0);

if ($orderId <= 0) {
    redirect('/admin/ecommerce/orders.php', 'Invalid order ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();

// Get order details
$stmt = $conn->prepare("
    SELECT o.*, u.email, u.phone, a.*
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN addresses a ON o.shipping_address_id = a.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/admin/ecommerce/orders.php', 'Order not found.', 'danger');
}

// Get order items with product slugs
$stmt = $conn->prepare("
    SELECT oi.*, p.slug as product_slug 
    FROM order_items oi
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->execute([$orderId]);
$orderItems = $stmt->fetchAll();

// Get order tracking
$stmt = $conn->prepare("
    SELECT ot.*, au.username as admin_username
    FROM order_tracking ot
    LEFT JOIN admin_users au ON ot.admin_id = au.id
    WHERE ot.order_id = ?
    ORDER BY ot.created_at ASC
");
$stmt->execute([$orderId]);
$trackingHistory = $stmt->fetchAll();

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Order #<?php echo htmlspecialchars($order['order_number']); ?></h1>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/ecommerce/orders.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
            <i class="fas fa-edit"></i> Update Status
        </button>
    </div>
</div>

<div class="row">
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Variant</th>
                                    <th class="text-center">Quantity</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderItems as $item): ?>
                                <tr>
                                    <td>
                                        <?php if (!empty($item['product_slug'])): ?>
                                            <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo htmlspecialchars($item['product_slug']); ?>" target="_blank" class="text-decoration-none">
                                                <?php echo htmlspecialchars($item['product_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($item['product_name']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($item['variant_details'] ?? '-'); ?></td>
                                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                                    <td class="text-end"><?php echo formatCurrency($item['price']); ?></td>
                                    <td class="text-end"><?php echo formatCurrency($item['total']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end"><?php echo formatCurrency($order['subtotal']); ?></td>
                                </tr>
                                <?php if ($order['tax'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>VAT:</strong></td>
                                    <td class="text-end"><?php echo formatCurrency($order['tax']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($order['shipping_fee'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Shipping:</strong></td>
                                    <td class="text-end"><?php echo formatCurrency($order['shipping_fee']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($order['discount'] > 0): ?>
                                <tr>
                                    <td colspan="4" class="text-end text-success"><strong>Discount:</strong></td>
                                    <td class="text-end text-success">-<?php echo formatCurrency($order['discount']); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td class="text-end"><strong><?php echo formatCurrency($order['total']); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Order Tracking -->
            <?php if (!empty($trackingHistory)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Tracking History</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <?php foreach ($trackingHistory as $index => $track): ?>
                        <div class="mb-4 position-relative <?php echo $index < count($trackingHistory) - 1 ? 'pb-4 border-start border-primary' : ''; ?>" 
                             style="padding-left: 2rem;">
                            <div class="position-absolute start-0 top-0">
                                <i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i>
                            </div>
                            <div>
                                <strong><?php echo ucfirst(str_replace('_', ' ', $track['status'])); ?></strong>
                                <small class="text-muted ms-2"><?php echo date('M d, Y h:i A', strtotime($track['created_at'])); ?></small>
                                <?php if ($track['admin_username']): ?>
                                    <small class="text-muted">by <?php echo htmlspecialchars($track['admin_username']); ?></small>
                                <?php endif; ?>
                                <?php if ($track['notes']): ?>
                                    <p class="mb-0 mt-1 text-muted"><?php echo htmlspecialchars($track['notes']); ?></p>
                                <?php endif; ?>
                                <?php if ($track['location']): ?>
                                    <p class="mb-0 mt-1"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($track['location']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Order Status -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Order Status</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Current Status:</strong><br>
                        <span class="badge bg-<?php 
                            echo $order['status'] === 'pending' ? 'warning' : 
                                ($order['status'] === 'delivered' ? 'success' : 
                                ($order['status'] === 'cancelled' ? 'danger' : 'info')); 
                        ?> fs-6 mt-2">
                            <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                        </span>
                    </div>
                    <div class="mb-3">
                        <strong>Payment Status:</strong><br>
                        <span class="badge bg-<?php echo $order['payment_status'] === 'success' ? 'success' : 'warning'; ?> mt-2">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </div>
                    <div>
                        <strong>Order Date:</strong><br>
                        <span class="text-muted"><?php echo date('F d, Y h:i A', strtotime($order['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Customer Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Customer Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <strong>Email:</strong><br>
                        <a href="mailto:<?php echo htmlspecialchars($order['email']); ?>">
                            <?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?>
                        </a>
                    </p>
                    <p class="mb-0">
                        <strong>Phone:</strong><br>
                        <a href="tel:<?php echo htmlspecialchars($order['phone']); ?>">
                            <?php echo htmlspecialchars($order['phone'] ?? 'N/A'); ?>
                        </a>
                    </p>
                </div>
            </div>
            
            <!-- Shipping Address -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Shipping Address</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <strong><?php echo htmlspecialchars($order['full_name']); ?></strong><br>
                        <?php echo htmlspecialchars($order['street']); ?><br>
                        <?php echo htmlspecialchars($order['city'] . ', ' . $order['region']); ?><br>
                        Phone: <?php echo htmlspecialchars($order['phone']); ?>
                    </p>
                </div>
            </div>
            
            <!-- Payment Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <p class="mb-1">
                        <strong>Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?><br>
                        <strong>Status:</strong> 
                        <span class="badge bg-<?php echo $order['payment_status'] === 'success' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </p>
                    <?php if ($order['paystack_reference']): ?>
                        <p class="mb-0 small text-muted">
                            Reference: <?php echo htmlspecialchars($order['paystack_reference']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo BASE_URL; ?>/admin/ecommerce/orders.php?update_status=<?php echo $orderId; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Order #<?php echo htmlspecialchars($order['order_number']); ?></label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="packed" <?php echo $order['status'] === 'packed' ? 'selected' : ''; ?>>Packed</option>
                            <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="out_for_delivery" <?php echo $order['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                            <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Location (Optional)</label>
                        <input type="text" name="location" class="form-control" 
                               placeholder="e.g., Accra Warehouse, In Transit to Kumasi...">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Add tracking number, additional notes..."></textarea>
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
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Order #' . htmlspecialchars($order['order_number']) . ' - Admin - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/admin-layout.php';







