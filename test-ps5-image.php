<?php
/**
 * Test PS5 Image Display
 * Quick test to verify PS5 image is loading correctly
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get PS5 product
$stmt = $conn->prepare("SELECT id, name, images FROM products WHERE id = 311");
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    die("Product not found");
}

$images = json_decode($product['images'] ?? '[]', true);
$mainImage = (!empty($images) && !empty($images[0])) ? $images[0] : 'default.jpg';
$imageUrl = imageUrl($mainImage, 400, 400);

?>
<!DOCTYPE html>
<html>
<head>
    <title>PS5 Image Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .info { background: #f0f0f0; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .image-container { margin: 20px 0; }
        img { max-width: 400px; border: 2px solid #ddd; padding: 10px; }
    </style>
</head>
<body>
    <h1>PS5 Slim - Digital Version Image Test</h1>
    
    <div class="info">
        <strong>Product ID:</strong> <?php echo $product['id']; ?><br>
        <strong>Product Name:</strong> <?php echo htmlspecialchars($product['name']); ?><br>
        <strong>Images JSON:</strong> <?php echo htmlspecialchars($product['images']); ?><br>
        <strong>Main Image Variable:</strong> <?php echo htmlspecialchars($mainImage); ?><br>
        <strong>imageUrl() Output:</strong> <?php echo htmlspecialchars($imageUrl); ?><br>
        <strong>File Exists:</strong> <?php echo file_exists(BASE_PATH . '/assets/images/products/' . $mainImage) ? 'YES ✓' : 'NO ✗'; ?>
    </div>
    
    <div class="image-container">
        <h2>Image Display:</h2>
        <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>"
             onerror="this.style.border='3px solid red'; this.alt='IMAGE FAILED TO LOAD';">
        <p><small>If image doesn't load, border will turn red</small></p>
    </div>
    
    <div class="info">
        <strong>Direct URL Test:</strong><br>
        <a href="<?php echo htmlspecialchars($imageUrl); ?>" target="_blank">Click to open image directly</a>
    </div>
</body>
</html>

