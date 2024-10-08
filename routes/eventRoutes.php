<?php
require_once '../controllers/EventController.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();
$eventController = new EventController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;
$user_role = isset($_GET['user_role']) ? $_GET['user_role'] : null; // Extract user role from query parameter

switch ($request_method) {
    case 'GET':
        if ($event_id) {
            $eventController->getEventById($event_id);
        } else {
            $eventController->getAllEvents();
        }
        break;

    case 'POST':
        // Pass user role to createEvent method
        if ($user_role) {
            $eventController->createEvent($user_role);
        } else {
            header("HTTP/1.0 400 Bad Request"); // Missing user role
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing user role.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    case 'PUT':
        if ($event_id) {
            // Pass user role to updateEvent method
            if ($user_role) {
                $eventController->updateEvent($event_id, $user_role);
            } else {
                header("HTTP/1.0 400 Bad Request"); // Missing user role
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Missing user role.'
                ], JSON_PRETTY_PRINT);
            }
        } else {
            header("HTTP/1.0 400 Bad Request"); // Missing event_id
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing event_id.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    case 'DELETE':
        if ($event_id) {
            $eventController->deleteEvent($event_id);
        } else {
            header("HTTP/1.0 400 Bad Request"); // Missing event_id
            echo json_encode([
                'status' => 'error',
                'message' => 'Missing event_id.'
            ], JSON_PRETTY_PRINT);
        }
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed"); // Method not allowed
        echo json_encode([
            'status' => 'error',
            'message' => 'Method not allowed.'
        ], JSON_PRETTY_PRINT);
        break;
}
?>
