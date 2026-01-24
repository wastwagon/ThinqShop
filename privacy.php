<?php
/**
 * Privacy Policy Page
 * ThinQShopping Platform - Ghana
 */

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'Privacy Policy - ' . APP_NAME;
$pageDescription = 'Learn how ' . APP_NAME . ' collects, uses, and protects your personal information in accordance with Ghana\'s Data Protection Act, 2012 (Act 843).';
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
                                <li class="breadcrumb-item active" aria-current="page">Privacy Policy</li>
                            </ol>
                        </nav>
                        <h1 class="legal-page-title">Privacy Policy</h1>
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
                                    <i class="fas fa-shield-alt"></i> Introduction
                                </h2>
                                <p class="legal-text">
                                    Welcome to <?php echo APP_NAME; ?> ("we," "our," or "us"). We are committed to protecting your privacy and ensuring the security of your personal information. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you use our platform, which includes e-commerce services, money transfer services, logistics and parcel delivery, and procurement services.
                                </p>
                                <p class="legal-text">
                                    This Privacy Policy is designed to comply with the <strong>Data Protection Act, 2012 (Act 843)</strong> of Ghana and other applicable data protection laws. By using our services, you consent to the collection and use of information in accordance with this policy.
                                </p>
                            </section>

                            <!-- Information We Collect -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-database"></i> Information We Collect
                                </h2>
                                
                                <h3 class="legal-subsection-title">1. Personal Information</h3>
                                <p class="legal-text">We collect personal information that you provide directly to us, including:</p>
                                <ul class="legal-list">
                                    <li><strong>Account Information:</strong> Name, email address, phone number, WhatsApp number, password, and profile information</li>
                                    <li><strong>Identity Verification:</strong> Government-issued identification documents (for money transfers and high-value transactions)</li>
                                    <li><strong>Financial Information:</strong> Bank account details, mobile money numbers, payment card information (processed securely through Paystack)</li>
                                    <li><strong>Address Information:</strong> Billing and shipping addresses for order fulfillment</li>
                                    <li><strong>Transaction Information:</strong> Purchase history, order details, payment records, money transfer records, and shipment tracking information</li>
                                </ul>

                                <h3 class="legal-subsection-title">2. Automatically Collected Information</h3>
                                <p class="legal-text">When you visit our website, we automatically collect certain information:</p>
                                <ul class="legal-list">
                                    <li><strong>Device Information:</strong> IP address, browser type, device type, operating system</li>
                                    <li><strong>Usage Data:</strong> Pages visited, time spent on pages, click patterns, search queries</li>
                                    <li><strong>Cookies and Tracking Technologies:</strong> We use cookies, web beacons, and similar technologies to enhance your experience</li>
                                    <li><strong>Location Data:</strong> General location information based on IP address (with your consent)</li>
                                </ul>

                                <h3 class="legal-subsection-title">3. Information from Third Parties</h3>
                                <p class="legal-text">We may receive information from:</p>
                                <ul class="legal-list">
                                    <li><strong>Payment Processors:</strong> Paystack provides transaction confirmations and payment status</li>
                                    <li><strong>Logistics Partners:</strong> Shipping carriers provide delivery status and tracking information</li>
                                    <li><strong>Social Media Platforms:</strong> If you connect your social media accounts</li>
                                    <li><strong>Credit Bureaus:</strong> For identity verification and fraud prevention</li>
                                </ul>
                            </section>

                            <!-- How We Use Your Information -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-cogs"></i> How We Use Your Information
                                </h2>
                                <p class="legal-text">We use the collected information for the following purposes:</p>
                                <ul class="legal-list">
                                    <li><strong>Service Delivery:</strong> Process orders, facilitate money transfers, arrange logistics, and handle procurement requests</li>
                                    <li><strong>Account Management:</strong> Create and manage your account, process registrations, and authenticate users</li>
                                    <li><strong>Payment Processing:</strong> Process payments, manage your wallet, and handle refunds</li>
                                    <li><strong>Communication:</strong> Send order confirmations, shipping updates, transaction notifications, and customer service responses</li>
                                    <li><strong>Marketing:</strong> Send promotional emails, newsletters, and special offers (with your consent, which you can withdraw at any time)</li>
                                    <li><strong>Legal Compliance:</strong> Comply with Ghanaian laws, including anti-money laundering regulations, tax requirements, and data protection obligations</li>
                                    <li><strong>Fraud Prevention:</strong> Detect and prevent fraudulent transactions, identity theft, and other illegal activities</li>
                                    <li><strong>Service Improvement:</strong> Analyze usage patterns to improve our services, website functionality, and user experience</li>
                                    <li><strong>Customer Support:</strong> Respond to inquiries, resolve disputes, and provide technical support</li>
                                </ul>
                            </section>

                            <!-- Legal Basis for Processing -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-balance-scale"></i> Legal Basis for Processing
                                </h2>
                                <p class="legal-text">Under Ghana's Data Protection Act, 2012 (Act 843), we process your personal data based on:</p>
                                <ul class="legal-list">
                                    <li><strong>Consent:</strong> You have given clear consent for us to process your personal data for specific purposes</li>
                                    <li><strong>Contract Performance:</strong> Processing is necessary for the performance of a contract with you</li>
                                    <li><strong>Legal Obligation:</strong> We are required to process your data to comply with legal obligations</li>
                                    <li><strong>Legitimate Interests:</strong> Processing is necessary for our legitimate business interests, such as fraud prevention and service improvement</li>
                                </ul>
                            </section>

                            <!-- Information Sharing and Disclosure -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-share-alt"></i> Information Sharing and Disclosure
                                </h2>
                                <p class="legal-text">We do not sell your personal information. We may share your information in the following circumstances:</p>
                                
                                <h3 class="legal-subsection-title">Service Providers</h3>
                                <p class="legal-text">We share information with trusted third-party service providers who assist us in operating our platform:</p>
                                <ul class="legal-list">
                                    <li><strong>Payment Processors:</strong> Paystack for payment processing</li>
                                    <li><strong>Logistics Partners:</strong> Shipping and delivery companies</li>
                                    <li><strong>Cloud Service Providers:</strong> For data storage and hosting</li>
                                    <li><strong>Email Service Providers:</strong> For sending transactional and marketing emails</li>
                                    <li><strong>Analytics Providers:</strong> For website analytics and performance monitoring</li>
                                </ul>

                                <h3 class="legal-subsection-title">Legal Requirements</h3>
                                <p class="legal-text">We may disclose your information if required by law or in response to:</p>
                                <ul class="legal-list">
                                    <li>Court orders, subpoenas, or legal processes</li>
                                    <li>Government requests or regulatory investigations</li>
                                    <li>Compliance with anti-money laundering and counter-terrorism financing regulations</li>
                                    <li>Protection of our rights, property, or safety, or that of our users</li>
                                </ul>

                                <h3 class="legal-subsection-title">Business Transfers</h3>
                                <p class="legal-text">In the event of a merger, acquisition, or sale of assets, your information may be transferred to the acquiring entity.</p>
                            </section>

                            <!-- Data Security -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-lock"></i> Data Security
                                </h2>
                                <p class="legal-text">We implement appropriate technical and organizational measures to protect your personal information:</p>
                                <ul class="legal-list">
                                    <li><strong>Encryption:</strong> SSL/TLS encryption for data transmission</li>
                                    <li><strong>Secure Storage:</strong> Encrypted databases and secure servers</li>
                                    <li><strong>Access Controls:</strong> Limited access to personal data on a need-to-know basis</li>
                                    <li><strong>Regular Audits:</strong> Security assessments and vulnerability testing</li>
                                    <li><strong>Payment Security:</strong> PCI DSS compliant payment processing through Paystack</li>
                                    <li><strong>Employee Training:</strong> Regular training on data protection and security practices</li>
                                </ul>
                                <p class="legal-text">
                                    However, no method of transmission over the internet or electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your information, we cannot guarantee absolute security.
                                </p>
                            </section>

                            <!-- Your Rights -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-user-shield"></i> Your Rights Under Ghana's Data Protection Act
                                </h2>
                                <p class="legal-text">You have the following rights regarding your personal data:</p>
                                <ul class="legal-list">
                                    <li><strong>Right of Access:</strong> Request copies of your personal data we hold</li>
                                    <li><strong>Right to Rectification:</strong> Request correction of inaccurate or incomplete data</li>
                                    <li><strong>Right to Erasure:</strong> Request deletion of your personal data (subject to legal obligations)</li>
                                    <li><strong>Right to Restrict Processing:</strong> Request limitation of how we process your data</li>
                                    <li><strong>Right to Data Portability:</strong> Request transfer of your data to another service provider</li>
                                    <li><strong>Right to Object:</strong> Object to processing of your data for marketing purposes</li>
                                    <li><strong>Right to Withdraw Consent:</strong> Withdraw consent at any time where processing is based on consent</li>
                                </ul>
                                <p class="legal-text">
                                    To exercise these rights, please contact us at <a href="mailto:privacy@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.com">privacy@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.com</a> or use the contact form on our website.
                                </p>
                            </section>

                            <!-- Cookies and Tracking Technologies -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-cookie-bite"></i> Cookies and Tracking Technologies
                                </h2>
                                <p class="legal-text">We use cookies and similar technologies to:</p>
                                <ul class="legal-list">
                                    <li>Remember your preferences and settings</li>
                                    <li>Authenticate your account</li>
                                    <li>Analyze website traffic and usage patterns</li>
                                    <li>Provide personalized content and advertisements</li>
                                    <li>Improve website functionality and performance</li>
                                </ul>
                                <p class="legal-text">
                                    You can control cookies through your browser settings. However, disabling cookies may limit your ability to use certain features of our website.
                                </p>
                            </section>

                            <!-- Data Retention -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-clock"></i> Data Retention
                                </h2>
                                <p class="legal-text">We retain your personal information for as long as necessary to:</p>
                                <ul class="legal-list">
                                    <li>Provide our services to you</li>
                                    <li>Comply with legal obligations (e.g., tax records, transaction records)</li>
                                    <li>Resolve disputes and enforce agreements</li>
                                    <li>Maintain security and prevent fraud</li>
                                </ul>
                                <p class="legal-text">
                                    Transaction records may be retained for up to <strong>7 years</strong> as required by Ghanaian law. When data is no longer needed, we securely delete or anonymize it.
                                </p>
                            </section>

                            <!-- International Data Transfers -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-globe"></i> International Data Transfers
                                </h2>
                                <p class="legal-text">
                                    Your information may be transferred to and processed in countries outside Ghana, including China (for procurement services) and other countries where our service providers operate. We ensure that appropriate safeguards are in place to protect your data in accordance with Ghana's Data Protection Act.
                                </p>
                            </section>

                            <!-- Children's Privacy -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-child"></i> Children's Privacy
                                </h2>
                                <p class="legal-text">
                                    Our services are not intended for individuals under the age of 18. We do not knowingly collect personal information from children. If you believe we have collected information from a child, please contact us immediately.
                                </p>
                            </section>

                            <!-- Changes to This Policy -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-edit"></i> Changes to This Privacy Policy
                                </h2>
                                <p class="legal-text">
                                    We may update this Privacy Policy from time to time. We will notify you of any material changes by posting the new policy on this page and updating the "Last Updated" date. We encourage you to review this policy periodically.
                                </p>
                            </section>

                            <!-- Contact Information -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-envelope"></i> Contact Us
                                </h2>
                                <p class="legal-text">If you have questions about this Privacy Policy or wish to exercise your rights, please contact us:</p>
                                <div class="contact-info-box">
                                    <p><strong><?php echo APP_NAME; ?> Data Protection Officer</strong></p>
                                    <p><i class="fas fa-envelope"></i> Email: <a href="mailto:privacy@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.com">privacy@<?php echo strtolower(str_replace(' ', '', APP_NAME)); ?>.com</a></p>
                                    <p><i class="fas fa-phone"></i> Phone: <a href="tel:+8618320709024">+8618320709024</a></p>
                                    <p><i class="fas fa-map-marker-alt"></i> Address: Accra, Ghana</p>
                                </div>
                            </section>

                            <!-- Data Protection Commission -->
                            <section class="legal-section">
                                <h2 class="legal-section-title">
                                    <i class="fas fa-gavel"></i> Data Protection Commission
                                </h2>
                                <p class="legal-text">
                                    If you are not satisfied with how we handle your personal data, you have the right to lodge a complaint with the <strong>Data Protection Commission of Ghana</strong>:
                                </p>
                                <div class="contact-info-box">
                                    <p><strong>Data Protection Commission</strong></p>
                                    <p>P.O. Box CT 7193, Cantonments, Accra, Ghana</p>
                                    <p>Website: <a href="https://www.dataprotection.org.gh" target="_blank" rel="noopener">www.dataprotection.org.gh</a></p>
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

