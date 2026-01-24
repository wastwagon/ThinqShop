<?php
/**
 * Edit Product
 * ThinQShopping Platform
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$productId = intval($_GET['id'] ?? 0);

// Capture pagination/filter/search parameters directly
$currentPage = max(1, intval($_GET['page'] ?? $_POST['page'] ?? 1));
$currentFilter = $_GET['filter'] ?? $_POST['filter'] ?? 'all';
$currentSearch = $_GET['search'] ?? $_POST['search'] ?? '';

$returnParams = [
    'page' => $currentPage,
    'filter' => $currentFilter,
    'search' => $currentSearch
];
$returnQueryString = http_build_query($returnParams);
$returnQuery = $returnQueryString !== '' ? '?' . $returnQueryString : '';
$returnUrlPath = '/admin/ecommerce/products.php' . $returnQuery;

// Persist last visited products list URL for this admin session
$_SESSION['admin_products_last_list_url'] = $returnUrlPath;

if ($productId <= 0) {
    redirect('/admin/ecommerce/products.php', 'Invalid product ID.', 'danger');
}

// Get product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('/admin/ecommerce/products.php', 'Product not found.', 'danger');
}

// Get categories
$stmt = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Get product images
$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
$stmt->execute([$productId]);
$productImages = $stmt->fetchAll();

$errors = [];
$success = false;

// Handle image deletion
if (isset($_GET['delete_image'])) {
    $imageId = intval($_GET['delete_image']);
    $deleteJsonIndex = isset($_GET['json_index']) ? intval($_GET['json_index']) : null;
    
    try {
        $conn->beginTransaction();
        
        $imagePathToDelete = null;
        
        if ($imageId > 0) {
            // Delete from product_images table
            $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE id = ? AND product_id = ?");
            $stmt->execute([$imageId, $productId]);
            $image = $stmt->fetch();
            
            if ($image) {
                $imagePathToDelete = $image['image_url'];
                
                // Delete from table
                $stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
                $stmt->execute([$imageId]);
                
                // Also remove from JSON if it exists there
                $jsonImages = json_decode($product['images'] ?? '[]', true);
                $jsonImages = array_filter($jsonImages, function($img) use ($imagePathToDelete) {
                    // Compare both full path and filename
                    return $img !== $imagePathToDelete && 
                           basename($img) !== basename($imagePathToDelete) &&
                           $img !== basename($imagePathToDelete);
                });
                $jsonImages = array_values($jsonImages); // Re-index array
                
                // Update products.images JSON
                $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
                $stmt->execute([json_encode($jsonImages), $productId]);
            }
        } elseif ($deleteJsonIndex !== null && $deleteJsonIndex >= 0) {
            // Delete from products.images JSON
            $jsonImages = json_decode($product['images'] ?? '[]', true);
            if (isset($jsonImages[$deleteJsonIndex])) {
                $imagePathToDelete = $jsonImages[$deleteJsonIndex];
                
                // Remove from array
                unset($jsonImages[$deleteJsonIndex]);
                $jsonImages = array_values($jsonImages); // Re-index array
                
                // Update products.images JSON
                $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
                $stmt->execute([json_encode($jsonImages), $productId]);
                
                // Also delete from product_images table if it exists there
                // Try to match by exact URL, filename, or basename
                $stmt = $conn->prepare("SELECT id, image_url FROM product_images WHERE product_id = ?");
                $stmt->execute([$productId]);
                $tableImages = $stmt->fetchAll();
                
                foreach ($tableImages as $tableImg) {
                    if ($tableImg['image_url'] === $imagePathToDelete || 
                        basename($tableImg['image_url']) === basename($imagePathToDelete) ||
                        $tableImg['image_url'] === basename($imagePathToDelete)) {
                        $stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
                        $stmt->execute([$tableImg['id']]);
                        break;
                    }
                }
            }
        }
        
        // Delete physical file if we have a path
        if ($imagePathToDelete) {
            // Try multiple possible paths
            $pathsToTry = [
                PRODUCT_IMAGE_PATH . $imagePathToDelete,
                PRODUCT_IMAGE_PATH . basename($imagePathToDelete),
                UPLOAD_PATH . $imagePathToDelete,
                UPLOAD_PATH . basename($imagePathToDelete),
                BASE_PATH . '/' . ltrim($imagePathToDelete, '/'),
                BASE_PATH . '/assets/images/products/' . basename($imagePathToDelete)
            ];
            
            foreach ($pathsToTry as $filePath) {
                if (file_exists($filePath) && is_file($filePath)) {
                    @unlink($filePath);
                    break;
                }
            }
        }
        
        $conn->commit();
        
        // Preserve return parameters when redirecting after image deletion
        $redirectUrl = '/admin/ecommerce/products-edit.php?id=' . $productId;
        if ($returnQueryString !== '') {
            $redirectUrl .= '&' . $returnQueryString;
        }
        redirect($redirectUrl, 'Image deleted successfully.', 'success');
        exit; // Ensure script stops after redirect
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Delete Image Error: " . $e->getMessage());
        
        // Preserve return parameters when redirecting after error
        $redirectUrl = '/admin/ecommerce/products-edit.php?id=' . $productId;
        if ($returnQueryString !== '') {
            $redirectUrl .= '&' . $returnQueryString;
        }
        redirect($redirectUrl, 'Failed to delete image.', 'danger');
        exit; // Ensure script stops after redirect
    }
}

// Process update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $description = $_POST['description'] ?? '';
        $price = floatval($_POST['price'] ?? 0);
        $comparePrice = !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null;
        $categoryId = intval($_POST['category_id'] ?? 0);
        $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
        $lowStockThreshold = intval($_POST['low_stock_threshold'] ?? 5);
        $sku = sanitize($_POST['sku'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        $specifications = sanitize($_POST['specifications'] ?? '');
        
        if (empty($name)) {
            $errors[] = 'Product name is required.';
        }
        
        if ($price <= 0) {
            $errors[] = 'Price must be greater than 0.';
        }
        
        if ($categoryId <= 0) {
            $errors[] = 'Please select a category.';
        }
        
        // Handle image uploads
        $uploadedImages = [];
        if (!empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmpName) {
                if (!empty($tmpName)) {
                    $file = [
                        'name' => $_FILES['images']['name'][$key],
                        'type' => $_FILES['images']['type'][$key],
                        'tmp_name' => $tmpName,
                        'error' => $_FILES['images']['error'][$key],
                        'size' => $_FILES['images']['size'][$key]
                    ];
                    
                    $uploadPath = PRODUCT_IMAGE_PATH;
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }
                    $result = uploadImage($file, $uploadPath);
                    if ($result['success']) {
                        $uploadedImages[] = $result['filename'];
                    }
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Update product
                $stmt = $conn->prepare("
                    UPDATE products SET
                        name = ?, description = ?, price = ?, compare_price = ?,
                        category_id = ?, stock_quantity = ?, low_stock_threshold = ?,
                        sku = ?, is_active = ?, is_featured = ?, specifications = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $name, $description, $price, $comparePrice,
                    $categoryId, $stockQuantity, $lowStockThreshold,
                    $sku, $isActive, $isFeatured, $specifications,
                    $productId
                ]);
                
                // Handle images - sync between JSON and table
                // Get current JSON images
                $currentJsonImages = json_decode($product['images'] ?? '[]', true);
                
                // Add new uploaded images to both JSON and table
                if (!empty($uploadedImages)) {
                    $sortOrder = count($productImages);
                    foreach ($uploadedImages as $image) {
                        // Store just filename (not full path) in JSON for consistency
                        $imageFilename = $image;
                        
                        // Add to product_images table
                        $stmt = $conn->prepare("
                            INSERT INTO product_images (product_id, image_url, sort_order, created_at)
                            VALUES (?, ?, ?, NOW())
                        ");
                        $stmt->execute([$productId, $imageFilename, ++$sortOrder]);
                        
                        // Add to JSON array (store as filename)
                        $currentJsonImages[] = $imageFilename;
                    }
                }
                
                // Update products.images JSON to sync with table
                // Get all images from table to ensure consistency
                $stmt = $conn->prepare("SELECT image_url FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
                $stmt->execute([$productId]);
                $tableImages = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Merge: use table images as source of truth, but keep any JSON-only images that exist
                $finalImages = !empty($tableImages) ? $tableImages : $currentJsonImages;
                
                // Update products.images JSON
                $stmt = $conn->prepare("UPDATE products SET images = ? WHERE id = ?");
                $stmt->execute([json_encode($finalImages), $productId]);
                
                $conn->commit();
                
                logAdminAction($_SESSION['admin_id'], 'update_product', 'products', $productId);
                
                // Preserve pagination and filter parameters in redirect
                $redirectUrl = '/admin/ecommerce/products.php' . $returnQuery;
                
                // If return_url was provided via POST, prefer that (after validation)
                if (!empty($_POST['return_url'])) {
                    $postedReturnUrl = $_POST['return_url'];
                    if (is_string($postedReturnUrl) && preg_match('#^/admin/ecommerce/products\.php(\?.*)?$#', $postedReturnUrl)) {
                        $redirectUrl = $postedReturnUrl;
                    }
                } elseif (!empty($_SESSION['admin_products_last_list_url']) && preg_match('#^/admin/ecommerce/products\.php(\?.*)?$#', $_SESSION['admin_products_last_list_url'])) {
                    $redirectUrl = $_SESSION['admin_products_last_list_url'];
                }
                
                error_log("Product update redirect for ID {$productId}: {$redirectUrl}");
                redirect($redirectUrl, 'Product updated successfully!', 'success');
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Product Update Error: " . $e->getMessage());
                $errors[] = 'Failed to update product: ' . $e->getMessage();
            }
        }
    }
}

// Reload product after update attempt
if (!empty($errors)) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    $stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$productId]);
    $productImages = $stmt->fetchAll();
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Edit Product</h1>
    <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products.php<?php echo htmlspecialchars($returnQuery); ?>" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<?php $formAction = htmlspecialchars($_SERVER['REQUEST_URI'] ?? ''); ?>
<form method="POST" action="<?php echo $formAction; ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        <input type="hidden" name="page" value="<?php echo htmlspecialchars($currentPage); ?>">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($currentFilter); ?>">
        <input type="hidden" name="search" value="<?php echo htmlspecialchars($currentSearch); ?>">
        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($returnUrlPath); ?>">
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Basic Information -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="6"><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Specifications</label>
                            <textarea name="specifications" class="form-control" rows="4" 
                                      placeholder="Key features, dimensions, etc."><?php echo htmlspecialchars($product['specifications'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Pricing & Inventory -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pricing & Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Price (GHS) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="price" class="form-control" 
                                       value="<?php echo $product['price']; ?>" required min="0.01">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Compare at Price (GHS)</label>
                                <input type="number" step="0.01" name="compare_price" class="form-control" 
                                       value="<?php echo $product['compare_price'] ?? ''; ?>" min="0.01"
                                       placeholder="Original price for discount">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" name="stock_quantity" class="form-control" 
                                       value="<?php echo $product['stock_quantity']; ?>" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Low Stock Threshold</label>
                                <input type="number" name="low_stock_threshold" class="form-control" 
                                       value="<?php echo $product['low_stock_threshold'] ?? 5; ?>" min="0">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">SKU</label>
                            <input type="text" name="sku" class="form-control" 
                                   value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>"
                                   placeholder="Stock keeping unit">
                        </div>
                    </div>
                </div>
                
                <!-- Product Images -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Product Images</h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // Get images from both product_images table and products.images JSON
                        $allImages = [];
                        
                        // Add images from product_images table
                        foreach ($productImages as $img) {
                            // Use image as-is, imageUrl() will handle path fixing
                            $imgPath = $img['image_url'];
                            $allImages[] = [
                                'id' => $img['id'],
                                'url' => $imgPath,
                                'source' => 'table'
                            ];
                        }
                        
                        // Add images from products.images JSON (only if not already in table)
                        $jsonImages = json_decode($product['images'] ?? '[]', true);
                        $tableImageUrls = array_column($productImages, 'image_url');
                        
                        foreach ($jsonImages as $index => $imgPath) {
                            // Skip if already in table
                            if (in_array($imgPath, $tableImageUrls)) {
                                continue;
                            }
                            
                            // Use image as-is, imageUrl() will handle path fixing
                            $allImages[] = [
                                'id' => null,
                                'url' => $imgPath,
                                'source' => 'json',
                                'json_index' => $index
                            ];
                        }
                        ?>
                        <?php if (!empty($allImages)): ?>
                            <div class="row mb-3">
                                <?php foreach ($allImages as $img): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="position-relative">
                                        <img src="<?php echo imageUrl($img['url'], 200, 200); ?>" 
                                             class="img-thumbnail w-100" style="height: 150px; object-fit: cover;"
                                             onerror="this.src='<?php echo BASE_URL; ?>/assets/images/products/default.jpg';">
                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                                onclick="deleteImage(<?php echo $img['id'] ?? 'null'; ?>, <?php echo isset($img['json_index']) ? $img['json_index'] : 'null'; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Add More Images</label>
                            <input type="file" name="images[]" class="form-control" accept="image/*" multiple>
                            <small class="form-text text-muted">You can select multiple images</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Category & Status -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Category & Status</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" 
                                            <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(html_entity_decode($cat['name'], ENT_QUOTES, 'UTF-8')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                                   <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">Active</label>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input type="checkbox" name="is_featured" class="form-check-input" id="is_featured" 
                                   <?php echo $product['is_featured'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">Featured Product</label>
                        </div>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Update Product
                    </button>
                    <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products.php<?php echo htmlspecialchars($returnQuery); ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
(function() {
    const baseQuery = <?php 
        $imageDeleteParams = array_merge(
            ['id' => $productId],
            $returnParams
        );
        echo json_encode(http_build_query($imageDeleteParams));
    ?>;
    
    window.deleteImage = function(imageId, jsonIndex) {
        if (!confirm('Are you sure you want to delete this image?')) {
            return;
        }
        
        let url = '?' + baseQuery;
        if (imageId !== null && imageId > 0) {
            url += '&delete_image=' + imageId;
        } else if (jsonIndex !== null && jsonIndex >= 0) {
            url += '&delete_image=0&json_index=' + jsonIndex;
        }
        
        window.location.href = url;
    };
})();
</script>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Edit Product - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';







