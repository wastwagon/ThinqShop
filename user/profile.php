<?php
/**
 * User Profile Management
 * ThinQShopping Platform
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

// Get user addresses
$stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
$stmt->execute([$userId]);
$addresses = $stmt->fetchAll();

$tab = $_GET['tab'] ?? 'profile';
$errors = [];
$success = false;

// Process profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $firstName = sanitize($_POST['first_name'] ?? '');
        $lastName = sanitize($_POST['last_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        
        if (empty($firstName)) {
            $errors[] = 'First name is required.';
        }
        
        // Phone is optional, but validate format if provided
        if (!empty($phone) && !isValidPhone($phone)) {
            $errors[] = 'Please enter a valid phone number.';
        }
        
        // Handle profile image upload
        $profileImage = $profile['profile_image'] ?? null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK && $_FILES['profile_image']['size'] > 0) {
            $uploadDir = __DIR__ . '/../assets/images/profiles/';
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0777, true);
            }
            
            if (is_dir($uploadDir)) {
                @chmod($uploadDir, 0777);
                $uploadResult = uploadImage($_FILES['profile_image'], $uploadDir);
                if ($uploadResult['success']) {
                    if ($profileImage) {
                        $oldImagePath = $uploadDir . $profileImage;
                        if (file_exists($oldImagePath)) {
                            @unlink($oldImagePath);
                        }
                    }
                    $profileImage = $uploadResult['filename'];
                } else {
                    $errors[] = $uploadResult['message'] ?? 'Failed to upload profile image.';
                }
            } else {
                $errors[] = 'Upload directory does not exist and could not be created.';
            }
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                if ($profile) {
                    $stmt = $conn->prepare("UPDATE user_profiles SET first_name = ?, last_name = ?, profile_image = ?, updated_at = NOW() WHERE user_id = ?");
                    $stmt->execute([$firstName, $lastName, $profileImage, $userId]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO user_profiles (user_id, first_name, last_name, profile_image, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$userId, $firstName, $lastName, $profileImage]);
                }
                
                $phoneFormatted = formatPhone($phone);
                $stmt = $conn->prepare("UPDATE users SET phone = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$phoneFormatted, $userId]);
                
                $conn->commit();
                $success = true;
                redirect('/user/profile.php?tab=profile&updated=' . time(), 'Profile updated successfully!', 'success');
            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = 'Failed to update profile: ' . $e->getMessage();
            }
        }
    }
}

// Process address add/update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_address'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $addressId = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
        $fullName = sanitize($_POST['full_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $street = sanitize($_POST['street'] ?? '');
        $city = sanitize($_POST['city'] ?? '');
        $region = sanitize($_POST['region'] ?? '');
        $country = sanitize($_POST['country'] ?? 'Ghana');
        $isDefault = isset($_POST['is_default']) ? 1 : 0;
        
        if (empty($fullName) || empty($phone) || empty($street) || empty($city) || empty($region)) {
            $errors[] = 'Please fill all required fields.';
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                if ($isDefault) {
                    $stmt = $conn->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
                    $stmt->execute([$userId]);
                }
                
                if ($addressId > 0) {
                    $stmt = $conn->prepare("UPDATE addresses SET full_name = ?, phone = ?, street = ?, city = ?, region = ?, country = ?, is_default = ?, updated_at = NOW() WHERE id = ? AND user_id = ?");
                    $stmt->execute([$fullName, $phone, $street, $city, $region, $country, $isDefault, $addressId, $userId]);
                } else {
                    $stmt = $conn->prepare("INSERT INTO addresses (user_id, full_name, phone, street, city, region, country, is_default, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->execute([$userId, $fullName, $phone, $street, $city, $region, $country, $isDefault]);
                }
                
                $conn->commit();
                $success = true;
                redirect('/user/profile.php?tab=addresses', 'Address saved successfully!', 'success');
            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = 'Failed to save address.';
            }
        }
    }
}

// Process password update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            $errors[] = 'All password fields are required.';
        } elseif ($newPassword !== $confirmPassword) {
            $errors[] = 'New passwords do not match.';
        } elseif (strlen($newPassword) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userRow = $stmt->fetch();
            
            if (!$userRow || !password_verify($currentPassword, $userRow['password'])) {
                $errors[] = 'Current password is incorrect.';
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                if ($stmt->execute([$hashedPassword, $userId])) {
                    $success = true;
                    redirect('/user/profile.php?tab=security', 'Password updated successfully!', 'success');
                } else {
                    $errors[] = 'Failed to update password.';
                }
            }
        }
    }
}

// Reload data after update (only if not redirecting)
if (!$success && !empty($errors)) {
    $stmt = $conn->prepare("SELECT * FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $profile = $stmt->fetch();
    $user = getCurrentUser();
    $stmt = $conn->prepare("SELECT * FROM addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC");
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll();
}

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-profile.css'
];

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

<div class="profile-container-premium shadow-sm border-0">
    <div class="profile-sidebar-premium">
        <div class="avatar-identity-premium">
            <div class="avatar-frame-premium shadow-sm" id="imagePreviewContainer">
                <?php 
                $profileImage = $profile['profile_image'] ?? null;
                if ($profileImage && file_exists(__DIR__ . '/../assets/images/profiles/' . $profileImage)): ?>
                    <img src="<?php echo BASE_URL; ?>/assets/images/profiles/<?php echo htmlspecialchars($profileImage); ?>?v=<?php echo time(); ?>" alt="Profile" class="avatar-img-premium">
                <?php else: ?>
                    <div class="avatar-initials-premium">
                        <?php echo strtoupper(substr($profile['first_name'] ?? $user['name'] ?? 'U', 0, 1)); ?>
                    </div>
                <?php endif; ?>
            </div>
            <h6 class="fw-bold mb-1 text-dark"><?php echo htmlspecialchars(($profile['first_name'] ?? '') . ' ' . ($profile['last_name'] ?? '')); ?></h6>
            <div class="badge-status-premium mb-2">VERIFIED USER</div>
            
            <?php if (!empty($user['user_identifier'])): ?>
                <div class="text-primary small fw-800 mb-4 letter-spacing-1"><?php echo htmlspecialchars($user['user_identifier']); ?></div>
            <?php endif; ?>
            
            <p class="text-muted x-small mb-4 text-center"><?php echo htmlspecialchars($user['email']); ?></p>
        </div>

        <nav class="nav-pills-premium">
            <a class="nav-link <?php echo $tab === 'profile' ? 'active' : ''; ?>" href="?tab=profile">
                <i class="fas fa-id-card"></i> Personal Details
            </a>
            <a class="nav-link <?php echo $tab === 'addresses' ? 'active' : ''; ?>" href="?tab=addresses">
                <i class="fas fa-map-marker-alt"></i> Addresses
            </a>
            <a class="nav-link <?php echo $tab === 'security' ? 'active' : ''; ?>" href="?tab=security">
                <i class="fas fa-shield-alt"></i> Security
            </a>
            <a class="nav-link <?php echo $tab === 'account' ? 'active' : ''; ?>" href="?tab=account">
                <i class="fas fa-user-slash"></i> Account
            </a>
        </nav>
    </div>

    <div class="profile-main-premium">
        <?php if ($tab === 'profile'): ?>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="update_profile" value="1">

                <h5 class="fw-bold text-dark mb-4">Personal Information</h5>
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label-premium">First Name</label>
                        <input type="text" name="first_name" class="form-control form-control-premium" value="<?php echo htmlspecialchars($profile['first_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-premium">Last Name</label>
                        <input type="text" name="last_name" class="form-control form-control-premium" value="<?php echo htmlspecialchars($profile['last_name'] ?? ''); ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label-premium">Email Address</label>
                        <input type="email" class="form-control form-control-premium bg-light" value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                    </div>
                    <div class="col-12">
                        <label class="form-label-premium">Phone Number <span class="text-muted">(Optional)</span></label>
                        <input type="tel" name="phone" class="form-control form-control-premium" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                    </div>
                    
                    <div class="col-12">
                        <div class="p-4 bg-light rounded-4 border border-dashed text-center mt-3">
                            <i class="fas fa-camera fa-2x text-muted opacity-50 mb-3"></i>
                            <h6 class="fw-bold small mb-1">Profile Photo</h6>
                            <p class="text-muted x-small mb-3">Upload a profile picture.</p>
                            <label class="btn btn-outline-primary btn-sm rounded-pill px-4 fw-bold" style="cursor: pointer;">
                                BROWSE PHOTO
                                <input type="file" name="profile_image" class="d-none" onchange="previewProfileImage(this)">
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-top">
                    <button type="submit" class="btn btn-primary btn-premium">Save Changes</button>
                </div>
            </form>

        <?php elseif ($tab === 'addresses'): ?>
            <div class="d-flex justify-content-between align-items-center mb-5">
                <h5 class="fw-bold text-dark mb-0">My Addresses</h5>
                <button type="button" class="btn btn-outline-primary rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                    <i class="fas fa-plus me-1"></i> Add New Address
                </button>
            </div>

            <div class="row g-4">
                <?php if (empty($addresses)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-4" style="width: 80px; height: 80px;">
                            <i class="fas fa-map-location-dot fa-2x text-muted opacity-30"></i>
                        </div>
                        <h6 class="fw-bold text-dark">No addresses found</h6>
                        <p class="text-muted small">Add an address for shipping and deliveries.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($addresses as $addr): ?>
                        <div class="col-md-6">
                            <div class="address-item-premium <?php echo $addr['is_default'] ? 'is-default' : ''; ?>">
                                <div class="d-flex justify-content-between align-items-start mb-4">
                                    <div>
                                        <h6 class="fw-800 text-dark mb-1 text-uppercase small"><?php echo htmlspecialchars($addr['full_name']); ?></h6>
                                        <div class="text-primary x-small fw-800"><?php echo htmlspecialchars($addr['phone']); ?></div>
                                    </div>
                                    <?php if ($addr['is_default']): ?>
                                        <span class="badge bg-primary rounded-pill px-3 py-1 x-small fw-800">PRIMARY</span>
                                    <?php endif; ?>
                                </div>
                                <p class="text-muted small mb-4 lh-lg">
                                    <?php echo htmlspecialchars($addr['street']); ?><br>
                                    <?php echo htmlspecialchars($addr['city'] . ', ' . $addr['region']); ?><br>
                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($addr['country'] ?? 'Ghana'); ?></span>
                                </p>
                                <div class="d-flex gap-3 align-items-center mt-auto pt-3 border-top border-light">
                                    <button class="btn btn-light btn-sm rounded-pill px-3 fw-800 x-small text-dark" onclick="editAddress(<?php echo htmlspecialchars(json_encode($addr)); ?>)">
                                        EDIT
                                    </button>
                                    <?php if (!$addr['is_default']): ?>
                                        <button class="btn btn-link btn-sm text-decoration-none text-muted x-small fw-bold p-0">Set as Default</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        <?php elseif ($tab === 'security'): ?>
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="change_password" value="1">

                <h5 class="fw-bold text-dark mb-4">Security Settings</h5>
                <div class="row g-4">
                    <div class="col-12">
                        <label class="form-label-premium">Current Authentication Password</label>
                        <input type="password" name="current_password" class="form-control form-control-premium" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-premium">New Access Key</label>
                        <input type="password" name="new_password" class="form-control form-control-premium" required minlength="8">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label-premium">Confirm New Access Key</label>
                        <input type="password" name="confirm_password" class="form-control form-control-premium" required>
                    </div>
                </div>

                <div class="mt-5 p-4 rounded-4 bg-light border-0">
                    <div class="d-flex gap-3">
                        <i class="fas fa-shield-check text-primary mt-1"></i>
                        <div>
                            <h6 class="fw-bold small mb-1">Password Requirements</h6>
                            <p class="text-muted x-small mb-0">Use at least 8 characters for your password.</p>
                        </div>
                    </div>
                </div>

                <div class="mt-5 pt-4 border-top">
                    <button type="submit" class="btn btn-primary btn-premium">Update Password</button>
                </div>
            </form>
            
        <?php elseif ($tab === 'account'): ?>
            <h5 class="fw-bold text-dark mb-4">Account Management</h5>
            
            <div class="alert alert-danger border-0 rounded-4 p-4 shadow-sm">
                <div class="d-flex gap-3 mb-3">
                    <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                    <div>
                        <h6 class="fw-bold mb-2">Delete Your Account</h6>
                        <p class="small mb-0">
                            Once you delete your account, there is no going back. This will permanently delete:
                        </p>
                    </div>
                </div>
                <ul class="small mb-3">
                    <li>Your profile and personal information</li>
                    <li>Order history and tracking data</li>
                    <li>Wallet balance and transaction history</li>
                    <li>Saved addresses and preferences</li>
                    <li>Wishlist and cart items</li>
                </ul>
                <div class="bg-white rounded-3 p-3 mb-3">
                    <p class="small mb-2"><strong>Grace Period:</strong></p>
                    <p class="x-small text-muted mb-0">
                        After requesting deletion, you'll have 30 days to cancel before your account is permanently deleted. 
                        We'll send you an email with a cancellation link.
                    </p>
                </div>
                <a href="<?php echo BASE_URL; ?>/user/delete-account.php" 
                   class="btn btn-danger btn-sm rounded-pill px-4 fw-bold text-white">
                    <i class="fas fa-trash-alt me-2"></i>Delete My Account
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="addAddressModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold text-dark mb-0" id="addressModalTitle">Address Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body p-4">
                    <input type="hidden" name="address_id" id="address_id">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="save_address" value="1">
                    <div class="mb-4">
                        <label class="form-label-premium">Full Name</label>
                        <input type="text" name="full_name" id="full_name" class="form-control form-control-premium" required>
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label-premium">Phone Number</label>
                            <input type="tel" name="phone" id="address_phone" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label-premium">Street Address</label>
                            <input type="text" name="street" id="street" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-premium">City</label>
                            <input type="text" name="city" id="city" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label-premium">Region</label>
                            <input type="text" name="region" id="region" class="form-control form-control-premium" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label-premium">Country</label>
                            <select name="country" id="country" class="form-select form-control-premium shadow-none" required>
                                <option value="Ghana" selected>Ghana</option>
                                <option value="China">China</option>
                                <option value="UK">United Kingdom</option>
                                <option value="USA">United States</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-check mt-4">
                        <input type="checkbox" name="is_default" id="is_default" class="form-check-input">
                        <label class="form-check-label small fw-bold text-muted" for="is_default">Set as default address</label>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="submit" class="btn btn-primary w-100 btn-premium py-3">Save Address</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewProfileImage(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const container = document.getElementById('imagePreviewContainer');
            if (container) {
                container.innerHTML = `<img src="${e.target.result}" alt="Preview" class="avatar-img-premium">`;
            }
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function editAddress(address) {
    document.getElementById('addressModalTitle').textContent = 'Edit Address';
    document.getElementById('address_id').value = address.id;
    document.getElementById('full_name').value = address.full_name || '';
    document.getElementById('address_phone').value = address.phone || '';
    document.getElementById('street').value = address.street || '';
    document.getElementById('city').value = address.city || '';
    document.getElementById('region').value = address.region || '';
    document.getElementById('country').value = address.country || 'Ghana';
    document.getElementById('is_default').checked = address.is_default == 1;
    var modal = new bootstrap.Modal(document.getElementById('addAddressModal'));
    modal.show();
}
</script>
<?php
$content = ob_get_clean();
$pageTitle = 'Profile Settings - ' . APP_NAME;
include __DIR__ . '/../includes/layouts/user-layout.php';
?>
