<?php
class Event {
    private $conn;
    private $table_name = "event";

    public $event_id;
    public $propose_user_id;
    public $title;
    public $date_add;
    public $category_id;
    public $admin_user_id;
    public $description;
    public $poster;
    public $location;
    public $place;
    public $quota;
    public $date_start;
    public $date_end;
    public $updated;
    public $status;
    public $note;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Create new event
    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (event_id, propose_user_id, title, date_add, category_id, admin_user_id, description, poster, location, place, quota, date_start, date_end, status, note)
                  VALUES(:event_id, :propose_user_id, :title, :date_add, :category_id, :admin_user_id, :description, :poster, :location, :place, :quota, :date_start, :date_end, :status, :note)";
        $stmt = $this->conn->prepare($query);

        // sanitize input
        $this->event_id = htmlspecialchars(strip_tags($this->event_id));
        $this->propose_user_id = htmlspecialchars(strip_tags($this->propose_user_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->date_add = htmlspecialchars(strip_tags($this->date_add));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->admin_user_id = htmlspecialchars(strip_tags($this->admin_user_id));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->poster = htmlspecialchars(strip_tags($this->poster));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->place = htmlspecialchars(strip_tags($this->place));
        $this->quota = htmlspecialchars(strip_tags($this->quota));
        $this->date_start = htmlspecialchars(strip_tags($this->date_start));
        $this->date_end = htmlspecialchars(strip_tags($this->date_end));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->note = htmlspecialchars(strip_tags($this->note));

        // bind parameters
        $stmt->bindParam(":event_id", $this->event_id);
        $stmt->bindParam(":propose_user_id", $this->propose_user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":date_add", $this->date_add);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":admin_user_id", $this->admin_user_id);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":poster", $this->poster);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":place", $this->place);
        $stmt->bindParam(":quota", $this->quota);
        $stmt->bindParam(":date_start", $this->date_start);
        $stmt->bindParam(":date_end", $this->date_end);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":note", $this->note);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Read all events (with propose_username, status_name, and category_name)
    public function read() {
        $query = "SELECT 
                    e.event_id,
                    u.username AS propose_user_id,  -- Replace propose_user_id with username
                    e.title,
                    e.date_add,
                    c.category_name AS category_id,  -- Replace category_id with category_name
                    e.description,
                    e.poster,
                    e.location,
                    e.place,
                    e.quota,
                    e.date_start,
                    e.date_end,
                    e.updated,
                    e.admin_user_id,
                    e.note,
                    s.status_name AS status        -- Replace status with status_name
                    FROM " . $this->table_name . " e
                    LEFT JOIN user u ON e.propose_user_id = u.user_id
                    LEFT JOIN event_status s ON e.status = s.status_id
                    LEFT JOIN category c ON e.category_id = c.category_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Find event by ID (with propose_username, status_name, and category_name)
    public function find($event_id) {
        $query = "SELECT 
                    e.event_id,
                    u.username AS propose_user_id,  -- Replace propose_user_id with username
                    e.title,
                    e.date_add,
                    c.category_name AS category_id,  -- Added comma here
                    e.description,
                    e.poster,
                    e.location,
                    e.place,
                    e.quota,
                    e.date_start,
                    e.date_end,
                    e.updated,
                    e.admin_user_id,
                    e.note,
                    s.status_name AS status         -- No comma after the last selected field
                    FROM " . $this->table_name . " e
                    LEFT JOIN user u ON e.propose_user_id = u.user_id
                    LEFT JOIN event_status s ON e.status = s.status_id
                    LEFT JOIN category c ON e.category_id = c.category_id
                    WHERE e.event_id = :event_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':event_id', $event_id);
        $stmt->execute();
        return $stmt;
    }

    // Update event
    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET propose_user_id = :propose_user_id, title = :title, date_add = :date_add, category_id = :category_id, 
                      admin_user_id = :admin_user_id, description = :description, poster = :poster, location = :location, 
                      place = :place, quota = :quota, date_start = :date_start, date_end = :date_end, 
                      status = :status, note = :note, updated = NOW() 
                  WHERE event_id = :event_id";
        
        $stmt = $this->conn->prepare($query);

        // sanitize input
        $this->event_id = htmlspecialchars(strip_tags($this->event_id));
        $this->propose_user_id = htmlspecialchars(strip_tags($this->propose_user_id));
        $this->title = htmlspecialchars(strip_tags($this->title));
        $this->date_add = htmlspecialchars(strip_tags($this->date_add));
        $this->category_id = htmlspecialchars(strip_tags($this->category_id));
        $this->admin_user_id = htmlspecialchars(strip_tags($this->admin_user_id));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->poster = htmlspecialchars(strip_tags($this->poster));
        $this->location = htmlspecialchars(strip_tags($this->location));
        $this->place = htmlspecialchars(strip_tags($this->place));
        $this->quota = htmlspecialchars(strip_tags($this->quota));
        $this->date_start = htmlspecialchars(strip_tags($this->date_start));
        $this->date_end = htmlspecialchars(strip_tags($this->date_end));
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->note = htmlspecialchars(strip_tags($this->note));

        // bind parameters
        $stmt->bindParam(":event_id", $this->event_id);
        $stmt->bindParam(":propose_user_id", $this->propose_user_id);
        $stmt->bindParam(":title", $this->title);
        $stmt->bindParam(":date_add", $this->date_add);
        $stmt->bindParam(":category_id", $this->category_id);
        $stmt->bindParam(":admin_user_id", $this->admin_user_id);
        $stmt->bindParam(":description", $this->description);
        $stmt->bindParam(":poster", $this->poster);
        $stmt->bindParam(":location", $this->location);
        $stmt->bindParam(":place", $this->place);
        $stmt->bindParam(":quota", $this->quota);
        $stmt->bindParam(":date_start", $this->date_start);
        $stmt->bindParam(":date_end", $this->date_end);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":note", $this->note);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    // Delete event
    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE event_id = :event_id";
        $stmt = $this->conn->prepare($query);

        $this->event_id = htmlspecialchars(strip_tags($this->event_id));
        $stmt->bindParam(':event_id', $this->event_id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }
}
?>
