<?php
/**
 * Process Ship Now Request
 * ThinQShopping Platform
 */

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Check if data is in session
if (!isset($_SESSION['ship_now_data'])) {
    redirect('/modules/logistics/booking/', 'Invalid request.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get form data from session
$shipData = $_SESSION['ship_now_data'];
$forwardingWarehouseId = intval($shipData['forwarding_warehouse_id'] ?? 0);
$destinationWarehouseId = intval($shipData['destination_warehouse_id'] ?? 0);
$shippingMethodType = sanitize($shipData['shipping_method_type'] ?? '');
$shippingRateId = sanitize($shipData['shipping_rate_id'] ?? '');
$trackingNumber = sanitize($shipData['tracking_number'] ?? '');
$weight = floatval($shipData['weight'] ?? 1.0);
$totalPrice = floatval($shipData['total_price'] ?? 0);
$paymentMethod = isset($shipData['payment_method']) ? trim(sanitize($shipData['payment_method'])) : '';

// Log the raw payment method value
error_log("Raw payment_method from session: " . var_export($shipData['payment_method'] ?? 'NOT SET', true));

// Validate payment method - ensure it's a valid enum value
$validPaymentMethods = ['card', 'mobile_money', 'wallet', 'cod', 'bank_transfer'];
if (empty($paymentMethod) || !in_array($paymentMethod, $validPaymentMethods)) {
    // Default to wallet if invalid or empty
    $paymentMethod = 'wallet';
    error_log("Invalid or empty payment_method received: " . var_export($shipData['payment_method'] ?? 'NULL', true) . ". Defaulting to 'wallet'.");
}

$productDeclaration = $shipData['product_declaration'] ?? null;

// Clear session data
unset($_SESSION['ship_now_data']);

// Validate data
if ($forwardingWarehouseId <= 0 || $destinationWarehouseId <= 0) {
    redirect('/modules/logistics/booking/', 'Please select warehouses.', 'danger');
}

if (empty($shippingMethodType) || !in_array($shippingMethodType, ['air', 'sea'])) {
    redirect('/modules/logistics/booking/', 'Invalid shipping method.', 'danger');
}

if (empty($shippingRateId)) {
    redirect('/modules/logistics/booking/', 'Please select a shipping rate.', 'danger');
}

if (empty($trackingNumber)) {
    redirect('/modules/logistics/booking/', 'Please enter a tracking number.', 'danger');
}

// Get warehouses
$stmt = $conn->prepare("SELECT * FROM warehouses WHERE id = ? AND is_active = 1");
$stmt->execute([$forwardingWarehouseId]);
$forwardingWarehouse = $stmt->fetch();

$stmt = $conn->prepare("SELECT * FROM warehouses WHERE id = ? AND is_active = 1");
$stmt->execute([$destinationWarehouseId]);
$destinationWarehouse = $stmt->fetch();

if (!$forwardingWarehouse || !$destinationWarehouse) {
    redirect('/modules/logistics/booking/', 'Invalid warehouse selected.', 'danger');
}

// Get rate details from database
try {
    $stmt = $conn->prepare("SELECT * FROM shipping_rates WHERE method_type = ? AND rate_id = ? AND is_active = 1");
    $stmt->execute([$shippingMethodType, $shippingRateId]);
    $rateDetails = $stmt->fetch();
    
    if (!$rateDetails) {
        redirect('/modules/logistics/booking/', 'Invalid shipping rate selected.', 'danger');
    }
    
    // Use price from session if calculated, otherwise calculate here
    if ($totalPrice <= 0) {
        if ($rateDetails['rate_type'] === 'cbm') {
            // Sea freight - per CBM (assume 1 CBM for now)
            $cbm = 1.0;
            $totalPrice = floatval($rateDetails['rate_value']) * $cbm;
        } else if ($rateDetails['rate_type'] === 'kg') {
            // Air freight - per kg
            $totalPrice = floatval($rateDetails['rate_value']) * $weight;
        } else if ($rateDetails['rate_type'] === 'unit') {
            // Per unit (e.g., phone)
            $quantity = 1; // Default
            $totalPrice = floatval($rateDetails['rate_value']) * $quantity;
        }
    }
    
    $basePrice = $totalPrice;
    $weightPrice = ($rateDetails['rate_type'] === 'kg') ? $totalPrice : 0;
    $dimensions = '';
} catch (PDOException $e) {
    error_log("Error getting rate details: " . $e->getMessage());
    redirect('/modules/logistics/booking/', 'Error loading shipping rate. Please try again.', 'danger');
}

// Calculate estimated delivery date based on duration
$durationDays = 60; // Default for sea
if ($shippingMethodType === 'air') {
    if (strpos($rateDetails['duration'], '3-5') !== false) {
        $durationDays = 5;
    } else {
        $durationDays = 14;
    }
}
$estimatedDelivery = date('Y-m-d', strtotime('+' . $durationDays . ' days'));

try {
    $conn->beginTransaction();
    
    // Use user-entered tracking number as the system tracking number
    // Check if tracking number already exists
    $stmt = $conn->prepare("SELECT id FROM shipments WHERE tracking_number = ?");
    $stmt->execute([$trackingNumber]);
    if ($stmt->fetch()) {
        redirect('/modules/logistics/booking/', 'This tracking number is already in use. Please use a different tracking number.', 'danger');
    }
    
    $systemTrackingNumber = $trackingNumber;
    
    // Create addresses for warehouses if they don't exist
    // We'll use the warehouse addresses as pickup and delivery addresses
    
    // Create or get forwarding warehouse address
    $stmt = $conn->prepare("
        SELECT id FROM addresses 
        WHERE user_id = ? AND street LIKE ? AND city = ? AND country = ?
        LIMIT 1
    ");
    $stmt->execute([
        $userId,
        '%' . substr($forwardingWarehouse['address_english'], 0, 50) . '%',
        $forwardingWarehouse['city'],
        $forwardingWarehouse['country']
    ]);
    $pickupAddress = $stmt->fetch();
    
    if (!$pickupAddress) {
        // Create address entry for forwarding warehouse
        $user = getCurrentUser();
        $stmt = $conn->prepare("
            INSERT INTO addresses (
                user_id, full_name, phone, street, city, region, country, 
                is_default, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $receiverName = $forwardingWarehouse['receiver_name'] . ' (' . ($user['user_identifier'] ?? 'User') . ')';
        $stmt->execute([
            $userId,
            $receiverName,
            $forwardingWarehouse['receiver_phone'],
            $forwardingWarehouse['address_english'],
            $forwardingWarehouse['city'],
            $forwardingWarehouse['district'] ?? '',
            $forwardingWarehouse['country']
        ]);
        $pickupAddressId = $conn->lastInsertId();
    } else {
        $pickupAddressId = $pickupAddress['id'];
    }
    
    // Create or get destination warehouse address
    $stmt = $conn->prepare("
        SELECT id FROM addresses 
        WHERE user_id = ? AND street LIKE ? AND city = ? AND country = ?
        LIMIT 1
    ");
    $stmt->execute([
        $userId,
        '%' . substr($destinationWarehouse['address_english'], 0, 50) . '%',
        $destinationWarehouse['city'],
        $destinationWarehouse['country']
    ]);
    $deliveryAddress = $stmt->fetch();
    
    if (!$deliveryAddress) {
        // Create address entry for destination warehouse
        $stmt = $conn->prepare("
            INSERT INTO addresses (
                user_id, full_name, phone, street, city, region, country, 
                is_default, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 0, NOW())
        ");
        $stmt->execute([
            $userId,
            $destinationWarehouse['receiver_name'],
            $destinationWarehouse['receiver_phone'],
            $destinationWarehouse['address_english'],
            $destinationWarehouse['city'],
            $destinationWarehouse['district'] ?? '',
            $destinationWarehouse['country']
        ]);
        $deliveryAddressId = $conn->lastInsertId();
    } else {
        $deliveryAddressId = $deliveryAddress['id'];
    }
    
    // Create shipment
    $notes = "Forwarding from: " . $forwardingWarehouse['warehouse_name'] . "\n";
    $notes .= "Destination: " . $destinationWarehouse['warehouse_name'] . "\n";
    $notes .= "Supplier Tracking: " . $trackingNumber . "\n";
    $notes .= "Shipping Method: " . strtoupper($shippingMethodType) . "\n";
    $notes .= "Rate: " . $shippingRateId;
    
    $stmt = $conn->prepare("
        INSERT INTO shipments (
            user_id, tracking_number, pickup_address_id, delivery_address_id,
            forwarding_warehouse_id, destination_warehouse_id,
            weight, dimensions, service_type, shipping_method_type, shipping_rate_id,
            status, payment_method, payment_status,
            base_price, weight_price, service_price, cod_amount, total_price,
            estimated_delivery, notes, product_declaration, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'standard', ?, ?, 'booked', ?, 'pending', 
                  ?, ?, 0, 0, ?, ?, ?, ?, NOW())
    ");
    
    // Ensure payment_method is set and valid before executing - final check
    $paymentMethod = trim($paymentMethod);
    if (empty($paymentMethod) || !in_array($paymentMethod, ['card', 'mobile_money', 'wallet', 'cod', 'bank_transfer'])) {
        $paymentMethod = 'wallet';
    }
    
    // Ensure product_declaration is properly formatted
    $productDeclarationJson = null;
    if ($productDeclaration) {
        if (is_string($productDeclaration)) {
            // Already a JSON string, use as is
            $productDeclarationJson = $productDeclaration;
        } else {
            // Array or object, encode it
            $productDeclarationJson = json_encode($productDeclaration, JSON_UNESCAPED_UNICODE);
        }
    }
    
    error_log("Final values before INSERT:");
    error_log("  payment_method: " . $paymentMethod . " (length: " . strlen($paymentMethod) . ")");
    error_log("  payment_method type: " . gettype($paymentMethod));
    error_log("  product_declaration: " . ($productDeclarationJson ? substr($productDeclarationJson, 0, 100) : 'NULL'));
    
    // Execute parameters in order matching VALUES clause:
    // 1-8: user_id, tracking_number, pickup_address_id, delivery_address_id, forwarding_warehouse_id, destination_warehouse_id, weight, dimensions
    // 9: 'standard' (hardcoded)
    // 10-11: shipping_method_type, shipping_rate_id
    // 12: 'booked' (hardcoded)
    // 13: payment_method
    // 14: 'pending' (hardcoded)
    // 15-16: base_price, weight_price
    // 17-18: 0, 0 (hardcoded for service_price, cod_amount)
    // 19-22: total_price, estimated_delivery, notes, product_declaration
    // 23: NOW() (hardcoded)
    $stmt->execute([
        $userId,                    // 1
        $systemTrackingNumber,      // 2
        $pickupAddressId,           // 3
        $deliveryAddressId,         // 4
        $forwardingWarehouseId,     // 5
        $destinationWarehouseId,    // 6
        $weight,                    // 7
        $dimensions,                // 8
        $shippingMethodType,        // 9 (after 'standard')
        $shippingRateId,            // 10
        $paymentMethod,             // 11 (after 'booked') - THIS WAS WRONG POSITION!
        $basePrice,                 // 12 (after 'pending')
        $weightPrice,               // 13
        $totalPrice,                // 14 (after 0, 0)
        $estimatedDelivery,         // 15
        $notes,                     // 16
        $productDeclarationJson     // 17
    ]);
    
    $shipmentId = $conn->lastInsertId();
    
    // Create tracking entry
    $stmt = $conn->prepare("
        INSERT INTO shipment_tracking (
            shipment_id, status, location, notes, created_at
        ) VALUES (?, 'booked', ?, ?, NOW())
    ");
    $stmt->execute([
        $shipmentId,
        $forwardingWarehouse['city'] . ', ' . $forwardingWarehouse['country'],
        'Shipment booked. Waiting for package to arrive at forwarding warehouse.'
    ]);
    
    // Handle payment based on payment method
    if ($paymentMethod === 'wallet') {
        // Check wallet balance
        $stmt = $conn->prepare("SELECT balance_ghs FROM user_wallets WHERE user_id = ?");
        $stmt->execute([$userId]);
        $wallet = $stmt->fetch();
        
        if (!$wallet) {
            // Create wallet if it doesn't exist
            $stmt = $conn->prepare("INSERT INTO user_wallets (user_id, balance_ghs, updated_at) VALUES (?, 0, NOW())");
            $stmt->execute([$userId]);
            $walletBalance = 0;
        } else {
            $walletBalance = floatval($wallet['balance_ghs']);
        }
        
        if ($walletBalance < $totalPrice) {
            $conn->rollBack();
            redirect('/modules/logistics/booking/', 'Insufficient wallet balance. Current balance: $' . number_format($walletBalance, 2), 'danger');
        }
        
        // Deduct from wallet
        $stmt = $conn->prepare("
            UPDATE user_wallets 
            SET balance_ghs = balance_ghs - ?, updated_at = NOW() 
            WHERE user_id = ?
        ");
        $stmt->execute([$totalPrice, $userId]);
        
        // Update payment status to success
        $stmt = $conn->prepare("UPDATE shipments SET payment_status = 'success', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$shipmentId]);
        
        // Record payment transaction
        $transactionRef = 'SHIP-' . $systemTrackingNumber;
        try {
            $stmt = $conn->prepare("
                INSERT INTO payments (user_id, transaction_ref, amount, payment_method, service_type, service_id, status, created_at)
                VALUES (?, ?, ?, 'wallet', 'logistics', ?, 'success', NOW())
            ");
            $stmt->execute([$userId, $transactionRef, $totalPrice, $shipmentId]);
        } catch (PDOException $e) {
            // Payments table might not exist, log but don't fail
            error_log("Payment record error: " . $e->getMessage());
        }
    } elseif ($paymentMethod === 'card' || $paymentMethod === 'mobile_money') {
        // For card/mobile money, payment status remains pending
        // In the future, redirect to payment gateway
        $stmt = $conn->prepare("UPDATE shipments SET payment_status = 'pending', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$shipmentId]);
    }
    
    $conn->commit();
    
    // Send notifications
    if (file_exists(__DIR__ . '/../../../includes/notification-helper.php')) {
        require_once __DIR__ . '/../../../includes/notification-helper.php';
        
        // Notify user
        NotificationHelper::createUserNotification(
            $userId,
            'shipment',
            'Shipment Request Created',
            'Your shipment request has been created successfully. Tracking number: ' . $systemTrackingNumber . '. Total: ' . formatCurrency($totalPrice),
            BASE_URL . '/user/shipments/view.php?id=' . $shipmentId
        );
        
        // Notify all admins
        $user = getCurrentUser();
        NotificationHelper::notifyAllAdmins(
            'shipment',
            'New Shipment Request',
            'New shipment request from ' . ($user['email'] ?? 'Customer') . '. Tracking: ' . $systemTrackingNumber . '. Total: ' . formatCurrency($totalPrice),
            BASE_URL . '/admin/logistics/shipments/view.php?id=' . $shipmentId
        );
    }
    
    // Redirect to confirmation page
    redirect('/modules/logistics/ship-now/confirmation.php?tracking=' . urlencode($systemTrackingNumber), 
             'Shipment request created successfully!', 'success');
    
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log("Ship Now Process Error: " . $e->getMessage());
    error_log("Ship Now Process Error Trace: " . $e->getTraceAsString());
    redirect('/modules/logistics/booking/', 'An error occurred while processing your request: ' . $e->getMessage(), 'danger');
}

