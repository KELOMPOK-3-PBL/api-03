<?php
require_once '../vendor/autoload.php'; // Include Composer's autoloader
require_once '../helpers/ResponseHelpers.php'; // Include your response helper functions
require_once '../helpers/FileUploadHelper.php'; // Include file upload helper
require_once '../helpers/JwtHelpers.php'; // Include the JWT helper

class EventController {
    private $db;
    private $jwtHelper;

    private $uploadDir;

    public function __construct($db) {
        $this->db = $db;
        $this->uploadDir = realpath(__DIR__ . '/../images') . '/';
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
        $updateQuery = "
        UPDATE event
        SET status = 6
        WHERE date_end < NOW() AND status != 6
        ";

        $this->db->prepare($updateQuery)->execute();

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
                e.event_id, e.title, e.date_add, u.username AS propose_user, u.avatar AS propose_user_avatar,
                c.category_name AS category, e.description, e.poster, e.location,
                e.place, e.quota, e.date_start, e.date_end, e.schedule, e.updated, a.username AS admin_user,
                s.status_name AS status, e.note,
                GROUP_CONCAT(u_inv.username ORDER BY u_inv.username ASC) AS invited_users,
                GROUP_CONCAT(u_inv.avatar ORDER BY u_inv.username ASC) AS invited_avatars
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN status s ON e.status = s.status_id
            LEFT JOIN invited i ON e.event_id = i.event_id
            LEFT JOIN user u_inv ON i.user_id = u_inv.user_id
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
    
        $query .= " GROUP BY 
                e.event_id, e.title, e.date_add, e.updated, u.username, c.category_name, e.description, e.poster, e.location, e.place, e.quota, e.date_start, e.date_end, e.schedule, e.updated, a.username, s.status_name, e.note";
    
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
    
        // Convert invited_users and invited_avatars to arrays
        foreach ($events as &$event) {
            $usernames = !empty($event['invited_users']) ? explode(',', $event['invited_users']) : [];
            $avatars = !empty($event['invited_avatars']) ? explode(',', $event['invited_avatars']) : [];
            
            // Map usernames and avatars
            $event['invited_users'] = [];
            foreach ($usernames as $index => $username) {
                $avatar = isset($avatars[$index]) ? $avatars[$index] : null;
                $event['invited_users'][] = [
                    'username' => $username,
                    'avatar' => $avatar
                ];
            }
        
            // Handle propose_user_avatar and schedule
            $event['propose_user_avatar'] = !empty($event['propose_user_avatar']) ? $event['propose_user_avatar'] : null;
            $event['schedule'] = !empty($event['schedule']) ? $event['schedule'] : null;
        
            // Unset unused fields
            unset($event['invited_avatars']);
        }
        

        response('success', 'Approved events retrieved successfully.', $events, 200);
    } 
    
