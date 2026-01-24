<?php
/**
 * Wishlist Toggle API
 * Add or remove product from wishlist
 */

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Check if user is logged in
if (!isLoggedIn()) {
    echo json_encode([
        'success' => false,
        'message' => 'Please login to add items to wishlist'
    ]);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$productId = intval($input['product_id'] ?? 0);

if ($productId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid product ID'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$db = new Database();
$conn = $db->getConnection();

try {
    // Check if product exists
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->execute([$productId]);
    if (!$stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Product not found'
        ]);
        exit;
    }
    
    // Check if already in wishlist
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$userId, $productId]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Remove from wishlist
        $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        
        echo json_encode([
            'success' => true,
            'in_wishlist' => false,
            'message' => 'Removed from wishlist'
        ]);
    } else {
        // Add to wishlist
        $stmt = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$userId, $productId]);
        
        echo json_encode([
            'success' => true,
            'in_wishlist' => true,
            'message' => 'Added to wishlist'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Wishlist Toggle Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating wishlist. Please try again.'
    ]);
}

