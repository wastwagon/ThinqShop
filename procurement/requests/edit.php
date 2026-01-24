<?php
/**
 * Edit Procurement Request
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get request ID
$requestId = intval($_GET['id'] ?? 0);

if ($requestId <= 0) {
    redirect('/admin/procurement/requests.php', 'Invalid request ID.', 'danger');
}

// Get request details
$stmt = $conn->prepare("
    SELECT pr.*, u.email, u.phone, up.first_name, up.last_name
    FROM procurement_requests pr
    LEFT JOIN users u ON pr.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE pr.id = ?
");
$stmt->execute([$requestId]);
$request = $stmt->fetch();

if (!$request) {
    redirect('/admin/procurement/requests.php', 'Request not found.', 'danger');
}

// Handle form submission
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $status = sanitize($_POST['status'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        
        $validStatuses = ['submitted', 'quote_provided', 'accepted', 'processing', 'delivered', 'cancelled'];
        if (!in_array($status, $validStatuses)) {
            $errors[] = 'Invalid status selected.';
        }
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Update request status
                $stmt = $conn->prepare("
                    UPDATE procurement_requests 
                    SET status = ?, updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$status, $requestId]);
                
                // Add status change note if provided
                if (!empty($notes)) {
                    // You can add a notes/activity log table here if needed
                }
                
                $conn->commit();
                redirect('/admin/procurement/requests/view.php?id=' . $requestId, 'Request updated successfully.', 'success');
            } catch (Exception $e) {
                $conn->rollBack();
                $errors[] = 'Failed to update request: ' . $e->getMessage();
            }
        }
    }
}

// Prepare content for layout
ob_start();
?>

<div class="page-title-section">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Edit Request</h1>
            <p class="text-muted mb-0">Request #<?php echo htmlspecialchars($request['request_number']); ?></p>
        </div>
        <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/view.php?id=<?php echo $requestId; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to View
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Request Information</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Request Number:</strong></label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($request['request_number']); ?>" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Status:</strong> <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="submitted" <?php echo $request['status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                                <option value="quote_provided" <?php echo $request['status'] === 'quote_provided' ? 'selected' : ''; ?>>Quote Provided</option>
                                <option value="accepted" <?php echo $request['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                                <option value="processing" <?php echo $request['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="delivered" <?php echo $request['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                                <option value="cancelled" <?php echo $request['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Description:</strong></label>
                            <textarea class="form-control" rows="3" readonly><?php echo htmlspecialchars($request['description']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Notes:</strong></label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about this request..."></textarea>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Update Request
                            </button>
                            <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/view.php?id=<?php echo $requestId; ?>" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Request Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Category:</strong><br>
                        <?php 
                        $category = $request['category'] ?? 'products_purchase';
                        echo $category === 'product_branding' ? 'Product Branding' : 'Products Purchase';
                        ?>
                    </p>
                    <p><strong>Quantity:</strong><br><?php echo $request['quantity']; ?></p>
                    <?php if ($request['budget_range']): ?>
                    <p><strong>Budget Range:</strong><br><?php echo htmlspecialchars($request['budget_range']); ?></p>
                    <?php endif; ?>
                    <p><strong>Created:</strong><br><?php echo date('F d, Y g:i A', strtotime($request['created_at'])); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Edit Procurement Request - Admin - ' . APP_NAME;

include __DIR__ . '/../../../includes/layouts/admin-layout.php';
?>

