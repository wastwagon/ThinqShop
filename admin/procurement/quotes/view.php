<?php
/**
 * View Quote Details
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get quote ID
$quoteId = intval($_GET['id'] ?? 0);

if ($quoteId <= 0) {
    redirect('/admin/procurement/requests.php', 'Invalid quote ID.', 'danger');
}

// Get quote details
$stmt = $conn->prepare("
    SELECT pq.*, pr.request_number, pr.user_id, u.email, up.first_name, up.last_name,
           a.username as admin_username
    FROM procurement_quotes pq
    LEFT JOIN procurement_requests pr ON pq.request_id = pr.id
    LEFT JOIN users u ON pr.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN admin_users a ON pq.admin_id = a.id
    WHERE pq.id = ?
");
$stmt->execute([$quoteId]);
$quote = $stmt->fetch();

if (!$quote) {
    redirect('/admin/procurement/requests.php', 'Quote not found.', 'danger');
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Quote Details</h1>
            <p class="text-muted mb-0">Quote #<?php echo $quoteId; ?> - Request #<?php echo htmlspecialchars($quote['request_number']); ?></p>
        </div>
        <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/view.php?id=<?php echo $quote['request_id']; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Request
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quote Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Quote Amount:</strong><br>
                        <h4 class="text-primary mt-2"><?php echo formatCurrency($quote['quote_amount']); ?></h4>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-<?php 
                            echo $quote['status'] === 'accepted' ? 'success' : 
                                ($quote['status'] === 'rejected' ? 'danger' : 'warning'); 
                        ?> fs-6 mt-2">
                            <?php echo ucfirst($quote['status']); ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($quote['quote_details']): ?>
                <div class="mb-3">
                    <strong>Details:</strong><br>
                    <div class="mt-2 p-3 bg-light rounded">
                        <?php echo nl2br(htmlspecialchars($quote['quote_details'])); ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($quote['valid_until']): ?>
                <div class="mb-3">
                    <strong>Valid Until:</strong><br>
                    <p class="mt-2"><?php echo date('F d, Y g:i A', strtotime($quote['valid_until'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Created By:</strong><br>
                        <p class="mt-2"><?php echo htmlspecialchars($quote['admin_username'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Created Date:</strong><br>
                        <p class="mt-2"><?php echo date('F d, Y g:i A', strtotime($quote['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Request Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Request Number:</strong><br>
                        <p class="mt-2"><code><?php echo htmlspecialchars($quote['request_number']); ?></code></p>
                    </div>
                    <div class="col-md-6">
                        <strong>Customer:</strong><br>
                        <p class="mt-2">
                            <?php 
                            $userName = trim(($quote['first_name'] ?? '') . ' ' . ($quote['last_name'] ?? ''));
                            if (empty($userName)) {
                                $userName = explode('@', $quote['email'])[0];
                            }
                            echo htmlspecialchars($userName);
                            ?>
                        </p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/view.php?id=<?php echo $quote['request_id']; ?>" class="btn btn-primary">
                        <i class="fas fa-eye me-2"></i>View Full Request Details
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/view.php?id=<?php echo $quote['request_id']; ?>" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Request
                    </a>
                    <a href="<?php echo BASE_URL; ?>/admin/procurement/requests.php" class="btn btn-outline-secondary">
                        <i class="fas fa-list me-2"></i>All Requests
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

$pageTitle = 'Quote Details - Admin - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/admin-layout.php';



