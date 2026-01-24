<?php
/**
 * About Us Page
 * ThinQShopping Platform
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$pageTitle = 'About Us - ' . APP_NAME;
$pageDescription = 'Learn more about ' . APP_NAME . ' - Your trusted bridge for commerce between China and Ghana.';

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Reuse Help/Premium Page Styles -->
<link rel="stylesheet" href="<?php echo asset('assets/css/pages/help.css'); ?>?v=<?php echo time(); ?>">

<div class="help-page">
    <!-- Hero Section -->
    <section class="help-hero">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-9">
                    <span class="d-inline-block text-white px-3 py-1 rounded-pill mb-3" style="background: rgba(255,255,255,0.1); font-weight: 600; font-size: 0.85rem;">ESTABLISHED 2024</span>
                    <h1 class="help-hero__title">Bridging Commerce Between China & Ghana</h1>
                    <p class="help-hero__subtitle" style="font-size: 1.25rem;">We provide seamless shopping, secure payments, and reliable logistics for individuals and businesses.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Our Mission -->
    <section class="py-5 bg-white">
        <div class="container py-4">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <div class="position-relative">
                        <img src="<?php echo asset('assets/images/about-mission.jpg'); ?>" alt="Our Mission" class="img-fluid rounded-4 shadow-sm" onerror="this.src='https://images.unsplash.com/photo-1578575437130-527eed3abbec?q=80&w=2070&auto=format&fit=crop'">
                         <div class="position-absolute bottom-0 start-0 m-4 p-4 bg-white rounded-3 shadow-lg" style="max-width: 300px;">
                            <div class="d-flex align-items-center gap-3">
                                <i class="fas fa-users text-primary fa-2x"></i>
                                <div>
                                    <h5 class="fw-bold mb-0">Customer First</h5>
                                    <p class="small text-muted mb-0">Dedicated support team</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="text-primary fw-bold text-uppercase ls-1">Who We Are</h6>
                    <h2 class="fw-bold mb-4" style="color: #0e2945; font-size: 2.5rem;">Your Trusted Trade Partner</h2>
                    <p class="lead text-muted mb-4">ThinQShopping is not just an online store; we are a comprehensive trade facilitation platform designed to simplify cross-border commerce.</p>
                    <p class="text-muted mb-4">
                        Founded with a vision to eliminate the barriers of international trade, we connect Ghanaian businesses and consumers directly with the vast manufacturing capabilities of China. From simple online purchases to complex procurement and logistics, we handle it all with transparency and efficiency.
                    </p>
                    
                    <div class="row g-4 mt-2">
                        <div class="col-6">
                            <div class="border-start border-4 border-primary ps-3">
                                <h3 class="fw-bold mb-1" style="color: #0e2945;">100%</h3>
                                <p class="text-muted small mb-0">Secure Transactions</p>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="border-start border-4 border-primary ps-3">
                                <h3 class="fw-bold mb-1" style="color: #0e2945;">24/7</h3>
                                <p class="text-muted small mb-0">Support Available</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Grid -->
    <section class="help-content-section" style="background-color: #f8f9fa;">
        <div class="container">
            <div class="text-center mb-5">
                <h6 class="text-primary fw-bold text-uppercase ls-1">What We Do</h6>
                <h2 class="fw-bold" style="color: #0e2945;">Our Core Services</h2>
            </div>
            
            <div class="help-topics-grid">
                <!-- E-Commerce -->
                <div class="help-card text-center h-100">
                    <div class="help-card__icon mx-auto"><i class="fas fa-shopping-cart"></i></div>
                    <h3 class="help-card__title">E-Commerce</h3>
                    <p class="text-muted mb-4">Shop thousands of products directly from China with local payment methods and reliable delivery to your doorstep.</p>
                    <a href="<?php echo BASE_URL; ?>/shop.php" class="btn btn-outline-primary rounded-pill">Shop Now</a>
                </div>

                <!-- Money Transfer -->
                <div class="help-card text-center h-100">
                    <div class="help-card__icon mx-auto"><i class="fas fa-exchange-alt"></i></div>
                    <h3 class="help-card__title">Money Transfer</h3>
                    <p class="text-muted mb-4">Securely pay suppliers in China using Ghana Cedis. We handle the currency exchange and remittance fast and safely.</p>
                    <a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/" class="btn btn-outline-primary rounded-pill">Send Money</a>
                </div>

                <!-- Logistics -->
                <div class="help-card text-center h-100">
                    <div class="help-card__icon mx-auto"><i class="fas fa-plane-departure"></i></div>
                    <h3 class="help-card__title">Logistics & Shipping</h3>
                    <p class="text-muted mb-4">Air and Sea cargo services from Guangzhou to Accra. Track your shipments in real-time with our advanced system.</p>
                    <a href="<?php echo BASE_URL; ?>/modules/logistics/booking/" class="btn btn-outline-primary rounded-pill">Book Shipping</a>
                </div>

                <!-- Procurement -->
                <div class="help-card text-center h-100">
                    <div class="help-card__icon mx-auto"><i class="fas fa-search-dollar"></i></div>
                    <h3 class="help-card__title">Procurement</h3>
                    <p class="text-muted mb-4">Let us source, verify, and buy goods for you from platforms like 1688, Taobao, and Alibaba. We ensure quality.</p>
                    <a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="btn btn-outline-primary rounded-pill">Request Sourcing</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Features / Values -->
    <section class="py-5 bg-white">
        <div class="container py-4">
             <div class="row g-4 text-center">
                 <div class="col-md-4">
                     <div class="p-4 rounded-4 bg-light h-100">
                         <i class="fas fa-shield-alt fa-3x text-primary mb-3"></i>
                         <h4 class="fw-bold" style="color: #0e2945;">Trust & Security</h4>
                         <p class="text-muted">Your funds and goods are safe with us. We use encrypted payments and verified logistics channels.</p>
                     </div>
                 </div>
                 <div class="col-md-4">
                     <div class="p-4 rounded-4 bg-light h-100">
                         <i class="fas fa-rocket fa-3x text-primary mb-3"></i>
                         <h4 class="fw-bold" style="color: #0e2945;">Speed & Efficiency</h4>
                         <p class="text-muted">We understand that time is money. Our diversified shipping options ensure you meet your deadlines.</p>
                     </div>
                 </div>
                 <div class="col-md-4">
                     <div class="p-4 rounded-4 bg-light h-100">
                         <i class="fas fa-headset fa-3x text-primary mb-3"></i>
                         <h4 class="fw-bold" style="color: #0e2945;">Expert Support</h4>
                         <p class="text-muted">Our team speaks both English and Chinese, bridging the communication gap for smoother business.</p>
                     </div>
                 </div>
             </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="py-5 text-center text-white" style="background-color: #0e2945;">
        <div class="container">
            <h2 class="fw-bold mb-3">Ready to grow your business?</h2>
            <p class="lead mb-4 opacity-75">Join thousands of customers who trust ThinQShopping for their China-Ghana trade needs.</p>
            <a href="<?php echo BASE_URL; ?>/register.php" class="btn btn-light btn-lg px-5 rounded-pill fw-bold" style="color: #0e2945;">Create Free Account</a>
        </div>
    </section>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
