<?php
session_start();

include_once '../config/database.php';

class UserController {
    private $conn;
    private $table_name = "user";

    public $user_id;
    public $username;
    public $email;
    public $password;
    public $about;
    public $roles = [];

    public function __construct($db) {
        $this->conn = $db;
    }

    private function setHeaders() {
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Max-Age: 3600");
    }

    // List all users (only accessible by Superadmin)
// List all users (only accessible by Superadmin)
public function getAllUsers() {
    $this->setHeaders();
    
    // Check user role
    $roles = $this->checkUserRole();
    if (!in_array('Superadmin', $roles)) {
        header("HTTP/1.0 403 Forbidden");
        echo json_encode(['status' => 'error', 'message' => 'Only superadmin can access this resource.'], JSON_PRETTY_PRINT);
        return;
    }

    // Get search query if present
    $search = isset($_GET['search']) ? htmlspecialchars(strip_tags($_GET['search'])) : '';

    // Prepare the query with optional search condition
    $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
              FROM " . $this->table_name . " u
              LEFT JOIN user_roles ur ON u.user_id = ur.user_id
              LEFT JOIN roles r ON ur.role_id = r.role_id";

    if (!empty($search)) {
        $query .= " WHERE u.username LIKE :search OR u.email LIKE :search";
    }

    $query .= " GROUP BY u.user_id";
    $stmt = $this->conn->prepare($query);

    // Bind the search parameter if it exists
    if (!empty($search)) {
        $searchParam = '%' . $search . '%'; // For partial matches
        $stmt->bindParam(':search', $searchParam);
    }

    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => empty($users) ? 'error' : 'success',
        'message' => empty($users) ? 'No users found.' : '',
        'data' => $users
    ], JSON_PRETTY_PRINT);
}


    // Get a specific user by ID (accessible by Superadmin and the Member themselves)
    public function getUserById($id) {
        $this->setHeaders();

        // Check user role
        $roles = $this->checkUserRole();
        if (!in_array('Superadmin', $roles) && $_SESSION['user_id'] != $id) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.'], JSON_PRETTY_PRINT);
            return;
        }

        $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
                  FROM " . $this->table_name . " u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id
                  WHERE u.user_id = :user_id
                  GROUP BY u.user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode([
                'status' => 'success',
                'data' => $user
            ], JSON_PRETTY_PRINT);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found.'
            ], JSON_PRETTY_PRINT);
        }
    }

    // Create a new user with roles (only accessible by Superadmin)
    public function createUser() {
        $this->setHeaders();

        $roles = $this->checkUserRole();
        if (!in_array('Superadmin', $roles)) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can create users.'], JSON_PRETTY_PRINT);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            $this->username = htmlspecialchars(strip_tags($data['username'] ?? ''));
            $this->email = htmlspecialchars(strip_tags($data['email'] ?? ''));
            $this->password = htmlspecialchars(strip_tags($data['password'] ?? ''));
            $this->about = htmlspecialchars(strip_tags($data['about'] ?? ''));
            $this->roles = $data['roles'] ?? [];

            if ($this->create()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User created successfully.'
                ], JSON_PRETTY_PRINT);
            } else {
                header("HTTP/1.0 400 Bad Request");
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User creation failed.'
                ], JSON_PRETTY_PRINT);
            }
        }
    }

    // Create user in the database
    private function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, email, password, about) 
                  VALUES(:username, :email, :password, :about)";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":about", $this->about);

        if ($stmt->execute()) {
            $this->user_id = $this->conn->lastInsertId();
            return $this->assignRoles();
        }

        return false;
    }

    // Assign roles to the user
    private function assignRoles() {
        foreach ($this->roles as $role_id) {
            $query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":role_id", $role_id);
            $stmt->execute();
        }
        return true;
    }

    // Update user details and roles (only accessible by Superadmin)
    public function updateUser($user_id) {
        $this->setHeaders();

        $roles = $this->checkUserRole();
        if (!in_array('Superadmin', $roles)) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can update users.'], JSON_PRETTY_PRINT);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            parse_str(file_get_contents("php://input"), $_PUT);
            $this->user_id = htmlspecialchars(strip_tags($user_id));
            $this->username = htmlspecialchars(strip_tags($_PUT['username'] ?? ''));
            $this->email = htmlspecialchars(strip_tags($_PUT['email'] ?? ''));
            $this->password = htmlspecialchars(strip_tags($_PUT['password'] ?? ''));
            $this->about = htmlspecialchars(strip_tags($_PUT['about'] ?? ''));
            $this->roles = $_PUT['roles'] ?? [];

            if ($this->update()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User updated successfully.'
                ], JSON_PRETTY_PRINT);
            } else {
                header("HTTP/1.0 400 Bad Request");
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User update failed.'
                ], JSON_PRETTY_PRINT);
            }
        }
    }

    // Update user in the database
    private function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET username = :username, email = :email, password = :password, about = :about 
                  WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":about", $this->about);

        if ($stmt->execute()) {
            $this->clearRoles();
            return $this->assignRoles();
        }

        return false;
    }

    // Clear roles for the user
    private function clearRoles() {
        $query = "DELETE FROM user_roles WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
    }

    // Delete user (only accessible by Superadmin)
    public function deleteUser($user_id) {
        $this->setHeaders();

        $roles = $this->checkUserRole();
        if (!in_array('Superadmin', $roles)) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can delete users.'], JSON_PRETTY_PRINT);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            if ($this->delete($user_id)) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User deleted successfully.'
                ], JSON_PRETTY_PRINT);
            } else {
                header("HTTP/1.0 400 Bad Request");
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User deletion failed.'
                ], JSON_PRETTY_PRINT);
            }
        }
    }

    // Delete user in the database
    private function delete($user_id) {
        // First delete user roles
        $this->clearRoles();

        // Now delete the user
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);

        $user_id = htmlspecialchars(strip_tags($user_id));
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    // Check user roles from the session
    private function checkUserRole() {
        // Assuming roles are stored in the session as an array
        return $_SESSION['roles'] ?? [];
    }
}

