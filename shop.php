<?php
/**
 * Shop/Product Listing Page - Modern Mobile-First Design
 * ThinQShopping Platform
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

// Helper function to get first product image (same as homepage)
function getProductImage($product) {
    if (!empty($product['images'])) {
        $images = json_decode($product['images'], true);
        if (!empty($images) && is_array($images)) {
            $imagePath = str_replace('\\/', '/', $images[0]);
            if (strpos($imagePath, '/') === false && strpos($imagePath, 'assets') === false) {
                $imagePath = 'assets/images/products/' . $imagePath;
            }
            return BASE_URL . '/' . $imagePath;
        }
    }
    return BASE_URL . '/assets/images/placeholder-product.jpg';
}

// Get filters from URL
$category = isset($_GET['category']) && is_numeric($_GET['category']) ? intval($_GET['category']) : null;
$search = isset($_GET['search']) && trim($_GET['search']) !== '' ? trim($_GET['search']) : null;
$minPrice = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? floatval($_GET['min_price']) : null;
$maxPrice = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? floatval($_GET['max_price']) : null;
$deals = isset($_GET['deals']) && $_GET['deals'] == '1';
$sortBy = $_GET['sort'] ?? 'newest';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 24;
$offset = ($page - 1) * $perPage;

// Get all categories for filter
$categories = [];
try {
    $stmt = $conn->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        WHERE c.is_active = 1 AND c.parent_id IS NULL 
        GROUP BY c.id 
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Build query
$where = ["p.is_active = 1"];
$params = [];

if ($category) {
    $where[] = "p.category_id = ?";
    $params[] = $category;
}

if ($search) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($minPrice !== null) {
    $where[] = "p.price >= ?";
    $params[] = $minPrice;
}

if ($maxPrice !== null) {
    $where[] = "p.price <= ?";
    $params[] = $maxPrice;
}

if ($deals) {
    $where[] = "p.compare_price > p.price";
}

$whereClause = implode(' AND ', $where);

// Build sort order
$orderBy = "p.created_at DESC";
switch ($sortBy) {
    case 'newest':
        $orderBy = "p.created_at DESC";
        break;
    case 'oldest':
        $orderBy = "p.created_at ASC";
        break;
    case 'price_low':
        $orderBy = "p.price ASC";
        break;
    case 'price_high':
        $orderBy = "p.price DESC";
        break;
    case 'name':
        $orderBy = "p.name ASC";
        break;
    case 'popular':
        $orderBy = "order_count DESC, p.created_at DESC";
        break;
}

// Get total count
$countSql = "SELECT COUNT(DISTINCT p.id) as total FROM products p WHERE $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetch()['total'];
$totalPages = ceil($totalProducts / $perPage);

// Get products
$products = [];
try {
    $sql = "
        SELECT p.*, c.name as category_name,
               COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
               COALESCE((SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as review_count,
               COALESCE((SELECT COUNT(*) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE oi.product_id = p.id AND o.status != 'cancelled'), 0) as order_count,
               CASE WHEN p.compare_price > p.price THEN ROUND(((p.compare_price - p.price) / p.compare_price * 100), 0) ELSE 0 END as discount_percent
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereClause
        ORDER BY $orderBy
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
}

// Get selected category name
$selectedCategoryName = null;
if ($category) {
    foreach ($categories as $cat) {
        if ($cat['id'] == $category) {
            $selectedCategoryName = $cat['name'];
            break;
        }
    }
}

// Page title
$pageTitle = 'Shop';
if ($selectedCategoryName) {
    $pageTitle = $selectedCategoryName . ' - Shop';
} elseif ($search) {
    $pageTitle = 'Search: ' . htmlspecialchars($search) . ' - Shop';
} elseif ($deals) {
    $pageTitle = 'Deals - Shop';
}
$pageTitle .= ' - ' . APP_NAME;

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Styles now loaded from assets/css/main-new.css -->
<link rel="stylesheet" href="<?php echo asset('assets/css/pages/shop.css'); ?>?v=<?php echo time(); ?>">

<div class="shop-page">
    <!-- Header -->
    <header class="shop-header">
        <div class="shop-header__title-row">
            <h1 class="shop-header__title">
                <?php if ($selectedCategoryName): ?>
                    <?php echo htmlspecialchars($selectedCategoryName); ?>
                <?php elseif ($search): ?>
                    Search Results
                <?php elseif ($deals): ?>
                    Hot Deals
                <?php else: ?>
                    Shop All
                <?php endif; ?>
            </h1>
        </div>
        
        <!-- Search -->
        <div class="shop-search">
            <form action="<?php echo BASE_URL; ?>/shop.php" method="GET" class="search-input-group">
                <?php if ($category): ?>
                    <input type="hidden" name="category" value="<?php echo $category; ?>">
                <?php endif; ?>
                <input type="search" name="search" placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($search ?? ''); ?>">
                <button type="submit" class="btn btn--primary btn--icon-sm"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </header>

    <!-- Horizontal Scrollable Categories -->
    <div class="filters-section">
        <a href="<?php echo BASE_URL; ?>/shop.php" class="filter-chip <?php echo !$category && !$deals ? 'active' : ''; ?>">
            All
        </a>
        <a href="<?php echo BASE_URL; ?>/shop.php?deals=1" class="filter-chip <?php echo $deals ? 'active' : ''; ?>">
            🔥 Hot Deals
        </a>
        <?php if (!empty($categories)): ?>
            <?php foreach ($categories as $cat): ?>
                <a href="<?php echo BASE_URL; ?>/shop.php?category=<?php echo $cat['id']; ?>" 
                   class="filter-chip <?php echo $category == $cat['id'] ? 'active' : ''; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Sort Bar -->
    <div class="sort-bar">
        <a href="<?php echo BASE_URL; ?>/shop.php?sort=newest<?php echo $category ? '&category='.$category : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
           class="sort-chip <?php echo $sortBy == 'newest' ? 'active' : ''; ?>">
            Newest
        </a>
        <a href="<?php echo BASE_URL; ?>/shop.php?sort=popular<?php echo $category ? '&category='.$category : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
           class="sort-chip <?php echo $sortBy == 'popular' ? 'active' : ''; ?>">
            Popular
        </a>
        <a href="<?php echo BASE_URL; ?>/shop.php?sort=price_low<?php echo $category ? '&category='.$category : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
           class="sort-chip <?php echo $sortBy == 'price_low' ? 'active' : ''; ?>">
            Price: Low to High
        </a>
        <a href="<?php echo BASE_URL; ?>/shop.php?sort=price_high<?php echo $category ? '&category='.$category : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
           class="sort-chip <?php echo $sortBy == 'price_high' ? 'active' : ''; ?>">
            Price: High to Low
        </a>
        <a href="<?php echo BASE_URL; ?>/shop.php?sort=name<?php echo $category ? '&category='.$category : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" 
           class="sort-chip <?php echo $sortBy == 'name' ? 'active' : ''; ?>">
            Name A-Z
        </a>
    </div>

    <!-- Products Grid -->
    <main class="products-container">
        <?php if (!empty($products)): ?>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-card__image">
                            <a href="<?php echo BASE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>">
                                <img src="<?php echo getProductImage($product); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     loading="lazy">
                            </a>
                            <?php if (!empty($product['discount_percent'])): ?>
                                <div class="product-card__discount-badge">-<?php echo $product['discount_percent']; ?>%</div>
                            <?php endif; ?>
                            
                            <div class="product-card__actions">
                                <button type="button" class="btn-quick-add" onclick="openQuickView(<?php echo $product['id']; ?>)" title="Quick Add">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="product-card__content">
                            <?php if (!empty($product['category_name'])): ?>
                                <div class="product-card__category"><?php echo htmlspecialchars($product['category_name']); ?></div>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" class="product-card__name-link">
                                <div class="product-card__name"><?php echo htmlspecialchars($product['name']); ?></div>
                            </a>
                            <?php if (!empty($product['avg_rating'])): ?>
                                <div class="product-card__rating">
                                    <span class="product-card__stars">★★★★★</span>
                                    <span class="product-card__rating-value"><?php echo number_format($product['avg_rating'], 1); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="product-card__price-section">
                                <div class="product-card__price-row">
                                    <span class="product-card__price"><?php echo formatCurrency($product['price']); ?></span>
                                    <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                                        <span class="product-card__price--old"><?php echo formatCurrency($product['compare_price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($product['stock_quantity'] > 0): ?>
                                    <div class="product-card__stock product-card__stock--in-stock">In Stock</div>
                                <?php else: ?>
                                    <div class="product-card__stock product-card__stock--out-of-stock">Out of Stock</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?><?php echo $category ? '&category='.$category : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $sortBy != 'newest' ? '&sort='.$sortBy : ''; ?>" 
                       class="pagination-btn">← Previous</a>
                <?php endif; ?>
                
                <span class="pagination-btn active">Page <?php echo $page; ?> of <?php echo $totalPages; ?></span>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page + 1; ?><?php echo $category ? '&category='.$category : ''; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $sortBy != 'newest' ? '&sort='.$sortBy : ''; ?>" 
                       class="pagination-btn">Next →</a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <div class="empty-text">
                    <?php if ($search): ?>
                        No products found for "<?php echo htmlspecialchars($search); ?>"
                    <?php else: ?>
                        No products available in this category
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
