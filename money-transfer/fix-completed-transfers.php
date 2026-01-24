<?php
/**
 * Fix Completed Transfers - Auto-update transfers where all QR codes are completed
 * ThinQShopping Platform
 * 
 * This script checks all transfers and automatically updates the main status to "completed"
 * if all QR codes within that transfer have status "completed"
 */

require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get all transfers with QR codes
$stmt = $conn->query("
    SELECT id, token, status, recipient_details 
    FROM money_transfers 
    WHERE recipient_details LIKE '%qr_codes%'
    AND status NOT IN ('completed', 'failed', 'cancelled')
    ORDER BY id DESC
");

$transfers = $stmt->fetchAll();
$updated = 0;
$skipped = 0;
$errors = [];

foreach ($transfers as $transfer) {
    $recipientDetails = json_decode($transfer['recipient_details'], true) ?? [];
    
    if (!isset($recipientDetails['qr_codes']) || !is_array($recipientDetails['qr_codes']) || empty($recipientDetails['qr_codes'])) {
        $skipped++;
        continue;
    }
    
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
    
    // If all QR codes are completed, update main status
    if ($hasQRCodes && $allQRCodesCompleted) {
        try {
            $conn->beginTransaction();
            
            // Update main transfer status to completed
            $stmt = $conn->prepare("UPDATE money_transfers SET status = 'completed', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$transfer['id']]);
            
            // Add tracking entry
            $stmt = $conn->prepare("
                INSERT INTO transfer_tracking (transfer_id, status, notes, admin_id, created_at)
                VALUES (?, 'completed', 'All QR code transactions completed. Transfer automatically marked as completed.', ?, NOW())
            ");
            $stmt->execute([$transfer['id'], $_SESSION['admin_id'] ?? null]);
            
            $conn->commit();
            $updated++;
            
            echo "✓ Updated transfer {$transfer['token']} (ID: {$transfer['id']}) to completed<br>";
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = "Error updating transfer {$transfer['token']}: " . $e->getMessage();
            error_log("Fix completed transfers error for transfer ID {$transfer['id']}: " . $e->getMessage());
        }
    } else {
        $skipped++;
    }
}

// Display results
$pageTitle = 'Fix Completed Transfers - Admin - ' . APP_NAME;
ob_start();
?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Fix Completed Transfers</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <h6>Results:</h6>
                        <ul class="mb-0">
                            <li><strong>Updated:</strong> <?php echo $updated; ?> transfer(s)</li>
                            <li><strong>Skipped:</strong> <?php echo $skipped; ?> transfer(s)</li>
                            <li><strong>Errors:</strong> <?php echo count($errors); ?></li>
                        </ul>
                    </div>
                    
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <h6>Errors:</h6>
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left"></i> Back to Transfers
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../../../includes/layouts/admin-layout.php';
?>



