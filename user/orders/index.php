<?php
/**
 * Order History
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

// Get filter
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["user_id = ?"];
$params = [$userId];

if ($statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) as total FROM orders WHERE $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalOrders = $countStmt->fetch()['total'];
$totalPages = ceil($totalOrders / $perPage);

// Get orders
$sql = "SELECT * FROM orders WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-orders.css'
];

ob_start();
?>




<?php if (empty($orders)): ?>
    <div class="card border-1 shadow-sm rounded-4 text-center py-5 bg-white">
        <div class="card-body py-5">
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center mb-1">
                    <i class="fas fa-shopping-bag fa-2x text-muted opacity-30"></i>
                </div>
            </div>
            <p class="fw-700 text-dark mb-1 small">No orders found</p>
            <p class="text-muted mb-3 mx-auto x-small" style="max-width: 320px;">
                You haven't placed any orders yet.
            </p>
            <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-primary btn--sm rounded-pill">
                Start Shopping
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="py-3 ps-4 text-secondary small fw-bold text-uppercase" style="letter-spacing: 1px;">Order Reference</th>
                        <th class="py-3 text-secondary small fw-bold text-uppercase" style="letter-spacing: 1px;">Date</th>
                        <th class="py-3 text-secondary small fw-bold text-uppercase" style="letter-spacing: 1px;">Items</th>
                        <th class="py-3 text-secondary small fw-bold text-uppercase" style="letter-spacing: 1px;">Total Price</th>
                        <th class="py-3 text-secondary small fw-bold text-uppercase" style="letter-spacing: 1px;">Status</th>
                        <th class="py-3 pe-4 text-end text-secondary small fw-bold text-uppercase" style="letter-spacing: 1px;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): 
                        $itemStmt = $conn->prepare("SELECT COUNT(*) FROM order_items WHERE order_id = ?");
                        $itemStmt->execute([$order['id']]);
                        $itemCount = $itemStmt->fetchColumn(); 
                        
                        $statusClass = 'bg-secondary bg-opacity-10 text-secondary';
                        if($order['status'] === 'delivered') $statusClass = 'bg-success bg-opacity-10 text-success';
                        elseif($order['status'] === 'cancelled') $statusClass = 'bg-danger bg-opacity-10 text-danger';
                        elseif($order['status'] === 'pending') $statusClass = 'bg-warning bg-opacity-10 text-warning';
                        elseif($order['status'] === 'shipped' || $order['status'] === 'processing') $statusClass = 'bg-info bg-opacity-10 text-info';
                    ?>
                    <tr style="cursor: pointer;" onclick="window.location.href='<?php echo BASE_URL; ?>/user/orders/view.php?id=<?php echo $order['id']; ?>'">
                        <td class="ps-4 py-3">
                            <span class="fw-bold text-dark">REQ-<?php echo htmlspecialchars($order['order_number']); ?></span>
                        </td>
                        <td class="py-3">
                            <span class="text-muted fw-medium small"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></span>
                        </td>
                        <td class="py-3">
                            <span class="fw-bold text-dark"><?php echo $itemCount; ?></span>
                        </td>
                        <td class="py-3">
                            <span class="fw-bold text-primary"><?php echo formatCurrency($order['total'] ?? 0); ?></span>
                        </td>
                        <td class="py-3">
                            <span class="badge rounded-pill <?php echo $statusClass; ?> px-3 py-2 text-uppercase x-small fw-bold">
                                <?php echo str_replace('_', ' ', $order['status']); ?>
                            </span>
                        </td>
                        <td class="pe-4 py-3 text-end">
                            <a href="<?php echo BASE_URL; ?>/user/orders/view.php?id=<?php echo $order['id']; ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3 fw-bold small">
                                View
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Premium Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center mt-5">
        <nav aria-label="Orders pagination">
            <ul class="pagination pagination-premium mb-0">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-4 me-2 x-small fw-800" href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $statusFilter; ?>">
                        PREV
                    </a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link rounded-circle mx-1 x-small fw-800" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link rounded-pill px-4 ms-2 x-small fw-800" href="?page=<?php echo min($totalPages, $page + 1); ?>&status=<?php echo $statusFilter; ?>">
                        NEXT
                    </a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
<?php endif; ?>



<?php
$content = ob_get_clean();
$pageTitle = 'Orders - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
