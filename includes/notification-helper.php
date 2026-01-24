<?php
/**
 * Notification Helper Functions
 * ThinQShopping Platform
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email-service.php';

class NotificationHelper {
    private static $db;
    private static $conn;
    
    /**
     * Initialize
     */
    private static function init() {
        if (!self::$conn) {
            self::$db = new Database();
            self::$conn = self::$db->getConnection();
        }
    }
    
    /**
     * Create user notification
     */
    public static function createUserNotification($userId, $type, $title, $message, $link = null) {
        self::init();
        
        try {
            $stmt = self::$conn->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $type, $title, $message, $link]);
            return true;
        } catch (Exception $e) {
            error_log("Failed to create notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create admin notification
     */
    public static function createAdminNotification($adminId, $type, $title, $message, $link = null) {
        self::init();
        
        try {
            $stmt = self::$conn->prepare("
                INSERT INTO admin_notifications (admin_id, type, title, message, link, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$adminId, $type, $title, $message, $link]);
            return true;
        } catch (Exception $e) {
            error_log("Failed to create admin notification: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create notification for all admins
     */
    public static function notifyAllAdmins($type, $title, $message, $link = null) {
        self::init();
        
        try {
            $stmt = self::$conn->prepare("SELECT id FROM admin_users WHERE is_active = 1");
            $stmt->execute();
            $admins = $stmt->fetchAll();
            
            foreach ($admins as $admin) {
                self::createAdminNotification($admin['id'], $type, $title, $message, $link);
            }
            return true;
        } catch (Exception $e) {
            error_log("Failed to notify admins: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send notification with email
     */
    public static function sendNotification($type, $userId = null, $adminId = null, $email = null, $variables = []) {
        self::init();
        
        // Check notification settings
        $stmt = self::$conn->prepare("SELECT * FROM notification_settings WHERE notification_type = ?");
        $stmt->execute([$type]);
        $setting = $stmt->fetch();
        
        if (!$setting) {
            return false;
        }
        
        // Create in-app notification
        if ($setting['send_in_app']) {
            if ($userId && $setting['send_to_user']) {
                $title = $variables['title'] ?? ucfirst(str_replace('_', ' ', $type));
                $message = $variables['message'] ?? '';
                $link = $variables['link'] ?? null;
                self::createUserNotification($userId, $type, $title, $message, $link);
            }
            
            if ($adminId && $setting['send_to_admin']) {
                $title = $variables['admin_title'] ?? $variables['title'] ?? ucfirst(str_replace('_', ' ', $type));
                $message = $variables['admin_message'] ?? $variables['message'] ?? '';
                $link = $variables['link'] ?? null;
                self::createAdminNotification($adminId, $type, $title, $message, $link);
            }
            
            // Notify all admins if no specific admin
            if (!$adminId && $setting['send_to_admin']) {
                $title = $variables['admin_title'] ?? $variables['title'] ?? ucfirst(str_replace('_', ' ', $type));
                $message = $variables['admin_message'] ?? $variables['message'] ?? '';
                $link = $variables['link'] ?? null;
                self::notifyAllAdmins($type, $title, $message, $link);
            }
        }
        
        // Send email notification
        if ($setting['send_email'] && $email) {
            try {
                // Check if EmailService has sendNotification method
                if (method_exists('EmailService', 'sendNotification')) {
                    $isAdmin = !empty($adminId);
                    EmailService::sendNotification($type, $email, $userId ?? $adminId, $variables, $isAdmin);
                } else {
                    // Fallback: use the send() method if sendNotification doesn't exist
                    // This is a silent failure - we don't want to break the flow
                    error_log("EmailService::sendNotification() method not found. Email notification skipped.");
                }
            } catch (Exception $e) {
                // Log error but don't break the flow
                error_log("Failed to send email notification: " . $e->getMessage());
            }
        }
        
        return true;
    }
    
    /**
     * Get unread notification count for user
     */
    public static function getUnreadCount($userId) {
        self::init();
        
        $stmt = self::$conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Get unread notification count for admin
     */
    public static function getAdminUnreadCount($adminId = null) {
        self::init();
        
        if ($adminId) {
            $stmt = self::$conn->prepare("SELECT COUNT(*) as count FROM admin_notifications WHERE admin_id = ? AND is_read = 0");
            $stmt->execute([$adminId]);
        } else {
            $stmt = self::$conn->query("SELECT COUNT(*) as count FROM admin_notifications WHERE is_read = 0");
        }
        
        $result = $stmt->fetch();
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Mark notification as read
     */
    public static function markAsRead($notificationId, $isAdmin = false) {
        self::init();
        
        $table = $isAdmin ? 'admin_notifications' : 'notifications';
        $stmt = self::$conn->prepare("UPDATE {$table} SET is_read = 1 WHERE id = ?");
        return $stmt->execute([$notificationId]);
    }
    
    /**
     * Mark all notifications as read
     */
    public static function markAllAsRead($userId = null, $adminId = null) {
        self::init();
        
        if ($userId) {
            $stmt = self::$conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } elseif ($adminId) {
            $stmt = self::$conn->prepare("UPDATE admin_notifications SET is_read = 1 WHERE admin_id = ?");
            return $stmt->execute([$adminId]);
        }
        
        return false;
    }
    
    /**
     * Get user notifications
     */
    public static function getUserNotifications($userId, $limit = 20, $unreadOnly = false) {
        self::init();
        
        $sql = "SELECT * FROM notifications WHERE user_id = ?";
        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        
        $stmt = self::$conn->prepare($sql);
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get admin notifications
     */
    public static function getAdminNotifications($adminId = null, $limit = 20, $unreadOnly = false) {
        self::init();
        
        if ($adminId) {
            $sql = "SELECT * FROM admin_notifications WHERE admin_id = ?";
            if ($unreadOnly) {
                $sql .= " AND is_read = 0";
            }
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $stmt = self::$conn->prepare($sql);
            $stmt->execute([$adminId, $limit]);
        } else {
            $sql = "SELECT * FROM admin_notifications";
            if ($unreadOnly) {
                $sql .= " WHERE is_read = 0";
            }
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $stmt = self::$conn->prepare($sql);
            $stmt->execute([$limit]);
        }
        
        return $stmt->fetchAll();
    }
}




