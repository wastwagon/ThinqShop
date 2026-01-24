<?php
/**
 * View Support Ticket - Premium Chat Experience
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Optional includes
if (file_exists(__DIR__ . '/../../includes/notification-helper.php')) {
    require_once __DIR__ . '/../../includes/notification-helper.php';
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

$ticketId = intval($_GET['id'] ?? 0);

// Get ticket details
$stmt = $conn->prepare("SELECT * FROM tickets WHERE id = ? AND user_id = ?");
$stmt->execute([$ticketId, $userId]);
$ticket = $stmt->fetch();

if (!$ticket) {
    redirect('/user/tickets/', 'Request not found in our archive.', 'danger');
}

// Handle reply session
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply'])) {
    $message = $_POST['message'] ?? '';
    
    if (empty($message)) {
        $errors[] = 'Message content is mandatory for transmission.';
    } else {
        try {
            $conn->beginTransaction();
            
            $stmt = $conn->prepare("
                INSERT INTO ticket_messages (ticket_id, user_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$ticketId, $userId, $message]);
            
            // Re-open if closed or just update activity
            if ($ticket['status'] === 'resolved' || $ticket['status'] === 'waiting_customer') {
                $stmt = $conn->prepare("UPDATE tickets SET status = 'in_progress', updated_at = NOW() WHERE id = ?");
                $stmt->execute([$ticketId]);
            }
            
            $conn->commit();
            
            // Notify Desk Analysts
            if (class_exists('NotificationHelper')) {
                NotificationHelper::notifyAllAdmins('ticket_replied', 'Ticket Activity: #' . $ticket['ticket_number'],
                    'Customer submitted a new message on ticket: ' . $ticket['subject'],
                    BASE_URL . '/admin/tickets/view.php?id=' . $ticketId
                );
            }
            
            redirect('/user/tickets/view.php?id=' . $ticketId, 'Message transmitted successfully.', 'success');
            
        } catch (Exception $e) {
            $conn->rollBack();
            $errors[] = 'Network disruption: Failed to deliver message.';
        }
    }
}

// Get conversation history
$stmt = $conn->prepare("
    SELECT tm.*, 
           up.first_name, up.last_name,
           au.username as admin_username
    FROM ticket_messages tm
    LEFT JOIN users u ON tm.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN admin_users au ON tm.admin_id = au.id
    WHERE tm.ticket_id = ? AND tm.is_internal = 0
    ORDER BY tm.created_at ASC
");
$stmt->execute([$ticketId]);
$messages = $stmt->fetchAll();

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-ticket-view.css'
];

ob_start();
?>

<div class="mb-5">
    <a href="<?php echo BASE_URL; ?>/user/tickets/" class="btn btn-outline-light text-dark rounded-pill px-4 fw-800 x-small shadow-sm mb-4">
        <i class="fas fa-chevron-left me-2"></i> HELP CENTER
    </a>
    
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="chat-canvas shadow-sm">
                <div class="text-center mb-4">
                    <span class="x-small fw-800 text-muted text-uppercase letter-spacing-1">Secure Channel Established • <?php echo strtoupper(date('M d, Y')); ?></span>
                </div>

                <?php foreach ($messages as $msg): 
                    $isAdmin = (bool)$msg['admin_id'];
                ?>
                    <div class="msg-bubble-p <?php echo $isAdmin ? 'msg-admin-p' : 'msg-user-p shadow-sm'; ?>">
                        <?php if ($isAdmin): ?>
                            <div class="d-flex align-items-center gap-2 mb-2 opacity-75 x-small fw-800">
                                <i class="fas fa-shield-halved"></i>
                                DESK ANALYST
                            </div>
                        <?php endif; ?>
                        
                        <div class="msg-content <?php echo !$isAdmin ? 'fw-bold small' : ''; ?>">
                            <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                        </div>
                        
                        <div class="meta-chat">
                            <span><?php echo date('h:i A', strtotime($msg['created_at'])); ?></span>
                            <?php if (!$isAdmin): ?>
                                <i class="fas fa-check-double text-primary"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
                <div class="mt-4">
                    <form method="POST">
                        <div class="reply-input-p shadow-sm">
                            <textarea name="message" class="form-control reply-textarea-p fw-bold" rows="3" required placeholder="Type your response here..."></textarea>
                            <div class="d-flex justify-content-between align-items-center p-2">
                                <span class="text-muted x-small fw-800 text-uppercase letter-spacing-1 ms-2">
                                    <i class="fas fa-paperclip me-1"></i> Attachments coming soon
                                </span>
                                <button type="submit" name="reply" class="btn btn-primary btn-premium px-4">
                                    SEND MESSAGE <i class="fas fa-paper-plane ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="card border-1 border-dashed text-center rounded-4 mt-4 p-5 bg-white shadow-sm">
                    <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 64px; height: 64px; border: 1px dashed #cbd5e1;">
                        <i class="fas fa-lock text-muted opacity-30"></i>
                    </div>
                    <h6 class="fw-800 text-dark mb-1 text-uppercase letter-spacing-1">Investigation Archived</h6>
                    <p class="x-small text-muted mb-0 fw-bold">THIS THREAD IS ARCHIVED. IF YOU HAVE FURTHER INQUIRIES, PLEASE INITIATE A NEW INVESTIGATION.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <div class="ticket-sidebar-card shadow-sm">
                <div class="mb-5 border-bottom pb-4">
                    <div class="x-small fw-800 text-muted text-uppercase mb-2 letter-spacing-1">Request Identity</div>
                    <h5 class="fw-800 text-dark mb-1">#<?php echo htmlspecialchars($ticket['ticket_number']); ?></h5>
                    <p class="x-small text-muted mb-3 fw-bold text-uppercase"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                    
                    <?php 
                    $sPill = 'bg-info-soft text-info';
                    if($ticket['status'] === 'resolved') $sPill = 'bg-secondary-soft text-secondary';
                    elseif($ticket['status'] === 'waiting_customer') $sPill = 'bg-warning-soft text-warning';
                    ?>
                    <span class="status-p-pill <?php echo $sPill; ?>">
                        <?php echo strtoupper(str_remove_snake($ticket['status'])); ?>
                    </span>
                </div>

                <div class="mb-5">
                    <div class="x-small fw-800 text-muted text-uppercase mb-3 letter-spacing-1">Triage Intelligence</div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="x-small text-muted fw-800">PRIORITY</span>
                        <?php 
                        $pPill = 'bg-secondary-soft text-secondary';
                        if($ticket['priority'] === 'urgent' || $ticket['priority'] === 'high') $pPill = 'bg-danger-soft text-danger';
                        elseif($ticket['priority'] === 'medium') $pPill = 'bg-warning-soft text-warning';
                        ?>
                        <span class="badge <?php echo $pPill; ?> rounded-pill px-3 py-1 fw-800 x-small"><?php echo strtoupper($ticket['priority']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="x-small text-muted fw-800">LOG CATEGORY</span>
                        <span class="x-small fw-800 text-dark"><?php echo strtoupper($ticket['category']); ?></span>
                    </div>
                </div>

                <div class="mb-5 border-top pt-4">
                    <div class="x-small fw-800 text-muted text-uppercase mb-3 letter-spacing-1">Operational Timeline</div>
                    <div class="d-flex justify-content-between text-muted x-small fw-800 mb-2">
                        <span>LOGGED ON</span>
                        <span><?php echo strtoupper(date('M d, Y', strtotime($ticket['created_at']))); ?></span>
                    </div>
                    <div class="d-flex justify-content-between text-muted x-small fw-800">
                        <span>LATEST UPDATE</span>
                        <span><?php echo strtoupper(date('M d, H:i', strtotime($ticket['updated_at'] ?: $ticket['created_at']))); ?></span>
                    </div>
                </div>

                <div class="bg-primary-soft p-3 rounded-4 border-0">
                    <div class="d-flex gap-2">
                        <i class="fas fa-clock text-primary mt-1"></i>
                        <div class="x-small text-dark fw-bold opacity-75">
                            TYPICAL RESOLUTION CYCLE FOR <span class="text-primary text-uppercase"><?php echo $ticket['priority']; ?></span> PRIORITY IS 4.5 HOURS.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Investigation #' . $ticket['ticket_number'] . ' - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
