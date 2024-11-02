<?php
require_once '../config/JwtConfig.php'; // Include the JWT config file
require_once '../vendor/autoload.php'; // Make sure to include the JWT library if you're using Composer

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHelper {
    private $jwtSecret;

    public function __construct() {
        $this->jwtSecret = JWT_SECRET; // Get the secret from the config file
    }

    private function getJWTFromCookie() {
        if (!isset($_COOKIE['jwt'])) {
            throw new Exception('Authorization token not provided.', 401);
        }
        
        $jwt = $_COOKIE['jwt'];
        if (!$jwt) {
            throw new Exception('Token is not valid.', 401);
        }

        return $jwt;
    }

    private function getJWTFromHeader() {
        // Implement the logic to get the JWT from the Authorization header
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new Exception('Authorization token not provided.', 401);
        }
        
        $matches = [];
        if (preg_match('/Bearer (.+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        } else {
            throw new Exception('Invalid authorization format.', 401);
        }
    }

    public function decodeJWT() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        $jwt = (isset($userAgent) && strpos($userAgent, 'Mobile') !== false)
            ? $this->getJWTFromHeader()
            : $this->getJWTFromCookie();

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

