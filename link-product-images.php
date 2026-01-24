<?php
/**
 * Link existing uploaded images to products
 * This script helps link image files to products in the product_images table
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Link Product Images</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        img { max-width: 80px; max-height: 80px; object-fit: cover; border: 1px solid #ddd; }
        .btn { display: inline-block; padding: 8px 16px; background: #05203e; color: white; text-decoration: none; border-radius: 4px; margin: 5px; }
        .btn:hover { background: #1f3651; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Link Product Images</h1>
        
<?php
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
    if ($tableCheck->rowCount() == 0) {
        echo '<div class="error">❌ product_images table does NOT exist. Please run the migration script first.</div>';
        echo '<p><a href="create-product-images-table.php" class="btn">Run Migration Script</a></p>';
        exit;
    }
    
    echo '<div class="success">✅ product_images table exists</div>';
    
    // Get PS5 product
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE '%PS5%' OR name LIKE '%ps5%' LIMIT 1");
    $stmt->execute();
    $ps5Product = $stmt->fetch();
    
    if (!$ps5Product) {
        echo '<div class="error">❌ PS5 product not found. Please create it first or search for a different product.</div>';
        exit;
    }
    
    echo '<div class="info">📦 Found product: <strong>' . htmlspecialchars($ps5Product['name']) . '</strong> (ID: ' . $ps5Product['id'] . ')</div>';
    
    // Check if product already has images
    $imgStmt = $conn->prepare("SELECT COUNT(*) as count FROM product_images WHERE product_id = ?");
    $imgStmt->execute([$ps5Product['id']]);
    $existingCount = $imgStmt->fetch()['count'];
    
    if ($existingCount > 0) {
        echo '<div class="info">ℹ️  Product already has ' . $existingCount . ' image(s) in the table.</div>';
    }
    
    // Get all image files in upload directory
    $uploadDir = BASE_PATH . '/assets/images/products/';
    $imageFiles = [];
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE);
        foreach ($files as $file) {
            $imageFiles[] = basename($file);
        }
    }
    
    echo '<h2>Available Image Files</h2>';
    echo '<p>Found ' . count($imageFiles) . ' image file(s) in upload directory.</p>';
    
    if (isset($_POST['link_images']) && isset($_POST['product_id'])) {
        $productId = intval($_POST['product_id']);
        $selectedImages = $_POST['images'] ?? [];
        
        if (!empty($selectedImages)) {
            $conn->beginTransaction();
            try {
                $sortOrder = $existingCount;
                $linked = 0;
                
                foreach ($selectedImages as $imageFile) {
                    // Check if image already linked
                    $checkStmt = $conn->prepare("SELECT id FROM product_images WHERE product_id = ? AND image_url = ?");
                    $checkStmt->execute([$productId, $imageFile]);
                    if ($checkStmt->fetch()) {
                        continue; // Skip if already linked
                    }
                    
                    $insertStmt = $conn->prepare("
                        INSERT INTO product_images (product_id, image_url, sort_order, is_primary, created_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $isPrimary = ($sortOrder === 0) ? 1 : 0;
                    $insertStmt->execute([$productId, $imageFile, $sortOrder, $isPrimary]);
                    $sortOrder++;
                    $linked++;
                }
                
                $conn->commit();
                echo '<div class="success">✅ Successfully linked ' . $linked . ' image(s) to product!</div>';
                echo '<p><a href="check-product-images.php" class="btn">Check Results</a> | <a href="admin/ecommerce/products-edit.php?id=' . $productId . '" class="btn">Edit Product</a></p>';
                
            } catch (Exception $e) {
                $conn->rollBack();
                echo '<div class="error">❌ Error linking images: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
        } else {
            echo '<div class="error">❌ No images selected</div>';
        }
    }
    
    // Show form to link images
    if (!isset($_POST['link_images'])) {
        echo '<form method="POST">';
        echo '<input type="hidden" name="product_id" value="' . $ps5Product['id'] . '">';
        echo '<h2>Select Images to Link</h2>';
        echo '<p>Select the image file(s) that belong to <strong>' . htmlspecialchars($ps5Product['name']) . '</strong>:</p>';
        
        if (!empty($imageFiles)) {
            echo '<div style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 4px;">';
            echo '<table>';
            echo '<tr><th>Select</th><th>Filename</th><th>Preview</th><th>Size</th></tr>';
            
            foreach (array_slice($imageFiles, 0, 50) as $imageFile) {
                $filePath = $uploadDir . $imageFile;
                $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
                $imageUrl = BASE_URL . '/assets/images/products/' . $imageFile;
                
                echo '<tr>';
                echo '<td><input type="checkbox" name="images[]" value="' . htmlspecialchars($imageFile) . '"></td>';
                echo '<td>' . htmlspecialchars($imageFile) . '</td>';
                echo '<td><img src="' . htmlspecialchars($imageUrl) . '" alt="Preview" onerror="this.style.display=\'none\'"></td>';
                echo '<td>' . number_format($fileSize / 1024, 2) . ' KB</td>';
                echo '</tr>';
            }
            echo '</table>';
            echo '</div>';
            
            echo '<p style="margin-top: 20px;">';
            echo '<button type="submit" name="link_images" class="btn">Link Selected Images</button>';
            echo '<a href="check-product-images.php" class="btn" style="background: #6c757d;">Cancel</a>';
            echo '</p>';
        } else {
            echo '<div class="error">❌ No image files found in upload directory</div>';
        }
        
        echo '</form>';
    }
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
?>
    </div>
</body>
</html>




