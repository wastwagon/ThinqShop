<?php
/**
 * Reset Admin Password Script
 * Access via browser: http://localhost/ThinQShopping/admin/reset-admin.php
 * 
 * WARNING: Remove this file after use for security!
 */

require_once __DIR__ . '/../config/database.php';

// Default admin credentials
$username = 'admin';
$email = 'admin@thinqshopping.com';
$password = 'admin123';
$fullName = 'Administrator';
$role = 'super_admin';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // Hash the password
    $passwordHash = password_hash($password, PASSWORD_BCRYPT);
    
    // Check if admin user already exists
    $stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing admin
        $stmt = $conn->prepare("
            UPDATE admin_users 
            SET password = ?, email = ?, full_name = ?, role = ?, is_active = 1 
            WHERE id = ?
        ");
        $stmt->execute([$passwordHash, $email, $fullName, $role, $existing['id']]);
        $message = "✅ Admin user updated successfully!";
    } else {
        // Create new admin user
        $stmt = $conn->prepare("
            INSERT INTO admin_users (username, email, password, full_name, role, is_active) 
            VALUES (?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$username, $email, $passwordHash, $fullName, $role]);
        $message = "✅ Admin user created successfully!";
    }
    
    // Verify the password works
    $stmt = $conn->prepare("SELECT * FROM admin_users WHERE username = ?");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    $passwordValid = password_verify($password, $admin['password']);
    
} catch (Exception $e) {
    $error = "❌ Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Admin Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success {
            color: #28a745;
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .credentials {
            background: #e9ecef;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .credentials h3 {
            margin-top: 0;
        }
        .credentials code {
            display: block;
            margin: 5px 0;
            padding: 5px;
            background: white;
            border-radius: 3px;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Admin Password</h1>
        
        <?php if (isset($message)): ?>
            <div class="success">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($message)): ?>
            <div class="credentials">
                <h3>Admin Login Credentials:</h3>
                <code><strong>Username:</strong> <?php echo htmlspecialchars($username); ?></code>
                <code><strong>Email:</strong> <?php echo htmlspecialchars($email); ?></code>
                <code><strong>Password:</strong> <?php echo htmlspecialchars($password); ?></code>
            </div>
            
            <?php if (isset($passwordValid) && $passwordValid): ?>
                <div class="success">
                    ✅ Password verification test: PASSED
                </div>
            <?php else: ?>
                <div class="error">
                    ❌ Password verification test: FAILED
                </div>
            <?php endif; ?>
            
            <div class="warning">
                <strong>⚠️ IMPORTANT:</strong> 
                <ul>
                    <li>Change this password immediately after first login!</li>
                    <li>Delete this file (<code>admin/reset-admin.php</code>) for security!</li>
                </ul>
            </div>
            
            <p>
                <a href="<?php echo BASE_URL; ?>/admin/login.php" style="display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                    Go to Admin Login →
                </a>
            </p>
        <?php endif; ?>
    </div>
</body>
</html>






