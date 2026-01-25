<?php
/**
 * Admin Orders Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle status update
if (isset($_GET['update_status']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = intval($_GET['update_status']);
    $newStatus = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/ecommerce/orders.php', 'Invalid security token.', 'danger');
    }
    
    $validStatuses = ['pending', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/ecommerce/orders.php', 'Invalid status.', 'danger');
    }
    
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
        if (file_exists(__DIR__ . '/../../includes/notification-helper.php')) {
            require_once __DIR__ . '/../../includes/notification-helper.php';
            
            // Get order details
            $orderStmt = $conn->prepare("SELECT user_id, order_number FROM orders WHERE id = ?");
            $orderStmt->execute([$orderId]);
            $orderData = $orderStmt->fetch();
            
            if ($orderData) {
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
                $message = $statusMessage . '. Order #' . $orderData['order_number'];
                if (!empty($notes)) {
                    $message .= ' - ' . $notes;
                }
                
                NotificationHelper::createUserNotification(
                    $orderData['user_id'],
                    'order',
                    'Order Status Updated',
                    $message,
                    BASE_URL . '/user/orders/view.php?id=' . $orderId
                );
            }
        }
        
        redirect('/admin/ecommerce/orders.php', 'Order status updated successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update Order Status Error: " . $e->getMessage());
        redirect('/admin/ecommerce/orders.php', 'Failed to update order status.', 'danger');
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}

$userIdFilter = intval($_GET['user_id'] ?? 0);
if ($userIdFilter > 0) {
    $where[] = "o.user_id = ?";
    $params[] = $userIdFilter;
}

if ($search) {
    $where[] = "(o.order_number LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM orders o LEFT JOIN users u ON o.user_id = u.id $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalOrders = $countStmt->fetch()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Get orders
$sql = "SELECT o.*, u.email, u.phone, 
               (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        $whereClause
        ORDER BY o.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders - Admin - ' . APP_NAME;

// Use admin layout
ob_start();
?>

<div class="container-fluid">
    <h2 class="mb-4">Orders Management</h2>
    
    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Orders</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="packed" <?php echo $statusFilter === 'packed' ? 'selected' : ''; ?>>Packed</option>
                        <option value="shipped" <?php echo $statusFilter === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                        <option value="out_for_delivery" <?php echo $statusFilter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Search by order number, email, or phone" 
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($orders)): ?>
                <p class="text-muted text-center py-5">No orders found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($order['order_number']); ?></strong>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($order['phone'] ?? ''); ?></small>
                                </td>
                                <td><?php echo $order['item_count']; ?> item(s)</td>
                                <td><strong><?php echo formatCurrency($order['total']); ?></strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $order['status'] === 'pending' ? 'warning' : 
                                            ($order['status'] === 'delivered' ? 'success' : 
                                            ($order['status'] === 'cancelled' ? 'danger' : 'info')); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $order['payment_status'] === 'success' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>/admin/ecommerce/orders/view.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/ecommerce/orders/edit.php?id=<?php echo $order['id']; ?>" 
                                           class="btn btn-outline-success" title="Update Status">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Orders pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($search); ?>">Next</a>
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
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>







