<?php
/**
 * Create product_images table migration
 * Run this script once to create the missing product_images table
 * Access via: http://localhost/ThinQShopping/create-product-images-table.php
 */

require_once __DIR__ . '/config/database.php';

// Simple HTML output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Create Product Images Table</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Create Product Images Table Migration</h1>
        
<?php
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Create product_images table
    $sql = "CREATE TABLE IF NOT EXISTS `product_images` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `product_id` int(11) NOT NULL,
        `image_url` varchar(500) NOT NULL,
        `sort_order` int(11) DEFAULT 0,
        `is_primary` tinyint(1) DEFAULT 0,
        `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `product_id` (`product_id`),
        KEY `sort_order` (`sort_order`),
        CONSTRAINT `product_images_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    
    echo '<div class="success">✅ Successfully created product_images table!</div>';
    
    // Optionally migrate existing images from products.images JSON field
    echo '<div class="info">🔄 Checking for existing product images to migrate...</div>';
    
    $stmt = $conn->query("SELECT id, images FROM products WHERE images IS NOT NULL AND images != '' AND images != '[]'");
    $products = $stmt->fetchAll();
    
    $migrated = 0;
    foreach ($products as $product) {
        $images = json_decode($product['images'], true);
        if (is_array($images) && !empty($images)) {
            // Check if images already migrated
            $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ?");
            $checkStmt->execute([$product['id']]);
            $exists = $checkStmt->fetch()['count'];
            
            if ($exists == 0) {
                $sortOrder = 0;
                foreach ($images as $image) {
                    if (!empty($image)) {
                        $insertStmt = $conn->prepare("
                            INSERT INTO product_images (product_id, image_url, sort_order, is_primary, created_at)
                            VALUES (?, ?, ?, ?, NOW())
                        ");
                        $isPrimary = ($sortOrder === 0) ? 1 : 0;
                        $insertStmt->execute([$product['id'], $image, $sortOrder, $isPrimary]);
                        $sortOrder++;
                    }
                }
                $migrated++;
            }
        }
    }
    
    if ($migrated > 0) {
        echo '<div class="success">✅ Migrated images from ' . $migrated . ' products!</div>';
    } else {
        echo '<div class="info">ℹ️  No existing images found to migrate.</div>';
    }
    
    echo '<div class="success"><strong>✅ Migration completed successfully!</strong></div>';
    echo '<div class="info">You can now safely delete this file or access the product edit page.</div>';
    
} catch (PDOException $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
?>
    </div>
</body>
</html>
