<?php
/**
 * View Transfer Details - Premium Design
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$transferId = intval($_GET['id'] ?? 0);

if ($transferId <= 0) {
    redirect('/user/transfers/index.php', 'Invalid transfer ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get transfer details
$stmt = $conn->prepare("
    SELECT mt.*, u.email, u.phone, up.first_name, up.last_name
    FROM money_transfers mt
    LEFT JOIN users u ON mt.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE mt.id = ? AND mt.user_id = ?
");
$stmt->execute([$transferId, $userId]);
$transfer = $stmt->fetch();

if (!$transfer) {
    redirect('/user/transfers/index.php', 'Transfer not found.', 'danger');
}

// Get transfer tracking history
$stmt = $conn->prepare("
    SELECT tt.*, au.username as admin_username
    FROM transfer_tracking tt
    LEFT JOIN admin_users au ON tt.admin_id = au.id
    WHERE tt.transfer_id = ?
    ORDER BY tt.created_at ASC
");
$stmt->execute([$transferId]);
$trackingHistory = $stmt->fetchAll();

// Decode recipient details
$recipientDetails = json_decode($transfer['recipient_details'], true) ?? [];

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-transfer-view.css'
];

ob_start();
?>

<div class="mb-5">
    <a href="<?php echo BASE_URL; ?>/user/transfers/" class="btn btn-outline-light text-dark rounded-pill px-4 fw-800 x-small shadow-sm mb-4">
        <i class="fas fa-chevron-left me-2"></i> ACTIVITY
    </a>
    
    <div class="transfer-header-premium shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
            <div>
                <span class="text-muted x-small fw-800 text-uppercase letter-spacing-1 mb-1 d-block">TRANSACTION TOKEN</span>
                <h2 class="fw-800 text-dark mb-1"><?php echo htmlspecialchars($transfer['token']); ?></h2>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small fw-800 text-uppercase"><?php echo date('M d, Y • h:i A', strtotime($transfer['created_at'])); ?></span>
                    <span class="text-muted small">•</span>
                    <span class="text-muted small fw-800 text-uppercase"><?php echo $transfer['transfer_type'] === 'send_to_china' ? 'Export to China (CNY)' : 'Import to Ghana (GHS)'; ?></span>
                </div>
            </div>
            <div class="text-md-end">
                <?php 
                $sClass = 'bg-secondary-soft text-secondary';
                if($transfer['status'] === 'completed') $sClass = 'bg-success-soft text-success';
                elseif($transfer['status'] === 'failed' || $transfer['status'] === 'cancelled') $sClass = 'bg-danger-soft text-danger';
                elseif($transfer['status'] === 'processing' || $transfer['status'] === 'payment_received') $sClass = 'bg-info-soft text-info';
                ?>
                <div class="transfer-badge-premium <?php echo $sClass; ?> mb-2">
                    <i class="fas fa-circle x-small"></i> <?php echo strtoupper(str_replace('_', ' ', $transfer['status'])); ?>
                </div>
                <div class="fw-800 text-primary h4 mb-0"><?php echo formatCurrency($transfer['total_amount']); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Progress Tracking -->
        <div class="card-premium-view p-4 p-md-5 shadow-sm">
            <h6 class="fw-800 text-dark mb-5 text-uppercase letter-spacing-2"><i class="fas fa-route me-2 text-primary opacity-50"></i>Transaction Journey</h6>
            
            <div class="timeline-premium">
                <?php if (empty($trackingHistory)): ?>
                    <div class="timeline-item-p active">
                        <div class="timeline-dot"></div>
                        <h6 class="fw-800 text-dark mb-1 text-uppercase x-small">Request Initiated</h6>
                        <p class="text-muted small mb-0 fw-bold">THE TRANSFER REQUEST HAS BEEN SUCCESSFULLY QUEUED FOR VALIDATION.</p>
                    </div>
                <?php else: ?>
                    <?php foreach (array_reverse($trackingHistory) as $index => $track): ?>
                        <div class="timeline-item-p <?php echo $index === 0 ? 'active' : ''; ?>">
                            <div class="timeline-dot"></div>
                            <div class="d-flex justify-content-between mb-1">
                                <h6 class="fw-800 text-dark mb-0 text-uppercase x-small"><?php echo str_replace('_', ' ', $track['status']); ?></h6>
                                <span class="text-muted x-small fw-800"><?php echo date('M d, H:i', strtotime($track['created_at'])); ?></span>
                            </div>
                            <p class="text-muted small mb-0 fw-bold text-uppercase"><?php echo htmlspecialchars($track['notes']); ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Verification Records -->
        <?php if (isset($recipientDetails['qr_codes']) && is_array($recipientDetails['qr_codes']) && !empty($recipientDetails['qr_codes'])): ?>
            <div class="card-premium-view shadow-sm">
                <div class="p-4 border-bottom bg-white">
                    <h6 class="fw-800 text-dark mb-0 text-uppercase letter-spacing-2">Verification Assets</h6>
                </div>
                <div class="p-4">
                    <?php foreach ($recipientDetails['qr_codes'] as $index => $qr): if (!empty($qr['qr_code'])): ?>
                        <div class="qr-verification-card mb-4 shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-800 text-dark mb-0 text-uppercase x-small">Record Reference #<?php echo $index + 1; ?></h6>
                                <span class="badge bg-dark text-white rounded-pill px-3 py-1 fw-800 x-small">¥<?php echo number_format($qr['amount'] ?? 0, 2); ?> CNY</span>
                            </div>
                            <div class="row g-4">
                                <div class="col-6">
                                    <span class="meta-label-premium">SUBMITTED ASSET</span>
                                    <img src="<?php echo BASE_URL . '/assets/images/uploads/' . htmlspecialchars($qr['qr_code']); ?>" class="qr-preview-premium shadow-sm" alt="Asset">
                                </div>
                                <div class="col-6">
                                    <span class="meta-label-premium">CLEARANCE PROOF</span>
                                    <?php if (!empty($qr['payment_confirmation_qr'])): ?>
                                        <img src="<?php echo BASE_URL . '/assets/images/uploads/' . htmlspecialchars($qr['payment_confirmation_qr']); ?>" class="qr-preview-premium shadow-sm" style="border: 2px solid #10b981;" alt="Proof">
                                    <?php else: ?>
                                        <div class="d-flex align-items-center justify-content-center bg-white rounded-3 border-dashed" style="aspect-ratio: 1; border: 1px dashed #cbd5e1;">
                                            <div class="text-center p-3">
                                                <i class="fas fa-clock text-muted opacity-20 fa-2x mb-2"></i>
                                                <div class="x-small text-muted fw-800 text-uppercase">PENDING<br>CLEARANCE</div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Recipient Profile -->
        <div class="card-premium-view p-4 shadow-sm">
            <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-2">Beneficiary Profile</h6>
            <div class="d-flex align-items-center gap-3 mb-4">
                <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center fw-800 shadow-sm" style="width: 48px; height: 48px;">
                    <?php echo strtoupper(substr($transfer['recipient_name'], 0, 1)); ?>
                </div>
                <div>
                    <div class="fw-800 text-dark small mb-0 text-uppercase"><?php echo htmlspecialchars($transfer['recipient_name']); ?></div>
                    <div class="text-muted x-small fw-bold"><?php echo htmlspecialchars($transfer['recipient_phone']); ?></div>
                </div>
            </div>
            <div class="p-3 bg-light rounded-3 border-0 text-center">
                <span class="meta-label-premium">Operational Methodology</span>
                <div class="x-small fw-800 text-dark text-uppercase">
                    <?php echo $transfer['transfer_type'] === 'send_to_china' ? 'GHS → CNY (CHINA)' : 'CNY → GHS (GHANA)'; ?>
                </div>
            </div>
        </div>

        <!-- Financial Ledger -->
        <div class="card-premium-view p-4 shadow-sm">
            <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-2">Financial Ledger</h6>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted x-small fw-800 text-uppercase">Nominal Value</span>
                <span class="fw-800 text-dark x-small"><?php echo formatCurrency($transfer['amount_ghs']); ?></span>
            </div>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted x-small fw-800 text-uppercase">Exchange Output</span>
                <span class="fw-800 text-dark x-small">¥<?php echo number_format($transfer['amount_cny'], 2); ?> CNY</span>
            </div>
            <?php if ($transfer['transfer_fee'] > 0): ?>
            <div class="d-flex justify-content-between mb-2">
                <span class="text-muted x-small fw-800 text-uppercase">Transaction Fee</span>
                <span class="fw-800 text-success x-small">+<?php echo formatCurrency($transfer['transfer_fee']); ?></span>
            </div>
            <?php endif; ?>
            <div class="mt-4 pt-4 border-top d-flex justify-content-between align-items-center">
                <span class="fw-800 text-dark x-small text-uppercase">TOTAL FLOW</span>
                <span class="fw-800 text-primary h5 mb-0"><?php echo formatCurrency($transfer['total_amount']); ?></span>
            </div>
            
            <div class="mt-4 p-3 rounded-3 bg-primary-soft text-center border-0">
                <div class="meta-label-premium">APPLIED EXCHANGE RATE</div>
                <div class="fw-800 text-primary x-small">1 GHS = <?php echo number_format($transfer['exchange_rate'], 4); ?> CNY</div>
            </div>
        </div>

        <!-- Clearance Profile -->
        <div class="card-premium-view p-4 shadow-sm">
            <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-2">Clearance Info</h6>
            <div class="d-flex align-items-center gap-3">
                <div class="bg-success-soft text-success rounded-circle p-2 shadow-sm"><i class="fas fa-credit-card"></i></div>
                <div>
                    <div class="fw-800 text-dark x-small mb-0 text-uppercase"><?php echo str_replace('_', ' ', $transfer['payment_method']); ?></div>
                    <div class="badge bg-success text-white x-small rounded-pill px-2 fw-800">CONFIRMED</div>
                </div>
            </div>
            <?php if ($transfer['paystack_reference']): ?>
                <div class="mt-4 pt-3 border-top border-light">
                    <span class="meta-label-premium">PAYMENT REFERENCE</span>
                    <div class="x-small fw-bold text-muted text-truncate"><?php echo strtoupper(htmlspecialchars($transfer['paystack_reference'])); ?></div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Transaction Clearance - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
