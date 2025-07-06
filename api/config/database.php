<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection settings for the Library Management System.
 */

// Database credentials
define('DB_HOST', 'localhost');     // Database host (usually localhost for phpMyAdmin)
define('DB_USERNAME', 'root');      // Database username (default: root)
define('DB_PASSWORD', '');          // Database password (default: empty)
define('DB_NAME', 'library_ms');    // Database name

// Create database connection
function getDbConnection() {
    $conn = new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}
