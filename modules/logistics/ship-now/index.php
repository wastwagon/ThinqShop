<?php
/**
 * Ship Now - Forwarding Warehouse Shipping
 * ThinQShopping Platform
 * 
 * This page allows users to:
 * 1. View and copy forwarding warehouse address (English display, Chinese copy)
 * 2. Select shipping method (Air/Sea) with dynamic rates
 * 3. Select forwarding warehouse and destination warehouse
 * 4. Enter tracking number (manual or scan)
 * 5. Optionally declare products in shipment
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];
$user = getCurrentUser();

// Get forwarding warehouses (group by to avoid duplicates, compatible with ONLY_FULL_GROUP_BY)
$stmt = $conn->prepare("
    SELECT * FROM warehouses 
    WHERE id IN (
        SELECT MIN(id) FROM warehouses 
        WHERE warehouse_type = 'forwarding' AND is_active = 1 
        GROUP BY warehouse_name
    ) 
    ORDER BY sort_order ASC, warehouse_name ASC
");
$stmt->execute();
$forwardingWarehouses = $stmt->fetchAll();

// Get destination warehouses (group by to avoid duplicates, compatible with ONLY_FULL_GROUP_BY)
$stmt = $conn->prepare("
    SELECT * FROM warehouses 
    WHERE id IN (
        SELECT MIN(id) FROM warehouses 
        WHERE warehouse_type = 'destination' AND is_active = 1 
        GROUP BY warehouse_name
    ) 
    ORDER BY sort_order ASC, warehouse_name ASC
");
$stmt->execute();
$destinationWarehouses = $stmt->fetchAll();

// Shipping rates configuration
$shippingRates = [
    'sea' => [
        [
            'id' => 'sea_standard',
            'name' => 'Standard Sea Freight',
            'rate' => '₵245/CBM',
            'rate_value' => 245,
            'rate_type' => 'cbm',
            'duration' => '45-60 days',
            'description' => 'Standard sea freight shipping'
        ]
    ],
    'air' => [
        [
            'id' => 'air_express',
            'name' => 'Express Air (3-5 days)',
            'rate' => '₵17/kg',
            'rate_value' => 17,
            'rate_type' => 'kg',
            'duration' => '3-5 days',
            'description' => 'Express air shipping'
        ],
        [
            'id' => 'air_normal',
            'name' => 'Normal Air (7-14 days)',
            'rate' => '₵13/kg',
            'rate_value' => 13,
            'rate_type' => 'kg',
            'duration' => '7-14 days',
            'description' => 'Normal air shipping'
        ],
        [
            'id' => 'air_special',
            'name' => 'Special/Battery Goods',
            'rate' => '₵20/kg',
            'rate_value' => 20,
            'rate_type' => 'kg',
            'duration' => '7-14 days',
            'description' => 'For special items and battery goods'
        ],
        [
            'id' => 'air_phone',
            'name' => 'Phone',
            'rate' => '₵150/phone',
            'rate_value' => 150,
            'rate_type' => 'unit',
            'duration' => '7-14 days',
            'description' => 'Per phone unit'
        ],
        [
            'id' => 'air_laptop',
            'name' => 'Laptop',
            'rate' => '₵200/kg',
            'rate_value' => 200,
            'rate_type' => 'kg',
            'duration' => '7-14 days',
            'description' => 'Per kg for laptops'
        ]
    ]
];

$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ship_now'])) {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        $forwardingWarehouseId = intval($_POST['forwarding_warehouse_id'] ?? 0);
        $destinationWarehouseId = intval($_POST['destination_warehouse_id'] ?? 0);
        $shippingMethodType = sanitize($_POST['shipping_method_type'] ?? '');
        $shippingRateId = sanitize($_POST['shipping_rate_id'] ?? '');
        $trackingNumber = sanitize($_POST['tracking_number'] ?? '');
        $declareProducts = isset($_POST['declare_products']) ? 1 : 0;
        
        // Validate required fields
        if ($forwardingWarehouseId <= 0) {
            $errors[] = 'Please select a forwarding warehouse.';
        }
        if ($destinationWarehouseId <= 0) {
            $errors[] = 'Please select a destination warehouse.';
        }
        if (empty($shippingMethodType) || !in_array($shippingMethodType, ['air', 'sea'])) {
            $errors[] = 'Please select a shipping method (Air or Sea).';
        }
        if (empty($shippingRateId)) {
            $errors[] = 'Please select a shipping rate.';
        }
        if (empty($trackingNumber)) {
            $errors[] = 'Please enter a tracking number.';
        }
        
        // Validate product declaration if enabled
        $productDeclaration = null;
        if ($declareProducts) {
            $products = [];
            if (isset($_POST['product_name']) && is_array($_POST['product_name'])) {
                foreach ($_POST['product_name'] as $index => $name) {
                    if (!empty($name)) {
                        $products[] = [
                            'name' => sanitize($name),
                            'quantity' => intval($_POST['product_quantity'][$index] ?? 1),
                            'value' => floatval($_POST['product_value'][$index] ?? 0)
                        ];
                    }
                }
            }
            if (!empty($products)) {
                $productDeclaration = json_encode($products, JSON_UNESCAPED_UNICODE);
            }
        }
        
        if (empty($errors)) {
            // Store in session and redirect to process page
            $_SESSION['ship_now_data'] = [
                'forwarding_warehouse_id' => $forwardingWarehouseId,
                'destination_warehouse_id' => $destinationWarehouseId,
                'shipping_method_type' => $shippingMethodType,
                'shipping_rate_id' => $shippingRateId,
                'tracking_number' => $trackingNumber,
                'product_declaration' => $productDeclaration
            ];
            redirect('/modules/logistics/ship-now/process.php', '', '');
        }
    }
}

// Prepare content
ob_start();
?>

<div class="page-title-section">
    <h1 class="page-title">Ship Now</h1>
    <p class="text-muted">Forward your packages from China to Ghana</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <strong><i class="fas fa-exclamation-triangle me-2"></i>Error:</strong>
            <ul class="mb-0 mt-2">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>
        
        <!-- Warehouse Address Display -->
        <?php if (!empty($forwardingWarehouses)): ?>
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-warehouse me-2"></i>Forwarding Warehouse Address</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Important:</strong> Please copy the address below exactly and give it to your supplier or paste it directly in the e-commerce app without editing. Any changes may cause delivery issues.
                </div>
                
                <?php foreach ($forwardingWarehouses as $warehouse): ?>
                <div class="warehouse-address-card mb-3 p-3 border rounded" data-warehouse-id="<?php echo $warehouse['id']; ?>">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($warehouse['warehouse_name']); ?></h6>
                        <button type="button" class="btn btn-sm btn-outline-primary copy-address-btn" 
                                data-english-address="<?php echo htmlspecialchars($warehouse['address_english']); ?>"
                                data-chinese-address="<?php echo htmlspecialchars($warehouse['address_chinese']); ?>"
                                data-receiver="<?php echo htmlspecialchars($warehouse['receiver_name'] . ' (' . ($user['user_identifier'] ?? 'Not assigned') . ')'); ?>"
                                data-phone="<?php echo htmlspecialchars($warehouse['receiver_phone']); ?>">
                            <i class="fas fa-copy me-1"></i>COPY
                        </button>
                    </div>
                    
                    <div class="address-details">
                        <div class="row mb-2">
                            <div class="col-sm-3 text-muted">Warehouse Name:</div>
                            <div class="col-sm-9"><?php echo htmlspecialchars($warehouse['warehouse_name']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3 text-muted">Receiver:</div>
                            <div class="col-sm-9"><?php echo htmlspecialchars($warehouse['receiver_name']); ?> (<?php echo htmlspecialchars($user['user_identifier'] ?? 'Not assigned'); ?>)</div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3 text-muted">Address:</div>
                            <div class="col-sm-9"><?php echo htmlspecialchars($warehouse['address_english']); ?></div>
                        </div>
                        <?php if (!empty($warehouse['district'])): ?>
                        <div class="row mb-2">
                            <div class="col-sm-3 text-muted">District:</div>
                            <div class="col-sm-9"><?php echo htmlspecialchars($warehouse['district']); ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="row mb-2">
                            <div class="col-sm-3 text-muted">City:</div>
                            <div class="col-sm-9"><?php echo htmlspecialchars($warehouse['city']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3 text-muted">Country:</div>
                            <div class="col-sm-9"><?php echo htmlspecialchars($warehouse['country']); ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-sm-3 text-muted">Phone Number:</div>
                            <div class="col-sm-9"><?php echo htmlspecialchars($warehouse['receiver_phone']); ?></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Ship Now Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Ship Now</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="" id="shipNowForm" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <!-- Forwarding Warehouse Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Forwarding Warehouse <span class="text-danger">*</span></label>
                        <select name="forwarding_warehouse_id" id="forwarding_warehouse_id" class="form-select" required>
                            <option value="">-- Select Forwarding Warehouse --</option>
                            <?php foreach ($forwardingWarehouses as $warehouse): ?>
                            <option value="<?php echo $warehouse['id']; ?>">
                                <?php echo htmlspecialchars($warehouse['warehouse_name'] . ' - ' . $warehouse['city'] . ', ' . $warehouse['country']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select the warehouse where your supplier will send the package</small>
                    </div>
                    
                    <!-- Destination Warehouse Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Destination Warehouse <span class="text-danger">*</span></label>
                        <select name="destination_warehouse_id" id="destination_warehouse_id" class="form-select" required>
                            <option value="">-- Select Destination Warehouse --</option>
                            <?php foreach ($destinationWarehouses as $warehouse): ?>
                            <option value="<?php echo $warehouse['id']; ?>">
                                <?php echo htmlspecialchars($warehouse['warehouse_name'] . ' - ' . $warehouse['city'] . ', ' . $warehouse['country']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Select where you want to pick up your package</small>
                    </div>
                    
                    <!-- Shipping Method Selection -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Shipping Method <span class="text-danger">*</span></label>
                        <select name="shipping_method_type" id="shipping_method_type" class="form-select" required>
                            <option value="">-- Select Shipping Method --</option>
                            <option value="air">Air</option>
                            <option value="sea">Sea</option>
                        </select>
                        <small class="form-text text-muted">Select Air or Sea shipping method</small>
                    </div>
                    
                    <!-- Shipping Rate Selection (Dynamic) -->
                    <div class="mb-4" id="shipping_rate_section" style="display: none;">
                        <label class="form-label fw-bold">Shipping Rate <span class="text-danger">*</span></label>
                        <select name="shipping_rate_id" id="shipping_rate_id" class="form-select" required>
                            <option value="">-- Select Rate --</option>
                        </select>
                        <div id="rate_description" class="mt-2 text-muted"></div>
                    </div>
                    
                    <!-- Tracking Number -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">Tracking Number <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" name="tracking_number" id="tracking_number" class="form-control" 
                                   placeholder="Enter tracking number" required>
                            <button type="button" class="btn btn-outline-secondary" id="scan_tracking_btn" title="Scan tracking number">
                                <i class="fas fa-camera"></i>
                            </button>
                        </div>
                        <small class="form-text text-muted">Enter the tracking number from your supplier or scan it from the package</small>
                        <div id="camera_modal_container"></div>
                    </div>
                    
                    <!-- Product Declaration Toggle -->
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="declare_products" id="declare_products" value="1">
                            <label class="form-check-label fw-bold" for="declare_products">
                                Declare Products in Shipment (Optional)
                            </label>
                        </div>
                        <small class="form-text text-muted">Enable this to declare products, quantities, and values</small>
                    </div>
                    
                    <!-- Product Declaration Fields (Hidden by default) -->
                    <div id="product_declaration_section" style="display: none;">
                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h6 class="card-title">Product Declaration</h6>
                                <div id="products_list">
                                    <div class="product-item mb-3 p-3 border rounded">
                                        <div class="row">
                                            <div class="col-md-4 mb-2">
                                                <label class="form-label small">Product Name</label>
                                                <input type="text" name="product_name[]" class="form-control form-control-sm" placeholder="Product name">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label small">Quantity</label>
                                                <input type="number" name="product_quantity[]" class="form-control form-control-sm" placeholder="Qty" min="1" value="1">
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <label class="form-label small">Value (GHS)</label>
                                                <input type="number" name="product_value[]" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01" value="0">
                                            </div>
                                            <div class="col-md-2 mb-2 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-danger remove-product-btn" style="display: none;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary" id="add_product_btn">
                                    <i class="fas fa-plus me-1"></i>Add Product
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="d-grid">
                        <button type="submit" name="ship_now" class="btn btn-primary btn-lg">
                            <i class="fas fa-paper-plane me-2"></i>Ship Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// JavaScript for dynamic functionality
$inlineJS = '
// Shipping rates data
const shippingRates = ' . json_encode($shippingRates, JSON_UNESCAPED_UNICODE) . ';

// Handle shipping method change
document.getElementById("shipping_method_type").addEventListener("change", function() {
    const methodType = this.value;
        const rateSection = document.getElementById("shipping_rate_section");
        const rateSelect = document.getElementById("shipping_rate_id");
        const rateDescription = document.getElementById("rate_description");
        
        if (methodType && shippingRates[methodType]) {
            rateSection.style.display = "block";
            rateSelect.innerHTML = "<option value=\"\">-- Select Rate --</option>";
            rateSelect.required = true;
            
            shippingRates[methodType].forEach(rate => {
                const option = document.createElement("option");
                option.value = rate.id;
                option.textContent = rate.name + " - " + rate.rate + " (" + rate.duration + ")";
                option.dataset.description = rate.description;
                rateSelect.appendChild(option);
            });
            
            rateDescription.textContent = "";
        } else {
            rateSection.style.display = "none";
            rateSelect.required = false;
            rateDescription.textContent = "";
        }
    });

// Handle rate selection change
document.getElementById("shipping_rate_id").addEventListener("change", function() {
    const selectedOption = this.options[this.selectedIndex];
    const description = document.getElementById("rate_description");
    if (selectedOption.dataset.description) {
        description.textContent = selectedOption.dataset.description;
    } else {
        description.textContent = "";
    }
});

// Copy address functionality
document.querySelectorAll(".copy-address-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        const chineseAddress = this.dataset.chineseAddress;
        const receiver = this.dataset.receiver;
        const phone = this.dataset.phone;
        
        // Format address for copying (Chinese version)
        const addressText = receiver + "\\n" + chineseAddress + "\\n" + phone;
        
        // Copy to clipboard
        navigator.clipboard.writeText(addressText).then(() => {
            const originalText = this.innerHTML;
            this.innerHTML = "<i class=\"fas fa-check me-1\"></i>COPIED!";
            this.classList.remove("btn-outline-primary");
            this.classList.add("btn-success");
            
            setTimeout(() => {
                this.innerHTML = originalText;
                this.classList.remove("btn-success");
                this.classList.add("btn-outline-primary");
            }, 2000);
        }).catch(err => {
            alert("Failed to copy address. Please copy manually.");
            console.error("Copy error:", err);
        });
    });
});

// Product declaration toggle
document.getElementById("declare_products").addEventListener("change", function() {
    const section = document.getElementById("product_declaration_section");
    if (this.checked) {
        section.style.display = "block";
    } else {
        section.style.display = "none";
    }
});

// Add product button
document.getElementById("add_product_btn").addEventListener("click", function() {
    const productsList = document.getElementById("products_list");
    const newProduct = document.createElement("div");
    newProduct.className = "product-item mb-3 p-3 border rounded";
    newProduct.innerHTML = `
        <div class="row">
            <div class="col-md-4 mb-2">
                <label class="form-label small">Product Name</label>
                <input type="text" name="product_name[]" class="form-control form-control-sm" placeholder="Product name">
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label small">Quantity</label>
                <input type="number" name="product_quantity[]" class="form-control form-control-sm" placeholder="Qty" min="1" value="1">
            </div>
            <div class="col-md-3 mb-2">
                <label class="form-label small">Value (USD)</label>
                <input type="number" name="product_value[]" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01" value="0">
            </div>
            <div class="col-md-2 mb-2 d-flex align-items-end">
                <button type="button" class="btn btn-sm btn-danger remove-product-btn">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
    `;
    productsList.appendChild(newProduct);
    
    // Show remove buttons if more than one product
    updateRemoveButtons();
    
    // Attach remove event
    newProduct.querySelector(".remove-product-btn").addEventListener("click", function() {
        newProduct.remove();
        updateRemoveButtons();
    });
});

// Remove product functionality
function updateRemoveButtons() {
    const products = document.querySelectorAll(".product-item");
    products.forEach((product, index) => {
        const removeBtn = product.querySelector(".remove-product-btn");
        if (products.length > 1) {
            removeBtn.style.display = "block";
        } else {
            removeBtn.style.display = "none";
        }
    });
}

// Camera scan functionality
let cameraStream = null;
document.getElementById("scan_tracking_btn").addEventListener("click", function() {
    if (!("mediaDevices" in navigator && "getUserMedia" in navigator.mediaDevices)) {
        alert("Camera access is not supported in your browser.");
        return;
    }
    
    // Request camera permission
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } })
        .then(stream => {
            cameraStream = stream;
            showCameraModal();
        })
        .catch(err => {
            alert("Camera access denied. Please allow camera access to scan tracking numbers.");
            console.error("Camera error:", err);
        });
});

function showCameraModal() {
    const container = document.getElementById("camera_modal_container");
    container.innerHTML = `
        <div class="modal fade show" id="cameraModal" tabindex="-1" style="display: block;" data-bs-backdrop="static">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Scan Tracking Number</h5>
                        <button type="button" class="btn-close" onclick="closeCameraModal()"></button>
                    </div>
                    <div class="modal-body text-center">
                        <video id="camera_video" autoplay playsinline style="width: 100%; max-width: 500px; border: 2px solid #ddd; border-radius: 8px;"></video>
                        <p class="mt-3 text-muted">Point your camera at the tracking number on the package</p>
                        <div id="scan_result" class="mt-3"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" onclick="closeCameraModal()">Cancel</button>
                        <button type="button" class="btn btn-primary" onclick="captureTrackingNumber()">Capture</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-backdrop fade show"></div>
    `;
    
    const video = document.getElementById("camera_video");
    if (cameraStream) {
        video.srcObject = cameraStream;
    }
    
    // Initialize barcode scanner (using QuaggaJS or similar)
    // For now, we\'ll use a simple OCR approach or manual entry
    // You can integrate a proper barcode scanner library here
}

function closeCameraModal() {
    if (cameraStream) {
        cameraStream.getTracks().forEach(track => track.stop());
        cameraStream = null;
    }
    document.getElementById("camera_modal_container").innerHTML = "";
}

function captureTrackingNumber() {
    // For now, prompt for manual entry
    // In production, integrate a barcode/QR code scanner library
    const trackingNumber = prompt("Please enter the tracking number manually (or scan with a barcode scanner app):");
    if (trackingNumber) {
        document.getElementById("tracking_number").value = trackingNumber.trim();
        closeCameraModal();
    }
}
';

// Set page title and include layout
$pageTitle = 'Ship Now - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/user-layout.php';
?>

