<?php
/**
 * Set Product Discounts Script
 * Sets 5-15% discounts for majority of products
 */

require_once __DIR__ . '/../config/database.php';

$db = new Database();
$conn = $db->getConnection();

// Get all active products
$stmt = $conn->query("SELECT id, price FROM products WHERE is_active = 1");
$products = $stmt->fetchAll();

$updated = 0;
$skipped = 0;

foreach ($products as $product) {
    // Random discount between 5-15%
    $discountPercent = rand(5, 15);
    
    // Set compare_price as original price (current price)
    // Calculate new discounted price
    $originalPrice = $product['price'];
    $discountedPrice = $originalPrice * (1 - ($discountPercent / 100));
    
    // Update product: compare_price = original, price = discounted
    $updateStmt = $conn->prepare("UPDATE products SET compare_price = ?, price = ? WHERE id = ?");
    $updateStmt->execute([
        number_format($originalPrice, 2, '.', ''),
        number_format($discountedPrice, 2, '.', ''),
        $product['id']
    ]);
    
    $updated++;
}

echo "✅ Updated {$updated} products with discounts between 5-15%\n";
echo "Discount applied: Original price set as compare_price, current price is discounted price\n";

