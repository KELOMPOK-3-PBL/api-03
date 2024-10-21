<?php
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

    // List all users with their roles
    public function getAllUser() {
        $this->setHeaders();

        $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
                  FROM " . $this->table_name . " u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id
                  GROUP BY u.user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $users
        ], JSON_PRETTY_PRINT);
    }

    // Get a specific user by ID with roles
    public function getUserById($id) {
        $this->setHeaders();

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

    // Create a new user with roles
    public function createUser() {
        $this->setHeaders();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = json_decode(file_get_contents("php://input"), true);
            
            // Assign data to properties
            $this->user_id = uniqid();
            $this->username = htmlspecialchars(strip_tags($data['username'] ?? ''));
            $this->email = htmlspecialchars(strip_tags($data['email'] ?? ''));
            $this->password = htmlspecialchars(strip_tags($data['password'] ?? ''));
            $this->about = htmlspecialchars(strip_tags($data['about'] ?? ''));
            $this->roles = $data['roles'] ?? []; // Array of role_ids

            // Log data for debugging
            error_log("Incoming Data: " . print_r($data, true));

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
        $stmt->bindParam(":password", password_hash($this->password, PASSWORD_BCRYPT));
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

    // Update user details and roles
    public function updateUser($user_id) {
        $this->setHeaders();

        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            parse_str(file_get_contents("php://input"), $_PUT);

            $this->user_id = htmlspecialchars(strip_tags($user_id));
            $this->username = htmlspecialchars(strip_tags($_PUT['username'] ?? ''));
            $this->email = htmlspecialchars(strip_tags($_PUT['email'] ?? ''));
            $this->password = htmlspecialchars(strip_tags($_PUT['password'] ?? ''));
            $this->about = htmlspecialchars(strip_tags($_PUT['about'] ?? ''));
            $this->roles = $_PUT['roles'] ?? []; // Array of role_ids

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
        $stmt->bindParam(":password", password_hash($this->password, PASSWORD_BCRYPT));
        $stmt->bindParam(":about", $this->about);

        if ($stmt->execute()) {
            // Clear existing roles and assign new ones
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

    // Delete user
    public function deleteUser($user_id) {
        $this->setHeaders();

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
}
?>
