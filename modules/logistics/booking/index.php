<?php
/**
 * Book Parcel/Delivery
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Force no cache
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

$userProfile = null;
try {
    $stmt = $conn->prepare("SELECT first_name, last_name FROM user_profiles WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $userProfile = null;
}

$customerFirstName = '';
if (!empty($userProfile['first_name'])) {
    $customerFirstName = $userProfile['first_name'];
} elseif (!empty($userProfile['last_name'])) {
    $customerFirstName = $userProfile['last_name'];
} elseif (!empty($user['user_identifier'])) {
    $customerFirstName = $user['user_identifier'];
} else {
    $customerFirstName = 'Customer';
}

$customerPhone = $user['phone'] ?? '';

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

$shippingRates = ['sea' => [], 'air' => []];
try {
    $stmt = $conn->prepare("SELECT * FROM shipping_rates WHERE is_active = 1 ORDER BY method_type, sort_order, rate_name");
    $stmt->execute();
    $rates = $stmt->fetchAll();
    
    foreach ($rates as $rate) {
        $methodType = $rate['method_type'];
        $rateDisplay = '$' . number_format($rate['rate_value'], 2) . '/' . strtoupper($rate['rate_type']);
        
        $shippingRates[$methodType][] = [
            'id' => $rate['rate_id'],
            'name' => $rate['rate_name'],
            'rate' => $rateDisplay,
            'rate_value' => floatval($rate['rate_value']),
            'rate_type' => $rate['rate_type'],
            'duration' => $rate['duration'] ?? '',
            'description' => $rate['description'] ?? ''
        ];
    }
} catch (PDOException $e) {
    error_log("Error loading shipping rates: " . $e->getMessage());
}

$errors = [];

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
        
        if ($forwardingWarehouseId <= 0) $errors[] = 'Please select a forwarding warehouse.';
        if ($destinationWarehouseId <= 0) $errors[] = 'Please select a destination warehouse.';
        if (empty($shippingMethodType)) $errors[] = 'Please select a mode of transport.';
        if (empty($shippingRateId)) $errors[] = 'Please select a shipping rate.';
        if (empty($trackingNumber)) $errors[] = 'Please enter a tracking number.';
        
        $weight = floatval($_POST['weight'] ?? 1.0);
        if ($weight <= 0) $weight = 1.0;
        
        $totalPrice = 0;
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("SELECT * FROM shipping_rates WHERE method_type = ? AND rate_id = ? AND is_active = 1");
                $stmt->execute([$shippingMethodType, $shippingRateId]);
                $rateDetails = $stmt->fetch();
                
                if ($rateDetails) {
                    if ($rateDetails['rate_type'] === 'cbm') {
                        $totalPrice = floatval($rateDetails['rate_value']) * 1.0;
                    } else if ($rateDetails['rate_type'] === 'kg') {
                        $totalPrice = floatval($rateDetails['rate_value']) * $weight;
                    } else if ($rateDetails['rate_type'] === 'unit') {
                        $totalPrice = floatval($rateDetails['rate_value']) * 1;
                    }
                } else {
                    $errors[] = 'Selected shipping rate not found.';
                }
            } catch (PDOException $e) {
                $errors[] = 'Calculation error.';
            }
        }
        
        $productDeclaration = null;
        if ($declareProducts && isset($_POST['product_name']) && is_array($_POST['product_name'])) {
            $products = [];
            foreach ($_POST['product_name'] as $index => $name) {
                if (!empty($name)) {
                    $products[] = [
                        'name' => sanitize($name),
                        'quantity' => intval($_POST['product_quantity'][$index] ?? 1),
                        'value' => floatval($_POST['product_value'][$index] ?? 0)
                    ];
                }
            }
            if (!empty($products)) $productDeclaration = json_encode($products, JSON_UNESCAPED_UNICODE);
        }
        
        if (empty($errors)) {
            $_SESSION['ship_now_data'] = [
                'forwarding_warehouse_id' => $forwardingWarehouseId,
                'destination_warehouse_id' => $destinationWarehouseId,
                'shipping_method_type' => $shippingMethodType,
                'shipping_rate_id' => $shippingRateId,
                'tracking_number' => $trackingNumber,
                'weight' => $weight,
                'total_price' => $totalPrice,
                'payment_method' => 'cod',
                'product_declaration' => $productDeclaration
            ];
            redirect('/modules/logistics/ship-now/process.php', '', '');
        }
    }
}

ob_start();
?>
<link rel="stylesheet" href="/assets/css/pages/logistics-premium.css?v=<?php echo time(); ?>">

<div class="logistics-page py-3">
    <div class="container animate-fade-in">
        <div class="page-title-section text-center mb-4">
            <h1 class="page-title">Logistics Hub</h1>
            <p class="text-muted small">Manage your warehouse addresses and create new shipments.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-10">
                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger px-3 py-2 small mb-3">
                    <ul class="mb-0">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Warehouse Section -->
                <?php if (!empty($forwardingWarehouses)): ?>
                <div class="premium-card">
                    <div class="premium-card-header">
                        <h5><i class="fas fa-warehouse"></i> Our Warehouse Addresses</h5>
                    </div>
                    <div class="premium-card-body">
                        <?php foreach ($forwardingWarehouses as $warehouse): ?>
                        <div class="warehouse-premium-card">
                            <div class="warehouse-header">
                                <div class="warehouse-title"><?php echo htmlspecialchars($warehouse['warehouse_name']); ?></div>
                                <button type="button" class="btn-copy-premium copy-address-btn" 
                                        data-english-address="<?php echo htmlspecialchars($warehouse['address_english']); ?>"
                                        data-chinese-address="<?php echo htmlspecialchars($warehouse['address_chinese']); ?>"
                                        data-receiver="<?php echo htmlspecialchars($warehouse['receiver_name'] . ' (' . ($user['user_identifier'] ?? 'Not assigned') . ')'); ?>"
                                        data-phone="<?php echo htmlspecialchars($warehouse['receiver_phone']); ?>"
                                        data-user-identifier="<?php echo htmlspecialchars($user['user_identifier'] ?? ''); ?>"
                                        data-customer-name="<?php echo htmlspecialchars($customerFirstName); ?>"
                                        data-customer-phone="<?php echo htmlspecialchars($customerPhone); ?>">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <div class="address-details small">
                                <div class="row mb-1">
                                    <div class="col-4 text-muted">Receiver</div>
                                    <div class="col-8 fw-700"><?php echo htmlspecialchars($warehouse['receiver_name']); ?> (<?php echo htmlspecialchars($user['user_identifier'] ?? 'N/A'); ?>)</div>
                                </div>
                                <div class="row mb-1">
                                    <div class="col-4 text-muted">Address</div>
                                    <div class="col-8"><?php echo htmlspecialchars($warehouse['address_chinese']); ?></div>
                                </div>
                                <div class="row">
                                    <div class="col-4 text-muted">Phone</div>
                                    <div class="col-8 fw-700"><?php echo htmlspecialchars($warehouse['receiver_phone']); ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Shipment Form -->
                <div class="premium-card">
                    <div class="premium-card-header">
                        <h5><i class="fas fa-shipping-fast"></i> Create New Shipment</h5>
                    </div>
                    <div class="premium-card-body">
                        <form method="POST" action="" id="shipNowForm">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-group-premium">
                                <label class="label-premium">Forwarding Warehouse</label>
                                <select name="forwarding_warehouse_id" id="forwarding_warehouse_id" class="select-premium" required>
                                    <option value="">-- Select Origin --</option>
                                    <?php foreach ($forwardingWarehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>"><?php echo htmlspecialchars($warehouse['warehouse_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group-premium">
                                <label class="label-premium">Destination Warehouse</label>
                                <select name="destination_warehouse_id" id="destination_warehouse_id" class="select-premium" required>
                                    <option value="">-- Select Destination --</option>
                                    <?php foreach ($destinationWarehouses as $warehouse): ?>
                                    <option value="<?php echo $warehouse['id']; ?>"><?php echo htmlspecialchars($warehouse['warehouse_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group-premium">
                                    <label class="label-premium">Shipping Method</label>
                                <select name="shipping_method_type" id="shipping_method_type" class="select-premium" required>
                                    <option value="">-- Select Mode --</option>
                                    <option value="air">✈️ Air Freight</option>
                                    <select name="shipping_method_type" id="shipping_method_type" class="select-premium" required>
                                        <option value="">-- Select Method --</option>
                                        <option value="air">✈️ Air Freight</option>
                                        <option value="sea">🚢 Sea Freight</option>
                                    </select>
                                </div>

                                <div class="col-md-6 form-group-premium d-none" id="shipping_rate_section">
                                    <label class="label-premium">Shipping Rate</label>
                                    <select name="shipping_rate_id" id="shipping_rate_id" class="select-premium" required>
                                        <option value="">-- Select Rate --</option>
                                    </select>
                                    <div id="rate_description" class="mt-2 x-small text-muted"></div>
                                </div>
                            </div>

                            <div class="form-group-premium">
                                <label class="label-premium">Tracking Number</label>
                                <div class="tracking-input-wrapper">
                                    <input type="text" name="tracking_number" id="tracking_number" class="input-premium" placeholder="Carrier tracking ID" required>
                                    <button type="button" class="btn-scan-premium" id="scan_tracking_btn">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-group-premium">
                                <label class="label-premium">Weight (kg)</label>
                                <input type="number" name="weight" id="package_weight" class="input-premium" value="1" min="0.1" step="0.1">
                            </div>

                            <div class="cost-display-premium d-none" id="cost_calculation_section">
                                <div class="cost-row">
                                    <span class="text-muted">Rate</span>
                                    <span id="calc_rate_display" class="fw-700">-</span>
                                </div>
                                <div class="cost-row">
                                    <span class="text-muted">Weight</span>
                                    <span id="calc_weight_display" class="fw-700">-</span>
                                </div>
                                <div class="total-cost-row">
                                    <span class="fw-700">Total Est. Price</span>
                                    <span id="total_cost_display" class="total-cost-value">$0.00</span>
                                </div>
                            </div>

                            <div class="my-3 py-2 px-3 bg-light rounded text-center small">
                                <i class="fas fa-check-circle text-success me-1"></i> Cash on Delivery (COD)
                            </div>

                            <div class="declaration-toggle" onclick="document.getElementById('declare_products').click()">
                                <span><i class="fas fa-list-ul me-2"></i> Items Declaration</span>
                                <div class="form-check form-switch m-0">
                                    <input class="form-check-input" type="checkbox" name="declare_products" id="declare_products">
                                </div>
                            </div>

                            <div id="product_declaration_section" class="mt-2 d-none">
                                <div id="products_list">
                                    <div class="product-item">
                                        <div class="row g-2">
                                            <div class="col-5">
                                                <label class="x-small fw-700">Product Name</label>
                                                <input type="text" name="product_name[]" class="form-control form-control-sm">
                                            </div>
                                            <div class="col-3">
                                                <label class="x-small fw-700">Qty</label>
                                                <input type="number" name="product_quantity[]" class="form-control form-control-sm" value="1">
                                            </div>
                                            <div class="col-4">
                                                <label class="x-small fw-700">Value (USD)</label>
                                                <input type="number" name="product_value[]" class="form-control form-control-sm" value="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_product_btn">
                                    <i class="fas fa-plus"></i> Add Item
                                </button>
                            </div>

                            <div class="mt-4 text-center">
                                <button type="submit" name="ship_now" class="btn-ship-now">
                                    <i class="fas fa-paper-plane"></i> Ship Now
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="camera_modal_container"></div>

<?php
$content = ob_get_clean();

$additionalCSS = [
    'https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.css'
];

$additionalJS = [
    'https://cdnjs.cloudflare.com/ajax/libs/quagga/0.12.1/quagga.min.js'
];

$inlineJS = '
const shippingRates = ' . json_encode($shippingRates, JSON_UNESCAPED_UNICODE) . ';

document.getElementById("shipping_method_type").addEventListener("change", function() {
    const methodType = this.value;
    const rateSection = document.getElementById("shipping_rate_section");
    const rateSelect = document.getElementById("shipping_rate_id");
    const rateDescription = document.getElementById("rate_description");
    
    if (methodType && shippingRates[methodType]) {
        rateSection.classList.remove("d-none");
        rateSelect.innerHTML = "<option value=\"\">-- Select Rate --</option>";
        shippingRates[methodType].forEach(rate => {
            const option = document.createElement("option");
            option.value = rate.id;
            option.textContent = rate.name + " - " + rate.rate;
            option.dataset.description = rate.description;
            rateSelect.appendChild(option);
        });
    } else {
        rateSection.classList.add("d-none");
        document.getElementById("cost_calculation_section").classList.add("d-none");
    }
    calculateCost();
});

function calculateCost() {
    const methodType = document.getElementById("shipping_method_type").value;
    const rateId = document.getElementById("shipping_rate_id").value;
    const weight = parseFloat(document.getElementById("package_weight").value) || 1;
    const costSection = document.getElementById("cost_calculation_section");
    
    if (methodType && rateId && shippingRates[methodType]) {
        const rate = shippingRates[methodType].find(r => r.id === rateId);
        if (rate) {
            let totalCost = rate.rate_type === "kg" ? rate.rate_value * weight : rate.rate_value;
            document.getElementById("calc_rate_display").textContent = rate.rate;
            document.getElementById("calc_weight_display").textContent = weight + " kg";
            document.getElementById("total_cost_display").textContent = "$" + totalCost.toFixed(2);
            costSection.classList.remove("d-none");
        } else {
            costSection.classList.add("d-none");
        }
    } else {
        costSection.classList.add("d-none");
    }
}

document.getElementById("shipping_rate_id").addEventListener("change", function() {
    const selectedOption = this.options[this.selectedIndex];
    document.getElementById("rate_description").textContent = selectedOption.dataset.description || "";
    calculateCost();
});

document.getElementById("package_weight").addEventListener("input", calculateCost);

document.querySelectorAll(".copy-address-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        const chineseAddress = this.dataset.chineseAddress || "";
        const warehousePhone = this.dataset.phone || "";
        const userIdentifier = this.dataset.userIdentifier || "";
        const customerName = this.dataset.customerName || "";
        const customerPhone = this.dataset.customerPhone || "";

        const text = "ThinQ:" + warehousePhone + "\\n" + chineseAddress + " (" + userIdentifier + ")\\n\\nShipping Mark: (" + customerName + ")" + customerPhone;

        const textarea = document.createElement("textarea");
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand("copy");
        document.body.removeChild(textarea);

        const originalText = this.innerHTML;
        this.innerHTML = "Copied!";
        setTimeout(() => { this.innerHTML = originalText; }, 2000);
    });
});

document.getElementById("declare_products").addEventListener("change", function() {
    document.getElementById("product_declaration_section").classList.toggle("d-none", !this.checked);
});

document.getElementById("add_product_btn").addEventListener("click", function() {
    const productsList = document.getElementById("products_list");
    const newProduct = document.createElement("div");
    newProduct.className = "product-item";
    newProduct.innerHTML = `
        <div class="row g-2">
            <div class="col-5">
                <input type="text" name="product_name[]" class="form-control form-control-sm">
            </div>
            <div class="col-3">
                <input type="number" name="product_quantity[]" class="form-control form-control-sm" value="1">
            </div>
            <div class="col-4 d-flex gap-1">
                <input type="number" name="product_value[]" class="form-control form-control-sm" value="0">
                <button type="button" class="btn btn-link text-danger p-0" onclick="this.closest(\'.product-item\').remove()"><i class="fas fa-trash"></i></button>
            </div>
        </div>
    `;
    productsList.appendChild(newProduct);
});

let cameraStream = null;
let quaggaInitialized = false;

document.getElementById("scan_tracking_btn").addEventListener("click", function() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return alert("Camera not supported");
    navigator.mediaDevices.getUserMedia({ video: { facingMode: "environment" } }).then(stream => {
        cameraStream = stream;
        document.getElementById("camera_modal_container").innerHTML = `
            <div class="modal fade show quagga-modal">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header fw-bold">Scan Barcode <button type="button" class="btn-close" onclick="closeCameraModal()"></button></div>
                        <div class="modal-body"><div id="interactive" class="quagga-viewport"></div></div>
                    </div>
                </div>
            </div>
            <div class="modal-backdrop fade show"></div>`;
        setTimeout(initQuagga, 100);
    });
});

function initQuagga() {
    Quagga.init({
        inputStream: { type: "LiveStream", target: document.querySelector("#interactive"), constraints: { facingMode: "environment" } },
        decoder: { readers: ["code_128_reader", "ean_reader"] }
    }, function(err) {
        if (err) return;
        Quagga.start();
        Quagga.onDetected(result => {
            document.getElementById("tracking_number").value = result.codeResult.code;
            closeCameraModal();
        });
    });
}

function closeCameraModal() {
    if (cameraStream) cameraStream.getTracks().forEach(t => t.stop());
    document.getElementById("camera_modal_container").innerHTML = "";
    Quagga.stop();
}
';

$pageTitle = 'Logistics Hub - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/user-layout.php';
?>
