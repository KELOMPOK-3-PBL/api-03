<?php
class EventController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get all events
    public function getAllEvents() {
        $query = "SELECT * FROM event";
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

    // Get a single event
    public function getEventById($event_id) {
        $query = "SELECT * FROM event WHERE id = ?";
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
    public function createEvent() {
        $data = json_decode(file_get_contents("php://input"));

        // Prepare SQL statement
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
            $data->admin_user_id,
            $data->status
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
        $data = json_decode(file_get_contents("php://input"));

        // Prepare SQL statement
        $stmt = $this->db->prepare("UPDATE event SET title = ?, category_id = ?, description = ?, poster = ?, location = ?, place = ?, quota = ?, date_start = ?, date_end = ?, admin_user_id = ?, status = ? WHERE id = ?");

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
            $data->admin_user_id,
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
        $stmt = $this->db->prepare("DELETE FROM event WHERE id = ?");
        
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
