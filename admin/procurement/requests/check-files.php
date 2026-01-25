<?php
/**
 * Check Files in Database - Debug Script
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get request ID
$requestId = intval($_GET['id'] ?? 0);

if ($requestId <= 0) {
    die("Invalid request ID");
}

// Get request details
// Check if procurement_request_items table exists first
$tableExists = false;
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'procurement_request_items'");
    $tableExists = $checkTable->rowCount() > 0;
} catch (Exception $e) {
    $tableExists = false;
}

if ($tableExists) {
    $stmt = $conn->prepare("
        SELECT pr.*, 
               (SELECT COUNT(*) FROM procurement_request_items WHERE request_id = pr.id) as item_count
        FROM procurement_requests pr
        WHERE pr.id = ?
    ");
} else {
    $stmt = $conn->prepare("
        SELECT pr.*
        FROM procurement_requests pr
        WHERE pr.id = ?
    ");
}
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    die("Request not found");
}

echo "<!DOCTYPE html><html><head><title>Check Files - Request #{$requestId}</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;} table{border-collapse:collapse;width:100%;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background-color:#f2f2f2;}</style>";
echo "</head><body>";
echo "<h2>Debug: Files Check for Request #{$requestId}</h2>";
echo "<h3>Request: {$request['request_number']}</h3>";
echo "<h3>Category: " . ($request['category'] ?? 'products_purchase') . "</h3>";

echo "<hr><h4>1. Main Request Table Data:</h4>";
echo "<pre>";
echo "reference_images: " . ($request['reference_images'] ?? 'NULL') . "\n";
echo "branding_logo_file: " . ($request['branding_logo_file'] ?? 'NULL') . "\n";
echo "branding_artwork_files: " . ($request['branding_artwork_files'] ?? 'NULL') . "\n";
echo "specifications: " . substr($request['specifications'] ?? 'NULL', 0, 200) . "...\n";
echo "</pre>";

// Check procurement_request_items
$category = $request['category'] ?? 'products_purchase';
if ($category === 'products_purchase') {
    echo "<hr><h4>2. Procurement Request Items:</h4>";
    try {
        $checkTable = $conn->query("SHOW TABLES LIKE 'procurement_request_items'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $conn->prepare("
                SELECT id, item_name, reference_images 
                FROM procurement_request_items 
                WHERE request_id = ? 
                ORDER BY item_order ASC
            ");
            $stmt->execute([$requestId]);
            $items = $stmt->fetchAll();
            
            if (empty($items)) {
                echo "<p>No items found in procurement_request_items table.</p>";
            } else {
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>ID</th><th>Item Name</th><th>Reference Images</th></tr>";
                foreach ($items as $item) {
                    echo "<tr>";
                    echo "<td>{$item['id']}</td>";
                    echo "<td>" . htmlspecialchars($item['item_name']) . "</td>";
                    echo "<td><pre>" . htmlspecialchars($item['reference_images'] ?? 'NULL') . "</pre></td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        } else {
            echo "<p>procurement_request_items table does not exist.</p>";
        }
    } catch (Exception $e) {
        echo "<p>Error: " . $e->getMessage() . "</p>";
    }
}

// Check branding files
if ($category === 'product_branding') {
    echo "<hr><h4>2. Branding Files:</h4>";
    $logoFile = $request['branding_logo_file'] ?? null;
    $artworkFiles = $request['branding_artwork_files'] ?? null;
    
    echo "<p><strong>Logo File (raw):</strong> " . ($logoFile ?? 'NULL') . "</p>";
    if ($logoFile) {
        $logoDecoded = json_decode($logoFile, true);
        echo "<p><strong>Logo File (decoded):</strong></p>";
        echo "<pre>" . print_r($logoDecoded, true) . "</pre>";
    }
    
    echo "<p><strong>Artwork Files (raw):</strong> " . ($artworkFiles ?? 'NULL') . "</p>";
    if ($artworkFiles) {
        $artworkDecoded = json_decode($artworkFiles, true);
        echo "<p><strong>Artwork Files (decoded):</strong></p>";
        echo "<pre>" . print_r($artworkDecoded, true) . "</pre>";
    }
    
    // Also check specifications
    if (!empty($request['specifications'])) {
        $specs = json_decode($request['specifications'], true);
        if (is_array($specs)) {
            echo "<p><strong>Files in Specifications JSON:</strong></p>";
            echo "<pre>" . print_r($specs, true) . "</pre>";
        }
    }
}

// Check if files exist on disk
echo "<hr><h4>3. Files on Disk:</h4>";
$uploadPath = BASE_PATH . '/assets/images/uploads/';
echo "<p>Upload Path: {$uploadPath}</p>";

// First, check reference_images column
if (!empty($request['reference_images'])) {
    echo "<h5>Files from reference_images column:</h5>";
    $refImages = json_decode($request['reference_images'], true);
    if (is_array($refImages)) {
        foreach ($refImages as $image) {
            $filePath = $uploadPath . $image;
            $exists = file_exists($filePath) ? "YES" : "NO";
            echo "<p>File: {$image} - Exists: {$exists}</p>";
            if (file_exists($filePath)) {
                echo "<p>  Size: " . filesize($filePath) . " bytes</p>";
                echo "<p>  <a href='" . ASSETS_URL . "/images/uploads/{$image}' target='_blank'>View File</a></p>";
            }
        }
    }
} else {
    echo "<p><em>No files in reference_images column</em></p>";
}

// Check specifications JSON for file references
if (!empty($request['specifications'])) {
    echo "<h5>Checking specifications JSON for file references:</h5>";
    $specs = json_decode($request['specifications'], true);
    if (is_array($specs)) {
        // Check if specifications contains products array
        if (isset($specs['products']) && is_array($specs['products'])) {
            foreach ($specs['products'] as $idx => $product) {
                if (isset($product['images']) && is_array($product['images'])) {
                    $itemName = isset($product['item_name']) ? $product['item_name'] : 'N/A';
                    echo "<p><strong>Product #{$idx} ({$itemName}) images:</strong></p>";
                    foreach ($product['images'] as $image) {
                        $filePath = $uploadPath . $image;
                        $exists = file_exists($filePath) ? "YES" : "NO";
                        echo "<p>  File: {$image} - Exists: {$exists}</p>";
                        if (file_exists($filePath)) {
                            echo "<p>    Size: " . filesize($filePath) . " bytes</p>";
                            echo "<p>    <a href='" . ASSETS_URL . "/images/uploads/{$image}' target='_blank'>View File</a></p>";
                        }
                    }
                }
            }
        }
        // Also check for any image/file fields directly in specs
        foreach ($specs as $key => $value) {
            if (is_string($key) && (stripos($key, 'image') !== false || stripos($key, 'file') !== false)) {
                if (is_array($value)) {
                    foreach ($value as $image) {
                        if (is_string($image)) {
                            $filePath = $uploadPath . $image;
                            $exists = file_exists($filePath) ? "YES" : "NO";
                            echo "<p>File from {$key}: {$image} - Exists: {$exists}</p>";
                        }
                    }
                } elseif (is_string($value)) {
                    $filePath = $uploadPath . $value;
                    $exists = file_exists($filePath) ? "YES" : "NO";
                    echo "<p>File from {$key}: {$value} - Exists: {$exists}</p>";
                }
            }
        }
    }
}

// Check procurement_request_items table if it exists
if ($category === 'products_purchase' && $tableExists) {
    try {
        $stmt = $conn->prepare("SELECT reference_images FROM procurement_request_items WHERE request_id = ?");
        $stmt->execute([$requestId]);
        $items = $stmt->fetchAll();
        
        if (!empty($items)) {
            echo "<h5>Files from procurement_request_items table:</h5>";
            foreach ($items as $item) {
                if (!empty($item['reference_images'])) {
                    $images = json_decode($item['reference_images'], true);
                    if (is_array($images)) {
                        foreach ($images as $image) {
                            $filePath = $uploadPath . $image;
                            $exists = file_exists($filePath) ? "YES" : "NO";
                            echo "<p>File: {$image} - Exists: {$exists}</p>";
                            if (file_exists($filePath)) {
                                echo "<p>  Size: " . filesize($filePath) . " bytes</p>";
                                echo "<p>  <a href='" . ASSETS_URL . "/images/uploads/{$image}' target='_blank'>View File</a></p>";
                            }
                        }
                    }
                }
            }
        }
    } catch (Exception $e) {
        echo "<p>Error checking items: " . $e->getMessage() . "</p>";
    }
}

// Check branding files
if ($category === 'product_branding') {
    $logoFile = $request['branding_logo_file'] ?? null;
    $artworkFiles = $request['branding_artwork_files'] ?? null;
    
    if ($logoFile) {
        $logoDecoded = json_decode($logoFile, true);
        if (is_array($logoDecoded)) {
            foreach ($logoDecoded as $file) {
                $filePath = $uploadPath . $file;
                $exists = file_exists($filePath) ? "YES" : "NO";
                echo "<p>Logo File: {$file} - Exists: {$exists}</p>";
            }
        }
    }
    
    if ($artworkFiles) {
        $artworkDecoded = json_decode($artworkFiles, true);
        if (is_array($artworkDecoded)) {
            foreach ($artworkDecoded as $file) {
                $filePath = $uploadPath . $file;
                $exists = file_exists($filePath) ? "YES" : "NO";
                echo "<p>Artwork File: {$file} - Exists: {$exists}</p>";
            }
        }
    }
}

echo "<hr><p><a href='view.php?id={$requestId}'>Back to Request View</a></p>";
echo "</body></html>";
?>


