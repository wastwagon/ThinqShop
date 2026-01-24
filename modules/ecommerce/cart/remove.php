<?php
/**
 * Remove Cart Item
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

if ($cartId <= 0) {
    redirect('/cart.php', 'Invalid cart item.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Verify cart item belongs to user and delete
$stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
$stmt->execute([$cartId, $userId]);

if ($stmt->rowCount() > 0) {
    redirect('/cart.php', 'Item removed from cart.', 'success');
} else {
    redirect('/cart.php', 'Cart item not found.', 'danger');
}

