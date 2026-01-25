<?php
/**
 * Download File Handler - Admin
 * Forces file download with proper headers
 */

require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/constants.php';
require_once __DIR__ . '/../../../config/database.php';

// Get file name from request
$fileName = $_GET['file'] ?? '';
$requestId = intval($_GET['request_id'] ?? 0);

if (empty($fileName) || $requestId <= 0) {
    http_response_code(400);
    die('Invalid request. File name and request ID are required.');
}

// Sanitize filename - only allow alphanumeric, dots, dashes, and underscores
$fileName = preg_replace('/[^a-zA-Z0-9._-]/', '', $fileName);

if (empty($fileName)) {
    http_response_code(400);
    die('Invalid file name.');
}

// Verify the request exists and admin has access
$db = new Database();
$conn = $db->getConnection();

try {
    $stmt = $conn->prepare("SELECT id FROM procurement_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) {
        http_response_code(404);
        die('Request not found.');
    }
} catch (Exception $e) {
    http_response_code(500);
    die('Database error.');
}

// Construct file path
$filePath = UPLOAD_PATH . $fileName;

// Verify file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    die('File not found.');
}

// Verify file is within upload directory (security check)
$realFilePath = realpath($filePath);
$realUploadPath = realpath(UPLOAD_PATH);

if (!$realFilePath || strpos($realFilePath, $realUploadPath) !== 0) {
    http_response_code(403);
    die('Access denied.');
}

// Get file info
$fileSize = filesize($filePath);
$mimeType = mime_content_type($filePath);

// If mime type detection fails, try to determine from extension
if (!$mimeType || $mimeType === 'application/octet-stream') {
    $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $mimeTypes = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'ai' => 'application/postscript',
        'eps' => 'application/postscript'
    ];
    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
}

// Set headers to force download
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
header('Pragma: public');
header('Expires: 0');

// Disable output buffering
if (ob_get_level()) {
    ob_end_clean();
}

// Stream the file
readfile($filePath);
exit;

