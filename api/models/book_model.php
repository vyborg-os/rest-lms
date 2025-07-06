<?php
/**
 * Book Model
 * 
 * Handles database operations for books
 */

class BookModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get all books
     */
    public function getAllBooks() {
        $result = $this->conn->query("SELECT * FROM books ORDER BY title");
        $books = [];
        
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        
        return $books;
    }
    
    /**
     * Get book by ID
     */
    public function getBookById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM books WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get book by ISBN
     */
    public function getBookByISBN($isbn) {
        $stmt = $this->conn->prepare("SELECT * FROM books WHERE isbn = ?");
        $stmt->bind_param("s", $isbn);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Create a new book
     */
    public function createBook($title, $author, $isbn, $total_copies = 1, $available_copies = 1, $quantity = 1, $shelf = null, $category = null, $description = null, $published_year = null, $publisher = null, $cover_image = null) {
        $stmt = $this->conn->prepare("INSERT INTO books (title, author, isbn, total_copies, available_copies, quantity, shelf, category, description, published_year, publisher, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiiississ", $title, $author, $isbn, $total_copies, $available_copies, $quantity, $shelf, $category, $description, $published_year, $publisher, $cover_image);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update book
     */
    public function updateBook($id, $data) {
        $fields = [];
        $types = "";
        $values = [];
        
        // Build dynamic query based on provided data
        if (isset($data->title)) {
            $fields[] = "title = ?";
            $types .= "s";
            $values[] = $data->title;
        }
        
        if (isset($data->author)) {
            $fields[] = "author = ?";
            $types .= "s";
            $values[] = $data->author;
        }
        
        if (isset($data->isbn)) {
            $fields[] = "isbn = ?";
            $types .= "s";
            $values[] = $data->isbn;
        }
        
        if (isset($data->total_copies)) {
            $fields[] = "total_copies = ?";
            $types .= "i";
            $values[] = $data->total_copies;
        }
        
        if (isset($data->available_copies)) {
            $fields[] = "available_copies = ?";
            $types .= "i";
            $values[] = $data->available_copies;
        }
        
        if (isset($data->quantity)) {
            $fields[] = "quantity = ?";
            $types .= "i";
            $values[] = $data->quantity;
        }
        
        if (isset($data->shelf)) {
            $fields[] = "shelf = ?";
            $types .= "s";
            $values[] = $data->shelf;
        }
        
        if (isset($data->category)) {
            $fields[] = "category = ?";
            $types .= "s";
            $values[] = $data->category;
        }
        
        if (isset($data->description)) {
            $fields[] = "description = ?";
            $types .= "s";
            $values[] = $data->description;
        }
        
        if (isset($data->published_year)) {
            $fields[] = "published_year = ?";
            $types .= "i";
            $values[] = $data->published_year;
        }
        
        if (isset($data->publisher)) {
            $fields[] = "publisher = ?";
            $types .= "s";
            $values[] = $data->publisher;
        }
        
        if (isset($data->cover_image)) {
            $fields[] = "cover_image = ?";
            $types .= "s";
            $values[] = $data->cover_image;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE books SET " . implode(", ", $fields) . " WHERE id = ?";
        $types .= "i";
        $values[] = $id;
        
        $stmt = $this->conn->prepare($sql);
        
        // Dynamically bind parameters
        $bindParams = array($types);
        foreach ($values as $key => $value) {
            $bindParams[] = &$values[$key];
        }
        
        call_user_func_array(array($stmt, 'bind_param'), $bindParams);
        
        return $stmt->execute();
    }
    
    /**
     * Delete book
     */
    public function deleteBook($id) {
        $stmt = $this->conn->prepare("DELETE FROM books WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    /**
     * Update available copies
     */
    public function updateAvailableCopies($id, $change) {
        $stmt = $this->conn->prepare("UPDATE books SET available_copies = available_copies + ? WHERE id = ?");
        $stmt->bind_param("ii", $change, $id);
        return $stmt->execute();
    }
}
