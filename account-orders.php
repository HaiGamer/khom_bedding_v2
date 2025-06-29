<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth.html');
    exit();
}
$user_id = $_SESSION['user_id'];
require_once __DIR__ . '/core/db_connect.php';

// Lấy thông tin người dùng cho sidebar
$stmt_user = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Lấy tất cả đơn hàng của người dùng, mới nhất xếp trước
$stmt_orders = $pdo->prepare("SELECT id, created_at, order_total, status FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt_orders->execute([$user_id]);
$orders = $stmt_orders->fetchAll();

// Hàm tiện ích để chuyển đổi 'status' thành class và text của Bootstrap badge
function get_status_badge($status) {
    switch ($status) {
        case 'pending':
            return ['class' => 'bg-warning text-dark', 'text' => 'Chờ xử lý'];
        case 'processing':
            return ['class' => 'bg-info text-dark', 'text' => 'Đang xử lý'];
        case 'shipped':
            return ['class' => 'bg-primary', 'text' => 'Đang giao'];
        case 'completed':
            return ['class' => 'bg-success', 'text' => 'Đã hoàn thành'];
        case 'cancelled':
            return ['class' => 'bg-danger', 'text' => 'Đã hủy'];
        default:
            return ['class' => 'bg-secondary', 'text' => 'Không xác định'];
    }
}

$page_title = "Đơn Hàng Của Tôi";
include 'templates/header.php';
?>

<div class="container my-5">
   <div class="row">
      <div class="col-lg-3">
         <aside class="account-sidebar">
            <div class="account-user-info mb-4">
               <div class="account-avatar"><?php echo strtoupper(mb_substr($user['full_name'], 0, 1)); ?></div>
               <h5 class="mt-2 mb-0"><?php echo htmlspecialchars($user['full_name']); ?></h5>
               <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
            </div>
            <nav class="nav flex-column nav-pills">
               <a class="nav-link" href="/account.html"><i class="bi bi-person-circle me-2"></i>Thông tin tài khoản</a>
               <a class="nav-link" href="/account-addresses.html"><i class="bi bi-geo-alt-fill me-2"></i>Sổ địa chỉ</a>
               <a class="nav-link active" href="/account-orders.html"><i class="bi bi-box-seam-fill me-2"></i>Đơn hàng
                  của tôi</a>
               <a class="nav-link" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Đăng xuất</a>
            </nav>
         </aside>
      </div>

      <div class="col-lg-9">
         <main class="account-content">
            <h2 class="mb-4">Đơn Hàng Của Tôi</h2>

            <div class="table-responsive">
               <table class="table table-hover align-middle">
                  <thead class="table-light">
                     <tr>
                        <th scope="col">Mã Đơn Hàng</th>
                        <th scope="col">Ngày Đặt</th>
                        <th scope="col">Tổng Tiền</th>
                        <th scope="col">Trạng Thái</th>
                        <th scope="col"></th>
                     </tr>
                  </thead>
                  <tbody>
                     <?php if (empty($orders)): ?>
                     <tr>
                        <td colspan="5" class="text-center">Bạn chưa có đơn hàng nào.</td>
                     </tr>
                     <?php else: ?>
                     <?php foreach ($orders as $order): ?>
                     <?php $status_info = get_status_badge($order['status']); ?>
                     <tr>
                        <th scope="row">#<?php echo $order['id']; ?></th>
                        <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                        <td><?php echo number_format($order['order_total'], 0, ',', '.'); ?>đ</td>
                        <td>
                           <span class="badge <?php echo $status_info['class']; ?>">
                              <?php echo $status_info['text']; ?>
                           </span>
                        </td>
                        <td class="text-end">
                           <a href="/account-order-detail.html?id=<?php echo $order['id']; ?>"
                              class="btn btn-sm btn-outline-primary">Xem chi tiết</a>
                        </td>
                     </tr>
                     <?php endforeach; ?>
                     <?php endif; ?>
                  </tbody>
               </table>
            </div>
         </main>
      </div>
   </div>
</div>

<?php include 'templates/footer.php'; ?>