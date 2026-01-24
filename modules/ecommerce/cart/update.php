<?php
/**
 * Update Cart Item
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/cart.php', 'Invalid request.', 'danger');
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    redirect('/cart.php', 'Invalid security token.', 'danger');
}

$cartId = intval($_POST['cart_id'] ?? 0);
$action = $_POST['action'] ?? '';
$newQuantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : null;

if ($cartId <= 0) {
    redirect('/cart.php', 'Invalid cart item.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Verify cart item belongs to user
$stmt = $conn->prepare("
    SELECT c.*, p.stock_quantity, pv.stock_quantity as variant_stock
    FROM cart c
    LEFT JOIN products p ON c.product_id = p.id
    LEFT JOIN product_variants pv ON c.variant_id = pv.id
    WHERE c.id = ? AND c.user_id = ?
");
$stmt->execute([$cartId, $userId]);
$cartItem = $stmt->fetch();

if (!$cartItem) {
    redirect('/cart.php', 'Cart item not found.', 'danger');
}

// Calculate new quantity
$currentQuantity = $cartItem['quantity'];
$maxStock = $cartItem['variant_id'] ? ($cartItem['variant_stock'] ?? 0) : $cartItem['stock_quantity'];

if ($action === 'increase') {
    $newQuantity = $currentQuantity + 1;
} elseif ($action === 'decrease') {
    $newQuantity = max(1, $currentQuantity - 1);
} elseif ($newQuantity !== null) {
    // Direct quantity update
    $newQuantity = max(1, $newQuantity);
} else {
    redirect('/cart.php', 'Invalid action.', 'danger');
}

// Check stock
if ($newQuantity > $maxStock) {
    redirect('/cart.php', 'Insufficient stock available. Maximum: ' . $maxStock, 'warning');
}

// Update cart
$stmt = $conn->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$newQuantity, $cartId]);

redirect('/cart.php', 'Cart updated successfully!', 'success');

