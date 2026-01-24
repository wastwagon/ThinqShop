<?php
/**
 * Receive Money from China
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../../includes/auth-check.php';
require_once __DIR__ . '/../../../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Get exchange rate setting (automatic or manual)
$stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'auto_exchange_rate'");
$autoExchangeRate = $stmt->fetch();
$isAutoEnabled = ($autoExchangeRate['setting_value'] ?? '0') === '1';

// Get exchange rate based on setting
if ($isAutoEnabled) {
    // Use automatic rate from exchange_rates table
    $stmt = $conn->query("
        SELECT rate_ghs_to_cny 
        FROM exchange_rates 
        WHERE is_active = 1 AND valid_from <= NOW() AND (valid_to IS NULL OR valid_to >= NOW())
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $rateData = $stmt->fetch();
    $exchangeRate = $rateData ? floatval($rateData['rate_ghs_to_cny']) : 1.25; // Default: 1 GHS = 1.25 CNY
} else {
    // Use manual rate from settings
    $stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'ghs_to_cny_rate'");
    $manualRate = $stmt->fetch();
    $exchangeRate = $manualRate ? floatval($manualRate['setting_value']) : 1.25; // Default: 1 GHS = 1.25 CNY
}

$errors = [];
$success = false;

// Process request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $amountGHS = floatval($_POST['amount_ghs'] ?? 0);
        $amountCNY = floatval($_POST['amount_cny'] ?? 0);
        $senderName = sanitize($_POST['sender_name'] ?? '');
        $senderContact = sanitize($_POST['sender_contact'] ?? '');
        $deliveryMethod = sanitize($_POST['delivery_method'] ?? '');
        
        // Use amount in CNY or GHS
        if ($amountCNY > 0) {
            $amountGHS = $amountCNY / $exchangeRate;
        }
        
        if ($amountGHS < MIN_TRANSFER_AMOUNT) {
            $errors[] = 'Minimum amount is ' . formatCurrency(MIN_TRANSFER_AMOUNT);
        }
        
        if (empty($senderName)) {
            $errors[] = 'Sender name is required.';
        }
        
        if (empty($deliveryMethod) || !in_array($deliveryMethod, ['wallet', 'mobile_money'])) {
            $errors[] = 'Please select delivery method.';
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Generate token
                $token = generateTransferToken('CHN2GH');
                
                // Create transfer record
                $stmt = $conn->prepare("
                    INSERT INTO money_transfers (
                        user_id, transfer_type, token, amount_ghs, amount_cny, exchange_rate,
                        status, sender_name, sender_phone, recipient_name, recipient_phone,
                        recipient_type, recipient_details, expires_at, created_at
                    ) VALUES (?, 'receive_from_china', ?, ?, ?, ?, 'request_submitted', ?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? DAY), NOW())
                ");
                
                // Get user profile
                $stmt_profile = $conn->prepare("SELECT first_name, last_name FROM user_profiles WHERE user_id = ?");
                $stmt_profile->execute([$userId]);
                $profile = $stmt_profile->fetch();
                $recipientName = ($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '');
                
                $recipientDetails = ['delivery_method' => $deliveryMethod];
                if ($deliveryMethod === 'mobile_money') {
                    $recipientDetails['mobile_money_number'] = $user['phone'];
                    $recipientDetails['mobile_money_provider'] = 'MTN'; // TODO: Get from user profile
                }
                
                $stmt->execute([
                    $userId,
                    $token,
                    $amountGHS,
                    $amountCNY > 0 ? $amountCNY : ($amountGHS * $exchangeRate),
                    $exchangeRate,
                    $senderName,
                    $senderContact,
                    $recipientName ?: $user['email'],
                    $user['phone'],
                    $deliveryMethod,
                    json_encode($recipientDetails),
                    TOKEN_EXPIRY_DAYS
                ]);
                $transferId = $conn->lastInsertId();
                
                // Add tracking entry
                $stmt = $conn->prepare("
                    INSERT INTO transfer_tracking (transfer_id, status, notes, created_at)
                    VALUES (?, 'request_submitted', 'Request submitted. Waiting for payment from China.', NOW())
                ");
                $stmt->execute([$transferId]);
                
                $conn->commit();
                
                // TODO: Send email with token
                
                $success = true;
                $transferToken = $token;
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Receive Transfer Error: " . $e->getMessage());
                $errors[] = 'Failed to create transfer request. Please try again.';
            }
        }
    }
}

$pageTitle = 'Receive Money from China - ' . APP_NAME;
include __DIR__ . '/../../../../includes/header.php';
?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="mb-4">Receive Money from China</h2>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Request Submitted Successfully!</h5>
                    <p><strong>Transfer Token:</strong> <code class="fs-4"><?php echo htmlspecialchars($transferToken); ?></code></p>
                    <p class="mb-2">Share this token with the sender in China. They will need it to complete the payment.</p>
                    <p class="mb-0">
                        <strong>Instructions:</strong>
                        <ol>
                            <li>Share the token above with the sender in China</li>
                            <li>The sender will contact our China partner with the token</li>
                            <li>Once payment is received, money will be credited to your account</li>
                            <li>You will receive email notifications at each step</li>
                        </ol>
                    </p>
                    <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/user/transfers/" class="btn btn-primary me-2">View My Transfers</a>
                        <a href="<?php echo BASE_URL; ?>/public/track-transfer.php?token=<?php echo urlencode($transferToken); ?>" 
                           class="btn btn-outline-primary">Track Transfer</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <!-- Exchange Rate -->
                <div class="alert alert-info">
                    <strong>Current Exchange Rate:</strong> 1 CNY = <?php echo number_format(1 / $exchangeRate, 4); ?> GHS
                    <small class="text-muted ms-2">(Rate valid for 24 hours)</small>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Create Receive Request</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expected Amount (GHS) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" name="amount_ghs" id="amount_ghs" 
                                           class="form-control" min="<?php echo MIN_TRANSFER_AMOUNT; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Or Amount (CNY)</label>
                                    <input type="number" step="0.01" name="amount_cny" id="amount_cny" 
                                           class="form-control">
                                    <small class="form-text text-muted">Enter amount in CNY if known</small>
                                </div>
                            </div>
                            
                            <div class="mb-3" id="cny_display" style="display: none;">
                                <label class="form-label">Amount in CNY</label>
                                <input type="text" id="cny_equivalent" class="form-control" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Sender Name (China) <span class="text-danger">*</span></label>
                                <input type="text" name="sender_name" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Sender Contact (China) <span class="text-danger">*</span></label>
                                <input type="text" name="sender_contact" class="form-control" 
                                       placeholder="Phone, WeChat, or Email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">How would you like to receive? <span class="text-danger">*</span></label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_method" 
                                           id="delivery_wallet" value="wallet" checked>
                                    <label class="form-check-label" for="delivery_wallet">
                                        <i class="fas fa-wallet"></i> Credit to Platform Wallet
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="delivery_method" 
                                           id="delivery_mobile" value="mobile_money">
                                    <label class="form-check-label" for="delivery_mobile">
                                        <i class="fas fa-mobile-alt"></i> Mobile Money (<?php echo htmlspecialchars($user['phone']); ?>)
                                    </label>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <strong>Important:</strong>
                                <ul class="mb-0 mt-2">
                                    <li>After submitting, you will receive a unique token</li>
                                    <li>Share this token with the sender in China</li>
                                    <li>The sender will use this token to make payment</li>
                                    <li>Money will be credited once payment is confirmed</li>
                                </ul>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100">Submit Request</button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
const exchangeRate = <?php echo $exchangeRate; ?>;
const cnyToGHS = 1 / exchangeRate;

document.getElementById('amount_ghs').addEventListener('input', function() {
    const amount = parseFloat(this.value) || 0;
    if (amount > 0) {
        document.getElementById('cny_equivalent').value = 'CNY ' + (amount * exchangeRate).toFixed(2);
        document.getElementById('cny_display').style.display = 'block';
        document.getElementById('amount_cny').value = '';
    } else {
        document.getElementById('cny_display').style.display = 'none';
    }
});

document.getElementById('amount_cny').addEventListener('input', function() {
    const amount = parseFloat(this.value) || 0;
    if (amount > 0) {
        document.getElementById('amount_ghs').value = (amount * cnyToGHS).toFixed(2);
        document.getElementById('cny_equivalent').value = 'CNY ' + amount.toFixed(2);
        document.getElementById('cny_display').style.display = 'block';
    } else {
        document.getElementById('cny_display').style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/../../../../includes/footer.php'; ?>

