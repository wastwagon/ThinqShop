<?php
/**
 * User Wishlist Page
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Handle remove from wishlist
if (isset($_GET['remove']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $productId = intval($_GET['remove']);
    if ($productId > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            redirect('/user/wishlist.php', 'Product removed from wishlist.', 'success');
        } catch (Exception $e) {
            error_log("Remove from wishlist error: " . $e->getMessage());
            redirect('/user/wishlist.php', 'Error removing product from wishlist.', 'danger');
        }
    }
}

// Get wishlist items
$stmt = $conn->prepare("
    SELECT w.*, p.*, c.name as category_name
    FROM wishlist w
    INNER JOIN products p ON w.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE w.user_id = ? AND p.is_active = 1
    ORDER BY w.created_at DESC
");
$stmt->execute([$userId]);
$wishlistItems = $stmt->fetchAll();

$pageTitle = 'My Wishlist - ' . APP_NAME;

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-wishlist.css'
];

ob_start();
?>



<?php if (empty($wishlistItems)): ?>
    <div class="card border-1 shadow-sm rounded-4 text-center py-5 bg-white">
        <div class="card-body py-5">
            <div class="mb-4">
                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; border: 1px dashed #cbd5e1;">
                    <i class="far fa-heart fa-2x text-muted opacity-30"></i>
                </div>
            </div>
            <h6 class="fw-bold text-dark mb-1">Wishlist is empty</h6>
            <p class="text-muted mb-4 mx-auto small" style="max-width: 320px;">
                Start browsing to add items to your wishlist.
            </p>
            <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-primary btn-premium px-5 py-3">
                Start Shopping
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="wishlist-grid">
        <?php foreach ($wishlistItems as $item): 
            $images = json_decode($item['images'] ?? '[]', true);
            $mainImage = (!empty($images) && !empty($images[0])) ? $images[0] : 'default.jpg';
            $hasDiscount = $item['compare_price'] && $item['compare_price'] > $item['price'];
        ?>
        <div class="product-card-premium shadow-sm">
            <div class="product-img-container">
                <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo $item['slug']; ?>">
                    <img src="<?php echo imageUrl($mainImage, 600, 600); ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         loading="lazy">
                </a>
                <button type="button" class="wishlist-remove-btn" onclick="removeFromWishlist(<?php echo $item['product_id']; ?>)" title="Remove Item">
                    <i class="fas fa-heart"></i>
                </button>
                <?php if ($hasDiscount): ?>
                    <span class="badge bg-danger position-absolute top-0 start-0 m-3 rounded-pill px-3 py-2 fw-800 x-small">
                        SAVE <?php echo round((($item['compare_price'] - $item['price']) / $item['compare_price']) * 100); ?>%
                    </span>
                <?php endif; ?>
            </div>
            
            <div class="product-info-premium">
                <div class="category-tag"><?php echo htmlspecialchars($item['category_name'] ?? 'In Stock'); ?></div>
                <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo $item['slug']; ?>" class="product-title">
                    <?php echo htmlspecialchars($item['name']); ?>
                </a>
                
                <div class="price-section">
                    <span class="current-price"><?php echo formatCurrency($item['price']); ?></span>
                    <?php if ($hasDiscount): ?>
                        <span class="old-price"><?php echo formatCurrency($item['compare_price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="mt-auto">
                    <?php if ($item['stock_quantity'] > 0): ?>
                        <form method="POST" action="<?php echo BASE_URL; ?>/modules/ecommerce/cart/add.php" class="mb-0">
                            <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <button type="submit" class="btn btn-primary w-100 btn-add-cart-premium">
                                <i class="fas fa-shopping-bag me-2"></i> Add to Cart
                            </button>
                        </form>
                    <?php else: ?>
                        <button type="button" class="btn btn-light disabled w-100 btn-add-cart-premium border" disabled>
                            Out of Stock
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
function removeFromWishlist(productId) {
    if (confirm('Remove this exquisite item from your wishlist?')) {
        window.location.href = '?remove=' + productId;
    }
}
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'My Selection - ' . APP_NAME;
include __DIR__ . '/../includes/layouts/user-layout.php';
?>

