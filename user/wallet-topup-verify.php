<?php
/**
 * Verify Wallet Top-Up Payment
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/paystack.php';
require_once __DIR__ . '/../includes/functions.php';

$reference = $_GET['reference'] ?? '';

if (empty($reference) || !isset($_SESSION['wallet_topup'])) {
    redirect('/user/wallet.php', 'Invalid payment reference.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Verify payment with Paystack
$response = PaystackConfig::verifyTransaction($reference);

if (!$response || !isset($response['status']) || $response['status'] !== true) {
    unset($_SESSION['wallet_topup']);
    redirect('/user/wallet.php', 'Payment verification failed.', 'danger');
}

$paymentData = $response['data'];

if ($paymentData['status'] !== 'success') {
    unset($_SESSION['wallet_topup']);
    redirect('/user/wallet.php', 'Payment was not successful.', 'warning');
}

$topupData = $_SESSION['wallet_topup'];
$paidAmount = $paymentData['amount'] / 100; // Convert from pesewas

try {
    $conn->beginTransaction();
    
    // Credit wallet
    $stmt = $conn->prepare("
        UPDATE user_wallets 
        SET balance_ghs = balance_ghs + ?, updated_at = NOW() 
        WHERE user_id = ?
    ");
    $stmt->execute([$paidAmount, $userId]);
    
    // Record payment
    $stmt = $conn->prepare("
        INSERT INTO payments (
            user_id, transaction_ref, amount, payment_method, service_type,
            service_id, status, paystack_reference, paystack_response, created_at
        ) VALUES (?, ?, ?, 'card', 'wallet_topup', 0, 'success', ?, ?, NOW())
    ");
    $paystackResponseJson = json_encode($paymentData);
    $stmt->execute([
        $userId,
        $reference,
        $paidAmount,
        $reference,
        $paystackResponseJson
    ]);
    
    // Get new balance
    $stmt = $conn->prepare("SELECT balance_ghs FROM user_wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    $newBalance = $wallet ? floatval($wallet['balance_ghs']) : $paidAmount;
    
    $conn->commit();
    
    // Send notification
    if (file_exists(__DIR__ . '/../includes/notification-helper.php')) {
        require_once __DIR__ . '/../includes/notification-helper.php';
        
        NotificationHelper::createUserNotification(
            $userId,
            'payment',
            'Wallet Topped Up',
            'Your wallet has been topped up with ' . formatCurrency($paidAmount) . '. New balance: ' . formatCurrency($newBalance),
            BASE_URL . '/user/wallet.php'
        );
    }
    
    unset($_SESSION['wallet_topup']);
    
    redirect('/user/wallet.php', 'Wallet topped up successfully!', 'success');
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Wallet Top-up Error: " . $e->getMessage());
    unset($_SESSION['wallet_topup']);
    redirect('/user/wallet.php', 'Top-up failed: ' . $e->getMessage(), 'danger');
}

