<?php
/**
 * Admin Shipments Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle status update
if (isset($_GET['update_status']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $shipmentId = intval($_GET['update_status']);
    $newStatus = sanitize($_POST['status'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/logistics/shipments.php', 'Invalid security token.', 'danger');
    }
    
    $validStatuses = ['booked', 'pickup_scheduled', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/logistics/shipments.php', 'Invalid status.', 'danger');
    }
    
    try {
        $conn->beginTransaction();
        
        // Update shipment status
        $stmt = $conn->prepare("UPDATE shipments SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $shipmentId]);
        
        // Add tracking entry
        $stmt = $conn->prepare("
            INSERT INTO shipment_tracking (shipment_id, status, location, notes, admin_id, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$shipmentId, $newStatus, $location, $notes, $_SESSION['admin_id']]);
        
        // Get shipment details for notification
        $stmt = $conn->prepare("SELECT user_id, tracking_number FROM shipments WHERE id = ?");
        $stmt->execute([$shipmentId]);
        $shipment = $stmt->fetch();
        
        $conn->commit();
        
        if (function_exists('logAdminAction')) {
            logAdminAction($_SESSION['admin_id'], 'update_shipment_status', 'shipments', $shipmentId, ['status' => $newStatus]);
        }
        
        // Send notification to user
        if ($shipment && file_exists(__DIR__ . '/../../includes/notification-helper.php')) {
            require_once __DIR__ . '/../../includes/notification-helper.php';
            
            $statusMessages = [
                'booked' => 'Your shipment has been booked',
                'pickup_scheduled' => 'Pickup has been scheduled for your shipment',
                'picked_up' => 'Your shipment has been picked up',
                'in_transit' => 'Your shipment is in transit',
                'out_for_delivery' => 'Your shipment is out for delivery',
                'delivered' => 'Your shipment has been delivered',
                'cancelled' => 'Your shipment has been cancelled'
            ];
            
            $statusMessage = $statusMessages[$newStatus] ?? 'Your shipment status has been updated';
            $message = $statusMessage . '. Tracking: ' . $shipment['tracking_number'];
            if (!empty($location)) {
                $message .= ' - Location: ' . $location;
            }
            if (!empty($notes)) {
                $message .= ' - ' . $notes;
            }
            
            NotificationHelper::createUserNotification(
                $shipment['user_id'],
                'shipment',
                'Shipment Status Updated',
                $message,
                BASE_URL . '/user/shipments/view.php?id=' . $shipmentId
            );
        }
        
        redirect('/admin/logistics/shipments.php', 'Shipment status updated successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update Shipment Status Error: " . $e->getMessage());
        redirect('/admin/logistics/shipments.php', 'Failed to update shipment status.', 'danger');
    }
}

// Get filter
$statusFilter = $_GET['status'] ?? 'all';
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$where = [];
$params = [];

if ($statusFilter !== 'all') {
    $where[] = "s.status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM shipments s $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalShipments = $countStmt->fetch()['total'];
$totalPages = ceil($totalShipments / $perPage);

// Get shipments
$sql = "SELECT s.*, u.email, u.phone 
        FROM shipments s
        LEFT JOIN users u ON s.user_id = u.id
        $whereClause
        ORDER BY s.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$shipments = $stmt->fetchAll();

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Shipments Management</h1>
</div>

<!-- Filters -->
<div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                        <option value="booked" <?php echo $statusFilter === 'booked' ? 'selected' : ''; ?>>Booked</option>
                        <option value="pickup_scheduled" <?php echo $statusFilter === 'pickup_scheduled' ? 'selected' : ''; ?>>Pickup Scheduled</option>
                        <option value="picked_up" <?php echo $statusFilter === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                        <option value="in_transit" <?php echo $statusFilter === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                        <option value="out_for_delivery" <?php echo $statusFilter === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Shipments Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($shipments)): ?>
                <p class="text-muted text-center py-5">No shipments found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Tracking #</th>
                                <th>Customer</th>
                                <th>Weight</th>
                                <th>Service</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($shipments as $shipment): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($shipment['tracking_number']); ?></code></td>
                                <td>
                                    <div><?php echo htmlspecialchars($shipment['email'] ?? 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($shipment['phone'] ?? ''); ?></small>
                                </td>
                                <td><?php echo $shipment['weight']; ?> kg</td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $shipment['service_type'])); ?></td>
                                <td><?php echo formatCurrency($shipment['total_price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $shipment['status'] === 'delivered' ? 'success' : 
                                            ($shipment['status'] === 'cancelled' ? 'danger' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $shipment['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($shipment['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>/admin/logistics/shipments/view.php?id=<?php echo $shipment['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#updateStatusModal<?php echo $shipment['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                    
                                    <!-- Update Status Modal -->
                                    <div class="modal fade" id="updateStatusModal<?php echo $shipment['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="?update_status=<?php echo $shipment['id']; ?>">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Update Shipment Status</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status <span class="text-danger">*</span></label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="booked" <?php echo $shipment['status'] === 'booked' ? 'selected' : ''; ?>>Booked</option>
                                                                <option value="pickup_scheduled" <?php echo $shipment['status'] === 'pickup_scheduled' ? 'selected' : ''; ?>>Pickup Scheduled</option>
                                                                <option value="picked_up" <?php echo $shipment['status'] === 'picked_up' ? 'selected' : ''; ?>>Picked Up</option>
                                                                <option value="in_transit" <?php echo $shipment['status'] === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                                                <option value="out_for_delivery" <?php echo $shipment['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                                                <option value="delivered" <?php echo $shipment['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Location (Optional)</label>
                                                            <input type="text" name="location" class="form-control" 
                                                                   placeholder="Current location">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Notes</label>
                                                            <textarea name="notes" class="form-control" rows="3"></textarea>
                                                        </div>
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Shipments pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $page - 2 && $i <= $page + 2)): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Shipments - Admin - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/admin-layout.php';

