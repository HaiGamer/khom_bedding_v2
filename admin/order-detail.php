<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$order_id) { header('Location: /admin/orders.php'); exit(); }

// Lấy thông tin chính của đơn hàng
$stmt_order = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
$stmt_order->execute([$order_id]);
$order = $stmt_order->fetch(PDO::FETCH_ASSOC);
if (!$order) { exit('Đơn hàng không tồn tại.'); }

// Lấy thông tin các sản phẩm trong đơn hàng
$stmt_items = $pdo->prepare("SELECT oi.quantity, oi.price, p.name AS product_name, p.slug AS product_slug, pv.sku, COALESCE(pv.image_url, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_featured = 1 LIMIT 1)) AS image_url, GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') AS variant_attributes FROM order_items oi JOIN product_variants pv ON oi.variant_id = pv.id JOIN products p ON pv.product_id = p.id LEFT JOIN variant_values vv ON pv.id = vv.variant_id LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id LEFT JOIN attributes a ON av.attribute_id = a.id WHERE oi.order_id = ? GROUP BY oi.id");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll();

// Lấy thông tin người dùng cho sidebar
$stmt_user = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt_user->execute([$_SESSION['admin_id']]);
$user = $stmt_user->fetch();

function get_status_badge($status) {
    switch ($status) {
        case 'pending': return ['class' => 'bg-warning text-dark', 'text' => 'Chờ xử lý'];
        case 'processing': return ['class' => 'bg-info text-dark', 'text' => 'Đang xử lý'];
        case 'shipped': return ['class' => 'bg-primary', 'text' => 'Đang giao'];
        case 'completed': return ['class' => 'bg-success', 'text' => 'Đã hoàn thành'];
        case 'cancelled': return ['class' => 'bg-danger', 'text' => 'Đã hủy'];
        default: return ['class' => 'bg-secondary', 'text' => 'Không xác định'];
    }
}
$status_info = get_status_badge($order['status']);
$page_title = "Chi Tiết Đơn Hàng #" . $order['id'];
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
         <a href="/admin/orders.php" class="btn btn-sm btn-outline-secondary mb-2"><i class="bi bi-arrow-left"></i> Quay
            lại</a>
         <h1 class="mb-0">Đơn Hàng #<?php echo $order['id']; ?></h1>
         <p class="text-muted">Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
      </div>
      <form id="status-update-form" class="d-flex align-items-center gap-2">
         <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
         <select name="status" class="form-select w-auto">
            <option value="processing" <?php if($order['status'] == 'processing') echo 'selected';?>>Đang xử lý</option>
            <option value="shipped" <?php if($order['status'] == 'shipped') echo 'selected';?>>Đang giao</option>
            <option value="completed" <?php if($order['status'] == 'completed') echo 'selected';?>>Đã hoàn thành
            </option>
            <option value="cancelled" <?php if($order['status'] == 'cancelled') echo 'selected';?>>Đã hủy</option>
         </select>
         <button type="submit" class="btn btn-success">Cập nhật</button>
      </form>
   </div>
   <div id="main-alert" class="alert d-none"></div>

   <div class="row">
      <div class="col-lg-4 mb-4">
         <div class="card h-100">
            <div class="card-header d-flex justify-content-between">
               <strong>Thông tin khách hàng</strong>
               <button class="btn btn-sm btn-outline-secondary" id="btn-copy-shipping"><i class="bi bi-clipboard"></i>
                  Sao chép</button>
            </div>
            <div class="card-body" id="shipping-info-container">
            </div>
         </div>
      </div>
      <div class="col-lg-8 mb-4">
         <div class="card h-100">
            <div class="card-header"><strong>Các sản phẩm trong đơn</strong></div>
            <div class="card-body table-responsive">
               <table class="table order-items-table">
                  <thead class="table-light">
                     <tr>
                        <th colspan="2">Sản phẩm</th>
                        <th class="text-end">Đơn giá</th>
                        <th class="text-center">Số lượng</th>
                        <th class="text-end">Tạm tính</th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php foreach ($order_items as $item): ?>
                     <?php $line_total = $item['price'] * $item['quantity']; ?>
                     <tr>
                        <td style="width: 80px;">
                           <img
                              src="<?php echo htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder.png'); ?>"
                              class="img-fluid rounded" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                        </td>
                        <td>
                           <a href="/san-pham/<?php echo $item['product_slug']; ?>.html" target="_blank"
                              class="fw-bold text-decoration-none text-dark"><?php echo htmlspecialchars($item['product_name']); ?></a>
                           <div class="text-muted small">
                              <?php echo htmlspecialchars($item['variant_attributes']); ?>
                           </div>
                        </td>
                        <td class="text-end"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                        <td class="text-end fw-bold"><?php echo number_format($line_total, 0, ',', '.'); ?>đ</td>
                     </tr>
                     <?php endforeach; ?>
                  </tbody>
                  <tfoot>
                     <tr>
                        <td colspan="4" class="text-end border-top-2"><strong>Tổng cộng</strong></td>
                        <td class="text-end h5 fw-bold border-top-2">
                           <?php echo number_format($order['order_total'], 0, ',', '.'); ?>đ</td>
                     </tr>
                  </tfoot>
               </table>
            </div>
         </div>
      </div>
   </div>
</div>

<script>
const orderData = <?php echo json_encode($order, JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="/assets/js/admin-order-detail.js"></script>

<?php include __DIR__ . '/templates/footer.php'; ?>