<?php
session_start();
header('Content-Type: application/json');

/**
 * Hàm tiện ích để trả về phản hồi JSON và kết thúc script.
 */
function json_response($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Bảo mật: Yêu cầu đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    json_response(false, 'Unauthorized');
}
require_once __DIR__ . '/../../../core/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$post_id = filter_var($data['post_id'] ?? 0, FILTER_VALIDATE_INT);

if ($action !== 'delete' || !$post_id) {
    json_response(false, 'Dữ liệu không hợp lệ.');
}

try {
    $pdo->beginTransaction();

    // 1. Lấy đường dẫn ảnh đại diện để xóa file
    $stmt_img = $pdo->prepare("SELECT featured_image FROM posts WHERE id = ?");
    $stmt_img->execute([$post_id]);
    $image_url = $stmt_img->fetchColumn();

    // 2. Xóa bài viết khỏi cơ sở dữ liệu
    $stmt_delete = $pdo->prepare("DELETE FROM posts WHERE id = ?");
    $stmt_delete->execute([$post_id]);

    // 3. Nếu xóa thành công và có ảnh, xóa file ảnh vật lý
    if ($stmt_delete->rowCount() > 0 && $image_url) {
        $file_path = __DIR__ . '/../../../' . $image_url;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $pdo->commit();
    json_response(true, 'Đã xóa bài viết thành công!');

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Lỗi xóa bài viết: " . $e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra phía máy chủ.');
}