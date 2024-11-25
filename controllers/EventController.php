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

    public function getEventCountsByStatus() {
        // Prepare SQL to count events grouped by status
        $query = "
            SELECT 
                s.status_id, 
                s.status_name, 
                COUNT(e.event_id) AS event_count
            FROM 
                status s
            LEFT JOIN 
                event e ON s.status_id = e.status
            GROUP BY 
                s.status_id, s.status_name
            ORDER BY 
                s.status_id ASC
        ";
    
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Send response with the counts
        response('success', 'Event counts by status retrieved successfully.', $counts, 200);
    }


    // Get all events with optional filters
    public function getAllEvents() {
        // Retrieve filters from query parameters
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null; // Let front end decide
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
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
            $query .= " AND s.status_name = :status";
            $params[':status'] = $status;
        }
    
        $query .= " ORDER BY $sortBy $sortOrder";
    
        // Add pagination only if limit is provided
        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
    
        $stmt = $this->db->prepare($query);
    
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
    
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        response('success', 'Approved events retrieved successfully.', $events, 200);
    }    
    
    public function getProposeUserEvents($userId) {
        // Retrieve filters and pagination from query parameters
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
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
            $query .= " AND s.status_name = :status";
            $params[':status'] = $status;
        }
    
        $query .= " ORDER BY $sortBy $sortOrder";
    
        // Add LIMIT and OFFSET only if limit is specified
        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
    
        // Prepare and execute the statement
        $stmt = $this->db->prepare($query);
    
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
    
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        response('success', 'Events for Propose user retrieved successfully.', $events, 200);
    }
    
    public function getAllProposedEventsForAdmin() {
        // Retrieve filters and pagination from query parameters
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
        $adminUserId = isset($_GET['admin_user_id']) ? (int)$_GET['admin_user_id'] : null;
    
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
            WHERE 1=1"; // Base query for flexibility
        
        $params = [];
    
        if ($adminUserId) {
            // Jika admin_user_id ada, hanya tampilkan event sesuai admin_user_id
            $query .= " AND e.admin_user_id = :adminUserId";
            $params[':adminUserId'] = $adminUserId;
        }
    
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
            $query .= " AND s.status_name = :status";
            $params[':status'] = $status;
        }
    
        $query .= " ORDER BY $sortBy $sortOrder";
    
        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
    
        $stmt = $this->db->prepare($query);
    
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
    
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        response('success', 'Events retrieved successfully.', $events, 200);
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
    
        // Validasi dan pengambilan data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        $place = $_POST['place'] ?? '';
        $quota = (int)($_POST['quota'] ?? 0);
        $dateStart = $_POST['date_start'] ?? '';
        $dateEnd = $_POST['date_end'] ?? '';
        $schedule = $_POST['schedule'] ?? '';
        $categoryId = $_POST['category_id'] ?? null;
        $proposeUserId = $this->getUserId();
        $dateAdd = date('Y-m-d H:i:s');
        $status = 1;
    
        // Validasi wajib
        if (empty($title) || empty($description) || empty($dateStart) || empty($dateEnd) || $quota <= 0) {
            response('error', 'All fields are required and quota must be greater than 0.', null, 400);
            return;
        }
    
        // Upload poster
        $fileUploadHelper = new FileUploadHelper();
        $poster = $fileUploadHelper->uploadFile($_FILES['poster'], 'poster');
    
        // Insert event ke database
        $stmt = $this->db->prepare("
            INSERT INTO event (title, description, poster, location, place, quota, date_start, date_end, schedule, propose_user_id, category_id, date_add, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
    
        if ($stmt->execute([$title, $description, $poster, $location, $place, $quota, $dateStart, $dateEnd, $schedule, $proposeUserId, $categoryId, $dateAdd, $status])) {
            $eventId = $this->db->lastInsertId();
    
            // Tangani invited_users
            if (isset($_POST['invited_users']) && is_array($_POST['invited_users'])) {
                $usernames = $_POST['invited_users']; // Array of usernames
                $invitedUserIds = $this->getUserIdsByUsername($usernames); // Convert to user_id
                
                foreach ($invitedUserIds as $userId) {
                    $inviteStmt = $this->db->prepare("INSERT INTO invited (event_id, user_id) VALUES (?, ?)");
                    $inviteStmt->execute([$eventId, $userId]);
                }
            }
    
            // Fetch the event with the invited users for the response
            $eventStmt = $this->db->prepare("SELECT * FROM event WHERE event_id = ?");
            $eventStmt->execute([$eventId]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
            // Fetch invited users
            $invitedStmt = $this->db->prepare("SELECT user_id FROM invited WHERE event_id = ?");
            $invitedStmt->execute([$eventId]);
            $invitedUsers = $invitedStmt->fetchAll(PDO::FETCH_ASSOC);
            $invitedUserIds = array_column($invitedUsers, 'user_id');
            
            // Return success response with event data and invited users
            response('success', 'Event created successfully.', ['event' => $event, 'invited_users' => $invitedUserIds], 201);
        } else {
            response('error', 'Failed to create event.', null, 500);
        }
    }    
    
    // Update an event by ID
    public function updateEvent($eventId) {
        $this->jwtHelper->decodeJWT(); // Verify JWT
        $roles = $this->getRoles(); // Get roles from JWT
        $userIdFromJWT = $this->getUserId();
        
        // Validasi role
        if (!in_array('Propose', $roles) && !in_array('Admin', $roles)) {
            response('error', 'Unauthorized.', null, 403);
            return;
        }
        
        // Validasi data
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $location = $_POST['location'] ?? '';
        $place = $_POST['place'] ?? '';
        $quota = (int)($_POST['quota'] ?? 0);
        $dateStart = $_POST['date_start'] ?? '';
        $dateEnd = $_POST['date_end'] ?? '';
        $schedule = $_POST['schedule'] ?? '';
        $categoryId = $_POST['category_id'] ?? null;
        
        if (empty($title) || empty($description) || empty($dateStart) || empty($dateEnd) || $quota <= 0) {
            response('error', 'All fields are required and quota must be greater than 0.', null, 400);
            return;
        }
        
        // Update event ke database
        $stmt = $this->db->prepare("
            UPDATE event
            SET title = ?, description = ?, location = ?, place = ?, quota = ?, date_start = ?, date_end = ?, schedule = ?, category_id = ?
            WHERE event_id = ?
        ");
        
        if ($stmt->execute([$title, $description, $location, $place, $quota, $dateStart, $dateEnd, $schedule, $categoryId, $eventId])) {
            // Update invited_users
            if (isset($_POST['invited_users']) && is_array($_POST['invited_users'])) {
                $usernames = $_POST['invited_users']; // Array of usernames
                $invitedUserIds = $this->getUserIdsByUsername($usernames); // Convert to user_id
    
                // Hapus existing invites
                $deleteStmt = $this->db->prepare("DELETE FROM invited WHERE event_id = ?");
                $deleteStmt->execute([$eventId]);
    
                // Masukkan user baru
                foreach ($invitedUserIds as $userId) {
                    $inviteStmt = $this->db->prepare("INSERT INTO invited (event_id, user_id) VALUES (?, ?)");
                    $inviteStmt->execute([$eventId, $userId]);
                }
            }
            
            // Fetch the updated event details
            $updatedEventStmt = $this->db->prepare("SELECT * FROM event WHERE event_id = ?");
            $updatedEventStmt->execute([$eventId]);
            $updatedEvent = $updatedEventStmt->fetch(PDO::FETCH_ASSOC);
    
            // Fetch invited users
            $invitedStmt = $this->db->prepare("SELECT user_id FROM invited WHERE event_id = ?");
            $invitedStmt->execute([$eventId]);
            $invitedUsers = $invitedStmt->fetchAll(PDO::FETCH_ASSOC);
            $invitedUserIds = array_column($invitedUsers, 'user_id');
            var_dump($_POST['invited_users']);  // This will print the input array
            var_dump($_POST['invited_users']);  // This will print the input array

            // Return success response with updated event data
            response('success', 'Event updated successfully.', ['event' => $updatedEvent, 'invited_users' => $invitedUserIds], 200);
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

    public function getUserIdsByUsername(array $usernames) {
        $placeholders = str_repeat('?,', count($usernames) - 1) . '?';
        $sql = "SELECT user_id FROM user WHERE username IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
    
        if (!$stmt->execute($usernames)) {
            response('error', 'Failed to fetch user IDs.', null, 500);
            return [];
        }
    
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
