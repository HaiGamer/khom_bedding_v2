<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../../core/db_connect.php';

function json_response($success, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    $response = ['success' => $success, 'message' => $message];
    echo json_encode(array_merge($response, $data));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.', 405);
}
if (!isset($_SESSION['user_id'])) {
    json_response(false, 'Bạn cần đăng nhập để đặt hàng.', 401);
}
if (empty($_SESSION['cart'])) {
    json_response(false, 'Giỏ hàng của bạn đang trống.', 400);
}

$user_id = $_SESSION['user_id'];

try {
    $stmt_check_orders = $pdo->prepare("SELECT COUNT(id) FROM orders WHERE user_id = ? AND status IN ('pending', 'processing')");
    $stmt_check_orders->execute([$user_id]);
    $processing_orders_count = $stmt_check_orders->fetchColumn();

    if ($processing_orders_count >= 5) {
        json_response(false, 'Bạn đã có 5 đơn hàng đang chờ xử lý. Vui lòng chờ các đơn hàng trước được hoàn thành để tiếp tục đặt hàng.');
        exit;
    }
} catch (PDOException $e) {
    error_log($e->getMessage());
    json_response(false, 'Lỗi khi kiểm tra đơn hàng của bạn.', 500);
}

$customer_name = trim($_POST['customer_name'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_address = trim($_POST['customer_address'] ?? '');
$note = trim($_POST['note'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? 'COD');
$cart = $_SESSION['cart'];

try {
    $pdo->beginTransaction();

    // === PHẦN SỬA LỖI LOGIC KIỂM KHO VÀ TÍNH TOÁN ===
    $variant_ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));
    
    // Lấy đầy đủ thông tin, không chỉ key-pair
    $stmt_variants = $pdo->prepare("SELECT id, price, stock_quantity FROM product_variants WHERE id IN ($placeholders) FOR UPDATE");
    $stmt_variants->execute($variant_ids);
    $variants_from_db_raw = $stmt_variants->fetchAll(PDO::FETCH_ASSOC);

    // Chuyển thành mảng kết hợp để dễ tra cứu
    $variants_in_db = [];
    foreach($variants_from_db_raw as $v) {
        $variants_in_db[$v['id']] = $v;
    }

    $order_total = 0;
    foreach ($cart as $variant_id => $item) {
        if (!isset($variants_in_db[$variant_id])) {
            throw new Exception("Sản phẩm với ID {$variant_id} không tồn tại hoặc đã bị xóa.");
        }
        // Sửa lại logic kiểm tra tồn kho
        if ($item['quantity'] > $variants_in_db[$variant_id]['stock_quantity']) {
            throw new Exception("Số lượng tồn kho của một sản phẩm không đủ.");
        }
        // Tính tổng tiền dựa trên giá từ CSDL cho chính xác
        $order_total += $item['quantity'] * $variants_in_db[$variant_id]['price'];
    }
    
    // === KẾT THÚC PHẦN SỬA LỖI ===

    $stmt_order = $pdo->prepare("INSERT INTO orders (user_id, customer_name, customer_email, customer_phone, customer_address, order_total, payment_method, note, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'processing')");
    $stmt_order->execute([
        $user_id, $customer_name, $_SESSION['user_email'], $customer_phone, $customer_address,
        $order_total, $payment_method, $note
    ]);
    $order_id = $pdo->lastInsertId();

    $stmt_items = $pdo->prepare("INSERT INTO order_items (order_id, variant_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt_stock = $pdo->prepare("UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ?");

    foreach ($cart as $variant_id => $item) {
        // Sử dụng giá từ CSDL đã lấy ở trên
        $stmt_items->execute([$order_id, $variant_id, $item['quantity'], $variants_in_db[$variant_id]['price']]);
        $stmt_stock->execute([$item['quantity'], $variant_id]);
    }

    $pdo->commit();
    unset($_SESSION['cart']);

    json_response(true, 'Đặt hàng thành công!', ['order_id' => $order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Lỗi đặt hàng: " . $e->getMessage());
    json_response(false, "Lỗi: " . $e->getMessage(), 500);
}