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
    public $role;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Function to set JSON headers and CORS headers
    private function setHeaders() {
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: *"); // Allow all domains
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Max-Age: 3600"); // Cache duration for CORS preflight requests
    }

    // List all users
    public function getAllUser() {
        $this->setHeaders(); // Set headers
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'data' => $users
        ], JSON_PRETTY_PRINT);
    }

    // Get a specific user by ID
    public function getUserById($id) {
        $this->setHeaders(); // Set headers
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
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

    // Create a new user
    public function createUser() {
        $this->setHeaders(); // Set headers
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
                $data = json_decode(file_get_contents("php://input"), true);
            } else {
                $data = $_POST;
            }

            // Log the incoming data for debugging
            error_log("Incoming Data: " . print_r($data, true)); // Log all incoming data

            // Check if 'role' exists and is not empty
            if (empty($data['role'])) {
                header("HTTP/1.0 400 Bad Request");
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Role is required.'
                ], JSON_PRETTY_PRINT);
                return;
            }

            // Assign data to properties
            $this->user_id = uniqid();
            $this->username = htmlspecialchars(strip_tags($data['username'] ?? ''));
            $this->email = htmlspecialchars(strip_tags($data['email'] ?? ''));
            $this->password = htmlspecialchars(strip_tags($data['password'] ?? ''));
            $this->about = htmlspecialchars(strip_tags($data['about'] ?? ''));
            $this->role = htmlspecialchars(strip_tags($data['role'] ?? ''));

            // Log the assigned values for debugging
            error_log("Assigned Values: " . print_r([
                'username' => $this->username,
                'email' => $this->email,
                'about' => $this->about,
                'role' => $this->role,
            ], true)); // Log assigned values
            
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

    // Create a new user in the database
    private function create() {
        // Ensure role is a valid ENUM value
        $valid_roles = ['Member', 'Admin', 'User', 'Propose'];
        if (!in_array($this->role, $valid_roles)) {
            throw new Exception("Invalid role value: " . $this->role);
        }
    
        // Prepare query
        $query = "INSERT INTO " . $this->table_name . " ( username, email, password, about, role) 
                  VALUES(:username, :email, :password, :about, :role)";
        $stmt = $this->conn->prepare($query);
    
        // Bind parameters
        $username = $this->username; // Create a variable
        $email = $this->email; // Create a variable
        $password = password_hash($this->password, PASSWORD_BCRYPT); // Create a variable with hashed password
        $about = $this->about ?? ''; // Create a variable
        $role = $this->role; // Create a variable
    
        // Bind parameters using the new variables
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->bindParam(":about", $about);
        $stmt->bindParam(":role", $role);
    
        return $stmt->execute();
    }

    // Update an existing user
    public function updateUser($user_id) {
        $this->setHeaders(); // Set headers
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            parse_str(file_get_contents("php://input"), $_PUT);
            
            $this->user_id = htmlspecialchars(strip_tags($user_id));
            $this->username = htmlspecialchars(strip_tags($_PUT['username'] ?? ''));
            $this->email = htmlspecialchars(strip_tags($_PUT['email'] ?? ''));
            $this->password = htmlspecialchars(strip_tags($_PUT['password'] ?? ''));
            $this->about = htmlspecialchars(strip_tags($_PUT['about'] ?? ''));
            $this->role = htmlspecialchars(strip_tags($_PUT['role'] ?? ''));

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
                  SET username = :username, email = :email, password = :password, about = :about, role = :role 
                  WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":password", password_hash($this->password, PASSWORD_BCRYPT)); // Hash password
        $stmt->bindParam(":about", $this->about);
        $stmt->bindParam(":role", $this->role);

        return $stmt->execute();
    }

    // Delete a user
    public function deleteUser($user_id) {
        $this->setHeaders(); // Set headers
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

    // Delete user from the database
    private function delete($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);

        $user_id = htmlspecialchars(strip_tags($user_id));
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }
}
?>
