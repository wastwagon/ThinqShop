<!-- 
    Premium World-Class Header
    Responsive: Mobile, Tablet, Desktop
    Brand Color: #0e2945
-->

<header class="main-header">
    <div class="header-container">
        <!-- Top Row: Logo, Search, Actions -->
        <div class="header-top-row">
            <!-- Logo -->
            <div class="header-logo">
                <a href="<?php echo BASE_URL; ?>">
                    <img src="<?php echo asset('assets/images/logo.png'); ?>" alt="<?php echo APP_NAME; ?>">
                </a>
            </div>
            
            <!-- Search Bar (Centered) -->
            <div class="header-search">
                <form action="<?php echo BASE_URL; ?>search.php" method="GET" class="header-search-form">
                    <input 
                        type="text" 
                        name="q" 
                        class="header-search-input" 
                        placeholder="Search products..."
                        autocomplete="off"
                        aria-label="Search products"
                    >
                    <button type="submit" class="header-search-btn" aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <!-- Actions (Right) -->
            <div class="header-actions">
                <!-- Account -->
                <a href="<?php echo BASE_URL; ?>dashboard.php" class="header-action-item">
                    <div class="header-action-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <span class="header-action-label">Account</span>
                </a>
                
                <!-- Wishlist -->
                <a href="<?php echo BASE_URL; ?>wishlist.php" class="header-action-item">
                    <div class="header-action-icon">
                        <i class="fas fa-heart"></i>
                        <?php if (isset($_SESSION['wishlist_count']) && $_SESSION['wishlist_count'] > 0): ?>
                            <span class="header-action-badge"><?php echo $_SESSION['wishlist_count']; ?></span>
                        <?php endif; ?>
                    </div>
                    <span class="header-action-label">Wishlist</span>
                </a>
                
                <!-- Cart -->
                <a href="<?php echo BASE_URL; ?>cart.php" class="header-action-item">
                    <div class="header-action-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <?php if (isset($_SESSION['cart_count']) && $_SESSION['cart_count'] > 0): ?>
                            <span class="header-action-badge"><?php echo $_SESSION['cart_count']; ?></span>
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
                    <a href="<?php echo BASE_URL; ?>category.php?cat=electronics" class="header-nav-link">
                        Electronics & Gadgets
                    </a>
                </div>
                <div class="header-nav-item">
                    <a href="<?php echo BASE_URL; ?>category.php?cat=home-appliance" class="header-nav-link">
                        Home Appliance
                    </a>
                </div>
                <div class="header-nav-item">
                    <a href="<?php echo BASE_URL; ?>category.php?cat=household" class="header-nav-link">
                        House and Household
                    </a>
                </div>
                <div class="header-nav-item">
                    <a href="<?php echo BASE_URL; ?>category.php?cat=fashion" class="header-nav-link">
                        Fashion
                    </a>
                </div>
                <div class="header-nav-item">
                    <a href="<?php echo BASE_URL; ?>category.php?cat=photography" class="header-nav-link">
                        Photography
                    </a>
                </div>
            </nav>
        </div>
    </div>
</header>

<!-- Mobile Bottom Navigation (Already exists in mobile-menu.php) -->
