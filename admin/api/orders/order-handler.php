<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) { exit(json_encode(['success' => false, 'message' => 'Unauthorized'])); }
require_once __DIR__ . '/../../../core/db_connect.php';

$action = $_POST['action'] ?? '';
$order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

if (!$action || !$order_id) { exit(json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ.'])); }

try {
    switch ($action) {
        case 'update_status':
            $status = $_POST['status'] ?? '';
            $allowed_statuses = ['processing', 'shipped', 'completed', 'cancelled'];
            if (!in_array($status, $allowed_statuses)) {
                throw new Exception("Trạng thái không hợp lệ.");
            }
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->execute([$status, $order_id]);
            break;

        case 'update_shipping':
            $name = trim($_POST['customer_name'] ?? '');
            $phone = trim($_POST['customer_phone'] ?? '');
            $address = trim($_POST['customer_address'] ?? '');
            if (empty($name) || empty($phone) || empty($address)) {
                throw new Exception("Vui lòng điền đầy đủ thông tin giao hàng.");
            }
            $stmt = $pdo->prepare("UPDATE orders SET customer_name = ?, customer_phone = ?, customer_address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $order_id]);
            break;
            // === THÊM VÀO: LOGIC XÓA ĐƠN HÀNG ===
        case 'delete':
            $order_id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
            if (!$order_id) {
                throw new Exception("ID đơn hàng không hợp lệ.");
            }

            // Lưu ý: Việc xóa đơn hàng có thể cần các logic phức tạp hơn như
            // hoàn lại số lượng tồn kho. Ở đây chúng ta chỉ thực hiện xóa đơn giản.
            $pdo->beginTransaction();

            // Xóa các chi tiết đơn hàng trước
            $stmt_items = $pdo->prepare("DELETE FROM order_items WHERE order_id = ?");
            $stmt_items->execute([$order_id]);

            // Xóa đơn hàng chính
            $stmt_order = $pdo->prepare("DELETE FROM orders WHERE id = ?");
            $stmt_order->execute([$order_id]);

            $pdo->commit();
            json_response(true, 'Đã xóa đơn hàng thành công!');
            break;
        // === KẾT THÚC PHẦN THÊM MỚI ===

        default:
            throw new Exception("Hành động không được hỗ trợ.");
    }
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}