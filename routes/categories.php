<?php

require_once "../controllers/CategoryController.php";
require_once "../config/Database.php";

// Membuat koneksi database
$db = new Database();
$conn = $db->getConnection();

// Inisialisasi CategoryController
$categoryController = new CategoryController($conn);

// Memastikan metode HTTP yang digunakan
$method = $_SERVER['REQUEST_METHOD'];
$id = $_GET['id'] ?? null;

switch ($method) {
    case 'GET':
        if ($id) {
            $categoryController->getCategoryById($id);
        } else {
            $categoryController->getAllCategories();
        }
        break;

    case 'POST':
        $categoryController->createCategory();
        break;

    case 'PUT':
        if ($id) {
            $categoryController->updateCategory($id);
        } else {
            response(false, 'ID is required for update', null, [
                'code' => 400,
                'message' => 'Bad request: ID is required'
            ]);
        }
        break;

    case 'DELETE':
        if ($id) {
            $categoryController->deleteCategory($id);
        } else {
            response(false, 'ID is required for delete', null, [
                'code' => 400,
                'message' => 'Bad request: ID is required'
            ]);
        }
        break;

    default:
        response(false, 'Method Not Allowed', null, [
            'code' => 405,
            'message' => 'Only GET, POST, PUT, DELETE methods are allowed'
        ]);
        break;
}
?>