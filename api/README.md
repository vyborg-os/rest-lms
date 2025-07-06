# Library Management System API (MySQLi Version)

This is the PHP/MySQLi implementation of the Library Management System API. This version is designed to work with shared hosting environments like Namecheap that support PHP and MySQL.

## Database Setup

1. Access your phpMyAdmin panel through your hosting control panel
2. Create a new database named `library_ms` (or use an existing database)
3. Import the SQL file located at `api/config/library_ms.sql` to create all required tables and sample data

## Configuration

1. Edit the database configuration in `api/config/database.php` to match your hosting credentials:

```php
// Database credentials
define('DB_HOST', 'localhost');     // Usually 'localhost' for shared hosting
define('DB_USERNAME', 'your_db_username');  // Your database username
define('DB_PASSWORD', 'your_db_password');  // Your database password
define('DB_NAME', 'library_ms');    // Your database name
```

## API Endpoints

### Authentication
- `POST /api/users/login` - User login
- `POST /api/users/register` - User registration

### Books
- `GET /api/books` - Get all books
- `GET /api/books/{id}` - Get book by ID
- `POST /api/books` - Add a new book (Admin only)
- `PUT /api/books/{id}` - Update book (Admin only)
- `DELETE /api/books/{id}` - Delete book (Admin only)

### Circulation
- `POST /api/circulation/reserve` - Reserve a book
- `POST /api/circulation/borrow` - Borrow a book (legacy endpoint)
- `POST /api/circulation/return` - Return a book
- `POST /api/circulation/approve` - Approve a reservation (Admin only)
- `POST /api/circulation/cancel` - Cancel a reservation
- `GET /api/circulation/borrowed` - Get borrowed books for the current user

### Dashboard
- `GET /api/dashboard/stats` - Get dashboard statistics

### Notifications
- `GET /api/notifications/user/{userId}` - Get notifications for a user

### Users
- `GET /api/users` - Get all users (Admin only)
- `PUT /api/users/{id}` - Update user (Admin only or own account)
- `DELETE /api/users/{id}` - Delete user (Admin only)

## Authentication

All protected endpoints require a JWT token in the Authorization header:

```
Authorization: Bearer your_jwt_token
```

## Development Mode

