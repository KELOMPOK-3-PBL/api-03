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

switch ($request_method) {
    case 'GET':
        // Automatically call getEventCountsByStatus when the page loads
        $eventController->getEventCountsByStatus();
        break;

    default:
        response('error', 'Method not allowed.', null, 405); // Method not allowed for non-GET requests
        break;
}
?>
