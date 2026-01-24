<?php
/**
 * View Procurement Details - Premium Design
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../includes/auth-check.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

$requestId = intval($_GET['id'] ?? 0);

if ($requestId <= 0) {
    redirect('/user/procurement/index.php', 'Invalid request ID.', 'danger');
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get request details
$stmt = $conn->prepare("SELECT * FROM procurement_requests WHERE id = ? AND user_id = ?");
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch();

if (!$request) {
    redirect('/user/procurement/index.php', 'Request not found.', 'danger');
}

// Get items
$stmt = $conn->prepare("SELECT * FROM procurement_items WHERE request_id = ?");
$stmt->execute([$requestId]);
$requestItems = $stmt->fetchAll();

// Get quotes
$stmt = $conn->prepare("SELECT * FROM procurement_quotes WHERE request_id = ? ORDER BY created_at DESC");
$stmt->execute([$requestId]);
$quotes = $stmt->fetchAll();

$category = $request['category'] ?? 'products_purchase';

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/user-procurement-view.css'
];

ob_start();
?>

<div class="mb-5">
    <a href="<?php echo BASE_URL; ?>/user/procurement/" class="btn btn-outline-light text-dark rounded-pill px-4 fw-800 x-small shadow-sm mb-4">
        <i class="fas fa-chevron-left me-2"></i> REGISTRY
    </a>
    
    <div class="proc-header-premium shadow-sm">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-4">
            <div>
                <span class="text-muted x-small fw-800 text-uppercase letter-spacing-1 mb-1 d-block">PROCUREMENT REFERENCE</span>
                <h2 class="fw-800 text-dark mb-1"><?php echo htmlspecialchars($request['request_number']); ?></h2>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted small fw-800 text-uppercase"><?php echo date('M d, Y • h:i A', strtotime($request['created_at'])); ?></span>
                    <span class="text-muted small">•</span>
                    <span class="text-muted small fw-800 text-uppercase"><?php echo $category === 'product_branding' ? 'Strategic Branding' : 'Products Sourcing'; ?></span>
                </div>
            </div>
            <div class="text-md-end">
                <?php 
                $sClass = 'bg-secondary-soft text-secondary';
                if($request['status'] === 'delivered') $sClass = 'bg-success-soft text-success';
                elseif($request['status'] === 'submitted') $sClass = 'bg-warning-soft text-warning';
                elseif($request['status'] === 'quote_provided' || $request['status'] === 'processing') $sClass = 'bg-info-soft text-info';
                ?>
                <div class="status-p-lg <?php echo $sClass; ?> mb-2">
                    <i class="fas fa-circle x-small"></i> <?php echo strtoupper(str_replace('_', ' ', $request['status'])); ?>
                </div>
                <div class="fw-800 text-muted x-small text-uppercase">LATEST UPDATE: <?php echo date('M d, Y', strtotime($request['updated_at'])); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Request Brief -->
        <div class="card-premium-view p-4 p-md-5 shadow-sm">
            <h6 class="fw-800 text-dark mb-5 text-uppercase letter-spacing-2"><i class="fas fa-info-circle me-2 text-primary opacity-50"></i>Request Brief</h6>
            
            <div class="row g-4">
                <div class="col-md-6">
                    <span class="meta-label-premium">Operational Category</span>
                    <div class="small fw-800 text-dark text-uppercase">
                        <i class="fas fa-tag me-1 opacity-30"></i>
                        <?php echo $category === 'product_branding' ? 'Branding' : 'Sourcing'; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <span class="meta-label-premium">Sourcing Deadline</span>
                    <div class="small fw-800 text-dark text-uppercase">
                        <i class="fas fa-clock me-1 opacity-30"></i>
                        <?php echo !empty($request['needed_by']) ? date('M d, Y', strtotime($request['needed_by'])) : 'ASAP / FLEXIBLE'; ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <span class="meta-label-premium">Allocation Pool</span>
                    <div class="small fw-800 text-success">
                        <?php echo is_numeric($request['budget_range']) ? formatCurrency($request['budget_range']) : strtoupper($request['budget_range'] ?? 'N/A'); ?>
                    </div>
                </div>
                <div class="col-md-6">
                    <span class="meta-label-premium">Manifest Units</span>
                    <div class="small fw-800 text-dark"><?php echo $request['quantity']; ?> UNITS</div>
                </div>
                <div class="col-12">
                    <span class="meta-label-premium">Operational Directives</span>
                    <div class="small text-muted lh-base bg-light p-3 rounded-3 border-0 fw-bold">
                        <?php echo nl2br(htmlspecialchars($request['description'])); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <?php if (!empty($requestItems)): ?>
            <div class="mb-5">
                <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-2 px-1">Manifest Line Items (<?php echo count($requestItems); ?>)</h6>
                <?php foreach ($requestItems as $item): ?>
                    <div class="item-card-premium shadow-sm">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="fw-800 text-dark mb-0 text-uppercase"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                            <span class="badge bg-dark text-white rounded-pill px-3 py-1 fw-800 x-small">x<?php echo $item['quantity']; ?></span>
                        </div>
                        
                        <?php if ($item['description']): ?>
                            <p class="text-muted x-small mb-4 fw-bold text-uppercase italic opacity-75"><?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                        <?php endif; ?>

                        <?php if ($item['specifications']): ?>
                            <div class="mb-4">
                                <span class="meta-label-premium">Technical Specifications</span>
                                <div class="spec-p-box x-small text-dark fw-bold">
                                    <?php echo nl2br(htmlspecialchars($item['specifications'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if ($item['reference_images']): ?>
                            <div>
                                <span class="meta-label-premium">Visual References</span>
                                <div class="d-flex flex-wrap gap-2 mt-2">
                                    <?php 
                                    $itemImages = json_decode($item['reference_images'], true);
                                    if (is_array($itemImages)) {
                                        foreach ($itemImages as $image) {
                                            echo '<img src="'.BASE_URL.'/assets/images/uploads/'.htmlspecialchars($image).'" class="rounded-2" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #e2e8f0;">';
                                        }
                                    } ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Active Quotations -->
        <div class="card-premium-view shadow-sm">
            <div class="p-4 border-bottom bg-white">
                <h6 class="fw-800 text-dark mb-0 text-uppercase letter-spacing-2">Negotiated Estimates</h6>
            </div>
            <div class="p-0">
                <?php if (empty($quotes)): ?>
                    <div class="text-center py-5">
                        <div class="bg-light d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 64px; height: 64px; border: 1px dashed #cbd5e1;">
                            <i class="fas fa-hourglass-half text-muted opacity-30"></i>
                        </div>
                        <p class="text-muted x-small fw-800 text-uppercase px-5">Our global sourcing experts are negotiating the best values.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th class="ps-4 border-0 py-3 x-small fw-800 text-muted">VALUATION</th>
                                    <th class="border-0 py-3 x-small fw-800 text-muted">STATUS</th>
                                    <th class="border-0 py-3 x-small fw-800 text-muted text-end pe-4">AUDIT</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quotes as $quote): ?>
                                <tr class="quote-row-premium" onclick="window.location.href='<?php echo BASE_URL; ?>/user/procurement/quotes/view.php?id=<?php echo $quote['id']; ?>'">
                                    <td class="ps-4 py-4 fw-800 text-primary"><?php echo formatCurrency($quote['quote_amount']); ?></td>
                                    <td>
                                        <span class="status-indicator-proc bg-<?php echo $quote['status'] === 'accepted' ? 'success' : ($quote['status'] === 'rejected' ? 'danger' : 'warning'); ?>-soft text-<?php echo $quote['status'] === 'accepted' ? 'success' : ($quote['status'] === 'rejected' ? 'danger' : 'warning'); ?>">
                                            <?php echo strtoupper($quote['status']); ?>
                                        </span>
                                    </td>
                                    <td class="pe-4 text-end">
                                        <i class="fas fa-chevron-right text-muted opacity-30"></i>
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

    <div class="col-lg-4">
        <!-- Milestone Tracking -->
        <div class="card-premium-view p-4 shadow-sm">
            <h6 class="fw-800 text-dark mb-4 text-uppercase letter-spacing-2">Activity Log</h6>
            <div class="milestone-list">
                <div class="milestone-item active">
                    <div class="milestone-icon"></div>
                    <div>
                        <div class="fw-800 x-small text-dark text-uppercase">Submission Logged</div>
                        <div class="text-muted x-small fw-bold"><?php echo date('M d, Y - H:i', strtotime($request['created_at'])); ?></div>
                    </div>
                </div>
                
                <?php if ($request['status'] !== 'submitted'): ?>
                <div class="milestone-item active">
                    <div class="milestone-icon"></div>
                    <div>
                        <div class="fw-800 x-small text-dark text-uppercase"><?php echo strtoupper(str_replace('_', ' ', $request['status'])); ?></div>
                        <div class="text-muted x-small fw-bold"><?php echo date('M d, Y - H:i', strtotime($request['updated_at'])); ?></div>
                    </div>
                </div>
                <?php else: ?>
                <div class="milestone-item opacity-50">
                    <div class="milestone-icon"></div>
                    <div>
                        <div class="fw-800 x-small text-dark text-uppercase">Awaiting Analysis</div>
                        <div class="text-muted x-small fw-bold">AVG CYCLE: 1-2 HOURS</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assistance Center -->
        <div class="text-center p-4 bg-primary-soft rounded-4 border-0">
            <h6 class="fw-800 text-primary mb-2 text-uppercase x-small letter-spacing-1">Dedicated Analyst</h6>
            <p class="x-small text-dark fw-bold opacity-75 mb-4 text-uppercase">Request manifest revisions or clarify branding protocols.</p>
            <a href="<?php echo BASE_URL; ?>/user/tickets/create.php?ref=<?php echo $request['request_number']; ?>" class="btn btn-primary w-100 btn-premium py-2">
                OPEN CHANNEL
            </a>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
$pageTitle = 'Procurement Sourcing - ' . APP_NAME;
include __DIR__ . '/../../includes/layouts/user-layout.php';
?>
