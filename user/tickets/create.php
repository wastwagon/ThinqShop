<?php
/**
 * Create Support Ticket - Premium Design
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Optional includes - check if files exist
if (file_exists(__DIR__ . '/../../includes/notification-helper.php')) {
    require_once __DIR__ . '/../../includes/notification-helper.php';
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = sanitize($_POST['subject'] ?? '');
    $category = sanitize($_POST['category'] ?? 'general');
    $priority = sanitize($_POST['priority'] ?? 'medium');
    $message = $_POST['message'] ?? '';
    
    // Validation
    if (empty($subject)) {
        $errors[] = 'Direct subject is required to triage your request.';
    }
    if (empty($message)) {
        $errors[] = 'A detailed report/description is required.';
    }
    
    if (empty($errors)) {
        try {
            $conn->beginTransaction();
            
            // Generate ticket number
            $ticketNumber = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            
            // Create ticket
            $stmt = $conn->prepare("
                INSERT INTO tickets (ticket_number, user_id, subject, category, priority, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'open', NOW())
            ");
            $stmt->execute([$ticketNumber, $userId, $subject, $category, $priority]);
            $ticketId = $conn->lastInsertId();
            
            // Create initial message
            $stmt = $conn->prepare("
                INSERT INTO ticket_messages (ticket_id, user_id, message, created_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$ticketId, $userId, $message]);
            
            $conn->commit();
            
            // Send notifications (if NotificationHelper is available)
            if (class_exists('NotificationHelper')) {
                try {
                    $userName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
                    NotificationHelper::sendNotification('ticket_created', $userId, null, $user['email'], [
                        'title' => 'Ticket Created',
                        'message' => 'Your support ticket #' . $ticketNumber . ' has been created.',
                        'link' => BASE_URL . '/user/tickets/view.php?id=' . $ticketId
                    ]);
                } catch (Exception $e) {}
            }
            
            redirect('/user/tickets/view.php?id=' . $ticketId, 'Support request logged successfully!', 'success');
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Ticket creation error: " . $e->getMessage());
            $errors[] = 'Protocol failure: Failed to log your request. Please retry.';
        }
    }
}

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-ticket-create.css'
];

ob_start();
?>

<div class="mb-5">
    <a href="<?php echo BASE_URL; ?>/user/tickets/" class="btn btn-outline-light text-dark rounded-pill px-4 fw-bold shadow-sm mb-4">
        <i class="fas fa-chevron-left me-2"></i> HELP CENTER
    </a>
    
    <div class="ticket-create-card-premium">
        <div class="ticket-creation-hero">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 54px; height: 54px;">
                    <i class="fas fa-headset"></i>
                </div>
                <div>
                    <h4 class="fw-800 text-dark mb-1">Open Sourcing Investigation</h4>
                    <p class="text-muted small mb-0">Our analysts are ready to address your operational challenges.</p>
                </div>
            </div>
        </div>

        <div class="ticket-p-form">
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger border-0 rounded-4 px-4 py-3 mb-5 shadow-sm small fw-bold">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $errors[0]; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-5">
                    <span class="meta-label-premium">Inquiry Subject</span>
                    <input type="text" name="subject" class="form-control form-control-premium" 
                           placeholder="Describe the nature of your request concisely"
                           value="<?php echo htmlspecialchars($_POST['subject'] ?? ''); ?>" required>
                    <div class="x-small text-muted mt-2 fw-medium italic">Example: Discrepancy in Wallet top-up #TXN-90210</div>
                </div>

                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <span class="meta-label-premium">Functional Category</span>
                        <select name="category" class="form-select form-control-premium" required>
                            <option value="general" <?php echo ($_POST['category'] ?? '') === 'general' ? 'selected' : ''; ?>>General Inquiry</option>
                            <option value="order" <?php echo ($_POST['category'] ?? '') === 'order' ? 'selected' : ''; ?>>Global Order Support</option>
                            <option value="payment" <?php echo ($_POST['category'] ?? '') === 'payment' ? 'selected' : ''; ?>>Financial Transactions</option>
                            <option value="transfer" <?php echo ($_POST['category'] ?? '') === 'transfer' ? 'selected' : ''; ?>>Currency Exchange</option>
                            <option value="shipment" <?php echo ($_POST['category'] ?? '') === 'shipment' ? 'selected' : ''; ?>>Freight & Logistics</option>
                            <option value="procurement" <?php echo ($_POST['category'] ?? '') === 'procurement' ? 'selected' : ''; ?>>Sourcing Intelligence</option>
                            <option value="technical" <?php echo ($_POST['category'] ?? '') === 'technical' ? 'selected' : ''; ?>>Platform Identity</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <span class="meta-label-premium">Operational Priority</span>
                        <input type="hidden" name="priority" id="supportPriority" value="<?php echo $_POST['priority'] ?? 'medium'; ?>">
                        <div class="priority-tab-group">
                            <button type="button" class="priority-tab-btn <?php echo ($_POST['priority'] ?? 'medium') === 'low' ? 'active' : ''; ?>" data-priority="low">Low</button>
                            <button type="button" class="priority-tab-btn <?php echo (($_POST['priority'] ?? 'medium') === 'medium') ? 'active' : ''; ?>" data-priority="medium">Medium</button>
                            <button type="button" class="priority-tab-btn <?php echo ($_POST['priority'] ?? '') === 'high' ? 'active' : ''; ?>" data-priority="high">High</button>
                            <button type="button" class="priority-tab-btn <?php echo ($_POST['priority'] ?? '') === 'urgent' ? 'active' : ''; ?>" data-priority="urgent">Urgent</button>
                        </div>
                    </div>
                </div>

                <div class="mb-5">
                    <span class="meta-label-premium">Detailed Statement</span>
                    <textarea name="message" class="form-control form-control-premium" rows="8" required 
                              placeholder="Provide a comprehensive report including Token IDs, dates, and amounts..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    <div class="d-flex align-items-center gap-2 mt-3 text-muted x-small fw-bold">
                        <i class="fas fa-lock text-primary"></i>
                        <span>End-to-end encrypted communication session.</span>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-3 pt-5 border-top border-light">
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold py-3 shadow-lg">
                        <i class="fas fa-paper-plane me-2"></i> LOG INCIDENT
                    </button>
                    <a href="<?php echo BASE_URL; ?>/user/tickets/" class="btn btn-outline-light text-muted px-5 rounded-pill fw-bold py-3">
                        DISCARD
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.priority-tab-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.priority-tab-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.getElementById('supportPriority').value = this.dataset.priority;
    });
});
</script>

<?php
$content = ob_get_clean();
$pageTitle = 'Incident Reporting - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
