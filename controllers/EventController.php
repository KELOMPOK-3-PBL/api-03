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
        $status = isset($_GET['status']) ? $_GET['status'] : null;
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
        $dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
        $searchTerm = isset($_GET['search']) ? $_GET['search'] : null;
        $sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'date_add';
        $sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
    
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = ($page - 1) * $limit;
    
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
            WHERE 1=1";
    
        // Initialize an array for parameters
        $params = [];
    
        if ($status) {
            $query .= " AND s.status_name = :status";
            $params[':status'] = $status;
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
    
        $query .= " ORDER BY $sortBy $sortOrder";
        
        // Only add LIMIT and OFFSET if $limit is provided
        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
            $params[':limit'] = $limit;
            $params[':offset'] = $offset;
        }
    
        $stmt = $this->db->prepare($query);
    
        // Bind parameters in a loop
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
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
        $status = '1';

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
        $this->jwtHelper->decodeJWT(); // Verify JWT
        $roles = $this->getRoles(); // Get roles from JWT

        // Check for authorized roles
        if (!in_array('Admin', $roles) && !in_array('Propose', $roles)) {
            response('error', 'Unauthorized.', null, 403);
            return;
        }

        // Check if the event exists
        $stmt = $this->db->prepare("SELECT * FROM event WHERE event_id = ?");
        $stmt->execute([$eventId]);
        $currentEvent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$currentEvent) {
            response('error', 'Event not found.', null, 404);
            return;
        }

        $updateFields = [];
        $stmtData = [];

        // Check for fields to update
        if (in_array('Propose', $roles)) {
            if (!empty($_POST['title'])) {
                $updateFields[] = "title = ?";
                $stmtData[] = $_POST['title'];
            }
            if (!empty($_POST['description'])) {
                $updateFields[] = "description = ?";
                $stmtData[] = $_POST['description'];
            }
            if (!empty($_POST['location'])) {
                $updateFields[] = "location = ?";
                $stmtData[] = $_POST['location'];
            }
            if (!empty($_POST['place'])) {
                $updateFields[] = "place = ?";
                $stmtData[] = $_POST['place'];
            }
            if (!empty($_POST['quota'])) {
                $updateFields[] = "quota = ?";
                $stmtData[] = (int)$_POST['quota'];
            }
            if (!empty($_POST['date_start']) && !empty($_POST['date_end'])) {
                $updateFields[] = "date_start = ?";
                $stmtData[] = date('Y-m-d H:i:s', strtotime($_POST['date_start']));
                $updateFields[] = "date_end = ?";
                $stmtData[] = date('Y-m-d H:i:s', strtotime($_POST['date_end']));
            }
        }

        // Handle poster upload
        if (isset($_FILES['poster']) && $_FILES['poster']['error'] === UPLOAD_ERR_OK) {
            $newPoster = $this->fileUploadHelper->uploadFile($_FILES['poster'], 'poster');
            if ($newPoster) {
                // Delete the old poster if exists
                if ($currentEvent['poster'] && file_exists($_SERVER['DOCUMENT_ROOT'] . $currentEvent['poster'])) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $currentEvent['poster']);
                }
                $updateFields[] = "poster = ?";
                $stmtData[] = $newPoster;
            } else {
                response('error', 'Failed to upload poster.', null, 500);
                return;
            }
        }

        // Admin role specific updates
        if (in_array('Admin', $roles)) {
            if (isset($_POST['note'])) {
                $updateFields[] = "note = ?";
                $stmtData[] = $_POST['note'];
            }
            if (isset($_POST['admin_user_id'])) {
                $updateFields[] = "admin_user_id = ?";
                $stmtData[] = $_POST['admin_user_id'];
            }
        }

        // Check if any fields have been marked for update
        if (!empty($updateFields)) {
            $query = "UPDATE event SET " . implode(", ", $updateFields) . " WHERE event_id = ?";
            $stmtData[] = $eventId;

            $stmt = $this->db->prepare($query);
            if ($stmt->execute($stmtData)) {
                response('success', 'Event updated successfully.', null, 200);
            } else {
                response('error', 'Failed to update event.', null, 500);
            }
        } else {
            response('error', 'No fields to update.', null, 400);
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
