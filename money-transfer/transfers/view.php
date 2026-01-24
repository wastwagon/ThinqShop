<?php
/**
 * View Transfer Details with Tracking - Admin
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

// Handle individual QR code payment confirmation upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_qr_code'])) {
    $qrIndex = intval($_POST['qr_index'] ?? -1);
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Invalid security token.', 'danger');
    }
    
    if ($qrIndex < 0) {
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Invalid QR code index.', 'danger');
    }
    
    try {
        // Get current transfer data
        $stmt = $conn->prepare("SELECT recipient_details FROM money_transfers WHERE id = ?");
        $stmt->execute([$transferId]);
        $transfer = $stmt->fetch();
        
        if (!$transfer) {
            throw new Exception('Transfer not found.');
        }
        
        $recipientDetails = json_decode($transfer['recipient_details'], true) ?? [];
        
        if (!isset($recipientDetails['qr_codes']) || !is_array($recipientDetails['qr_codes']) || !isset($recipientDetails['qr_codes'][$qrIndex])) {
            throw new Exception('QR code not found.');
        }
        
        $conn->beginTransaction();
        
        // Handle payment confirmation QR code upload
        if (!empty($_FILES['payment_confirmation_qr']['name']) && $_FILES['payment_confirmation_qr']['error'] === UPLOAD_ERR_OK) {
            require_once __DIR__ . '/../../../config/constants.php';
            $file = [
                'name' => $_FILES['payment_confirmation_qr']['name'],
                'type' => $_FILES['payment_confirmation_qr']['type'],
                'tmp_name' => $_FILES['payment_confirmation_qr']['tmp_name'],
                'error' => $_FILES['payment_confirmation_qr']['error'],
                'size' => $_FILES['payment_confirmation_qr']['size']
            ];
            
            $result = uploadImage($file, UPLOAD_PATH);
            if ($result['success']) {
                // Delete old payment confirmation file if exists
                if (!empty($recipientDetails['qr_codes'][$qrIndex]['payment_confirmation_qr'])) {
                    $oldFile = UPLOAD_PATH . '/' . $recipientDetails['qr_codes'][$qrIndex]['payment_confirmation_qr'];
                    if (file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
                }
                
                // Update the QR code with payment confirmation
                $recipientDetails['qr_codes'][$qrIndex]['payment_confirmation_qr'] = $result['filename'];
            } else {
                throw new Exception('Failed to upload payment confirmation: ' . ($result['message'] ?? 'Unknown error'));
            }
        }
        
        // Update QR code status if provided
        if (isset($_POST['qr_status']) && !empty($_POST['qr_status'])) {
            $newQrStatus = sanitize($_POST['qr_status']);
            $validStatuses = ['pending', 'payment_received', 'processing', 'completed', 'failed'];
            if (in_array($newQrStatus, $validStatuses)) {
                $recipientDetails['qr_codes'][$qrIndex]['status'] = $newQrStatus;
            }
        }
        
        // Update recipient_details in database
        $stmt = $conn->prepare("UPDATE money_transfers SET recipient_details = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([json_encode($recipientDetails), $transferId]);
        
        // Add tracking entry
        $qrAmount = $recipientDetails['qr_codes'][$qrIndex]['amount'] ?? 0;
        $notes = "Payment confirmation uploaded for QR Code #" . ($qrIndex + 1) . " (Amount: ¥" . number_format($qrAmount, 2) . ")";
        if (isset($_POST['qr_notes']) && !empty($_POST['qr_notes'])) {
            $notes .= " - " . sanitize($_POST['qr_notes']);
        }
        
        $stmt = $conn->prepare("
            INSERT INTO transfer_tracking (transfer_id, status, notes, admin_id, created_at)
            VALUES (?, 'processing', ?, ?, NOW())
        ");
        $stmt->execute([$transferId, $notes, $_SESSION['admin_id']]);
        
        $conn->commit();
        
        if (function_exists('logAdminAction')) {
            logAdminAction($_SESSION['admin_id'], 'update_qr_payment_confirmation', 'money_transfers', $transferId, ['qr_index' => $qrIndex]);
        }
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Payment confirmation uploaded successfully for QR Code #' . ($qrIndex + 1) . '.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update QR Code Payment Confirmation Error: " . $e->getMessage());
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Failed to upload payment confirmation: ' . $e->getMessage(), 'danger');
    }
}

// Handle general transfer status update (without QR code upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Invalid security token.', 'danger');
    }
    
    $validStatuses = ['payment_received', 'processing', 'sent_to_partner', 'completed', 'failed', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/money-transfer/transfers/view.php?id=' . $transferId, 'Invalid status.', 'danger');
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

// Auto-update main transfer status if all QR codes are completed
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
    <h1 class="page-title">Transfer #<?php echo htmlspecialchars($transfer['token']); ?></h1>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Transfers
        </a>
        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers/update-status.php?id=<?php echo $transferId; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Update Status
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Transfer Status Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <h5 class="mb-1">Transfer Token: <code><?php echo htmlspecialchars($transfer['token']); ?></code></h5>
                        <p class="text-muted mb-0">Created on <?php echo date('F d, Y h:i A', strtotime($transfer['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6 text-md-end mt-3 mt-md-0">
                        <span class="badge bg-<?php 
                            echo $transfer['status'] === 'completed' ? 'success' : 
                                ($transfer['status'] === 'failed' ? 'danger' : 'warning'); 
                        ?> fs-6">
                            <?php echo ucfirst(str_replace('_', ' ', $transfer['status'])); ?>
                        </span>
                        <br>
                        <small class="text-muted">Payment: 
                            <span class="badge bg-<?php echo $transfer['payment_status'] === 'success' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($transfer['payment_status']); ?>
                            </span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Transfer Tracking -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Transfer Tracking History</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($trackingHistory)): ?>
                <div class="timeline">
                    <?php foreach ($trackingHistory as $index => $track): ?>
                    <div class="mb-4 position-relative <?php echo $index < count($trackingHistory) - 1 ? 'pb-4 border-start border-primary' : ''; ?>" 
                         style="padding-left: 2rem;">
                        <div class="position-absolute start-0 top-0">
                            <i class="fas fa-circle text-primary" style="font-size: 0.5rem;"></i>
                        </div>
                        <div>
                            <strong><?php echo ucfirst(str_replace('_', ' ', $track['status'])); ?></strong>
                            <small class="text-muted ms-2"><?php echo date('M d, Y h:i A', strtotime($track['created_at'])); ?></small>
                            <?php if ($track['admin_username']): ?>
                                <small class="text-muted">by <?php echo htmlspecialchars($track['admin_username']); ?></small>
                            <?php endif; ?>
                            <?php if ($track['notes']): ?>
                                <p class="mb-0 mt-1 text-muted"><?php echo htmlspecialchars($track['notes']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted mb-0">No tracking updates yet.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User QR Codes (if Alipay/WeChat) -->
        <?php if (isset($recipientDetails['qr_codes']) && is_array($recipientDetails['qr_codes']) && !empty($recipientDetails['qr_codes'])): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">User QR Codes - Payment Confirmation</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Upload payment confirmation QR codes for each transaction separately:</p>
                <div class="row">
                    <?php foreach ($recipientDetails['qr_codes'] as $index => $qr): 
                        $qrIndex = $index + 1;
                        if (!empty($qr['qr_code'])):
                            $qrPath = BASE_URL . '/assets/images/uploads/' . htmlspecialchars($qr['qr_code']);
                            $confirmationPath = !empty($qr['payment_confirmation_qr']) ? BASE_URL . '/assets/images/uploads/' . htmlspecialchars($qr['payment_confirmation_qr']) : '';
                            $qrStatus = $qr['status'] ?? 'pending';
                            $statusColors = [
                                'pending' => 'secondary',
                                'payment_received' => 'info',
                                'processing' => 'warning',
                                'completed' => 'success',
                                'failed' => 'danger'
                            ];
                            $statusColor = $statusColors[$qrStatus] ?? 'secondary';
                    ?>
                    <div class="col-12 mb-4">
                        <div class="card border">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">QR Code #<?php echo $qrIndex; ?></h6>
                                <span class="badge bg-<?php echo $statusColor; ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $qrStatus)); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <!-- User QR Code -->
                                    <div class="col-md-4 mb-3">
                                        <h6 class="small fw-semibold mb-2">User QR Code</h6>
                                        <p class="mb-2">
                                            <strong>Amount:</strong> ¥<?php echo number_format($qr['amount'] ?? 0, 2); ?>
                                        </p>
                                        <img src="<?php echo $qrPath; ?>" alt="QR Code #<?php echo $qrIndex; ?>" 
                                             class="img-thumbnail mb-2" style="max-width: 200px; max-height: 200px; cursor: pointer;"
                                             onclick="window.open('<?php echo $qrPath; ?>', '_blank')">
                                        <div class="d-grid gap-1">
                                            <a href="<?php echo $qrPath; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="<?php echo $qrPath; ?>" download class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                    
                                    <!-- Payment Confirmation QR Code -->
                                    <div class="col-md-4 mb-3">
                                        <h6 class="small fw-semibold mb-2">Payment Confirmation</h6>
                                        <?php if ($confirmationPath): ?>
                                        <img src="<?php echo $confirmationPath; ?>" alt="Payment Confirmation #<?php echo $qrIndex; ?>" 
                                             class="img-thumbnail mb-2" style="max-width: 200px; max-height: 200px; cursor: pointer;"
                                             onclick="window.open('<?php echo $confirmationPath; ?>', '_blank')">
                                        <div class="d-grid gap-1">
                                            <a href="<?php echo $confirmationPath; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="<?php echo $confirmationPath; ?>" download class="btn btn-sm btn-outline-success">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                        <?php else: ?>
                                        <div class="alert alert-warning mb-0 py-3">
                                            <small><i class="fas fa-exclamation-triangle"></i> No payment confirmation uploaded yet</small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Upload Form -->
                                    <div class="col-md-4 mb-3">
                                        <h6 class="small fw-semibold mb-2">Upload Payment Confirmation</h6>
                                        <form method="POST" action="" enctype="multipart/form-data" class="qr-upload-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="update_qr_code" value="1">
                                            <input type="hidden" name="qr_index" value="<?php echo $index; ?>">
                                            
                                            <div class="mb-2">
                                                <label class="form-label small">Status</label>
                                                <select name="qr_status" class="form-select form-select-sm">
                                                    <option value="pending" <?php echo $qrStatus === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                    <option value="payment_received" <?php echo $qrStatus === 'payment_received' ? 'selected' : ''; ?>>Payment Received</option>
                                                    <option value="processing" <?php echo $qrStatus === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                    <option value="completed" <?php echo $qrStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                    <option value="failed" <?php echo $qrStatus === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="form-label small">Payment Confirmation QR Code</label>
                                                <input type="file" name="payment_confirmation_qr" class="form-control form-control-sm" accept="image/*" required>
                                                <small class="form-text text-muted">Upload screenshot of payment confirmation</small>
                                            </div>
                                            
                                            <div class="mb-2">
                                                <label class="form-label small">Notes (Optional)</label>
                                                <textarea name="qr_notes" class="form-control form-control-sm" rows="2" 
                                                          placeholder="Add notes about this payment..."></textarea>
                                            </div>
                                            
                                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                                <i class="fas fa-upload"></i> Upload Confirmation
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php 
                        endif;
                    endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Customer Information -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Customer Information</h5>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong>Name:</strong> <?php echo htmlspecialchars(trim(($transfer['first_name'] ?? '') . ' ' . ($transfer['last_name'] ?? '')) ?: 'N/A'); ?><br>
                    <strong>Email:</strong> <?php echo htmlspecialchars($transfer['email'] ?? 'N/A'); ?><br>
                    <strong>Phone:</strong> <?php echo htmlspecialchars($transfer['phone'] ?? 'N/A'); ?>
                </p>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Transfer Details -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Transfer Details</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Type:</strong><br>
                    <?php echo $transfer['transfer_type'] === 'send_to_china' ? 'Send to China' : 'Receive from China'; ?>
                </div>
                
                <div class="mb-3">
                    <strong>Recipient:</strong><br>
                    <?php echo htmlspecialchars($transfer['recipient_name']); ?><br>
                    <small class="text-muted"><?php echo htmlspecialchars($transfer['recipient_phone']); ?></small>
                </div>
                
                <?php if (!empty($recipientDetails)): ?>
                <div class="mb-3">
                    <strong>Recipient Details:</strong><br>
                    <small class="text-muted">
                        <?php 
                        if (isset($recipientDetails['account_number'])) {
                            echo "Bank: " . htmlspecialchars($recipientDetails['bank_name'] ?? 'N/A') . "<br>";
                            echo "Account: " . htmlspecialchars($recipientDetails['account_number']);
                        } elseif (isset($recipientDetails['qr_codes']) && is_array($recipientDetails['qr_codes'])) {
                            // Display QR codes summary for Alipay/WeChat
                            echo "<strong>User QR Codes:</strong><br>";
                            foreach ($recipientDetails['qr_codes'] as $index => $qr) {
                                $qrIndex = $index + 1;
                                echo "<div class='mb-2 p-2 border rounded'>";
                                echo "<strong>QR Code #{$qrIndex}</strong><br>";
                                echo "Amount: ¥" . number_format($qr['amount'] ?? 0, 2) . "<br>";
                                $qrStatus = $qr['status'] ?? 'pending';
                                echo "Status: <span class='badge bg-secondary'>" . ucfirst(str_replace('_', ' ', $qrStatus)) . "</span>";
                                if (!empty($qr['payment_confirmation_qr'])) {
                                    echo "<br><small class='text-success'><i class='fas fa-check'></i> Confirmation uploaded</small>";
                                }
                                echo "</div>";
                            }
                        } elseif (isset($recipientDetails['alipay_id'])) {
                            echo "Alipay ID: " . htmlspecialchars($recipientDetails['alipay_id']);
                        } elseif (isset($recipientDetails['wechat_id'])) {
                            echo "WeChat ID: " . htmlspecialchars($recipientDetails['wechat_id']);
                        } elseif (isset($recipientDetails['number'])) {
                            echo "Mobile Money: " . htmlspecialchars($recipientDetails['network'] ?? '') . " - " . htmlspecialchars($recipientDetails['number']);
                        } elseif (isset($recipientDetails['collection_location'])) {
                            echo "Collection Location: " . htmlspecialchars(ucfirst(str_replace('_', ' ', $recipientDetails['collection_location'])));
                        }
                        ?>
                    </small>
                </div>
                <?php endif; ?>
                
                <?php if ($transfer['purpose']): ?>
                <div class="mb-3">
                    <strong>Purpose:</strong><br>
                    <small class="text-muted"><?php echo htmlspecialchars($transfer['purpose']); ?></small>
                </div>
                <?php endif; ?>
                
                <?php if ($transfer['admin_notes']): ?>
                <div class="mb-3">
                    <strong>Admin Notes:</strong><br>
                    <small class="text-muted"><?php echo htmlspecialchars($transfer['admin_notes']); ?></small>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Amount Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Amount Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Amount (GHS)</span>
                    <strong><?php echo formatCurrency($transfer['amount_ghs']); ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Amount (CNY)</span>
                    <strong><?php echo number_format($transfer['amount_cny'], 2); ?> CNY</strong>
                </div>
                <?php if ($transfer['transfer_fee'] > 0): ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Transfer Fee</span>
                    <strong><?php echo formatCurrency($transfer['transfer_fee']); ?></strong>
                </div>
                <?php endif; ?>
                <hr>
                <div class="d-flex justify-content-between">
                    <strong>Total Paid</strong>
                    <strong class="text-primary"><?php echo formatCurrency($transfer['total_amount']); ?></strong>
                </div>
                <small class="text-muted">
                    Exchange Rate: 1 GHS = <?php echo number_format($transfer['exchange_rate'], 4); ?> CNY
                </small>
            </div>
        </div>
        
        <!-- Payment Info -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Information</h5>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong>Method:</strong> <?php echo ucfirst(str_replace('_', ' ', $transfer['payment_method'])); ?><br>
                    <strong>Status:</strong> 
                    <span class="badge bg-<?php echo $transfer['payment_status'] === 'success' ? 'success' : 'warning'; ?>">
                        <?php echo ucfirst($transfer['payment_status']); ?>
                    </span>
                </p>
                <?php if ($transfer['paystack_reference']): ?>
                    <p class="mb-0 small text-muted">
                        Reference: <?php echo htmlspecialchars($transfer['paystack_reference']); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Transfer #' . htmlspecialchars($transfer['token']) . ' - Admin - ' . APP_NAME;
include __DIR__ . '/../../../layouts/admin-layout.php';
?>

