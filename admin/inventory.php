<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Báo cáo Tồn kho";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// === THAY ĐỔI 1: Nâng cấp câu truy vấn để lấy thêm ảnh đại diện ===
$sql = "
    SELECT 
        p.id as product_id, p.name as product_name,
        pv.id as variant_id, pv.sku, pv.stock_quantity, pv.cost_price,
        -- Lấy ảnh đại diện của sản phẩm cha
        (SELECT image_url FROM product_images WHERE product_id = p.id AND is_featured = 1 LIMIT 1) as featured_image,
        -- Gộp các thuộc tính của phiên bản
        GROUP_CONCAT(CONCAT(a.name, ': ', av.value) ORDER BY a.id SEPARATOR ', ') as variant_attributes
    FROM products p
    LEFT JOIN product_variants pv ON p.id = pv.product_id
    LEFT JOIN variant_values vv ON pv.id = vv.variant_id
    LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id
    LEFT JOIN attributes a ON av.attribute_id = a.id
    WHERE pv.id IS NOT NULL -- Chỉ lấy các sản phẩm có ít nhất 1 phiên bản
    GROUP BY pv.id
    ORDER BY p.name, pv.id;
";
$stmt = $pdo->query($sql);
$all_variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// === THAY ĐỔI 2: Cập nhật lại vòng lặp để lưu ảnh đại diện ===
// Nhóm các phiên bản theo sản phẩm và tính tổng vốn
$products = [];
$grand_total_cost = 0; // Biến mới để tính tổng vốn
foreach($all_variants as $variant) {
    $product_id = $variant['product_id'];
    if (!isset($products[$product_id])) {
        $products[$product_id]['product_name'] = $variant['product_name'];
        $products[$product_id]['featured_image'] = $variant['featured_image'];
        $products[$product_id]['variants'] = [];
    }
    $products[$product_id]['variants'][] = $variant;
    // Cộng dồn vào tổng vốn
    $grand_total_cost += $variant['cost_price'] * $variant['stock_quantity'];
}
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Báo cáo Tồn kho</h1>
      <a href="/admin/export-inventory.php" class="btn btn-info text-white">
         <i class="bi bi-file-earmark-excel-fill me-2"></i>Xuất Excel
      </a>
   </div>

   <div class="row">
      <div class="col-12">
         <div class="card bg-light border-primary mb-4">
            <div class="card-body text-center">
               <h6 class="card-title text-primary">TỔNG VỐN TỒN KHO</h6>
               <p class="card-text fs-2 fw-bold"><?php echo number_format($grand_total_cost, 0, ',', '.'); ?>đ</p>
            </div>
         </div>
      </div>
   </div>

   <div class="card">
      <div class="card-body table-responsive">
         <table class="table table-hover align-middle">
            <thead class="table-light">
               <tr>
                  <th>Sản phẩm</th>
                  <th>Phiên bản (SKU)</th>
                  <th>Thuộc tính</th>
                  <th class="text-end">Giá vốn</th>
                  <th class="text-center">Tồn kho</th>
                  <th class="text-end">Tổng vốn tồn kho</th>
               </tr>
            </thead>
            <tbody id="inventory-table-body">
               <?php if (empty($products)): ?>
               <tr>
                  <td colspan="6" class="text-center">Không có sản phẩm nào để thống kê.</td>
               </tr>
               <?php else: ?>
               <?php foreach ($products as $product_id => $product_data): ?>
               <?php foreach ($product_data['variants'] as $index => $variant): ?>
               <tr class="variant-row">
                  <?php if ($index === 0): ?>
                  <td rowspan="<?php echo count($product_data['variants']); ?>" class="fw-bold product-name-cell">
                     <div class="d-flex align-items-center">
                        <img
                           src="<?php echo htmlspecialchars($product_data['featured_image'] ?? '/assets/images/placeholder.png'); ?>"
                           class="admin-product-thumbnail me-2">
                        <span><?php echo htmlspecialchars($product_data['product_name']); ?></span>
                     </div>
                  </td>
                  <?php endif; ?>

                  <td><?php echo htmlspecialchars($variant['sku']); ?></td>
                  <td><?php echo htmlspecialchars($variant['variant_attributes']); ?></td>
                  <td class="text-end cost-price-cell">
                     <?php echo number_format($variant['cost_price'], 0, ',', '.'); ?>đ</td>
                  <td class="text-center" style="width: 150px;">
                     <div class="input-group input-group-sm stock-update-group">
                        <input type="number" class="form-control stock-quantity-input"
                           value="<?php echo $variant['stock_quantity']; ?>"
                           data-variant-id="<?php echo $variant['variant_id']; ?>"
                           data-cost-price="<?php echo $variant['cost_price']; ?>">
                        <button class="btn btn-success btn-save-stock d-none" title="Lưu"><i
                              class="bi bi-check-lg"></i></button>
                        <button class="btn btn-secondary btn-cancel-stock d-none" title="Hủy"><i
                              class="bi bi-x-lg"></i></button>
                     </div>
                  </td>
                  <td class="text-end fw-bold total-value-cell">
                     <?php echo number_format($variant['cost_price'] * $variant['stock_quantity'], 0, ',', '.'); ?>đ
                  </td>
               </tr>
               <?php endforeach; ?>
               <?php endforeach; ?>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<script src="/admin/assets/js/admin-inventory.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>