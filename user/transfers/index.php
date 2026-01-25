<?php
/**
 * User Money Transfers - Premium Design
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
$typeFilter = $_GET['type'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Build query
$where = ["user_id = ?"];
$params = [$userId];

if ($typeFilter !== 'all') {
    $where[] = "transfer_type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter !== 'all') {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = implode(' AND ', $where);

// Get total count
$countSql = "SELECT COUNT(*) as total FROM money_transfers WHERE $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalTransfers = $countStmt->fetch()['total'];
$totalPages = ceil($totalTransfers / $perPage);

// Get transfers
$sql = "SELECT * FROM money_transfers WHERE $whereClause ORDER BY created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$transfers = $stmt->fetchAll();

// Auto-update transfers where all QR codes are completed
foreach ($transfers as $index => $transfer) {
    $recipientDetails = json_decode($transfer['recipient_details'], true) ?? [];
    if (isset($recipientDetails['qr_codes']) && is_array($recipientDetails['qr_codes']) && !empty($recipientDetails['qr_codes'])) {
        $allQRCodesCompleted = true;
        $hasQRCodes = false;
        foreach ($recipientDetails['qr_codes'] as $qr) {
            if (!empty($qr['qr_code'])) {
                $hasQRCodes = true;
                $qrStatus = $qr['status'] ?? 'pending';
                if ($qrStatus !== 'completed') {
                    $allQRCodesCompleted = false;
                    break;
                }
            }
        }
        if ($hasQRCodes && $allQRCodesCompleted && $transfer['status'] !== 'completed' && !in_array($transfer['status'], ['failed', 'cancelled'])) {
            try {
                $conn->beginTransaction();
                $stmt = $conn->prepare("UPDATE money_transfers SET status = 'completed', updated_at = NOW() WHERE id = ? AND user_id = ?");
                $stmt->execute([$transfer['id'], $userId]);
                $stmt = $conn->prepare("INSERT INTO transfer_tracking (transfer_id, status, notes, created_at) VALUES (?, 'completed', 'System: All transactions verified.', NOW())");
                $stmt->execute([$transfer['id']]);
                $conn->commit();
                $transfers[$index]['status'] = 'completed';
            } catch (Exception $e) { $conn->rollBack(); }
        }
    }
}

$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-transfers.css'
];

ob_start();
?>

<div class="page-title-section">
    <h1 class="page-title">Money Transfers</h1>
    <a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/" class="btn btn-primary btn-premium">
        <i class="fas fa-plus me-2"></i> New Transfer
    </a>
</div>




<?php if (empty($transfers)): ?>
    <div class="text-center py-5">
        <div class="mb-3">
            <div class="d-inline-flex align-items-center justify-content-center mb-1">
                <i class="fas fa-exchange-alt fa-2x text-muted opacity-30"></i>
            </div>
        </div>
        <p class="fw-700 text-dark mb-1 small">No transfers found</p>
        <p class="text-muted mb-3 mx-auto x-small" style="max-width: 320px;">
            No transfer records found in your account.
        </p>
        <a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/" class="btn btn-primary btn--sm rounded-pill">
            Initiate Transfer
        </a>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($transfers as $transfer): 
            $statusClass = 'bg-secondary-soft text-secondary';
            if($transfer['status'] === 'completed') $statusClass = 'bg-success-soft text-success';
            elseif($transfer['status'] === 'failed' || $transfer['status'] === 'cancelled') $statusClass = 'bg-danger-soft text-danger';
            elseif($transfer['status'] === 'processing' || $transfer['status'] === 'payment_received') $statusClass = 'bg-info-soft text-info';
        ?>
        <div class="col-12">
            <div class="transfer-card-premium shadow-sm" onclick="window.location.href='<?php echo BASE_URL; ?>/user/transfers/view.php?id=<?php echo $transfer['id']; ?>'">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="text-primary d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;">
                                <i class="fas <?php echo $transfer['transfer_type'] === 'send_to_china' ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i>
                            </div>
                            <div>
                                <div class="token-badge-premium mb-0"><?php echo htmlspecialchars($transfer['token']); ?></div>
                                <span class="text-muted x-small fw-800 text-uppercase"><?php echo date('M d, Y', strtotime($transfer['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="small fw-800 text-dark text-uppercase letter-spacing-1 mb-1">Direction</div>
                        <div class="x-small text-muted fw-800"><?php echo $transfer['transfer_type'] === 'send_to_china' ? 'GHS → CNY (CHINA)' : 'CNY → GHS (GHANA)'; ?></div>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="amount-display-premium">
                            <span class="amount-main-premium"><?php echo formatCurrency($transfer['amount_ghs']); ?></span>
                            <span class="amount-sub-premium"><?php echo number_format($transfer['amount_cny'], 2); ?> CNY</span>
                        </div>
                    </div>
                    
                    <div class="col-md-3 text-end">
                        <div class="d-flex flex-wrap justify-content-end align-items-center gap-3">
                            <span class="status-indicator-transfer <?php echo $statusClass; ?>">
                                <?php echo str_replace('_', ' ', strtoupper($transfer['status'])); ?>
                            </span>
                            <a href="<?php echo BASE_URL; ?>/user/transfers/view.php?id=<?php echo $transfer['id']; ?>" class="btn btn-outline-primary rounded-pill px-4 fw-800 x-small d-none d-md-inline-block">
                                ANALYSIS
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
                <a class="page-link rounded-pill px-4 me-2 x-small fw-800" href="?page=<?php echo max(1, $page - 1); ?>&type=<?php echo $typeFilter; ?>&status=<?php echo $statusFilter; ?>">PREV</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link rounded-circle mx-1 x-small fw-800" href="?page=<?php echo $i; ?>&type=<?php echo $typeFilter; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                <a class="page-link rounded-pill px-4 ms-2 x-small fw-800" href="?page=<?php echo min($totalPages, $page + 1); ?>&type=<?php echo $typeFilter; ?>&status=<?php echo $statusFilter; ?>">NEXT</a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
<?php endif; ?>



<?php
$content = ob_get_clean();
$pageTitle = 'Transfers - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
