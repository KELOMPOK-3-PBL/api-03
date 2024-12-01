<?php

include_once '../config/database.php';
require_once '../config/JwtConfig.php';
require_once '../vendor/autoload.php';
require_once '../helpers/ResponseHelpers.php';
require_once '../helpers/JwtHelpers.php';
require_once '../helpers/FileUploadHelper.php';

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
    public $avatar; 
    public $roles = [];

    public function __construct($db) {
        $this->conn = $db;
        $this->jwtHelper = new JWTHelper();
    }

    private function getRoles() {
        return $this->jwtHelper->getRoles();
    }

    private function getUserId() {
        return $this->jwtHelper->getUserId();
    }

    public function getAllUsers() {
        $roles = $this->getRoles();
    
        if (!in_array('Superadmin', $roles)) {
            response('error', 'Only superadmin can access this resource.', null, 403);
            return;
        }
    
        // Parameters for searching, sorting, and filtering
        $search = isset($_GET['search']) ? htmlspecialchars(strip_tags($_GET['search'])) : '';
        $sort = isset($_GET['sort']) ? htmlspecialchars(strip_tags($_GET['sort'])) : 'username';
        $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
        $roleFilter = isset($_GET['role']) ? htmlspecialchars(strip_tags($_GET['role'])) : '';
    
        // Pagination
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : null; // No default limit
        $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
        $offset = $limit ? ($page - 1) * $limit : 0; // Calculate offset only if limit is set
    
        // Base query
        $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
                  FROM " . $this->table_name . " u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id";
    
        // Filtering conditions
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
    
        // Grouping and ordering
        $query .= " GROUP BY u.user_id ORDER BY $sort $order";
    
        // Apply LIMIT and OFFSET if limit is set
        if ($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
    
        $stmt = $this->conn->prepare($query);
    
        // Bind parameters
        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam);
        }
        if (!empty($roleFilter)) {
            $stmt->bindParam(':role', $roleFilter);
        }
        if ($limit) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
    
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Remove the password field from each user
        foreach ($users as &$user) {
            unset($user['password']);
        }
    
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
    
        // Remove the password field if user data exists
        if ($user) {
            unset($user['password']);
        }
    
        response($user ? 'success' : 'error', $user ? 'User found.' : 'User not found.', $user, $user ? 200 : 404);
    }    

    public function searchUsers($query) {
        $roles = $this->getRoles();
        if (empty($roles)) {
            response('error', 'Unauthorized access.', null, 403);
            return;
        }

        $sql = "SELECT username FROM user WHERE username LIKE :query LIMIT 50";
    
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            response('error', 'Failed to prepare statement.', null, 500);
            return;
        }
    
        $likeQuery = "%" . $query . "%";
        $stmt->bindParam(':query', $likeQuery, PDO::PARAM_STR);
    
        if (!$stmt->execute()) {
            response('error', 'Failed to execute query.', null, 500);
            return;
        }
    
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$users) {
            response('error', 'No results found.', null, 404);
            return;
        }
    
        $usernames = array_column($users, 'username');
        response('success', 'Users found.', $usernames, 200);
    }    
    
    public function createUser() {
        $roles = $this->getRoles();
        if (!in_array('Superadmin', $roles)) {
            response('error', 'Only superadmin can create users.', null, 403);
            return;
        }
    
        $this->username = htmlspecialchars(strip_tags($_POST['username'] ?? ''));
        $this->email = htmlspecialchars(strip_tags($_POST['email'] ?? ''));
        $this->password = htmlspecialchars(strip_tags($_POST['password'] ?? ''));
        $this->about = htmlspecialchars(strip_tags($_POST['about'] ?? ''));
        
        // Convert roles input (e.g., "1,3") to an array
        $rolesInput = $_POST['roles'] ?? '';
        $this->roles = array_map('intval', explode(',', $rolesInput)); // Convert roles to an array of integers
    
        if (isset($_FILES['avatar'])) {
            $fileUploadHelper = new FileUploadHelper();
            $this->avatar = $fileUploadHelper->uploadFile($_FILES['avatar'], 'avatar');
        }
    
        if ($this->create()) {
            response('success', 'User created successfully.', null, 201);
        } else {
            response('error', 'User creation failed.', null, 400);
        }
    }
    
    private function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, email, password, about, avatar) 
                  VALUES(:username, :email, :password, :about, :avatar)";
        $stmt = $this->conn->prepare($query);
    
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        
        // Hash the password and store it in a variable first
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $hashed_password);
        
        $stmt->bindParam(":about", $this->about);
        $stmt->bindParam(":avatar", $this->avatar);  // Make sure $this->avatar is a variable
    
        if ($stmt->execute()) {
            $this->user_id = $this->conn->lastInsertId();
            return $this->assignRoles();
        }
    
        return false;
    }   

    private function assignRoles() {
        // First, delete any existing roles for this user
        $deleteQuery = "DELETE FROM user_roles WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($deleteQuery);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
    
        // If no roles are provided, return true (nothing to update)
        if (empty($this->roles)) {
            return true;
        }
    
        // Assign new roles to the user
        $insertQuery = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
        $stmt = $this->conn->prepare($insertQuery);
    
        foreach ($this->roles as $roleId) {
            $stmt->bindParam(":user_id", $this->user_id);
            $stmt->bindParam(":role_id", $roleId);
    
            if (!$stmt->execute()) {
                return false;  // Return false if any insert fails
            }
        }
    
        return true;
    }

    public function updateUser($id) {
        // Get roles and user ID from JWT
        $roles = $this->getRoles();
        $userIdFromJWT = $this->getUserId();
    
        // Check if user is authorized to update based on their role
        if (!in_array('Superadmin', $roles) && $userIdFromJWT != $id) {
            response('error', 'Unauthorized access.', null, 403);
            return;
        }
    
        // Fetch the current user data from the database
        $currentUserData = $this->getUserDataById($id);
        
        if (!$currentUserData) {
            response('error', 'User not found.', null, 404);
            return;
        }
    
        // Initialize user fields with existing data if no new data is provided
        // For Superadmin, allow all updates
        if (in_array('Superadmin', $roles)) {
            $this->username = htmlspecialchars(strip_tags($_POST['username'] ?? $currentUserData['username']));
            $this->email = htmlspecialchars(strip_tags($_POST['email'] ?? $currentUserData['email']));
        }
    
        // Members can only update their password, about, and avatar
        $this->password = htmlspecialchars(strip_tags($_POST['password'] ?? $currentUserData['password']));
        $this->about = htmlspecialchars(strip_tags($_POST['about'] ?? $currentUserData['about']));
    
        // Convert roles input (e.g., "1,3") to an array if it's a superadmin
        $rolesInput = $_POST['roles'] ?? '';
        if (in_array('Superadmin', $roles)) {
            $this->roles = array_map('intval', explode(',', $rolesInput)); // Convert roles to an array of integers
        } else {
            $this->roles = []; // If not superadmin, roles should remain empty
        }
    
        // Set user_id to the provided id (needed for the assignRoles method)
        $this->user_id = $id;  // Ensure user_id is set correctly
    
        // If avatar is provided, handle the upload
        if (isset($_FILES['avatar'])) {
            // Fetch the current avatar file path (you can fetch this from the database or existing user data)
            $oldAvatar = $currentUserData['avatar'] ?? null;
            
            // Upload the new avatar and delete the old one if provided
            $fileUploadHelper = new FileUploadHelper();
            $this->avatar = $fileUploadHelper->uploadFile($_FILES['avatar'], 'avatar', $oldAvatar);
        } else {
            $this->avatar = $currentUserData['avatar']; // If no new avatar is uploaded, retain the old one
        }
    
        // If the update is successful
        if ($this->update($id)) {
            if (!empty($this->roles) && !$this->assignRoles()) {
                // If roles were updated, and assigning them failed, return an error
                response('error', 'User update failed while updating roles.', null, 400);
            } else {
                // If roles were not updated or assignment was successful
                $updatedUserData = $this->getUserDataById($id); // Fetch updated user data
                response('success', 'User updated successfully.', $updatedUserData, 200);
            }
        } else {
            response('error', 'User update failed.', null, 400);
        }
    }
       
    private function update($id) {
        // Start building the base query
        $query = "UPDATE " . $this->table_name . " SET username = :username, email = :email, about = :about";
        
        // Add avatar to the query if provided
        if ($this->avatar) {
            $query .= ", avatar = :avatar";  // Append avatar update only if it's provided
        }
        
        // Add password to the query if provided
        if (!empty($this->password)) {
            $query .= ", password = :password";  // Append password update only if it's provided
        }
        
        // Finalize the query with the WHERE clause
        $query .= " WHERE user_id = :user_id";
        
        // Prepare the SQL statement
        $stmt = $this->conn->prepare($query);
        
        // Bind the parameters
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        $stmt->bindParam(":about", $this->about);
        $stmt->bindParam(":user_id", $id);
        
        // Bind password if provided
        if (!empty($this->password)) {
            $hashedPassword = password_hash($this->password, PASSWORD_BCRYPT);
            $stmt->bindParam(":password", $hashedPassword);
        }
        
        // Bind avatar if provided
        if ($this->avatar) {
            $stmt->bindParam(":avatar", $this->avatar);
        }
        
        // Execute the update query
        return $stmt->execute();
    }
    
    private function getUserDataById($id) {
        // Query to fetch the user data based on user_id
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC); // Return the user data as an associative array
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
