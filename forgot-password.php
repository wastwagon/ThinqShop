<?php
/**
 * Forgot Password Page
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

$pageTitle = 'Forgot Password - ' . APP_NAME;
$additionalCSS = ['assets/css/pages/auth.css'];
include __DIR__ . '/includes/header.php';

$successMsg = '';
$errorMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $errorMsg = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please enter a valid email address.';
    } else {
        // TODO: Implement actual reset logic (token generation + email)
        // For now, simulating success to unblock the UI flow
        $successMsg = 'If an account exists for this email, we have sent password reset instructions.';
    }
}
?>

<div class="auth-page-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <!-- Header -->
            <div class="auth-header">
                <h1 class="auth-title">Forgot Password?</h1>
                <p class="auth-subtitle">Enter your email to reset your password</p>
            </div>
            
            <!-- Messages -->
            <?php if ($successMsg): ?>
                <div class="auth-alert" style="background-color: var(--color-success-light); color: var(--color-success-dark); border: 1px solid var(--color-success);">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo htmlspecialchars($successMsg); ?></span>
                </div>
            <?php endif; ?>

            <?php if ($errorMsg): ?>
                <div class="auth-alert auth-alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($errorMsg); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Form -->
            <form method="POST" class="auth-form">
                <div class="form-group mb-4">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" class="form-input" id="email" name="email" placeholder="Enter your email" required autofocus>
                </div>
                
                <button type="submit" class="btn btn--primary btn--block btn--compact">
                    <span>Send Reset Link</span>
                    <i class="fas fa-paper-plane"></i>
                </button>
            </form>
            
            <!-- Footer -->
            <div class="auth-footer mt-4">
                <a href="<?php echo BASE_URL; ?>/login.php" class="auth-switch-link">
                    <i class="fas fa-arrow-left me-1"></i> Back to Sign In
                </a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
