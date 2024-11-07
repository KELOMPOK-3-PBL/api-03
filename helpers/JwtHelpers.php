<?php
require_once '../config/JwtConfig.php'; // Sertakan file konfigurasi JWT
require_once '../vendor/autoload.php'; // Pastikan untuk menyertakan library JWT jika menggunakan Composer

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHelper {
    private $jwtSecret;

    public function __construct() {
        $this->jwtSecret = JWT_SECRET; // Dapatkan secret dari file konfigurasi
    }

    private function getJWTFromHeader() {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            throw new Exception('Authorization token not provided in header.', 401);
        }
        
        $matches = [];
        if (preg_match('/Bearer (.+)/', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            return $matches[1];
        } else {
            throw new Exception('Invalid authorization format in header.', 401);
        }
    }

    // Opsional: Jika Anda masih ingin mendukung cookie
    private function getJWTFromCookie() {
        if (!isset($_COOKIE['jwt'])) {
            throw new Exception('Authorization token not provided in cookie.', 401);
        }
        
        $jwt = $_COOKIE['jwt'];
        if (!$jwt) {
            throw new Exception('Token is not valid.', 401);
        }

        return $jwt;
    }

    public function getJWT() {
        // Prioritaskan header Authorization
        try {
            return $this->getJWTFromHeader();
        } catch (Exception $e) {
            // Jika header tidak tersedia, coba dari cookie
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
