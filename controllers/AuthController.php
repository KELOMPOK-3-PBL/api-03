<?php
require_once '../config/JwtConfig.php';
require_once '../vendor/autoload.php'; 
require_once '../helpers/ResponseHelpers.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Generate JWT
    private function generateJWT($userId, $roles) {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_EXPIRATION_TIME; // Token valid for the configured expiration time
        $payload = [
            'user_id' => $userId,
            'roles' => $roles,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
        ];
        
        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    // Set JWT in cookie
    private function setJWTInCookie($jwt) {
        // Set cookie parameters
        $cookieParams = [
            'expires' => time() + JWT_EXPIRATION_TIME, // Cookie expiration time
            'path' => '/', // Cookie path
            'domain' => '', // Set domain if needed
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true, // Prevent JavaScript access to the cookie
            'samesite' => 'Strict', // Set SameSite attribute for CSRF protection
        ];
        
        // Set the cookie
        setcookie('jwt', $jwt, $cookieParams);
    }

    // User login
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';

        // Validate input
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            response('error', 'Invalid email format.', null, 400);
            return;
        }
    
        if (empty($password)) {
            response('error', 'Password is required.', null, 400);
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
                // Create JWT
                $roles = explode(',', $result['roles'] ?? '');
                $jwt = $this->generateJWT($result['user_id'], $roles);
                
                // Set JWT in cookie
                $this->setJWTInCookie($jwt);

                response('success', 'Login successful.', ['token' => $jwt], 200);
            } else {
                response('error', 'Incorrect email or password.', null, 401);
            }
        } else {
            response('error', 'User not found.', null, 404);
        }
    }

    // User logout
    public function logout() {
        // Clear the cookie
        setcookie('jwt', '', time() - 3600, '/');
        response('success', 'Logout successful.', null, 200);
    }

    // Password recovery
    public function forgotPassword() {
        $data = json_decode(file_get_contents("php://input"), true);

        $email = $data['email'] ?? '';

        // Validate email
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            response('error', 'Invalid email format.', null, 400);
            return;
        }

        // Check if email exists in the database
        $stmt = $this->db->prepare("SELECT * FROM user WHERE email = ?");
        $stmt->execute([$email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            // Here you can implement sending a reset password email
            response('success', 'Reset password link sent.', null, 200);
        } else {
            response('error', 'Email not found.', null, 404);
        }
    }

    // Change user password
    public function changePassword($userId) {
        $data = json_decode(file_get_contents("php://input"), true);

        $oldPassword = $data['oldPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        // Validate input
        if (empty($oldPassword) || empty($newPassword)) {
            response('error', 'Both old and new passwords are required.', null, 400);
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
                if ($updateStmt->execute([$hashedPassword, $userId])) {
                    response('success', 'Password changed successfully.', null, 200);
                } else {
                    response('error', 'Password change failed.', null, 500);
                }
            } else {
                response('error', 'Old password is incorrect.', null, 401);
            }
        } else {
            response('error', 'User not found.', null, 404);
        }
    }
}
?>
