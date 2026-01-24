<?php
/**
 * Admin User Edit Page
 * Edit user information and account settings
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get user ID
$userId = intval($_GET['id'] ?? 0);

if ($userId <= 0) {
    redirect('/admin/users/manage.php', 'Invalid user ID.', 'danger');
}

// Get user details
$stmt = $conn->prepare("
    SELECT u.*, up.first_name, up.last_name, up.profile_image, up.whatsapp_number, 
           up.date_of_birth, up.gender
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    redirect('/admin/users/manage.php', 'User not found.', 'danger');
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $whatsappNumber = sanitize($_POST['whatsapp_number'] ?? '');
        $dateOfBirth = $_POST['date_of_birth'] ?? null;
        $gender = sanitize($_POST['gender'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $resetPassword = isset($_POST['reset_password']) && !empty($_POST['new_password']);
        $newPassword = $_POST['new_password'] ?? '';
        
        // Validation
        if (empty($firstName)) {
            $errors[] = 'First name is required.';
        }
        
        if (empty($lastName)) {
            $errors[] = 'Last name is required.';
        }
        
        if (empty($email) || !isValidEmail($email)) {
            $errors[] = 'Valid email is required.';
        }
        
        if (empty($phone) || !isValidPhone($phone)) {
            $errors[] = 'Valid phone number is required.';
        }
        
        if (!empty($whatsappNumber) && !isValidPhone($whatsappNumber)) {
            $errors[] = 'Valid WhatsApp number is required.';
        }
        
        if ($resetPassword && strlen($newPassword) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        
        // Check if email is already taken by another user
        if ($email !== $user['email']) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'This email is already registered to another user.';
            }
        }
        
        // Check if phone is already taken by another user
        $phoneFormatted = formatPhone($phone);
        if ($phoneFormatted !== $user['phone']) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phoneFormatted, $userId]);
            if ($stmt->fetch()) {
                $errors[] = 'This phone number is already registered to another user.';
            }
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Update users table
                if ($resetPassword) {
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET email = ?, phone = ?, is_active = ?, password = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$email, $phoneFormatted, $isActive, $hashedPassword, $userId]);
                } else {
                    $stmt = $conn->prepare("
                        UPDATE users 
                        SET email = ?, phone = ?, is_active = ?, updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$email, $phoneFormatted, $isActive, $userId]);
                }
                
                // Update or create user profile
                $whatsappFormatted = !empty($whatsappNumber) ? formatPhone($whatsappNumber) : null;
                $dateOfBirthFormatted = !empty($dateOfBirth) ? $dateOfBirth : null;
                $genderValue = in_array($gender, ['male', 'female', 'other']) ? $gender : null;
                
                $stmt = $conn->prepare("SELECT id FROM user_profiles WHERE user_id = ?");
                $stmt->execute([$userId]);
                $profileExists = $stmt->fetch();
                
                if ($profileExists) {
                    $stmt = $conn->prepare("
                        UPDATE user_profiles 
                        SET first_name = ?, last_name = ?, whatsapp_number = ?, 
                            date_of_birth = ?, gender = ?, updated_at = NOW() 
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$firstName, $lastName, $whatsappFormatted, $dateOfBirthFormatted, $genderValue, $userId]);
                } else {
                    $stmt = $conn->prepare("
                        INSERT INTO user_profiles (user_id, first_name, last_name, whatsapp_number, date_of_birth, gender, created_at) 
                        VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ");
                    $stmt->execute([$userId, $firstName, $lastName, $whatsappFormatted, $dateOfBirthFormatted, $genderValue]);
                }
                
                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = __DIR__ . '/../../assets/images/profiles/';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0755, true);
                    }
                    
                    $fileExtension = strtolower(pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION));
                    $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($fileExtension, $allowedExtensions)) {
                        $newFileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
                        $uploadPath = $uploadDir . $newFileName;
                        
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $uploadPath)) {
                            // Delete old profile image if exists
                            if (!empty($user['profile_image'])) {
                                $oldImagePath = $uploadDir . $user['profile_image'];
                                if (file_exists($oldImagePath)) {
                                    unlink($oldImagePath);
                                }
                            }
                            
                            $stmt = $conn->prepare("UPDATE user_profiles SET profile_image = ? WHERE user_id = ?");
                            $stmt->execute([$newFileName, $userId]);
                        }
                    }
                }
                
                // Log admin action
                if (function_exists('logAdminAction')) {
                    logAdminAction($_SESSION['admin_id'], 'edit_user', 'users', $userId, [
                        'email' => $email,
                        'is_active' => $isActive,
                        'password_reset' => $resetPassword
                    ]);
                }
                
                $conn->commit();
                redirect('/admin/users/view.php?id=' . $userId, 'User updated successfully!', 'success');
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("User Update Error: " . $e->getMessage());
                $errors[] = 'Failed to update user: ' . $e->getMessage();
            }
        }
    }
}

// Re-fetch user data after potential update
$stmt = $conn->prepare("
    SELECT u.*, up.first_name, up.last_name, up.profile_image, up.whatsapp_number, 
           up.date_of_birth, up.gender
    FROM users u
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE u.id = ?
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

$userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if (empty($userName)) {
    $userName = explode('@', $user['email'])[0];
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section d-flex justify-content-between align-items-center">
    <div>
        <h1 class="page-title">Edit User</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/users/manage.php">Users</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/admin/users/view.php?id=<?php echo $userId; ?>"><?php echo htmlspecialchars($userName); ?></a></li>
                <li class="breadcrumb-item active">Edit</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/users/view.php?id=<?php echo $userId; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Profile
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    
    <div class="row">
        <div class="col-md-8">
            <!-- Personal Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Personal Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            <?php if ($user['email_verified_at']): ?>
                                <small class="text-success"><i class="fas fa-check-circle"></i> Email verified</small>
                            <?php else: ?>
                                <small class="text-warning"><i class="fas fa-exclamation-circle"></i> Email not verified</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Phone <span class="text-danger">*</span></label>
                            <input type="tel" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            <?php if ($user['phone_verified_at']): ?>
                                <small class="text-success"><i class="fas fa-check-circle"></i> Phone verified</small>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">WhatsApp Number</label>
                            <input type="tel" name="whatsapp_number" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['whatsapp_number'] ?? ''); ?>"
                                   placeholder="+233XXXXXXXXX or 0XXXXXXXXX">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" 
                                   value="<?php echo $user['date_of_birth'] ?? ''; ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select">
                                <option value="">Select Gender</option>
                                <option value="male" <?php echo ($user['gender'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($user['gender'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($user['gender'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Account Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Account Settings</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                                   <?php echo $user['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                Account Active
                            </label>
                        </div>
                        <small class="text-muted">Inactive users cannot log in to the system.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">User Identifier</label>
                        <input type="text" class="form-control" 
                               value="<?php echo htmlspecialchars($user['user_identifier'] ?? 'Not assigned'); ?>" 
                               readonly>
                        <small class="text-muted">This is a system-generated unique identifier and cannot be changed.</small>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="reset_password" id="reset_password" 
                                   onchange="document.getElementById('password_fields').style.display = this.checked ? 'block' : 'none';">
                            <label class="form-check-label" for="reset_password">
                                Reset Password
                            </label>
                        </div>
                    </div>
                    
                    <div id="password_fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" name="new_password" class="form-control" 
                                   minlength="8" placeholder="Minimum 8 characters">
                            <small class="text-muted">Leave blank if you don't want to change the password.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <!-- Profile Image -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Profile Image</h5>
                </div>
                <div class="card-body text-center">
                    <?php 
                    $profileImage = $user['profile_image'] ?? null;
                    $imagePath = __DIR__ . '/../../assets/images/profiles/' . ($profileImage ?? '');
                    ?>
                    <?php if ($profileImage && file_exists($imagePath) && filesize($imagePath) > 0): ?>
                        <img src="<?php echo BASE_URL; ?>/assets/images/profiles/<?php echo htmlspecialchars($profileImage); ?>?v=<?php echo time(); ?>" 
                             alt="Profile" 
                             class="rounded-circle mb-3" 
                             style="width: 150px; height: 150px; object-fit: cover;">
                    <?php else: ?>
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" 
                             style="width: 150px; height: 150px; font-size: 4rem; font-weight: 600;">
                            <?php echo strtoupper(substr($userName, 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                    <small class="text-muted d-block mt-2">JPG, PNG, GIF, or WEBP. Max 5MB.</small>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Account Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <strong>Member Since:</strong><br>
                        <span><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>Last Updated:</strong><br>
                        <span><?php echo date('M d, Y H:i', strtotime($user['updated_at'])); ?></span>
                    </div>
                    <?php if ($user['email_verified_at']): ?>
                        <div class="mb-3">
                            <strong>Email Verified:</strong><br>
                            <span class="text-success"><?php echo date('M d, Y', strtotime($user['email_verified_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($user['phone_verified_at']): ?>
                        <div class="mb-3">
                            <strong>Phone Verified:</strong><br>
                            <span class="text-success"><?php echo date('M d, Y', strtotime($user['phone_verified_at'])); ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="<?php echo BASE_URL; ?>/admin/users/view.php?id=<?php echo $userId; ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Edit User - ' . htmlspecialchars($userName) . ' - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>

