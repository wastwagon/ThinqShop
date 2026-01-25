<?php
/**
 * User Login Page
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

// Generate CSRF token early for the form
$csrfToken = generateCSRFToken();

// Redirect if already logged in
if (isLoggedIn()) {
    $redirectUrl = $_SESSION['redirect_after_login'] ?? '/user/dashboard.php';
    unset($_SESSION['redirect_after_login']);
    redirect($redirectUrl);
}

// Redirect if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    redirect('/admin/dashboard.php');
}

$errors = [];
$loginError = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $loginError = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $email = sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);
        
        // Validation
        if (empty($email)) {
            $errors[] = 'Email is required.';
        }
        
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }
        
        // Authenticate user or admin
        if (empty($errors)) {
            $db = new Database();
            $conn = $db->getConnection();
            
            // First, try to authenticate as admin (check by email or username)
            $adminStmt = $conn->prepare("SELECT * FROM admin_users WHERE (email = ? OR username = ?) AND is_active = 1");
            $adminStmt->execute([$email, $email]);
            $admin = $adminStmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                // Admin login successful
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'admin';
                
                // Update last login
                $updateStmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$admin['id']]);
                
                // Log admin action if function exists
                if (function_exists('logAdminAction')) {
                    logAdminAction($admin['id'], 'admin_login', null, null, ['ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
                }
                
                // Redirect to admin dashboard
                redirect('/admin/dashboard.php', 'Welcome back!', 'success');
            } else {
                // Try to authenticate as regular user
                $userStmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
                $userStmt->execute([$email]);
                $user = $userStmt->fetch();
                
                if ($user && password_verify($password, $user['password'])) {
                    // User login successful
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    
                    // Update last login (optional)
                    $updateStmt = $conn->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                    $updateStmt->execute([$user['id']]);
                    
                    // Migrate guest cart items
                    migrateGuestCart($user['id'], session_id());
                    
                    // Handle remember me (extend session)
                    if ($remember_me) {
                        // Extend session lifetime
                        $_SESSION['remember_me'] = true;
                    }
                    
                    // Redirect
                    $redirectUrl = $_SESSION['redirect_after_login'] ?? '/user/dashboard.php';
                    unset($_SESSION['redirect_after_login']);
                    redirect($redirectUrl, 'Welcome back!', 'success');
                } else {
                    $loginError = 'Invalid email or password.';
                }
            }
        }
    }
}

$pageTitle = 'Login - ' . APP_NAME;
include __DIR__ . '/../includes/header.php';
?>

<div class="auth-page-wrapper">
    <div class="auth-container">
        <div class="auth-card">
            <!-- Login Header -->
            <div class="auth-header">
                <h1 class="auth-title">Welcome Back</h1>
                <p class="auth-subtitle">Sign in to your account</p>
            </div>
            
            <!-- Error Messages -->
            <?php if ($loginError): ?>
                <div class="auth-alert auth-alert--error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><?php echo htmlspecialchars($loginError); ?></span>
                </div>
            <?php endif; ?>
            
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
            
            <!-- Login Form -->
            <form method="POST" action="" class="auth-form" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                
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
                           required 
                           autofocus>
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
                               placeholder="Enter your password"
                               required>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword()">
                            <i class="fas fa-eye" id="passwordToggleIcon"></i>
                        </button>
                    </div>
                </div>
                
                <!-- Remember Me & Forgot Password -->
                <div class="auth-options">
                    <label class="form-checkbox-wrapper">
                        <input type="checkbox" class="form-checkbox" id="remember_me" name="remember_me">
                        <span class="auth-subtitle">Remember me</span>
                    </label>
                    <a href="<?php echo BASE_URL; ?>/forgot-password.php" class="auth-forgot-link">
                        Forgot password?
                    </a>
                </div>
                
                <!-- Submit Button -->
                <button type="submit" class="btn btn--primary btn--block">
                    <span>Sign In</span>
                    <i class="fas fa-arrow-right"></i>
                </button>
            </form>
            
            <!-- Divider -->
            <div class="auth-divider">
                <span>or</span>
            </div>
            
            <!-- Sign Up Link -->
            <div class="auth-footer">
                <p class="auth-switch-text">
                    Don't have an account? 
                    <a href="<?php echo BASE_URL; ?>/register.php" class="auth-switch-link">Create one now</a>
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('passwordToggleIcon');
    
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
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>




