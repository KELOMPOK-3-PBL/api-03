<?php
// routes.php
require_once '../config/database.php';
require_once '../controllers/UserController.php';

$database = new Database();
$db = $database->getConnection();

$userController = new UserController($db);;

// Route untuk menangani upload file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $userController->bulkUserUpload();
}
