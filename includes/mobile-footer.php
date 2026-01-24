<?php
/**
 * Mobile Footer
 * Modern mobile-optimized footer design
 */

// Get cart count for mobile
$cartCount = 0;
if (isLoggedIn() && isset($conn)) {
    $stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    $cartCount = intval($result['total'] ?? 0);
}
?>

<!-- Mobile Footer (Only visible on mobile) -->
<footer class="mobile-footer d-lg-none">
    <!-- Quick Links Section -->
    <div class="mobile-footer__section">
        <div class="container py-3">
            <div class="row g-3">
                <!-- Quick Links -->
                <div class="col-6">
                    <h6 class="mobile-footer__heading">Quick Links</h6>
                    <ul class="mobile-footer__links">
                        <li><a href="<?php echo BASE_URL; ?>">Home</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/shop.php">Shop</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/about.php">About Us</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/contact.php">Contact</a></li>
                    </ul>
                </div>
                
                <!-- Services -->
                <div class="col-6">
                    <h6 class="mobile-footer__heading">Services</h6>
                    <ul class="mobile-footer__links">
                        <li><a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/">Money Transfer</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/modules/logistics/booking/">Send Parcel</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/modules/procurement/request/">Procurement</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/public/track.php">Tracking</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Contact Section -->
    <div class="mobile-footer__section mobile-footer__section--light">
        <div class="container py-3">
            <div class="mobile-footer__contact">
                <a href="tel:+8618320709024" class="mobile-contact-item">
                    <i class="fas fa-phone"></i>
                    <span>Call Us</span>
                </a>
                <a href="https://wa.me/8618320709024" target="_blank" class="mobile-contact-item">
                    <i class="fab fa-whatsapp"></i>
                    <span>WhatsApp</span>
                </a>
                <a href="mailto:<?php echo BUSINESS_EMAIL; ?>" class="mobile-contact-item">
                    <i class="fas fa-envelope"></i>
                    <span>Email</span>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Copyright & Payment -->
    <div class="mobile-footer__section mobile-footer__bottom">
        <div class="container py-3">
            <div class="text-center">
                <p class="mobile-footer__copyright mb-2">
                    &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.
                </p>
                <div class="mobile-footer__payment">
                    <i class="fab fa-cc-visa" title="Visa"></i>
                    <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                    <i class="fas fa-mobile-alt" title="Mobile Money"></i>
                </div>
            </div>
        </div>
    </div>
</footer>




