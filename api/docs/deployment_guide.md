# Deployment Guide for Library Management System API (MySQLi Version)

This guide will help you deploy the PHP MySQLi API to your Namecheap shared hosting environment.

## Prerequisites

- A Namecheap hosting account with:
  - PHP 7.4+ support
  - MySQL database
  - phpMyAdmin access

## Step 1: Upload Files

1. Connect to your Namecheap hosting using FTP (FileZilla, Cyberduck, etc.)
2. Upload the entire `/api` folder to your website's root directory
3. Upload the `.htaccess` file to your website's root directory

## Step 2: Create and Configure the Database

1. Log in to your Namecheap cPanel
2. Open phpMyAdmin
3. Create a new database named `library_ms` (or choose your preferred name)
4. Import the SQL file from `/api/config/library_ms.sql`

## Step 3: Configure Database Connection

1. Edit the `/api/config/database.php` file with your Namecheap MySQL credentials:

```php
// Database credentials
define('DB_HOST', 'localhost');     // Usually 'localhost' for shared hosting
define('DB_USERNAME', 'your_cpanel_username');  // Your cPanel username
define('DB_PASSWORD', 'your_database_password'); // Your database password
define('DB_NAME', 'library_ms');    // Your database name
```

## Step 4: Test the Connection

1. Visit `https://your-domain.com/api/connection_test.php` in your browser
2. This will test your database connection and show any issues
3. If successful, you should see confirmation that the connection works

## Step 5: Test the API

1. Visit `https://your-domain.com/api/test.php` in your browser
2. This should return JSON with database connection information
3. If successful, your API is ready to use

## Step 6: Secure Your API

1. Edit the JWT secret in `/api/controllers/auth_controller.php`:

```php
private $jwt_secret = "your_secure_random_string"; // Change this to a secure random string
```

2. Remove or secure test files after deployment:
   - `/api/connection_test.php`
   - `/api/test.php`

## Step 7: Configure CORS (If Needed)

If your frontend is hosted on a different domain, you may need to adjust the CORS headers in `/api/index.php`:

```php
// Set CORS headers
header('Access-Control-Allow-Origin: https://your-frontend-domain.com');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

## Step 8: Update Frontend API Endpoints

1. Update your React frontend to use the new API endpoints
2. Change all API calls from the Node.js endpoints to the new PHP endpoints
3. The API structure remains the same, so minimal changes should be required

## Troubleshooting

### API Returns 500 Error
- Check your PHP error logs in cPanel
- Ensure database credentials are correct
- Verify PHP version is 7.4 or higher

### Database Connection Issues
- Confirm database name, username, and password
- Check if your hosting has database access restrictions

### CORS Issues
- Adjust CORS headers in `/api/index.php`
- Test with a browser extension that disables CORS during development

### File Permissions
- Set appropriate permissions: 755 for directories, 644 for files
- Ensure PHP has write access to the `/api/logs` directory

## Additional Resources

- [Namecheap Hosting Documentation](https://www.namecheap.com/support/knowledgebase/category/29/shared-hosting/)
- [PHP MySQLi Documentation](https://www.php.net/manual/en/book.mysqli.php)
- [JWT Authentication in PHP](https://github.com/firebase/php-jwt)
