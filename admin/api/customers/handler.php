<?php
session_start();
header('Content-Type: application/json');

/**
 * Hàm tiện ích để trả về phản hồi JSON và kết thúc script.
 * @param bool $success Trạng thái thành công hay thất bại.
 * @param string $message Thông điệp trả về.
 * @param int $http_code Mã trạng thái HTTP.
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

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'add':
            $name = trim($data['customer_name'] ?? '');
            if (empty($name)) {
                throw new Exception("Tên khách hàng không được để trống.");
            }
            $stmt = $pdo->prepare("INSERT INTO customers (customer_name, phone_number, address, email, customer_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$name, $data['phone_number'], $data['address'], $data['email'], $data['customer_type']]);
            break;

        case 'edit':
            $id = filter_var($data['customer_id'], FILTER_VALIDATE_INT);
            $name = trim($data['customer_name'] ?? '');
            if (!$id || empty($name)) {
                throw new Exception("Dữ liệu không hợp lệ.");
            }
            $stmt = $pdo->prepare("UPDATE customers SET customer_name=?, phone_number=?, address=?, email=?, customer_type=? WHERE id=?");
            $stmt->execute([$name, $data['phone_number'], $data['address'], $data['email'], $data['customer_type'], $id]);
            break;

        case 'delete':
            $id = filter_var($data['customer_id'], FILTER_VALIDATE_INT);
            if (!$id) {
                throw new Exception("ID không hợp lệ.");
            }
            // Theo thiết kế CSDL, các hóa đơn liên quan sẽ được gán customer_id = NULL nhờ ON DELETE SET NULL
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id=?");
            $stmt->execute([$id]);
            break;

        default:
            throw new Exception("Hành động không hợp lệ.");
    }
    json_response(true, "Thao tác thành công!");

} catch (Exception $e) {
    // Xử lý các lỗi có thể xảy ra và trả về thông báo lỗi thân thiện
    http_response_code(400);
    json_response(false, 'Lỗi: ' . $e->getMessage());
}