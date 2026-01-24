<?php
/**
 * Mark All Notifications as Read API
 * ThinQShopping Platform
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../../config/constants.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_start();
}

$isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'true';

$db = new Database();
$conn = $db->getConnection();

try {
    if ($isAdmin) {
        if (!isset($_SESSION['admin_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        $adminId = $_SESSION['admin_id'];
        $stmt = $conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE admin_id = ?");
        $stmt->execute([$adminId]);
        echo json_encode(['success' => true]);
    } else {
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated']);
            exit;
        }
        $userId = $_SESSION['user_id'];
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->execute([$userId]);
        echo json_encode(['success' => true]);
    }
} catch (Exception $e) {
    error_log("Mark all notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update notifications']);
}

