<?php
session_start();
header('Content-Type: text/html'); // Trả về HTML để dễ dàng chèn vào modal
if (!isset($_SESSION['admin_id'])) { http_response_code(401); exit('Unauthorized'); }
require_once __DIR__ . '/../../../core/db_connect.php';

$export_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$export_id) { http_response_code(400); exit('ID không hợp lệ'); }

$stmt = $pdo->prepare("
    SELECT sei.quantity, sei.price_at_export, pv.sku, p.name as product_name,
           GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') as variant_attributes
    FROM stock_export_items sei
    JOIN product_variants pv ON sei.variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    LEFT JOIN variant_values vv ON pv.id = vv.variant_id
    LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id
    LEFT JOIN attributes a ON av.attribute_id = a.id
    WHERE sei.export_id = ?
    GROUP BY sei.id
");
$stmt->execute([$export_id]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($items)) {
    echo '<p>Không có sản phẩm nào trong phiếu xuất này.</p>';
    exit;
}
?>
<table class="table table-sm">
   <thead>
      <tr>
         <th>Sản phẩm</th>
         <th>SKU</th>
         <th>Thuộc tính</th>
         <th class="text-center">Số lượng</th>
         <th class="text-end">Giá vốn lúc xuất</th>
      </tr>
   </thead>
   <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
         <td><?= htmlspecialchars($item['product_name']) ?></td>
         <td><?= htmlspecialchars($item['sku']) ?></td>
         <td><?= htmlspecialchars($item['variant_attributes']) ?></td>
         <td class="text-center"><?= $item['quantity'] ?></td>
         <td class="text-end"><?= number_format($item['price_at_export'], 0, ',', '.') ?>đ</td>
      </tr>
      <?php endforeach; ?>
   </tbody>
</table>