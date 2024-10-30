<?php
require_once '../vendor/autoload.php'; // Include Composer's autoloader
require_once '../config/JwtConfig.php'; // Include your JWT configuration
require_once '../helpers/ResponseHelpers.php'; // Include your response helper functions

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class EventController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get user from JWT stored in cookies
    private function getUserFromJWT() {
        if (!isset($_COOKIE['jwt'])) {
            response('error', 'No token provided.', null, 403);
            return false;
        }

        $jwt = $_COOKIE['jwt'];
        if (!$jwt) {
            response('error', 'Token is not valid.', null, 403);
            return false;
        }

        try {
            $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
            return (array) $decoded;
        } catch (Exception $e) {
            response('error', 'Token is invalid: ' . $e->getMessage(), null, 403);
            return false;
        }
    }

    // Check user role and return roles or error response
    private function checkUserRole() {
        $userData = $this->getUserFromJWT();
        if (!$userData) return false;

        return $userData['roles'] ?? [];
    }

    // Get all events with optional filters
    public function getAllEvents() {
        $roles = $this->checkUserRole();
        if (!$roles) return;

        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';

        // Get pagination parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1; // Default to page 1
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5; // Default to 5

        // Calculate the offset for pagination
        $offset = ($page - 1) * $limit;

        $query = "
            SELECT 
                e.event_id,
                e.title,
                e.date_add,
                u.username AS propose_user,
                c.category_name AS category,
                e.description,
                e.poster,
                e.location,
                e.place,
                e.quota,
                e.date_start,
                e.date_end,
                a.username AS admin_user,
                s.status_name AS status,
                e.note
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN status s ON e.status = s.status_id
            WHERE 1=1";

        // Add filters
        if ($status) {
            $query .= " AND s.status_name = :status";
        }
        if ($category) {
            $query .= " AND c.category_name = :category";
        }
        if ($dateFrom) {
            $query .= " AND e.date_start >= :dateFrom";
        }
        if ($dateTo) {
            $query .= " AND e.date_end <= :dateTo";
        }
        if ($searchTerm) {
            $query .= " AND (e.title LIKE :searchTerm OR e.description LIKE :searchTerm)";
        }

        $query .= " ORDER BY $sortBy $sortOrder LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);

        // Bind parameters
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        if ($category) {
            $stmt->bindParam(':category', $category);
        }
        if ($dateFrom) {
            $stmt->bindParam(':dateFrom', $dateFrom);
        }
        if ($dateTo) {
            $stmt->bindParam(':dateTo', $dateTo);
        }
        if ($searchTerm) {
            $searchTerm = "%$searchTerm%";
            $stmt->bindParam(':searchTerm', $searchTerm);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Return events
        response('success', 'Events retrieved successfully.', $events, 200);
    }

    // Get event by ID
    public function getEventById($eventId) {
        $roles = $this->checkUserRole();
        if (!$roles) return;

        $stmt = $this->db->prepare("
            SELECT 
                e.event_id,
                e.title,
                e.description,
                e.poster,
                e.location,
                e.place,
                e.quota,
                e.date_start,
                e.date_end,
                u.username AS propose_user,
                c.category_name AS category,
                s.status_name AS status,
                e.note
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN status s ON e.status = s.status_id
            WHERE e.event_id = ?
        ");

        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($event) {
            response('success', 'Event retrieved successfully.', $event, 200);
        } else {
            response('error', 'Event not found.', null, 404);
        }
    }

    // Create a new event
    public function createEvent() {
        $roles = $this->checkUserRole();
        if (!$roles) return;

        $data = json_decode(file_get_contents("php://input"), true);

        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $poster = $data['poster'] ?? '';
        $location = $data['location'] ?? '';
        $place = $data['place'] ?? '';
        $quota = $data['quota'] ?? 0;
        $dateStart = $data['date_start'] ?? '';
        $dateEnd = $data['date_end'] ?? '';
        $proposeUserId = $data['propose_user_id'] ?? null; // Assuming you want to set the proposing user ID
        $categoryId = $data['category_id'] ?? null; // Assuming you want to set the category ID

        // Validate input
        if (empty($title) || empty($description) || empty($dateStart) || empty($dateEnd) || $quota <= 0) {
            response('error', 'All fields are required and quota must be greater than 0.', null, 400);
            return;
        }

        // Insert new event
        $stmt = $this->db->prepare("
            INSERT INTO event (title, description, poster, location, place, quota, date_start, date_end, propose_user_id, category_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$title, $description, $poster, $location, $place, $quota, $dateStart, $dateEnd, $proposeUserId, $categoryId])) {
            response('success', 'Event created successfully.', null, 201);
        } else {
            response('error', 'Failed to create event.', null, 500);
        }
    }

    // Update an event by ID
    public function updateEvent($eventId) {
        $roles = $this->checkUserRole();
        if (!$roles) return;

        $data = json_decode(file_get_contents("php://input"), true);

        $title = $data['title'] ?? '';
        $description = $data['description'] ?? '';
        $poster = $data['poster'] ?? '';
        $location = $data['location'] ?? '';
        $place = $data['place'] ?? '';
        $quota = $data['quota'] ?? 0;
        $dateStart = $data['date_start'] ?? '';
        $dateEnd = $data['date_end'] ?? '';
        $categoryId = $data['category_id'] ?? null; // Assuming you want to update the category ID

        // Validate input
        if (empty($title) || empty($description) || empty($dateStart) || empty($dateEnd) || $quota <= 0) {
            response('error', 'All fields are required and quota must be greater than 0.', null, 400);
            return;
        }

        // Update event
        $stmt = $this->db->prepare("
            UPDATE event 
            SET title = ?, description = ?, poster = ?, location = ?, place = ?, quota = ?, date_start = ?, date_end = ?, category_id = ?
            WHERE event_id = ?
        ");

        if ($stmt->execute([$title, $description, $poster, $location, $place, $quota, $dateStart, $dateEnd, $categoryId, $eventId])) {
            response('success', 'Event updated successfully.', null, 200);
        } else {
            response('error', 'Failed to update event or no changes made.', null, 500);
        }
    }

    // Delete an event by ID
    public function deleteEvent($eventId) {
        $roles = $this->checkUserRole();
        if (!$roles) return;

        $stmt = $this->db->prepare("DELETE FROM event WHERE event_id = ?");
        
        if ($stmt->execute([$eventId])) {
            response('success', 'Event deleted successfully.', null, 200);
        } else {
            response('error', 'Failed to delete event or event not found.', null, 500);
        }
    }
}
?>
