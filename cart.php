<?php
/**
 * Shopping Cart Page
 * ThinQShopping Platform - Premium Professional Design 2025
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

// Use session ID for guest users, user_id for logged in users
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $sessionId = null;
    $whereClause = "c.user_id = ?";
    $params = [$userId];
} else {
    $userId = null;
    $sessionId = session_id();
    $whereClause = "c.session_id = ?";
    $params = [$sessionId];
}

// Get cart items
try {
    $stmt = $conn->prepare("
        SELECT c.*, p.name, p.price, p.slug, p.stock_quantity, p.images,
               pv.variant_type, pv.variant_value, pv.price_adjust, pv.stock_quantity as variant_stock
        FROM cart c
        LEFT JOIN products p ON c.product_id = p.id
        LEFT JOIN product_variants pv ON c.variant_id = pv.id
        WHERE $whereClause
        ORDER BY c.created_at DESC
    ");
    $stmt->execute($params);
    $cartItems = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Cart query error: " . $e->getMessage());
    $cartItems = [];
}

// Calculate totals
$subtotal = 0;
$itemCount = 0;

foreach ($cartItems as &$item) {
    $itemPrice = $item['price'];
    if (isset($item['price_adjust']) && $item['price_adjust'] !== null) {
        $itemPrice += floatval($item['price_adjust']);
    }
    $itemTotal = $itemPrice * $item['quantity'];
    $item['item_price'] = $itemPrice;
    $item['item_total'] = $itemTotal;
    $subtotal += $itemTotal;
    $itemCount += $item['quantity'];
    
    // Get product image using helper if available, otherwise fallback
    if (function_exists('getProductImage')) {
        $item['image_url'] = getProductImage($item);
    } else {
        $images = json_decode($item['images'] ?? '[]', true);
        $imagePath = !empty($images) ? $images[0] : 'assets/images/placeholder-product.jpg';
        $item['image_url'] = BASE_URL . '/' . $imagePath;
    }
}

// VAT Rate
$vatRate = defined('VAT_RATE') ? VAT_RATE : 0;
$tax = $subtotal * ($vatRate / 100);
$total = $subtotal + $tax;

$pageTitle = 'Shopping Cart - ' . APP_NAME;

// Add page specific CSS
$additionalCSS = '<link rel="stylesheet" href="' . ASSETS_URL . '/css/pages/cart.css">';

include __DIR__ . '/includes/header.php';
?>

<div class="cart-page">
    <div class="cart-container">
        <h1 class="cart-title">Shopping Cart</h1>

        <?php if (empty($cartItems)): ?>
            <div class="cart-empty">
                <div class="cart-empty__icon">
                    <i class="fas fa-shopping-basket"></i>
                </div>
                <h2 class="cart-empty__text">Your cart is feeling a bit empty.</h2>
                <div class="cart-empty__actions">
                    <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn--primary btn--lg">Start Shopping</a>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-layout">
                <!-- Cart Items -->
                <div class="cart-items">
                    <?php foreach ($cartItems as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item__image">
                                <img src="<?php echo $item['image_url']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </div>
                            
                            <div class="cart-item__content">
                                <div class="cart-item__info">
                                    <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo $item['slug']; ?>" class="cart-item__name">
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </a>
                                    <?php if ($item['variant_type'] && $item['variant_value']): ?>
                                        <div class="cart-item__variant text-uppercase">
                                            <?php echo htmlspecialchars($item['variant_type']); ?>: <?php echo htmlspecialchars($item['variant_value']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="cart-item__quantity">
                                    <form method="POST" action="<?php echo BASE_URL; ?>/modules/ecommerce/cart/update.php" class="qty-control">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" name="action" value="decrease" class="qty-btn" aria-label="Decrease quantity">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['variant_stock'] ?? $item['stock_quantity']; ?>" 
                                               class="qty-input" readonly>
                                        <button type="submit" name="action" value="increase" class="qty-btn" aria-label="Increase quantity">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </form>
                                </div>

                                <div class="cart-item__total">
                                    <?php echo formatCurrency($item['item_total']); ?>
                                </div>
                            </div>

                            <div class="cart-item__actions">
                                <form method="POST" action="<?php echo BASE_URL; ?>/modules/ecommerce/cart/remove.php">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <button type="submit" class="btn-remove" onclick="return confirm('Remove this item from your cart?')" aria-label="Remove item">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Summary -->
                <aside class="cart-summary">
                    <h2 class="cart-summary__title">Order Summary</h2>
                    
                    <div class="cart-summary__row">
                        <span>Subtotal (<?php echo $itemCount; ?> items)</span>
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
                        <span class="text-success">Calculated at checkout</span>
                    </div>
                    
                    <div class="cart-summary__row cart-summary__row--total">
                        <span>Total</span>
                        <span class="cart-summary__value--primary"><?php echo formatCurrency($total); ?></span>
                    </div>
                    
                    <div class="cart-summary__actions">
                        <a href="<?php echo BASE_URL; ?>/checkout.php" class="btn btn--primary btn--lg btn--block btn--checkout">
                            Proceed to Checkout
                        </a>
                        <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn--secondary btn--block">
                            Continue Shopping
                        </a>
                    </div>
                </aside>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
