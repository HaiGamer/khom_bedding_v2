<?php
session_start();
header('Content-Type: application/json');

// --- HÀM TIỆN ÍCH ---
function json_response($success, $message, $data = [], $http_code = 200) {
    http_response_code($http_code);
    $response = ['success' => $success, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit();
}

// --- BẢO MẬT VÀ KIỂM TRA ĐẦU VÀO ---
if (!isset($_SESSION['admin_id'])) {
    json_response(false, 'Unauthorized', [], 401);
}
require_once __DIR__ . '/../../../core/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$note = trim($data['note'] ?? '');
$items = $data['items'] ?? [];

if (empty($items)) {
    json_response(false, 'Vui lòng thêm ít nhất một sản phẩm vào phiếu xuất.');
}

// --- XỬ LÝ LOGIC ---
try {
    $pdo->beginTransaction();

    // Bước 1: Kiểm tra lại toàn bộ tồn kho trước khi thực hiện bất kỳ thay đổi nào
    foreach ($items as $item) {
        $quantity_to_export = filter_var($item['quantity'], FILTER_VALIDATE_INT);
        $variant_id = filter_var($item['variant_id'], FILTER_VALIDATE_INT);

        if ($quantity_to_export === false || $quantity_to_export <= 0 || $variant_id === false) {
            throw new Exception("Dữ liệu số lượng hoặc ID sản phẩm không hợp lệ.");
        }

        $stmt_check_stock = $pdo->prepare("SELECT sku, stock_quantity FROM product_variants WHERE id = ?");
        $stmt_check_stock->execute([$variant_id]);
        $variant = $stmt_check_stock->fetch();

        if (!$variant) {
            throw new Exception("Không tìm thấy sản phẩm với ID {$variant_id}.");
        }
        if ($variant['stock_quantity'] < $quantity_to_export) {
            throw new Exception("Số lượng tồn kho của sản phẩm SKU '{$variant['sku']}' không đủ. Chỉ còn {$variant['stock_quantity']} sản phẩm.");
        }
    }

    // Bước 2: Nếu tất cả đều hợp lệ, bắt đầu tạo phiếu và cập nhật CSDL
    $export_code = 'PXK-' . time();
    $stmt_export = $pdo->prepare("INSERT INTO stock_exports (export_code, user_id, note) VALUES (?, ?, ?)");
    $stmt_export->execute([$export_code, $_SESSION['admin_id'], $note]);
    $export_id = $pdo->lastInsertId();

    // Bước 3: Thêm các sản phẩm vào chi tiết phiếu và trừ tồn kho
    $stmt_item = $pdo->prepare("INSERT INTO stock_export_items (export_id, variant_id, quantity, price_at_export) VALUES (?, ?, ?, (SELECT cost_price FROM product_variants WHERE id=?))");
    $stmt_stock = $pdo->prepare("UPDATE product_variants SET stock_quantity = stock_quantity - ? WHERE id = ?");

    foreach ($items as $item) {
        $variant_id = $item['variant_id'];
        $quantity = $item['quantity'];
        
        $stmt_item->execute([$export_id, $variant_id, $quantity, $variant_id]);
        $stmt_stock->execute([$quantity, $variant_id]);
    }

    $pdo->commit();
    json_response(true, 'Tạo phiếu xuất kho thành công!', ['export_id' => $export_id]);

} catch (Exception $e) {
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log($e->getMessage()); // Ghi log lỗi để admin xem lại
    json_response(false, 'Lỗi: ' . $e->getMessage(), 400);
}