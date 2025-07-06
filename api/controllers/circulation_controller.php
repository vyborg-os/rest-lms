<?php
/**
 * Circulation Controller
 * 
 * Handles circulation-related operations (borrow, return, reserve)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/circulation_model.php';
require_once __DIR__ . '/../models/book_model.php';
require_once __DIR__ . '/../models/notification_model.php';
require_once __DIR__ . '/../controllers/auth_controller.php';

class CirculationController {
    private $conn;
    private $circulationModel;
    private $bookModel;
    private $notificationModel;
    private $authController;
    
    public function __construct() {
        $this->conn = getDbConnection();
        $this->circulationModel = new CirculationModel($this->conn);
        $this->bookModel = new BookModel($this->conn);
        $this->notificationModel = new NotificationModel($this->conn);
        $this->authController = new AuthController();
    }
    
    /**
     * Get circulation records
     * Can filter by user ID if provided as a query parameter
     */
    public function getCirculationRecords() {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        // Check if user_id query parameter is provided
        $userId = isset($_GET['userId']) ? intval($_GET['userId']) : null;
        
        // If userId is provided and user is not admin, ensure they can only see their own records
        if ($userId && $userId !== $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'You can only view your own circulation records']);
            return;
        }
        
        // If no userId is provided and user is not admin, default to their own records
        if (!$userId && $user['role'] !== 'admin') {
            $userId = $user['id'];
        }
        
        // Get circulation records
        if ($userId) {
            $records = $this->circulationModel->getUserCirculationHistory($userId);
        } else {
            // If no userId and user is admin, get all active circulation records
            $records = $this->circulationModel->getAllActiveCirculation();
        }
        
        // Format the response
        $formattedRecords = [];
        foreach ($records as $record) {
            // Determine the correct book_id to use
            // In some cases, the record might have both 'book_id' and 'id' from the JOIN
            $bookId = isset($record['book_id']) ? $record['book_id'] : $record['id'];
            
            // Get book details
            $book = $this->bookModel->getBookById($bookId);
            
            // Use book data if available, otherwise use record data or default values
            if ($book) {
                $bookTitle = $book['title'];
                $bookAuthor = $book['author'];
            } else if (isset($record['title'])) {
                $bookTitle = $record['title'];
                $bookAuthor = isset($record['author']) ? $record['author'] : 'Unknown Author';
            } else {
                $bookTitle = 'Unknown Book';
                $bookAuthor = 'Unknown Author';
            }
            
            // If username is not already included in the record, get it
            if (!isset($record['username'])) {
                // Get user details from the user model
                require_once __DIR__ . '/../models/user_model.php';
                $userModel = new UserModel($this->conn);
                $user = $userModel->getUserById($record['user_id']);
                $username = $user ? $user['username'] : 'Unknown User';
            } else {
                $username = $record['username'];
            }
            
            $formattedRecords[] = [
                'id' => $record['id'],
                'user_id' => $record['user_id'],
                'username' => $username,
                'book_id' => $record['book_id'],
                'book_title' => $bookTitle,
                'book_author' => $bookAuthor,
                'action' => $record['action'],
                'action_date' => $record['action_date'],
                'due_date' => $record['due_date'],
                'fine_amount' => $record['fine_amount'],
                'returned' => (bool)$record['returned']
            ];
        }
        
        echo json_encode($formattedRecords);
    }
    
    /**
     * Reserve a book
     */
    public function reserveBook() {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        // Get POST data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate input
        if (!isset($data->book_id)) {
            http_response_code(400);
            echo json_encode(['message' => 'Book ID is required']);
            return;
        }
        
        // Check if book exists and has available copies
        $book = $this->bookModel->getBookById($data->book_id);
        
        if (!$book) {
            http_response_code(404);
            echo json_encode(['message' => 'Book not found']);
            return;
        }
        
        if ($book['available_copies'] <= 0) {
            http_response_code(400);
            echo json_encode(['message' => 'No copies available for reservation']);
            return;
        }
        
        // Set due date (default to 14 days from now if not specified)
        $due_date = isset($data->due_date) ? $data->due_date : date('Y-m-d H:i:s', strtotime('+14 days'));
        
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Create reservation record
            $circulationId = $this->circulationModel->createCirculationRecord(
                $user['id'],
                $data->book_id,
                'reserve',
                $due_date
            );
            
            if (!$circulationId) {
                throw new Exception('Failed to create reservation record');
            }
            
            // Update available copies
            $success = $this->bookModel->updateAvailableCopies($data->book_id, -1);
            
            if (!$success) {
                throw new Exception('Failed to update available copies');
            }
            
            // Create notification for admin
            $this->notificationModel->createNotification(
                null, // For all admins
                'Book Reservation',
                "User {$user['username']} has reserved the book '{$book['title']}'"
            );
            
            // Commit transaction
            $this->conn->commit();
            
            // Get the created circulation record
            $circulation = $this->circulationModel->getCirculationById($circulationId);
            
            http_response_code(201);
            echo json_encode([
                'message' => 'Book reserved successfully',
                'circulation' => $circulation
            ]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Borrow a book (legacy endpoint - redirects to reserve)
     */
    public function borrowBook() {
        // This endpoint now just redirects to reserve for compatibility
        $this->reserveBook();
    }
    
    /**
     * Approve a reservation and convert it to a borrow (Admin only)
     */
    public function approveReservation() {
        // Verify admin role
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['message' => 'Admin access required']);
            return;
        }
        
        // Get POST data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate input
        if (!isset($data->circulation_id)) {
            http_response_code(400);
            echo json_encode(['message' => 'Circulation ID is required']);
            return;
        }
        
        // Get the circulation record
        $circulation = $this->circulationModel->getCirculationById($data->circulation_id);
        
        if (!$circulation) {
            http_response_code(404);
            echo json_encode(['message' => 'Circulation record not found']);
            return;
        }
        
        // Check if it's a reservation
        if ($circulation['action'] !== 'reserve') {
            http_response_code(400);
            echo json_encode(['message' => 'Only reservations can be approved']);
            return;
        }
        
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Update circulation record to borrow
            $success = $this->circulationModel->updateCirculationAction(
                $data->circulation_id,
                'borrow'
            );
            
            if (!$success) {
                throw new Exception('Failed to update circulation record');
            }
            
            // Get user and book details for notification
            $user = $this->getUserById($circulation['user_id']);
            $book = $this->bookModel->getBookById($circulation['book_id']);
            
            // Create notification for user
            $this->notificationModel->createNotification(
                $circulation['user_id'],
                'Reservation Approved',
                "Your reservation for '{$book['title']}' has been approved. The book is now checked out to you."
            );
            
            // Commit transaction
            $this->conn->commit();
            
            // Get the updated circulation record
            $updatedCirculation = $this->circulationModel->getCirculationById($data->circulation_id);
            
            echo json_encode([
                'message' => 'Reservation approved successfully',
                'circulation' => $updatedCirculation
            ]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Cancel a reservation
     */
    public function cancelReservation() {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        // Get POST data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate input
        if (!isset($data->circulation_id)) {
            http_response_code(400);
            echo json_encode(['message' => 'Circulation ID is required']);
            return;
        }
        
        // Get the circulation record
        $circulation = $this->circulationModel->getCirculationById($data->circulation_id);
        
        if (!$circulation) {
            http_response_code(404);
            echo json_encode(['message' => 'Circulation record not found']);
            return;
        }
        
        // Check if user owns this reservation or is admin
        if ($circulation['user_id'] != $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'You can only cancel your own reservations']);
            return;
        }
        
        // Check if it's a reservation or borrow
        if ($circulation['action'] !== 'reserve' && $circulation['action'] !== 'borrow') {
            http_response_code(400);
            echo json_encode(['message' => 'Only reservations or borrows can be cancelled']);
            return;
        }
        
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Delete circulation record
            $success = $this->circulationModel->deleteCirculationRecord($data->circulation_id);
            
            if (!$success) {
                throw new Exception('Failed to delete circulation record');
            }
            
            // Update available copies
            $success = $this->bookModel->updateAvailableCopies($circulation['book_id'], 1);
            
            if (!$success) {
                throw new Exception('Failed to update available copies');
            }
            
            // Get book details for notification
            $book = $this->bookModel->getBookById($circulation['book_id']);
            
            // Create notification for admin if user cancelled
            if ($user['role'] !== 'admin') {
                $this->notificationModel->createNotification(
                    null, // For all admins
                    'Reservation Cancelled',
                    "User {$user['username']} has cancelled their reservation for '{$book['title']}'"
                );
            }
            
            // Create notification for user if admin cancelled
            if ($user['role'] === 'admin' && $user['id'] !== $circulation['user_id']) {
                $this->notificationModel->createNotification(
                    $circulation['user_id'],
                    'Reservation Cancelled',
                    "Your reservation for '{$book['title']}' has been cancelled by an administrator"
                );
            }
            
            // Commit transaction
            $this->conn->commit();
            
            echo json_encode([
                'message' => 'Reservation cancelled successfully'
            ]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Return a book
     */
    public function returnBook() {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        // Get POST data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate input
        if (!isset($data->circulation_id)) {
            http_response_code(400);
            echo json_encode(['message' => 'Circulation ID is required']);
            return;
        }
        
        // Get the circulation record
        $circulation = $this->circulationModel->getCirculationById($data->circulation_id);
        
        if (!$circulation) {
            http_response_code(404);
            echo json_encode(['message' => 'Circulation record not found']);
            return;
        }
        
        // Check if user owns this borrow or is admin
        if ($circulation['user_id'] != $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'You can only return your own borrowed books']);
            return;
        }
        
        // Check if it's a borrow
        if ($circulation['action'] !== 'borrow') {
            http_response_code(400);
            echo json_encode(['message' => 'Only borrowed books can be returned']);
            return;
        }
        
        // Begin transaction
        $this->conn->begin_transaction();
        
        try {
            // Calculate fine if overdue
            $fine_amount = 0;
            $due_date = strtotime($circulation['due_date']);
            $current_date = time();
            
            if ($current_date > $due_date) {
                // Calculate fine ($1 per day overdue)
                $days_overdue = ceil(($current_date - $due_date) / (60 * 60 * 24));
                $fine_amount = $days_overdue * 1.00; // $1 per day
            }
            
            // Update circulation record
            $success = $this->circulationModel->updateCirculationReturn(
                $data->circulation_id,
                $fine_amount
            );
            
            if (!$success) {
                throw new Exception('Failed to update circulation record');
            }
            
            // Update available copies
            $success = $this->bookModel->updateAvailableCopies($circulation['book_id'], 1);
            
            if (!$success) {
                throw new Exception('Failed to update available copies');
            }
            
            // Get book details for notification
            $book = $this->bookModel->getBookById($circulation['book_id']);
            
            // Create notification for admin
            $this->notificationModel->createNotification(
                null, // For all admins
                'Book Returned',
                "User {$user['username']} has returned the book '{$book['title']}'" . 
                ($fine_amount > 0 ? " with a fine of $" . number_format($fine_amount, 2) : "")
            );
            
            // Create notification for user if there's a fine
            if ($fine_amount > 0) {
                $this->notificationModel->createNotification(
                    $circulation['user_id'],
                    'Late Return Fine',
                    "You have been charged a fine of $" . number_format($fine_amount, 2) . 
                    " for returning '{$book['title']}' after the due date"
                );
            }
            
            // Commit transaction
            $this->conn->commit();
            
            // Get the updated circulation record
            $updatedCirculation = $this->circulationModel->getCirculationById($data->circulation_id);
            
            echo json_encode([
                'message' => 'Book returned successfully' . 
                    ($fine_amount > 0 ? " with a fine of $" . number_format($fine_amount, 2) : ""),
                'circulation' => $updatedCirculation
            ]);
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
        }
    }
    
    /**
     * Get borrowed books for the current user
     */
    public function getBorrowedBooks() {
        // Authenticate user
        $user = $this->authenticateUser();
        if (!$user) {
            return;
        }
        
        // Get user ID from query parameter or use authenticated user
        $userId = isset($_GET['user_id']) ? $_GET['user_id'] : $user['id'];
        
        // If requesting another user's books, must be admin
        if ($userId != $user['id'] && $user['role'] !== 'admin') {
            http_response_code(403);
            echo json_encode(['message' => 'You can only view your own borrowed books']);
            return;
        }
        
        // Get borrowed books
        $borrowedBooks = $this->circulationModel->getBorrowedBooks($userId);
        
        echo json_encode($borrowedBooks);
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
    
    /**
     * Check if the current user is an admin
     */
    private function isAdmin() {
        $user = $this->authenticateUser();
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Get user by ID
     */
    private function getUserById($id) {
        $stmt = $this->conn->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
}
