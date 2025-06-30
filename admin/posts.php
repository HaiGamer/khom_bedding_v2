<?php
require_once __DIR__ . '/core/admin-guard.php';
require_once __DIR__ . '/../core/db_connect.php';
require_once __DIR__ . '/core/helpers.php';

$page_title = "Quản lý Bài viết";
include __DIR__ . '/templates/header.php';
include __DIR__ . '/templates/sidebar.php';

// --- LOGIC LẤY DỮ LIỆU, TÌM KIẾM VÀ PHÂN TRANG ---
$limit = 15;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;
$search_term = trim(filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

$sql = "
    SELECT p.id, p.title, p.status, p.created_at, p.updated_at, u.full_name as author_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
";
$count_sql = "SELECT COUNT(p.id) FROM posts p ";
$params = [];

if ($search_term) {
    $sql .= " WHERE p.title LIKE ? ";
    $count_sql .= " WHERE p.title LIKE ? ";
    $params[] = "%{$search_term}%";
}

$sql .= " ORDER BY p.updated_at DESC LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$posts = $stmt->fetchAll();

$count_params = $search_term ? ["%{$search_term}%"] : [];
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($count_params);
$total_posts = $count_stmt->fetchColumn();
$total_pages = ceil($total_posts / $limit);
?>

<div class="p-4">
   <div class="d-flex justify-content-between align-items-center mb-4">
      <h1 class="mb-0">Quản lý Bài viết</h1>
      <a href="/admin/post-edit.php" class="btn btn-primary">
         <i class="bi bi-plus-circle-fill me-2"></i>Thêm bài viết mới
      </a>
   </div>

   <div class="card mb-4">
      <div class="card-body">
         <form method="GET" class="d-flex">
            <input type="text" class="form-control me-2" name="search" placeholder="Nhập tiêu đề bài viết để tìm..."
               value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn btn-success">Tìm kiếm</button>
         </form>
      </div>
   </div>

   <div class="card">
      <div class="card-body table-responsive">
         <table class="table table-hover align-middle">
            <thead class="table-light">
               <tr>
                  <th>ID</th>
                  <th>Tiêu đề</th>
                  <th>Tác giả</th>
                  <th>Trạng thái</th>
                  <th>Ngày cập nhật</th>
                  <th class="text-end">Hành động</th>
               </tr>
            </thead>
            <tbody>
               <?php if (empty($posts)): ?>
               <tr>
                  <td colspan="6" class="text-center">Không tìm thấy bài viết nào.</td>
               </tr>
               <?php else: ?>
               <?php foreach ($posts as $post): ?>
               <tr>
                  <th scope="row"><?php echo $post['id']; ?></th>
                  <td><?php echo htmlspecialchars($post['title']); ?></td>
                  <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                  <td>
                     <?php if ($post['status'] === 'published'): ?>
                     <span class="badge bg-success">Đã xuất bản</span>
                     <?php else: ?>
                     <span class="badge bg-secondary">Bản nháp</span>
                     <?php endif; ?>
                  </td>
                  <td><?php echo date('d/m/Y H:i', strtotime($post['updated_at'])); ?></td>
                  <td class="text-end">
                     <a href="/admin/post-edit.php?id=<?php echo $post['id']; ?>"
                        class="btn btn-sm btn-outline-primary">Sửa</a>
                     <a href="#" class="btn btn-sm btn-outline-danger btn-delete-post"
                        data-id="<?php echo $post['id']; ?>">Xóa</a>
                  </td>
               </tr>
               <?php endforeach; ?>
               <?php endif; ?>
            </tbody>
         </table>
      </div>
   </div>

   <?php if ($total_pages > 1): ?>
   <nav class="mt-4">
      <ul class="pagination justify-content-center">
         <?php for ($i = 1; $i <= $total_pages; $i++): ?>
         <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link"
               href="?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search_term); ?>"><?php echo $i; ?></a>
         </li>
         <?php endfor; ?>
      </ul>
   </nav>
   <?php endif; ?>
</div>

<?php
// Chúng ta sẽ thêm file JS riêng cho trang này nếu cần các hành động AJAX (như xóa)
// <script src="/admin/assets/js/admin-posts.js"></script>
include __DIR__ . '/templates/footer.php';
?>