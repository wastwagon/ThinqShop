<?php
/**
 * Admin Email Notification Settings
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

$errors = [];
$success = false;

// Get all notification settings
$stmt = $conn->query("SELECT * FROM email_notification_settings ORDER BY notification_name");
$notifications = $stmt->fetchAll();

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        try {
            $conn->beginTransaction();
            
            foreach ($notifications as $notification) {
                $type = $notification['notification_type'];
                $sendToUser = isset($_POST["send_to_user_{$type}"]) ? 1 : 0;
                $sendToAdmin = isset($_POST["send_to_admin_{$type}"]) ? 1 : 0;
                $isActive = isset($_POST["is_active_{$type}"]) ? 1 : 0;
                
                $stmt = $conn->prepare("
                    UPDATE email_notification_settings 
                    SET send_to_user = ?, send_to_admin = ?, is_active = ?
                    WHERE notification_type = ?
                ");
                $stmt->execute([$sendToUser, $sendToAdmin, $isActive, $type]);
            }
            
            $conn->commit();
            $success = 'Notification settings updated successfully.';
            
            // Reload notifications
            $stmt = $conn->query("SELECT * FROM email_notification_settings ORDER BY notification_name");
            $notifications = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = 'Error updating settings: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Email Notifications - ' . APP_NAME;
include __DIR__ . '/../../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="page-title">Email Notification Management</h1>
        <div>
            <a href="<?php echo BASE_URL; ?>/admin/email/templates.php" class="btn btn-secondary me-2">
                <i class="fas fa-envelope me-2"></i>Templates
            </a>
            <a href="<?php echo BASE_URL; ?>/admin/email/settings.php" class="btn btn-secondary">
                <i class="fas fa-cog me-2"></i>Settings
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

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Notification Type</th>
                                <th class="text-center">Send to User</th>
                                <th class="text-center">Send to Admin</th>
                                <th class="text-center">Active</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($notifications)): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted">No notification settings found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($notif['notification_name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($notif['notification_type']); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="send_to_user_<?php echo $notif['notification_type']; ?>"
                                                       name="send_to_user_<?php echo $notif['notification_type']; ?>"
                                                       <?php echo $notif['send_to_user'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="send_to_admin_<?php echo $notif['notification_type']; ?>"
                                                       name="send_to_admin_<?php echo $notif['notification_type']; ?>"
                                                       <?php echo $notif['send_to_admin'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="is_active_<?php echo $notif['notification_type']; ?>"
                                                       name="is_active_<?php echo $notif['notification_type']; ?>"
                                                       <?php echo $notif['is_active'] ? 'checked' : ''; ?>>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Notification Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-body">
            <h5 class="card-title">Email Notification Activities</h5>
            <p class="text-muted">The following activities can trigger email notifications:</p>
            
            <div class="row">
                <div class="col-md-6">
                    <h6>User-Facing Notifications:</h6>
                    <ul>
                        <li><strong>User Registration:</strong> Welcome email sent to new users</li>
                        <li><strong>Email Verification:</strong> Verification link sent after registration</li>
                        <li><strong>Order Placed:</strong> Order confirmation sent to customer</li>
                        <li><strong>Order Status Change:</strong> Updates when order status changes</li>
                        <li><strong>Transfer Requested:</strong> Confirmation when money transfer is initiated</li>
                        <li><strong>Transfer Completed:</strong> Notification when transfer is completed</li>
                        <li><strong>Shipment Booked:</strong> Confirmation when parcel is booked</li>
                        <li><strong>Shipment Status Update:</strong> Tracking updates for shipments</li>
                        <li><strong>Support Ticket Created:</strong> Confirmation when ticket is created</li>
                        <li><strong>Support Ticket Reply:</strong> Notification when admin replies</li>
                        <li><strong>Password Reset:</strong> Reset link sent when password is reset</li>
                        <li><strong>Procurement Requested:</strong> Confirmation when procurement request is submitted</li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h6>Admin Notifications:</h6>
                    <ul>
                        <li><strong>New Order:</strong> Notification when customer places order</li>
                        <li><strong>New Transfer:</strong> Notification when money transfer is requested</li>
                        <li><strong>New Shipment:</strong> Notification when parcel is booked</li>
                        <li><strong>New Support Ticket:</strong> Notification when user creates ticket</li>
                        <li><strong>New Procurement Request:</strong> Notification when procurement is requested</li>
                        <li><strong>New User Registration:</strong> Notification when new user registers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../../includes/admin-footer.php'; ?>






