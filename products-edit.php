<?php
/**
 * Edit Product
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$productId = intval($_GET['id'] ?? 0);

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
                    
                    $result = uploadImage($file, UPLOAD_IMAGE_PATH);
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
                
                // Add new images
                if (!empty($uploadedImages)) {
                    $sortOrder = count($productImages);
                    foreach ($uploadedImages as $image) {
                        $stmt = $conn->prepare("
                            INSERT INTO product_images (product_id, image_url, sort_order, created_at)
                            VALUES (?, ?, ?, NOW())
                        ");
                        $stmt->execute([$productId, $image, ++$sortOrder]);
                    }
                }
                
                $conn->commit();
                
                logAdminAction($_SESSION['admin_id'], 'update_product', 'products', $productId);
                redirect('/admin/ecommerce/products.php', 'Product updated successfully!', 'success');
                
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
    <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
        
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
                        <?php if (!empty($productImages)): ?>
                            <div class="row mb-3">
                                <?php foreach ($productImages as $img): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="position-relative">
                                        <img src="<?php echo asset('uploads/images/' . $img['image_url']); ?>" 
                                             class="img-thumbnail w-100" style="height: 150px; object-fit: cover;">
                                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                                onclick="deleteImage(<?php echo $img['id']; ?>)">
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
                    <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products.php" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function deleteImage(imageId) {
    if (confirm('Are you sure you want to delete this image?')) {
        window.location.href = '?delete_image=' + imageId + '&id=<?php echo $productId; ?>';
    }
}
</script>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Edit Product - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';

