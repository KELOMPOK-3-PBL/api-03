<?php
require_once '../vendor/autoload.php'; // Include Composer's autoloader
require_once '../config/jwt_config.php'; // Include your JWT configuration
use Firebase\JWT\JWT;
use Firebase\JWT\Key; 

class EventController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Function to set JSON headers and CORS headers
    private function setHeaders() {
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Max-Age: 3600");
    }

    // Get user from JWT
    private function getUserFromJWT() {
        $this->setHeaders();
        
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'No token provided.'], JSON_PRETTY_PRINT);
            return false;
        }

        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        if (!$jwt) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Token is not valid.'], JSON_PRETTY_PRINT);
            return false;
        }

        try {
            // Decode the JWT without passing headers by reference
            $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256')); // 'HS256' or the actual algorithm you're using
            return (array) $decoded;
        } catch (Exception $e) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Token is invalid: ' . $e->getMessage()], JSON_PRETTY_PRINT);
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
        $this->setHeaders();
    
        $roles = $this->checkUserRole();
        if (!$roles) return;
    
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null; // New search term
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
    
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
    
        $query .= " ORDER BY e." . $sortBy . " " . $sortOrder;
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
    
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        echo json_encode([
            'status' => 'success',
            'message' => 'Events retrieved successfully.',
            'code' => 200,
            'data' => $events
        ], JSON_PRETTY_PRINT);
    }
    

    // Create a new event
    public function createEvent() {
        $this->setHeaders();

        $roles = $this->checkUserRole();
        if (!$roles) return;

        if (!in_array('Propose', $roles)) {
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
            echo json_encode([
                'status' => 'success',
                'message' => 'Event created successfully.',
                'code' => 201,
                'data' => null
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Event creation failed.',
                'code' => 500,
                'data' => null
            ], JSON_PRETTY_PRINT);
        }
    }

    // Update an existing event
    public function updateEvent($event_id) {
        $this->setHeaders();

        $roles = $this->checkUserRole();
        if (!$roles) return;

        $data = json_decode(file_get_contents("php://input"));

        if (in_array('Admin', $roles)) {
            // Admin can only edit note and admin_user_id
            $stmt = $this->db->prepare("UPDATE event SET admin_user_id = ?, note = ?, status = 'pending' WHERE event_id = ?");
            if ($stmt->execute([$data->admin_user_id, $data->note, $event_id])) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Event updated successfully.',
                    'code' => 200,
                    'data' => null
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Event update failed.',
                    'code' => 500,
                    'data' => null
                ], JSON_PRETTY_PRINT);
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
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Event updated successfully.',
                    'code' => 200,
                    'data' => null
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Event update failed.',
                    'code' => 500,
                    'data' => null
                ], JSON_PRETTY_PRINT);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only admin or propose users can update events.',
                'code' => 403,
                'data' => null
            ], JSON_PRETTY_PRINT);
        }
    }

    // Delete an event
    public function deleteEvent($event_id) {
        $this->setHeaders();

        $roles = $this->checkUserRole();
        if (!$roles) return;

        if (!in_array('Admin', $roles)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only admin can delete events.',
                'code' => 403,
                'data' => null
            ], JSON_PRETTY_PRINT);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM event WHERE event_id = ?");
        if ($stmt->execute([$event_id])) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Event deleted successfully.',
                'code' => 200,
                'data' => null
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Event deletion failed.',
                'code' => 500,
                'data' => null
            ], JSON_PRETTY_PRINT);
        }
    }
}
