<?php
/**
 * Admin Procurement Requests Management
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Handle quote creation
if (isset($_GET['create_quote']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $requestId = intval($_GET['create_quote']);
    $quoteAmount = floatval($_POST['quote_amount'] ?? 0);
    $quoteDetails = sanitize($_POST['quote_details'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/procurement/requests.php', 'Invalid security token.', 'danger');
    }
    
    if ($quoteAmount <= 0) {
        redirect('/admin/procurement/requests.php', 'Quote amount must be greater than 0.', 'danger');
    }
    
    // Handle admin file uploads (images, PDFs, documents)
    $adminFiles = [];
    if (!empty($_FILES['admin_files']['name'][0])) {
        foreach ($_FILES['admin_files']['tmp_name'] as $key => $tmpName) {
            if (!empty($tmpName) && $_FILES['admin_files']['error'][$key] === UPLOAD_ERR_OK) {
                $fileName = $_FILES['admin_files']['name'][$key];
                $fileSize = $_FILES['admin_files']['size'][$key];
                $fileTmp = $tmpName;
                $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                
                // Allowed file types
                $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
                
                if (!in_array($fileExt, $allowedTypes)) {
                    error_log("Admin file upload failed: Invalid file type - {$fileExt}");
                    continue;
                }
                
                // Check file size (10MB max)
                if ($fileSize > 10485760) {
                    error_log("Admin file upload failed: File too large - {$fileSize} bytes");
                    continue;
                }
                
                // Generate unique filename
                $newFileName = uniqid('admin_', true) . '.' . $fileExt;
                $destination = rtrim(UPLOAD_PATH, '/') . '/' . $newFileName;
                
                // Create directory if it doesn't exist
                if (!is_dir(UPLOAD_PATH)) {
                    mkdir(UPLOAD_PATH, 0755, true);
                }
                
                // Move uploaded file
                if (move_uploaded_file($fileTmp, $destination)) {
                    $adminFiles[] = $newFileName;
                } else {
                    error_log("Admin file upload failed: Could not move file - {$fileName}");
                }
            }
        }
    }
    
    try {
        $conn->beginTransaction();
        
        // Create quote with admin files
        $adminFilesJson = !empty($adminFiles) ? json_encode($adminFiles) : null;
        $stmt = $conn->prepare("
            INSERT INTO procurement_quotes (request_id, admin_id, quote_amount, quote_details, admin_files, status, created_at)
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$requestId, $_SESSION['admin_id'], $quoteAmount, $quoteDetails, $adminFilesJson]);
        
        // Update request status
        $stmt = $conn->prepare("UPDATE procurement_requests SET status = 'quote_provided' WHERE id = ?");
        $stmt->execute([$requestId]);
        
        // Get request details for notification
        $stmt = $conn->prepare("SELECT user_id, request_number FROM procurement_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $requestData = $stmt->fetch();
        
        $conn->commit();
        
        logAdminAction($_SESSION['admin_id'], 'create_procurement_quote', 'procurement_requests', $requestId);
        
        // Send notification to user
        if ($requestData && file_exists(__DIR__ . '/../../includes/notification-helper.php')) {
            require_once __DIR__ . '/../../includes/notification-helper.php';
            
            NotificationHelper::createUserNotification(
                $requestData['user_id'],
                'ticket',
                'Procurement Quote Received',
                'A quote has been provided for your procurement request #' . $requestData['request_number'] . '. Amount: ' . formatCurrency($quoteAmount),
                BASE_URL . '/user/procurement/quotes/view.php?request_id=' . $requestId
            );
        }
        
        redirect('/admin/procurement/requests.php', 'Quote created successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Create Quote Error: " . $e->getMessage());
        redirect('/admin/procurement/requests.php', 'Failed to create quote.', 'danger');
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
    $where[] = "pr.status = ?";
    $params[] = $statusFilter;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Get total count
$countSql = "SELECT COUNT(*) as total FROM procurement_requests pr $whereClause";
$countStmt = $conn->prepare($countSql);
$countStmt->execute($params);
$totalRequests = $countStmt->fetch()['total'];
$totalPages = ceil($totalRequests / $perPage);

// Get requests
$sql = "SELECT pr.*, u.email, u.phone 
        FROM procurement_requests pr
        LEFT JOIN users u ON pr.user_id = u.id
        $whereClause
        ORDER BY pr.created_at DESC
        LIMIT $perPage OFFSET $offset";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$pageTitle = 'Procurement Requests - Admin - ' . APP_NAME;

// Use admin layout
ob_start();
?>

<div class="container-fluid">
    <h2 class="mb-4">Procurement Requests</h2>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All Requests</option>
                        <option value="submitted" <?php echo $statusFilter === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                        <option value="quote_provided" <?php echo $statusFilter === 'quote_provided' ? 'selected' : ''; ?>>Quote Provided</option>
                        <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                        <option value="processing" <?php echo $statusFilter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="delivered" <?php echo $statusFilter === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                    </select>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Requests Table -->
    <div class="card">
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <p class="text-muted text-center py-5">No requests found.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Request #</th>
                                <th>Customer</th>
                                <th>Description</th>
                                <th>Quantity</th>
                                <th>Budget</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><code><?php echo htmlspecialchars($request['request_number']); ?></code></td>
                                <td>
                                    <div><?php echo htmlspecialchars($request['email'] ?? 'N/A'); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($request['phone'] ?? ''); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars(substr($request['description'], 0, 50)); ?>
                                    <?php echo strlen($request['description']) > 50 ? '...' : ''; ?>
                                </td>
                                <td><?php echo $request['quantity']; ?></td>
                                <td><?php echo $request['budget_range'] ? htmlspecialchars($request['budget_range']) : 'N/A'; ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $request['status'] === 'delivered' ? 'success' : 
                                            ($request['status'] === 'submitted' ? 'warning' : 'info'); 
                                    ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/view.php?id=<?php echo $request['id']; ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($request['status'] === 'submitted'): ?>
                                        <button type="button" class="btn btn-outline-success" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#createQuoteModal<?php echo $request['id']; ?>">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Create Quote Modal -->
                                    <div class="modal fade" id="createQuoteModal<?php echo $request['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="?create_quote=<?php echo $request['id']; ?>" enctype="multipart/form-data">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Create Quote</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label>Request #<?php echo htmlspecialchars($request['request_number']); ?></label>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Quote Amount (GHS) <span class="text-danger">*</span></label>
                                                            <input type="number" step="0.01" name="quote_amount" class="form-control" required min="0.01">
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Quote Details</label>
                                                            <textarea name="quote_details" class="form-control" rows="4" 
                                                                      placeholder="Include item details, estimated delivery time, etc."></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Attach Files (Optional)</label>
                                                            <input type="file" name="admin_files[]" class="form-control" 
                                                                   accept="image/*,.pdf,.doc,.docx" multiple>
                                                            <small class="form-text text-muted">You can attach multiple files (images, PDFs, documents) to this quote</small>
                                                        </div>
                                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Create Quote</button>
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
                <nav aria-label="Requests pagination" class="mt-4">
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
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../layouts/admin-layout.php';
?>







