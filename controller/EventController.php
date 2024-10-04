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
        echo json_encode($events);
    }

    // Get event by ID
    public function show($event_id) {
        $stmt = $this->event->find($event_id);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode($event);
    }

    // Create new event
    public function store() {
        // Assuming data comes from a POST request
        $data = json_decode(file_get_contents("php://input"));

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

        if ($this->event->create()) {
            echo json_encode(['message' => 'Event created successfully']);
        } else {
            echo json_encode(['message' => 'Event creation failed']);
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

        if ($this->event->update()) {
            echo json_encode(['message' => 'Event updated successfully']);
        } else {
            echo json_encode(['message' => 'Event update failed']);
        }
    }

    // Delete event
    public function destroy($event_id) {
        $this->event->event_id = $event_id;
        if ($this->event->delete()) {
            echo json_encode(['message' => 'Event deleted successfully']);
        } else {
            echo json_encode(['message' => 'Event deletion failed']);
        }
    }
}
?>
