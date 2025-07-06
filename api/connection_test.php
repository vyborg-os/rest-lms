<?php
/**
 * Database Connection Test
 * 
 * Use this file to test your database connection on the Namecheap server
 */

// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database credentials - update these with your Namecheap MySQL credentials
$host = 'localhost';
$username = 'your_cpanel_username';  // Usually your cPanel username
$password = 'your_database_password'; // Your database password
$database = 'library_ms';            // Your database name

echo "<h1>Database Connection Test</h1>";

// Test connection without database selection first
try {
    $conn = new mysqli($host, $username, $password);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color:green'>✓ Successfully connected to MySQL server!</p>";
    echo "<p>Server info: " . $conn->server_info . "</p>";
    
    // Now try to select the database
    if (!$conn->select_db($database)) {
        echo "<p style='color:red'>✗ Database '$database' does not exist. You need to create it first.</p>";
        echo "<p>Available databases:</p><ul>";
        
        $result = $conn->query("SHOW DATABASES");
        while ($row = $result->fetch_assoc()) {
            echo "<li>" . $row['Database'] . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color:green'>✓ Successfully connected to database '$database'!</p>";
        
        // Check if tables exist
        $tables = array('users', 'books', 'circulation', 'notifications');
        $missing_tables = array();
        
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows == 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (count($missing_tables) > 0) {
            echo "<p style='color:orange'>⚠ Some tables are missing: " . implode(", ", $missing_tables) . "</p>";
            echo "<p>You need to import the SQL file from api/config/library_ms.sql</p>";
        } else {
            echo "<p style='color:green'>✓ All required tables exist!</p>";
            
            // Check if admin user exists
            $result = $conn->query("SELECT * FROM users WHERE role = 'admin' LIMIT 1");
            if ($result->num_rows == 0) {
                echo "<p style='color:orange'>⚠ No admin user found. You should create one.</p>";
            } else {
                $admin = $result->fetch_assoc();
                echo "<p style='color:green'>✓ Admin user exists: " . $admin['username'] . "</p>";
            }
        }
    }
    
    $conn->close();
} catch (Exception $e) {
    echo "<p style='color:red'>✗ " . $e->getMessage() . "</p>";
    echo "<h2>Troubleshooting</h2>";
    echo "<ol>";
    echo "<li>Check if your MySQL service is running on the server</li>";
    echo "<li>Verify your username and password are correct</li>";
    echo "<li>Make sure your hosting account has MySQL access</li>";
    echo "<li>Check if your IP is allowed to connect to the database</li>";
    echo "</ol>";
}
?>
