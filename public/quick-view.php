<?php
/**
 * Quick View API Endpoint
 * Returns product details for quick view modal
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Product ID required']);
    exit;
}

$productId = intval($_GET['id']);

$db = new Database();
$conn = $db->getConnection();

// Get product details with rating
$stmt = $conn->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug,
    COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
    COALESCE((SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as review_count
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.is_active = 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['error' => 'Product not found']);
    exit;
}

// Get product images
$images = json_decode($product['images'] ?? '[]', true);
if (empty($images)) {
    $images = ['default.jpg']; // imageUrl() will handle the path
}

// Convert image paths to full URLs using imageUrl() function
$imageUrls = [];
foreach ($images as $img) {
    $imageUrls[] = imageUrl($img, 800, 800);
}

// Get product variants
$stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ? ORDER BY variant_type, variant_value");
$stmt->execute([$product['id']]);
$variants = $stmt->fetchAll();

$avgRating = floatval($product['avg_rating'] ?? 0);
$reviewCount = intval($product['review_count'] ?? 0);
$hasDiscount = $product['compare_price'] && $product['compare_price'] > $product['price'];

// Check if product is in user's wishlist
$inWishlist = false;
if (isLoggedIn()) {
    $stmt = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$_SESSION['user_id'], $product['id']]);
    $inWishlist = $stmt->fetch() !== false;
}

echo json_encode([
    'success' => true,
    'product' => [
        'id' => $product['id'],
        'name' => $product['name'],
        'slug' => $product['slug'],
        'short_description' => $product['short_description'],
        'description' => $product['description'],
        'price' => $product['price'],
        'compare_price' => $product['compare_price'],
        'has_discount' => $hasDiscount,
        'discount_percent' => $hasDiscount ? round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100) : 0,
        'stock_quantity' => $product['stock_quantity'],
        'sku' => $product['sku'],
        'category_name' => $product['category_name'],
        'category_slug' => $product['category_slug'],
        'images' => $imageUrls,
        'main_image' => !empty($imageUrls) ? $imageUrls[0] : imageUrl('default.jpg', 800, 800),
        'avg_rating' => $avgRating,
        'review_count' => $reviewCount,
        'variants' => $variants,
        'has_variants' => !empty($variants),
        'in_wishlist' => $inWishlist,
        'url' => BASE_URL . '/product-detail.php?slug=' . $product['slug']
    ]
]);

