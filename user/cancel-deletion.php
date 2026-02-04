<?php
/**
 * Cancel Account Deletion
 * Allows users to cancel pending account deletion using token from email
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$db = new Database();
$conn = $db->getConnection();

$token = $_GET['token'] ?? '';
$success = false;
$error = '';

if (empty($token)) {
    $error = 'Invalid cancellation link.';
} else {
    try {
        // Find user by deletion token
        $stmt = $conn->prepare("
            SELECT id, email, deletion_requested_at, deletion_scheduled_for 
            FROM users 
            WHERE deletion_token = ? 
            AND deletion_requested_at IS NOT NULL
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            $error = 'Invalid or expired cancellation link.';
        } else {
            // Cancel the deletion
            $stmt = $conn->prepare("
                UPDATE users 
                SET deletion_requested_at = NULL,
                    deletion_scheduled_for = NULL,
                    deletion_token = NULL,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$user['id']]);
            
            // Update deletion log
            $stmt = $conn->prepare("
                UPDATE account_deletion_logs 
                SET deletion_cancelled_at = NOW(),
                    cancelled_by_user = TRUE
                WHERE user_id = ? 
                AND deletion_completed_at IS NULL
                ORDER BY created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$user['id']]);
            
            // Send confirmation email
            $emailSubject = 'Account Deletion Cancelled - ' . APP_NAME;
            $emailBody = "
                <h2>Account Deletion Cancelled</h2>
                <p>Good news! Your account deletion has been successfully cancelled.</p>
                <p>Your account is now active and you can continue using " . APP_NAME . " as normal.</p>
                <p><strong>Security Reminder:</strong> If you didn't cancel this deletion request, please contact our support team immediately.</p>
                <p>Best regards,<br>The " . APP_NAME . " Team</p>
            ";
            
            if (class_exists('EmailService')) {
                try {
                    $emailService = new EmailService();
                    $emailService->sendEmail($user['email'], $emailSubject, $emailBody);
                } catch (Exception $e) {
                    error_log("Failed to send cancellation email: " . $e->getMessage());
                }
            }
            
            $success = true;
        }
    } catch (Exception $e) {
        error_log("Cancellation error: " . $e->getMessage());
        $error = 'An error occurred. Please try again or contact support.';
    }
}

$pageTitle = 'Cancel Account Deletion - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <?php if ($success): ?>
                    <!-- Success State -->
                    <div class="card-body p-5 text-center">
                        <div class="bg-success bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-3">Deletion Cancelled!</h3>
                        <p class="text-muted mb-4">
                            Your account deletion has been successfully cancelled. 
                            Your account is now active and all your data is safe.
                        </p>
                        <div class="alert alert-success border-0 rounded-3 mb-4">
                            <p class="small mb-0">
                                <i class="fas fa-envelope me-2"></i>
                                A confirmation email has been sent to your email address.
                            </p>
                        </div>
                        <div class="d-flex gap-3 justify-content-center">
                            <?php if (isLoggedIn()): ?>
                                <a href="<?php echo BASE_URL; ?>/user/dashboard.php" class="btn btn-primary rounded-pill px-4">
                                    Go to Dashboard
                                </a>
                            <?php else: ?>
                                <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-primary rounded-pill px-4">
                                    Login to Your Account
                                </a>
                            <?php endif; ?>
                            <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary rounded-pill px-4">
                                Go to Homepage
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Error State -->
                    <div class="card-body p-5 text-center">
                        <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="fas fa-times-circle fa-3x text-danger"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-3">Cancellation Failed</h3>
                        <p class="text-muted mb-4">
                            <?php echo htmlspecialchars($error); ?>
                        </p>
                        <div class="alert alert-info border-0 rounded-3 mb-4">
                            <p class="small mb-0">
                                <i class="fas fa-info-circle me-2"></i>
                                If you're having trouble, please contact our support team.
                            </p>
                        </div>
                        <div class="d-flex gap-3 justify-content-center">
                            <a href="<?php echo BASE_URL; ?>/contact.php" class="btn btn-primary rounded-pill px-4">
                                Contact Support
                            </a>
                            <a href="<?php echo BASE_URL; ?>" class="btn btn-outline-secondary rounded-pill px-4">
                                Go to Homepage
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
