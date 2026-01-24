<?php
/**
 * Submit Procurement Request
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force no cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

$errors = [];
$success = false;
$requestNum = '';

// Check for success parameter from redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = true;
    if (isset($_GET['request'])) {
        $requestNum = $_GET['request'];
    }
}

// Add page-specific CSS
$additionalCSS = [
    BASE_URL . '/assets/css/pages/procurement-request.css'
];

// Process form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== PROCUREMENT FORM SUBMISSION START ===");
    error_log("POST data: " . print_r($_POST, true));
    error_log("FILES data: " . print_r($_FILES, true));
    
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        error_log("CSRF token validation FAILED");
        $errors[] = 'Invalid security token.';
    } else {
        error_log("CSRF token validation PASSED");
        $category = $_POST['category'] ?? 'products_purchase';
        error_log("Category: " . $category);
        $neededBy = !empty($_POST['needed_by']) ? $_POST['needed_by'] : null;
        $budgetRange = sanitize($_POST['budget_range'] ?? '');
        
        // Validate category
        if (!in_array($category, ['products_purchase', 'product_branding'])) {
            $errors[] = 'Invalid procurement category.';
        }
        
        // Handle Products Purchase category
        if ($category === 'products_purchase') {
            // Get products array
            $products = [];
            if (isset($_POST['products']) && is_array($_POST['products'])) {
                foreach ($_POST['products'] as $index => $product) {
                    if (!empty($product['item_name'])) {
                        $products[] = [
                            'item_name' => sanitize($product['item_name']),
                            'description' => sanitize($product['description'] ?? ''),
                            'specifications' => sanitize($product['specifications'] ?? ''),
                            'quantity' => intval($product['quantity'] ?? 1),
                            'unit_price' => !empty($product['unit_price']) ? floatval($product['unit_price']) : null,
                            'item_order' => $index
                        ];
                    }
                }
            }
            
            if (empty($products)) {
                $errors[] = 'Please add at least one product.';
            }
            
            // Handle product images
            $productImages = [];
            if (!empty($_FILES['product_images'])) {
                foreach ($_FILES['product_images']['name'] as $productIndex => $files) {
                    if (is_array($files)) {
                        $productImages[$productIndex] = [];
                        foreach ($files as $fileIndex => $fileName) {
                            if (!empty($fileName) && !empty($_FILES['product_images']['tmp_name'][$productIndex][$fileIndex])) {
                                $file = [
                                    'name' => $fileName,
                                    'type' => $_FILES['product_images']['type'][$productIndex][$fileIndex],
                                    'tmp_name' => $_FILES['product_images']['tmp_name'][$productIndex][$fileIndex],
                                    'error' => $_FILES['product_images']['error'][$productIndex][$fileIndex],
                                    'size' => $_FILES['product_images']['size'][$productIndex][$fileIndex]
                                ];
                                
                                $result = uploadImage($file, UPLOAD_PATH);
                                if ($result['success']) {
                                    $productImages[$productIndex][] = $result['filename'];
                                }
                            }
                        }
                    }
                }
            }
            
        } else {
            // Handle Product Branding category
            $brandingType = sanitize($_POST['branding_type'] ?? '');
            $brandingQuantity = intval($_POST['branding_quantity'] ?? 0);
            $brandingMaterial = sanitize($_POST['branding_material'] ?? '');
            $brandingSize = sanitize($_POST['branding_size'] ?? '');
            $brandingColorScheme = sanitize($_POST['branding_color_scheme'] ?? '');
            $brandingNotes = sanitize($_POST['branding_notes'] ?? '');
            
            if (empty($brandingType)) {
                $errors[] = 'Please specify the branding type.';
            }
            
            if ($brandingQuantity <= 0) {
                $errors[] = 'Branding quantity must be at least 1.';
            }
            
            // Handle branding file uploads - Logo files (multiple)
            // Note: JavaScript creates inputs with name="branding_logo[]" which PHP receives as $_FILES['branding_logo']
            $brandingLogoFiles = [];
            if (!empty($_FILES['branding_logo'])) {
                error_log("Branding logo files detected. Structure: " . print_r($_FILES['branding_logo'], true));
                
                // Handle array of files (when name="branding_logo[]")
                if (isset($_FILES['branding_logo']['name'])) {
                    // Check if it's an array (multiple files)
                    if (is_array($_FILES['branding_logo']['name'])) {
                        foreach ($_FILES['branding_logo']['tmp_name'] as $key => $tmpName) {
                            if (!empty($tmpName) && $_FILES['branding_logo']['error'][$key] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name' => $_FILES['branding_logo']['name'][$key],
                                    'type' => $_FILES['branding_logo']['type'][$key],
                                    'tmp_name' => $tmpName,
                                    'error' => $_FILES['branding_logo']['error'][$key],
                                    'size' => $_FILES['branding_logo']['size'][$key]
                                ];
                                
                                $result = uploadImage($file, UPLOAD_PATH);
                                if ($result['success']) {
                                    $brandingLogoFiles[] = $result['filename'];
                                    error_log("Successfully uploaded logo file: " . $result['filename']);
                                } else {
                                    error_log("Failed to upload logo file: " . ($result['message'] ?? 'Unknown error'));
                                }
                            }
                        }
                    } else {
                        // Single file (shouldn't happen with multiple, but handle it)
                        if (!empty($_FILES['branding_logo']['tmp_name']) && $_FILES['branding_logo']['error'] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['branding_logo']['name'],
                                'type' => $_FILES['branding_logo']['type'],
                                'tmp_name' => $_FILES['branding_logo']['tmp_name'],
                                'error' => $_FILES['branding_logo']['error'],
                                'size' => $_FILES['branding_logo']['size']
                            ];
                            
                            $result = uploadImage($file, UPLOAD_PATH);
                            if ($result['success']) {
                                $brandingLogoFiles[] = $result['filename'];
                                error_log("Successfully uploaded single logo file: " . $result['filename']);
                            }
                        }
                    }
                }
            } else {
                error_log("No branding_logo files in \$_FILES");
            }
            
            // Handle branding file uploads - Artwork files (multiple)
            // Note: JavaScript creates inputs with name="branding_artwork[]" which PHP receives as $_FILES['branding_artwork']
            $brandingArtworkFiles = [];
            if (!empty($_FILES['branding_artwork'])) {
                error_log("Branding artwork files detected. Structure: " . print_r($_FILES['branding_artwork'], true));
                
                // Handle array of files (when name="branding_artwork[]")
                if (isset($_FILES['branding_artwork']['name'])) {
                    // Check if it's an array (multiple files)
                    if (is_array($_FILES['branding_artwork']['name'])) {
                        foreach ($_FILES['branding_artwork']['tmp_name'] as $key => $tmpName) {
                            if (!empty($tmpName) && $_FILES['branding_artwork']['error'][$key] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name' => $_FILES['branding_artwork']['name'][$key],
                                    'type' => $_FILES['branding_artwork']['type'][$key],
                                    'tmp_name' => $tmpName,
                                    'error' => $_FILES['branding_artwork']['error'][$key],
                                    'size' => $_FILES['branding_artwork']['size'][$key]
                                ];
                                
                                $result = uploadImage($file, UPLOAD_PATH);
                                if ($result['success']) {
                                    $brandingArtworkFiles[] = $result['filename'];
                                    error_log("Successfully uploaded artwork file: " . $result['filename']);
                                } else {
                                    error_log("Failed to upload artwork file: " . ($result['message'] ?? 'Unknown error'));
                                }
                            }
                        }
                    } else {
                        // Single file (shouldn't happen with multiple, but handle it)
                        if (!empty($_FILES['branding_artwork']['tmp_name']) && $_FILES['branding_artwork']['error'] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $_FILES['branding_artwork']['name'],
                                'type' => $_FILES['branding_artwork']['type'],
                                'tmp_name' => $_FILES['branding_artwork']['tmp_name'],
                                'error' => $_FILES['branding_artwork']['error'],
                                'size' => $_FILES['branding_artwork']['size']
                            ];
                            
                            $result = uploadImage($file, UPLOAD_PATH);
                            if ($result['success']) {
                                $brandingArtworkFiles[] = $result['filename'];
                                error_log("Successfully uploaded single artwork file: " . $result['filename']);
                            }
                        }
                    }
                }
            } else {
                error_log("No branding_artwork files in \$_FILES");
            }
            
            // Combine all branding files into reference_images for consistent storage
            $allBrandingFiles = array_merge($brandingLogoFiles, $brandingArtworkFiles);
            error_log("Total branding files to store: " . count($allBrandingFiles) . " (Logos: " . count($brandingLogoFiles) . ", Artwork: " . count($brandingArtworkFiles) . ")");
        }
        
        error_log("Validation errors: " . (empty($errors) ? 'None' : implode(', ', $errors)));
        
        if (empty($errors)) {
            error_log("No errors, proceeding with database transaction");
            try {
                $conn->beginTransaction();
                error_log("Transaction started");
                
                // Generate request number
                $requestNumber = 'PRC-' . date('Ymd') . '-' . strtoupper(uniqid());
                
                // Prepare description based on category
                if ($category === 'products_purchase') {
                    $description = 'Products Purchase Request - ' . count($products) . ' item(s)';
                    $specifications = json_encode(['products_count' => count($products)]);
                    $quantity = array_sum(array_column($products, 'quantity'));
                } else {
                    $description = 'Product Branding Request - ' . $brandingType;
                    $specifications = json_encode([
                        'branding_type' => $brandingType,
                        'material' => $brandingMaterial,
                        'size' => $brandingSize,
                        'color_scheme' => $brandingColorScheme
                    ]);
                    $quantity = $brandingQuantity;
                }
                
                // Check which columns exist in the table
                $categoryColumn = '';
                $categoryValue = '';
                $neededByColumn = '';
                $neededByValue = '';
                
                try {
                    $checkStmt = $conn->query("SHOW COLUMNS FROM procurement_requests LIKE 'category'");
                    if ($checkStmt->rowCount() > 0) {
                        $categoryColumn = ', category';
                        $categoryValue = ', ?';
                    }
                } catch (Exception $e) {
                    // Column doesn't exist yet
                }
                
                try {
                    $checkStmt = $conn->query("SHOW COLUMNS FROM procurement_requests LIKE 'needed_by'");
                    if ($checkStmt->rowCount() > 0) {
                        $neededByColumn = ', needed_by';
                        $neededByValue = ', ?';
                    }
                } catch (Exception $e) {
                    // Column doesn't exist yet
                    error_log("needed_by column does not exist in procurement_requests table");
                }
                
                // Prepare reference_images based on category
                $referenceImagesJson = null;
                if ($category === 'products_purchase') {
                    // For products purchase, collect all product images
                    $allProductImages = [];
                    foreach ($productImages as $productImgs) {
                        if (is_array($productImgs)) {
                            $allProductImages = array_merge($allProductImages, $productImgs);
                        }
                    }
                    $referenceImagesJson = !empty($allProductImages) ? json_encode($allProductImages) : null;
                    error_log("Products Purchase - Total images to store in reference_images: " . count($allProductImages));
                } else {
                    // For product branding, use the combined branding files
                    $referenceImagesJson = !empty($allBrandingFiles) ? json_encode($allBrandingFiles) : null;
                    error_log("Product Branding - Total images to store in reference_images: " . count($allBrandingFiles));
                }
                
                // Create procurement request
                $sql = "
                    INSERT INTO procurement_requests (
                        user_id, request_number, description, specifications, quantity,
                        budget_range, reference_images{$neededByColumn}{$categoryColumn}, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?{$neededByValue}{$categoryValue}, 'submitted', NOW())
                ";
                
                $params = [
                    $userId,
                    $requestNumber,
                    $description,
                    $specifications,
                    $quantity,
                    $budgetRange ?: null,
                    $referenceImagesJson // Store all files in reference_images for consistency
                ];
                
                // Add needed_by if column exists
                if (!empty($neededByColumn)) {
                    $params[] = $neededBy;
                }
                
                // Add category if column exists
                if (!empty($categoryColumn)) {
                    $params[] = $category;
                }
                
                error_log("SQL: " . $sql);
                error_log("Params: " . print_r($params, true));
                
                $stmt = $conn->prepare($sql);
                $stmt->execute($params);
                $requestId = $conn->lastInsertId();
                
                // Handle Products Purchase - create items
                if ($category === 'products_purchase') {
                    // Check if procurement_request_items table exists
                    $checkTable = $conn->query("SHOW TABLES LIKE 'procurement_request_items'");
                    if ($checkTable->rowCount() > 0) {
                        foreach ($products as $index => $product) {
                            $imagesJson = isset($productImages[$index]) && !empty($productImages[$index]) 
                                ? json_encode($productImages[$index]) 
                                : null;
                            
                            $itemStmt = $conn->prepare("
                                INSERT INTO procurement_request_items (
                                    request_id, item_name, description, specifications, quantity,
                                    unit_price, reference_images, item_order, created_at
                                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                            ");
                            $itemStmt->execute([
                                $requestId,
                                $product['item_name'],
                                $product['description'],
                                $product['specifications'],
                                $product['quantity'],
                                $product['unit_price'],
                                $imagesJson,
                                $product['item_order']
                            ]);
                        }
                    } else {
                        // Fallback: store in main request if items table doesn't exist
                        $firstProduct = $products[0];
                        $allProductsJson = json_encode($products);
                        $stmt = $conn->prepare("
                            UPDATE procurement_requests 
                            SET description = ?, specifications = ? 
                            WHERE id = ?
                        ");
                        $stmt->execute([
                            $firstProduct['item_name'],
                            $allProductsJson,
                            $requestId
                        ]);
                    }
                } else {
                    // Handle Product Branding - update request with branding fields
                    $brandingFields = '';
                    $brandingValues = [];
                    
                        try {
                            $checkStmt = $conn->query("SHOW COLUMNS FROM procurement_requests LIKE 'branding_type'");
                            if ($checkStmt->rowCount() > 0) {
                                $logoJson = !empty($brandingLogoFiles) ? json_encode($brandingLogoFiles) : null;
                                $artworkJson = !empty($brandingArtworkFiles) ? json_encode($brandingArtworkFiles) : null;
                                
                                $updateStmt = $conn->prepare("
                                    UPDATE procurement_requests 
                                    SET branding_type = ?, branding_quantity = ?, branding_material = ?, 
                                        branding_size = ?, branding_color_scheme = ?, branding_logo_file = ?, 
                                        branding_artwork_files = ?, branding_notes = ?
                                    WHERE id = ?
                                ");
                                $updateStmt->execute([
                                    $brandingType,
                                    $brandingQuantity,
                                    $brandingMaterial,
                                    $brandingSize,
                                    $brandingColorScheme,
                                    $logoJson, // Store as JSON array for multiple files
                                    $artworkJson,
                                    $brandingNotes,
                                    $requestId
                                ]);
                            }
                        } catch (Exception $e) {
                            // Branding columns don't exist yet, store in specifications
                            $brandingData = json_encode([
                                'branding_type' => $brandingType,
                                'branding_quantity' => $brandingQuantity,
                                'branding_material' => $brandingMaterial,
                                'branding_size' => $brandingSize,
                                'branding_color_scheme' => $brandingColorScheme,
                                'branding_logo_files' => $brandingLogoFiles, // Changed to array
                                'branding_artwork_files' => $brandingArtworkFiles,
                                'branding_notes' => $brandingNotes
                            ]);
                            $stmt = $conn->prepare("UPDATE procurement_requests SET specifications = ? WHERE id = ?");
                            $stmt->execute([$brandingData, $requestId]);
                        }
                }
                
                $conn->commit();
                error_log("Transaction committed successfully. Request ID: " . $requestId . ", Request Number: " . $requestNumber);
                
                // Send notifications
                if (file_exists(__DIR__ . '/../../../includes/notification-helper.php')) {
                    require_once __DIR__ . '/../../../includes/notification-helper.php';
                    
                    // Notify user
                    NotificationHelper::createUserNotification(
                        $userId,
                        'ticket',
                        'Procurement Request Submitted',
                        'Your procurement request #' . $requestNumber . ' has been submitted successfully. We will review and provide a quote soon.',
                        BASE_URL . '/user/procurement/view.php?id=' . $requestId
                    );
                    
                    // Notify all admins
                    $user = getCurrentUser();
                    NotificationHelper::notifyAllAdmins(
                        'ticket',
                        'New Procurement Request',
                        'New procurement request #' . $requestNumber . ' from ' . ($user['email'] ?? 'Customer'),
                        BASE_URL . '/admin/procurement/requests/view.php?id=' . $requestId
                    );
                }
                
                // TODO: Send email to admin
                
                // Set flash message and redirect to confirmation page
                error_log("=== PROCUREMENT FORM SUBMISSION SUCCESS ===");
                
                // Store request ID in session for confirmation page
                $_SESSION['procurement_request_id'] = $requestId;
                $_SESSION['procurement_request_number'] = $requestNumber;
                
                // For AJAX requests, return JSON response with confirmation page URL
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'message' => 'Request submitted successfully!',
                        'request_number' => $requestNumber,
                        'redirect_url' => BASE_URL . '/modules/procurement/request/confirmation.php'
                    ]);
                    exit;
                }
                
                // For normal form submission, redirect to confirmation page
                redirect('/modules/procurement/request/confirmation.php', null, 'success');
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("=== PROCUREMENT FORM SUBMISSION ERROR ===");
                error_log("Error message: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                $errors[] = 'Failed to submit request: ' . $e->getMessage();
            }
        } else {
            error_log("Form has validation errors, submission aborted");
        }
    }
} else {
    error_log("Form not submitted via POST method. Method: " . $_SERVER['REQUEST_METHOD']);
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Submit Procurement Request</h1>
</div>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <h5><i class="fas fa-check-circle"></i> Request Submitted Successfully!</h5>
                    <p><strong>Request Number:</strong> <code><?php echo htmlspecialchars($requestNum); ?></code></p>
                    <p class="mb-2">Our team will review your request and contact you within 24-48 hours.</p>
                    <p class="mb-0">
                        <strong>What happens next:</strong>
                        <ol>
                            <li>We'll review your request</li>
                            <li>We'll contact you via phone/WhatsApp/email to discuss details</li>
                            <li>We'll provide you with a quote</li>
                            <li>Once accepted, you can make payment and we'll fulfill your order</li>
                        </ol>
                    </p>
                    <div class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/user/procurement/" class="btn btn-primary">View My Requests</a>
                    </div>
                </div>
            <?php else: ?>
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Submit Your Request</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" enctype="multipart/form-data" id="procurementForm" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <!-- Category Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold">Procurement Category <span class="text-danger">*</span></label>
                                <select name="category" id="category_select" class="form-select form-select-lg" required>
                                    <option value="">-- Select Category --</option>
                                    <option value="products_purchase" <?php echo ($_POST['category'] ?? 'products_purchase') === 'products_purchase' ? 'selected' : ''; ?>>Products Purchase - Purchase products from China</option>
                                    <option value="product_branding" <?php echo ($_POST['category'] ?? '') === 'product_branding' ? 'selected' : ''; ?>>Product Branding - Logo, packaging, labels, etc.</option>
                                </select>
                                <small class="form-text text-muted">Select the type of procurement request you want to submit</small>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Products Purchase Form -->
                            <div id="productsPurchaseForm">
                                <h5 class="mb-3">Products to Purchase</h5>
                                
                                <div id="productsContainer">
                                    <!-- Products will be added here dynamically -->
                                </div>
                                
                                <button type="button" class="btn btn-outline-primary mb-3" id="addProductBtn">
                                    <i class="fas fa-plus me-2"></i>Add Another Product
                                </button>
                            </div>
                            
                            <!-- Product Branding Form -->
                            <div id="productBrandingForm" style="display: none;">
                                <h5 class="mb-3">Branding Details</h5>
                            
                            <div class="mb-3">
                                    <label class="form-label">Branding Type <span class="text-danger">*</span></label>
                                    <select name="branding_type" class="form-select" id="brandingType">
                                        <option value="">Select branding type...</option>
                                        <option value="logo">Logo Design & Printing</option>
                                        <option value="packaging">Packaging Design & Printing</option>
                                        <option value="labels">Product Labels</option>
                                        <option value="tags">Product Tags</option>
                                        <option value="boxes">Custom Boxes</option>
                                        <option value="bags">Custom Bags</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Quantity <span class="text-danger">*</span></label>
                                        <input type="number" name="branding_quantity" class="form-control" 
                                               min="1" value="<?php echo htmlspecialchars($_POST['branding_quantity'] ?? ''); ?>" required>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Material</label>
                                        <input type="text" name="branding_material" class="form-control" 
                                               placeholder="e.g., Paper, Plastic, Fabric, etc." 
                                               value="<?php echo htmlspecialchars($_POST['branding_material'] ?? ''); ?>">
                                    </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                        <label class="form-label">Size/Dimensions</label>
                                        <input type="text" name="branding_size" class="form-control" 
                                               placeholder="e.g., 10cm x 10cm, A4, etc." 
                                               value="<?php echo htmlspecialchars($_POST['branding_size'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Color Scheme</label>
                                        <input type="text" name="branding_color_scheme" class="form-control" 
                                               placeholder="e.g., Red and White, CMYK, etc." 
                                               value="<?php echo htmlspecialchars($_POST['branding_color_scheme'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Logo Files (Optional)</label>
                                    <input type="file" id="branding_logo_input" class="form-control" accept="image/*,.pdf,.ai,.eps" multiple style="display: none;">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('branding_logo_input').click()">
                                        <i class="fas fa-upload me-2"></i>Select Logo Files
                                    </button>
                                    <small class="form-text text-muted d-block mt-1">Upload your logo files (JPG, PNG, PDF, AI, EPS) - You can select multiple files and add more later</small>
                                    <div id="logo_files_list" class="mt-3"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Artwork Files (Optional)</label>
                                    <input type="file" id="branding_artwork_input" class="form-control" accept="image/*,.pdf,.ai,.eps" multiple style="display: none;">
                                    <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('branding_artwork_input').click()">
                                        <i class="fas fa-upload me-2"></i>Select Artwork Files
                                    </button>
                                    <small class="form-text text-muted d-block mt-1">Upload artwork files (JPG, PNG, PDF, AI, EPS) - You can select multiple files and add more later</small>
                                    <div id="artwork_files_list" class="mt-3"></div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">Additional Notes/Requirements</label>
                                    <textarea name="branding_notes" class="form-control" rows="4" 
                                              placeholder="Any specific requirements, design preferences, etc."><?php echo htmlspecialchars($_POST['branding_notes'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <!-- Common Fields -->
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Budget Range (Optional)</label>
                                    <input type="text" name="budget_range" class="form-control" 
                                           placeholder="e.g., 500-1000 GHS" 
                                           value="<?php echo htmlspecialchars($_POST['budget_range'] ?? ''); ?>">
                            </div>
                            
                                <div class="col-md-6 mb-3">
                                <label class="form-label">Needed By (Optional)</label>
                                <input type="date" name="needed_by" class="form-control" 
                                       min="<?php echo date('Y-m-d'); ?>"
                                       value="<?php echo htmlspecialchars($_POST['needed_by'] ?? ''); ?>">
                            </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <strong>Note:</strong> After submitting, our team will contact you to discuss your requirements and provide a quote.
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg w-100" id="submitBtn">
                                <span id="submitBtnText">Submit Request</span>
                                <span id="submitBtnSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;" role="status" aria-hidden="true"></span>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// File management for logo and artwork files
let logoFiles = [];
let artworkFiles = [];

function addLogoFiles(input) {
    console.log('addLogoFiles called, input files:', input.files?.length || 0);
    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach(file => {
            // Check if file already exists
            const exists = logoFiles.some(f => f.name === file.name && f.size === file.size);
            if (!exists) {
                logoFiles.push(file);
                console.log('Added logo file to array:', file.name, 'Size:', file.size);
            } else {
                console.log('Logo file already in array:', file.name);
            }
        });
        console.log('Total logo files in array:', logoFiles.length);
        updateLogoFilesList();
        // Clear the input so user can select the same file again if needed
        input.value = '';
    }
}

function removeLogoFile(index) {
    logoFiles.splice(index, 1);
    updateLogoFilesList();
}

function updateLogoFilesList() {
    const listContainer = document.getElementById('logo_files_list');
    listContainer.innerHTML = '';
    
    if (logoFiles.length === 0) {
        return;
    }
    
    const listGroup = document.createElement('div');
    listGroup.className = 'list-group mb-2';
    
    logoFiles.forEach((file, index) => {
        const listItem = document.createElement('div');
        listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
        listItem.innerHTML = `
            <div>
                <i class="fas fa-file me-2"></i>
                <span>${file.name}</span>
                <small class="text-muted ms-2">(${(file.size / 1024).toFixed(2)} KB)</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeLogoFile(${index})">
                <i class="fas fa-times"></i>
            </button>
        `;
        listGroup.appendChild(listItem);
    });
    
    listContainer.appendChild(listGroup);
    
    // Add "Add More" button
    const addMoreBtn = document.createElement('button');
    addMoreBtn.type = 'button';
    addMoreBtn.className = 'btn btn-sm btn-outline-primary';
    addMoreBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add More Logo Files';
    addMoreBtn.onclick = function() {
        document.getElementById('branding_logo_input').click();
    };
    listContainer.appendChild(addMoreBtn);
}

function addArtworkFiles(input) {
    console.log('addArtworkFiles called, input files:', input.files?.length || 0);
    if (input.files && input.files.length > 0) {
        Array.from(input.files).forEach(file => {
            // Check if file already exists
            const exists = artworkFiles.some(f => f.name === file.name && f.size === file.size);
            if (!exists) {
                artworkFiles.push(file);
                console.log('Added artwork file to array:', file.name, 'Size:', file.size);
            } else {
                console.log('Artwork file already in array:', file.name);
            }
        });
        console.log('Total artwork files in array:', artworkFiles.length);
        updateArtworkFilesList();
        // Clear the input so user can select the same file again if needed
        input.value = '';
    }
}

function removeArtworkFile(index) {
    artworkFiles.splice(index, 1);
    updateArtworkFilesList();
}

function updateArtworkFilesList() {
    const listContainer = document.getElementById('artwork_files_list');
    listContainer.innerHTML = '';
    
    if (artworkFiles.length === 0) {
        return;
    }
    
    const listGroup = document.createElement('div');
    listGroup.className = 'list-group mb-2';
    
    artworkFiles.forEach((file, index) => {
        const listItem = document.createElement('div');
        listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
        listItem.innerHTML = `
            <div>
                <i class="fas fa-file me-2"></i>
                <span>${file.name}</span>
                <small class="text-muted ms-2">(${(file.size / 1024).toFixed(2)} KB)</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeArtworkFile(${index})">
                <i class="fas fa-times"></i>
            </button>
        `;
        listGroup.appendChild(listItem);
    });
    
    listContainer.appendChild(listGroup);
    
    // Add "Add More" button
    const addMoreBtn = document.createElement('button');
    addMoreBtn.type = 'button';
    addMoreBtn.className = 'btn btn-sm btn-outline-primary';
    addMoreBtn.innerHTML = '<i class="fas fa-plus me-2"></i>Add More Artwork Files';
    addMoreBtn.onclick = function() {
        document.getElementById('branding_artwork_input').click();
    };
    listContainer.appendChild(addMoreBtn);
}

// Update form submission to include all files
function prepareFileInputs() {
    console.log('prepareFileInputs called');
    console.log('Logo files count:', logoFiles.length);
    console.log('Artwork files count:', artworkFiles.length);
    
    // Remove any existing hidden file inputs
    const existingLogoInputs = document.querySelectorAll('input[name="branding_logo[]"]');
    console.log('Existing logo inputs to remove:', existingLogoInputs.length);
    existingLogoInputs.forEach(input => {
        if (input.id !== 'branding_logo_input') {
            input.remove();
        }
    });
    
    const existingArtworkInputs = document.querySelectorAll('input[name="branding_artwork[]"]');
    console.log('Existing artwork inputs to remove:', existingArtworkInputs.length);
    existingArtworkInputs.forEach(input => {
        if (input.id !== 'branding_artwork_input') {
            input.remove();
        }
    });
    
    // Create file inputs for each file using FormData approach
    // For logo files
    if (logoFiles.length > 0) {
        console.log('Creating logo file inputs...');
        try {
            const logoDataTransfer = new DataTransfer();
            logoFiles.forEach(file => {
                logoDataTransfer.items.add(file);
            });
            
            const logoInput = document.createElement('input');
            logoInput.type = 'file';
            logoInput.name = 'branding_logo[]';
            logoInput.multiple = true;
            logoInput.style.display = 'none';
            logoInput.files = logoDataTransfer.files;
            document.getElementById('procurementForm').appendChild(logoInput);
            console.log('Logo input created with', logoInput.files.length, 'files');
        } catch (e) {
            console.error('Error creating logo file input:', e);
            // Fallback: create individual inputs for each file
            logoFiles.forEach((file, index) => {
                const logoInput = document.createElement('input');
                logoInput.type = 'file';
                logoInput.name = 'branding_logo[]';
                logoInput.style.display = 'none';
                const dt = new DataTransfer();
                dt.items.add(file);
                logoInput.files = dt.files;
                document.getElementById('procurementForm').appendChild(logoInput);
            });
            console.log('Created', logoFiles.length, 'individual logo inputs');
        }
    } else {
        console.log('No logo files to add');
    }
    
    // For artwork files
    if (artworkFiles.length > 0) {
        console.log('Creating artwork file inputs...');
        try {
            const artworkDataTransfer = new DataTransfer();
            artworkFiles.forEach(file => {
                artworkDataTransfer.items.add(file);
            });
            
            const artworkInput = document.createElement('input');
            artworkInput.type = 'file';
            artworkInput.name = 'branding_artwork[]';
            artworkInput.multiple = true;
            artworkInput.style.display = 'none';
            artworkInput.files = artworkDataTransfer.files;
            document.getElementById('procurementForm').appendChild(artworkInput);
            console.log('Artwork input created with', artworkInput.files.length, 'files');
        } catch (e) {
            console.error('Error creating artwork file input:', e);
            // Fallback: create individual inputs for each file
            artworkFiles.forEach((file, index) => {
                const artworkInput = document.createElement('input');
                artworkInput.type = 'file';
                artworkInput.name = 'branding_artwork[]';
                artworkInput.style.display = 'none';
                const dt = new DataTransfer();
                dt.items.add(file);
                artworkInput.files = dt.files;
                document.getElementById('procurementForm').appendChild(artworkInput);
            });
            console.log('Created', artworkFiles.length, 'individual artwork inputs');
        }
    } else {
        console.log('No artwork files to add');
    }
    
    console.log('prepareFileInputs completed');
}

document.addEventListener('DOMContentLoaded', function() {
    console.log('=== DOM CONTENT LOADED ===');
    let productCount = 0;
    
    // Setup file input handlers
    const logoInput = document.getElementById('branding_logo_input');
    const artworkInput = document.getElementById('branding_artwork_input');
    
    if (logoInput) {
        console.log('Logo input found');
        logoInput.addEventListener('change', function() {
            addLogoFiles(this);
        });
    } else {
        console.warn('Logo input NOT found');
    }
    
    if (artworkInput) {
        console.log('Artwork input found');
        artworkInput.addEventListener('change', function() {
            addArtworkFiles(this);
        });
    } else {
        console.warn('Artwork input NOT found');
    }
    
    // Prepare files before form submission
    const form = document.getElementById('procurementForm');
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnSpinner = document.getElementById('submitBtnSpinner');
    
    console.log('Form element:', form);
    console.log('Submit button:', submitBtn);
    console.log('Submit button text:', submitBtnText);
    console.log('Submit button spinner:', submitBtnSpinner);
    
    if (form) {
        console.log('Form found, setting up submit handler');
        
        // Use capture phase to ensure our handler runs first
        form.addEventListener('submit', function(e) {
            console.log('=== FORM SUBMIT EVENT TRIGGERED ===');
            
            // Prevent double submission
            if (form.classList.contains('submitting')) {
                console.log('Form already submitting, preventing double submission');
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }
            form.classList.add('submitting');
            
            const selectedCategory = document.getElementById('category_select')?.value;
            console.log('Selected category:', selectedCategory);
            const isBranding = selectedCategory === 'product_branding';
            const hasFiles = logoFiles.length > 0 || artworkFiles.length > 0;
            console.log('Is branding:', isBranding, 'Has files:', hasFiles, 'Logo files:', logoFiles.length, 'Artwork files:', artworkFiles.length);
            
            try {
                // Manual validation (since we have novalidate attribute)
                let isValid = true;
                const validationErrors = [];
                
                // Check category selection
                const categorySelect = document.getElementById('category_select');
                if (!categorySelect || !categorySelect.value) {
                    isValid = false;
                    validationErrors.push('Please select a procurement category');
                    if (categorySelect) categorySelect.classList.add('is-invalid');
                } else {
                    if (categorySelect) categorySelect.classList.remove('is-invalid');
                }
                
                // Category-specific validation
                if (isBranding) {
                    // Branding type is required
                    const brandingType = document.getElementById('brandingType');
                    if (!brandingType || !brandingType.value) {
                        isValid = false;
                        validationErrors.push('Please select a branding type');
                        if (brandingType) brandingType.classList.add('is-invalid');
                    } else {
                        if (brandingType) brandingType.classList.remove('is-invalid');
                    }
                    
                    // Branding quantity is required
                    const brandingQuantity = document.querySelector('[name="branding_quantity"]');
                    if (!brandingQuantity || !brandingQuantity.value || parseInt(brandingQuantity.value) <= 0) {
                        isValid = false;
                        validationErrors.push('Please enter a valid quantity (at least 1)');
                        if (brandingQuantity) brandingQuantity.classList.add('is-invalid');
                    } else {
                        if (brandingQuantity) brandingQuantity.classList.remove('is-invalid');
                    }
                } else {
                    // Products Purchase validation - check if at least one product is added
                    const productItems = document.querySelectorAll('.product-item');
                    if (productItems.length === 0) {
                        isValid = false;
                        validationErrors.push('Please add at least one product');
                    } else {
                        // Check each product has required fields
                        productItems.forEach((item, index) => {
                            const itemName = item.querySelector('[name*="[item_name]"]');
                            const quantity = item.querySelector('[name*="[quantity]"]');
                            
                            if (!itemName || !itemName.value.trim()) {
                                isValid = false;
                                validationErrors.push(`Product #${index + 1}: Please enter product name`);
                                if (itemName) itemName.classList.add('is-invalid');
                            } else {
                                if (itemName) itemName.classList.remove('is-invalid');
                            }
                            
                            if (!quantity || !quantity.value || parseInt(quantity.value) <= 0) {
                                isValid = false;
                                validationErrors.push(`Product #${index + 1}: Please enter a valid quantity`);
                                if (quantity) quantity.classList.add('is-invalid');
                            } else {
                                if (quantity) quantity.classList.remove('is-invalid');
                            }
                        });
                    }
                }
                
                if (!isValid) {
                    console.log('Form validation failed. Errors:', validationErrors);
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    form.classList.remove('submitting');
                    form.classList.add('was-validated');
                    alert('Please fix the following errors:\n\n' + validationErrors.join('\n'));
                    return false;
                }
                
                console.log('Form validation passed');
                
                // For branding category with files, use XMLHttpRequest
                // Otherwise, allow normal form submission
                if (isBranding && hasFiles) {
                    console.log('Using XMLHttpRequest for branding with files');
                    // Show loading state only for XMLHttpRequest
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        if (submitBtnText) submitBtnText.textContent = 'Submitting...';
                        if (submitBtnSpinner) submitBtnSpinner.style.display = 'inline-block';
                    }
                    
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Create FormData from form
                    const formData = new FormData(form);
                    
                    // Add files to FormData
                    if (logoFiles.length > 0) {
                        logoFiles.forEach(file => formData.append('branding_logo[]', file));
                    }
                    
                    if (artworkFiles.length > 0) {
                        artworkFiles.forEach(file => formData.append('branding_artwork[]', file));
                    }
                    
                    // Use XMLHttpRequest
                    const xhr = new XMLHttpRequest();
                    const formUrl = form.action || window.location.href;
                    
                    // Add progress tracking
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            console.log('Upload progress: ' + percentComplete + '%');
                        }
                    });
                    
                    xhr.open('POST', formUrl, true);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.timeout = 120000; // 2 minutes timeout for file uploads
                    
                    xhr.ontimeout = function() {
                        alert('Request timed out. The files may be too large. Please try again.');
                        form.classList.remove('submitting');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitBtnText) submitBtnText.textContent = 'Submit Request';
                            if (submitBtnSpinner) submitBtnSpinner.style.display = 'none';
                        }
                    };
                    
                    xhr.onreadystatechange = function() {
                        console.log('XHR readyState: ' + xhr.readyState + ', status: ' + xhr.status);
                        
                        if (xhr.readyState === 4) {
                            // Always reset button state first
                            form.classList.remove('submitting');
                            if (submitBtn) {
                                submitBtn.disabled = false;
                                if (submitBtnText) submitBtnText.textContent = 'Submit Request';
                                if (submitBtnSpinner) submitBtnSpinner.style.display = 'none';
                            }
                            
                            if (xhr.status === 200) {
                                // Check if response is JSON
                                let responseData;
                                try {
                                    responseData = JSON.parse(xhr.responseText);
                                } catch (e) {
                                    // Not JSON, treat as HTML
                                    responseData = null;
                                }
                                
                                if (responseData && responseData.success) {
                                    // JSON success response
                                    console.log('Success response received:', responseData);
                                    if (responseData.redirect_url) {
                                        window.location.href = responseData.redirect_url;
                                    } else {
                                        window.location.href = '<?php echo BASE_URL; ?>/user/procurement/';
                                    }
                                } else {
                                    // HTML response - check for success indicators
                                    const responseText = xhr.responseText;
                                    console.log('Response received, length: ' + responseText.length);
                                    
                                    // Check for success indicators
                                    if (responseText.includes('Request Submitted Successfully') || 
                                        responseText.includes('fa-check-circle') ||
                                        responseText.includes('Request Number') ||
                                        responseText.includes('PRC-')) {
                                        // Success - redirect to procurement list
                                        console.log('Success detected in HTML response, redirecting...');
                                        window.location.href = '<?php echo BASE_URL; ?>/user/procurement/';
                                    } else if (responseText.includes('alert-danger') || 
                                              responseText.includes('Error') || 
                                              responseText.includes('Failed')) {
                                        // Error in response - show alert
                                        alert('An error occurred. Please check the form and try again.');
                                    } else {
                                        // Unknown response - assume success and redirect
                                        console.log('Unknown response, assuming success and redirecting');
                                        window.location.href = '<?php echo BASE_URL; ?>/user/procurement/';
                                    }
                                }
                            } else if (xhr.status === 302 || xhr.status === 301) {
                                // Handle redirect
                                const location = xhr.getResponseHeader('Location');
                                if (location) {
                                    window.location.href = location;
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                // Error occurred
                                alert('Submission failed with status: ' + xhr.status + '. Please try again.');
                                console.error('XHR Error:', xhr.status, xhr.statusText, xhr.responseText.substring(0, 500));
                            }
                        }
                    };
                    
                    xhr.onerror = function(e) {
                        console.error('XHR Network Error:', e);
                        alert('Network error. Please check your connection and try again.');
                        form.classList.remove('submitting');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            if (submitBtnText) submitBtnText.textContent = 'Submit Request';
                            if (submitBtnSpinner) submitBtnSpinner.style.display = 'none';
                        }
                    };
                    
                    xhr.onloadstart = function() {
                        console.log('XHR upload started');
                    };
                    
                    xhr.onloadend = function() {
                        console.log('XHR upload ended');
                    };
                    
                    console.log('Sending XHR request to: ' + formUrl);
                    console.log('FormData entries: ' + Array.from(formData.entries()).length);
                    xhr.send(formData);
                    return false;
                }
                
                // For other categories (Products Purchase), allow normal form submission
                // Don't prevent default - allow normal form submission
                
            } catch (error) {
                e.preventDefault();
                alert('An error occurred: ' + error.message + '\n\nPlease try again.');
                
                // Reset button state
                if (submitBtn) {
                    submitBtn.disabled = false;
                    if (submitBtnText) submitBtnText.textContent = 'Submit Request';
                    if (submitBtnSpinner) submitBtnSpinner.style.display = 'none';
                }
                return false;
            }
        });
        
        // Button click handler - ensure form submits
        if (submitBtn) {
            submitBtn.addEventListener('click', function(e) {
                console.log('Submit button clicked!');
                // Don't prevent default - let the form submit event fire naturally
                // The form submit handler will handle everything
            }, false); // Use capture phase to ensure it fires
        }
        
        // Also check if form has any other submit handlers that might be interfering
        console.log('Form event listeners check complete');
    } else {
        console.error('Form element not found!');
    }
    
    // Test: Add a simple alert to verify JavaScript is running
    console.log('=== JAVASCRIPT LOADED SUCCESSFULLY ===');
    
    // Add a simple test to verify the form exists and can be accessed
    if (form) {
        console.log('Form is accessible and ready');
        console.log('Form HTML:', form.outerHTML.substring(0, 200));
    } else {
        console.error('CRITICAL: Form element not found!');
        alert('ERROR: Form element not found. Please refresh the page.');
    }
    
    // Category toggle - using dropdown instead of radio buttons
    const categorySelect = document.getElementById('category_select');
    const productsForm = document.getElementById('productsPurchaseForm');
    const brandingForm = document.getElementById('productBrandingForm');
    
    function toggleCategoryForms() {
        const selectedValue = categorySelect.value;
        
        if (selectedValue === 'products_purchase') {
            productsForm.style.display = 'block';
            brandingForm.style.display = 'none';
            // Clear branding required fields
            brandingForm.querySelectorAll('[required]').forEach(field => {
                field.removeAttribute('required');
            });
            // Add required to first product if exists
            if (productCount === 0) {
                addProduct();
            }
        } else if (selectedValue === 'product_branding') {
            productsForm.style.display = 'none';
            brandingForm.style.display = 'block';
            // Make branding fields required
            const brandingType = document.getElementById('brandingType');
            const brandingQuantity = document.querySelector('[name="branding_quantity"]');
            if (brandingType) brandingType.setAttribute('required', 'required');
            if (brandingQuantity) brandingQuantity.setAttribute('required', 'required');
        } else {
            // No category selected
            productsForm.style.display = 'none';
            brandingForm.style.display = 'none';
        }
    }
    
    // Listen for category change
    if (categorySelect) {
        categorySelect.addEventListener('change', toggleCategoryForms);
        
        // Trigger on page load
        toggleCategoryForms();
    }
    
    // Add product function
    function addProduct(productData = null) {
        productCount++;
        const productIndex = productCount - 1;
        
        const productHtml = `
            <div class="card mb-3 product-item" data-index="${productIndex}">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Product #${productCount}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-product" ${productCount === 1 ? 'style="display:none;"' : ''}>
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" name="products[${productIndex}][item_name]" class="form-control" 
                               placeholder="e.g., iPhone 15 Pro Max" 
                               value="${productData?.item_name || ''}" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="products[${productIndex}][description]" class="form-control" rows="2" 
                                  placeholder="Describe the product">${productData?.description || ''}</textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Specifications</label>
                        <textarea name="products[${productIndex}][specifications]" class="form-control" rows="2" 
                                  placeholder="Size, color, material, brand, model, etc.">${productData?.specifications || ''}</textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Quantity <span class="text-danger">*</span></label>
                            <input type="number" name="products[${productIndex}][quantity]" class="form-control" 
                                   min="1" value="${productData?.quantity || '1'}" required>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Unit Price (Optional)</label>
                            <input type="number" step="0.01" name="products[${productIndex}][unit_price]" class="form-control" 
                                   placeholder="0.00" value="${productData?.unit_price || ''}">
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Product Images (Optional)</label>
                            <input type="file" name="product_images[${productIndex}][]" class="form-control" 
                                   accept="image/*" multiple>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const container = document.getElementById('productsContainer');
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = productHtml;
        container.appendChild(tempDiv.firstElementChild);
        
        // Update remove buttons visibility
        updateRemoveButtons();
    }
    
    // Remove product function
    function removeProduct(button) {
        const productItem = button.closest('.product-item');
        productItem.remove();
        productCount--;
        updateProductNumbers();
        updateRemoveButtons();
    }
    
    // Update product numbers
    function updateProductNumbers() {
        const items = document.querySelectorAll('.product-item');
        items.forEach((item, index) => {
            const header = item.querySelector('.card-header h6');
            header.textContent = `Product #${index + 1}`;
            // Update data-index
            item.setAttribute('data-index', index);
            // Update input names
            item.querySelectorAll('input, textarea, select').forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    const newName = name.replace(/products\[\d+\]/, `products[${index}]`);
                    input.setAttribute('name', newName);
                }
            });
        });
        productCount = items.length;
    }
    
    // Update remove buttons visibility
    function updateRemoveButtons() {
        const removeButtons = document.querySelectorAll('.remove-product');
        removeButtons.forEach(btn => {
            btn.style.display = productCount > 1 ? 'block' : 'none';
        });
    }
    
    // Add product button
    document.getElementById('addProductBtn').addEventListener('click', function() {
        addProduct();
    });
    
    // Remove product buttons (delegated event)
    document.getElementById('productsContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-product')) {
            removeProduct(e.target.closest('.remove-product'));
        }
    });
    
    // Add first product on load if products category is selected
    if (categorySelect && categorySelect.value === 'products_purchase') {
        addProduct();
    }
});
</script>


<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Procurement Request - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/user-layout.php';
?>
