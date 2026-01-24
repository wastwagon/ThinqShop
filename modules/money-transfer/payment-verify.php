<?php
/**
 * Verify Transfer Payment
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/paystack.php';
require_once __DIR__ . '/../../includes/functions.php';

$reference = $_GET['reference'] ?? '';
$transferId = intval($_GET['transfer_id'] ?? 0);

if (empty($reference) || $transferId <= 0) {
    redirect('/modules/money-transfer/transfer-form/', 'Invalid payment reference.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Verify payment with Paystack
$response = PaystackConfig::verifyTransaction($reference);

if (!$response || !isset($response['status']) || $response['status'] !== true) {
    redirect('/modules/money-transfer/transfer-form/', 'Payment verification failed.', 'danger');
}

$paymentData = $response['data'];

if ($paymentData['status'] !== 'success') {
    redirect('/modules/money-transfer/transfer-form/', 'Payment was not successful.', 'warning');
}

// Get transfer
$stmt = $conn->prepare("SELECT * FROM money_transfers WHERE id = ? AND user_id = ?");
$stmt->execute([$transferId, $userId]);
$transfer = $stmt->fetch();

if (!$transfer) {
    redirect('/modules/money-transfer/transfer-form/', 'Transfer not found.', 'danger');
}

try {
    $conn->beginTransaction();
    
    // Update transfer status
    $stmt = $conn->prepare("
        UPDATE money_transfers 
        SET payment_status = 'success',
            status = 'processing',
            paystack_reference = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$reference, $transferId]);
    
    // Record payment
    $transactionRef = $reference;
    $stmt = $conn->prepare("
        INSERT INTO payments (
            user_id, transaction_ref, amount, payment_method, service_type,
            service_id, status, paystack_reference, paystack_response, created_at
        ) VALUES (?, ?, ?, ?, 'money_transfer', ?, 'success', ?, ?, NOW())
    ");
    $paystackResponseJson = json_encode($paymentData);
    $stmt->execute([
        $userId,
        $transactionRef,
        $transfer['total_amount'],
        $transfer['payment_method'],
        $transferId,
        $reference,
        $paystackResponseJson
    ]);
    
    // Update tracking
    $stmt = $conn->prepare("
        INSERT INTO transfer_tracking (transfer_id, status, notes, created_at)
        VALUES (?, 'processing', 'Payment confirmed. Transfer processing.', NOW())
    ");
    $stmt->execute([$transferId]);
    
    $conn->commit();
    
    // Send notifications
    if (file_exists(__DIR__ . '/../../includes/notification-helper.php')) {
        require_once __DIR__ . '/../../includes/notification-helper.php';
        
        // Get user info for admin notification
        $user = getCurrentUser();
        $userName = $user['email'] ?? 'User';
        
        // Notify user
        NotificationHelper::createUserNotification(
            $userId,
            'transfer',
            'Payment Confirmed',
            'Payment confirmed for your money transfer. Token: ' . $transfer['token'] . '. Transfer is being processed.',
            BASE_URL . '/user/transfers/view.php?id=' . $transferId
        );
        
        // Notify admins
        NotificationHelper::notifyAllAdmins(
            'transfer',
            'New Money Transfer Payment',
            'New money transfer payment received from ' . $userName . '. Token: ' . $transfer['token'],
            BASE_URL . '/admin/money-transfer/transfers/view.php?id=' . $transferId
        );
    }
    
    // TODO: Send email with token
    
    redirect('/modules/money-transfer/confirmation.php?token=' . $transfer['token'], 
             'Payment successful! Transfer is being processed.', 'success');
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Transfer Payment Verification Error: " . $e->getMessage());
    redirect('/modules/money-transfer/transfer-form/', 
             'Payment processing failed: ' . $e->getMessage(), 'danger');
}

