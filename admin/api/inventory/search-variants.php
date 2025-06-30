<?php
session_start();
header('Content-Type: application/json');

// Bảo mật
if (!isset($_SESSION['admin_id'])) { 
    http_response_code(401);
    exit(json_encode([])); 
}
// SỬA LẠI ĐƯỜNG DẪN: Đi ngược ra 3 cấp thư mục để tìm đến file core
require_once __DIR__ . '/../../../core/db_connect.php';

// Lấy từ khóa tìm kiếm
$term = $_GET['term'] ?? '';
if (strlen($term) < 2) {
    exit(json_encode([]));
}

try {
    // Tìm kiếm trong tên sản phẩm hoặc SKU của phiên bản
    $stmt = $pdo->prepare("
        SELECT 
            pv.id, 
            pv.sku, 
            pv.stock_quantity, 
            pv.price,
            p.name as product_name,
            GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') as variant_attributes
        FROM product_variants pv
        JOIN products p ON pv.product_id = p.id
        LEFT JOIN variant_values vv ON pv.id = vv.variant_id
        LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id
        LEFT JOIN attributes a ON av.attribute_id = a.id
        WHERE p.name LIKE ? OR pv.sku LIKE ?
        GROUP BY pv.id
        LIMIT 10
    ");
    $stmt->execute(["%{$term}%", "%{$term}%"]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($results);

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Lỗi tìm kiếm sản phẩm: " . $e->getMessage());
    echo json_encode([]); // Trả về mảng rỗng nếu có lỗi
}