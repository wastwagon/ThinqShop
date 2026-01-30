<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
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
    
    <!-- Premium Header - World-Class Responsive Design -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/premium-header.css'); ?>?v=<?php echo time(); ?>">
    <!-- Hero & Header Styles (Required for Top Bar & Desktop Nav) -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/pages/home-hero.css'); ?>?v=<?php echo time(); ?>">
    
    <!-- Modern Component System (BEM) -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/core/variables.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/components/forms.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/components/buttons.css'); ?>?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="<?php echo asset('assets/css/components/cards.css'); ?>?v=<?php echo time(); ?>">
    
    <!-- Mobile-First Optimization - Pure Mobile Look (Loaded Last to Override) -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/mobile-first-optimization.css'); ?>?v=<?php echo time(); ?>">

    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo asset($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
    
    <!-- Mobile Bottom Menu Styles (Injected here for priority) -->
    <style>
        @media (max-width: 991.98px) {
            /* Mobile Bottom Menu - Fixed Visibility - FORCE OVERRIDE */
            .mobile-bottom-menu {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 9999 !important;
                background: #ffffff !important;
                border-top: 1px solid #e2e8f0 !important;
                padding-bottom: calc(env(safe-area-inset-bottom, 20px) + 10px) !important;
                display: block !important;
                box-shadow: 0 -4px 15px rgba(0, 0, 0, 0.05) !important;
                height: auto !important;
                transform: none !important; /* Prevent bugs */
            }

            .mobile-menu-items {
                display: flex !important;
                justify-content: space-around !important;
                align-items: center !important;
                height: 60px !important;
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
            }

            .mobile-menu-item {
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                color: #64748b !important; /* Slate-500 */
                text-decoration: none !important;
                flex: 1 !important;
                height: 100% !important;
                background: none !important;
                border: none !important;
                opacity: 1 !important;
                visibility: visible !important;
            }

            .mobile-menu-item.active {
                color: #0e2945 !important; /* Brand Dark */
            }

            .mobile-menu-item .menu-item-icon-wrapper {
                margin-bottom: 2px !important;
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .mobile-menu-item i {
                font-size: 20px !important;
                color: inherit !important;
                display: block !important;
            }

            .mobile-menu-item .menu-item-label {
                font-size: 10px !important;
                font-weight: 500 !important;
                color: inherit !important;
                margin-top: 2px !important;
                display: block !important;
                opacity: 1 !important;
            }

            .menu-item-indicator {
                display: none !important;
            }
        }
    </style>
</head>
<body<?php echo isset($bodyClass) ? ' class="' . htmlspecialchars($bodyClass) . '"' : ''; ?>>

    <!-- Top Utility Bar (Premium Dark) -->
    <div class="top-bar bg-dark text-white py-1">
        <div class="container">
            <div class="row align-items-center">
                <!-- 1. Contact Info & Socials (Left) -->
                <div class="col-md-4 d-flex align-items-center gap-3">
                    <a href="tel:+8618320709024" class="text-white text-decoration-none d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                        <i class="fas fa-phone-alt text-warning"></i> 
                        <span class="fw-bold">+86 183 2070 9024</span>
                    </a>
                    <div class="d-flex gap-2 border-start ps-3 border-secondary d-none d-lg-flex">
                        <a href="#" class="text-white hover-primary"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white hover-primary"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white hover-primary"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <!-- Center: Flash Sale Timer -->
                <div class="col-md-4 text-center d-none d-md-block">
                    <span class="fw-bold text-uppercase text-danger me-2">Flash Sale Ends In:</span>
                    <span id="flash-timer" class="fw-bold font-monospace text-warning">-- : -- : -- : --</span>
                </div>
                
                <!-- Right: Links -->
                <div class="col-md-4 text-end d-none d-md-block">
                    <a href="<?php echo BASE_URL; ?>/user/procurement/quotes/view.php" class="text-white text-decoration-none me-3 hover-primary">Track Order</a>
                    <a href="<?php echo BASE_URL; ?>/help.php" class="text-white text-decoration-none hover-primary">Help Center</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Header Section (Sticky Single Row) -->
    <header class="main-header sticky-top bg-white border-bottom shadow-sm" style="top: 0; z-index: 1020;">
        <div class="header-container">
            <div class="d-flex align-items-center justify-content-between py-2">
                <!-- 1. Logo (Left) -->
                <a href="<?php echo BASE_URL; ?>" class="navbar-brand me-4 d-flex align-items-center">
                    <img src="<?php echo asset('assets/images/logos/2025-06-02-683db744bd48b.webp'); ?>" alt="<?php echo APP_NAME; ?>" style="height: 38px; width: auto;">
                </a>
                
                <?php 
                $currentPage = basename($_SERVER['PHP_SELF']);
                $isHome = ($currentPage == 'index.php' || $currentPage == '');
                $isShop = ($currentPage == 'shop.php');
                $isTransfer = (strpos($_SERVER['REQUEST_URI'], 'money-transfer') !== false);
                $isLogistics = (strpos($_SERVER['REQUEST_URI'], 'logistics') !== false);
                $isProcurement = (strpos($_SERVER['REQUEST_URI'], 'procurement') !== false);
                ?>
                
                <!-- 2. Navigation (Center - Desktop Only) -->
                <nav class="desktop-nav align-items-center gap-3 gap-lg-4 mx-auto d-none d-lg-flex">
                    <a class="nav-link <?php echo $isHome ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>">Home</a>
                    <a class="nav-link <?php echo $isShop ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/shop.php">Shop</a>
                    <a class="nav-link <?php echo $isTransfer ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/">Money Transfer</a>
                    <a class="nav-link <?php echo $isLogistics ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/modules/logistics/booking/">Logistics</a>
                    <a class="nav-link <?php echo $isProcurement ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/modules/procurement/request/">Procurement</a>
                </nav>
                
                <!-- 3. Actions (Right) -->
                <div class="user-actions d-flex align-items-center gap-2 gap-lg-3">
                    <?php 
                    // Get cart count (Reuse existing logic)
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

                    <div class="action-item">
                        <button class="btn btn-link text-dark p-0" onclick="document.getElementById('header-search-bar').classList.toggle('d-none'); document.getElementById('header-search-input').focus();">
                            <i class="fas fa-search fa-lg"></i>
                        </button>
                    </div>
                    
                    <div class="action-item">
                        <?php if (isLoggedIn()): ?>
                            <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="action-link text-dark">
                                <i class="far fa-user fa-lg"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/login.php" class="action-link text-dark">
                                <i class="far fa-user fa-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="action-item">
                        <a href="<?php echo BASE_URL; ?>/modules/ecommerce/cart/" class="action-link position-relative text-dark">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <span class="cart-badge-custom"><?php echo $cartCount; ?></span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Hidden Search Bar (Toggles on click) -->
            <div id="header-search-bar" class="py-2 d-none border-top">
                 <form action="<?php echo BASE_URL; ?>/shop.php" method="GET" class="d-flex">
                    <input type="text" id="header-search-input" name="search" class="form-control me-2" placeholder="Search products..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button class="btn btn-dark" type="submit">Search</button>
                 </form>
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
    
    <!-- Timer Script -->
    <script>
    (function() {
        const timerEl = document.getElementById('flash-timer');
        if (!timerEl) return;
        
        // Set a future date (e.g., 3 days from now)
        const date = new Date();
        date.setDate(date.getDate() + 3); 
        const targetDate = date.getTime();

        const updateTimer = () => {
            const now = new Date().getTime();
            const distance = targetDate - now;

            if (distance < 0) {
                timerEl.innerHTML = "EXPIRED";
                return;
            }

            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            timerEl.innerHTML = `${days}d : ${hours}h : ${minutes}m : ${seconds}s`;
        };

        setInterval(updateTimer, 1000);
        updateTimer();
    })();
    </script>
    
    <!-- Mobile Bottom Menu (shown only on mobile) -->
    <?php include __DIR__ . '/mobile-menu.php'; ?>
