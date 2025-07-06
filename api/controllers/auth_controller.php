<?php
/**
 * Authentication Controller
 * 
 * Handles user login and registration
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/user_model.php';

class AuthController {
    private $conn;
    private $userModel;
    
    public function __construct() {
        $this->conn = getDbConnection();
        $this->userModel = new UserModel($this->conn);
    }
    
    /**
     * User login
     */
    public function login() {
        // Get POST data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate input
        if (!isset($data->username) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Username and password are required']);
            return;
        }
        
        // Attempt to find the user
        $user = $this->userModel->getUserByUsername($data->username);
        
        if (!$user) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
            return;
        }
        
        // Verify password
        if (!password_verify($data->password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid credentials']);
            return;
        }
        
        // Generate JWT token
        $token = $this->generateJWT($user);
        
        // Return user data and token
        echo json_encode([
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }
    
    /**
     * User registration
     */
    public function register() {
        // Get POST data
        $data = json_decode(file_get_contents("php://input"));
        
        // Validate input
        if (!isset($data->username) || !isset($data->email) || !isset($data->password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Username, email, and password are required']);
            return;
        }
        
        // Check if username already exists
        if ($this->userModel->getUserByUsername($data->username)) {
            http_response_code(400);
            echo json_encode(['message' => 'Username already exists']);
            return;
        }
        
        // Check if email already exists
        if ($this->userModel->getUserByEmail($data->email)) {
            http_response_code(400);
            echo json_encode(['message' => 'Email already exists']);
            return;
        }
        
        // Set default role to patron if not specified
        $role = isset($data->role) ? $data->role : 'patron';
        
        // Only allow admin role if specified by an existing admin
        if ($role === 'admin') {
            // Check if request has authorization header
            $headers = getallheaders();
            if (!isset($headers['Authorization'])) {
                $role = 'patron'; // Default to patron if no auth token
            } else {
                // Verify token and check if user is admin
                $token = str_replace('Bearer ', '', $headers['Authorization']);
                $payload = $this->verifyJWT($token);
                
                if (!$payload || $payload['role'] !== 'admin') {
                    $role = 'patron'; // Default to patron if not admin
                }
            }
        }
        
        // Hash password
        $password_hash = password_hash($data->password, PASSWORD_DEFAULT);
        
        // Create user
        $userId = $this->userModel->createUser($data->username, $password_hash, $data->email, $role);
        
        if (!$userId) {
            http_response_code(500);
            echo json_encode(['message' => 'Failed to create user']);
            return;
        }
        
        // Get the created user
        $user = $this->userModel->getUserById($userId);
        
        // Generate JWT token
        $token = $this->generateJWT($user);
        
        // Return user data and token
        http_response_code(201);
        echo json_encode([
            'message' => 'User registered successfully',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ]
        ]);
    }
    
    /**
     * Generate JWT token
     */
    private function generateJWT($user) {
        $secret_key = "your_jwt_secret"; // In production, use environment variable
        $issued_at = time();
        $expiration = $issued_at + (60 * 60 * 24); // Token valid for 24 hours
        
        $payload = [
            'iat' => $issued_at,
            'exp' => $expiration,
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ];
        
        // Encode the JWT
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $header = base64_encode($header);
        
        $payload = json_encode($payload);
        $payload = base64_encode($payload);
        
        $signature = hash_hmac('sha256', "$header.$payload", $secret_key, true);
        $signature = base64_encode($signature);
        
        return "$header.$payload.$signature";
    }
    
    /**
     * Verify JWT token
     */
    public function verifyJWT($token) {
        $secret_key = "your_jwt_secret"; // In production, use environment variable
        
        // Split the token
        $token_parts = explode('.', $token);
        
        if (count($token_parts) != 3) {
            return false;
        }
        
        $header = base64_decode($token_parts[0]);
        $payload = base64_decode($token_parts[1]);
        $signature_provided = $token_parts[2];
        
        // Check the expiration time
        $payload_data = json_decode($payload, true);
        $expiration = isset($payload_data['exp']) ? $payload_data['exp'] : 0;
        
        if ($expiration < time()) {
            return false;
        }
        
        // Verify signature
        $base64_header = base64_encode($header);
        $base64_payload = base64_encode($payload);
        
        $signature = hash_hmac('sha256', "$base64_header.$base64_payload", $secret_key, true);
        $base64_signature = base64_encode($signature);
        
        if ($base64_signature !== $signature_provided) {
            return false;
        }
        
        return $payload_data;
    }
}
