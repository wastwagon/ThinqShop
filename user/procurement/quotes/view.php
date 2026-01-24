<?php
/**
 * View Quote Details (User) - Premium Design
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get quote ID
$quoteId = intval($_GET['id'] ?? 0);

if ($quoteId <= 0) {
    redirect('/user/procurement/', 'Invalid quote ID.', 'danger');
}

// Get quote details
$stmt = $conn->prepare("
    SELECT pq.*, pr.request_number, pr.user_id, pr.status as request_status,
           a.username as admin_username
    FROM procurement_quotes pq
    LEFT JOIN procurement_requests pr ON pq.request_id = pr.id
    LEFT JOIN admin_users a ON pq.admin_id = a.id
    WHERE pq.id = ? AND pr.user_id = ?
");
$stmt->execute([$quoteId, $userId]);
$quote = $stmt->fetch();

if (!$quote) {
    redirect('/user/procurement/', 'Quote not found.', 'danger');
}

// Decode admin files
$adminFiles = [];
if (!empty($quote['admin_files'])) {
    $decoded = json_decode($quote['admin_files'], true);
    if (is_array($decoded)) {
        $adminFiles = $decoded;
    }
}

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-procurement-quote-view.css'
];

ob_start();
?>

<div class="mb-5">
    <a href="<?php echo BASE_URL; ?>/user/procurement/view.php?id=<?php echo $quote['request_id']; ?>" class="btn btn-outline-light text-dark rounded-pill px-4 fw-bold shadow-sm mb-4">
        <i class="fas fa-chevron-left me-2"></i> REQUEST BRIEF
    </a>
    
    <div class="quote-header-premium">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
            <div>
                <span class="text-muted x-small fw-800 text-uppercase letter-spacing-1 mb-1 d-block">VALUATION RECORD</span>
                <h2 class="fw-800 text-dark mb-1">Financial Quotation</h2>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small">For Request #<?php echo htmlspecialchars($quote['request_number']); ?></span>
                    <span class="text-muted small">•</span>
                    <span class="text-muted small"><?php echo date('M d, Y', strtotime($quote['created_at'])); ?></span>
                </div>
            </div>
            <div class="text-md-end">
                <?php 
                $sClass = 'bg-warning-soft text-warning';
                if($quote['status'] === 'accepted') $sClass = 'bg-success-soft text-success';
                elseif($quote['status'] === 'rejected') $sClass = 'bg-danger-soft text-danger';
                ?>
                <span class="status-q-badge <?php echo $sClass; ?>">
                    <?php echo $quote['status']; ?>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="valuation-card-premium">
            <span class="meta-label-premium text-white opacity-50">Grand Total Valuation</span>
            <h1 class="display-4 fw-800 mb-0"><?php echo formatCurrency($quote['quote_amount']); ?></h1>
        </div>

        <div class="card-premium-view p-4 p-md-5">
            <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-1">Financial Breakdown & Terms</h6>
            <div class="p-3 bg-light rounded-4 border-light mb-4">
                <div class="lh-base small text-dark opacity-75">
                    <?php echo nl2br(htmlspecialchars($quote['quote_details'] ?? 'No additional financial terms provided.')); ?>
                </div>
            </div>

            <?php if ($quote['valid_until']): ?>
                <div class="d-flex align-items-center gap-2 mb-4">
                    <i class="fas fa-hourglass-half text-danger x-small"></i>
                    <span class="small fw-bold text-danger">VALUATION EXPIRES: <?php echo date('M d, Y • h:i A', strtotime($quote['valid_until'])); ?></span>
                </div>
            <?php endif; ?>

            <div class="row g-4 pt-4 border-top">
                <div class="col-md-6">
                    <span class="meta-label-premium">Expert Consult</span>
                    <div class="small fw-bold text-dark"><?php echo htmlspecialchars($quote['admin_username'] ?? 'Global Desk'); ?></div>
                </div>
                <div class="col-md-6">
                    <span class="meta-label-premium">Issuance Date</span>
                    <div class="small fw-bold text-dark"><?php echo date('M d, Y', strtotime($quote['created_at'])); ?></div>
                </div>
            </div>
        </div>

        <?php if (!empty($adminFiles)): ?>
        <div class="mb-5">
            <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-1 px-1">Verification Documents</h6>
            <div class="row g-3">
                <?php foreach ($adminFiles as $fileIdx => $adminFile): 
                    $fileUrl = ASSETS_URL . '/images/uploads/' . $adminFile;
                    $isImage = in_array(strtolower(pathinfo($adminFile, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                ?>
                    <div class="col-md-4">
                        <div class="file-card-premium bg-white">
                            <?php if ($isImage): ?>
                                <img src="<?php echo $fileUrl; ?>" class="w-100" style="height: 140px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 140px;">
                                    <i class="fas fa-file-pdf fa-3x text-muted opacity-20"></i>
                                </div>
                            <?php endif; ?>
                            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                                <span class="x-small fw-bold text-muted text-truncate me-2"><?php echo htmlspecialchars($adminFile); ?></span>
                                <div class="d-flex gap-2">
                                    <a href="<?php echo $fileUrl; ?>" target="_blank" class="text-primary"><i class="fas fa-external-link-alt"></i></a>
                                    <a href="<?php echo $fileUrl; ?>" download class="text-success"><i class="fas fa-download"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <!-- Decisions -->
        <?php if ($quote['status'] === 'pending' && $quote['request_status'] === 'quote_provided'): ?>
            <div class="card-premium-view p-4">
                <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-1 text-center">Executive Decisions</h6>
                <div class="d-grid gap-3">
                    <form method="POST" action="<?php echo BASE_URL; ?>/modules/procurement/quote/accept.php">
                        <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                        <input type="hidden" name="request_id" value="<?php echo $quote['request_id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-3 shadow-lg" onclick="return confirm('Approve this valuation?')">
                            ACCEPT QUOTATION
                        </button>
                    </form>
                    <form method="POST" action="<?php echo BASE_URL; ?>/modules/procurement/quote/reject.php">
                        <input type="hidden" name="quote_id" value="<?php echo $quote['id']; ?>">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        <button type="submit" class="btn btn-outline-danger w-100 rounded-pill fw-bold py-3" onclick="return confirm('Reject this valuation?')">
                            REJECT QUOTATION
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="card-premium-view p-4 text-center">
                <h6 class="fw-800 text-dark mb-3 text-uppercase letter-spacing-1">Decision Locked</h6>
                <p class="small text-muted mb-0">This quotation has already been <?php echo $quote['status']; ?> and the record is archived.</p>
            </div>
        <?php endif; ?>

        <!-- Support -->
        <div class="bg-primary text-white p-4 rounded-4 shadow-sm text-center">
            <h6 class="fw-800 mb-2">Pricing Inquiry?</h6>
            <p class="x-small opacity-75 mb-4">Is the valuation above your allocation? Discuss with our agents.</p>
            <a href="<?php echo BASE_URL; ?>/user/tickets/create.php?ref=QUOTE-<?php echo $quote['id']; ?>" class="btn btn-white text-primary w-100 rounded-pill fw-bold">
                OPEN NEGOTIATION
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Quotation Review - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/user-layout.php';
?>
