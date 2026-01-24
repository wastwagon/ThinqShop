<?php
/**
 * Get Category Preview Products
 * Returns 4 products from a category for navigation preview
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['category_id'])) {
    echo json_encode(['error' => 'Category ID required']);
    exit;
}

$categoryId = intval($_GET['category_id']);

$db = new Database();
$conn = $db->getConnection();

// Get total product count
$countStmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = ? AND is_active = 1");
$countStmt->execute([$categoryId]);
$totalCount = $countStmt->fetch()['total'];

// Get 4 products from the category
$stmt = $conn->prepare("
    SELECT p.id, p.name, p.slug, p.price, p.compare_price, p.images
    FROM products p 
    WHERE p.category_id = ? AND p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 4
");
$stmt->execute([$categoryId]);
$products = $stmt->fetchAll();

$result = [];
foreach ($products as $product) {
    $images = json_decode($product['images'] ?? '[]', true);
    $mainImage = '';
    
    if (!empty($images) && !empty($images[0])) {
        $mainImage = imageUrl($images[0], 300, 300);
    } else {
        // Use placeholder image
        $mainImage = BASE_URL . '/assets/images/placeholder-product.jpg';
        // Try to use a function if it exists
        if (function_exists('getProductImageUrl')) {
            $mainImage = getProductImageUrl($product['name'], 300, 300);
        }
    }
    
    $result[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'slug' => $product['slug'],
        'price' => $product['price'],
        'compare_price' => $product['compare_price'],
        'image' => $mainImage
    ];
}

echo json_encode(['products' => $result, 'total_count' => intval($totalCount)]);

