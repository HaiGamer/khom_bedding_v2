<?php
session_start();
$order_id = filter_input(INPUT_GET, 'order_id', FILTER_VALIDATE_INT);

// Nếu không có mã đơn hàng, đơn giản là hiển thị một thông báo chung
$page_title = "Đặt Hàng Thành Công";
include 'templates/header.php';
?>

<div class="container my-5 text-center">
   <div class="py-5">
      <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
      <h1 class="mt-4">Cảm ơn bạn đã đặt hàng!</h1>
      <p class="lead">Đơn hàng của bạn đã được tiếp nhận thành công.</p>
      <?php if ($order_id): ?>
      <p>Mã đơn hàng của bạn là: <strong><a href="/account-order-detail.html?id=<?php echo $order_id; ?>"
               class="btn btn-outline-success"> #<?php echo $order_id; ?></a></strong></p>
      <?php endif; ?>
      <p class="text-danger lead">Chúng tôi sẽ liên hệ với bạn để xác nhận đơn hàng trong thời gian sớm nhất.</p>
      <div class="mt-4">
         <a href="/products.html" class="btn btn-primary">Tiếp tục mua sắm</a>
         <a href="/account-orders.html" class="btn btn-outline-secondary">Xem lịch sử đơn hàng</a>
      </div>
   </div>
</div>

<?php include 'templates/footer.php'; ?>