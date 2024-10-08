<?php
require_once '../config/database.php';
require_once '../controllers/QRCodeController.php';

$database = new Database();
$db = $database->getConnection();

$qrCodeController = new QRCodeController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$qr_code = isset($_GET['qr_code']) ? $_GET['qr_code'] : null;

switch ($request_method) {
    case 'POST':
        if ($event_id && $user_id) {
            $qrCodeController->registerUserForEvent($event_id, $user_id);  // Register user for an event
        } else {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing event_id or user_id.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    case 'GET':
        if ($event_id) {
            $qrCodeController->getRegisteredUsers($event_id);  // Get registered users for an event
        } else {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing event_id.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    case 'PUT':
        if ($qr_code) {
            $qrCodeController->markAttendance($qr_code);  // Mark attendance for a user
        } else {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing qr_code.'
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
