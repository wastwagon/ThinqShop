<?php
/**
 * Mark Notification as Read API
 * ThinQShopping Platform
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$notificationId = intval($_POST['notification_id'] ?? 0);
$isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'true';

if ($notificationId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid notification ID']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();

try {
    if ($isAdmin && isset($_SESSION['admin_id'])) {
        // Mark admin notification as read
        $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE id = ? AND admin_id = ?");
        $stmt->execute([$notificationId, $_SESSION['admin_id']]);
    } elseif (isset($_SESSION['user_id'])) {
        // Mark user notification as read
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
        $stmt->execute([$notificationId, $_SESSION['user_id']]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Not authenticated']);
        exit;
    }
    
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    error_log("Mark notification read error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update notification']);
}

