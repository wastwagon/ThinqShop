<?php
/**
 * Paystack Payment Initialization
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../config/paystack.php';
require_once __DIR__ . '/../../../includes/functions.php';

if (!isset($_SESSION['checkout_data'])) {
    redirect('/cart.php', 'Invalid checkout session.', 'danger');
}

$checkoutData = $_SESSION['checkout_data'];
$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get user email
$user = getCurrentUser();
if (!$user) {
    redirect('/login.php', 'Please login to continue.', 'warning');
}

// Get cart items for order creation
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

if (empty($cartItems)) {
    unset($_SESSION['checkout_data']);
    redirect('/cart.php', 'Your cart is empty.', 'warning');
}

// Create order first (pending payment)
try {
    $conn->beginTransaction();
    
    $orderNumber = generateOrderNumber();
    
    // Verify address
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
    $stmt->execute([$checkoutData['shipping_address_id'], $userId]);
    $shippingAddress = $stmt->fetch();
    
    if (!$shippingAddress) {
        throw new Exception('Invalid shipping address.');
    }
    
    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (
            user_id, order_number, subtotal, tax, shipping_fee, discount, total,
            status, payment_method, payment_status, shipping_address_id, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, 'pending', ?, NOW())
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
        $checkoutData['shipping_address_id']
    ]);
    $orderId = $conn->lastInsertId();
    
    // Create order items (but don't update stock yet - will do after payment)
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
    }
    
    // Add order tracking
    $stmt = $conn->prepare("
        INSERT INTO order_tracking (order_id, status, notes, created_at)
        VALUES (?, 'pending', 'Order created, awaiting payment', NOW())
    ");
    $stmt->execute([$orderId]);
    
    $conn->commit();
    
    // Store order ID in checkout data
    $_SESSION['checkout_data']['order_id'] = $orderId;
    $_SESSION['checkout_data']['order_number'] = $orderNumber;
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Order Creation Error: " . $e->getMessage());
    unset($_SESSION['checkout_data']);
    redirect('/checkout.php', 'Failed to create order: ' . $e->getMessage(), 'danger');
}

// Initialize Paystack payment
$amount = $checkoutData['total'] * 100; // Convert to pesewas/kobo
$reference = PaystackConfig::generateReference('ORD');
$callbackUrl = BASE_URL . '/modules/ecommerce/checkout/verify.php?reference=' . $reference;

$metadata = [
    'order_id' => $orderId,
    'order_number' => $orderNumber,
    'user_id' => $userId,
    'payment_method' => $checkoutData['payment_method']
];

$response = PaystackConfig::initializeTransaction(
    $user['email'],
    $checkoutData['total'],
    $reference,
    $callbackUrl,
    $metadata
);

if ($response && isset($response['status']) && $response['status']) {
    // Save reference to session
    $_SESSION['checkout_data']['paystack_reference'] = $reference;
    
    // Save to database
    $stmt = $conn->prepare("UPDATE orders SET paystack_reference = ? WHERE id = ?");
    $stmt->execute([$reference, $orderId]);
    
    // Redirect to Paystack payment page
    header('Location: ' . $response['data']['authorization_url']);
    exit;
} else {
    $errorMessage = $response['message'] ?? 'Failed to initialize payment.';
    error_log("Paystack Error: " . $errorMessage);
    
    // Delete order since payment failed
    $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    
    unset($_SESSION['checkout_data']);
    redirect('/checkout.php', 'Payment initialization failed: ' . $errorMessage, 'danger');
}

