<?php
session_start();
header('Content-Type: application/json');
if (!isset($_SESSION['admin_id'])) { exit(json_encode(['success' => false, 'message' => 'Unauthorized'])); }
require_once __DIR__ . '/../../../core/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $name = trim($data['name']);
            $slug = trim($data['slug']);
            $description = trim($data['description']);
            if(empty($name) || empty($slug)) throw new Exception("Tên và đường dẫn không được để trống.");

            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description) VALUES (?, ?, ?)");
            $stmt->execute([$name, $slug, $description]);
            echo json_encode(['success' => true, 'message' => 'Thêm danh mục thành công!']);
            break;

        case 'edit':
            $id = filter_var($data['category_id'], FILTER_VALIDATE_INT);
            $name = trim($data['name']);
            $slug = trim($data['slug']);
            $description = trim($data['description']);
            if(!$id || empty($name) || empty($slug)) throw new Exception("Dữ liệu không hợp lệ.");

            $stmt = $pdo->prepare("UPDATE categories SET name=?, slug=?, description=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $id]);
            echo json_encode(['success' => true, 'message' => 'Cập nhật danh mục thành công!']);
            break;

        case 'delete':
            $id = filter_var($data['category_id'], FILTER_VALIDATE_INT);
            if(!$id) throw new Exception("ID không hợp lệ.");
            
            // Theo thiết kế CSDL, sản phẩm sẽ được gán category_id = NULL
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id=?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Xóa danh mục thành công!']);
            break;

        default:
            throw new Exception("Hành động không hợp lệ.");
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}