<?php
/**
 * Process Money Transfer Payment
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paystack.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Check if session data exists
if (!isset($_SESSION['transfer_recipient']) || !isset($_SESSION['transfer_details']) || !isset($_SESSION['transfer_payment'])) {
    error_log("Transfer Process: Missing session data. transfer_recipient: " . (isset($_SESSION['transfer_recipient']) ? 'set' : 'not set') . 
              ", transfer_details: " . (isset($_SESSION['transfer_details']) ? 'set' : 'not set') . 
              ", transfer_payment: " . (isset($_SESSION['transfer_payment']) ? 'set' : 'not set'));
    redirect('/modules/money-transfer/transfer-form/', 'Session expired. Please fill the form again.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get user data
$user = getCurrentUser();
if (!$user) {
    redirect('/login.php', 'Please log in to continue.', 'danger');
}

// Get transfer data from session
$recipient = $_SESSION['transfer_recipient'];
$details = $_SESSION['transfer_details'];
$payment = $_SESSION['transfer_payment'];

$paymentMethod = $payment['method'] ?? '';

try {
    $conn->beginTransaction();
    
    // Get user profile for sender information
    $stmt_profile = $conn->prepare("SELECT first_name, last_name FROM user_profiles WHERE user_id = ?");
    $stmt_profile->execute([$userId]);
    $profile = $stmt_profile->fetch();
    
    $senderName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
    if (empty($senderName)) {
        $senderName = $user['email'] ?? 'User';
    }
    // Phone is in the users table, not user_profiles
    $senderPhone = $user['phone'] ?? '';
    
    // Map recipient type - 'in_person' is not in enum, map to 'mobile_money'
    $recipientType = $recipient['type'] ?? '';
    if ($recipientType === 'in_person') {
        $recipientType = 'mobile_money';
    }
    
    // Ensure recipient type is valid
    $validRecipientTypes = ['bank_account', 'alipay', 'wechat_pay', 'mobile_money'];
    if (!in_array($recipientType, $validRecipientTypes)) {
        throw new Exception('Invalid recipient type: ' . $recipientType);
    }
    
    // Handle recipient name and phone - database requires NOT NULL
    $recipientName = $recipient['name'] ?? '';
    $recipientPhone = $recipient['phone'] ?? '';
    
    // For alipay/wechat_pay, use placeholder values if empty
    if (in_array($recipientType, ['alipay', 'wechat_pay'])) {
        if (empty($recipientName)) {
            $recipientName = ucfirst(str_replace('_', ' ', $recipientType)) . ' User';
        }
        if (empty($recipientPhone)) {
            $recipientPhone = 'N/A';
        }
    }
    
    // Validate required fields
    if (empty($recipientName)) {
        throw new Exception('Recipient name is required');
    }
    if (empty($recipientPhone)) {
        throw new Exception('Recipient phone is required');
    }
    
    // Generate transfer token
    $token = generateTransferToken('GH2CHN');
    
    $totalAmount = $details['total_amount'] ?? $details['amount_ghs'] ?? 0;
    
    // Create transfer record
    $stmt = $conn->prepare("
        INSERT INTO money_transfers (
            user_id, transfer_type, token, 
            amount_ghs, amount_cny, exchange_rate, total_amount,
            sender_name, sender_phone, sender_email,
            recipient_name, recipient_phone, recipient_type, recipient_details,
            payment_method, payment_status, status,
            expires_at, created_at
        ) VALUES (
            ?, 'send_to_china', ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, 'pending', 'payment_received',
            DATE_ADD(NOW(), INTERVAL ? DAY), NOW()
        )
    ");
    
    $stmt->execute([
        $userId,
        $token,
        $details['amount_ghs'] ?? 0,
        $details['amount_cny'] ?? 0,
        $details['exchange_rate'] ?? 1.25,
        $totalAmount,
        $senderName,
        $senderPhone,
        $user['email'] ?? null,
        $recipientName,
        $recipientPhone,
        $recipientType,
        json_encode($recipient['details'] ?? []),
        $paymentMethod,
        TOKEN_EXPIRY_DAYS
    ]);
    
    $transferId = $conn->lastInsertId();
    
    // Add initial tracking entry
    $stmt = $conn->prepare("
        INSERT INTO transfer_tracking (transfer_id, status, notes, created_at)
        VALUES (?, 'payment_received', 'Transfer created. Waiting for payment.', NOW())
    ");
    $stmt->execute([$transferId]);
    
    // Handle payment based on method
    if ($paymentMethod === 'wallet') {
        // Check wallet balance
        $walletBalance = getUserWalletBalance($userId);
        $totalAmount = $details['total_amount'] ?? 0;
        
        if ($walletBalance < $totalAmount) {
            $conn->rollBack();
            redirect('/modules/money-transfer/transfer-form/', 'Insufficient wallet balance. Current balance: ' . formatCurrency($walletBalance), 'danger');
        }
        
        // Ensure wallet exists
        $stmt = $conn->prepare("SELECT id FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wallet = $stmt->fetch();
        
        if (!$wallet) {
            // Create wallet if it doesn't exist
            $stmt = $conn->prepare("INSERT INTO user_wallets (user_id, balance_ghs, updated_at) VALUES (?, 0, NOW())");
            $stmt->execute([$userId]);
        }
        
        // Deduct from wallet
        $stmt = $conn->prepare("
            UPDATE user_wallets 
            SET balance_ghs = balance_ghs - ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $stmt->execute([$totalAmount, $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to update wallet balance.');
        }
        
        // Update transfer payment status
        $stmt = $conn->prepare("
            UPDATE money_transfers 
            SET payment_status = 'success', 
                status = 'processing',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$transferId]);
        
        // Record payment transaction
        $transactionRef = 'TRF-' . $token;
        $stmt = $conn->prepare("
            INSERT INTO payments (
                user_id, transaction_ref, amount, payment_method, 
                service_type, service_id, status, created_at
            ) VALUES (?, ?, ?, 'wallet', 'money_transfer', ?, 'success', NOW())
        ");
        $stmt->execute([$userId, $transactionRef, $totalAmount, $transferId]);
        
        // Update tracking
        $stmt = $conn->prepare("
            INSERT INTO transfer_tracking (transfer_id, status, notes, created_at)
            VALUES (?, 'processing', 'Payment confirmed. Transfer processing.', NOW())
        ");
        $stmt->execute([$transferId]);
        
        $conn->commit();
        
        // Send notifications
        if (file_exists(__DIR__ . '/../../../includes/notification-helper.php')) {
            require_once __DIR__ . '/../../../includes/notification-helper.php';
            
            // Notify user
            NotificationHelper::createUserNotification(
                $userId,
                'transfer',
                'Payment Confirmed',
                'Payment confirmed for your money transfer. Token: ' . $token . '. Transfer is being processed.',
                BASE_URL . '/user/transfers/view.php?id=' . $transferId
            );
            
            // Notify admins
            NotificationHelper::notifyAllAdmins(
                'transfer',
                'New Money Transfer',
                'New money transfer request from ' . ($user['email'] ?? 'User') . '. Token: ' . $token,
                BASE_URL . '/admin/money-transfer/transfers/view.php?id=' . $transferId
            );
        }
        
        // Clear session data
        unset($_SESSION['transfer_recipient']);
        unset($_SESSION['transfer_details']);
        unset($_SESSION['transfer_payment']);
        
        // Redirect to confirmation page
        redirect('/modules/money-transfer/confirmation.php?token=' . urlencode($token), '', '');
        
    } elseif (in_array($paymentMethod, ['card', 'mobile_money', 'bank_transfer'])) {
        // Initialize Paystack payment
        $totalAmount = $details['total_amount'] ?? 0;
        $reference = 'TRF-' . time() . '-' . $transferId;
        $callbackUrl = BASE_URL . '/modules/money-transfer/payment-verify.php?reference=' . urlencode($reference) . '&transfer_id=' . $transferId;
        
        $metadata = [
            'transfer_id' => $transferId,
            'token' => $token,
            'user_id' => $userId,
            'payment_method' => $paymentMethod
        ];
        
        $response = PaystackConfig::initializeTransaction(
            $user['email'],
            $totalAmount,
            $reference,
            $callbackUrl,
            $metadata
        );
        
        if ($response && isset($response['status']) && $response['status']) {
            // Save Paystack reference to transfer
            $stmt = $conn->prepare("UPDATE money_transfers SET paystack_reference = ? WHERE id = ?");
            $stmt->execute([$reference, $transferId]);
            
            $conn->commit();
            
            // Clear session data
            unset($_SESSION['transfer_recipient']);
            unset($_SESSION['transfer_details']);
            unset($_SESSION['transfer_payment']);
            
            // Redirect to Paystack payment page
            header('Location: ' . $response['data']['authorization_url']);
            exit;
        } else {
            $conn->rollBack();
            $errorMessage = $response['message'] ?? 'Failed to initialize payment.';
            error_log("Paystack Error: " . $errorMessage);
            
            // Clear session data on error
            unset($_SESSION['transfer_recipient']);
            unset($_SESSION['transfer_details']);
            unset($_SESSION['transfer_payment']);
            
            redirect('/modules/money-transfer/transfer-form/', 'Payment initialization failed: ' . $errorMessage, 'danger');
        }
    } else {
        $conn->rollBack();
        redirect('/modules/money-transfer/transfer-form/', 'Invalid payment method.', 'danger');
    }
    
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $errorMessage = $e->getMessage();
    $errorCode = $e->getCode();
    error_log("Transfer Process PDO Error: " . $errorMessage . " (Code: " . $errorCode . ")");
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear session data on error
    unset($_SESSION['transfer_recipient']);
    unset($_SESSION['transfer_details']);
    unset($_SESSION['transfer_payment']);
    
    // Show detailed error for debugging
    $displayMessage = 'Database Error: ' . $errorMessage;
    if (APP_DEBUG) {
        $displayMessage .= ' (Code: ' . $errorCode . ')';
    }
    redirect('/modules/money-transfer/transfer-form/', $displayMessage, 'danger');
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    $errorMessage = $e->getMessage();
    error_log("Transfer Process Error: " . $errorMessage);
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Clear session data on error
    unset($_SESSION['transfer_recipient']);
    unset($_SESSION['transfer_details']);
    unset($_SESSION['transfer_payment']);
    
    // Show detailed error for debugging
    $displayMessage = 'Error: ' . $errorMessage;
    redirect('/modules/money-transfer/transfer-form/', $displayMessage, 'danger');
}

