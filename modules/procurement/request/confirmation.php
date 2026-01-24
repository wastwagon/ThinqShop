<?php
/**
 * Procurement Request Confirmation Page
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Get request ID from session
$requestId = $_SESSION['procurement_request_id'] ?? null;
$requestNumber = $_SESSION['procurement_request_number'] ?? null;

// Clear session variables
unset($_SESSION['procurement_request_id']);
unset($_SESSION['procurement_request_number']);

// If no request ID in session, try to get from URL
if (!$requestId && isset($_GET['id'])) {
    $requestId = intval($_GET['id']);
}

if (!$requestId) {
    redirect('/user/procurement/', 'Invalid request. Please submit a procurement request first.', 'danger');
}

// Get request details
$stmt = $conn->prepare("
    SELECT pr.*, u.email, u.phone, up.first_name, up.last_name
    FROM procurement_requests pr
    LEFT JOIN users u ON pr.user_id = u.id
    LEFT JOIN user_profiles up ON u.id = up.user_id
    WHERE pr.id = ? AND pr.user_id = ?
");
$stmt->execute([$requestId, $userId]);
$request = $stmt->fetch();

if (!$request) {
    redirect('/user/procurement/', 'Request not found.', 'danger');
}

// Get category
$category = $request['category'] ?? 'products_purchase';

// Get request items if Products Purchase
$requestItems = [];
if ($category === 'products_purchase') {
    try {
        $checkTable = $conn->query("SHOW TABLES LIKE 'procurement_request_items'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $conn->prepare("
                SELECT * FROM procurement_request_items 
                WHERE request_id = ? 
                ORDER BY item_order ASC
            ");
            $stmt->execute([$requestId]);
            $requestItems = $stmt->fetchAll();
        }
    } catch (Exception $e) {
        // Table doesn't exist, items stored in specifications
    }
}

// Parse specifications for display
$specifications = json_decode($request['specifications'], true) ?? [];

$pageTitle = 'Request Confirmation - ' . APP_NAME;

ob_start();
?>

<div class="container-fluid" style="padding: 1rem 0;">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <!-- Success Alert -->
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <h4 class="alert-heading mb-2"><i class="fas fa-check-circle me-2"></i>Request Submitted Successfully!</h4>
                <p class="mb-0">Your procurement request has been received. Our team will review it and contact you within 24-48 hours.</p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <!-- Request Details Card -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Request Details</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Request Number:</strong><br>
                            <code class="fs-5"><?php echo htmlspecialchars($request['request_number']); ?></code>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong><br>
                            <span class="badge bg-warning fs-6"><?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Category:</strong><br>
                            <span class="badge bg-info">
                                <?php 
                                $categoryName = $category === 'product_branding' ? 'Product Branding' : 'Products Purchase';
                                echo $categoryName;
                                ?>
                            </span>
                        </div>
                        <div class="col-md-6">
                            <strong>Submitted Date:</strong><br>
                            <?php echo date('F d, Y g:i A', strtotime($request['created_at'])); ?>
                        </div>
                    </div>
                    
                    <?php if ($request['budget_range']): ?>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <strong>Budget Range:</strong><br>
                            <?php 
                            if (is_numeric($request['budget_range'])) {
                                echo formatCurrency($request['budget_range']);
                            } else {
                                echo htmlspecialchars($request['budget_range']);
                            }
                            ?>
                        </div>
                        <?php if (!empty($request['needed_by'] ?? null)): ?>
                        <div class="col-md-6">
                            <strong>Needed By:</strong><br>
                            <?php echo date('F d, Y', strtotime($request['needed_by'])); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Request Items/Details -->
            <?php if ($category === 'products_purchase' && !empty($requestItems)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Products Requested</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($requestItems as $index => $item): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">Product #<?php echo $index + 1; ?>: <?php echo htmlspecialchars($item['item_name']); ?></h6>
                                    <?php if ($item['unit_price']): ?>
                                    <span class="badge bg-primary"><?php echo formatCurrency($item['unit_price']); ?> each</span>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($item['description']): ?>
                                <p class="mb-2"><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($item['description'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if ($item['specifications']): ?>
                                <p class="mb-2"><strong>Specifications:</strong> <?php echo nl2br(htmlspecialchars($item['specifications'])); ?></p>
                                <?php endif; ?>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Quantity:</strong> <?php echo $item['quantity']; ?>
                                    </div>
                                    <?php if ($item['unit_price']): ?>
                                    <div class="col-md-6">
                                        <strong>Subtotal:</strong> <?php echo formatCurrency($item['unit_price'] * $item['quantity']); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($item['reference_images']): ?>
                                <div class="mt-3">
                                    <strong>Images:</strong><br>
                                    <div class="mt-2">
                                        <?php 
                                        $itemImages = json_decode($item['reference_images'], true);
                                        if (is_array($itemImages) && !empty($itemImages)) {
                                            foreach ($itemImages as $image) {
                                                echo '<a href="' . BASE_URL . '/assets/images/uploads/' . htmlspecialchars($image) . '" target="_blank" class="me-2 mb-2 d-inline-block">';
                                                echo '<img src="' . BASE_URL . '/assets/images/uploads/' . htmlspecialchars($image) . '" alt="Product Image" class="img-thumbnail" style="max-width: 100px; max-height: 100px;">';
                                                echo '</a>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($category === 'product_branding'): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Branding Details</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $brandingData = [];
                        if (!empty($request['branding_type'])) {
                            $brandingData = [
                                'type' => $request['branding_type'],
                                'quantity' => $request['branding_quantity'] ?? null,
                                'material' => $request['branding_material'] ?? null,
                                'size' => $request['branding_size'] ?? null,
                                'color_scheme' => $request['branding_color_scheme'] ?? null,
                                'logo_file' => $request['branding_logo_file'] ?? null,
                                'artwork_files' => $request['branding_artwork_files'] ?? null,
                                'notes' => $request['branding_notes'] ?? null
                            ];
                        } else {
                            // Try to parse from specifications JSON
                            $brandingData = is_array($specifications) ? $specifications : [];
                        }
                        ?>
                        
                        <?php if (!empty($brandingData)): ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Branding Type:</strong><br>
                                <?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $brandingData['type'] ?? 'N/A'))); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Quantity:</strong><br>
                                <?php echo $brandingData['quantity'] ?? 'N/A'; ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($brandingData['material'])): ?>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong>Material:</strong><br>
                                <?php echo htmlspecialchars($brandingData['material']); ?>
                            </div>
                            <?php if (!empty($brandingData['size'])): ?>
                            <div class="col-md-6">
                                <strong>Size/Dimensions:</strong><br>
                                <?php echo htmlspecialchars($brandingData['size']); ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($brandingData['color_scheme'])): ?>
                        <div class="mb-3">
                            <strong>Color Scheme:</strong><br>
                            <?php echo htmlspecialchars($brandingData['color_scheme']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $logoFiles = [];
                        if (!empty($brandingData['logo_file'])) {
                            if (is_string($brandingData['logo_file']) && (strpos($brandingData['logo_file'], '[') === 0)) {
                                $logoFiles = json_decode($brandingData['logo_file'], true);
                            } else {
                                $logoFiles = [$brandingData['logo_file']];
                            }
                        } elseif (!empty($brandingData['logo_files'])) {
                            $logoFiles = is_array($brandingData['logo_files']) ? $brandingData['logo_files'] : json_decode($brandingData['logo_files'], true);
                        } elseif (!empty($brandingData['branding_logo_files'])) {
                            $logoFiles = is_array($brandingData['branding_logo_files']) ? $brandingData['branding_logo_files'] : json_decode($brandingData['branding_logo_files'], true);
                        }
                        
                        if (!empty($logoFiles) && is_array($logoFiles)):
                        ?>
                        <div class="mb-3">
                            <strong>Logo Files:</strong><br>
                            <div class="mt-2">
                                <div class="row g-2">
                                    <?php foreach ($logoFiles as $file): 
                                        $fileUrl = BASE_URL . '/assets/images/uploads/' . $file;
                                        $isImage = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    ?>
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <div class="card">
                                            <?php if ($isImage): ?>
                                            <a href="<?php echo $fileUrl; ?>" target="_blank" class="text-decoration-none">
                                                <img src="<?php echo $fileUrl; ?>" class="card-img-top" alt="Logo" style="height: 150px; object-fit: contain; background: #f8f9fa;">
                                            </a>
                                            <?php endif; ?>
                                            <div class="card-body p-2">
                                                <small class="text-muted d-block mb-1" style="font-size: 0.75rem; word-break: break-all;"><?php echo htmlspecialchars($file); ?></small>
                                                <div class="btn-group btn-group-sm w-100">
                                                    <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo $fileUrl; ?>" download class="btn btn-outline-success btn-sm" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php 
                        $artworkFiles = [];
                        if (!empty($brandingData['artwork_files'])) {
                            if (is_string($brandingData['artwork_files'])) {
                                $artworkFiles = json_decode($brandingData['artwork_files'], true);
                            } else {
                                $artworkFiles = $brandingData['artwork_files'];
                            }
                        } elseif (!empty($brandingData['branding_artwork_files'])) {
                            $artworkFiles = is_array($brandingData['branding_artwork_files']) ? $brandingData['branding_artwork_files'] : json_decode($brandingData['branding_artwork_files'], true);
                        }
                        
                        if (!empty($artworkFiles) && is_array($artworkFiles)):
                        ?>
                        <div class="mb-3">
                            <strong>Artwork Files:</strong><br>
                            <div class="mt-2">
                                <div class="row g-2">
                                    <?php foreach ($artworkFiles as $file): 
                                        $fileUrl = BASE_URL . '/assets/images/uploads/' . $file;
                                        $isImage = in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                                    ?>
                                    <div class="col-md-3 col-sm-4 col-6">
                                        <div class="card">
                                            <?php if ($isImage): ?>
                                            <a href="<?php echo $fileUrl; ?>" target="_blank" class="text-decoration-none">
                                                <img src="<?php echo $fileUrl; ?>" class="card-img-top" alt="Artwork" style="height: 150px; object-fit: contain; background: #f8f9fa;">
                                            </a>
                                            <?php endif; ?>
                                            <div class="card-body p-2">
                                                <small class="text-muted d-block mb-1" style="font-size: 0.75rem; word-break: break-all;"><?php echo htmlspecialchars($file); ?></small>
                                                <div class="btn-group btn-group-sm w-100">
                                                    <a href="<?php echo $fileUrl; ?>" target="_blank" class="btn btn-outline-primary btn-sm" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo $fileUrl; ?>" download class="btn btn-outline-success btn-sm" title="Download">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($brandingData['notes'])): ?>
                        <div class="mb-3">
                            <strong>Additional Notes:</strong><br>
                            <p class="mt-2"><?php echo nl2br(htmlspecialchars($brandingData['notes'])); ?></p>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- What Happens Next -->
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>What Happens Next?</h5>
                </div>
                <div class="card-body">
                    <ol class="mb-0">
                        <li class="mb-2"><strong>Review:</strong> Our team will review your request within 24-48 hours.</li>
                        <li class="mb-2"><strong>Contact:</strong> We'll contact you via phone, WhatsApp, or email to discuss details and answer any questions.</li>
                        <li class="mb-2"><strong>Quote:</strong> We'll provide you with a detailed quote based on your requirements.</li>
                        <li class="mb-2"><strong>Payment:</strong> Once you accept the quote, you can make payment through your preferred method.</li>
                        <li><strong>Fulfillment:</strong> We'll process and fulfill your order, keeping you updated throughout the process.</li>
                    </ol>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex gap-3 justify-content-center">
                <a href="<?php echo BASE_URL; ?>/user/procurement/" class="btn btn-primary btn-lg">
                    <i class="fas fa-list me-2"></i>View My Requests
                </a>
                <a href="<?php echo BASE_URL; ?>/modules/procurement/request/" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>Submit Another Request
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/../../../includes/layouts/user-layout.php';
?>

