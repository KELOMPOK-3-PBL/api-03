<?php
require_once '../config/JwtConfig.php';
require_once '../vendor/autoload.php'; 
require_once '../helpers/ResponseHelpers.php';
require_once '../helpers/JwtHelpers.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController {
    private $db;

    private $jwtHelper;

    public function __construct($db) {
        $this->db = $db;
        $this->jwtHelper = new JWTHelper();
    }

    private function getUserId() {
        return $this->jwtHelper->getUserId();
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

    private function generateRefreshToken($userId, $roles) {
        $issuedAt = time();
        $expirationTime = $issuedAt + JWT_REFRESH_EXPIRATION_TIME; // Set longer expiration time for refresh token
        $payload = [
            'user_id' => $userId,
            'roles' => $roles,
            'iat' => $issuedAt,
            'exp' => $expirationTime,
        ];
        
        return JWT::encode($payload, JWT_SECRET, 'HS256');
    }

    private function setRefreshTokenInCookie($refreshToken) {
        $cookieParams = [
            'expires' => time() + JWT_REFRESH_EXPIRATION_TIME, // Longer expiration time for refresh token
            'path' => '/', 
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true, // Prevent JavaScript access to the cookie
            'samesite' => 'Strict',
        ];
        
        setcookie('refresh_token', $refreshToken, $cookieParams);
    }

    private function setJWTInCookie($jwt, $expiration = null) {
        // Set cookie parameters
        $cookieParams = [
            'path' => '/', // Cookie path
            'domain' => '', // Set domain if needed
            'secure' => false, // Set to true if using HTTPS
            'httponly' => true, // Prevent JavaScript access to the cookie
            'samesite' => 'Strict', // Set SameSite attribute for CSRF protection
        ];
    
        // If expiration is provided, set it; otherwise, it will default to a session cookie
        // if ($expiration) {
        //     $cookieParams['expires'] = time() + $expiration;
        // }
    
        // Set the cookie
        setcookie('jwt', $jwt, $cookieParams);
    }

    // User login
    public function login() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        $rememberMe = $data['remember_me'] ?? false; // Capture remember me option

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
                $refreshToken = $this->generateRefreshToken($result['user_id'], $roles);
                
                // Set JWT and refresh token in cookies
                if ($rememberMe) {
                    // If remember me is checked, set a longer expiration time
                    $this->setJWTInCookie($jwt, JWT_EXPIRATION_TIME); // Use long expiration for "Remember Me"
                    $this->setRefreshTokenInCookie($refreshToken);
                } else {
                    // Otherwise, set a session cookie (no expiration time)
                    $this->setJWTInCookie($jwt); // Session cookie
                    $this->setRefreshTokenInCookie($refreshToken);
                }

                response('success', 'Login successful.', ['token' => $jwt, 'refresh_token' => $refreshToken], 200);
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
        setcookie('refresh_token', '', time() - 3600, '/');
        response('success', 'Logout successful.', null, 200);
    }

    public function checkLogin() {
        // Inisialisasi JWTHelper 
        $userId  = $this->getUserId();
        try {
            // Decode JWT untuk mendapatkan data pengguna
            if (!$userId) {
                response('error', 'User ID not found in token.', null, 401);
                return;
            }
    
            // Ambil data pengguna dari database berdasarkan user_id
            $stmt = $this->db->prepare("SELECT u.*, r.role_name FROM user u 
                                         LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                                         LEFT JOIN roles r ON ur.role_id = r.role_id 
                                         WHERE u.user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($user) {
                unset($user['password']); // Jangan kirimkan password ke pengguna
                
                // Jika pengguna ditemukan, kirimkan data pengguna beserta perannya
                response('success', 'User data fetched successfully.', $user, 200);
            } else {
                response('error', 'User not found.', null, 404);
            }
        } catch (PDOException $e) {
            // Tangani kesalahan SQL
            response('error', $e->getMessage(), null, 500);
        } catch (Exception $e) {
            // Tangani error lainnya
            response('error', $e->getMessage(), null, $e->getCode() ?? 401);
        }
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
            // Define a default password
            $defaultPassword = 'polivent'; // Ensure it meets your security policy
    
            // Hash the default password
            $hashedPassword = password_hash($defaultPassword, PASSWORD_DEFAULT);
    
            // Update the user's password in the database
            $updateStmt = $this->db->prepare("UPDATE user SET password = ? WHERE email = ?");
            if ($updateStmt->execute([$hashedPassword, $email])) {
                response('success', 'Password has been reset to the default value.', null, 200);
            } else {
                response('error', 'Failed to reset the password.', null, 500);
            }
        } else {
            response('error', 'Email not found.', null, 404);
        }
    }
    

    // Reset Password
    public function resetPassword() {
        $data = json_decode(file_get_contents("php://input"), true);
        $token = $data['token'] ?? '';
        $password = $data['password'] ?? '';
    
        // Validate token
        if (empty($token)) {
            response('error', 'Token is required.', null, 400);
            return;
        }
    
        // Check if token exists in the database
        $stmt = $this->db->prepare("SELECT * FROM password_resets WHERE token = ? AND expires_at > ?");
        $stmt->execute([$token, date('Y-m-d H:i:s')]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
        if ($result) {
            // Validate password
            if (empty($password) || strlen($password) < 8) {
                response('error', 'Password must be at least 8 characters long.', null, 400);
                return;
            }
    
            // Hash the new password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
            // Update the password in the database
            $stmt = $this->db->prepare("UPDATE user SET password = ? WHERE user_id = ?");
            $stmt->execute([$hashedPassword, $result['user_id']]);
    
            // Remove the token from the database
            $stmt = $this->db->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);
    
            response('success', 'Password has been reset successfully.', null, 200);
        } else {
            response('error', 'Invalid or expired token.', null, 400);
        }
    }
}
?>
