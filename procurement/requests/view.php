<?php
/**
 * View Procurement Request Details - Admin
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$requestId = intval($_GET['id'] ?? 0);

if ($requestId <= 0) {
    redirect('/admin/procurement/requests.php', 'Invalid request ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $newStatus = sanitize($_POST['status'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        redirect('/admin/procurement/requests/view.php?id=' . $requestId, 'Invalid security token.', 'danger');
    }
    
    $validStatuses = ['submitted', 'quote_provided', 'accepted', 'payment_received', 'processing', 'delivered', 'cancelled'];
    if (!in_array($newStatus, $validStatuses)) {
        redirect('/admin/procurement/requests/view.php?id=' . $requestId, 'Invalid status.', 'danger');
    }
    
    try {
        $conn->beginTransaction();
        
        // Update request status
        $stmt = $conn->prepare("UPDATE procurement_requests SET status = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$newStatus, $requestId]);
        
        $conn->commit();
        
        if (function_exists('logAdminAction')) {
            logAdminAction($_SESSION['admin_id'], 'update_procurement_status', 'procurement_requests', $requestId, ['status' => $newStatus]);
        }
        redirect('/admin/procurement/requests/view.php?id=' . $requestId, 'Request status updated successfully.', 'success');
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Update Procurement Status Error: " . $e->getMessage());
        redirect('/admin/procurement/requests/view.php?id=' . $requestId, 'Failed to update request status.', 'danger');
    }
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

// Decode reference images - check all possible storage locations
$referenceImages = [];

// 1. Primary location: reference_images column
if (!empty($request['reference_images'])) {
    $decoded = json_decode($request['reference_images'], true);
    if (is_array($decoded)) {
        $referenceImages = array_merge($referenceImages, $decoded);
    }
}

// 2. Check specifications JSON for Product Branding files (backward compatibility)
if (!empty($request['specifications'])) {
    $specs = json_decode($request['specifications'], true);
    if (is_array($specs)) {
        // Check for branding_logo_files
        if (!empty($specs['branding_logo_files'])) {
            $logoFiles = is_array($specs['branding_logo_files']) 
                ? $specs['branding_logo_files'] 
                : json_decode($specs['branding_logo_files'], true);
            if (is_array($logoFiles)) {
                $referenceImages = array_merge($referenceImages, $logoFiles);
            }
        }
        // Check for branding_artwork_files
        if (!empty($specs['branding_artwork_files'])) {
            $artworkFiles = is_array($specs['branding_artwork_files']) 
                ? $specs['branding_artwork_files'] 
                : json_decode($specs['branding_artwork_files'], true);
            if (is_array($artworkFiles)) {
                $referenceImages = array_merge($referenceImages, $artworkFiles);
            }
        }
        // Check for logo_files (alternative key)
        if (!empty($specs['logo_files'])) {
            $logoFiles = is_array($specs['logo_files']) 
                ? $specs['logo_files'] 
                : json_decode($specs['logo_files'], true);
            if (is_array($logoFiles)) {
                $referenceImages = array_merge($referenceImages, $logoFiles);
            }
        }
        // Check for artwork_files (alternative key)
        if (!empty($specs['artwork_files'])) {
            $artworkFiles = is_array($specs['artwork_files']) 
                ? $specs['artwork_files'] 
                : json_decode($specs['artwork_files'], true);
            if (is_array($artworkFiles)) {
                $referenceImages = array_merge($referenceImages, $artworkFiles);
            }
        }
    }
}

// 3. Check procurement_request_items table for Products Purchase files
try {
    $checkTable = $conn->query("SHOW TABLES LIKE 'procurement_request_items'");
    if ($checkTable->rowCount() > 0) {
        $itemsStmt = $conn->prepare("
            SELECT reference_images 
            FROM procurement_request_items 
            WHERE request_id = ? AND reference_images IS NOT NULL
        ");
        $itemsStmt->execute([$requestId]);
        $items = $itemsStmt->fetchAll();
        foreach ($items as $item) {
            if (!empty($item['reference_images'])) {
                $itemImages = json_decode($item['reference_images'], true);
                if (is_array($itemImages)) {
                    $referenceImages = array_merge($referenceImages, $itemImages);
                }
            }
        }
    }
} catch (Exception $e) {
    // Table doesn't exist, skip
}

// Remove duplicates and re-index
$referenceImages = array_values(array_unique($referenceImages));

// Get quotes for this request
$stmt = $conn->prepare("
    SELECT pq.*, au.username as admin_username
    FROM procurement_quotes pq
    LEFT JOIN admin_users au ON pq.admin_id = au.id
    WHERE pq.request_id = ?
    ORDER BY pq.created_at DESC
");
$stmt->execute([$requestId]);
$quotes = $stmt->fetchAll();

// Decode admin files for each quote
foreach ($quotes as &$quote) {
    $quote['admin_files_decoded'] = [];
    if (!empty($quote['admin_files'])) {
        $decoded = json_decode($quote['admin_files'], true);
        if (is_array($decoded)) {
            $quote['admin_files_decoded'] = $decoded;
        }
    }
}
unset($quote); // Break reference

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Request #<?php echo htmlspecialchars($request['request_number']); ?></h1>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/procurement/requests.php" class="btn btn-outline-secondary me-2">
            <i class="fas fa-arrow-left"></i> Back to Requests
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#updateStatusModal">
            <i class="fas fa-edit"></i> Update Status
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Request Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Request Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>Request Number:</strong><br>
                        <code class="text-primary"><?php echo htmlspecialchars($request['request_number']); ?></code>
                    </div>
                    <div class="col-md-6">
                        <strong>Status:</strong><br>
                        <span class="badge bg-<?php 
                            echo $request['status'] === 'delivered' ? 'success' : 
                                ($request['status'] === 'submitted' ? 'warning' : 
                                ($request['status'] === 'cancelled' ? 'danger' : 'info')); 
                        ?> fs-6 mt-2">
                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                        </span>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Description:</strong><br>
                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($request['description'])); ?></p>
                </div>
                
                <?php if (!empty($request['specifications'])): ?>
                <div class="mb-3">
                    <strong>Specifications/Requirements:</strong><br>
                    <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($request['specifications'])); ?></p>
                </div>
                <?php endif; ?>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <strong>Quantity:</strong><br>
                        <span class="text-muted"><?php echo $request['quantity']; ?></span>
                    </div>
                    <?php if (!empty($request['budget_range'])): ?>
                    <div class="col-md-8">
                        <strong>Budget Range:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($request['budget_range']); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <strong>Created:</strong><br>
                        <small class="text-muted"><?php echo date('F d, Y h:i A', strtotime($request['created_at'])); ?></small>
                    </div>
                    <div class="col-md-6">
                        <strong>Last Updated:</strong><br>
                        <small class="text-muted"><?php echo date('F d, Y h:i A', strtotime($request['updated_at'])); ?></small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- User Submitted Files -->
        <?php if (!empty($referenceImages)): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">User Submitted Files</h5>
                <span class="badge bg-info"><?php echo count($referenceImages); ?> file(s)</span>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">Reference images uploaded by the user to help understand their requirements.</p>
                <div class="row g-3">
                    <?php foreach ($referenceImages as $index => $image): 
                        $imageUrl = ASSETS_URL . '/images/uploads/' . $image;
                        $imagePath = UPLOAD_PATH . $image;
                        $fileExists = file_exists($imagePath);
                    ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card border">
                            <div class="card-body p-2">
                                <?php if ($fileExists): ?>
                                    <a href="<?php echo $imageUrl; ?>" target="_blank" data-bs-toggle="modal" data-bs-target="#imageModal<?php echo $index; ?>">
                                        <img src="<?php echo $imageUrl; ?>" 
                                             alt="Reference Image <?php echo $index + 1; ?>" 
                                             class="img-fluid rounded" 
                                             style="width: 100%; height: 200px; object-fit: cover; cursor: pointer;">
                                    </a>
                                <?php else: ?>
                                    <div class="bg-light rounded d-flex align-items-center justify-content-center" style="height: 200px;">
                                        <div class="text-center text-muted">
                                            <i class="fas fa-image fa-3x mb-2"></i>
                                            <p class="mb-0 small">File not found</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer p-2 bg-white">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted text-truncate me-2" style="max-width: 60%;">
                                        <?php echo htmlspecialchars($image); ?>
                                    </small>
                                    <?php if ($fileExists): ?>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?php echo $imageUrl; ?>" 
                                               target="_blank" 
                                               class="btn btn-outline-primary btn-sm" 
                                               title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/download-file.php?file=<?php echo urlencode($image); ?>&request_id=<?php echo $requestId; ?>" 
                                               class="btn btn-outline-success btn-sm" 
                                               title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Modal -->
                    <?php if ($fileExists): ?>
                    <div class="modal fade" id="imageModal<?php echo $index; ?>" tabindex="-1">
                        <div class="modal-dialog modal-lg modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Reference Image <?php echo $index + 1; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-center p-0">
                                    <img src="<?php echo $imageUrl; ?>" 
                                         alt="Reference Image <?php echo $index + 1; ?>" 
                                         class="img-fluid">
                                </div>
                                <div class="modal-footer">
                                    <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/download-file.php?file=<?php echo urlencode($image); ?>&request_id=<?php echo $requestId; ?>" 
                                       class="btn btn-primary">
                                        <i class="fas fa-download me-2"></i>Download
                                    </a>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="card mb-4">
            <div class="card-body">
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-2"></i>No files were submitted with this request.
                </p>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Quotes -->
        <?php if (!empty($quotes)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Quotes</h5>
            </div>
            <div class="card-body">
                <?php foreach ($quotes as $quote): ?>
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <strong>Quote Amount:</strong> <?php echo formatCurrency($quote['quote_amount']); ?><br>
                            <small class="text-muted">
                                Created: <?php echo date('M d, Y h:i A', strtotime($quote['created_at'])); ?>
                                <?php if ($quote['admin_username']): ?>
                                    by <?php echo htmlspecialchars($quote['admin_username']); ?>
                                <?php endif; ?>
                            </small>
                        </div>
                        <span class="badge bg-<?php 
                            echo $quote['status'] === 'accepted' ? 'success' : 
                                ($quote['status'] === 'rejected' ? 'danger' : 'warning'); 
                        ?>">
                            <?php echo ucfirst($quote['status']); ?>
                        </span>
                    </div>
                    <?php if (!empty($quote['quote_details'])): ?>
                    <div class="mt-2">
                        <strong>Details:</strong><br>
                        <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($quote['quote_details'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Admin Attached Files -->
                    <?php if (!empty($quote['admin_files_decoded'])): ?>
                    <div class="mt-3">
                        <strong>Attached Files:</strong>
                        <div class="row g-2 mt-2">
                            <?php foreach ($quote['admin_files_decoded'] as $fileIdx => $adminFile): 
                                $fileUrl = ASSETS_URL . '/images/uploads/' . $adminFile;
                                $filePath = UPLOAD_PATH . $adminFile;
                                $fileExists = file_exists($filePath);
                                $isImage = in_array(strtolower(pathinfo($adminFile, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                            ?>
                            <div class="col-md-6">
                                <div class="border rounded p-2">
                                    <?php if ($fileExists && $isImage): ?>
                                        <a href="<?php echo $fileUrl; ?>" target="_blank" data-bs-toggle="modal" data-bs-target="#adminFileModal<?php echo $quote['id']; ?>_<?php echo $fileIdx; ?>">
                                            <img src="<?php echo $fileUrl; ?>" 
                                                 alt="Admin File <?php echo $fileIdx + 1; ?>" 
                                                 class="img-fluid rounded" 
                                                 style="max-height: 100px; width: auto;">
                                        </a>
                                    <?php else: ?>
                                        <i class="fas fa-file fa-2x text-muted"></i>
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <small class="text-muted d-block text-truncate" style="max-width: 100%;">
                                            <?php echo htmlspecialchars($adminFile); ?>
                                        </small>
                                        <?php if ($fileExists): ?>
                                            <div class="btn-group btn-group-sm mt-1">
                                                <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-outline-primary btn-sm">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                                <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/download-file.php?file=<?php echo urlencode($adminFile); ?>&request_id=<?php echo $requestId; ?>" class="btn btn-outline-success btn-sm">
                                                    <i class="fas fa-download"></i> Download
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Admin File Modal -->
                            <?php if ($fileExists && $isImage): ?>
                            <div class="modal fade" id="adminFileModal<?php echo $quote['id']; ?>_<?php echo $fileIdx; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Admin Attached File</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body text-center p-0">
                                            <img src="<?php echo $fileUrl; ?>" 
                                                 alt="Admin File <?php echo $fileIdx + 1; ?>" 
                                                 class="img-fluid">
                                        </div>
                                        <div class="modal-footer">
                                            <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/download-file.php?file=<?php echo urlencode($adminFile); ?>&request_id=<?php echo $requestId; ?>" 
                                               class="btn btn-primary">
                                                <i class="fas fa-download me-2"></i>Download
                                            </a>
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="col-lg-4">
        <!-- Customer Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Customer Information</h5>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong>Name:</strong><br>
                    <?php echo htmlspecialchars(trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? '')) ?: 'N/A'); ?>
                </p>
                <p class="mb-1">
                    <strong>Email:</strong><br>
                    <a href="mailto:<?php echo htmlspecialchars($request['email']); ?>">
                        <?php echo htmlspecialchars($request['email'] ?? 'N/A'); ?>
                    </a>
                </p>
                <p class="mb-0">
                    <strong>Phone:</strong><br>
                    <a href="tel:<?php echo htmlspecialchars($request['phone']); ?>">
                        <?php echo htmlspecialchars($request['phone'] ?? 'N/A'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <?php if ($request['status'] === 'submitted'): ?>
                <button type="button" class="btn btn-success w-100 mb-2" 
                        data-bs-toggle="modal" 
                        data-bs-target="#createQuoteModal">
                    <i class="fas fa-dollar-sign me-2"></i>Create Quote
                </button>
                <?php endif; ?>
                <a href="mailto:<?php echo htmlspecialchars($request['email']); ?>?subject=Regarding Request <?php echo htmlspecialchars($request['request_number']); ?>" 
                   class="btn btn-primary w-100">
                    <i class="fas fa-envelope me-2"></i>Email Customer
                </a>
            </div>
        </div>
        
        <!-- Request Summary -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Request Summary</h5>
            </div>
            <div class="card-body">
                <div class="mb-2">
                    <strong>Request #:</strong><br>
                    <code><?php echo htmlspecialchars($request['request_number']); ?></code>
                </div>
                <div class="mb-2">
                    <strong>Status:</strong><br>
                    <span class="badge bg-<?php 
                        echo $request['status'] === 'delivered' ? 'success' : 
                            ($request['status'] === 'submitted' ? 'warning' : 
                            ($request['status'] === 'cancelled' ? 'danger' : 'info')); 
                    ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                    </span>
                </div>
                <div class="mb-2">
                    <strong>Quantity:</strong><br>
                    <span class="text-muted"><?php echo $request['quantity']; ?></span>
                </div>
                <?php if (!empty($request['budget_range'])): ?>
                <div class="mb-2">
                    <strong>Budget:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($request['budget_range']); ?></span>
                </div>
                <?php endif; ?>
                <div class="mb-0">
                    <strong>Files Submitted:</strong><br>
                    <span class="text-muted"><?php echo count($referenceImages); ?> file(s)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <div class="modal-header">
                    <h5 class="modal-title">Update Request Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <input type="hidden" name="update_status" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            <option value="submitted" <?php echo $request['status'] === 'submitted' ? 'selected' : ''; ?>>Submitted</option>
                            <option value="quote_provided" <?php echo $request['status'] === 'quote_provided' ? 'selected' : ''; ?>>Quote Provided</option>
                            <option value="accepted" <?php echo $request['status'] === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                            <option value="payment_received" <?php echo $request['status'] === 'payment_received' ? 'selected' : ''; ?>>Payment Received</option>
                            <option value="processing" <?php echo $request['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                            <option value="delivered" <?php echo $request['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            <option value="cancelled" <?php echo $request['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes (Optional)</label>
                        <textarea name="notes" class="form-control" rows="3" 
                                  placeholder="Add notes about this status update..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Quote Modal -->
<?php if ($request['status'] === 'submitted'): ?>
<div class="modal fade" id="createQuoteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?php echo BASE_URL; ?>/admin/procurement/requests.php?create_quote=<?php echo $requestId; ?>" enctype="multipart/form-data">
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
<?php endif; ?>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Request #' . htmlspecialchars($request['request_number']) . ' - Admin - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/admin-layout.php';
?>

