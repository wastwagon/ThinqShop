<?php
/**
 * Reject Procurement Quote
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
error_log("Reject Quote - Request Method: " . $_SERVER['REQUEST_METHOD']);
error_log("Reject Quote - POST data: " . print_r($_POST, true));

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("Reject Quote: Invalid request method - " . $_SERVER['REQUEST_METHOD']);
    // If accessed via GET, redirect back to quote view
    $quoteId = intval($_GET['quote_id'] ?? 0);
    if ($quoteId > 0) {
        redirect('/user/procurement/quotes/view.php?id=' . $quoteId, 'Please use the form to reject the quote.', 'warning');
    }
    redirect('/user/procurement/', 'Invalid request method. Please use the form to reject quotes.', 'danger');
}

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
    redirect('/user/procurement/', 'Invalid security token.', 'danger');
}

// Get quote ID
$quoteId = intval($_POST['quote_id'] ?? 0);

if ($quoteId <= 0) {
    redirect('/user/procurement/', 'Invalid quote ID.', 'danger');
}

try {
    // Verify the quote belongs to the user's request
    $stmt = $conn->prepare("
        SELECT pq.*, pr.id as request_id, pr.user_id, pr.status as request_status
        FROM procurement_quotes pq
        LEFT JOIN procurement_requests pr ON pq.request_id = pr.id
        WHERE pq.id = ? AND pr.user_id = ?
    ");
    $stmt->execute([$quoteId, $userId]);
    $quote = $stmt->fetch();

    if (!$quote) {
        redirect('/user/procurement/', 'Quote not found or you do not have permission to reject it.', 'danger');
    }

    // Check if quote is still pending
    if ($quote['status'] !== 'pending') {
        redirect('/user/procurement/view.php?id=' . $quote['request_id'], 'This quote has already been ' . $quote['status'] . '.', 'warning');
    }

    // Update quote status to rejected
    $stmt = $conn->prepare("UPDATE procurement_quotes SET status = 'rejected', updated_at = NOW() WHERE id = ?");
    $stmt->execute([$quoteId]);

    // Check if there are any other pending quotes for this request
    $stmt = $conn->prepare("
        SELECT COUNT(*) as pending_count
        FROM procurement_quotes
        WHERE request_id = ? AND status = 'pending'
    ");
    $stmt->execute([$quote['request_id']]);
    $result = $stmt->fetch();

    // If no pending quotes remain, update request status back to submitted
    if ($result['pending_count'] == 0) {
        $stmt = $conn->prepare("UPDATE procurement_requests SET status = 'submitted', updated_at = NOW() WHERE id = ?");
        $stmt->execute([$quote['request_id']]);
    }

    // Redirect with success message
    redirect('/user/procurement/view.php?id=' . $quote['request_id'], 'Quote rejected successfully.', 'success');

} catch (Exception $e) {
    error_log("Reject Quote Error: " . $e->getMessage());
    $requestId = isset($quote) && isset($quote['request_id']) ? $quote['request_id'] : 0;
    if ($requestId > 0) {
        redirect('/user/procurement/view.php?id=' . $requestId, 'Failed to reject quote. Please try again.', 'danger');
    } else {
        redirect('/user/procurement/', 'Failed to reject quote. Please try again.', 'danger');
    }
}

