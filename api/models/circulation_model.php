<?php
/**
 * Circulation Model
 * 
 * Handles database operations for circulation records
 */

class CirculationModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get circulation record by ID
     */
    public function getCirculationById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM circulation WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Create a new circulation record
     */
    public function createCirculationRecord($user_id, $book_id, $action, $due_date = null) {
        $stmt = $this->conn->prepare("INSERT INTO circulation (user_id, book_id, action, due_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $user_id, $book_id, $action, $due_date);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update circulation action
     */
    public function updateCirculationAction($id, $action) {
        $stmt = $this->conn->prepare("UPDATE circulation SET action = ? WHERE id = ?");
        $stmt->bind_param("si", $action, $id);
        return $stmt->execute();
    }
    
    /**
     * Update circulation for return
     */
    public function updateCirculationReturn($id, $fine_amount = 0) {
        $stmt = $this->conn->prepare("UPDATE circulation SET action = 'return', returned = TRUE, fine_amount = ? WHERE id = ?");
        $stmt->bind_param("di", $fine_amount, $id);
        return $stmt->execute();
    }
    
    /**
     * Delete circulation record
     */
    public function deleteCirculationRecord($id) {
        $stmt = $this->conn->prepare("DELETE FROM circulation WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    /**
     * Get borrowed books for a user
     */
    public function getBorrowedBooks($user_id) {
        $sql = "SELECT c.*, b.title, b.author, b.isbn, b.cover_image 
                FROM circulation c 
                JOIN books b ON c.book_id = b.id 
                WHERE c.user_id = ? AND (c.action = 'borrow' OR c.action = 'reserve') AND c.returned = FALSE 
                ORDER BY c.action_date DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        return $books;
    }
    
    /**
     * Get all active circulation records
     */
    public function getAllActiveCirculation() {
        $sql = "SELECT c.*, u.id as user_id, u.username, u.email, u.role, 
                       b.id as book_id, b.title, b.author, b.isbn, b.cover_image, b.available_copies 
                FROM circulation c 
                JOIN users u ON c.user_id = u.id 
                JOIN books b ON c.book_id = b.id 
                WHERE (c.action = 'borrow' OR c.action = 'reserve') AND c.returned = FALSE 
                ORDER BY c.action_date DESC";
                
        $result = $this->conn->query($sql);
        
        if (!$result) {
            // Log the error for debugging
            error_log("MySQL Error in getAllActiveCirculation: " . $this->conn->error);
            return [];
        }
        
        $records = [];
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        
        return $records;
    }
    
    /**
     * Get circulation history for a book
     */
    public function getBookCirculationHistory($book_id) {
        $sql = "SELECT c.*, u.username, u.email 
                FROM circulation c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.book_id = ? 
                ORDER BY c.action_date DESC";
                
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
    
    /**
     * Get circulation history for a user
     */
    public function getUserCirculationHistory($user_id) {
        $sql = "SELECT c.*, b.id as book_id, b.title, b.author, b.isbn, b.cover_image, b.available_copies, 
                       u.id as user_id, u.username, u.email, u.role
                FROM circulation c 
                JOIN books b ON c.book_id = b.id 
                JOIN users u ON c.user_id = u.id
                WHERE c.user_id = ? 
                ORDER BY c.action_date DESC";
                
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            error_log("MySQL Prepare Error in getUserCirculationHistory: " . $this->conn->error);
            return [];
        }
        
        $stmt->bind_param("i", $user_id);
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("MySQL Execute Error in getUserCirculationHistory: " . $stmt->error);
            return [];
        }
        
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    }
}
