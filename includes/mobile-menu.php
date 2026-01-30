<?php
// Prevent duplicate includes
if (isset($mobileMenuIncluded)) {
    return;
}
$mobileMenuIncluded = true;
?>
<!-- Premium Bottom Mobile Menu (Fixed) -->
<nav class="mobile-bottom-menu d-md-none fixed-bottom">
    <div class="mobile-menu-backdrop"></div>
    <div class="mobile-menu-container">
        <div class="mobile-menu-items">
            <a href="<?php echo BASE_URL; ?>/shop.php" class="mobile-menu-item <?php echo (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'shop') !== false) ? 'active' : ''; ?>">
                <div class="menu-item-icon-wrapper">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <span class="menu-item-label">Shop</span>
                <div class="menu-item-indicator"></div>
            </a>
            <a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/" class="mobile-menu-item <?php echo (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'money-transfer') !== false || strpos($_SERVER['REQUEST_URI'], 'transfer') !== false) ? 'active' : ''; ?>">
                <div class="menu-item-icon-wrapper">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <span class="menu-item-label">Send</span>
                <div class="menu-item-indicator"></div>
            </a>
            <a href="<?php echo BASE_URL; ?>/modules/logistics/booking/" class="mobile-menu-item <?php echo (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'logistics') !== false) ? 'active' : ''; ?>">
                <div class="menu-item-icon-wrapper">
                    <i class="fas fa-truck"></i>
                </div>
                <span class="menu-item-label">Logistic</span>
                <div class="menu-item-indicator"></div>
            </a>
            <a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="mobile-menu-item <?php echo (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'procurement') !== false) ? 'active' : ''; ?>">
                <div class="menu-item-icon-wrapper">
                    <i class="fas fa-box"></i>
                </div>
                <span class="menu-item-label">Procure</span>
                <div class="menu-item-indicator"></div>
            </a>
        </div>
    </div>
</nav>




