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

    // Get all events with joined user, category, and status
    public function getAllEvents() {
        $this->setHeaders(); // Set the headers
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
                es.status_name AS status,
                e.note
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN event_status es ON e.status = es.status_id";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            'status' => 'success',
            'message' => 'Events retrieved successfully.',
            'code' => 200,
            'data' => $events
        ], JSON_PRETTY_PRINT);
    }

    // Get a single event by ID with joined user, category, and status
    public function getEventById($event_id) {
        $this->setHeaders(); // Set the headers
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
                es.status_name AS status,
                e.note
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN event_status es ON e.status = es.status_id
            WHERE e.event_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$event_id]);
        
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($event) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Event retrieved successfully.',
                'code' => 200,
                'data' => $event
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Event not found.',
                'code' => 404,
                'data' => null
            ], JSON_PRETTY_PRINT);
        }
    }

    // Create a new event
    public function createEvent($userRole) {
        $this->setHeaders(); // Set the headers
        $data = json_decode(file_get_contents("php://input"));

        // Check user role
        if ($userRole !== 'Propose') {
            echo json_encode([
                'status' => 'error',
                'message' => 'Only propose users can create events.',
                'code' => 403,
                'data' => null
            ], JSON_PRETTY_PRINT);
            return;
        }

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
            'proposed' // Set status to 'reviewing'
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
    public function updateEvent($event_id, $userRole) {
        $this->setHeaders(); // Set the headers

        // Check if user role is Admin or Propose
        if ($userRole !== 'Admin' && $userRole !== 'Propose') {
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
        $stmt = $this->db->prepare("UPDATE event SET title = ?, category_id = ?, description = ?, poster = ?, location = ?, place = ?, quota = ?, date_start = ?, date_end = ?, admin_user_id = ?, note = ?, status = ? WHERE event_id = ?");

        // Admin can set the admin_user_id and the status
        $admin_user_id = ($userRole === 'Admin') ? $data->admin_user_id : null;
        $status = ($userRole === 'Admin') ? $data->status : 'reviewing'; // Admin can set any status; Propose sets it to 'reviewing'

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
            $admin_user_id, // Only set if user is Admin
            $data->note, // Note from Admin for Propose
            $status, // Status can be set by Admin or defaults to 'reviewing' for Propose
            $event_id // Event ID is used to specify which record to update
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
?>
