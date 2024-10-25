<?php
session_start();

require_once '../config/database.php'; // Include your database connection file

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Function to set JSON headers and CORS headers
    private function setHeaders() {
        header("Content-Type: application/json; charset=UTF-8");
        header("Access-Control-Allow-Origin: http://localhost:50581"); // Replace with actual frontend URL
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Authorization");
        header("Access-Control-Max-Age: 3600");
    }

    // User login
    public function login() {
        $this->setHeaders(); // Set the headers
        $data = json_decode(file_get_contents("php://input"), true);
        
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
    
        // Validate input
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format.'], JSON_PRETTY_PRINT);
            return;
        }
    
        if (empty($password)) {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode(['status' => 'error', 'message' => 'Password is required.'], JSON_PRETTY_PRINT);
            return;
        }
    
        // Check user in the database with roles using JOIN
        $stmt = $this->db->prepare("
            SELECT u.user_id, u.username, u.email, u.password, GROUP_CONCAT(r.role_name) AS roles
            FROM user u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE u.email = ?
            GROUP BY u.user_id
        ");
        
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($result) {
            // Verify password
            if (password_verify($password, $result['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $result['user_id'];
                $_SESSION['username'] = $result['username'];
                
                // Convert comma-separated roles into an array
                $roles = !empty($result['roles']) ? explode(',', $result['roles']) : [];
    
                // Send the roles as an array to Flutter, ordered as user_id, username, roles
                echo json_encode([
                    'status' => 'success',
                    'message' => 'Login successful.',
                    'user_id' => $result['user_id'],
                    'username' => $result['username'],
                    'roles' => $roles // roles will now contain the actual role names
                ], JSON_PRETTY_PRINT);
            } else {
                header("HTTP/1.0 401 Unauthorized");
                echo json_encode(['status' => 'error', 'message' => 'Incorrect password.'], JSON_PRETTY_PRINT);
            }
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode(['status' => 'error', 'message' => 'User not found.'], JSON_PRETTY_PRINT);
        }
    }
    

    


    // User logout
    public function logout() {
        $this->setHeaders(); // Set the headers
        session_destroy(); // Destroy session
        echo json_encode(['status' => 'success', 'message' => 'Logout successful.'], JSON_PRETTY_PRINT);
    }

    // Password recovery
    public function forgotPassword() {
        $this->setHeaders(); // Set the headers
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? '';

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode(['status' => 'error', 'message' => 'Invalid email format.'], JSON_PRETTY_PRINT);
            return;
        }

        // Check if email exists in the database
        $stmt = $this->db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Here you can implement sending a reset password email
            echo json_encode(['status' => 'success', 'message' => 'Reset password link sent.'], JSON_PRETTY_PRINT);
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode(['status' => 'error', 'message' => 'Email not found.'], JSON_PRETTY_PRINT);
        }
    }

    // Change user password
    public function changePassword($userId) {
        $this->setHeaders(); // Set the headers
        $data = json_decode(file_get_contents("php://input"), true);

        $oldPassword = $data['oldPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        // Validate input
        if (empty($oldPassword) || empty($newPassword)) {
            header("HTTP/1.0 400 Bad Request");
            echo json_encode(['status' => 'error', 'message' => 'Both old and new passwords are required.'], JSON_PRETTY_PRINT);
            return;
        }

        // Check if user exists
        $stmt = $this->db->prepare("SELECT * FROM user WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Verify old password
            if (password_verify($oldPassword, $result['password'])) {
                // Update with new password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $updateStmt = $this->db->prepare("UPDATE user SET password = ? WHERE user_id = ?");
                $updateStmt->execute([$hashedPassword, $userId]); // Pass parameters as an array

                echo json_encode(['status' => 'success', 'message' => 'Password changed successfully.'], JSON_PRETTY_PRINT);
            } else {
                header("HTTP/1.0 401 Unauthorized");
                echo json_encode(['status' => 'error', 'message' => 'Old password is incorrect.'], JSON_PRETTY_PRINT);
            }
        } else {
            header("HTTP/1.0 404 Not Found");
            echo json_encode(['status' => 'error', 'message' => 'User not found.'], JSON_PRETTY_PRINT);
        }
    }
}
?>
