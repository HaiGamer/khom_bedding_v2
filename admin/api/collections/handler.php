<?php
session_start();
header('Content-Type: application/json');

function json_response($success, $message, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

if (!isset($_SESSION['admin_id'])) { 
    json_response(false, 'Unauthorized', 401); 
}
require_once __DIR__ . '/../../../core/db_connect.php';

$action = $_POST['action'] ?? '';

try {
    // Vì có upload file, chúng ta dùng $_POST thay vì json_decode
    if ($action === 'add' || $action === 'edit') {
        $id = filter_input(INPUT_POST, 'collection_id', FILTER_VALIDATE_INT);
        $name = trim($_POST['name'] ?? '');
        $slug = trim($_POST['slug'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name) || empty($slug)) {
            throw new Exception("Tên và đường dẫn không được để trống.");
        }

        // Xử lý upload ảnh
        $image_url = $_POST['existing_image_url'] ?? null; 
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../../uploads/collections/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
            
            // Nếu là sửa và có ảnh mới, xóa ảnh cũ
            if($id) {
                $stmt_old_img = $pdo->prepare("SELECT image_url FROM collections WHERE id = ?");
                $stmt_old_img->execute([$id]);
                $old_img_url = $stmt_old_img->fetchColumn();
                if ($old_img_url && file_exists(__DIR__ . '/../../../' . $old_img_url)) {
                    unlink(__DIR__ . '/../../../' . $old_img_url);
                }
            }

            $new_filename = uniqid('coll_', true) . '.' . pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            if(move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $new_filename)) {
                $image_url = '/uploads/collections/' . $new_filename;
            } else {
                throw new Exception("Không thể lưu file ảnh.");
            }
        }

        if ($id) { // Cập nhật
            $stmt = $pdo->prepare("UPDATE collections SET name=?, slug=?, description=?, image_url=? WHERE id=?");
            $stmt->execute([$name, $slug, $description, $image_url, $id]);
        } else { // Thêm mới
            $stmt = $pdo->prepare("INSERT INTO collections (name, slug, description, image_url) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $description, $image_url]);
        }
    } 
    elseif ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'collection_id', FILTER_VALIDATE_INT);
        if (!$id) throw new Exception("ID không hợp lệ.");

        // Xóa file ảnh trước khi xóa record CSDL
        $stmt_old_img = $pdo->prepare("SELECT image_url FROM collections WHERE id = ?");
        $stmt_old_img->execute([$id]);
        $old_img_url = $stmt_old_img->fetchColumn();
        if ($old_img_url && file_exists(__DIR__ . '/../../../' . $old_img_url)) {
            unlink(__DIR__ . '/../../../' . $old_img_url);
        }

        $stmt = $pdo->prepare("DELETE FROM collections WHERE id=?");
        $stmt->execute([$id]);
    } 
    else {
        throw new Exception("Hành động không hợp lệ.");
    }
    
    json_response(true, "Thao tác thành công!");

} catch (Exception $e) {
    json_response(false, $e->getMessage(), 400);
}