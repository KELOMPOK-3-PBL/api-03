<?php
require_once '../controllers/AuthController.php';
require_once '../config/database.php';

// Handle preflight request (OPTIONS)
// if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
//     header("Access-Control-Allow-Origin: http://localhost:50581"); // Use your frontend's exact origin
//     header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
//     header("Access-Control-Allow-Headers: Content-Type, Authorization");
//     header("Access-Control-Allow-Credentials: true");
//     header("Access-Control-Max-Age: 3600");
//     header("HTTP/1.1 204 No Content");
//     exit(0); // Stop further execution for OPTIONS request
// }


$database = new Database();
$db = $database->getConnection();
$authController = new AuthController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");

switch ($request_method) {
    case 'POST':
        if (isset($_GET['action']) && $_GET['action'] === 'forgotPassword') {
            $authController->forgotPassword();
        } elseif (isset($_GET['action']) && $_GET['action'] === 'resetPassword') {
            $authController->resetPassword();
        } else {
            $authController->login();
        }
        break;

    case 'DELETE':
        $authController->logout();
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
