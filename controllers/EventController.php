<?php
require_once '../models/Event.php';

class EventController {
    private $event;

    public function __construct($db) {
        $this->event = new Event($db);
    }

    // Get all events
    public function index() {
        $stmt = $this->event->read();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json'); // Set header Content-Type
        echo json_encode([
            'status' => 'success',
            'data' => $events
        ], JSON_PRETTY_PRINT); // Menggunakan JSON_PRETTY_PRINT untuk format yang lebih rapi
    }

    // Get event by ID
    public function show($event_id) {
        $stmt = $this->event->find($event_id);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        header('Content-Type: application/json'); // Set header Content-Type
        if ($event) {
            echo json_encode([
                'status' => 'success',
                'data' => $event
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Event not found.'
            ], JSON_PRETTY_PRINT);
        }
    }

    // Create new event
    public function store() {
        $data = json_decode(file_get_contents("php://input"));

        // Mengatur data
        $this->event->event_id = $data->event_id;
        $this->event->propose_user_id = $data->propose_user_id;
        $this->event->title = $data->title;
        $this->event->date_add = date('Y-m-d H:i:s');
        $this->event->category_id = $data->category_id;
        $this->event->admin_user_id = $data->admin_user_id;
        $this->event->description = $data->description;
        $this->event->poster = $data->poster;
        $this->event->location = $data->location;
        $this->event->place = $data->place;
        $this->event->quota = $data->quota;
        $this->event->date_start = $data->date_start;
        $this->event->date_end = $data->date_end;
        $this->event->status = $data->status;
        $this->event->note = $data->note;

        header('Content-Type: application/json'); // Set header Content-Type
        if ($this->event->create()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Event created successfully.'
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Event creation failed.'
            ], JSON_PRETTY_PRINT);
        }
    }

    // Update event
    public function update($event_id) {
        $data = json_decode(file_get_contents("php://input"));
        
        $this->event->event_id = $event_id;
        $this->event->propose_user_id = $data->propose_user_id;
        $this->event->title = $data->title;
        $this->event->date_add = $data->date_add;
        $this->event->category_id = $data->category_id;
        $this->event->admin_user_id = $data->admin_user_id;
        $this->event->description = $data->description;
        $this->event->poster = $data->poster;
        $this->event->location = $data->location;
        $this->event->place = $data->place;
        $this->event->quota = $data->quota;
        $this->event->date_start = $data->date_start;
        $this->event->date_end = $data->date_end;
        $this->event->status = $data->status;
        $this->event->note = $data->note;

        header('Content-Type: application/json'); // Set header Content-Type
        if ($this->event->update()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Event updated successfully.'
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Event update failed.'
            ], JSON_PRETTY_PRINT);
        }
    }

    // Delete event
    public function destroy($event_id) {
        $this->event->event_id = $event_id;
        header('Content-Type: application/json'); // Set header Content-Type
        if ($this->event->delete()) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Event deleted successfully.'
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Event deletion failed.'
            ], JSON_PRETTY_PRINT);
        }
    }
}
?>
