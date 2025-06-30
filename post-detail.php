<?php
// Bước 1: Kết nối CSDL trước tiên
require_once __DIR__ . '/core/db_connect.php';
// 1. Lấy slug sản phẩm từ URL
$slug = $_GET['slug'] ?? null;
if (!$slug) {
    header("Location: /");
    exit();
}
// Bước 2: Truy vấn sản phẩm dựa trên slug
$stmt2 = $pdo->prepare("
    SELECT 
        p.*
    FROM posts p 
    WHERE p.slug = ?
");
$stmt2->execute([$slug]);
$product2 = $stmt2->fetch(PDO::FETCH_ASSOC);

if (!$product2) {
    http_response_code(404);
    // Chúng ta vẫn include header và footer để trang lỗi có giao diện
    $page_title = "404 Not Found";
    include 'templates/header.php';
    echo "<div class='container text-center py-5'><h1>404 Not Found</h1><p>Sản phẩm không tồn tại.</p></div>";
    include 'templates/footer.php';
    exit();
}
// Bước 3: Định nghĩa các biến SEO động
$page_title = htmlspecialchars($product2['title']) . ' - Khóm Bedding';
$page_description = htmlspecialchars(strip_tags($product2['excerpt'] ?? ''));

include 'templates/header.php';

$slug = $_GET['slug'] ?? null;
if (!$slug) {
    http_response_code(404);
    exit('Trang không tồn tại.');
}

// Lấy thông tin bài viết, đảm bảo chỉ lấy bài đã xuất bản
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name as author_name
    FROM posts p
    JOIN users u ON p.user_id = u.id
    WHERE p.slug = ? AND p.status = 'published'
");
$stmt->execute([$slug]);
$post = $stmt->fetch();

if (!$post) {
    http_response_code(404);
    exit('Bài viết không tồn tại hoặc chưa được xuất bản.');
}

// Thiết lập SEO
$page_title = htmlspecialchars($post['title']);
$page_description = htmlspecialchars($post['excerpt']);
?>

<div class="container my-5">
   <div class="row justify-content-center">
      <div class="col-lg-8">
         <article class="post-detail">
            <header class="mb-4">
               <h1 class="fw-bolder mb-1"><?php echo htmlspecialchars($post['title']); ?></h1>
               <div class="text-muted fst-italic mb-2">
                  Đăng bởi <?php echo htmlspecialchars($post['author_name']); ?> vào ngày
                  <?php echo date('d F, Y', strtotime($post['created_at'])); ?>
               </div>
            </header>

            <?php if ($post['featured_image']): ?>
            <figure class="mb-4">
               <img class="img-fluid rounded" src="<?php echo htmlspecialchars($post['featured_image']); ?>"
                  alt="<?php echo htmlspecialchars($post['title']); ?>" />
            </figure>
            <?php endif; ?>

            <section class="mb-5 post-content">
               <?php echo $post['content']; // In ra nội dung HTML từ CKEditor ?>
            </section>
         </article>
      </div>

      <div class="col-lg-4">
         <div class="card mb-4">
            <div class="card-header">
               <h5 class="mb-0">Chia sẻ bài viết</h5>
            </div>
            <div class="card-body">
               <div class="d-flex justify-content-around">
                  <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode('https://yourwebsite.com/bai-viet/' . $post['slug'] . '.html'); ?>"
                     target="_blank" class="btn btn-primary">
                     <i class="bi bi-facebook"></i> Facebook
                  </a>
                  <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode('https://yourwebsite.com/bai-viet/' . $post['slug'] . '.html'); ?>"
                     target="_blank" class="btn btn-info">
                     <i class="bi bi-twitter"></i> Twitter
                  </a>
                  <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode('https://yourwebsite.com/bai-viet/' . $post['slug'] . '.html'); ?>"
                     target="_blank" class="btn btn-secondary">
                     <i class="bi bi-linkedin"></i> LinkedIn
                  </a>
               </div>
            </div>
         </div>

         <div class="card mb-4">
            <div class="card-header">
               <h5 class="mb-0">Bài viết liên quan</h5>
            </div>
            <div class="card-body">
               <?php
               // Lấy các bài viết liên quan (cùng danh mục hoặc cùng tác giả)
               $related_stmt = $pdo->prepare("
                   SELECT p.id, p.title, p.slug, p.featured_image
                   FROM posts p
                   WHERE p.id != ? AND p.status = 'published'
                   ORDER BY p.created_at DESC
                   LIMIT 5
               ");
               $related_stmt->execute([$post['id']]);
               $related_posts = $related_stmt->fetchAll();

               if ($related_posts): ?>
               <ul class="list-unstyled">
                  <?php foreach ($related_posts as $related_post): ?>
                  <li class="mb-2">
                     <a href="/bai-viet/<?php echo htmlspecialchars($related_post['slug']); ?>.html"
                        class="text-decoration-none text-dark">
                        <?php if ($related_post['featured_image']): ?>
                        <img src="<?php echo htmlspecialchars($related_post['featured_image']); ?>"
                           alt="<?php echo htmlspecialchars($related_post['title']); ?>" class="img-thumbnail me-2"
                           style="width: 80px; height: 60px;">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($related_post['title']); ?>
                     </a>
                  </li>
                  <?php endforeach; ?>
               </ul>
               <?php else: ?>
               <p>Không có bài viết liên quan.</p>
               <?php endif; ?>
            </div>
         </div>

      </div>
   </div>
</div>

<?php include 'templates/footer.php'; ?>