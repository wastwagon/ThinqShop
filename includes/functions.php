<?php
/**
 * Core Functions
 * ThinQShopping Platform
 */

// Prevent direct access
if (!defined('BASE_PATH')) {
    require_once __DIR__ . '/../config/constants.php';
}

/**
 * Get asset URL
 */
function asset($path) {
    $path = ltrim($path, '/');
    return BASE_URL . '/' . $path;
}

/**
 * Get image URL
 */
function imageUrl($path, $width = null, $height = null, $quality = 80) {
    // If it's an external URL (picsum, unsplash, etc), return as is or with dimensions
    if (strpos($path, 'http') === 0 || strpos($path, '//') === 0) {
        if ($width && $height) {
            // Update external URL dimensions if provided
            if (strpos($path, 'picsum.photos') !== false) {
                // Update Picsum URL dimensions
                $path = preg_replace('/\/\d+\/\d+/', "/{$width}/{$height}", $path);
            } elseif (strpos($path, 'unsplash.com') !== false) {
                // Update Unsplash URL dimensions if still using old format
                $path = preg_replace('/\/\d+x\d+\//', "/{$width}x{$height}/", $path);
            }
        }
        return $path;
    }
    
    // Handle local images
    // Only return placeholder if path is explicitly default or doesn't exist
    if (empty($path)) {
        return "https://via.placeholder.com/" . ($width ?: 400) . "x" . ($height ?: 400) . "?text=Product";
    }
    
    // Fix path if it's just a filename (doesn't start with assets/ or http)
    // This handles cases where images are stored as just filenames in the database
    if (strpos($path, 'assets/') !== 0 && strpos($path, 'http') !== 0 && strpos($path, '//') !== 0 && strpos($path, '/') !== 0) {
        // Assume it's a product image if it's just a filename
        $path = 'assets/images/products/' . $path;
    }
    
    // Check if file exists for local paths
    if (strpos($path, 'http') !== 0 && strpos($path, '//') !== 0) {
        $fullPath = BASE_PATH . '/' . ltrim($path, '/');
        
        // If file doesn't exist, check what to do
        if (!file_exists($fullPath)) {
            // Only use placeholder service if path explicitly contains 'default' or 'placeholder' 
            // AND it's not a real file (like placeholder-product.jpg which might be a real file)
            // Check if it's the actual default.jpg file
            if ($path === 'assets/images/products/default.jpg' || 
                (strpos($path, '/default.jpg') !== false && !file_exists($fullPath))) {
                return "https://via.placeholder.com/" . ($width ?: 400) . "x" . ($height ?: 400) . "?text=Product";
            }
            // For other files (including placeholder-product.jpg if it exists), return the URL
            // The browser will handle it - if file doesn't exist, it shows broken image
        }
        
        // File exists or we'll try anyway, return the actual image URL
        // Return direct URL (no query parameters for local images)
        return asset($path);
    }
    
    // External URL - return as is
    return asset($path);
}

/**
 * Get product image from Unsplash based on product name
 * Uses deterministic seed based on product name for consistent images
 */
function getProductImageUrl($productName, $width = 400, $height = 400) {
    // Generate a seed from product name for consistent image
    $seed = crc32(strtolower(trim($productName)));
    // Use Unsplash Images API with seed for deterministic results
    // Format: https://picsum.photos/seed/{seed}/{width}/{height}
    // This provides consistent images per product name
    return "https://picsum.photos/seed/{$seed}/{$width}/{$height}";
}

/**
 * Format currency (GHS)
 */
function formatCurrency($amount) {
    return CURRENCY_SYMBOL . number_format($amount, 2);
}

/**
 * Get product average rating and review count
 */
