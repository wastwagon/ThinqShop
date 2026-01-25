<?php
/**
 * User Dashboard - Modern Premium Design
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get user data
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Get wallet balance
$walletBalance = getUserWalletBalance($userId);

// Get user profile
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Statistics
$stats = [];
// Total orders
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
$stmt->execute([$userId]);
$stats['total_orders'] = $stmt->fetch()['count'];

// Total spent
$stmt = $conn->prepare("
    SELECT COALESCE(SUM(total), 0) as total 
    FROM orders 
    WHERE user_id = ? AND status != 'cancelled'
");
$stmt->execute([$userId]);
$stats['total_spent'] = floatval($stmt->fetch()['total']);

// Active orders (pending/processing)
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')");
$stmt->execute([$userId]);
$stats['active_orders'] = $stats['active_orders'] ?? $stmt->fetch()['count'];

// Get recent orders
$stmt = $conn->prepare("
    SELECT o.*, 
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
    FROM orders o 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute([$userId]);
$recent_orders = $stmt->fetchAll();

// Prepare content for layout
ob_start();
?>

<!-- Page Title removed as it's already in the top header -->

<!-- Overview Section -->
<div class="row g-4 mb-5">
    <div class="col-md-3 col-sm-6">
        <div class="stats-card-premium shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="bg-transparent text-primary"><i class="fas fa-wallet"></i></div>
                <div class="text-success small fw-700 x-small">ACTIVE</div>
            </div>
            <span class="card-title-premium">Wallet Balance</span>
            <h3 class="stats-value-premium"><?php echo formatCurrency($walletBalance); ?></h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card-premium shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="bg-transparent text-success"><i class="fas fa-box-open"></i></div>
                <div class="badge bg-info-soft text-info rounded-pill x-small px-3 fw-700"><?php echo $stats['active_orders']; ?> ORDERS</div>
            </div>
            <span class="card-title-premium">Current Orders</span>
            <h3 class="stats-value-premium"><?php echo $stats['active_orders']; ?></h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card-premium shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="bg-transparent text-indigo"><i class="fas fa-credit-card"></i></div>
                <div class="text-muted small fw-700 x-small">TOTAL</div>
            </div>
            <span class="card-title-premium">Total Expenditure</span>
            <h3 class="stats-value-premium"><?php echo formatCurrency($stats['total_spent']); ?></h3>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="stats-card-premium shadow-sm">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="bg-transparent text-warning"><i class="fas fa-history"></i></div>
                <div class="text-muted small fw-700 x-small">HISTORY</div>
            </div>
            <span class="card-title-premium">Order History</span>
            <h3 class="stats-value-premium"><?php echo $stats['total_orders']; ?></h3>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-12">
        <!-- Recent Orders -->
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-700 text-dark"><i class="fas fa-clock me-2 text-primary"></i>Recent Orders</h6>
                <a href="<?php echo BASE_URL; ?>/user/orders/index.php" class="btn btn-outline-primary btn--sm rounded-pill">VIEW ALL</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-4">
                        <div class="d-inline-flex align-items-center justify-content-center mb-2">
                            <i class="fas fa-shopping-cart fa-2x text-muted opacity-50"></i>
                        </div>
                        <p class="text-muted mb-2 x-small">No recent orders found.</p>
                        <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-primary btn--sm rounded-pill">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 recent-table">
                            <thead>
                                <tr>
                                    <th>Order Reference</th>
                                    <th>Status</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr onclick="window.location.href='<?php echo BASE_URL; ?>/user/orders/view.php?id=<?php echo $order['id']; ?>'" style="cursor: pointer;">
                                        <td>
                                            <div class="fw-bold text-dark mb-1">REQ-<?php echo htmlspecialchars($order['order_number']); ?></div>
                                            <div class="x-small text-muted fw-bold"><?php echo date('M d, Y', strtotime($order['created_at'])); ?> • <?php echo $order['item_count']; ?> ITEMS</div>
                                        </td>
                                        <td>
                                            <?php 
                                            $sClass = 'bg-secondary-soft text-secondary';
                                            if($order['status'] === 'pending') $sClass = 'bg-warning-soft text-warning';
                                            elseif($order['status'] === 'delivered') $sClass = 'bg-success-soft text-success';
                                            elseif($order['status'] === 'processing' || $order['status'] === 'shipped') $sClass = 'bg-info-soft text-info';
                                            ?>
                                            <span class="status-badge-premium <?php echo $sClass; ?>">
                                                <?php echo strtoupper($order['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end fw-800 text-dark">
                                            <?php echo formatCurrency($order['total']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean();
$pageTitle = 'Dashboard - ' . APP_NAME;
include __DIR__ . '/../includes/layouts/user-layout.php';
?>