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
        $categoryId = $_POST['category_id'] ?? null;

        $proposeUserId = $this->getUserId(); // Get user ID from JWT
        $dateAdd = date('Y-m-d H:i:s');
        $status = 1;

        if (empty($title) || empty($description) || empty($dateStart) || empty($dateEnd) || $quota <= 0) {
            response('error', 'All fields are required and quota must be greater than 0.', null, 400);
            return;
        }

        $stmt = $this->db->prepare("
            INSERT INTO event (title, description, poster, location, place, quota, date_start, date_end, propose_user_id, category_id, date_add, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt->execute([$title, $description, $poster, $location, $place, $quota, $dateStart, $dateEnd, $proposeUserId, $categoryId, $dateAdd, $status])) {
            response('success', 'Event created successfully.', null, 201);
        } else {
            response('error', 'Failed to create event.', null, 500);
        }
    }

    // Update an event by ID
    public function updateEvent($eventId) {
        $this->jwtHelper->decodeJWT(); // Verifikasi JWT
        $roles = $this->getRoles(); // Ambil roles dari JWT
    
        var_dump('Incoming Request Method:', $_SERVER['REQUEST_METHOD']);
        var_dump('POST Data:', $_POST);
        var_dump('FILES Data:', $_FILES);
    
        // Pengecekan izin peran pengguna
        if (!in_array('Admin', $roles) && !in_array('Propose', $roles)) {
            response('error', 'Unauthorized.', null, 403);
            return;
        }
    
        // Inisialisasi array untuk menampung field yang akan diupdate dan parameter
        $fieldsToUpdate = [];
        $params = [];
    
        // Jika metode adalah PATCH, ambil data dari php://input sebagai alternatif $_POST
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            parse_str(file_get_contents("php://input"), $_POST);
        }
    
        // Field untuk diupdate berdasarkan data POST
        $updateFields = [
            'title', 'description', 'location', 'place', 'quota', 
            'date_start', 'date_end', 'category_id'
        ];
    
        foreach ($updateFields as $field) {
            if (isset($_POST[$field])) {
                $fieldsToUpdate[] = "$field = ?";
                $params[] = ($field === 'quota') ? (int)$_POST[$field] : $_POST[$field];
            }
        }
    
        // Field khusus untuk Admin
        if (in_array('Admin', $roles)) {
            if (isset($_POST['admin_user_id'])) {
                $fieldsToUpdate[] = "admin_user_id = ?";
                $params[] = $_POST['admin_user_id'];
            }
            if (isset($_POST['note'])) {
                $fieldsToUpdate[] = "note = ?";
                $params[] = $_POST['note'];
            }
        }
    
        // Penanganan upload poster
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $fileUploadHelper = new FileUploadHelper();
            $newPoster = $fileUploadHelper->uploadFile($_FILES['poster'], 'poster');
            $fieldsToUpdate[] = "poster = ?";
            $params[] = $newPoster;
        }
    
        // Set status berdasarkan role
        $status = in_array('Propose', $roles) ? 1 : 2; // "reviewing" untuk propose, "pending" untuk admin
        $fieldsToUpdate[] = "status = ?";
        $params[] = $status;
    
        // Pengecekan apakah ada field yang diupdate
        if (empty($fieldsToUpdate)) {
            response('error', 'No fields to update.', null, 400);
            return;
        }
    
        // Debug: Cetak field yang diupdate dan parameter
        var_dump('Fields to update:', $fieldsToUpdate);
        var_dump('Parameters:', $params);
    
        // Siapkan dan eksekusi query update
        $sql = "UPDATE event SET " . implode(", ", $fieldsToUpdate) . ", updated = NOW() WHERE event_id = ?";
        $params[] = $eventId; // Tambahkan eventId ke params untuk klausa WHERE
    
        // Debug: Cetak query SQL
        var_dump('SQL Query:', $sql);
    
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($params)) {
            $rowCount = $stmt->rowCount();
            response('success', $rowCount > 0 ? 'Event updated successfully.' : 'No changes were made to the event.', null, 200);
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