    public function getAllEventsProposeUser($userId) {
        $updateQuery = "
        UPDATE event
        SET status = 6
        WHERE date_end < NOW() AND status != 6
        ";

        $this->db->prepare($updateQuery)->execute();
        
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
    
        // Start building the query
            $query = "
            SELECT 
                e.event_id, e.title, e.date_add, u.username AS propose_user, u.avatar AS propose_user_avatar,
                c.category_name AS category, e.description, e.poster, e.location,
                e.place, e.quota, e.date_start, e.date_end, e.schedule, e.updated, a.username AS admin_user,
                s.status_name AS status, e.note,
                GROUP_CONCAT(u_inv.username ORDER BY u_inv.username ASC) AS invited_users,
                GROUP_CONCAT(u_inv.avatar ORDER BY u_inv.username ASC) AS invited_avatars
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN status s ON e.status = s.status_id
            LEFT JOIN invited i ON e.event_id = i.event_id
            LEFT JOIN user u_inv ON i.user_id = u_inv.user_id
            WHERE e.propose_user_id = :userId";
    
        // Initialize an array for parameters
        $params = [':userId' => $userId];
    
        // Apply filters if set
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
    
        // Include the necessary columns in the GROUP BY clause
        $query .= " GROUP BY e.event_id, e.title, e.date_add, e.schedule, e.updated, u.username, c.category_name, 
                    e.description, e.poster, e.location, e.place, e.quota, e.date_start, e.date_end, a.username, 
                    s.status_name, e.note";
    
        // Sorting
        $query .= " ORDER BY $sortBy $sortOrder";
    
        // Pagination
        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
    
        // Prepare and execute the statement
        $stmt = $this->db->prepare($query);
    
        // Bind the parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
    
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
    
        // Execute and fetch the events
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Convert invited_users to array and include their avatars
        foreach ($events as &$event) {
            $usernames = !empty($event['invited_users']) ? explode(',', $event['invited_users']) : [];
            $avatars = !empty($event['invited_avatars']) ? explode(',', $event['invited_avatars']) : [];
            
            // Map usernames and avatars
            $event['invited_users'] = [];
            foreach ($usernames as $index => $username) {
                $avatar = isset($avatars[$index]) ? $avatars[$index] : null;
                $event['invited_users'][] = [
                    'username' => $username,
                    'avatar' => $avatar
                ];
            }
        
            // Handle propose_user_avatar and schedule
            $event['propose_user_avatar'] = !empty($event['propose_user_avatar']) ? $event['propose_user_avatar'] : null;
            $event['schedule'] = !empty($event['schedule']) ? $event['schedule'] : null;
        
            // Unset unused fields
            unset($event['invited_avatars']);
        }
        

    
        response('success', 'Events for Propose user retrieved successfully.', $events, 200);
    }
    
