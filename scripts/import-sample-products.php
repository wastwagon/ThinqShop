<?php
/**
 * Import Sample Products Script
 * This script imports 50 sample products with images from Unsplash
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Sample products data
$sampleProducts = [
    // Electronics
    ['name' => 'Smartphone 128GB', 'price' => 899.99, 'category' => 'Electronics', 'stock' => 50, 'image' => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=800'],
    ['name' => 'Wireless Headphones', 'price' => 129.99, 'category' => 'Electronics', 'stock' => 75, 'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=800'],
    ['name' => 'Laptop 15.6"', 'price' => 1299.99, 'category' => 'Electronics', 'stock' => 30, 'image' => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=800'],
    ['name' => 'Smart Watch', 'price' => 249.99, 'category' => 'Electronics', 'stock' => 60, 'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=800'],
    ['name' => 'Tablet 10.1"', 'price' => 449.99, 'category' => 'Electronics', 'stock' => 40, 'image' => 'https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800'],
    ['name' => 'Bluetooth Speaker', 'price' => 79.99, 'category' => 'Electronics', 'stock' => 90, 'image' => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800'],
    ['name' => 'Camera DSLR', 'price' => 899.99, 'category' => 'Electronics', 'stock' => 25, 'image' => 'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=800'],
    ['name' => 'Gaming Mouse', 'price' => 49.99, 'category' => 'Electronics', 'stock' => 100, 'image' => 'https://images.unsplash.com/photo-1527814050087-3793815479db?w=800'],
    
    // Fashion
    ['name' => 'Leather Jacket', 'price' => 199.99, 'category' => 'Fashion', 'stock' => 35, 'image' => 'https://images.unsplash.com/photo-1551028719-00167b16eac5?w=800'],
    ['name' => 'Designer Sunglasses', 'price' => 89.99, 'category' => 'Fashion', 'stock' => 80, 'image' => 'https://images.unsplash.com/photo-1511499767150-a48a237f0083?w=800'],
    ['name' => 'Running Shoes', 'price' => 129.99, 'category' => 'Fashion', 'stock' => 65, 'image' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=800'],
    ['name' => 'Wristwatch', 'price' => 159.99, 'category' => 'Fashion', 'stock' => 70, 'image' => 'https://images.unsplash.com/photo-1524592094714-0f0654e20314?w=800'],
    ['name' => 'Backpack', 'price' => 79.99, 'category' => 'Fashion', 'stock' => 55, 'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800'],
    ['name' => 'Casual T-Shirt', 'price' => 29.99, 'category' => 'Fashion', 'stock' => 120, 'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=800'],
    ['name' => 'Denim Jeans', 'price' => 69.99, 'category' => 'Fashion', 'stock' => 85, 'image' => 'https://images.unsplash.com/photo-1542272604-787c3835535d?w=800'],
    ['name' => 'Dress Shirt', 'price' => 49.99, 'category' => 'Fashion', 'stock' => 95, 'image' => 'https://images.unsplash.com/photo-1594938291221-94f18cbb6b22?w=800'],
    
    // Home & Living
    ['name' => 'Coffee Maker', 'price' => 89.99, 'category' => 'Home & Living', 'stock' => 45, 'image' => 'https://images.unsplash.com/photo-1517668808823-f8c3f5436d83?w=800'],
    ['name' => 'Throw Pillow Set', 'price' => 39.99, 'category' => 'Home & Living', 'stock' => 60, 'image' => 'https://images.unsplash.com/photo-1584100936595-c0654b55c3a9?w=800'],
    ['name' => 'Desk Lamp', 'price' => 34.99, 'category' => 'Home & Living', 'stock' => 75, 'image' => 'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?w=800'],
    ['name' => 'Plant Pot Set', 'price' => 24.99, 'category' => 'Home & Living', 'stock' => 90, 'image' => 'https://images.unsplash.com/photo-1485955900006-10f4d324d411?w=800'],
    ['name' => 'Wall Clock', 'price' => 54.99, 'category' => 'Home & Living', 'stock' => 50, 'image' => 'https://images.unsplash.com/photo-1467269204594-9661b134dd2b?w=800'],
    ['name' => 'Dinnerware Set', 'price' => 79.99, 'category' => 'Home & Living', 'stock' => 40, 'image' => 'https://images.unsplash.com/photo-1556910103-1c02745aae4d?w=800'],
    ['name' => 'Yoga Mat', 'price' => 29.99, 'category' => 'Home & Living', 'stock' => 65, 'image' => 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=800'],
    ['name' => 'Storage Boxes', 'price' => 19.99, 'category' => 'Home & Living', 'stock' => 110, 'image' => 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=800'],
    
    // Beauty & Personal Care
    ['name' => 'Face Moisturizer', 'price' => 34.99, 'category' => 'Beauty', 'stock' => 85, 'image' => 'https://images.unsplash.com/photo-1556228720-195a672e8a03?w=800'],
    ['name' => 'Perfume 50ml', 'price' => 79.99, 'category' => 'Beauty', 'stock' => 55, 'image' => 'https://images.unsplash.com/photo-1541643600914-78b084683601?w=800'],
    ['name' => 'Hair Dryer', 'price' => 49.99, 'category' => 'Beauty', 'stock' => 70, 'image' => 'https://images.unsplash.com/photo-1522338242992-e1a54906a8da?w=800'],
    ['name' => 'Skincare Set', 'price' => 64.99, 'category' => 'Beauty', 'stock' => 45, 'image' => 'https://images.unsplash.com/photo-1556229010-6c3f2c9ca5f8?w=800'],
    ['name' => 'Makeup Brush Set', 'price' => 29.99, 'category' => 'Beauty', 'stock' => 95, 'image' => 'https://images.unsplash.com/photo-1583241804757-1e44537237c1?w=800'],
    ['name' => 'Shampoo & Conditioner', 'price' => 18.99, 'category' => 'Beauty', 'stock' => 100, 'image' => 'https://images.unsplash.com/photo-1556228578-0d85b1a4d571?w=800'],
    
    // Sports & Outdoors
    ['name' => 'Basketball', 'price' => 39.99, 'category' => 'Sports', 'stock' => 60, 'image' => 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800'],
    ['name' => 'Tennis Racket', 'price' => 89.99, 'category' => 'Sports', 'stock' => 35, 'image' => 'https://images.unsplash.com/photo-1622163642999-7a3b541c9326?w=800'],
    ['name' => 'Dumbbells Set', 'price' => 149.99, 'category' => 'Sports', 'stock' => 25, 'image' => 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=800'],
    ['name' => 'Soccer Ball', 'price' => 34.99, 'category' => 'Sports', 'stock' => 80, 'image' => 'https://images.unsplash.com/photo-1431324155629-1a6deb1dec8d?w=800'],
    ['name' => 'Cycling Helmet', 'price' => 59.99, 'category' => 'Sports', 'stock' => 50, 'image' => 'https://images.unsplash.com/photo-1571068316344-75bc76f77890?w=800'],
    ['name' => 'Tent 4-Person', 'price' => 199.99, 'category' => 'Sports', 'stock' => 20, 'image' => 'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=800'],
    
    // Books & Media
    ['name' => 'Best Seller Book', 'price' => 24.99, 'category' => 'Books', 'stock' => 100, 'image' => 'https://images.unsplash.com/photo-1544947950-fa07a98d237f?w=800'],
    ['name' => 'Notebook Set', 'price' => 14.99, 'category' => 'Books', 'stock' => 150, 'image' => 'https://images.unsplash.com/photo-1528607929212-2636ec44253e?w=800'],
    ['name' => 'Pen Set', 'price' => 12.99, 'category' => 'Books', 'stock' => 200, 'image' => 'https://images.unsplash.com/photo-1583484963886-cfe2bff2945f?w=800'],
    
    // Toys & Games
    ['name' => 'Board Game', 'price' => 34.99, 'category' => 'Toys', 'stock' => 55, 'image' => 'https://images.unsplash.com/photo-1606092195730-5d7b9af1efc5?w=800'],
    ['name' => 'Action Figure', 'price' => 19.99, 'category' => 'Toys', 'stock' => 90, 'image' => 'https://images.unsplash.com/photo-1526170375885-4d8ecf77b99f?w=800'],
    ['name' => 'Puzzle 1000 Pieces', 'price' => 24.99, 'category' => 'Toys', 'stock' => 65, 'image' => 'https://images.unsplash.com/photo-1606092195730-5d7b9af1efc5?w=800'],
    
    // Food & Beverages
    ['name' => 'Gourmet Coffee Beans', 'price' => 29.99, 'category' => 'Food', 'stock' => 75, 'image' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=800'],
    ['name' => 'Organic Tea Set', 'price' => 22.99, 'category' => 'Food', 'stock' => 85, 'image' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?w=800'],
    ['name' => 'Honey Jar', 'price' => 18.99, 'category' => 'Food', 'stock' => 70, 'image' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=800'],
    
    // Automotive
    ['name' => 'Car Phone Mount', 'price' => 19.99, 'category' => 'Automotive', 'stock' => 100, 'image' => 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800'],
    ['name' => 'Car Charger', 'price' => 14.99, 'category' => 'Automotive', 'stock' => 120, 'image' => 'https://images.unsplash.com/photo-1608770355745-b0f97c1aefbe?w=800'],
    
    // Kitchen & Dining
    ['name' => 'Stainless Steel Cookware', 'price' => 149.99, 'category' => 'Kitchen', 'stock' => 30, 'image' => 'https://images.unsplash.com/photo-1556912172-45b7abe8b7e1?w=800'],
    ['name' => 'Electric Kettle', 'price' => 39.99, 'category' => 'Kitchen', 'stock' => 60, 'image' => 'https://images.unsplash.com/photo-1514228742587-6b1558fcca3d?w=800'],
    ['name' => 'Knife Set', 'price' => 89.99, 'category' => 'Kitchen', 'stock' => 40, 'image' => 'https://images.unsplash.com/photo-1584464491033-06628f3a6b7b?w=800'],
];

$db = new Database();
$conn = $db->getConnection();

// Get or create categories
$categories = [];
$categoryMap = [];

foreach (['Electronics', 'Fashion', 'Home & Living', 'Beauty', 'Sports', 'Books', 'Toys', 'Food', 'Automotive', 'Kitchen'] as $catName) {
    $stmt = $conn->prepare("SELECT id FROM categories WHERE name = ? AND parent_id IS NULL");
    $stmt->execute([$catName]);
    $category = $stmt->fetch();
    
    if (!$category) {
        $slug = strtolower(str_replace([' ', '&'], ['-', 'and'], $catName));
        $stmt = $conn->prepare("INSERT INTO categories (name, slug, is_active, sort_order) VALUES (?, ?, 1, ?)");
        $stmt->execute([$catName, $slug, count($categories) + 1]);
        $categoryId = $conn->lastInsertId();
    } else {
        $categoryId = $category['id'];
    }
    
    $categoryMap[$catName] = $categoryId;
}

// Function to download and save image
function downloadImage($url, $filename) {
    $imageDir = PRODUCT_IMAGE_PATH;
    if (!is_dir($imageDir)) {
        mkdir($imageDir, 0755, true);
    }
    
    $fullPath = $imageDir . $filename;
    
    // Use cURL to download
    $ch = curl_init($url);
    $fp = fopen($fullPath, 'wb');
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
    fclose($fp);
    
    return file_exists($fullPath) ? 'assets/images/products/' . $filename : null;
}

// Import products
$imported = 0;
$errors = [];

foreach ($sampleProducts as $index => $product) {
    try {
        // Generate slug
        $slug = strtolower(str_replace(' ', '-', $product['name']));
        $slug = preg_replace('/[^a-z0-9-]/', '', $slug);
        
        // Check if product exists
        $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug = $slug . '-' . time() . '-' . $index;
        }
        
        // Download image
        $imagePath = null;
        if (!empty($product['image'])) {
            $imageExt = 'jpg';
            $imageFilename = $slug . '.' . $imageExt;
            $imagePath = downloadImage($product['image'], $imageFilename);
        }
        
        // Insert product
        $stmt = $conn->prepare("
            INSERT INTO products (
                name, slug, description, price, 
                category_id, stock_quantity, low_stock_threshold,
                images, sku, is_featured, is_active, 
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $description = "High-quality {$product['name']}. Perfect for your needs. Fast delivery across Ghana.";
        $sku = 'SKU-' . strtoupper(substr($slug, 0, 8)) . '-' . rand(1000, 9999);
        $images = $imagePath ? json_encode([$imagePath]) : json_encode([]);
        $isFeatured = ($index < 8) ? 1 : 0; // First 8 are featured
        
        $stmt->execute([
            $product['name'],
            $slug,
            $description,
            $product['price'],
            $categoryMap[$product['category']],
            $product['stock'],
            max(10, intval($product['stock'] * 0.2)),
            $images,
            $sku,
            $isFeatured,
            1
        ]);
        
        $imported++;
        
        if (($imported % 10) == 0) {
            echo "Imported $imported products...\n";
        }
        
    } catch (Exception $e) {
        $errors[] = "Error importing {$product['name']}: " . $e->getMessage();
    }
}

echo "\n✅ Import Complete!\n";
echo "Total imported: $imported products\n";
if (!empty($errors)) {
    echo "Errors: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "  - $error\n";
    }
}







