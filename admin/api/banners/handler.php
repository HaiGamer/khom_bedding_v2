<?php
session_start();
header('Content-Type: application/json');

/**
 * Hàm tiện ích để trả về phản hồi JSON và kết thúc script.
 */
function json_response($success, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    $response = ['success' => $success, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit();
}

// Bảo mật và kiểm tra đầu vào
if (!isset($_SESSION['admin_id'])) {
    json_response(false, 'Unauthorized', [], 401);
}
require_once __DIR__ . '/../../../core/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.', [], 405);
}

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'add':
        case 'edit':
            $banner_id = filter_input(INPUT_POST, 'banner_id', FILTER_VALIDATE_INT);
            $title = trim($_POST['title'] ?? '');
            $link_url = trim($_POST['link_url'] ?? '');
            $display_order = filter_input(INPUT_POST, 'display_order', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (empty($title)) {
                throw new Exception("Tiêu đề không được để trống.");
            }
            
            $image_desktop_url = null;
            $image_mobile_url = null;
            $upload_dir = __DIR__ . '/../../../uploads/banners/';
            if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

            // Nếu là sửa, lấy lại đường dẫn ảnh cũ
            if ($banner_id) {
                $stmt_old_images = $pdo->prepare("SELECT image_url_desktop, image_url_mobile FROM banners WHERE id = ?");
                $stmt_old_images->execute([$banner_id]);
                $old_images = $stmt_old_images->fetch(PDO::FETCH_ASSOC);
                $image_desktop_url = $old_images['image_url_desktop'];
                $image_mobile_url = $old_images['image_url_mobile'];
            }
            
            // Xử lý upload ảnh desktop
            if (isset($_FILES['image_url_desktop']) && $_FILES['image_url_desktop']['error'] === UPLOAD_ERR_OK) {
                if ($image_desktop_url && file_exists(__DIR__ . '/../../../' . $image_desktop_url)) unlink(__DIR__ . '/../../../' . $image_desktop_url);
                $new_filename = uniqid('banner_d_', true) . '.' . pathinfo($_FILES['image_url_desktop']['name'], PATHINFO_EXTENSION);
                if(move_uploaded_file($_FILES['image_url_desktop']['tmp_name'], $upload_dir . $new_filename)) {
                    $image_desktop_url = '/uploads/banners/' . $new_filename;
                }
            }

            // Xử lý upload ảnh mobile
            if (isset($_FILES['image_url_mobile']) && $_FILES['image_url_mobile']['error'] === UPLOAD_ERR_OK) {
                 if ($image_mobile_url && file_exists(__DIR__ . '/../../../' . $image_mobile_url)) unlink(__DIR__ . '/../../../' . $image_mobile_url);
                $new_filename = uniqid('banner_m_', true) . '.' . pathinfo($_FILES['image_url_mobile']['name'], PATHINFO_EXTENSION);
                if(move_uploaded_file($_FILES['image_url_mobile']['tmp_name'], $upload_dir . $new_filename)) {
                    $image_mobile_url = '/uploads/banners/' . $new_filename;
                }
            }

            if ($banner_id) { // Cập nhật
                $stmt = $pdo->prepare("UPDATE banners SET title=?, link_url=?, image_url_desktop=?, image_url_mobile=?, display_order=?, is_active=? WHERE id=?");
                $stmt->execute([$title, $link_url, $image_desktop_url, $image_mobile_url, $display_order, $is_active, $banner_id]);
            } else { // Thêm mới
                $stmt = $pdo->prepare("INSERT INTO banners (title, link_url, image_url_desktop, image_url_mobile, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $link_url, $image_desktop_url, $image_mobile_url, $display_order, $is_active]);
            }
            break;

        case 'delete':
            $banner_id = filter_input(INPUT_POST, 'banner_id', FILTER_VALIDATE_INT);
            if (!$banner_id) throw new Exception("ID banner không hợp lệ.");
            
            $stmt_images = $pdo->prepare("SELECT image_url_desktop, image_url_mobile FROM banners WHERE id = ?");
            $stmt_images->execute([$banner_id]);
            $images = $stmt_images->fetch(PDO::FETCH_ASSOC);

            if ($images) {
                if ($images['image_url_desktop'] && file_exists(__DIR__ . '/../../../' . $images['image_url_desktop'])) unlink(__DIR__ . '/../../../' . $images['image_url_desktop']);
                if ($images['image_url_mobile'] && file_exists(__DIR__ . '/../../../' . $images['image_url_mobile'])) unlink(__DIR__ . '/../../../' . $images['image_url_mobile']);
            }
            
            $stmt = $pdo->prepare("DELETE FROM banners WHERE id=?");
            $stmt->execute([$banner_id]);
            break;

        default:
            throw new Exception("Hành động không hợp lệ.");
    }
    json_response(true, "Thao tác thành công!");

} catch (Exception $e) {
    json_response(false, 'Lỗi: ' . $e->getMessage(), 400);
}