<?php

include 'templates/header.php';

// --- LOGIC LẤY DỮ LIỆU VÀ PHÂN TRANG ---
$limit = 6; // 6 bài viết trên mỗi trang
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, ['options' => ['default' => 1, 'min_range' => 1]]);
$offset = ($page - 1) * $limit;

// Đếm tổng số bài viết đã xuất bản
$total_posts = $pdo->query("SELECT COUNT(id) FROM posts WHERE status = 'published'")->fetchColumn();
$total_pages = ceil($total_posts / $limit);

// Lấy các bài viết cho trang hiện tại
$stmt = $pdo->prepare("
    SELECT p.title, p.slug, p.excerpt, p.featured_image, p.created_at, u.full_name as author_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.status = 'published'
    ORDER BY p.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$page_title = "Blog - Tin tức & Cẩm nang";
$page_description = "Khám phá các bài viết hữu ích về giấc ngủ, cách chọn chăn ga gối và các xu hướng mới nhất từ Khóm Bedding.";
?>

<div class="container my-5">
   <div class="text-center mb-5">
      <h1>Blog</h1>
      <p class="lead">Tin tức, cẩm nang và các câu chuyện từ Khóm Bedding</p>
   </div>

   <div class="row">
      <?php if (empty($posts)): ?>
      <p class="text-center">Chưa có bài viết nào.</p>
      <?php else: ?>
      <?php foreach ($posts as $post): ?>
      <div class="col-lg-4 col-md-6 mb-4">
         <div class="card h-100 post-card">
            <a href="/bai-viet/<?php echo htmlspecialchars($post['slug']); ?>.html">
               <img src="<?php echo htmlspecialchars($post['featured_image'] ?? '/assets/images/placeholder.png'); ?>"
                  class="card-img-top" alt="<?php echo htmlspecialchars($post['title']); ?>">
            </a>
            <div class="card-body d-flex flex-column">
               <h5 class="card-title">
                  <a href="/bai-viet/<?php echo htmlspecialchars($post['slug']); ?>.html"
                     class="text-dark text-decoration-none"><?php echo htmlspecialchars($post['title']); ?></a>
               </h5>
               <small class="text-muted mb-2">
                  Đăng bởi <?php echo htmlspecialchars($post['author_name']); ?> vào ngày
                  <?php echo date('d/m/Y', strtotime($post['created_at'])); ?>
               </small>
               <p class="card-text flex-grow-1"><?php echo htmlspecialchars($post['excerpt']); ?></p>
               <a href="/bai-viet/<?php echo htmlspecialchars($post['slug']); ?>.html" class="btn btn-link p-0">Đọc thêm
                  <i class="bi bi-arrow-right"></i></a>
            </div>
         </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
   </div>

   <?php if ($total_pages > 1): ?>
   <nav class="mt-4">
      <ul class="pagination justify-content-center">
         <?php for ($i = 1; $i <= $total_pages; $i++): ?>
         <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
            <a class="page-link" href="/blog.html?page=<?php echo $i; ?>"><?php echo $i; ?></a>
         </li>
         <?php endfor; ?>
      </ul>
   </nav>
   <?php endif; ?>
</div>

<?php include 'templates/footer.php'; ?>