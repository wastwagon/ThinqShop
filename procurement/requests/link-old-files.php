<?php
/**
 * Link Files to Old Procurement Requests
 * This script helps link uploaded files to old requests that don't have files in the database
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../../../includes/admin-auth-check.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../../includes/functions.php';

$db = new Database();
$conn = $db->getConnection();

// Get all requests without files
$stmt = $conn->query("
    SELECT id, request_number, created_at 
    FROM procurement_requests 
    WHERE reference_images IS NULL OR reference_images = '' 
    ORDER BY id ASC
");
$requestsWithoutFiles = $stmt->fetchAll();

$uploadPath = UPLOAD_PATH;
$linkedCount = 0;
$linkedRequests = [];

// Process each request
foreach ($requestsWithoutFiles as $request) {
    $requestDate = date('Y-m-d', strtotime($request['created_at']));
    $requestTime = strtotime($request['created_at']);
    
    // Find files uploaded around the same time (within 5 minutes)
    $timeWindow = 300; // 5 minutes in seconds
    $files = glob($uploadPath . '*');
    $matchingFiles = [];
    
    foreach ($files as $file) {
        if (is_file($file)) {
            $fileTime = filemtime($file);
            $timeDiff = abs($fileTime - $requestTime);
            
            // If file was created within 5 minutes of request, consider it a match
            if ($timeDiff <= $timeWindow) {
                $matchingFiles[] = basename($file);
            }
        }
    }
    
    // If we found matching files, link them
    if (!empty($matchingFiles)) {
        $filesJson = json_encode($matchingFiles);
        $updateStmt = $conn->prepare("UPDATE procurement_requests SET reference_images = ? WHERE id = ?");
        $updateStmt->execute([$filesJson, $request['id']]);
        
        $linkedCount++;
        $linkedRequests[] = [
            'id' => $request['id'],
            'request_number' => $request['request_number'],
            'files' => $matchingFiles
        ];
    }
}

// Prepare content for layout
ob_start();
?>
<div class="page-title-section">
    <h1 class="page-title">Link Files to Old Requests</h1>
    <div>
        <a href="<?php echo BASE_URL; ?>/admin/procurement/requests.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left"></i> Back to Requests
        </a>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">File Linking Results</h5>
            </div>
            <div class="card-body">
                <?php if ($linkedCount > 0): ?>
                    <div class="alert alert-success">
                        <strong>Success!</strong> Linked files to <?php echo $linkedCount; ?> request(s).
                    </div>
                    
                    <h6>Linked Requests:</h6>
                    <ul>
                        <?php foreach ($linkedRequests as $linked): ?>
                        <li>
                            Request #<?php echo $linked['id']; ?> - <?php echo htmlspecialchars($linked['request_number']); ?>
                            <ul>
                                <?php foreach ($linked['files'] as $file): ?>
                                <li><?php echo htmlspecialchars($file); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="alert alert-info">
                        <strong>No files found to link.</strong> All requests either already have files, or no matching files were found in the uploads directory.
                    </div>
                <?php endif; ?>
                
                <hr>
                
                <h6>Requests Without Files:</h6>
                <p>Total: <?php echo count($requestsWithoutFiles); ?> request(s)</p>
                <?php if (!empty($requestsWithoutFiles)): ?>
                <ul>
                    <?php foreach ($requestsWithoutFiles as $req): ?>
                    <li>
                        Request #<?php echo $req['id']; ?> - <?php echo htmlspecialchars($req['request_number']); ?> 
                        (Created: <?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?>)
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Set page title and include layout
$pageTitle = 'Link Files to Old Requests - Admin - ' . APP_NAME;
include __DIR__ . '/../../../includes/layouts/admin-layout.php';
?>

