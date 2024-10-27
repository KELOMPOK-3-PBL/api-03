<?php
require_once '../config/database.php';
require_once '../controllers/UserController.php';

$database = new Database();
$db = $database->getConnection();

$userController = new UserController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

switch ($request_method) {
    case 'GET':
        if ($user_id) {
            $userController->getUserById($user_id);  // GET a specific user
        } else {
            $userController->getAllUsers();  // GET all users
        }
        break;

    case 'POST':
        $userController->createUser();  // Create a new user
        break;

    case 'PUT':
        if ($user_id) {
            $userController->updateUser($user_id);  // Update a specific user
        } else {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing user_id.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    case 'DELETE':
        if ($user_id) {
            $userController->deleteUser($user_id);  // Delete a specific user
        } else {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing user_id.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed");
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed.'
        ], JSON_PRETTY_PRINT);
        break;
}
?>
