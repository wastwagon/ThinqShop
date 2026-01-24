<?php
/**
 * Admin Money Transfer Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle status update
if (isset($_GET['update_status']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $transferId = intval($_GET['update_status']);
    $newStatus = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/money-transfer/transfers.php', 'Invalid security token.', 'danger');
    }
    
    $validStatuses = ['payment_received', 'processing', 'sent_to_partner', 'completed', 'failed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/money-transfer/transfers.php', 'Invalid status.', 'danger');
    }
    
    try {
        $conn->beginTransaction();
        
        // Update transfer status
        $stmt = $conn->prepare("UPDATE money_transfers SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $transferId]);
        
        // Add tracking entry
        $stmt = $conn->prepare("
            INSERT INTO transfer_tracking (transfer_id, status, notes, admin_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$transferId, $newStatus, $notes, $_SESSION['admin_id']]);
        
        $conn->commit();
        
        logAdminAction($_SESSION['admin_id'], 'update_transfer_status', 'money_transfers', $transferId, ['status' => $newStatus]);
        redirect('/admin/money-transfer/transfers.php', 'Transfer status updated successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update Transfer Status Error: " . $e->getMessage());
        redirect('/admin/money-transfer/transfers.php', 'Failed to update transfer status.', 'danger');
    }
}

// Get filter
$typeFilter = $_GET['type'] ?? 'all';
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($typeFilter !== 'all') {
    $where[] = "transfer_type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter !== 'all') {
    $where[] = "mt.status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM money_transfers mt $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalTransfers = $countStmt->fetch()['total'];
$totalPages = ceil($totalTransfers / $perPage);

// Get transfers
$sql = "SELECT mt.*, u.email, u.phone 
        FROM money_transfers mt
        LEFT JOIN users u ON mt.user_id = u.id
        $whereClause
        ORDER BY mt.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$transfers = $stmt->fetchAll();

$pageTitle = 'Money Transfers - Admin - ' . APP_NAME;

// Use admin layout
ob_start();
?>

<div class="container-fluid">
    <h2 class="mb-4">Money Transfer Management</h2>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Transfer Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $typeFilter === 'all' ? 'selected' : ''; ?>>All Types</option>
                        <option value="send_to_china" <?php echo $typeFilter === 'send_to_china' ? 'selected' : ''; ?>>Send to China</option>
                        <option value="receive_from_china" <?php echo $typeFilter === 'receive_from_china' ? 'selected' : ''; ?>>Receive from China</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="payment_received" <?php echo $statusFilter === 'payment_received' ? 'selected' : ''; ?>>Payment Received</option>
                        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="sent_to_partner" <?php echo $statusFilter === 'sent_to_partner' ? 'selected' : ''; ?>>Sent to Partner</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transfers Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($transfers)): ?>
                <p class="text-muted text-center py-5">No transfers found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Token</th>
                                <th>Type</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transfers as $transfer): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($transfer['token']); ?></code></td>
                                <td>
                                    <?php echo $transfer['transfer_type'] === 'send_to_china' ? 'Send to China' : 'Receive from China'; ?>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($transfer['email'] ?? 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($transfer['phone'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php echo formatCurrency($transfer['amount_ghs']); ?> GHS<br>
                                    <small class="text-muted"><?php echo number_format($transfer['amount_cny'], 2); ?> CNY</small>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $transfer['status'] === 'completed' ? 'success' : 
                                            ($transfer['status'] === 'failed' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $transfer['payment_status'] === 'success' ? 'success' : 'warning'; ?>">
                                        <?php echo ucfirst($transfer['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($transfer['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers/view.php?id=<?php echo $transfer['id']; ?>" 
                                           class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers/update-status.php?id=<?php echo $transfer['id']; ?>" 
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
                <nav aria-label="Transfers pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&type=<?php echo $typeFilter; ?>&status=<?php echo $statusFilter; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&type=<?php echo $typeFilter; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&type=<?php echo $typeFilter; ?>&status=<?php echo $statusFilter; ?>">Next</a>
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
include __DIR__ . '/../../layouts/admin-layout.php';
?>







