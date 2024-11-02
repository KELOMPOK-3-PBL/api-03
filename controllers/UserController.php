<?php

include_once '../config/database.php';
require_once '../config/JwtConfig.php';
require_once '../vendor/autoload.php'; // Include Composer's autoloader
require_once '../helpers/ResponseHelpers.php';
require_once '../helpers/JwtHelpers.php'; // Make sure to include your JWTHelper

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class UserController {
    private $conn;
    private $table_name = "user";
    private $jwtHelper;

    public $user_id;
    public $username;
    public $email;
    public $password;
    public $about;
    public $roles = [];

    public function __construct($db) {
        $this->conn = $db;
        $this->jwtHelper = new JWTHelper(); // Instantiate JWTHelper
    }

    private function getRoles() {
        return $this->jwtHelper->getRoles(); // Use JWTHelper to get roles
    }

    private function getUserId() {
        return $this->jwtHelper->getUserId(); // Use JWTHelper to get user ID
    }

    public function getAllUsers() {
        $roles = $this->getRoles();
        
        // Check for superadmin role
        if (!in_array('Superadmin', $roles)) {
            response('error', 'Only superadmin can access this resource.', null, 403);
            return;
        }

        $search = isset($_GET['search']) ? htmlspecialchars(strip_tags($_GET['search'])) : '';
        $sort = isset($_GET['sort']) ? htmlspecialchars(strip_tags($_GET['sort'])) : 'username'; // Default to 'username'
        $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC'; // Default to 'ASC'
        $roleFilter = isset($_GET['role']) ? htmlspecialchars(strip_tags($_GET['role'])) : '';
        
        // Pagination
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : 5; // Default limit
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1; // Default to page 1
        $offset = ($page - 1) * $limit; // Calculate the offset

        // Base query to fetch users and their roles
        $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
                  FROM " . $this->table_name . " u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id";
        
        // Search and filter conditions
        $conditions = [];
        if (!empty($search)) {
            $conditions[] = "(u.username LIKE :search OR u.email LIKE :search)";
        }
        if (!empty($roleFilter)) {
            $conditions[] = "r.role_name = :role";
        }
        if ($conditions) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $query .= " GROUP BY u.user_id";
        $query .= " ORDER BY $sort $order";
        $query .= " LIMIT :limit OFFSET :offset"; // Adding LIMIT and OFFSET

        $stmt = $this->conn->prepare($query);

        // Bind parameters for search and role filter
        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam);
        }
        if (!empty($roleFilter)) {
            $stmt->bindParam(':role', $roleFilter);
        }

        // Bind limit and offset parameters
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        response(empty($users) ? 'error' : 'success', empty($users) ? 'No users found.' : 'Users retrieved successfully', $users, 200);
    }

    public function getUserById($id) {
        $roles = $this->getRoles();
        $userIdFromJWT = $this->getUserId();

        if (!in_array('Superadmin', $roles) && $userIdFromJWT != $id) {
            response('error', 'Unauthorized access.', null, 403);
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
            response('success', 'User found.', $user, 200);
        } else {
            response('error', 'User not found.', null, 404);
        }
    }

    public function createUser() {
        $roles = $this->getRoles();
        if (!in_array('Superadmin', $roles)) {
            response('error', 'Only superadmin can create users.', null, 403);
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
                response('success', 'User created successfully.', null, 201);
            } else {
                response('error', 'User creation failed.', null, 400);
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
            if (!$stmt->execute()) {
                return false; // Return false if any role assignment fails
            }
        }
        return true; // Return true if all roles are assigned successfully
    }

    public function updateUser($id) {
        $roles = $this->getRoles();
        $userIdFromJWT = $this->getUserId();

        if (!in_array('Superadmin', $roles) && $userIdFromJWT != $id) {
            response('error', 'Unauthorized access.', null, 403);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            $data = json_decode(file_get_contents("php://input"), true);
            $this->username = htmlspecialchars(strip_tags($data['username'] ?? ''));
            $this->email = htmlspecialchars(strip_tags($data['email'] ?? ''));
            $this->password = htmlspecialchars(strip_tags($data['password'] ?? ''));
            $this->about = htmlspecialchars(strip_tags($data['about'] ?? ''));

            if ($this->update($id)) {
                response('success', 'User updated successfully.', null, 200);
            } else {
                response('error', 'User update failed.', null, 400);
            }
        }
    }

    private function update($id) {
        $query = "UPDATE " . $this->table_name . " SET username = :username, email = :email, about = :about WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":user_id", $id);
        
        if (!empty($this->password)) {
            $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(":password", $hashed_password);
            $stmt->execute(); // Execute the update statement if the password is provided
        }

        return $stmt->execute();
    }

    public function deleteUser($id) {
        $roles = $this->getRoles();
        if (!in_array('Superadmin', $roles)) {
            response('error', 'Only superadmin can delete users.', null, 403);
            return;
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $id);
        
        if ($stmt->execute()) {
            response('success', 'User deleted successfully.', null, 200);
        } else {
            response('error', 'User deletion failed.', null, 400);
        }
    }
}
