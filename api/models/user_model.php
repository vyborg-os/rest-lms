<?php
/**
 * User Model
 * 
 * Handles database operations for users
 */

class UserModel {
    private $conn;
    
    public function __construct($conn) {
        $this->conn = $conn;
    }
    
    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get user by username
     */
    public function getUserByUsername($username) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        
        return null;
    }
    
    /**
     * Create a new user
     */
    public function createUser($username, $password_hash, $email, $role = 'patron') {
        $stmt = $this->conn->prepare("INSERT INTO users (username, password_hash, email, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $username, $password_hash, $email, $role);
        
        if ($stmt->execute()) {
            return $this->conn->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update user
     */
    public function updateUser($id, $data) {
        $fields = [];
        $types = "";
        $values = [];
        
        // Build dynamic query based on provided data
        if (isset($data->username)) {
            $fields[] = "username = ?";
            $types .= "s";
            $values[] = $data->username;
        }
        
        if (isset($data->email)) {
            $fields[] = "email = ?";
            $types .= "s";
            $values[] = $data->email;
        }
        
        if (isset($data->password)) {
            $fields[] = "password_hash = ?";
            $types .= "s";
            $values[] = password_hash($data->password, PASSWORD_DEFAULT);
        }
        
        if (isset($data->role)) {
            $fields[] = "role = ?";
            $types .= "s";
            $values[] = $data->role;
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE id = ?";
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
     * Delete user
     */
    public function deleteUser($id) {
        $stmt = $this->conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    
    /**
     * Get all users
     */
    public function getAllUsers() {
        $result = $this->conn->query("SELECT id, username, email, role, created_at FROM users ORDER BY username");
        $users = [];
        
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        
        return $users;
    }
}
