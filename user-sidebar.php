<?php
/**
 * User Dashboard Sidebar Component
 * Modern Premium Design
 */

$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$user = getCurrentUser();

// Get user profile
if ($userId) {
    $db = new Database();
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
}

$userName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
if (empty($userName)) {
    $userName = $user['email'] ?? 'User';
}
$userInitials = strtoupper(substr($userName, 0, 1));
?>

<div class="user-sidebar" id="userSidebar">
    <!-- Logo Section -->
    <div class="logo-section">
        <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="logo">
            <img src="<?php echo asset('assets/images/logos/2025-06-02-683db744bd48b.webp'); ?>" alt="<?php echo APP_NAME; ?>">
        </a>
    </div>

    <!-- Menu Section -->
    <div class="menu-section">
        <div class="section-title">Menu</div>
        
        <a href="<?php echo BASE_URL; ?>/user/dashboard.php" 
           class="menu-item <?php echo ($currentPage === 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Overview</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/user/orders/" 
           class="menu-item <?php echo (strpos($currentPath, '/orders') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-shopping-bag"></i>
            <span>My Orders</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/user/transfers/" 
           class="menu-item <?php echo (strpos($currentPath, '/transfers') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-exchange-alt"></i>
            <span>Money Transfers</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/user/shipments/" 
           class="menu-item <?php echo (strpos($currentPath, '/shipments') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-truck"></i>
            <span>My Shipments</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/user/procurement/" 
           class="menu-item <?php echo (strpos($currentPath, '/procurement') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            <span>Procurement</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/user/wallet.php" 
           class="menu-item <?php echo ($currentPage === 'wallet.php') ? 'active' : ''; ?>">
            <i class="fas fa-wallet"></i>
            <span>My Wallet</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/user/tickets/" 
           class="menu-item <?php echo (strpos($currentPath, '/tickets') !== false) ? 'active' : ''; ?>">
            <i class="fas fa-ticket-alt"></i>
            <span>Support Tickets</span>
        </a>
        
        <a href="<?php echo BASE_URL; ?>/user/profile.php" 
           class="menu-item <?php echo ($currentPage === 'profile.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>

    <!-- Profile Section -->
    <div class="profile-section">
        <div class="section-title">Profile</div>
        
        <div class="profile-info">
            <div class="profile-avatar">
                <?php 
                $profileImage = $profile['profile_image'] ?? null;
                $imagePath = __DIR__ . '/assets/images/profiles/' . ($profileImage ?? '');
                if ($profileImage && file_exists($imagePath) && filesize($imagePath) > 0): 
                ?>
                    <img src="<?php echo BASE_URL; ?>/assets/images/profiles/<?php echo htmlspecialchars($profileImage); ?>?v=<?php echo time(); ?>" 
                         alt="<?php echo htmlspecialchars($userName); ?>" 
                         style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                <?php else: ?>
                    <?php echo $userInitials; ?>
                <?php endif; ?>
            </div>
            <div class="profile-details">
                <div class="profile-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="profile-role">User Account</div>
            </div>
        </div>
        
        <a href="<?php echo BASE_URL; ?>/logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleUserSidebar()"></div>

<script>
function toggleUserSidebar() {
    const sidebar = document.getElementById('userSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    sidebar.classList.toggle('active');
    overlay.classList.toggle('active');
}

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    const sidebar = document.getElementById('userSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    if (window.innerWidth <= 768) {
        if (!sidebar.contains(e.target) && !e.target.matches('.sidebar-toggle')) {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        }
    }
});
</script>





