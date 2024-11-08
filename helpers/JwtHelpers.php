<?php
require_once '../config/JwtConfig.php';
require_once '../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHelper {
    private $jwtSecret;

    public function __construct() {
        $this->jwtSecret = JWT_SECRET; // Get secret from config file
    }

    private function getJWTFromHeader() {
        $headers = apache_request_headers();
        if (isset($headers['Authorization'])) {
            $matches = [];
            if (preg_match('/Bearer\s(\S+)/', $headers['Authorization'], $matches)) {
                return $matches[1];
            } else {
                throw new Exception('Invalid authorization format in header.', 401);
            }
        } else {
            throw new Exception('Authorization token not provided in header.', 401);
        }
    }
    

    private function getJWTFromCookie() {
        if (!isset($_COOKIE['jwt'])) {
            throw new Exception('Authorization token not provided in cookie.', 401);
        }
        
        return $_COOKIE['jwt'];
    }

    public function getJWT() {
        try {
            // Prioritize checking the Authorization header
            return $this->getJWTFromHeader();
        } catch (Exception $e) {
            // If header is not available, try getting from cookie (for web)
            return $this->getJWTFromCookie();
        }
    }

    public function decodeJWT() {
        $jwt = $this->getJWT();

        try {
            return (array) JWT::decode($jwt, new Key($this->jwtSecret, 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage(), 401);
        }
    }

    public function getRoles() {
        $decoded = $this->decodeJWT();
        if (isset($decoded['roles'])) {
            if (is_array($decoded['roles'])) {
                return array_map('trim', $decoded['roles']);
            } elseif (is_string($decoded['roles'])) {
                return array_map('trim', explode(',', $decoded['roles']));
            }
        }
        return [];
    }

    public function getUserId() {
        $decoded = $this->decodeJWT();
        return $decoded['user_id'] ?? null;
    }

    public function getUserData() {
        return $this->decodeJWT();
    }
}
