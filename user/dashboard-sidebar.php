<?php
/**
 * User Dashboard Sidebar (Reusable)
 * ThinQShopping Platform
 */
?>

<div class="card">
    <div class="card-body text-center">
        <div class="mb-3">
            <i class="fas fa-user-circle fa-4x text-muted"></i>
        </div>
        <h5><?php echo htmlspecialchars(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')); ?></h5>
        <p class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
    </div>
    <ul class="list-group list-group-flush">
        <li class="list-group-item">
            <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="text-decoration-none">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <li class="list-group-item">
            <a href="<?php echo BASE_URL; ?>/user/profile.php" class="text-decoration-none">
                <i class="fas fa-user me-2"></i> My Profile
            </a>
        </li>
        <li class="list-group-item">
            <a href="<?php echo BASE_URL; ?>/user/orders/" class="text-decoration-none">
                <i class="fas fa-shopping-bag me-2"></i> My Orders
            </a>
        </li>
        <li class="list-group-item">
            <a href="<?php echo BASE_URL; ?>/user/transfers/" class="text-decoration-none">
                <i class="fas fa-exchange-alt me-2"></i> Money Transfers
            </a>
        </li>
        <li class="list-group-item">
            <a href="<?php echo BASE_URL; ?>/user/shipments/" class="text-decoration-none">
                <i class="fas fa-truck me-2"></i> My Shipments
            </a>
        </li>
        <li class="list-group-item">
            <a href="<?php echo BASE_URL; ?>/user/procurement/" class="text-decoration-none">
                <i class="fas fa-box me-2"></i> Procurement
            </a>
        </li>
        <li class="list-group-item">
            <a href="<?php echo BASE_URL; ?>/user/wallet.php" class="text-decoration-none">
                <i class="fas fa-wallet me-2"></i> My Wallet
            </a>
        </li>
        <li class="list-group-item">
            <a href="<?php echo BASE_URL; ?>/logout.php" class="text-decoration-none text-danger">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>

