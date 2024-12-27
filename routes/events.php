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

// Ambil URI dari request
$event_id = isset($_GET['event_id']) ? $_GET['event_id'] : null;

// Initialize user roles (if needed for other methods)
$jwtHelper = new JWTHelper();
$user_roles = [];

// Check if JWT exists and is valid before proceeding
try {
    // Attempt to get roles from the JWT token
    $user_roles = $jwtHelper->getRoles(); // Retrieve user roles from the token
} catch (Exception $e) {
    // If no JWT or invalid JWT, deny access and return 401 Unauthorized
    response('error', 'Unauthorized access: ' . $e->getMessage(), null, 401);
    exit(); // Stop further execution
}

// Handle the request based on the HTTP method
switch ($request_method) {
    case 'GET':
        if ($event_id) {
            // If event ID is in URL, call getEventById
            $eventController->getEventById($event_id);
        } elseif (in_array('Admin', $user_roles) || in_array('Superadmin', $user_roles)) {
            // Admin or Superadmin can get events based on admin_user_id or all events
            $adminUserId = isset($_GET['admin_user_id']) ? (int)$_GET['admin_user_id'] : null;
            if ($adminUserId) {
                $eventController->getAllEventsAdminUser($adminUserId);  // Pass adminUserId to filter events
            } else {
                $eventController->getAllEventsAdminUser();  // Get all events if no admin_user_id is provided
            }
        } elseif (in_array('Propose', $user_roles)) {
            // Propose user can get their own proposed events
            $user_id = $jwtHelper->getUserId();  // Get user ID from JWT
            $eventController->getAllEventsProposeUser($user_id);  // Filter events by Propose user
        } else {
            response('error', 'Unauthorized to access events.', null, 403);
        }
        break;

    case 'POST':
        if ($event_id) {
            // Update event if an event_id is provided and the user has the appropriate role
            if (array_intersect(['Propose', 'Admin', 'Superadmin'], $user_roles)) {
                $eventController->updateEvent($event_id);
            } else {
                response('error', 'Unauthorized to update events.', null, 403); // User not authorized
            }
        } else {
            // No event_id provided, so create a new event
            if (in_array('Propose', $user_roles)) {
                $eventController->createEvent(); // Call createEvent method
            } else {
                response('error', 'Unauthorized to create events.', null, 403); // User not authorized
            }
        }
        break;

    case 'DELETE':
        if ($event_id) {
            if (in_array('Admin', $user_roles)) {
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
