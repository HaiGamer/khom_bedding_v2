<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../core/db_connect.php';

$slug = $_GET['slug'] ?? '';
if (empty($slug)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Slug sản phẩm không hợp lệ.']);
    exit;
}

// Lấy thông tin cơ bản của sản phẩm
$stmt_product = $pdo->prepare("SELECT id, name, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_featured = 1 LIMIT 1) as image_url FROM products p WHERE slug = ?");
$stmt_product->execute([$slug]);
$product_info = $stmt_product->fetch();

if (!$product_info) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Không tìm thấy sản phẩm.']);
    exit;
}

// Lấy thông tin các phiên bản (giống hệt logic trong product-detail.php)
$stmt_variants = $pdo->prepare("SELECT pv.id, pv.sku, pv.price, pv.original_price, pv.stock_quantity, pv.image_url, pv.is_default, a.name as attribute_name, av.value as attribute_value FROM product_variants pv LEFT JOIN variant_values vv ON pv.id = vv.variant_id LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id LEFT JOIN attributes a ON av.attribute_id = a.id WHERE pv.product_id = ? ORDER BY pv.id");
$stmt_variants->execute([$product_info['id']]);
$raw_variants_data = $stmt_variants->fetchAll();

$variants_by_id = [];
foreach ($raw_variants_data as $row) {
    $variant_id = $row['id'];
    if (!isset($variants_by_id[$variant_id])) {
        $variants_by_id[$variant_id] = ['id' => $variant_id, 'sku' => $row['sku'], 'price' => $row['price'], 'original_price' => $row['original_price'], 'stock_quantity' => $row['stock_quantity'], 'image_url' => $row['image_url'], 'is_default' => (bool)$row['is_default'], 'attributes' => []];
    }
    if ($row['attribute_name'] && $row['attribute_value']) {
        $variants_by_id[$variant_id]['attributes'][$row['attribute_name']] = $row['attribute_value'];
    }
}
$structured_variants = array_values($variants_by_id);

echo json_encode([
    'success' => true,
    'product' => $product_info,
    'variants' => $structured_variants
]);