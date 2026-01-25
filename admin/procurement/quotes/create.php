<?php
/**
 * Create Procurement Quote
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get request ID
$requestId = intval($_GET['request_id'] ?? 0);

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
        $quoteAmount = floatval($_POST['quote_amount'] ?? 0);
        $quoteDetails = sanitize($_POST['quote_details'] ?? '');
        $validUntil = !empty($_POST['valid_until']) ? $_POST['valid_until'] : null;
        
        if ($quoteAmount <= 0) {
            $errors[] = 'Quote amount must be greater than 0.';
        }
        
        // Handle admin file uploads (images, PDFs, documents)
        $adminFiles = [];
        
        // Log file upload attempt for debugging
        if (!empty($_FILES)) {
            error_log("Create Quote - Files received: " . print_r(array_keys($_FILES), true));
            error_log("Create Quote - admin_files structure: " . print_r($_FILES['admin_files'] ?? 'NOT SET', true));
        }
        
        // Check if files were uploaded - handle both single and multiple file scenarios
        if (isset($_FILES['admin_files']) && !empty($_FILES['admin_files'])) {
            error_log("Create Quote - Processing admin_files upload");
            
            // Handle multiple files - check if name is an array
            if (isset($_FILES['admin_files']['name'])) {
                // Check if it's an array (multiple files)
                if (is_array($_FILES['admin_files']['name'])) {
                    $fileCount = count(array_filter($_FILES['admin_files']['name']));
                    error_log("Create Quote - Multiple files detected: " . $fileCount);
                    
                    foreach ($_FILES['admin_files']['tmp_name'] as $key => $tmpName) {
                        // Skip if no file was uploaded for this index
                        if (empty($tmpName) || empty($_FILES['admin_files']['name'][$key])) {
                            continue;
                        }
                        
                        // Check for upload errors
                        if ($_FILES['admin_files']['error'][$key] !== UPLOAD_ERR_OK) {
                            if ($_FILES['admin_files']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                                error_log("Create Quote - File upload error for index {$key}: " . $_FILES['admin_files']['error'][$key]);
                            }
                            continue;
                        }
                        
                        $fileName = $_FILES['admin_files']['name'][$key];
                        $fileSize = $_FILES['admin_files']['size'][$key];
                        $fileTmp = $tmpName;
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        error_log("Create Quote - Processing file: {$fileName} (size: {$fileSize}, ext: {$fileExt})");
                        
                        // Allowed file types
                        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
                        
                        if (!in_array($fileExt, $allowedTypes)) {
                            error_log("Create Quote - Invalid file type: {$fileExt} for {$fileName}");
                            $errors[] = "Invalid file type for {$fileName}. Allowed: " . implode(', ', $allowedTypes);
                            continue;
                        }
                        
                        // Check file size (10MB max)
                        if ($fileSize > 10485760) {
                            error_log("Create Quote - File too large: {$fileSize} bytes for {$fileName}");
                            $errors[] = "File {$fileName} is too large. Maximum size is 10MB.";
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
                            error_log("Create Quote - Successfully uploaded file: {$newFileName}");
                        } else {
                            error_log("Create Quote - Failed to move file: {$fileName} to {$destination}");
                            $errors[] = "Failed to upload file: {$fileName}";
                        }
                    }
                } else {
                    // Single file (shouldn't happen with multiple attribute, but handle it)
                    error_log("Create Quote - Single file detected");
                    if (!empty($_FILES['admin_files']['tmp_name']) && $_FILES['admin_files']['error'] === UPLOAD_ERR_OK) {
                        $fileName = $_FILES['admin_files']['name'];
                        $fileSize = $_FILES['admin_files']['size'];
                        $fileTmp = $_FILES['admin_files']['tmp_name'];
                        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        
                        // Allowed file types
                        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
                        
                        if (in_array($fileExt, $allowedTypes) && $fileSize <= 10485760) {
                            $newFileName = uniqid('admin_', true) . '.' . $fileExt;
                            $destination = rtrim(UPLOAD_PATH, '/') . '/' . $newFileName;
                            
                            if (!is_dir(UPLOAD_PATH)) {
                                mkdir(UPLOAD_PATH, 0755, true);
                            }
                            
                            if (move_uploaded_file($fileTmp, $destination)) {
                                $adminFiles[] = $newFileName;
                                error_log("Create Quote - Successfully uploaded single file: {$newFileName}");
                            }
                        }
                    }
                }
            }
        } else {
            error_log("Create Quote - No admin_files in \$_FILES");
        }
        
        error_log("Create Quote - Total files uploaded: " . count($adminFiles));
        
        if (empty($errors)) {
            try {
                $conn->beginTransaction();
                
                // Create quote with admin files
                $adminFilesJson = !empty($adminFiles) ? json_encode($adminFiles) : null;
                $stmt = $conn->prepare("
                    INSERT INTO procurement_quotes (
                        request_id, admin_id, quote_amount, quote_details, 
                        admin_files, valid_until, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");
                $stmt->execute([
                    $requestId, 
                    $_SESSION['admin_id'], 
                    $quoteAmount, 
                    $quoteDetails,
                    $adminFilesJson,
                    $validUntil
                ]);
                $quoteId = $conn->lastInsertId();
                
                // Update request status to quote_provided if it's still submitted
                if ($request['status'] === 'submitted') {
                    $stmt = $conn->prepare("UPDATE procurement_requests SET status = 'quote_provided' WHERE id = ?");
                    $stmt->execute([$requestId]);
                }
                
                $conn->commit();
                
                logAdminAction($_SESSION['admin_id'], 'create_procurement_quote', 'procurement_quotes', $quoteId);
                
                // Send notification to user
                if (file_exists(__DIR__ . '/../../../includes/notification-helper.php')) {
                    require_once __DIR__ . '/../../../includes/notification-helper.php';
                    
                    NotificationHelper::createUserNotification(
                        $request['user_id'],
                        'ticket',
                        'Procurement Quote Received',
                        'A quote has been provided for your procurement request #' . $request['request_number'] . '. Amount: ' . formatCurrency($quoteAmount),
                        BASE_URL . '/user/procurement/quotes/view.php?request_id=' . $requestId
                    );
                }
                
                redirect('/admin/procurement/requests/view.php?id=' . $requestId, 'Quote created successfully. User can now review and accept it.', 'success');
                
            } catch (Exception $e) {
                $conn->rollBack();
                error_log("Create Quote Error: " . $e->getMessage());
                $errors[] = 'Failed to create quote: ' . $e->getMessage();
            }
        }
    }
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
        error_log("Error fetching request items: " . $e->getMessage());
    }
}

// Prepare content for layout
ob_start();
?>

<div class="page-title-section">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Create Quote</h1>
            <p class="text-muted mb-0">Request #<?php echo htmlspecialchars($request['request_number']); ?></p>
        </div>
        <a href="<?php echo BASE_URL; ?>/admin/procurement/requests/view.php?id=<?php echo $requestId; ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Request
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
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Quote Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Quote Amount (GHS)</strong> <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">₵</span>
                            <input type="number" step="0.01" name="quote_amount" class="form-control" 
                                   required min="0.01" placeholder="0.00"
                                   value="<?php echo htmlspecialchars($_POST['quote_amount'] ?? ''); ?>">
                        </div>
                        <small class="form-text text-muted">Enter the total quote amount for this procurement request.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Quote Details / Feedback</strong> <span class="text-danger">*</span></label>
                        <textarea name="quote_details" class="form-control" rows="8" required 
                                  placeholder="Provide detailed quote information, item breakdown, estimated delivery time, terms and conditions, etc. This will be visible to the user for review."><?php echo htmlspecialchars($_POST['quote_details'] ?? ''); ?></textarea>
                        <small class="form-text text-muted">This feedback will be sent to the user for review and acceptance.</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Valid Until (Optional)</strong></label>
                        <input type="date" name="valid_until" class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['valid_until'] ?? ''); ?>"
                               min="<?php echo date('Y-m-d'); ?>">
                        <small class="form-text text-muted">Set an expiration date for this quote (optional).</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><strong>Attach Files (Optional)</strong></label>
                        <input type="file" id="admin_files_input" name="admin_files[]" class="form-control" 
                               accept="image/*,.pdf,.doc,.docx" multiple>
                        <div id="selected_files_list" class="mt-2"></div>
                        <small class="form-text text-muted">You can select multiple files at once, or click "Choose Files" multiple times to add more files. Maximum file size: 10MB per file.</small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Create Quote
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
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Request Summary</h5>
            </div>
            <div class="card-body">
                <p><strong>Request Number:</strong><br>
                    <code><?php echo htmlspecialchars($request['request_number']); ?></code>
                </p>
                <p><strong>Category:</strong><br>
                    <?php 
                    $categoryName = $category === 'product_branding' ? 'Product Branding' : 'Products Purchase';
                    echo $categoryName;
                    ?>
                </p>
                <p><strong>Quantity:</strong><br><?php echo $request['quantity']; ?></p>
                <?php if ($request['budget_range']): ?>
                <p><strong>Budget Range:</strong><br>
                    <?php 
                    if (is_numeric($request['budget_range'])) {
                        echo formatCurrency($request['budget_range']);
                    } else {
                        echo htmlspecialchars($request['budget_range']);
                    }
                    ?>
                </p>
                <?php endif; ?>
                <p><strong>Customer:</strong><br>
                    <?php 
                    $userName = trim(($request['first_name'] ?? '') . ' ' . ($request['last_name'] ?? ''));
                    if (empty($userName)) {
                        $userName = explode('@', $request['email'])[0];
                    }
                    echo htmlspecialchars($userName);
                    ?>
                </p>
            </div>
        </div>
        
        <?php if (!empty($requestItems)): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Request Items</h5>
            </div>
            <div class="card-body">
                <?php foreach ($requestItems as $index => $item): ?>
                <div class="mb-3 <?php echo $index < count($requestItems) - 1 ? 'border-bottom pb-3' : ''; ?>">
                    <strong><?php echo htmlspecialchars($item['item_name']); ?></strong><br>
                    <small class="text-muted">
                        Quantity: <?php echo $item['quantity']; ?>
                        <?php if ($item['unit_price']): ?>
                        | Unit Price: <?php echo formatCurrency($item['unit_price']); ?>
                        <?php endif; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Manage multiple file selections - accumulate files across multiple selections
let selectedFiles = [];

document.getElementById('admin_files_input').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    
    // Add new files to the array (avoid duplicates)
    files.forEach(file => {
        // Check if file already exists (by name and size)
        const exists = selectedFiles.some(f => f.name === file.name && f.size === file.size);
        if (!exists) {
            selectedFiles.push(file);
        }
    });
    
    // Update the display
    updateFilesList();
    
    // Clear the input so user can select more files
    e.target.value = '';
});

function updateFilesList() {
    const listContainer = document.getElementById('selected_files_list');
    listContainer.innerHTML = '';
    
    if (selectedFiles.length === 0) {
        return;
    }
    
    const listGroup = document.createElement('div');
    listGroup.className = 'list-group';
    
    selectedFiles.forEach((file, index) => {
        const listItem = document.createElement('div');
        listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
        listItem.innerHTML = `
            <div>
                <i class="fas fa-file me-2"></i>
                <span>${file.name}</span>
                <small class="text-muted ms-2">(${(file.size / 1024).toFixed(2)} KB)</small>
            </div>
            <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile(${index})">
                <i class="fas fa-times"></i>
            </button>
        `;
        listGroup.appendChild(listItem);
    });
    
    listContainer.appendChild(listGroup);
}

function removeFile(index) {
    selectedFiles.splice(index, 1);
    updateFilesList();
    updateFileInput();
}

// Update the actual file input before form submission
function updateFileInput() {
    // Create a DataTransfer object to hold all files
    const dataTransfer = new DataTransfer();
    selectedFiles.forEach(file => {
        dataTransfer.items.add(file);
    });
    
    // Create a new file input with all accumulated files
    const fileInput = document.getElementById('admin_files_input');
    fileInput.files = dataTransfer.files;
}

// Update file input before form submission
document.querySelector('form').addEventListener('submit', function(e) {
    updateFileInput();
    console.log('Form submitting with', selectedFiles.length, 'files');
});
</script>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Create Quote - Admin - ' . APP_NAME;

include __DIR__ . '/../../../includes/layouts/admin-layout.php';
?>
