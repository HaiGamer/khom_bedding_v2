<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';
require_once __DIR__ . '/core/helpers.php'; // <-- GỌI FILE HELPER MỚI

$page_title = "Quản lý Đánh giá";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// Logic lọc và phân trang
$status_filter = $_GET['status'] ?? 'pending';
$allowed_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'pending';
}

// Logic lọc và phân trang
$status_filter = $_GET['status'] ?? 'pending'; // Mặc định hiển thị các đánh giá đang chờ duyệt
$limit = 15;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;


$sql = "
    SELECT r.id, r.rating, r.comment, r.status, r.created_at, u.full_name, p.name as product_name, p.slug as product_slug
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.status = ?
    ORDER BY r.created_at DESC
"; // Bỏ LIMIT và OFFSET để phân trang sau
$stmt = $pdo->prepare($sql);
$stmt->execute([$status_filter]);
$reviews = $stmt->fetchAll();

// Hàm get_status_badge đã được chuyển vào file helpers.php
?>

<div class="p-4">
   <h1 class="mb-4">Quản lý Đánh giá</h1>

   <div class="mb-4">
      <a href="?status=pending"
         class="btn btn-sm <?= $status_filter == 'pending' ? 'btn-dark' : 'btn-outline-dark' ?>">Chờ duyệt</a>
      <a href="?status=approved"
         class="btn btn-sm <?= $status_filter == 'approved' ? 'btn-dark' : 'btn-outline-dark' ?>">Đã duyệt</a>
      <a href="?status=rejected"
         class="btn btn-sm <?= $status_filter == 'rejected' ? 'btn-dark' : 'btn-outline-dark' ?>">Đã từ chối</a>
   </div>

   <div class="card">
      <div class="card-body table-responsive">
         <table class="table table-hover align-middle">
            <thead>
               <tr>
                  <th>ID</th>
                  <th>Sản phẩm</th>
                  <th>Người đánh giá</th>
                  <th>Nội dung</th>
                  <th class="text-center">Trạng thái</th>
                  <th class="text-end">Hành động</th>
               </tr>
            </thead>
            <tbody id="reviews-table-body">
               <?php if (empty($reviews)): ?>
               <tr>
                  <td colspan="6" class="text-center">Không có đánh giá nào trong mục này.</td>
               </tr>
               <?php else: ?>
               <?php foreach ($reviews as $review): ?>
               <?php 
                                // Tạo badge trạng thái cho đánh giá
                                $review_status_info = ['class' => 'bg-secondary', 'text' => ucfirst($review['status'])];
                                if($review['status'] == 'approved') $review_status_info = ['class' => 'bg-success', 'text' => 'Đã duyệt'];
                                if($review['status'] == 'rejected') $review_status_info = ['class' => 'bg-danger', 'text' => 'Từ chối'];
                                if($review['status'] == 'pending') $review_status_info = ['class' => 'bg-warning text-dark', 'text' => 'Chờ duyệt'];
                            ?>
               <tr id="review-row-<?php echo $review['id']; ?>">
                  <td><?php echo $review['id']; ?></td>
                  <td><a href="/san-pham/<?php echo $review['product_slug']; ?>.html" target="_blank"
                        title="Xem sản phẩm"><?php echo htmlspecialchars($review['product_name']); ?></a></td>
                  <td><?php echo htmlspecialchars($review['full_name']); ?></td>
                  <td style="min-width: 300px;">
                     <div class="review-stars" data-rating="<?php echo $review['rating']; ?>"></div>
                     <p class="mb-0 small"><?php echo htmlspecialchars($review['comment']); ?></p>
                  </td>
                  <td class="text-center">
                     <span
                        class="badge <?php echo $review_status_info['class']; ?> status-badge"><?php echo $review_status_info['text']; ?></span>
                  </td>
                  <td class="text-end action-buttons">
                     <?php if ($review['status'] === 'pending'): ?>
                     <button class="btn btn-sm btn-success btn-approve" data-id="<?php echo $review['id']; ?>"
                        title="Duyệt"><i class="bi bi-check-lg"></i></button>
                     <button class="btn btn-sm btn-warning btn-reject" data-id="<?php echo $review['id']; ?>"
                        title="Từ chối"><i class="bi bi-x-lg"></i></button>
                     <?php endif; ?>
                     <button class="btn btn-sm btn-danger btn-delete" data-id="<?php echo $review['id']; ?>"
                        title="Xóa"><i class="bi bi-trash-fill"></i></button>
                  </td>
               </tr>
               <?php endforeach; ?>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
   </div>
</div>

<script src="/admin/assets/js/admin-reviews.js"></script>
<?php include __DIR__ . '/templates/footer.php'; ?>