<?php
/**
 * PostgreSQL to MySQL Migration Script
 * 
 * This script helps migrate data from PostgreSQL to MySQL
 * You'll need to have both database connections configured
 */

// MySQL Connection
require_once __DIR__ . '/../config/database.php';

// Set up PostgreSQL connection parameters
$pg_host = 'localhost';
$pg_port = '5432';
$pg_dbname = 'library_ms';
$pg_user = 'postgres';
$pg_password = 'postgres';

// Connect to PostgreSQL
try {
    $pg_conn_string = "host=$pg_host port=$pg_port dbname=$pg_dbname user=$pg_user password=$pg_password";
    $pg_conn = pg_connect($pg_conn_string);
    
    if (!$pg_conn) {
        throw new Exception("PostgreSQL connection failed: " . pg_last_error());
    }
    
    echo "PostgreSQL connection successful.\n";
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

// Connect to MySQL
try {
    $mysql_conn = getDbConnection();
    echo "MySQL connection successful.\n";
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}

// Start migration
echo "Starting migration...\n";

// Migrate users
migrateUsers($pg_conn, $mysql_conn);

// Migrate books
migrateBooks($pg_conn, $mysql_conn);

// Migrate circulation
migrateCirculation($pg_conn, $mysql_conn);

// Migrate notifications
migrateNotifications($pg_conn, $mysql_conn);

echo "Migration completed successfully.\n";

// Close connections
pg_close($pg_conn);
$mysql_conn->close();

/**
 * Migrate users from PostgreSQL to MySQL
 */
function migrateUsers($pg_conn, $mysql_conn) {
    echo "Migrating users...\n";
    
    // Get users from PostgreSQL
    $pg_result = pg_query($pg_conn, "SELECT * FROM users");
    if (!$pg_result) {
        echo "Error fetching users from PostgreSQL: " . pg_last_error($pg_conn) . "\n";
        return;
    }
    
    // Clear existing users in MySQL (except admin)
    $mysql_conn->query("DELETE FROM users WHERE username != 'admin'");
    
    // Insert users into MySQL
    $count = 0;
    while ($row = pg_fetch_assoc($pg_result)) {
        $username = $mysql_conn->real_escape_string($row['username']);
        $password_hash = $mysql_conn->real_escape_string($row['password_hash']);
        $email = $mysql_conn->real_escape_string($row['email']);
        $role = $mysql_conn->real_escape_string($row['role']);
        $created_at = $mysql_conn->real_escape_string($row['created_at']);
        
        // Skip if user already exists
        $check = $mysql_conn->query("SELECT id FROM users WHERE username = '$username' OR email = '$email'");
        if ($check->num_rows > 0) {
            echo "User $username already exists, skipping.\n";
            continue;
        }
        
        $sql = "INSERT INTO users (username, password_hash, email, role, created_at) 
                VALUES ('$username', '$password_hash', '$email', '$role', '$created_at')";
        
        if ($mysql_conn->query($sql)) {
            $count++;
        } else {
            echo "Error inserting user $username: " . $mysql_conn->error . "\n";
        }
    }
    
    echo "Migrated $count users.\n";
}

/**
 * Migrate books from PostgreSQL to MySQL
 */
function migrateBooks($pg_conn, $mysql_conn) {
    echo "Migrating books...\n";
    
    // Get books from PostgreSQL
    $pg_result = pg_query($pg_conn, "SELECT * FROM books");
    if (!$pg_result) {
        echo "Error fetching books from PostgreSQL: " . pg_last_error($pg_conn) . "\n";
        return;
    }
    
    // Clear existing books in MySQL
    $mysql_conn->query("DELETE FROM books");
    
    // Insert books into MySQL
    $count = 0;
    while ($row = pg_fetch_assoc($pg_result)) {
        $title = $mysql_conn->real_escape_string($row['title']);
        $author = $mysql_conn->real_escape_string($row['author']);
        $isbn = $mysql_conn->real_escape_string($row['isbn']);
        $total_copies = (int)$row['total_copies'];
        $available_copies = (int)$row['available_copies'];
        $quantity = (int)$row['quantity'];
        $shelf = $mysql_conn->real_escape_string($row['shelf']);
        $category = $mysql_conn->real_escape_string($row['category']);
        $description = $mysql_conn->real_escape_string($row['description']);
        $published_year = $row['published_year'] ? (int)$row['published_year'] : 'NULL';
        $publisher = $mysql_conn->real_escape_string($row['publisher']);
        $cover_image = $mysql_conn->real_escape_string($row['cover_image']);
        $created_at = $mysql_conn->real_escape_string($row['created_at']);
        
        $sql = "INSERT INTO books (title, author, isbn, total_copies, available_copies, quantity, 
                shelf, category, description, published_year, publisher, cover_image, created_at) 
                VALUES ('$title', '$author', '$isbn', $total_copies, $available_copies, $quantity, 
                '$shelf', '$category', '$description', $published_year, '$publisher', '$cover_image', '$created_at')";
        
        if ($mysql_conn->query($sql)) {
            $count++;
        } else {
            echo "Error inserting book $title: " . $mysql_conn->error . "\n";
        }
    }
    
    echo "Migrated $count books.\n";
}

