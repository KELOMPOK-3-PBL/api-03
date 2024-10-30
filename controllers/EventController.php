<?php
require_once '../vendor/autoload.php'; // Include Composer's autoloader
require_once '../config/JwtConfig.php'; // Include your JWT configuration
require_once '../helpers/ResponseHelpers.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key; 

class EventController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get user from JWT
    private function getUserFromJWT() {
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            response('error', 'No token provided.', null, 403);
            return false;
        }

        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
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
        if ($dateFrom && $dateTo) {
            $query .= " AND e.date_start BETWEEN :date_from AND :date_to";
        } elseif ($dateFrom) {
            $query .= " AND e.date_start >= :date_from";
        } elseif ($dateTo) {
            $query .= " AND e.date_start <= :date_to";
        }

        // Add search condition
        if ($searchTerm) {
            $query .= " AND (e.title LIKE :search OR e.description LIKE :search)";
        }

        // Order and limit the results
        $query .= " ORDER BY e." . $sortBy . " " . $sortOrder . " LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($query);

        // Bind parameters
        if ($status) {
            $stmt->bindParam(':status', $status);
        }
        if ($category) {
            $stmt->bindParam(':category', $category);
        }
        if ($dateFrom) {
            $stmt->bindParam(':date_from', $dateFrom);
        }
        if ($dateTo) {
            $stmt->bindParam(':date_to', $dateTo);
        }
        if ($searchTerm) {
            $searchParam = '%' . $searchTerm . '%';
            $stmt->bindParam(':search', $searchParam);
        }

        // Bind pagination parameters
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        response('success', 'Events retrieved successfully.', $events, 200);
    }

    // Get an event by ID
    public function getEventById($event_id) {
        $roles = $this->checkUserRole();
        if (!$roles) return;

        // Prepare the query to fetch the event details
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
            WHERE e.event_id = :event_id
        ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':event_id', $event_id, PDO::PARAM_INT);
        $stmt->execute();
        
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

        if (!in_array('Propose', $roles)) {
            response('error', 'Only propose users can create events.', null, 403);
            echo json_encode([
                'status' => 'error',
                'message' => 'Only propose users can create events.',
                'code' => 403,
                'data' => null
            ], JSON_PRETTY_PRINT);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        $stmt = $this->db->prepare("INSERT INTO event (propose_user_id, title, date_add, category_id, description, poster, location, place, quota, date_start, date_end, admin_user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt->execute([
            $data->propose_user_id,
            $data->title,
            $data->date_add,
            $data->category_id,
            $data->description,
            $data->poster,
            $data->location,
            $data->place,
            $data->quota,
            $data->date_start,
            $data->date_end,
            null,
            'reviewing'
        ])) {
            response('success', 'Event created successfully.', $data, 201);
        } else {
            response('error', 'Event creation failed.', null, 500);
        }
    }

    // Update an existing event
    public function updateEvent($event_id) {
        $roles = $this->checkUserRole();
        if (!$roles) return;

        $data = json_decode(file_get_contents("php://input"));

        if (in_array('Admin', $roles)) {
            // Admin can only edit note and admin_user_id
            $stmt = $this->db->prepare("UPDATE event SET admin_user_id = ?, note = ?, status = 'pending' WHERE event_id = ?");
            if ($stmt->execute([$data->admin_user_id, $data->note, $event_id])) {
                response('success', 'Event updated successfully.', $data, 200);
            } else {
                response('error', 'Event update failed.', null, 500);
            }
        } elseif (in_array('Propose', $roles)) {
            // Propose can edit other fields
            $stmt = $this->db->prepare("UPDATE event SET title = ?, category_id = ?, description = ?, poster = ?, location = ?, place = ?, quota = ?, date_start = ?, date_end = ?, status = 'reviewing' WHERE event_id = ?");
            if ($stmt->execute([
                $data->title,
                $data->category_id,
                $data->description,
                $data->poster,
                $data->location,
                $data->place,
                $data->quota,
                $data->date_start,
                $data->date_end,
                $event_id
            ])) {
                response('success', 'Event updated successfully.', $data, 200);
            } else {
                response('error', 'Event update failed.', null, 500);
            }
        } else {
            response('error', 'Only admin or propose users can update events.', null, 403);
        }
    }

    // Delete an event
    public function deleteEvent($event_id) {
        $roles = $this->checkUserRole();
        if (!$roles) return;

        if (!in_array('Admin', $roles)) {
            response('error', 'Only admin can delete events.', null, 403);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM event WHERE event_id = ?");
        if ($stmt->execute([$event_id])) {
            response('success', 'Event deleted successfully.', null, 200);
        } else {
            response('error', 'Event deletion failed.', null, 500);
        }
    }
}
