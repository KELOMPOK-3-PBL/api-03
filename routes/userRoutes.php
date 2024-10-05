<?php
require_once '../controllers/UserController.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$userController = new UserController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;

switch ($request_method) {
    case 'GET':
        if ($user_id) {
            $userController->show($user_id);  // GET a specific user
        } else {
            $userController->index();  // GET all users
        }
        break;

    case 'POST':
        $userController->store();  // Create a new user
        break;

    case 'PUT':
        if ($user_id) {
            $userController->update();  // Update a specific user
        } else {
            header("HTTP/1.0 400 Bad Request");  // Missing user_id
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing user_id.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    case 'DELETE':
        if ($user_id) {
            $userController->delete();  // Delete a specific user
        } else {
            header("HTTP/1.0 400 Bad Request");  // Missing user_id
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing user_id.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed");  // Method not allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed.'
        ], JSON_PRETTY_PRINT);
        break;
}
?>
