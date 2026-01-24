<?php
/**
 * Admin View Ticket
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../includes/admin-auth-check.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/notification-helper.php';
require_once __DIR__ . '/../config/email-service.php';

$db = new Database();
$conn = $db->getConnection();
$adminId = $_SESSION['admin_id'];

$ticketId = intval($_GET['id'] ?? 0);

// Get ticket
$stmt = $conn->prepare("
    SELECT t.*, 
           u.email as user_email,
           up.first_name, up.last_name,
           au.username as assigned_admin
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN admin_users au ON t.assigned_to = au.id
    WHERE t.id = ?
");
$stmt->execute([$ticketId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    redirect('/admin/tickets/', 'Ticket not found', 'error');
}

// Handle actions
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reply'])) {
        $message = $_POST['message'] ?? '';
        $isInternal = isset($_POST['is_internal']) ? 1 : 0;
        
        if (!empty($message)) {
            try {
                $stmt = $conn->prepare("
                    INSERT INTO ticket_messages (ticket_id, admin_id, message, is_internal, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$ticketId, $adminId, $message, $isInternal]);
                
                // Update ticket status
                if (!$isInternal && $ticket['status'] === 'waiting_customer') {
                    $stmt = $conn->prepare("UPDATE tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$ticketId]);
                }
                
                // Notify user if not internal
                if (!$isInternal) {
                    $user = getCurrentUser();
                    NotificationHelper::sendNotification('ticket_replied', $ticket['user_id'], null, $ticket['user_email'], [
                        'title' => 'New Reply on Your Ticket',
                        'message' => 'Admin replied to your ticket: ' . $ticket['subject'],
                        'link' => BASE_URL . '/user/tickets/view.php?id=' . $ticketId,
                        'TICKET_NUMBER' => $ticket['ticket_number'],
                        'TICKET_SUBJECT' => $ticket['subject'],
                        'REPLY_MESSAGE' => $message,
                        'TICKET_LINK' => BASE_URL . '/user/tickets/view.php?id=' . $ticketId
                    ]);
                }
                
                $success = 'Reply sent successfully';
                redirect('/admin/tickets/view.php?id=' . $ticketId, 'Reply sent successfully', 'success');
            } catch (Exception $e) {
                $error = 'Failed to send reply: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_status'])) {
        $newStatus = sanitize($_POST['status'] ?? '');
        $assignedTo = !empty($_POST['assigned_to']) ? intval($_POST['assigned_to']) : null;
        
        try {
            $stmt = $conn->prepare("UPDATE tickets SET status = ?, assigned_to = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $assignedTo, $ticketId]);
            $success = 'Ticket updated successfully';
            redirect('/admin/tickets/view.php?id=' . $ticketId, 'Ticket updated successfully', 'success');
        } catch (Exception $e) {
            $error = 'Failed to update ticket: ' . $e->getMessage();
        }
    }
}

// Get ticket messages
$stmt = $conn->prepare("
    SELECT tm.*, 
           u.email as user_email,
           up.first_name, up.last_name,
           au.username as admin_username
    FROM ticket_messages tm
    LEFT JOIN users u ON tm.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN admin_users au ON tm.admin_id = au.id
    WHERE tm.ticket_id = ? AND (tm.is_internal = 0 OR tm.admin_id = ?)
    ORDER BY tm.created_at ASC
");
$stmt->execute([$ticketId, $adminId]);
$messages = $stmt->fetchAll();

// Get all admins for assignment
$stmt = $conn->query("SELECT id, username FROM admin_users WHERE is_active = 1");
$admins = $stmt->fetchAll();

$pageTitle = 'Ticket #' . $ticket['ticket_number'] . ' - ' . APP_NAME;

// Start output buffering to capture content
ob_start();
?>

<div class="page-title-section">
        <h1 class="page-title">Ticket #<?php echo htmlspecialchars($ticket['ticket_number']); ?></h1>
        <a href="<?php echo BASE_URL; ?>/admin/tickets/" class="btn btn-secondary">Back to Tickets</a>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><?php echo htmlspecialchars($ticket['subject']); ?></h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="messages">
                        <?php foreach ($messages as $msg): ?>
                            <div class="message mb-4 <?php echo $msg['admin_id'] ? 'admin-message' : 'user-message'; ?>">
                                <div class="d-flex align-items-start">
                                    <div class="message-avatar me-3">
                                        <?php if ($msg['admin_id']): ?>
                                            <div class="avatar bg-primary text-white">
                                                <?php echo strtoupper(substr($msg['admin_username'], 0, 1)); ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="avatar bg-secondary text-white">
                                                <?php 
                                                $name = trim(($msg['first_name'] ?? '') . ' ' . ($msg['last_name'] ?? ''));
                                                echo strtoupper(substr($name ?: $msg['user_email'], 0, 1)); 
                                                ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="message-content flex-grow-1">
                                        <div class="message-header mb-2">
                                            <strong>
                                                <?php 
                                                if ($msg['admin_id']) {
                                                    echo htmlspecialchars($msg['admin_username']) . ' (Admin)';
                                                    if ($msg['is_internal']) {
                                                        echo ' <span class="badge bg-warning">Internal Note</span>';
                                                    }
                                                } else {
                                                    $name = trim(($msg['first_name'] ?? '') . ' ' . ($msg['last_name'] ?? ''));
                                                    echo htmlspecialchars($name ?: $msg['user_email']);
                                                }
                                                ?>
                                            </strong>
                                            <span class="text-muted ms-2">
                                                <?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>
                                            </span>
                                        </div>
                                        <div class="message-body">
                                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <hr>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Add Reply</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_internal" class="form-check-input" id="is_internal">
                            <label class="form-check-label" for="is_internal">Internal Note (not visible to customer)</label>
                        </div>
                        <button type="submit" name="reply" class="btn btn-primary">Send Reply</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h6>Ticket Details</h6>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="open" <?php echo $ticket['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                <option value="in_progress" <?php echo $ticket['status'] === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="waiting_customer" <?php echo $ticket['status'] === 'waiting_customer' ? 'selected' : ''; ?>>Waiting Customer</option>
                                <option value="resolved" <?php echo $ticket['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                <option value="closed" <?php echo $ticket['status'] === 'closed' ? 'selected' : ''; ?>>Closed</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Assign To</label>
                            <select name="assigned_to" class="form-select">
                                <option value="">Unassigned</option>
                                <?php foreach ($admins as $admin): ?>
                                    <option value="<?php echo $admin['id']; ?>" <?php echo $ticket['assigned_to'] == $admin['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($admin['username']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="update_status" class="btn btn-sm btn-primary w-100">Update</button>
                    </form>

                    <hr>

                    <div class="mb-2">
                        <strong>Customer:</strong><br>
                        <?php 
                        $customerName = trim(($ticket['first_name'] ?? '') . ' ' . ($ticket['last_name'] ?? ''));
                        echo htmlspecialchars($customerName ?: $ticket['user_email']); 
                        ?>
                    </div>

                    <div class="mb-2">
                        <strong>Category:</strong><br>
                        <span class="badge bg-secondary"><?php echo ucfirst($ticket['category']); ?></span>
                    </div>

                    <div class="mb-2">
                        <strong>Priority:</strong><br>
                        <span class="badge bg-<?php 
                            echo $ticket['priority'] === 'urgent' ? 'danger' : 
                                ($ticket['priority'] === 'high' ? 'warning' : 'info'); 
                        ?>">
                            <?php echo ucfirst($ticket['priority']); ?>
                        </span>
                    </div>

                    <div class="mb-2">
                        <strong>Created:</strong><br>
                        <?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/admin-ticket-view.css'
];

// Capture content and include layout
$content = ob_get_clean();
include __DIR__ . '/../layouts/admin-layout.php';
?>