function getProductRating($productId, $conn) {
    $stmt = $conn->prepare("
        SELECT 
            AVG(rating) as avg_rating,
            COUNT(*) as review_count
        FROM product_reviews 
        WHERE product_id = ? AND is_approved = 1
    ");
    $stmt->execute([$productId]);
    $result = $stmt->fetch();
    
    return [
        'rating' => $result ? round($result['avg_rating'], 1) : 0,
        'count' => $result ? (int)$result['review_count'] : 0
    ];
}

/**
 * Render star rating display
 */
function renderStars($rating, $size = 'fa-sm') {
    $html = '';
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5;
    
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $fullStars) {
            $html .= '<i class="fas fa-star text-warning ' . $size . '"></i>';
        } elseif ($i == $fullStars + 1 && $halfStar) {
            $html .= '<i class="fas fa-star-half-alt text-warning ' . $size . '"></i>';
        } else {
            $html .= '<i class="far fa-star text-muted ' . $size . '"></i>';
        }
    }
    
    return $html;
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Sanitize input
 */
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect with message
 */
function redirect($url, $message = null, $type = 'success') {
    if ($message) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $type;
    }
    
    // Ensure URL starts with / and BASE_URL doesn't have trailing slash
    $baseUrl = rtrim(BASE_URL, '/');
    $url = '/' . ltrim($url, '/');
    
    // Remove any quotes that might be in BASE_URL
    $baseUrl = trim($baseUrl, '"\'');
    
    header('Location: ' . $baseUrl . $url);
    exit;
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}

/**
 * Generate unique order number
 */
function generateOrderNumber($prefix = 'ORD') {
    return $prefix . '-' . date('Ymd') . '-' . strtoupper(uniqid());
}

/**
 * Generate transfer token
 */
function generateTransferToken($type = 'GH2CHN') {
    $year = date('Y');
    $random = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    return $type . '-' . $year . '-' . $random;
}

/**
 * Generate tracking number
 */
function generateTrackingNumber($prefix = 'TRK') {
    return $prefix . '-' . date('Ymd') . strtoupper(uniqid());
}

/**
 * Generate unique user identifier
 * Format: TQ-[FirstName]-[4 digit code]
 * Example: TQ-John-1234
 * 
 * @param string $firstName User's first name
 * @param PDO $conn Database connection (optional, will create if not provided)
 * @return string Unique user identifier
 */
function generateUserIdentifier($firstName, $conn = null) {
    // Normalize first name
    // 1. Convert to title case
    $firstName = ucwords(strtolower(trim($firstName)));
    
    // 2. Remove spaces
    $firstName = str_replace(' ', '', $firstName);
    
    // 3. Remove special characters and accents
    $firstName = removeAccents($firstName);
    $firstName = preg_replace('/[^a-zA-Z0-9]/', '', $firstName);
    
    // 4. Ensure it's not empty (fallback to "User" if needed)
    if (empty($firstName)) {
        $firstName = 'User';
    }
    
    // 5. Limit length to reasonable size (e.g., 20 characters)
    $firstName = substr($firstName, 0, 20);
    
    // Get database connection if not provided
    if ($conn === null) {
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
    }
    
    // Generate unique 4-digit code
    $maxAttempts = 100; // Prevent infinite loop
    $attempts = 0;
    
    do {
        // Generate random 4-digit code (0000-9999)
        $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        
        // Create identifier
        $identifier = 'TQ-' . $firstName . '-' . $code;
        
        // Check if it already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE user_identifier = ?");
        $stmt->execute([$identifier]);
        $exists = $stmt->fetch();
        
        $attempts++;
        
        // If not found, it's unique
        if (!$exists) {
            return $identifier;
        }
        
    } while ($attempts < $maxAttempts);
    
    // Fallback: use timestamp if all attempts failed (very unlikely)
    $code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
    $timestamp = substr(time(), -4);
    return 'TQ-' . $firstName . '-' . $code . $timestamp;
}

/**
 * Remove accents from string
 * 
 * @param string $string String with accents
 * @return string String without accents
 */
function removeAccents($string) {
    $accents = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ç' => 'C', 'ç' => 'c',
        'Ñ' => 'N', 'ñ' => 'n',
        'Ý' => 'Y', 'ý' => 'y', 'ÿ' => 'y',
    ];
    
    return strtr($string, $accents);
}

/**
 * Get current user
 */
