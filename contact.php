<?php
/**
 * Contact Us Page
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

$pageTitle = 'Contact Us - ' . APP_NAME;
$pageDescription = 'Get in touch with ' . APP_NAME . ' support team for assistance with shopping, money transfers, logistics, and procurement services.';

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
                <div class="col-lg-8">
                    <h1 class="help-hero__title">Contact Us</h1>
                    <p class="help-hero__subtitle">Have a question or need assistance? Our team is available 24/7 to help you.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Content -->
    <section class="help-content-section" style="margin-top: -60px; padding-top: 0;">
        <div class="container">
            <div class="row g-4">
                <!-- Contact Info Cards -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden;">
                        <div class="card-body p-4">
                            <h4 class="fw-bold mb-4" style="color: #0e2945;">Get in Touch</h4>
                            
                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0" style="width: 40px; height: 40px; background: rgba(14, 41, 69, 0.05); color: #0e2945; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                    <i class="fas fa-phone-alt"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="fw-bold mb-1" style="color: #1e293b;">Phone</h6>
                                    <p class="mb-0 text-muted">
                                        <a href="tel:+8618320709024" class="text-decoration-none fw-bold" style="color: #0e2945;">+86 183 2070 9024</a>
                                    </p>
                                    <small class="text-muted">Expert Advice & Support</small>
                                </div>
                            </div>

                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0" style="width: 40px; height: 40px; background: rgba(14, 41, 69, 0.05); color: #0e2945; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                    <i class="fas fa-envelope"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="fw-bold mb-1" style="color: #1e293b;">Email</h6>
                                    <p class="mb-0 text-muted">
                                        <a href="mailto:support@thinqshopping.com" class="text-decoration-none" style="color: #0e2945;">support@thinqshopping.com</a>
                                    </p>
                                    <small class="text-muted">We reply within 24 hours</small>
                                </div>
                            </div>

                            <div class="d-flex mb-4">
                                <div class="flex-shrink-0" style="width: 40px; height: 40px; background: rgba(14, 41, 69, 0.05); color: #0e2945; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
                                    <i class="fas fa-clock"></i>
                                </div>
                                <div class="ms-3">
                                    <h6 class="fw-bold mb-1" style="color: #1e293b;">Working Hours</h6>
                                    <p class="mb-0 text-muted">Mon - Sat: 9:00 AM - 8:00 PM</p>
                                    <p class="mb-0 text-muted">Sunday: Closed</p>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <h6 class="fw-bold mb-3" style="color: #1e293b;">Connect With Us</h6>
                            <div class="d-flex gap-3">
                                <a href="#" style="color: #0e2945; font-size: 1.2rem;"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" style="color: #0e2945; font-size: 1.2rem;"><i class="fab fa-twitter"></i></a>
                                <a href="#" style="color: #0e2945; font-size: 1.2rem;"><i class="fab fa-instagram"></i></a>
                                <a href="#" style="color: #0e2945; font-size: 1.2rem;"><i class="fab fa-whatsapp"></i></a>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Contact Form -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100" style="border-radius: 12px; overflow: hidden;">
                        <div class="card-body p-4 p-md-5">
                            <h3 class="fw-bold mb-4" style="color: #0e2945;">Send Us a Message</h3>
                            
                            <form action="<?php echo BASE_URL; ?>/api/contact.php" method="POST">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted">Your Name</label>
                                        <input type="text" class="form-control form-control-lg" placeholder="John Doe" required style="font-size: 0.95rem; border-color: #e2e8f0;">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-medium text-muted">Your Email</label>
                                        <input type="email" class="form-control form-control-lg" placeholder="name@example.com" required style="font-size: 0.95rem; border-color: #e2e8f0;">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted">Subject</label>
                                        <select class="form-select form-select-lg" style="font-size: 0.95rem; border-color: #e2e8f0;">
                                            <option selected>General Inquiry</option>
                                            <option>Order Support</option>
                                            <option>Money Transfer</option>
                                            <option>Logistics / Shipping</option>
                                            <option>Procurement Service</option>
                                            <option>Technical Issue</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-medium text-muted">Message</label>
                                        <textarea class="form-control" rows="5" placeholder="How can we help you?" required style="font-size: 0.95rem; border-color: #e2e8f0;"></textarea>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-lg w-100 text-white fw-bold" style="background-color: #0e2945; padding: 12px;">Send Message</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Map Section (Optional Placeholder) -->
    <section class="py-5 bg-white border-top">
        <div class="container text-center">
             <h4 class="fw-bold mb-4" style="color: #0e2945;">Visit Our Office</h4>
             <div style="background: #f1f5f9; height: 300px; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                <p>Map Integration Coming Soon<br><small>Guangzhou, China & Accra, Ghana</small></p>
             </div>
        </div>
    </section>
</div>

<?php
// Include footer
include __DIR__ . '/includes/footer.php';
?>
