<?php
/**
 * Send Money Transfer (Ghana to China)
 * ThinQShopping Platform
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../includes/auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

// Fixed currencies: Ghana to China only
$sendingCurrency = 'GHS';
$receivingCurrency = 'CNY';

// Get exchange rate setting (automatic or manual)
$stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'auto_exchange_rate'");
$autoExchangeRate = $stmt->fetch();
$isAutoEnabled = ($autoExchangeRate['setting_value'] ?? '0') === '1';

// Get exchange rate based on setting
if ($isAutoEnabled) {
    // Use automatic rate from exchange_rates table
    $stmt = $conn->query("
        SELECT rate_ghs_to_cny 
        FROM exchange_rates 
        WHERE is_active = 1 AND valid_from <= NOW() AND (valid_to IS NULL OR valid_to >= NOW())
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $rateData = $stmt->fetch();
    $exchangeRate = $rateData ? floatval($rateData['rate_ghs_to_cny']) : 1.25; // Default: 1 GHS = 1.25 CNY
} else {
    // Use manual rate from settings
    $stmt = $conn->query("SELECT setting_value FROM settings WHERE setting_key = 'ghs_to_cny_rate'");
    $manualRate = $stmt->fetch();
    $exchangeRate = $manualRate ? floatval($manualRate['setting_value']) : 1.25; // Default: 1 GHS = 1.25 CNY
}

// Saved recipients feature removed - form is always open for new entries

// Get wallet balance
$walletBalance = getUserWalletBalance($userId);

$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'Invalid security token.';
    } else {
        // Transfer amount (needed for QR code validation)
        $amountGHS = floatval($_POST['amount_ghs'] ?? 0);
        
        // Recipient details - always new entry
        $recipientType = sanitize($_POST['recipient_type'] ?? '');
        $recipientName = sanitize($_POST['recipient_name'] ?? '');
        $recipientPhone = sanitize($_POST['recipient_phone'] ?? '');
        
        if (empty($recipientType)) {
            $errors[] = 'Please select recipient type.';
        }
        
        // Name and phone only required for bank_account and in_person
        if (in_array($recipientType, ['bank_account', 'in_person'])) {
            if (empty($recipientName)) {
                $errors[] = 'Please enter recipient name.';
            }
            if (empty($recipientPhone)) {
                $errors[] = 'Please enter recipient phone.';
            }
        }
        
        // Recipient details based on type
        if ($recipientType === 'bank_account') {
            $recipientDetails = [
                'account_number' => sanitize($_POST['account_number'] ?? ''),
                'bank_name' => sanitize($_POST['bank_name'] ?? ''),
                'branch' => sanitize($_POST['branch'] ?? ''),
                'swift_code' => sanitize($_POST['swift_code'] ?? '')
            ];
        } elseif ($recipientType === 'alipay') {
            // Handle Alipay QR codes
            $qrCodes = [];
            if (isset($_POST['alipay_qr_amounts']) && is_array($_POST['alipay_qr_amounts'])) {
                foreach ($_POST['alipay_qr_amounts'] as $index => $amount) {
                    $amount = floatval($amount);
                    if ($amount > 0) {
                        $qrCodes[] = [
                            'amount' => $amount,
                            'index' => $index
                        ];
                    }
                }
            }
            
            // Handle QR code file uploads
            $uploadedQRCodes = [];
            if (!empty($_FILES['alipay_qr_codes']['name'][0])) {
                foreach ($_FILES['alipay_qr_codes']['tmp_name'] as $key => $tmpName) {
                    if (!empty($tmpName) && $_FILES['alipay_qr_codes']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['alipay_qr_codes']['name'][$key],
                            'type' => $_FILES['alipay_qr_codes']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['alipay_qr_codes']['error'][$key],
                            'size' => $_FILES['alipay_qr_codes']['size'][$key]
                        ];
                        
                        $result = uploadImage($file, UPLOAD_PATH);
                        if ($result['success']) {
                            $uploadedQRCodes[] = $result['filename'];
                        } else {
                            // Only add error once per upload attempt, not for each file
                            $uploadError = 'Failed to upload QR code: ' . ($result['message'] ?? 'Unknown error');
                            if (!in_array($uploadError, $errors)) {
                                $errors[] = $uploadError;
                            }
                        }
                    }
                }
            }
            
            // Match QR codes with amounts in order
            $qrCodeData = [];
            $uploadedIndex = 0;
            $missingFiles = [];
            
            foreach ($qrCodes as $qr) {
                if ($uploadedIndex < count($uploadedQRCodes)) {
                    $qrCodeData[] = [
                        'qr_code' => $uploadedQRCodes[$uploadedIndex],
                        'amount' => $qr['amount']
                    ];
                    $uploadedIndex++;
                } elseif ($qr['amount'] > 0) {
                    // Amount provided but no file uploaded
                    $missingFiles[] = '¥' . number_format($qr['amount'], 2);
                }
            }
            
            // Add missing file errors (only once, not duplicated)
            if (!empty($missingFiles)) {
                $errors[] = 'QR code file(s) required for amount(s): ' . implode(', ', $missingFiles);
            }
            
            $recipientDetails = [
                'qr_codes' => $qrCodeData
            ];
            
            // Validate that first QR code is provided (required)
            if (empty($qrCodeData) || !isset($qrCodeData[0]) || empty($qrCodeData[0]['qr_code'])) {
                if (empty($uploadedQRCodes)) {
                    // Only add this error if we haven't already added upload errors
                    $hasUploadError = false;
                    foreach ($errors as $error) {
                        if (strpos($error, 'Failed to upload QR code') !== false) {
                            $hasUploadError = true;
                            break;
                        }
                    }
                    if (!$hasUploadError) {
                        $errors[] = 'The first Alipay QR code file is required. Please upload at least one QR code.';
                    }
                } else {
                    $errors[] = 'The first Alipay QR code is required. Please ensure the first QR code has both a file and an amount.';
                }
            } else {
                // Validate that QR code amounts match recipient amount
                $totalQRAmount = 0;
                foreach ($qrCodeData as $qr) {
                    $totalQRAmount += floatval($qr['amount']);
                }
                
                // Calculate expected recipient amount
                $expectedAmountCNY = $amountGHS * $exchangeRate;
                $difference = abs($totalQRAmount - $expectedAmountCNY);
                
                if ($difference > 0.01) { // Allow 0.01 CNY tolerance for rounding
                    if ($totalQRAmount > $expectedAmountCNY) {
                        $errors[] = sprintf(
                            'Total Alipay QR code amounts (¥ %s) exceeds recipient amount (¥ %s) by ¥ %s. Please adjust the amounts.',
                            number_format($totalQRAmount, 2),
                            number_format($expectedAmountCNY, 2),
                            number_format($totalQRAmount - $expectedAmountCNY, 2)
                        );
                    } else {
                        $errors[] = sprintf(
                            'Total Alipay QR code amounts (¥ %s) is less than recipient amount (¥ %s) by ¥ %s. Please adjust the amounts.',
                            number_format($totalQRAmount, 2),
                            number_format($expectedAmountCNY, 2),
                            number_format($expectedAmountCNY - $totalQRAmount, 2)
                        );
                    }
                }
            }
        } elseif ($recipientType === 'wechat_pay') {
            // Handle WeChat QR codes
            $qrCodes = [];
            if (isset($_POST['wechat_qr_amounts']) && is_array($_POST['wechat_qr_amounts'])) {
                foreach ($_POST['wechat_qr_amounts'] as $index => $amount) {
                    $amount = floatval($amount);
                    if ($amount > 0) {
                        $qrCodes[] = [
                            'amount' => $amount,
                            'index' => $index
                        ];
                    }
                }
            }
            
            // Handle QR code file uploads
            $uploadedQRCodes = [];
            if (!empty($_FILES['wechat_qr_codes']['name'][0])) {
                foreach ($_FILES['wechat_qr_codes']['tmp_name'] as $key => $tmpName) {
                    if (!empty($tmpName) && $_FILES['wechat_qr_codes']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['wechat_qr_codes']['name'][$key],
                            'type' => $_FILES['wechat_qr_codes']['type'][$key],
                            'tmp_name' => $tmpName,
                            'error' => $_FILES['wechat_qr_codes']['error'][$key],
                            'size' => $_FILES['wechat_qr_codes']['size'][$key]
                        ];
                        
                        $result = uploadImage($file, UPLOAD_PATH);
                        if ($result['success']) {
                            $uploadedQRCodes[] = $result['filename'];
                        } else {
                            // Only add error once per upload attempt, not for each file
                            $uploadError = 'Failed to upload QR code: ' . ($result['message'] ?? 'Unknown error');
                            if (!in_array($uploadError, $errors)) {
                                $errors[] = $uploadError;
                            }
                        }
                    }
                }
            }
            
            // Match QR codes with amounts in order
            $qrCodeData = [];
            $uploadedIndex = 0;
            $missingFiles = [];
            
            foreach ($qrCodes as $qr) {
                if ($uploadedIndex < count($uploadedQRCodes)) {
                    $qrCodeData[] = [
                        'qr_code' => $uploadedQRCodes[$uploadedIndex],
                        'amount' => $qr['amount']
                    ];
                    $uploadedIndex++;
                } elseif ($qr['amount'] > 0) {
                    // Amount provided but no file uploaded
                    $missingFiles[] = '¥' . number_format($qr['amount'], 2);
                }
            }
            
            // Add missing file errors (only once, not duplicated)
            if (!empty($missingFiles)) {
                $errors[] = 'QR code file(s) required for amount(s): ' . implode(', ', $missingFiles);
            }
            
            $recipientDetails = [
                'qr_codes' => $qrCodeData
            ];
            
            // Validate that first QR code is provided (required)
            if (empty($qrCodeData) || !isset($qrCodeData[0]) || empty($qrCodeData[0]['qr_code'])) {
                if (empty($uploadedQRCodes)) {
                    // Only add this error if we haven't already added upload errors
                    $hasUploadError = false;
                    foreach ($errors as $error) {
                        if (strpos($error, 'Failed to upload QR code') !== false) {
                            $hasUploadError = true;
                            break;
                        }
                    }
                    if (!$hasUploadError) {
                        $errors[] = 'The first WeChat QR code file is required. Please upload at least one QR code.';
                    }
                } else {
                    $errors[] = 'The first WeChat QR code is required. Please ensure the first QR code has both a file and an amount.';
                }
            } else {
                // Validate that QR code amounts match recipient amount
                $totalQRAmount = 0;
                foreach ($qrCodeData as $qr) {
                    $totalQRAmount += floatval($qr['amount']);
                }
                
                // Calculate expected recipient amount
                $expectedAmountCNY = $amountGHS * $exchangeRate;
                $difference = abs($totalQRAmount - $expectedAmountCNY);
                
                if ($difference > 0.01) { // Allow 0.01 CNY tolerance for rounding
                    if ($totalQRAmount > $expectedAmountCNY) {
                        $errors[] = sprintf(
                            'Total WeChat QR code amounts (¥ %s) exceeds recipient amount (¥ %s) by ¥ %s. Please adjust the amounts.',
                            number_format($totalQRAmount, 2),
                            number_format($expectedAmountCNY, 2),
                            number_format($totalQRAmount - $expectedAmountCNY, 2)
                        );
                    } else {
                        $errors[] = sprintf(
                            'Total WeChat QR code amounts (¥ %s) is less than recipient amount (¥ %s) by ¥ %s. Please adjust the amounts.',
                            number_format($totalQRAmount, 2),
                            number_format($expectedAmountCNY, 2),
                            number_format($expectedAmountCNY - $totalQRAmount, 2)
                        );
                    }
                }
            }
        } elseif ($recipientType === 'in_person') {
            $recipientDetails = [
                'collection_location' => sanitize($_POST['collection_location'] ?? '')
            ];
        }
        
        // Purpose
        $purpose = sanitize($_POST['purpose'] ?? '');
        
        // Validate amount
        if ($amountGHS < MIN_TRANSFER_AMOUNT) {
            $errors[] = 'Minimum transfer amount is ' . formatCurrency(MIN_TRANSFER_AMOUNT);
        } elseif ($amountGHS > MAX_TRANSFER_AMOUNT) {
            $errors[] = 'Maximum transfer amount is ' . formatCurrency(MAX_TRANSFER_AMOUNT);
        }
        
        // Payment method
        $paymentMethod = sanitize($_POST['payment_method'] ?? '');
        
        if (!in_array($paymentMethod, ['card', 'mobile_money', 'bank_transfer', 'wallet'])) {
            $errors[] = 'Invalid payment method.';
        }
        
        if ($paymentMethod === 'wallet' && $walletBalance < $amountGHS) {
            $errors[] = 'Insufficient wallet balance.';
        }
        
        if (empty($errors)) {
            // Calculate amounts
            $amountCNY = $amountGHS * $exchangeRate;
            $totalAmount = $amountGHS; // No transfer fee
            
            // Store in session for processing (format expected by process.php)
            $_SESSION['transfer_recipient'] = [
                'name' => $recipientName,
                'phone' => $recipientPhone,
                'type' => $recipientType,
                'details' => $recipientDetails,
                'save' => false // Save recipient feature removed
            ];
            
            $_SESSION['transfer_details'] = [
                'sending_currency' => 'GHS',
                'receiving_currency' => 'CNY',
                'amount_ghs' => $amountGHS,
                'amount_cny' => $amountCNY,
                'exchange_rate' => $exchangeRate,
                'transfer_fee' => 0,
                'total_amount' => $totalAmount,
                'purpose' => $purpose
            ];
            
            $_SESSION['transfer_payment'] = [
                'method' => $paymentMethod
            ];
            
            // Redirect to payment processing
            redirect('/modules/money-transfer/transfer-form/process.php', '', '');
        }
    }
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Send Money Transfer</h1>
</div>

<div class="row justify-content-center">
    <div class="col-lg-10">
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <!-- Exchange Rate Banner -->
        <div class="alert alert-info mb-4">
            <strong>Current Exchange Rate:</strong> 
            1 GHS = <?php echo number_format($exchangeRate, 4); ?> CNY
            <small class="text-muted ms-2">(Rates may vary at any given time)</small>
        </div>
        
        <!-- Single Form -->
        <form method="POST" action="" id="transferForm" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <!-- Transfer Amount Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Transfer Amount</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Amount to Send (GHS) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-lg">
                            <span class="input-group-text">₵</span>
                            <input type="number" step="0.01" name="amount_ghs" id="amount_ghs" 
                                   class="form-control" required
                                   min="<?php echo MIN_TRANSFER_AMOUNT; ?>"
                                   max="<?php echo MAX_TRANSFER_AMOUNT; ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="decrementAmount()">−</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="incrementAmount()">+</button>
                        </div>
                        <small class="form-text text-muted">
                            Min: <?php echo formatCurrency(MIN_TRANSFER_AMOUNT); ?> | 
                            Max: <?php echo formatCurrency(MAX_TRANSFER_AMOUNT); ?>
                        </small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount Recipient Will Receive (CNY)</label>
                        <div class="input-group">
                            <span class="input-group-text">¥</span>
                            <input type="text" id="amount_cny" class="form-control" readonly>
                        </div>
                        <small class="form-text text-muted" id="recipient_equivalent"></small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Purpose of Transfer (Optional)</label>
                        <textarea name="purpose" class="form-control" rows="3" placeholder="e.g., Family support, Business payment..."></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Recipient Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Recipient Information</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Recipient Payment Method <span class="text-danger">*</span></label>
                        <select name="recipient_type" id="recipient_type" class="form-select" required>
                            <option value="">Select Payment Method</option>
                            <option value="alipay">Alipay</option>
                            <option value="wechat_pay">WeChat</option>
                            <option value="bank_account">Bank Transfer</option>
                            <option value="in_person">In Person Collection</option>
                        </select>
                        <small class="form-text text-muted">How the recipient will receive the money</small>
                    </div>
                    
                    <!-- Name and Phone (only for bank_account and in_person) -->
                    <div id="name_phone_fields" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Recipient Name <span class="text-danger">*</span></label>
                            <input type="text" name="recipient_name" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Recipient Phone <span class="text-danger">*</span></label>
                            <input type="tel" name="recipient_phone" id="recipient_phone" class="form-control" 
                                   placeholder="+86XXXXXXXXXXX">
                            <small class="form-text text-muted">Include country code (e.g., +86 for China)</small>
                        </div>
                    </div>
                    
                    <!-- Bank Account Details -->
                    <div id="bank_details" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Account Number <span class="text-danger">*</span></label>
                            <input type="text" name="account_number" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Bank Name <span class="text-danger">*</span></label>
                            <input type="text" name="bank_name" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branch</label>
                            <input type="text" name="branch" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">SWIFT Code</label>
                            <input type="text" name="swift_code" class="form-control">
                        </div>
                    </div>
                    
                    <!-- Alipay QR Code Details -->
                    <div id="alipay_details" style="display: none;">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Upload Alipay QR Codes:</strong> Upload one or more Alipay QR codes and specify the amount to send to each QR code. The first QR code is required.
                        </div>
                        <div id="alipay_qr_container">
                            <!-- QR codes will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addAlipayQR()">
                            <i class="fas fa-plus me-2"></i>Add Another QR Code
                        </button>
                        <!-- QR Code Amount Validation -->
                        <div id="alipay_qr_validation" class="mt-3" style="display: none;"></div>
                    </div>
                    
                    <!-- WeChat QR Code Details -->
                    <div id="wechat_details" style="display: none;">
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Upload WeChat QR Codes:</strong> Upload one or more WeChat QR codes and specify the amount to send to each QR code. The first QR code is required.
                        </div>
                        <div id="wechat_qr_container">
                            <!-- QR codes will be added here dynamically -->
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addWeChatQR()">
                            <i class="fas fa-plus me-2"></i>Add Another QR Code
                        </button>
                        <!-- QR Code Amount Validation -->
                        <div id="wechat_qr_validation" class="mt-3" style="display: none;"></div>
                    </div>
                    
                    <!-- In Person Collection Details -->
                    <div id="in_person_details" style="display: none;">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>In Person Collection:</strong> The recipient will collect the money in person at our designated location. 
                            Please ensure the recipient has valid identification.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Collection Location <span class="text-danger">*</span></label>
                            <select name="collection_location" class="form-select">
                                <option value="">Select Location</option>
                                <option value="beijing">Beijing Office</option>
                                <option value="shanghai">Shanghai Office</option>
                                <option value="guangzhou">Guangzhou Office</option>
                                <option value="shenzhen">Shenzhen Office</option>
                            </select>
                            <small class="form-text text-muted">Select the location where the recipient will collect the money</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Method Section -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Payment Method</h5>
                </div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="payment_card" value="card" checked required>
                        <label class="form-check-label" for="payment_card">
                            <i class="fas fa-credit-card"></i> Card Payment
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="payment_mobile" value="mobile_money">
                        <label class="form-check-label" for="payment_mobile">
                            <i class="fas fa-mobile-alt"></i> Mobile Money
                        </label>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="payment_bank" value="bank_transfer">
                        <label class="form-check-label" for="payment_bank">
                            <i class="fas fa-university"></i> Bank Transfer
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="payment_method" 
                               id="payment_wallet" value="wallet"
                               <?php echo $walletBalance >= MIN_TRANSFER_AMOUNT ? '' : 'disabled'; ?>>
                        <label class="form-check-label" for="payment_wallet">
                            <i class="fas fa-wallet"></i> Platform Wallet
                            <small class="text-muted">(Balance: <?php echo formatCurrency($walletBalance); ?>)</small>
                            <?php if ($walletBalance < MIN_TRANSFER_AMOUNT): ?>
                                <span class="badge bg-warning ms-2">Insufficient</span>
                            <?php endif; ?>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="text-center mb-4">
                <button type="submit" class="btn btn-primary btn-lg px-5">
                    <i class="fas fa-paper-plane me-2"></i>Complete Transfer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const exchangeRate = <?php echo $exchangeRate; ?>;
const minAmountGHS = <?php echo MIN_TRANSFER_AMOUNT; ?>;
const maxAmountGHS = <?php echo MAX_TRANSFER_AMOUNT; ?>;

// Saved recipient functionality removed - form is always open for new entries

let alipayQRCount = 0;
let wechatQRCount = 0;

// Add Alipay QR code field
function addAlipayQR(isRequired = false) {
    alipayQRCount++;
    const index = alipayQRCount - 1;
    const container = document.getElementById('alipay_qr_container');
    const requiredAttr = isRequired ? 'required' : '';
    const requiredLabel = isRequired ? '<span class="text-danger">*</span>' : '';
    
    const qrHtml = `
        <div class="card mb-3 alipay-qr-item" data-index="${index}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">QR Code #${alipayQRCount} ${!isRequired ? '<span class="badge bg-secondary ms-2">Optional</span>' : ''}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeAlipayQR(this)" ${alipayQRCount <= 2 ? 'style="display:none;"' : ''}>
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Upload QR Code ${requiredLabel}</label>
                        <input type="file" name="alipay_qr_codes[]" class="form-control" accept="image/*" ${requiredAttr}>
                        <small class="form-text text-muted">Upload Alipay QR code image</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount (CNY) ${requiredLabel}</label>
                        <div class="input-group">
                            <span class="input-group-text">¥</span>
                            <input type="number" step="0.01" name="alipay_qr_amounts[${index}]" class="form-control qr-amount-input" min="0.01" ${requiredAttr} oninput="validateQRCodeAmounts()">
                        </div>
                        <small class="form-text text-muted">Amount to send to this QR code</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = qrHtml;
    container.appendChild(tempDiv.firstElementChild);
    
    // Attach event listener to the new amount input
    const newInput = container.querySelector('.alipay-qr-item:last-child input.qr-amount-input');
    if (newInput) {
        newInput.addEventListener('input', validateQRCodeAmounts);
    }
    
    updateAlipayRemoveButtons();
    validateQRCodeAmounts();
}

// Remove Alipay QR code field
function removeAlipayQR(button) {
    const item = button.closest('.alipay-qr-item');
    item.remove();
    alipayQRCount--;
    updateAlipayQRNumbers();
    updateAlipayRemoveButtons();
    validateQRCodeAmounts();
}

// Update Alipay QR numbers
function updateAlipayQRNumbers() {
    const items = document.querySelectorAll('.alipay-qr-item');
    items.forEach((item, index) => {
        const header = item.querySelector('h6');
        header.textContent = `QR Code #${index + 1}`;
        item.setAttribute('data-index', index);
        const amountInput = item.querySelector('input[name^="alipay_qr_amounts"]');
        if (amountInput) {
            amountInput.setAttribute('name', `alipay_qr_amounts[${index}]`);
        }
    });
    alipayQRCount = items.length;
}

// Update Alipay remove buttons visibility
function updateAlipayRemoveButtons() {
    const removeButtons = document.querySelectorAll('.alipay-qr-item .btn-outline-danger');
    removeButtons.forEach(btn => {
        btn.style.display = alipayQRCount > 2 ? 'block' : 'none';
    });
}

// Add WeChat QR code field
function addWeChatQR(isRequired = false) {
    wechatQRCount++;
    const index = wechatQRCount - 1;
    const container = document.getElementById('wechat_qr_container');
    const requiredAttr = isRequired ? 'required' : '';
    const requiredLabel = isRequired ? '<span class="text-danger">*</span>' : '';
    
    const qrHtml = `
        <div class="card mb-3 wechat-qr-item" data-index="${index}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">QR Code #${wechatQRCount} ${!isRequired ? '<span class="badge bg-secondary ms-2">Optional</span>' : ''}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeWeChatQR(this)" ${wechatQRCount <= 2 ? 'style="display:none;"' : ''}>
                        <i class="fas fa-times"></i> Remove
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Upload QR Code ${requiredLabel}</label>
                        <input type="file" name="wechat_qr_codes[]" class="form-control" accept="image/*" ${requiredAttr}>
                        <small class="form-text text-muted">Upload WeChat QR code image</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Amount (CNY) ${requiredLabel}</label>
                        <div class="input-group">
                            <span class="input-group-text">¥</span>
                            <input type="number" step="0.01" name="wechat_qr_amounts[${index}]" class="form-control qr-amount-input" min="0.01" ${requiredAttr} oninput="validateQRCodeAmounts()">
                        </div>
                        <small class="form-text text-muted">Amount to send to this QR code</small>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = qrHtml;
    container.appendChild(tempDiv.firstElementChild);
    
    // Attach event listener to the new amount input
    const newInput = container.querySelector('.wechat-qr-item:last-child input.qr-amount-input');
    if (newInput) {
        newInput.addEventListener('input', validateQRCodeAmounts);
    }
    
    updateWeChatRemoveButtons();
    validateQRCodeAmounts();
}

// Remove WeChat QR code field
function removeWeChatQR(button) {
    const item = button.closest('.wechat-qr-item');
    item.remove();
    wechatQRCount--;
    updateWeChatQRNumbers();
    updateWeChatRemoveButtons();
    validateQRCodeAmounts();
}

// Update WeChat QR numbers
function updateWeChatQRNumbers() {
    const items = document.querySelectorAll('.wechat-qr-item');
    items.forEach((item, index) => {
        const header = item.querySelector('h6');
        header.textContent = `QR Code #${index + 1}`;
        item.setAttribute('data-index', index);
        const amountInput = item.querySelector('input[name^="wechat_qr_amounts"]');
        if (amountInput) {
            amountInput.setAttribute('name', `wechat_qr_amounts[${index}]`);
        }
    });
    wechatQRCount = items.length;
}

// Update WeChat remove buttons visibility
function updateWeChatRemoveButtons() {
    const removeButtons = document.querySelectorAll('.wechat-qr-item .btn-outline-danger');
    removeButtons.forEach(btn => {
        btn.style.display = wechatQRCount > 2 ? 'block' : 'none';
    });
}

// Handle recipient type changes
const recipientTypeSelect = document.getElementById('recipient_type');
if (recipientTypeSelect) {
    recipientTypeSelect.addEventListener('change', function() {
        const namePhoneFields = document.getElementById('name_phone_fields');
        const bankDetails = document.getElementById('bank_details');
        const alipayDetails = document.getElementById('alipay_details');
        const wechatDetails = document.getElementById('wechat_details');
        const inPersonDetails = document.getElementById('in_person_details');
        
        // Hide all
        namePhoneFields.style.display = 'none';
        bankDetails.style.display = 'none';
        alipayDetails.style.display = 'none';
        wechatDetails.style.display = 'none';
        inPersonDetails.style.display = 'none';
        
        // Remove required from name/phone
        const nameInput = document.querySelector('input[name="recipient_name"]');
        const phoneInput = document.querySelector('input[name="recipient_phone"]');
        if (nameInput) nameInput.removeAttribute('required');
        if (phoneInput) phoneInput.removeAttribute('required');
        
        if (this.value === 'bank_account') {
            namePhoneFields.style.display = 'block';
            bankDetails.style.display = 'block';
            if (nameInput) nameInput.setAttribute('required', 'required');
            if (phoneInput) phoneInput.setAttribute('required', 'required');
        } else if (this.value === 'alipay') {
            alipayDetails.style.display = 'block';
            if (alipayQRCount === 0) {
                addAlipayQR(true); // First one is required
                addAlipayQR(false); // Second one is optional
            }
            // Attach event listeners to existing inputs
            setTimeout(() => {
                document.querySelectorAll('#alipay_qr_container input.qr-amount-input').forEach(input => {
                    input.addEventListener('input', validateQRCodeAmounts);
                });
                validateQRCodeAmounts();
            }, 100);
        } else if (this.value === 'wechat_pay') {
            wechatDetails.style.display = 'block';
            if (wechatQRCount === 0) {
                addWeChatQR(true); // First one is required
                addWeChatQR(false); // Second one is optional
            }
            // Attach event listeners to existing inputs
            setTimeout(() => {
                document.querySelectorAll('#wechat_qr_container input.qr-amount-input').forEach(input => {
                    input.addEventListener('input', validateQRCodeAmounts);
                });
                validateQRCodeAmounts();
            }, 100);
        } else if (this.value === 'in_person') {
            namePhoneFields.style.display = 'block';
            inPersonDetails.style.display = 'block';
            if (nameInput) nameInput.setAttribute('required', 'required');
            if (phoneInput) phoneInput.setAttribute('required', 'required');
        }
    });
}

// Calculate amounts
function calculateAmounts() {
    const amountGHS = parseFloat(document.getElementById('amount_ghs').value) || 0;
    const amountCNY = amountGHS * exchangeRate;
    
    // Update CNY amount display
    document.getElementById('amount_cny').value = '¥ ' + amountCNY.toFixed(2);
    document.getElementById('recipient_equivalent').textContent = 
        'Recipient will receive approximately ¥ ' + amountCNY.toFixed(2);
    
    // Validate QR code amounts after amount changes
    validateQRCodeAmounts();
}

// Validate QR code amounts against recipient amount
function validateQRCodeAmounts() {
    const recipientType = document.getElementById('recipient_type').value;
    const amountGHS = parseFloat(document.getElementById('amount_ghs').value) || 0;
    const amountCNY = amountGHS * exchangeRate;
    
    // Only validate for Alipay and WeChat
    if (recipientType !== 'alipay' && recipientType !== 'wechat_pay') {
        document.getElementById('alipay_qr_validation').style.display = 'none';
        document.getElementById('wechat_qr_validation').style.display = 'none';
        return;
    }
    
    let totalQRAmount = 0;
    let validationDiv = null;
    
    if (recipientType === 'alipay') {
        // Sum all Alipay QR code amounts
        const alipayInputs = document.querySelectorAll('#alipay_qr_container input.qr-amount-input');
        alipayInputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            totalQRAmount += value;
        });
        validationDiv = document.getElementById('alipay_qr_validation');
        document.getElementById('wechat_qr_validation').style.display = 'none';
    } else if (recipientType === 'wechat_pay') {
        // Sum all WeChat QR code amounts
        const wechatInputs = document.querySelectorAll('#wechat_qr_container input.qr-amount-input');
        wechatInputs.forEach(input => {
            const value = parseFloat(input.value) || 0;
            totalQRAmount += value;
        });
        validationDiv = document.getElementById('wechat_qr_validation');
        document.getElementById('alipay_qr_validation').style.display = 'none';
    }
    
    if (!validationDiv) return;
    
    // Calculate difference
    const difference = totalQRAmount - amountCNY;
    const tolerance = 0.01; // Allow 0.01 CNY difference for rounding
    
    if (totalQRAmount === 0) {
        // No amounts entered yet
        validationDiv.style.display = 'none';
        return;
    }
    
    if (Math.abs(difference) <= tolerance) {
        // Amounts match (within tolerance)
        validationDiv.innerHTML = `
            <div class="alert alert-success mb-0">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Amounts Match!</strong> Total QR code amounts (¥ ${totalQRAmount.toFixed(2)}) equals recipient amount (¥ ${amountCNY.toFixed(2)}).
            </div>
        `;
        validationDiv.style.display = 'block';
    } else if (difference > tolerance) {
        // QR code amounts exceed recipient amount
        validationDiv.innerHTML = `
            <div class="alert alert-danger mb-0">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Amount Mismatch!</strong> Total QR code amounts (¥ ${totalQRAmount.toFixed(2)}) exceeds recipient amount (¥ ${amountCNY.toFixed(2)}) by ¥ ${Math.abs(difference).toFixed(2)}.
                <br><small>Please adjust the QR code amounts to match the recipient amount.</small>
            </div>
        `;
        validationDiv.style.display = 'block';
    } else {
        // QR code amounts are less than recipient amount
        validationDiv.innerHTML = `
            <div class="alert alert-warning mb-0">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Amount Mismatch!</strong> Total QR code amounts (¥ ${totalQRAmount.toFixed(2)}) is less than recipient amount (¥ ${amountCNY.toFixed(2)}) by ¥ ${Math.abs(difference).toFixed(2)}.
                <br><small>Please adjust the QR code amounts to match the recipient amount.</small>
            </div>
        `;
        validationDiv.style.display = 'block';
    }
}

function incrementAmount() {
    const input = document.getElementById('amount_ghs');
    const current = parseFloat(input.value) || 0;
    const newValue = current + 10;
    input.value = newValue.toFixed(2);
    calculateAmounts();
}

function decrementAmount() {
    const input = document.getElementById('amount_ghs');
    const current = parseFloat(input.value) || 0;
    if (current >= 10) {
        const newValue = current - 10;
        input.value = newValue.toFixed(2);
        calculateAmounts();
    }
}

// Initialize - ensure calculation runs on page load and on any input change
document.addEventListener('DOMContentLoaded', function() {
    const amountInput = document.getElementById('amount_ghs');
    if (amountInput) {
        // Add multiple event listeners to ensure calculation happens
        amountInput.addEventListener('input', calculateAmounts);
        amountInput.addEventListener('change', calculateAmounts);
        amountInput.addEventListener('keyup', calculateAmounts);
        
        // Run calculation on page load
        calculateAmounts();
    }
});
</script>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Send Money Transfer - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/user-layout.php';
?>
