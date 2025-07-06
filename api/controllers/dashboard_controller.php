<?php
/**
 * Dashboard Controller
 * 
 * Handles dashboard statistics
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../controllers/auth_controller.php';

class DashboardController {
    private $conn;
    private $authController;
    
    public function __construct() {
        $this->conn = getDbConnection();
        $this->authController = new AuthController();
    }
    
    /**
     * Get dashboard statistics
     */
    public function getStats() {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        try {
            // Get books statistics
            $booksStatsQuery = "SELECT COUNT(*) as total_books, SUM(available_copies) as available_books FROM books";
            $booksStatsResult = $this->conn->query($booksStatsQuery);
            $booksStats = $booksStatsResult->fetch_assoc();
            
            // Get circulation statistics
            $circulationStatsQuery = "SELECT COUNT(*) as borrowed_books FROM circulation WHERE action = 'borrow' AND returned = false";
            $circulationStatsResult = $this->conn->query($circulationStatsQuery);
            $circulationStats = $circulationStatsResult->fetch_assoc();
            
            // Get recent notifications
            $notificationsQuery = "SELECT id, title, message, created_at FROM notifications ORDER BY created_at DESC LIMIT 5";
            $notificationsResult = $this->conn->query($notificationsQuery);
            
            $notifications = [];
            while ($row = $notificationsResult->fetch_assoc()) {
                $notifications[] = [
                    'id' => $row['id'],
                    'title' => $row['title'],
                    'message' => $row['message'],
                    'date' => $row['created_at']
                ];
            }
            
            echo json_encode([
                'totalBooks' => (int)$booksStats['total_books'] ?? 0,
                'availableBooks' => (int)$booksStats['available_books'] ?? 0,
                'borrowedBooks' => (int)$circulationStats['borrowed_books'] ?? 0,
                'notifications' => $notifications
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => 'Error fetching dashboard statistics: ' . $e->getMessage()]);
        }
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
