<?php
/**
 * Notification Model
 * 
 * Handles database operations for notifications
 */

class NotificationModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get notification by ID
     */
    public function getNotificationById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Create a new notification
     * If user_id is null, notification is for all admins
     */
    public function createNotification($user_id, $title, $message) {
        if ($user_id === null) {
            // Create notifications for all admins
            $admins = $this->getAllAdmins();
            
            foreach ($admins as $admin) {
                $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $stmt->bind_param("iss", $admin['id'], $title, $message);
                $stmt->execute();
            }
            
            return true;
        } else {
            // Create notification for specific user
            $stmt = $this->conn->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $user_id, $title, $message);
            
            if ($stmt->execute()) {
                return $this->conn->insert_id;
            }
            
            return false;
        }
    }
    
    /**
     * Get notifications for a user
     */
    public function getUserNotifications($user_id) {
        $stmt = $this->conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $notifications;
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($id) {
        $stmt = $this->conn->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($id) {
        $stmt = $this->conn->prepare("DELETE FROM notifications WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    /**
     * Get all admin users
     */
    private function getAllAdmins() {
        $result = $this->conn->query("SELECT id FROM users WHERE role = 'admin'");
        
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        
        return $admins;
    }
}
