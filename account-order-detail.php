<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth.html');
    exit();
}
$user_id = $_SESSION['user_id'];
$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$order_id) {
    header('Location: /account-orders.html');
    exit();
}

require_once __DIR__ . '/core/db_connect.php';

// --- BẢO MẬT: Lấy thông tin đơn hàng VÀ xác thực nó thuộc về người dùng đang đăng nhập ---
$stmt_order = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt_order->execute([$order_id, $user_id]);
$order = $stmt_order->fetch();

// Nếu không tìm thấy đơn hàng (hoặc nó không thuộc về user này), báo lỗi và thoát
if (!$order) {
    http_response_code(404);
    // Bạn có thể tạo một trang 404 đẹp hơn
    require_once __DIR__ . '/core/db_connect.php';
    include 'templates/header.php';
    exit('<h1>404 Not Found</h1><p>Đơn hàng không tồn tại hoặc bạn không có quyền truy cập.</p>');
    include 'templates/footer.php';
}


// --- Lấy thông tin các sản phẩm trong đơn hàng ---
$stmt_items = $pdo->prepare("
    SELECT
        oi.quantity,
        oi.price,
        p.name AS product_name,
        p.slug AS product_slug,
        pv.sku,
        -- Lấy ảnh của phiên bản, nếu không có thì lấy ảnh đại diện của sản phẩm
        COALESCE(pv.image_url, (SELECT image_url FROM product_images WHERE product_id = p.id AND is_featured = 1 LIMIT 1)) AS image_url,
        -- Gộp các thuộc tính của phiên bản thành một chuỗi
        GROUP_CONCAT(CONCAT(a.name, ': ', av.value) SEPARATOR '<br>') AS variant_attributes
    FROM order_items oi
    JOIN product_variants pv ON oi.variant_id = pv.id
    JOIN products p ON pv.product_id = p.id
    LEFT JOIN variant_values vv ON pv.id = vv.variant_id
    LEFT JOIN attribute_values av ON vv.attribute_value_id = av.id
    LEFT JOIN attributes a ON av.attribute_id = a.id
    WHERE oi.order_id = ?
    GROUP BY oi.id
");
$stmt_items->execute([$order_id]);
$order_items = $stmt_items->fetchAll();


// --- Lấy thông tin cơ bản của người dùng cho sidebar ---
$stmt_user = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

// Hàm tiện ích để lấy badge trạng thái (copy từ trang trước)
function get_status_badge($status) {
    // ... (code hàm này giống hệt trang account-orders.php)
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
            <a href="/account-orders.html" class="btn btn-sm btn-outline-secondary mb-4">
               <i class="bi bi-arrow-left"></i> Quay lại danh sách đơn hàng
            </a>
            <div class="d-flex justify-content-between align-items-center mb-3">
               <h2 class="mb-0">Chi Tiết Đơn Hàng #<?php echo $order['id']; ?></h2>
               <span class="badge fs-6 <?php echo $status_info['class']; ?>"><?php echo $status_info['text']; ?></span>
            </div>
            <p class="text-muted">Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>

            <div class="row">
               <div class="col-md-6">
                  <div class="card">
                     <div class="card-header"><strong>Địa chỉ giao hàng</strong></div>
                     <div class="card-body">
                        <p class="mb-1"><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></p>
                        <p class="mb-1"><?php echo htmlspecialchars($order['customer_phone']); ?></p>
                        <p class="mb-0"><?php echo htmlspecialchars($order['customer_address']); ?></p>
                     </div>
                  </div>
               </div>
               <div class="col-md-6">
                  <div class="card">
                     <div class="card-header"><strong>Thanh toán</strong></div>
                     <div class="card-body">
                        <p class="mb-1"><strong>Phương thức:</strong>
                           <?php echo htmlspecialchars($order['payment_method']); ?></p>
                        <p class="mb-0"><strong>Ghi chú:</strong>
                           <?php echo htmlspecialchars($order['note'] ?: 'Không có'); ?></p>
                     </div>
                  </div>
               </div>
            </div>

            <h4 class="mt-5 mb-3">Các sản phẩm trong đơn</h4>
            <div class="table-responsive">
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
                     <?php 
                            $subtotal = 0;
                            foreach ($order_items as $item): 
                                $line_total = $item['price'] * $item['quantity'];
                                $subtotal += $line_total;
                            ?>
                     <tr>
                        <td style="width: 80px;">
                           <img
                              src="<?php echo htmlspecialchars($item['image_url'] ?? '/assets/images/placeholder.png'); ?>"
                              class="img-fluid rounded" alt="">
                        </td>
                        <td>
                           <a href="/san-pham/<?php echo $item['product_slug']; ?>.html"
                              class="fw-bold text-decoration-none text-dark"><?php echo htmlspecialchars($item['product_name']); ?></a>
                           <div class="text-muted small">
                              <?php echo $item['variant_attributes']; ?>
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
                        <td colspan="4" class="text-end"><strong>Tổng cộng</strong></td>
                        <td class="text-end h5 fw-bold">
                           <?php echo number_format($order['order_total'], 0, ',', '.'); ?>đ</td>
                     </tr>
                  </tfoot>
               </table>
            </div>
         </main>
      </div>
   </div>
</div>

<?php include 'templates/footer.php'; ?>