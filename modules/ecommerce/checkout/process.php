<?php
/**
 * Process Order (Wallet/COD)
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

if (!isset($_SESSION['checkout_data'])) {
    redirect('/cart.php', 'Invalid checkout session.', 'danger');
}

$checkoutData = $_SESSION['checkout_data'];
$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Get cart items
try {
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.slug, p.stock_quantity,
               pv.variant_type, pv.variant_value, pv.price_adjust
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants pv ON c.variant_id = pv.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Checkout process cart query error: " . $e->getMessage());
    unset($_SESSION['checkout_data']);
    redirect('/cart.php', 'Error loading cart items. Please try again.', 'danger');
}

if (empty($cartItems)) {
    unset($_SESSION['checkout_data']);
    redirect('/cart.php', 'Your cart is empty.', 'warning');
}

// Verify address
$stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
$stmt->execute([$checkoutData['shipping_address_id'], $userId]);
$shippingAddress = $stmt->fetch();

if (!$shippingAddress) {
    unset($_SESSION['checkout_data']);
    redirect('/checkout.php', 'Invalid shipping address.', 'danger');
}

try {
    $conn->beginTransaction();
    
    // Generate order number
    $orderNumber = generateOrderNumber();
    
    // Payment status
    $paymentStatus = 'pending';
    
    // Handle wallet payment
    if ($checkoutData['payment_method'] === 'wallet') {
        $walletBalance = 0;
        if (function_exists('getUserWalletBalance')) {
            $walletBalance = getUserWalletBalance($userId);
        } else {
            // Fallback: query wallet balance directly
            $stmt = $conn->prepare("SELECT balance_ghs FROM user_wallets WHERE user_id = ?");
            $stmt->execute([$userId]);
            $wallet = $stmt->fetch();
            $walletBalance = $wallet ? floatval($wallet['balance_ghs']) : 0;
        }
        
        if ($walletBalance < $checkoutData['total']) {
            throw new Exception('Insufficient wallet balance. Current balance: ' . formatCurrency($walletBalance));
        }
        
        // Ensure wallet exists, create if not
        $stmt = $conn->prepare("SELECT id FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wallet = $stmt->fetch();
        
        if (!$wallet) {
            // Create wallet if it doesn't exist
            $stmt = $conn->prepare("
                INSERT INTO user_wallets (user_id, balance_ghs, updated_at) 
                VALUES (?, 0.00, NOW())
            ");
            $stmt->execute([$userId]);
        }
        
        // Deduct from wallet
        $stmt = $conn->prepare("
            UPDATE user_wallets 
            SET balance_ghs = balance_ghs - ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $stmt->execute([$checkoutData['total'], $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to update wallet balance.');
        }
        
        $paymentStatus = 'success';
    } elseif ($checkoutData['payment_method'] === 'cod') {
        $paymentStatus = 'pending'; // Will be marked success when COD is collected
    }
    
    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, order_number, subtotal, tax, shipping_fee, discount, total,
            status, payment_method, payment_status, shipping_address_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $orderNumber,
        $checkoutData['subtotal'],
        $checkoutData['tax'],
        $checkoutData['shipping_fee'],
        $checkoutData['discount'] ?? 0,
        $checkoutData['total'],
        $checkoutData['payment_method'],
        $paymentStatus,
        $checkoutData['shipping_address_id']
    ]);
    $orderId = $conn->lastInsertId();
    
    // Create order items
    foreach ($cartItems as $item) {
        $itemPrice = $item['price'];
        if ($item['price_adjust']) {
            $itemPrice += floatval($item['price_adjust']);
        }
        
        $variantDetails = null;
        if ($item['variant_type'] && $item['variant_value']) {
            $variantDetails = ucfirst($item['variant_type']) . ': ' . $item['variant_value'];
        }
        
        $stmt = $conn->prepare("
            INSERT INTO order_items (
                order_id, product_id, variant_id, product_name, variant_details,
                quantity, price, total, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $orderId,
            $item['product_id'],
            $item['variant_id'],
            $item['name'],
            $variantDetails,
            $item['quantity'],
            $itemPrice,
            $itemPrice * $item['quantity']
        ]);
        
        // Update stock
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
    
    // Record payment if wallet payment
    if ($checkoutData['payment_method'] === 'wallet') {
        $transactionRef = 'WLT_' . time() . '_' . uniqid();
        $stmt = $conn->prepare("
            INSERT INTO payments (
                user_id, transaction_ref, amount, payment_method, service_type,
                service_id, status, created_at
            ) VALUES (?, ?, ?, 'wallet', 'ecommerce', ?, 'success', NOW())
        ");
        $stmt->execute([$userId, $transactionRef, $checkoutData['total'], $orderId]);
    }
    
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
        
        // Update coupon used count
        $stmt = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
        $stmt->execute([$checkoutData['coupon_id']]);
    }
    
    // Add order tracking entry
    $stmt = $conn->prepare("
        INSERT INTO order_tracking (order_id, status, notes, created_at)
        VALUES (?, 'pending', 'Order placed successfully', NOW())
    ");
    $stmt->execute([$orderId]);
    
    // Clear cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
    $stmt->execute([$userId]);
    
    $conn->commit();
    
    // Clear checkout session
    unset($_SESSION['checkout_data']);
    
    // Send notifications
    if (file_exists(__DIR__ . '/../../../includes/notification-helper.php')) {
        require_once __DIR__ . '/../../../includes/notification-helper.php';
        
        // Notify user
        NotificationHelper::createUserNotification(
            $userId,
            'order',
            'Order Placed Successfully',
            'Your order #' . $orderNumber . ' has been placed successfully. Total: ' . formatCurrency($checkoutData['total']),
            BASE_URL . '/user/orders/view.php?id=' . $orderId
        );
        
        // Notify all admins
        NotificationHelper::notifyAllAdmins(
            'order',
            'New Order Received',
            'New order #' . $orderNumber . ' from ' . ($user['email'] ?? 'Customer') . '. Total: ' . formatCurrency($checkoutData['total']),
            BASE_URL . '/admin/ecommerce/orders/view.php?id=' . $orderId
        );
    }
    
    // Send email notification (async)
    // TODO: Queue email
    
    // Redirect to order confirmation
    redirect('/confirmation.php?order=' . $orderNumber, 
             'Order placed successfully!', 'success');
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Order Processing Error: " . $e->getMessage());
    unset($_SESSION['checkout_data']);
    redirect('/checkout.php', 'Order processing failed: ' . $e->getMessage(), 'danger');
}







