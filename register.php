<?php
/**
 * User Registration Page
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/config/email-service.php';
require_once __DIR__ . '/includes/notification-helper.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('/user/dashboard.php');
}

$errors = [];
$success = false;

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token. Please try again.';
    } else {
        // Get form data
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $whatsapp_number = sanitize($_POST['whatsapp_number'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $first_name = sanitize($_POST['first_name'] ?? '');
        $last_name = sanitize($_POST['last_name'] ?? '');
        $agree_terms = isset($_POST['agree_terms']);
        
        // Validation
        if (empty($email) || !isValidEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        if (empty($phone) || !isValidPhone($phone)) {
            $errors[] = 'Please enter a valid Ghana phone number (e.g., +233XXXXXXXXX or 0XXXXXXXXX).';
        }
        
        if (empty($whatsapp_number) || !isValidPhone($whatsapp_number)) {
            $errors[] = 'Please enter a valid WhatsApp number (e.g., +233XXXXXXXXX or 0XXXXXXXXX).';
        }
        
        if (empty($password) || strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
        
        if (empty($first_name)) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($last_name)) {
            $errors[] = 'Last name is required.';
        }
        
        if (!$agree_terms) {
            $errors[] = 'You must agree to the terms and conditions.';
        }
        
        // Check if email or phone already exists
        if (empty($errors)) {
            $db = new Database();
            $conn = $db->getConnection();
            
            // Check email
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered.';
            }
            
            // Check phone
            $phone_formatted = formatPhone($phone);
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone_formatted]);
            if ($stmt->fetch()) {
                $errors[] = 'This phone number is already registered.';
            }
            
            // Create user account
            if (empty($errors)) {
                try {
                    $conn->beginTransaction();
                    
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                    
                    // Generate unique user identifier
                    $userIdentifier = generateUserIdentifier($first_name, $conn);
                    
                    // Insert user (email automatically verified)
                    // Check if user_identifier column exists - rowCount() is unreliable for SHOW COLUMNS
                    $checkColumn = $conn->query("SHOW COLUMNS FROM users LIKE 'user_identifier'");
                    $hasUserIdentifier = ($checkColumn && $checkColumn->fetch() !== false);
                    
                    if ($hasUserIdentifier) {
                        $stmt = $conn->prepare("
                            INSERT INTO users (user_identifier, email, phone, password, email_verified_at, is_verified, is_active, created_at) 
                            VALUES (?, ?, ?, ?, NOW(), 1, 1, NOW())
                        ");
                        $stmt->execute([$userIdentifier, $email, $phone_formatted, $hashed_password]);
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO users (email, phone, password, email_verified_at, is_verified, is_active, created_at) 
                            VALUES (?, ?, ?, NOW(), 1, 1, NOW())
                        ");
                        $stmt->execute([$email, $phone_formatted, $hashed_password]);
                    }
                    $userId = $conn->lastInsertId();
                    
                    // Format WhatsApp number
                    $whatsapp_formatted = formatPhone($whatsapp_number);
                    
                    // Create user profile
                    $stmt = $conn->prepare("
                        INSERT INTO user_profiles (user_id, first_name, last_name, whatsapp_number, created_at) 
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$userId, $first_name, $last_name, $whatsapp_formatted]);
                    
                    // Create wallet
                    $stmt = $conn->prepare("
                        INSERT INTO user_wallets (user_id, balance_ghs, updated_at) 
                        VALUES (?, 0.00, NOW())
                    ");
                    $stmt->execute([$userId]);
                    
                    $conn->commit();
                    
                    // Send notification to all admins
                    $userName = trim($first_name . ' ' . $last_name);
                    if (class_exists('NotificationHelper')) {
                        NotificationHelper::notifyAllAdmins(
                            'user_registered',
                            'New User Registration',
                            'New user registered: ' . $email . ' (' . $userName . ')',
                            BASE_URL . '/admin/users/view.php?id=' . $userId
                        );
                    }
                    
                    // Auto-login the user
                    $_SESSION['user_id'] = $userId;
                    $_SESSION['user_email'] = $email;
                    
                    // Migrate guest cart items
                    migrateGuestCart($userId, session_id());
                    
                    // Redirect to dashboard
                    redirect('/user/dashboard.php', 'Registration successful! Welcome to ' . APP_NAME . '.', 'success');
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    error_log("Registration Error: " . $e->getMessage());
                    $errors[] = 'Registration failed. Please try again later.';
                }
            }
        }
    }
}

$pageTitle = 'Register - ' . APP_NAME;
$additionalCSS = ['assets/css/pages/auth.css'];
include __DIR__ . '/includes/header.php';
?>

<div class="auth-page-wrapper">
    <div class="auth-container auth-container--wide">
        <div class="auth-card">
            <!-- Register Header -->
            <div class="auth-header">
                <h1 class="auth-title">Create Account</h1>
                <p class="auth-subtitle">Join <?php echo APP_NAME; ?> and get started</p>
            </div>
            
            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
                <div class="auth-alert auth-alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- Registration Form -->
            <form method="POST" action="" class="auth-form" novalidate>
                <?php $csrfToken = generateCSRFToken(); ?>
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <!-- Name Fields -->
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name" class="form-label">
                            <i class="fas fa-user"></i> First Name
                        </label>
                        <input type="text" 
                               class="form-input" 
                               id="first_name" 
                               name="first_name" 
                               placeholder="Enter first name"
                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" 
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="last_name" class="form-label">
                            <i class="fas fa-user"></i> Last Name
                        </label>
                        <input type="text" 
                               class="form-input" 
                               id="last_name" 
                               name="last_name" 
                               placeholder="Enter last name"
                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" 
                               required>
                    </div>
                </div>
                
                <!-- Email Field -->
                <div class="form-group">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" 
                           class="form-input" 
                           id="email" 
                           name="email" 
                           placeholder="Enter your email"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" 
                           required>
                </div>
                
                <!-- Phone Field -->
                <div class="form-group">
                    <label for="phone" class="form-label">
                        <i class="fas fa-phone"></i> Phone Number
                    </label>
                    <input type="tel" 
                           class="form-input" 
                           id="phone" 
                           name="phone" 
                           placeholder="+233XXXXXXXXX or 0XXXXXXXXX"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" 
                           required>
                    <small class="strength-text">Ghana phone number format</small>
                </div>
                
                <!-- WhatsApp Field -->
                <div class="form-group">
                    <label for="whatsapp_number" class="form-label">
                        <i class="fab fa-whatsapp"></i> WhatsApp Number
                    </label>
                    <input type="tel" 
                           class="form-input" 
                           id="whatsapp_number" 
                           name="whatsapp_number" 
                           placeholder="+233XXXXXXXXX or 0XXXXXXXXX"
                           value="<?php echo htmlspecialchars($_POST['whatsapp_number'] ?? ''); ?>" 
                           required>
                    <small class="strength-text">Your WhatsApp number</small>
                </div>
                
                <!-- Password Field -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-toggle-wrapper">
                        <input type="password" 
                               class="form-input" 
                               id="password" 
                               name="password" 
                               placeholder="Create a password"
                               minlength="8" 
                               required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength">
                        <div class="strength-bar">
                            <div class="strength-fill" id="strengthFill"></div>
                        </div>
                        <small class="strength-text" id="strengthText">Minimum 8 characters</small>
                    </div>
                </div>
                
                <!-- Confirm Password Field -->
                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <div class="password-toggle-wrapper">
                        <input type="password" 
                               class="form-input" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Confirm your password"
                               minlength="8" 
                               required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="confirmPasswordToggleIcon"></i>
                        </button>
                    </div>
                    <div class="password-match" id="passwordMatch"></div>
                </div>
                
                <!-- Terms Checkbox -->
                <div class="form-group">
                    <label class="form-checkbox-wrapper">
                        <input type="checkbox" class="form-checkbox" id="agree_terms" name="agree_terms" required>
                        <span class="auth-subtitle">
                            I agree to the <a href="<?php echo BASE_URL; ?>/terms.php" target="_blank" class="auth-switch-link">Terms and Conditions</a> and 
                            <a href="<?php echo BASE_URL; ?>/privacy.php" target="_blank" class="auth-switch-link">Privacy Policy</a>
                        </span>
                    </label>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn--primary btn--block">
                    <span>Create Account</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <!-- Divider -->
            <div class="auth-divider">
                <span>or</span>
            </div>
            
            <!-- Login Link -->
            <div class="auth-footer">
                <p class="auth-switch-text">
                    Already have an account? 
                    <a href="<?php echo BASE_URL; ?>/login.php" class="auth-switch-link">Sign in here</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id) {
    const passwordInput = document.getElementById(id);
    const toggleIcon = id === 'password' ? 
        document.getElementById('passwordToggleIcon') : 
        document.getElementById('confirmPasswordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

// Form validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.auth-form');
    const inputs = form.querySelectorAll('.form-input');
    
    // Password strength and matching
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthFill = document.getElementById('strengthFill');
    const strengthText = document.getElementById('strengthText');
    const passwordMatch = document.getElementById('passwordMatch');
    
    password.addEventListener('input', function() {
        const val = password.value;
        let strength = 0;
        
        if (val.length >= 8) strength++;
        if (/[A-Z]/.test(val)) strength++;
        if (/[0-9]/.test(val)) strength++;
        if (/[^A-Za-z0-9]/.test(val)) strength++;
        
        strengthFill.className = 'strength-fill';
        if (val.length === 0) {
            strengthText.textContent = 'Minimum 8 characters';
            strengthFill.style.width = '0%';
        } else if (strength < 2) {
            strengthFill.classList.add('weak');
            strengthText.textContent = 'Weak password';
        } else if (strength < 4) {
            strengthFill.classList.add('medium');
            strengthText.textContent = 'Medium password';
        } else {
            strengthFill.classList.add('strong');
            strengthText.textContent = 'Strong password';
        }
    });
    
    function checkMatch() {
        if (!confirmPassword.value) {
            passwordMatch.textContent = '';
            passwordMatch.className = 'password-match';
            return;
        }
        
        if (password.value === confirmPassword.value) {
            passwordMatch.textContent = 'Passwords match ✓';
            passwordMatch.className = 'password-match match';
        } else {
            passwordMatch.textContent = 'Passwords do not match ✗';
            passwordMatch.className = 'password-match no-match';
        }
    }
    
    password.addEventListener('input', checkMatch);
    confirmPassword.addEventListener('input', checkMatch);
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
