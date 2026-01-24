<?php
/**
 * Verify Paystack Payment
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paystack.php';
require_once __DIR__ . '/../../../includes/functions.php';

$reference = $_GET['reference'] ?? '';

if (empty($reference)) {
    redirect('/cart.php', 'Invalid payment reference.', 'danger');
}

if (!isset($_SESSION['checkout_data'])) {
    redirect('/cart.php', 'Invalid checkout session.', 'danger');
}

$checkoutData = $_SESSION['checkout_data'];
$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Verify payment with Paystack
$response = PaystackConfig::verifyTransaction($reference);

if (!$response || !isset($response['status']) || !$response['status']) {
    $errorMessage = $response['message'] ?? 'Payment verification failed.';
    unset($_SESSION['checkout_data']);
    redirect('/checkout.php', 'Payment verification failed: ' . $errorMessage, 'danger');
}

$paymentData = $response['data'];

// Check if payment was successful
if ($paymentData['status'] !== 'success') {
    unset($_SESSION['checkout_data']);
    redirect('/checkout.php', 'Payment was not successful.', 'warning');
}

// Verify amount matches
$paidAmount = $paymentData['amount'] / 100; // Convert from pesewas/kobo
if (abs($paidAmount - $checkoutData['total']) > 0.01) {
    error_log("Payment amount mismatch: Expected " . $checkoutData['total'] . ", Got " . $paidAmount);
    // Continue anyway, but log the issue
}

// Get order
$orderId = $checkoutData['order_id'] ?? null;
if (!$orderId) {
    // Try to find by reference
    $stmt = $conn->prepare("SELECT * FROM orders WHERE paystack_reference = ? AND user_id = ?");
    $stmt->execute([$reference, $userId]);
    $order = $stmt->fetch();
    $orderId = $order ? $order['id'] : null;
}

if (!$orderId) {
    unset($_SESSION['checkout_data']);
    redirect('/cart.php', 'Order not found.', 'danger');
}

try {
    $conn->beginTransaction();
    
    // Update order payment status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET payment_status = 'success', 
            paystack_reference = ?,
            status = 'processing',
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$reference, $orderId]);
    
    // Get order items to update stock
    $stmt = $conn->prepare("
        SELECT oi.*
        FROM order_items oi
        LEFT JOIN product_variants pv ON oi.variant_id = pv.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$orderId]);
    $orderItems = $stmt->fetchAll();
    
    // Update stock
    foreach ($orderItems as $item) {
        if ($item['variant_id']) {
            $stmt = $conn->prepare("
                UPDATE product_variants 
                SET stock_quantity = stock_quantity - ? 
                WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $item['variant_id']]);
        } else {
            $stmt = $conn->prepare("
                UPDATE products 
                SET stock_quantity = stock_quantity - ? 
                WHERE id = ?
            ");
            $stmt->execute([$item['quantity'], $item['product_id']]);
        }
    }
    
    // Record payment
    $transactionRef = $reference;
    $stmt = $conn->prepare("
        INSERT INTO payments (
            user_id, transaction_ref, amount, payment_method, service_type,
            service_id, status, paystack_reference, paystack_response, created_at
        ) VALUES (?, ?, ?, ?, 'ecommerce', ?, 'success', ?, ?, NOW())
    ");
    $paystackResponseJson = json_encode($paymentData);
    $stmt->execute([
        $userId,
        $transactionRef,
        $paidAmount,
        $checkoutData['payment_method'],
        $orderId,
        $reference,
        $paystackResponseJson
    ]);
    
    // Apply coupon if used
    if (!empty($checkoutData['coupon_id'])) {
        $stmt = $conn->prepare("
            INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount, used_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $checkoutData['coupon_id'],
            $userId,
            $orderId,
            $checkoutData['discount']
        ]);
        
        $stmt = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$checkoutData['coupon_id']]);
    }
    
    // Update order tracking
    $stmt = $conn->prepare("
        INSERT INTO order_tracking (order_id, status, notes, created_at)
        VALUES (?, 'processing', 'Payment confirmed, order being processed', NOW())
    ");
    $stmt->execute([$orderId]);
    
    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    $conn->commit();
    
    // Get order number
    $stmt = $conn->prepare("SELECT order_number FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    $orderNumber = $order['order_number'];
    
    // Clear checkout session
    unset($_SESSION['checkout_data']);
    
    // Send email notification (async)
    // TODO: Queue email
    
    // Redirect to confirmation
    redirect('/confirmation.php?order=' . $orderNumber, 
             'Payment successful! Order confirmed.', 'success');
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Payment Processing Error: " . $e->getMessage());
    unset($_SESSION['checkout_data']);
    redirect('/checkout.php', 'Order processing failed: ' . $e->getMessage(), 'danger');
}

