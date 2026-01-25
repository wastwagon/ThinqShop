<?php
/**
 * Admin Products Management - Using New Layout
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle delete
if (isset($_GET['delete']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_GET['delete']);
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/ecommerce/products.php', 'Invalid security token.', 'danger');
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        
        logAdminAction($_SESSION['admin_id'], 'delete_product', 'products', $productId);
        redirect('/admin/ecommerce/products.php', 'Product deleted successfully.', 'success');
    } catch (Exception $e) {
        error_log("Delete Product Error: " . $e->getMessage());
        redirect('/admin/ecommerce/products.php', 'Failed to delete product.', 'danger');
    }
}

// Handle toggle active status
if (isset($_GET['toggle']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_GET['toggle']);
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/ecommerce/products.php', 'Invalid security token.', 'danger');
    }
    
    try {
        $stmt = $conn->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$productId]);
        
        logAdminAction($_SESSION['admin_id'], 'toggle_product_status', 'products', $productId);
        redirect('/admin/ecommerce/products.php', 'Product status updated.', 'success');
    } catch (Exception $e) {
        error_log("Toggle Product Status Error: " . $e->getMessage());
        redirect('/admin/ecommerce/products.php', 'Failed to update product status.', 'danger');
    }
}

// Get filter
$filter = $_GET['filter'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($filter === 'low_stock') {
    $where[] = "stock_quantity <= low_stock_threshold";
}

if ($filter === 'out_of_stock') {
    $where[] = "stock_quantity = 0";
}

if ($filter === 'active') {
    $where[] = "is_active = 1";
}

if ($filter === 'inactive') {
    $where[] = "is_active = 0";
}

if ($search) {
    $where[] = "(name LIKE ? OR sku LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM products $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetch()['total'];
$totalPages = ceil($totalProducts / $perPage);

// Get products
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        $whereClause
        ORDER BY p.created_at DESC 
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Products Management</h1>
    <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products/add.php" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add New Product
    </a>
</div>

<!-- Filters and Search -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">Filter</label>
                <select name="filter" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>All Products</option>
                    <option value="active" <?php echo $filter === 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo $filter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    <option value="low_stock" <?php echo $filter === 'low_stock' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="out_of_stock" <?php echo $filter === 'out_of_stock' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="text" name="search" class="form-control" 
                       placeholder="Search by name or SKU" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Search
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card">
    <div class="card-body">
        <?php if (empty($products)): ?>
            <p class="text-muted text-center py-5">No products found.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Product Name</th>
                            <th>SKU</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): 
                            $images = json_decode($product['images'] ?? '[]', true);
                            $mainImage = !empty($images) ? $images[0] : 'assets/images/products/default.jpg';
                        ?>
                        <tr>
                            <td>
                                <img src="<?php echo imageUrl($mainImage, 50, 50); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td><?php echo htmlspecialchars(html_entity_decode($product['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8')); ?></td>
                            <td><?php echo formatCurrency($product['price']); ?></td>
                            <td>
                                <?php 
                                if ($product['stock_quantity'] <= 0) {
                                    echo '<span class="stock-badge out-of-stock">Out of Stock</span>';
                                } elseif ($product['stock_quantity'] <= $product['low_stock_threshold']) {
                                    echo '<span class="stock-badge" style="background: rgba(255, 193, 7, 0.1); color: var(--warning-color);">' . $product['stock_quantity'] . ' (Low)</span>';
                                } else {
                                    echo '<span class="stock-badge in-stock">' . $product['stock_quantity'] . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($product['is_active']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products-edit.php?id=<?php echo $product['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="?toggle=<?php echo $product['id']; ?>" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-warning" title="Toggle Status">
                                            <i class="fas fa-<?php echo $product['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="?delete=<?php echo $product['id']; ?>" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Products pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Products Management - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';







