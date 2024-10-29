<?php

include_once '../config/database.php';
include_once '../config/jwt_config.php'; // Assuming JWT_SECRET is defined here
require_once '../vendor/autoload.php'; // Include Composer's autoloader

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

    private function getRolesFromJWT() {
        // Mengambil semua header
        $headers = getallheaders();
        
        // Memastikan Authorization header ada
        if (!isset($headers['Authorization'])) {
            header("HTTP/1.0 401 Unauthorized");
            echo json_encode(['status' => 'error', 'message' => 'Authorization token not provided.'], JSON_PRETTY_PRINT);
            exit;
        }
    
        // Mendapatkan token dari header
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        try {
            $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
            return $decoded->roles ?? [];
        } catch (Exception $e) {
            header("HTTP/1.0 401 Unauthorized");
            echo json_encode(['status' => 'error', 'message' => 'Invalid token.'], JSON_PRETTY_PRINT);
            exit;
        }
    }
    
    private function getUserIdFromJWT() {
        // Mengambil semua header
        $headers = getallheaders();
        
        // Memastikan Authorization header ada
        if (!isset($headers['Authorization'])) {
            header("HTTP/1.0 401 Unauthorized");
            echo json_encode(['status' => 'error', 'message' => 'Authorization token not provided.'], JSON_PRETTY_PRINT);
            exit;
        }
    
        // Mendapatkan token dari header
        $jwt = str_replace('Bearer ', '', $headers['Authorization']);
        try {
            $decoded = JWT::decode($jwt, new Key(JWT_SECRET, 'HS256'));
            return $decoded->user_id ?? null;
        } catch (Exception $e) {
            header("HTTP/1.0 401 Unauthorized");
            echo json_encode(['status' => 'error', 'message' => 'Invalid token.'], JSON_PRETTY_PRINT);
            exit;
        }
    }
    

    public function getAllUsers() {
        $this->setHeaders();

        $roles = $this->getRolesFromJWT();
        if (!in_array('Superadmin', $roles)) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can access this resource.'], JSON_PRETTY_PRINT);
            return;
        }

        $search = isset($_GET['search']) ? htmlspecialchars(strip_tags($_GET['search'])) : '';

        $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
                  FROM " . $this->table_name . " u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id";

        if (!empty($search)) {
            $query .= " WHERE u.username LIKE :search OR u.email LIKE :search";
        }

        $query .= " GROUP BY u.user_id";
        $stmt = $this->conn->prepare($query);

        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam);
        }

        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => empty($users) ? 'error' : 'success',
            'message' => empty($users) ? 'No users found.' : 'Users retrieved successfully',
            'data' => $users
        ], JSON_PRETTY_PRINT);
    }

    public function getUserById($id) {
        $this->setHeaders();

        $roles = $this->getRolesFromJWT();
        $userIdFromJWT = $this->getUserIdFromJWT();

        if (!in_array('Superadmin', $roles) && $userIdFromJWT != $id) {
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

    public function createUser() {
        $this->setHeaders();

        $roles = $this->getRolesFromJWT();
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

    public function updateUser($user_id) {
        $this->setHeaders();

        $roles = $this->getRolesFromJWT();
        $userIdFromJWT = $this->getUserIdFromJWT();

        if (!in_array('Superadmin', $roles) && $userIdFromJWT != $user_id) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized access.'], JSON_PRETTY_PRINT);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            parse_str(file_get_contents("php://input"), $_PUT);
            $this->user_id = htmlspecialchars(strip_tags($user_id));

            if (in_array('Superadmin', $roles)) {
                $this->roles = $_PUT['roles'] ?? [];
                if ($this->updateRolesOnly()) {
                    echo json_encode([
                        'status' => 'success',
                        'message' => 'User roles updated successfully.'
                    ], JSON_PRETTY_PRINT);
                } else {
                    header("HTTP/1.0 400 Bad Request");
                    echo json_encode([
                        'status' => 'error',
                        'message' => 'User role update failed.'
                    ], JSON_PRETTY_PRINT);
                }
            } elseif ($userIdFromJWT == $user_id) {
                $this->password = htmlspecialchars(strip_tags($_PUT['password'] ?? ''));
                $this->about = htmlspecialchars(strip_tags($_PUT['about'] ?? ''));

                if ($this->updatePasswordAndAbout()) {
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
    }

    private function updateRolesOnly() {
        $query = "DELETE FROM user_roles WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $this->user_id);
        if (!$stmt->execute()) return false;

        foreach ($this->roles as $role_id) {
            $query = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":role_id", $role_id);
            if (!$stmt->execute()) return false;
        }
        return true;
    }

    private function updatePasswordAndAbout() {
        $query = "UPDATE " . $this->table_name . " 
                  SET password = :password, about = :about 
                  WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);

        if (!empty($this->password)) {
            $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(":password", $hashed_password);
        }
        $stmt->bindParam(":about", $this->about);
        $stmt->bindParam(":user_id", $this->user_id);

        return $stmt->execute();
    }

    public function deleteUser($user_id) {
        $this->setHeaders();

        $roles = $this->getRolesFromJWT();
        if (!in_array('Superadmin', $roles)) {
            header("HTTP/1.0 403 Forbidden");
            echo json_encode(['status' => 'error', 'message' => 'Only superadmin can delete users.'], JSON_PRETTY_PRINT);
            return;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        if ($stmt->execute()) {
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
