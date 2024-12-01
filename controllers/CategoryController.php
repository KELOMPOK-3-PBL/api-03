<?php
require_once "../helpers/ResponseHelpers.php";
require_once '../vendor/autoload.php'; // Include Composer's autoloader
require_once '../config/JwtConfig.php'; // Include your JWT configuration

class CategoryController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Mendapatkan semua kategori
    public function getAllCategories() {
        $query = "SELECT * FROM category";
        $stmt = $this->db->query($query);

        $data = [];
        while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
            $data[] = $row;
        }

        response('success', 'List of Categories Retrieved Successfully', $data);
    }

    // Mendapatkan kategori berdasarkan ID
    public function getCategoryById($id) {
        $query = "SELECT * FROM category WHERE category_id = ? LIMIT 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            $data = $stmt->fetch(PDO::FETCH_OBJ);
            response('success', 'Category Retrieved Successfully', $data);
        } else {
            response('error', 'Category Not Found', null, 404);
        }
    }

    // Menambahkan kategori baru
    public function createCategory() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (is_null($input) || !isset($input['category_name'])) {
            response('error', 'Invalid JSON or Missing category name', null, 400);
            return;
        }

        $query = "INSERT INTO category (category_name) VALUES (?)";
        $stmt = $this->db->prepare($query);

        if ($stmt->execute([$input['category_name']])) {
            $id = $this->db->lastInsertId();
            $this->getCategoryById($id); // Menampilkan data yang baru ditambahkan
        } else {
            response('error', 'Failed to Create Category', null, 500);
        }
    }

    // Memperbarui kategori
    public function updateCategory($id) {
        $input = json_decode(file_get_contents('php://input'), true);

        if (is_null($input) || !isset($input['category_name'])) {
            response('error', 'Invalid JSON or Missing Category Name', null, 400);
            return;
        }

        $query = "UPDATE category SET category_name = ? WHERE category_id = ?";
        $stmt = $this->db->prepare($query);

        if ($stmt->execute([$input['category_name'], $id])) {
            $this->getCategoryById($id); // Menampilkan data yang diperbarui
        } else {
            response('error', 'Failed to Update Category', null, 500);
        }
    }

    // Menghapus kategori
    public function deleteCategory($id) {
        $stmt = $this->db->prepare("DELETE FROM category WHERE category_id = ?");

        if ($stmt->execute([$id])) {
            response('success', 'Category Deleted Successfully');
        } else {
            response('error', 'Failed to Delete Category', null, 500);
        }
    }
}
?>