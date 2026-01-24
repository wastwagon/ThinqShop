<?php
/**
 * Premium Header File - World-Class Design
 * Responsive: Mobile, Tablet, Desktop
 * Brand Color: #0e2945
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/functions.php';

// Get page title
$pageTitle = isset($pageTitle) ? $pageTitle : APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo asset('assets/images/favicon.ico'); ?>">
    <link rel="apple-touch-icon" href="<?php echo asset('assets/images/apple-touch-icon.png'); ?>">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <?php 
    $cssFile = __DIR__ . '/assets/css/main.css';
    $cssVersion = file_exists($cssFile) ? md5_file($cssFile) : time();
    ?>
    <link rel="stylesheet" href="<?php echo asset('assets/css/main.css'); ?>?v=<?php echo time(); ?>&rev=<?php echo substr($cssVersion, 0, 8); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/premium-product-cards.css'); ?>?v=<?php echo time(); ?>">
    
    <!-- Premium UX Design System - World-Class Components -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/mobile-clean.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/premium-ux.css'); ?>?v=<?php echo time(); ?>">
    
    <!-- Brand Color Override - #0e2945 Theme -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/brand-color-override.css'); ?>?v=<?php echo time(); ?>">
    
    <!-- Mobile-First Optimization - Pure Mobile Look -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/mobile-first-optimization.css'); ?>?v=<?php echo time(); ?>">
    
    <!-- Premium Header - World-Class Responsive Design -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/premium-header.css'); ?>?v=<?php echo time(); ?>">
    
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo asset($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Premium World-Class Header -->
    <header class="main-header">
        <div class="header-container">
            <!-- Top Row: Logo, Search, Actions -->
            <div class="header-top-row">
                <!-- Logo -->
                <div class="header-logo">
                    <a href="<?php echo BASE_URL; ?>">
                        <img src="<?php echo asset('assets/images/logos/2025-06-02-683db744bd48b.webp'); ?>" alt="<?php echo APP_NAME; ?>">
                    </a>
                </div>
                
                <!-- Search Bar (Centered) -->
                <div class="header-search">
                    <form action="<?php echo BASE_URL; ?>/shop.php" method="GET" class="header-search-form">
                        <input 
                            type="text" 
                            name="search" 
                            class="header-search-input" 
                            placeholder="Search products..."
                            autocomplete="off"
                            aria-label="Search products"
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                        >
                        <button type="submit" class="header-search-btn" aria-label="Search">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                </div>
                
                <!-- Actions (Right) -->
                <div class="header-actions">
                    <?php 
                    // Get cart count
                    $cartCount = 0;
                    $wishlistCount = 0;
                    
                    if (!isset($conn)) {
                        require_once __DIR__ . '/../config/database.php';
                        $db = new Database();
                        $conn = $db->getConnection();
                    }
                    
                    if (isLoggedIn()) {
                        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cart WHERE user_id = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                        $result = $stmt->fetch();
                        $cartCount = intval($result['total'] ?? 0);
                        
                        try {
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $result = $stmt->fetch();
                            $wishlistCount = intval($result['total'] ?? 0);
                        } catch (Exception $e) {
                            $wishlistCount = 0;
                        }
                    } else {
                        $sessionId = session_id();
                        if ($sessionId) {
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cart WHERE session_id = ?");
                            $stmt->execute([$sessionId]);
                            $result = $stmt->fetch();
                            $cartCount = intval($result['total'] ?? 0);
                        }
                    }
                    ?>
                    
                    <!-- Account -->
                    <a href="<?php echo isLoggedIn() ? BASE_URL . '/user/dashboard.php' : BASE_URL . '/login.php'; ?>" class="header-action-item">
                        <div class="header-action-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="header-action-label">Account</span>
                    </a>
                    
                    <!-- Wishlist -->
                    <?php if (isLoggedIn()): ?>
                    <a href="<?php echo BASE_URL; ?>/user/wishlist.php" class="header-action-item">
                        <div class="header-action-icon">
                            <i class="fas fa-heart"></i>
                            <?php if ($wishlistCount > 0): ?>
                                <span class="header-action-badge"><?php echo $wishlistCount; ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="header-action-label">Wishlist</span>
                    </a>
                    <?php endif; ?>
                    
                    <!-- Cart -->
                    <a href="<?php echo BASE_URL; ?>/modules/ecommerce/cart/" class="header-action-item">
                        <div class="header-action-icon">
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cartCount > 0): ?>
                                <span class="header-action-badge"><?php echo $cartCount; ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="header-action-label">Cart</span>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Bottom Row: Navigation -->
        <div class="header-bottom-row">
            <div class="header-container">
                <nav class="header-nav">
                    <div class="header-nav-item">
                        <a href="<?php echo BASE_URL; ?>" class="header-nav-link">
                            Home
                        </a>
                    </div>
                    <div class="header-nav-item">
                        <a href="<?php echo BASE_URL; ?>/shop.php" class="header-nav-link">
                            Shop
                        </a>
                    </div>
                    <div class="header-nav-item">
                        <a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/" class="header-nav-link">
                            Money Transfer
                        </a>
                    </div>
                    <div class="header-nav-item">
                        <a href="<?php echo BASE_URL; ?>/modules/logistics/booking/" class="header-nav-link">
                            Send a Parcel
                        </a>
                    </div>
                    <div class="header-nav-item">
                        <a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="header-nav-link">
                            Procurement
                        </a>
                    </div>
                </nav>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php 
    $flash = getFlashMessage();
    if ($flash): 
    ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Mobile Bottom Menu (shown only on mobile) -->
    <?php include __DIR__ . '/includes/mobile-menu.php'; ?>
