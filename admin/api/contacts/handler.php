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

// Lấy dữ liệu được gửi lên dưới dạng JSON
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$contact_id = filter_var($data['contact_id'] ?? 0, FILTER_VALIDATE_INT);

if (!$action || !$contact_id) {
    json_response(false, 'Dữ liệu không hợp lệ.');
}

try {
    switch ($action) {
        case 'mark_read':
            $stmt = $pdo->prepare("UPDATE contacts SET status = 'read' WHERE id = ?");
            $message = 'Đã đánh dấu là đã đọc.';
            break;
        case 'mark_new':
            $stmt = $pdo->prepare("UPDATE contacts SET status = 'new' WHERE id = ?");
            $message = 'Đã đánh dấu là mới.';
            break;
        case 'delete':
            // Trong tương lai nếu có ảnh đính kèm, cần thêm logic xóa file ở đây
            $stmt = $pdo->prepare("DELETE FROM contacts WHERE id = ?");
            $message = 'Đã xóa vĩnh viễn tin nhắn.';
            break;
        default:
            throw new Exception("Hành động không hợp lệ.");
    }
    
    $stmt->execute([$contact_id]);
    json_response(true, $message);

} catch (Exception $e) {
    // Khối xử lý lỗi đã được hoàn thiện
    http_response_code(400); // Bad Request
    json_response(false, $e->getMessage());
}