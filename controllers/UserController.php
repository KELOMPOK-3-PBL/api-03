<?php
include_once '../config/database.php';
include_once '../models/User.php';

class UserController {
    private $user;

    public function __construct($db) {
        $this->user = new User($db);
    }

    // List all users
    public function index() {
        $stmt = $this->user->read();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json'); // Set header Content-Type
        echo json_encode([
            'status' => 'success',
            'data' => $users
        ], JSON_PRETTY_PRINT); // Menggunakan JSON_PRETTY_PRINT untuk format yang lebih rapi
    }

    // Get a specific user by ID
    public function show($id) {
        $stmt = $this->user->find($id);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        header('Content-Type: application/json'); // Set header Content-Type
        if ($user) {
            echo json_encode([
                'status' => 'success',
                'data' => $user
            ], JSON_PRETTY_PRINT);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'User not found.'
            ], JSON_PRETTY_PRINT);
        }
    }

    // Create a new user
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->user->user_id = uniqid();
            $this->user->username = $_POST['username'];
            $this->user->email = $_POST['email'];
            $this->user->password = $_POST['password'];
            $this->user->aboutme = $_POST['aboutme'];
            $this->user->role = $_POST['role'];

            header('Content-Type: application/json'); // Set header Content-Type
            if ($this->user->create()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User created successfully.'
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User creation failed.'
                ], JSON_PRETTY_PRINT);
            }
        }
    }

    // Update an existing user
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
            parse_str(file_get_contents("php://input"), $_PUT);
            $this->user->user_id = $_PUT['user_id'];
            $this->user->username = $_PUT['username'];
            $this->user->email = $_PUT['email'];
            $this->user->password = $_PUT['password'];
            $this->user->aboutme = $_PUT['aboutme'];
            $this->user->role = $_PUT['role'];

            header('Content-Type: application/json'); // Set header Content-Type
            if ($this->user->update()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User updated successfully.'
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User update failed.'
                ], JSON_PRETTY_PRINT);
            }
        }
    }

    // Delete a user
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            parse_str(file_get_contents("php://input"), $_DELETE);
            $this->user->user_id = $_DELETE['user_id'];

            header('Content-Type: application/json'); // Set header Content-Type
            if ($this->user->delete()) {
                echo json_encode([
                    'status' => 'success',
                    'message' => 'User deleted successfully.'
                ], JSON_PRETTY_PRINT);
            } else {
                echo json_encode([
                    'status' => 'error',
                    'message' => 'User deletion failed.'
                ], JSON_PRETTY_PRINT);
            }
        }
    }
}
?>
