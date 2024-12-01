<?php
require_once '../controllers/EventController.php';
require_once '../config/database.php';
require_once '../helpers/JwtHelpers.php'; // Ensure you include your JWTHelper
require_once '../helpers/ResponseHelpers.php'; // Include the ResponseHelpers

$database = new Database();
$db = $database->getConnection();
$eventController = new EventController($db);

// Handle HTTP methods
$request_method = $_SERVER["REQUEST_METHOD"];
$jwtHelper = new JWTHelper();
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;

// Debugging: Show the event_id being received
// var_dump("Event ID received: ", $event_id);

switch ($request_method) {
    case 'GET':
        if (!empty($event_id)) {
            $eventController->getEventById($event_id);
        } else {
            $eventController->getAllEvents();
        }
        break;

    default:
        response('error', 'Method not allowed.', null, 405); // Method not allowed
        break;
}
?>