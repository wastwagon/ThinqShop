<?php
/**
 * Product Detail Page - Modern Mobile-First Design
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

// Helper function to get product images
function getProductImages($product) {
    $images = [];
    if (!empty($product['images'])) {
        $imageData = json_decode($product['images'], true);
        if (!empty($imageData) && is_array($imageData)) {
            foreach ($imageData as $img) {
                $imagePath = str_replace('\\/', '/', $img);
                if (strpos($imagePath, '/') === false && strpos($imagePath, 'assets') === false) {
                    $imagePath = 'assets/images/products/' . $imagePath;
                }
                $images[] = BASE_URL . '/' . $imagePath;
            }
        }
    }
    if (empty($images)) {
        $images[] = BASE_URL . '/assets/images/placeholder-product.jpg';
    }
    return $images;
}

// Get product ID
$productId = isset($_GET['id']) && is_numeric($_GET['id']) ? intval($_GET['id']) : null;

if (!$productId) {
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

// Get product details
try {
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name, c.id as category_id,
               COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
               COALESCE((SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as review_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    
    if (!$product) {
        header('Location: ' . BASE_URL . '/shop.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Error fetching product: " . $e->getMessage());
    header('Location: ' . BASE_URL . '/shop.php');
    exit;
}

// Get product images
$productImages = getProductImages($product);

// Get product reviews
$reviews = [];
try {
    $stmt = $conn->prepare("
        SELECT pr.*, u.email as user_email
        FROM product_reviews pr
        LEFT JOIN users u ON pr.user_id = u.id
        WHERE pr.product_id = ? AND pr.is_approved = 1
        ORDER BY pr.created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$productId]);
    $reviews = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching reviews: " . $e->getMessage());
}

// Get related products (same category)
$relatedProducts = [];
try {
    $stmt = $conn->prepare("
        SELECT p.*, c.name as category_name,
               COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
               CASE WHEN p.compare_price > p.price THEN ROUND(((p.compare_price - p.price) / p.compare_price * 100), 0) ELSE 0 END as discount_percent
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.category_id = ? AND p.id != ? AND p.is_active = 1
        ORDER BY RAND()
        LIMIT 6
    ");
    $stmt->execute([$product['category_id'], $productId]);
    $relatedProducts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching related products: " . $e->getMessage());
}

// Calculate discount percentage
$discountPercent = 0;
if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']) {
    $discountPercent = round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100);
}

// Page title
$pageTitle = htmlspecialchars($product['name']) . ' - ' . APP_NAME;

// Styles
$additionalCSS = [
    'assets/css/pages/product-detail.css'
];

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Styles now loaded from assets/css/main-new.css -->

<div class="product-detail-page">
    <div class="container py-3">
        <!-- Breadcrumb -->
        <nav class="breadcrumb-nav mb-4">
            <a href="<?php echo BASE_URL; ?>">Home</a>
            <span>›</span>
            <a href="<?php echo BASE_URL; ?>/shop.php">Shop</a>
            <?php if (!empty($product['category_name'])): ?>
                <span>›</span>
                <a href="<?php echo BASE_URL; ?>/shop.php?category=<?php echo $product['category_id']; ?>">
                    <?php echo htmlspecialchars($product['category_name']); ?>
                </a>
            <?php endif; ?>
            <span>›</span>
            <span class="breadcrumb-current"><?php echo htmlspecialchars($product['name']); ?></span>
        </nav>

        <div class="product-detail-container">
            <!-- Image Gallery -->
            <div class="image-gallery">
                <div class="main-image" id="mainImage">
                    <img src="<?php echo $productImages[0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" id="mainImageEl">
                    <?php if ($discountPercent > 0): ?>
                        <div class="discount-badge-large">-<?php echo $discountPercent; ?>%</div>
                    <?php endif; ?>
                </div>
                
                <?php if (count($productImages) > 1): ?>
                <div class="image-thumbnails">
                    <?php foreach ($productImages as $index => $image): ?>
                        <div class="thumbnail <?php echo $index === 0 ? 'thumbnail--active' : ''; ?>" 
                             onclick="changeImage('<?php echo $image; ?>', this)">
                            <img src="<?php echo $image; ?>" alt="Thumbnail <?php echo $index + 1; ?>">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Product Info -->
            <div class="product-info">
                <?php if (!empty($product['category_name'])): ?>
                    <a href="<?php echo BASE_URL; ?>/shop.php?category=<?php echo $product['category_id']; ?>" 
                       class="product-category-link">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a>
                <?php endif; ?>
                
                <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <?php if ($product['review_count'] > 0): ?>
                <div class="rating-section">
                    <span class="stars-display">★★★★★</span>
                    <span class="rating-text">
                        <?php echo number_format($product['avg_rating'], 1); ?> 
                        (<?php echo $product['review_count']; ?> <?php echo $product['review_count'] == 1 ? 'review' : 'reviews'; ?>)
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="price-section">
                    <span class="current-price-large"><?php echo formatCurrency($product['price']); ?></span>
                    <?php if (!empty($product['compare_price']) && $product['compare_price'] > $product['price']): ?>
                        <span class="original-price-large"><?php echo formatCurrency($product['compare_price']); ?></span>
                        <div class="savings-text">
                            You save <?php echo formatCurrency($product['compare_price'] - $product['price']); ?> 
                            (<?php echo $discountPercent; ?>%)
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="stock-section">
                    <?php if ($product['stock_quantity'] > 10): ?>
                        <span class="stock-badge stock-badge--in-stock">
                            <i class="fas fa-check-circle"></i> In Stock
                        </span>
                    <?php elseif ($product['stock_quantity'] > 0): ?>
                        <span class="stock-badge stock-badge--low-stock">
                            <i class="fas fa-exclamation-triangle"></i> Only <?php echo $product['stock_quantity']; ?> left!
                        </span>
                    <?php else: ?>
                        <span class="stock-badge stock-badge--out-of-stock">
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                <div class="quantity-section">
                    <label class="quantity-label">Quantity</label>
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="decreaseQty()">−</button>
                        <input type="number" id="quantity" class="qty-input" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                        <button class="qty-btn" onclick="increaseQty()">+</button>
                    </div>
                </div>
                
                <div class="action-buttons">
                    <button class="btn-add-to-cart-premium" onclick="addToCart(<?php echo $product['id']; ?>)">
                        <i class="fas fa-shopping-cart"></i> ADD TO CART
                    </button>
                    <button class="btn-buy-now-premium" onclick="buyNow(<?php echo $product['id']; ?>)">
                        BUY NOW
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Description -->
            <?php if (!empty($product['description'])): ?>
            <div class="description-section">
                <h2 class="section-title-detail">Product Description</h2>
                <div class="description-text">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Specifications -->
            <div class="specs-section">
                <h2 class="section-title-detail">Specifications</h2>
                <div class="specs-table">
                    <?php if (!empty($product['sku'])): ?>
                    <div class="spec-row">
                        <div class="spec-label">SKU</div>
                        <div class="spec-value"><?php echo htmlspecialchars($product['sku']); ?></div>
                    </div>
                    <?php endif; ?>
                    <div class="spec-row">
                        <div class="spec-label">Category</div>
                        <div class="spec-value"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></div>
                    </div>
                    <div class="spec-row">
                        <div class="spec-label">Availability</div>
                        <div class="spec-value">
                            <?php echo $product['stock_quantity'] > 0 ? 'In Stock (' . $product['stock_quantity'] . ' units)' : 'Out of Stock'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reviews -->
            <?php if (!empty($reviews)): ?>
            <div class="reviews-section">
                <h2 class="section-title-detail">Customer Reviews</h2>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header">
                            <span class="reviewer-name"><?php echo htmlspecialchars(substr($review['user_email'], 0, strpos($review['user_email'], '@'))); ?></span>
                            <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                        </div>
                        <div class="review-stars">
                            <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                        </div>
                        <?php if (!empty($review['comment'])): ?>
                            <div class="review-text"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Related Products -->
            <?php if (!empty($relatedProducts)): ?>
            <div class="related-section">
                <h2 class="section-title-detail">You May Also Like</h2>
                <div class="related-grid product-grid">
                    <?php foreach ($relatedProducts as $related): ?>
                        <div class="product-card">
                            <div class="product-card__image">
                                <?php
                                $relatedImages = getProductImages($related);
                                ?>
                                <a href="<?php echo BASE_URL; ?>/product-detail.php?id=<?php echo $related['id']; ?>">
                                    <img src="<?php echo $relatedImages[0]; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>">
                                </a>
                                <?php if (isset($related['discount_percent']) && $related['discount_percent'] > 0): ?>
                                    <div class="product-card__discount-badge">-<?php echo $related['discount_percent']; ?>%</div>
                                <?php endif; ?>
                                
                                <div class="product-card__actions">
                                    <button type="button" class="btn-quick-add" onclick="openQuickView(<?php echo $related['id']; ?>)" title="Quick Add">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="product-card__content">
                                <?php if (!empty($related['category_name'])): ?>
                                    <div class="product-card__category"><?php echo htmlspecialchars($related['category_name']); ?></div>
                                <?php endif; ?>
                                <a href="<?php echo BASE_URL; ?>/product-detail.php?id=<?php echo $related['id']; ?>" class="product-card__name-link">
                                    <div class="product-card__name"><?php echo htmlspecialchars($related['name']); ?></div>
                                </a>
                                <div class="product-card__price-section">
                                    <div class="product-card__price-row">
                                        <span class="product-card__price"><?php echo formatCurrency($related['price']); ?></span>
                                        <?php if ($related['compare_price'] > $related['price']): ?>
                                            <span class="product-card__price--old"><?php echo formatCurrency($related['compare_price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Image gallery
function changeImage(imageSrc, element) {
    document.getElementById('mainImageEl').src = imageSrc;
    document.querySelectorAll('.thumbnail').forEach(thumb => thumb.classList.remove('active'));
    element.classList.add('active');
}

// Quantity controls
function increaseQty() {
    const input = document.getElementById('quantity');
    const max = parseInt(input.max);
    const current = parseInt(input.value);
    if (current < max) {
        input.value = current + 1;
    }
}

function decreaseQty() {
    const input = document.getElementById('quantity');
    const current = parseInt(input.value);
    if (current > 1) {
        input.value = current - 1;
    }
}

// Add to cart
function addToCart(productId) {
    const quantity = document.getElementById('quantity').value;
    
    // Send to cart API
    fetch('<?php echo BASE_URL; ?>/api/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Product added to cart!');
            // Update cart count in header if exists
            const cartCount = document.querySelector('.cart-badge-custom');
            if (cartCount) {
                cartCount.textContent = data.cart_count || '0';
            }
        } else {
            alert(data.message || 'Error adding to cart');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error adding to cart. Please try again.');
    });
}

// Buy now
function buyNow(productId) {
    const quantity = document.getElementById('quantity').value;
    
    // Add to cart then redirect to checkout
    fetch('<?php echo BASE_URL; ?>/api/cart/add.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_id: productId,
            quantity: parseInt(quantity)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = '<?php echo BASE_URL; ?>/checkout.php';
        } else {
            alert(data.message || 'Error processing request');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error processing request. Please try again.');
    });
}
</script>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
