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
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;

// Initialize user roles (if needed for other methods)
$jwtHelper = new JWTHelper();
$user_roles = [];

// Only retrieve roles for methods that require authentication
if (in_array($request_method, ['POST', 'PUT', 'DELETE'])) {
    $user_roles = $jwtHelper->getRoles(); // Retrieve user roles from the token
}
switch ($request_method) {
    case 'GET':
        if ($event_id) {
            $eventController->getEventById($event_id);
        } else {
            $eventController->getAllEvents();
        }
        break;

    case 'POST':
        if (in_array('Propose', $user_roles)) { // Check if user has the 'propose' role
            $eventController->createEvent();
        } else {
            response('error', 'Unauthorized to create events.', null, 403); // User not authorized
        }
        break;

    case 'PUT':
        if ($event_id) {
            if (in_array('Admin', $user_roles) || in_array('Propose', $user_roles)) { // Check for roles
                $eventController->updateEvent($event_id);
            } else {
                response('error', 'Unauthorized to update events.', null, 403); // User not authorized
            }
        } else {
            response('error', 'Missing event_id.', null, 400); // Missing event_id
        }
        break;

    case 'DELETE':
        if ($event_id) {
            if (in_array('Admin', $user_roles)) { // Check if user has the 'admin' role
                $eventController->deleteEvent($event_id);
            } else {
                response('error', 'Unauthorized to delete events.', null, 403); // User not authorized
            }
        } else {
            response('error', 'Missing event_id.', null, 400); // Missing event_id
        }
        break;

    default:
        response('error', 'Method not allowed.', null, 405); // Method not allowed
        break;
}
?>
