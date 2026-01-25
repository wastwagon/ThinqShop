<?php
/**
 * Admin Products Management
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
    
    // Preserve current query parameters
    $currentPage = max(1, intval($_GET['page'] ?? 1));
    $currentFilter = $_GET['filter'] ?? 'all';
    $currentSearch = $_GET['search'] ?? '';
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $redirectParams = http_build_query([
            'page' => $currentPage,
            'filter' => $currentFilter,
            'search' => $currentSearch
        ]);
        redirect('/admin/ecommerce/products.php?' . $redirectParams, 'Invalid security token.', 'danger');
    }
    
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        
        logAdminAction($_SESSION['admin_id'], 'delete_product', 'products', $productId);
        
        // Build redirect URL with preserved parameters
        $redirectParams = http_build_query([
            'page' => $currentPage,
            'filter' => $currentFilter,
            'search' => $currentSearch
        ]);
        redirect('/admin/ecommerce/products.php?' . $redirectParams, 'Product deleted successfully.', 'success');
    } catch (Exception $e) {
        error_log("Delete Product Error: " . $e->getMessage());
        
        // Build redirect URL with preserved parameters
        $redirectParams = http_build_query([
            'page' => $currentPage,
            'filter' => $currentFilter,
            'search' => $currentSearch
        ]);
        redirect('/admin/ecommerce/products.php?' . $redirectParams, 'Failed to delete product.', 'danger');
    }
}

// Handle toggle active status
if (isset($_GET['toggle']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = intval($_GET['toggle']);
    
    // Preserve current query parameters
    $currentPage = max(1, intval($_GET['page'] ?? 1));
    $currentFilter = $_GET['filter'] ?? 'all';
    $currentSearch = $_GET['search'] ?? '';
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $redirectParams = http_build_query([
            'page' => $currentPage,
            'filter' => $currentFilter,
            'search' => $currentSearch
        ]);
        redirect('/admin/ecommerce/products.php?' . $redirectParams, 'Invalid security token.', 'danger');
    }
    
    try {
        $stmt = $conn->prepare("UPDATE products SET is_active = NOT is_active WHERE id = ?");
        $stmt->execute([$productId]);
        
        logAdminAction($_SESSION['admin_id'], 'toggle_product_status', 'products', $productId);
        
        // Build redirect URL with preserved parameters
        $redirectParams = http_build_query([
            'page' => $currentPage,
            'filter' => $currentFilter,
            'search' => $currentSearch
        ]);
        redirect('/admin/ecommerce/products.php?' . $redirectParams, 'Product status updated.', 'success');
    } catch (Exception $e) {
        error_log("Toggle Product Status Error: " . $e->getMessage());
        
        // Build redirect URL with preserved parameters
        $redirectParams = http_build_query([
            'page' => $currentPage,
            'filter' => $currentFilter,
            'search' => $currentSearch
        ]);
        redirect('/admin/ecommerce/products.php?' . $redirectParams, 'Failed to update product status.', 'danger');
    }
}

// Get filter and sanitize inputs
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Reset to page 1 if search is performed (new search)
if (!empty($search) && !isset($_GET['page'])) {
    $page = 1;
    $offset = 0;
}

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
    // Search in product name (case-insensitive) and SKU
    // Search for both the original term and HTML-encoded version to handle encoded data
    $searchTerm = trim($search);
    $searchParam = "%" . strtolower($searchTerm) . "%";
    $searchParamEncoded = "%" . strtolower(htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8')) . "%";
    $where[] = "(LOWER(p.name) LIKE ? OR LOWER(p.name) LIKE ? OR LOWER(p.sku) LIKE ?)";
    $params[] = $searchParam;
    $params[] = $searchParamEncoded;
    $params[] = $searchParam;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
try {
    $countSql = "SELECT COUNT(*) as total FROM products $whereClause";
    $countStmt = $conn->prepare($countSql);
    if (!$countStmt) {
        throw new Exception("Failed to prepare count query: " . implode(", ", $conn->errorInfo()));
    }
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $perPage);
} catch (Exception $e) {
    error_log("Error getting product count: " . $e->getMessage());
    $totalProducts = 0;
    $totalPages = 0;
}

// Get products
try {
    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            $whereClause
            ORDER BY p.created_at DESC 
            LIMIT $perPage OFFSET $offset";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare products query: " . implode(", ", $conn->errorInfo()));
    }
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error getting products: " . $e->getMessage());
    $products = [];
}

$pageTitle = 'Products - Admin - ' . APP_NAME;

// Use admin layout
ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Products Management</h2>
        <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products/add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Product
        </a>
    </div>
    
    <!-- Filters and Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="1">
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
                    <table class="table table-hover">
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
                                try {
                                $images = json_decode($product['images'] ?? '[]', true);
                                // Use the first image if available, otherwise use default
                                // imageUrl() will handle path fixing
                                $mainImage = (!empty($images) && !empty($images[0])) ? $images[0] : 'default.jpg';
                                    $imageUrl = imageUrl($mainImage, 50, 50);
                                } catch (Exception $e) {
                                    error_log("Error processing product image: " . $e->getMessage());
                                    $imageUrl = BASE_URL . '/assets/images/products/default.jpg';
                                }
                            ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                         alt="<?php echo htmlspecialchars(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')); ?>" 
                                         class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;"
                                         onerror="this.src='<?php echo BASE_URL; ?>/assets/images/products/default.jpg';"
                                         loading="lazy">
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars(html_entity_decode($product['name'], ENT_QUOTES, 'UTF-8')); ?></strong>
                                    <?php if ($product['is_featured']): ?>
                                        <span class="badge bg-warning ms-1">Featured</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars(html_entity_decode($product['category_name'] ?? 'Uncategorized', ENT_QUOTES, 'UTF-8')); ?></td>
                                <td><?php echo formatCurrency($product['price']); ?></td>
                                <td>
                                    <?php 
                                    $stockClass = 'success';
                                    if ($product['stock_quantity'] == 0) {
                                        $stockClass = 'danger';
                                    } elseif ($product['stock_quantity'] <= $product['low_stock_threshold']) {
                                        $stockClass = 'warning';
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $stockClass; ?>">
                                        <?php echo $product['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $product['is_active'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $product['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <?php
                                        // Build edit link query with pagination/filter/search params
                                        $editParams = [
                                            'id' => $product['id'],
                                            'page' => $page,
                                            'filter' => $filter,
                                            'search' => $search
                                        ];
                                        $editQuery = http_build_query($editParams);
                                        ?>
                                        <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products-edit.php?<?php echo htmlspecialchars($editQuery); ?>" 
                                           class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php
                                        // Build toggle link query with pagination/filter/search params
                                        $toggleParams = [
                                            'toggle' => $product['id'],
                                            'page' => $page,
                                            'filter' => $filter,
                                            'search' => $search
                                        ];
                                        $toggleQuery = http_build_query($toggleParams);
                                        
                                        // Build delete link query with pagination/filter/search params
                                        $deleteParams = [
                                            'delete' => $product['id'],
                                            'page' => $page,
                                            'filter' => $filter,
                                            'search' => $search
                                        ];
                                        $deleteQuery = http_build_query($deleteParams);
                                        ?>
                                        <form method="POST" action="?<?php echo htmlspecialchars($toggleQuery); ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <button type="submit" class="btn btn-outline-<?php echo $product['is_active'] ? 'warning' : 'success'; ?>" 
                                                    title="<?php echo $product['is_active'] ? 'Deactivate' : 'Activate'; ?>"
                                                    onclick="return confirm('Are you sure?');">
                                                <i class="fas fa-<?php echo $product['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="?<?php echo htmlspecialchars($deleteQuery); ?>" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <button type="submit" class="btn btn-outline-danger" title="Delete"
                                                    onclick="return confirm('Are you sure you want to delete this product? This action cannot be undone.');">
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
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php elseif ($i == $page - 3 || $i == $page + 3): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
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
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>

