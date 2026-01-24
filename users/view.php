<?php
/**
 * Admin User Detail View
 * Comprehensive user profile and activity overview
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get user ID
$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    redirect('/admin/users/manage.php', 'Invalid user ID.', 'danger');
}

// Get user details
$stmt = $conn->prepare("
    SELECT u.*, up.first_name, up.last_name, up.profile_image, up.whatsapp_number, 
           up.date_of_birth, up.gender,
           COALESCE(uw.balance_ghs, 0.00) as wallet_balance
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN user_wallets uw ON u.id = uw.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/admin/users/manage.php', 'User not found.', 'danger');
}

// Get activity statistics
$stats = [];

// Ecommerce stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as order_count,
        COALESCE(SUM(total), 0) as total_spent,
        COALESCE(SUM(CASE WHEN payment_status = 'success' THEN total ELSE 0 END), 0) as paid_total
    FROM orders 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$stats['ecommerce'] = $stmt->fetch();

// Money transfer stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as transfer_count,
        COALESCE(SUM(amount_ghs), 0) as total_sent
    FROM money_transfers 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$stats['transfers'] = $stmt->fetch();

// Logistics stats
$stmt = $conn->prepare("
    SELECT 
        COUNT(*) as shipment_count,
        COALESCE(SUM(total_price), 0) as total_shipping_cost
    FROM shipments 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$stats['logistics'] = $stmt->fetch();

// Procurement stats
$stmt = $conn->prepare("
    SELECT COUNT(*) as request_count
    FROM procurement_requests 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$stats['procurement'] = $stmt->fetch();

// Payment transactions count
$stmt = $conn->prepare("
    SELECT COUNT(*) as transaction_count
    FROM payments 
    WHERE user_id = ?
");
$stmt->execute([$userId]);
$stats['payments'] = $stmt->fetch();

$userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (empty($userName)) {
    $userName = explode('@', $user['email'])[0];
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">User Profile</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/users/manage.php">Users</a></li>
                <li class="breadcrumb-item active"><?php echo htmlspecialchars($userName); ?></li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/users/edit.php?id=<?php echo $userId; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Edit User
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/users/manage.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to List
        </a>
    </div>
</div>

<!-- User Profile Card -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body text-center">
                <?php 
                $profileImage = $user['profile_image'] ?? null;
                $imagePath = __DIR__ . '/../../assets/images/profiles/' . ($profileImage ?? '');
                ?>
                <?php if ($profileImage && file_exists($imagePath) && filesize($imagePath) > 0): ?>
                    <img src="<?php echo BASE_URL; ?>/assets/images/profiles/<?php echo htmlspecialchars($profileImage); ?>?v=<?php echo time(); ?>" 
                         alt="<?php echo htmlspecialchars($userName); ?>" 
                         class="rounded-circle mb-3" 
                         style="width: 120px; height: 120px; object-fit: cover;">
                <?php else: ?>
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" 
                         style="width: 120px; height: 120px; font-size: 3rem; font-weight: 600;">
                        <?php echo strtoupper(substr($userName, 0, 1)); ?>
                    </div>
                <?php endif; ?>
                
                <h4 class="mb-1"><?php echo htmlspecialchars($userName); ?></h4>
                <p class="text-muted mb-3"><?php echo htmlspecialchars($user['email']); ?></p>
                
                <span class="badge bg-<?php echo $user['is_active'] ? 'success' : 'danger'; ?> mb-3">
                    <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                </span>
                
                <?php if (!empty($user['user_identifier'])): ?>
                    <div class="mb-2">
                        <small class="text-muted">User ID:</small><br>
                        <strong><?php echo htmlspecialchars($user['user_identifier']); ?></strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Personal Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>First Name:</strong><br>
                        <span><?php echo htmlspecialchars($user['first_name'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Last Name:</strong><br>
                        <span><?php echo htmlspecialchars($user['last_name'] ?? 'Not set'); ?></span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Email:</strong><br>
                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                        <?php if ($user['email_verified_at']): ?>
                            <span class="badge bg-success ms-2">Verified</span>
                        <?php else: ?>
                            <span class="badge bg-warning ms-2">Not Verified</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Phone:</strong><br>
                        <span><?php echo htmlspecialchars($user['phone'] ?? 'Not set'); ?></span>
                        <?php if ($user['phone_verified_at']): ?>
                            <span class="badge bg-success ms-2">Verified</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>WhatsApp Number:</strong><br>
                        <?php if (!empty($user['whatsapp_number'])): 
                            // Clean phone number (remove spaces, dashes, etc.)
                            $whatsappNumber = preg_replace('/[^0-9+]/', '', $user['whatsapp_number']);
                            // Ensure it starts with + if it doesn't already
                            if (substr($whatsappNumber, 0, 1) !== '+') {
                                // If it starts with 0, replace with country code (assuming Ghana +233)
                                if (substr($whatsappNumber, 0, 1) === '0') {
                                    $whatsappNumber = '+233' . substr($whatsappNumber, 1);
                                } else {
                                    $whatsappNumber = '+233' . $whatsappNumber;
                                }
                            }
                            $whatsappUrl = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $whatsappNumber);
                        ?>
                            <a href="<?php echo $whatsappUrl; ?>" target="_blank" class="text-decoration-none">
                                <i class="fab fa-whatsapp text-success me-1"></i>
                                <?php echo htmlspecialchars($user['whatsapp_number']); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">Not set</span>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <strong>Date of Birth:</strong><br>
                        <span><?php echo $user['date_of_birth'] ? date('M d, Y', strtotime($user['date_of_birth'])) : 'Not set'; ?></span>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Gender:</strong><br>
                        <span><?php echo ucfirst($user['gender'] ?? 'Not set'); ?></span>
                    </div>
                    <div class="col-md-6">
                        <strong>Member Since:</strong><br>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Financial Overview -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Financial Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-primary mb-1"><?php echo formatCurrency($user['wallet_balance']); ?></h3>
                            <small class="text-muted">Wallet Balance</small>
                            <div class="mt-2">
                                <a href="<?php echo BASE_URL; ?>/admin/wallet/manage.php?user_id=<?php echo $userId; ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-wallet"></i> Manage Wallet
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-success mb-1"><?php echo formatCurrency($stats['ecommerce']['paid_total']); ?></h3>
                            <small class="text-muted">Total Spent (Ecommerce)</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-info mb-1"><?php echo formatCurrency($stats['transfers']['total_sent']); ?></h3>
                            <small class="text-muted">Total Sent (Transfers)</small>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center p-3 bg-light rounded">
                            <h3 class="text-warning mb-1"><?php echo formatCurrency($stats['logistics']['total_shipping_cost']); ?></h3>
                            <small class="text-muted">Total Shipping Cost</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Activity Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h2 class="text-primary"><?php echo $stats['ecommerce']['order_count']; ?></h2>
                <p class="text-muted mb-0">Ecommerce Orders</p>
                <a href="<?php echo BASE_URL; ?>/admin/ecommerce/orders.php?user_id=<?php echo $userId; ?>" 
                   class="btn btn-sm btn-outline-primary mt-2">View Orders</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h2 class="text-info"><?php echo $stats['transfers']['transfer_count']; ?></h2>
                <p class="text-muted mb-0">Money Transfers</p>
                <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers.php?user_id=<?php echo $userId; ?>" 
                   class="btn btn-sm btn-outline-info mt-2">View Transfers</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h2 class="text-warning"><?php echo $stats['logistics']['shipment_count']; ?></h2>
                <p class="text-muted mb-0">Shipments</p>
                <a href="<?php echo BASE_URL; ?>/admin/logistics/shipments.php?user_id=<?php echo $userId; ?>" 
                   class="btn btn-sm btn-outline-warning mt-2">View Shipments</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h2 class="text-success"><?php echo $stats['procurement']['request_count']; ?></h2>
                <p class="text-muted mb-0">Procurement Requests</p>
                <a href="<?php echo BASE_URL; ?>/admin/procurement/requests.php?user_id=<?php echo $userId; ?>" 
                   class="btn btn-sm btn-outline-success mt-2">View Requests</a>
            </div>
        </div>
    </div>
</div>

<!-- Payment Transactions -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Payment Transactions</h5>
        <a href="<?php echo BASE_URL; ?>/admin/payments/transactions.php?user_id=<?php echo $userId; ?>" 
           class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <?php
        $stmt = $conn->prepare("
            SELECT * FROM payments 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $transactions = $stmt->fetchAll();
        
        if (empty($transactions)):
        ?>
            <p class="text-muted text-center py-3">No transactions found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Reference</th>
                            <th>Service</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><small><?php echo htmlspecialchars($txn['transaction_ref']); ?></small></td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?php echo strtoupper($txn['service_type']); ?>
                                </span>
                            </td>
                            <td><?php echo formatCurrency($txn['amount']); ?></td>
                            <td><?php echo ucfirst($txn['payment_method']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $txn['status'] === 'success' ? 'success' : ($txn['status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                    <?php echo ucfirst($txn['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($txn['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'User Profile - ' . htmlspecialchars($userName) . ' - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>

