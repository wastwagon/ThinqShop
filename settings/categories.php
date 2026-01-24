<?php
/**
 * Product Categories Management
 * ThinQShopping Platform
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = false;
$action = $_GET['action'] ?? 'add';
$categoryId = intval($_GET['id'] ?? 0);

// Get flash message if any
$flash = getFlashMessage();
if ($flash) {
    if ($flash['type'] === 'success') {
        $success = true;
    } else {
        $errors[] = $flash['message'];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    $debugMsg = "Category Form POST at " . date('Y-m-d H:i:s') . ": " . print_r($_POST, true);
    error_log($debugMsg);
    
    if (!isset($_POST['csrf_token'])) {
        $errors[] = 'CSRF token is missing. Please refresh the page and try again.';
        error_log("CSRF token missing in POST");
    } elseif (!verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please refresh the page and try again.';
        $tokenDebug = "CSRF token verification failed. Session token: " . ($_SESSION[CSRF_TOKEN_NAME] ?? 'not set') . ", POST token: " . ($_POST['csrf_token'] ?? 'not set');
        error_log($tokenDebug);
    } else {
        $formAction = $_POST['action'] ?? 'add';
        error_log("Form action received: " . $formAction);
        
        if ($formAction === 'add') {
            $name = strip_tags(trim($_POST['name'] ?? ''));
            $description = strip_tags(trim($_POST['description'] ?? ''));
            $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name)) {
                $errors[] = 'Category name is required.';
            } else {
                // Generate slug
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                $slug = trim($slug, '-');
                
                // Check if slug exists
                $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ?");
                $stmt->execute([$slug]);
                if ($stmt->fetch()) {
                    $slug .= '-' . time();
                }
                
                try {
                    $stmt = $conn->prepare("
                        INSERT INTO categories (name, slug, description, parent_id, is_active, created_at)
                        VALUES (?, ?, ?, ?, ?, NOW())
                    ");
                    $result = $stmt->execute([$name, $slug, $description, $parentId, $isActive]);
                    
                    if ($result) {
                        $newCategoryId = $conn->lastInsertId();
                        error_log("Category added successfully. ID: $newCategoryId, Name: $name");
                        
                        if (function_exists('logAdminAction')) {
                            logAdminAction($_SESSION['admin_id'], 'add_category', 'categories', $newCategoryId);
                        }
                        // Redirect to prevent form resubmission
                        redirect('/admin/settings/categories.php', 'Category "' . htmlspecialchars($name) . '" added successfully!', 'success');
                    } else {
                        throw new Exception('Database insert returned false');
                    }
                } catch (PDOException $e) {
                    error_log("Add Category PDO Error: " . $e->getMessage());
                    $errors[] = 'Failed to add category: ' . $e->getMessage();
                } catch (Exception $e) {
                    error_log("Add Category Error: " . $e->getMessage());
                    $errors[] = 'Failed to add category: ' . $e->getMessage();
                }
            }
        } elseif ($formAction === 'edit' && $categoryId > 0) {
            $name = strip_tags(trim($_POST['name'] ?? ''));
            $description = strip_tags(trim($_POST['description'] ?? ''));
            $parentId = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if (empty($name)) {
                $errors[] = 'Category name is required.';
            } else {
                // Generate slug
                $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
                $slug = trim($slug, '-');
                
                // Check if slug exists for another category
                $stmt = $conn->prepare("SELECT id FROM categories WHERE slug = ? AND id != ?");
                $stmt->execute([$slug, $categoryId]);
                if ($stmt->fetch()) {
                    $slug .= '-' . time();
                }
                
                try {
                    $stmt = $conn->prepare("
                        UPDATE categories 
                        SET name = ?, slug = ?, description = ?, parent_id = ?, is_active = ?, updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$name, $slug, $description, $parentId, $isActive, $categoryId]);
                    $success = true;
                    if (function_exists('logAdminAction')) {
                        logAdminAction($_SESSION['admin_id'], 'update_category', 'categories', $categoryId);
                    }
                    // Redirect after successful update
                    redirect('/admin/settings/categories.php', 'Category "' . htmlspecialchars($name) . '" updated successfully!', 'success');
                } catch (Exception $e) {
                    error_log("Update Category Error: " . $e->getMessage());
                    $errors[] = 'Failed to update category: ' . $e->getMessage();
                }
            }
        } elseif ($formAction === 'delete' && $categoryId > 0) {
            try {
                // Check if category has products (check both old category_id and new product_categories table)
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
                $stmt->execute([$categoryId]);
                $result = $stmt->fetch();
                $productCount = $result ? intval($result['count']) : 0;
                
                // Also check product_categories table if it exists
                try {
                    $tableCheck = $conn->query("SHOW TABLES LIKE 'product_categories'");
                    if ($tableCheck->rowCount() > 0) {
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM product_categories WHERE category_id = ?");
                        $stmt->execute([$categoryId]);
                        $result = $stmt->fetch();
                        $productCount += $result ? intval($result['count']) : 0;
                    }
                } catch (PDOException $e) {
                    // Table doesn't exist, ignore
                }
                
                if ($productCount > 0) {
                    $errors[] = "Cannot delete category. It has {$productCount} product(s) assigned. Please reassign products first.";
                } else {
                    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                    $stmt->execute([$categoryId]);
                    $success = true;
                    if (function_exists('logAdminAction')) {
                        logAdminAction($_SESSION['admin_id'], 'delete_category', 'categories', $categoryId);
                    }
                    redirect('/admin/settings/categories.php', 'Category deleted successfully!', 'success');
                }
            } catch (Exception $e) {
                error_log("Delete Category Error: " . $e->getMessage());
                $errors[] = 'Failed to delete category: ' . $e->getMessage();
            }
        }
    }
}

// Get all categories
// Count products from both old category_id field and new product_categories table (if it exists)
try {
    // Check if product_categories table exists
    $tableCheck = $conn->query("SHOW TABLES LIKE 'product_categories'");
    $hasProductCategoriesTable = $tableCheck->rowCount() > 0;
    
    if ($hasProductCategoriesTable) {
        $stmt = $conn->query("
            SELECT c.*, 
                   COALESCE(
                       (SELECT COUNT(*) FROM products WHERE category_id = c.id), 0
                   ) + COALESCE(
                       (SELECT COUNT(*) FROM product_categories WHERE category_id = c.id), 0
                   ) as product_count,
                   (SELECT name FROM categories WHERE id = c.parent_id) as parent_name
            FROM categories c
            GROUP BY c.id
            ORDER BY 
                CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END ASC,
                c.parent_id ASC,
                c.name ASC
        ");
    } else {
        // Fallback to old method if product_categories table doesn't exist
        $stmt = $conn->query("
            SELECT c.*, 
                   COUNT(p.id) as product_count,
                   (SELECT name FROM categories WHERE id = c.parent_id) as parent_name
            FROM categories c
            LEFT JOIN products p ON c.id = p.category_id
            GROUP BY c.id
            ORDER BY 
                CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END ASC,
                c.parent_id ASC,
                c.name ASC
        ");
    }
    $allCategories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Categories query error: " . $e->getMessage());
    // Fallback to simple query
    $stmt = $conn->query("
        SELECT c.*, 
               0 as product_count,
               (SELECT name FROM categories WHERE id = c.parent_id) as parent_name
        FROM categories c
        ORDER BY 
            CASE WHEN c.parent_id IS NULL THEN 0 ELSE 1 END ASC,
            c.parent_id ASC,
            c.name ASC
    ");
    $allCategories = $stmt->fetchAll();
}

// Get category for editing
$category = null;
if ($action === 'edit' && $categoryId > 0) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $category = $stmt->fetch();
    if (!$category) {
        redirect('/admin/settings/categories.php', 'Category not found.', 'danger');
    }
}

// Get parent categories (for dropdown)
$stmt = $conn->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND (id != ? OR ? = 0) ORDER BY name ASC");
$stmt->execute([$categoryId, $categoryId]);
$parentCategories = $stmt->fetchAll();

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Product Categories</h1>
    <a href="<?php echo BASE_URL; ?>/admin/settings/general.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left"></i> Back to Settings
    </a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> Category saved successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle"></i> <strong>Error:</strong>
        <ul class="mb-0 mt-2">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4">
        <!-- Add/Edit Category Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php echo $action === 'edit' ? 'Edit Category' : 'Add New Category'; ?>
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?><?php echo $action === 'edit' ? '?action=edit&id=' . $categoryId : ''; ?>" id="categoryForm">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="action" value="<?php echo $action === 'edit' ? 'edit' : 'add'; ?>">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="id" value="<?php echo $categoryId; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" 
                               value="<?php echo htmlspecialchars(html_entity_decode($category['name'] ?? '', ENT_QUOTES, 'UTF-8')); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Parent Category</label>
                        <select name="parent_id" class="form-select">
                            <option value="">None (Top Level)</option>
                            <?php foreach ($parentCategories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>"
                                        <?php echo ($category['parent_id'] ?? null) == $parent['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(html_entity_decode($parent['name'], ENT_QUOTES, 'UTF-8')); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input type="checkbox" name="is_active" class="form-check-input" id="is_active" 
                               <?php echo ($category['is_active'] ?? 1) ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> <?php echo $action === 'edit' ? 'Update Category' : 'Add Category'; ?>
                        </button>
                        <?php if ($action === 'edit'): ?>
                            <a href="<?php echo BASE_URL; ?>/admin/settings/categories.php" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-8">
        <!-- Categories List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Categories</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Parent</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allCategories)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        No categories found. Add your first category using the form on the left.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($allCategories as $cat): ?>
                                <tr>
                                    <td>
                                        <?php if ($cat['parent_id']): ?>
                                            <span class="text-muted me-2">└─</span>
                                        <?php endif; ?>
                                        <strong><?php echo htmlspecialchars(html_entity_decode($cat['name'], ENT_QUOTES, 'UTF-8')); ?></strong>
                                        <?php if (!empty($cat['description'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars(substr($cat['description'], 0, 50)); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($cat['parent_name']): ?>
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars(html_entity_decode($cat['parent_name'], ENT_QUOTES, 'UTF-8')); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?php echo intval($cat['product_count']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($cat['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?action=edit&id=<?php echo $cat['id']; ?>" 
                                               class="btn btn-outline-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (intval($cat['product_count']) == 0): ?>
                                            <a href="?action=delete&id=<?php echo $cat['id']; ?>" 
                                               class="btn btn-outline-danger" 
                                               onclick="return confirm('Are you sure you want to delete this category?');"
                                               title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Product Categories - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';

