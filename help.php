<?php
/**
 * Help Center Page
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

$pageTitle = 'Help Center - ' . APP_NAME;
$pageDescription = 'Get help and support for ' . APP_NAME . ' - Find answers to frequently asked questions and contact our support team.';

// Include header
include __DIR__ . '/includes/header.php';
?>

<!-- Styles -->
<link rel="stylesheet" href="<?php echo asset('assets/css/pages/help.css'); ?>?v=<?php echo time(); ?>">

<div class="help-page">
    <!-- Hero Section -->
    <section class="help-hero">
        <div class="container">
            <div class="row justify-content-center text-center">
                <div class="col-lg-8">
                    <h1 class="help-hero__title">How can we help you?</h1>
                    <p class="help-hero__subtitle">Find answers to common questions or contact our support team directly.</p>
                    
                    <!-- Search Box (Visual Only for now) -->
                    <div class="help-search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="Search for help topics, e.g., 'Track Order', 'Returns'">
                        <button class="btn btn-primary">Search</button>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Quick Contact Bar -->
    <div class="quick-contact-bar">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="contact-item">
                        <div class="icon-wrapper">
                            <i class="fas fa-phone-alt"></i>
                        </div>
                        <div class="details">
                            <span class="label">Call Us</span>
                            <a href="tel:+8618320709024" class="value">+86 183 2070 9024</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <div class="contact-item">
                        <div class="icon-wrapper">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="details">
                            <span class="label">Email Us</span>
                            <a href="mailto:support@thinqshopping.com" class="value">support@thinqshopping.com</a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="contact-item">
                        <div class="icon-wrapper">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="details">
                            <span class="label">Working Hours</span>
                            <span class="value">Mon - Sat: 9AM - 8PM</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Help Categories -->
    <section class="help-content-section">
        <div class="container">
            <h2 class="section-title">Browse by Topic</h2>
            <div class="help-topics-grid">
                <!-- Shopping & Account -->
                <div class="help-card">
                    <div class="help-card__icon"><i class="fas fa-shopping-bag"></i></div>
                    <h3 class="help-card__title">Shopping & Account</h3>
                    <ul class="help-card__links">
                        <li><a href="<?php echo BASE_URL; ?>/shop.php">Browse Products</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/login.php">Create Account / Login</a></li>
                        <li><a href="#">Forgot Password</a></li>
                        <li><a href="#">My Wishlist</a></li>
                    </ul>
                </div>

                <!-- Shipping & Logistics -->
                <div class="help-card">
                    <div class="help-card__icon"><i class="fas fa-shipping-fast"></i></div>
                    <h3 class="help-card__title">Shipping & Logistics</h3>
                    <ul class="help-card__links">
                        <li><a href="<?php echo BASE_URL; ?>/public/track.php">Track Your Order</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/modules/logistics/booking/">Logistics Services</a></li>
                        <li><a href="#">Shipping Rates & Delivery Times</a></li>
                        <li><a href="#">International Shipping (China to Ghana)</a></li>
                    </ul>
                </div>

                <!-- Services -->
                <div class="help-card">
                    <div class="help-card__icon"><i class="fas fa-concierge-bell"></i></div>
                    <h3 class="help-card__title">Our Services</h3>
                    <ul class="help-card__links">
                        <li><a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/">Money Transfer</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/modules/procurement/request/">Procurement Request</a></li>
                        <li><a href="#">Supplier Verification</a></li>
                        <li><a href="#">Business Solutions</a></li>
                    </ul>
                </div>

                <!-- Returns & Policies -->
                <div class="help-card">
                    <div class="help-card__icon"><i class="fas fa-undo-alt"></i></div>
                    <h3 class="help-card__title">Returns & Policies</h3>
                    <ul class="help-card__links">
                        <li><a href="#">Return Policy</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/privacy.php">Privacy Policy</a></li>
                        <li><a href="<?php echo BASE_URL; ?>/terms.php">Terms of Service</a></li>
                        <li><a href="#">Refund Process</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="faq-section">
        <div class="container">
            <h2 class="section-title">Frequently Asked Questions</h2>
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion custom-accordion" id="helpAccordion">
                        
                        <!-- FAQ 1 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    How do I track my order?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    You can track your order status in real-time by entering your order ID on our <a href="<?php echo BASE_URL; ?>/public/track.php">Tracking Page</a>. You will also receive email updates as your shipment progresses.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 2 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    How does the Money Transfer service work?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    Our Money Transfer service allows you to securely send payments to suppliers in China. Simply fill out the <a href="<?php echo BASE_URL; ?>/modules/money-transfer/transfer-form/">Transfer Request Form</a>, and our team will handle the exchange and transfer at competitive rates.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 3 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Can you help me buy products from China websites?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    Yes! With our <a href="<?php echo BASE_URL; ?>/modules/procurement/request/">Procurement Service</a>, you can send us links or descriptions of products you want to buy from platforms like 1688, Taobao, or Alibaba. We will purchase, inspect, and ship them to you.
                                </div>
                            </div>
                        </div>

                        <!-- FAQ 4 -->
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    What is your return policy?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    We accept returns for defective items or incorrect orders within 7 days of delivery. Items must be in their original condition. Please contact our support team to initiate a return.
                                </div>
                            </div>
                        </div>

                         <!-- FAQ 5 -->
                         <div class="accordion-item">
                            <h2 class="accordion-header" id="headingFive">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                    Do you offer shipping to my door?
                                </button>
                            </h2>
                            <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#helpAccordion">
                                <div class="accordion-body">
                                    Yes, we offer door-to-door delivery services for many locations. You can also choose to pick up your goods from our local warehouse or partner centers.
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Still Need Help -->
    <section class="still-need-help">
        <div class="container text-center">
            <h3>Still need help?</h3>
            <p>Our support team is just a message away.</p>
            <a href="<?php echo BASE_URL; ?>/contact.php" class="btn btn-dark btn-lg px-5">Contact Us</a>
        </div>
    </section>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
