<?php
/**
 * Get User Notifications API
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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$db = new Database();
$conn = $db->getConnection();
$userId = $_SESSION['user_id'];

try {
    // Check if notifications table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'notifications'");
    if ($checkTable->rowCount() === 0) {
        echo json_encode([
            'success' => true,
            'unread_count' => 0,
            'notifications' => []
        ]);
        exit;
    }
    
    // Get unread count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    $unreadCount = $stmt->fetch()['count'];
    
    // Get recent notifications (last 10)
    $stmt = $conn->prepare("
        SELECT id, type, title, message, link, is_read, created_at
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll();
    
    // Format notifications
    $formattedNotifications = [];
    foreach ($notifications as $notification) {
        $formattedNotifications[] = [
            'id' => $notification['id'],
            'type' => $notification['type'],
            'title' => $notification['title'],
            'message' => $notification['message'],
            'link' => $notification['link'],
            'is_read' => (bool)$notification['is_read'],
            'time_ago' => timeAgo($notification['created_at']),
            'created_at' => $notification['created_at']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'unread_count' => (int)$unreadCount,
        'notifications' => $formattedNotifications
    ]);
    
} catch (Exception $e) {
    error_log("Get notifications error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to fetch notifications']);
}

