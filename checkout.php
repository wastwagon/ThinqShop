<?php
/**
 * Checkout Page
 * ThinQShopping Platform - Premium Professional Design 2025
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth-check.php';

// Enable error reporting for debugging in development
if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get cart items
try {
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.slug, p.stock_quantity, p.images,
               pv.variant_type, pv.variant_value, pv.price_adjust, pv.stock_quantity as variant_stock
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants pv ON c.variant_id = pv.id
        WHERE c.user_id = ?
    ");
    $stmt->execute([$userId]);
    $cartItems = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Checkout cart query error: " . $e->getMessage());
    $cartItems = [];
}

if (empty($cartItems)) {
    redirect('/cart.php', 'Your cart is empty.', 'warning');
}

// Verify stock availability
foreach ($cartItems as $item) {
    $maxStock = $item['variant_id'] ? ($item['variant_stock'] ?? 0) : $item['stock_quantity'];
    if ($item['quantity'] > $maxStock) {
        redirect('/cart.php', 'Some items in your cart are out of stock. Please update your cart.', 'warning');
    }
}

// Calculate totals
$subtotal = 0;
foreach ($cartItems as &$item) {
    $itemPrice = $item['price'];
    if (isset($item['price_adjust']) && $item['price_adjust'] !== null) {
        $itemPrice += floatval($item['price_adjust']);
    }
    $itemTotal = $itemPrice * $item['quantity'];
    $item['item_price'] = $itemPrice;
    $item['item_total'] = $itemTotal;
    $subtotal += $itemTotal;
    
    // Get image
    if (function_exists('getProductImage')) {
        $item['image_url'] = getProductImage($item);
    } else {
        $images = json_decode($item['images'] ?? '[]', true);
        $item['image_url'] = BASE_URL . '/' . (!empty($images) ? $images[0] : 'assets/images/placeholder-product.jpg');
    }
}

// VAT Rate
$vatRate = defined('VAT_RATE') ? VAT_RATE : 0;
$tax = $subtotal * ($vatRate / 100);
$shippingFee = 0; // Default to 0, will be updated based on address if logic allows
$total = $subtotal + $tax + $shippingFee;

// Get user addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();

// Get default address
$defaultAddressId = 0;
foreach ($addresses as $addr) {
    if ($addr['is_default']) {
        $defaultAddressId = $addr['id'];
        break;
    }
}
if ($defaultAddressId == 0 && !empty($addresses)) {
    $defaultAddressId = $addresses[0]['id'];
}

// Get user wallet balance
$walletBalance = 0;
try {
    $stmt = $conn->prepare("SELECT balance FROM user_wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $wallet = $stmt->fetch();
    $walletBalance = $wallet ? floatval($wallet['balance']) : 0;
} catch (PDOException $e) {
    error_log("Checkout wallet balance error: " . $e->getMessage());
}

$errors = [];
$successMsg = '';

// Process checkout form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $shippingAddressId = intval($_POST['shipping_address_id'] ?? 0);
        $paymentMethod = sanitize($_POST['payment_method'] ?? '');
        $couponCode = sanitize($_POST['coupon_code'] ?? '');
        
        if ($shippingAddressId <= 0) {
            $errors[] = 'Please select a shipping address.';
        }
        
        if (!in_array($paymentMethod, [PAYMENT_METHOD_CARD, PAYMENT_METHOD_MOBILE_MONEY, PAYMENT_METHOD_BANK_TRANSFER, PAYMENT_METHOD_WALLET, PAYMENT_METHOD_COD])) {
            $errors[] = 'Please select a valid payment method.';
        }
        
        if ($paymentMethod === PAYMENT_METHOD_WALLET && $walletBalance < $total) {
            $errors[] = 'Insufficient wallet balance. Please top up or choose another payment method.';
        }
        
        if (empty($errors)) {
            // Store checkout data in session for processing
            $_SESSION['checkout_data'] = [
                'shipping_address_id' => $shippingAddressId,
                'payment_method' => $paymentMethod,
                'coupon_code' => $couponCode,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'shipping_fee' => $shippingFee,
                'total' => $total
            ];
            
            // Redirect to processing
            if ($paymentMethod === PAYMENT_METHOD_WALLET || $paymentMethod === PAYMENT_METHOD_COD) {
                redirect('/modules/ecommerce/checkout/process.php');
            } else {
                redirect('/modules/ecommerce/checkout/payment.php');
            }
        }
    }
}

$pageTitle = 'Checkout - ' . APP_NAME;

// Add page specific CSS
$additionalCSS = '<link rel="stylesheet" href="' . ASSETS_URL . '/css/pages/checkout.css">';

include __DIR__ . '/includes/header.php';
?>

<div class="checkout-page">
    <div class="checkout-container">
        <h1 class="checkout-title">Checkout</h1>

        <?php if (!empty($errors)): ?>
            <div class="checkout-alert checkout-alert--error">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="checkout-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="checkout-layout">
                <div class="checkout-main">
                    <!-- Shipping Address Section -->
                    <section class="checkout-section">
                        <div class="checkout-card">
                            <div class="checkout-card__header">
                                <h2 class="checkout-card__title">
                                    <i class="fas fa-map-marker-alt"></i> Shipping Address
                                </h2>
                            </div>
                            <div class="checkout-card__body">
                                <?php if (empty($addresses)): ?>
                                    <div class="text-center py-4">
                                        <p class="text-muted mb-3">You don't have any shipping addresses yet.</p>
                                        <a href="<?php echo BASE_URL; ?>/user/profile.php?tab=addresses" class="btn btn--secondary btn--sm">
                                            <i class="fas fa-plus"></i> Add New Address
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="address-grid">
                                        <?php foreach ($addresses as $addr): ?>
                                            <label class="address-card <?php echo ($addr['id'] == $defaultAddressId) ? 'selected' : ''; ?>" for="addr-<?php echo $addr['id']; ?>">
                                                <input type="radio" name="shipping_address_id" id="addr-<?php echo $addr['id']; ?>" 
                                                       value="<?php echo $addr['id']; ?>" class="address-card__input"
                                                       <?php echo ($addr['id'] == $defaultAddressId) ? 'checked' : ''; ?>
                                                       onchange="document.querySelectorAll('.address-card').forEach(c => c.classList.remove('selected')); this.parentElement.classList.add('selected');">
                                                
                                                <div class="address-card__info">
                                                    <span class="address-card__name"><?php echo htmlspecialchars($addr['full_name']); ?></span>
                                                    <?php echo htmlspecialchars($addr['street']); ?><br>
                                                    <?php echo htmlspecialchars($addr['city']); ?>, <?php echo htmlspecialchars($addr['region']); ?><br>
                                                    <?php echo htmlspecialchars($addr['phone']); ?>
                                                </div>
                                                <?php if ($addr['is_default']): ?>
                                                    <span class="address-card__tag">Default</span>
                                                <?php endif; ?>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="mt-4">
                                        <a href="<?php echo BASE_URL; ?>/user/profile.php?tab=addresses" class="btn btn--link btn--sm p-0">
                                            Manage Addresses
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </section>

                    <!-- Payment Method Section -->
                    <section class="checkout-section">
                        <div class="checkout-card">
                            <div class="checkout-card__header">
                                <h2 class="checkout-card__title">
                                    <i class="fas fa-credit-card"></i> Payment Method
                                </h2>
                            </div>
                            <div class="checkout-card__body">
                                <div class="payment-methods">
                                    <label class="payment-method selected" for="pay-card">
                                        <input type="radio" name="payment_method" id="pay-card" value="<?php echo PAYMENT_METHOD_CARD; ?>" 
                                               class="payment-method__input" checked
                                               onchange="document.querySelectorAll('.payment-method').forEach(c => c.classList.remove('selected')); this.parentElement.classList.add('selected');">
                                        <div class="payment-method__icon"><i class="fas fa-credit-card"></i></div>
                                        <div class="payment-method__label">Credit or Debit Card</div>
                                        <div class="payment-method__badges">
                                            <i class="fab fa-cc-visa"></i>
                                            <i class="fab fa-cc-mastercard"></i>
                                        </div>
                                    </label>

                                    <label class="payment-method" for="pay-momo">
                                        <input type="radio" name="payment_method" id="pay-momo" value="<?php echo PAYMENT_METHOD_MOBILE_MONEY; ?>" 
                                               class="payment-method__input"
                                               onchange="document.querySelectorAll('.payment-method').forEach(c => c.classList.remove('selected')); this.parentElement.classList.add('selected');">
                                        <div class="payment-method__icon"><i class="fas fa-mobile-alt"></i></div>
                                        <div class="payment-method__label">Mobile Money</div>
                                    </label>

                                    <label class="payment-method <?php echo ($walletBalance < $total) ? 'disabled-method' : ''; ?>" for="pay-wallet">
                                        <input type="radio" name="payment_method" id="pay-wallet" value="<?php echo PAYMENT_METHOD_WALLET; ?>" 
                                               class="payment-method__input" <?php echo ($walletBalance < $total) ? 'disabled' : ''; ?>
                                               onchange="document.querySelectorAll('.payment-method').forEach(c => c.classList.remove('selected')); this.parentElement.classList.add('selected');">
                                        <div class="payment-method__icon"><i class="fas fa-wallet"></i></div>
                                        <div class="payment-method__label">
                                            Wallet Balance
                                            <small class="d-block text-muted">Available: <?php echo formatCurrency($walletBalance); ?></small>
                                        </div>
                                        <?php if ($walletBalance < $total): ?>
                                            <span class="payment-method__badge">Insufficient</span>
                                        <?php endif; ?>
                                    </label>

                                    <label class="payment-method" for="pay-cod">
                                        <input type="radio" name="payment_method" id="pay-cod" value="<?php echo PAYMENT_METHOD_COD; ?>" 
                                               class="payment-method__input"
                                               onchange="document.querySelectorAll('.payment-method').forEach(c => c.classList.remove('selected')); this.parentElement.classList.add('selected');">
                                        <div class="payment-method__icon"><i class="fas fa-money-bill-wave"></i></div>
                                        <div class="payment-method__label">Cash on Delivery</div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Order Summary Sidebar -->
                <aside class="checkout-sidebar">
                    <div class="checkout-summary">
                        <h2 class="checkout-card__title mb-4">Order Summary</h2>
                        
                        <div class="summary-items">
                            <?php foreach ($cartItems as $item): ?>
                                <div class="summary-item">
                                    <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="summary-item__image">
                                    <div class="summary-item__info">
                                        <span class="summary-item__name"><?php echo htmlspecialchars($item['name']); ?></span>
                                        <span class="summary-item__price"><?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['item_price']); ?></span>
                                    </div>
                                    <div class="summary-item__total">
                                        <?php echo formatCurrency($item['item_total']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="cart-summary__row">
                            <span>Subtotal</span>
                            <span><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        
                        <?php if ($vatRate > 0): ?>
                            <div class="cart-summary__row">
                                <span>Tax (<?php echo $vatRate; ?>%)</span>
                                <span><?php echo formatCurrency($tax); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="cart-summary__row">
                            <span>Shipping</span>
                            <span><?php echo $shippingFee > 0 ? formatCurrency($shippingFee) : '<span class="text-success">FREE</span>'; ?></span>
                        </div>

                        <div class="coupon-section">
                            <div class="coupon-form">
                                <input type="text" name="coupon_code" class="coupon-input" placeholder="Coupon Code">
                                <button type="button" class="btn btn--secondary btn--sm">Apply</button>
                            </div>
                        </div>
                        
                        <div class="cart-summary__row cart-summary__row--total">
                            <span>Total</span>
                            <span class="cart-summary__value--primary"><?php echo formatCurrency($total); ?></span>
                        </div>
                        
                        <div class="cart-summary__actions">
                            <button type="submit" class="btn btn--primary btn--lg btn--block btn--checkout">
                                Place Order
                            </button>
                            <a href="<?php echo BASE_URL; ?>/cart.php" class="btn btn--link btn--block mt-2">
                                <i class="fas fa-arrow-left"></i> Back to Cart
                            </a>
                        </div>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</div>



<?php include __DIR__ . '/includes/footer.php'; ?>
