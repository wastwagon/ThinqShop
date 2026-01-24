<?php
/**
 * User Wallet Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paystack.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Get wallet balance
$walletBalance = getUserWalletBalance($userId);

// Get user profile
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

// Get wallet transactions
$stmt = $conn->prepare("
    SELECT * FROM payments 
    WHERE user_id = ? AND (
        service_type = 'wallet_topup' OR 
        (service_type IN ('ecommerce', 'money_transfer', 'logistics', 'procurement') AND payment_method = 'wallet')
    )
    ORDER BY created_at DESC 
    LIMIT 20
");
$stmt->execute([$userId]);
$transactions = $stmt->fetchAll();

$errors = [];
$success = false;

// Process top-up
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['topup'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $amount = floatval($_POST['amount'] ?? 0);
        
        if ($amount < 1) {
            $errors[] = 'Minimum top-up amount is ' . formatCurrency(1);
        }
        
        if (empty($errors)) {
            // Redirect to Paystack payment
            $reference = PaystackConfig::generateReference('TOP');
            $callbackUrl = BASE_URL . '/user/wallet-topup-verify.php?reference=' . $reference;
            
            $metadata = [
                'user_id' => $userId,
                'type' => 'wallet_topup',
                'amount' => $amount
            ];
            
            $response = PaystackConfig::initializeTransaction(
                $user['email'],
                $amount,
                $reference,
                $callbackUrl,
                $metadata
            );
            
            if ($response && isset($response['status']) && $response['status']) {
                // Save to session
                $_SESSION['wallet_topup'] = [
                    'amount' => $amount,
                    'reference' => $reference
                ];
                
                header('Location: ' . $response['data']['authorization_url']);
                exit;
            } else {
                $errors[] = 'Failed to initialize payment. Please try again.';
            }
        }
    }
}

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-wallet.css?v=' . time()
];

// Prepare content for layout
ob_start();
?>



<div class="row g-4">
    <div class="col-lg-7">
        <!-- Main Wallet Card -->
        <div class="card wallet-card-premium mb-4 border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <span class="balance-label d-block mb-1">Current Balance</span>
                        <h2 class="balance-amount-lg"><?php echo formatCurrency($walletBalance); ?></h2>
                    </div>
                    <div class="text-end">
                        <i class="fas fa-wallet fa-2x text-primary opacity-25"></i>
                    </div>
                </div>
                

            </div>
        </div>

        <!-- Transaction List -->
        <div class="d-flex justify-content-between align-items-center mb-4 px-1">
            <h6 class="fw-bold mb-0 text-dark small">Transaction History</h6>
            <a href="#" class="text-primary x-small fw-bold text-decoration-none">View All</a>
        </div>
        
        <div class="card action-card-premium border-0 shadow-sm overflow-hidden mb-4">
            <div class="card-body p-0">
                <?php if (empty($transactions)): ?>
                    <div class="text-center py-5">
                        <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle mb-2" style="width: 48px; height: 48px;">
                            <i class="fas fa-history text-muted opacity-50"></i>
                        </div>
                        <p class="text-muted small mb-0">No transactions found.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): 
                        $isCredit = ($tx['service_type'] === 'wallet_topup');
                        $serviceName = str_replace('_', ' ', $tx['service_type']);
                        $description = $isCredit ? 'Wallet Top-up' : ucwords($serviceName);
                    ?>
                    <div class="tx-activity-item d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <div class="tx-icon-wrapper <?php echo $isCredit ? 'tx-type-credit' : 'tx-type-debit'; ?> shadow-sm">
                                <i class="fas <?php echo $isCredit ? 'fa-plus' : 'fa-minus'; ?>"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-dark small"><?php echo $description; ?></h6>
                                <span class="text-muted x-small"><?php echo date('M d, Y • h:i A', strtotime($tx['created_at'])); ?></span>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-800 <?php echo $isCredit ? 'text-success' : 'text-dark'; ?> small">
                                <?php echo ($isCredit ? '+' : '-') . formatCurrency($tx['amount']); ?>
                            </div>
                            <span class="text-muted fw-bold x-small opacity-50"><?php echo strtoupper($tx['status']); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <h6 class="fw-bold text-dark mb-3 px-1 small">Add Funds</h6>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger border-0 rounded-4 px-4 py-3 mb-4 shadow-sm small fw-medium">
                <?php foreach ($errors as $error): echo htmlspecialchars($error); endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <input type="hidden" name="topup" value="1">
            
            <div class="mb-3">
                <label class="form-label-premium">Amount (GHS)</label>
                <div class="input-group">
                    <input type="number" step="0.01" name="amount" id="topupAmount" class="form-control form-control-lg border-1 rounded-3 fw-bold text-dark px-3 mt-1" 
                           placeholder="0.00" min="1" required style="font-size: 1.25rem; height: 50px;">
                </div>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-primary btn-premium px-4 py-2">
                    Proceed to Payment
                </button>
                <small class="text-muted x-small"><i class="fas fa-lock me-1"></i> Paystack Secure</small>
            </div>
        </form>
    </div>
</div>

<script>
function setAmount(val) {
    document.getElementById('topupAmount').value = val;
    // Add visual feedback to pills
    document.querySelectorAll('.quick-amount-pill').forEach(p => {
        p.classList.remove('active');
        if(p.textContent === '+' + val) p.classList.add('active');
    });
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Digital Wallet - ' . APP_NAME;
include __DIR__ . '/../includes/layouts/user-layout.php';
?>

