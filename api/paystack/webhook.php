<?php
/**
 * Paystack Webhook Handler
 * ThinQShopping Platform
 * 
 * This endpoint should be configured in Paystack dashboard:
 * https://dashboard.paystack.com/#/settings/developer
 */

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paystack.php';

// Get webhook signature
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

// Get request body
$payload = @file_get_contents('php://input');

if (empty($payload)) {
    http_response_code(400);
    die('Empty payload');
}

// Verify signature
if (!PaystackConfig::verifyWebhookSignature($payload, $signature)) {
    http_response_code(401);
    error_log('Paystack webhook: Invalid signature');
    die('Invalid signature');
}

// Decode payload
$event = json_decode($payload, true);

if (!$event || !isset($event['event'])) {
    http_response_code(400);
    die('Invalid event data');
}

$eventType = $event['event'];
$data = $event['data'] ?? [];

// Log webhook received
error_log("Paystack Webhook Received: " . $eventType);

// Handle different event types
switch ($eventType) {
    case 'charge.success':
        handleChargeSuccess($data);
        break;
    
    case 'charge.failed':
        handleChargeFailed($data);
        break;
    
    case 'transfer.success':
        handleTransferSuccess($data);
        break;
    
    case 'transfer.failed':
        handleTransferFailed($data);
        break;
    
    default:
        // Log unhandled events
        error_log("Unhandled Paystack event: " . $eventType);
}

http_response_code(200);
echo 'OK';

/**
 * Handle successful charge
 */
function handleChargeSuccess($data) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $reference = $data['reference'] ?? '';
    if (empty($reference)) {
        error_log('Paystack webhook: Missing reference');
        return;
    }
    
    // Verify transaction (additional verification)
    $verifyResponse = PaystackConfig::verifyTransaction($reference);
    if (!$verifyResponse || $verifyResponse['data']['status'] !== 'success') {
        error_log('Paystack webhook: Transaction verification failed for ' . $reference);
        return;
    }
    
    // Find order by reference
    $stmt = $conn->prepare("SELECT * FROM orders WHERE paystack_reference = ?");
    $stmt->execute([$reference]);
    $order = $stmt->fetch();
    
    if (!$order) {
        error_log('Paystack webhook: Order not found for reference ' . $reference);
        return;
    }
    
    // Skip if already processed
    if ($order['payment_status'] === 'success') {
        return;
    }
    
    try {
        $conn->beginTransaction();
        
        // Update order payment status
        $stmt = $conn->prepare("
            UPDATE orders 
            SET payment_status = 'success',
                status = CASE WHEN status = 'pending' THEN 'processing' ELSE status END,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$order['id']]);
        
        // Update stock (if not already done)
        $stmt = $conn->prepare("
            SELECT oi.*, p.stock_quantity, pv.stock_quantity as variant_stock
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            LEFT JOIN product_variants pv ON oi.variant_id = pv.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $orderItems = $stmt->fetchAll();
        
        // Only update stock if order status was pending
        if ($order['status'] === 'pending') {
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
        }
        
        // Record payment if not exists
        $stmt = $conn->prepare("SELECT id FROM payments WHERE paystack_reference = ?");
        $stmt->execute([$reference]);
        if (!$stmt->fetch()) {
            $stmt = $conn->prepare("
                INSERT INTO payments (
                    user_id, transaction_ref, amount, payment_method, service_type,
                    service_id, status, paystack_reference, paystack_response, created_at
                ) VALUES (?, ?, ?, ?, 'ecommerce', ?, 'success', ?, ?, NOW())
            ");
            $paystackResponseJson = json_encode($data);
            $stmt->execute([
                $order['user_id'],
                $reference,
                $order['total'],
                $order['payment_method'],
                $order['id'],
                $reference,
                $paystackResponseJson
            ]);
        }
        
        // Update order tracking
        $stmt = $conn->prepare("
            INSERT INTO order_tracking (order_id, status, notes, created_at)
            VALUES (?, 'processing', 'Payment confirmed via webhook', NOW())
        ");
        $stmt->execute([$order['id']]);
        
        $conn->commit();
        
        // TODO: Send email notification
        error_log("Paystack webhook: Order {$order['id']} payment confirmed via webhook");
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log('Paystack webhook error: ' . $e->getMessage());
    }
}

/**
 * Handle failed charge
 */
function handleChargeFailed($data) {
    $db = new Database();
    $conn = $db->getConnection();
    
    $reference = $data['reference'] ?? '';
    if (empty($reference)) {
        return;
    }
    
    $stmt = $conn->prepare("SELECT * FROM orders WHERE paystack_reference = ?");
    $stmt->execute([$reference]);
    $order = $stmt->fetch();
    
    if ($order && $order['payment_status'] !== 'failed') {
        $stmt = $conn->prepare("
            UPDATE orders 
            SET payment_status = 'failed',
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$order['id']]);
        
        error_log("Paystack webhook: Order {$order['id']} payment failed");
    }
}

/**
 * Handle successful transfer (for refunds)
 */
function handleTransferSuccess($data) {
    // Handle transfer success if needed
    error_log('Paystack transfer success: ' . json_encode($data));
}

/**
 * Handle failed transfer
 */
function handleTransferFailed($data) {
    // Handle transfer failure if needed
    error_log('Paystack transfer failed: ' . json_encode($data));
}