function getCurrentUser() {
    if (isset($_SESSION['user_id'])) {
        require_once __DIR__ . '/../config/database.php';
        $db = new Database();
        $conn = $db->getConnection();
        
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND is_active = 1");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    return null;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['admin_id']);
}

/**
 * Get user wallet balance
 */
function getUserWalletBalance($userId) {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("SELECT balance_ghs FROM user_wallets WHERE user_id = ?");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    
    return $result ? floatval($result['balance_ghs']) : 0.00;
}

/**
 * Log admin action
 */
function logAdminAction($adminId, $action, $tableName = null, $recordId = null, $details = null) {
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    $stmt = $conn->prepare("INSERT INTO admin_logs (admin_id, action, table_name, record_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
    $detailsJson = $details ? json_encode($details) : null;
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt->execute([$adminId, $action, $tableName, $recordId, $detailsJson, $ipAddress]);
}

/**
 * Time ago function
 */
function timeAgo($datetime) {
    // Ensure we have a valid datetime string
    if (empty($datetime)) {
        return 'just now';
    }
    
    // Create DateTime object from the database timestamp
    // MySQL timestamps are typically in 'Y-m-d H:i:s' format
    // Try to parse it, handling both with and without timezone info
    try {
        // Try to create DateTime, assuming UTC if no timezone specified
        if (strpos($datetime, '+') === false && strpos($datetime, 'Z') === false && strpos($datetime, 'T') === false) {
            // No timezone info, assume UTC (database stores in UTC)
            $notificationTime = new DateTime($datetime, new DateTimeZone('UTC'));
        } else {
            $notificationTime = new DateTime($datetime);
        }
    } catch (Exception $e) {
        // If parsing fails, try with strtotime as fallback
        $timestamp = strtotime($datetime);
        if ($timestamp === false) {
            return 'just now';
        }
        $notificationTime = new DateTime('@' . $timestamp);
    }
    
    // Get current time (will use server's default timezone, but we compare timestamps so it's fine)
    $currentTime = new DateTime();
    
    // Calculate the difference in seconds
    $diff = $currentTime->getTimestamp() - $notificationTime->getTimestamp();
    
    // Handle negative differences (shouldn't happen, but just in case)
    if ($diff < 0) {
        return 'just now';
    }
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) {
        $minutes = floor($diff/60);
        return $minutes . ' minute' . ($minutes == 1 ? '' : 's') . ' ago';
    }
    if ($diff < 86400) {
        $hours = floor($diff/3600);
        return $hours . ' hour' . ($hours == 1 ? '' : 's') . ' ago';
    }
    if ($diff < 604800) {
        $days = floor($diff/86400);
        return $days . ' day' . ($days == 1 ? '' : 's') . ' ago';
    }
    if ($diff < 2592000) {
        $weeks = floor($diff/604800);
        return $weeks . ' week' . ($weeks == 1 ? '' : 's') . ' ago';
    }
    if ($diff < 31536000) {
        $months = floor($diff/2592000);
        return $months . ' month' . ($months == 1 ? '' : 's') . ' ago';
    }
    $years = floor($diff/31536000);
    return $years . ' year' . ($years == 1 ? '' : 's') . ' ago';
}

/**
 * Validate email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone (Ghana format)
 */
function isValidPhone($phone) {
    // Remove spaces and special characters
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    // Ghana phone: +233XXXXXXXXX or 0XXXXXXXXX
    return preg_match('/^(\+233|0)[0-9]{9}$/', $phone);
}

/**
 * Format phone number
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (strpos($phone, '0') === 0) {
        return '+233' . substr($phone, 1);
    }
    return $phone;
}

/**
 * Pagination helper
 */
function paginate($currentPage, $totalPages, $baseUrl) {
    $html = '<nav aria-label="Page navigation"><ul class="pagination">';
    
    // Previous
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '">Previous</a></li>';
    }
    
    // Page numbers
    for ($i = 1; $i <= $totalPages; $i++) {
        $active = $i == $currentPage ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a></li>';
    }
    
    // Next
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Next</a></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Upload image
 */
function uploadImage($file, $destination, $maxSize = null) {
    if (!defined('UPLOAD_MAX_SIZE')) {
        define('UPLOAD_MAX_SIZE', 5242880); // 5MB default
    }
    if (!defined('UPLOAD_ALLOWED_TYPES')) {
        define('UPLOAD_ALLOWED_TYPES', ['jpg', 'jpeg', 'png', 'webp', 'gif']);
    }
    
    if ($maxSize === null) {
        $maxSize = UPLOAD_MAX_SIZE;
    }
    
    // Check upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
        ];
        return ['success' => false, 'message' => $errorMessages[$file['error']] ?? 'Upload error: ' . $file['error']];
    }
    
    // Check file size
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'File too large. Maximum size: ' . round($maxSize / 1024 / 1024, 2) . 'MB'];
    }
    
    // Check file type
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = is_array(UPLOAD_ALLOWED_TYPES) ? UPLOAD_ALLOWED_TYPES : explode(',', UPLOAD_ALLOWED_TYPES);
    if (!in_array($ext, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowedTypes)];
    }
    
    // Validate it's actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => 'File is not a valid image'];
    }
    
    // Generate unique filename
    $filename = uniqid('profile_', true) . '.' . $ext;
    
    // Ensure destination ends with slash
    $destination = rtrim($destination, '/') . '/';
    $filepath = $destination . $filename;
    
    // Create directory if it doesn't exist
    if (!is_dir($destination)) {
        if (!mkdir($destination, 0755, true)) {
            return ['success' => false, 'message' => 'Failed to create upload directory'];
        }
    }
    
    // Check if directory is writable
    if (!is_writable($destination)) {
        return ['success' => false, 'message' => 'Upload directory is not writable'];
    }
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $filepath];
    }
    
    return ['success' => false, 'message' => 'Failed to move uploaded file'];
}

