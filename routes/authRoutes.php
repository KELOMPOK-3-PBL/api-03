<?php
<<<<<<< HEAD
require_once 'AuthController.php'; // Include the AuthController class

$authController = new AuthController();

// Get the request method and endpoint
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = explode('?', $_SERVER['REQUEST_URI'])[0]; // Remove query string

// Define your routes
switch ($requestUri) {
    case '/login':
        if ($requestMethod === 'POST') {
            // Get input data from POST request
            $inputData = json_decode(file_get_contents("php://input"), true);
            $email = $inputData['email'] ?? '';
            $password = $inputData['password'] ?? '';
            echo $authController->login($email, $password);
        } else {
            // Method not allowed
            header('HTTP/1.1 405 Method Not Allowed');
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        }
        break;

    case '/logout':
        if ($requestMethod === 'POST') {
            echo $authController->logout();
        } else {
            // Method not allowed
            header('HTTP/1.1 405 Method Not Allowed');
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        }
        break;

    case '/status':
        if ($requestMethod === 'GET') {
            // Check if user is logged in and return their status
            if (isset($_SESSION['user_id'])) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User is logged in.',
                    'user_id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'role' => $_SESSION['role']
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User is not logged in.'
                ], JSON_PRETTY_PRINT);
            }
        } else {
            // Method not allowed
            header('HTTP/1.1 405 Method Not Allowed');
            echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
        }
        break;

    // Uncomment these routes if you implement forgot password and change password functionalities
    // case '/forgot-password':
    //     if ($requestMethod === 'POST') {
    //         $inputData = json_decode(file_get_contents("php://input"), true);
    //         $email = $inputData['email'] ?? '';
    //         echo $authController->forgotPassword($email);
    //     } else {
    //         header('HTTP/1.1 405 Method Not Allowed');
    //         echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    //     }
    //     break;

    // case '/change-password':
    //     if ($requestMethod === 'POST') {
    //         $inputData = json_decode(file_get_contents("php://input"), true);
    //         $userId = $inputData['user_id'] ?? '';
    //         $oldPassword = $inputData['old_password'] ?? '';
    //         $newPassword = $inputData['new_password'] ?? '';
    //         echo $authController->changePassword($userId, $oldPassword, $newPassword);
    //     } else {
    //         header('HTTP/1.1 405 Method Not Allowed');
    //         echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    //     }
    //     break;

    default:
        // Route not found
        header('HTTP/1.1 404 Not Found');
        echo json_encode(['status' => 'error', 'message' => 'Route not found.']);
=======
require_once '../controllers/AuthController.php';
require_once '../config/database.php';

// Handle preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: http://localhost:50064");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 3600");
    header("HTTP/1.1 204 No Content");
    exit(0); // Stop further execution for OPTIONS request
}

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];

switch ($request_method) {
    case 'POST':
        // For login
        $authController->login();
        break;

    case 'DELETE':
        // For logout
        $authController->logout();
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed"); // Method not allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed.'
        ], JSON_PRETTY_PRINT);
>>>>>>> 22089e1 (fix cors on login routes)
        break;
}
?>
