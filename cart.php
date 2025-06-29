<?php
session_start();
require_once __DIR__ . '/core/db_connect.php';

$cart_items = [];
$subtotal = 0;
$cart_data = $_SESSION['cart'] ?? [];

if (!empty($cart_data)) {
    // ... (Toàn bộ code PHP ở đầu file để lấy dữ liệu giữ nguyên, không thay đổi)
    $variant_ids = array_keys($cart_data);
    $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));
    $stmt = $pdo->prepare("SELECT pv.id, pv.price, pv.stock_quantity, p.name as product_name, p.slug as product_slug, COALESCE(pv.image_url, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_featured = 1 LIMIT 1)) AS image_url, GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') AS variant_attributes FROM product_variants pv JOIN products p ON pv.product_id = p.id LEFT JOIN variant_values vv ON pv.id = vv.variant_id LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id LEFT JOIN attributes a ON av.attribute_id = a.id WHERE pv.id IN ($placeholders) GROUP BY pv.id");
    $stmt->execute($variant_ids);
    $product_details = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($product_details as $detail) {
        $quantity = $cart_data[$detail['id']]['quantity'];
        $line_total = $detail['price'] * $quantity;
        $subtotal += $line_total;
        $cart_items[] = array_merge($detail, ['quantity' => $quantity, 'line_total' => $line_total]);
    }
}

$page_title = "Giỏ Hàng";
include 'templates/header.php';
?>

<div class="container my-5">
   <h1 class="mb-4">Giỏ Hàng Của Bạn</h1>
   <div class="row">
      <?php if (empty($cart_items)): ?>
      <div class="col-12 text-center">
         <p class="lead">Giỏ hàng của bạn đang trống.</p>
         <a href="/products.html" class="btn btn-primary">Tiếp tục mua sắm</a>
      </div>
      <?php else: ?>
      <div class="col-lg-8" id="cart-items-container">
         <div class="table-responsive d-none d-lg-block">
            <table class="table cart-table align-middle">
               <thead>
                  <tr>
                     <th colspan="2">Sản phẩm</th>
                     <th class="text-end">Đơn giá</th>
                     <th class="text-center">Số lượng</th>
                     <th class="text-end">Tạm tính</th>
                     <th></th>
                  </tr>
               </thead>
               <tbody>
                  <?php foreach ($cart_items as $item): ?>
                  <tr class="cart-item" data-variant-id="<?php echo $item['id']; ?>">
                     <td style="width: 100px;">
                        <img
                           src="<?php echo htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder.png'); ?>"
                           class="img-fluid rounded" alt="">
                     </td>
                     <td>
                        <a href="/san-pham/<?php echo $item['product_slug']; ?>.html"
                           class="fw-bold text-dark text-decoration-none"><?php echo htmlspecialchars($item['product_name']); ?></a>
                        <div class="text-muted small variant-attributes">
                           <?php echo htmlspecialchars($item['variant_attributes']); ?></div>
                     </td>
                     <td class="text-end price-per-item"><?php echo number_format($item['price'], 0, ',', '.'); ?>đ</td>
                     <td class="text-center" style="width: 130px;">
                        <input type="number" class="form-control form-control-sm text-center quantity-input"
                           value="<?php echo $item['quantity']; ?>" min="1"
                           max="<?php echo $item['stock_quantity']; ?>">
                     </td>
                     <td class="text-end fw-bold line-total">
                        <?php echo number_format($item['line_total'], 0, ',', '.'); ?>đ</td>
                     <td class="text-center">
                        <button class="btn btn-sm btn-outline-danger btn-remove-item">&times;</button>
                     </td>
                  </tr>
                  <?php endforeach; ?>
               </tbody>
            </table>
         </div>

         <div class="d-lg-none">
            <?php foreach ($cart_items as $item): ?>
            <div class="card mb-3 cart-item" data-variant-id="<?php echo $item['id']; ?>">
               <div class="row g-0">
                  <div class="col-4 col-md-3">
                     <img src="<?php echo htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder.png'); ?>"
                        class="img-fluid rounded-start h-100" style="object-fit: cover;">
                  </div>
                  <div class="col-8 col-md-9">
                     <div class="card-body">
                        <div class="d-flex justify-content-between">
                           <h5 class="card-title fs-6">
                              <a href="/san-pham/<?php echo $item['product_slug']; ?>.html"
                                 class="fw-bold text-dark text-decoration-none"><?php echo htmlspecialchars($item['product_name']); ?></a>
                           </h5>
                           <button class="btn btn-sm btn-outline-danger btn-remove-item border-0">&times;</button>
                        </div>
                        <p class="card-text text-muted small variant-attributes">
                           <?php echo htmlspecialchars($item['variant_attributes']); ?></p>
                        <p class="card-text price-per-item fw-bold">
                           <?php echo number_format($item['price'], 0, ',', '.'); ?>đ</p>
                        <div class="d-flex justify-content-between align-items-center">
                           <div style="width: 120px;">
                              <input type="number" class="form-control form-control-sm text-center quantity-input"
                                 value="<?php echo $item['quantity']; ?>" min="1"
                                 max="<?php echo $item['stock_quantity']; ?>">
                           </div>
                           <div class="fw-bold line-total">
                              <?php echo number_format($item['line_total'], 0, ',', '.'); ?>đ</div>
                        </div>
                     </div>
                  </div>
               </div>
            </div>
            <?php endforeach; ?>
         </div>
      </div>

      <div class="col-lg-4">
         <div class="card">
            <div class="card-body">
               <h5 class="card-title mb-4">Tóm tắt đơn hàng</h5>
               <div class="d-flex justify-content-between mb-3">
                  <span>Tạm tính</span>
                  <strong id="cart-subtotal"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</strong>
               </div>
               <hr>
               <div class="d-flex justify-content-between h5">
                  <span>Tổng cộng</span>
                  <strong id="cart-grandtotal"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</strong>
               </div>
               <div class="d-grid mt-4">
                  <a href="/checkout.html" class="btn btn-primary btn-lg">Tiến hành thanh toán</a>
               </div>
            </div>
         </div>
      </div>
      <?php endif; ?>
   </div>
</div>

<script src="/assets/js/cart.js"></script>
<?php include 'templates/footer.php'; ?>