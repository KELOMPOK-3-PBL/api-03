<?php
require_once '../vendor/autoload.php'; // Include Composer's autoloader
require_once '../config/JwtConfig.php'; // Include your JWT configuration
require_once '../helpers/ResponseHelpers.php'; // Include your response helper functions
require_once '../helpers/FileUploadHelper.php'; // Include file upload helper
require_once '../helpers/JwtHelpers.php'; // Include the JWT helper

class EventController {
    private $db;
    private $jwtHelper;

    public function __construct($db) {
        $this->db = $db;
        $this->jwtHelper = new JWTHelper(); // Instantiate JWTHelper
    }

    private function getRoles() {
        return $this->jwtHelper->getRoles(); // Use JWTHelper to get roles
    }

    private function getUserId() {
        return $this->jwtHelper->getUserId(); // Use JWTHelper to get user ID
    }

    // Get all events with optional filters
    public function getAllEvents() {
        // Retrieve filters from query parameters
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null; // New status filter
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
    
        $query = "
            SELECT 
                e.event_id, e.title, e.date_add, u.username AS propose_user,
                c.category_name AS category, e.description, e.poster, e.location,
                e.place, e.quota, e.date_start, e.date_end, a.username AS admin_user,
                s.status_name AS status, e.note
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN status s ON e.status = s.status_id
            WHERE s.status_name = 'approved'";
    
        // Initialize an array for parameters
        $params = [];
    
        if ($category) {
            $query .= " AND c.category_name = :category";
            $params[':category'] = $category;
        }
        if ($dateFrom) {
            $query .= " AND e.date_start >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $query .= " AND e.date_end <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }
        if ($searchTerm) {
            $query .= " AND (e.title LIKE :searchTerm OR e.description LIKE :searchTerm)";
            $params[':searchTerm'] = "%$searchTerm%";
        }
        if ($status) {
            $query .= " AND s.status_name = :status"; // Filter by status
            $params[':status'] = $status;
        }
    
        $query .= " ORDER BY $sortBy $sortOrder";
    
        // Prepare and execute the statement
        $stmt = $this->db->prepare($query);
        
        // Bind parameters in a loop
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        response('success', 'Approved events retrieved successfully.', $events, 200);
    }
    
    public function getProposeUserEvents($userId) {
        // Retrieve filters from query parameters
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null; // New status filter
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
    
        $query = "
            SELECT 
                e.event_id, e.title, e.date_add, u.username AS propose_user,
                c.category_name AS category, e.description, e.poster, e.location,
                e.place, e.quota, e.date_start, e.date_end, a.username AS admin_user,
                s.status_name AS status, e.note
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN status s ON e.status = s.status_id
            WHERE e.propose_user_id = :userId";
    
        // Initialize an array for parameters
        $params = [':userId' => $userId];
    
        if ($category) {
            $query .= " AND c.category_name = :category";
            $params[':category'] = $category;
        }
        if ($dateFrom) {
            $query .= " AND e.date_start >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $query .= " AND e.date_end <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }
        if ($searchTerm) {
            $query .= " AND (e.title LIKE :searchTerm OR e.description LIKE :searchTerm)";
            $params[':searchTerm'] = "%$searchTerm%";
        }
        if ($status) {
            $query .= " AND s.status_name = :status"; // Filter by status
            $params[':status'] = $status;
        }
    
        $query .= " ORDER BY $sortBy $sortOrder";
    
        // Prepare and execute the statement
        $stmt = $this->db->prepare($query);
        
        // Bind parameters in a loop
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        response('success', 'Events for Propose user retrieved successfully.', $events, 200);
    }
    
