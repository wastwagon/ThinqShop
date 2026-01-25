<?php
/**
 * Admin Ticket Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Check if tickets table exists
try {
    $conn->query("SELECT 1 FROM tickets LIMIT 1");
} catch (PDOException $e) {
    // Table doesn't exist - show error message
    $pageTitle = 'Database Migration Required - Admin - ' . APP_NAME;
    ob_start();
    ?>
    <div class="container-fluid">
        <div class="alert alert-warning">
            <h4>Database Tables Not Found</h4>
            <p>The tickets table has not been created yet. Please run the database migration first.</p>
            <p><a href="<?php echo BASE_URL; ?>/database/migrations/" class="btn btn-primary">Run Database Migration</a></p>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    include __DIR__ . '/../../includes/layouts/admin-layout.php';
    exit;
}

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$priority = $_GET['priority'] ?? 'all';
$category = $_GET['category'] ?? 'all';

// Build query
$sql = "
    SELECT t.*, 
           u.email as user_email,
           up.first_name, up.last_name,
           au.username as assigned_admin,
           (SELECT COUNT(*) FROM ticket_messages WHERE ticket_id = t.id) as message_count,
           (SELECT created_at FROM ticket_messages WHERE ticket_id = t.id ORDER BY created_at DESC LIMIT 1) as last_reply
    FROM tickets t
    LEFT JOIN users u ON t.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    LEFT JOIN admin_users au ON t.assigned_to = au.id
    WHERE 1=1
";

$params = [];

if ($status !== 'all') {
    $sql .= " AND t.status = ?";
    $params[] = $status;
}

if ($priority !== 'all') {
    $sql .= " AND t.priority = ?";
    $params[] = $priority;
}

if ($category !== 'all') {
    $sql .= " AND t.category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY t.created_at DESC";

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $tickets = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Tickets query error: " . $e->getMessage());
    $tickets = [];
}

// Get statistics
$stats = [];
try {
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'open'");
    $stats['open'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'in_progress'");
    $stats['in_progress'] = $stmt->fetch()['count'] ?? 0;
    
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tickets WHERE status = 'resolved'");
    $stats['resolved'] = $stmt->fetch()['count'] ?? 0;
} catch (PDOException $e) {
    error_log("Tickets stats error: " . $e->getMessage());
    $stats = ['open' => 0, 'in_progress' => 0, 'resolved' => 0];
}

// Prepare content for layout
ob_start();
?>
<div class="container-fluid">
    <div class="page-title-section mb-4">
        <h1 class="page-title">Ticket Management</h1>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Open Tickets</h6>
                    <h3><?php echo $stats['open']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">In Progress</h6>
                    <h3><?php echo $stats['in_progress']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Resolved</h6>
                    <h3><?php echo $stats['resolved']; ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Tickets</h6>
                    <h3><?php echo count($tickets); ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                        <option value="open" <?php echo $status === 'open' ? 'selected' : ''; ?>>Open</option>
                        <option value="in_progress" <?php echo $status === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="waiting_customer" <?php echo $status === 'waiting_customer' ? 'selected' : ''; ?>>Waiting Customer</option>
                        <option value="resolved" <?php echo $status === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                        <option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Priority</label>
                    <select name="priority" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $priority === 'all' ? 'selected' : ''; ?>>All Priority</option>
                        <option value="low" <?php echo $priority === 'low' ? 'selected' : ''; ?>>Low</option>
                        <option value="medium" <?php echo $priority === 'medium' ? 'selected' : ''; ?>>Medium</option>
                        <option value="high" <?php echo $priority === 'high' ? 'selected' : ''; ?>>High</option>
                        <option value="urgent" <?php echo $priority === 'urgent' ? 'selected' : ''; ?>>Urgent</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                        <option value="general" <?php echo $category === 'general' ? 'selected' : ''; ?>>General</option>
                        <option value="order" <?php echo $category === 'order' ? 'selected' : ''; ?>>Order</option>
                        <option value="payment" <?php echo $category === 'payment' ? 'selected' : ''; ?>>Payment</option>
                        <option value="transfer" <?php echo $category === 'transfer' ? 'selected' : ''; ?>>Transfer</option>
                        <option value="shipment" <?php echo $category === 'shipment' ? 'selected' : ''; ?>>Shipment</option>
                        <option value="procurement" <?php echo $category === 'procurement' ? 'selected' : ''; ?>>Procurement</option>
                        <option value="technical" <?php echo $category === 'technical' ? 'selected' : ''; ?>>Technical</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <a href="?" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Tickets Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($tickets)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                    <p class="text-muted">No tickets found.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ticket #</th>
                                <th>Subject</th>
                                <th>Customer</th>
                                <th>Category</th>
                                <th>Priority</th>
                                <th>Status</th>
                                <th>Assigned To</th>
                                <th>Last Reply</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tickets as $ticket): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($ticket['ticket_number'] ?? 'N/A'); ?></strong></td>
                                    <td><?php echo htmlspecialchars($ticket['subject'] ?? 'No subject'); ?></td>
                                    <td>
                                        <?php 
                                        $customerName = trim(($ticket['first_name'] ?? '') . ' ' . ($ticket['last_name'] ?? ''));
                                        echo htmlspecialchars($customerName ?: ($ticket['user_email'] ?? 'N/A')); 
                                        ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?php echo ucfirst($ticket['category'] ?? 'general'); ?></span></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            $priority = $ticket['priority'] ?? 'medium';
                                            echo $priority === 'urgent' ? 'danger' : 
                                                ($priority === 'high' ? 'warning' : 'info'); 
                                        ?>">
                                            <?php echo ucfirst($priority); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            $ticketStatus = $ticket['status'] ?? 'open';
                                            echo $ticketStatus === 'open' ? 'success' : 
                                                ($ticketStatus === 'resolved' ? 'secondary' : 'primary'); 
                                        ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $ticketStatus)); ?>
                                        </span>
                                    </td>
                                    <td><?php echo $ticket['assigned_admin'] ? htmlspecialchars($ticket['assigned_admin']) : '<span class="text-muted">Unassigned</span>'; ?></td>
                                    <td><?php echo $ticket['last_reply'] ? date('M d, Y', strtotime($ticket['last_reply'])) : 'No replies'; ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/tickets/view.php?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Ticket Management - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';
?>