    public function getAllEventsAdminUser($adminUserId = null) {
        $updateQuery = "
        UPDATE event
        SET status = 6
        WHERE date_end < NOW() AND status != 6
        ";

        $this->db->prepare($updateQuery)->execute();
        

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
        
        // Start building the query
        $query = "
            SELECT 
                e.event_id, e.title, e.date_add, u.username AS propose_user, u.avatar AS propose_user_avatar,
                c.category_name AS category, e.description, e.poster, e.location,
                e.place, e.quota, e.date_start, e.date_end, e.schedule, e.updated, a.username AS admin_user,
                s.status_name AS status, e.note,
                GROUP_CONCAT(u_inv.username ORDER BY u_inv.username ASC) AS invited_users,
                GROUP_CONCAT(u_inv.avatar ORDER BY u_inv.username ASC) AS invited_avatars
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN status s ON e.status = s.status_id
            LEFT JOIN invited i ON e.event_id = i.event_id
            LEFT JOIN user u_inv ON i.user_id = u_inv.user_id
            WHERE 1=1";
        
        $params = [];
        
        if ($adminUserId != null) {
            $query .= " AND e.admin_user_id = :admin_user_id";
            $params[':admin_user_id'] = $adminUserId;
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
        
        // Include the necessary columns in the GROUP BY clause
        $query .= " GROUP BY e.event_id, e.title, e.date_add, u.username, c.category_name, e.description, e.poster, 
            e.location, e.place, e.quota, e.date_start, e.date_end, a.username, s.status_name, e.note";
        
        // Sorting
        $query .= " ORDER BY $sortBy $sortOrder";
        
        // Pagination
        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        // Prepare and execute the query
        $stmt = $this->db->prepare($query);
        
        // Bind the parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        
        if ($limit !== null) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        // Execute and fetch the events
        $stmt->execute();
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert invited_users to array and include their avatars
        foreach ($events as &$event) {
            $usernames = !empty($event['invited_users']) ? explode(',', $event['invited_users']) : [];
            $avatars = !empty($event['invited_avatars']) ? explode(',', $event['invited_avatars']) : [];
            
            // Map usernames and avatars
            $event['invited_users'] = [];
            foreach ($usernames as $index => $username) {
                $avatar = isset($avatars[$index]) ? $avatars[$index] : null;
                $event['invited_users'][] = [
                    'username' => $username,
                    'avatar' => $avatar
                ];
            }
        
            // Handle propose_user_avatar and schedule
            $event['propose_user_avatar'] = !empty($event['propose_user_avatar']) ? $event['propose_user_avatar'] : null;
            $event['schedule'] = !empty($event['schedule']) ? $event['schedule'] : null;
        
            // Unset unused fields
            unset($event['invited_avatars']);
        }
        
       
        response('success', 'Events retrieved successfully.', $events, 200);
    }
     
    // Get event by ID
    public function getEventById($eventId) {
        $updateQuery = "
        UPDATE event
        SET status = 6
        WHERE date_end < NOW() AND status != 6
        ";

        $this->db->prepare($updateQuery)->execute();

        // Verify JWT
        $this->jwtHelper->decodeJWT(); 
    
        // Check if event_id is valid
        if (!is_numeric($eventId)) {
            response('error', 'Invalid event ID.', null, 400);
            return;
        }
    
        // Query to retrieve event data
        $stmt = $this->db->prepare("
            SELECT 
                e.event_id, e.title, e.date_add, u.username AS propose_user, u.avatar AS propose_user_avatar,
                c.category_name AS category, e.description, e.poster, e.location,
                e.place, e.quota, e.date_start, e.date_end, e.schedule, e.updated, a.username AS admin_user,
                s.status_name AS status, e.note,
                GROUP_CONCAT(u_inv.username ORDER BY u_inv.username ASC) AS invited_users,
                GROUP_CONCAT(u_inv.avatar ORDER BY u_inv.username ASC) AS invited_avatars
            FROM 
                event e
            LEFT JOIN user u ON e.propose_user_id = u.user_id
            LEFT JOIN category c ON e.category_id = c.category_id
            LEFT JOIN user a ON e.admin_user_id = a.user_id
            LEFT JOIN status s ON e.status = s.status_id
            LEFT JOIN invited i ON e.event_id = i.event_id
            LEFT JOIN user u_inv ON i.user_id = u_inv.user_id
            WHERE e.event_id = ?
            GROUP BY e.event_id
        ");
        
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
        // Check if event is found
        if ($event) {
            // Convert invited_users and invited_user_avatars to arrays
            $usernames = !empty($event['invited_users']) ? explode(',', $event['invited_users']) : [];
            $avatars = !empty($event['invited_avatars']) ? explode(',', $event['invited_avatars']) : [];
    
            $event['invited_users'] = array_map(function ($username, $avatar) {
                return [
                    'username' => $username,
                    'avatar' => !empty($avatar) ? $avatar : null // Explicitly set null for empty avatars
                ];
            }, $usernames, $avatars);
    
            // Remove invited_avatars from the response
            unset($event['invited_avatars']);
    
            // Send successful response
            response('success', 'Event retrieved successfully.', $event, 200);
        } else {
            // If event not found
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
        $schedule = $_POST['schedule'] ?? null;
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
    
            if (isset($_POST['invited_users']) && !empty(trim($_POST['invited_users']))) {
                $usernames = array_filter(array_map('trim', explode(',', $_POST['invited_users'])));
                $invitedUserIds = $this->getUserIdsByUsername($usernames);
    
                foreach ($invitedUserIds as $userId) {
                    $inviteStmt = $this->db->prepare("INSERT INTO invited (event_id, user_id) VALUES (?, ?)");
                    $inviteStmt->execute([$eventId, $userId]);
                }
            }
    
            // Fetch event data
            $eventStmt = $this->db->prepare("SELECT * FROM event WHERE event_id = ?");
            $eventStmt->execute([$eventId]);
            $event = $eventStmt->fetch(PDO::FETCH_ASSOC);       
            
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
        $userIdFromJWT = $this->getUserId(); // Get user ID from JWT
        
        // Validate roles
        if (!in_array('Propose', $roles) && !in_array('Admin', $roles)) {
            response('error', 'Unauthorized.', null, 403);
            return;
        }
    
        // For 'Admin', only notes can be updated
        if (in_array('Admin', $roles)) {
            $note = $_POST['note'] ?? '';
            if (empty($note)) {
                response('error', 'Note is required for admin.', null, 400);
                return;
            }
    
            // Update note and track admin user
            $stmt = $this->db->prepare("
                UPDATE event
                SET note = ?, status = 2, admin_user_id = ?
                WHERE event_id = ?
            ");
    
            if ($stmt->execute([$note, $userIdFromJWT, $eventId])) {
                $updatedEventStmt = $this->db->prepare("SELECT note, admin_user_id FROM event WHERE event_id = ?");
                $updatedEventStmt->execute([$eventId]);
                $updatedEvent = $updatedEventStmt->fetch(PDO::FETCH_ASSOC);
    
                response('success', 'Event note updated successfully.', ['event' => $updatedEvent], 200);
            } else {
                response('error', 'Failed to update event note.', null, 500);
            }
        } else if (in_array('Propose', $roles)) {
            // Validate all fields except notes for 'Propose'
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
    
            $fileUploadHelper = new FileUploadHelper($this->uploadDir);
    
            // Check if a new file has been uploaded
            $poster = null;
            if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
                $oldPoster = $this->getOldPoster($eventId);
                if ($oldPoster) {
                    $fileUploadHelper->deleteFile($oldPoster);
                }
                $poster = $fileUploadHelper->uploadFile($_FILES['poster'], 'poster', $oldPoster);
            }
    
            // If no new file uploaded, maintain the old poster
            if ($poster === null) {
                $poster = $this->getOldPoster($eventId);
            }
    
            $stmt = $this->db->prepare("
                UPDATE event
                SET title = ?, description = ?, location = ?, place = ?, quota = ?, date_start = ?, date_end = ?, schedule = ?, category_id = ?, poster = ?, status = 3
                WHERE event_id = ?
            ");
    
            if ($stmt->execute([$title, $description, $location, $place, $quota, $dateStart, $dateEnd, $schedule, $categoryId, $poster, $eventId])) {
                if (isset($_POST['invited_users']) && !empty($_POST['invited_users'])) {
                    $usernames = explode(',', $_POST['invited_users']);
                    $invitedUserIds = $this->getUserIdsByUsername($usernames);
    
                    $deleteStmt = $this->db->prepare("DELETE FROM invited WHERE event_id = ?");
                    $deleteStmt->execute([$eventId]);
    
                    foreach ($invitedUserIds as $userId) {
                        $inviteStmt = $this->db->prepare("INSERT INTO invited (event_id, user_id) VALUES (?, ?)");
                        $inviteStmt->execute([$eventId, $userId]);
                    }
                }
    
                $updatedEventStmt = $this->db->prepare("SELECT * FROM event WHERE event_id = ?");
                $updatedEventStmt->execute([$eventId]);
                $updatedEvent = $updatedEventStmt->fetch(PDO::FETCH_ASSOC);
    
                $invitedStmt = $this->db->prepare("SELECT user_id FROM invited WHERE event_id = ?");
                $invitedStmt->execute([$eventId]);
                $invitedUsers = $invitedStmt->fetchAll(PDO::FETCH_ASSOC);
                $invitedUserIds = array_column($invitedUsers, 'user_id');
    
                response('success', 'Event updated successfully.', ['event' => $updatedEvent, 'invited_users' => $invitedUserIds], 200);
            } else {
                response('error', 'Failed to update event.', null, 500);
            }
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
    
        // Retrieve the event to get the poster path
        $stmt = $this->db->prepare("SELECT poster FROM event WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$event) {
            response('error', 'Event not found.', null, 404);
            return;
        }
    
        $posterPath = $event['poster'] ?? null;
    
        // If there is a poster, delete the file using FileUploadHelper
        if ($posterPath) {
            $fileUploadHelper = new FileUploadHelper(); // You may pass any specific upload dir if needed
            $deleteResult = $fileUploadHelper->deleteFile($posterPath);
    
            if ($deleteResult['status'] !== 'success') {
                response('error', 'Failed to delete poster file.', null, 500);
                return;
            }
        }
    
        // Now proceed to delete the event from the database
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
        $stmt = $this->db->prepare($sql);
    
        if (!$stmt->execute($usernames)) {
            response('error', 'Failed to fetch user IDs.', null, 500);
            return [];
        }
    
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    // Define this method inside your EventController class
    private function getOldPoster($eventId) {
        $stmt = $this->db->prepare("SELECT poster FROM event WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return the poster filename if it exists
        return $result ? $result['poster'] : null;
    }
}
