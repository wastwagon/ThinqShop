<?php
/**
 * Accept Procurement Quote
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

// Debug logging
error_log("Accept Quote - Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Accept Quote - POST data: " . print_r($_POST, true));

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Accept Quote: Invalid request method - " . $_SERVER['REQUEST_METHOD']);
    // If accessed via GET, redirect back to quote view
    $quoteId = intval($_GET['quote_id'] ?? 0);
    $requestId = intval($_GET['request_id'] ?? 0);
    if ($quoteId > 0 && $requestId > 0) {
        redirect('/user/procurement/quotes/view.php?id=' . $quoteId, 'Please use the form to accept the quote.', 'warning');
    }
    redirect('/user/procurement/', 'Invalid request method. Please use the form to accept quotes.', 'danger');
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    redirect('/user/procurement/', 'Invalid security token.', 'danger');
}

// Get quote ID and request ID
$quoteId = intval($_POST['quote_id'] ?? 0);
$requestId = intval($_POST['request_id'] ?? 0);

if ($quoteId <= 0 || $requestId <= 0) {
    redirect('/user/procurement/', 'Invalid quote or request ID.', 'danger');
}

try {
    // Verify the quote belongs to the user's request
    $stmt = $conn->prepare("
        SELECT pq.*, pr.user_id, pr.status as request_status
        FROM procurement_quotes pq
        LEFT JOIN procurement_requests pr ON pq.request_id = pr.id
        WHERE pq.id = ? AND pr.id = ? AND pr.user_id = ?
    ");
    $stmt->execute([$quoteId, $requestId, $userId]);
    $quote = $stmt->fetch();

    if (!$quote) {
        redirect('/user/procurement/', 'Quote not found or you do not have permission to accept it.', 'danger');
    }

    // Check if quote is still pending
    if ($quote['status'] !== 'pending') {
        redirect('/user/procurement/view.php?id=' . $requestId, 'This quote has already been ' . $quote['status'] . '.', 'warning');
    }

    // Check if request status allows acceptance
    if ($quote['request_status'] !== 'quote_provided') {
        redirect('/user/procurement/view.php?id=' . $requestId, 'Request status does not allow quote acceptance.', 'warning');
    }

    // Start transaction
    $conn->beginTransaction();

    // Update quote status to accepted
    $stmt = $conn->prepare("UPDATE procurement_quotes SET status = 'accepted', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$quoteId]);

    // Reject all other pending quotes for this request
    $stmt = $conn->prepare("
        UPDATE procurement_quotes 
        SET status = 'rejected', updated_at = NOW() 
        WHERE request_id = ? AND id != ? AND status = 'pending'
    ");
    $stmt->execute([$requestId, $quoteId]);

    // Update request status to accepted
    $stmt = $conn->prepare("UPDATE procurement_requests SET status = 'accepted', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$requestId]);

    // Commit transaction
    $conn->commit();

    // Redirect with success message
    redirect('/user/procurement/view.php?id=' . $requestId, 'Quote accepted successfully. You can now proceed with payment.', 'success');

} catch (Exception $e) {
    // Rollback on error
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    error_log("Accept Quote Error: " . $e->getMessage());
    redirect('/user/procurement/view.php?id=' . $requestId, 'Failed to accept quote. Please try again.', 'danger');
}

