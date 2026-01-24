<?php
/**
 * Admin Dashboard Sidebar Component
 * Modern Premium Design
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
?>

<!-- Admin Sidebar Styles now in admin-dashboard.css -->

<div class="admin-sidebar" id="adminSidebar">
    <!-- Logo Section -->
    <div class="logo-section">
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" class="logo">
            <img src="<?php echo asset('assets/images/logos/2025-06-02-683db744bd48b.webp'); ?>" alt="<?php echo APP_NAME; ?>">
        </a>
    </div>

    <!-- Menu Section -->
    <div class="menu-section">
        <div class="section-title">Menu</div>
        
        <!-- DEBUG: Sidebar version 2.1 - Updated with tickets and email settings - <?php echo date('Y-m-d H:i:s'); ?> -->
        
        <a href="<?php echo BASE_URL; ?>/admin/dashboard.php" 
           class="menu-item <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Overview</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/ecommerce/products.php" 
           class="menu-item <?php echo (strpos($currentPath, '/products') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>Product</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/users/manage.php" 
           class="menu-item <?php echo (strpos($currentPath, '/users') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-users"></i>
            <span>Customers</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/ecommerce/orders.php" 
           class="menu-item <?php echo (strpos($currentPath, '/orders') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-list"></i>
            <span>Order</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/money-transfer/transfers.php" 
           class="menu-item <?php echo (strpos($currentPath, '/money-transfer') !== false || strpos($currentPath, '/transfers') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt"></i>
            <span>Money Transfer</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/logistics/shipments.php" 
           class="menu-item <?php echo (strpos($currentPath, '/logistics') !== false || strpos($currentPath, '/shipments') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i>
            <span>Logistics</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/procurement/requests.php" 
           class="menu-item <?php echo (strpos($currentPath, '/procurement') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Procurement</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/payments/transactions.php" 
           class="menu-item <?php echo (strpos($currentPath, '/payments') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-credit-card"></i>
            <span>Payments</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/wallet/manage.php" 
           class="menu-item <?php echo (strpos($currentPath, '/wallet') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i>
            <span>Wallet Management</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/tickets/" 
           class="menu-item <?php echo (strpos($currentPath, '/tickets') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-ticket-alt"></i>
            <span>Ticket Management</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/settings/general.php" 
           class="menu-item <?php echo (strpos($currentPath, '/settings') !== false && strpos($currentPath, '/settings/email') === false && strpos($currentPath, '/settings/warehouses') === false && strpos($currentPath, '/settings/shipping-rates') === false && strpos($currentPath, '/settings/categories') === false) ? 'active' : ''; ?>">
            <i class="fas fa-cog"></i>
            <span>Settings</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/settings/categories.php" 
           class="menu-item <?php echo (strpos($currentPath, '/settings/categories') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-tags"></i>
            <span>Categories</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/settings/warehouses.php" 
           class="menu-item <?php echo (strpos($currentPath, '/settings/warehouses') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-warehouse"></i>
            <span>Warehouses</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/settings/shipping-rates.php" 
           class="menu-item <?php echo (strpos($currentPath, '/settings/shipping-rates') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-shipping-fast"></i>
            <span>Shipping Rates</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/settings/email-settings.php" 
           class="menu-item <?php echo (strpos($currentPath, '/email-settings') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>
            <span>Email Settings</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/admin/settings/email-templates.php" 
           class="menu-item <?php echo (strpos($currentPath, '/email-templates') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Email Templates</span>
        </a>
    </div>

    <!-- Profile Section -->
    <div class="profile-section">
        <div class="section-title">Profile</div>
        
        <div class="profile-info">
            <div class="profile-avatar">
                <?php 
                $initials = strtoupper(substr($_SESSION['admin_username'] ?? 'A', 0, 1));
                echo $initials;
                ?>
            </div>
            <div class="profile-details">
                <div class="profile-name"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?></div>
                <div class="profile-role">Admin Account</div>
            </div>
        </div>
        
        <a href="<?php echo BASE_URL; ?>/admin/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleAdminSidebar()"></div>

<script>
function toggleAdminSidebar() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const toggleBtn = document.querySelector('.sidebar-toggle');
    
    if (window.innerWidth <= 991) {
        if (sidebar && overlay && !sidebar.contains(e.target) && !toggleBtn?.contains(e.target)) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    }
});
</script>
