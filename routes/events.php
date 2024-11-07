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
if (in_array($request_method, ['GET','POST', 'DELETE'])) {
    $user_roles = $jwtHelper->getRoles(); // Retrieve user roles from the token
}
switch ($request_method) {
    case 'GET':
        if (in_array('Admin', $user_roles)) {
            // Admin user can get all proposed events (regardless of status)
            $eventController->getAllProposedEventsForAdmin();
            return; // Prevent further execution
        } elseif (in_array('Propose', $user_roles)) { // Corrected from else to elseif
            // Propose user can get their own proposed events (regardless of status)
            $user_id = $jwtHelper->getUserId(); // Get the user ID from JWT
            $eventController->getProposeUserEvents($user_id);
            return; // Prevent further execution
        } else {
            response('error', 'Unauthorized to access events.', null, 403); // User not authorized
            return; // Prevent further execution
        }
        break;

        case 'POST':
            if ($event_id) {
                // Update event if an event_id is provided and the user has the appropriate role
                if (in_array('Admin', $user_roles) || in_array('Propose', $user_roles)) { // Check for roles
                    $eventController->updateEvent($event_id);
                } else {
                    response('error', 'Unauthorized to update events.', null, 403); // User not authorized
                }
            } else {
                // No event_id provided, so create a new event
                if (in_array('Propose', $user_roles)) { // Check if user has 'Propose' role
                    $eventController->createEvent(); // Call createEvent method
                } else {
                    response('error', 'Unauthorized to create events.', null, 403); // User not authorized
                }
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
