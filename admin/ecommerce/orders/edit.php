<?php
/**
 * Edit Order Status - Admin
 * ThinQShopping Platform
 */

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
    SELECT o.*, u.email, u.phone
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    redirect('/admin/ecommerce/orders.php', 'Order not found.', 'danger');
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $newStatus = sanitize($_POST['status'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        
        $validStatuses = ['pending', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'];
        if (!in_array($newStatus, $validStatuses)) {
            $errors[] = 'Invalid status selected.';
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Update order status
                $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$newStatus, $orderId]);
                
                // Add tracking entry
                $stmt = $conn->prepare("
                    INSERT INTO order_tracking (order_id, status, notes, admin_id, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$orderId, $newStatus, $notes, $_SESSION['admin_id']]);
                
                $conn->commit();
                
                logAdminAction($_SESSION['admin_id'], 'update_order_status', 'orders', $orderId, ['status' => $newStatus]);
                
                // Send notification to user
                if (file_exists(__DIR__ . '/../../../includes/notification-helper.php')) {
                    require_once __DIR__ . '/../../../includes/notification-helper.php';
                    
                    $statusMessages = [
                        'pending' => 'Your order is pending',
                        'processing' => 'Your order is being processed',
                        'packed' => 'Your order has been packed',
                        'shipped' => 'Your order has been shipped',
                        'out_for_delivery' => 'Your order is out for delivery',
                        'delivered' => 'Your order has been delivered',
                        'cancelled' => 'Your order has been cancelled'
                    ];
                    
                    $statusMessage = $statusMessages[$newStatus] ?? 'Your order status has been updated';
                    $message = $statusMessage . '. Order #' . $order['order_number'];
                    if (!empty($notes)) {
                        $message .= ' - ' . $notes;
                    }
                    
                    NotificationHelper::createUserNotification(
                        $order['user_id'],
                        'order',
                        'Order Status Updated',
                        $message,
                        BASE_URL . '/user/orders/view.php?id=' . $orderId
                    );
                }
                
                redirect('/admin/ecommerce/orders/view.php?id=' . $orderId, 'Order status updated successfully.', 'success');
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Update Order Status Error: " . $e->getMessage());
                $errors[] = 'Failed to update order status. Please try again.';
            }
        }
    }
}

$pageTitle = 'Edit Order Status - Admin - ' . APP_NAME;

// Use admin layout
ob_start();
?>

<div class="container-fluid">
    <div class="page-title-section mb-4">
        <h1 class="page-title">Update Order Status</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/ecommerce/orders.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/ecommerce/orders/view.php?id=<?php echo $orderId; ?>" class="btn btn-outline-primary">
                <i class="fas fa-eye"></i> View Order
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Order Number:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($order['order_number']); ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Customer:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></span><br>
                            <small class="text-muted"><?php echo htmlspecialchars($order['phone'] ?? ''); ?></small>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Current Status:</strong><br>
                            <span class="badge bg-<?php 
                                echo $order['status'] === 'pending' ? 'warning' : 
                                    ($order['status'] === 'delivered' ? 'success' : 
                                    ($order['status'] === 'cancelled' ? 'danger' : 'info')); 
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Total Amount:</strong><br>
                            <span class="text-muted"><?php echo formatCurrency($order['total']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Update Status</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label">New Status <span class="text-danger">*</span></label>
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
                            <label class="form-label">Notes (Optional)</label>
                            <textarea name="notes" class="form-control" rows="4" 
                                      placeholder="Add tracking number, location, or any notes..."><?php echo htmlspecialchars($_POST['notes'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">This note will be added to the order tracking history.</small>
                        </div>
                        
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                            <a href="<?php echo BASE_URL; ?>/admin/ecommerce/orders/view.php?id=<?php echo $orderId; ?>" class="btn btn-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Subtotal:</strong>
                        <span class="float-end"><?php echo formatCurrency($order['subtotal']); ?></span>
                    </div>
                    <?php if ($order['tax'] > 0): ?>
                    <div class="mb-3">
                        <strong>Tax:</strong>
                        <span class="float-end"><?php echo formatCurrency($order['tax']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['shipping_fee'] > 0): ?>
                    <div class="mb-3">
                        <strong>Shipping:</strong>
                        <span class="float-end"><?php echo formatCurrency($order['shipping_fee']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($order['discount'] > 0): ?>
                    <div class="mb-3">
                        <strong>Discount:</strong>
                        <span class="float-end text-success">-<?php echo formatCurrency($order['discount']); ?></span>
                    </div>
                    <?php endif; ?>
                    <hr>
                    <div class="mb-0">
                        <strong>Total:</strong>
                        <span class="float-end"><strong><?php echo formatCurrency($order['total']); ?></strong></span>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Payment Method:</strong><br>
                        <span class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                    </div>
                    <div class="mb-2">
                        <strong>Payment Status:</strong><br>
                        <span class="badge bg-<?php echo $order['payment_status'] === 'success' ? 'success' : 'warning'; ?>">
                            <?php echo ucfirst($order['payment_status']); ?>
                        </span>
                    </div>
                    <?php if ($order['paystack_reference']): ?>
                    <div class="mb-0">
                        <strong>Reference:</strong><br>
                        <small class="text-muted font-monospace"><?php echo htmlspecialchars($order['paystack_reference']); ?></small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../../includes/layouts/admin-layout.php';
?>

