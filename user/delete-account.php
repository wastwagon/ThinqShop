<?php
/**
 * Account Deletion Page
 * Allows users to request account deletion with 30-day grace period
 */

require_once __DIR__ . '/../includes/auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Get user profile
$stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
$stmt->execute([$userId]);
$profile = $stmt->fetch();

$errors = [];
$success = false;

// Check if deletion is already pending
$stmt = $conn->prepare("SELECT deletion_requested_at, deletion_scheduled_for FROM users WHERE id = ?");
$stmt->execute([$userId]);
$deletionStatus = $stmt->fetch();

// Process deletion request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirmText = $_POST['confirm_text'] ?? '';
        
        // Verify password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $userRow = $stmt->fetch();
        
        if (!password_verify($password, $userRow['password'])) {
            $errors[] = 'Incorrect password. Please try again.';
        } elseif (strtoupper(trim($confirmText)) !== 'DELETE') {
            $errors[] = 'Please type DELETE to confirm.';
        } else {
            try {
                // Generate deletion token for cancellation
                $deletionToken = bin2hex(random_bytes(32));
                $deletionRequestedAt = date('Y-m-d H:i:s');
                $deletionScheduledFor = date('Y-m-d H:i:s', strtotime('+30 days'));
                
                // Update user record
                $stmt = $conn->prepare("
                    UPDATE users 
                    SET deletion_requested_at = ?, 
                        deletion_scheduled_for = ?, 
                        deletion_token = ?,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$deletionRequestedAt, $deletionScheduledFor, $deletionToken, $userId]);
                
                // Log the deletion request
                $userName = trim(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? ''));
                $stmt = $conn->prepare("
                    INSERT INTO account_deletion_logs 
                    (user_id, user_email, user_name, deletion_requested_at, ip_address, user_agent)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $userId,
                    $user['email'],
                    $userName,
                    $deletionRequestedAt,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]);
                
                // Send email notification
                $cancellationUrl = BASE_URL . '/user/cancel-deletion.php?token=' . $deletionToken;
                $emailSubject = 'Account Deletion Request - ' . APP_NAME;
                $emailBody = "
                    <h2>Account Deletion Requested</h2>
                    <p>Hello {$userName},</p>
                    <p>We received a request to delete your account. Your account is scheduled for permanent deletion on <strong>" . date('F j, Y', strtotime($deletionScheduledFor)) . "</strong>.</p>
                    
                    <h3>What happens next?</h3>
                    <ul>
                        <li>You have 30 days to cancel this request</li>
                        <li>Your account will remain accessible during this period</li>
                        <li>After 30 days, all your data will be permanently deleted</li>
                    </ul>
                    
                    <h3>Changed your mind?</h3>
                    <p>If you didn't request this or want to keep your account, click the link below:</p>
                    <p><a href='{$cancellationUrl}' style='background: #0e2945; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; display: inline-block;'>Cancel Account Deletion</a></p>
                    
                    <p style='margin-top: 20px; color: #666;'>If you didn't request this, please contact support immediately.</p>
                    <p>Best regards,<br>The " . APP_NAME . " Team</p>
                ";
                
                // Send email (using your existing email service)
                if (class_exists('EmailService')) {
                    try {
                        $emailService = new EmailService();
                        $emailService->sendEmail($user['email'], $emailSubject, $emailBody);
                    } catch (Exception $e) {
                        error_log("Failed to send deletion email: " . $e->getMessage());
                    }
                }
                
                redirect('/user/profile.php?tab=account', 'Account deletion requested. Check your email for cancellation instructions.', 'warning');
                
            } catch (Exception $e) {
                error_log("Account deletion error: " . $e->getMessage());
                $errors[] = 'Failed to process deletion request. Please try again.';
            }
        }
    }
}

$pageTitle = 'Delete Account - ' . APP_NAME;
$additionalCSS = [BASE_URL . '/assets/css/pages/user-profile.css'];

ob_start();
?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger mb-4 shadow-sm border-0 rounded-4 p-3">
        <ul class="mb-0 small fw-medium">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($deletionStatus && $deletionStatus['deletion_requested_at']): ?>
    <!-- Deletion Already Pending -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-body p-5 text-center">
            <div class="bg-warning bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                <i class="fas fa-clock fa-2x text-warning"></i>
            </div>
            <h4 class="fw-bold text-dark mb-3">Account Deletion Pending</h4>
            <p class="text-muted mb-4">
                Your account is scheduled for permanent deletion on:<br>
                <strong class="text-dark fs-5"><?php echo date('F j, Y', strtotime($deletionStatus['deletion_scheduled_for'])); ?></strong>
            </p>
            <div class="alert alert-warning border-0 rounded-3 mb-4">
                <p class="small mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    You can still cancel this request. Check your email for the cancellation link.
                </p>
            </div>
            <a href="<?php echo BASE_URL; ?>/user/profile.php?tab=account" class="btn btn-outline-primary rounded-pill px-4">
                Back to Profile
            </a>
        </div>
    </div>
<?php else: ?>
    <!-- Deletion Request Form -->
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-danger text-white p-4 border-0">
            <div class="d-flex align-items-center gap-3">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
                <div>
                    <h4 class="mb-1 fw-bold">Delete Your Account</h4>
                    <p class="mb-0 small opacity-90">This action cannot be undone</p>
                </div>
            </div>
        </div>
        
        <div class="card-body p-5">
            <div class="alert alert-warning border-0 rounded-4 mb-4">
                <h6 class="fw-bold mb-2"><i class="fas fa-clock me-2"></i>30-Day Grace Period</h6>
                <p class="small mb-0">
                    After confirming deletion, you'll have 30 days to change your mind. 
                    We'll send you an email with a cancellation link.
                </p>
            </div>
            
            <h6 class="fw-bold text-dark mb-3">What will be deleted:</h6>
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <i class="fas fa-user text-danger me-2"></i>
                        <strong>Personal Information</strong>
                        <p class="small text-muted mb-0 mt-1">Profile, name, contact details</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <i class="fas fa-shopping-bag text-danger me-2"></i>
                        <strong>Order History</strong>
                        <p class="small text-muted mb-0 mt-1">All past and pending orders</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <i class="fas fa-wallet text-danger me-2"></i>
                        <strong>Wallet & Transactions</strong>
                        <p class="small text-muted mb-0 mt-1">Balance and payment history</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 bg-light rounded-3">
                        <i class="fas fa-heart text-danger me-2"></i>
                        <strong>Saved Items</strong>
                        <p class="small text-muted mb-0 mt-1">Wishlist, cart, addresses</p>
                    </div>
                </div>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="confirm_delete" value="1">
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Confirm Your Password</label>
                    <input type="password" name="password" class="form-control form-control-lg" required placeholder="Enter your password">
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold">Type DELETE to confirm</label>
                    <input type="text" name="confirm_text" class="form-control form-control-lg" required placeholder="Type DELETE in capital letters">
                    <small class="text-muted">This confirms you understand this action is permanent</small>
                </div>
                
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-danger btn-lg px-5 fw-bold text-white">
                        <i class="fas fa-trash-alt me-2"></i>Delete My Account
                    </button>
                    <a href="<?php echo BASE_URL; ?>/user/profile.php?tab=account" class="btn btn-outline-secondary btn-lg px-5">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$content = ob_get_clean();
include __DIR__ . '/../includes/layouts/user-layout.php';
?>
