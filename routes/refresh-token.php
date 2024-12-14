<?php
require_once '../controllers/AuthController.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];

switch ($request_method) {
    case 'POST':
        $authController->refreshToken();
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed");
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed.'
        ], JSON_PRETTY_PRINT);
        break;
}
