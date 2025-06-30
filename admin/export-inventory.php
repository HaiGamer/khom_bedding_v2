<?php
// Bảo mật và gọi các file cần thiết
session_start();
if (!isset($_SESSION['admin_id'])) { 
    exit('Unauthorized'); 
}
require_once __DIR__ . '/../core/db_connect.php';

// Lấy toàn bộ dữ liệu tồn kho (câu truy vấn không đổi)
$sql = "
    SELECT 
        p.name as product_name, pv.sku, 
        GROUP_CONCAT(CONCAT(a.name, ': ', av.value) ORDER BY a.id SEPARATOR ', ') as variant_attributes,
        pv.cost_price, pv.stock_quantity
    FROM products p
    JOIN product_variants pv ON p.id = pv.product_id
    LEFT JOIN variant_values vv ON pv.id = vv.variant_id
    LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id
    LEFT JOIN attributes a ON av.attribute_id = a.id
    WHERE pv.id IS NOT NULL
    GROUP BY pv.id
    ORDER BY p.name, pv.id;
";
$stmt = $pdo->query($sql);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- TẠO NỘI DUNG HTML CHO FILE EXCEL ---
// Bắt đầu buffer để chứa nội dung output
ob_start();
?>
<html xmlns:x="urn:schemas-microsoft-com:office:excel">

<head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
</head>

<body>
   <table>
      <thead>
         <tr>
            <th>Sản phẩm</th>
            <th>SKU</th>
            <th>Thuộc tính</th>
            <th>Giá vốn</th>
            <th>Tồn kho</th>
            <th>Tổng vốn</th>
         </tr>
      </thead>
      <tbody>
         <?php foreach ($data as $row): ?>
         <tr>
            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
            <td><?php echo htmlspecialchars($row['sku']); ?></td>
            <td><?php echo htmlspecialchars($row['variant_attributes']); ?></td>
            <td><?php echo $row['cost_price']; ?></td>
            <td><?php echo $row['stock_quantity']; ?></td>
            <td><?php echo $row['cost_price'] * $row['stock_quantity']; ?></td>
         </tr>
         <?php endforeach; ?>
      </tbody>
   </table>
</body>

</html>
<?php
$html = ob_get_clean(); // Lấy nội dung buffer và xóa nó đi

// --- GỬI FILE ĐẾN TRÌNH DUYỆT ---
$filename = 'bao_cao_ton_kho_' . date('Y-m-d') .'_'. date('His') . '.xls';

header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo $html; // Xuất nội dung HTML
exit();