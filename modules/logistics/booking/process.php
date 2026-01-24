<?php
/**
 * Process Parcel Booking
 * ThinQShopping Platform
 */

// Force no cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Check if data comes from POST or session (redirect from index.php)
$fromSession = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book'])) {
    // Validate CSRF token for POST requests
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/modules/logistics/booking/', 'Invalid security token.', 'danger');
    }
} elseif (isset($_SESSION['booking_data'])) {
    // Data is in session (redirected from index.php)
    $fromSession = true;
} else {
    // No valid request
    redirect('/modules/logistics/booking/', 'Invalid request.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get form data from POST or session
if ($fromSession) {
    $bookingData = $_SESSION['booking_data'];
    $pickupAddressId = intval($bookingData['pickup_address_id'] ?? 0);
    $deliveryAddressId = intval($bookingData['delivery_address_id'] ?? 0);
    $weight = floatval($bookingData['weight'] ?? 0);
    $length = floatval($bookingData['dimensions'] ?? '');
    $width = 0;
    $height = 0;
    // Parse dimensions if they're in format "LxWxH"
    if (!empty($length) && preg_match('/(\d+(?:\.\d+)?)x(\d+(?:\.\d+)?)x(\d+(?:\.\d+)?)/', $length, $matches)) {
        $length = floatval($matches[1]);
        $width = floatval($matches[2]);
        $height = floatval($matches[3]);
        $dimensions = $matches[0];
    } else {
        $dimensions = sanitize($bookingData['dimensions'] ?? '');
    }
    $shippingMethodId = intval($bookingData['shipping_method_id'] ?? 0);
    $paymentMethod = sanitize($bookingData['payment_method'] ?? '');
    $codAmount = floatval($bookingData['cod_amount'] ?? 0);
    $pickupDate = sanitize($bookingData['pickup_date'] ?? '');
    $pickupTimeSlot = sanitize($bookingData['pickup_time_slot'] ?? '');
    $calculationType = sanitize($bookingData['calculation_type'] ?? 'predefined');
    
    // Clear session data after reading
    unset($_SESSION['booking_data']);
} else {
    // Get from POST
    $pickupAddressId = intval($_POST['pickup_address_id'] ?? 0);
    $deliveryAddressId = intval($_POST['delivery_address_id'] ?? 0);
    $weight = floatval($_POST['weight'] ?? 0);
    $length = floatval($_POST['length'] ?? 0);
    $width = floatval($_POST['width'] ?? 0);
    $height = floatval($_POST['height'] ?? 0);
    $dimensions = ($length > 0 && $width > 0 && $height > 0) ? $length . 'x' . $width . 'x' . $height : sanitize($_POST['dimensions'] ?? '');
    $shippingMethodId = intval($_POST['shipping_method_id'] ?? 0);
    $paymentMethod = sanitize($_POST['payment_method'] ?? '');
    $codAmount = floatval($_POST['cod_amount'] ?? 0);
    $pickupDate = sanitize($_POST['pickup_date'] ?? '');
    $pickupTimeSlot = sanitize($_POST['pickup_time_slot'] ?? '');
    $calculationType = sanitize($_POST['calculation_type'] ?? 'predefined');
}

// Calculate volumetric weight (dimensional weight)
// Formula: (Length × Width × Height) / Dimensional Factor
function calculateVolumetricWeight($length, $width, $height, $dimensionalFactor = 5000) {
    if ($length <= 0 || $width <= 0 || $height <= 0) {
        return 0;
    }
    return ($length * $width * $height) / $dimensionalFactor;
}

// Validate
if ($pickupAddressId <= 0 || $deliveryAddressId <= 0) {
    // Try to get from radio buttons if available
    if ($pickupAddressId <= 0 && isset($_POST['pickup_address_radio'])) {
        $pickupAddressId = intval($_POST['pickup_address_radio']);
    }
    if ($deliveryAddressId <= 0 && isset($_POST['delivery_address_radio'])) {
        $deliveryAddressId = intval($_POST['delivery_address_radio']);
    }
    
    if ($pickupAddressId <= 0 || $deliveryAddressId <= 0) {
        redirect('/modules/logistics/booking/', 'Please select addresses.', 'danger');
    }
}

// For predefined calculations, weight defaults to 1 if not provided
if ($weight <= 0) {
    if ($calculationType === 'predefined') {
        $weight = 1; // Default weight for predefined
    } else {
        redirect('/modules/logistics/booking/', 'Invalid weight.', 'danger');
    }
}

if ($shippingMethodId <= 0) {
    redirect('/modules/logistics/booking/', 'Please select a shipping method.', 'danger');
}

// Get addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
$stmt->execute([$pickupAddressId, $userId]);
$pickupAddress = $stmt->fetch();

$stmt = $conn->prepare("SELECT * FROM addresses WHERE id = ? AND user_id = ?");
$stmt->execute([$deliveryAddressId, $userId]);
$deliveryAddress = $stmt->fetch();

if (!$pickupAddress || !$deliveryAddress) {
    redirect('/modules/logistics/booking/', 'Invalid address selected.', 'danger');
}

// Get shipping method
$stmt = $conn->prepare("SELECT * FROM shipping_methods WHERE id = ? AND is_active = 1");
$stmt->execute([$shippingMethodId]);
$shippingMethod = $stmt->fetch();

if (!$shippingMethod) {
    redirect('/modules/logistics/booking/', 'Invalid shipping method selected.', 'danger');
}

// Get shipping settings
$stmt = $conn->query("SELECT setting_key, setting_value FROM shipping_settings");
$settingsData = $stmt->fetchAll();
$shippingSettings = [];
foreach ($settingsData as $setting) {
    $shippingSettings[$setting['setting_key']] = $setting['setting_value'];
}

// Calculate volumetric weight if dimensions provided
$dimensionalFactor = floatval($shippingSettings['dimensional_factor'] ?? 5000);
$volumetricWeight = 0;
if ($length > 0 && $width > 0 && $height > 0) {
    $volumetricWeight = calculateVolumetricWeight($length, $width, $height, $dimensionalFactor);
    // Use the greater of actual weight or volumetric weight (industry standard)
    if ($volumetricWeight > $weight) {
        $weight = $volumetricWeight;
    }
}

// Calculate price based on charged weight
$basePrice = floatval($shippingMethod['base_price']);
$perKgPrice = floatval($shippingMethod['per_kg_price']);
$weightPrice = ($weight - 1) * $perKgPrice; // First kg included in base
if ($weightPrice < 0) $weightPrice = 0;

$basePriceTotal = $basePrice + $weightPrice;

// Apply overseas surcharge if international
$pickupCountry = $pickupAddress['country'] ?? 'Ghana';
$deliveryCountry = $deliveryAddress['country'] ?? 'Ghana';
if ($pickupCountry !== $deliveryCountry) {
    $overseasSurcharge = floatval($shippingSettings['overseas_surcharge'] ?? 15) / 100;
    $basePriceTotal = $basePriceTotal * (1 + $overseasSurcharge);
}

// Add fuel surcharge if enabled
if (!empty($shippingSettings['fuel_surcharge_enabled']) && $shippingSettings['fuel_surcharge_enabled'] == '1') {
    $fuelSurcharge = floatval($shippingSettings['fuel_surcharge_rate'] ?? 3) / 100;
    $basePriceTotal = $basePriceTotal * (1 + $fuelSurcharge);
}

$servicePrice = 0; // Service price is now included in method
$totalPrice = $basePriceTotal;

// Calculate estimated delivery date
$estimatedDays = intval($shippingMethod['max_days']);
$estimatedDelivery = date('Y-m-d', strtotime('+' . $estimatedDays . ' days'));

try {
    $conn->beginTransaction();
    
    // Generate tracking number
    $trackingNumber = generateTrackingNumber('SHIP');
    
    // Create shipment
    $stmt = $conn->prepare("
        INSERT INTO shipments (
            user_id, tracking_number, pickup_address_id, delivery_address_id,
            weight, dimensions, service_type, status, payment_method, payment_status,
            base_price, weight_price, service_price, total_price, cod_amount,
            pickup_date, pickup_time_slot, estimated_delivery, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'booked', ?, 'pending', ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $userId,
        $trackingNumber,
        $pickupAddressId,
        $deliveryAddressId,
        $weight,
        $dimensions,
        'standard', // Service type is now determined by shipping method
        $paymentMethod,
        $basePrice,
        $weightPrice,
        $servicePrice,
        $totalPrice,
        $codAmount,
        $pickupDate ?: null,
        $pickupTimeSlot ?: null,
        $estimatedDelivery
    ]);
    $shipmentId = $conn->lastInsertId();
    
    // Add tracking entry
    $stmt = $conn->prepare("
        INSERT INTO shipment_tracking (shipment_id, status, notes, created_at)
        VALUES (?, 'booked', 'Shipment booked successfully', NOW())
    ");
    $stmt->execute([$shipmentId]);
    
    // Handle wallet payment
    if ($paymentMethod === 'wallet') {
        $walletBalance = getUserWalletBalance($userId);
        
        if ($walletBalance < $totalPrice) {
            $conn->rollBack();
            redirect('/modules/logistics/booking/', 'Insufficient wallet balance. Current balance: ' . formatCurrency($walletBalance), 'danger');
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
        $stmt->execute([$totalPrice, $userId]);
        
        if ($stmt->rowCount() === 0) {
            throw new Exception('Failed to update wallet balance.');
        }
        
        // Update payment status to success
        $stmt = $conn->prepare("UPDATE shipments SET payment_status = 'success', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$shipmentId]);
        
        // Record payment transaction
        $transactionRef = 'SHIP-' . $trackingNumber;
        $stmt = $conn->prepare("
            INSERT INTO payments (user_id, transaction_ref, amount, payment_method, service_type, service_id, status, created_at)
            VALUES (?, ?, ?, 'wallet', 'logistics', ?, 'success', NOW())
        ");
        $stmt->execute([$userId, $transactionRef, $totalPrice, $shipmentId]);
    } elseif ($paymentMethod === 'cod') {
        // COD payment - status remains pending
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
            'Shipment Booked',
            'Your shipment has been booked successfully. Tracking number: ' . $trackingNumber . '. Total: ' . formatCurrency($totalPrice),
            BASE_URL . '/user/shipments/view.php?id=' . $shipmentId
        );
        
        // Notify all admins
        $user = getCurrentUser();
        NotificationHelper::notifyAllAdmins(
            'shipment',
            'New Shipment Booking',
            'New shipment booking from ' . ($user['email'] ?? 'Customer') . '. Tracking: ' . $trackingNumber . '. Total: ' . formatCurrency($totalPrice),
            BASE_URL . '/admin/logistics/shipments/view.php?id=' . $shipmentId
        );
    }
    
    // Redirect based on payment method
    if ($paymentMethod === 'cod' || $paymentMethod === 'wallet') {
        redirect('/modules/logistics/booking/confirmation.php?tracking=' . $trackingNumber, 
                 'Shipment booked successfully!', 'success');
    } else {
        // Store in session and redirect to payment (card/mobile_money)
        $_SESSION['shipment_payment'] = [
            'shipment_id' => $shipmentId,
            'amount' => $totalPrice,
            'tracking_number' => $trackingNumber,
            'payment_method' => $paymentMethod
        ];
        redirect('/modules/logistics/booking/payment.php', '', '');
    }
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Shipment Booking Error: " . $e->getMessage());
    redirect('/modules/logistics/booking/', 'Booking failed: ' . $e->getMessage(), 'danger');
}

