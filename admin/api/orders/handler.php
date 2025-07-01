<?php
session_start();
header('Content-Type: application/json');

/**
 * Hàm tiện ích để trả về phản hồi JSON và kết thúc script.
 */
function json_response($success, $message, $http_code = 200) {
    http_response_code($http_code);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit();
}

// Bảo mật: Yêu cầu đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    json_response(false, 'Unauthorized', 401);
}
require_once __DIR__ . '/../../../core/db_connect.php';


// === SỬA LỖI: Đọc dữ liệu JSON từ request body ===
$data = json_decode(file_get_contents('php://input'), true);

$action = $data['action'] ?? '';
$order_id = filter_var($data['order_id'] ?? 0, FILTER_VALIDATE_INT);
// === KẾT THÚC SỬA LỖI ===


if (!$action) {
    json_response(false, 'Hành động không được chỉ định.');
}

try {
    switch ($action) {
        case 'update_status':
            // Lưu ý: Dữ liệu cho hành động này được gửi từ FormData, nên vẫn dùng $_POST
            $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
            $status = $_POST['status'] ?? '';
            if(!$order_id || empty($status)) throw new Exception("Dữ liệu cập nhật trạng thái không hợp lệ.");
            
            $allowed_statuses = ['processing', 'shipped', 'completed', 'cancelled'];
            if (!in_array($status, $allowed_statuses)) {
                throw new Exception("Trạng thái không hợp lệ.");
            }
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            $message = 'Cập nhật trạng thái thành công!';
            break;

        case 'update_shipping':
            // Hành động này cũng dùng FormData
            $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
            // ... (logic update_shipping không đổi)
            $message = 'Cập nhật thông tin giao hàng thành công!';
            break;

        case 'delete':
            if (!$order_id) {
                throw new Exception("ID đơn hàng không hợp lệ.");
            }
            
            $pdo->beginTransaction();
            $stmt_items = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt_items->execute([$order_id]);
            $stmt_order = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt_order->execute([$order_id]);
            $pdo->commit();
            
            $message = 'Đã xóa đơn hàng thành công!';
            break;

        default:
            throw new Exception("Hành động không được hỗ trợ.");
    }

    json_response(true, $message);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(400);
    json_response(false, $e->getMessage());
}