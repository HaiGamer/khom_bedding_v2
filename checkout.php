<?php
session_start();
require_once __DIR__ . '/core/db_connect.php';

// --- BẢO MẬT ---
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_to'] = '/checkout.html';
    header('Location: /auth.html');
    exit();
}
if (empty($_SESSION['cart'])) {
    header('Location: /cart.html');
    exit();
}

$user_id = $_SESSION['user_id'];

// --- LẤY DỮ LIỆU ---
// 1. Lấy tất cả địa chỉ của người dùng
$stmt_addresses = $pdo->prepare("SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC");
$stmt_addresses->execute([$user_id]);
$addresses = $stmt_addresses->fetchAll(PDO::FETCH_ASSOC);

// 2. Lấy thông tin giỏ hàng để hiển thị tóm tắt
$cart_items_summary = [];
$subtotal = 0;
$cart_data = $_SESSION['cart'];

if (!empty($cart_data)) {
    $variant_ids = array_keys($cart_data);
    $placeholders = implode(',', array_fill(0, count($variant_ids), '?'));

    // === THAY ĐỔI 1: Sử dụng câu truy vấn đầy đủ giống như trang giỏ hàng ===
    $stmt_cart = $pdo->prepare("
        SELECT 
            pv.id, pv.sku, pv.price, p.name as product_name, p.slug as product_slug,
            COALESCE(pv.image_url, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_featured = 1 LIMIT 1)) AS image_url,
            GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR ', ') AS variant_attributes
        FROM product_variants pv 
        JOIN products p ON pv.product_id = p.id 
        LEFT JOIN variant_values vv ON pv.id = vv.variant_id
        LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id
        LEFT JOIN attributes a ON av.attribute_id = a.id
        WHERE pv.id IN ($placeholders)
        GROUP BY pv.id
    ");
    $stmt_cart->execute($variant_ids);
    $product_details = $stmt_cart->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($product_details as $detail) {
        $quantity = $cart_data[$detail['id']]['quantity'];
        $subtotal += $detail['price'] * $quantity;
        $cart_items_summary[] = array_merge($detail, ['quantity' => $quantity]);
    }
}


$page_title = "Thanh Toán";
include 'templates/header.php';
?>

<div class="container my-5">
   <form id="checkout-form">
      <div class="row">
         <div class="col-lg-7">
            <h2 class="mb-4">Thông tin thanh toán</h2>
            <div class="mb-4">
               <label for="address-selector" class="form-label">Chọn địa chỉ giao hàng:</label>
               <select class="form-select" id="address-selector">
                  <?php foreach($addresses as $address): ?>
                  <option value="<?php echo $address['id']; ?>" <?php echo $address['is_default'] ? 'selected' : ''; ?>
                     data-name="<?php echo htmlspecialchars($address['full_name']); ?>"
                     data-phone="<?php echo htmlspecialchars($address['phone_number']); ?>"
                     data-address="<?php echo htmlspecialchars($address['address_line']); ?>">
                     <?php echo htmlspecialchars($address['address_line']); ?>
                  </option>
                  <?php endforeach; ?>
                  <option value="new">Sử dụng địa chỉ mới...</option>
               </select>
            </div>
            <div id="shipping-details-form">
               <div class="row">
                  <div class="col-md-6 mb-3"><label for="checkout-name" class="form-label">Họ tên người
                        nhận</label><input type="text" class="form-control" id="checkout-name" name="customer_name"
                        required></div>
                  <div class="col-md-6 mb-3"><label for="checkout-phone" class="form-label">Số điện thoại</label><input
                        type="tel" class="form-control" id="checkout-phone" name="customer_phone" required></div>
               </div>
               <div class="mb-3"><label for="checkout-address" class="form-label">Địa chỉ chi tiết</label><input
                     type="text" class="form-control" id="checkout-address" name="customer_address" required></div>
            </div>
            <hr class="my-4">
            <div class="mb-4"><label for="order-notes" class="form-label">Ghi chú đơn hàng (tùy chọn)</label><textarea
                  class="form-control" id="order-notes" name="note" rows="3"></textarea></div>
         </div>

         <div class="col-lg-5">
            <div class="card checkout-summary-card">
               <div class="card-body">
                  <h4 class="card-title mb-4">Đơn hàng của bạn</h4>
                  <ul class="list-group list-group-flush">
                     <?php foreach ($cart_items_summary as $item): ?>
                     <li class="list-group-item d-flex">
                        <img
                           src="<?php echo htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder.png'); ?>"
                           alt="" class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                        <div class="flex-grow-1">
                           <div class="fw-bold"><?php echo htmlspecialchars($item['product_name']); ?></div>
                           <div class="text-muted small"><?php echo htmlspecialchars($item['variant_attributes']); ?>
                           </div>
                           <div class="text-muted small">SL: <?php echo $item['quantity']; ?> / SKU:
                              <?php echo htmlspecialchars($item['sku']); ?></div>

                        </div>
                        <div><?php echo number_format($item['price'] * $item['quantity'], 0, ',', '.'); ?>đ</div>
                     </li>
                     <?php endforeach; ?>
                  </ul>
                  <div class="d-flex justify-content-between pt-3 mt-3 border-top">
                     <span>Tạm tính</span>
                     <strong><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</strong>
                  </div>
                  <div class="d-flex justify-content-between fw-bold h5 mt-3">
                     <span>Tổng cộng</span>
                     <span><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</span>
                  </div>
                  <hr>
                  <h5 class="mb-3">Phương thức thanh toán</h5>
                  <div class="form-check">
                     <input class="form-check-input" type="radio" name="payment_method" id="payment-cod" value="COD"
                        checked>
                     <label class="form-check-label" for="payment-cod">Thanh toán khi nhận hàng (COD)</label>
                     <div class="text-muted small">
                        Bạn sẽ thanh toán tiền mặt cho nhân viên giao hàng khi nhận hàng tại địa chỉ đã cung cấp.</div>
                  </div>
                  <div class="form-check mt-3">
                     <input class="form-check-input" type="radio" name="payment_method" id="payment-bank"
                        value="Bank Transfer">
                     <label class="form-check-label" for="payment-bank">Chuyển khoản ngân hàng</label>
                  </div>
                  <div class="d-grid mt-4">
                     <button type="submit" class="btn btn-primary btn-lg">ĐẶT HÀNG</button>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </form>
</div>

<script src="/assets/js/checkout.js"></script>
<?php include 'templates/footer.php'; ?>