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

    public function decodeJWT() {
        $jwt = $this->getJWTFromCookie();

        try {
            return (array) JWT::decode($jwt, new Key($this->jwtSecret, 'HS256'));
        } catch (Exception $e) {
            throw new Exception('Invalid token: ' . $e->getMessage(), 401);
        }
    }

    public function getRoles() {
        $decoded = $this->decodeJWT();
        if (isset($decoded['roles'])) {
            // Check if roles is an array or a string
            if (is_array($decoded['roles'])) {
                return array_map('trim', $decoded['roles']); // If it's already an array, just trim whitespace
            } elseif (is_string($decoded['roles'])) {
                return array_map('trim', explode(',', $decoded['roles'])); // Split if it's a string
            }
        }
        return []; // Return an empty array if no roles found
    }
    

    public function getUserId() {
        $decoded = $this->decodeJWT();
        return $decoded['user_id'] ?? null;
    }

    public function getUserData() {
        return $this->decodeJWT();
    }
}
