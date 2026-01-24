<?php
/**
 * Add to Cart
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    redirect('/shop.php', 'Invalid request.', 'danger');
}

$productId = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);
$variantId = isset($_POST['variant_id']) ? intval($_POST['variant_id']) : null;

// Get variant from form if available
foreach ($_POST as $key => $value) {
    if (strpos($key, 'variant_') === 0) {
        $variantId = intval($value);
        break;
    }
}

if ($productId <= 0 || $quantity <= 0) {
    redirect('/shop.php', 'Invalid product or quantity.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();

// Use session ID for guest users, user_id for logged in users
if (isLoggedIn()) {
    $userId = $_SESSION['user_id'];
    $sessionId = null;
} else {
    // For guest users, use session ID
    $userId = null;
    $sessionId = session_id();
}

// Verify product exists and is active
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND is_active = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('/shop.php', 'Product not found.', 'danger');
}

// Check stock
if ($product['stock_quantity'] < $quantity) {
    redirect('/product-detail.php?slug=' . $product['slug'], 'Insufficient stock available.', 'warning');
}

// If variant selected, check variant stock
if ($variantId) {
    $stmt = $conn->prepare("SELECT * FROM product_variants WHERE id = ? AND product_id = ?");
    $stmt->execute([$variantId, $productId]);
    $variant = $stmt->fetch();
    
    if (!$variant) {
        redirect('/product-detail.php?slug=' . $product['slug'], 'Invalid product variant.', 'danger');
    }
    
    if ($variant['stock_quantity'] < $quantity) {
        redirect('/product-detail.php?slug=' . $product['slug'], 'Insufficient stock for selected variant.', 'warning');
    }
}

// Check if item already in cart
if ($userId) {
    // Logged in user - check by user_id
    $stmt = $conn->prepare("
        SELECT * FROM cart 
        WHERE user_id = ? AND product_id = ? AND variant_id " . ($variantId ? "= ?" : "IS NULL")
    );
    if ($variantId) {
        $stmt->execute([$userId, $productId, $variantId]);
    } else {
        $stmt->execute([$userId, $productId]);
    }
} else {
    // Guest user - check by session_id
    $stmt = $conn->prepare("
        SELECT * FROM cart 
        WHERE session_id = ? AND product_id = ? AND variant_id " . ($variantId ? "= ?" : "IS NULL")
    );
    if ($variantId) {
        $stmt->execute([$sessionId, $productId, $variantId]);
    } else {
        $stmt->execute([$sessionId, $productId]);
    }
}
$existingItem = $stmt->fetch();

if ($existingItem) {
    // Update quantity
    $newQuantity = $existingItem['quantity'] + $quantity;
    
    // Check stock again
    $maxStock = $variantId ? $variant['stock_quantity'] : $product['stock_quantity'];
    if ($newQuantity > $maxStock) {
        $newQuantity = $maxStock;
        $message = 'Updated cart quantity to available stock.';
    } else {
        $message = 'Cart updated successfully!';
    }
    
    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
    $stmt->execute([$newQuantity, $existingItem['id']]);
    
    redirect('/cart.php', $message, 'success');
} else {
    // Add new item
    $stmt = $conn->prepare("
        INSERT INTO cart (user_id, session_id, product_id, variant_id, quantity, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([$userId, $sessionId, $productId, $variantId, $quantity]);
    
    redirect('/cart.php', 'Product added to cart!', 'success');
}
