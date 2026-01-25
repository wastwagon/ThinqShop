<?php
/**
 * Homepage - Modern E-commerce Design
 * ThinQShopping Platform
 */

// Load configuration
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Get database connection
$db = new Database();
$conn = $db->getConnection();

// Get featured/new products (latest 12 products)
$featuredProducts = [];
try {
    $stmt = $conn->query("
        SELECT p.*, c.name as category_name,
               COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
               COALESCE((SELECT COUNT(*) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as review_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1 
        ORDER BY p.created_at DESC 
        LIMIT 12
    ");
    $featuredProducts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching featured products: " . $e->getMessage());
}

// Get deal products (products with discounts)
$dealProducts = [];
try {
    $stmt = $conn->query("
        SELECT p.*, c.name as category_name,
               COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
               ROUND(((p.compare_price - p.price) / p.compare_price * 100), 0) as discount_percent
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.is_active = 1 AND p.compare_price > p.price
        ORDER BY discount_percent DESC, p.created_at DESC 
        LIMIT 8
    ");
    $dealProducts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching deal products: " . $e->getMessage());
}

// Get trending products (most ordered)
$trendingProducts = [];
try {
    $stmt = $conn->query("
        SELECT p.*, c.name as category_name,
               COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
               COUNT(oi.id) as order_count
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN order_items oi ON p.id = oi.product_id
        LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
        WHERE p.is_active = 1 
        GROUP BY p.id
        ORDER BY order_count DESC, p.created_at DESC 
        LIMIT 8
    ");
    $trendingProducts = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching trending products: " . $e->getMessage());
}

// Get categories
$categories = [];
try {
    $stmt = $conn->query("
        SELECT c.*, COUNT(p.id) as product_count 
        FROM categories c 
        LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
        WHERE c.is_active = 1 AND c.parent_id IS NULL 
        GROUP BY c.id 
        ORDER BY product_count DESC, c.name ASC 
        LIMIT 8
    ");
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching categories: " . $e->getMessage());
}

// Page title
$pageTitle = 'Shop Quality Products - ' . APP_NAME;

// Include header
include __DIR__ . '/includes/header.php';
?>

<div class="homepage">
    <!-- Hero Slider Section -->
    <div class="hero-slider-container">
        <div class="container">
            <div class="hero-swiper swiper">
                <div class="swiper-wrapper">
                    <?php 
                    // Select products for hero slider (Top 3 Deals or Featured)
                    $heroProducts = !empty($dealProducts) ? array_slice($dealProducts, 0, 3) : array_slice($featuredProducts, 0, 3);
                    
                    if (!empty($heroProducts)):
                        foreach ($heroProducts as $product):
                            $img = getProductImage($product);
                    ?>
                    <div class="swiper-slide" style="background-image: url('<?php echo $img; ?>');">
                        <div class="hero-overlay"></div>
                        <div class="hero-content">
                            <?php if (!empty($product['discount_percent'])): ?>
                                <span class="hero-discount-badge">
                                    <i class="fas fa-fire me-1"></i> <?php echo $product['discount_percent']; ?>% OFF
                                </span>
                            <?php endif; ?>
                            <h2 class="hero-title"><?php echo htmlspecialchars($product['name']); ?></h2>
                            <p class="hero-subtitle">
                                <?php 
                                    $desc = !empty($product['short_description']) ? $product['short_description'] : $product['description'];
                                    echo htmlspecialchars(substr(strip_tags($desc), 0, 100)) . '...'; 
                                ?>
                            </p>
                            <div class="hero-button-wrapper">
                                <a href="<?php echo BASE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>" class="btn-hero">
                                    Shop Now <i class="fas fa-arrow-right ms-2"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; 
                    else: 
                        // Fallback static slide if no products
                    ?>
                    <div class="swiper-slide" style="background-color: #05203e;">
                        <div class="hero-content text-center mx-auto">
                            <h2 class="hero-title">Discover Amazing Products</h2>
                            <p class="hero-subtitle">Quality items at unbeatable prices</p>
                            <div class="hero-button-wrapper">
                                <a href="<?php echo BASE_URL; ?>/shop.php" class="btn-hero">Shop Now</a>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- Pagination -->
                <div class="swiper-pagination"></div>
                <!-- Navigation -->
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
            </div>
        </div>
    </div>

    <!-- Categories -->
    <?php if (!empty($categories)): ?>
    <div class="categories-section">
        <div class="container">
            <div class="categories-bar-wrapper">
                <div class="categories-scroll">
                    <a href="<?php echo BASE_URL; ?>/shop.php" class="modern-filter-chip active">
                        <span class="chip-icon"><i class="fas fa-th-large"></i></span>
                        <span class="chip-label">All</span>
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="<?php echo BASE_URL; ?>/shop.php?category=<?php echo $category['id']; ?>" class="modern-filter-chip">
                            <span class="chip-label"><?php echo htmlspecialchars($category['name']); ?></span>
                            <?php if ($category['product_count'] > 0): ?>
                                <span class="chip-count"><?php echo $category['product_count']; ?></span>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Deal Products -->
    <?php if (!empty($dealProducts)): ?>
    <div class="deal-banner-modern">
        <div class="deal-banner-title">🔥 Flash Deals</div>
        <div class="deal-banner-subtitle">Limited time offers - Save big today!</div>
    </div>
    
    <div class="container section-container">
        <div class="product-grid">
            <?php foreach ($dealProducts as $product): ?>
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
    </div>
    <?php endif; ?>

    <!-- Featured/New Products -->
    <?php if (!empty($featuredProducts)): ?>
    <div class="container section-container">
        <div class="product-grid">
            <?php foreach ($featuredProducts as $product): ?>
                <div class="product-card">
                    <div class="product-card__image">
                        <a href="<?php echo BASE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo getProductImage($product); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy">
                        </a>
                        
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
    </div>
    <?php endif; ?>

    <!-- Trending Products -->
    <?php if (!empty($trendingProducts)): ?>
    <div class="deal-banner-modern">
        <div class="deal-banner-title">🚀 Trending Now</div>
        <div class="deal-banner-subtitle">Discover the most popular products loved by our customers</div>
    </div>
    <div class="container section-container">
        <div class="product-grid">
            <?php foreach ($trendingProducts as $product): ?>
                <div class="product-card">
                    <div class="product-card__image">
                        <a href="<?php echo BASE_URL; ?>/product-detail.php?id=<?php echo $product['id']; ?>">
                            <img src="<?php echo getProductImage($product); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 loading="lazy">
                        </a>
                        
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
    </div>
    <?php endif; ?>

    <!-- Empty State -->
    <?php if (empty($featuredProducts) && empty($dealProducts) && empty($trendingProducts)): ?>
    <div class="empty-state-modern">
        <div class="empty-icon">🛍️</div>
        <div class="empty-text">No products available at the moment. Check back soon!</div>
    </div>
    <?php endif; ?>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>

<!-- Initialize Hero Slider -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Swiper !== 'undefined') {
        const heroSwiper = new Swiper('.hero-swiper', {
            loop: true,
            speed: 800,
            effect: 'fade',
            fadeEffect: {
                crossFade: true
            },
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
                dynamicBullets: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
        });
    } else {
        console.warn('Swiper not loaded');
    }
});
</script>
