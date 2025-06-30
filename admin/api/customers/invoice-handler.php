<?php
session_start();
header('Content-Type: application/json');

function json_response($success, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    $response = ['success' => $success, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit();
}

if (!isset($_SESSION['admin_id'])) {
    json_response(false, 'Unauthorized', [], 401);
}
require_once __DIR__ . '/../../../core/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Yêu cầu không hợp lệ.', [], 405);
}

$action = $_POST['action'] ?? '';
$customer_id = filter_input(INPUT_POST, 'customer_id', FILTER_VALIDATE_INT) ?: null;
$customer_name = trim($_POST['customer_name'] ?? '');
$customer_phone = trim($_POST['customer_phone'] ?? '');
$customer_address = trim($_POST['customer_address'] ?? '');
$status = $_POST['status'] ?? 'unpaid';
$note = trim($_POST['note'] ?? '');
$items = $_POST['items'] ?? [];

if (empty($customer_name)) {
    json_response(false, 'Tên khách hàng không được để trống.');
}
if (empty($items) && $action !== 'delete') {
    json_response(false, 'Hóa đơn phải có ít nhất một sản phẩm.');
}

try {
    $pdo->beginTransaction();
    $invoice_id = null;
    $message = '';

    switch ($action) {
        case 'add':
            $total_amount = 0;
            foreach ($items as $item) {
                $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
                $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
                if ($quantity > 0 && $price >= 0) {
                    $total_amount += $quantity * $price;
                }
            }
            $last_id = $pdo->query("SELECT MAX(id) FROM invoices")->fetchColumn();
            $invoice_code = 'HD-' . str_pad($last_id + 1, 6, '0', STR_PAD_LEFT);

            $stmt_invoice = $pdo->prepare(
                "INSERT INTO invoices (invoice_code, customer_id, customer_name, customer_phone, customer_address, total_amount, status, note, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt_invoice->execute([
                $invoice_code, $customer_id, $customer_name, $customer_phone, $customer_address,
                $total_amount, $status, $note, $_SESSION['admin_id']
            ]);
            $invoice_id = $pdo->lastInsertId();

            $stmt_item = $pdo->prepare(
                "INSERT INTO invoice_items (invoice_id, product_name, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($items as $variant_id => $item) {
                $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
                $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
                if ($quantity > 0 && $price >= 0) {
                    $line_total = $quantity * $price;
                    $stmt_item->execute([$invoice_id, $item['name'], $quantity, $price, $line_total]);
                }
            }
            $message = 'Tạo hóa đơn thành công!';
            break;
        
        case 'edit':
            $invoice_id = filter_input(INPUT_POST, 'invoice_id', FILTER_VALIDATE_INT);
            if (!$invoice_id) { throw new Exception("ID hóa đơn không hợp lệ."); }

            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += (filter_var($item['quantity'], FILTER_VALIDATE_INT) * filter_var($item['price'], FILTER_VALIDATE_FLOAT));
            }
            
            $stmt_invoice = $pdo->prepare("UPDATE invoices SET customer_id=?, customer_name=?, customer_phone=?, customer_address=?, total_amount=?, status=?, note=? WHERE id=?");
            $stmt_invoice->execute([$customer_id, $customer_name, $customer_phone, $customer_address, $total_amount, $status, $note, $invoice_id]);

            $stmt_delete = $pdo->prepare("DELETE FROM invoice_items WHERE invoice_id = ?");
            $stmt_delete->execute([$invoice_id]);
            
            $stmt_item = $pdo->prepare("INSERT INTO invoice_items (invoice_id, product_name, quantity, unit_price, total) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $variant_id => $item) {
                 $quantity = filter_var($item['quantity'], FILTER_VALIDATE_INT);
                 $price = filter_var($item['price'], FILTER_VALIDATE_FLOAT);
                 if ($quantity > 0 && $price >= 0) {
                    $line_total = $quantity * $price;
                    $stmt_item->execute([$invoice_id, $item['name'], $quantity, $price, $line_total]);
                 }
            }
            $message = 'Cập nhật hóa đơn thành công!';
            break;

        default:
            throw new Exception("Hành động không được hỗ trợ.");
    }
    
    $pdo->commit();
    json_response(true, $message, ['invoice_id' => $invoice_id]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Lỗi xử lý hóa đơn: " . $e->getMessage());
    json_response(false, 'Đã có lỗi xảy ra phía máy chủ. Vui lòng thử lại.', 500);
}
?>