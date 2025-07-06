<?php
/**
 * Library Management System API
 * 
 * Main entry point for the API
 */

// Enable CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Parse the URL to determine the requested endpoint
$request_uri = $_SERVER['REQUEST_URI'];
$uri_parts = explode('/api/', $request_uri);

if (count($uri_parts) < 2) {
    // No specific endpoint requested, return welcome message
    echo json_encode(['message' => 'Welcome to the Library Management System API']);
    exit();
}

// Get the endpoint path
$endpoint = trim($uri_parts[1], '/');

// Route the request to the appropriate controller
switch (true) {
    // Authentication endpoints
    case preg_match('/^users\/login$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_once __DIR__ . '/controllers/auth_controller.php';
        $controller = new AuthController();
        $controller->login();
        break;
        
    case preg_match('/^users\/register$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_once __DIR__ . '/controllers/auth_controller.php';
        $controller = new AuthController();
        $controller->register();
        break;
        
    // Books endpoints
    case preg_match('/^books$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require_once __DIR__ . '/controllers/book_controller.php';
        $controller = new BookController();
        $controller->getAllBooks();
        break;
        
    case preg_match('/^books\/(\d+)$/', $endpoint, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require_once __DIR__ . '/controllers/book_controller.php';
        $controller = new BookController();
        $controller->getBookById($matches[1]);
        break;
        
    case preg_match('/^books$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_once __DIR__ . '/controllers/book_controller.php';
        $controller = new BookController();
        $controller->addBook();
        break;
        
    case preg_match('/^books\/(\d+)$/', $endpoint, $matches) && $_SERVER['REQUEST_METHOD'] === 'PUT':
        require_once __DIR__ . '/controllers/book_controller.php';
        $controller = new BookController();
        $controller->updateBook($matches[1]);
        break;
        
    case preg_match('/^books\/(\d+)$/', $endpoint, $matches) && $_SERVER['REQUEST_METHOD'] === 'DELETE':
        require_once __DIR__ . '/controllers/book_controller.php';
        $controller = new BookController();
        $controller->deleteBook($matches[1]);
        break;
        
    // Circulation endpoints
    case preg_match('/^circulation$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require_once __DIR__ . '/controllers/circulation_controller.php';
        $controller = new CirculationController();
        $controller->getCirculationRecords();
        break;
        
    case preg_match('/^circulation\/reserve$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_once __DIR__ . '/controllers/circulation_controller.php';
        $controller = new CirculationController();
        $controller->reserveBook();
        break;
        
    case preg_match('/^circulation\/borrow$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_once __DIR__ . '/controllers/circulation_controller.php';
        $controller = new CirculationController();
        $controller->borrowBook();
        break;
        
    case preg_match('/^circulation\/return$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_once __DIR__ . '/controllers/circulation_controller.php';
        $controller = new CirculationController();
        $controller->returnBook();
        break;
        
    case preg_match('/^circulation\/approve$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_once __DIR__ . '/controllers/circulation_controller.php';
        $controller = new CirculationController();
        $controller->approveReservation();
        break;
        
    case preg_match('/^circulation\/cancel$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'POST':
        require_once __DIR__ . '/controllers/circulation_controller.php';
        $controller = new CirculationController();
        $controller->cancelReservation();
        break;
        
    case preg_match('/^circulation\/borrowed$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require_once __DIR__ . '/controllers/circulation_controller.php';
        $controller = new CirculationController();
        $controller->getBorrowedBooks();
        break;
        
    // Dashboard statistics endpoint
    case preg_match('/^dashboard\/stats$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require_once __DIR__ . '/controllers/dashboard_controller.php';
        $controller = new DashboardController();
        $controller->getStats();
        break;
        
    // Notifications endpoints
    case preg_match('/^notifications\/user\/(\d+)$/', $endpoint, $matches) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require_once __DIR__ . '/controllers/notification_controller.php';
        $controller = new NotificationController();
        $controller->getUserNotifications($matches[1]);
        break;
        
    // Users endpoints
    case preg_match('/^users$/', $endpoint) && $_SERVER['REQUEST_METHOD'] === 'GET':
        require_once __DIR__ . '/controllers/user_controller.php';
        $controller = new UserController();
        $controller->getAllUsers();
        break;
        
    case preg_match('/^users\/(\d+)$/', $endpoint, $matches) && $_SERVER['REQUEST_METHOD'] === 'PUT':
        require_once __DIR__ . '/controllers/user_controller.php';
        $controller = new UserController();
        $controller->updateUser($matches[1]);
        break;
        
    case preg_match('/^users\/(\d+)$/', $endpoint, $matches) && $_SERVER['REQUEST_METHOD'] === 'DELETE':
        require_once __DIR__ . '/controllers/user_controller.php';
        $controller = new UserController();
        $controller->deleteUser($matches[1]);
        break;
        
    default:
        // Endpoint not found
        http_response_code(404);
        echo json_encode(['message' => 'Endpoint not found']);
        break;
}
