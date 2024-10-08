<?php
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
        break;
}
?>
