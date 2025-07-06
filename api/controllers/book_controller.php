<?php
/**
 * Book Controller
 * 
 * Handles book-related operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/book_model.php';
require_once __DIR__ . '/../controllers/auth_controller.php';

class BookController {
    private $conn;
    private $bookModel;
    private $authController;
    
    public function __construct() {
        $this->conn = getDbConnection();
        $this->bookModel = new BookModel($this->conn);
        $this->authController = new AuthController();
    }
    
    /**
     * Get all books
     */
    public function getAllBooks() {
        $books = $this->bookModel->getAllBooks();
        echo json_encode($books);
    }
    
    /**
     * Get book by ID
     */
    public function getBookById($id) {
        $book = $this->bookModel->getBookById($id);
        
        if (!$book) {
            http_response_code(404);
            echo json_encode(['message' => 'Book not found']);
            return;
        }
        
        echo json_encode($book);
    }
    
    /**
     * Add a new book (Admin only)
     */
    public function addBook() {
        // Verify admin role
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['message' => 'Admin access required']);
            return;
        }
        
        // Get POST data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate required fields
        if (!isset($data->title) || !isset($data->author) || !isset($data->isbn)) {
            http_response_code(400);
            echo json_encode(['message' => 'Title, author and ISBN are required']);
            return;
        }
        
        // Check if ISBN already exists
        if ($this->bookModel->getBookByISBN($data->isbn)) {
            http_response_code(400);
            echo json_encode(['message' => 'A book with this ISBN already exists']);
            return;
        }
        
        // Set default values if not provided
        $total_copies = isset($data->total_copies) ? $data->total_copies : 1;
        $available_copies = isset($data->available_copies) ? $data->available_copies : $total_copies;
        $quantity = isset($data->quantity) ? $data->quantity : $total_copies;
        
        // Create book
        $bookId = $this->bookModel->createBook(
            $data->title,
            $data->author,
            $data->isbn,
            $total_copies,
            $available_copies,
            $quantity,
            isset($data->shelf) ? $data->shelf : null,
            isset($data->category) ? $data->category : null,
            isset($data->description) ? $data->description : null,
            isset($data->published_year) ? $data->published_year : null,
            isset($data->publisher) ? $data->publisher : null,
            isset($data->cover_image) ? $data->cover_image : null
        );
        
        if (!$bookId) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create book']);
            return;
        }
        
        // Get the created book
        $book = $this->bookModel->getBookById($bookId);
        
        http_response_code(201);
        echo json_encode($book);
    }
    
    /**
     * Update book (Admin only)
     */
    public function updateBook($id) {
        // Verify admin role
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['message' => 'Admin access required']);
            return;
        }
        
        // Check if book exists
        $book = $this->bookModel->getBookById($id);
        
        if (!$book) {
            http_response_code(404);
            echo json_encode(['message' => 'Book not found']);
            return;
        }
        
        // Get PUT data
        $data = json_decode(file_get_contents("php://input"));
        
        // Update book
        $success = $this->bookModel->updateBook($id, $data);
        
        if (!$success) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to update book']);
            return;
        }
        
        // Get the updated book
        $updatedBook = $this->bookModel->getBookById($id);
        
        echo json_encode($updatedBook);
    }
    
    /**
     * Delete book (Admin only)
     */
    public function deleteBook($id) {
        // Verify admin role
        if (!$this->isAdmin()) {
            http_response_code(403);
            echo json_encode(['message' => 'Admin access required']);
            return;
        }
        
        // Check if book exists
        $book = $this->bookModel->getBookById($id);
        
        if (!$book) {
            http_response_code(404);
            echo json_encode(['message' => 'Book not found']);
            return;
        }
        
        // Delete book
        $success = $this->bookModel->deleteBook($id);
        
        if (!$success) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to delete book']);
            return;
        }
        
        echo json_encode(['message' => 'Book deleted successfully']);
    }
    
    /**
     * Check if the current user is an admin
     */
    private function isAdmin() {
        // Get authorization header
        $headers = getallheaders();
        
        if (!isset($headers['Authorization'])) {
            return false;
        }
        
        // Extract token
        $token = str_replace('Bearer ', '', $headers['Authorization']);
        
        // Special case for development
        if ($token === 'test_token') {
            return true;
        }
        
        // Verify token
        $payload = $this->authController->verifyJWT($token);
        
        if (!$payload || $payload['role'] !== 'admin') {
            return false;
        }
        
        return true;
    }
}
