<?php
/**
 * Helper Functions
 * 
 * Common utility functions for the API
 */

/**
 * Send JSON response
 * 
 * @param mixed $data The data to send
 * @param int $status_code HTTP status code
 */
function sendJsonResponse($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Get request body as JSON
 * 
 * @return object Decoded JSON object
 */
function getRequestBody() {
    $json = file_get_contents('php://input');
    return json_decode($json);
}

/**
 * Validate required fields
 * 
 * @param object $data Request data
 * @param array $required_fields List of required field names
 * @return bool True if all required fields are present
 */
function validateRequiredFields($data, $required_fields) {
    foreach ($required_fields as $field) {
        if (!isset($data->$field) || empty($data->$field)) {
            return false;
        }
    }
    return true;
}

/**
 * Generate a secure random string
 * 
 * @param int $length Length of the string
 * @return string Random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Format date for MySQL
 * 
 * @param string $date Date string
 * @return string Formatted date
 */
function formatMySQLDate($date) {
    return date('Y-m-d H:i:s', strtotime($date));
}

/**
 * Sanitize input to prevent SQL injection
 * 
 * @param string $input Input string
 * @param mysqli $conn MySQLi connection
 * @return string Sanitized string
 */
function sanitizeInput($input, $conn) {
    return $conn->real_escape_string($input);
}

/**
 * Log API errors
 * 
 * @param string $message Error message
 * @param string $level Error level
 */
function logError($message, $level = 'ERROR') {
    $log_file = __DIR__ . '/../logs/api_errors.log';
    $log_dir = dirname($log_file);
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[$timestamp] [$level] $message" . PHP_EOL;
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
}

/**
 * Check if a string is a valid JSON
 * 
 * @param string $string String to check
 * @return bool True if valid JSON
 */
function isValidJson($string) {
    json_decode($string);
    return json_last_error() === JSON_ERROR_NONE;
}

/**
 * Get client IP address
 * 
 * @return string IP address
 */
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
