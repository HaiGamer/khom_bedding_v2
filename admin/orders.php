<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';

$page_title = "Quản lý Đơn hàng";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// --- LOGIC LẤY DỮ LIỆU, LỌC VÀ PHÂN TRANG ---

// 1. Phân trang
$limit = 15; // Số đơn hàng trên mỗi trang
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;

// 2. Lọc theo trạng thái
$allowed_statuses = ['pending', 'processing', 'shipped', 'completed', 'cancelled'];
$status_filter = filter_input(INPUT_GET, 'status', FILTER_SANITIZE_SPECIAL_CHARS);
if (!$status_filter || !in_array($status_filter, $allowed_statuses)) {
    $status_filter = ''; // Nếu status không hợp lệ, hiển thị tất cả
}

// 3. Xây dựng câu truy vấn
$sql = "SELECT id, customer_name, order_total, status, created_at FROM orders";
$count_sql = "SELECT COUNT(id) FROM orders";
$params = [];

if ($status_filter) {
    $sql .= " WHERE status = ?";
    $count_sql .= " WHERE status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

// Thực thi câu truy vấn lấy đơn hàng
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Thực thi câu truy vấn đếm tổng số đơn hàng (cho phân trang)
$count_params = $status_filter ? [$status_filter] : [];
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_orders = $count_stmt->fetchColumn();
$total_pages = ceil($total_orders / $limit);

// Hàm tiện ích để lấy badge trạng thái
require_once __DIR__ . '/core/helpers.php';
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Quản lý Đơn hàng</h1>
   </div>

   <div class="mb-4">
      <a href="/admin/orders.php"
         class="btn btn-sm <?php echo !$status_filter ? 'btn-dark' : 'btn-outline-dark'; ?>">Tất cả</a>
      <?php foreach ($allowed_statuses as $status): ?>
      <a href="?status=<?php echo $status; ?>"
         class="btn btn-sm <?php echo $status_filter == $status ? 'btn-dark' : 'btn-outline-dark'; ?>">
         <?php echo ucfirst($status); ?>
      </a>
      <?php endforeach; ?>
   </div>

   <div class="card">
      <div class="card-body">
         <div class="table-responsive">
            <table class="table table-hover align-middle">
               <thead class="table-light">
                  <tr>
                     <th>Mã ĐH</th>
                     <th>Khách hàng</th>
                     <th>Ngày đặt</th>
                     <th>Trạng Thái</th>
                     <th class="text-end">Tổng tiền</th>
                     <th class="text-end">Hành động</th>
                  </tr>
               </thead>
               <tbody>
                  <?php if (empty($orders)): ?>
                  <tr>
                     <td colspan="6" class="text-center">Không tìm thấy đơn hàng nào.</td>
                  </tr>
                  <?php else: ?>
                  <?php foreach ($orders as $order): ?>
                  <?php $status_info = get_status_badge($order['status']); ?>
                  <tr>
                     <th scope="row">#<?php echo $order['id']; ?></th>
                     <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                     <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                     <td><span
                           class="badge <?php echo $status_info['class']; ?>"><?php echo $status_info['text']; ?></span>
                     </td>
                     <td class="text-end fw-bold"><?php echo number_format($order['order_total'], 0, ',', '.'); ?>đ</td>
                     <td class="text-end">
                        <a href="/admin/order-detail.php?id=<?php echo $order['id']; ?>"
                           class="btn btn-sm btn-primary">Xem</a>
                        <button class="btn btn-sm btn-outline-danger btn-delete-order"
                           data-id="<?= $order['id'] ?>">Xóa</button>
                     </td>
                  </tr>
                  <?php endforeach; ?>
                  <?php endif; ?>
               </tbody>
            </table>
         </div>
      </div>
   </div>

   <?php if ($total_pages > 1): ?>
   <nav class="mt-4">
      <ul class="pagination justify-content-center">
         <?php for ($i = 1; $i <= $total_pages; $i++): ?>
         <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link"
               href="?page=<?php echo $i; ?>&status=<?php echo htmlspecialchars($status_filter); ?>"><?php echo $i; ?></a>
         </li>
         <?php endfor; ?>
      </ul>
   </nav>
   <?php endif; ?>
</div>
<script src="/admin/assets/js/admin-orders.js"></script>
<?php
include __DIR__ . '/templates/footer.php';
?>