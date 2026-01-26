<?php
/**
 * Procurement Requests - Premium Design
 * ThinQShopping Platform
 */

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
$countSql = "SELECT COUNT(*) as total FROM procurement_requests WHERE $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalRequests = $countStmt->fetch()['total'];
$totalPages = ceil($totalRequests / $perPage);

// Get requests
$sql = "SELECT * FROM procurement_requests WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-procurement.css'
];

ob_start();
?>

<div class="page-title-section">
    <h1 class="page-title">Procurement Requests</h1>
    <a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="btn btn-primary btn-premium">
        <i class="fas fa-plus me-2"></i> New Request
    </a>
</div>


<?php if (empty($requests)): ?>
    <div class="card border-1 shadow-sm rounded-4 text-center py-5 bg-white">
        <div class="card-body py-5">
            <div class="mb-4">
                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; border: 1px dashed #cbd5e1;">
                    <i class="fas fa-box-open fa-2x text-muted opacity-30"></i>
                </div>
            </div>
            <h6 class="fw-bold text-dark mb-1">No requests found</h6>
            <p class="text-muted mb-4 mx-auto small" style="max-width: 320px;">
                You haven't made any procurement requests yet.
            </p>
            <a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="btn btn-primary btn-premium px-5 py-3">
                Initiate First Request
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($requests as $request): 
            $statusClass = 'bg-secondary-soft text-secondary';
            if($request['status'] === 'delivered') $statusClass = 'bg-success-soft text-success';
            elseif($request['status'] === 'cancelled') $statusClass = 'bg-danger-soft text-danger';
            elseif($request['status'] === 'submitted') $statusClass = 'bg-warning-soft text-warning';
            elseif($request['status'] === 'quote_provided' || $request['status'] === 'processing') $statusClass = 'bg-info-soft text-info';
        ?>
        <div class="col-12">
            <div class="procurement-card-premium shadow-sm" onclick="window.location.href='<?php echo BASE_URL; ?>/user/procurement/view.php?id=<?php echo $request['id']; ?>'">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 40px; height: 40px;">
                                <i class="fas fa-file-contract x-small"></i>
                            </div>
                            <div>
                                <div class="request-id-badge mb-0"><?php echo htmlspecialchars($request['request_number']); ?></div>
                                <span class="text-muted x-small fw-800 text-uppercase"><?php echo date('M d, Y', strtotime($request['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <span class="meta-label-premium">Asset Description</span>
                        <div class="small fw-800 text-dark text-truncate text-uppercase" style="max-width: 300px;">
                            <?php echo htmlspecialchars($request['description']); ?>
                        </div>
                        <div class="x-small text-muted fw-800 text-uppercase"><?php echo str_replace('_', ' ', $request['category'] ?? 'General'); ?></div>
                    </div>
                    
                    <div class="col-md-2">
                        <span class="meta-label-premium">Units</span>
                        <div class="small fw-800 text-dark"><?php echo $request['quantity']; ?> UNITS</div>
                    </div>
                    
                    <div class="col-md-3 text-end">
                        <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                            <span class="status-indicator-proc <?php echo $statusClass; ?>">
                                <?php echo str_replace('_', ' ', strtoupper($request['status'])); ?>
                            </span>
                            <a href="<?php echo BASE_URL; ?>/user/procurement/view.php?id=<?php echo $request['id']; ?>" class="audit-btn-mobile">
                                AUDIT
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center mt-5">
        <ul class="pagination pagination-premium mb-0">
            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link rounded-pill px-4 me-2 x-small fw-800" href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $statusFilter; ?>">PREV</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link rounded-circle mx-1 x-small fw-800" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link rounded-pill px-4 ms-2 x-small fw-800" href="?page=<?php echo min($totalPages, $page + 1); ?>&status=<?php echo $statusFilter; ?>">NEXT</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
<?php endif; ?>



<?php
$content = ob_get_clean();
$pageTitle = 'Procurement - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
