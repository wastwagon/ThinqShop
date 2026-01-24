<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : APP_NAME; ?></title>
    <meta name="description" content="<?php echo isset($pageDescription) ? htmlspecialchars($pageDescription) : 'ThinQShopping - Your one-stop platform for shopping, money transfers, logistics, and procurement services in Ghana.'; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo asset('assets/images/icons/favicon.png'); ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo asset('assets/images/icons/favicon.png'); ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo asset('assets/images/icons/favicon.png'); ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo asset('assets/images/icons/favicon.png'); ?>">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    
    <!-- Swiper CSS (for carousels) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css">
    
    <!-- GLightbox CSS (for image lightbox) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css">
    
    <!-- Custom CSS - CONSOLIDATED FILE -->
    <?php 
    $cssFile = __DIR__ . '/../assets/css/main-new.css';
    $cssVersion = file_exists($cssFile) ? md5_file($cssFile) : time();
    ?>
    <link rel="stylesheet" href="<?php echo asset('assets/css/main-new.css'); ?>?v=<?php echo time(); ?>&rev=<?php echo substr($cssVersion, 0, 8); ?>">
    
    <!-- Temporary: Header Styles (Legacy) - REMOVED to fix conflicts -->
    <!-- <link rel="stylesheet" href="<?php echo asset('assets/css/premium-header.css'); ?>?v=<?php echo time(); ?>"> -->

    <!-- NEW: World Class Header & Hero Styles -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/pages/home-hero.css'); ?>?v=<?php echo time(); ?>">
    
    <!-- NEW: Modern Footer Styles -->
    <link rel="stylesheet" href="<?php echo asset('assets/css/layouts/modern-footer.css'); ?>?v=<?php echo time(); ?>">
    
    <?php if (isset($additionalCSS) && is_array($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?php echo asset($css); ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Top Bar (Restored & Enhanced) -->
    <div class="top-bar bg-dark text-white py-1">
        <div class="container">
            <div class="row align-items-center">
                <!-- 1. Contact Info & Socials (Left) -->
                <div class="col-md-4 d-flex align-items-center gap-3">
                    <a href="tel:+8618320709024" class="text-white text-decoration-none d-flex align-items-center gap-2" style="font-size: 0.85rem;">
                        <i class="fas fa-phone-alt text-warning"></i> 
                        <span class="fw-bold">+86 183 2070 9024</span>
                    </a>
                    <div class="d-flex gap-2 border-start ps-3 border-secondary">
                        <a href="#" class="text-white hover-primary"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white hover-primary"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white hover-primary"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                
                <!-- Center: Flash Sale Timer -->
                <div class="col-md-4 text-center">
                    <span class="fw-bold text-uppercase text-danger me-2">Flash Sale Ends In:</span>
                    <span id="flash-timer" class="fw-bold font-monospace text-warning">Loading...</span>
                </div>
                
                <!-- Right: Links -->
                <div class="col-md-4 text-end">
                    <a href="<?php echo BASE_URL; ?>/user/procurement/quotes/view.php" class="text-white text-decoration-none me-3 hover-primary">Track Order</a>
                    <a href="<?php echo BASE_URL; ?>/help.php" class="text-white text-decoration-none hover-primary">Help Center</a>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Simple Flash Sale Timer
        (function() {
            // Target: 3 months from 22/01/2026 -> 22/04/2026
            const targetDate = new Date('2026-04-22T00:00:00').getTime();
            
            function updateTimer() {
                const now = new Date().getTime();
                const distance = targetDate - now;
                const timerEl = document.getElementById('flash-timer');
                
                if (!timerEl) return;
                
                if (distance < 0) {
                    timerEl.innerHTML = "EXPIRED";
                    return;
                }
                
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                // Format with leading zeros
                const d = days;
                const h = hours < 10 ? '0'+hours : hours;
                const m = minutes < 10 ? '0'+minutes : minutes;
                const s = seconds < 10 ? '0'+seconds : seconds;
                
                timerEl.innerHTML = `${d}d : ${h}h : ${m}m : ${s}s`;
            }
            
            setInterval(updateTimer, 1000);
            document.addEventListener('DOMContentLoaded', updateTimer); // Initial run
        })();
    </script>

    <!-- Main Header Section (Unified & Sticky) -->
    <header class="main-header sticky-top bg-white border-bottom shadow-sm" style="top: 0; z-index: 1020;">
        <div class="container">
            <div class="d-flex align-items-center justify-content-between py-2">
                <!-- 1. Logo (Left) -->
                <a href="<?php echo BASE_URL; ?>" class="navbar-brand me-4 d-flex align-items-center">
                    <img src="<?php echo asset('assets/images/logos/2025-06-02-683db744bd48b.webp'); ?>" alt="<?php echo APP_NAME; ?>" style="height: 38px; width: auto;">
                </a>
                
                <!-- 2. Navigation (Center) - Visible on MD+ screens -->
                <nav class="desktop-nav align-items-center gap-3 gap-lg-4 mx-auto">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>">Home</a>
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'shop.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/shop.php">Shop</a>
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/">Money Transfer</a>
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/logistics/booking/">Logistics</a>
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/modules/procurement/request/">Procurement</a>
                </nav>
                
                <!-- 3. Actions (Right) -->
                <div class="user-actions d-flex align-items-center gap-2 gap-lg-3">
                    <?php 
                    // Cart Count Logic
                     $cartCount = 0;
                     if (isset($conn)) {
                         try {
                             if (isset($_SESSION['user_id'])) {
                                 // Logged in user
                                 $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = ?");
                                 $stmt->execute([$_SESSION['user_id']]);
                                 $res = $stmt->fetch();
                                 $cartCount = $res['count'] ?? 0;
                             } else {
                                 // Guest user (session_id)
                                 $sessParams = session_get_cookie_params();
                                 $currentSessionId = session_id();
                                 if ($currentSessionId) {
                                     $stmt = $conn->prepare("SELECT SUM(quantity) as count FROM cart WHERE session_id = ?");
                                     $stmt->execute([$currentSessionId]);
                                     $res = $stmt->fetch();
                                     $cartCount = $res['count'] ?? 0;
                                 }
                             }
                         } catch (Exception $e) {
                             // Silent fail
                         }
                     }
                    ?>

                    <!-- Search Toggle -->
                    <div class="action-item">
                        <button class="btn btn-link text-dark p-0" onclick="document.getElementById('header-search-bar').classList.toggle('d-none');">
                            <i class="fas fa-search fa-lg"></i>
                        </button>
                    </div>
                
                    <!-- Account -->
                    <div class="action-item">
                        <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                            <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="action-link text-dark">
                                <i class="far fa-user fa-lg"></i>
                            </a>
                        <?php else: ?>
                            <a href="<?php echo BASE_URL; ?>/login.php" class="action-link text-dark">
                                <i class="far fa-user fa-lg"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Cart -->
                    <div class="action-item">
                        <a href="<?php echo BASE_URL; ?>/cart.php" class="action-link position-relative text-dark">
                            <i class="fas fa-shopping-cart fa-lg"></i>
                            <span class="cart-badge-custom">
                                <?php echo isset($cartCount) ? $cartCount : '0'; ?>
                            </span>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Expandable Search Bar -->
            <div id="header-search-bar" class="d-none pb-3">
                 <form action="<?php echo BASE_URL; ?>/shop.php" method="GET">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0" placeholder="Search for products..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button class="btn btn-dark" type="submit">Search</button>
                    </div>
                </form>
            </div>
        </div>
    </header>

    <!-- Flash Messages -->
    <?php 
    if (function_exists('getFlashMessage')) {
        $flash = getFlashMessage();
        if ($flash): 
        ?>
        <div class="container mt-3">
            <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php 
        endif; 
    }
    ?>
    
    <!-- Mobile Bottom Menu (shown only on mobile) -->
    <?php include __DIR__ . '/mobile-menu.php'; ?>
