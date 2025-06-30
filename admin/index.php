<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';
require_once __DIR__ . '/core/helpers.php'; // Gọi file helpers

$page_title = "Dashboard";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// Lấy các thống kê nhanh
$today_revenue = $pdo->query("SELECT SUM(order_total) FROM orders WHERE status = 'completed' AND DATE(created_at) = CURDATE()")->fetchColumn();
$new_orders_today = $pdo->query("SELECT COUNT(id) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$pending_reviews = $pdo->query("SELECT COUNT(id) FROM reviews WHERE status = 'pending'")->fetchColumn();
$low_stock_products = $pdo->query("SELECT COUNT(id) FROM product_variants WHERE stock_quantity BETWEEN 1 AND 9")->fetchColumn();

// Lấy 10 đơn hàng mới nhất
$latest_orders = $pdo->query("SELECT id, customer_name, order_total, status FROM orders ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

// Lấy Top 5 sản phẩm bán chạy nhất trong tháng
$top_products = $pdo->query("
    SELECT p.id,p.name,p.slug, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN product_variants pv ON oi.variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'completed' AND o.created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="p-4">
   <h1 class="mb-4">Dashboard</h1>

   <div class="row">
      <div class="col-xl-3 col-md-6 mb-4">
         <div class="card text-white bg-success h-100">
            <div class="card-body">
               <div class="d-flex justify-content-between align-items-center">
                  <i class="bi bi-cash-coin fs-1"></i>
                  <div class="text-end">
                     <div class="fs-3 fw-bold"><?php echo number_format($today_revenue ?? 0, 0, ',', '.'); ?>đ</div>
                     <div>Doanh thu hôm nay</div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
         <div class="card text-white bg-primary h-100">
            <div class="card-body">
               <div class="d-flex justify-content-between">
                  <i class="bi bi-receipt fs-1"></i>
                  <div class="text-end">
                     <div class="fs-3 fw-bold"><?php echo $new_orders_today ?? 0; ?></div>
                     <div>Đơn hàng mới</div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
         <div class="card text-white bg-warning h-100">
            <div class="card-body">
               <div class="d-flex justify-content-between">
                  <i class="bi bi-star-half fs-1"></i>
                  <div class="text-end">
                     <div class="fs-3 fw-bold"><?php echo $pending_reviews ?? 0; ?></div>
                     <div>Đánh giá chờ duyệt</div>
                  </div>
               </div>
            </div>
         </div>
      </div>
      <div class="col-xl-3 col-md-6 mb-4">
         <div class="card text-white bg-danger h-100">
            <div class="card-body">
               <div class="d-flex justify-content-between">
                  <i class="bi bi-box-seam fs-1"></i>
                  <div class="text-end">
                     <div class="fs-3 fw-bold"><?php echo $low_stock_products ?? 0; ?></div>
                     <div>Sản phẩm sắp hết hàng</div>
                  </div>
               </div>
            </div>
         </div>
      </div>
   </div>

   <div class="row mt-4">
      <div class="col-lg-8 mb-4">
         <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap">
               <strong>Biểu đồ doanh thu</strong>
               <form id="revenue-filter-form" class="d-flex align-items-center gap-2 mt-2 mt-md-0">
                  <input type="date" name="start" class="form-control form-control-sm"
                     value="<?php echo date('Y-m-d', strtotime('-6 days')); ?>">
                  <span>đến</span>
                  <input type="date" name="end" class="form-control form-control-sm"
                     value="<?php echo date('Y-m-d'); ?>">
                  <button type="submit" class="btn btn-sm btn-primary">Lọc</button>
               </form>
            </div>
            <div class="card-body">
               <canvas id="revenueChart"></canvas>
            </div>
         </div>
      </div>

      <div class="col-lg-4 mb-4">
         <div class="card mb-4">
            <div class="card-header"><strong>10 Đơn hàng mới nhất</strong></div>
            <div class="card-body p-0">
               <ul class="list-group list-group-flush">
                  <?php if(empty($latest_orders)): ?>
                  <li class="list-group-item">Chưa có đơn hàng nào.</li>
                  <?php else: ?>
                  <?php foreach($latest_orders as $order): ?>
                  <?php $status_info = get_status_badge($order['status']); ?>
                  <a href="/admin/order-detail.php?id=<?= $order['id'] ?>"
                     class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                     <div>
                        <div class="fw-bold">#<?= $order['id'] ?> - <?= htmlspecialchars($order['customer_name']) ?>
                        </div>
                        <small><span
                              class="badge <?= $status_info['class'] ?>"><?= $status_info['text'] ?></span></small>
                     </div>
                     <div class="fw-bold text-nowrap"><?= number_format($order['order_total'], 0, ',', '.') ?>đ</div>
                  </a>
                  <?php endforeach; ?>
                  <?php endif; ?>
               </ul>
            </div>
         </div>
         <div class="card">
            <div class="card-header"><strong>Top 5 sản phẩm bán chạy (Tháng này)</strong></div>
            <div class="card-body p-0">
               <ul class="list-group list-group-flush">
                  <?php foreach($top_products as $product): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                     <?php echo htmlspecialchars($product['name']); ?>
                     <span class="badge bg-primary rounded-pill"><?php echo $product['total_sold']; ?></span>
                     <a href="/admin/product-edit.php?id=<?php echo htmlspecialchars($product['id']); ?>"
                        target="_blank"><button type="button" class="btn btn-danger btn-sm"
                           style="margin: 5px;">Edit</button></a>
                     <a href="/san-pham/<?php echo htmlspecialchars($product['slug']); ?>.html" target="_blank"><button
                           type="button" class="btn btn-success btn-sm">View</button></a>
                  </li>
                  <?php endforeach; ?>
               </ul>
            </div>
         </div>
      </div>
   </div>
</div>

<script src="/admin/assets/js/admin-dashboard.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>