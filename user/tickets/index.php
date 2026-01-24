<?php
/**
 * User Help Center - Premium Support Dashboard
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get user tickets with message count and last reply timestamp
$stmt = $conn->prepare("
    SELECT t.*, 
           (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id AND is_internal = 0) as message_count,
           (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_reply
    FROM tickets t
    WHERE t.user_id = ?
    ORDER BY t.created_at DESC
");
$stmt->execute([$userId]);
$tickets = $stmt->fetchAll();

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-tickets.css'
];

ob_start();
?>



<div class="d-flex justify-content-between align-items-center mb-4 px-1">
    <h6 class="fw-800 text-dark mb-0 text-uppercase letter-spacing-1 small">Communication Log</h6>
</div>

<?php if (empty($tickets)): ?>
    <div class="card border-1 shadow-sm rounded-4 text-center py-5 bg-white">
        <div class="card-body py-5">
            <div class="mb-4">
                <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 80px; height: 80px; border: 1px dashed #cbd5e1;">
                    <i class="fas fa-ticket-alt fa-2x text-muted opacity-30"></i>
                </div>
            </div>
            <h5 class="fw-800 text-dark text-uppercase letter-spacing-1">Log is empty</h5>
            <p class="text-muted mb-5 mx-auto small fw-bold" style="max-width: 320px;">
                NO ACTIVE SUPPORT CONVERSATIONS DETECTED.
            </p>
            <a href="<?php echo BASE_URL; ?>/user/tickets/create.php" class="btn btn-primary btn-premium px-5 py-3">
                Open New Ticket
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($tickets as $ticket): 
            $pClass = 'bg-secondary-soft text-secondary';
            if($ticket['priority'] === 'urgent' || $ticket['priority'] === 'high') $pClass = 'bg-danger-soft text-danger';
            elseif($ticket['priority'] === 'medium') $pClass = 'bg-warning-soft text-warning';
            elseif($ticket['priority'] === 'low') $pClass = 'bg-success-soft text-success';

            $sIndicator = 'bg-info';
            if($ticket['status'] === 'resolved') $sIndicator = 'bg-secondary';
            elseif($ticket['status'] === 'waiting_customer') $sIndicator = 'bg-warning';
        ?>
        <div class="col-12">
            <div class="ticket-card-premium shadow-sm" onclick="window.location.href='view.php?id=<?php echo $ticket['id']; ?>'">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <span class="ticket-id-pill">ID-<?php echo htmlspecialchars($ticket['ticket_number']); ?></span>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="support-category-tag"><?php echo strtoupper($ticket['category']); ?></span>
                            <span class="priority-badge-premium <?php echo $pClass; ?>">
                                <?php echo strtoupper($ticket['priority']); ?>
                            </span>
                        </div>
                        <h6 class="fw-800 text-dark mb-0 text-truncate small text-uppercase"><?php echo htmlspecialchars($ticket['subject']); ?></h6>
                    </div>
                    
                    <div class="col-md-3">
                        <div class="small fw-800 text-dark mb-1">
                            <span class="status-indicator-support <?php echo $sIndicator; ?>"></span>
                            <?php echo strtoupper(str_replace('_', ' ', $ticket['status'])); ?>
                        </div>
                        <div class="x-small text-muted fw-800 text-uppercase"><?php echo $ticket['message_count']; ?> MESSAGES RECORDED</div>
                    </div>
                    
                    <div class="col-md-3 text-md-end mt-3 mt-md-0">
                        <div class="text-muted x-small fw-800 text-uppercase letter-spacing-1 mb-1">Last Update</div>
                        <div class="small fw-800 text-dark">
                            <?php echo $ticket['last_reply'] ? strtoupper(date('M d, Y • h:i A', strtotime($ticket['last_reply']))) : strtoupper(date('M d, Y', strtotime($ticket['created_at']))); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="operating-protocol-premium mt-5 shadow-sm">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h6 class="fw-800 text-primary text-uppercase letter-spacing-1 mb-2 small">Operating Protocol</h6>
            <p class="x-small text-dark fw-bold opacity-75 mb-0">REQUESTS ARE TRIAGED BY SYSTEM PRIORITY. URGENT PROTOCOLS ARE ACTIVATED WITHIN 60 MINUTES OF SUBMISSION.</p>
        </div>
        <div class="col-md-4 text-md-end mt-3 mt-md-0">
            <span class="badge bg-primary text-white x-small rounded-pill px-4 py-2 fw-800">GMT 09:00 - 18:00</span>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Help Center - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
