<?php
/**
 * Terms of Use Page
 * ThinQShopping Platform - Ghana
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Terms of Use - ' . APP_NAME;
$pageDescription = 'Read the terms and conditions governing your use of ' . APP_NAME . ' services including e-commerce, money transfers, logistics, and procurement.';
$additionalCSS = [
    'assets/css/pages/legal.css'
];

include __DIR__ . '/includes/header.php';
?>

<div class="legal-page-wrapper">
    <div class="legal-page-container">
        <!-- Header Section -->
        <div class="legal-page-header">
            <div class="container">
                <div class="row">
                    <div class="col-12">
                        <nav aria-label="breadcrumb" class="legal-breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Terms of Use</li>
                            </ol>
                        </nav>
                        <h1 class="legal-page-title">Terms of Use</h1>
                        <p class="legal-page-subtitle">Last Updated: <?php echo date('F j, Y'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Section -->
        <div class="legal-content-section">
            <div class="container">
                <div class="row">
                    <div class="col-lg-10 offset-lg-1">
                        <div class="legal-content-card">
                            <!-- Introduction -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-file-contract"></i> Introduction
                                </h2>
                                <p class="legal-text">
                                    Welcome to <?php echo APP_NAME; ?> ("we," "our," "us," or "the Platform"). These Terms of Use ("Terms") govern your access to and use of our multi-service platform, including our website, mobile applications, and all related services (collectively, the "Services").
                                </p>
                                <p class="legal-text">
                                    Our Services include:
                                </p>
                                <ul class="legal-list">
                                    <li><strong>E-Commerce:</strong> Online shopping platform for purchasing products</li>
                                    <li><strong>Money Transfer:</strong> Ghana-China money transfer services</li>
                                    <li><strong>Logistics & Parcel Delivery:</strong> Door-to-door shipping and delivery services</li>
                                    <li><strong>Procurement:</strong> Request-based procurement services from China</li>
                                    <li><strong>Wallet Services:</strong> Digital wallet for managing funds across all services</li>
                                </ul>
                                <p class="legal-text">
                                    By accessing or using our Services, you agree to be bound by these Terms. If you do not agree to these Terms, please do not use our Services. These Terms constitute a legally binding agreement between you and <?php echo APP_NAME; ?>.
                                </p>
                            </section>

                            <!-- Acceptance of Terms -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-check-circle"></i> Acceptance of Terms
                                </h2>
                                <p class="legal-text">
                                    By registering for an account, making a purchase, using our money transfer services, booking logistics, or accessing any part of our Platform, you acknowledge that you have read, understood, and agree to be bound by these Terms and our Privacy Policy.
                                </p>
                                <p class="legal-text">
                                    You must be at least <strong>18 years old</strong> and have the legal capacity to enter into contracts under Ghanaian law to use our Services. If you are using our Services on behalf of a company or organization, you represent that you have the authority to bind that entity to these Terms.
                                </p>
                            </section>

                            <!-- Account Registration -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-user-plus"></i> Account Registration and Security
                                </h2>
                                
                                <h3 class="legal-subsection-title">Account Creation</h3>
                                <p class="legal-text">To use certain Services, you must create an account by providing:</p>
                                <ul class="legal-list">
                                    <li>Accurate, current, and complete information</li>
                                    <li>Valid email address and phone number</li>
                                    <li>Secure password</li>
                                    <li>Any additional information required for identity verification</li>
                                </ul>

                                <h3 class="legal-subsection-title">Account Security</h3>
                                <p class="legal-text">You are responsible for:</p>
                                <ul class="legal-list">
                                    <li>Maintaining the confidentiality of your account credentials</li>
                                    <li>All activities that occur under your account</li>
                                    <li>Notifying us immediately of any unauthorized access or security breach</li>
                                    <li>Ensuring your account information remains accurate and up-to-date</li>
                                </ul>
                                <p class="legal-text">
                                    We reserve the right to suspend or terminate accounts that violate these Terms or engage in fraudulent activity.
                                </p>
                            </section>

                            <!-- E-Commerce Terms -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-shopping-cart"></i> E-Commerce Terms
                                </h2>
                                
                                <h3 class="legal-subsection-title">Product Information</h3>
                                <p class="legal-text">
                                    We strive to provide accurate product descriptions, images, and pricing. However, we do not warrant that product descriptions or other content on our Platform is accurate, complete, reliable, current, or error-free. Prices are subject to change without notice.
                                </p>

                                <h3 class="legal-subsection-title">Orders and Payment</h3>
                                <ul class="legal-list">
                                    <li>All orders are subject to product availability and our acceptance</li>
                                    <li>We reserve the right to refuse or cancel any order for any reason</li>
                                    <li>Payment must be made in full at the time of purchase unless otherwise agreed</li>
                                    <li>We accept payments via Paystack (cards, mobile money, bank transfers)</li>
                                    <li>All prices are in <strong>Ghana Cedis (GHS)</strong> unless otherwise stated</li>
                                </ul>

                                <h3 class="legal-subsection-title">Shipping and Delivery</h3>
                                <ul class="legal-list">
                                    <li>Delivery times are estimates and not guaranteed</li>
                                    <li>Risk of loss and title pass to you upon delivery to the carrier</li>
                                    <li>You are responsible for providing accurate delivery addresses</li>
                                    <li>Additional charges may apply for international shipping</li>
                                </ul>

                                <h3 class="legal-subsection-title">Returns and Refunds</h3>
                                <p class="legal-text">
                                    Returns are subject to our Return Policy. Refunds will be processed according to our refund policy and may take 5-10 business days to reflect in your account.
                                </p>
                            </section>

                            <!-- Money Transfer Terms -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-exchange-alt"></i> Money Transfer Terms
                                </h2>
                                
                                <h3 class="legal-subsection-title">Service Description</h3>
                                <p class="legal-text">
                                    Our money transfer service facilitates transfers between Ghana and China. All transfers are subject to:
                                </p>
                                <ul class="legal-list">
                                    <li>Ghana's anti-money laundering (AML) and counter-terrorism financing (CTF) regulations</li>
                                    <li>Bank of Ghana regulations</li>
                                    <li>Chinese foreign exchange regulations</li>
                                    <li>Identity verification requirements</li>
                                </ul>

                                <h3 class="legal-subsection-title">Transfer Limits and Fees</h3>
                                <ul class="legal-list">
                                    <li>Transfer limits may apply based on your account verification level</li>
                                    <li>Fees are disclosed before you confirm the transfer</li>
                                    <li>Exchange rates are provided at the time of transfer and may fluctuate</li>
                                    <li>All fees are non-refundable once a transfer is initiated</li>
                                </ul>

                                <h3 class="legal-subsection-title">Transfer Processing</h3>
                                <ul class="legal-list">
                                    <li>Transfers are processed during business hours</li>
                                    <li>Processing times vary based on destination and payment method</li>
                                    <li>We are not responsible for delays caused by third-party financial institutions</li>
                                    <li>You must provide accurate recipient information</li>
                                </ul>

                                <h3 class="legal-subsection-title">Prohibited Uses</h3>
                                <p class="legal-text">You may not use our money transfer service for:</p>
                                <ul class="legal-list">
                                    <li>Illegal activities or money laundering</li>
                                    <li>Fraudulent transactions</li>
                                    <li>Circumventing currency controls or tax obligations</li>
                                    <li>Any purpose prohibited by Ghanaian or Chinese law</li>
                                </ul>
                            </section>

                            <!-- Logistics Terms -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-truck"></i> Logistics and Parcel Delivery Terms
                                </h2>
                                
                                <h3 class="legal-subsection-title">Service Description</h3>
                                <p class="legal-text">
                                    We provide door-to-door parcel delivery services. By booking a shipment, you agree to:
                                </p>
                                <ul class="legal-list">
                                    <li>Provide accurate sender and recipient information</li>
                                    <li>Accurately declare package contents and value</li>
                                    <li>Comply with customs regulations for international shipments</li>
                                    <li>Pay all applicable shipping fees and customs duties</li>
                                </ul>

                                <h3 class="legal-subsection-title">Prohibited Items</h3>
                                <p class="legal-text">You may not ship:</p>
                                <ul class="legal-list">
                                    <li>Illegal substances, weapons, or explosives</li>
                                    <li>Perishable items without proper packaging</li>
                                    <li>Items prohibited by Ghanaian or destination country laws</li>
                                    <li>Items that require special licenses without proper documentation</li>
                                </ul>

                                <h3 class="legal-subsection-title">Liability and Insurance</h3>
                                <ul class="legal-list">
                                    <li>We are not liable for loss or damage to packages beyond our control</li>
                                    <li>Optional insurance is available for valuable items</li>
                                    <li>Claims must be filed within 30 days of delivery</li>
                                    <li>Liability is limited to the declared value or shipping cost, whichever is lower</li>
                                </ul>
                            </section>

                            <!-- Procurement Terms -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-box"></i> Procurement Service Terms
                                </h2>
                                
                                <h3 class="legal-subsection-title">Service Description</h3>
                                <p class="legal-text">
                                    Our procurement service allows you to request products from China. By submitting a procurement request, you:
                                </p>
                                <ul class="legal-list">
                                    <li>Provide detailed product specifications</li>
                                    <li>Agree to pay quoted prices and fees</li>
                                    <li>Accept delivery timelines as estimates</li>
                                    <li>Understand that product availability may vary</li>
                                </ul>

                                <h3 class="legal-subsection-title">Quotes and Pricing</h3>
                                <ul class="legal-list">
                                    <li>Quotes are valid for the period specified</li>
                                    <li>Final prices may vary based on market conditions</li>
                                    <li>You will be notified of any significant price changes</li>
                                    <li>Payment terms will be specified in each quote</li>
                                </ul>

                                <h3 class="legal-subsection-title">Cancellation</h3>
                                <p class="legal-text">
                                    Procurement requests may be cancelled before order confirmation. Cancellation fees may apply if the order has been placed with suppliers.
                                </p>
                            </section>

                            <!-- Wallet Terms -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-wallet"></i> Wallet Terms
                                </h2>
                                <p class="legal-text">
                                    Our digital wallet allows you to store funds for use across all Services. By using the wallet:
                                </p>
                                <ul class="legal-list">
                                    <li>Funds are held securely but do not earn interest</li>
                                    <li>You can top up your wallet via Paystack</li>
                                    <li>Wallet balance can be used for all Platform services</li>
                                    <li>Withdrawals are subject to verification and processing times</li>
                                    <li>We reserve the right to freeze accounts for security or legal reasons</li>
                                </ul>
                            </section>

                            <!-- User Conduct -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-user-check"></i> User Conduct and Prohibited Activities
                                </h2>
                                <p class="legal-text">You agree not to:</p>
                                <ul class="legal-list">
                                    <li>Violate any applicable laws or regulations</li>
                                    <li>Infringe on intellectual property rights</li>
                                    <li>Transmit viruses, malware, or harmful code</li>
                                    <li>Attempt to gain unauthorized access to our systems</li>
                                    <li>Use automated systems to access our Platform without permission</li>
                                    <li>Interfere with or disrupt our Services</li>
                                    <li>Create fake accounts or impersonate others</li>
                                    <li>Engage in fraudulent or deceptive practices</li>
                                    <li>Harass, abuse, or harm other users</li>
                                    <li>Post false, misleading, or defamatory content</li>
                                </ul>
                            </section>

                            <!-- Intellectual Property -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-copyright"></i> Intellectual Property
                                </h2>
                                <p class="legal-text">
                                    All content on our Platform, including text, graphics, logos, images, software, and trademarks, is the property of <?php echo APP_NAME; ?> or its licensors and is protected by Ghanaian and international copyright and trademark laws.
                                </p>
                                <p class="legal-text">
                                    You may not reproduce, distribute, modify, or create derivative works from our content without our express written permission.
                                </p>
                            </section>

                            <!-- Limitation of Liability -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-exclamation-triangle"></i> Limitation of Liability
                                </h2>
                                <p class="legal-text">
                                    To the maximum extent permitted by Ghanaian law:
                                </p>
                                <ul class="legal-list">
                                    <li>Our Services are provided "as is" without warranties of any kind</li>
                                    <li>We are not liable for indirect, incidental, or consequential damages</li>
                                    <li>Our total liability is limited to the amount you paid for the specific service</li>
                                    <li>We are not responsible for third-party actions or services</li>
                                    <li>We do not guarantee uninterrupted or error-free service</li>
                                </ul>
                            </section>

                            <!-- Indemnification -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-shield-alt"></i> Indemnification
                                </h2>
                                <p class="legal-text">
                                    You agree to indemnify and hold harmless <?php echo APP_NAME; ?>, its officers, directors, employees, and agents from any claims, damages, losses, liabilities, and expenses (including legal fees) arising from:
                                </p>
                                <ul class="legal-list">
                                    <li>Your use of our Services</li>
                                    <li>Your violation of these Terms</li>
                                    <li>Your violation of any rights of another party</li>
                                    <li>Your violation of any applicable laws</li>
                                </ul>
                            </section>

                            <!-- Dispute Resolution -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-gavel"></i> Dispute Resolution
                                </h2>
                                <h3 class="legal-subsection-title">Governing Law</h3>
                                <p class="legal-text">
                                    These Terms are governed by the laws of the Republic of Ghana. Any disputes arising from these Terms or your use of our Services shall be subject to the exclusive jurisdiction of the courts of Ghana.
                                </p>

                                <h3 class="legal-subsection-title">Dispute Process</h3>
                                <p class="legal-text">
                                    Before initiating legal proceedings, you agree to:
                                </p>
                                <ol class="legal-list" style="list-style: decimal; padding-left: 2rem;">
                                    <li>Contact us first to attempt to resolve the dispute amicably</li>
                                    <li>Participate in good faith in any mediation or alternative dispute resolution process we propose</li>
                                    <li>Allow 30 days for resolution attempts before pursuing legal action</li>
                                </ol>
                            </section>

                            <!-- Termination -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-ban"></i> Termination
                                </h2>
                                <p class="legal-text">
                                    We reserve the right to suspend or terminate your account and access to our Services at any time, with or without cause or notice, for:
                                </p>
                                <ul class="legal-list">
                                    <li>Violation of these Terms</li>
                                    <li>Fraudulent or illegal activity</li>
                                    <li>Non-payment of fees</li>
                                    <li>Any reason we deem necessary to protect our business or users</li>
                                </ul>
                                <p class="legal-text">
                                    You may terminate your account at any time by contacting customer service. Upon termination, your right to use our Services immediately ceases.
                                </p>
                            </section>

                            <!-- Changes to Terms -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-edit"></i> Changes to Terms
                                </h2>
                                <p class="legal-text">
                                    We reserve the right to modify these Terms at any time. Material changes will be notified by:
                                </p>
                                <ul class="legal-list">
                                    <li>Posting the updated Terms on this page</li>
                                    <li>Updating the "Last Updated" date</li>
                                    <li>Sending email notifications for significant changes</li>
                                </ul>
                                <p class="legal-text">
                                    Your continued use of our Services after changes become effective constitutes acceptance of the modified Terms.
                                </p>
                            </section>

                            <!-- Severability -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-balance-scale"></i> Severability
                                </h2>
                                <p class="legal-text">
                                    If any provision of these Terms is found to be invalid or unenforceable, the remaining provisions will continue in full force and effect.
                                </p>
                            </section>

                            <!-- Contact Information -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-envelope"></i> Contact Us
                                </h2>
                                <p class="legal-text">If you have questions about these Terms, please contact us:</p>
                                <div class="contact-info-box">
                                    <p><strong><?php echo APP_NAME; ?> Legal Department</strong></p>
                                    <p><i class="fas fa-envelope"></i> Email: <a href="mailto:legal@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.com">legal@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.com</a></p>
                                    <p><i class="fas fa-phone"></i> Phone: <a href="tel:+8618320709024">+8618320709024</a></p>
                                    <p><i class="fas fa-map-marker-alt"></i> Address: Accra, Ghana</p>
                                </div>
                            </section>

                            <!-- Acknowledgment -->
                            <section class="legal-section">
                                <div class="acknowledgment-box">
                                    <p class="legal-text" style="margin-bottom: 0;">
                                        <strong>By using our Services, you acknowledge that you have read, understood, and agree to be bound by these Terms of Use.</strong>
                                    </p>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

