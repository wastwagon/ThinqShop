<?php
/**
 * Public Transfer Tracking
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';

// If no token, show search form
if (empty($token)) {
    // Use dashboard layout if logged in, otherwise use frontend layout
    if (isLoggedIn()) {
        $pageTitle = 'Track Transfer - ' . APP_NAME;
        ob_start();
        ?>
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <h3 class="mb-4"><i class="fas fa-search me-2"></i>Track Money Transfer</h3>
                            <form method="GET" action="">
                                <div class="mb-3">
                                    <label class="form-label">Enter Transfer Token</label>
                                    <input type="text" name="token" class="form-control form-control-lg" 
                                           placeholder="GH2CHN-2025-XXXXXX or CHN2GH-2025-XXXXXX" required autofocus>
                                    <small class="form-text text-muted">Enter the token you received via email</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>Track Transfer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        include __DIR__ . '/../includes/layouts/user-layout.php';
        exit;
    } else {
        $pageTitle = 'Track Transfer - ' . APP_NAME;
        include __DIR__ . '/../includes/header.php';
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center py-5">
                            <h3 class="mb-4"><i class="fas fa-search me-2"></i>Track Money Transfer</h3>
                            <form method="GET" action="">
                                <div class="mb-3">
                                    <label class="form-label">Enter Transfer Token</label>
                                    <input type="text" name="token" class="form-control form-control-lg" 
                                           placeholder="GH2CHN-2025-XXXXXX or CHN2GH-2025-XXXXXX" required autofocus>
                                    <small class="form-text text-muted">Enter the token you received via email</small>
                                </div>
                                <button type="submit" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-search me-2"></i>Track Transfer
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}

$db = new Database();
$conn = $db->getConnection();

// Get transfer details
$stmt = $conn->prepare("
    SELECT mt.*, u.email
    FROM money_transfers mt
    LEFT JOIN users u ON mt.user_id = u.id
    WHERE mt.token = ?
");
$stmt->execute([$token]);
$transfer = $stmt->fetch();

if (!$transfer) {
    // Use dashboard layout if logged in, otherwise use frontend layout
    if (isLoggedIn()) {
        $pageTitle = 'Track Transfer - ' . APP_NAME;
        ob_start();
        ?>
        <div class="container-fluid py-4">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert alert-danger border-0 shadow-sm">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Transfer Not Found</h5>
                        <p class="mb-3">The token you entered is invalid. Please check and try again.</p>
                        <a href="<?php echo BASE_URL; ?>/public/track-transfer.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Try Again
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        $content = ob_get_clean();
        include __DIR__ . '/../includes/layouts/user-layout.php';
        exit;
    } else {
        $pageTitle = 'Track Transfer - ' . APP_NAME;
        include __DIR__ . '/../includes/header.php';
        ?>
        <div class="container my-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="alert alert-danger border-0 shadow-sm">
                        <h5><i class="fas fa-exclamation-triangle me-2"></i>Transfer Not Found</h5>
                        <p class="mb-3">The token you entered is invalid. Please check and try again.</p>
                        <a href="<?php echo BASE_URL; ?>/public/track-transfer.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left me-2"></i>Try Again
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        include __DIR__ . '/../includes/footer.php';
        exit;
    }
}

// Get tracking history
$stmt = $conn->prepare("
    SELECT * FROM transfer_tracking 
    WHERE transfer_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$transfer['id']]);
$trackingHistory = $stmt->fetchAll();

// Status display
$statusInfo = [
    'payment_received' => ['label' => 'Payment Received', 'icon' => 'check-circle', 'color' => 'success'],
    'processing' => ['label' => 'Processing Transfer', 'icon' => 'cog', 'color' => 'info'],
    'sent_to_partner' => ['label' => 'Sent to China Partner', 'icon' => 'paper-plane', 'color' => 'info'],
    'completed' => ['label' => 'Completed', 'icon' => 'check-double', 'color' => 'success'],
    'failed' => ['label' => 'Failed', 'icon' => 'times-circle', 'color' => 'danger'],
    'request_submitted' => ['label' => 'Request Submitted', 'icon' => 'file-alt', 'color' => 'info'],
    'awaiting_payment' => ['label' => 'Awaiting Payment from China', 'icon' => 'clock', 'color' => 'warning'],
    'payment_received_china' => ['label' => 'Payment Received from China', 'icon' => 'check', 'color' => 'success'],
    'transferred_account' => ['label' => 'Transferred to Your Account', 'icon' => 'wallet', 'color' => 'success'],
    'cancelled' => ['label' => 'Cancelled', 'icon' => 'ban', 'color' => 'danger']
];

$currentStatus = $statusInfo[$transfer['status']] ?? ['label' => ucfirst($transfer['status']), 'icon' => 'info-circle', 'color' => 'secondary'];

// Use dashboard layout if logged in, otherwise use frontend layout
if (isLoggedIn()) {
    $pageTitle = 'Track Transfer #' . htmlspecialchars($token) . ' - ' . APP_NAME;
    ob_start();
    ?>
    <div class="container-fluid py-4">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white border-0">
                        <h4 class="mb-0"><i class="fas fa-truck me-2"></i>Transfer Tracking</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4 pb-3 border-bottom">
                            <h5 class="mb-2 text-muted">Transfer Token</h5>
                            <code class="fs-3 d-inline-block px-4 py-2 bg-light rounded border"><?php echo htmlspecialchars($token); ?></code>
                        </div>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Transfer Details</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <th class="text-muted" style="width: 40%;">Type:</th>
                                                <td><?php echo $transfer['transfer_type'] === 'send_to_china' ? 'Send to China' : 'Receive from China'; ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Amount:</th>
                                                <td><strong><?php echo formatCurrency($transfer['amount_ghs']); ?> GHS</strong></td>
                                            </tr>
                                            <?php 
                                            $amountReceived = $transfer['amount_cny'] ?? $transfer['amount_received'] ?? 0;
                                            $receivingCurrency = $transfer['receiving_currency'] ?? 'CNY';
                                            if ($amountReceived > 0): 
                                            ?>
                                            <tr>
                                                <th class="text-muted">Recipient Receives:</th>
                                                <td><strong><?php echo number_format($amountReceived, 2); ?> <?php echo $receivingCurrency; ?></strong></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($transfer['exchange_rate'])): ?>
                                            <tr>
                                                <th class="text-muted">Exchange Rate:</th>
                                                <td>1 GHS = <?php echo number_format($transfer['exchange_rate'], 4); ?> <?php echo $receivingCurrency; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <th class="text-muted">Status:</th>
                                                <td>
                                                    <span class="badge bg-<?php echo $currentStatus['color']; ?> px-3 py-2">
                                                        <i class="fas fa-<?php echo $currentStatus['icon']; ?>"></i>
                                                        <?php echo $currentStatus['label']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3"><i class="fas fa-user me-2 text-primary"></i>Recipient Information</h6>
                                        <p class="mb-2">
                                            <strong>Name:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($transfer['recipient_name']); ?></span>
                                        </p>
                                        <p class="mb-2">
                                            <strong>Phone:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($transfer['recipient_phone']); ?></span>
                                        </p>
                                        <?php if ($transfer['transfer_type'] === 'send_to_china'): ?>
                                        <p class="mb-0">
                                            <strong>Type:</strong><br>
                                            <span class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $transfer['recipient_type'])); ?></span>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tracking Timeline -->
                        <div class="mb-4">
                            <h6 class="mb-3"><i class="fas fa-history me-2"></i>Tracking History</h6>
                            <?php if (empty($trackingHistory)): ?>
                                <div class="alert alert-info border-0">
                                    <p class="mb-0"><i class="fas fa-info-circle me-2"></i>No tracking history available yet. Updates will appear here as your transfer is processed.</p>
                                </div>
                            <?php else: ?>
                            <div class="timeline ps-3">
                                <?php foreach ($trackingHistory as $index => $track): 
                                    $trackStatus = $statusInfo[$track['status']] ?? ['label' => ucfirst($track['status']), 'icon' => 'info-circle', 'color' => 'secondary'];
                                ?>
                                <div class="mb-4 position-relative <?php echo $index < count($trackingHistory) - 1 ? 'pb-4 border-start border-2 border-primary' : ''; ?>" 
                                     style="padding-left: 2.5rem;">
                                    <div class="position-absolute start-0 top-0 translate-middle">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 1.5rem; height: 1.5rem; border: 3px solid white; box-shadow: 0 0 0 3px #0d6efd;">
                                            <i class="fas fa-<?php echo $trackStatus['icon']; ?> text-white" style="font-size: 0.6rem;"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <strong class="d-block mb-1">
                                            <i class="fas fa-<?php echo $trackStatus['icon']; ?> text-<?php echo $trackStatus['color']; ?> me-2"></i>
                                            <?php echo $trackStatus['label']; ?>
                                        </strong>
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($track['created_at'])); ?>
                                        </small>
                                        <?php if (!empty($track['notes'])): ?>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($track['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($transfer['status'] !== 'completed' && $transfer['status'] !== 'failed' && $transfer['status'] !== 'cancelled'): ?>
                        <div class="alert alert-info border-0 mt-4">
                            <strong><i class="fas fa-calendar-alt me-2"></i>Estimated Completion:</strong> 
                            <?php 
                            $created = new DateTime($transfer['created_at']);
                            $estimated = clone $created;
                            $estimated->modify('+2-5 business days');
                            echo $estimated->format('F d, Y');
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4 pt-3 border-top">
                            <a href="<?php echo BASE_URL; ?>/public/track-transfer.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-search me-2"></i>Track Another Transfer
                            </a>
                            <a href="<?php echo BASE_URL; ?>/user/transfers/" class="btn btn-primary">
                                <i class="fas fa-list me-2"></i>View My Transfers
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    include __DIR__ . '/../includes/layouts/user-layout.php';
} else {
    // Public view (not logged in)
    $pageTitle = 'Track Transfer #' . htmlspecialchars($token) . ' - ' . APP_NAME;
    include __DIR__ . '/../includes/header.php';
    ?>
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white border-0">
                        <h4 class="mb-0"><i class="fas fa-truck me-2"></i>Transfer Tracking</h4>
                    </div>
                    <div class="card-body p-4">
                        <div class="text-center mb-4 pb-3 border-bottom">
                            <h5 class="mb-2 text-muted">Transfer Token</h5>
                            <code class="fs-3 d-inline-block px-4 py-2 bg-light rounded border"><?php echo htmlspecialchars($token); ?></code>
                        </div>
                        
                        <div class="row g-4 mb-4">
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Transfer Details</h6>
                                        <table class="table table-sm table-borderless mb-0">
                                            <tr>
                                                <th class="text-muted" style="width: 40%;">Type:</th>
                                                <td><?php echo $transfer['transfer_type'] === 'send_to_china' ? 'Send to China' : 'Receive from China'; ?></td>
                                            </tr>
                                            <tr>
                                                <th class="text-muted">Amount:</th>
                                                <td><strong><?php echo formatCurrency($transfer['amount_ghs']); ?> GHS</strong></td>
                                            </tr>
                                            <?php 
                                            $amountReceived = $transfer['amount_cny'] ?? $transfer['amount_received'] ?? 0;
                                            $receivingCurrency = $transfer['receiving_currency'] ?? 'CNY';
                                            if ($amountReceived > 0): 
                                            ?>
                                            <tr>
                                                <th class="text-muted">Recipient Receives:</th>
                                                <td><strong><?php echo number_format($amountReceived, 2); ?> <?php echo $receivingCurrency; ?></strong></td>
                                            </tr>
                                            <?php endif; ?>
                                            <?php if (!empty($transfer['exchange_rate'])): ?>
                                            <tr>
                                                <th class="text-muted">Exchange Rate:</th>
                                                <td>1 GHS = <?php echo number_format($transfer['exchange_rate'], 4); ?> <?php echo $receivingCurrency; ?></td>
                                            </tr>
                                            <?php endif; ?>
                                            <tr>
                                                <th class="text-muted">Status:</th>
                                                <td>
                                                    <span class="badge bg-<?php echo $currentStatus['color']; ?> px-3 py-2">
                                                        <i class="fas fa-<?php echo $currentStatus['icon']; ?>"></i>
                                                        <?php echo $currentStatus['label']; ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <h6 class="card-title mb-3"><i class="fas fa-user me-2 text-primary"></i>Recipient Information</h6>
                                        <p class="mb-2">
                                            <strong>Name:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($transfer['recipient_name']); ?></span>
                                        </p>
                                        <p class="mb-2">
                                            <strong>Phone:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($transfer['recipient_phone']); ?></span>
                                        </p>
                                        <?php if ($transfer['transfer_type'] === 'send_to_china'): ?>
                                        <p class="mb-0">
                                            <strong>Type:</strong><br>
                                            <span class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $transfer['recipient_type'])); ?></span>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tracking Timeline -->
                        <div class="mb-4">
                            <h6 class="mb-3"><i class="fas fa-history me-2"></i>Tracking History</h6>
                            <?php if (empty($trackingHistory)): ?>
                                <div class="alert alert-info border-0">
                                    <p class="mb-0"><i class="fas fa-info-circle me-2"></i>No tracking history available yet. Updates will appear here as your transfer is processed.</p>
                                </div>
                            <?php else: ?>
                            <div class="timeline ps-3">
                                <?php foreach ($trackingHistory as $index => $track): 
                                    $trackStatus = $statusInfo[$track['status']] ?? ['label' => ucfirst($track['status']), 'icon' => 'info-circle', 'color' => 'secondary'];
                                ?>
                                <div class="mb-4 position-relative <?php echo $index < count($trackingHistory) - 1 ? 'pb-4 border-start border-2 border-primary' : ''; ?>" 
                                     style="padding-left: 2.5rem;">
                                    <div class="position-absolute start-0 top-0 translate-middle">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center" 
                                             style="width: 1.5rem; height: 1.5rem; border: 3px solid white; box-shadow: 0 0 0 3px #0d6efd;">
                                            <i class="fas fa-<?php echo $trackStatus['icon']; ?> text-white" style="font-size: 0.6rem;"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <strong class="d-block mb-1">
                                            <i class="fas fa-<?php echo $trackStatus['icon']; ?> text-<?php echo $trackStatus['color']; ?> me-2"></i>
                                            <?php echo $trackStatus['label']; ?>
                                        </strong>
                                        <small class="text-muted d-block mb-2">
                                            <i class="fas fa-clock me-1"></i><?php echo date('M d, Y h:i A', strtotime($track['created_at'])); ?>
                                        </small>
                                        <?php if (!empty($track['notes'])): ?>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($track['notes']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($transfer['status'] !== 'completed' && $transfer['status'] !== 'failed' && $transfer['status'] !== 'cancelled'): ?>
                        <div class="alert alert-info border-0 mt-4">
                            <strong><i class="fas fa-calendar-alt me-2"></i>Estimated Completion:</strong> 
                            <?php 
                            $created = new DateTime($transfer['created_at']);
                            $estimated = clone $created;
                            $estimated->modify('+2-5 business days');
                            echo $estimated->format('F d, Y');
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-4 pt-3 border-top">
                            <a href="<?php echo BASE_URL; ?>/public/track-transfer.php" class="btn btn-outline-primary">
                                <i class="fas fa-search me-2"></i>Track Another Transfer
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    include __DIR__ . '/../includes/footer.php';
}
?>
