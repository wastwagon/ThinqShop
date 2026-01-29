<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : APP_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'ThinQShopping - Your one-stop platform for shopping, money transfers, logistics, and procurement services in Ghana.'; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo asset('assets/images/icons/favicon.png'); ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo asset('assets/images/icons/favicon.png'); ?>">
    <link rel="apple-touch-icon" href="<?php echo asset('assets/images/icons/favicon.png'); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Swiper CSS (for carousels) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    
    <!-- Custom CSS -->
    <?php 
    $cssFile = dirname(__DIR__) . '/assets/css/main.css';
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
    
    <!-- Modern Component System (BEM) -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/core/variables.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/components/buttons.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/components/cards.css'); ?>?v=<?php echo time(); ?>">
    
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo asset($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Top Utility Bar -->
    <div class="top-utility-bar">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-4 d-none d-md-block">
                    <div class="utility-contact">
                        <a href="tel:+233242565695" class="utility-link">
                            <i class="fas fa-phone"></i> +233 24 256 5695
                        </a>
                        <a href="https://wa.me/233242565695" class="utility-link ms-3" target="_blank">
                            <i class="fab fa-whatsapp"></i> +233 24 256 5695
                        </a>
                    </div>
                </div>
                <div class="col-md-4 text-center d-none d-md-block">
                    <span class="utility-tagline">World's Fastest Online Shopping Destination</span>
                </div>
                <div class="col-md-4">
                    <div class="utility-links d-flex justify-content-end align-items-center">
                        <a href="<?php echo BASE_URL; ?>/help.php" class="utility-link me-3">Help?</a>
                        <div class="dropdown d-inline-block me-3">
                            <button class="utility-dropdown" type="button" data-bs-toggle="dropdown">
                                English <i class="fas fa-chevron-down ms-1"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">English</a></li>
                                <li><a class="dropdown-item" href="#">Français</a></li>
                                <li><a class="dropdown-item" href="#">中文</a></li>
                            </ul>
                        </div>
                        <div class="dropdown d-inline-block">
                            <button class="utility-dropdown" type="button" data-bs-toggle="dropdown">
                                <?php echo CURRENCY_SYMBOL; ?> <?php echo CURRENCY_CODE; ?> <i class="fas fa-chevron-down ms-1"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="#">₵ GHS</a></li>
                                <li><a class="dropdown-item" href="#">$ USD</a></li>
                                <li><a class="dropdown-item" href="#">€ EUR</a></li>
                                <li><a class="dropdown-item" href="#">¥ CNY</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header Section -->
    <header class="main-header">
        <div class="container">
            <div class="row align-items-center py-2 py-lg-3 g-2">
                <!-- Logo -->
                <div class="col-lg-3 col-md-4 col-6">
                    <a href="<?php echo BASE_URL; ?>" class="navbar-brand">
                        <img src="<?php echo asset('assets/images/logos/2025-06-02-683db744bd48b.webp'); ?>" alt="<?php echo APP_NAME; ?>" height="45" class="logo-img">
                    </a>
                </div>
                
                <!-- Search Bar -->
                <div class="col-lg-6 col-md-8 col-12 order-3 order-lg-2 mt-2 mt-md-0">
                    <form action="<?php echo BASE_URL; ?>/shop.php" method="GET" class="search-form">
                        <div class="input-group search-input-group">
                            <input type="text" name="search" class="form-control search-input" 
                                   placeholder="Search Products..." 
                                   value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                            <button class="btn btn-search" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- User Actions -->
                <div class="col-lg-3 col-6 order-2 order-lg-3 mt-0">
                    <div class="user-actions d-flex justify-content-end align-items-center">
                        <?php 
                        // Get cart count (count unique items, not total quantity)
                        $cartCount = 0;
                        $wishlistCount = 0;
                        
                        if (!isset($conn)) {
                            require_once __DIR__ . '/../config/database.php';
                            $db = new Database();
                            $conn = $db->getConnection();
                        }
                        
                        // Get cart count for logged-in users or guest sessions
                        if (isLoggedIn()) {
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM cart WHERE user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $result = $stmt->fetch();
                            $cartCount = intval($result['total'] ?? 0);
                            
                            // Get wishlist count (if wishlist table exists)
                            try {
                                $stmt = $conn->prepare("SELECT COUNT(*) as total FROM wishlist WHERE user_id = ?");
                                $stmt->execute([$_SESSION['user_id']]);
                                $result = $stmt->fetch();
                                $wishlistCount = intval($result['total'] ?? 0);
                            } catch (Exception $e) {
                                $wishlistCount = 0;
                            }
                        } else {
                            // Guest user - count by session_id
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
                        <div class="action-item">
                            <?php if (isLoggedIn()): ?>
                                <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="action-link" title="Account Dashboard">
                                    <i class="fas fa-user"></i>
                                </a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/login.php" class="action-link" title="Login">
                                    <i class="fas fa-user"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Wishlist -->
                        <?php if (isLoggedIn()): ?>
                        <div class="action-item">
                            <a href="<?php echo BASE_URL; ?>/user/wishlist.php" class="action-link position-relative" title="Wishlist">
                                <i class="fas fa-heart"></i>
                                <?php if ($wishlistCount > 0): ?>
                                    <span class="badge-count"><?php echo $wishlistCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Cart -->
                        <div class="action-item">
                            <a href="<?php echo BASE_URL; ?>/modules/ecommerce/cart/" class="action-link position-relative" title="Shopping Cart">
                                <i class="fas fa-shopping-bag"></i>
                                <?php if ($cartCount > 0): ?>
                                    <span class="badge-count"><?php echo $cartCount; ?></span>
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation Bar -->
    <nav class="main-navbar">
        <div class="container">
            <div class="row align-items-center">
                <!-- All Categories Dropdown -->
                <div class="col-lg-2 col-md-3 col-12 mb-2 mb-lg-0 position-relative">
                    <div class="dropdown categories-dropdown">
                        <button class="btn btn-categories" type="button" data-bs-toggle="dropdown" id="categoriesDropdownBtn">
                            <i class="fas fa-th"></i>
                            <span>All Categories</span>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </button>
                        <ul class="dropdown-menu categories-dropdown-menu" aria-labelledby="categoriesDropdownBtn">
                            <?php
                            // Get categories with product counts
                            if (!isset($conn)) {
                                require_once __DIR__ . '/../config/database.php';
                                $db = new Database();
                                $conn = $db->getConnection();
                            }
                            $stmt = $conn->query("
                                SELECT c.*, COUNT(p.id) as product_count 
                                FROM categories c 
                                LEFT JOIN products p ON c.id = p.category_id AND p.is_active = 1
                                WHERE c.is_active = 1 AND c.parent_id IS NULL
                                GROUP BY c.id 
                                ORDER BY c.name ASC
                            ");
                            $allCategories = $stmt->fetchAll();
                            foreach ($allCategories as $cat): ?>
                                <li>
                                    <a class="dropdown-item category-dropdown-item" href="<?php echo BASE_URL; ?>/shop.php?category=<?php echo $cat['id']; ?>">
                                        <i class="fas fa-folder me-2"></i>
                                        <span><?php echo htmlspecialchars($cat['name']); ?></span>
                                        <small class="text-muted ms-auto">(<?php echo intval($cat['product_count']); ?>)</small>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                
                <!-- Main Navigation -->
                <div class="col-lg-10 col-md-9 col-12">
                    <div class="main-nav-wrapper">
                        <ul class="main-nav d-flex align-items-center justify-content-center list-unstyled mb-0 ps-0">
                            <li class="nav-item"><a href="<?php echo BASE_URL; ?>" class="nav-link">Home</a></li>
                            <li class="nav-item"><a href="<?php echo BASE_URL; ?>/shop.php" class="nav-link">Shop</a></li>
                            <li class="nav-item"><a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/" class="nav-link">Money Transfer</a></li>
                            <li class="nav-item"><a href="<?php echo BASE_URL; ?>/modules/logistics/booking/" class="nav-link">Send a Parcel</a></li>
                            <li class="nav-item"><a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="nav-link">Procurement</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </nav>

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
    <?php include __DIR__ . '/mobile-menu.php'; ?>
