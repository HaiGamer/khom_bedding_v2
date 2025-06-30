<?php
session_start();
// Bảo mật: Yêu cầu đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit();
}
require_once __DIR__ . '/../../../core/db_connect.php';

// Kiểm tra phương thức request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit('Invalid request method.');
}

// Lấy dữ liệu từ form
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$title = trim($_POST['title'] ?? '');
$slug = trim($_POST['slug'] ?? '');
$content = $_POST['content'] ?? '';
$excerpt = trim($_POST['excerpt'] ?? '');
$status = $_POST['status'] ?? 'draft';
$user_id = $_SESSION['admin_id']; // Lấy ID của admin đang đăng nhập

// --- Kiểm tra dữ liệu đầu vào ---
if (empty($title) || empty($slug)) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Tiêu đề và đường dẫn không được để trống.'];
    header('Location: /admin/post-edit.php' . ($post_id ? '?id='.$post_id : ''));
    exit();
}


try {
    // --- Xử lý upload ảnh đại diện ---
    $image_url_to_save = null;
    if ($post_id) { // Nếu là sửa, lấy ảnh cũ phòng trường hợp không có ảnh mới
        $stmt_img = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
        $stmt_img->execute([$post_id]);
        $image_url_to_save = $stmt_img->fetchColumn();
    }
    
    if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../../uploads/posts/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        
        // Nếu là sửa và có ảnh cũ, xóa file ảnh cũ đi
        if ($post_id && $image_url_to_save && file_exists(__DIR__ . '/../../../' . $image_url_to_save)) {
            unlink(__DIR__ . '/../../../' . $image_url_to_save);
        }

        // Tạo tên file mới và di chuyển file
        $file_extension = pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION);
        $new_filename = uniqid('post_', true) . '.' . $file_extension;
        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $upload_dir . $new_filename)) {
            $image_url_to_save = '/uploads/posts/' . $new_filename;
        } else {
            throw new Exception("Không thể lưu file ảnh.");
        }
    }
    
    // --- Lưu vào cơ sở dữ liệu ---
    if ($post_id) {
        // Cập nhật bài viết đã có
        $sql = "UPDATE posts SET title=?, slug=?, content=?, excerpt=?, featured_image=?, status=?, updated_at=NOW() WHERE id=?";
        $params = [$title, $slug, $content, $excerpt, $image_url_to_save, $status, $post_id];
    } else {
        // Thêm bài viết mới
        $sql = "INSERT INTO posts (user_id, title, slug, content, excerpt, featured_image, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $params = [$user_id, $title, $slug, $content, $excerpt, $image_url_to_save, $status];
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // Nếu là thêm mới, lấy ID của bài viết vừa tạo để chuyển hướng
    if (!$post_id) {
        $post_id = $pdo->lastInsertId();
    }
    
    $_SESSION['flash_message'] = ['type' => 'success', 'message' => 'Lưu bài viết thành công!'];

} catch (Exception $e) {
    $_SESSION['flash_message'] = ['type' => 'danger', 'message' => 'Lỗi: ' . $e->getMessage()];
}

// Chuyển hướng về trang sửa để người dùng thấy kết quả
header('Location: /admin/post-edit.php?id=' . $post_id);
exit();