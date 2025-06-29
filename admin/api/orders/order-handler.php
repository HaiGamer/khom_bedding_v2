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

        default:
            throw new Exception("Hành động không được hỗ trợ.");
    }
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}