    public function getAllProposedEventsForAdmin() {
        // Retrieve filters from query parameters
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null; // New status filter
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
    
        $query = "
            SELECT 
                e.event_id, e.title, e.date_add, u.username AS propose_user,
                c.category_name AS category, e.description, e.poster, e.location,
                e.place, e.quota, e.date_start, e.date_end, a.username AS admin_user,
                s.status_name AS status, e.note
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN status s ON e.status = s.status_id
            WHERE 1=1"; // Allows flexible filtering
    
        // Initialize an array for parameters
        $params = [];
    
        if ($category) {
            $query .= " AND c.category_name = :category";
            $params[':category'] = $category;
        }
        if ($dateFrom) {
            $query .= " AND e.date_start >= :dateFrom";
            $params[':dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $query .= " AND e.date_end <= :dateTo";
            $params[':dateTo'] = $dateTo;
        }
        if ($searchTerm) {
            $query .= " AND (e.title LIKE :searchTerm OR e.description LIKE :searchTerm)";
            $params[':searchTerm'] = "%$searchTerm%";
        }
        if ($status) {
            $query .= " AND s.status_name = :status"; // Filter by status
            $params[':status'] = $status;
        }
    
        $query .= " ORDER BY $sortBy $sortOrder";
    
        // Prepare and execute the statement
        $stmt = $this->db->prepare($query);
        
        // Bind parameters in a loop
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        response('success', 'All proposed events retrieved for Admin.', $events, 200);
    }
    

    // Get event by ID
    public function getEventById($eventId) {
        $this->jwtHelper->decodeJWT(); // Verify JWT

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
                e.schedule,
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
        $this->jwtHelper->decodeJWT(); // Verify JWT
        $roles = $this->getRoles(); // Get roles from JWT
        if (!in_array('Propose', $roles)) {
            response('error', 'Unauthorized.', null, 403);
            return;
        }
    
        // Use $_POST for non-file data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        
        // Check if the 'poster' file exists in $_FILES
        if (!isset($_FILES['poster'])) {
            response('error', 'Poster file is required.', null, 400);
            return;
        }
    
        $fileUploadHelper = new FileUploadHelper(); // No path argument needed
        $poster = $fileUploadHelper->uploadFile($_FILES['poster'], 'poster');
        
        // Now access other form fields using $_POST
        $location = $_POST['location'] ?? '';
        $place = $_POST['place'] ?? '';
        $quota = (int)($_POST['quota'] ?? 0); 
        $dateStart = $_POST['date_start'] ?? '';
        $dateEnd = $_POST['date_end'] ?? '';
        $schedule = $_POST['schedule'] ?? '';  // Added schedule
        $categoryId = $_POST['category_id'] ?? null;
    
        $proposeUserId = $this->getUserId(); // Get user ID from JWT
        $dateAdd = date('Y-m-d H:i:s');
        $status = 1;
    
        if (empty($title) || empty($description) || empty($dateStart) || empty($dateEnd) || $quota <= 0) {
            response('error', 'All fields are required and quota must be greater than 0.', null, 400);
            return;
        }
    
        $stmt = $this->db->prepare("
            INSERT INTO event (title, description, poster, location, place, quota, date_start, date_end, schedule, propose_user_id, category_id, date_add, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
    
        if ($stmt->execute([$title, $description, $poster, $location, $place, $quota, $dateStart, $dateEnd, $schedule, $proposeUserId, $categoryId, $dateAdd, $status])) {
            response('success', 'Event created successfully.', null, 201);
        } else {
            response('error', 'Failed to create event.', null, 500);
        }
    }
    

    // Update an event by ID
    public function updateEvent($eventId) {
        $this->jwtHelper->decodeJWT(); // Verify JWT
        $roles = $this->getRoles(); // Get roles from JWT
    
        // Check if the user has 'Propose' role for general event modification
        if (!in_array('Propose', $roles) && !in_array('Admin', $roles)) {
            response('error', 'Unauthorized.', null, 403);
            return;
        }
    
        // Fetch current event data by eventId
        $stmt = $this->db->prepare("SELECT * FROM event WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch();
    
        if (!$event) {
            response('error', 'Event not found.', null, 404);
            return;
        }
    
        // Use $_POST for non-file data
        $title = $_POST['title'] ?? $event['title'];
        $description = $_POST['description'] ?? $event['description'];
        
        // Check if the 'poster' file exists in $_FILES for an update
        if (isset($_FILES['poster'])) {
            // Remove the old poster if it's being updated
            $fileUploadHelper = new FileUploadHelper(); // No path argument needed
            $poster = $fileUploadHelper->uploadFile($_FILES['poster'], 'poster');
        } else {
            // Keep the old poster if not updating
            $poster = $event['poster'];
        }
    
        $location = $_POST['location'] ?? $event['location'];
        $place = $_POST['place'] ?? $event['place'];
        $quota = (int)($_POST['quota'] ?? $event['quota']);
        $dateStart = $_POST['date_start'] ?? $event['date_start'];
        $dateEnd = $_POST['date_end'] ?? $event['date_end'];
        $schedule = $_POST['schedule'] ?? $event['schedule'];
        $categoryId = $_POST['category_id'] ?? $event['category_id'];
    
        $proposeUserId = $this->getUserId(); // Get user ID from JWT
        $dateAdd = $event['date_add']; // Keep original add date
        $status = $event['status']; // Keep original status (or modify if needed)
    
        // Check for required fields
        if (empty($title) || empty($description) || empty($dateStart) || empty($dateEnd) || $quota <= 0) {
            response('error', 'All fields are required and quota must be greater than 0.', null, 400);
            return;
        }
    
        // Admin role: Update admin_user_id, note, and set status to 2
        if (in_array('Admin', $roles)) {
            $adminUserId = $this->getUserId(); // The admin user making the update
            $note = $_POST['note'] ?? ''; // Note can be updated by Admin
            $status = 2; // Set status to 2 for Admin updates
        } else {
            $adminUserId = $event['admin_user_id']; // Keep the existing admin_user_id
            $note = $event['note']; // Keep the existing note
        }
    
        // Update event in the database
        $stmt = $this->db->prepare("
            UPDATE event SET 
                title = ?, 
                description = ?, 
                poster = ?, 
                location = ?, 
                place = ?, 
                quota = ?, 
                date_start = ?, 
                date_end = ?, 
                schedule = ?, 
                category_id = ?, 
                admin_user_id = ?, 
                note = ?, 
                date_add = ?, 
                status = ?
            WHERE event_id = ?
        ");
    
        if ($stmt->execute([
            $title, $description, $poster, $location, $place, $quota, $dateStart, $dateEnd, $schedule, 
            $categoryId, $adminUserId, $note, $dateAdd, $status, $eventId
        ])) {
            response('success', 'Event updated successfully.', null, 200);
        } else {
            response('error', 'Failed to update event.', null, 500);
        }
    }
    
    
    // Delete an event by ID
    public function deleteEvent($eventId) {
        $this->jwtHelper->decodeJWT(); // Verify JWT
        $roles = $this->getRoles(); // Get roles from JWT
        if (!in_array('Admin', $roles)) {
            response('error', 'Unauthorized.', null, 403);
            return;
        }

        $stmt = $this->db->prepare("DELETE FROM event WHERE event_id = ?");
        if ($stmt->execute([$eventId])) {
            response('success', 'Event deleted successfully.', null, 200);
        } else {
            response('error', 'Failed to delete event.', null, 500);
        }
    }
}
