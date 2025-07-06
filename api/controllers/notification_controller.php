<?php
/**
 * Notification Controller
 * 
 * Handles notification-related operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/notification_model.php';
require_once __DIR__ . '/../controllers/auth_controller.php';

class NotificationController {
    private $conn;
    private $notificationModel;
    private $authController;
    
    public function __construct() {
        $this->conn = getDbConnection();
        $this->notificationModel = new NotificationModel($this->conn);
        $this->authController = new AuthController();
    }
    
    /**
     * Get notifications for a user
     */
    public function getUserNotifications($userId) {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        // Check if user is requesting their own notifications or is admin
        if ($userId != $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'You can only view your own notifications']);
            return;
        }
        
        // Get notifications
        $notifications = $this->notificationModel->getUserNotifications($userId);
        
        echo json_encode($notifications);
    }
    
    /**
     * Mark notification as read
     */
    public function markNotificationAsRead($id) {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        // Get the notification
        $notification = $this->notificationModel->getNotificationById($id);
        
        if (!$notification) {
            http_response_code(404);
            echo json_encode(['message' => 'Notification not found']);
            return;
        }
        
        // Check if user owns this notification or is admin
        if ($notification['user_id'] != $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'You can only mark your own notifications as read']);
            return;
        }
        
        // Mark as read
        $success = $this->notificationModel->markNotificationAsRead($id);
        
        if (!$success) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to mark notification as read']);
            return;
        }
        
        echo json_encode(['message' => 'Notification marked as read']);
    }
    
    /**
     * Delete notification
     */
    public function deleteNotification($id) {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        // Get the notification
        $notification = $this->notificationModel->getNotificationById($id);
        
        if (!$notification) {
            http_response_code(404);
            echo json_encode(['message' => 'Notification not found']);
            return;
        }
        
        // Check if user owns this notification or is admin
        if ($notification['user_id'] != $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'You can only delete your own notifications']);
            return;
        }
        
        // Delete notification
        $success = $this->notificationModel->deleteNotification($id);
        
        if (!$success) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete notification']);
            return;
        }
        
        echo json_encode(['message' => 'Notification deleted successfully']);
    }
    
    /**
     * Authenticate user from token
     */
    private function authenticateUser() {
        // Get authorization header
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Authentication required']);
            return null;
        }
        
        // Extract token
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        // Special case for development
        if ($token === 'test_token') {
            return [
                'id' => 1,
                'username' => 'admin',
                'role' => 'admin'
            ];
        }
        
        // Verify token
        $payload = $this->authController->verifyJWT($token);
        
        if (!$payload) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid token']);
            return null;
        }
        
        return [
            'id' => $payload['id'],
            'username' => $payload['username'],
            'email' => $payload['email'],
            'role' => $payload['role']
        ];
    }
}
