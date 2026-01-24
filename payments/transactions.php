<?php
/**
 * Admin Payment Transactions Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get filters
$statusFilter = $_GET['status'] ?? 'all';
$serviceFilter = $_GET['service'] ?? 'all';
$paymentMethodFilter = $_GET['method'] ?? 'all';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "p.status = ?";
    $params[] = $statusFilter;
}

if ($serviceFilter !== 'all') {
    $where[] = "p.service_type = ?";
    $params[] = $serviceFilter;
}

if ($paymentMethodFilter !== 'all') {
    $where[] = "p.payment_method = ?";
    $params[] = $paymentMethodFilter;
}

if (!empty($dateFrom)) {
    $where[] = "DATE(p.created_at) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $where[] = "DATE(p.created_at) <= ?";
    $params[] = $dateTo;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM payments p $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalTransactions = $countStmt->fetch()['total'];
$totalPages = ceil($totalTransactions / $perPage);

// Get transactions
$sql = "SELECT p.*, u.email, u.phone 
        FROM payments p
        LEFT JOIN users u ON p.user_id = u.id
        $whereClause
        ORDER BY p.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Get summary statistics
$stmt = $conn->query("
    SELECT 
        COUNT(*) as total_count,
        COALESCE(SUM(CASE WHEN status = 'success' THEN amount ELSE 0 END), 0) as total_success,
        COALESCE(SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END), 0) as success_count,
        COALESCE(SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END), 0) as failed_count
    FROM payments
    WHERE DATE(created_at) = CURDATE()
");
$todayStats = $stmt->fetch();

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Payment Transactions</h1>
</div>

<!-- Today's Summary -->
<div class="metrics-grid mb-4">
    <div class="metric-card">
        <div class="metric-icon revenue" style="background: rgba(13, 110, 253, 0.1); color: var(--primary-color);">
            <i class="fas fa-list"></i>
        </div>
        <div class="metric-title">Today's Transactions</div>
        <div class="metric-value"><?php echo number_format($todayStats['total_count']); ?></div>
    </div>
    
    <div class="metric-card">
        <div class="metric-icon revenue" style="background: rgba(25, 135, 84, 0.1); color: var(--success-color);">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="metric-title">Today's Revenue</div>
        <div class="metric-value"><?php echo formatCurrency($todayStats['total_success']); ?></div>
    </div>
    
    <div class="metric-card">
        <div class="metric-icon revenue" style="background: rgba(13, 202, 240, 0.1); color: var(--info-color);">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="metric-title">Successful</div>
        <div class="metric-value"><?php echo number_format($todayStats['success_count']); ?></div>
    </div>
    
    <div class="metric-card">
        <div class="metric-icon revenue" style="background: rgba(220, 53, 69, 0.1); color: var(--danger-color);">
            <i class="fas fa-times-circle"></i>
        </div>
        <div class="metric-title">Failed</div>
        <div class="metric-value"><?php echo number_format($todayStats['failed_count']); ?></div>
    </div>
</div>

<!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="success" <?php echo $statusFilter === 'success' ? 'selected' : ''; ?>>Success</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Service</label>
                    <select name="service" class="form-select">
                        <option value="all" <?php echo $serviceFilter === 'all' ? 'selected' : ''; ?>>All Services</option>
                        <option value="ecommerce" <?php echo $serviceFilter === 'ecommerce' ? 'selected' : ''; ?>>E-Commerce</option>
                        <option value="money_transfer" <?php echo $serviceFilter === 'money_transfer' ? 'selected' : ''; ?>>Money Transfer</option>
                        <option value="logistics" <?php echo $serviceFilter === 'logistics' ? 'selected' : ''; ?>>Logistics</option>
                        <option value="procurement" <?php echo $serviceFilter === 'procurement' ? 'selected' : ''; ?>>Procurement</option>
                        <option value="wallet_topup" <?php echo $serviceFilter === 'wallet_topup' ? 'selected' : ''; ?>>Wallet Top-up</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Payment Method</label>
                    <select name="method" class="form-select">
                        <option value="all" <?php echo $paymentMethodFilter === 'all' ? 'selected' : ''; ?>>All Methods</option>
                        <option value="card" <?php echo $paymentMethodFilter === 'card' ? 'selected' : ''; ?>>Card</option>
                        <option value="mobile_money" <?php echo $paymentMethodFilter === 'mobile_money' ? 'selected' : ''; ?>>Mobile Money</option>
                        <option value="bank_transfer" <?php echo $paymentMethodFilter === 'bank_transfer' ? 'selected' : ''; ?>>Bank Transfer</option>
                        <option value="wallet" <?php echo $paymentMethodFilter === 'wallet' ? 'selected' : ''; ?>>Wallet</option>
                        <option value="cod" <?php echo $paymentMethodFilter === 'cod' ? 'selected' : ''; ?>>Cash on Delivery</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-select" value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-select" value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Transactions Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($transactions)): ?>
                <p class="text-muted text-center py-5">No transactions found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Reference</th>
                                <th>Customer</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $txn): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($txn['transaction_ref']); ?></code></td>
                                <td>
                                    <div><?php echo htmlspecialchars($txn['email'] ?? 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($txn['phone'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-secondary">
                                        <?php echo ucfirst(str_replace('_', ' ', $txn['service_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo formatCurrency($txn['amount']); ?></td>
                                <td>
                                    <?php 
                                    $method = ucfirst(str_replace('_', ' ', $txn['payment_method']));
                                    echo $method === 'Cod' ? 'Cash on Delivery' : $method;
                                    ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $txn['status'] === 'success' ? 'success' : 
                                            ($txn['status'] === 'failed' ? 'danger' : 'warning'); 
                                    ?>">
                                        <?php echo ucfirst($txn['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($txn['created_at'])); ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#txnModal<?php echo $txn['id']; ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                    
                                    <!-- Transaction Detail Modal -->
                                    <div class="modal fade" id="txnModal<?php echo $txn['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Transaction Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <table class="table table-sm">
                                                        <tr>
                                                            <th width="30%">Reference:</th>
                                                            <td><code><?php echo htmlspecialchars($txn['transaction_ref']); ?></code></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Paystack Reference:</th>
                                                            <td><code><?php echo htmlspecialchars($txn['paystack_reference'] ?? 'N/A'); ?></code></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Customer:</th>
                                                            <td>
                                                                <?php echo htmlspecialchars($txn['email'] ?? 'N/A'); ?><br>
                                                                <small><?php echo htmlspecialchars($txn['phone'] ?? ''); ?></small>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Service Type:</th>
                                                            <td><?php echo ucfirst(str_replace('_', ' ', $txn['service_type'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Amount:</th>
                                                            <td><strong><?php echo formatCurrency($txn['amount']); ?></strong></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Payment Method:</th>
                                                            <td><?php echo ucfirst(str_replace('_', ' ', $txn['payment_method'])); ?></td>
                                                        </tr>
                                                        <tr>
                                                            <th>Status:</th>
                                                            <td>
                                                                <span class="badge bg-<?php 
                                                                    echo $txn['status'] === 'success' ? 'success' : 
                                                                        ($txn['status'] === 'failed' ? 'danger' : 'warning'); 
                                                                ?>">
                                                                    <?php echo ucfirst($txn['status']); ?>
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <th>Created:</th>
                                                            <td><?php echo date('M d, Y H:i:s', strtotime($txn['created_at'])); ?></td>
                                                        </tr>
                                                    </table>
                                                    
                                                    <?php if ($txn['paystack_response']): 
                                                        $response = json_decode($txn['paystack_response'], true);
                                                        if ($response):
                                                    ?>
                                                    <h6 class="mt-3">Paystack Response:</h6>
                                                    <pre class="bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;"><code><?php echo htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)); ?></code></pre>
                                                    <?php endif; endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Transactions pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>&service=<?php echo $serviceFilter; ?>&method=<?php echo $paymentMethodFilter; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&service=<?php echo $serviceFilter; ?>&method=<?php echo $paymentMethodFilter; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>&service=<?php echo $serviceFilter; ?>&method=<?php echo $paymentMethodFilter; ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Payment Transactions - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';







