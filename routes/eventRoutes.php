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
        }
        break;

    case 'DELETE':
        if ($event_id) {
            $eventController->destroy($event_id);
        }
        break;

    default:
        header("HTTP/1.0 405 Method Not Allowed");
        break;
}
?>
