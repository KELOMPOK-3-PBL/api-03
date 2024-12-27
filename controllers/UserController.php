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
    private $db;
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
        $this->db = $db;
        $this->jwtHelper = new JWTHelper();
    }

    private function getRoles() {
        return $this->jwtHelper->getRoles();
    }

    private function getUserId() {
        return $this->jwtHelper->getUserId();
    }
    
    public function getAllUsers() {
        $roles = $this->getRoles(); // Roles of the requesting user
    
        // Parameters for searching, sorting, and filtering
        $search = isset($_GET['search']) ? htmlspecialchars(strip_tags($_GET['search'])) : '';
        $sort = isset($_GET['sort']) ? htmlspecialchars(strip_tags($_GET['sort'])) : 'username';
        $order = isset($_GET['order']) && strtolower($_GET['order']) === 'desc' ? 'DESC' : 'ASC';
        $roleFilter = isset($_GET['role']) ? htmlspecialchars(strip_tags($_GET['role'])) : '';
    
        // Pagination
        $limit = isset($_GET['limit']) && is_numeric($_GET['limit']) ? (int)$_GET['limit'] : null; // No default limit
        $offset = isset($_GET['offset']) && is_numeric($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
        // Base query
        $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
                FROM " . $this->table_name . " u
                LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                LEFT JOIN roles r ON ur.role_id = r.role_id";
    
        // Filtering conditions
        $conditions = [];
        if (!empty($search)) {
            $conditions[] = "u.username LIKE :search";
        }
        if (!empty($roleFilter)) {
            $conditions[] = "r.role_name = :role";
        }
        // Exclude Superadmin role
        $conditions[] = "r.role_name != 'Superadmin'";
    
        if ($conditions) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
    
        // Grouping and ordering
        $query .= " GROUP BY u.user_id ORDER BY $sort $order";
    
        // Apply LIMIT and OFFSET if limit is set
        if ($limit !== null) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
    
        $stmt = $this->db->prepare($query);
    
        // Bind parameters
        if (!empty($search)) {
            $searchParam = '%' . $search . '%';
            $stmt->bindParam(':search', $searchParam);
        }
        if (!empty($roleFilter)) {
            $stmt->bindParam(':role', $roleFilter);
        }
        if ($limit !== null) {
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        }
    
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        // Process each user based on the role of the requesting user
        foreach ($users as &$user) {
            unset($user['password']); // Always unset password
    
            // If the requesting user is not a Superadmin
            if (!in_array('Superadmin', $roles)) {
                unset($user['email']);
                unset($user['roles']);
            }
        }
    
        response(empty($users) ? 'error' : 'success', empty($users) ? 'No users found.' : 'Users retrieved successfully', $users, 200);
    }
    

    public function getUserById($id) {
        $roles = $this->getRoles();
        $userIdFromJWT = $this->getUserId();
    
        $query = "SELECT u.*, GROUP_CONCAT(r.role_name) as roles 
                  FROM " . $this->table_name . " u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id
                  WHERE u.user_id = :user_id
                  GROUP BY u.user_id";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':user_id', $id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if (!$user) {
            response('error', 'User not found.', null, 404);
            return;
        }
    
        // Hapus field password
        unset($user['password']);
    
        // Periksa role pengguna target
        $targetRoles = explode(',', $user['roles']);
        if (in_array('Superadmin', $targetRoles) && $userIdFromJWT != $id) {
            response('error', 'Unauthorized access to Superadmin profile.', null, 403);
            return;
        }
    
        // Handle field visibility for non-Superadmin roles
        if (!in_array('Superadmin', $roles)) {
            unset($user['email']);
            unset($user['roles']);
        }
    
        response('success', 'User found.', $user, 200);
    }   
     

    public function searchUsers($query) {
        $roles = $this->getRoles();
        if (empty($roles) || !array_intersect($roles, ['Superadmin', 'Admin', 'Propose'])) {
            response('error', 'Unauthorized access.', null, 403);
            return;
        }

        $sql = "SELECT username FROM user WHERE username LIKE :query LIMIT 50";
    
        $stmt = $this->db->prepare($sql);
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
    
    // Method to handle bulk user creation via file upload
    public function bulkUserUpload() {
        // Check if the user has 'Superadmin' role
        $roles = $this->getRoles();
        if (!in_array('Superadmin', $roles)) {
            response('error', 'Only Superadmin can bulk upload users.', null, 403);
            return;
        }
    
        // Ensure a file was uploaded
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            response('error', 'No file uploaded or file upload failed.', null, 400);
            return;
        }
    
        // Validate file type (only CSV, XLSX, XLS allowed)
        $allowedExtensions = ['xls', 'xlsx', 'csv'];
        $fileInfo = pathinfo($_FILES['file']['name']);
        $fileExtension = strtolower($fileInfo['extension']);
    
        if (!in_array($fileExtension, $allowedExtensions)) {
            response('error', 'Invalid file type. Only .xls, .xlsx, and .csv files are allowed.', null, 400);
            return;
        }
    
        // Process the file (using PhpSpreadsheet for XLSX/CSV files)
        $file = $_FILES['file']['tmp_name'];
    
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file);
            $sheet = $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();
    
            // Validate header
            $header = $rows[0]; // Assuming first row contains the header
            $expectedHeader = ['Username', 'Email', 'Password', 'Roles'];
    
            foreach ($expectedHeader as $index => $column) {
                if (strtolower(trim($header[$index])) !== strtolower($column)) {
                    response('error', 'Invalid file format. Missing or incorrect headers.', null, 400);
                    return;
                }
            }
    
            // Process rows (skipping header)
            $usersCreated = 0;
            $errors = [];
    
            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                $this->username = htmlspecialchars(strip_tags($row[0]));
                $this->email = htmlspecialchars(strip_tags($row[1]));
                $this->password = htmlspecialchars(strip_tags($row[2]));
                $rolesInput = isset($row[3]) ? $row[3] : '1'; // Default to '1' if no roles input
    
                // Convert roles input to array (comma separated)
                $this->roles = !empty($rolesInput) ? array_map('intval', explode(',', $rolesInput)) : [];
    
                // Check if mandatory fields are present
                if (empty($this->username) || empty($this->email) || empty($this->password)) {
                    $errors[] = "Missing data for user in row $i.";
                    continue; // Skip this row
                }
    
                // Check for duplicate email
                if ($this->emailExists($this->email)) {
                    $errors[] = "Email '$this->email' already exists. Skipping row $i.";
                    continue; // Skip this row
                }
    
                // Create the user
                if ($this->create()) {
                    $usersCreated++;
                } else {
                    $errors[] = "Failed to create user '$this->username' in row $i.";
                }
            }
    
            // Return response with success or failure
            if ($usersCreated > 0) {
                response('success', "$usersCreated users created successfully.", null, 200);
            }
    
            if (!empty($errors)) {
                response('error', 'Some users could not be created: ' . implode(' ', $errors), null, 400);
            }
    
        } catch (Exception $e) {
            response('error', 'Failed to process the file: ' . $e->getMessage(), null, 500);
        }
    }
    
    // Method to check if email already exists in the database
    private function emailExists($email) {
        // Replace with your actual database check logic
        $query = "SELECT COUNT(*) FROM user WHERE email = :email";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $count = $stmt->fetchColumn();
        return $count > 0; // Return true if email exists
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
        
        // Check if roles are provided
        if (empty($rolesInput)) {
            response('error', 'Roles must be specified.', null, 400);
            return;
        }
    
        $this->roles = array_map('intval', explode(',', $rolesInput)); // Convert roles to an array of integers
    
        if (isset($_FILES['avatar'])) {
            $fileUploadHelper = new FileUploadHelper();
            $this->avatar = $fileUploadHelper->uploadFile($_FILES['avatar'], 'avatar');
        }
    
        if ($this->create()) {
            // Return success response with the created user data
            $userData = [
                'username' => $this->username,
                'email' => $this->email,
                'about' => $this->about,
                'roles' => $this->roles,
                'avatar' => $this->avatar ?? null, // Avatar URL or null if not set
            ];
            response('success', 'User created successfully.', $userData, 201);
        } else {
            response('error', 'User creation failed.', null, 400);
        }
    }
    
    
    private function create() {
        $query = "INSERT INTO " . $this->table_name . " (username, email, password, about, avatar) 
                  VALUES(:username, :email, :password, :about, :avatar)";
        $stmt = $this->db->prepare($query);
    
        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":email", $this->email);
        
        // Hash the password and store it in a variable first
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindParam(":password", $hashed_password);
        
        $stmt->bindParam(":about", $this->about);
        $stmt->bindParam(":avatar", $this->avatar);  // Make sure $this->avatar is a variable
    
        if ($stmt->execute()) {
            $this->user_id = $this->db->lastInsertId();
            return $this->assignRoles();
        }
    
        return false;
    }   

    private function assignRoles() {
        // First, delete any existing roles for this user
        $deleteQuery = "DELETE FROM user_roles WHERE user_id = :user_id";
        $stmt = $this->db->prepare($deleteQuery);
        $stmt->bindParam(":user_id", $this->user_id);
        $stmt->execute();
    
        // If no roles are provided, return true (nothing to update)
        if (empty($this->roles)) {
            return true;
        }
    
        // Assign new roles to the user
        $insertQuery = "INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)";
        $stmt = $this->db->prepare($insertQuery);
    
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
    
        // Set the user ID for subsequent operations
        $this->user_id = $id;
    
        // Handle data updates based on roles
        if (in_array('Superadmin', $roles)) {
            $this->username = htmlspecialchars(strip_tags($_POST['username'] ?? $currentUserData['username']));
            $this->email = htmlspecialchars(strip_tags($_POST['email'] ?? $currentUserData['email']));
            $rolesInput = $_POST['roles'] ?? '';
            $this->roles = array_map('intval', explode(',', $rolesInput));
        } else {
            $this->about = htmlspecialchars(strip_tags($_POST['about'] ?? $currentUserData['about']));
            $this->password = htmlspecialchars(strip_tags($_POST['password'] ?? $currentUserData['password']));
            $this->avatar = $currentUserData['avatar'];
    
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $fileUploadHelper = new FileUploadHelper();
                try {
                    $this->avatar = $fileUploadHelper->uploadFile($_FILES['avatar'], 'avatar', $currentUserData['avatar']);
                    if ($currentUserData['avatar']) {
                        $fileUploadHelper->deleteFile($currentUserData['avatar']);
                    }
                } catch (Exception $e) {
                    response('error', 'Failed to upload avatar. ' . $e->getMessage(), null, 500);
                    return;
                }
            }
        }
    
        // Update the user
        if ($this->update($id)) {
            if (!empty($this->roles) && !$this->assignRoles()) {
                response('error', 'Failed to update roles.', null, 400);
                return;
            }
            $updatedUserData = $this->getUserDataById($id);
            unset($updatedUserData['password']);
            response('success', 'User updated successfully.', $updatedUserData, 200);
        } else {
            response('error', 'Failed to update user.', null, 400);
        }
    }
    
       
    private function update($id) {
        // Start building the base query
        $query = "UPDATE " . $this->table_name . " SET";
        $fields = [];
        $bindings = [];
    
        // Only allow Superadmin to update specific fields
        if (in_array('Superadmin', $this->getRoles())) {
            if (!empty($this->username)) {
                $fields[] = "username = :username";
                $bindings[":username"] = $this->username;
            }
            if (!empty($this->email)) {
                $fields[] = "email = :email";
                $bindings[":email"] = $this->email;
            }
            if (!empty($this->roles)) {
                // Roles are handled outside SQL, no need to include here
                // Just to make sure we handle them in assignRoles()
            }
        } else {
            // Non-Superadmin: Can only update about, avatar, and password
            if (!empty($this->about)) {
                $fields[] = "about = :about";
                $bindings[":about"] = $this->about;
            }
            if (!empty($this->avatar)) {
                $fields[] = "avatar = :avatar";
                $bindings[":avatar"] = $this->avatar;
            }
            if (!empty($this->password)) {
                $fields[] = "password = :password";
                $bindings[":password"] = password_hash($this->password, PASSWORD_BCRYPT);
            }
        }
    
        // Combine the fields for the SET clause
        if (empty($fields)) {
            // No valid fields to update
            response('error', 'No valid fields to update.', null, 400);
            return false;
        }
    
        $query .= " " . implode(", ", $fields) . " WHERE user_id = :user_id";
        $bindings[":user_id"] = $id;
    
        // Prepare the SQL statement
        $stmt = $this->db->prepare($query);
    
        // Bind the parameters dynamically
        foreach ($bindings as $param => $value) {
            $stmt->bindValue($param, $value);
        }
    
        // Execute the update query
        return $stmt->execute();
    }
    
    
    
    private function getUserDataById($id) {
        // Query to fetch the user data based on user_id
        $query = "SELECT * FROM " . $this->table_name . " WHERE user_id = :user_id";
        $stmt = $this->db->prepare($query);
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
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(":user_id", $id);

        if ($stmt->execute()) {
            response('success', 'User deleted successfully.', null, 200);
        } else {
            response('error', 'User deletion failed.', null, 400);
        }
    }
    // private function getOldAvatar($userId) {
    //     $stmt = $this->db->prepare("SELECT avatar FROM users WHERE user_id = ?");
    //     $stmt->execute([$userId]);
    //     $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    //     // Return the avatar filename if it exists
    //     return $result ? $result['avatar'] : null;
    // }
    
}
