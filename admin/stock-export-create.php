<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Tạo Phiếu Xuất Kho";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// --- LOGIC LẤY DANH SÁCH SẢN PHẨM ĐỂ BROWSE ---
$sort_order = $_GET['sort'] ?? 'name_asc';
$limit = 100; // Số lượng sản phẩm hiển thị mỗi trang
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;

// Xây dựng phần ORDER BY
$order_by_sql = " ORDER BY p.name ASC ";
if ($sort_order === 'sales_desc') {
    $order_by_sql = " ORDER BY total_sold DESC, p.name ASC ";
}

// Lấy tất cả sản phẩm và phiên bản, kèm theo số lượng đã bán để sắp xếp
$sql = "
    SELECT 
        p.name as product_name,
        pv.id as variant_id, pv.sku, pv.stock_quantity,
        GROUP_CONCAT(CONCAT(a.name, ': ', av.value) ORDER BY a.id SEPARATOR ', ') as variant_attributes,
        (SELECT SUM(oi.quantity) FROM order_items oi WHERE oi.variant_id = pv.id) as total_sold
    FROM products p
    JOIN product_variants pv ON p.id = pv.product_id
    LEFT JOIN variant_values vv ON pv.id = vv.variant_id
    LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id
    LEFT JOIN attributes a ON av.attribute_id = a.id
    GROUP BY pv.id
    {$order_by_sql}
    LIMIT ? OFFSET ?
";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$all_variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Đếm tổng số phiên bản để phân trang
$total_variants = $pdo->query("SELECT COUNT(id) FROM product_variants")->fetchColumn();
$total_pages = ceil($total_variants / $limit);
?>

<div class="p-4">
   <h1 class="mb-4">Tạo Phiếu Xuất Kho Thủ Công</h1>
   <div class="row">
      <div class="col-lg-7">
         <div class="card">
            <div class="card-header"><strong>Trình duyệt sản phẩm</strong></div>
            <div class="card-body">
               <div class="mb-3">
                  <label for="variant-search" class="form-label">Tìm kiếm nhanh (theo Tên hoặc SKU)</label>
                  <div class="position-relative">
                     <input type="text" class="form-control" id="variant-search" autocomplete="off"
                        placeholder="Gõ để tìm...">
                     <div id="search-results" class="list-group position-absolute w-100" style="z-index: 1000;"></div>
                  </div>
               </div>
               <hr>
               <div class="d-flex justify-content-between align-items-center mb-2">
                  <h6 class="mb-0">Tất cả sản phẩm</h6>
                  <form method="GET" id="sort-form" class="d-flex gap-2 align-items-center">
                     <label for="sort-select" class="form-label mb-0 small">Sắp xếp:</label>
                     <select name="sort" id="sort-select" class="form-select form-select-sm w-auto">
                        <option value="name_asc" <?php if($sort_order == 'name_asc') echo 'selected' ?>>Theo tên A-Z
                        </option>
                        <option value="sales_desc" <?php if($sort_order == 'sales_desc') echo 'selected' ?>>Theo lượt
                           bán</option>
                     </select>
                  </form>
               </div>
               <div class="table-responsive">
                  <table class="table table-sm">
                     <tbody id="all-products-list">
                        <?php foreach($all_variants as $variant): ?>
                        <tr>
                           <td>
                              <strong><?php echo htmlspecialchars($variant['product_name']); ?></strong><br>
                              <small
                                 class="text-muted"><?php echo htmlspecialchars($variant['variant_attributes']); ?></small>
                           </td>
                           <td>SKU: <?php echo htmlspecialchars($variant['sku']); ?></td>
                           <td>Tồn: <?php echo $variant['stock_quantity']; ?></td>
                           <td>
                              <button class="btn btn-sm btn-outline-primary btn-add-to-slip"
                                 data-variant='<?php echo json_encode($variant, JSON_UNESCAPED_UNICODE); ?>'>Thêm</button>
                           </td>
                        </tr>
                        <?php endforeach; ?>
                     </tbody>
                  </table>
               </div>
               <?php if ($total_pages > 1): ?>
               <nav>
                  <ul class="pagination pagination-sm justify-content-center">
                     <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                     <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                        <a class="page-link"
                           href="?page=<?php echo $i; ?>&sort=<?php echo $sort_order; ?>"><?php echo $i; ?></a>
                     </li>
                     <?php endfor; ?>
                  </ul>
               </nav>
               <?php endif; ?>
            </div>
         </div>
      </div>
      <div class="col-lg-5">
         <form id="stock-export-form">
            <div class="card position-sticky" style="top: 20px;">
               <div class="card-header"><strong>Chi tiết phiếu xuất</strong></div>
               <div class="card-body">
                  <div class="mb-3">
                     <label for="export-note" class="form-label">Ghi chú</label>
                     <textarea class="form-control" id="export-note" name="note" rows="2"
                        placeholder="VD: Xuất hàng mẫu, hàng tặng..."></textarea>
                  </div>
                  <h6 class="mb-3">Danh sách sản phẩm xuất kho</h6>
                  <div class="table-responsive">
                     <table class="table">
                        <thead class="table-light">
                           <tr>
                              <th>Sản phẩm</th>
                              <th style="width: 120px;">Số lượng</th>
                              <th></th>
                           </tr>
                        </thead>
                        <tbody id="export-items-table-body">
                           <tr class="placeholder-row">
                              <td colspan="3" class="text-center text-muted">Chưa có sản phẩm nào</td>
                           </tr>
                        </tbody>
                     </table>
                  </div>
               </div>
               <div class="card-footer text-end">
                  <button type="submit" class="btn btn-primary btn-lg">Tạo Phiếu Xuất Kho</button>
               </div>
            </div>
         </form>
      </div>
   </div>
</div>

<script src="/admin/assets/js/admin-stock-export.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>