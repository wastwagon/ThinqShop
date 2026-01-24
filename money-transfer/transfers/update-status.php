<?php
/**
 * Update Transfer Status - Admin
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$transferId = intval($_GET['id'] ?? 0);

if ($transferId <= 0) {
    redirect('/admin/money-transfer/transfers.php', 'Invalid transfer ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/money-transfer/transfers/update-status.php?id=' . $transferId, 'Invalid security token.', 'danger');
    }
    
    $validStatuses = ['payment_received', 'processing', 'sent_to_partner', 'completed', 'failed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/money-transfer/transfers/update-status.php?id=' . $transferId, 'Invalid status.', 'danger');
    }
    
    try {
        $conn->beginTransaction();
        
        // Update transfer status
        $stmt = $conn->prepare("UPDATE money_transfers SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $transferId]);
        
        // Add tracking entry
        $stmt = $conn->prepare("
            INSERT INTO transfer_tracking (transfer_id, status, notes, admin_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$transferId, $newStatus, $notes, $_SESSION['admin_id']]);
        
        $conn->commit();
        
        if (function_exists('logAdminAction')) {
            logAdminAction($_SESSION['admin_id'], 'update_transfer_status', 'money_transfers', $transferId, ['status' => $newStatus]);
        }
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Transfer status updated successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update Transfer Status Error: " . $e->getMessage());
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Failed to update transfer status: ' . $e->getMessage(), 'danger');
    }
}

// Get transfer details
$stmt = $conn->prepare("
    SELECT mt.*, u.email, u.phone, up.first_name, up.last_name
    FROM money_transfers mt
    LEFT JOIN users u ON mt.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE mt.id = ?
");
$stmt->execute([$transferId]);
$transfer = $stmt->fetch();

if (!$transfer) {
    redirect('/admin/money-transfer/transfers.php', 'Transfer not found.', 'danger');
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Update Transfer Status</h1>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers/view.php?id=<?php echo $transferId; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Transfer
        </a>
    </div>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Transfer #<?php echo htmlspecialchars($transfer['token']); ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="payment_received" <?php echo $transfer['status'] === 'payment_received' ? 'selected' : ''; ?>>Payment Received</option>
                            <option value="processing" <?php echo $transfer['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="sent_to_partner" <?php echo $transfer['status'] === 'sent_to_partner' ? 'selected' : ''; ?>>Sent to Partner</option>
                            <option value="completed" <?php echo $transfer['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $transfer['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="cancelled" <?php echo $transfer['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="4" 
                                  placeholder="Add notes about this status update..."></textarea>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers/view.php?id=<?php echo $transferId; ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Update Transfer Status - Admin - ' . APP_NAME;
include __DIR__ . '/../../../layouts/admin-layout.php';
?>

