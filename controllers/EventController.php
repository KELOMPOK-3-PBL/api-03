<?php
class EventController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Function to set JSON header
    private function setHeaders() {
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Max-Age: 3600");
    }

    // Check user role and return roles or error response
    public function checkUserRole() {
        $this->setHeaders(); // Set the headers
        
        // Check if the user is logged in
        if (!isset($_SESSION['user_id'])) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'User not logged in.'], JSON_PRETTY_PRINT);
            return false; // Return false to indicate unauthorized access
        }

        // Get user roles from the session
        $roles = $_SESSION['roles'] ?? []; // Assuming roles are stored in session during login

        // Return roles if found
        return $roles;
    }

    // Get all events with optional filters
    public function getAllEvents() {
        $this->setHeaders(); // Set the headers
        
        // Check user role
        $roles = $this->checkUserRole();
        if (!$roles) return; // Unauthorized access

        // Get filter parameters from the query string
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add'; // Default sorting by date_add
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC'; // Default sorting in ascending order

        // Start building the query
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
            WHERE 1=1"; // This allows dynamic filtering

        // Add filters if provided
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

        // Add sorting
        $query .= " ORDER BY e." . $sortBy . " " . $sortOrder;

        // Prepare the query
        $stmt = $this->db->prepare($query);

        // Bind parameters if applicable
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

        // Execute the query
        $stmt->execute();
        
        // Fetch the events
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
        $this->setHeaders(); // Set the headers

        // Check user role
        $roles = $this->checkUserRole();
        if (!$roles) return; // Unauthorized access

        // Check if the user has 'Propose' role
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

        // Prepare SQL statement with status as 'reviewing'
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
            null, // No admin_user_id initially
            'reviewing' // Set status to 'reviewing'
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
        $this->setHeaders(); // Set the headers

        // Check user role
        $roles = $this->checkUserRole();
        if (!$roles) return; // Unauthorized access

        // Check if user has Admin or Propose role
        if (!in_array('Admin', $roles) && !in_array('Propose', $roles)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only admin or propose users can update events.',
                'code' => 403,
                'data' => null
            ], JSON_PRETTY_PRINT);
            return;
        }

        $data = json_decode(file_get_contents("php://input"));

        // Prepare SQL statement
        $stmt = $this->db->prepare("UPDATE event SET title = ?, category_id = ?, description = ?, poster = ?, location = ?, place = ?, quota = ?, date_start = ?, date_end = ?, status = ? WHERE event_id = ?");
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
            $data->status,
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
    }

    // Delete an event
    public function deleteEvent($event_id) {
        $this->setHeaders(); // Set the headers

        // Check user role
        $roles = $this->checkUserRole();
        if (!$roles) return; // Unauthorized access

        // Check if user has Admin role
        if (!in_array('Admin', $roles)) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only admin users can delete events.',
                'code' => 403,
                'data' => null
            ], JSON_PRETTY_PRINT);
            return;
        }

        // Prepare SQL statement
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