/**
 * Detect if user is on a mobile device
 * @return bool
 */
function isMobileDevice() {
    if (!isset($_SERVER['HTTP_USER_AGENT'])) {
        return false;
    }
    
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    // Common mobile device patterns
    $mobilePatterns = [
        '/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini|Windows Phone|webOS/i',
        '/Mobile/i'
    ];
    
    foreach ($mobilePatterns as $pattern) {
        if (preg_match($pattern, $userAgent)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Migrate guest cart items to user account
 */
function migrateGuestCart($userId, $sessionId) {
    if (empty($userId) || empty($sessionId)) {
        return;
    }
    
    // Check if both exist
    require_once __DIR__ . '/../config/database.php';
    $db = new Database();
    $conn = $db->getConnection();
    
    // Find all guest items
    $stmt = $conn->prepare("SELECT * FROM cart WHERE session_id = ?");
    $stmt->execute([$sessionId]);
    $guestItems = $stmt->fetchAll();
    
    if (empty($guestItems)) {
        return;
    }
    
    foreach ($guestItems as $item) {
        // Check if user already has this product/variant in cart
        $stmt = $conn->prepare("
            SELECT id, quantity FROM cart 
            WHERE user_id = ? AND product_id = ? AND variant_id " . ($item['variant_id'] ? "= ?" : "IS NULL")
        );
        if ($item['variant_id']) {
            $stmt->execute([$userId, $item['product_id'], $item['variant_id']]);
        } else {
            $stmt->execute([$userId, $item['product_id']]);
        }
        $existing = $stmt->fetch();
        
        if ($existing) {
            // Merge quantities
            $newQty = $existing['quantity'] + $item['quantity'];
            $stmt = $conn->prepare("UPDATE cart SET quantity = ?, session_id = NULL WHERE id = ?");
            $stmt->execute([$newQty, $existing['id']]);
            
            // Delete the guest item
            $stmt = $conn->prepare("DELETE FROM cart WHERE id = ?");
            $stmt->execute([$item['id']]);
        } else {
            // Transfer to user
            $stmt = $conn->prepare("UPDATE cart SET user_id = ?, session_id = NULL WHERE id = ?");
            $stmt->execute([$userId, $item['id']]);
        }
    }
}

