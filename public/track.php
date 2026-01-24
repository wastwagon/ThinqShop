<?php
/**
 * Unified Tracking Page
 * Track Orders, Transfers, or Parcels
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../includes/functions.php';

$pageTitle = 'Track - ' . APP_NAME;
include __DIR__ . '/../../includes/header.php';
?>

<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="text-center mb-5">Track Your Order, Transfer, or Parcel</h2>
            
            <div class="row g-4">
                <!-- Track Order -->
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-shopping-bag fa-4x text-primary mb-3"></i>
                            <h5 class="card-title">Track Order</h5>
                            <p class="card-text text-muted">Track your e-commerce order</p>
                            <?php if (isLoggedIn()): ?>
                                <a href="<?php echo BASE_URL; ?>/user/orders/" class="btn btn-primary">View My Orders</a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-primary">Login to Track</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Track Money Transfer -->
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-exchange-alt fa-4x text-success mb-3"></i>
                            <h5 class="card-title">Track Money Transfer</h5>
                            <p class="card-text text-muted">Track your Ghana-China money transfer</p>
                            <a href="<?php echo BASE_URL; ?>/public/track-transfer.php" class="btn btn-success">Track Transfer</a>
                        </div>
                    </div>
                </div>
                
                <!-- Track Parcel -->
                <div class="col-md-4">
                    <div class="card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-truck fa-4x text-info mb-3"></i>
                            <h5 class="card-title">Track Parcel</h5>
                            <p class="card-text text-muted">Track your parcel delivery</p>
                            <a href="<?php echo BASE_URL; ?>/public/track-parcel.php" class="btn btn-info">Track Parcel</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/footer.php'; ?>

