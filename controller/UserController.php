<?php
include_once '../config/database.php';
include_once '../models/User.php';

class UserController {
    private $db;
    private $user;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->user = new User($this->db);
    }

    // List all users
    public function index() {
        $stmt = $this->user->read();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    }

    // Get a specific user by ID
    public function show($id) {
        $stmt = $this->user->find($id);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            echo json_encode($user);
        } else {
            echo json_encode(['message' => 'User not found.']);
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

            if ($this->user->create()) {
                echo json_encode(['message' => 'User created successfully.']);
            } else {
                echo json_encode(['message' => 'User creation failed.']);
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

            if ($this->user->update()) {
                echo json_encode(['message' => 'User updated successfully.']);
            } else {
                echo json_encode(['message' => 'User update failed.']);
            }
        }
    }

    // Delete a user
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
            parse_str(file_get_contents("php://input"), $_DELETE);
            $this->user->user_id = $_DELETE['user_id'];

            if ($this->user->delete()) {
                echo json_encode(['message' => 'User deleted successfully.']);
            } else {
                echo json_encode(['message' => 'User deletion failed.']);
            }
        }
    }
}
?>
