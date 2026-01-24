<?php
/**
 * Add Product - Admin
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = false;

// Get categories
$stmt = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll();

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $slug = sanitize($_POST['slug'] ?? '');
        $sku = sanitize($_POST['sku'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = $_POST['description'] ?? '';
        $shortDescription = sanitize($_POST['short_description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $comparePrice = !empty($_POST['compare_price']) ? floatval($_POST['compare_price']) : null;
        $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
        $lowStockThreshold = intval($_POST['low_stock_threshold'] ?? 10);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
        
        // Validation
        if (empty($name)) {
            $errors[] = 'Product name is required.';
        }
        
        if (empty($slug)) {
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
        }
        
        // Check slug uniqueness
        $stmt = $conn->prepare("SELECT id FROM products WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetch()) {
            $slug .= '-' . time();
        }
        
        if ($categoryId <= 0) {
            $errors[] = 'Please select a category.';
        }
        
        if ($price <= 0) {
            $errors[] = 'Price must be greater than 0.';
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
                    
                    $result = uploadImage($file, PRODUCT_IMAGE_PATH);
                    if ($result['success']) {
                        $uploadedImages[] = $result['filename'];
                    }
                }
            }
        }
        
        if (empty($errors)) {
            try {
                $imagesJson = json_encode($uploadedImages);
                
                $stmt = $conn->prepare("
                    INSERT INTO products (
                        name, slug, sku, category_id, description, short_description,
                        price, compare_price, stock_quantity, low_stock_threshold,
                        images, is_active, is_featured, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ");
                
                $stmt->execute([
                    $name,
                    $slug,
                    $sku ?: null,
                    $categoryId,
                    $description,
                    $shortDescription,
                    $price,
                    $comparePrice,
                    $stockQuantity,
                    $lowStockThreshold,
                    $imagesJson,
                    $isActive,
                    $isFeatured
                ]);
                
                $productId = $conn->lastInsertId();
                
                logAdminAction($_SESSION['admin_id'], 'add_product', 'products', $productId);
                
                redirect('/admin/ecommerce/products.php', 'Product added successfully!', 'success');
                
            } catch (Exception $e) {
                error_log("Add Product Error: " . $e->getMessage());
                $errors[] = 'Failed to add product: ' . $e->getMessage();
            }
        }
    }
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Add New Product</h1>
    <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Products
    </a>
</div>

<form method="POST" action="" enctype="multipart/form-data" class="needs-validation" novalidate>
        <div class="row">
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Basic Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                            <div class="invalid-feedback">Please provide a product name.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug (URL-friendly)</label>
                            <input type="text" class="form-control" id="slug" name="slug" 
                                   value="<?php echo htmlspecialchars($_POST['slug'] ?? ''); ?>"
                                   placeholder="auto-generated if empty">
                            <small class="form-text text-muted">Leave empty to auto-generate from product name</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sku" class="form-label">SKU</label>
                            <input type="text" class="form-control" id="sku" name="sku" 
                                   value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(html_entity_decode($category['name'], ENT_QUOTES, 'UTF-8')); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a category.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="short_description" class="form-label">Short Description</label>
                            <textarea class="form-control" id="short_description" name="short_description" rows="3"><?php echo htmlspecialchars($_POST['short_description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="6"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Pricing & Inventory</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="price" class="form-label">Price (GHS) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" 
                                       value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required min="0.01">
                                <div class="invalid-feedback">Please provide a valid price.</div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="compare_price" class="form-label">Compare at Price (GHS)</label>
                                <input type="number" step="0.01" class="form-control" id="compare_price" name="compare_price" 
                                       value="<?php echo htmlspecialchars($_POST['compare_price'] ?? ''); ?>" min="0.01">
                                <small class="form-text text-muted">Original price (for showing discounts)</small>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="stock_quantity" class="form-label">Stock Quantity</label>
                                <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" 
                                       value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? '0'); ?>" min="0">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" 
                                       value="<?php echo htmlspecialchars($_POST['low_stock_threshold'] ?? '10'); ?>" min="0">
                                <small class="form-text text-muted">Alert when stock reaches this level</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Product Images</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="images" class="form-label">Upload Images</label>
                            <input type="file" class="form-control" id="images" name="images[]" 
                                   accept="image/*" multiple>
                            <small class="form-text text-muted">You can select multiple images. First image will be the main product image.</small>
                        </div>
                        <div id="imagePreview" class="mt-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                   <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Active (Product will be visible to customers)
                            </label>
                        </div>
                        
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="is_featured" name="is_featured" 
                                   <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_featured">
                                Featured Product (Show on homepage)
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <?php $csrfToken = generateCSRFToken(); ?>
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-2">
                            <i class="fas fa-save"></i> Save Product
                        </button>
                        <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products.php" class="btn btn-outline-secondary w-100">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>

<script>
// Auto-generate slug from name
document.getElementById('name').addEventListener('input', function() {
    const slugInput = document.getElementById('slug');
    if (!slugInput.value) {
        slugInput.value = this.value.toLowerCase()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }
});

// Image preview
document.getElementById('images').addEventListener('change', function(e) {
    const preview = document.getElementById('imagePreview');
    preview.innerHTML = '';
    
    if (this.files) {
        Array.from(this.files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'img-thumbnail me-2 mb-2';
                    img.style.maxWidth = '150px';
                    img.style.maxHeight = '150px';
                    preview.appendChild(img);
                };
                reader.readAsDataURL(file);
            }
        });
    }
});
</script>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

