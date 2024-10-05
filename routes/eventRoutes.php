<?php
require_once '../controllers/EventController.php';
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$eventController = new EventController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;

switch ($request_method) {
    case 'GET':
        if ($event_id) {
            $eventController->show($event_id);
        } else {
            $eventController->index();
        }
        break;

    case 'POST':
        $eventController->store();
        break;

    case 'PUT':
        if ($event_id) {
            $eventController->update($event_id);
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
            $eventController->destroy($event_id);
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
