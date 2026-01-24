<?php
/**
 * Edit Transfer - Admin
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';
require_once __DIR__ . '/../../../config/constants.php';

$transferId = intval($_GET['id'] ?? 0);

if ($transferId <= 0) {
    redirect('/admin/money-transfer/transfers.php', 'Invalid transfer ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/money-transfer/transfers/edit.php?id=' . $transferId, 'Invalid security token.', 'danger');
    }
    
    $validStatuses = ['payment_received', 'processing', 'sent_to_partner', 'completed', 'failed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/money-transfer/transfers/edit.php?id=' . $transferId, 'Invalid status.', 'danger');
    }
    
    try {
        $conn->beginTransaction();
        
        // Get current transfer to access recipient_details
        $stmt = $conn->prepare("SELECT recipient_details FROM money_transfers WHERE id = ?");
        $stmt->execute([$transferId]);
        $currentTransfer = $stmt->fetch();
        $currentRecipientDetails = json_decode($currentTransfer['recipient_details'], true) ?? [];
        
        // Handle individual QR code payment confirmations (for Alipay/WeChat)
        $updatedQRCodes = false;
        if (isset($currentRecipientDetails['qr_codes']) && is_array($currentRecipientDetails['qr_codes'])) {
            // Process each QR code confirmation
            foreach ($currentRecipientDetails['qr_codes'] as $index => $qr) {
                $qrIndex = $index;
                
                // Handle payment confirmation QR code upload for this specific QR code
                if (isset($_FILES['qr_payment_confirmation']) && 
                    isset($_FILES['qr_payment_confirmation']['name'][$qrIndex]) && 
                    !empty($_FILES['qr_payment_confirmation']['name'][$qrIndex]) &&
                    $_FILES['qr_payment_confirmation']['error'][$qrIndex] === UPLOAD_ERR_OK) {
                    
                    $file = [
                        'name' => $_FILES['qr_payment_confirmation']['name'][$qrIndex],
                        'type' => $_FILES['qr_payment_confirmation']['type'][$qrIndex],
                        'tmp_name' => $_FILES['qr_payment_confirmation']['tmp_name'][$qrIndex],
                        'error' => $_FILES['qr_payment_confirmation']['error'][$qrIndex],
                        'size' => $_FILES['qr_payment_confirmation']['size'][$qrIndex]
                    ];
                    
                    $result = uploadImage($file, UPLOAD_PATH);
                    if ($result['success']) {
                        $currentRecipientDetails['qr_codes'][$index]['payment_confirmation_qr'] = $result['filename'];
                        $updatedQRCodes = true;
                    } else {
                        throw new Exception('Failed to upload payment confirmation for QR code #' . ($index + 1) . ': ' . ($result['message'] ?? 'Unknown error'));
                    }
                }
                
                // Update status for this QR code if provided
                if (isset($_POST['qr_status'][$qrIndex])) {
                    $qrStatus = sanitize($_POST['qr_status'][$qrIndex]);
                    $validQRStatuses = ['pending', 'payment_received', 'processing', 'completed', 'failed'];
                    if (in_array($qrStatus, $validQRStatuses)) {
                        $currentRecipientDetails['qr_codes'][$index]['status'] = $qrStatus;
                        $updatedQRCodes = true;
                    }
                }
            }
        }
        
        // Handle general payment confirmation QR code upload (for backward compatibility)
        $proofOfTransfer = null;
        if (!empty($_FILES['proof_of_transfer']['name']) && $_FILES['proof_of_transfer']['error'] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $_FILES['proof_of_transfer']['name'],
                'type' => $_FILES['proof_of_transfer']['type'],
                'tmp_name' => $_FILES['proof_of_transfer']['tmp_name'],
                'error' => $_FILES['proof_of_transfer']['error'],
                'size' => $_FILES['proof_of_transfer']['size']
            ];
            
            $result = uploadImage($file, UPLOAD_PATH);
            if ($result['success']) {
                $proofOfTransfer = $result['filename'];
            } else {
                throw new Exception('Failed to upload payment confirmation: ' . ($result['message'] ?? 'Unknown error'));
            }
        }
        
        // Update transfer status, proof of transfer, and recipient_details
        $updateFields = ['status = ?', 'updated_at = NOW()'];
        $updateValues = [$newStatus];
        
        if ($proofOfTransfer) {
            $updateFields[] = 'proof_of_transfer = ?';
            $updateValues[] = $proofOfTransfer;
        }
        
        if ($updatedQRCodes) {
            $updateFields[] = 'recipient_details = ?';
            $updateValues[] = json_encode($currentRecipientDetails);
            
            // Check if all QR codes are completed - if so, auto-update main status to completed
            if (isset($currentRecipientDetails['qr_codes']) && is_array($currentRecipientDetails['qr_codes']) && !empty($currentRecipientDetails['qr_codes'])) {
                $allCompleted = true;
                $hasQRCodes = false;
                
                foreach ($currentRecipientDetails['qr_codes'] as $qr) {
                    if (!empty($qr['qr_code'])) { // Only count QR codes that have files
                        $hasQRCodes = true;
                        $qrStatus = $qr['status'] ?? 'pending';
                        if ($qrStatus !== 'completed') {
                            $allCompleted = false;
                            break;
                        }
                    }
                }
                
                // If all QR codes are completed and we have at least one QR code, auto-update main status
                if ($hasQRCodes && $allCompleted && $newStatus !== 'failed' && $newStatus !== 'cancelled') {
                    $newStatus = 'completed';
                    // Update the status in the update query
                    $updateFields[0] = 'status = ?';
                    $updateValues[0] = $newStatus;
                }
            }
        }
        
        $updateValues[] = $transferId;
        $sql = "UPDATE money_transfers SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute($updateValues);
        
        // Add tracking entry
        $trackingNotes = $notes;
        if ($updatedQRCodes && isset($currentRecipientDetails['qr_codes']) && is_array($currentRecipientDetails['qr_codes'])) {
            $allCompleted = true;
            $hasQRCodes = false;
            foreach ($currentRecipientDetails['qr_codes'] as $qr) {
                if (!empty($qr['qr_code'])) {
                    $hasQRCodes = true;
                    $qrStatus = $qr['status'] ?? 'pending';
                    if ($qrStatus !== 'completed') {
                        $allCompleted = false;
                        break;
                    }
                }
            }
            if ($hasQRCodes && $allCompleted && $newStatus === 'completed') {
                if (empty($trackingNotes)) {
                    $trackingNotes = 'All QR code transactions completed. Transfer automatically marked as completed.';
                } else {
                    $trackingNotes .= ' All QR code transactions completed. Transfer automatically marked as completed.';
                }
            }
        }
        
        $stmt = $conn->prepare("
            INSERT INTO transfer_tracking (transfer_id, status, notes, admin_id, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$transferId, $newStatus, $trackingNotes, $_SESSION['admin_id']]);
        
        $conn->commit();
        
        if (function_exists('logAdminAction')) {
            logAdminAction($_SESSION['admin_id'], 'update_transfer_status', 'money_transfers', $transferId, [
                'status' => $newStatus, 
                'proof_uploaded' => $proofOfTransfer ? true : false,
                'qr_confirmations_updated' => $updatedQRCodes
            ]);
        }
        
        // Send notification to user
        if (file_exists(__DIR__ . '/../../../includes/notification-helper.php')) {
            require_once __DIR__ . '/../../../includes/notification-helper.php';
            
            $statusMessages = [
                'payment_received' => 'Payment received for your transfer',
                'processing' => 'Your transfer is being processed',
                'sent_to_partner' => 'Your transfer has been sent to partner',
                'completed' => 'Your transfer has been completed',
                'failed' => 'Your transfer has failed',
                'cancelled' => 'Your transfer has been cancelled'
            ];
            
            $statusMessage = $statusMessages[$newStatus] ?? 'Your transfer status has been updated';
            $message = $statusMessage . '. Token: ' . $transfer['token'];
            if (!empty($trackingNotes)) {
                $message .= ' - ' . $trackingNotes;
            }
            
            NotificationHelper::createUserNotification(
                $transfer['user_id'],
                'transfer',
                'Transfer Status Updated',
                $message,
                BASE_URL . '/user/transfers/view.php?id=' . $transferId
            );
        }
        
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Transfer status updated successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update Transfer Status Error: " . $e->getMessage());
        redirect('/admin/money-transfer/transfers/edit.php?id=' . $transferId, 'Failed to update transfer status: ' . $e->getMessage(), 'danger');
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

// Decode recipient details
$recipientDetails = json_decode($transfer['recipient_details'], true) ?? [];

// Auto-update main transfer status if all QR codes are completed (on page load)
if (isset($recipientDetails['qr_codes']) && is_array($recipientDetails['qr_codes']) && !empty($recipientDetails['qr_codes'])) {
    $allQRCodesCompleted = true;
    $hasQRCodes = false;
    
    foreach ($recipientDetails['qr_codes'] as $qr) {
        if (!empty($qr['qr_code'])) { // Only count QR codes that have files
            $hasQRCodes = true;
            $qrStatus = $qr['status'] ?? 'pending';
            if ($qrStatus !== 'completed') {
                $allQRCodesCompleted = false;
                break;
            }
        }
    }
    
    // If all QR codes are completed but main status is not completed, auto-update
    if ($hasQRCodes && $allQRCodesCompleted && $transfer['status'] !== 'completed' && $transfer['status'] !== 'failed' && $transfer['status'] !== 'cancelled') {
        try {
            $conn->beginTransaction();
            
            // Update main transfer status to completed
            $stmt = $conn->prepare("UPDATE money_transfers SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$transferId]);
            
            // Add tracking entry
            $stmt = $conn->prepare("
                INSERT INTO transfer_tracking (transfer_id, status, notes, admin_id, created_at)
                VALUES (?, 'completed', 'All QR code transactions completed. Transfer automatically marked as completed.', ?, NOW())
            ");
            $stmt->execute([$transferId, $_SESSION['admin_id'] ?? null]);
            
            $conn->commit();
            
            // Refresh transfer data
            $stmt = $conn->prepare("
                SELECT mt.*, u.email, u.phone, up.first_name, up.last_name
                FROM money_transfers mt
                LEFT JOIN users u ON mt.user_id = u.id
                LEFT JOIN user_profiles up ON u.id = up.user_id
                WHERE mt.id = ?
            ");
            $stmt->execute([$transferId]);
            $transfer = $stmt->fetch();
            
            // Refresh recipient details
            $recipientDetails = json_decode($transfer['recipient_details'], true) ?? [];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Auto-update transfer status error: " . $e->getMessage());
            // Continue without throwing - don't break the page if auto-update fails
        }
    }
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Edit Transfer #<?php echo htmlspecialchars($transfer['token']); ?></h1>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Transfers
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers/view.php?id=<?php echo $transferId; ?>" class="btn btn-outline-primary">
            <i class="fas fa-eye"></i> View Details
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Update Transfer Status</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Transfer Token</label>
                        <div>
                            <code class="fs-5"><?php echo htmlspecialchars($transfer['token']); ?></code>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Current Status</label>
                        <div>
                            <span class="badge bg-<?php 
                                echo $transfer['status'] === 'completed' ? 'success' : 
                                    ($transfer['status'] === 'failed' ? 'danger' : 'warning'); 
                            ?> fs-6">
                                <?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Update Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-lg" required>
                            <option value="payment_received" <?php echo $transfer['status'] === 'payment_received' ? 'selected' : ''; ?>>Payment Received</option>
                            <option value="processing" <?php echo $transfer['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="sent_to_partner" <?php echo $transfer['status'] === 'sent_to_partner' ? 'selected' : ''; ?>>Sent to Partner</option>
                            <option value="completed" <?php echo $transfer['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="failed" <?php echo $transfer['status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            <option value="cancelled" <?php echo $transfer['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <?php if (isset($recipientDetails['qr_codes']) && is_array($recipientDetails['qr_codes']) && !empty($recipientDetails['qr_codes'])): 
                        // Check if all QR codes are completed
                        $allQRCodesCompleted = true;
                        $hasQRCodes = false;
                        foreach ($recipientDetails['qr_codes'] as $qr) {
                            if (!empty($qr['qr_code'])) {
                                $hasQRCodes = true;
                                $qrStatus = $qr['status'] ?? 'pending';
                                if ($qrStatus !== 'completed') {
                                    $allQRCodesCompleted = false;
                                    break;
                                }
                            }
                        }
                    ?>
                    <!-- Individual QR Code Payment Confirmations -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">QR Code Payment Confirmations</label>
                        <p class="text-muted small mb-3">Upload payment confirmation QR codes and update status for each recipient QR code:</p>
                        <?php if ($hasQRCodes && $allQRCodesCompleted): ?>
                        <div class="alert alert-success mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <strong>All QR codes completed!</strong> The main transfer status will automatically be set to "Completed" when you save.
                        </div>
                        <?php elseif ($hasQRCodes): ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Note:</strong> Once all QR code transactions are marked as "Completed", the main transfer status will automatically be updated to "Completed".
                        </div>
                        <?php endif; ?>
                        
                        <div class="row">
                            <?php foreach ($recipientDetails['qr_codes'] as $index => $qr): 
                                $qrIndex = $index + 1;
                                $qrPath = !empty($qr['qr_code']) ? BASE_URL . '/assets/images/uploads/' . htmlspecialchars($qr['qr_code']) : '';
                                $currentStatus = $qr['status'] ?? 'pending';
                                $confirmationPath = !empty($qr['payment_confirmation_qr']) ? BASE_URL . '/assets/images/uploads/' . htmlspecialchars($qr['payment_confirmation_qr']) : '';
                            ?>
                            <div class="col-md-6 mb-4">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">QR Code #<?php echo $qrIndex; ?></h6>
                                    </div>
                                    <div class="card-body">
                                        <!-- User QR Code Display -->
                                        <div class="mb-3 text-center">
                                            <p class="mb-2">
                                                <strong>Amount:</strong> ¥<?php echo number_format($qr['amount'] ?? 0, 2); ?>
                                            </p>
                                            <?php if ($qrPath): ?>
                                            <img src="<?php echo $qrPath; ?>" alt="User QR Code #<?php echo $qrIndex; ?>" 
                                                 class="img-thumbnail mb-2" style="max-width: 150px; max-height: 150px; cursor: pointer;"
                                                 onclick="window.open('<?php echo $qrPath; ?>', '_blank')">
                                            <div class="d-grid gap-1">
                                                <a href="<?php echo $qrPath; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View User QR
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Payment Confirmation QR Code Upload -->
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Payment Confirmation QR Code</label>
                                            <input type="file" name="qr_payment_confirmation[<?php echo $index; ?>]" class="form-control form-control-sm" accept="image/*">
                                            <?php if ($confirmationPath): ?>
                                            <div class="mt-2">
                                                <small class="text-muted d-block mb-1">Current confirmation:</small>
                                                <a href="<?php echo $confirmationPath; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="<?php echo $confirmationPath; ?>" download class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- QR Code Status -->
                                        <div class="mb-0">
                                            <label class="form-label small fw-semibold">Status</label>
                                            <select name="qr_status[<?php echo $index; ?>]" class="form-select form-select-sm">
                                                <option value="pending" <?php echo $currentStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                <option value="payment_received" <?php echo $currentStatus === 'payment_received' ? 'selected' : ''; ?>>Payment Received</option>
                                                <option value="processing" <?php echo $currentStatus === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                <option value="completed" <?php echo $currentStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                <option value="failed" <?php echo $currentStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- General Payment Confirmation (for non-QR code transfers) -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Payment Confirmation QR Code (Optional)</label>
                        <input type="file" name="proof_of_transfer" class="form-control" accept="image/*">
                        <small class="form-text text-muted">Upload a QR code screenshot as proof of payment to the recipient</small>
                        <?php if ($transfer['proof_of_transfer']): ?>
                            <div class="mt-2">
                                <small class="text-muted">Current: <?php echo htmlspecialchars($transfer['proof_of_transfer']); ?></small>
                                <br>
                                <a href="<?php echo BASE_URL; ?>/assets/images/uploads/<?php echo htmlspecialchars($transfer['proof_of_transfer']); ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-eye"></i> View Current
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="5" 
                                  placeholder="Add notes about this status update..."></textarea>
                        <small class="form-text text-muted">These notes will be added to the transfer tracking history</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Update Transfer Status
                        </button>
                        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers/view.php?id=<?php echo $transferId; ?>" class="btn btn-outline-secondary btn-lg">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Transfer Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Transfer Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Customer:</strong><br>
                    <span class="text-muted">
                        <?php echo htmlspecialchars(trim(($transfer['first_name'] ?? '') . ' ' . ($transfer['last_name'] ?? '')) ?: 'N/A'); ?><br>
                        <?php echo htmlspecialchars($transfer['email'] ?? 'N/A'); ?>
                    </span>
                </div>
                
                <div class="mb-3">
                    <strong>Amount:</strong><br>
                    <span class="text-muted">
                        <?php echo formatCurrency($transfer['amount_ghs']); ?> GHS<br>
                        <?php echo number_format($transfer['amount_cny'], 2); ?> CNY
                    </span>
                </div>
                
                <div class="mb-3">
                    <strong>Payment Status:</strong><br>
                    <span class="badge bg-<?php echo $transfer['payment_status'] === 'success' ? 'success' : 'warning'; ?>">
                        <?php echo ucfirst($transfer['payment_status']); ?>
                    </span>
                </div>
                
                <div class="mb-0">
                    <strong>Recipient Type:</strong><br>
                    <span class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $transfer['recipient_type'])); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers/view.php?id=<?php echo $transferId; ?>" class="btn btn-outline-primary w-100 mb-2">
                    <i class="fas fa-eye me-2"></i>View Full Details
                </a>
                <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers.php" class="btn btn-outline-secondary w-100">
                    <i class="fas fa-list me-2"></i>Back to List
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Edit Transfer #' . htmlspecialchars($transfer['token']) . ' - Admin - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/admin-layout.php';
?>

