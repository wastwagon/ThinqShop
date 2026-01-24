<?php
/**
 * Diagnostic script to check product images
 * Check if images are saved correctly in database and files exist
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Product Images Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: 600; }
        img { max-width: 100px; max-height: 100px; object-fit: cover; border: 1px solid #ddd; }
        .status-ok { color: #28a745; font-weight: 600; }
        .status-error { color: #dc3545; font-weight: 600; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Product Images Diagnostic Tool</h1>
        
<?php
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Check if product_images table exists
    echo '<h2>1. Table Check</h2>';
    $tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
    if ($tableCheck->rowCount() > 0) {
        echo '<div class="success">✅ product_images table exists</div>';
    } else {
        echo '<div class="error">❌ product_images table does NOT exist. Please run the migration script first.</div>';
        echo '<p><a href="create-product-images-table.php" class="btn btn-primary">Run Migration Script</a></p>';
    }
    
    // Get PS5 product
    echo '<h2>2. PS5 Slim - Digital Version Product Check</h2>';
    $stmt = $conn->prepare("SELECT * FROM products WHERE name LIKE '%PS5%' OR name LIKE '%ps5%' LIMIT 5");
    $stmt->execute();
    $ps5Products = $stmt->fetchAll();
    
    if (empty($ps5Products)) {
        echo '<div class="warning">⚠️ No PS5 products found. Searching for all products...</div>';
        $stmt = $conn->query("SELECT * FROM products ORDER BY id DESC LIMIT 5");
        $ps5Products = $stmt->fetchAll();
    }
    
    if (!empty($ps5Products)) {
        echo '<table>';
        echo '<tr><th>ID</th><th>Name</th><th>Images (JSON)</th><th>Images in Table</th><th>File Exists</th><th>Preview</th></tr>';
        
        foreach ($ps5Products as $product) {
            echo '<tr>';
            echo '<td>' . $product['id'] . '</td>';
            echo '<td><strong>' . htmlspecialchars($product['name']) . '</strong></td>';
            
            // Check JSON field
            $jsonImages = json_decode($product['images'] ?? '[]', true);
            echo '<td>';
            if (!empty($jsonImages) && is_array($jsonImages)) {
                echo '<span class="status-ok">✅ ' . count($jsonImages) . ' image(s)</span><br>';
                foreach ($jsonImages as $idx => $img) {
                    echo '<small>' . htmlspecialchars($img) . '</small><br>';
                }
            } else {
                echo '<span class="status-error">❌ No images in JSON</span>';
            }
            echo '</td>';
            
            // Check product_images table
            echo '<td>';
            try {
                $tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
                if ($tableCheck->rowCount() > 0) {
                    $imgStmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
                    $imgStmt->execute([$product['id']]);
                    $tableImages = $imgStmt->fetchAll();
                    
                    if (!empty($tableImages)) {
                        echo '<span class="status-ok">✅ ' . count($tableImages) . ' image(s)</span><br>';
                        foreach ($tableImages as $img) {
                            echo '<small>ID: ' . $img['id'] . ' - ' . htmlspecialchars($img['image_url']) . '</small><br>';
                        }
                    } else {
                        echo '<span class="status-error">❌ No images in table</span>';
                    }
                } else {
                    echo '<span class="warning">⚠️ Table not found</span>';
                }
            } catch (PDOException $e) {
                echo '<span class="status-error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</span>';
            }
            echo '</td>';
            
            // Check if files exist
            echo '<td>';
            $allImages = [];
            
            // Get from table
            try {
                $tableCheck = $conn->query("SHOW TABLES LIKE 'product_images'");
                if ($tableCheck->rowCount() > 0) {
                    $imgStmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ?");
                    $imgStmt->execute([$product['id']]);
                    $tableImages = $imgStmt->fetchAll();
                    foreach ($tableImages as $img) {
                        $allImages[] = $img['image_url'];
                    }
                }
            } catch (PDOException $e) {}
            
            // Get from JSON
            if (!empty($jsonImages)) {
                foreach ($jsonImages as $img) {
                    if (is_string($img) && !empty($img)) {
                        // Extract filename if it's a full path
                        $filename = basename($img);
                        if (!in_array($filename, $allImages)) {
                            $allImages[] = $filename;
                        }
                    }
                }
            }
            
            if (!empty($allImages)) {
                foreach ($allImages as $imgFile) {
                    $filePath = BASE_PATH . '/assets/images/products/' . $imgFile;
                    if (file_exists($filePath)) {
                        echo '<span class="status-ok">✅ ' . htmlspecialchars($imgFile) . '</span><br>';
                    } else {
                        echo '<span class="status-error">❌ ' . htmlspecialchars($imgFile) . ' (not found)</span><br>';
                        echo '<small>Expected: ' . htmlspecialchars($filePath) . '</small><br>';
                    }
                }
            } else {
                echo '<span class="status-error">❌ No images to check</span>';
            }
            echo '</td>';
            
            // Preview
            echo '<td>';
            if (!empty($allImages)) {
                $firstImage = $allImages[0];
                $imagePath = BASE_PATH . '/assets/images/products/' . $firstImage;
                if (file_exists($imagePath)) {
                    $imageUrl = BASE_URL . '/assets/images/products/' . $firstImage;
                    echo '<img src="' . htmlspecialchars($imageUrl) . '" alt="Preview" onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'block\';"><span style="display:none; color:red;">❌ Failed to load</span>';
                } else {
                    echo '<span class="status-error">❌ File not found</span>';
                }
            } else {
                echo '<span class="status-error">No image</span>';
            }
            echo '</td>';
            
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<div class="error">❌ No products found in database</div>';
    }
    
    // Check upload directory
    echo '<h2>3. Upload Directory Check</h2>';
    $uploadDir = BASE_PATH . '/assets/images/products/';
    if (is_dir($uploadDir)) {
        echo '<div class="success">✅ Upload directory exists: ' . htmlspecialchars($uploadDir) . '</div>';
        
        if (is_writable($uploadDir)) {
            echo '<div class="success">✅ Directory is writable</div>';
        } else {
            echo '<div class="error">❌ Directory is NOT writable</div>';
        }
        
        // List files
        $files = glob($uploadDir . '*');
        echo '<div class="info">📁 Found ' . count($files) . ' file(s) in upload directory</div>';
        
        if (count($files) > 0) {
            echo '<table>';
            echo '<tr><th>Filename</th><th>Size</th><th>Preview</th></tr>';
            foreach (array_slice($files, 0, 10) as $file) {
                $filename = basename($file);
                echo '<tr>';
                echo '<td>' . htmlspecialchars($filename) . '</td>';
                echo '<td>' . number_format(filesize($file) / 1024, 2) . ' KB</td>';
                echo '<td><img src="' . BASE_URL . '/assets/images/products/' . htmlspecialchars($filename) . '" alt="Preview" style="max-width: 80px;"></td>';
                echo '</tr>';
            }
            echo '</table>';
        }
    } else {
        echo '<div class="error">❌ Upload directory does NOT exist: ' . htmlspecialchars($uploadDir) . '</div>';
        echo '<div class="info">Attempting to create directory...</div>';
        if (mkdir($uploadDir, 0755, true)) {
            echo '<div class="success">✅ Directory created successfully</div>';
        } else {
            echo '<div class="error">❌ Failed to create directory</div>';
        }
    }
    
} catch (Exception $e) {
    echo '<div class="error">❌ Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
}
?>
    </div>
</body>
</html>