/**
 * Migrate circulation records from PostgreSQL to MySQL
 */
function migrateCirculation($pg_conn, $mysql_conn) {
    echo "Migrating circulation records...\n";
    
    // Get circulation records from PostgreSQL
    $pg_result = pg_query($pg_conn, "SELECT * FROM circulation");
    if (!$pg_result) {
        echo "Error fetching circulation records from PostgreSQL: " . pg_last_error($pg_conn) . "\n";
        return;
    }
    
    // Clear existing circulation records in MySQL
    $mysql_conn->query("DELETE FROM circulation");
    
    // Insert circulation records into MySQL
    $count = 0;
    while ($row = pg_fetch_assoc($pg_result)) {
        $user_id = (int)$row['user_id'];
        $book_id = (int)$row['book_id'];
        $action = $mysql_conn->real_escape_string($row['action']);
        $action_date = $mysql_conn->real_escape_string($row['action_date']);
        $due_date = $row['due_date'] ? "'" . $mysql_conn->real_escape_string($row['due_date']) . "'" : 'NULL';
        $fine_amount = (float)$row['fine_amount'];
        $returned = $row['returned'] == 't' ? 1 : 0;
        
        // Check if user and book exist
        $user_check = $mysql_conn->query("SELECT id FROM users WHERE id = $user_id");
        $book_check = $mysql_conn->query("SELECT id FROM books WHERE id = $book_id");
        
        if ($user_check->num_rows == 0 || $book_check->num_rows == 0) {
            echo "User ID $user_id or Book ID $book_id not found, skipping circulation record.\n";
            continue;
        }
        
        $sql = "INSERT INTO circulation (user_id, book_id, action, action_date, due_date, fine_amount, returned) 
                VALUES ($user_id, $book_id, '$action', '$action_date', $due_date, $fine_amount, $returned)";
        
        if ($mysql_conn->query($sql)) {
            $count++;
        } else {
            echo "Error inserting circulation record: " . $mysql_conn->error . "\n";
        }
    }
    
    echo "Migrated $count circulation records.\n";
}

/**
 * Migrate notifications from PostgreSQL to MySQL
 */
function migrateNotifications($pg_conn, $mysql_conn) {
    echo "Migrating notifications...\n";
    
    // Get notifications from PostgreSQL
    $pg_result = pg_query($pg_conn, "SELECT * FROM notifications");
    if (!$pg_result) {
        echo "Error fetching notifications from PostgreSQL: " . pg_last_error($pg_conn) . "\n";
        return;
    }
    
    // Clear existing notifications in MySQL
    $mysql_conn->query("DELETE FROM notifications");
    
    // Insert notifications into MySQL
    $count = 0;
    while ($row = pg_fetch_assoc($pg_result)) {
        $user_id = (int)$row['user_id'];
        $title = $mysql_conn->real_escape_string($row['title']);
        $message = $mysql_conn->real_escape_string($row['message']);
        $is_read = $row['is_read'] == 't' ? 1 : 0;
        $created_at = $mysql_conn->real_escape_string($row['created_at']);
        
        // Check if user exists
        $user_check = $mysql_conn->query("SELECT id FROM users WHERE id = $user_id");
        
        if ($user_check->num_rows == 0) {
            echo "User ID $user_id not found, skipping notification.\n";
            continue;
        }
        
        $sql = "INSERT INTO notifications (user_id, title, message, is_read, created_at) 
                VALUES ($user_id, '$title', '$message', $is_read, '$created_at')";
        
        if ($mysql_conn->query($sql)) {
            $count++;
        } else {
            echo "Error inserting notification: " . $mysql_conn->error . "\n";
        }
    }
    
    echo "Migrated $count notifications.\n";
}
