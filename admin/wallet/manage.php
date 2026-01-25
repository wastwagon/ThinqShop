<?php
/**
 * Admin Wallet Management
 * Credit and Debit User Wallets
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle wallet transaction
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wallet_transaction'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/wallet/manage.php', 'Invalid security token.', 'danger');
    }
    
    $userId = intval($_POST['user_id'] ?? 0);
    $transactionType = sanitize($_POST['transaction_type'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');
    
    if ($userId <= 0) {
        redirect('/admin/wallet/manage.php', 'Please select a user.', 'danger');
    }
    
    if (!in_array($transactionType, ['credit', 'debit'])) {
        redirect('/admin/wallet/manage.php', 'Invalid transaction type.', 'danger');
    }
    
    if ($amount <= 0) {
        redirect('/admin/wallet/manage.php', 'Amount must be greater than 0.', 'danger');
    }
    
    try {
        $conn->beginTransaction();
        
        // Get current wallet balance
        $stmt = $conn->prepare("SELECT balance_ghs FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wallet = $stmt->fetch();
        
        if (!$wallet) {
            // Create wallet if doesn't exist
            $stmt = $conn->prepare("INSERT INTO user_wallets (user_id, balance_ghs) VALUES (?, 0.00)");
            $stmt->execute([$userId]);
            $currentBalance = 0.00;
        } else {
            $currentBalance = floatval($wallet['balance_ghs']);
        }
        
        // Calculate new balance
        if ($transactionType === 'credit') {
            $newBalance = $currentBalance + $amount;
        } else {
            if ($currentBalance < $amount) {
                throw new Exception("Insufficient wallet balance. Current balance: " . formatCurrency($currentBalance));
            }
            $newBalance = $currentBalance - $amount;
            $amount = -$amount; // Negative for debit
        }
        
        // Update wallet balance
        $stmt = $conn->prepare("UPDATE user_wallets SET balance_ghs = ?, updated_at = NOW() WHERE user_id = ?");
        $stmt->execute([$newBalance, $userId]);
        
        // Record transaction in payments table
        $stmt = $conn->prepare("
            INSERT INTO payments (user_id, transaction_ref, amount, payment_method, service_type, service_id, status, created_at)
            VALUES (?, ?, ?, 'wallet', 'wallet_topup', 0, 'success', NOW())
        ");
        $transactionRef = 'WLT-' . strtoupper(substr(md5(time() . $userId), 0, 10));
        $stmt->execute([$userId, $transactionRef, $amount]);
        
        // Log admin action
        if (function_exists('logAdminAction')) {
            logAdminAction($_SESSION['admin_id'], 'wallet_' . $transactionType, 'user_wallets', $userId, [
                'amount' => abs($amount),
                'old_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'notes' => $notes
            ]);
        }
        
        $conn->commit();
        
        // Send notification to user
        if (file_exists(__DIR__ . '/../../includes/notification-helper.php')) {
            require_once __DIR__ . '/../../includes/notification-helper.php';
            
            if ($transactionType === 'credit') {
                $title = 'Wallet Credited';
                $message = 'Your wallet has been credited with ' . formatCurrency(abs($amount)) . '. New balance: ' . formatCurrency($newBalance);
            } else {
                $title = 'Wallet Debited';
                $message = formatCurrency(abs($amount)) . ' has been deducted from your wallet. New balance: ' . formatCurrency($newBalance);
            }
            
            if (!empty($notes)) {
                $message .= ' - ' . $notes;
            }
            
            NotificationHelper::createUserNotification(
                $userId,
                'payment',
                $title,
                $message,
                BASE_URL . '/user/wallet.php'
            );
        }
        
        $message = ucfirst($transactionType) . ' of ' . formatCurrency(abs($amount)) . ' processed successfully.';
        redirect('/admin/wallet/manage.php', $message, 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Wallet Transaction Error: " . $e->getMessage());
        redirect('/admin/wallet/manage.php', 'Failed to process transaction: ' . $e->getMessage(), 'danger');
    }
}

// Get filters
$search = sanitize($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(u.email LIKE ? OR u.phone LIKE ? OR up.first_name LIKE ? OR up.last_name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total 
             FROM users u 
             LEFT JOIN user_profiles up ON u.id = up.user_id 
             $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalUsers = $countStmt->fetch()['total'];
$totalPages = ceil($totalUsers / $perPage);

// Get users with wallet balances
$sql = "SELECT u.id, u.email, u.phone, u.is_active,
               up.first_name, up.last_name,
               COALESCE(uw.balance_ghs, 0.00) as wallet_balance
        FROM users u
        LEFT JOIN user_profiles up ON u.id = up.user_id
        LEFT JOIN user_wallets uw ON u.id = uw.user_id
        $whereClause
        ORDER BY u.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Wallet Management</h1>
</div>

<!-- Wallet Transaction Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0">Credit/Debit User Wallet</h5>
    </div>
    <div class="card-body">
        <form method="POST" action="" id="walletForm">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="wallet_transaction" value="1">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Select User <span class="text-danger">*</span></label>
                    <select name="user_id" id="user_id" class="form-select" required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): 
                            $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                            if (empty($userName)) $userName = $user['email'];
                        ?>
                        <option value="<?php echo $user['id']; ?>" 
                                data-balance="<?php echo $user['wallet_balance']; ?>">
                            <?php echo htmlspecialchars($userName); ?> (<?php echo htmlspecialchars($user['email']); ?>)
                            - Balance: <?php echo formatCurrency($user['wallet_balance']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Transaction Type <span class="text-danger">*</span></label>
                    <select name="transaction_type" id="transaction_type" class="form-select" required>
                        <option value="">-- Select Type --</option>
                        <option value="credit">Credit (Add Money)</option>
                        <option value="debit">Debit (Deduct Money)</option>
                    </select>
                </div>
                
                <div class="col-md-3 mb-3">
                    <label class="form-label">Amount (GHS) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" name="amount" class="form-control" required>
                </div>
                
                <div class="col-md-2 mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-exchange-alt"></i> Process
                    </button>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label">Notes (Optional)</label>
                    <textarea name="notes" class="form-control" rows="2" 
                              placeholder="Reason for this transaction..."></textarea>
                </div>
            </div>
            
            <div id="balance_warning" class="alert alert-warning" style="display: none;">
                <i class="fas fa-exclamation-triangle"></i> 
                <span id="warning_message"></span>
            </div>
        </form>
    </div>
</div>

<!-- Users Wallet List -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end mb-3">
            <div class="col-md-10">
                <label class="form-label">Search Users</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by email, phone, or name" 
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

<div class="card">
    <div class="card-body">
        <?php if (empty($users)): ?>
            <p class="text-muted text-center py-5">No users found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Contact</th>
                            <th>Wallet Balance</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): 
                            $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                            if (empty($userName)) $userName = 'User #' . $user['id'];
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($userName); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($user['email']); ?></div>
                                <small class="text-muted"><?php echo htmlspecialchars($user['phone'] ?? ''); ?></small>
                            </td>
                            <td>
                                <strong class="text-primary"><?php echo formatCurrency($user['wallet_balance']); ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                        onclick="selectUser(<?php echo $user['id']; ?>, <?php echo $user['wallet_balance']; ?>)">
                                    <i class="fas fa-wallet"></i> Manage Wallet
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav aria-label="Users pagination" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function selectUser(userId, balance) {
    document.getElementById('user_id').value = userId;
    document.getElementById('user_id').scrollIntoView({ behavior: 'smooth', block: 'center' });
    updateBalanceWarning();
}

function updateBalanceWarning() {
    const userId = document.getElementById('user_id');
    const transactionType = document.getElementById('transaction_type');
    const amount = document.getElementsByName('amount')[0];
    const warningDiv = document.getElementById('balance_warning');
    const warningMessage = document.getElementById('warning_message');
    
    if (userId.selectedIndex > 0 && transactionType.value === 'debit') {
        const selectedOption = userId.options[userId.selectedIndex];
        const currentBalance = parseFloat(selectedOption.getAttribute('data-balance') || 0);
        const debitAmount = parseFloat(amount.value || 0);
        
        if (debitAmount > currentBalance) {
            warningDiv.style.display = 'block';
            warningMessage.textContent = 'Warning: This debit will result in negative balance. Current balance: ' + 
                new Intl.NumberFormat('en-GH', { style: 'currency', currency: 'GHS' }).format(currentBalance);
        } else {
            warningDiv.style.display = 'none';
        }
    } else {
        warningDiv.style.display = 'none';
    }
}

document.getElementById('user_id').addEventListener('change', updateBalanceWarning);
document.getElementById('transaction_type').addEventListener('change', updateBalanceWarning);
document.getElementsByName('amount')[0].addEventListener('input', updateBalanceWarning);
</script>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Wallet Management - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>

