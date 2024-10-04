<?php
require_once '../controllers/UserController.php';
require_once '../controllers/EventController.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Extract query parameters
parse_str($_SERVER['QUERY_STRING'], $query);

switch ($path) {
    case '/users':
        $controller = new UserController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->store();
        } elseif (isset($query['user_id'])) {
            $controller->show($query['user_id']);  // GET a specific user
        } else {
            $controller->index();  // GET all users
        }
        break;
    case '/users/update':
        $controller = new UserController();
        $controller->update();
        break;
    case '/users/delete':
        $controller = new UserController();
        $controller->delete();
        break;

    case '/events':
        $controller = new EventController();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $controller->store();
        } elseif (isset($query['event_id'])) {
            $controller->show($query['event_id']);  // GET a specific event
        } else {
            $controller->index();  // GET all events
        }
        break;
    case '/events/update':
        $controller = new EventController();
        $controller->update();
        break;
    case '/events/delete':
        $controller = new EventController();
        $controller->delete();
        break;
}
?>
