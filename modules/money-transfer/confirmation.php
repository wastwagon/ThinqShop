<?php
/**
 * Transfer Confirmation
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    redirect('/user/dashboard.php', 'Invalid transfer token.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get transfer details
$stmt = $conn->prepare("SELECT * FROM money_transfers WHERE token = ? AND user_id = ?");
$stmt->execute([$token, $userId]);
$transfer = $stmt->fetch();

if (!$transfer) {
    redirect('/user/dashboard.php', 'Transfer not found.', 'danger');
}

// Decode recipient details
$recipientDetails = json_decode($transfer['recipient_details'], true) ?? [];
$recipientType = $transfer['recipient_type'] ?? '';

// Determine if we should show recipient name/phone
$showRecipientNamePhone = !in_array($recipientType, ['alipay', 'wechat_pay']);

// Set page title
$pageTitle = 'Transfer Confirmation - ' . APP_NAME;

// Start output buffering for content
ob_start();
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-success text-white border-0">
                    <h4 class="mb-0"><i class="fas fa-check-circle me-2"></i>Transfer Initiated Successfully!</h4>
                </div>
                <div class="card-body p-4">
                    <div class="text-center mb-4">
                        <h5 class="mb-3">Your Transfer Token</h5>
                        <code class="fs-3 d-inline-block px-3 py-2 bg-light rounded border"><?php echo htmlspecialchars($token); ?></code>
                        <p class="text-muted mt-3 mb-0">Keep this token safe. You'll need it to track your transfer.</p>
                    </div>
                    
                    <div class="alert alert-info border-0 mb-4">
                        <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>What Happens Next?</h6>
                        <?php 
                        // Check if transfer has amount_cny or use amount_received
                        $amountReceived = $transfer['amount_cny'] ?? $transfer['amount_received'] ?? 0;
                        $receivingCurrency = $transfer['receiving_currency'] ?? 'CNY';
                        ?>
                        <ol class="mb-0 ps-3">
                            <li>Your payment has been received and confirmed</li>
                            <li>Our team is processing your transfer</li>
                            <?php if ($amountReceived > 0): ?>
                            <li>The recipient will receive <?php echo number_format($amountReceived, 2); ?> <?php echo $receivingCurrency; ?></li>
                            <?php endif; ?>
                            <li>You'll receive email updates at each stage</li>
                            <li>Estimated completion: 2-5 business days</li>
                        </ol>
                    </div>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card border">
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
                                        <tr>
                                            <th class="text-muted">Status:</th>
                                            <td>
                                                <span class="badge bg-<?php echo $transfer['status'] === 'processing' ? 'info' : 'warning'; ?> px-3 py-2">
                                                    <?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card border">
                                <div class="card-body">
                                    <h6 class="card-title mb-3">
                                        <i class="fas fa-user me-2 text-primary"></i>Recipient
                                        <?php if (in_array($recipientType, ['alipay', 'wechat_pay'])): ?>
                                            <span class="badge bg-info ms-2"><?php echo ucfirst(str_replace('_', ' ', $recipientType)); ?></span>
                                        <?php endif; ?>
                                    </h6>
                                    
                                    <?php if ($showRecipientNamePhone): ?>
                                        <!-- Show name and phone for bank_account and in_person -->
                                        <p class="mb-2">
                                            <strong>Name:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($transfer['recipient_name']); ?></span>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Phone:</strong><br>
                                            <span class="text-muted"><?php echo htmlspecialchars($transfer['recipient_phone']); ?></span>
                                        </p>
                                    <?php elseif (in_array($recipientType, ['alipay', 'wechat_pay']) && isset($recipientDetails['qr_codes']) && is_array($recipientDetails['qr_codes'])): ?>
                                        <!-- Show QR code information for Alipay/WeChat -->
                                        <p class="mb-3">
                                            <strong>Payment Method:</strong><br>
                                            <span class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $recipientType)); ?> QR Code(s)</span>
                                        </p>
                                        <p class="mb-2">
                                            <strong>Number of QR Codes:</strong><br>
                                            <span class="text-muted"><?php echo count($recipientDetails['qr_codes']); ?> QR code(s) uploaded</span>
                                        </p>
                                        <?php 
                                        $totalQRAmount = 0;
                                        foreach ($recipientDetails['qr_codes'] as $qr) {
                                            $totalQRAmount += floatval($qr['amount'] ?? 0);
                                        }
                                        if ($totalQRAmount > 0):
                                        ?>
                                        <p class="mb-0">
                                            <strong>Total Amount:</strong><br>
                                            <span class="text-muted">¥<?php echo number_format($totalQRAmount, 2); ?> CNY</span>
                                        </p>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <!-- Fallback for other recipient types -->
                                        <p class="mb-0 text-muted">
                                            <strong>Payment Method:</strong><br>
                                            <?php echo ucfirst(str_replace('_', ' ', $recipientType)); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center pt-3">
                        <a href="<?php echo BASE_URL; ?>/public/track-transfer.php?token=<?php echo urlencode($token); ?>" 
                           class="btn btn-primary btn-lg me-3 px-4">
                            <i class="fas fa-search me-2"></i>Track Transfer
                        </a>
                        <a href="<?php echo BASE_URL; ?>/user/transfers/" class="btn btn-outline-primary btn-lg px-4">
                            <i class="fas fa-list me-2"></i>View All Transfers
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get content and include layout
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
