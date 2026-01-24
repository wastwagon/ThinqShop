<?php
/**
 * Homepage
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

// Get featured products for homepage
$db = new Database();
$conn = $db->getConnection();

$featuredProducts = [];
$stmt = $conn->query("
    SELECT p.*, c.name as category_name
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_featured = 1 AND p.is_active = 1 
    ORDER BY p.created_at DESC 
    LIMIT 8
");
$featuredProducts = $stmt->fetchAll();

// Get categories with product counts
$categories = [];
$stmt = $conn->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
    WHERE c.is_active = 1 AND c.parent_id IS NULL 
    GROUP BY c.id 
    ORDER BY c.name ASC 
    LIMIT 6
");
$categories = $stmt->fetchAll();

// Get trending, top rated, and top selling products
$trendingProducts = [];
$stmt = $conn->query("
    SELECT p.*, 
           COALESCE((SELECT AVG(rating) FROM product_reviews WHERE product_id = p.id AND is_approved = 1), 0) as avg_rating,
           COUNT(o.id) as order_count
    FROM products p 
    LEFT JOIN order_items oi ON p.id = oi.product_id
    LEFT JOIN orders o ON oi.order_id = o.id AND o.status != 'cancelled'
    WHERE p.is_active = 1 
    GROUP BY p.id
    ORDER BY order_count DESC, p.created_at DESC 
    LIMIT 9
");
$trendingProducts = $stmt->fetchAll();

// Page title
$pageTitle = 'Home - ' . APP_NAME;

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="premium-hero-section">
    <div class="container">
        <div class="row align-items-center justify-content-center text-center">
            <div class="col-lg-10 col-xl-8">
                <div class="premium-hero-content">
                    <h1 class="hero-title-premium">
                        Shop, Transfer & Deliver with <span class="text-accent">Ease</span>
                    </h1>
                    <div class="hero-cta-premium">
                        <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-hero-primary">
                            <span>Start Shopping</span>
                            <i class="fas fa-arrow-right ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="hero-background-video">
        <video autoplay muted loop playsinline>
            <source src="<?php echo BASE_URL; ?>/assets/video/videoplayback.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
</section>

<!-- Services Section -->
<section id="services" class="services-section py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge bg-primary mb-3">Our Services</span>
            <h2 class="section-title mb-3">Everything You Need in One Place</h2>
            <p class="text-muted">Comprehensive solutions for all your shopping, transfer, and delivery needs</p>
        </div>
        <div class="row g-4">
            <div class="col-md-6 col-lg-3">
                <div class="service-card service-card-primary text-center h-100">
                    <div class="service-icon-wrapper mb-4">
                        <div class="service-icon-bg bg-primary-light">
                            <i class="fas fa-shopping-bag fa-3x text-primary"></i>
                        </div>
                    </div>
                    <h4 class="mb-3">E-Commerce</h4>
                    <p class="text-muted mb-4">Shop quality products with fast delivery across Ghana. Browse thousands of items from trusted sellers.</p>
                    <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag me-2"></i>Shop Now
                    </a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="service-card service-card-success text-center h-100">
                    <div class="service-icon-wrapper mb-4">
                        <div class="service-icon-bg bg-success-light">
                            <i class="fas fa-exchange-alt fa-3x text-success"></i>
                        </div>
                    </div>
                    <h4 class="mb-3">Money Transfer</h4>
                    <p class="text-muted mb-4">Send and receive money securely between Ghana and China. Real-time exchange rates, token-based tracking.</p>
                    <a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/" class="btn btn-success">
                        <i class="fas fa-exchange-alt me-2"></i>Transfer Now
                    </a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="service-card service-card-info text-center h-100">
                    <div class="service-icon-wrapper mb-4">
                        <div class="service-icon-bg bg-info-light">
                            <i class="fas fa-truck fa-3x text-info"></i>
                        </div>
                    </div>
                    <h4 class="mb-3">Logistics</h4>
                    <p class="text-muted mb-4">Door-to-door parcel delivery service. Same-day, next-day, or standard delivery options available.</p>
                    <a href="<?php echo BASE_URL; ?>/modules/logistics/booking/" class="btn btn-info text-white">
                        <i class="fas fa-truck me-2"></i>Book Now
                    </a>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="service-card service-card-warning text-center h-100">
                    <div class="service-icon-wrapper mb-4">
                        <div class="service-icon-bg bg-warning-light">
                            <i class="fas fa-box fa-3x text-warning"></i>
                        </div>
                    </div>
                    <h4 class="mb-3">Procurement</h4>
                    <p class="text-muted mb-4">Request any item you need from China. We source and deliver exactly what you're looking for.</p>
                    <a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="btn btn-warning">
                        <i class="fas fa-box me-2"></i>Request Now
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products -->
<?php if (!empty($featuredProducts)): ?>
<section class="featured-products py-5 bg-light">
    <div class="container">
        <h2 class="section-title text-center mb-5">Featured Products</h2>
        <div class="row">
            <?php foreach ($featuredProducts as $product): 
                $images = json_decode($product['images'] ?? '[]', true);
                // Check if image exists
                if (empty($images) || empty($images[0])) {
                    // Generate image URL based on product name
                    $mainImage = getProductImageUrl($product['name'], 400, 400);
                } else {
                    // Use image as-is, imageUrl() will handle path fixing
                    $mainImage = $images[0];
                }
            ?>
            <div class="col-6 col-md-4 col-lg-3 mb-4">
                <?php 
                    $hasDiscount = $product['compare_price'] && $product['compare_price'] > $product['price'];
                ?>
                <div class="modern-product-card">
                    <!-- Badges -->
                    <div class="product-badges">
                        <?php if ($product['is_featured']): ?>
                            <span class="product-badge featured">Featured</span>
                        <?php endif; ?>
                        <?php if ($hasDiscount): ?>
                            <span class="product-badge sale">
                                -<?php echo round((($product['compare_price'] - $product['price']) / $product['compare_price']) * 100); ?>%
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="product-actions-top">
                        <button class="action-btn" title="Quick View" onclick="openQuickView(<?php echo $product['id']; ?>)" data-product-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php if (isLoggedIn()): ?>
                        <button class="action-btn" title="Add to Wishlist" onclick="toggleWishlist(<?php echo $product['id']; ?>)">
                            <i class="far fa-heart"></i>
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Product Image -->
                    <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo $product['slug']; ?>">
                        <div class="product-image-wrapper">
                            <img src="<?php echo imageUrl($mainImage, 400, 400); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 loading="lazy">
                        </div>
                    </a>

                    <!-- Product Info -->
                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo $product['slug']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>


                        <!-- Price -->
                        <div class="product-price-section">
                            <div class="product-price">
                                <?php echo formatCurrency($product['price']); ?>
                            </div>
                            <?php if ($hasDiscount): ?>
                                <div class="product-compare-price">
                                    <?php echo formatCurrency($product['compare_price']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Add to Cart -->
                    <div class="product-actions-bottom">
                        <?php if ($product['stock_quantity'] > 0): ?>
                            <?php if (isLoggedIn()): ?>
                            <form action="<?php echo BASE_URL; ?>/modules/ecommerce/cart/add.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="quantity" value="1">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <button type="submit" class="add-to-cart-btn">
                                    <i class="fas fa-cart-plus"></i>
                                    Add to Cart
                                </button>
                            </form>
                            <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/login.php" class="add-to-cart-btn">
                                <i class="fas fa-sign-in-alt"></i>
                                Login to Buy
                            </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <button class="add-to-cart-btn" disabled>
                                <i class="fas fa-times"></i>
                                Out of Stock
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-primary">View All Products</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Category Cards Section -->
<?php if (!empty($categories)): ?>
<section class="category-cards-section py-5 bg-white">
    <div class="container">
        <div class="row g-3">
            <?php 
            $categoryIcons = [
                'fruits' => 'fa-apple-alt',
                'vegetables' => 'fa-carrot',
                'dairy' => 'fa-wine-bottle',
                'bakery' => 'fa-bread-slice',
                'snacks' => 'fa-cookie',
                'drinks' => 'fa-wine-glass-alt'
            ];
            foreach ($categories as $index => $category): 
                $catNameLower = strtolower($category['name']);
                $icon = 'fa-folder';
                foreach ($categoryIcons as $key => $catIcon) {
                    if (strpos($catNameLower, $key) !== false) {
                        $icon = $catIcon;
                        break;
                    }
                }
                $productCount = intval($category['product_count'] ?? 0);
                $hasDiscount = ($index % 3 === 0); // Show discount on some categories
            ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="<?php echo BASE_URL; ?>/shop.php?category=<?php echo $category['id']; ?>" class="home-category-card">
                    <div class="category-card-icon">
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="category-card-content">
                        <h6 class="category-card-name"><?php echo htmlspecialchars($category['name']); ?></h6>
                        <small class="category-card-count"><?php echo $productCount; ?> Items</small>
                    </div>
                    <?php if ($hasDiscount): ?>
                        <span class="category-card-badge"><?php echo ($index * 5 + 10); ?>%</span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Service Guarantees Section -->
<section class="service-guarantees-section py-5 bg-white">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="guarantee-card">
                    <div class="guarantee-icon">
                        <i class="fas fa-truck"></i>
                    </div>
                    <h6 class="guarantee-title">Free Shipping</h6>
                    <p class="guarantee-text">Free shipping on all US order or order above $200.</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="guarantee-card">
                    <div class="guarantee-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h6 class="guarantee-title">24X7 Support</h6>
                    <p class="guarantee-text">Contact us 24 hours a day, 7 days a week.</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="guarantee-card">
                    <div class="guarantee-icon">
                        <i class="fas fa-undo-alt"></i>
                    </div>
                    <h6 class="guarantee-title">30 Days Return</h6>
                    <p class="guarantee-text">Simply return it within 30 days for an exchange.</p>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="guarantee-card">
                    <div class="guarantee-icon">
                        <i class="fas fa-lock"></i>
                    </div>
                    <h6 class="guarantee-title">Payment Secure</h6>
                    <p class="guarantee-text">We ensure secure payment with encryption.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Product Showcase Section -->
<?php if (!empty($trendingProducts)): ?>
<section class="product-showcase-section py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-3 mb-4 mb-lg-0">
                <div class="showcase-banner">
                    <h3 class="banner-title">Our Top Most Products Check It Now.</h3>
                    <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-success btn-lg mt-3">Shop Now</a>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="showcase-tabs">
                    <ul class="nav nav-tabs border-0 mb-4" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#trending" type="button">Trending Items</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#top-rated" type="button">Top Rated</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#top-selling" type="button">Top Selling</button>
                        </li>
                    </ul>
                    <div class="tab-content">
                        <div class="tab-pane fade show active" id="trending" role="tabpanel">
                            <div class="row g-3">
                                <?php foreach (array_slice($trendingProducts, 0, 6) as $product): 
                                    $images = json_decode($product['images'] ?? '[]', true);
                                    // Check if image exists
                                    if (empty($images) || empty($images[0])) {
                                        $mainImage = getProductImageUrl($product['name'], 150, 150);
                                    } else {
                                        // Use image as-is, imageUrl() will handle path fixing
                                        $mainImage = $images[0];
                                    }
                                ?>
                                <div class="col-6 col-md-4">
                                    <div class="showcase-product-card">
                                        <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo $product['slug']; ?>">
                                            <img src="<?php echo imageUrl($mainImage, 150, 150); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumb">
                                            <div class="product-details">
                                                <h6 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Product'); ?></small>
                                                <div class="product-price-row">
                                                    <span class="product-price"><?php echo formatCurrency($product['price']); ?></span>
                                                    <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                                        <span class="product-compare-price"><?php echo formatCurrency($product['compare_price']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="top-rated" role="tabpanel">
                            <div class="row g-3">
                                <?php 
                                $topRated = array_slice(array_filter($trendingProducts, function($p) { return floatval($p['avg_rating']) >= 4; }), 0, 6);
                                if (empty($topRated)) $topRated = array_slice($trendingProducts, 0, 6);
                                foreach ($topRated as $product): 
                                    $images = json_decode($product['images'] ?? '[]', true);
                                    // Check if image exists
                                    if (empty($images) || empty($images[0])) {
                                        $mainImage = getProductImageUrl($product['name'], 150, 150);
                                    } else {
                                        // Use image as-is, imageUrl() will handle path fixing
                                        $mainImage = $images[0];
                                    }
                                ?>
                                <div class="col-6 col-md-4">
                                    <div class="showcase-product-card">
                                        <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo $product['slug']; ?>">
                                            <img src="<?php echo imageUrl($mainImage, 150, 150); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumb">
                                            <div class="product-details">
                                                <h6 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Product'); ?></small>
                                                <div class="product-price-row">
                                                    <span class="product-price"><?php echo formatCurrency($product['price']); ?></span>
                                                    <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                                        <span class="product-compare-price"><?php echo formatCurrency($product['compare_price']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="top-selling" role="tabpanel">
                            <div class="row g-3">
                                <?php foreach (array_slice($trendingProducts, 0, 6) as $product): 
                                    $images = json_decode($product['images'] ?? '[]', true);
                                    // Check if image exists
                                    if (empty($images) || empty($images[0])) {
                                        $mainImage = getProductImageUrl($product['name'], 150, 150);
                                    } else {
                                        // Use image as-is, imageUrl() will handle path fixing
                                        $mainImage = $images[0];
                                    }
                                ?>
                                <div class="col-6 col-md-4">
                                    <div class="showcase-product-card">
                                        <a href="<?php echo BASE_URL; ?>/product-detail.php?slug=<?php echo $product['slug']; ?>">
                                            <img src="<?php echo imageUrl($mainImage, 150, 150); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-thumb">
                                            <div class="product-details">
                                                <h6 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                <small class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Product'); ?></small>
                                                <div class="product-price-row">
                                                    <span class="product-price"><?php echo formatCurrency($product['price']); ?></span>
                                                    <?php if ($product['compare_price'] && $product['compare_price'] > $product['price']): ?>
                                                        <span class="product-compare-price"><?php echo formatCurrency($product['compare_price']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>

